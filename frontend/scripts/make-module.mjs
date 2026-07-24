#!/usr/bin/env node
/**
 * Frontend CRUD module generator — mirrors the backend's
 * `backend/app/Console/Commands/MakeModuleCommand.php` architecture:
 * schema-driven, stub-template rendering via render(stubName, tokens) reading
 * `stubs/module/{name}.stub` files with `{{ token }}` placeholders,
 * --dry-run, --force, and full rollback-on-failure via before/after content
 * snapshots.
 *
 * Usage:
 *   node scripts/make-module.mjs Category
 *   node scripts/make-module.mjs Category --schema=custom/path.yaml
 *   node scripts/make-module.mjs Category --dry-run
 *   node scripts/make-module.mjs Category --force
 *
 * By default reads ../backend/module-schemas/{Name}.yaml — the SAME schema
 * file the backend's `php artisan make:module` reads, so one YAML file is
 * the single source of truth for both stacks. See that directory for the
 * schema shape. Supported field types:
 * string, text, integer, "decimal:P,S", boolean, date, datetime. Only
 * `belongsTo` relations get full frontend support (select input, options
 * composable import, guid payload field); hasMany/hasOne/belongsToMany are
 * recorded as a type-only placeholder field with a warning printed to the
 * console — same spirit as the backend's belongsToMany limitation.
 */

import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { parse as parseYaml } from 'yaml'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const FRONTEND_ROOT = path.resolve(__dirname, '..')

const FIELD_TYPES = ['string', 'text', 'integer', 'boolean', 'date', 'datetime']
const RELATION_TYPES = ['belongsTo', 'hasMany', 'hasOne', 'belongsToMany']
const IDENTIFIER_PATTERN = /^[A-Za-z][A-Za-z0-9_-]*$/
const DECIMAL_TYPE_PATTERN = /^decimal:(\d+),(\d+)$/

// ---------------------------------------------------------------------------
// String helpers (lightweight equivalents of Laravel's Str:: helpers — this
// is a plain-English approximation, not a full inflector; irregular plurals
// beyond the y->ies / s|x|z|ch|sh->es / default->s rules are not handled).
// ---------------------------------------------------------------------------

function pascalCase(input) {
  return input
    .replace(/[_-]+/g, ' ')
    .split(' ')
    .filter(Boolean)
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join('')
}

function camelCase(pascal) {
  return pascal.charAt(0).toLowerCase() + pascal.slice(1)
}

function kebabCase(pascalOrCamel) {
  return pascalOrCamel
    .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
    .replace(/([A-Z]+)([A-Z][a-z])/g, '$1-$2')
    .toLowerCase()
}

function snakeCase(pascalOrCamel) {
  return kebabCase(pascalOrCamel).replace(/-/g, '_')
}

function pluralize(word) {
  if (/[^aeiou]y$/i.test(word)) return word.slice(0, -1) + 'ies'
  if (/(s|x|z|ch|sh)$/i.test(word)) return word + 'es'
  return word + 's'
}

function humanize(name) {
  const spaced = name.replace(/_/g, ' ').trim()
  return spaced.charAt(0).toUpperCase() + spaced.slice(1)
}

// ---------------------------------------------------------------------------
// CLI arg parsing
// ---------------------------------------------------------------------------

function parseArgs(argv) {
  const args = { _: [], dryRun: false, force: false, schema: null }
  for (const arg of argv) {
    if (arg === '--dry-run') args.dryRun = true
    else if (arg === '--force') args.force = true
    else if (arg.startsWith('--schema=')) args.schema = arg.slice('--schema='.length)
    else args._.push(arg)
  }
  return args
}

function fail(message) {
  console.error(`Error: ${message}`)
  process.exit(1)
}

function warn(message) {
  console.warn(`Warning: ${message}`)
}

// ---------------------------------------------------------------------------
// Name derivation
// ---------------------------------------------------------------------------

function deriveNames(rawName) {
  const studlyName = pascalCase(rawName)
  const studlyPlural = pluralize(studlyName)
  const camelName = camelCase(studlyName)
  const camelPlural = camelCase(studlyPlural)
  const kebabName = kebabCase(studlyName)
  const kebabPlural = kebabCase(studlyPlural)
  const snakeName = snakeCase(studlyName)
  // Same value as studlyName, kept as its own token because several stubs
  // (composables, routes, i18n) use it as a human-facing label rather than
  // a type/identifier — derived once here instead of per builder function.
  const entityLabel = studlyName
  const entityLabelLower = entityLabel.toLowerCase()

  return {
    studlyName,
    studlyPlural,
    camelName,
    camelPlural,
    kebabName,
    kebabPlural,
    snakeName,
    entityLabel,
    entityLabelLower,
  }
}

// ---------------------------------------------------------------------------
// Schema validation & normalization
// ---------------------------------------------------------------------------

