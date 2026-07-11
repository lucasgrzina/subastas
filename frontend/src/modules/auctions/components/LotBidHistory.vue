<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatDate } from '@/core/utils/date'
import { useBidHistory } from '../composables/useBidHistory'
import type { TableColumnDef } from '@/core/composables/useColumnVisibility'

const props = defineProps<{ guid: string }>()

const { t } = useI18n()
const { data, isLoading } = useBidHistory(computed(() => props.guid))

const columns: TableColumnDef[] = [
  { title: 'Monto', key: 'amount' },
  { title: 'Oferente', key: 'bidder' },
  { title: 'Fecha', key: 'created_at' },
]
</script>

<template>
  <div>
    <h3 class="lbh-title">{{ t('lots.detail.bidHistory') }}</h3>

    <EmptyState
      v-if="!isLoading && !data?.data.length"
      :message="t('lots.detail.noBids')"
      icon="📜"
    />

    <BaseDataTable
      v-else
      :columns="columns"
      :data-source="data?.data ?? []"
      :loading="isLoading"
      row-key="guid"
      :pagination="false"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'amount'">
          <strong>${{ record.amount }}</strong>
        </template>
        <template v-else-if="column.key === 'bidder'">
          {{ record.user?.name ?? '—' }}
        </template>
        <template v-else-if="column.key === 'created_at'">
          {{ formatDate(record.created_at) }}
        </template>
      </template>
    </BaseDataTable>
  </div>
</template>

<style scoped>
.lbh-title {
  font-size: 15px;
  font-weight: 600;
  margin: 0 0 12px;
}
</style>
