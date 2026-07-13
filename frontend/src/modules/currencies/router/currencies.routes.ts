import type { RouteRecordRaw } from 'vue-router'

export const currenciesRoutes: RouteRecordRaw[] = [
  {
    path: '/currencies',
    name: 'currencies',
    component: () => import('@/modules/currencies/pages/CurrenciesPage.vue'),
    meta: { requiresAuth: true, title: 'Monedas' },
  },
  {
    path: '/currencies/nuevo',
    name: 'currencies-create',
    component: () => import('@/modules/currencies/pages/CurrencyFormPage.vue'),
    meta: { requiresAuth: true, title: 'Nueva moneda' },
  },
  {
    path: '/currencies/:guid/editar',
    name: 'currencies-edit',
    component: () => import('@/modules/currencies/pages/CurrencyFormPage.vue'),
    props: true,
    meta: { requiresAuth: true, title: 'Editar moneda' },
  },
]
