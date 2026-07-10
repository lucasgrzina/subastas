import { reactive, toRef, watch, type Ref } from 'vue'
import { useDebounce } from '@/core/composables/useDebounce'
import { useTablePageSize } from '@/modules/settings/composables/useTablePageSize'
import type { InfluencerFilters } from '../types/influencer.types'

export function useInfluencerFilters() {
  const { perPage: storedPerPage, setPerPage } = useTablePageSize('influencers', 15)

  const filters = reactive<InfluencerFilters>({
    search: '',
    activo: '',
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
    filters.activo = ''
    filters.page = 1
    filters.per_page = storedPerPage.value
  }

  return { filters, debouncedSearch, reset }
}
