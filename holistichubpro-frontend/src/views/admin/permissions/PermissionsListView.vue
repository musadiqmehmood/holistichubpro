<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Permissions</h1>
                <p class="text-sm text-gray-500 mt-1">Manage system access controls grouped by resource</p>
            </div>
            <AppButton
                v-if="authStore.isSuperAdmin"
                @click="openCreateModal"
                variant="filled"
                color="primary"
            >
                <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Permission
            </AppButton>
        </div>

        <div v-if="loading" class="flex justify-center p-12">
            <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-100 border-t-blue-600"></div>
        </div>

        <AppCard v-else-if="error" class="p-12 text-center">
            <p class="text-red-600 mb-4">{{ error }}</p>
            <AppButton @click="fetchPermissions" variant="filled" color="primary">Retry</AppButton>
        </AppCard>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 items-stretch">
            <AppCard
                v-for="(perms, resource) in groupedPermissions"
                :key="resource"
                class="flex flex-col h-full overflow-hidden border-gray-200 shadow-sm"
                padding="none"
            >
                <div class="bg-gray-50 px-5 py-4 border-b border-gray-200 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm shadow-sm">
                            {{ getInitials(resource) }}
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 capitalize">{{ formatResourceName(resource) }}</h3>
                    </div>
                    <span class="bg-white border border-gray-200 text-gray-700 px-3 py-1 rounded-full text-xs font-bold shadow-sm">
                        {{ perms.length }}
                    </span>
                </div>

                <div class="flex-1 bg-white divide-y divide-gray-100 flex flex-col">
                    <div v-for="perm in perms" :key="perm.id" class="p-5 hover:bg-gray-50 transition-colors flex-1 flex flex-col justify-between">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-2.5">
                                <span class="font-semibold text-gray-900 text-sm">
                                    {{ formatPermissionName(perm.name) }}
                                </span>
                                <span class="text-[10px] uppercase font-bold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">
                                    {{ perm.guard_name }}
                                </span>
                            </div>

                            <div v-if="authStore.isSuperAdmin" class="flex items-center gap-3 shrink-0 ml-2">
                                <button @click="openEditModal(perm)" class="text-blue-600 hover:text-blue-800 text-xs font-medium transition-colors">Edit</button>
                                <button
                                    @click="confirmDelete(perm)"
                                    :disabled="perm.roles && perm.roles.length > 0"
                                    class="text-red-600 hover:text-red-800 text-xs font-medium disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                                    :title="perm.roles?.length > 0 ? 'Cannot delete permission assigned to roles' : ''"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-1.5 mt-auto">
                            <span
                                v-for="role in perm.roles"
                                :key="role.id"
                                class="text-[10px] font-semibold bg-gray-100 text-gray-600 px-2 py-1 rounded-md border border-gray-200 capitalize"
                            >
                                {{ role.name.replace('-', ' ') }}
                            </span>
                            <span v-if="!perm.roles || perm.roles.length === 0" class="text-[10px] text-gray-400 italic py-1">
                                Not assigned to any role
                            </span>
                        </div>
                    </div>
                </div>
            </AppCard>
        </div>

        <!-- Create/Edit Modal -->
        <AppModal
            :is-open="showModal"
            :title="editingPermission ? 'Edit Permission' : 'Create Permission'"
            :show-confirm="false"
            @close="closeModal"
        >
            <form @submit.prevent="savePermission" class="space-y-5">
                <AppFormField label="Resource" required>
                    <select v-model="form.resource" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50 text-sm">
                        <option value="">Select resource...</option>
                        <option v-for="res in currentResources" :key="res" :value="res">
                            {{ formatResourceName(res) }}
                        </option>
                        <option value="new" class="font-bold text-blue-600">+ Create New Resource</option>
                    </select>
                </AppFormField>

                <AppFormField v-if="form.resource === 'new'" label="New Resource Name" required>
                    <input v-model="form.newResource" type="text" required placeholder="e.g., inventory" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50 text-sm" />
                    <p class="text-xs text-gray-500 mt-1">Will be sanitized: lowercase, spaces replaced with hyphens</p>
                </AppFormField>

                <AppFormField label="Action" required>
                    <div class="flex flex-wrap gap-3">
                        <label
                            v-for="action in currentActions"
                            :key="action"
                            class="border rounded-lg px-4 py-2.5 cursor-pointer text-center transition-all duration-200 grow sm:grow-0"
                            :class="form.action === action ? 'border-blue-500 bg-blue-50 text-blue-700 font-bold shadow-sm' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                        >
                            <input type="radio" :value="action" v-model="form.action" class="sr-only" />
                            <span class="text-sm capitalize">{{ action }}</span>
                        </label>

                        <label
                            class="border rounded-lg px-4 py-2.5 cursor-pointer text-center transition-all duration-200 grow sm:grow-0 border-dashed"
                            :class="form.action === 'custom' ? 'border-blue-500 bg-blue-50 text-blue-700 font-bold shadow-sm border-solid' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
                        >
                            <input type="radio" value="custom" v-model="form.action" class="sr-only" />
                            <span class="text-sm font-medium">+ Custom</span>
                        </label>
                    </div>
                </AppFormField>

                <AppFormField v-if="form.action === 'custom'" label="Custom Action Name" required>
                    <input v-model="form.customAction" type="text" required placeholder="e.g., export" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50 text-sm" />
                    <p class="text-xs text-gray-500 mt-1">Will be sanitized: lowercase, spaces replaced with hyphens</p>
                </AppFormField>

                <AppFormField label="Guard Name">
                    <select v-model="form.guard_name" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50 text-sm">
                        <option value="web">web</option>
                        <option value="api">api</option>
                    </select>
                </AppFormField>

                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 mt-2">
                    <p class="text-xs font-semibold text-blue-800 mb-1">System Key Preview:</p>
                    <code class="text-blue-900 font-mono text-sm font-bold">{{ permissionPreview }}</code>
                </div>

                <div class="flex justify-end gap-3 pt-5 mt-5 border-t border-gray-100">
                    <AppButton type="button" @click="closeModal" variant="outlined" color="neutral">
                        Cancel
                    </AppButton>
                    <AppButton type="submit" variant="filled" color="primary" :loading="saving">
                        {{ editingPermission ? 'Update Permission' : 'Create Permission' }}
                    </AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Delete Confirmation Modal -->
        <AppModal
            :is-open="showDeleteModal"
            title="Confirm Delete"
            confirm-text="Delete Permission"
            cancel-text="Cancel"
            :loading="deleting"
            @close="showDeleteModal = false"
            @confirm="deletePermission"
        >
            <p class="text-gray-700 text-base">Are you sure you want to delete <strong class="text-gray-900 font-mono bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">{{ permissionToDelete?.name }}</strong>?</p>
        </AppModal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { permissionsApi } from '@/api/permissions'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppFormField from '@/components/ui/AppFormField.vue'

