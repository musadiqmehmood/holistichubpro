<template>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Roles Management</h1>
                <p class="text-sm text-gray-500 mt-1">Manage system roles and their assigned permissions</p>
            </div>
            <AppButton v-if="authStore.hasPermission('roles.create')" @click="openCreateModal" variant="filled" color="primary">
                <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Role
            </AppButton>
        </div>

        <div v-if="loading" class="flex justify-center p-12">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-100 border-t-blue-600"></div>
        </div>

        <AppCard v-else-if="error" class="p-12 text-center">
            <p class="text-red-600 mb-4">{{ error }}</p>
            <AppButton @click="fetchRoles" variant="filled" color="primary">Retry</AppButton>
        </AppCard>

        <div v-else class="space-y-8">
            <!-- Super Admin Role Card (system protected) -->
            <AppCard v-if="superAdminRole" padding="none" class="w-full overflow-hidden border-indigo-200 shadow-md ring-1 ring-indigo-50">
                <div class="p-6 sm:p-8 flex flex-col md:flex-row gap-6 items-start md:items-center bg-gradient-to-r from-indigo-50/50 to-white">
                    <div class="flex items-center gap-5 md:w-2/5 shrink-0">
                        <div
                            class="w-16 h-16 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-md"
                            :style="{ background: getRoleColor(superAdminRole.name) }"
                        >
                            {{ getInitials(superAdminRole.name) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="font-bold text-xl text-gray-900">{{ formatRoleTitle(superAdminRole.name) }}</h3>
                                <span class="px-2.5 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] uppercase font-bold tracking-wider rounded border border-indigo-200">
                                    System Protected
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 font-medium">Unrestricted access to all features</p>
                        </div>
                    </div>

                    <div class="flex-1 w-full border-t md:border-t-0 md:border-l border-indigo-100 pt-5 md:pt-0 md:pl-8">
                        <p class="text-sm font-bold text-gray-900 mb-3">Active Permissions</p>
                        <div class="flex flex-wrap gap-2">
                            <AppBadge
                                text="✓ All Permissions Auto-Assigned (Bypasses Checks)"
                                color="success"
                                class="px-3.5 py-1.5 shadow-sm text-sm"
                            />
                        </div>
                        <p class="text-xs text-gray-500 mt-3 italic">
                            * This role cannot be edited or deleted.
                        </p>
                    </div>
                </div>
            </AppCard>

            <!-- Custom Roles Grid -->
            <div>
                <h2 class="text-lg font-bold text-gray-900 mb-4">Custom Roles</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-stretch">
                    <AppCard v-for="role in regularRoles" :key="role.id" padding="none" class="flex flex-col h-full overflow-hidden">
                        <div class="p-6 flex flex-col h-full">
                            <div class="flex items-center gap-4 mb-5">
                                <div
                                    class="w-14 h-14 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-sm"
                                    :style="{ background: getRoleColor(role.name) }"
                                >
                                    {{ getInitials(role.name) }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-lg text-gray-900">{{ formatRoleTitle(role.name) }}</h3>
                                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold mt-0.5">{{ role.guard_name }}</p>
                                </div>
                            </div>

                            <div class="mb-6 flex-1">
                                <p class="text-sm font-semibold text-gray-700 mb-3 border-b border-gray-100 pb-2">
                                    Permissions ({{ role.permissions?.length || 0 }})
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <AppBadge
                                        v-for="perm in role.permissions"
                                        :key="perm.id"
                                        :text="formatPermissionName(perm.name)"
                                        color="success"
                                        class="px-2.5 py-1"
                                    />
                                    <span v-if="!role.permissions || role.permissions.length === 0" class="text-sm text-gray-400 italic mt-1">
                                        No permissions assigned
                                    </span>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 mt-auto">
                                <AppButton
                                    v-if="role.name !== 'super-admin' && authStore.hasPermission('roles.edit')"
                                    size="small"
                                    variant="tonal"
                                    color="primary"
                                    @click="openEditModal(role)"
                                >
                                    Edit Role
                                </AppButton>
                                <AppButton
                                    v-if="role.name !== 'super-admin' && authStore.hasPermission('roles.delete')"
                                    size="small"
                                    variant="text"
                                    color="error"
                                    @click="confirmDelete(role)"
                                >
                                    Delete
                                </AppButton>
                            </div>
                        </div>
                    </AppCard>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <AppModal
            :is-open="showModal"
            :title="editingRole ? 'Edit Role' : 'Create Role'"
            :show-confirm="false"
            @close="closeModal"
        >
            <form @submit.prevent="saveRole" class="space-y-5">
                <AppFormField label="Role Name" required :error="errors.name">
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="e.g., manager"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none"
                    />
                </AppFormField>

                <!-- Guard Name – preserved when editing, optional when creating -->
                <AppFormField label="Guard Name" :error="errors.guard_name">
                    <select v-model="form.guard_name" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                        <option value="web">web</option>
                        <option value="api">api</option>
                        <option value="sanctum">sanctum</option>
                    </select>
                </AppFormField>

                <AppFormField label="Assign Permissions">
                    <div class="border border-gray-200 rounded-xl p-4 max-h-[50vh] overflow-y-auto bg-gray-50">
                        <div v-for="(perms, resource) in groupedPermissions" :key="resource" class="mb-5 last:mb-0 bg-white p-4 rounded-lg border border-gray-100 shadow-sm">
                            <h4 class="text-sm font-bold text-gray-900 capitalize mb-3 border-b border-gray-100 pb-2">
                                {{ formatResourceName(resource) }} Management
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label
                                    v-for="perm in perms"
                                    :key="perm.id"
                                    class="flex items-center gap-3 p-2 rounded-md hover:bg-gray-50 cursor-pointer transition-colors border border-transparent hover:border-gray-200"
                                >
                                    <input
                                        type="checkbox"
                                        :value="perm.name"
                                        v-model="form.permissions"
                                        class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                    />
                                    <span class="text-sm text-gray-700 font-medium select-none">
                                        {{ perm.action }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </AppFormField>

                <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-gray-100">
                    <AppButton type="button" @click="closeModal" variant="outlined" color="neutral">Cancel</AppButton>
                    <AppButton type="submit" variant="filled" color="primary" :loading="saving">
                        {{ editingRole ? 'Update Role' : 'Create Role' }}
                    </AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Delete Confirmation Modal -->
        <AppModal
            :is-open="showDeleteModal"
            title="Confirm Delete"
            :show-confirm="false"
            @close="showDeleteModal = false"
        >
            <p class="text-gray-700">Are you sure you want to delete the role <strong class="text-gray-900 font-bold bg-gray-100 px-1 rounded">{{ roleToDelete?.name }}</strong>?</p>
            <p class="text-sm text-red-600 mt-2 font-medium">This action cannot be undone. Users with this role may lose access.</p>

            <div class="flex justify-end gap-3 pt-4 mt-4">
                <AppButton type="button" @click="showDeleteModal = false" variant="tonal" color="neutral">Cancel</AppButton>
                <AppButton type="button" @click="deleteRole" variant="filled" color="error" :loading="deleting">
                    Delete Role
                </AppButton>
            </div>
        </AppModal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { rolesApi } from '@/api/roles'
import { permissionsApi } from '@/api/permissions'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppFormField from '@/components/ui/AppFormField.vue'

const authStore = useAuthStore()

const loading = ref(false)
const error = ref(null)
const roles = ref([])
const allPermissions = ref([])
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingRole = ref(null)
const roleToDelete = ref(null)
const saving = ref(false)
const deleting = ref(false)
const errors = ref({})

// Form state – guard_name is preserved from the role when editing
const form = reactive({
    name: '',
    guard_name: 'web',
    permissions: []
})

// Split roles
const superAdminRole = computed(() => {
    return roles.value.find(r => r.name === 'super-admin')
})

const regularRoles = computed(() => {
    return roles.value.filter(r => r.name !== 'super-admin')
})

// Group permissions by resource
const groupedPermissions = computed(() => {
    const grouped = {}
    allPermissions.value.forEach(perm => {
        const [resource, action] = perm.name.split('.')
        if (!grouped[resource]) grouped[resource] = []
        const formattedAction = action ? action.charAt(0).toUpperCase() + action.slice(1) : perm.name
        grouped[resource].push({ ...perm, action: formattedAction })
    })
    return grouped
})

// Fetch roles
const fetchRoles = async () => {
    loading.value = true
    error.value = null
    try {
        const response = await rolesApi.getAll()
        const data = response.data
        if (Array.isArray(data)) {
            roles.value = data
        } else if (data && Array.isArray(data.data)) {
            roles.value = data.data
        } else if (data) {
            roles.value = Object.values(data).flat()
        } else {
            roles.value = []
        }
    } catch (err) {
        error.value = err.response?.data?.message || err.message
        if (err.response?.status === 401) authStore.logout()
    } finally {
        loading.value = false
    }
}

// Fetch permissions
const fetchPermissions = async () => {
    try {
        const response = await permissionsApi.getAll()
        const data = response.data
        if (Array.isArray(data)) {
            allPermissions.value = data
        } else if (data && Array.isArray(data.data)) {
            allPermissions.value = data.data
        } else if (data && data.flat && Array.isArray(data.flat)) {
            allPermissions.value = data.flat
        } else if (data) {
            allPermissions.value = Object.values(data).flat()
        } else {
            allPermissions.value = []
        }
    } catch (err) {
        console.error('Failed to fetch permissions:', err)
        allPermissions.value = []
    }
}

const openCreateModal = () => {
    editingRole.value = null
    form.name = ''
    form.guard_name = 'web'   // default for new roles
    form.permissions = []
    errors.value = {}
    showModal.value = true
}

const openEditModal = (role) => {
    editingRole.value = role
    form.name = role.name
    // ✅ CRITICAL: Preserve the existing guard_name
    form.guard_name = role.guard_name || 'web'
    form.permissions = role.permissions?.map(p => p.name) || []
    errors.value = {}
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    editingRole.value = null
}

const saveRole = async () => {
    saving.value = true
    errors.value = {}
    try {
        const payload = {
            name: form.name.trim(),
            guard_name: form.guard_name,   // send the actual guard_name
            permissions: form.permissions.map(p => typeof p === 'object' ? p.name : p)
        }
        if (editingRole.value) {
            await rolesApi.update(editingRole.value.id, payload)
        } else {
            await rolesApi.create(payload)
        }
        await fetchRoles()
        closeModal()
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors
        } else {
            alert(err.response?.data?.message || err.message)
        }
    } finally {
        saving.value = false
    }
}

const confirmDelete = (role) => {
    roleToDelete.value = role
    showDeleteModal.value = true
}

const deleteRole = async () => {
    if (!roleToDelete.value) return
    deleting.value = true
    try {
        await rolesApi.delete(roleToDelete.value.id)
        await fetchRoles()
        showDeleteModal.value = false
        roleToDelete.value = null
    } catch (err) {
        alert(err.response?.data?.message || err.message)
    } finally {
        deleting.value = false
    }
}

// Formatters
const formatPermissionName = (name) => {
    if (!name) return ''
    return name.split('.').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatRoleTitle = (name) => {
    if (!name) return ''
    return name.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const formatResourceName = (name) => {
    if (!name) return ''
    return name.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getInitials = (name) => name.split('-').map(n => n[0]).join('').toUpperCase().slice(0, 2)

const getRoleColor = (name) => {
    const colors = {
        'super-admin': 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
        'admin': 'linear-gradient(135deg, #ec4899 0%, #e11d48 100%)',
        'manager': 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)',
        'stylist': 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
        'receptionist': 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
        'staff': 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)'
    }
    return colors[name] || 'linear-gradient(135deg, #64748b 0%, #475569 100%)'
}

onMounted(() => {
    fetchRoles()
    fetchPermissions()
})
</script>
