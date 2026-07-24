import { reactive, toRef, watch, type Ref } from 'vue'
import { useDebounce } from '@/core/composables/useDebounce'
import { useTablePageSize } from '@/modules/settings/composables/useTablePageSize'
import type { ProductFilters } from '../types/product.types'

export function useProductFilters() {
  const { perPage: storedPerPage, setPerPage } = useTablePageSize('products', 15)

  const filters = reactive<ProductFilters>({
    search: '',
    status: '',
    with_trashed: false,
    page: 1,
    per_page: storedPerPage.value,
  })

  const searchRef = toRef(filters, 'search')
  const debouncedSearch = useDebounce(searchRef as Ref<string>, 400)

  watch(() => filters.per_page, (size: number | undefined) => {
    setPerPage(size)
  })

  function reset() {
    filters.search = ''
    filters.status = ''
    filters.with_trashed = false
    filters.page = 1
    filters.per_page = storedPerPage.value
  }

  return { filters, debouncedSearch, reset }
}