// --- Constants (base resources and actions) ---
const BASE_RESOURCES = ['users', 'roles', 'permissions', 'branches', 'services', 'products', 'customers', 'appointments', 'audit', 'pos', 'reports', 'settings']
const BASE_ACTIONS = ['view', 'create', 'edit', 'delete', 'access', 'manage']

// --- State ---
const authStore = useAuthStore()
const loading = ref(false)
const error = ref(null)
const permissions = ref([])
const showModal = ref(false)
const showDeleteModal = ref(false)
const editingPermission = ref(null)
const permissionToDelete = ref(null)
const saving = ref(false)
const deleting = ref(false)

// ✅ F-49: Removed localStorage persistence for hh_actions and hh_resources
// All options are now derived solely from API data (permissions list)

const form = reactive({
    resource: '',
    newResource: '',
    action: 'view',
    customAction: '',
    guard_name: 'web'
})

// --- Computed (derived from API only) ---
const groupedPermissions = computed(() => {
    const grouped = {}
    permissions.value.forEach(p => {
        const [resource] = p.name.split('.')
        if (!grouped[resource]) grouped[resource] = []
        grouped[resource].push(p)
    })
    return grouped
})

// ✅ F-49: Resources derived from API permissions + base list (no localStorage)
const currentResources = computed(() => {
    const resSet = new Set()
    BASE_RESOURCES.forEach(r => resSet.add(r.trim().toLowerCase()))
    permissions.value.forEach(p => {
        const [res] = p.name.split('.')
        if (res) resSet.add(res.trim().toLowerCase())
    })
    return Array.from(resSet).sort()
})

