<script setup lang="ts">
import { ref } from 'vue'
import { PlusOutlined, DeleteOutlined } from '@ant-design/icons-vue'
import { useI18n } from 'vue-i18n'
import { usePublishedProducts } from '../../composables/usePublishedProducts'
import type { LotProductEntry } from '../../validators/lot.validator'

const modelValue = defineModel<LotProductEntry[]>({ required: true })

const { t } = useI18n()

const search = ref<string | undefined>(undefined)
const { data: products, isLoading } = usePublishedProducts(search)

const productOptions = () => (products.value ?? []).map((p) => ({ label: p.title, value: p.guid }))

function addRow() {
  modelValue.value = [...modelValue.value, { product_guid: '', quantity: 1 }]
}

function removeRow(index: number) {
  modelValue.value = modelValue.value.filter((_, i) => i !== index)
}

function updateProduct(index: number, guid: string) {
  modelValue.value = modelValue.value.map((row, i) => (i === index ? { ...row, product_guid: guid } : row))
}

function updateQuantity(index: number, quantity: number) {
  modelValue.value = modelValue.value.map((row, i) => (i === index ? { ...row, quantity } : row))
}
</script>

<template>
  <div class="lot-composer">
    <div v-if="!modelValue.length" class="lot-composer__empty">
      {{ t('lots.composer.empty') }}
    </div>

    <div v-for="(row, index) in modelValue" :key="index" class="lot-composer__row">
      <a-select
        :value="row.product_guid || undefined"
        :options="productOptions()"
        :loading="isLoading"
        show-search
        :placeholder="t('lots.composer.product')"
        style="flex: 1"
        :filter-option="false"
        @search="(v: string) => (search = v)"
        @update:value="(v: string) => updateProduct(index, v)"
      />
      <a-input-number
        :value="row.quantity"
        :min="1"
        :precision="0"
        :placeholder="t('lots.composer.quantity')"
        style="width: 120px"
        @update:value="(v: number) => updateQuantity(index, v)"
      />
      <BaseButton
        variant="row-action"
        size="small"
        danger
        :tooltip="t('lots.composer.remove')"
        @click="removeRow(index)"
      >
        <template #icon><DeleteOutlined /></template>
      </BaseButton>
    </div>

    <BaseButton variant="secondary" size="small" @click="addRow">
      <template #icon><PlusOutlined /></template>
      {{ t('lots.composer.addProduct') }}
    </BaseButton>
  </div>
</template>

<style scoped>
.lot-composer {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.lot-composer__row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.lot-composer__empty {
  font-size: 12px;
  color: var(--dt-muted, #6b8cae);
}
</style>
