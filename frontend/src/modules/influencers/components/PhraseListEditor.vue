<script setup lang="ts">
import { ref } from 'vue'
import { PlusOutlined, DeleteOutlined } from '@ant-design/icons-vue'
import BaseButton from '@/components/atoms/buttons/BaseButton.vue'

const props = defineProps<{
  label: string
  placeholder?: string
}>()

const model = defineModel<string[]>({ default: () => [] })

const draft = ref('')

function add() {
  const value = draft.value.trim()
  if (!value) return
  model.value = [...model.value, value]
  draft.value = ''
}

function remove(index: number) {
  model.value = model.value.filter((_, i) => i !== index)
}
</script>

<template>
  <div class="phrase-editor">
    <label class="phrase-editor__label">{{ label }}</label>

    <div class="phrase-editor__input-row">
      <a-input
        v-model:value="draft"
        :placeholder="placeholder ?? 'Escribí una frase y agregala'"
        allow-clear
        @press-enter="add"
      />
      <BaseButton variant="secondary" size="small" @click="add">
        <template #icon><PlusOutlined /></template>
        Agregar
      </BaseButton>
    </div>

    <ul v-if="model.length" class="phrase-editor__list">
      <li v-for="(phrase, index) in model" :key="index" class="phrase-editor__item">
        <span class="phrase-editor__text">{{ phrase }}</span>
        <BaseButton
          variant="row-action"
          size="small"
          danger
          tooltip="Quitar"
          @click="remove(index)"
        >
          <template #icon><DeleteOutlined /></template>
        </BaseButton>
      </li>
    </ul>
    <p v-else class="phrase-editor__empty">Sin frases cargadas.</p>
  </div>
</template>

<style scoped>
.phrase-editor {
  margin-bottom: 16px;
}
.phrase-editor__label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
}
.phrase-editor__input-row {
  display: flex;
  gap: 8px;
  align-items: center;
}
.phrase-editor__list {
  list-style: none;
  margin: 10px 0 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.phrase-editor__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 6px 10px;
  border: 1px solid var(--dt-border, #e2e8f0);
  border-radius: 6px;
}
.phrase-editor__text {
  flex: 1;
  word-break: break-word;
}
.phrase-editor__empty {
  margin: 8px 0 0;
  color: var(--dt-muted, #6b8cae);
  font-size: 13px;
}
</style>