// ✅ F-49: Actions derived from API permissions + base list (no localStorage)
const currentActions = computed(() => {
    const actSet = new Set()
    BASE_ACTIONS.forEach(a => actSet.add(a.trim().toLowerCase()))
    permissions.value.forEach(p => {
        const parts = p.name.split('.')
        if (parts[1]) actSet.add(parts[1].trim().toLowerCase())
    })
    return Array.from(actSet).sort()
})

// Sanitize resource/action name (F-50)
const sanitizeName = (name) => {
    if (!name) return ''
    return name.trim().toLowerCase().replace(/\s+/g, '-')
}

const permissionPreview = computed(() => {
    let resource = ''
    if (form.resource === 'new') {
        resource = sanitizeName(form.newResource)
    } else {
        resource = sanitizeName(form.resource)
    }
    let action = ''
    if (form.action === 'custom') {
        action = sanitizeName(form.customAction)
    } else {
        action = sanitizeName(form.action)
    }
    if (!resource || !action) return '...'
    return `${resource}.${action}`
})

// --- API Calls ---
const fetchPermissions = async () => {
    loading.value = true
    error.value = null
    try {
        const response = await permissionsApi.getAll()
        const payload = response.data
        // ApiResponse envelope: { success, message, data: [...], meta: {...} }
        if (payload && Array.isArray(payload.data)) {
            permissions.value = payload.data
        } else if (Array.isArray(payload)) {
            permissions.value = payload
        } else {
            permissions.value = []
        }
    } catch (err) {
        error.value = 'Failed to fetch permissions'
        console.error(err)
    } finally {
        loading.value = false
    }
}

// --- Modal Handlers ---
const openCreateModal = () => {
    editingPermission.value = null
    Object.assign(form, {
        resource: '',
        newResource: '',
        action: 'view',
        customAction: '',
        guard_name: 'web'
    })
    showModal.value = true
}

const openEditModal = (perm) => {
    editingPermission.value = perm
    const [res, act] = perm.name.split('.')
    form.resource = sanitizeName(res)
    form.newResource = ''
    const cleanAct = sanitizeName(act)
    if (BASE_ACTIONS.includes(cleanAct) || currentActions.value.includes(cleanAct)) {
        form.action = cleanAct
        form.customAction = ''
    } else {
        form.action = 'custom'
        form.customAction = cleanAct
    }
    form.guard_name = perm.guard_name
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    editingPermission.value = null
}

// ✅ F-50: Save with sanitized names
const savePermission = async () => {
    saving.value = true
    try {
        const resource = form.resource === 'new' ? sanitizeName(form.newResource) : sanitizeName(form.resource)
        const action = form.action === 'custom' ? sanitizeName(form.customAction) : sanitizeName(form.action)
        const permissionName = `${resource}.${action}`

        // Validate that both parts are non-empty
        if (!resource || !action) {
            alert('Resource and action names cannot be empty.')
            return
        }

        const payload = {
            name: permissionName,
            guard_name: form.guard_name
        }

        if (editingPermission.value) {
            await permissionsApi.update(editingPermission.value.id, payload)
        } else {
            await permissionsApi.create(payload)
        }

        await fetchPermissions()
        closeModal()
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to save permission')
    } finally {
        saving.value = false
    }
}

const confirmDelete = (perm) => {
    permissionToDelete.value = perm
    showDeleteModal.value = true
}

const deletePermission = async () => {
    if (!permissionToDelete.value) return
    deleting.value = true
    try {
        await permissionsApi.delete(permissionToDelete.value.id)
        await fetchPermissions()
        showDeleteModal.value = false
        permissionToDelete.value = null
    } catch (err) {
        alert(err.response?.data?.message || 'Delete failed')
    } finally {
        deleting.value = false
    }
}

// --- Formatters ---
const formatPermissionName = (n) => n ? n.split('.').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') : ''
const formatRoleTitle = (n) => n ? n.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') : ''
const formatResourceName = (n) => n.replace(/[-_]/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
const getInitials = (n) => n.slice(0, 2).toUpperCase()

onMounted(fetchPermissions)
</script>
