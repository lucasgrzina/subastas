import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { deleteAuctionApi } from '../api/auctions.api'
import { useNotification } from '@/core/composables/useNotification'
import { useConfirm } from '@/core/composables/useConfirm'
import type { AuctionItem } from '../types/auction.types'

export function useDeleteAuction() {
  const queryClient = useQueryClient()
  const { success, error } = useNotification()
  const confirm = useConfirm()

  const mutation = useMutation({
    mutationFn: (guid: string) => deleteAuctionApi(guid),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['auctions'] })
      success('Subasta eliminada correctamente')
    },
    onError: () => {
      error('Error al eliminar la subasta')
    },
  })

  async function deleteAuction(auction: AuctionItem) {
    await confirm.confirm({
      title: 'Eliminar subasta',
      message: `¿Estás seguro de que querés eliminar "${auction.title}"?`,
      confirmLabel: 'Eliminar',
      danger: true,
      onConfirm: () => mutation.mutateAsync(auction.guid),
    })
  }

  return { ...mutation, deleteAuction }
}
