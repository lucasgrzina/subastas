<script setup lang="ts">
import { UploadOutlined, DeleteOutlined, StarOutlined, StarFilled } from '@ant-design/icons-vue'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'
import { useTempImageUpload } from '@/core/composables/useTempImageUpload'
import type { GalleryItem } from './gallery-item'

const modelValue = defineModel<GalleryItem[]>({ required: true })

const { mutate, isPending } = useTempImageUpload()

function beforeUpload(file: File) {
  mutate(file, {
    onSuccess: (result) => {
      const isFirst = modelValue.value.length === 0
      modelValue.value = [
        ...modelValue.value,
        {
          key: result.token,
          url: result.url,
          is_main: isFirst,
          token: result.token,
        },
      ]
    },
  })
  return false
}

function removeImage(key: string) {
  const removedWasMain = modelValue.value.find((item) => item.key === key)?.is_main ?? false
  const remaining = modelValue.value.filter((item) => item.key !== key)

  if (removedWasMain && remaining.length > 0) {
    remaining[0] = { ...remaining[0], is_main: true }
  }

  modelValue.value = remaining
}

function setMain(key: string) {
  modelValue.value = modelValue.value.map((item) => ({ ...item, is_main: item.key === key }))
}
</script>

<template>
  <div class="gallery-uploader">
    <div class="gallery-uploader__grid">
      <div v-for="item in modelValue" :key="item.key" class="gallery-uploader__item">
        <img :src="item.url" alt="Imagen del producto" />

        <div class="gallery-uploader__badge" v-if="item.is_main">Principal</div>

        <div class="gallery-uploader__actions">
          <BaseButton
            variant="row-action"
            size="small"
            shape="circle"
            :tooltip="item.is_main ? 'Es la imagen principal' : 'Marcar como principal'"
            @click="setMain(item.key)"
          >
            <template #icon>
              <StarFilled v-if="item.is_main" />
              <StarOutlined v-else />
            </template>
          </BaseButton>
          <BaseButton
            variant="row-action"
            size="small"
            shape="circle"
            danger
            tooltip="Quitar"
            @click="removeImage(item.key)"
          >
            <template #icon><DeleteOutlined /></template>
          </BaseButton>
        </div>
      </div>

      <a-upload
        :show-upload-list="false"
        :before-upload="beforeUpload"
        accept="image/jpeg,image/png,image/webp"
      >
        <div class="gallery-uploader__add">
          <UploadOutlined />
          <span>{{ isPending ? 'Subiendo...' : 'Agregar imagen' }}</span>
        </div>
      </a-upload>
    </div>

    <p class="gallery-uploader__hint">JPEG, PNG o WebP. Marcá una imagen como principal.</p>
  </div>
</template>

<style scoped>
.gallery-uploader {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.gallery-uploader__grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.gallery-uploader__item {
  position: relative;
  width: 140px;
  height: 140px;
  border: 1px solid var(--dt-border, #e2e8f0);
  border-radius: 8px;
  overflow: hidden;
}
.gallery-uploader__item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.gallery-uploader__badge {
  position: absolute;
  bottom: 4px;
  left: 4px;
  background: rgba(0, 0, 0, 0.6);
  color: #fff;
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 4px;
}
.gallery-uploader__actions {
  position: absolute;
  top: 6px;
  right: 6px;
  display: flex;
  gap: 4px;
}
.gallery-uploader__add {
  width: 140px;
  height: 140px;
  border: 1px dashed var(--dt-border, #cbd5e1);
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  cursor: pointer;
  color: var(--dt-muted, #6b8cae);
  font-size: 12px;
}
.gallery-uploader__hint {
  margin: 0;
  color: var(--dt-muted, #6b8cae);
  font-size: 12px;
}
</style>
