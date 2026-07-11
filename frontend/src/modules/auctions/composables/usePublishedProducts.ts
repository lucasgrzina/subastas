import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { listPublishedProductsApi } from '../api/lots.api'

/** Backs the lot composition picker — published products only, optional search. */
export function usePublishedProducts(search: Ref<string | undefined> | string | undefined = undefined) {
  const searchValue = computed(() => toValue(search))

  return useQuery({
    queryKey: ['published-products', searchValue],
    queryFn: ({ signal }) => listPublishedProductsApi(searchValue.value, signal),
    staleTime: 1000 * 60,
  })
}
