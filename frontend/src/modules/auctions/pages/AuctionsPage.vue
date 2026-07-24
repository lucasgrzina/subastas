<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { PlusOutlined } from '@ant-design/icons-vue'
import { useI18n } from 'vue-i18n'
import AuctionFilters from '../components/AuctionFilters.vue'
import AuctionsTable from '../components/AuctionsTable.vue'
import { useAuctions } from '../composables/useAuctions'
import { useDeleteAuction } from '../composables/useDeleteAuction'
import { useAuctionFilters } from '../composables/useAuctionFilters'
import { useAuctionsUiStore } from '../stores/auctions-ui.store'
import type { AuctionFilters as AuctionFiltersType, AuctionItem } from '../types/auction.types'

const router = useRouter()
const { t } = useI18n()
const { filters, debouncedSearch } = useAuctionFilters()
const { deleteAuction } = useDeleteAuction()
const uiStore = useAuctionsUiStore()

const filtersModel = computed<AuctionFiltersType>({
  get: () => filters,
  set: (val) => Object.assign(filters, val),
})

const queryParams = computed(() => ({
  ...filters,
  search: debouncedSearch.value,
}))

const { data, isLoading } = useAuctions(queryParams)

function handleEdit(auction: AuctionItem) {
  router.push({ name: 'auctions-edit', params: { guid: auction.guid } })
}

function handleViewLots(auction: AuctionItem) {
  uiStore.setActiveAuction(auction.guid, auction.title)
  router.push({ name: 'lots', query: { auction: auction.guid } })
}
</script>

<template>
  <div>
    <AppHeader :title="t('auctions.title')" :subtitle="t('auctions.subtitle')">
      <template #actions="{ buttonSize }">
        <PermissionGuard permission="auctions.create">
          <BaseButton variant="primary" :size="buttonSize" @click="router.push({ name: 'auctions-create' })">
            <template #icon><PlusOutlined /></template>
            {{ t('auctions.new') }}
          </BaseButton>
        </PermissionGuard>
      </template>
    </AppHeader>

    <AuctionFilters v-model:filters="filtersModel" />

    <EmptyState
      v-if="!isLoading && !data?.data.length"
      :message="t('auctions.empty')"
      icon="🏷️"
    >
      <PermissionGuard permission="auctions.create">
        <BaseButton variant="primary" class="mt-3" @click="router.push({ name: 'auctions-create' })">
          <template #icon><PlusOutlined /></template>
          {{ t('auctions.createFirst') }}
        </BaseButton>
      </PermissionGuard>
    </EmptyState>

    <AuctionsTable
      v-else
      :auctions="data?.data ?? []"
      :loading="isLoading"
      @edit="handleEdit"
      @delete="deleteAuction"
      @view-lots="handleViewLots"
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
