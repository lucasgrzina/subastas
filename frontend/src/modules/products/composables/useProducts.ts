import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { listProductsApi } from '../api/products.api'
import type { ProductFilters } from '../types/product.types'

export function useProducts(filters: Ref<ProductFilters> | ProductFilters = {}) {
  const filtersRef = computed(() => toValue(filters))

  return useQuery({
    queryKey: ['products', filtersRef],
    queryFn: ({ signal }) => listProductsApi(filtersRef.value, signal),
    staleTime: 1000 * 60,
  })
}
