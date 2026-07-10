import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { useCreateInfluencer } from './useCreateInfluencer'
import { useUpdateInfluencer } from './useUpdateInfluencer'
import type { InfluencerItem, InfluencerPayload } from '../types/influencer.types'

/**
 * Lógica de guardado (create/update) compartida entre el drawer y la página de
 * creación/edición, para que ambas formas de presentación se mantengan en sync.
 */
export function useInfluencerFormController(
  influencer: Ref<InfluencerItem | null | undefined> | InfluencerItem | null | undefined,
  onSaved: () => void,
) {
  const influencerValue = computed(() => toValue(influencer) ?? null)
  const isEdit = computed(() => Boolean(influencerValue.value))
  const title = computed(() => (isEdit.value ? 'Editar influencer' : 'Nuevo influencer'))

  const {
    mutate: create,
    isPending: isCreating,
    fieldErrors: createErrors,
    generalError: createGeneralError,
    resetErrors: resetCreate,
  } = useCreateInfluencer()

  const {
    mutate: update,
    isPending: isUpdating,
    fieldErrors: updateErrors,
    generalError: updateGeneralError,
    resetErrors: resetUpdate,
  } = useUpdateInfluencer()

  const fieldErrors = computed(() => (isEdit.value ? updateErrors.value : createErrors.value))
  const generalError = computed(() => (isEdit.value ? updateGeneralError.value : createGeneralError.value))
  const isPending = computed(() => (isEdit.value ? isUpdating.value : isCreating.value))

  function resetErrors() {
    if (isEdit.value) resetUpdate()
    else resetCreate()
  }

  function handleSubmit(values: InfluencerPayload) {
    if (!isEdit.value) {
      create(values, { onSuccess: onSaved })
    } else if (influencerValue.value) {
      update({ guid: influencerValue.value.guid, payload: values }, { onSuccess: onSaved })
    }
  }

  return { isEdit, title, fieldErrors, generalError, isPending, resetErrors, handleSubmit }
}
