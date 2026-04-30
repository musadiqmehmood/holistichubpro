<template>
    <aside
        :class="[
            'bg-primary-900 text-white transition-all duration-300 flex flex-col',
            uiStore.sidebarOpen ? 'w-64' : 'w-20'
        ]"
    >
        <!-- Logo -->
        <div class="h-16 flex items-center justify-center border-b border-primary-700">
            <span v-if="uiStore.sidebarOpen" class="text-xl font-bold">HolisticHubPro</span>
            <span v-else class="text-xl font-bold">HHP</span>
        </div>

        <!-- Branch Selector (Super Admin Only) -->
        <BranchSelector v-if="authStore.isSuperAdmin && uiStore.sidebarOpen" class="p-4 border-b border-primary-700" />

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4">
            <ul class="space-y-1 px-2">
                <!-- Regular nav items -->
                <li v-for="item in visibleMenuItems" :key="item.name">
                    <router-link
                        :to="item.path"
                        :class="[
                            'flex items-center px-4 py-3 rounded-lg transition-colors',
                            route.path === item.path
                                ? 'bg-primary-700 text-white'
                                : 'text-primary-100 hover:bg-primary-800'
                        ]"
                    >
                        <component :is="item.icon" class="w-6 h-6 flex-shrink-0" />
                        <span v-if="uiStore.sidebarOpen" class="ml-3">{{ item.name }}</span>
                    </router-link>
                </li>

                <!-- ── Settings: expanded (sidebar open) ────────────────────── -->
                <li v-if="visibleSettingsItems.length > 0 && uiStore.sidebarOpen">
                    <!-- Group toggle -->
                    <button
                        type="button"
                        @click="settingsOpen = !settingsOpen"
                        :class="[
                            'flex w-full items-center justify-between px-4 py-3 rounded-lg transition-colors',
                            isSettingsActive
                                ? 'bg-primary-700 text-white'
                                : 'text-primary-100 hover:bg-primary-800'
                        ]"
                    >
                        <div class="flex items-center">
                            <Cog6ToothIcon class="w-6 h-6 flex-shrink-0" />
                            <span class="ml-3">Settings</span>
                        </div>
                        <ChevronDownIcon
                            :class="[
                                'w-4 h-4 transition-transform duration-200',
                                settingsOpen ? 'rotate-180' : ''
                            ]"
                        />
                    </button>

                    <!-- Sub-items -->
                    <div v-show="settingsOpen" class="mt-1 ml-3 space-y-0.5 border-l border-primary-700 pl-3">
                        <router-link
                            v-for="sub in visibleSettingsItems"
                            :key="sub.name"
                            :to="sub.path"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium transition-colors"
                            :class="
                                route.name === sub.routeName
                                    ? 'bg-primary-800 text-white'
                                    : 'text-primary-200 hover:bg-primary-800 hover:text-white'
                            "
                        >
                            <component v-if="sub.icon" :is="sub.icon" class="h-4 w-4 flex-shrink-0" />
                            {{ sub.name }}
                        </router-link>
                    </div>
                </li>

                <!-- ── Settings: collapsed (sidebar icon-only) ───────────────── -->
                <li v-if="visibleSettingsItems.length > 0 && !uiStore.sidebarOpen">
                    <router-link
                        to="/settings"
                        :class="[
                            'flex items-center justify-center px-4 py-3 rounded-lg transition-colors',
                            isSettingsActive
                                ? 'bg-primary-700 text-white'
                                : 'text-primary-100 hover:bg-primary-800'
                        ]"
                    >
                        <Cog6ToothIcon class="w-6 h-6" />
                    </router-link>
                </li>
            </ul>
        </nav>

        <!-- User Info -->
        <div class="p-4 border-t border-primary-700">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-primary-600 flex items-center justify-center flex-shrink-0">
                    <span class="text-lg font-semibold">{{ userInitials }}</span>
                </div>
                <div v-if="uiStore.sidebarOpen" class="ml-3 min-w-0">
                    <p class="text-sm font-medium truncate">{{ authStore.user?.name }}</p>
                    <p class="text-xs text-primary-300 truncate">{{ authStore.user?.email }}</p>
                    <p v-if="authStore.userBranch" class="text-xs text-primary-400 mt-1 truncate">
                        {{ authStore.userBranch.name || 'No Branch' }}
                    </p>
                </div>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
    HomeIcon,
    UsersIcon,
    ShieldCheckIcon,
    KeyIcon,
    ClipboardDocumentListIcon,
    BuildingOfficeIcon,
    Cog6ToothIcon,
    ChevronDownIcon,
    PaintBrushIcon,               // ✅ added
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import BranchSelector from '../ui/BranchSelector.vue'

