<script setup lang="ts">
import { computed } from 'vue'
import { SearchOutlined } from '@ant-design/icons-vue'
import FiltersRow from '@/components/filters/FiltersRow.vue'
import FiltersCol from '@/components/filters/FiltersCol.vue'
import FiltersWrapper from '@/components/filters/FiltersWrapper.vue'
import type { ApiClientFilters } from '../types/api-client.types'

const props = defineProps<{ filters: ApiClientFilters }>()
const emit  = defineEmits<{ 'update:filters': [filters: ApiClientFilters] }>()

const dateRangeValue = computed<[string, string] | null>(() => {
  if (props.filters.date_from && props.filters.date_to) {
    return [props.filters.date_from, props.filters.date_to]
  }
  return null
})

function handleDateChange(val: [string, string] | null) {
  emit('update:filters', {
    ...props.filters,
    date_from: val ? val[0] : '',
    date_to:   val ? val[1] : '',
    page:      1,
  })
}
</script>

<template>
  <FiltersRow>
    <FiltersCol>
      <FiltersWrapper label="Buscar">
        <a-input
          :value="filters.search"
          placeholder="Nombre o email"
          allow-clear
          @update:value="(v: string) => emit('update:filters', { ...filters, search: v, page: 1 })"
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
          :value="filters.active === '' || filters.active === undefined ? '' : filters.active"
          style="width: 100%"
          :options="[
            { label: 'Todos', value: '' },
            { label: 'Activo', value: true },
            { label: 'Inactivo', value: false },
          ]"
          @update:value="(v: boolean | '') => emit('update:filters', { ...filters, active: v, page: 1 })"
        />
      </FiltersWrapper>
    </FiltersCol>

    <FiltersCol>
      <FiltersWrapper label="Fecha de creación">
        <a-range-picker
          :value="dateRangeValue"
          style="width: 100%"
          format="YYYY-MM-DD"
          value-format="YYYY-MM-DD"
          :placeholder="['Desde', 'Hasta']"
          @update:value="handleDateChange"
        />
      </FiltersWrapper>
    </FiltersCol>
  </FiltersRow>
</template>
