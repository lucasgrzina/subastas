---
name: frontend-vue-conventions
description: "Trigger: Vue, .vue files, frontend/src/modules, composables, Pinia, Vue Query, Ant Design Vue, i18n, Zod validators, new module, scaffold module. Apply this repo's non-negotiable Vue 3 + TypeScript frontend conventions and module-generation workflow."
license: MIT
metadata:
  author: gentleman-programming
  version: "1.0"
---

## Activation Contract

Load this skill when creating or editing Vue 3 frontend code under `frontend/src/modules/`, `frontend/src/components/`, or `frontend/src/router/` — components, composables, Pinia stores, API layer functions, validators, or routes. Also load when the user asks to scaffold a brand-new frontend module end-to-end.

## Hard Rules

- Feature module pattern: `frontend/src/modules/{name}/` with `api/`, `components/`, `composables/`, `pages/`, `router/`, `stores/`, `types/`, `validators/`.
- Never `any` — use `unknown` with a type guard. Props via `defineProps<{}>()`, emits via `defineEmits<{}>()`. All types live in `types/`.
- Server state via Vue Query (`useQuery`/`useMutation`) — never in Pinia. Every `useMutation` calls `invalidateQueries` on success.
- Pinia only for UI state (open modals, active filters, drawer state) — never server data. Stores at `stores/{name}-ui.store.ts`. Auth store persisted with `pinia-plugin-persistedstate`.
- Composables: `use` prefix mandatory, one composable per operation (`useCreate{Name}`, `useUpdate{Name}`, `useDelete{Name}`, `use{Name}s`). No business logic in components.
- Components: `<script setup lang="ts">` always. Reuse atoms in `frontend/src/components/atoms/` (`buttons/`, `cards/`, `display/`, `feedback/`, `inputs/`, `navigation/`, `overlays/`, `pickers/`, `selects/`) before creating new ones — auto-imported via `unplugin-vue-components`. If the needed atom doesn't exist, create it there first.
- Every write action (create/edit/delete) wrapped in `<PermissionGuard :permission="'module.action'">`.
- Validation: Zod + Vee-Validate, schemas in `validators/`, used with `useForm`. Error messages in Spanish (system language is es-AR). Export the inferred type: `export type FormValues = z.infer<typeof schema>`. Map 422 errors via `src/core/composables/use422Errors.ts`.
- API layer: functions in `api/{name}.api.ts`, return data directly — the axios interceptor already unwraps `{ success, data }`. Use `guid` as the identifier in every call, never `id`.
- i18n: never hardcode strings in templates, always `$t('key')`; `const { t } = useI18n()` in `<script setup>`. Keys live in `frontend/src/i18n/locales/es/`. System language is es-AR only.
- Router: lazy loading mandatory (`component: () => import(...)`), `authGuard` in `beforeEnter` for every protected route. Routes in `router/{name}.routes.ts`, registered in `frontend/src/router/index.ts`.
- Theming: palettes `green`/`blue`/`red`/`yellow`, modes `dark`/`light`, tokens built by `src/core/themes/palettes.ts → buildDashTheme(palette, mode)`. Never hardcode color values in components.
- Styling: Tailwind for layout/spacing/color utilities, Ant Design Vue 4 for business components. No inline CSS except punctual cases.
- Semantic colors: green = success, red = alert/danger, orange = warning, blue = info. Icon-only buttons require `aria-label`.

## UI Component Tier Decision

Before writing a new UI piece (not a full module), decide its tier — it determines where it lives:

| Tier | Criteria | Location |
|---|---|---|
| Atom | Generic, no business logic, reusable by any module | `components/atoms/{category}/` |
| Molecule | Composed of atoms, still cross-module (e.g. a filter bar, a confirm-delete dialog) | `components/{category}/` |
| Module component | Specific to one feature's domain data | `modules/{name}/components/` |

1. Check `components/atoms/**` and the module's existing `components/` before writing — never build from memory.
2. State the tier, props, emits, and which atoms it consumes in 2-3 lines before writing code.
3. If the requirement is ambiguous (data shown, actions, admin vs. public context, new vs. refactor), ask before building.

## Decision Gates

| Artifact | Location |
|---|---|
| Server-state read/write | `composables/use{Name}s.ts` / `useCreate{Name}.ts` (Vue Query) |
| UI-only state | `stores/{name}-ui.store.ts` (Pinia) |
| Form validation | `validators/{name}.schema.ts` (Zod) |
| API calls | `api/{name}.api.ts` |
| Reusable presentational piece | `components/atoms/{category}/` |
| Route definitions | `router/{name}.routes.ts` |

## Execution Steps

1. Identify the artifact type (component/composable/store/validator/api function/route).
2. Apply the matching Hard Rule and Decision Gate location.
3. For a new entity, create in order: `types` → `api` → `validators` → `composables` (queries/mutations) → `components` → `pages` → `router`.
4. Verify before finishing: no `any`, no hardcoded strings/colors, server state never lives in Pinia, write actions wrapped in `PermissionGuard`.

## Module Generation Workflow

Trigger: user asks to scaffold a new frontend module end-to-end (not a single edit).

1. Confirm the essentials if missing: module name (singular PascalCase), backend Resource shape (fields returned), full CRUD or subset, UI-visible relations, permission keys (`{module}.lectura/alta/modificacion/baja`).
2. Read a reference module fully first — pick `users` or `roles` — in this order: `types/`, `api/`, `validators/`, `stores/{name}-ui.store.ts`, `composables/use{Name}s.ts` plus one mutation composable, `{Name}Table.vue`, `{Name}Form.vue`, `{Name}sPage.vue`, `router/{name}.routes.ts`, and its i18n keys. Never generate from memory.
3. Show a 10-15 line plan (files to create, fields, endpoints consumed, permission keys) and get user confirmation before writing anything. If a similarly-named module already exists, flag it before overwriting.
4. Generate in this exact order, applying the Hard Rules at each step: types → api → validators → UI store → composables (`use{Name}s`, `useCreate{Name}`, `useUpdate{Name}`, `useDelete{Name}`) → components (`{Name}Table.vue`, `{Name}Form.vue`, `Create{Name}Modal.vue`, `Edit{Name}Drawer.vue`) → page (`{Name}sPage.vue`) → router → i18n keys (`frontend/src/i18n/locales/es/{name}.ts`). For files you modify rather than create (`router/index.ts`, the main i18n registration file), Read before Edit — never blind-overwrite.
5. Verify with `cd frontend && npm run type-check`; fix errors before reporting.
6. Report: files created, files modified with what changed, verify result, suggested next step.

## Output Contract

List files created or modified and confirm each against the Hard Rules above (module folder shape respected, Vue Query vs. Pinia split correct, `PermissionGuard` applied to write actions, i18n keys added instead of hardcoded strings). For module generation, additionally confirm every Final Checklist item: all 8 sub-folders populated, types aligned to the backend Resource, API returns data directly, Zod messages in Spanish, Pinia holds UI state only, composables invalidate queries, atoms reused, write actions wrapped in `PermissionGuard`, no hardcoded strings, router lazy-loaded with `authGuard` and registered, `npm run type-check` passes.

## References

- `frontend/src/core/composables/use422Errors.ts` — 422 validation error mapping.
- `frontend/src/core/themes/palettes.ts` — theme token builder (`buildDashTheme`).
