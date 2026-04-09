<template>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Users Management</h1>
                <p class="text-sm text-gray-500 mt-1">Manage system-wide user accounts, security roles, and branch data.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <AppButton
                    v-if="authStore.hasPermission('users.view')"
                    @click="handleExport"
                    variant="outlined"
                    color="neutral"
                    :loading="exporting"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export CSV
                </AppButton>

                <AppButton
                    v-if="authStore.hasPermission('users.create')"
                    @click="triggerImport"
                    variant="tonal"
                    color="primary"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Import Users
                </AppButton>

                <AppButton
                    v-if="authStore.hasPermission('users.create')"
                    @click="openCreateModal"
                    variant="filled"
                    color="primary"
                >
                    <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add User
                </AppButton>
            </div>
        </div>

        <!-- Filters (unchanged) -->
        <AppCard padding="small" class="shadow-sm border-gray-100 bg-white">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <AppFormField label="Search Identity">
                    <div class="relative">
                        <input
                            v-model="filters.search"
                            @input="handleSearchInput"
                            type="text"
                            placeholder="Name, email, or phone..."
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 pl-10 focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                        />
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </AppFormField>

                <AppFormField v-if="isActuallySuperAdmin" label="Assigned Branch">
                    <select
                        v-model="filters.branch_id"
                        @change="handleFilterChange"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none bg-white font-medium"
                    >
                        <option value="">All Available Branches</option>
                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                            {{ branch.name }}
                        </option>
                    </select>
                </AppFormField>

                <AppFormField label="System Role">
                    <select
                        v-model="filters.role"
                        @change="handleFilterChange"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none bg-white font-medium"
                    >
                        <option value="">Any Access Level</option>
                        <option v-for="role in roleOptions" :key="role.id" :value="role.name">
                            {{ formatRoleName(role.name) }}
                        </option>
                    </select>
                </AppFormField>

                <div class="flex items-center gap-3 pb-1">
                    <div class="flex-1">
                        <select
                            v-model="filters.per_page"
                            @change="handleFilterChange"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white"
                        >
                            <option :value="10">10 / Page</option>
                            <option :value="25">25 / Page</option>
                            <option :value="50">50 / Page</option>
                        </select>
                    </div>
                    <AppButton @click="resetFilters" variant="text" color="neutral" size="small">
                        Reset All
                    </AppButton>
                </div>
            </div>
        </AppCard>

        <!-- Loading / Error / Table (unchanged except AppBadge fix) -->
        <div v-if="loading" class="flex flex-col items-center justify-center p-24 bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-50 border-t-blue-600 mb-4"></div>
            <p class="text-gray-500 font-medium">Synchronizing user data...</p>
        </div>

        <AppCard v-else-if="error" class="p-16 text-center border-red-100 bg-red-50/20">
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Service Error</h3>
                <p class="text-gray-600 mb-6 max-w-sm mx-auto">{{ error }}</p>
                <AppButton @click="fetchUsers" variant="filled" color="primary">Retry Fetching</AppButton>
            </div>
        </AppCard>

        <template v-else>
            <AppTable :columns="columns" :data="users" class="shadow-sm">
                <template #name="{ item }">
                    <div class="flex items-center gap-4 py-2">
                        <AppAvatar :name="item.name" size="md" />
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900 flex items-center gap-2">
                                {{ item.name }}
                                <span v-if="isSuperAdminUser(item)" class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded-md uppercase tracking-tighter">System Protected</span>
                            </span>
                            <span class="text-xs text-gray-500">{{ item.email }}</span>
                        </div>
                    </div>
                </template>

                <template #roles="{ item }">
                    <div class="flex flex-wrap gap-1.5 max-w-[240px]">
                        <!-- ✅ F-46: Removed variant="tonal" (not supported) -->
                        <AppBadge
                            v-for="role in item.roles"
                            :key="role.id"
                            :text="formatRoleName(role.name)"
                            :color="getRoleBadgeColor(role.name)"
                        />
                        <span v-if="!item.roles?.length" class="text-xs text-gray-400 italic font-medium">No Roles</span>
                    </div>
                </template>

                <template #branch="{ item }">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full" :class="item.branch ? 'bg-blue-500' : 'bg-gray-300'"></div>
                        <span class="text-sm font-semibold text-gray-700">
                            {{ item.branch?.name || 'Headquarters' }}
                        </span>
                    </div>
                </template>

                <template #last_login="{ item }">
                    <div class="flex flex-col text-xs">
                        <span class="text-gray-800 font-bold">{{ formatDate(item.last_login_at) }}</span>
                        <span class="text-gray-400 font-mono">{{ item.last_login_ip || '---' }}</span>
                    </div>
                </template>

                <template #status="{ item }">
                    <!-- ✅ F-46: Removed variant="tonal" -->
                    <AppBadge
                        :text="item.email_verified_at ? 'Security Verified' : 'Awaiting Link'"
                        :color="item.email_verified_at ? 'success' : 'warning'"
                    />
                </template>

                <template #actions="{ item }">
                    <div class="flex justify-end gap-1">
                        <AppButton
                            v-if="authStore.hasPermission('users.edit')"
                            size="small"
                            variant="text"
                            color="primary"
                            @click="openEditModal(item)"
                            :disabled="isSuperAdminUser(item) && !isActuallySuperAdmin"
                            title="Edit User Access"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </AppButton>

                        <AppButton
                            v-if="authStore.hasPermission('users.delete')"
                            size="small"
                            variant="text"
                            color="error"
                            @click="confirmDelete(item)"
                            :disabled="isSuperAdminUser(item)"
                            title="Purge User Account"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </AppButton>
                    </div>
                </template>
            </AppTable>

            <AppPagination
                v-if="pagination.last_page > 1"
                :current-page="pagination.current_page"
                :last-page="pagination.last_page"
                :total="pagination.total"
                :from="pagination.from"
                :to="pagination.to"
                @change="handlePageChange"
            />
        </template>

        <UserFormModal
            ref="userModalRef"
            :is-open="showModal"
            :user="editingUser"
            :branches="branches"
            :available-roles="roleOptions"
            :roles-loading="rolesLoading"
            :is-super-admin="isActuallySuperAdmin"
            @close="closeModal"
            @save="handleSave"
        />

        <!-- Hidden file input for import -->
        <input
            type="file"
            ref="importFileInput"
            class="hidden"
            accept=".csv"
            @change="handleImportFile"
        />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { usersApi } from '@/api/users'
