import type { AuctionItem } from './auction.types'
import type { BidItem } from './bid.types'

/** Final 4-value enum (no `closed`) — see backend tasks obs#41 1.2a. */
export type LotStatus = 'scheduled' | 'open' | 'sold' | 'unsold'

/** `single`: exactly one attached product at quantity 1. `bundle`: everything else. */
export type LotType = 'single' | 'bundle'

export interface LotProductItem {
  guid: string
  title: string
  quantity: number
}

export interface LotWinnerRef {
  guid: string
  name: string
}

export interface LotItem {
  guid: string
  lot_number: string
  /** Optional editorial override — null when unset. */
  title: string | null
  /** Resolved value: `title` if set, otherwise derived from products. Never null. */
  display_title: string
  type: LotType
  starting_price: string
  bid_increment: string
  reserve_price: string | null
  status: LotStatus
  current_price: string | null
  current_bid: BidItem | null
  current_winner: LotWinnerRef | null
  auction: AuctionItem | null
  products: LotProductItem[]
  created_at: string
  deleted_at: string | null
}

export interface LotProductPayload {
  product_guid: string
  quantity: number
}

export interface CreateLotPayload {
  auction_guid: string
  lot_number: string
  title?: string | null
  starting_price: string
  bid_increment: string
  reserve_price?: string | null
  status?: LotStatus
  products: LotProductPayload[]
}

export interface UpdateLotPayload {
  lot_number?: string
  title?: string | null
  starting_price?: string
  bid_increment?: string
  reserve_price?: string | null
  status?: LotStatus
  products?: LotProductPayload[]
}

export interface LotListParams {
  search?: string
  page?: number
  per_page?: number
}

export type LotFilters = LotListParams

export interface LotListResponse {
  data: LotItem[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

/** Option shape for the multi-product composer's picker (published products only). */
export interface PublishedProductOption {
  guid: string
  title: string
}
