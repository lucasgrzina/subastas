import { http } from '@/core/api/http'
import type {
  LotItem,
  LotListParams,
  LotListResponse,
  CreateLotPayload,
  UpdateLotPayload,
  PublishedProductOption,
} from '../types/lot.types'
import type { BidItem, BidListParams, BidListResponse, CreateBidPayload } from '../types/bid.types'

export async function listLotsApi(
  params: LotListParams = {},
  signal?: AbortSignal,
): Promise<LotListResponse> {
  const res = await http.get<LotListResponse>('/v1/lots', { params, signal })
  return res.data
}

export async function getLotApi(guid: string): Promise<LotItem> {
  const res = await http.get<LotItem>(`/v1/lots/${guid}`)
  return res.data
}

export async function createLotApi(payload: CreateLotPayload): Promise<LotItem> {
  const res = await http.post<LotItem>('/v1/lots', payload)
  return res.data
}

export async function updateLotApi(guid: string, payload: UpdateLotPayload): Promise<LotItem> {
  const res = await http.put<LotItem>(`/v1/lots/${guid}`, payload)
  return res.data
}

export async function deleteLotApi(guid: string): Promise<void> {
  await http.delete(`/v1/lots/${guid}`)
}

export async function closeLotApi(guid: string): Promise<LotItem> {
  const res = await http.post<LotItem>(`/v1/lots/${guid}/close`)
  return res.data
}

export async function listBidHistoryApi(
  guid: string,
  params: BidListParams = {},
  signal?: AbortSignal,
): Promise<BidListResponse> {
  const res = await http.get<BidListResponse>(`/v1/lots/${guid}/bids`, { params, signal })
  return res.data
}

export async function placeBidApi(guid: string, payload: CreateBidPayload): Promise<BidItem> {
  const res = await http.post<BidItem>(`/v1/lots/${guid}/bids`, payload)
  return res.data
}

/**
 * Published-product picker for the lot composition UI. Kept local to this
 * module (not imported from `modules/products/api`) — no module in this
 * codebase cross-imports another module's `api/`; this hits the same
 * `/v1/products` endpoint the products module uses, requesting only the
 * `published` subset.
 */
export async function listPublishedProductsApi(
  search: string | undefined,
  signal?: AbortSignal,
): Promise<PublishedProductOption[]> {
  const res = await http.get<{ data: PublishedProductOption[] }>('/v1/products', {
    params: { status: 'published', search: search || undefined, per_page: 50 },
    signal,
  })
  return res.data.data
}
