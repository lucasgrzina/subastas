import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { deleteInfluencerApi } from '../api/influencers.api'
import { useNotification } from '@/core/composables/useNotification'
import { useConfirm } from '@/core/composables/useConfirm'
import type { InfluencerItem } from '../types/influencer.types'

export function useDeleteInfluencer() {
  const queryClient = useQueryClient()
  const { success, error } = useNotification()
  const confirm = useConfirm()

  const mutation = useMutation({
    mutationFn: (guid: string) => deleteInfluencerApi(guid),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['influencers'] })
      success('Influencer eliminado correctamente')
    },
    onError: () => {
      error('Error al eliminar el influencer')
    },
  })

  async function deleteInfluencer(item: InfluencerItem) {
    await confirm.confirm({
      title: 'Eliminar influencer',
      message: `¿Estás seguro de que querés eliminar a "${item.nombre}"?`,
      confirmLabel: 'Eliminar',
      danger: true,
      onConfirm: () => mutation.mutateAsync(item.guid),
    })
  }

  return { ...mutation, deleteInfluencer }
}
