import { ref } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { createApiClientApi } from '../api/api-clients.api'
import { useNotification } from '@/core/composables/useNotification'
import { parseApiError } from '@/core/composables/parseApiError'
import type { ApiClientCreatePayload } from '../types/api-client.types'

export function useCreateApiClient() {
  const queryClient  = useQueryClient()
  const { success, error } = useNotification()
  const fieldErrors  = ref<Record<string, string> | null>(null)
  const generalError = ref<string | null>(null)

  const mutation = useMutation({
    mutationFn: (payload: ApiClientCreatePayload) => createApiClientApi(payload),
    onMutate: () => {
      fieldErrors.value  = null
      generalError.value = null
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['api-clients'] })
      success('Cliente API creado correctamente')
    },
    onError: (err: unknown) => {
      const apiError = parseApiError(err)
      fieldErrors.value  = apiError.fieldErrors
      generalError.value = apiError.message ?? 'Error al crear el cliente.'
      if (apiError.message) {
        error('Error al crear el cliente API')
      }
    },
  })

  function resetErrors() {
    fieldErrors.value  = null
    generalError.value = null
    mutation.reset()
  }

  return { ...mutation, fieldErrors, generalError, resetErrors }
}
