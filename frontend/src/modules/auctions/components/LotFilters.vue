<script setup lang="ts">
import { SearchOutlined } from '@ant-design/icons-vue'
import FiltersRow from '@/components/filters/FiltersRow.vue'
import FiltersCol from '@/components/filters/FiltersCol.vue'
import FiltersWrapper from '@/components/filters/FiltersWrapper.vue'
import type { LotFilters } from '../types/lot.types'

const props = defineProps<{ filters: LotFilters }>()
const emit = defineEmits<{ 'update:filters': [filters: LotFilters] }>()
</script>

<template>
  <FiltersRow>
    <FiltersCol>
      <FiltersWrapper label="Buscar">
        <a-input
          :value="filters.search"
          placeholder="Número de lote"
          allow-clear
          @update:value="(v: string) => emit('update:filters', { ...props.filters, search: v, page: 1 })"
        >
          <template #prefix>
            <SearchOutlined :style="{ color: 'var(--dt-muted, #6B8CAE)' }" />
          </template>
        </a-input>
      </FiltersWrapper>
    </FiltersCol>
  </FiltersRow>
</template>
