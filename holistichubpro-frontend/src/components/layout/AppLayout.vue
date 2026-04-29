<template>
    <div class="min-h-screen bg-[var(--background-color)] flex">
        <!-- ========== SIDEBAR ========== -->
        <aside
            :class="[
        'fixed top-0 left-0 h-full bg-white border-r border-gray-200 z-40 transition-all duration-300 flex flex-col',
        sidebarCollapsed ? 'w-16' : 'w-64',
        // mobile behaviour unchanged
        uiStore.sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
      ]"
        >
            <!-- TOP: Logo & Title (stacked) -->
            <div class="flex flex-col items-center justify-center px-3 py-5 border-b border-gray-100">
                <div v-if="settingsStore.siteSettings?.site_logo_url" class="mb-2">
                    <img
                        :src="settingsStore.siteSettings.site_logo_url"
                        class="h-8 w-auto"
                        alt="Logo"
                    />
                </div>
                <transition name="fade">
          <span
              v-if="!sidebarCollapsed"
              class="text-sm font-bold text-[var(--text-primary)] text-center leading-tight"
          >
            {{ settingsStore.siteSettings?.site_name || 'HolisticHubPro' }}
          </span>
                </transition>
            </div>

            <!-- NAVIGATION (sorted alphabetically) -->
            <nav class="flex-1 overflow-y-auto py-2 px-2 space-y-1">
                <router-link
                    v-for="item in sortedMenuItems"
                    :key="item.path"
                    :to="item.path"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="[
            isActiveRoute(item.path)
              ? 'bg-[var(--primary-color)]/10 text-[var(--primary-color)] font-semibold'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800',
            sidebarCollapsed ? 'justify-center px-2' : ''
          ]"
                    :title="sidebarCollapsed ? item.name : ''"
                >
                    <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
                    <span v-if="!sidebarCollapsed">{{ item.name }}</span>
                </router-link>

                <!-- SETTINGS COLLAPSIBLE -->
                <div v-if="visibleSettingsItems.length">
                    <button
                        v-if="!sidebarCollapsed"
                        @click="settingsOpen = !settingsOpen"
                        class="flex w-full items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                        :class="[
              isSettingsActive
                ? 'bg-[var(--primary-color)]/10 text-[var(--primary-color)] font-semibold'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
            ]"
                    >
                        <Cog6ToothIcon class="w-5 h-5 flex-shrink-0" />
                        <span class="flex-1 text-left">Settings</span>
                        <ChevronDownIcon
                            class="w-4 h-4 transition-transform duration-200"
                            :class="[settingsOpen ? 'rotate-180' : '', isSettingsActive ? 'text-[var(--primary-color)]' : 'text-gray-400']"
                        />
                    </button>

                    <!-- when collapsed: just a link to /settings -->
                    <router-link
                        v-else
                        to="/settings"
                        class="flex items-center justify-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                        :class="[
              isSettingsActive
                ? 'bg-[var(--primary-color)]/10 text-[var(--primary-color)]'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-800'
            ]"
                        title="Settings"
                    >
                        <Cog6ToothIcon class="w-5 h-5 flex-shrink-0" />
                    </router-link>

                    <!-- Sub‑items (only when expanded AND settings open) -->
                    <div v-if="!sidebarCollapsed && settingsOpen" class="mt-1 ml-4 space-y-0.5 border-l border-gray-100 pl-3">
                        <router-link
                            v-for="sub in sortedSettingsItems"
                            :key="sub.routeName"
                            :to="sub.path"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium transition-colors"
                            :class="[
                route.name === sub.routeName
                  ? 'bg-[var(--primary-color)]/10 text-[var(--primary-color)]'
                  : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'
              ]"
                        >
                            <component :is="sub.icon" class="w-4 h-4 flex-shrink-0 opacity-70" />
                            {{ sub.name }}
                        </router-link>
                    </div>
                </div>
            </nav>

            <!-- BOTTOM: User info + Collapse toggle -->
            <div class="border-t border-gray-100 p-3 flex items-center justify-between">
                <div v-if="!sidebarCollapsed" class="flex items-center gap-3 min-w-0 flex-1">
                    <AppAvatar :name="authStore.user?.name" size="sm" bgColor="var(--primary-color)" />
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">{{ authStore.user?.name }}</p>
                        <p class="text-xs text-gray-400 truncate">
                            {{ formatLastLogin(authStore.user?.last_login_at) }}
                        </p>
                        <p v-if="authStore.isSuperAdmin" class="text-[10px] text-[var(--primary-color)] font-semibold uppercase">
                            Super Admin
                        </p>
                    </div>
                </div>

                <!-- Collapse toggle button -->
                <button
                    @click="sidebarCollapsed = !sidebarCollapsed"
                    class="p-1.5 rounded-md hover:bg-gray-100 text-gray-500 transition-colors"
                    :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                >
                    <svg v-if="!sidebarCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </aside>

        <!-- ========== MAIN CONTENT ========== -->
        <div
            :class="[
        'flex-1 flex flex-col min-w-0 transition-all duration-300',
        sidebarCollapsed ? 'lg:pl-16' : 'lg:pl-64'
      ]"
        >
            <!-- Header -->
            <header class="h-16 bg-white border-b border-gray-100 flex items-center justify-between px-4 sm:px-6 shrink-0">
                <button @click="uiStore.toggleSidebar" class="p-2 rounded-lg hover:bg-gray-100 lg:hidden">
                    <Bars3Icon class="w-6 h-6 text-gray-600" />
                </button>

                <div class="flex items-center gap-4 ml-auto">
                    <Menu as="div" class="relative">
                        <MenuButton class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-50 transition-colors">
                            <AppAvatar :name="authStore.user?.name" size="sm" bgColor="var(--primary-color)" />
                            <ChevronDownIcon class="w-4 h-4 text-gray-400" />
                        </MenuButton>
                        <transition
                            enter-active-class="transition duration-100 ease-out"
                            enter-from-class="transform scale-95 opacity-0"
                            enter-to-class="transform scale-100 opacity-100"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="transform scale-100 opacity-100"
                            leave-to-class="transform scale-95 opacity-0"
                        >
                            <MenuItems class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-elevation-2 border border-gray-200 py-1 z-50">
                                <MenuItem v-slot="{ active }">
                                    <router-link
                                        to="/settings/change-password"
                                        :class="[active ? 'bg-gray-50' : '', 'block px-4 py-2 text-sm text-gray-700']"
                                    >
                                        Change Password
                                    </router-link>
                                </MenuItem>
                                <div class="border-t border-gray-100 my-1" />
                                <MenuItem v-slot="{ active }">
                                    <button
                                        @click="handleLogout"
                                        :class="[active ? 'bg-red-50' : '', 'block w-full text-left px-4 py-2 text-sm text-red-600 font-medium']"
                                    >
                                        Log Out
                                    </button>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden">
                <router-view />
            </main>
        </div>

        <!-- Mobile overlay -->
        <div
            v-if="uiStore.sidebarOpen"
            class="fixed inset-0 bg-black/30 z-30 lg:hidden"
            @click="uiStore.toggleSidebar"
        />
    </div>
