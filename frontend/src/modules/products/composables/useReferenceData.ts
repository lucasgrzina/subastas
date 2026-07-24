import { useQuery } from '@tanstack/vue-query'
import { listGrapeVarietiesApi, listWineRegionsApi, listWineriesApi } from '../api/reference-data.api'

const STALE_TIME = 1000 * 60 * 30

export function useWineries() {
  return useQuery({
    queryKey: ['reference-data', 'wineries'],
    queryFn: () => listWineriesApi(),
    staleTime: STALE_TIME,
  })
}

export function useGrapeVarieties() {
  return useQuery({
    queryKey: ['reference-data', 'grape-varieties'],
    queryFn: () => listGrapeVarietiesApi(),
    staleTime: STALE_TIME,
  })
}

export function useWineRegions() {
  return useQuery({
    queryKey: ['reference-data', 'wine-regions'],
    queryFn: () => listWineRegionsApi(),
    staleTime: STALE_TIME,
  })
}
