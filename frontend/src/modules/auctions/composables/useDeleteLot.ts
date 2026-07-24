import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { deleteLotApi } from '../api/lots.api'
import { useNotification } from '@/core/composables/useNotification'
import { useConfirm } from '@/core/composables/useConfirm'
import type { LotItem } from '../types/lot.types'

/**
 * Not explicitly named in tasks obs#41 6.5 (which lists only Create/Update/Close
 * for Lot), but the backend ships `lots.delete` + `DELETE /v1/lots/{guid}` — added
 * for parity with every other CRUD module in this codebase (Auction included).
 */
export function useDeleteLot() {
  const queryClient = useQueryClient()
  const { success, error } = useNotification()
  const confirm = useConfirm()

  const mutation = useMutation({
    mutationFn: (guid: string) => deleteLotApi(guid),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['lots'] })
      success('Lote eliminado correctamente')
    },
    onError: () => {
      error('Error al eliminar el lote')
    },
  })

  async function deleteLot(lot: LotItem) {
    await confirm.confirm({
      title: 'Eliminar lote',
      message: `¿Estás seguro de que querés eliminar el lote "${lot.lot_number}"?`,
      confirmLabel: 'Eliminar',
      danger: true,
      onConfirm: () => mutation.mutateAsync(lot.guid),
    })
  }

  return { ...mutation, deleteLot }
}
