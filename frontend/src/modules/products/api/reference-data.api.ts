import { http } from '@/core/api/http'
import type { ReferenceOption } from '../types/product.types'

export async function listWineriesApi(): Promise<ReferenceOption[]> {
  const res = await http.get<ReferenceOption[]>('/v1/wineries')
  return res.data
}

export async function listGrapeVarietiesApi(): Promise<ReferenceOption[]> {
  const res = await http.get<ReferenceOption[]>('/v1/grape-varieties')
  return res.data
}

export async function listWineRegionsApi(): Promise<ReferenceOption[]> {
  const res = await http.get<ReferenceOption[]>('/v1/wine-regions')
  return res.data
}
