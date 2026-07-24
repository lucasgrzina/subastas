<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import ProductImageGalleryUploader from './ProductImageGalleryUploader.vue'
import { productDetailsSchema, productImagesSchema } from '../../validators/product.validator'
import { useWineries, useGrapeVarieties, useWineRegions } from '../../composables/useReferenceData'
import type { GalleryItem } from './gallery-item'
import type { ProductItem, CreateProductPayload, UpdateProductPayload, NewImagePayload, KeptImagePayload } from '../../types/product.types'

const props = withDefaults(
  defineProps<{
    mode: 'create' | 'edit'
    initialValues?: ProductItem | null
    fieldErrors?: Record<string, string> | null
    loading?: boolean
    saveLabel?: string
    hideFooter?: boolean
  }>(),
  { loading: false, hideFooter: false },
)

const emit = defineEmits<{
  submit: [values: CreateProductPayload | UpdateProductPayload]
}>()

const { data: wineries, isLoading: loadingWineries } = useWineries()
const { data: grapeVarieties, isLoading: loadingGrapeVarieties } = useGrapeVarieties()
const { data: wineRegions, isLoading: loadingWineRegions } = useWineRegions()

const wineryOptions = computed(() => (wineries.value ?? []).map((w) => ({ label: w.name, value: w.guid })))
const grapeVarietyOptions = computed(() => (grapeVarieties.value ?? []).map((g) => ({ label: g.name, value: g.guid })))
const wineRegionOptions = computed(() => (wineRegions.value ?? []).map((r) => ({ label: r.name, value: r.guid })))

const { errors, defineField, handleSubmit, setErrors, setValues } = useForm({
  validationSchema: toTypedSchema(productDetailsSchema),
})

const [title, titleAttrs] = defineField('title')
const [description, descriptionAttrs] = defineField('description')
const [status] = defineField('status')
const [year, yearAttrs] = defineField('wine_details.year')
const [wineryGuid] = defineField('wine_details.winery_guid')
const [grapeVarietyGuid] = defineField('wine_details.grape_variety_guid')
const [wineRegionGuid] = defineField('wine_details.wine_region_guid')

const gallery = ref<GalleryItem[]>([])
const galleryError = ref<string | null>(null)

watch(
  () => props.initialValues,
  (product) => {
    if (props.mode === 'edit' && product) {
      setValues({
        title: product.title,
        description: product.description ?? '',
        status: product.status,
        wine_details: product.wine_details
          ? {
              year: product.wine_details.year,
              winery_guid: product.wine_details.winery.guid,
              grape_variety_guid: product.wine_details.grape_variety.guid,
              wine_region_guid: product.wine_details.wine_region.guid,
            }
          : undefined,
      })

      gallery.value = product.images.map((image) => ({
        key: `image-${image.id}`,
        url: image.url,
        is_main: image.is_main,
        image_id: image.id,
      }))
    } else {
      setValues({
        status: 'draft',
      })

      gallery.value = []
    }
  },
  { immediate: true },
)

watch(() => props.fieldErrors, (errs) => {
  setErrors(errs ?? {})
})

function buildImagesPayload(): (NewImagePayload | KeptImagePayload)[] {
  return gallery.value.map((item) =>
    item.token
      ? { token: item.token, is_main: item.is_main }
      : { image_id: item.image_id as number, is_main: item.is_main },
  )
}

const onSubmit = handleSubmit((values) => {
  const imagesPayload = buildImagesPayload()
  const validation = productImagesSchema.safeParse(imagesPayload)

  if (!validation.success) {
    galleryError.value = validation.error.issues[0]?.message ?? 'Revisá las imágenes cargadas.'
    return
  }

  galleryError.value = null

  emit('submit', {
    title: values.title,
    description: values.description ?? null,
    status: values.status,
    wine_details: values.wine_details,
    images: imagesPayload,
  } as CreateProductPayload)
})

defineExpose({ submit: onSubmit })
</script>

<template>
  <a-form class="flex flex-col gap-y-6" layout="vertical" @submit.prevent="onSubmit">
    <FormSection title="Datos generales">
      <a-row :gutter="12">
        <a-col :xs="24" :md="16">
          <a-form-item label="Título" :validate-status="errors.title ? 'error' : ''" :help="errors.title ?? ''">
            <a-input v-model:value="title" v-bind="titleAttrs" placeholder="Ej: Malbec Reserva 2020" />
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="8">
          <a-form-item label="Estado">
            <a-select
              v-model:value="status"
              :options="[
                { label: 'Borrador', value: 'draft' },
                { label: 'Publicado', value: 'published' },
              ]"
            />
          </a-form-item>
        </a-col>
        <a-col :xs="24">
          <a-form-item label="Descripción" :validate-status="errors.description ? 'error' : ''" :help="errors.description ?? ''">
            <a-textarea v-model:value="description" v-bind="descriptionAttrs" :rows="3" />
          </a-form-item>
        </a-col>
      </a-row>
    </FormSection>

    <FormSection title="Detalles del vino">
      <a-row :gutter="12">
        <a-col :xs="24" :md="6">
          <a-form-item
            label="Año"
            :validate-status="errors['wine_details.year'] ? 'error' : ''"
            :help="errors['wine_details.year'] ?? ''"
          >
            <a-input-number v-model:value="year" v-bind="yearAttrs" style="width: 100%" :min="1900" :max="2100" />
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="6">
          <a-form-item
            label="Bodega"
            :validate-status="errors['wine_details.winery_guid'] ? 'error' : ''"
            :help="errors['wine_details.winery_guid'] ?? ''"
          >
            <a-select v-model:value="wineryGuid" :options="wineryOptions" :loading="loadingWineries" show-search
              :filter-option="(input: string, option: { label: string }) => option.label.toLowerCase().includes(input.toLowerCase())" />
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="6">
          <a-form-item
            label="Variedad de uva"
            :validate-status="errors['wine_details.grape_variety_guid'] ? 'error' : ''"
            :help="errors['wine_details.grape_variety_guid'] ?? ''"
          >
            <a-select v-model:value="grapeVarietyGuid" :options="grapeVarietyOptions" :loading="loadingGrapeVarieties" show-search
              :filter-option="(input: string, option: { label: string }) => option.label.toLowerCase().includes(input.toLowerCase())" />
          </a-form-item>
        </a-col>
        <a-col :xs="24" :md="6">
          <a-form-item
            label="Región vitivinícola"
            :validate-status="errors['wine_details.wine_region_guid'] ? 'error' : ''"
            :help="errors['wine_details.wine_region_guid'] ?? ''"
          >
            <a-select v-model:value="wineRegionGuid" :options="wineRegionOptions" :loading="loadingWineRegions" show-search
              :filter-option="(input: string, option: { label: string }) => option.label.toLowerCase().includes(input.toLowerCase())" />
          </a-form-item>
        </a-col>
      </a-row>
    </FormSection>

    <FormSection title="Imágenes">
      <ProductImageGalleryUploader v-model="gallery" />
      <span v-if="galleryError" class="pf-error">{{ galleryError }}</span>
    </FormSection>

    <FormFooter
      v-if="!hideFooter"
      :loading="loading"
      cancel-to="/products"
      :save-label="saveLabel ?? 'Guardar'"
    />
  </a-form>
</template>

<style scoped>
.pf-error {
  font-size: 12px;
  color: #FF5A6A;
}
</style>
