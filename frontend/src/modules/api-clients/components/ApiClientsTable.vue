<script setup lang="ts">
import { computed } from 'vue'
import { EditOutlined, StopOutlined } from '@ant-design/icons-vue'
import { formatDate } from '@/core/utils/date'
import type { ApiClientItem } from '../types/api-client.types'
import type { TableColumnDef } from '@/core/composables/useColumnVisibility'

const props = defineProps<{
  clients:      ApiClientItem[]
  loading:      boolean
  columns?:     TableColumnDef[]
  pendingGuid?: string | null
}>()

const emit = defineEmits<{
  edit:   [client: ApiClientItem]
  toggle: [client: ApiClientItem]
  delete: [client: ApiClientItem]
}>()

const defaultColumns: TableColumnDef[] = [
  { title: 'Nombre',       dataIndex: 'nombre',       key: 'nombre'       },
  { title: 'Email',        dataIndex: 'email',        key: 'email'        },
  { title: 'Token',        key: 'token_hint'                              },
  { title: 'Activo',       key: 'active'                                  },
  { title: 'Último uso',   key: 'last_used_at'                            },
  { title: 'Fecha de alta', key: 'created_at'                             },
  { title: 'Acciones',     key: 'actions', width: 140, alwaysVisible: true },
]

const columns = computed(() => props.columns ?? defaultColumns)
</script>

<template>
  <BaseDataTable
    :columns="columns"
    :data-source="clients"
    :loading="loading"
    row-key="guid"
    :scroll="{ x: 900 }"
    :pagination="false"
  >
    <template #bodyCell="{ column, record }">
      <template v-if="column.key === 'email'">
        <span>{{ record.email ?? '—' }}</span>
      </template>

      <template v-else-if="column.key === 'token_hint'">
        <code style="font-family: monospace; font-size: 13px; letter-spacing: 0.05em">
          {{ record.token_hint }}
        </code>
      </template>

      <template v-else-if="column.key === 'active'">
        <PermissionGuard permission="api-clients.update">
          <a-switch
            :checked="record.active"
            :loading="props.pendingGuid === record.guid"
            @change="emit('toggle', record)"
          />
          <template #fallback>
            <a-badge
              :status="record.active ? 'success' : 'error'"
              :text="record.active ? 'Activo' : 'Inactivo'"
            />
          </template>
        </PermissionGuard>
      </template>

      <template v-else-if="column.key === 'last_used_at'">
        {{ record.last_used_at ? formatDate(record.last_used_at) : '—' }}
      </template>

      <template v-else-if="column.key === 'created_at'">
        {{ formatDate(record.created_at) }}
      </template>

      <template v-else-if="column.key === 'actions'">
        <BaseTableActions>
          <PermissionGuard permission="api-clients.update">
            <BaseButton
              variant="row-action"
              size="small"
              tooltip="Editar"
              @click="emit('edit', record)"
            >
              <template #icon><EditOutlined /></template>
            </BaseButton>
          </PermissionGuard>

          <PermissionGuard permission="api-clients.delete">
            <BaseButton
              variant="row-action"
              size="small"
              tooltip="Revocar"
              danger
              @click="emit('delete', record)"
            >
              <template #icon><StopOutlined /></template>
            </BaseButton>
          </PermissionGuard>
        </BaseTableActions>
      </template>
    </template>
  </BaseDataTable>
</template>