defineOptions({ name: 'AppSidebar' })

const authStore = useAuthStore()
const uiStore   = useUiStore()
const route     = useRoute()

const settingsOpen = ref(false)

const isSettingsActive = computed(() => route.path.startsWith('/settings'))

watch(
    () => route.path,
    (path) => {
        if (path.startsWith('/settings')) settingsOpen.value = true
    },
    { immediate: true }
)

// ── Main nav items (unchanged) ─────────────────────────────────────────────
const menuItemsList = [
    { name: 'Dashboard', path: '/dashboard',  icon: HomeIcon,                  permission: null },
    { name: 'Users',     path: '/users',       icon: UsersIcon,                 permission: 'users.view' },
    { name: 'Roles',     path: '/roles',       icon: ShieldCheckIcon,           permission: 'roles.view' },
    { name: 'Permissions', path: '/permissions', icon: KeyIcon,                 permission: 'permissions.view' },
    { name: 'Audit Logs', path: '/audit-logs', icon: ClipboardDocumentListIcon, permission: 'audit.view' },
    { name: 'Branches',  path: '/branches',    icon: BuildingOfficeIcon,        permission: 'branches.view' },
]

const visibleMenuItems = computed(() =>
    menuItemsList.filter(item => !item.permission || authStore.hasPermission(item.permission))
)

// ── Settings sub-nav items (Global Design added) ───────────────────────────
const settingsItemsList = [
    {
        name: 'Global Design',
        path: '/settings/global-design',
        routeName: 'GlobalDesign',
        permission: null,            // everyone sees it
        icon: PaintBrushIcon,
    },
    { name: 'Store Settings',  path: '/settings/store',           routeName: 'StoreSettings',    permission: 'settings.view' },
    { name: 'Site Settings',   path: '/settings/site',            routeName: 'SiteSettings',     permission: 'settings.view' },
    { name: 'SMTP Settings',   path: '/settings/smtp',            routeName: 'SmtpSettings',     permission: 'settings.manage' },
    { name: 'Tax List',        path: '/settings/taxes',           routeName: 'TaxSettings',      permission: 'taxes.view' },
    { name: 'Units List',      path: '/settings/units',           routeName: 'UnitsSettings',    permission: 'units.view' },
    { name: 'Payment Types',   path: '/settings/payment-types',   routeName: 'PaymentTypes',     permission: 'payment_types.view' },
    { name: 'Currency List',   path: '/settings/currencies',      routeName: 'Currencies',       permission: 'currencies.view' },
    { name: 'Change Password', path: '/settings/change-password', routeName: 'SettingsPassword', permission: null },
    { name: 'Database Backup', path: '/settings/backup',          routeName: 'DatabaseBackup',   superAdminOnly: true },
]

const visibleSettingsItems = computed(() =>
    settingsItemsList.filter(item => {
        if (item.superAdminOnly) return authStore.isSuperAdmin
        if (!item.permission) return true
        return authStore.hasPermission(item.permission)
    })
)

// ── User initials ──────────────────────────────────────────────────────────
const userInitials = computed(() => {
    const name = authStore.user?.name || ''
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
})
</script>
