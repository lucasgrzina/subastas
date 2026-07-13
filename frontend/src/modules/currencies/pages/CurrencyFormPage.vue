<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import CurrencyForm from '../components/forms/CurrencyForm.vue'
import { useCurrency } from '../composables/useCurrency'
import { useCreateCurrency } from '../composables/useCreateCurrency'
import { useUpdateCurrency } from '../composables/useUpdateCurrency'
import type { CurrencyFormValues } from '../validators/currency.validator'

const props = defineProps<{ guid?: string }>()

const router = useRouter()
const { t } = useI18n()
const formRef = ref<InstanceType<typeof CurrencyForm> | null>(null)

const { data: item, isLoading } = useCurrency(computed(() => props.guid))

function goToList() {
  router.push({ name: 'currencies' })
}

const isEdit = computed(() => Boolean(props.guid))
const title = computed(() => (isEdit.value ? t('currencies.edit') : t('currencies.new')))

const createCurrency = useCreateCurrency()
const updateCurrency = useUpdateCurrency()

const isMutating = computed(() => createCurrency.isPending.value || updateCurrency.isPending.value)
const fieldErrors = computed(() => (isEdit.value ? updateCurrency.fieldErrors.value : createCurrency.fieldErrors.value))
const generalError = computed(() => (isEdit.value ? updateCurrency.generalError.value : createCurrency.generalError.value))

function handleSubmit(values: CurrencyFormValues) {
  if (isEdit.value) {
    updateCurrency.mutate(
      { guid: props.guid!, payload: values },
      { onSuccess: goToList },
    )
  } else {
    createCurrency.mutate(values, { onSuccess: goToList })
  }
}
</script>

<template>
  <div>
    <AppHeader :title="title">
      <template #actions="{ buttonSize }">
        <BaseButton variant="secondary" :size="buttonSize" :disabled="isMutating" @click="goToList">
          {{ t('currencies.cancel') }}
        </BaseButton>
        <BaseButton variant="primary" :size="buttonSize" :loading="isMutating" @click="formRef?.submit()">
          {{ isEdit ? t('currencies.form.updateSave') : t('currencies.form.createSave') }}
        </BaseButton>
      </template>
    </AppHeader>

    <a-alert
      v-if="generalError && !fieldErrors"
      :message="generalError"
      type="error"
      show-icon
      style="margin-bottom: 16px"
    />

    <a-skeleton v-if="guid && isLoading" active />
    <CurrencyForm
      v-else
      ref="formRef"
      :mode="isEdit ? 'edit' : 'create'"
      :initial-values="item ?? null"
      :field-errors="fieldErrors"
      hide-footer
      @submit="handleSubmit"
    />
  </div>
</template>
