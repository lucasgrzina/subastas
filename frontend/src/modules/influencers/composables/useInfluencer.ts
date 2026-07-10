import { useQuery } from '@tanstack/vue-query'
import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { getInfluencerApi } from '../api/influencers.api'

export function useInfluencer(guid: Ref<string | undefined> | string | undefined) {
  const guidValue = computed(() => toValue(guid))

  return useQuery({
    queryKey: ['influencer', guidValue],
    queryFn: () => getInfluencerApi(guidValue.value as string),
    enabled: computed(() => Boolean(guidValue.value)),
  })
}
