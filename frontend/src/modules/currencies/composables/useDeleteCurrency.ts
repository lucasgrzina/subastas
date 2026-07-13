import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { deleteCurrencyApi } from '../api/currencies.api'
import { useNotification } from '@/core/composables/useNotification'
import { useConfirm } from '@/core/composables/useConfirm'
import type { CurrencyItem } from '../types/currency.types'

export function useDeleteCurrency() {
  const queryClient = useQueryClient()
  const { success, error } = useNotification()
  const confirm = useConfirm()

  const mutation = useMutation({
    mutationFn: (guid: string) => deleteCurrencyApi(guid),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['currencies'] })
      success('Moneda eliminada correctamente')
    },
    onError: () => {
      error('Error al eliminar la moneda')
    },
  })

  async function deleteCurrency(item: CurrencyItem) {
    await confirm.confirm({
      title: 'Eliminar moneda',
      message: `¿Estás seguro de que querés eliminar "${item.code}"? Esta acción no se puede deshacer.`,
      confirmLabel: 'Eliminar',
      danger: true,
      onConfirm: () => mutation.mutateAsync(item.guid),
    })
  }

  return { ...mutation, deleteCurrency }
}