import { rolesApi } from '@/api/roles'
import { branchesApi } from '@/api/branches'
import UserFormModal from '@/components/modals/UserFormModal.vue'
import { saveAs } from 'file-saver' // ✅ For client‑side CSV export

const authStore = useAuthStore()
const uiStore = useUiStore()
const userModalRef = ref(null)
const importFileInput = ref(null)

const loading = ref(false)
const rolesLoading = ref(false)
const exporting = ref(false)
const error = ref(null)

const users = ref([])
const roleOptions = ref([])
const branches = ref([])
const showModal = ref(false)
const editingUser = ref(null)

const pagination = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
    from: 0,
    to: 0
})

const filters = reactive({
    search: '',
    branch_id: '',
    role: '',
    page: 1,
    per_page: 10
})

const columns = [
    { key: 'name', label: 'User Identity' },
    { key: 'roles', label: 'Security Level' },
    { key: 'branch', label: 'Assigned Branch' },
    { key: 'last_login', label: 'Last Login' },
    { key: 'status', label: 'Verified Status' }
]

// Computed
const isActuallySuperAdmin = computed(() => {
    return authStore.isSuperAdmin || authStore.user?.roles?.some(r => r.name === 'super-admin')
})

const isSuperAdminUser = (u) => {
    return u.roles?.some(r => r.name === 'super-admin')
}

// Debounced search
let searchTimer = null
const handleSearchInput = () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        filters.page = 1
        fetchUsers()
    }, 450)
}

const handleFilterChange = () => {
    filters.page = 1
    fetchUsers()
}

const resetFilters = () => {
    Object.assign(filters, { search: '', branch_id: '', role: '', page: 1, per_page: 10 })
    fetchUsers()
}

const handlePageChange = (num) => {
    filters.page = num
    fetchUsers()
}

// Fetch users
const fetchUsers = async () => {
    if (!authStore.hasPermission('users.view')) {
        error.value = 'Security Access Violation: You do not have "users.view" permission.'
        return
    }

    loading.value = true
    error.value = null
    try {
        const response = await usersApi.getAll(filters)
        const responseData = response.data.data || response.data
        users.value = Array.isArray(responseData) ? responseData : responseData.data || []

        Object.assign(pagination, {
            current_page: response.data.current_page || 1,
            last_page: response.data.last_page || 1,
            total: response.data.total || 0,
            from: response.data.from || 0,
            to: response.data.to || 0
        })
    } catch (err) {
        error.value = err.response?.data?.message || 'CRITICAL: User Service Communication Failure.'
    } finally {
        loading.value = false
    }
}

