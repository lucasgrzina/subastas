import { ref } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { toggleInfluencerApi } from '../api/influencers.api'
import { useNotification } from '@/core/composables/useNotification'
import type { InfluencerItem } from '../types/influencer.types'

export function useToggleInfluencer() {
  const queryClient = useQueryClient()
  const { success, error } = useNotification()
  const pendingGuid = ref<string | null>(null)

  const mutation = useMutation({
    mutationFn: (guid: string) => {
      pendingGuid.value = guid
      return toggleInfluencerApi(guid)
    },
    onSuccess: (updated: InfluencerItem) => {
      queryClient.invalidateQueries({ queryKey: ['influencers'] })
      success(updated.activo ? 'Influencer activado' : 'Influencer desactivado')
    },
    onError: () => {
      error('Error al cambiar el estado del influencer')
    },
    onSettled: () => {
      pendingGuid.value = null
    },
  })

  return { ...mutation, pendingGuid }
}
