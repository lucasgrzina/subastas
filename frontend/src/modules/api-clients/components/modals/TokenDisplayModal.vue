<script setup lang="ts">
import { ref } from 'vue'
import { useClipboard } from '@vueuse/core'
import { CheckOutlined, CopyOutlined } from '@ant-design/icons-vue'
import BaseModal from '@/components/atoms/overlays/BaseModal.vue'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'

const props = defineProps<{
  token: string
}>()

const emit = defineEmits<{
  close: []
}>()

const isOpen = defineModel<boolean>({ default: false })

const { copy, copied } = useClipboard({ legacy: true })

function handleClose() {
  isOpen.value = false
  emit('close')
}

async function copyToken() {
  await copy(props.token)
}
</script>

<template>
  <BaseModal
    v-model="isOpen"
    title="Token generado (guardalo, no se vuelve a mostrar)"
    :width="560"
    :destroy-on-close="false"
    @cancel="handleClose"
  >
    <a-alert
      message="Copiá el token ahora. No podrás verlo nuevamente."
      type="warning"
      show-icon
      style="margin-bottom: 16px"
    />

    <a-input-group compact style="display: flex">
      <a-input
        :value="token"
        readonly
        style="flex: 1; font-family: monospace; font-size: 13px"
      />
      <BaseButton
        variant="secondary"
        :title="copied ? '¡Copiado!' : 'Copiar'"
        @click="copyToken"
      >
        <template #icon>
          <CheckOutlined v-if="copied" style="color: #52c41a" />
          <CopyOutlined v-else />
        </template>
        {{ copied ? '¡Copiado!' : 'Copiar' }}
      </BaseButton>
    </a-input-group>

    <template #footer>
      <div style="text-align: right">
        <BaseButton variant="primary" @click="handleClose">Cerrar</BaseButton>
      </div>
    </template>
  </BaseModal>
</template>
