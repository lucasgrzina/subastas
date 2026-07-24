<script setup lang="ts">
import { EditOutlined, DeleteOutlined, UnorderedListOutlined } from '@ant-design/icons-vue'
import { useI18n } from 'vue-i18n'
import { formatDate } from '@/core/utils/date'
import type { AuctionItem, AuctionStatus } from '../types/auction.types'
import type { TableColumnDef } from '@/core/composables/useColumnVisibility'

const props = defineProps<{
  auctions: AuctionItem[]
  loading: boolean
}>()

const emit = defineEmits<{
  edit: [auction: AuctionItem]
  delete: [auction: AuctionItem]
  viewLots: [auction: AuctionItem]
}>()

const { t } = useI18n()

const columns: TableColumnDef[] = [
  { title: 'Título', dataIndex: 'title', key: 'title' },
  { title: 'Inicio', key: 'starts_at' },
  { title: 'Fin', key: 'ends_at' },
  { title: 'Estado', key: 'status' },
  { title: 'Acciones', key: 'actions', width: 160, alwaysVisible: true },
]

const statusColor: Record<AuctionStatus, string> = {
  draft: 'default',
  scheduled: 'blue',
  live: 'green',
  closed: 'purple',
  cancelled: 'red',
}
</script>

<template>
  <BaseDataTable
    :columns="columns"
    :data-source="props.auctions"
    :loading="loading"
    row-key="guid"
    :scroll="{ x: 900 }"
    :pagination="false"
  >
    <template #bodyCell="{ column, record }">
      <template v-if="column.key === 'starts_at'">
        {{ formatDate(record.starts_at) }}
      </template>

      <template v-else-if="column.key === 'ends_at'">
        {{ record.ends_at ? formatDate(record.ends_at) : '—' }}
      </template>

      <template v-else-if="column.key === 'status'">
        <a-tag :color="statusColor[record.status as AuctionStatus]">
          {{ t(`auctions.status.${record.status}`) }}
        </a-tag>
      </template>

      <template v-else-if="column.key === 'actions'">
        <BaseTableActions>
          <BaseButton
            variant="row-action"
            size="small"
            :tooltip="t('auctions.viewLots')"
            @click="emit('viewLots', record)"
          >
            <template #icon><UnorderedListOutlined /></template>
          </BaseButton>
          <PermissionGuard permission="auctions.update">
            <BaseButton variant="row-action" size="small" tooltip="Editar" @click="emit('edit', record)">
              <template #icon><EditOutlined /></template>
            </BaseButton>
          </PermissionGuard>
          <PermissionGuard permission="auctions.delete">
            <BaseButton variant="row-action" size="small" danger tooltip="Eliminar" @click="emit('delete', record)">
              <template #icon><DeleteOutlined /></template>
            </BaseButton>
          </PermissionGuard>
        </BaseTableActions>
      </template>
    </template>
  </BaseDataTable>
</template>
