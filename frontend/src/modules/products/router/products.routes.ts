import type { RouteRecordRaw } from 'vue-router'

export const productsRoutes: RouteRecordRaw[] = [
  {
    path: '/products',
    name: 'products',
    component: () => import('@/modules/products/pages/ProductsPage.vue'),
    meta: { requiresAuth: true, title: 'Productos' },
  },
  {
    path: '/products/nuevo',
    name: 'products-create',
    component: () => import('@/modules/products/pages/ProductFormPage.vue'),
    meta: { requiresAuth: true, title: 'Nuevo producto' },
  },
  {
    path: '/products/:guid/editar',
    name: 'products-edit',
    component: () => import('@/modules/products/pages/ProductFormPage.vue'),
    props: true,
    meta: { requiresAuth: true, title: 'Editar producto' },
  },
]
