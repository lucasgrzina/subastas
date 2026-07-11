import { http } from '@/core/api/http'
import type {
  ProductItem,
  ProductListParams,
  ProductListResponse,
  CreateProductPayload,
  UpdateProductPayload,
} from '../types/product.types'

export async function listProductsApi(
  params: ProductListParams = {},
  signal?: AbortSignal,
): Promise<ProductListResponse> {
  const res = await http.get<ProductListResponse>('/v1/products', { params, signal })
  return res.data
}

export async function getProductApi(guid: string): Promise<ProductItem> {
  const res = await http.get<ProductItem>(`/v1/products/${guid}`)
  return res.data
}

export async function createProductApi(payload: CreateProductPayload): Promise<ProductItem> {
  const res = await http.post<ProductItem>('/v1/products', payload)
  return res.data
}

export async function updateProductApi(guid: string, payload: UpdateProductPayload): Promise<ProductItem> {
  const res = await http.put<ProductItem>(`/v1/products/${guid}`, payload)
  return res.data
}

export async function deleteProductApi(guid: string): Promise<void> {
  await http.delete(`/v1/products/${guid}`)
}

export async function restoreProductApi(guid: string): Promise<ProductItem> {
  const res = await http.patch<ProductItem>(`/v1/products/${guid}/restore`)
  return res.data
}
