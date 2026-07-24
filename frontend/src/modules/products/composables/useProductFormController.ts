import { computed, toValue } from 'vue'
import type { Ref } from 'vue'
import { useCreateProduct } from './useCreateProduct'
import { useUpdateProduct } from './useUpdateProduct'
import type { ProductItem, CreateProductPayload, UpdateProductPayload } from '../types/product.types'

/**
 * Shared create/update saving logic between the products page and any future
 * drawer/modal presentation, so both stay in sync (mirrors influencers' controller).
 */
export function useProductFormController(
  product: Ref<ProductItem | null | undefined> | ProductItem | null | undefined,
  onSaved: () => void,
) {
  const productValue = computed(() => toValue(product) ?? null)
  const isEdit = computed(() => Boolean(productValue.value))

  const {
    mutate: create,
    isPending: isCreating,
    fieldErrors: createErrors,
    generalError: createGeneralError,
    resetErrors: resetCreate,
  } = useCreateProduct()

  const {
    mutate: update,
    isPending: isUpdating,
    fieldErrors: updateErrors,
    generalError: updateGeneralError,
    resetErrors: resetUpdate,
  } = useUpdateProduct()

  const fieldErrors = computed(() => (isEdit.value ? updateErrors.value : createErrors.value))
  const generalError = computed(() => (isEdit.value ? updateGeneralError.value : createGeneralError.value))
  const isPending = computed(() => (isEdit.value ? isUpdating.value : isCreating.value))

  function resetErrors() {
    if (isEdit.value) resetUpdate()
    else resetCreate()
  }

  function handleSubmit(values: CreateProductPayload | UpdateProductPayload) {
    if (!isEdit.value) {
      create(values as CreateProductPayload, { onSuccess: onSaved })
    } else if (productValue.value) {
      update({ guid: productValue.value.guid, payload: values as UpdateProductPayload }, { onSuccess: onSaved })
    }
  }

  return { isEdit, fieldErrors, generalError, isPending, resetErrors, handleSubmit }
}
