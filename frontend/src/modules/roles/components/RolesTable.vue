<script setup lang="ts">
import { computed } from 'vue'
import { EditOutlined, DeleteOutlined, SafetyCertificateOutlined } from '@ant-design/icons-vue'
import type { RoleItem } from '../types/role.types'
import type { TableColumnDef } from '@/core/composables/useColumnVisibility'
import { formatDate } from '@/core/utils/date'
import { getRoleLabel } from '@/core/utils/roles'

const props = defineProps<{
  roles: RoleItem[]
  loading: boolean
  columns?: TableColumnDef[]
}>()

const emit = defineEmits<{
  edit: [role: RoleItem]
  delete: [role: RoleItem]
}>()

const defaultColumns: TableColumnDef[] = [
  { title: 'Nombre', key: 'name' },
  { title: 'Creado', key: 'created_at' },
  { title: 'Acciones', key: 'actions', width: 120, alwaysVisible: true },
]

const columns = computed(() => props.columns ?? defaultColumns)
</script>

<template>
  <BaseDataTable
    :columns="columns"
    :data-source="roles"
    :loading="loading"
    row-key="guid"
    :scroll="{ x: 800 }"
    :pagination="false"
  >
    <template #bodyCell="{ column, record }">
      <template v-if="column.key === 'name'">
        <div class="rl-name-cell">
          <div class="rl-icon-wrap">
            <SafetyCertificateOutlined style="font-size:14px; color:#1AE5A0" />
          </div>
          <span class="rl-name">{{ getRoleLabel(record.name) }}</span>
        </div>
      </template>

      <template v-else-if="column.key === 'created_at'">
        {{ formatDate(record.created_at) }}
      </template>

      <template v-else-if="column.key === 'actions'">
        <BaseTableActions>
          <BaseButton variant="row-action" size="small" tooltip="Editar" @click="emit('edit', record)">
            <template #icon><EditOutlined /></template>
          </BaseButton>
          <BaseButton
            variant="row-action"
            size="small"
            danger
            tooltip="Eliminar"
            :disabled="['super-admin', 'admin'].includes(record.name)"
            @click="emit('delete', record)"
          >
            <template #icon><DeleteOutlined /></template>
          </BaseButton>
        </BaseTableActions>
      </template>
    </template>
  </BaseDataTable>
</template>

<style scoped>
.rl-name-cell { display: flex; align-items: center; gap: 10px; }
.rl-icon-wrap {
  width: 30px; height: 30px;
  border-radius: 8px;
  background: rgba(26,229,160,0.1);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.rl-name { font-weight: 600; }
</style>
