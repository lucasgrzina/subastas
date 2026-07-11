<script setup lang="ts">
import { EditOutlined, DeleteOutlined, EyeOutlined, LockOutlined } from '@ant-design/icons-vue'
import { useI18n } from 'vue-i18n'
import type { LotItem, LotStatus } from '../types/lot.types'
import type { TableColumnDef } from '@/core/composables/useColumnVisibility'

const props = defineProps<{
  lots: LotItem[]
  loading: boolean
}>()

const emit = defineEmits<{
  view: [lot: LotItem]
  edit: [lot: LotItem]
  delete: [lot: LotItem]
  close: [lot: LotItem]
}>()

const { t } = useI18n()

const columns: TableColumnDef[] = [
  { title: 'Lote', dataIndex: 'lot_number', key: 'lot_number' },
  { title: 'Subasta', key: 'auction' },
  { title: 'Productos', key: 'products' },
  { title: 'Estado', key: 'status' },
  { title: 'Precio actual', key: 'current_price' },
  { title: 'Acciones', key: 'actions', width: 180, alwaysVisible: true },
]

const statusColor: Record<LotStatus, string> = {
  scheduled: 'blue',
  open: 'green',
  sold: 'gold',
  unsold: 'default',
}
</script>

<template>
  <BaseDataTable
    :columns="columns"
    :data-source="props.lots"
    :loading="loading"
    row-key="guid"
    :scroll="{ x: 1000 }"
    :pagination="false"
  >
    <template #bodyCell="{ column, record }">
      <template v-if="column.key === 'auction'">
        {{ record.auction?.title ?? '—' }}
      </template>

      <template v-else-if="column.key === 'products'">
        <a-tag v-for="p in record.products" :key="p.guid">{{ p.title }} × {{ p.quantity }}</a-tag>
      </template>

      <template v-else-if="column.key === 'status'">
        <a-tag :color="statusColor[record.status as LotStatus]">
          {{ t(`lots.status.${record.status}`) }}
        </a-tag>
      </template>

      <template v-else-if="column.key === 'current_price'">
        {{ record.current_price ? `$${record.current_price}` : '—' }}
      </template>

      <template v-else-if="column.key === 'actions'">
        <BaseTableActions>
          <BaseButton variant="row-action" size="small" tooltip="Ver detalle" @click="emit('view', record)">
            <template #icon><EyeOutlined /></template>
          </BaseButton>
          <PermissionGuard permission="lots.update">
            <BaseButton
              variant="row-action"
              size="small"
              tooltip="Editar"
              :disabled="record.status === 'sold' || record.status === 'unsold'"
              @click="emit('edit', record)"
            >
              <template #icon><EditOutlined /></template>
            </BaseButton>
          </PermissionGuard>
          <PermissionGuard permission="lots.close">
            <BaseButton
              variant="row-action"
              size="small"
              :tooltip="t('lots.close.action')"
              :disabled="record.status !== 'open'"
              @click="emit('close', record)"
            >
              <template #icon><LockOutlined /></template>
            </BaseButton>
          </PermissionGuard>
          <PermissionGuard permission="lots.delete">
            <BaseButton variant="row-action" size="small" danger tooltip="Eliminar" @click="emit('delete', record)">
              <template #icon><DeleteOutlined /></template>
            </BaseButton>
          </PermissionGuard>
        </BaseTableActions>
      </template>
    </template>
  </BaseDataTable>
</template>
