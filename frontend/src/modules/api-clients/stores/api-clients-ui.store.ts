import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { ApiClientItem } from '../types/api-client.types'

type ModalType = 'create' | 'edit' | null

export const useApiClientsUiStore = defineStore('api-clients-ui', () => {
  const selectedClient = ref<ApiClientItem | null>(null)
  const activeModal    = ref<ModalType>(null)
  // Token plano post-creación (se muestra una sola vez)
  const newPlainToken  = ref<string | null>(null)

  function openModal(modal: NonNullable<ModalType>, client?: ApiClientItem) {
    selectedClient.value = client ?? null
    activeModal.value    = modal
  }

  function closeModal() {
    activeModal.value    = null
    selectedClient.value = null
  }

  function setNewToken(token: string) {
    newPlainToken.value = token
  }

  function clearNewToken() {
    newPlainToken.value = null
  }

  return {
    selectedClient,
    activeModal,
    newPlainToken,
    openModal,
    closeModal,
    setNewToken,
    clearNewToken,
  }
})
