import type { NavigationGuard } from 'vue-router'
import { useAuthStore } from '@/modules/auth/stores/auth.store'
import { ROUTES } from '@/core/constants/app'

export const guestGuard: NavigationGuard = () => {
    const auth = useAuthStore()

    if (auth.isAuthenticated) {
        return { path: ROUTES.dashboard, replace: true }
    }

    return true
}
