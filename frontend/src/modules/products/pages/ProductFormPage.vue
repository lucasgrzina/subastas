<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import ProductForm from '../components/forms/ProductForm.vue'
import { useProduct } from '../composables/useProduct'
import { useProductFormController } from '../composables/useProductFormController'

const props = defineProps<{
  guid?: string
}>()

const router = useRouter()
const formRef = ref<InstanceType<typeof ProductForm> | null>(null)

const { data: product, isLoading } = useProduct(computed(() => props.guid))

function goToList() {
  router.push({ name: 'products' })
}

const isEdit = computed(() => Boolean(props.guid))
const title = computed(() => (isEdit.value ? 'Editar producto' : 'Nuevo producto'))

const { fieldErrors, generalError, isPending, handleSubmit } = useProductFormController(product, goToList)
</script>

<template>
  <div>
    <AppHeader :title="title">
      <template #actions="{ buttonSize }">
        <BaseButton variant="secondary" :size="buttonSize" :disabled="isPending" @click="goToList">
          Cancelar
        </BaseButton>
        <BaseButton variant="primary" :size="buttonSize" :loading="isPending" @click="formRef?.submit()">
          {{ isEdit ? 'Guardar cambios' : 'Crear producto' }}
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
    <ProductForm
      v-else
      ref="formRef"
      :mode="isEdit ? 'edit' : 'create'"
      :initial-values="product ?? null"
      :field-errors="fieldErrors"
      hide-footer
      @submit="handleSubmit"
    />
  </div>
</template>