function validateSchemaShape(rawFields, rawRelations) {
  for (const [index, field] of rawFields.entries()) {
    if (typeof field !== 'object' || field === null || !field.name || !field.type) {
      return `Field #${index} in the schema must be a mapping with at least 'name' and 'type'.`
    }
    if (!IDENTIFIER_PATTERN.test(String(field.name))) {
      return `Invalid field name '${field.name}'. Use letters, digits or '_' only, starting with a letter.`
    }
    const type = String(field.type)
    if (type.startsWith('decimal:')) {
      if (!DECIMAL_TYPE_PATTERN.test(type)) {
        return `Invalid decimal type '${type}' for field '${field.name}'. Expected format: decimal:P,S (e.g. decimal:8,2).`
      }
    } else if (!FIELD_TYPES.includes(type)) {
      return `Unsupported field type '${type}' for field '${field.name}'. Supported: ${FIELD_TYPES.join(', ')}, decimal:P,S`
    }
  }

  for (const [index, relation] of rawRelations.entries()) {
    if (typeof relation !== 'object' || relation === null || !relation.name || !relation.type || !relation.model) {
      return `Relation #${index} in the schema must be a mapping with 'name', 'type' and 'model'.`
    }
    if (!IDENTIFIER_PATTERN.test(String(relation.name))) {
      return `Invalid relation name '${relation.name}'. Use letters, digits or '_' only, starting with a letter.`
    }
    if (!IDENTIFIER_PATTERN.test(String(relation.model))) {
      return `Invalid relation model '${relation.model}' for relation '${relation.name}'. Must be a valid class name.`
    }
    if (!RELATION_TYPES.includes(relation.type)) {
      return `Unsupported relation type '${relation.type}' for relation '${relation.name}'. Supported: ${RELATION_TYPES.join(', ')}`
    }
  }

  return null
}

function normalizeFields(rawFields) {
  return rawFields.map((f) => ({
    name: f.name,
    type: String(f.type),
    nullable: Boolean(f.nullable ?? false),
    label: f.label ?? null,
  }))
}

function normalizeRelations(rawRelations) {
  return rawRelations.map((r) => ({
    name: r.name,
    type: String(r.type),
    model: String(r.model),
    nullable: Boolean(r.nullable ?? false),
    labelField: r.labelField ?? 'name',
  }))
}

function fieldLabel(field) {
  return field.label || humanize(field.name)
}

function relationLabel(rel) {
  return humanize(rel.model)
}

function mapFieldTsType(type) {
  if (type.startsWith('decimal:')) return 'string'
  switch (type) {
    case 'string':
    case 'text':
    case 'date':
    case 'datetime':
      return 'string'
    case 'integer':
      return 'number'
    case 'boolean':
      return 'boolean'
    default:
      return 'string'
  }
}

function parseDecimal(type) {
  const m = type.match(DECIMAL_TYPE_PATTERN)
  return { precision: Number(m[1]), scale: Number(m[2]) }
}

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

function render(stubName, tokens) {
  const stubPath = path.join(FRONTEND_ROOT, 'stubs', 'module', `${stubName}.stub`)
  let content = fs.readFileSync(stubPath, 'utf8')
  for (const [key, value] of Object.entries(tokens)) {
    content = content.split(`{{ ${key} }}`).join(value ?? '')
  }
  return content
}

/** Collapses 3+ consecutive newlines into 2 — cosmetic cleanup for the
 * blank lines left behind by empty conditional tokens. */
function normalizeBlankLines(content) {
  return content.replace(/\n{3,}/g, '\n\n')
}

function ensureDirectoryExists(filePath) {
  fs.mkdirSync(path.dirname(filePath), { recursive: true })
}

function put(filePath, content) {
  ensureDirectoryExists(filePath)
  fs.writeFileSync(filePath, normalizeBlankLines(content), 'utf8')
}

// ---------------------------------------------------------------------------
// Field/relation partitioning helpers
// ---------------------------------------------------------------------------

function belongsToRelations(relations) {
  return relations.filter((r) => r.type === 'belongsTo')
}

function otherRelations(relations) {
  return relations.filter((r) => r.type !== 'belongsTo')
}

function textualFields(fields) {
  return fields.filter((f) => f.type === 'string' || f.type === 'text')
}

function firstTextualFieldName(fields) {
  const f = textualFields(fields)[0]
  return f ? f.name : null
}

// ---------------------------------------------------------------------------
// types/{name}.types.ts
// ---------------------------------------------------------------------------

function buildTypesTokens(names, fields, relations, softDeletes) {
  const belongsTo = belongsToRelations(relations)
  const others = otherRelations(relations)

  const itemLines = []
  for (const f of fields) {
    const suffix = f.nullable ? ' | null' : ''
    itemLines.push(`  ${f.name}: ${mapFieldTsType(f.type)}${suffix}`)
  }
  for (const r of belongsTo) {
    // Guid only, no label, no nested object — mirrors what the backend
    // Resource stub actually emits ('{guidKey}' => $this->{method}?->guid).
    const key = `${snakeCase(r.name)}_guid`
    itemLines.push(r.nullable ? `  ${key}?: string` : `  ${key}: string`)
  }
  for (const r of others) {
    itemLines.push(
      `  // TODO: '${r.name}' (${r.type} -> ${r.model}) is not represented in generated UI — documented generator limitation, wire up manually if needed.`,
    )
  }

  const createLines = []
  const updateLines = []
  for (const f of fields) {
    const ts = mapFieldTsType(f.type)
    const nullSuffix = f.nullable ? ' | null' : ''
    createLines.push(f.nullable ? `  ${f.name}?: ${ts}${nullSuffix}` : `  ${f.name}: ${ts}`)
    updateLines.push(`  ${f.name}?: ${ts}${nullSuffix}`)
  }
  for (const r of belongsTo) {
    const key = `${snakeCase(r.name)}_guid`
    const nullSuffix = r.nullable ? ' | null' : ''
    createLines.push(r.nullable ? `  ${key}?: string${nullSuffix}` : `  ${key}: string`)
    updateLines.push(`  ${key}?: string${nullSuffix}`)
  }

  const hasSearch = firstTextualFieldName(fields) !== null

  return {
    studlyName: names.studlyName,
    itemFields: itemLines.join('\n'),
    softDeleteField: softDeletes ? '  deleted_at: string | null' : '',
    createFields: createLines.join('\n'),
    updateFields: updateLines.join('\n'),
    filterSearchParam: hasSearch ? '  search?: string' : '',
  }
}

