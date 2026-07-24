<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import LotForm from '../components/forms/LotForm.vue'
import { useLot } from '../composables/useLot'
import { useCreateLot } from '../composables/useCreateLot'
import { useUpdateLot } from '../composables/useUpdateLot'
import type { CreateLotPayload, UpdateLotPayload } from '../types/lot.types'

const props = defineProps<{ guid?: string }>()

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const formRef = ref<InstanceType<typeof LotForm> | null>(null)

const { data: lot, isLoading } = useLot(computed(() => props.guid))
const presetAuctionGuid = computed(() => (route.query.auction as string | undefined) ?? undefined)

function goToList() {
  router.push({ name: 'lots' })
}

const isEdit = computed(() => Boolean(props.guid))
const title = computed(() => (isEdit.value ? t('lots.edit') : t('lots.new')))

const createLot = useCreateLot()
const updateLot = useUpdateLot()

const isMutating = computed(() => createLot.isPending.value || updateLot.isPending.value)
const fieldErrors = computed(() => (isEdit.value ? updateLot.fieldErrors.value : createLot.fieldErrors.value))
const generalError = computed(() => (isEdit.value ? updateLot.generalError.value : createLot.generalError.value))

function handleSubmit(values: CreateLotPayload | UpdateLotPayload) {
  if (isEdit.value) {
    updateLot.mutate(
      { guid: props.guid!, payload: values as UpdateLotPayload },
      { onSuccess: goToList },
    )
  } else {
    createLot.mutate(values as CreateLotPayload, { onSuccess: goToList })
  }
}
</script>

<template>
  <div>
    <AppHeader :title="title">
      <template #actions="{ buttonSize }">
        <BaseButton variant="secondary" :size="buttonSize" :disabled="isMutating" @click="goToList">
          {{ t('auctions.cancel') }}
        </BaseButton>
        <BaseButton variant="primary" :size="buttonSize" :loading="isMutating" @click="formRef?.submit()">
          {{ isEdit ? t('lots.form.updateSave') : t('lots.form.createSave') }}
        </BaseButton>
      </template>
    </AppHeader>

    <a-alert
      v-if="generalError && !fieldErrors"
      :message="generalError"
      type="error"
      show-icon
      style="margin-bottom: 16px"
    />

    <a-skeleton v-if="guid && isLoading" active />
    <LotForm
      v-else
      ref="formRef"
      :mode="isEdit ? 'edit' : 'create'"
      :initial-values="lot ?? null"
      :preset-auction-guid="presetAuctionGuid"
      :field-errors="fieldErrors"
      hide-footer
      @submit="handleSubmit"
    />
  </div>
</template>
