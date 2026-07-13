<script setup lang="ts">
import { watch } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { useI18n } from 'vue-i18n'

import { createCurrencySchema, type CurrencyFormValues } from '../../validators/currency.validator'
import type { CurrencyItem } from '../../types/currency.types'

const props = withDefaults(
  defineProps<{
    mode: 'create' | 'edit'
    initialValues?: CurrencyItem | null
    fieldErrors?: Record<string, string> | null
    loading?: boolean
    hideFooter?: boolean
  }>(),
  { loading: false, hideFooter: false },
)

const emit = defineEmits<{
  submit: [values: CurrencyFormValues]
}>()

const { t } = useI18n()

const { errors, defineField, handleSubmit, setErrors, setValues } = useForm({
  validationSchema: toTypedSchema(createCurrencySchema),
})

const [code, codeAttrs] = defineField('code')
const [name, nameAttrs] = defineField('name')
const [symbol, symbolAttrs] = defineField('symbol')
const [is_active] = defineField('is_active')

watch(
  () => props.initialValues,
  (item) => {
    if (props.mode === 'edit' && item) {
      setValues({
        code: item.code,
        name: item.name,
        symbol: item.symbol,
        is_active: item.is_active,
      })
    }
  },
  { immediate: true },
)

watch(() => props.fieldErrors, (errs) => {
  setErrors(errs ?? {})
})

const onSubmit = handleSubmit((values) => emit('submit', values))

defineExpose({ submit: onSubmit })
</script>

<template>
  <a-form layout="vertical" @submit.prevent="onSubmit">
    <FormSection>
      <a-row :gutter="12">
        <a-col xs="24" md="12">
          <a-form-item
            label="Código"
            :validate-status="errors.code ? 'error' : ''"
            :help="errors.code ?? ''"
          >
            <a-input v-model:value="code" v-bind="codeAttrs" />
          </a-form-item>
        </a-col>
        <a-col xs="24" md="12">
          <a-form-item
            label="Nombre"
            :validate-status="errors.name ? 'error' : ''"
            :help="errors.name ?? ''"
          >
            <a-input v-model:value="name" v-bind="nameAttrs" />
          </a-form-item>
        </a-col>
        <a-col xs="24" md="12">
          <a-form-item
            label="Símbolo"
            :validate-status="errors.symbol ? 'error' : ''"
            :help="errors.symbol ?? ''"
          >
            <a-input v-model:value="symbol" v-bind="symbolAttrs" />
          </a-form-item>
        </a-col>
        <a-col xs="24" md="6">
          <a-form-item
            label="Activa"
          >
            <a-switch v-model:checked="is_active" />
          </a-form-item>
        </a-col>
      </a-row>
    </FormSection>

    <FormFooter
      v-if="!hideFooter"
      :loading="loading"
      cancel-to="/currencies"
      :save-label="mode === 'edit' ? t('currencies.form.updateSave') : t('currencies.form.createSave')"
    />
  </a-form>
</template>
