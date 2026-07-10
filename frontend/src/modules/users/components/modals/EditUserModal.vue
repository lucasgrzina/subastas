<script setup lang="ts">
import { computed, watch } from 'vue'
import BaseModal from '@/components/atoms/overlays/BaseModal.vue'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'
import UserForm from '../forms/UserForm.vue'
import { useUpdateUser } from '../../composables/useUpdateUser'
import { useUser } from '../../composables/useUser'
import type { UserItem, UserUpdatePayload } from '../../types/user.types'

const props = defineProps<{ user: UserItem | null }>()
const isOpen = defineModel<boolean>({ default: false })

const { mutate, isPending, fieldErrors, generalError, resetErrors } = useUpdateUser()

watch(isOpen, (open) => {
  if (open) resetErrors()
})

const userGuid = computed(() => props.user?.guid ?? '')
const { data: userDetail, isFetching: isLoadingUser } = useUser(userGuid)

const showForm = computed(() => Boolean(props.user && userDetail.value))
const showLoading = computed(() => Boolean(props.user && isLoadingUser.value && !userDetail.value))

const initialValues = computed(() => ({
  first_name: userDetail.value?.first_name ?? props.user?.first_name ?? '',
  last_name: userDetail.value?.last_name ?? props.user?.last_name ?? '',
  email: userDetail.value?.email ?? props.user?.email ?? '',
  role_guids: userDetail.value?.roles?.map((r) => r.guid) ?? [],
}))

function handleSubmit(values: UserUpdatePayload) {
  if (!props.user) return
  mutate(
    { guid: props.user.guid, payload: values },
    {
      onSuccess: () => {
        isOpen.value = false
      },
    },
  )
}
</script>

<template>
  <BaseModal v-model="isOpen" title="Editar usuario" :width="640">
    <a-alert
      v-if="generalError && !fieldErrors"
      :message="generalError"
      type="error"
      show-icon
      style="margin-bottom: 16px"
    />
    <div v-if="showLoading" style="text-align: center; padding: 24px 0">
      <a-spin size="default" />
    </div>
    <UserForm
      v-if="showForm"
      mode="edit"
      :initial-values="initialValues"
      :loading="isPending"
      :field-errors="fieldErrors"
      @submit="handleSubmit"
    >
      <template #footer>
        <a-form-item style="margin-bottom: 0; text-align: right">
          <a-space>
            <BaseButton variant="secondary" @click="isOpen = false">Cancelar</BaseButton>
            <BaseButton variant="primary" html-type="submit" :loading="isPending">
              Guardar cambios
            </BaseButton>
          </a-space>
        </a-form-item>
      </template>
    </UserForm>
  </BaseModal>
</template>
