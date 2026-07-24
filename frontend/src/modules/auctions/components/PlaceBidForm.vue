<script setup lang="ts">
import { computed, watch } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { useI18n } from 'vue-i18n'
import { bidSchema, type BidFormValues } from '../validators/bid.validator'
import { usePlaceBid } from '../composables/usePlaceBid'
import { useLot } from '../composables/useLot'

const props = defineProps<{ guid: string }>()

const { t } = useI18n()
const { mutate, isPending, fieldErrors, generalError } = usePlaceBid()
const { data: lot } = useLot(computed(() => props.guid))

/**
 * The minimum acceptable bid, mirroring the backend floor: the base price for
 * the opening bid (no current price yet), otherwise current price + increment.
 * This is a display/prefill hint only — the backend comparison stays
 * authoritative and race-safe.
 */
const isFirstBid = computed(() => lot.value != null && lot.value.current_price === null)
const minBid = computed<string | null>(() => {
  const l = lot.value
  if (!l) return null

  return l.current_price === null
    ? Number(l.starting_price).toFixed(2)
    : (Number(l.current_price) + Number(l.bid_increment)).toFixed(2)
})

const { errors, defineField, handleSubmit, resetForm, setErrors } = useForm({
  validationSchema: toTypedSchema(bidSchema),
})

const [amount, amountAttrs] = defineField('amount')

// Prefill the amount with the minimum once the lot loads, but never clobber a
// value the user has already started typing.
watch(minBid, (value) => {
  if (value && !amount.value) {
    amount.value = value
  }
})

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
      :help="errors.amount ?? (minBid ? t(isFirstBid ? 'bids.form.minFirst' : 'bids.form.minNext', { amount: minBid }) : '')"
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
