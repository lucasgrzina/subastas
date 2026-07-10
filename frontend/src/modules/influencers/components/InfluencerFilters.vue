<script setup lang="ts">
import { SearchOutlined } from '@ant-design/icons-vue'
import FiltersRow from '@/components/filters/FiltersRow.vue'
import FiltersCol from '@/components/filters/FiltersCol.vue'
import FiltersWrapper from '@/components/filters/FiltersWrapper.vue'
import type { InfluencerFilters } from '../types/influencer.types'

const props = defineProps<{ filters: InfluencerFilters }>()
const emit = defineEmits<{ 'update:filters': [filters: InfluencerFilters] }>()
</script>

<template>
  <FiltersRow>
    <FiltersCol>
      <FiltersWrapper label="Buscar">
        <a-input
          :value="filters.search"
          placeholder="Nombre, alias o especialidad"
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
          :value="filters.activo === '' || filters.activo === undefined ? '' : filters.activo"
          style="width: 100%"
          :options="[
            { label: 'Todos', value: '' },
            { label: 'Activo', value: true },
            { label: 'Inactivo', value: false },
          ]"
          @update:value="(v: boolean | '') => emit('update:filters', { ...props.filters, activo: v, page: 1 })"
        />
      </FiltersWrapper>
    </FiltersCol>
  </FiltersRow>
</template>
