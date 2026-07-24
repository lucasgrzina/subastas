import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { listCurrenciesApi } from '../api/currencies.api'
import type { CurrencyFilters } from '../types/currency.types'

export function useCurrencies(filters: Ref<CurrencyFilters> | CurrencyFilters = {}) {
  const filtersRef = computed(() => toValue(filters))

  return useQuery({
    queryKey: ['currencies', filtersRef],
    queryFn: ({ signal }) => listCurrenciesApi(filtersRef.value, signal),
    staleTime: 1000 * 60 * 5,
  })
}
