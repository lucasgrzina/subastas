import { ref } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { toggleApiClientApi } from '../api/api-clients.api'
import { useNotification } from '@/core/composables/useNotification'
import type { ApiClientItem } from '../types/api-client.types'

export function useToggleApiClient() {
  const queryClient  = useQueryClient()
  const { success, error } = useNotification()
  const pendingGuid  = ref<string | null>(null)

  const mutation = useMutation({
    mutationFn: (guid: string) => {
      pendingGuid.value = guid
      return toggleApiClientApi(guid)
    },
    onSuccess: (updated: ApiClientItem) => {
      queryClient.invalidateQueries({ queryKey: ['api-clients'] })
      success(updated.active ? 'Cliente API activado' : 'Cliente API desactivado')
    },
    onError: () => {
      error('Error al cambiar el estado del cliente API')
    },
    onSettled: () => {
      pendingGuid.value = null
    },
  })

  return { ...mutation, pendingGuid }
}