// ---------------------------------------------------------------------------
// api/{name}.api.ts
// ---------------------------------------------------------------------------

function buildApiTokens(names) {
  return {
    studlyName: names.studlyName,
    studlyPlural: names.studlyPlural,
    kebabName: names.kebabName,
    kebabPlural: names.kebabPlural,
  }
}

// ---------------------------------------------------------------------------
// validators/{name}.validator.ts
// ---------------------------------------------------------------------------

function zodFieldLine(field, { partial }) {
  const label = fieldLabel(field)
  let base

  if (field.type === 'string') {
    base = field.nullable
      ? `z.string().max(255, 'Máximo 255 caracteres').optional().nullable()`
      : `z.string().min(1, 'El campo ${label} es requerido').max(255, 'Máximo 255 caracteres')`
  } else if (field.type === 'text') {
    base = field.nullable
      ? `z.string().max(5000, 'Máximo 5000 caracteres').optional().nullable()`
      : `z.string().min(1, 'El campo ${label} es requerido').max(5000, 'Máximo 5000 caracteres')`
  } else if (field.type === 'integer') {
    base = `z.coerce.number({ message: 'El campo ${label} es requerido' }).int('Debe ser un número entero')`
    if (field.nullable) base += '.optional().nullable()'
  } else if (field.type.startsWith('decimal:')) {
    const { scale } = parseDecimal(field.type)
    const regex = `/^\\d+(\\.\\d{1,${scale}})?$/`
    base = field.nullable
      ? `z.string().regex(${regex}, 'Debe ser un valor numérico válido').optional().nullable()`
      : `z.string().min(1, 'El campo ${label} es requerido').regex(${regex}, 'Debe ser un valor numérico válido')`
  } else if (field.type === 'boolean') {
    base = 'z.boolean()'
    if (field.nullable) base += '.optional()'
  } else {
    // date | datetime
    base = field.nullable ? `z.string().optional().nullable()` : `z.string().min(1, 'El campo ${label} es requerido')`
  }

  if (partial && !base.includes('.optional()')) base += '.optional()'

  return `  ${field.name}: ${base},`
}

function zodRelationLine(rel, { partial }) {
  const key = `${snakeCase(rel.name)}_guid`
  const label = relationLabel(rel)
  let base = `z.string().uuid('Seleccioná un/a ${label} válido/a')`
  if (rel.nullable) base += '.optional().nullable()'
  if (partial && !base.includes('.optional()')) base += '.optional()'
  return `  ${key}: ${base},`
}

function buildValidatorTokens(names, fields, relations) {
  const belongsTo = belongsToRelations(relations)

  const createLines = [
    ...fields.map((f) => zodFieldLine(f, { partial: false })),
    ...belongsTo.map((r) => zodRelationLine(r, { partial: false })),
  ]
  const updateLines = [
    ...fields.map((f) => zodFieldLine(f, { partial: true })),
    ...belongsTo.map((r) => zodRelationLine(r, { partial: true })),
  ]

  return {
    studlyName: names.studlyName,
    zodCreateFields: createLines.join('\n'),
    zodUpdateFields: updateLines.join('\n'),
  }
}

// ---------------------------------------------------------------------------
// stores/{name}-ui.store.ts
// ---------------------------------------------------------------------------

function buildStoreTokens(names, fields) {
  const hasSearch = firstTextualFieldName(fields) !== null

  return {
    studlyName: names.studlyName,
    studlyPlural: names.studlyPlural,
    camelPlural: names.camelPlural,
    kebabName: names.kebabName,
    kebabPlural: names.kebabPlural,
    storeDefaultFilters: hasSearch ? `    search: '',` : '',
    storeResetFilters: hasSearch ? `    filters.search = ''` : '',
  }
}

// ---------------------------------------------------------------------------
// composables
// ---------------------------------------------------------------------------

function buildComposableListTokens(names) {
  return {
    studlyName: names.studlyName,
    studlyPlural: names.studlyPlural,
    camelPlural: names.camelPlural,
    kebabName: names.kebabName,
    kebabPlural: names.kebabPlural,
  }
}

function buildComposableGetTokens(names) {
  return {
    studlyName: names.studlyName,
    camelName: names.camelName,
    kebabName: names.kebabName,
    kebabPlural: names.kebabPlural,
  }
}

function buildComposableCreateTokens(names) {
  return {
    studlyName: names.studlyName,
    kebabName: names.kebabName,
    kebabPlural: names.kebabPlural,
    camelPlural: names.camelPlural,
    entityLabel: names.entityLabel,
    entityLabelLower: names.entityLabelLower,
  }
}

function buildComposableUpdateTokens(names) {
  return {
    studlyName: names.studlyName,
    kebabName: names.kebabName,
    kebabPlural: names.kebabPlural,
    camelPlural: names.camelPlural,
    camelName: names.camelName,
    entityLabel: names.entityLabel,
    entityLabelLower: names.entityLabelLower,
  }
}

