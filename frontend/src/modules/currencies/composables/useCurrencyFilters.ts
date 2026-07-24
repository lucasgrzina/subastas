import { toRef, watch } from 'vue'
import type { Ref } from 'vue'
import { useDebounce } from '@/core/composables/useDebounce'
import { useCurrenciesUiStore } from '../stores/currencies-ui.store'

export function useCurrencyFilters() {
  const store = useCurrenciesUiStore()

  const searchRef = toRef(store.filters, 'search')
  const debouncedSearch = useDebounce(searchRef as Ref<string>, 400)

  watch(() => store.filters.search, () => {
    store.filters.page = 1
  })

  return { filters: store.filters, debouncedSearch, reset: store.resetFilters }
}
