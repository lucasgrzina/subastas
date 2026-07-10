import { http } from '@/core/api/http'
import type {
  ApiClientListParams,
  ApiClientListResponse,
  ApiClientItem,
  ApiClientCreated,
  ApiClientCreatePayload,
  ApiClientUpdatePayload,
} from '../types/api-client.types'

export async function listApiClientsApi(
  params: ApiClientListParams,
  signal?: AbortSignal,
): Promise<ApiClientListResponse> {
  const response = await http.get<ApiClientListResponse>('/v1/api-clients', { params, signal })
  return response.data
}

export async function createApiClientApi(
  payload: ApiClientCreatePayload,
): Promise<ApiClientCreated> {
  const response = await http.post<ApiClientCreated>('/v1/api-clients', payload)
  return response.data
}

export async function getApiClientApi(guid: string): Promise<ApiClientItem> {
  const response = await http.get<ApiClientItem>(`/v1/api-clients/${guid}`)
  return response.data
}

export async function updateApiClientApi(
  guid: string,
  payload: ApiClientUpdatePayload,
): Promise<ApiClientItem> {
  const response = await http.put<ApiClientItem>(`/v1/api-clients/${guid}`, payload)
  return response.data
}

export async function toggleApiClientApi(guid: string): Promise<ApiClientItem> {
  const response = await http.patch<ApiClientItem>(`/v1/api-clients/${guid}/toggle-active`)
  return response.data
}

export async function deleteApiClientApi(guid: string): Promise<void> {
  await http.delete(`/v1/api-clients/${guid}`)
}
