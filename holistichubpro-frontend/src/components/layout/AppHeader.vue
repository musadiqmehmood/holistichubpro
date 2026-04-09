<template>
    <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6">
        <button
            @click="uiStore.toggleSidebar"
            class="p-2 rounded-lg hover:bg-gray-100"
        >
            <Bars3Icon class="w-6 h-6 text-gray-600" />
        </button>

        <div class="flex items-center space-x-4">
            <!-- Notifications -->
            <button class="p-2 rounded-lg hover:bg-gray-100 relative">
                <BellIcon class="w-6 h-6 text-gray-600" />
                <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
            </button>

            <!-- User Dropdown -->
            <div ref="dropdownRef" class="relative">
                <button
                    @click="toggleDropdown"
                    class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100"
                >
                    <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-sm font-semibold">
                        {{ userInitials }}
                    </div>
                    <span class="text-sm font-medium text-gray-700">{{ authStore.user?.name }}</span>
                    <ChevronDownIcon class="w-4 h-4 text-gray-500" />
                </button>

                <!-- Dropdown -->
                <div
                    v-if="showDropdown"
                    class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                >
                    <!-- ✅ F-26: Removed broken /profile link -->
                    <button
                        @click="goToChangePassword"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                        Change Password
                    </button>
                    <div class="border-t border-gray-100 my-1"></div>
                    <button
                        @click="handleLogout"
                        class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                    >
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { Bars3Icon, BellIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'

const authStore = useAuthStore()
const uiStore = useUiStore()
const router = useRouter()
const showDropdown = ref(false)
const dropdownRef = ref(null)

const userInitials = computed(() => {
    const name = authStore.user?.name || ''
    return name.split(' ').map(n => n[0]).join('').toUpperCase()
})

const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value
}

const goToChangePassword = () => {
    showDropdown.value = false
    router.push('/change-password')
}

const handleLogout = async () => {
    showDropdown.value = false
    await authStore.logout()
    router.push('/login')
}

// ✅ F-25: Click outside handler
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showDropdown.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>
