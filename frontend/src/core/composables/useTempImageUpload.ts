import { useMutation } from '@tanstack/vue-query'
import { uploadTempImageApi, type TempUploadResult } from '@/core/api/uploads.api'
import { useNotification } from '@/core/composables/useNotification'
import { parseApiError } from '@/core/composables/parseApiError'

/** Subida diferida de imagen al área temporal. Reusable por cualquier módulo. */
export function useTempImageUpload() {
  const { error } = useNotification()

  const mutation = useMutation({
    mutationFn: (file: File): Promise<TempUploadResult> => uploadTempImageApi(file),
    onError: (err: unknown) => {
      const apiError = parseApiError(err)
      error(apiError.message ?? 'Error al subir la imagen')
    },
  })

  return { ...mutation }
}
