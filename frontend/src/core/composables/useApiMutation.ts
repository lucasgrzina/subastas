import { ref } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import type { MutationFunction, QueryClient } from '@tanstack/vue-query'
import { useNotification } from './useNotification'
import { parseApiError } from './parseApiError'

export interface UseApiMutationOptions<TVariables, TData> {
  mutationFn: MutationFunction<TData, TVariables>
  successMessage: string
  errorMessage: string
  invalidate?: (queryClient: QueryClient) => void
}

/**
 * Shared create/update/delete mutation shape used across modules: resets
 * field/general errors on mutate, maps 422s via parseApiError, and exposes
 * resetErrors() for drawers/modals to call when they open.
 */
export function useApiMutation<TVariables, TData>(options: UseApiMutationOptions<TVariables, TData>) {
  const queryClient = useQueryClient()
  const { success, error } = useNotification()
  const fieldErrors = ref<Record<string, string> | null>(null)
  const generalError = ref<string | null>(null)

  const mutation = useMutation({
    mutationFn: options.mutationFn,
    onMutate: () => {
      fieldErrors.value = null
      generalError.value = null
    },
    onSuccess: () => {
      options.invalidate?.(queryClient)
      success(options.successMessage)
    },
    onError: (err: unknown) => {
      const apiError = parseApiError(err)
      fieldErrors.value = apiError.fieldErrors
      generalError.value = apiError.message ?? options.errorMessage
      if (apiError.message) {
        error(options.errorMessage)
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
