<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import BaseDrawer from '@/components/atoms/overlays/BaseDrawer.vue'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'
import InfluencerForm from '../forms/InfluencerForm.vue'
import { useInfluencerFormController } from '../../composables/useInfluencerFormController'
import type { InfluencerItem } from '../../types/influencer.types'

const props = defineProps<{
  influencer: InfluencerItem | null
}>()

const isOpen = defineModel<boolean>({ default: false })

const formRef = ref<InstanceType<typeof InfluencerForm> | null>(null)

const { isEdit, title, fieldErrors, generalError, isPending, resetErrors, handleSubmit } =
  useInfluencerFormController(
    computed(() => props.influencer),
    () => { isOpen.value = false },
  )

watch(isOpen, (open) => {
  if (open) resetErrors()
})
</script>

<template>
  <BaseDrawer v-model="isOpen" :title="title" :width="720" padding="0">
    <a-alert
      v-if="generalError && !fieldErrors"
      :message="generalError"
      type="error"
      show-icon
      style="margin-bottom: 16px"
    />

    <InfluencerForm
      ref="formRef"
      :mode="isEdit ? 'edit' : 'create'"
      :initial-values="influencer"
      :field-errors="fieldErrors"
      @submit="handleSubmit"
    />

    <template #footer>
      <a-space style="justify-content: flex-end; width: 100%">
        <BaseButton variant="secondary" :disabled="isPending" @click="isOpen = false">
          Cancelar
        </BaseButton>
        <BaseButton variant="primary" :loading="isPending" @click="formRef?.submit()">
          {{ isEdit ? 'Guardar cambios' : 'Crear influencer' }}
        </BaseButton>
      </a-space>
    </template>
  </BaseDrawer>
</template>