function buildComposableDeleteTokens(names, fields) {
  const titleField = firstTextualFieldName(fields) ?? 'guid'
  return {
    studlyName: names.studlyName,
    kebabName: names.kebabName,
    kebabPlural: names.kebabPlural,
    camelPlural: names.camelPlural,
    entityLabel: names.entityLabel,
    entityLabelLower: names.entityLabelLower,
    titleField,
  }
}

function buildComposableFiltersTokens(names, fields) {
  const hasSearch = firstTextualFieldName(fields) !== null

  const filtersImports = hasSearch
    ? `import { toRef, watch } from 'vue'\nimport type { Ref } from 'vue'\nimport { useDebounce } from '@/core/composables/useDebounce'`
    : ''
  const filtersBody = hasSearch
    ? `\n  const searchRef = toRef(store.filters, 'search')\n  const debouncedSearch = useDebounce(searchRef as Ref<string>, 400)\n\n  watch(() => store.filters.search, () => {\n    store.filters.page = 1\n  })`
    : ''
  const filtersReturnExtra = hasSearch ? 'debouncedSearch, ' : ''

  return {
    studlyName: names.studlyName,
    studlyPlural: names.studlyPlural,
    kebabName: names.kebabName,
    kebabPlural: names.kebabPlural,
    filtersImports,
    filtersBody,
    filtersReturnExtra,
  }
}

// ---------------------------------------------------------------------------
// components/{Name}Filters.vue
// ---------------------------------------------------------------------------

function buildFiltersComponentTokens(names, fields) {
  const searchField = firstTextualFieldName(fields)

  const filtersTemplateBody = searchField
    ? `    <FiltersCol>
      <FiltersWrapper label="Buscar">
        <a-input
          :value="filters.search"
          :placeholder="t('${names.camelPlural}.title')"
          allow-clear
          @update:value="(v: string) => emit('update:filters', { ...props.filters, search: v, page: 1 })"
        >
          <template #prefix>
            <SearchOutlined :style="{ color: 'var(--dt-muted, #6B8CAE)' }" />
          </template>
        </a-input>
      </FiltersWrapper>
    </FiltersCol>`
    : `    <!-- This module's schema has no string/text field to search by. -->`

  return {
    studlyName: names.studlyName,
    kebabName: names.kebabName,
    filtersTemplateBody,
  }
}

// ---------------------------------------------------------------------------
// components/{Name}sTable.vue
// ---------------------------------------------------------------------------

function renderIfElseChain(blocks) {
  return blocks
    .map((b, i) => {
      const directive = i === 0 ? 'v-if' : 'v-else-if'
      return `      <template ${directive}="${b.cond}">\n${b.content}\n      </template>`
    })
    .join('\n')
}

function buildTableTokens(names, fields, moduleName) {
  const dateFields = fields.filter((f) => f.type === 'date' || f.type === 'datetime')

  const tableImports = dateFields.length ? `import { formatDate } from '@/core/utils/date'` : ''

  // belongsTo relations are intentionally NOT rendered as a table column: the
  // Item type only carries the raw '{relation}_guid' (no label, matching the
  // backend Resource) so there is no human-readable value to display here
  // without the developer wiring a lookup — same TODO left on the form select.
  const columnLines = []
  for (const f of fields) {
    const label = fieldLabel(f)
    if (f.type === 'date' || f.type === 'datetime' || f.type === 'boolean') {
      columnLines.push(`  { title: t('${names.camelPlural}.table.${f.name}'), key: '${f.name}' },`)
    } else {
      columnLines.push(`  { title: t('${names.camelPlural}.table.${f.name}'), dataIndex: '${f.name}', key: '${f.name}' },`)
    }
  }

  const chainBlocks = []
  for (const f of dateFields) {
    chainBlocks.push({
      cond: `column.key === '${f.name}'`,
      content: `        {{ record.${f.name} ? formatDate(record.${f.name}) : '—' }}`,
    })
  }
  for (const f of fields.filter((f) => f.type === 'boolean')) {
    chainBlocks.push({
      cond: `column.key === '${f.name}'`,
      content: `        <a-tag :color="record.${f.name} ? 'green' : 'default'">{{ record.${f.name} ? 'Sí' : 'No' }}</a-tag>`,
    })
  }

  chainBlocks.push({
    cond: `column.key === 'actions'`,
    content: `        <BaseTableActions>
          <PermissionGuard permission="${moduleName}.update">
            <BaseButton variant="row-action" size="small" tooltip="Editar" @click="emit('edit', record)">
              <template #icon><EditOutlined /></template>
            </BaseButton>
          </PermissionGuard>
          <PermissionGuard permission="${moduleName}.delete">
            <BaseButton variant="row-action" size="small" danger tooltip="Eliminar" @click="emit('delete', record)">
              <template #icon><DeleteOutlined /></template>
            </BaseButton>
          </PermissionGuard>
        </BaseTableActions>`,
  })

  return {
    studlyName: names.studlyName,
    studlyPlural: names.studlyPlural,
    camelPlural: names.camelPlural,
    kebabName: names.kebabName,
    tableImports,
    tableColumns: columnLines.join('\n'),
    tableBodyCells: renderIfElseChain(chainBlocks),
  }
}

// ---------------------------------------------------------------------------
// components/forms/{Name}Form.vue
// ---------------------------------------------------------------------------

