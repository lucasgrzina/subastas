<script setup lang="ts">
import { EditOutlined, DeleteOutlined } from '@ant-design/icons-vue'
import { useI18n } from 'vue-i18n'

import type { CurrencyItem } from '../types/currency.types'
import type { TableColumnDef } from '@/core/composables/useColumnVisibility'

const props = defineProps<{
  currencies: CurrencyItem[]
  loading: boolean
}>()

const emit = defineEmits<{
  edit: [item: CurrencyItem]
  delete: [item: CurrencyItem]
}>()

const { t } = useI18n()

const columns: TableColumnDef[] = [
  { title: t('currencies.table.code'), dataIndex: 'code', key: 'code' },
  { title: t('currencies.table.name'), dataIndex: 'name', key: 'name' },
  { title: t('currencies.table.symbol'), dataIndex: 'symbol', key: 'symbol' },
  { title: t('currencies.table.is_active'), key: 'is_active' },
  { title: t('currencies.table.actions'), key: 'actions', width: 160, alwaysVisible: true },
]
</script>

<template>
  <BaseDataTable
    :columns="columns"
    :data-source="props.currencies"
    :loading="loading"
    row-key="guid"
    :scroll="{ x: 900 }"
    :pagination="false"
  >
    <template #bodyCell="{ column, record }">
      <template v-if="column.key === 'is_active'">
        <a-tag :color="record.is_active ? 'green' : 'default'">{{ record.is_active ? 'Sí' : 'No' }}</a-tag>
      </template>
      <template v-else-if="column.key === 'actions'">
        <BaseTableActions>
          <PermissionGuard permission="currencies.update">
            <BaseButton variant="row-action" size="small" tooltip="Editar" @click="emit('edit', record)">
              <template #icon><EditOutlined /></template>
            </BaseButton>
          </PermissionGuard>
          <PermissionGuard permission="currencies.delete">
            <BaseButton variant="row-action" size="small" danger tooltip="Eliminar" @click="emit('delete', record)">
              <template #icon><DeleteOutlined /></template>
            </BaseButton>
          </PermissionGuard>
        </BaseTableActions>
      </template>
    </template>
  </BaseDataTable>
</template>
