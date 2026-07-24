<script setup lang="ts">
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { useI18n } from 'vue-i18n'
import { bidSchema, type BidFormValues } from '../validators/bid.validator'
import { usePlaceBid } from '../composables/usePlaceBid'

const props = defineProps<{ guid: string }>()

const { t } = useI18n()
const { mutate, isPending, fieldErrors, generalError } = usePlaceBid()

const { errors, defineField, handleSubmit, resetForm, setErrors } = useForm({
  validationSchema: toTypedSchema(bidSchema),
})

const [amount, amountAttrs] = defineField('amount')

const onSubmit = handleSubmit((values: BidFormValues) => {
  mutate(
    { guid: props.guid, payload: values },
    {
      onSuccess: () => resetForm(),
      onError: () => setErrors(fieldErrors.value ?? {}),
    },
  )
})
</script>

<template>
  <a-form layout="inline" class="place-bid-form" @submit.prevent="onSubmit">
    <a-form-item
      :label="t('bids.form.amount')"
      :validate-status="errors.amount ? 'error' : ''"
      :help="errors.amount ?? ''"
    >
      <a-input v-model:value="amount" v-bind="amountAttrs" placeholder="0.00" style="width: 140px" />
    </a-form-item>
    <a-form-item>
      <BaseButton variant="primary" :loading="isPending" html-type="submit">
        {{ t('bids.form.submit') }}
      </BaseButton>
    </a-form-item>
    <span v-if="generalError" class="place-bid-form__error">{{ generalError }}</span>
  </a-form>
</template>

<style scoped>
.place-bid-form {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  flex-wrap: wrap;
}
.place-bid-form__error {
  font-size: 12px;
  color: #FF5A6A;
  align-self: center;
}
</style>
