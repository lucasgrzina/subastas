import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { listLotsApi } from '../api/lots.api'
import type { LotFilters } from '../types/lot.types'

export function useLots(filters: Ref<LotFilters> | LotFilters = {}) {
  const filtersRef = computed(() => toValue(filters))

  return useQuery({
    queryKey: ['lots', filtersRef],
    queryFn: ({ signal }) => listLotsApi(filtersRef.value, signal),
    staleTime: 1000 * 60 * 5,
  })
}