</template>

<script setup>
import { ref, computed, shallowRef, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import {
    HomeIcon,
    UsersIcon,
    ShieldCheckIcon,
    KeyIcon,
    ClipboardDocumentListIcon,
    BuildingOfficeIcon,
    Cog6ToothIcon,
    ChevronDownIcon,
    Bars3Icon,
    // Settings sub‑menu icons
    BuildingStorefrontIcon,
    CameraIcon,
    EnvelopeIcon,
    ReceiptPercentIcon,
    ScaleIcon,
    CreditCardIcon,
    CurrencyDollarIcon,
    KeyIcon as PwdIcon,
    CircleStackIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useSettingsStore } from '@/stores/settings'
import AppAvatar from '@/components/ui/AppAvatar.vue'

defineOptions({ name: 'AppLayout' })

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const uiStore = useUiStore()
const settingsStore = useSettingsStore()

const settingsOpen = ref(false)
const sidebarCollapsed = ref(false)   // ✅ new collapse state

const isActiveRoute = (path) => {
    if (path === '/dashboard') return route.path === '/dashboard'
    return route.path.startsWith(path)
}
const isSettingsActive = computed(() => route.path.startsWith('/settings'))

// Last login formatter
const formatLastLogin = (date) => {
    if (!date) return 'Never logged in'
    const d = new Date(date)
    const now = new Date()
    const diffMs = now - d
    const diffMins = Math.floor(diffMs / 60000)
    if (diffMins < 1) return 'Just now'
    if (diffMins < 60) return `${diffMins} min ago`
    const diffHours = Math.floor(diffMins / 60)
    if (diffHours < 24) return `${diffHours}h ago`
    const diffDays = Math.floor(diffHours / 24)
    return `${diffDays}d ago`
}

onMounted(() => settingsStore.fetchDynamicSettings())

// ── Menu items (always icons, sorted) ──
const allMenuItems = shallowRef([
    { path: '/dashboard',  name: 'Dashboard',   icon: HomeIcon,                  permission: null },
    { path: '/users',      name: 'Users',        icon: UsersIcon,                 permission: 'users.view' },
    { path: '/roles',      name: 'Roles',        icon: ShieldCheckIcon,           permission: 'roles.view' },
    { path: '/permissions', name: 'Permissions', icon: KeyIcon,                   permission: 'permissions.view' },
    { path: '/audit-logs', name: 'Audit Logs',   icon: ClipboardDocumentListIcon, permission: 'audit.view' },
    { path: '/branches',   name: 'Branches',     icon: BuildingOfficeIcon,        permission: 'branches.view' },
])

const sortedMenuItems = computed(() =>
    allMenuItems.value
        .filter(item => !item.permission || authStore.hasPermission(item.permission))
        .sort((a, b) => a.name.localeCompare(b.name))
)

// ── Settings sub‑items (with icons, sorted) ──
const settingsItemsList = [
    { name: 'Change Password', path: '/settings/change-password', routeName: 'SettingsPassword', permission: null, icon: PwdIcon },
    { name: 'Currency List',   path: '/settings/currencies',      routeName: 'Currencies',       permission: 'currencies.view', icon: CurrencyDollarIcon },
    { name: 'Database Backup', path: '/settings/backup',          routeName: 'DatabaseBackup',   superAdminOnly: true, icon: CircleStackIcon },
    { name: 'Payment Types',   path: '/settings/payment-types',   routeName: 'PaymentTypes',     permission: 'payment_types.view', icon: CreditCardIcon },
    { name: 'Site Settings',   path: '/settings/site',            routeName: 'SiteSettings',     permission: 'settings.view', icon: CameraIcon },
    { name: 'SMTP Settings',   path: '/settings/smtp',            routeName: 'SmtpSettings',     permission: 'settings.manage', icon: EnvelopeIcon },
    { name: 'Store Settings',  path: '/settings/store',           routeName: 'StoreSettings',    permission: 'settings.view', icon: BuildingStorefrontIcon },
    { name: 'Tax List',        path: '/settings/taxes',           routeName: 'TaxSettings',      permission: 'taxes.view', icon: ReceiptPercentIcon },
    { name: 'Units List',      path: '/settings/units',           routeName: 'UnitsSettings',    permission: 'units.view', icon: ScaleIcon },
]

const visibleSettingsItems = computed(() =>
    settingsItemsList
        .filter(item => {
            if (item.superAdminOnly) return authStore.isSuperAdmin
            if (!item.permission) return true
            return authStore.hasPermission(item.permission)
        })
        .sort((a, b) => a.name.localeCompare(b.name))
)

const sortedSettingsItems = computed(() => visibleSettingsItems.value)

const handleLogout = async () => {
    await authStore.logout()
    router.push('/login')
}
</script>

<style scoped>
/* fade transition for store name */
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
