<script setup lang="ts">
import { EditOutlined, DeleteOutlined } from '@ant-design/icons-vue'
import { formatDate } from '@/core/utils/date'
import type { InfluencerItem } from '../types/influencer.types'
import type { TableColumnDef } from '@/core/composables/useColumnVisibility'

const props = defineProps<{
  influencers: InfluencerItem[]
  loading: boolean
  pendingGuid?: string | null
}>()

const emit = defineEmits<{
  edit: [influencer: InfluencerItem]
  toggle: [influencer: InfluencerItem]
  delete: [influencer: InfluencerItem]
}>()

const columns: TableColumnDef[] = [
  { title: '', key: 'imagen', width: 64 },
  { title: 'Nombre', dataIndex: 'nombre', key: 'nombre' },
  { title: 'Alias', dataIndex: 'alias', key: 'alias' },
  { title: 'Especialidad', dataIndex: 'especialidad', key: 'especialidad' },
  { title: 'Activo', key: 'activo' },
  { title: 'Alta', key: 'created_at' },
  { title: 'Acciones', key: 'actions', width: 120, alwaysVisible: true },
]
</script>

<template>
  <BaseDataTable
    :columns="columns"
    :data-source="influencers"
    :loading="loading"
    row-key="guid"
    :scroll="{ x: 800 }"
    :pagination="false"
  >
    <template #bodyCell="{ column, record }">
      <template v-if="column.key === 'imagen'">
        <a-avatar :src="record.character_image_full_url ?? undefined" shape="square" :size="40">
          {{ record.nombre?.charAt(0) }}
        </a-avatar>
      </template>

      <template v-else-if="column.key === 'alias'">
        <span>{{ record.alias ?? '—' }}</span>
      </template>

      <template v-else-if="column.key === 'especialidad'">
        <span>{{ record.especialidad ?? '—' }}</span>
      </template>

      <template v-else-if="column.key === 'activo'">
        <PermissionGuard permission="influencers.update">
          <a-switch
            :checked="record.activo"
            :loading="props.pendingGuid === record.guid"

            @change="emit('toggle', record)"
          />
          <template #fallback>
            <a-badge :status="record.activo ? 'success' : 'error'" :text="record.activo ? 'Activo' : 'Inactivo'" />
          </template>
        </PermissionGuard>
      </template>

      <template v-else-if="column.key === 'created_at'">
        {{ formatDate(record.created_at) }}
      </template>

      <template v-else-if="column.key === 'actions'">
        <BaseTableActions>
          <PermissionGuard permission="influencers.update">
            <BaseButton variant="row-action" size="small" tooltip="Editar" @click="emit('edit', record)">
              <template #icon><EditOutlined /></template>
            </BaseButton>
          </PermissionGuard>
          <PermissionGuard permission="influencers.delete">
            <BaseButton variant="row-action" size="small" tooltip="Eliminar" danger @click="emit('delete', record)">
              <template #icon><DeleteOutlined /></template>
            </BaseButton>
          </PermissionGuard>
        </BaseTableActions>
      </template>
    </template>
  </BaseDataTable>
</template>
