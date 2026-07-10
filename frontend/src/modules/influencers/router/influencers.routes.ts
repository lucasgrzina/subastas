import type { RouteRecordRaw } from 'vue-router'

export const influencersRoutes: RouteRecordRaw[] = [
  {
    path: '/influencers',
    name: 'influencers-module',
    component: () => import('@/modules/influencers/pages/InfluencersPage.vue'),
    meta: { requiresAuth: true, title: 'Influencers' },
  },
  {
    path: '/influencers/nuevo',
    name: 'influencers-create',
    component: () => import('@/modules/influencers/pages/InfluencerFormPage.vue'),
    meta: { requiresAuth: true, title: 'Nuevo influencer' },
  },
  {
    path: '/influencers/:guid/editar',
    name: 'influencers-edit',
    component: () => import('@/modules/influencers/pages/InfluencerFormPage.vue'),
    props: true,
    meta: { requiresAuth: true, title: 'Editar influencer' },
  },
]
