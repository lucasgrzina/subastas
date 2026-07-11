import { createRouter, createWebHistory } from 'vue-router'
import { authRoutes } from '@/modules/auth/router/auth.routes'
import { usersRoutes } from '@/modules/users/router/users.routes'
import { rolesRoutes } from '@/modules/roles/router/roles.routes'
import { dashboardRoutes } from '@/modules/dashboard/router/dashboard.routes'
import { settingsRoutes } from '@/modules/settings/router/settings.routes'
import { supportMessagesRoutes } from '@/modules/support-messages/router/support-messages.routes'
import { systemSettingsRoutes } from '@/modules/system-settings/router/system-settings.routes'
import { apiClientsRoutes } from '@/modules/api-clients/router/api-clients.routes'
import { influencersRoutes } from '@/modules/influencers/router/influencers.routes'
import { productsRoutes } from '@/modules/products/router/products.routes'
import { authGuard } from './guards/auth.guard'
import { guestGuard } from './guards/guest.guard'

const routes = [
    {
        path: '/auth',
        component: () => import('@/layouts/AuthLayout.vue'),
        children: authRoutes,
    },
    {
        path: '/',
        component: () => import('@/layouts/AppLayout.vue'),
        beforeEnter: authGuard,
        children: [
            ...dashboardRoutes,
            ...usersRoutes,
            ...rolesRoutes,
            ...apiClientsRoutes,
            ...influencersRoutes,
            ...productsRoutes,
            ...settingsRoutes,
            ...supportMessagesRoutes,
            ...systemSettingsRoutes,
        ],
    },
    {
        path: '/change-expired-password',
        component: () => import('@/layouts/ForceChangePasswordLayout.vue'),
        beforeEnter: authGuard,
        children: [
            {
                path: '',
                name: 'change-expired-password',
                component: () => import('@/modules/auth/pages/ChangeExpiredPasswordPage.vue'),
                meta: { requiresAuth: true, title: 'Cambiar contraseña' },
            },
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
]

export const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to, from) => {
    if (to.meta?.requiresGuest) {
        return guestGuard(to, from, () => undefined)
    }
    return true
})
