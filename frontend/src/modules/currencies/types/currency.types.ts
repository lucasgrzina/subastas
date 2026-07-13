export interface CurrencyItem {
  guid: string
  code: string
  name: string
  symbol: string
  is_active: boolean
  created_at: string

}

export interface CreateCurrencyPayload {
  code: string
  name: string
  symbol: string
  is_active: boolean
}

export interface UpdateCurrencyPayload {
  code?: string
  name?: string
  symbol?: string
  is_active?: boolean
}

export interface CurrencyListParams {
  search?: string
  page?: number
  per_page?: number
}

export type CurrencyFilters = CurrencyListParams

export interface CurrencyListResponse {
  data: CurrencyItem[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
