<script setup lang="ts">
import { computed } from 'vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import UserFilters from '../components/UserFilters.vue'
import UsersTable from '../components/UsersTable.vue'
import CreateUserModal from '../components/modals/CreateUserModal.vue'
import EditUserModal from '../components/modals/EditUserModal.vue'
import ChangePasswordModal from '../components/modals/ChangePasswordModal.vue'
import ResetPasswordModal from '../components/modals/ResetPasswordModal.vue'
import ExportButton from '@/modules/exports/components/ExportButton.vue'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'
import ColumnSelectorButton from '@/components/tables/ColumnSelectorButton.vue'
import ColumnSelectorDrawer from '@/components/tables/ColumnSelectorDrawer.vue'
import { useUsers } from '../composables/useUsers'
import { useToggleLock } from '../composables/useToggleLock'
import { useUsersUiStore } from '../stores/users.store'
import { useUserFilters } from '../composables/useUserFilters'
import { useResetPassword } from '../composables/useResetPassword'
import type { UserFilters as UserFiltersType } from '../types/user.types'
import { USER_EXPORT_COLUMNS } from '../types/user.enums'
import { useDeleteUser } from '../composables/useDeleteUser'
import { useColumnVisibility } from '@/core/composables/useColumnVisibility'
import type { TableColumnDef } from '@/core/composables/useColumnVisibility'

const USER_TABLE_COLUMNS: TableColumnDef[] = [
  { key: 'name',          title: 'Nombre completo', dataIndex: 'name' },
  { key: 'email',         title: 'Email',           dataIndex: 'email' },
  { key: 'roles',         title: 'Roles' },
  { key: 'status',        title: 'Estado' },
  { key: 'last_login_at', title: 'Último login' },
  { key: 'created_at',    title: 'Fecha registro' },
  { key: 'actions',       title: 'Acciones', width: 220, alwaysVisible: true },
]

const { visibleKeys, visibleColumns, selectableColumns, isDrawerOpen } =
  useColumnVisibility('users-table', USER_TABLE_COLUMNS)

const usersUiStore = useUsersUiStore()
const { filters, debouncedSearch } = useUserFilters()
const { toggleLockUser } = useToggleLock()
const { newPassword, showResult: showResetResult, resetPassword } = useResetPassword()
const { deleteUser } = useDeleteUser()

const filtersModel = computed<UserFiltersType>({
  get: () => filters,
  set: (val) => Object.assign(filters, val),
})

const queryParams = computed(() => ({
  ...filters,
  search: debouncedSearch.value,
}))

const { data, isLoading } = useUsers(queryParams)

const exportFilters = computed<Record<string, string | undefined>>(() => ({
  search:    filtersModel.value.search      || undefined,
  status:    filtersModel.value.status      || undefined,
  date_from: filtersModel.value.date_from   || undefined,
  date_to:   filtersModel.value.date_to     || undefined,
}))

const showCreate = computed({
  get: () => usersUiStore.activeModal === 'create',
  set: (v) => { if (!v) usersUiStore.closeModal() },
})
const showEdit = computed({
  get: () => usersUiStore.activeModal === 'edit',
  set: (v) => { if (!v) usersUiStore.closeModal() },
})
const showChangePassword = computed({
  get: () => usersUiStore.activeModal === 'changePassword',
  set: (v) => { if (!v) usersUiStore.closeModal() },
})
</script>

<template>
  <div>
    <AppHeader title="Usuarios" subtitle="Administración general">
      <template #actions="{ buttonSize }">
        <ExportButton
          :size="buttonSize"
          export-type="users"
          :filters="exportFilters"
          :available-columns="USER_EXPORT_COLUMNS"
        />
        <BaseButton variant="primary" :size="buttonSize" @click="usersUiStore.openModal('create')">
          <PlusOutlined />
          Nuevo usuario
        </BaseButton>
      </template>
    </AppHeader>

    <UserFilters v-model:filters="filtersModel" />

    <div class="page-toolbar">
      <ColumnSelectorButton @click="isDrawerOpen = true" />
    </div>

    <UsersTable
      :users="data?.data ?? []"
      :loading="isLoading"
      :columns="visibleColumns"
      @edit="(user) => usersUiStore.openModal('edit', user)"
      @delete="(user) => deleteUser(user)"
      @change-password="(user) => usersUiStore.openModal('changePassword', user)"
      @toggle-lock="(user) => toggleLockUser(user)"
      @reset-password="resetPassword"
    />

    <BasePagination
      :page="filters.page"
      :total="data?.total ?? 0"
      :per-page="filters.per_page"
      @change="({ page, perPage }) => { filters.page = page; filters.per_page = perPage }"
    />

    <CreateUserModal v-model="showCreate" />
    <EditUserModal v-model="showEdit" :user="usersUiStore.selectedUser" />
    <ChangePasswordModal v-model="showChangePassword" :user="usersUiStore.selectedUser" />
    <ResetPasswordModal v-model="showResetResult" :password="newPassword" />

    <ColumnSelectorDrawer
      v-model="isDrawerOpen"
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
