import { http } from '@/core/api/http'

export interface TempUploadResult {
  token: string
  url: string
}

/**
 * Sube una imagen al área temporal genérica y devuelve un token + URL de preview.
 * El token viaja luego en el store/update del modelo para promover la imagen.
 */
export async function uploadTempImageApi(file: File): Promise<TempUploadResult> {
  const formData = new FormData()
  formData.append('image', file)

  const response = await http.post<TempUploadResult>('/v1/uploads/images', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return response.data
}
