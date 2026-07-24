import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { listAuctionsApi } from '../api/auctions.api'
import type { AuctionFilters } from '../types/auction.types'

export function useAuctions(filters: Ref<AuctionFilters> | AuctionFilters = {}) {
  const filtersRef = computed(() => toValue(filters))

  return useQuery({
    queryKey: ['auctions', filtersRef],
    queryFn: ({ signal }) => listAuctionsApi(filtersRef.value, signal),
    staleTime: 1000 * 60 * 5,
  })
}