function moduleComposableImportInfo(relationModel) {
  const modelPlural = pluralize(relationModel)
  const kebabModelPlural = kebabCase(modelPlural)
  const camelModelPlural = camelCase(modelPlural)
  const composableName = `use${modelPlural}`
  const modulePath = `@/modules/${kebabModelPlural}/composables/${composableName}`
  const composableFile = path.join(FRONTEND_ROOT, 'src', 'modules', kebabModelPlural, 'composables', `${composableName}.ts`)
  const exists = fs.existsSync(composableFile)
  return { modelPlural, kebabModelPlural, camelModelPlural, composableName, modulePath, exists }
}

function buildFormComponentTokens(names, fields, relations) {
  const belongsTo = belongsToRelations(relations)
  const hasRelations = belongsTo.length > 0

  const formVueImports = hasRelations ? `import { computed, watch } from 'vue'` : `import { watch } from 'vue'`

  const relationImportLines = []
  const relationQueryLines = []
  const relationOptionsVars = []
  for (const r of belongsTo) {
    const info = moduleComposableImportInfo(r.model)
    const todo = info.exists ? '' : ` // TODO: verify ${r.model} module exists`
    relationImportLines.push(`import { ${info.composableName} } from '${info.modulePath}'${todo}`)
    const relCamel = camelCase(r.name)
    // per_page: 100 is a pragmatic "load enough for a dropdown" default, not
    // a paginated/searchable select — bump it (or swap in a search-as-you-type
    // composable) if this relation's target table can exceed 100 rows.
    relationQueryLines.push(
      `const { data: ${info.camelModelPlural}Data, isLoading: loading${info.modelPlural} } = ${info.composableName}({ per_page: 100 })`,
    )
    relationQueryLines.push(
      `const ${relCamel}Options = computed(() => (${info.camelModelPlural}Data.value?.data ?? []).map((o) => ({ label: o.${r.labelField}, value: o.guid })))`,
    )
    relationOptionsVars.push({ rel: r, relCamel, loadingVar: `loading${info.modelPlural}` })
  }

  // Omit destructuring `errors` from useForm() when every field is boolean
  // (a-switch fields never render a validate-status/help block), so the
  // generated component doesn't declare an unused variable.
  const usesErrors = fields.some((f) => f.type !== 'boolean')
  const formErrorsBinding = usesErrors ? 'errors, ' : ''

  const defineFieldLines = []
  for (const f of fields) {
    if (f.type === 'boolean') {
      defineFieldLines.push(`const [${camelCase(f.name)}] = defineField('${f.name}')`)
    } else {
      defineFieldLines.push(`const [${camelCase(f.name)}, ${camelCase(f.name)}Attrs] = defineField('${f.name}')`)
    }
  }
  for (const r of belongsTo) {
    defineFieldLines.push(`const [${camelCase(r.name)}Guid] = defineField('${snakeCase(r.name)}_guid')`)
  }

  const setValuesLines = []
  for (const f of fields) {
    if (f.type === 'boolean') {
      setValuesLines.push(`        ${f.name}: item.${f.name},`)
    } else if (f.nullable) {
      setValuesLines.push(`        ${f.name}: item.${f.name} ?? '',`)
    } else {
      setValuesLines.push(`        ${f.name}: item.${f.name},`)
    }
  }
  for (const r of belongsTo) {
    const key = `${snakeCase(r.name)}_guid`
    setValuesLines.push(`        ${key}: item.${camelCase(r.name)}?.guid,`)
  }

  const errorEligible = (type) => type !== 'boolean'

  const fieldTemplateBlocks = []
  for (const f of fields) {
    const camel = camelCase(f.name)
    const label = fieldLabel(f)
    const validateBlock = errorEligible(f.type)
      ? `\n            :validate-status="errors.${f.name} ? 'error' : ''"\n            :help="errors.${f.name} ?? ''"`
      : ''

    let control
    let colSize = 'md="12"'
    if (f.type === 'text') {
      control = `<a-textarea v-model:value="${camel}" v-bind="${camel}Attrs" :rows="3" />`
      colSize = 'xs="24"'
    } else if (f.type === 'integer') {
      control = `<a-input-number v-model:value="${camel}" v-bind="${camel}Attrs" style="width: 100%" />`
      colSize = 'md="8"'
    } else if (f.type.startsWith('decimal:')) {
      control = `<a-input v-model:value="${camel}" v-bind="${camel}Attrs" placeholder="0.00" />`
      colSize = 'md="8"'
    } else if (f.type === 'boolean') {
      control = `<a-switch v-model:checked="${camel}" />`
      colSize = 'md="6"'
    } else if (f.type === 'date') {
      control = `<a-date-picker v-model:value="${camel}" value-format="YYYY-MM-DD" format="DD/MM/YYYY" style="width: 100%" />`
      colSize = 'md="8"'
    } else if (f.type === 'datetime') {
      control = `<a-date-picker v-model:value="${camel}" show-time value-format="YYYY-MM-DDTHH:mm:ss" format="DD/MM/YYYY HH:mm" style="width: 100%" />`
      colSize = 'md="8"'
    } else {
      control = `<a-input v-model:value="${camel}" v-bind="${camel}Attrs" />`
    }

    fieldTemplateBlocks.push(`        <a-col xs="24" ${colSize}>
          <a-form-item
            label="${label}"${validateBlock}
          >
            ${control}
          </a-form-item>
        </a-col>`)
  }
  for (const r of belongsTo) {
    const relCamel = camelCase(r.name)
    const label = relationLabel(r)
    fieldTemplateBlocks.push(`        <a-col xs="24" md="12">
          <a-form-item label="${label}">
            <a-select
              v-model:value="${relCamel}Guid"
              :options="${relCamel}Options"
              :loading="loading${pluralize(r.model)}"
              show-search
              :filter-option="(input: string, option: { label: string }) => option.label.toLowerCase().includes(input.toLowerCase())"
            />
          </a-form-item>
        </a-col>`)
  }

  return {
    studlyName: names.studlyName,
    kebabName: names.kebabName,
    kebabPlural: names.kebabPlural,
    camelPlural: names.camelPlural,
    formVueImports,
    formRelationImports: relationImportLines.join('\n'),
    formRelationQueries: relationQueryLines.length ? '\n' + relationQueryLines.join('\n') : '',
    formErrorsBinding,
    formDefineFields: defineFieldLines.join('\n'),
    formSetValuesEdit: setValuesLines.join('\n'),
    formFieldsTemplate: fieldTemplateBlocks.join('\n'),
  }
}

