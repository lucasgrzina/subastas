import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { closeLotApi } from '../api/lots.api'
import { useNotification } from '@/core/composables/useNotification'
import { useConfirm } from '@/core/composables/useConfirm'
import type { LotItem } from '../types/lot.types'

export function useCloseLot() {
  const queryClient = useQueryClient()
  const { success, error } = useNotification()
  const confirm = useConfirm()

  const mutation = useMutation({
    mutationFn: (guid: string) => closeLotApi(guid),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['lots'] })
      queryClient.invalidateQueries({ queryKey: ['lot'] })
      success('Lote cerrado correctamente')
    },
    onError: () => {
      error('Error al cerrar el lote')
    },
  })

  async function closeLot(lot: LotItem) {
    await confirm.confirm({
      title: 'Cerrar lote',
      message: `¿Estás seguro de que querés cerrar el lote "${lot.lot_number}"? Se determinará ganador según la mejor oferta y la reserva. Esta acción no se puede deshacer.`,
      confirmLabel: 'Cerrar lote',
      danger: true,
      onConfirm: () => mutation.mutateAsync(lot.guid).then(() => undefined),
    })
  }

  return { ...mutation, closeLot }
}
