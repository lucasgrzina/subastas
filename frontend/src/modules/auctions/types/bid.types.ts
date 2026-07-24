export interface BidUserRef {
  guid: string
  name: string
}

export interface BidItem {
  guid: string
  amount: string
  user?: BidUserRef
  created_at: string
}

export interface CreateBidPayload {
  amount: string
}

export interface BidListParams {
  page?: number
  per_page?: number
}

export interface BidListResponse {
  data: BidItem[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
