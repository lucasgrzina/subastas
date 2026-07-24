import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { listBidHistoryApi } from '../api/lots.api'
import type { BidListParams } from '../types/bid.types'

export function useBidHistory(
  guid: Ref<string | undefined> | string | undefined,
  params: Ref<BidListParams> | BidListParams = {},
) {
  const guidValue = computed(() => toValue(guid))
  const paramsValue = computed(() => toValue(params))

  return useQuery({
    queryKey: ['lot-bids', guidValue, paramsValue],
    queryFn: ({ signal }) => listBidHistoryApi(guidValue.value as string, paramsValue.value, signal),
    enabled: computed(() => Boolean(guidValue.value)),
  })
}
