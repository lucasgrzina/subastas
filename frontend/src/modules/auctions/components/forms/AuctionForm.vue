<script setup lang="ts">
import { computed, watch } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { useI18n } from 'vue-i18n'
import { auctionSchema, type AuctionFormValues } from '../../validators/auction.validator'
import type { AuctionItem } from '../../types/auction.types'

const props = withDefaults(
  defineProps<{
    mode: 'create' | 'edit'
    initialValues?: AuctionItem | null
    fieldErrors?: Record<string, string> | null
    loading?: boolean
    hideFooter?: boolean
  }>(),
  { loading: false, hideFooter: false },
)

const emit = defineEmits<{
  submit: [values: AuctionFormValues]
}>()

const { t } = useI18n()

const { errors, defineField, handleSubmit, setErrors, setValues } = useForm({
  validationSchema: toTypedSchema(auctionSchema),
  initialValues: { status: 'draft' },
})

const [title, titleAttrs] = defineField('title')
const [description, descriptionAttrs] = defineField('description')
const [startsAt] = defineField('starts_at')
const [endsAt] = defineField('ends_at')
const [status] = defineField('status')

watch(
  () => props.initialValues,
  (auction) => {
    if (props.mode === 'edit' && auction) {
      setValues({
        title: auction.title,
        description: auction.description ?? '',
        starts_at: auction.starts_at,
        ends_at: auction.ends_at ?? undefined,
        status: auction.status,
      })
    }
  },
  { immediate: true },
)

watch(() => props.fieldErrors, (errs) => {
  setErrors(errs ?? {})
})

const statusOptions = computed(() => [
  { label: t('auctions.status.draft'), value: 'draft' },
  { label: t('auctions.status.scheduled'), value: 'scheduled' },
  { label: t('auctions.status.live'), value: 'live' },
  { label: t('auctions.status.closed'), value: 'closed' },
  { label: t('auctions.status.cancelled'), value: 'cancelled' },
])

const onSubmit = handleSubmit((values) => emit('submit', values))

defineExpose({ submit: onSubmit })
</script>

<template>
  <a-form layout="vertical" @submit.prevent="onSubmit">
    <FormSection>
      <a-row :gutter="12">
        <a-col :xs="24" :md="16">
          <a-form-item
            :label="t('auctions.form.title')"
            :validate-status="errors.title ? 'error' : ''"
            :help="errors.title ?? ''"
          >
            <a-input v-model:value="title" v-bind="titleAttrs" :placeholder="t('auctions.form.titlePlaceholder')" />
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="8">
          <a-form-item :label="t('auctions.form.status')">
            <a-select v-model:value="status" :options="statusOptions" />
          </a-form-item>
        </a-col>
        <a-col :xs="24">
          <a-form-item :label="t('auctions.form.description')">
            <a-textarea v-model:value="description" v-bind="descriptionAttrs" :rows="3" />
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="12">
          <a-form-item
            :label="t('auctions.form.starts_at')"
            :validate-status="errors.starts_at ? 'error' : ''"
            :help="errors.starts_at ?? ''"
          >
            <a-date-picker
              v-model:value="startsAt"
              show-time
              value-format="YYYY-MM-DDTHH:mm:ss"
              format="DD/MM/YYYY HH:mm"
              style="width: 100%"
            />
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="12">
          <a-form-item
            :label="t('auctions.form.ends_at')"
            :validate-status="errors.ends_at ? 'error' : ''"
            :help="errors.ends_at ?? ''"
          >
            <a-date-picker
              v-model:value="endsAt"
              show-time
              value-format="YYYY-MM-DDTHH:mm:ss"
              format="DD/MM/YYYY HH:mm"
              style="width: 100%"
              allow-clear
            />
          </a-form-item>
        </a-col>
      </a-row>
    </FormSection>

    <FormFooter
      v-if="!hideFooter"
      :loading="loading"
      cancel-to="/auctions"
      :save-label="mode === 'edit' ? t('auctions.form.updateSave') : t('auctions.form.createSave')"
    />
  </a-form>
</template>
