<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { PlusOutlined, CloseOutlined } from '@ant-design/icons-vue'
import { useI18n } from 'vue-i18n'
import LotFilters from '../components/LotFilters.vue'
import LotsTable from '../components/LotsTable.vue'
import { useLots } from '../composables/useLots'
import { useDeleteLot } from '../composables/useDeleteLot'
import { useCloseLot } from '../composables/useCloseLot'
import { useLotFilters } from '../composables/useLotFilters'
import { useAuctionsUiStore } from '../stores/auctions-ui.store'
import type { LotFilters as LotFiltersType, LotItem } from '../types/lot.types'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const { filters, debouncedSearch } = useLotFilters()
const { deleteLot } = useDeleteLot()
const { closeLot } = useCloseLot()
const uiStore = useAuctionsUiStore()

/**
 * The backend's `GET /v1/lots` has no `auction_guid` filter param (out of
 * scope for this change's backend slice). When arriving via "Ver lotes" from
 * an Auction, we fetch a larger page and filter client-side instead of
 * adding a new backend filter mid-frontend-batch — acceptable at this
 * platform's scale (~300 users); pagination is disabled while this filter
 * is active since it only reflects the currently-fetched page.
 */
const auctionFilterGuid = computed(() => (route.query.auction as string | undefined) ?? uiStore.activeAuctionGuid ?? undefined)

const filtersModel = computed<LotFiltersType>({
  get: () => filters,
  set: (val) => Object.assign(filters, val),
})

const queryParams = computed(() => ({
  ...filters,
  search: debouncedSearch.value,
  per_page: auctionFilterGuid.value ? 100 : filters.per_page,
}))

const { data, isLoading } = useLots(queryParams)

const visibleLots = computed<LotItem[]>(() => {
  const items = data.value?.data ?? []
  if (!auctionFilterGuid.value) return items
  return items.filter((lot) => lot.auction?.guid === auctionFilterGuid.value)
})

function clearAuctionFilter() {
  uiStore.clearActiveAuction()
  router.replace({ name: 'lots' })
}

function handleView(lot: LotItem) {
  router.push({ name: 'lots-detail', params: { guid: lot.guid } })
}

function handleEdit(lot: LotItem) {
  router.push({ name: 'lots-edit', params: { guid: lot.guid } })
}

function handleCreate() {
  router.push({ name: 'lots-create', query: auctionFilterGuid.value ? { auction: auctionFilterGuid.value } : {} })
}
</script>

<template>
  <div>
    <AppHeader :title="t('lots.title')" :subtitle="t('lots.subtitle')">
      <template #actions="{ buttonSize }">
        <PermissionGuard permission="lots.create">
          <BaseButton variant="primary" :size="buttonSize" @click="handleCreate">
            <template #icon><PlusOutlined /></template>
            {{ t('lots.new') }}
          </BaseButton>
        </PermissionGuard>
      </template>
    </AppHeader>

    <a-tag v-if="auctionFilterGuid" closable class="lots-page__filter-chip" @close="clearAuctionFilter">
      <template #closeIcon><CloseOutlined /></template>
      {{ t('lots.filteredByAuction') }} "{{ uiStore.activeAuctionTitle ?? auctionFilterGuid }}"
    </a-tag>

    <LotFilters v-model:filters="filtersModel" />

    <EmptyState
      v-if="!isLoading && !visibleLots.length"
      :message="t('lots.empty')"
      icon="📦"
    >
      <PermissionGuard permission="lots.create">
        <BaseButton variant="primary" class="mt-3" @click="handleCreate">
          <template #icon><PlusOutlined /></template>
          {{ t('lots.createFirst') }}
        </BaseButton>
      </PermissionGuard>
    </EmptyState>

    <LotsTable
      v-else
      :lots="visibleLots"
      :loading="isLoading"
      @view="handleView"
      @edit="handleEdit"
      @delete="deleteLot"
      @close="closeLot"
    />

    <BasePagination
      v-if="!auctionFilterGuid"
      :page="filters.page"
      :total="data?.total ?? 0"
      :per-page="filters.per_page"
      @change="({ page, perPage }) => { filters.page = page; filters.per_page = perPage }"
    />
  </div>
</template>

<style scoped>
.mt-3 { margin-top: 12px; }
.lots-page__filter-chip { margin-bottom: 12px; }
</style>
