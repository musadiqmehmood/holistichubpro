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

            // ── Settings Module ─────────────────────────────────────────────
            {
                path: 'settings',
                component: () => import('@/views/admin/settings/SettingsView.vue'),
                meta: { requiresAuth: true },
                children: [
                    {
                        path: '',
                        redirect: { name: 'StoreSettings' },
                    },
                    {
                        path: 'store',
                        name: 'StoreSettings',
                        component: () => import('@/views/admin/settings/StoreSettingsView.vue'),
                        meta: { permission: 'settings.view' },
                    },
                    {
                        path: 'site',
                        name: 'SiteSettings',
                        component: () => import('@/views/admin/settings/SiteSettingsView.vue'),
                        meta: { permission: 'settings.view' },
                    },
                    {
                        path: 'smtp',
                        name: 'SmtpSettings',
                        component: () => import('@/views/admin/settings/SmtpSettingsView.vue'),
                        meta: { permission: 'settings.manage' },
                    },
                    {
                        path: 'taxes',
                        name: 'TaxSettings',
                        component: () => import('@/views/admin/settings/TaxListView.vue'),
                        meta: { permission: 'taxes.view' },
                    },
                    {
                        path: 'units',
                        name: 'UnitsSettings',
                        component: () => import('@/views/admin/settings/UnitsListView.vue'),
                        meta: { permission: 'units.view' },
                    },
                    {
                        path: 'payment-types',
                        name: 'PaymentTypes',
                        component: () => import('@/views/admin/settings/PaymentTypesView.vue'),
                        meta: { permission: 'payment_types.view' },
                    },
                    {
                        path: 'currencies',
                        name: 'Currencies',
                        component: () => import('@/views/admin/settings/CurrenciesView.vue'),
                        meta: { permission: 'currencies.view' },
                    },
                    {
                        path: 'change-password',
                        name: 'SettingsPassword',
                        component: () => import('@/views/auth/ChangePasswordView.vue'),
                    },
                    {
                        path: 'backup',
                        name: 'DatabaseBackup',
                        component: () => import('@/views/admin/settings/DatabaseBackupView.vue'),
                        meta: { superAdminOnly: true },
                    },
                ],
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
                if (to.path !== '/login') return next('/login')
                return next()
            }
        }

        const requiresAuth    = to.matched.some(record => record.meta.requiresAuth)
        const isGuestRoute    = to.matched.some(record => record.meta.guest)
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

        // 4. Handle strict user states (password change or email verification)
        if (isAuthenticated) {
            if (authStore.requiresPasswordChange && to.path !== '/change-password') {
                if (import.meta.env.DEV) console.log('[Router] Password change required, redirecting')
                return next('/change-password')
            }

            if (to.path === '/change-password' && !authStore.requiresPasswordChange) {
                if (import.meta.env.DEV) console.log('[Router] Allowing change-password without flag')
                return next()
            }

            if (authStore.requiresVerification && to.path !== '/verify-email') {
                if (import.meta.env.DEV) console.log('[Router] Email verification required, redirecting')
                return next('/verify-email')
            }
        }

        // 5. Permission and role checks
        if (isAuthenticated && authStore.user !== null) {
            // Super-admin-only routes
            const superAdminOnly = to.matched.some(record => record.meta.superAdminOnly)
            if (superAdminOnly && !authStore.isSuperAdmin) {
                if (import.meta.env.DEV) console.warn('[Router] Access denied: Super Admin only')
                return next('/dashboard')
            }

            // Permission-gated routes (super-admin bypasses all permission checks)
            if (!authStore.isSuperAdmin) {
                const permissionRequired = to.matched.find(record => record.meta.permission)?.meta.permission
                if (permissionRequired) {
                    const hasPermission = authStore.hasPermission(permissionRequired)
                    if (import.meta.env.DEV) console.log('[Router] Permission check for ' + permissionRequired + ': ' + hasPermission)
                    if (!hasPermission) {
                        if (import.meta.env.DEV) console.warn('[Router] Access denied: Missing permission ' + permissionRequired)
                        return next('/dashboard')
                    }
                }
            }

            // Role-gated routes
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

        // 6. All checks passed
        return next()

    } catch (error) {
        if (import.meta.env.DEV) console.error('[Router] Navigation guard error:', error)
        if (to.path !== '/login') return next('/login')
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
