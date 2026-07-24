import type { RouteRecordRaw } from 'vue-router'

export const auctionsRoutes: RouteRecordRaw[] = [
  {
    path: '/auctions',
    name: 'auctions',
    component: () => import('@/modules/auctions/pages/AuctionsPage.vue'),
    meta: { requiresAuth: true, title: 'Subastas' },
  },
  {
    path: '/auctions/nueva',
    name: 'auctions-create',
    component: () => import('@/modules/auctions/pages/AuctionFormPage.vue'),
    meta: { requiresAuth: true, title: 'Nueva subasta' },
  },
  {
    path: '/auctions/:guid/editar',
    name: 'auctions-edit',
    component: () => import('@/modules/auctions/pages/AuctionFormPage.vue'),
    props: true,
    meta: { requiresAuth: true, title: 'Editar subasta' },
  },
  {
    path: '/lots',
    name: 'lots',
    component: () => import('@/modules/auctions/pages/LotsPage.vue'),
    meta: { requiresAuth: true, title: 'Lotes' },
  },
  {
    path: '/lots/nuevo',
    name: 'lots-create',
    component: () => import('@/modules/auctions/pages/LotFormPage.vue'),
    meta: { requiresAuth: true, title: 'Nuevo lote' },
  },
  {
    path: '/lots/:guid/editar',
    name: 'lots-edit',
    component: () => import('@/modules/auctions/pages/LotFormPage.vue'),
    props: true,
    meta: { requiresAuth: true, title: 'Editar lote' },
  },
  {
    path: '/lots/:guid',
    name: 'lots-detail',
    component: () => import('@/modules/auctions/pages/LotDetailPage.vue'),
    props: true,
    meta: { requiresAuth: true, title: 'Detalle de lote' },
  },
]
