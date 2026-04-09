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

        <!-- ✅ F-28: Removed debug div with console logs -->

        <!-- Branch Selector (Super Admin Only) -->
        <BranchSelector v-if="authStore.isSuperAdmin && uiStore.sidebarOpen" class="p-4 border-b border-primary-700" />

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4">
            <ul class="space-y-1 px-2">
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
                        <component :is="item.icon" class="w-6 h-6" />
                        <span v-if="uiStore.sidebarOpen" class="ml-3">{{ item.name }}</span>
                    </router-link>
                </li>
            </ul>
        </nav>

        <!-- User Info -->
        <div class="p-4 border-t border-primary-700">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-primary-600 flex items-center justify-center">
                    <span class="text-lg font-semibold">{{ userInitials }}</span>
                </div>
                <div v-if="uiStore.sidebarOpen" class="ml-3">
                    <p class="text-sm font-medium">{{ authStore.user?.name }}</p>
                    <p class="text-xs text-primary-300">{{ authStore.user?.email }}</p>
                    <p v-if="authStore.userBranch" class="text-xs text-primary-400 mt-1">
                        {{ authStore.userBranch.name || 'No Branch' }}
                    </p>
                </div>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import {
    HomeIcon,
    UsersIcon,
    ShieldCheckIcon,
    KeyIcon,
    ClipboardDocumentListIcon,
    BuildingOfficeIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import BranchSelector from '../ui/BranchSelector.vue'

defineOptions({
    name: 'AppSidebar'
})

const authStore = useAuthStore()
const uiStore = useUiStore()
const route = useRoute()

// ✅ F-28: Removed console logs and debug watch blocks

const userPermissionsList = computed(() => {
    return authStore.userPermissions || []
})

const menuItemsList = [
    { name: 'Dashboard', path: '/dashboard', icon: HomeIcon, permission: null },
    { name: 'Users', path: '/users', icon: UsersIcon, permission: 'users.view' },
    { name: 'Roles', path: '/roles', icon: ShieldCheckIcon, permission: 'roles.view' },
    { name: 'Permissions', path: '/permissions', icon: KeyIcon, permission: 'permissions.view' },
    { name: 'Audit Logs', path: '/audit-logs', icon: ClipboardDocumentListIcon, permission: 'audit.view' },
    { name: 'Branches', path: '/branches', icon: BuildingOfficeIcon, permission: 'branches.view' },
]

const visibleMenuItems = computed(() => {
    return menuItemsList.filter(item => {
        if (!item.permission) return true
        return authStore.hasPermission(item.permission)
    })
})

const userInitials = computed(() => {
    const name = authStore.user?.name || ''
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
})
</script>
