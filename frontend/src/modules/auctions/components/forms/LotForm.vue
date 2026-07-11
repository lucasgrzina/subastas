<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { useI18n } from 'vue-i18n'
import LotProductsComposer from './LotProductsComposer.vue'
import { lotDetailsSchema, lotProductsSchema, type LotDetailsFormValues, type LotProductEntry } from '../../validators/lot.validator'
import { useAuctions } from '../../composables/useAuctions'
import type { LotItem, CreateLotPayload, UpdateLotPayload } from '../../types/lot.types'

const props = withDefaults(
  defineProps<{
    mode: 'create' | 'edit'
    initialValues?: LotItem | null
    presetAuctionGuid?: string
    fieldErrors?: Record<string, string> | null
    loading?: boolean
    hideFooter?: boolean
  }>(),
  { loading: false, hideFooter: false },
)

const emit = defineEmits<{
  submit: [values: CreateLotPayload | UpdateLotPayload]
}>()

const { t } = useI18n()

const { data: auctionsData, isLoading: loadingAuctions } = useAuctions({ per_page: 100 })
const auctionOptions = computed(() =>
  (auctionsData.value?.data ?? []).map((a) => ({ label: a.title, value: a.guid })),
)

const { errors, defineField, handleSubmit, setErrors, setValues } = useForm({
  validationSchema: toTypedSchema(lotDetailsSchema),
})

const [lotNumber, lotNumberAttrs] = defineField('lot_number')
const [startingPrice, startingPriceAttrs] = defineField('starting_price')
const [bidIncrement, bidIncrementAttrs] = defineField('bid_increment')
const [reservePrice, reservePriceAttrs] = defineField('reserve_price')
const [status] = defineField('status')

const auctionGuid = ref<string | undefined>(props.presetAuctionGuid)
const products = ref<LotProductEntry[]>([])
const productsError = ref<string | null>(null)

watch(
  () => props.initialValues,
  (lot) => {
    if (props.mode === 'edit' && lot) {
      setValues({
        lot_number: lot.lot_number,
        starting_price: lot.starting_price,
        bid_increment: lot.bid_increment,
        reserve_price: lot.reserve_price ?? '',
        status: lot.status === 'sold' || lot.status === 'unsold' ? undefined : lot.status,
      })
      auctionGuid.value = lot.auction?.guid
      products.value = lot.products.map((p) => ({ product_guid: p.guid, quantity: p.quantity }))
    } else if (props.mode === 'create') {
      setValues({ status: 'scheduled' })
      auctionGuid.value = props.presetAuctionGuid
      products.value = []
    }
  },
  { immediate: true },
)

watch(() => props.fieldErrors, (errs) => {
  setErrors(errs ?? {})
})

const statusOptions = computed(() => [
  { label: t('lots.status.scheduled'), value: 'scheduled' },
  { label: t('lots.status.open'), value: 'open' },
])

const onSubmit = handleSubmit((values: LotDetailsFormValues) => {
  const validation = lotProductsSchema.safeParse(products.value)

  if (!validation.success) {
    productsError.value = validation.error.issues[0]?.message ?? 'Revisá los productos del lote.'
    return
  }

  productsError.value = null

  if (props.mode === 'create') {
    if (!auctionGuid.value) {
      productsError.value = null
      return
    }

    emit('submit', {
      auction_guid: auctionGuid.value,
      lot_number: values.lot_number,
      starting_price: values.starting_price,
      bid_increment: values.bid_increment,
      reserve_price: values.reserve_price || null,
      status: values.status,
      products: validation.data,
    } as CreateLotPayload)
  } else {
    emit('submit', {
      lot_number: values.lot_number,
      starting_price: values.starting_price,
      bid_increment: values.bid_increment,
      reserve_price: values.reserve_price || null,
      status: values.status,
      products: validation.data,
    } as UpdateLotPayload)
  }
})

defineExpose({ submit: onSubmit })
</script>

<template>
  <a-form layout="vertical" @submit.prevent="onSubmit">
    <FormSection>
      <a-row :gutter="12">
        <a-col :xs="24" :md="12">
          <a-form-item :label="t('lots.form.auction')">
            <a-select
              v-if="mode === 'create'"
              v-model:value="auctionGuid"
              :options="auctionOptions"
              :loading="loadingAuctions"
              show-search
              :filter-option="(input: string, option: { label: string }) => option.label.toLowerCase().includes(input.toLowerCase())"
            />
            <span v-else>{{ initialValues?.auction?.title ?? '—' }}</span>
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="12">
          <a-form-item
            :label="t('lots.form.lot_number')"
            :validate-status="errors.lot_number ? 'error' : ''"
            :help="errors.lot_number ?? ''"
          >
            <a-input v-model:value="lotNumber" v-bind="lotNumberAttrs" />
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="8">
          <a-form-item
            :label="t('lots.form.starting_price')"
            :validate-status="errors.starting_price ? 'error' : ''"
            :help="errors.starting_price ?? ''"
          >
            <a-input v-model:value="startingPrice" v-bind="startingPriceAttrs" placeholder="0.00" />
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="8">
          <a-form-item
            :label="t('lots.form.bid_increment')"
            :validate-status="errors.bid_increment ? 'error' : ''"
            :help="errors.bid_increment ?? ''"
          >
            <a-input v-model:value="bidIncrement" v-bind="bidIncrementAttrs" placeholder="0.00" />
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="8">
          <a-form-item
            :label="t('lots.form.reserve_price')"
            :validate-status="errors.reserve_price ? 'error' : ''"
            :help="errors.reserve_price ?? t('lots.form.reservePriceHint')"
          >
            <a-input v-model:value="reservePrice" v-bind="reservePriceAttrs" placeholder="0.00" />
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="8">
          <a-form-item :label="t('lots.form.status')">
            <a-select v-model:value="status" :options="statusOptions" />
          </a-form-item>
        </a-col>
      </a-row>
    </FormSection>

    <FormSection :title="t('lots.form.products')">
      <LotProductsComposer v-model="products" />
      <span v-if="productsError" class="lf-error">{{ productsError }}</span>
    </FormSection>

    <FormFooter
      v-if="!hideFooter"
      :loading="loading"
      cancel-to="/lots"
      :save-label="mode === 'edit' ? t('lots.form.updateSave') : t('lots.form.createSave')"
    />
  </a-form>
</template>

<style scoped>
.lf-error {
  font-size: 12px;
  color: #FF5A6A;
}
</style>
