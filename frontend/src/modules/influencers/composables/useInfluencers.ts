import { useQuery } from '@tanstack/vue-query'
import { computed, toValue, type Ref } from 'vue'
import { listInfluencersApi } from '../api/influencers.api'
import type { InfluencerFilters, InfluencerListParams } from '../types/influencer.types'

function toListParams(filters: InfluencerFilters): InfluencerListParams {
  return { ...filters }
}

export function useInfluencers(filters: Ref<InfluencerFilters> | InfluencerFilters) {
  const filtersRef = computed(() => toValue(filters))

  return useQuery({
    queryKey: ['influencers', filtersRef],
    queryFn: ({ signal }) => listInfluencersApi(toListParams(filtersRef.value), signal),
    staleTime: 1000 * 30,
  })
}
