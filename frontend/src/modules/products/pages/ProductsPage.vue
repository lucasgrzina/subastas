<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { PlusOutlined } from '@ant-design/icons-vue'
import ProductFilters from '../components/ProductFilters.vue'
import ProductsTable from '../components/ProductsTable.vue'
import PermissionGuard from '@/components/shared/PermissionGuard.vue'
import { useProducts } from '../composables/useProducts'
import { useDeleteProduct } from '../composables/useDeleteProduct'
import { useRestoreProduct } from '../composables/useRestoreProduct'
import { useProductFilters } from '../composables/useProductFilters'
import type { ProductFilters as ProductFiltersType, ProductItem } from '../types/product.types'

const router = useRouter()
const { filters, debouncedSearch } = useProductFilters()
const { deleteProduct } = useDeleteProduct()
const { restoreProduct } = useRestoreProduct()

const filtersModel = computed<ProductFiltersType>({
  get: () => filters,
  set: (val) => Object.assign(filters, val),
})

const queryParams = computed(() => ({
  ...filters,
  search: debouncedSearch.value,
}))

const { data, isLoading } = useProducts(queryParams)

function handleEdit(product: ProductItem) {
  router.push({ name: 'products-edit', params: { guid: product.guid } })
}
</script>

<template>
  <div>
    <AppHeader title="Productos" subtitle="Catálogo de productos (vertical de vinos)">
      <template #actions="{ buttonSize }">
        <PermissionGuard permission="products.create">
          <BaseButton variant="primary" :size="buttonSize" @click="router.push({ name: 'products-create' })">
            <PlusOutlined />
            Nuevo producto
          </BaseButton>
        </PermissionGuard>
      </template>
    </AppHeader>

    <ProductFilters v-model:filters="filtersModel" />

    <EmptyState
      v-if="!isLoading && !data?.data.length"
      message="No se encontraron productos."
      icon="🍷"
    >
      <PermissionGuard permission="products.create">
        <BaseButton variant="primary" class="mt-3" @click="router.push({ name: 'products-create' })">
          <template #icon><PlusOutlined /></template>
          Crear primer producto
        </BaseButton>
      </PermissionGuard>
    </EmptyState>

    <ProductsTable
      v-else
      :products="data?.data ?? []"
      :loading="isLoading"
      @edit="handleEdit"
      @delete="deleteProduct"
      @restore="restoreProduct"
    />

    <BasePagination
      :page="filters.page"
      :total="data?.total ?? 0"
      :per-page="filters.per_page"
      @change="({ page, perPage }) => { filters.page = page; filters.per_page = perPage }"
    />
  </div>
</template>

<style scoped>
.mt-3 { margin-top: 12px; }
</style>
