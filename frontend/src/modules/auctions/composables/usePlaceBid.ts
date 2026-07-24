import { ref } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { placeBidApi } from '../api/lots.api'
import { useNotification } from '@/core/composables/useNotification'
import { parseApiError } from '@/core/composables/parseApiError'
import type { CreateBidPayload } from '../types/bid.types'

export function usePlaceBid() {
  const queryClient = useQueryClient()
  const { success, error } = useNotification()
  const fieldErrors = ref<Record<string, string> | null>(null)
  const generalError = ref<string | null>(null)

  const mutation = useMutation({
    mutationFn: ({ guid, payload }: { guid: string; payload: CreateBidPayload }) =>
      placeBidApi(guid, payload),
    onMutate: () => {
      fieldErrors.value = null
      generalError.value = null
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['lot-bids'] })
      queryClient.invalidateQueries({ queryKey: ['lots'] })
      queryClient.invalidateQueries({ queryKey: ['lot'] })
      success('Oferta registrada correctamente')
    },
    onError: (err: unknown) => {
      const apiError = parseApiError(err)
      fieldErrors.value = apiError.fieldErrors
      generalError.value = apiError.message ?? 'Error al registrar la oferta.'
      if (apiError.message) {
        error('Error al registrar la oferta')
      }
    },
  })

  function resetErrors() {
    fieldErrors.value = null
    generalError.value = null
    mutation.reset()
  }

  return { ...mutation, fieldErrors, generalError, resetErrors }
}
