import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { deleteProductApi } from '../api/products.api'
import { useNotification } from '@/core/composables/useNotification'
import { useConfirm } from '@/core/composables/useConfirm'
import type { ProductItem } from '../types/product.types'

export function useDeleteProduct() {
  const queryClient = useQueryClient()
  const { success, error } = useNotification()
  const confirm = useConfirm()

  const mutation = useMutation({
    mutationFn: (guid: string) => deleteProductApi(guid),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['products'] })
      success('Producto eliminado correctamente')
    },
    onError: () => {
      error('Error al eliminar el producto')
    },
  })

  async function deleteProduct(item: ProductItem) {
    await confirm.confirm({
      title: 'Eliminar producto',
      message: `¿Estás seguro de que querés eliminar "${item.title}"?`,
      confirmLabel: 'Eliminar',
      danger: true,
      onConfirm: () => mutation.mutateAsync(item.guid),
    })
  }

  return { ...mutation, deleteProduct }
}
