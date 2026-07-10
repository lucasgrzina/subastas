<script setup lang="ts">
import { ref } from 'vue'
import { UploadOutlined, DeleteOutlined } from '@ant-design/icons-vue'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'
import { useTempImageUpload } from '@/core/composables/useTempImageUpload'

const props = defineProps<{
  /** URL de la imagen actual del modelo (edición). */
  currentUrl: string | null
}>()

const emit = defineEmits<{
  /** Nuevo token temporal subido (o null si se quitó). */
  'update:token': [token: string | null]
  /** Se solicitó quitar la imagen actual. */
  'update:removed': [removed: boolean]
}>()

const { mutate, isPending } = useTempImageUpload()

// Preview: prioriza la imagen temporal recién subida; si no, la actual del modelo.
const tempPreview = ref<string | null>(null)
const removed = ref(false)

function beforeUpload(file: File) {
  mutate(file, {
    onSuccess: (result) => {
      tempPreview.value = result.url
      removed.value = false
      emit('update:token', result.token)
      emit('update:removed', false)
    },
  })
  return false // no dejamos que antd haga el POST; subimos nosotros al temporal
}

function removeImage() {
  tempPreview.value = null
  removed.value = true
  emit('update:token', null)
  emit('update:removed', true)
}

function displayUrl(): string | null {
  if (removed.value) return null
  return tempPreview.value ?? props.currentUrl
}
</script>

<template>
  <div class="image-uploader">
    <div class="image-uploader__preview">
      <img v-if="displayUrl()" :src="displayUrl()!" alt="Imagen de personaje" />
      <div v-else class="image-uploader__placeholder">Sin imagen</div>

      <div class="image-uploader__actions">
        <a-upload
          :show-upload-list="false"
          :before-upload="beforeUpload"
          accept="image/jpeg,image/png,image/webp"
        >
          <BaseButton variant="row-action" shape="circle" :loading="isPending" tooltip="Subir imagen">
            <template #icon><UploadOutlined /></template>
          </BaseButton>
        </a-upload>

        <BaseButton
          v-if="displayUrl()"
          variant="row-action"
          shape="circle"
          danger
          tooltip="Quitar"
          @click="removeImage"
        >
          <template #icon><DeleteOutlined /></template>
        </BaseButton>
      </div>
    </div>

    <p class="image-uploader__hint">JPEG, PNG o WebP.</p>
  </div>
</template>

<style scoped>
.image-uploader {
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: flex-start;
}
.image-uploader__preview {
  position: relative;
  width: 160px;
  height: 160px;
  border: 1px solid var(--dt-border, #e2e8f0);
  border-radius: 8px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--dt-surface-alt, #f8fafc);
}
.image-uploader__preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.image-uploader__placeholder {
  color: var(--dt-muted, #6b8cae);
  font-size: 13px;
}
.image-uploader__actions {
  position: absolute;
  top: 6px;
  right: 6px;
  display: flex;
  gap: 0px;
  border-radius: 20px;
  background: #f3f5f7b3;
}
.image-uploader__hint {
  margin: 0;
  color: var(--dt-muted, #6b8cae);
  font-size: 12px;
  max-width: 320px;
}
</style>
