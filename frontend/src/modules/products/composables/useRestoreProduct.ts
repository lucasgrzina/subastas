import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { restoreProductApi } from '../api/products.api'
import { useNotification } from '@/core/composables/useNotification'
import { useConfirm } from '@/core/composables/useConfirm'
import type { ProductItem } from '../types/product.types'

export function useRestoreProduct() {
  const queryClient = useQueryClient()
  const { success, error } = useNotification()
  const confirm = useConfirm()

  const mutation = useMutation({
    mutationFn: (guid: string) => restoreProductApi(guid),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] })
      success('Producto restaurado correctamente')
    },
    onError: () => {
      error('Error al restaurar el producto')
    },
  })

  async function restoreProduct(item: ProductItem) {
    await confirm.confirm({
      title: 'Restaurar producto',
      message: `¿Querés restaurar "${item.title}"?`,
      confirmLabel: 'Restaurar',
      onConfirm: () => mutation.mutateAsync(item.guid),
    })
  }

  return { ...mutation, restoreProduct }
}
