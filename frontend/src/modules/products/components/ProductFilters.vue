<script setup lang="ts">
import { SearchOutlined } from '@ant-design/icons-vue'
import FiltersRow from '@/components/filters/FiltersRow.vue'
import FiltersCol from '@/components/filters/FiltersCol.vue'
import FiltersWrapper from '@/components/filters/FiltersWrapper.vue'
import type { ProductFilters } from '../types/product.types'

const props = defineProps<{ filters: ProductFilters }>()
const emit = defineEmits<{ 'update:filters': [filters: ProductFilters] }>()
</script>

<template>
  <FiltersRow>
    <FiltersCol>
      <FiltersWrapper label="Buscar">
        <a-input
          :value="filters.search"
          placeholder="Título"
          allow-clear
          @update:value="(v: string) => emit('update:filters', { ...props.filters, search: v, page: 1 })"
        >
          <template #prefix>
            <SearchOutlined :style="{ color: 'var(--dt-muted, #6B8CAE)' }" />
          </template>
        </a-input>
      </FiltersWrapper>
    </FiltersCol>

    <FiltersCol>
      <FiltersWrapper label="Estado">
        <a-select
          :value="filters.status === '' || filters.status === undefined ? '' : filters.status"
          style="width: 100%"
          :options="[
            { label: 'Todos', value: '' },
            { label: 'Borrador', value: 'draft' },
            { label: 'Publicado', value: 'published' },
          ]"
          @update:value="(v: string) => emit('update:filters', { ...props.filters, status: v as ProductFilters['status'], page: 1 })"
        />
      </FiltersWrapper>
    </FiltersCol>

    <!--FiltersCol>
      <FiltersWrapper label="Eliminados">
        <a-switch
          :checked="filters.with_trashed ?? false"
          @change="(v: boolean) => emit('update:filters', { ...props.filters, with_trashed: v, page: 1 })"
        />
      </FiltersWrapper>
    </FiltersCol-->
  </FiltersRow>
</template>
