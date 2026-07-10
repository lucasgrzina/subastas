import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { InfluencerItem } from '../types/influencer.types'

type ModalType = 'create' | 'edit' | null

export const useInfluencersUiStore = defineStore('influencers-ui', () => {
  const selectedInfluencer = ref<InfluencerItem | null>(null)
  const activeModal = ref<ModalType>(null)

  function openModal(modal: NonNullable<ModalType>, influencer?: InfluencerItem) {
    selectedInfluencer.value = influencer ?? null
    activeModal.value = modal
  }

  function closeModal() {
    activeModal.value = null
    selectedInfluencer.value = null
  }

  return {
    selectedInfluencer,
    activeModal,
    openModal,
    closeModal,
  }
})
