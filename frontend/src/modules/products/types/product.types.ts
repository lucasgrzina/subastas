export type ProductStatus = 'draft' | 'published'

export interface ReferenceOption {
  guid: string
  name: string
}

export interface ProductWineDetails {
  year: number
  winery: ReferenceOption
  grape_variety: ReferenceOption
  wine_region: ReferenceOption
}

export interface ProductImageItem {
  id: number
  url: string
  is_main: boolean
}

export interface ProductItem {
  guid: string
  title: string
  description: string | null
  status: ProductStatus
  created_at: string
  deleted_at: string | null
  wine_details: ProductWineDetails | null
  images: ProductImageItem[]
  main_image_url: string | null
}

export interface WineDetailsPayload {
  year: number
  winery_guid: string
  grape_variety_guid: string
  wine_region_guid: string
}

/** A kept (already persisted) image, identified by its numeric id. */
export interface KeptImagePayload {
  image_id: number
  is_main: boolean
}

/** A newly staged image, identified by its temp-upload token. */
export interface NewImagePayload {
  token: string
  is_main: boolean
}

export type ProductImagePayload = KeptImagePayload | NewImagePayload

export interface CreateProductPayload {
  title: string
  description?: string | null
  status?: ProductStatus
  wine_details: WineDetailsPayload
  images: NewImagePayload[]
}

export interface UpdateProductPayload {
  title?: string
  description?: string | null
  status?: ProductStatus
  wine_details?: WineDetailsPayload
  images?: ProductImagePayload[]
}

export interface ProductListParams {
  search?: string
  status?: ProductStatus | ''
  with_trashed?: boolean
  page?: number
  per_page?: number
}

export type ProductFilters = ProductListParams

export interface ProductListResponse {
  data: ProductItem[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
