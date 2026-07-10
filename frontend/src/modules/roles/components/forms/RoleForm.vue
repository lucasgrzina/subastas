<script setup lang="ts">
import { computed, watch } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import RolePermissionsSelector from './RolePermissionsSelector.vue'
import { createRoleSchema, type RoleFormValues, type RoleUpdateFormValues } from '../../validators/role.validator'
import type { PermissionGroup } from '../../types/role.types'

const props = withDefaults(
  defineProps<{
    initialValues?: Partial<RoleFormValues>
    permissionGroups: PermissionGroup[]
    loadingPermissions?: boolean
    loading?: boolean
    saveLabel?: string
    isProtected?: boolean
    fieldErrors?: Record<string, string> | null
    hideFooter?: boolean
  }>(),
  { loading: false, loadingPermissions: false, isProtected: false, hideFooter: false },
)

const emit = defineEmits<{
  submit: [values: RoleFormValues | RoleUpdateFormValues]
}>()

const { handleSubmit, defineField, errors, setValues, setErrors } = useForm({
  validationSchema: computed(() => toTypedSchema(createRoleSchema)),
  initialValues: {
    name: props.initialValues?.name ?? '',
    permissions: props.initialValues?.permissions ?? [],
  },
})

watch(
  () => props.initialValues,
  (vals) => {
    if (vals) setValues({ name: vals.name ?? '', permissions: vals.permissions ?? [] })
  },
)

watch(() => props.fieldErrors, (errors) => {
  setErrors(errors ?? {})
})

const [name, nameAttrs] = defineField('name')
const [permissionsField] = defineField('permissions')
const permissions = computed({
  get: () => (permissionsField.value ?? []) as string[],
  set: (val: string[]) => { permissionsField.value = val },
})

const onSubmit = handleSubmit((values) => emit('submit', values))

defineExpose({ submit: onSubmit })
</script>

<template>
  <a-form layout="vertical" @submit.prevent="onSubmit">

    <FormSection compact>
      <a-row :gutter="12">
        <a-col :xs="24">
          <a-form-item
            label="Nombre del rol"
            :validate-status="errors.name ? 'error' : ''"
            :help="errors.name ?? ''"
          >
            <a-input v-model:value="name" v-bind="nameAttrs" placeholder="Ej: supervisor, auditor..." />
          </a-form-item>
        </a-col>
      </a-row>
    </FormSection>

    <FormSection
      compact
      title="Permisos"
      subtitle="Seleccioná los permisos que tendrá este rol."
    >
      <a-row :gutter="12">
        <a-col :xs="24">
          <RolePermissionsSelector
            v-model="permissions"
            :permission-groups="permissionGroups"
            :loading="loadingPermissions"
          />
          <span v-if="errors.permissions" class="rf-error">{{ errors.permissions }}</span>
        </a-col>
      </a-row>
    </FormSection>

    <FormFooter
      v-if="!hideFooter"
      :loading="loading"
      cancel-to="/roles"
      :save-label="saveLabel ?? 'Guardar'"
    />
  </a-form>
</template>

<style scoped>
.rf-error {
  font-size: 12px;
  color: #FF5A6A;
}
</style>
