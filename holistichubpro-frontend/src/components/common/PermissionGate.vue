<!-- src/components/common/PermissionGate.vue -->
<template>
    <slot v-if="hasAccess" />
    <slot v-else name="fallback">
        <div class="p-8 text-center bg-gray-50 rounded-lg border border-gray-200">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Access Denied</h3>
            <p class="mt-1 text-sm text-gray-500">You don't have permission to view this content.</p>
        </div>
    </slot>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
    permission: {
        type: String,
        default: null,
    },
    role: {
        type: String,
        default: null,
    },
    anyPermission: {
        type: Array,
        default: () => []
    }
})

const authStore = useAuthStore()

const hasAccess = computed(() => {
    // Super admin bypass
    if (authStore.isSuperAdmin) return true

    // ✅ F-44: If no access criteria are provided, default to DENY access
    const hasPermissionCriteria = props.permission || props.role || (props.anyPermission && props.anyPermission.length > 0)
    if (!hasPermissionCriteria) {
        return false
    }

    // Check single permission
    if (props.permission && !authStore.hasPermission(props.permission)) {
        return false
    }

    // Check single role
    if (props.role && !authStore.hasRole(props.role)) {
        return false
    }

    // Check any of the permissions in the array
    if (props.anyPermission.length > 0) {
        const hasAny = props.anyPermission.some(p => authStore.hasPermission(p))
        if (!hasAny) return false
    }

    return true
})
</script>
