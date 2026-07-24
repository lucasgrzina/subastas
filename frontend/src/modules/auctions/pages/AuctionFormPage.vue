<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import AuctionForm from '../components/forms/AuctionForm.vue'
import { useAuction } from '../composables/useAuction'
import { useCreateAuction } from '../composables/useCreateAuction'
import { useUpdateAuction } from '../composables/useUpdateAuction'
import type { AuctionFormValues } from '../validators/auction.validator'

const props = defineProps<{ guid?: string }>()

const router = useRouter()
const { t } = useI18n()
const formRef = ref<InstanceType<typeof AuctionForm> | null>(null)

const { data: auction, isLoading } = useAuction(computed(() => props.guid))

function goToList() {
  router.push({ name: 'auctions' })
}

const isEdit = computed(() => Boolean(props.guid))
const title = computed(() => (isEdit.value ? t('auctions.edit') : t('auctions.new')))

const createAuction = useCreateAuction()
const updateAuction = useUpdateAuction()

const isMutating = computed(() => createAuction.isPending.value || updateAuction.isPending.value)
const fieldErrors = computed(() => (isEdit.value ? updateAuction.fieldErrors.value : createAuction.fieldErrors.value))
const generalError = computed(() => (isEdit.value ? updateAuction.generalError.value : createAuction.generalError.value))

function handleSubmit(values: AuctionFormValues) {
  if (isEdit.value) {
    updateAuction.mutate(
      { guid: props.guid!, payload: values },
      { onSuccess: goToList },
    )
  } else {
    createAuction.mutate(values, { onSuccess: goToList })
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
          {{ isEdit ? t('auctions.form.updateSave') : t('auctions.form.createSave') }}
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
    <AuctionForm
      v-else
      ref="formRef"
      :mode="isEdit ? 'edit' : 'create'"
      :initial-values="auction ?? null"
      :field-errors="fieldErrors"
      hide-footer
      @submit="handleSubmit"
    />
  </div>
</template>
