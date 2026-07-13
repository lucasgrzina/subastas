import { updateCurrencyApi } from '../api/currencies.api'
import { useApiMutation } from '@/core/composables/useApiMutation'
import type { UpdateCurrencyPayload } from '../types/currency.types'

export function useUpdateCurrency() {
  return useApiMutation({
    mutationFn: ({ guid, payload }: { guid: string; payload: UpdateCurrencyPayload }) =>
      updateCurrencyApi(guid, payload),
    successMessage: 'Moneda actualizada correctamente',
    errorMessage: 'Error al actualizar la moneda',
    invalidate: (queryClient) => {
      queryClient.invalidateQueries({ queryKey: ['currencies'] })
      queryClient.invalidateQueries({ queryKey: ['currency'] })
    },
  })
}
