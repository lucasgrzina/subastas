import { defineStore } from 'pinia'
import { reactive } from 'vue'
import type { CurrencyFilters } from '../types/currency.types'

const DEFAULT_PER_PAGE = 15

/**
 * UI-only state for the currencies module — never server data (that
 * lives in Vue Query). Holds the list page's active filters so they survive
 * navigating away to the form page and back.
 */
export const useCurrenciesUiStore = defineStore('currencies-ui', () => {
  const filters = reactive<CurrencyFilters>({
    search: '',
    page: 1,
    per_page: DEFAULT_PER_PAGE,
  })

  function resetFilters() {
    filters.search = ''
    filters.page = 1
    filters.per_page = DEFAULT_PER_PAGE
  }

  return { filters, resetFilters }
})
