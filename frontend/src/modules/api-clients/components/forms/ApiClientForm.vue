<script setup lang="ts">
import { watch } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { apiClientCreateSchema, apiClientUpdateSchema } from '@/modules/api-clients/validators/api-client.validator'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'
import type { ApiClientCreatePayload, ApiClientUpdatePayload } from '../../types/api-client.types'

const props = withDefaults(
  defineProps<{
    mode:           'create' | 'edit'
    initialValues?: Partial<ApiClientUpdatePayload>
    loading?:       boolean
    fieldErrors?:   Record<string, string> | null
  }>(),
  { loading: false },
)

const emit = defineEmits<{
  submit: [values: ApiClientCreatePayload | ApiClientUpdatePayload]
}>()

const { errors, defineField, handleSubmit, setErrors, setValues } = useForm({
  validationSchema: toTypedSchema(
    props.mode === 'create' ? apiClientCreateSchema : apiClientUpdateSchema,
  ),
})

const [nombre, nombreAttrs] = defineField('nombre')
const [email, emailAttrs]   = defineField('email')

watch(
  () => props.initialValues,
  (vals) => {
    if (props.mode === 'edit' && vals) {
      setValues({
        nombre: vals.nombre ?? '',
        email:  vals.email  ?? '',
      })
    }
  },
  { immediate: true, deep: true },
)

watch(
  () => props.fieldErrors,
  (errs) => {
    setErrors(errs ?? {})
  },
)

const onSubmit = handleSubmit((values) => {
  emit('submit', {
    nombre: values.nombre,
    email:  values.email || undefined,
  })
})

defineExpose({ submit: onSubmit })
</script>

<template>
  <a-form layout="vertical" @submit.prevent="onSubmit">
    <a-form-item
      label="Nombre"
      :validate-status="errors.nombre ? 'error' : ''"
      :help="errors.nombre ?? ''"
    >
      <a-input
        v-model:value="nombre"
        v-bind="nombreAttrs"
        placeholder="Ej: App Móvil"
        autocomplete="off"
      />
    </a-form-item>

    <a-form-item
      label="Email de contacto"
      :validate-status="errors.email ? 'error' : ''"
      :help="errors.email ?? ''"
    >
      <a-input
        v-model:value="email"
        v-bind="emailAttrs"
        placeholder="correo@empresa.com"
        autocomplete="off"
      />
    </a-form-item>

    <slot name="footer">
      <a-form-item style="margin-bottom: 0; text-align: right">
        <a-space>
          <slot name="cancel" />
          <BaseButton variant="primary" html-type="submit" :loading="loading">
            {{ mode === 'create' ? 'Crear cliente' : 'Guardar cambios' }}
          </BaseButton>
        </a-space>
      </a-form-item>
    </slot>
  </a-form>
</template>
