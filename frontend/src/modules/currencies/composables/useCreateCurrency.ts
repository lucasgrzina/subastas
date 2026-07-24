import { createCurrencyApi } from '../api/currencies.api'
import { useApiMutation } from '@/core/composables/useApiMutation'
import type { CreateCurrencyPayload } from '../types/currency.types'

export function useCreateCurrency() {
  return useApiMutation({
    mutationFn: (payload: CreateCurrencyPayload) => createCurrencyApi(payload),
    successMessage: 'Moneda creada correctamente',
    errorMessage: 'Error al crear la moneda',
    invalidate: (queryClient) => queryClient.invalidateQueries({ queryKey: ['currencies'] }),
  })
}
