export type AuctionStatus = 'draft' | 'scheduled' | 'live' | 'closed' | 'cancelled'

export interface AuctionItem {
  guid: string
  title: string
  description: string | null
  starts_at: string
  ends_at: string | null
  status: AuctionStatus
  created_at: string
  deleted_at: string | null
}

export interface CreateAuctionPayload {
  title: string
  description?: string | null
  starts_at: string
  ends_at?: string | null
  status?: AuctionStatus
}

export interface UpdateAuctionPayload {
  title?: string
  description?: string | null
  starts_at?: string
  ends_at?: string | null
  status?: AuctionStatus
}

export interface AuctionListParams {
  search?: string
  date_from?: string
  date_to?: string
  page?: number
  per_page?: number
}

export type AuctionFilters = AuctionListParams

export interface AuctionListResponse {
  data: AuctionItem[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
