<script setup lang="ts">
import { EditOutlined, DeleteOutlined, UndoOutlined } from '@ant-design/icons-vue'
import { formatDate } from '@/core/utils/date'
import type { ProductItem } from '../types/product.types'
import type { TableColumnDef } from '@/core/composables/useColumnVisibility'

const props = defineProps<{
  products: ProductItem[]
  loading: boolean
}>()

const emit = defineEmits<{
  edit: [product: ProductItem]
  delete: [product: ProductItem]
  restore: [product: ProductItem]
}>()

const columns: TableColumnDef[] = [
  { title: '', key: 'image', width: 64 },
  { title: 'Título', dataIndex: 'title', key: 'title' },
  { title: 'Bodega', key: 'winery' },
  { title: 'Año', key: 'year' },
  { title: 'Estado', key: 'status' },
  { title: 'Alta', key: 'created_at' },
  { title: 'Acciones', key: 'actions', width: 140, alwaysVisible: true },
]
</script>

<template>
  <BaseDataTable
    :columns="columns"
    :data-source="props.products"
    :loading="loading"
    row-key="guid"
    :scroll="{ x: 900 }"
    :pagination="false"
  >
    <template #bodyCell="{ column, record }">
      <template v-if="column.key === 'image'">
        <a-avatar :src="record.main_image_url ?? undefined" shape="square" :size="40">
          {{ record.title?.charAt(0) }}
        </a-avatar>
      </template>

      <template v-else-if="column.key === 'winery'">
        <span>{{ record.wine_details?.winery.name ?? '—' }}</span>
      </template>

      <template v-else-if="column.key === 'year'">
        <span>{{ record.wine_details?.year ?? '—' }}</span>
      </template>

      <template v-else-if="column.key === 'status'">
        <a-badge
          :status="record.status === 'published' ? 'success' : 'default'"
          :text="record.status === 'published' ? 'Publicado' : 'Borrador'"
        />
        <a-tag v-if="record.deleted_at" color="red" style="margin-left: 6px">Eliminado</a-tag>
      </template>

      <template v-else-if="column.key === 'created_at'">
        {{ formatDate(record.created_at) }}
      </template>

      <template v-else-if="column.key === 'actions'">
        <BaseTableActions>
          <template v-if="!record.deleted_at">
            <PermissionGuard permission="products.update">
              <BaseButton variant="row-action" size="small" tooltip="Editar" @click="emit('edit', record)">
                <template #icon><EditOutlined /></template>
              </BaseButton>
            </PermissionGuard>
            <PermissionGuard permission="products.delete">
              <BaseButton variant="row-action" size="small" tooltip="Eliminar" danger @click="emit('delete', record)">
                <template #icon><DeleteOutlined /></template>
              </BaseButton>
            </PermissionGuard>
          </template>
          <template v-else>
            <PermissionGuard permission="products.update">
              <BaseButton variant="row-action" size="small" tooltip="Restaurar" @click="emit('restore', record)">
                <template #icon><UndoOutlined /></template>
              </BaseButton>
            </PermissionGuard>
          </template>
        </BaseTableActions>
      </template>
    </template>
  </BaseDataTable>
</template>
