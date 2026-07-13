<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { PlusOutlined } from '@ant-design/icons-vue'
import { useI18n } from 'vue-i18n'
import CurrencyFilters from '../components/CurrencyFilters.vue'
import CurrenciesTable from '../components/CurrenciesTable.vue'
import { useCurrencies } from '../composables/useCurrencies'
import { useDeleteCurrency } from '../composables/useDeleteCurrency'
import { useCurrencyFilters } from '../composables/useCurrencyFilters'
import type { CurrencyFilters as CurrencyFiltersType, CurrencyItem } from '../types/currency.types'

const router = useRouter()
const { t } = useI18n()
const { filters, debouncedSearch } = useCurrencyFilters()
const { deleteCurrency } = useDeleteCurrency()

const filtersModel = computed<CurrencyFiltersType>({
  get: () => filters,
  set: (val) => Object.assign(filters, val),
})

const queryParams = computed(() => ({
  ...filters,
  search: debouncedSearch.value,
}))

const { data, isLoading } = useCurrencies(queryParams)

function handleEdit(item: CurrencyItem) {
  router.push({ name: 'currencies-edit', params: { guid: item.guid } })
}
</script>

<template>
  <div>
    <AppHeader :title="t('currencies.title')" :subtitle="t('currencies.subtitle')">
      <template #actions="{ buttonSize }">
        <PermissionGuard permission="currencies.create">
          <BaseButton variant="primary" :size="buttonSize" @click="router.push({ name: 'currencies-create' })">
            <template #icon><PlusOutlined /></template>
            {{ t('currencies.new') }}
          </BaseButton>
        </PermissionGuard>
      </template>
    </AppHeader>

    <CurrencyFilters v-model:filters="filtersModel" />

    <EmptyState
      v-if="!isLoading && !data?.data.length"
      :message="t('currencies.empty')"
      icon="🗂️"
    >
      <PermissionGuard permission="currencies.create">
        <BaseButton variant="primary" class="mt-3" @click="router.push({ name: 'currencies-create' })">
          <template #icon><PlusOutlined /></template>
          {{ t('currencies.createFirst') }}
        </BaseButton>
      </PermissionGuard>
    </EmptyState>

    <CurrenciesTable
      v-else
      :currencies="data?.data ?? []"
      :loading="isLoading"
      @edit="handleEdit"
      @delete="deleteCurrency"
    />

    <BasePagination
      :page="filters.page"
      :total="data?.total ?? 0"
      :per-page="filters.per_page"
      @change="({ page, perPage }) => { filters.page = page; filters.per_page = perPage }"
    />
  </div>
</template>

<style scoped>
.mt-3 { margin-top: 12px; }
</style>
