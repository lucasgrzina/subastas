import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { getAuctionApi } from '../api/auctions.api'

export function useAuction(guid: Ref<string | undefined> | string | undefined) {
  const guidValue = computed(() => toValue(guid))

  return useQuery({
    queryKey: ['auction', guidValue],
    queryFn: () => getAuctionApi(guidValue.value as string),
    enabled: computed(() => Boolean(guidValue.value)),
  })
}
