import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { getLotApi } from '../api/lots.api'

export function useLot(guid: Ref<string | undefined> | string | undefined) {
  const guidValue = computed(() => toValue(guid))

  return useQuery({
    queryKey: ['lot', guidValue],
    queryFn: () => getLotApi(guidValue.value as string),
    enabled: computed(() => Boolean(guidValue.value)),
  })
}
