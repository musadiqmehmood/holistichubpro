<template>
    <div class="min-h-screen bg-neutral-10 flex">
        <aside
            :class="[
                'fixed top-0 left-0 h-full bg-white border-r border-neutral-20 z-40 transition-transform duration-300 ease-in-out flex flex-col',
                uiStore.sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                'w-64'
            ]"
        >
            <div class="flex items-center justify-center h-16 border-b border-neutral-20">
                <div class="flex items-center gap-2 text-primary-600 font-bold text-xl tracking-tight">
                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    HolisticHub Pro
                </div>
            </div>

            <div class="p-4 border-b border-neutral-10">
                <BranchSelector v-if="authStore.isSuperAdmin" />
                <div v-else class="text-sm font-medium text-neutral-70 px-2">
                    {{ authStore.userBranch?.name || 'Assigned Branch' }}
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                <router-link
                    v-for="item in menuItems"
                    :key="item.path"
                    :to="item.path"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all"
                    :class="[
                        isActiveRoute(item.path)
                            ? 'bg-primary-50 text-primary-700 shadow-sm border border-primary-100'
                            : 'text-neutral-60 hover:bg-neutral-5 hover:text-neutral-90'
                    ]"
                >
                    <component :is="item.icon" class="w-5 h-5" :class="isActiveRoute(item.path) ? 'text-primary-600' : 'text-neutral-40'" />
                    {{ item.name }}
                </router-link>
            </nav>
        </aside>

        <div class="flex-1 flex flex-col min-w-0 lg:pl-64 transition-all duration-300">
            <!-- Header (unchanged) -->
            <header class="h-16 bg-white border-b border-neutral-20 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-30">
                <div class="flex items-center">
                    <button @click="uiStore.toggleSidebar" class="p-2 rounded-md hover:bg-neutral-10 lg:hidden text-neutral-60">
                        <Bars3Icon class="w-6 h-6" />
                    </button>
                </div>

                <div class="flex items-center gap-4">
                    <div v-if="passwordExpiryWarning" class="hidden md:flex items-center gap-1.5 px-3 py-1 bg-warning/10 text-warning border border-warning/20 rounded-full text-xs font-medium">
                        <ExclamationTriangleIcon class="w-4 h-4" />
                        Pwd expires in {{ passwordExpiryWarning }} days
                    </div>

                    <Menu as="div" class="relative">
                        <MenuButton class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-neutral-5 border border-transparent hover:border-neutral-20 transition-all focus:outline-none">
                            <AppAvatar :name="authStore.user?.name" size="sm" bgColor="var(--color-primary-600)" />
                            <div class="hidden sm:block text-left">
                                <p class="text-sm font-semibold text-neutral-90 leading-none">{{ authStore.user?.name }}</p>
                                <p class="text-xs text-neutral-50">{{ authStore.user?.roles?.[0]?.name || 'User' }}</p>
                            </div>
                            <ChevronDownIcon class="w-4 h-4 text-neutral-40" />
                        </MenuButton>
                        <transition
                            enter-active-class="transition duration-100 ease-out"
                            enter-from-class="transform scale-95 opacity-0"
                            enter-to-class="transform scale-100 opacity-100"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="transform scale-100 opacity-100"
                            leave-to-class="transform scale-95 opacity-0"
                        >
                            <MenuItems class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-elevation-2 border border-neutral-20 py-1 focus:outline-none z-50">
                                <MenuItem v-slot="{ active }">
                                    <router-link to="/change-password" :class="[active ? 'bg-neutral-5' : '', 'block px-4 py-2 text-sm text-neutral-70']">
                                        Change Password
                                    </router-link>
                                </MenuItem>
                                <div class="border-t border-neutral-10 my-1" />
                                <MenuItem v-slot="{ active }">
                                    <button @click="handleLogout" :class="[active ? 'bg-error/5' : '', 'block w-full text-left px-4 py-2 text-sm text-error font-medium']">
                                        Log Out
                                    </button>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-x-hidden">
                <router-view />
            </main>
        </div>

        <div
            v-if="uiStore.sidebarOpen"
            class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm z-30 lg:hidden"
            @click="uiStore.toggleSidebar"
        />
    </div>
</template>

<script setup>
import { computed, shallowRef } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { HomeIcon, UsersIcon, ShieldCheckIcon, KeyIcon, ClipboardDocumentListIcon, BuildingOfficeIcon, Bars3Icon, ChevronDownIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import BranchSelector from '@/components/ui/BranchSelector.vue'
import AppAvatar from '@/components/ui/AppAvatar.vue'

defineOptions({ name: 'AppLayout' })

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const uiStore = useUiStore()

// ✅ F-27: Helper to check if route is active (supports nested routes)
const isActiveRoute = (path) => {
    // Exact match for dashboard or root
    if (path === '/dashboard') {
        return route.path === '/dashboard'
    }
    // For other paths, check if current route starts with the path
    // but avoid matching '/users' when route is '/users-edit' etc. – ensure trailing slash or exact boundary
    if (route.path === path) return true
    if (route.path.startsWith(path + '/')) return true
    return false
}

const passwordExpiryWarning = computed(() => {
    if (!authStore.user?.password_changed_at) return null
    const changed = new Date(authStore.user.password_changed_at)
    const expiry = new Date(changed)
    expiry.setDate(expiry.getDate() + 90)
    const diff = Math.ceil((expiry - new Date()) / (1000 * 60 * 60 * 24))
    return diff > 0 && diff <= 7 ? diff : null
})

const allMenuItems = shallowRef([
    { path: '/dashboard', name: 'Dashboard', icon: HomeIcon, permission: null },
    { path: '/users', name: 'Users', icon: UsersIcon, permission: 'users.view' },
    { path: '/roles', name: 'Roles', icon: ShieldCheckIcon, permission: 'roles.view' },
    { path: '/permissions', name: 'Permissions', icon: KeyIcon, permission: 'permissions.view' },
    { path: '/audit-logs', name: 'Audit Logs', icon: ClipboardDocumentListIcon, permission: 'audit.view' },
    { path: '/branches', name: 'Branches', icon: BuildingOfficeIcon, permission: 'branches.view' },
])

const menuItems = computed(() => {
    return allMenuItems.value.filter(item => {
        if (!item.permission) return true
        return authStore.hasPermission(item.permission)
    })
})

const handleLogout = async () => {
    await authStore.logout()
    router.push('/login')
}
</script>
