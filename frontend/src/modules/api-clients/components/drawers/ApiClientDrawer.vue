<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import BaseDrawer from '@/components/atoms/overlays/BaseDrawer.vue'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'
import ApiClientForm from '../forms/ApiClientForm.vue'
import { useCreateApiClient } from '../../composables/useCreateApiClient'
import { useUpdateApiClient } from '../../composables/useUpdateApiClient'
import type { ApiClientItem, ApiClientCreatePayload, ApiClientUpdatePayload } from '../../types/api-client.types'

const props = defineProps<{
  client: ApiClientItem | null
}>()

const emit = defineEmits<{
  created: [token: string]
}>()

const isOpen = defineModel<boolean>({ default: false })

const isEdit = computed(() => Boolean(props.client))
const title  = computed(() => isEdit.value ? 'Editar cliente API' : 'Nuevo cliente API')

const formRef = ref<InstanceType<typeof ApiClientForm> | null>(null)

const {
  mutate: create,
  isPending: isCreating,
  fieldErrors: createErrors,
  generalError: createGeneralError,
  resetErrors: resetCreate,
} = useCreateApiClient()

const {
  mutate: update,
  isPending: isUpdating,
  fieldErrors: updateErrors,
  generalError: updateGeneralError,
  resetErrors: resetUpdate,
} = useUpdateApiClient()

const fieldErrors  = computed(() => isEdit.value ? updateErrors.value  : createErrors.value)
const generalError = computed(() => isEdit.value ? updateGeneralError.value : createGeneralError.value)
const isPending    = computed(() => isEdit.value ? isUpdating.value    : isCreating.value)

const initialValues = computed(() =>
  props.client
    ? { nombre: props.client.nombre, email: props.client.email ?? '' }
    : undefined,
)

watch(isOpen, (open) => {
  if (open) {
    if (isEdit.value) {
      resetUpdate()
    } else {
      resetCreate()
    }
  }
})

function handleSubmit(values: ApiClientCreatePayload | ApiClientUpdatePayload) {
  if (!isEdit.value) {
    create(values as ApiClientCreatePayload, {
      onSuccess: (data) => {
        isOpen.value = false
        emit('created', data.plain_token)
      },
    })
  } else if (props.client) {
    update(
      { guid: props.client.guid, payload: values as ApiClientUpdatePayload },
      {
        onSuccess: () => {
          isOpen.value = false
        },
      },
    )
  }
}
</script>

<template>
  <BaseDrawer v-model="isOpen" :title="title" :width="520">
    <a-alert
      v-if="generalError && !fieldErrors"
      :message="generalError"
      type="error"
      show-icon
      style="margin-bottom: 16px"
    />

    <ApiClientForm
      ref="formRef"
      :mode="isEdit ? 'edit' : 'create'"
      :initial-values="initialValues"
      :loading="isPending"
      :field-errors="fieldErrors"
      @submit="handleSubmit"
    >
      <template #footer />
    </ApiClientForm>

    <template #footer>
      <a-space style="justify-content: flex-end; width: 100%">
        <BaseButton variant="secondary" :disabled="isPending" @click="isOpen = false">
          Cancelar
        </BaseButton>
        <BaseButton variant="primary" :loading="isPending" @click="formRef?.submit()">
          {{ isEdit ? 'Guardar cambios' : 'Crear cliente' }}
        </BaseButton>
      </a-space>
    </template>
  </BaseDrawer>
</template>