// Fetch roles & branches
const fetchRoles = async () => {
    if (!authStore.hasPermission('roles.view')) return
    rolesLoading.value = true
    try {
        const res = await rolesApi.getAll()
        roleOptions.value = res.data.data || res.data || []
    } catch (e) {
        console.warn('[RBAC] Roles fetch bypassed due to permissions.')
    } finally {
        rolesLoading.value = false
    }
}

const fetchBranches = async () => {
    if (!authStore.hasPermission('branches.view')) return
    try {
        const res = await branchesApi.getAll()
        branches.value = res.data.data || res.data || []
    } catch (e) {
        console.warn('[Branching] Unauthorized or empty branch list.')
    }
}

// CRUD
const openCreateModal = () => {
    editingUser.value = null
    showModal.value = true
}

const openEditModal = (user) => {
    editingUser.value = user
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    editingUser.value = null
}

const handleSave = async ({ id, payload, isEditing }) => {
    try {
        if (isEditing) {
            await usersApi.update(id, payload)
        } else {
            await usersApi.create(payload)
        }
        closeModal()
        fetchUsers()
    } catch (err) {
        const msg = err.response?.data?.message || 'Update Rejected: Invalid Parameters.'
        if (userModalRef.value?.setError) userModalRef.value.setError(msg)
    }
}

const confirmDelete = async (user) => {
    if (isSuperAdminUser(user)) {
        alert('Forbidden: Super Admin accounts cannot be purged through this interface.')
        return
    }
    if (!confirm(`Confirm Deletion: All access for "${user.name}" will be revoked immediately.`)) return
    try {
        await usersApi.delete(user.id)
        fetchUsers()
    } catch (err) {
        alert(err.response?.data?.message || 'Purge Operation Failed.')
    }
}

// ✅ F-45: Client‑side export (CSV from current filtered data)
const handleExport = async () => {
    exporting.value = true
    try {
        // Fetch all users matching current filters (without pagination limit)
        const exportParams = { ...filters, per_page: 10000 }
        const response = await usersApi.getAll(exportParams)
        const allUsers = response.data.data || response.data || []

        const headers = ['ID', 'Name', 'Email', 'Branch', 'Roles', 'Created At']
        const rows = allUsers.map(user => [
            user.id,
            user.name,
            user.email,
            user.branch?.name || 'N/A',
            (user.roles || []).map(r => r.name).join('; '),
            new Date(user.created_at).toLocaleString()
        ])

        const csvContent = [headers, ...rows].map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n')
        const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' })
        saveAs(blob, `users-${new Date().toISOString().slice(0, 19)}.csv`)

        uiStore.addNotification({ type: 'success', message: 'Exported successfully' })
    } catch (err) {
        console.error('Export failed:', err)
        uiStore.addNotification({ type: 'error', message: 'Export failed' })
    } finally {
        exporting.value = false
    }
}

// ✅ F-45: Import using backend endpoint (already exists from earlier backend fixes)
const triggerImport = () => importFileInput.value?.click()

const handleImportFile = async (event) => {
    const file = event.target.files[0]
    if (!file) return

    const formData = new FormData()
    formData.append('file', file)

    try {
        loading.value = true
        const response = await usersApi.import(formData)  // Assumes usersApi.import is defined (add if missing)
        uiStore.addNotification({ type: 'success', message: response.data?.message || 'Import successful' })
        fetchUsers()
    } catch (err) {
        uiStore.addNotification({ type: 'error', message: err.response?.data?.message || 'Import failed' })
    } finally {
        loading.value = false
        event.target.value = ''
    }
}

// Utils
const formatRoleName = (n) => {
    if (!n) return ''
    return n.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const formatDate = (str) => {
    if (!str) return 'No Recorded Activity'
    return new Date(str).toLocaleString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    })
}

const getRoleBadgeColor = (n) => {
    const map = {
        'super-admin': 'error',
        'admin': 'primary',
        'manager': 'success',
        'stylist': 'primary',
        'receptionist': 'warning'
    }
    return map[n] || 'gray'
}

// Init
onMounted(async () => {
    await Promise.allSettled([
        fetchUsers(),
        fetchRoles(),
        fetchBranches()
    ])
})
</script>
