<script setup lang="ts">
import { computed } from 'vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import ApiClientFilters from '../components/ApiClientFilters.vue'
import ApiClientsTable from '../components/ApiClientsTable.vue'
import ApiClientDrawer from '../components/drawers/ApiClientDrawer.vue'
import TokenDisplayModal from '../components/modals/TokenDisplayModal.vue'
import ExportButton from '@/modules/exports/components/ExportButton.vue'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'
import ColumnSelectorButton from '@/components/tables/ColumnSelectorButton.vue'
import ColumnSelectorDrawer from '@/components/tables/ColumnSelectorDrawer.vue'
import PermissionGuard from '@/components/shared/PermissionGuard.vue'
import { useApiClients } from '../composables/useApiClients'
import { useDeleteApiClient } from '../composables/useDeleteApiClient'
import { useToggleApiClient } from '../composables/useToggleApiClient'
import { useApiClientsUiStore } from '../stores/api-clients-ui.store'
import { useApiClientFilters } from '../composables/useApiClientFilters'
import { API_CLIENT_EXPORT_COLUMNS } from '../types/api-client.enums'
import { useColumnVisibility } from '@/core/composables/useColumnVisibility'
import type { TableColumnDef } from '@/core/composables/useColumnVisibility'
import type { ApiClientFilters as ApiClientFiltersType } from '../types/api-client.types'
import type { ApiClientItem } from '../types/api-client.types'

const API_CLIENT_TABLE_COLUMNS: TableColumnDef[] = [
  { key: 'nombre',       title: 'Nombre',        dataIndex: 'nombre'       },
  { key: 'email',        title: 'Email',          dataIndex: 'email'        },
  { key: 'token_hint',   title: 'Token'                                     },
  { key: 'active',       title: 'Activo'                                    },
  { key: 'last_used_at', title: 'Último uso'                                },
  { key: 'created_at',   title: 'Fecha de alta'                             },
  { key: 'actions',      title: 'Acciones', width: 140, alwaysVisible: true },
]

const { visibleKeys, visibleColumns, selectableColumns, isDrawerOpen: isColumnDrawerOpen } =
  useColumnVisibility('api-clients-table', API_CLIENT_TABLE_COLUMNS)

const uiStore = useApiClientsUiStore()
const { filters, debouncedSearch } = useApiClientFilters()
const { deleteApiClient } = useDeleteApiClient()
const { mutate: toggleApiClient, pendingGuid: togglePendingGuid } = useToggleApiClient()

const filtersModel = computed<ApiClientFiltersType>({
  get: () => filters,
  set: (val) => Object.assign(filters, val),
})

const queryParams = computed(() => ({
  ...filters,
  search: debouncedSearch.value,
}))

const { data, isLoading } = useApiClients(queryParams)

const exportFilters = computed<Record<string, string | undefined>>(() => ({
  search:    filters.search    || undefined,
  date_from: filters.date_from || undefined,
  date_to:   filters.date_to   || undefined,
}))

const showDrawer = computed({
  get: () => uiStore.activeModal !== null,
  set: (v) => { if (!v) uiStore.closeModal() },
})

const showTokenModal = computed({
  get: () => Boolean(uiStore.newPlainToken),
  set: (v) => { if (!v) uiStore.clearNewToken() },
})

function handleClientCreated(token: string) {
  uiStore.setNewToken(token)
}

function handleEdit(client: ApiClientItem) {
  uiStore.openModal('edit', client)
}
</script>

<template>
  <div>
    <AppHeader title="Clientes API" subtitle="Credenciales de acceso a la API pública">
      <template #actions="{ buttonSize }">
        <ExportButton
          :size="buttonSize"
          export-type="api-clients"
          :filters="exportFilters"
          :available-columns="API_CLIENT_EXPORT_COLUMNS"
        />
        <PermissionGuard permission="api-clients.create">
          <BaseButton
            variant="primary"
            :size="buttonSize"
            @click="uiStore.openModal('create')"
          >
            <PlusOutlined />
            Nuevo cliente
          </BaseButton>
        </PermissionGuard>
      </template>
    </AppHeader>

    <ApiClientFilters v-model:filters="filtersModel" />

    <div class="page-toolbar">
      <ColumnSelectorButton @click="isColumnDrawerOpen = true" />
    </div>

    <ApiClientsTable
      :clients="data?.data ?? []"
      :loading="isLoading"
      :columns="visibleColumns"
      :pending-guid="togglePendingGuid"
      @edit="handleEdit"
      @toggle="(client) => toggleApiClient(client.guid)"
      @delete="deleteApiClient"
    />

    <BasePagination
      :page="filters.page"
      :total="data?.total ?? 0"
      :per-page="filters.per_page"
      @change="({ page, perPage }) => { filters.page = page; filters.per_page = perPage }"
    />

    <ApiClientDrawer
      v-model="showDrawer"
      :client="uiStore.selectedClient"
      @created="handleClientCreated"
    />

    <TokenDisplayModal
      v-model="showTokenModal"
      :token="uiStore.newPlainToken ?? ''"
      @close="uiStore.clearNewToken()"
    />

    <ColumnSelectorDrawer
      v-model="isColumnDrawerOpen"
      v-model:keys="visibleKeys"
      :columns="selectableColumns"
    />
  </div>
</template>

<style scoped>
.page-toolbar {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-bottom: 8px;
}
</style>
