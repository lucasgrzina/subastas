<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { PlusOutlined } from '@ant-design/icons-vue'
import InfluencerFilters from '../components/InfluencerFilters.vue'
import InfluencersTable from '../components/InfluencersTable.vue'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'
import PermissionGuard from '@/components/shared/PermissionGuard.vue'
import { useInfluencers } from '../composables/useInfluencers'
import { useDeleteInfluencer } from '../composables/useDeleteInfluencer'
import { useToggleInfluencer } from '../composables/useToggleInfluencer'
import { useInfluencerFilters } from '../composables/useInfluencerFilters'
import type { InfluencerFilters as InfluencerFiltersType, InfluencerItem } from '../types/influencer.types'

const router = useRouter()
const { filters, debouncedSearch } = useInfluencerFilters()
const { deleteInfluencer } = useDeleteInfluencer()
const { mutate: toggleInfluencer, pendingGuid: togglePendingGuid } = useToggleInfluencer()

const filtersModel = computed<InfluencerFiltersType>({
  get: () => filters,
  set: (val) => Object.assign(filters, val),
})

const queryParams = computed(() => ({
  ...filters,
  search: debouncedSearch.value,
}))

const { data, isLoading } = useInfluencers(queryParams)

function handleEdit(influencer: InfluencerItem) {
  router.push({ name: 'influencers-edit', params: { guid: influencer.guid } })
}
</script>

<template>
  <div>
    <AppHeader title="Influencers" subtitle="Personajes de IA para generación de contenido">
      <template #actions="{ buttonSize }">
        <PermissionGuard permission="influencers.create">
          <BaseButton variant="primary" :size="buttonSize" @click="router.push({ name: 'influencers-create' })">
            <PlusOutlined />
            Nuevo influencer
          </BaseButton>
        </PermissionGuard>
      </template>
    </AppHeader>

    <InfluencerFilters v-model:filters="filtersModel" />

    <InfluencersTable
      :influencers="data?.data ?? []"
      :loading="isLoading"
      :pending-guid="togglePendingGuid"
      @edit="handleEdit"
      @toggle="(influencer) => toggleInfluencer(influencer.guid)"
      @delete="deleteInfluencer"
    />

    <BasePagination
      :page="filters.page"
      :total="data?.total ?? 0"
      :per-page="filters.per_page"
      @change="({ page, perPage }) => { filters.page = page; filters.per_page = perPage }"
    />
  </div>
</template>
