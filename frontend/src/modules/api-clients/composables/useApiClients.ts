import { useQuery } from '@tanstack/vue-query'
import { computed, toValue, type Ref } from 'vue'
import { listApiClientsApi } from '../api/api-clients.api'
import type { ApiClientFilters, ApiClientListParams } from '../types/api-client.types'

function toListParams(filters: ApiClientFilters): ApiClientListParams {
  return { ...filters }
}

export function useApiClients(filters: Ref<ApiClientFilters> | ApiClientFilters) {
  const filtersRef = computed(() => toValue(filters))

  return useQuery({
    queryKey: ['api-clients', filtersRef],
    queryFn:  ({ signal }) => listApiClientsApi(toListParams(filtersRef.value), signal),
    staleTime: 1000 * 30,
  })
}