// ---------------------------------------------------------------------------
// pages/{Name}sPage.vue
// ---------------------------------------------------------------------------

function buildListPageTokens(names, fields, moduleName) {
  const hasSearch = firstTextualFieldName(fields) !== null

  return {
    studlyName: names.studlyName,
    studlyPlural: names.studlyPlural,
    camelPlural: names.camelPlural,
    kebabName: names.kebabName,
    kebabPlural: names.kebabPlural,
    moduleName,
    filtersDestructureExtra: hasSearch ? ', debouncedSearch' : '',
    queryParamsSearch: hasSearch ? '  search: debouncedSearch.value,' : '',
  }
}

// ---------------------------------------------------------------------------
// pages/{Name}FormPage.vue
// ---------------------------------------------------------------------------

function buildFormPageTokens(names) {
  return {
    studlyName: names.studlyName,
    kebabName: names.kebabName,
    kebabPlural: names.kebabPlural,
    camelPlural: names.camelPlural,
  }
}

// ---------------------------------------------------------------------------
// router/{name}.routes.ts
// ---------------------------------------------------------------------------

function buildRoutesTokens(names) {
  return {
    studlyName: names.studlyName,
    studlyPlural: names.studlyPlural,
    camelPlural: names.camelPlural,
    kebabPlural: names.kebabPlural,
    entityLabel: names.entityLabel,
    entityLabelLower: names.entityLabelLower,
    entityLabelPlural: names.studlyPlural,
  }
}

// ---------------------------------------------------------------------------
// i18n/locales/es/{name}.ts
// ---------------------------------------------------------------------------

function buildI18nTokens(names, fields, relations) {
  const belongsTo = belongsToRelations(relations)
  const entityLabelPlural = names.studlyPlural

  const tableFieldLines = fields.map((f) => `      ${f.name}: '${fieldLabel(f)}',`)
  for (const r of belongsTo) {
    tableFieldLines.push(`      ${camelCase(r.name)}: '${relationLabel(r)}',`)
  }

  const formFieldLines = fields.map((f) => `      ${f.name}: '${fieldLabel(f)}',`)
  for (const r of belongsTo) {
    formFieldLines.push(`      ${snakeCase(r.name)}_guid: '${relationLabel(r)}',`)
  }

  return {
    camelPlural: names.camelPlural,
    entityLabel: names.entityLabel,
    entityLabelLower: names.entityLabelLower,
    entityLabelPlural,
    entityLabelPluralLower: entityLabelPlural.toLowerCase(),
    i18nTableFields: tableFieldLines.join('\n'),
    i18nFormFields: formFieldLines.join('\n'),
  }
}

// ---------------------------------------------------------------------------
// router/index.ts / i18n/index.ts safe insertion
// ---------------------------------------------------------------------------

/** Splits on both CRLF and LF (Windows-checked-out files in this repo use
 * CRLF) and reports the detected line ending so callers can rejoin without
 * silently converting the file's line-ending style. */
function splitLines(content) {
  const eol = content.includes('\r\n') ? '\r\n' : '\n'
  return { lines: content.split(/\r\n|\n/), eol }
}

/**
 * Shared "insert an import + a list/object entry into an existing file"
 * algorithm used for both router/index.ts (import + `...xRoutes,` inside a
 * `children: [...]` array) and i18n/index.ts (import + `...xxx,` inside the
 * `es: {...}` messages object) — same shape, different anchors/close token.
 * Returns { content, applied, alreadyPresent } — if the expected anchors
 * aren't found, no changes are made and applied=false (caller prints a
 * manual-step warning), mirroring the backend command's anchor-missing
 * fallback behavior.
 */
function insertIntoAnchoredList(content, { importLine, importLineRegex, anchorRegex, entryRegex, closeToken, entryLine, defaultIndent }) {
  if (content.includes(importLine)) {
    return { content, applied: true, alreadyPresent: true }
  }

  const { lines, eol } = splitLines(content)

  let lastImportIdx = -1
  for (let i = 0; i < lines.length; i++) {
    if (importLineRegex.test(lines[i])) lastImportIdx = i
  }
  if (lastImportIdx === -1) {
    return { content, applied: false }
  }

  let anchorIdx = -1
  for (let i = 0; i < lines.length; i++) {
    if (anchorRegex.test(lines[i])) {
      anchorIdx = i
      break
    }
  }
  if (anchorIdx === -1) {
    return { content, applied: false }
  }

  let closeIdx = -1
  let entryIndent = defaultIndent
  for (let i = anchorIdx + 1; i < lines.length; i++) {
    const trimmed = lines[i].trim()
    if (trimmed === closeToken) {
      closeIdx = i
      break
    }
    const entryMatch = lines[i].match(entryRegex)
    if (entryMatch) entryIndent = entryMatch[1]
  }
  if (closeIdx === -1) {
    return { content, applied: false }
  }

  lines.splice(lastImportIdx + 1, 0, importLine)
  const newCloseIdx = closeIdx + 1
  lines.splice(newCloseIdx, 0, `${entryIndent}${entryLine}`)

  return { content: lines.join(eol), applied: true, alreadyPresent: false }
}

