export interface ApiClient {
  guid:         string
  nombre:       string
  email:        string | null
  active:       boolean
  token_hint:   string
  last_used_at: string | null
  created_at:   string
}

// Solo presente en la respuesta del store (creación)
export interface ApiClientCreated extends ApiClient {
  plain_token: string
}

export type ApiClientItem = ApiClient

export interface ApiClientListParams {
  search?:    string
  active?:    boolean | ''
  date_from?: string
  date_to?:   string
  page?:      number
  per_page?:  number
}

export interface ApiClientListResponse {
  data:         ApiClientItem[]
  current_page: number
  last_page:    number
  per_page:     number
  total:        number
}

export interface ApiClientCreatePayload {
  nombre: string
  email?: string
}

export interface ApiClientUpdatePayload {
  nombre: string
  email?: string | null
}

export interface ApiClientFilters {
  search?:    string
  active?:    boolean | ''
  date_from?: string
  date_to?:   string
  page?:      number
  per_page?:  number
}
