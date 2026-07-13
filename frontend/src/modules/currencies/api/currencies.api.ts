import { http } from '@/core/api/http'
import type {
  CurrencyItem,
  CurrencyListParams,
  CurrencyListResponse,
  CreateCurrencyPayload,
  UpdateCurrencyPayload,
} from '../types/currency.types'

export async function listCurrenciesApi(params: CurrencyListParams = {}, signal?: AbortSignal): Promise<CurrencyListResponse> {
  const res = await http.get<CurrencyListResponse>('/v1/currencies', { params, signal })
  return res.data
}

export async function getCurrencyApi(guid: string): Promise<CurrencyItem> {
  const res = await http.get<CurrencyItem>(`/v1/currencies/${guid}`)
  return res.data
}

export async function createCurrencyApi(payload: CreateCurrencyPayload): Promise<CurrencyItem> {
  const res = await http.post<CurrencyItem>('/v1/currencies', payload)
  return res.data
}

export async function updateCurrencyApi(guid: string, payload: UpdateCurrencyPayload): Promise<CurrencyItem> {
  const res = await http.put<CurrencyItem>(`/v1/currencies/${guid}`, payload)
  return res.data
}

export async function deleteCurrencyApi(guid: string): Promise<void> {
  await http.delete(`/v1/currencies/${guid}`)
}