/**
 * Inserts the new module's route import + registration into router/index.ts.
 * See insertIntoAnchoredList() for the shared algorithm; this just supplies
 * router-specific anchors (the `children: [...]` array of `...xRoutes,`).
 */
function insertRouterRegistration(content, names) {
  return insertIntoAnchoredList(content, {
    importLine: `import { ${names.camelPlural}Routes } from '@/modules/${names.kebabPlural}/router/${names.kebabPlural}.routes'`,
    importLineRegex: /^import \{ \w+Routes \} from '@\/modules\//,
    anchorRegex: /children: \[/,
    entryRegex: /^(\s*)\.\.\.\w+Routes,/,
    closeToken: '],',
    entryLine: `...${names.camelPlural}Routes,`,
    defaultIndent: '            ',
  })
}

/**
 * Inserts the new module's i18n locale import + registration into
 * i18n/index.ts. See insertIntoAnchoredList() for the shared algorithm; this
 * just supplies i18n-specific anchors (the `es: {...}` messages object).
 */
function insertI18nRegistration(content, names) {
  return insertIntoAnchoredList(content, {
    importLine: `import ${names.camelPlural} from './locales/es/${names.kebabPlural}';`,
    importLineRegex: /^import \w+ from '\.\/locales\/es\/[\w-]+';?$/,
    anchorRegex: /\bes:\s*\{/,
    entryRegex: /^(\s*)(\.\.\.\w+|\w+),$/,
    closeToken: '},',
    entryLine: `...${names.camelPlural},`,
    defaultIndent: '            ',
  })
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

function main() {
  const args = parseArgs(process.argv.slice(2))
  const rawName = args._[0]

  if (!rawName) {
    fail('Usage: node scripts/make-module.mjs {Name} [--schema=path] [--dry-run] [--force]')
  }
  if (!IDENTIFIER_PATTERN.test(rawName)) {
    fail(`Invalid module name '${rawName}'. Use letters, digits, '_' or '-' only, starting with a letter.`)
  }

  const names = deriveNames(rawName)

  const schemaPath = args.schema
    ? path.resolve(FRONTEND_ROOT, args.schema)
    : path.join(FRONTEND_ROOT, '..', 'backend', 'module-schemas', `${names.studlyName}.yaml`)

  if (!fs.existsSync(schemaPath)) {
    fail(
      `Schema file not found: ${schemaPath}\nPass --schema=path/to/file.yaml or create backend/module-schemas/${names.studlyName}.yaml (the same schema the backend's 'php artisan make:module' reads).`,
    )
  }

  const schema = parseYaml(fs.readFileSync(schemaPath, 'utf8'))
  if (!schema || typeof schema !== 'object') {
    fail(`Schema file is empty or invalid: ${schemaPath}`)
  }

  const rawFields = schema.fields ?? []
  const rawRelations = schema.relations ?? []

  const validationError = validateSchemaShape(rawFields, rawRelations)
  if (validationError) {
    fail(validationError)
  }

  const fields = normalizeFields(rawFields)
  const relations = normalizeRelations(rawRelations)
  const softDeletes = Boolean(schema.softDeletes ?? false)
  const moduleName = schema.permissions?.module ?? names.kebabPlural

  for (const r of otherRelations(relations)) {
    warn(
      `Relation '${r.name}' (${r.type} -> ${r.model}) only gets a type-only placeholder field. hasMany/hasOne/belongsToMany UI is out of scope for this generator — wire it up by hand.`,
    )
  }

  const moduleDir = path.join(FRONTEND_ROOT, 'src', 'modules', names.kebabPlural)

  const targets = {
    types: path.join(moduleDir, 'types', `${names.kebabName}.types.ts`),
    api: path.join(moduleDir, 'api', `${names.kebabPlural}.api.ts`),
    validator: path.join(moduleDir, 'validators', `${names.kebabName}.validator.ts`),
    store: path.join(moduleDir, 'stores', `${names.kebabPlural}-ui.store.ts`),
    composableList: path.join(moduleDir, 'composables', `use${names.studlyPlural}.ts`),
    composableGet: path.join(moduleDir, 'composables', `use${names.studlyName}.ts`),
    composableCreate: path.join(moduleDir, 'composables', `useCreate${names.studlyName}.ts`),
    composableUpdate: path.join(moduleDir, 'composables', `useUpdate${names.studlyName}.ts`),
    composableDelete: path.join(moduleDir, 'composables', `useDelete${names.studlyName}.ts`),
    composableFilters: path.join(moduleDir, 'composables', `use${names.studlyName}Filters.ts`),
    filtersComponent: path.join(moduleDir, 'components', `${names.studlyName}Filters.vue`),
    tableComponent: path.join(moduleDir, 'components', `${names.studlyPlural}Table.vue`),
    formComponent: path.join(moduleDir, 'components', 'forms', `${names.studlyName}Form.vue`),
    listPage: path.join(moduleDir, 'pages', `${names.studlyPlural}Page.vue`),
    formPage: path.join(moduleDir, 'pages', `${names.studlyName}FormPage.vue`),
    routes: path.join(moduleDir, 'router', `${names.kebabPlural}.routes.ts`),
    i18n: path.join(FRONTEND_ROOT, 'src', 'i18n', 'locales', 'es', `${names.kebabPlural}.ts`),
  }

  const routerIndexPath = path.join(FRONTEND_ROOT, 'src', 'router', 'index.ts')
  const i18nIndexPath = path.join(FRONTEND_ROOT, 'src', 'i18n', 'index.ts')

  const conflicts = Object.entries(targets).filter(([, p]) => fs.existsSync(p))
  if (conflicts.length && !args.force) {
    console.error('The following files already exist. Re-run with --force to overwrite them:')
    for (const [, p] of conflicts) console.error(`  - ${p}`)
    process.exit(1)
  }

  if (args.dryRun) {
    console.log('Dry run — the following files would be created/modified:')
    for (const p of Object.values(targets)) console.log(`  - ${p}`)
    console.log(`  - ${routerIndexPath} (route import + registration inserted)`)
    console.log(`  - ${i18nIndexPath} (locale import + registration inserted)`)
    process.exit(0)
  }

  // Snapshot pre-existing content for rollback.
  const moduleDirExisted = fs.existsSync(moduleDir)
  const originalTargetContents = {}
  for (const [key, p] of Object.entries(targets)) {
    if (fs.existsSync(p)) originalTargetContents[key] = fs.readFileSync(p, 'utf8')
  }
  const originalRouterContent = fs.readFileSync(routerIndexPath, 'utf8')
  const originalI18nContent = fs.readFileSync(i18nIndexPath, 'utf8')

  function rollback() {
    for (const [key, p] of Object.entries(targets)) {
      if (Object.prototype.hasOwnProperty.call(originalTargetContents, key)) {
        fs.writeFileSync(p, originalTargetContents[key], 'utf8')
      } else if (fs.existsSync(p)) {
        fs.unlinkSync(p)
      }
    }
    // The module directory itself (types/, api/, components/, ...) is only
    // ever created fresh by this command — if it didn't exist before this
    // run, remove it entirely so a failed run doesn't leave an empty shell
    // of subdirectories behind after their files were unlinked above.
    if (!moduleDirExisted && fs.existsSync(moduleDir)) {
      fs.rmSync(moduleDir, { recursive: true, force: true })
    }
    fs.writeFileSync(routerIndexPath, originalRouterContent, 'utf8')
    fs.writeFileSync(i18nIndexPath, originalI18nContent, 'utf8')
  }

  try {
    put(targets.types, render('types', buildTypesTokens(names, fields, relations, softDeletes)))
    put(targets.api, render('api', buildApiTokens(names)))
    put(targets.validator, render('validator', buildValidatorTokens(names, fields, relations)))
    put(targets.store, render('store', buildStoreTokens(names, fields)))
    put(targets.composableList, render('composable-list', buildComposableListTokens(names)))
    put(targets.composableGet, render('composable-get', buildComposableGetTokens(names)))
    put(targets.composableCreate, render('composable-create', buildComposableCreateTokens(names)))
    put(targets.composableUpdate, render('composable-update', buildComposableUpdateTokens(names)))
    put(targets.composableDelete, render('composable-delete', buildComposableDeleteTokens(names, fields)))
    put(targets.composableFilters, render('composable-filters', buildComposableFiltersTokens(names, fields)))
    put(targets.filtersComponent, render('filters', buildFiltersComponentTokens(names, fields)))
    put(targets.tableComponent, render('table', buildTableTokens(names, fields, moduleName)))
    put(targets.formComponent, render('form', buildFormComponentTokens(names, fields, relations)))
    put(targets.listPage, render('list-page', buildListPageTokens(names, fields, moduleName)))
    put(targets.formPage, render('form-page', buildFormPageTokens(names)))
    put(targets.routes, render('routes', buildRoutesTokens(names)))
    put(targets.i18n, render('i18n', buildI18nTokens(names, fields, relations)))

    const routerResult = insertRouterRegistration(originalRouterContent, names)
    if (routerResult.applied) {
      fs.writeFileSync(routerIndexPath, routerResult.content, 'utf8')
    } else {
      warn(
        `Could not find safe insertion anchors in ${routerIndexPath} — register '${names.camelPlural}Routes' manually: import it and spread it into the authenticated layout route's children array.`,
      )
    }

    const i18nResult = insertI18nRegistration(originalI18nContent, names)
    if (i18nResult.applied) {
      fs.writeFileSync(i18nIndexPath, i18nResult.content, 'utf8')
    } else {
      warn(
        `Could not find safe insertion anchors in ${i18nIndexPath} — register '${names.camelPlural}' manually: import it and spread it into the 'es' messages object.`,
      )
    }

    console.log('Module generated:')
    for (const p of Object.values(targets)) console.log(`  - ${p}`)
    if (routerResult.applied) console.log(`  - ${routerIndexPath} (route registered)`)
    if (i18nResult.applied) console.log(`  - ${i18nIndexPath} (locale registered)`)

    console.log('')
    console.log('Paste into the permission seeder / role assignment for this module:')
    console.log(`  ${moduleName}.create`)
    console.log(`  ${moduleName}.update`)
    console.log(`  ${moduleName}.delete`)
  } catch (err) {
    rollback()
    console.error(`Generation failed and was rolled back: ${err.message}`)
    console.error('Fix the schema and re-run — no partial files were left behind.')
    process.exit(1)
  }
}

main()
