import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * UI-only state for the auctions module — never server data (that lives in
 * Vue Query). Tracks which Auction the user is currently "focused" on while
 * browsing into its Lots, so the Lots list can pre-filter/show a back-link
 * without round-tripping the guid through every route.
 */
export const useAuctionsUiStore = defineStore('auctions-ui', () => {
  const activeAuctionGuid = ref<string | null>(null)
  const activeAuctionTitle = ref<string | null>(null)

  function setActiveAuction(guid: string, title: string) {
    activeAuctionGuid.value = guid
    activeAuctionTitle.value = title
  }

  function clearActiveAuction() {
    activeAuctionGuid.value = null
    activeAuctionTitle.value = null
  }

  return { activeAuctionGuid, activeAuctionTitle, setActiveAuction, clearActiveAuction }
})
