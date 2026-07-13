import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { getCurrencyApi } from '../api/currencies.api'

export function useCurrency(guid: Ref<string | undefined> | string | undefined) {
  const guidValue = computed(() => toValue(guid))

  return useQuery({
    queryKey: ['currency', guidValue],
    queryFn: () => getCurrencyApi(guidValue.value as string),
    enabled: computed(() => Boolean(guidValue.value)),
  })
}
