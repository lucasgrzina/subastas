<script setup lang="ts">
import { computed, ref } from 'vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import RoleFilters from '../components/RoleFilters.vue'
import RolesTable from '../components/RolesTable.vue'
import RoleFormDrawer from '../components/RoleFormDrawer.vue'
import ExportButton from '@/modules/exports/components/ExportButton.vue'
import ColumnSelectorButton from '@/components/tables/ColumnSelectorButton.vue'
import ColumnSelectorDrawer from '@/components/tables/ColumnSelectorDrawer.vue'
import { useRoles } from '../composables/useRoles'
import { useDeleteRole } from '../composables/useDeleteRole'
import { useRoleFilters } from '../composables/useRoleFilters'
import type { RoleFilters as RoleFiltersType } from '../types/role.types'
import { ROLE_EXPORT_COLUMNS } from '../types/role.enums'
import { useColumnVisibility } from '@/core/composables/useColumnVisibility'
import type { TableColumnDef } from '@/core/composables/useColumnVisibility'

const ROLE_TABLE_COLUMNS: TableColumnDef[] = [
  { key: 'name',        title: 'Nombre' },
  { key: 'permissions', title: 'Permisos' },
  { key: 'created_at',  title: 'Creado' },
  { key: 'actions',     title: 'Acciones', width: 120, alwaysVisible: true },
]

const { visibleKeys, visibleColumns, selectableColumns, isDrawerOpen } =
  useColumnVisibility('roles-table', ROLE_TABLE_COLUMNS)

const { filters, debouncedSearch } = useRoleFilters()

const formDrawerOpen = ref(false)
const editingGuid = ref<string | undefined>(undefined)

function openCreateDrawer() {
  editingGuid.value = undefined
  formDrawerOpen.value = true
}

function openEditDrawer(guid: string) {
  editingGuid.value = guid
  formDrawerOpen.value = true
}

const { deleteRole } = useDeleteRole()

const filtersModel = computed<RoleFiltersType>({
  get: () => filters,
  set: (val) => Object.assign(filters, val),
})

const { data, isLoading } = useRoles(computed(() => ({ ...filters, search: debouncedSearch.value })))

const exportFilters = computed<Record<string, string | undefined>>(() => ({
  search: filtersModel.value.search || undefined,
}))
</script>

<template>
  <div>
    <AppHeader title="Roles" subtitle="Gestioná los roles y sus permisos asociados.">
      <template #actions="{ buttonSize }">
        <ExportButton
          :size="buttonSize"
          export-type="roles"
          :filters="exportFilters"
          :available-columns="ROLE_EXPORT_COLUMNS"
        />
        <BaseButton :size="buttonSize" @click="openCreateDrawer">
          <template #icon><PlusOutlined /></template>
          Nuevo rol
        </BaseButton>
      </template>
    </AppHeader>

    <RoleFilters v-model:filters="filtersModel" />

    <div class="page-toolbar">
      <ColumnSelectorButton @click="isDrawerOpen = true" />
    </div>

    <EmptyState
      v-if="!isLoading && !data?.data.length"
      message="No se encontraron roles."
      icon="🔐"
    >
      <BaseButton variant="primary" class="mt-3" @click="openCreateDrawer">
        <template #icon><PlusOutlined /></template>
        Crear primer rol
      </BaseButton>
    </EmptyState>

    <RolesTable
      v-else
      :roles="data?.data ?? []"
      :loading="isLoading"
      :columns="visibleColumns"
      @edit="(role) => openEditDrawer(role.guid)"
      @delete="deleteRole"
    />

    <BasePagination
      :page="filters.page"
      :total="data?.total ?? 0"
      :per-page="filters.per_page"
      @change="({ page, perPage }) => { filters.page = page; filters.per_page = perPage }"
    />

    <ColumnSelectorDrawer
      v-model="isDrawerOpen"
      v-model:keys="visibleKeys"
      :columns="selectableColumns"
    />

    <RoleFormDrawer v-model="formDrawerOpen" :guid="editingGuid" />
  </div>
</template>

<style scoped>
.mt-3 { margin-top: 12px; }

.page-toolbar {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 8px;
}
</style>
