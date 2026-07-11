import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { getProductApi } from '../api/products.api'

export function useProduct(guid: Ref<string | undefined> | string | undefined) {
  const guidRef = computed(() => toValue(guid))

  return useQuery({
    queryKey: ['product', guidRef],
    queryFn: () => getProductApi(guidRef.value as string),
    enabled: computed(() => Boolean(guidRef.value)),
  })
}
