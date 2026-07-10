import type { RouteRecordRaw } from 'vue-router'

export const apiClientsRoutes: RouteRecordRaw[] = [
  {
    path:      '/api-clients',
    name:      'api-clients-module',
    component: () => import('@/modules/api-clients/pages/ApiClientsPage.vue'),
    meta:      { requiresAuth: true, title: 'Clientes API' },
  },
]
