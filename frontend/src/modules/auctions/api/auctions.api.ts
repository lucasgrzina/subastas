import { http } from '@/core/api/http'
import type {
  AuctionItem,
  AuctionListParams,
  AuctionListResponse,
  CreateAuctionPayload,
  UpdateAuctionPayload,
} from '../types/auction.types'

export async function listAuctionsApi(
  params: AuctionListParams = {},
  signal?: AbortSignal,
): Promise<AuctionListResponse> {
  const res = await http.get<AuctionListResponse>('/v1/auctions', { params, signal })
  return res.data
}

export async function getAuctionApi(guid: string): Promise<AuctionItem> {
  const res = await http.get<AuctionItem>(`/v1/auctions/${guid}`)
  return res.data
}

export async function createAuctionApi(payload: CreateAuctionPayload): Promise<AuctionItem> {
  const res = await http.post<AuctionItem>('/v1/auctions', payload)
  return res.data
}

export async function updateAuctionApi(guid: string, payload: UpdateAuctionPayload): Promise<AuctionItem> {
  const res = await http.put<AuctionItem>(`/v1/auctions/${guid}`, payload)
  return res.data
}

export async function deleteAuctionApi(guid: string): Promise<void> {
  await http.delete(`/v1/auctions/${guid}`)
}
