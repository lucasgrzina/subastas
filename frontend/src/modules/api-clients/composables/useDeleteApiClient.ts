import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { deleteApiClientApi } from '../api/api-clients.api'
import { useNotification } from '@/core/composables/useNotification'
import { useConfirm } from '@/core/composables/useConfirm'
import type { ApiClientItem } from '../types/api-client.types'

export function useDeleteApiClient() {
  const queryClient = useQueryClient()
  const { success, error } = useNotification()
  const confirm = useConfirm()

  const mutation = useMutation({
    mutationFn: (guid: string) => deleteApiClientApi(guid),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['api-clients'] })
      success('Cliente API eliminado correctamente')
    },
    onError: () => {
      error('Error al eliminar el cliente API')
    },
  })

  async function deleteApiClient(item: ApiClientItem) {
    await confirm.confirm({
      title:        'Revocar cliente API',
      message:      `¿Estás seguro de que querés revocar "${item.nombre}"? Perderá acceso inmediatamente y no se puede deshacer.`,
      confirmLabel: 'Revocar',
      danger:       true,
      onConfirm:    () => mutation.mutateAsync(item.guid),
    })
  }

  return { ...mutation, deleteApiClient }
}
