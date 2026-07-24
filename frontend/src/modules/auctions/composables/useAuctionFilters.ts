import { reactive, toRef, watch } from 'vue'
import type { Ref } from 'vue'
import { useDebounce } from '@/core/composables/useDebounce'
import type { AuctionFilters } from '../types/auction.types'

export function useAuctionFilters() {
  const filters = reactive<AuctionFilters>({
    search: '',
    page: 1,
    per_page: 15,
  })

  const searchRef = toRef(filters, 'search')
  const debouncedSearch = useDebounce(searchRef as Ref<string>, 400)

  function reset() {
    filters.search = ''
    filters.page = 1
    filters.per_page = 15
  }

  watch(() => filters.search, () => {
    filters.page = 1
  })

  return { filters, debouncedSearch, reset }
}
