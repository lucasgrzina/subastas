<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'
import InfluencerForm from '../components/forms/InfluencerForm.vue'
import { useInfluencer } from '../composables/useInfluencer'
import { useInfluencerFormController } from '../composables/useInfluencerFormController'

const props = defineProps<{
  guid?: string
}>()

const router = useRouter()
const formRef = ref<InstanceType<typeof InfluencerForm> | null>(null)

const { data: influencer, isLoading } = useInfluencer(computed(() => props.guid))

function goToList() {
  router.push({ name: 'influencers-module' })
}

// Basado en el param de ruta (no en los datos ya cargados) para que el título
// no parpadee a "Nuevo influencer" mientras se carga el influencer a editar.
const isEdit = computed(() => Boolean(props.guid))
const title = computed(() => (isEdit.value ? 'Editar influencer' : 'Nuevo influencer'))

const { fieldErrors, generalError, isPending, handleSubmit } =
  useInfluencerFormController(influencer, goToList)
</script>

<template>
  <div>
    <AppHeader :title="title">
      <template #actions="{ buttonSize }">
        <BaseButton variant="secondary" :size="buttonSize" :disabled="isPending" @click="goToList">
          Cancelar
        </BaseButton>
        <BaseButton variant="primary" :size="buttonSize" :loading="isPending" @click="formRef?.submit()">
          {{ isEdit ? 'Guardar cambios' : 'Crear influencer' }}
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
    <InfluencerForm
      v-else
      ref="formRef"
      :mode="isEdit ? 'edit' : 'create'"
      :initial-values="influencer ?? null"
      :field-errors="fieldErrors"
      @submit="handleSubmit"
    />
  </div>
</template>
