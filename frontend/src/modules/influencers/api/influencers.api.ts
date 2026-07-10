import { http } from '@/core/api/http'
import type {
  InfluencerListParams,
  InfluencerListResponse,
  InfluencerItem,
  InfluencerPayload,
} from '../types/influencer.types'

export async function listInfluencersApi(
  params: InfluencerListParams,
  signal?: AbortSignal,
): Promise<InfluencerListResponse> {
  const response = await http.get<InfluencerListResponse>('/v1/influencers', { params, signal })
  return response.data
}

export async function getInfluencerApi(guid: string): Promise<InfluencerItem> {
  const response = await http.get<InfluencerItem>(`/v1/influencers/${guid}`)
  return response.data
}

export async function createInfluencerApi(payload: InfluencerPayload): Promise<InfluencerItem> {
  const response = await http.post<InfluencerItem>('/v1/influencers', payload)
  return response.data
}

export async function updateInfluencerApi(
  guid: string,
  payload: InfluencerPayload,
): Promise<InfluencerItem> {
  const response = await http.put<InfluencerItem>(`/v1/influencers/${guid}`, payload)
  return response.data
}

export async function toggleInfluencerApi(guid: string): Promise<InfluencerItem> {
  const response = await http.patch<InfluencerItem>(`/v1/influencers/${guid}/toggle-active`)
  return response.data
}

export async function deleteInfluencerApi(guid: string): Promise<void> {
  await http.delete(`/v1/influencers/${guid}`)
}
