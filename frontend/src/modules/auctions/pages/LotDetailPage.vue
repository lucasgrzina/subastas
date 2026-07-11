<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import LotBidHistory from '../components/LotBidHistory.vue'
import PlaceBidForm from '../components/PlaceBidForm.vue'
import { useLot } from '../composables/useLot'
import type { LotStatus } from '../types/lot.types'

const props = defineProps<{ guid: string }>()

const { t } = useI18n()
const { data: lot, isLoading } = useLot(computed(() => props.guid))

const statusColor: Record<LotStatus, string> = {
  scheduled: 'blue',
  open: 'green',
  sold: 'gold',
  unsold: 'default',
}
</script>

<template>
  <div>
    <AppHeader :title="lot ? `${t('lots.title')} ${lot.lot_number}` : t('lots.title')" :subtitle="lot?.auction?.title" />

    <a-skeleton v-if="isLoading" active />

    <template v-else-if="lot">
      <div class="lot-detail__summary">
        <div class="lot-detail__stat">
          <span class="lot-detail__label">{{ t('lots.detail.currentPrice') }}</span>
          <strong>{{ lot.current_price ? `$${lot.current_price}` : '—' }}</strong>
        </div>
        <div class="lot-detail__stat">
          <span class="lot-detail__label">{{ t('lots.detail.reservePrice') }}</span>
          <strong>{{ lot.reserve_price ? `$${lot.reserve_price}` : '—' }}</strong>
        </div>
        <div class="lot-detail__stat">
          <span class="lot-detail__label">{{ t('lots.detail.currentWinner') }}</span>
          <strong>{{ lot.current_winner?.name ?? t('lots.detail.noWinnerYet') }}</strong>
        </div>
        <div class="lot-detail__stat">
          <span class="lot-detail__label">{{ t('lots.table.status') }}</span>
          <a-tag :color="statusColor[lot.status]">{{ t(`lots.status.${lot.status}`) }}</a-tag>
        </div>
      </div>

      <FormSection :title="t('lots.form.products')">
        <a-tag v-for="p in lot.products" :key="p.guid">{{ p.title }} × {{ p.quantity }}</a-tag>
      </FormSection>

      <FormSection :title="t('lots.detail.placeBidTitle')">
        <PermissionGuard permission="bids.create">
          <PlaceBidForm :guid="lot.guid" />
        </PermissionGuard>
      </FormSection>

      <FormSection>
        <LotBidHistory :guid="lot.guid" />
      </FormSection>
    </template>
  </div>
</template>

<style scoped>
.lot-detail__summary {
  display: flex;
  flex-wrap: wrap;
  gap: 24px;
  margin-bottom: 20px;
}
.lot-detail__stat {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.lot-detail__label {
  font-size: 12px;
  color: var(--dt-muted, #6b8cae);
}
</style>
