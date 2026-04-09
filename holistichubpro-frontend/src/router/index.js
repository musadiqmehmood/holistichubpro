// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('@/views/auth/LoginView.vue'),
        meta: { guest: true },
    },
    {
        path: '/change-password',
        name: 'ChangePassword',
        component: () => import('@/views/auth/ChangePasswordView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/verify-email',
        name: 'VerifyEmail',
        component: () => import('@/views/auth/EmailVerificationView.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/',
        component: () => import('@/components/layout/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            { path: '', redirect: '/dashboard' },
            {
                path: 'dashboard',
                name: 'Dashboard',
                component: () => import('@/views/dashboard/DashboardView.vue'),
            },
            {
                path: 'users',
                name: 'Users',
                component: () => import('@/views/admin/users/UsersListView.vue'),
                meta: { permission: 'users.view' },
            },
            {
                path: 'roles',
                name: 'Roles',
                component: () => import('@/views/admin/roles/RolesListView.vue'),
                meta: { permission: 'roles.view' },
            },
            {
                path: 'permissions',
                name: 'Permissions',
                component: () => import('@/views/admin/permissions/PermissionsListView.vue'),
                meta: { permission: 'permissions.view' },
            },
            {
                path: 'audit-logs',
                name: 'AuditLogs',
                component: () => import('@/views/admin/audit/AuditLogsView.vue'),
                meta: { permission: 'audit.view' },
            },
            {
                path: 'branches',
                name: 'Branches',
                component: () => import('@/views/admin/branches/BranchesListView.vue'),
                meta: { permission: 'branches.view' },
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: () => import('@/views/NotFoundView.vue'),
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

// CRITICAL FIX: Comprehensive navigation guard with error handling
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()

    if (import.meta.env.DEV) console.log(`[Router] Navigating to: ${to.path}`)

    try {
        // 1. Check if token exists but user data is missing (page refresh scenario)
        if (authStore.token && !authStore.user) {
            if (import.meta.env.DEV) console.log('[Router] Token exists but user missing, fetching user...')
            try {
                await authStore.fetchUser()
            } catch (error) {
                if (import.meta.env.DEV) console.error('[Router] Failed to fetch user:', error)
                authStore.clearAuth()
                if (to.path !== '/login') {
                    return next('/login')
                }
                return next()
            }
        }

        // ✅ F-12: Use to.matched.some() instead of to.meta directly for robustness
        const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
        const isGuestRoute = to.matched.some(record => record.meta.guest)
        const isAuthenticated = authStore.isAuthenticated

        // 2. Guest attempting to access protected route
        if (requiresAuth && !isAuthenticated) {
            if (import.meta.env.DEV) console.log('[Router] Auth required but not authenticated, redirecting to login')
            return next('/login')
        }

        // 3. Authenticated user attempting to access guest route (like Login)
        if (isGuestRoute && isAuthenticated) {
            if (import.meta.env.DEV) console.log('[Router] Already authenticated, redirecting to dashboard')
            return next('/dashboard')
        }

        // 4. Handle strict user states (Password change or Email Verification)
        // 4. Handle strict user states (Password change or Email Verification)
        if (isAuthenticated) {
            // Check for password expiry first
            if (authStore.requiresPasswordChange && to.path !== '/change-password') {
                if (import.meta.env.DEV) console.log('[Router] Password change required, redirecting')
                return next('/change-password')
            }

            // ✅ Allow /change-password if user has a token (even if flag is false)
            if (to.path === '/change-password' && !authStore.requiresPasswordChange) {
                // Don't redirect – let the user attempt to change password
                // (the backend will enforce expiry and return error if not needed)
                if (import.meta.env.DEV) console.log('[Router] Allowing change-password even without flag (user may have temporary token)')
                return next()
            }

            // Check for email verification
            if (authStore.requiresVerification && to.path !== '/verify-email') {
                if (import.meta.env.DEV) console.log('[Router] Email verification required, redirecting')
                return next('/verify-email')
            }
        }

        // 5. CRITICAL: Handle Permissions and Roles for protected routes
        // ✅ F-10: Ensure user is loaded before checking permissions
        if (isAuthenticated && authStore.user !== null) {
            // Check for permission meta on matched routes
            const permissionRequired = to.matched.find(record => record.meta.permission)?.meta.permission
            if (permissionRequired) {
                const hasPermission = authStore.hasPermission(permissionRequired)
                if (import.meta.env.DEV) console.log(`[Router] Permission check for ${permissionRequired}: ${hasPermission}`)
                if (!hasPermission) {
                    if (import.meta.env.DEV) console.warn(`[Router] Access denied: Missing permission ${permissionRequired}`)
                    return next('/dashboard')
                }
            }

            // Check for role meta on matched routes
            const roleRequired = to.matched.find(record => record.meta.role)?.meta.role
            if (roleRequired) {
                const hasRole = authStore.hasRole(roleRequired)
                if (import.meta.env.DEV) console.log(`[Router] Role check for ${roleRequired}: ${hasRole}`)
                if (!hasRole) {
                    if (import.meta.env.DEV) console.warn(`[Router] Access denied: Missing role ${roleRequired}`)
                    return next('/dashboard')
                }
            }
        }

        // 6. All checks passed, allow navigation
        return next()

    } catch (error) {
        if (import.meta.env.DEV) console.error('[Router] Navigation guard error:', error)
        if (to.path !== '/login') {
            return next('/login')
        }
        return next()
    }
})

// Global error handler for navigation errors
router.onError((error) => {
    if (import.meta.env.DEV) console.error('[Router] Navigation error:', error)

    if (error.message?.includes('Failed to fetch dynamically imported module')) {
        if (import.meta.env.DEV) console.error('[Router] Chunk load error, reloading...')
        window.location.reload()
    }
})

// After each navigation, scroll to top
router.afterEach(() => {
    window.scrollTo(0, 0)
})

export default router
