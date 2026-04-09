<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">System Audit Logs</h1>
                <p class="text-sm text-gray-500 mt-1">Track and monitor all administrative changes</p>
            </div>
            <div class="flex gap-2">
                <!-- Bulk Delete - Super Admin Only -->
                <AppButton
                    v-if="isSuperAdmin && selectedLogs.length > 0"
                    @click="confirmBulkDelete"
                    variant="filled"
                    color="error"
                    class="animate-pulse"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete ({{ selectedLogs.length }})
                </AppButton>

                <AppButton @click="exportLogs" variant="tonal" color="primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16m-8-8l8 8-8 8" />
                    </svg>
                    Export CSV
                </AppButton>
            </div>
        </div>

        <!-- Filters (unchanged) -->
        <AppCard padding="medium" class="mb-6 shadow-sm border border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4">
                <AppFormField label="Search" class="lg:col-span-2">
                    <div class="relative">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Search in logs..."
                            class="w-full border rounded-lg px-3 py-2.5 bg-gray-50 text-sm focus:ring-2 focus:ring-primary-500 outline-none pr-8"
                            @input="debouncedApplyFilters"
                        />
                        <svg v-if="filters.search" @click="clearSearch" class="w-4 h-4 absolute right-3 top-3 text-gray-400 cursor-pointer hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </AppFormField>

                <AppFormField label="Action">
                    <select v-model="filters.action" @change="applyFilters" class="w-full border rounded-lg px-3 py-2.5 bg-gray-50 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="">All Actions</option>
                        <option v-for="action in filterOptions.actions" :key="action" :value="action">
                            {{ formatAction(action) }}
                        </option>
                    </select>
                </AppFormField>

                <AppFormField label="Target Type">
                    <select v-model="filters.entity_type" @change="applyFilters" class="w-full border rounded-lg px-3 py-2.5 bg-gray-50 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="">All Types</option>
                        <option v-for="type in filterOptions.entity_types" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>
                </AppFormField>

                <AppFormField label="Performed By">
                    <select v-model="filters.performed_by" @change="applyFilters" class="w-full border rounded-lg px-3 py-2.5 bg-gray-50 text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        <option value="">Everyone</option>
                        <option v-for="user in filterOptions.performers" :key="user.id" :value="user.id">
                            {{ user.name }}
                        </option>
                    </select>
                </AppFormField>

                <AppFormField label="From Date">
                    <input v-model="filters.from_date" @change="applyFilters" type="date" class="w-full border rounded-lg px-3 py-2.5 bg-gray-50 text-sm outline-none" />
                </AppFormField>

                <AppFormField label="To Date">
                    <input v-model="filters.to_date" @change="applyFilters" type="date" class="w-full border rounded-lg px-3 py-2.5 bg-gray-50 text-sm outline-none" />
                </AppFormField>
            </div>

            <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Show:</label>
                    <select v-model="pagination.per_page" @change="applyFilters" class="border rounded px-2 py-1 text-sm bg-white">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <span class="text-sm text-gray-600">entries per page</span>
                    <span v-if="loading" class="text-xs text-primary-600 animate-pulse ml-2">
                        <svg class="w-3 h-3 inline mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading...
                    </span>
                </div>
                <AppButton @click="resetFilters" variant="tonal" size="small" class="h-[36px] px-4">
                    Reset Filters
                </AppButton>
            </div>
        </AppCard>

        <!-- Error Alert -->
        <AppAlert v-if="error" type="error" class="mb-4" dismissible @dismiss="error = null">
            {{ error }}
        </AppAlert>

        <!-- Loading State -->
        <div v-if="loading && logs.length === 0" class="flex justify-center p-12">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-100 border-t-primary-600"></div>
        </div>

        <!-- Empty State -->
        <AppCard v-else-if="logs.length === 0" class="p-16 text-center border-dashed border-2">
            <div class="flex flex-col items-center">
                <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-gray-500 font-medium">No audit logs match your filters.</p>
                <AppButton @click="resetFilters" variant="text" color="primary" class="mt-2">Clear all filters</AppButton>
            </div>
        </AppCard>

        <!-- Data Table -->
        <AppCard v-else padding="none" class="overflow-hidden shadow-sm border border-gray-100">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th v-if="isSuperAdmin" class="px-4 py-3 w-10">
                            <input
                                ref="selectAllCheckbox"
                                type="checkbox"
                                @change="toggleSelectAll"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                        </th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Timestamp</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Target</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Changes</th>
                        <th v-if="isSuperAdmin" class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50 transition-colors" :class="{ 'bg-primary-50': selectedLogs.includes(log.id) }">
                        <td v-if="isSuperAdmin" class="px-4 py-3">
                            <input
                                type="checkbox"
                                :value="log.id"
                                v-model="selectedLogs"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ formatDate(log.created_at) }}</div>
                            <div class="text-[11px] text-gray-500 uppercase tracking-tight">{{ formatTime(log.created_at) }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <AppBadge :text="formatAction(log.action)" :color="getActionColor(log.action)" class="font-bold" />
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col">
                                <span class="font-semibold text-gray-800">{{ log.entity_type?.split('\\').pop() }}</span>
                                <span class="text-[10px] text-gray-400 font-mono">ID #{{ log.entity_id }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <!-- ✅ F-56: Changed size from "xs" to "sm" -->
                                <AppAvatar :name="log.performer?.name || 'System'" size="sm" class="ring-1 ring-gray-100" />
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900">{{ log.performer?.name || 'System' }}</span>
                                    <span class="text-[10px] font-mono text-gray-500" :class="{ 'text-red-500': !log.ip_address || log.ip_address === '127.0.0.1' }">
                                            {{ log.ip_address || 'No IP' }}
                                        </span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <button
                                v-if="hasChanges(log)"
                                @click="showDetails(log)"
                                class="px-3 py-1 bg-blue-50 text-blue-700 text-xs font-bold rounded-md hover:bg-blue-100 transition-colors border border-blue-100"
                            >
                                View Diff
                            </button>
                            <span v-else class="text-gray-300 text-xs italic">No changes</span>
                        </td>
                        <td v-if="isSuperAdmin" class="px-4 py-3 text-right">
                            <button
                                @click="confirmDelete(log)"
                                class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                title="Delete Log"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total }} entries
                    <span v-if="isSuperAdmin && selectedLogs.length > 0" class="ml-2 text-primary-600 font-medium">
                        ({{ selectedLogs.length }} selected)
                    </span>
                </div>
                <AppPagination
                    v-if="pagination.last_page > 1"
                    :current-page="pagination.current_page"
                    :last-page="pagination.last_page"
                    :total="pagination.total"
                    @change="changePage"
                />
            </div>
        </AppCard>

        <!-- Detail Modal (unchanged) -->
        <Teleport to="body">
            <div v-if="showDetailsModal" class="modal-overlay" @click.self="closeDetails">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3 class="modal-title">Audit Data Comparison</h3>
                        <button class="btn-close" @click="closeDetails">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="info-bar">
                            <div class="info-item">
                                <span class="info-label">Performer:</span>
                                <span class="info-value">{{ selectedLogDetails?.performed_by }}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Action:</span>
                                <span class="info-value action-badge">{{ selectedLogDetails?.action }}</span>
                            </div>
                        </div>
                        <div class="diff-grid">
                            <div class="diff-column old-state">
                                <div class="diff-header">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Previous State (Old)</span>
                                </div>
                                <div class="diff-content old">
                                    <pre class="json-viewer">{{ formatJson(selectedLogDetails?.old_values) }}</pre>
                                </div>
                            </div>
                            <div class="diff-column new-state">
                                <div class="diff-header">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Current State (New)</span>
                                </div>
                                <div class="diff-content new">
                                    <pre class="json-viewer">{{ formatJson(selectedLogDetails?.new_values) }}</pre>
                                </div>
                            </div>
                        </div>
                        <div class="user-agent-section">
                            <p class="user-agent-label">User Agent:</p>
                            <div class="user-agent-value">{{ selectedLogDetails?.user_agent }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" @click="closeDetails">Close</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Confirmation Modal -->
        <AppModal
            :is-open="showDeleteModal"
            :title="deleteModalTitle"
            :show-confirm="true"
            confirm-text="Delete"
            confirm-color="error"
            @close="showDeleteModal = false"
            @confirm="executeDelete"
        >
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-4 bg-red-50 border border-red-100 rounded-lg">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-red-900">This action cannot be undone</p>
                        <p class="text-xs text-red-700 mt-1">{{ deleteModalMessage }}</p>
                    </div>
                </div>
                <div v-if="logToDelete && !isBulkDelete" class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div><span class="text-gray-500">Action:</span> <span class="font-medium">{{ formatAction(logToDelete.action) }}</span></div>
                        <div><span class="text-gray-500">Entity:</span> <span class="font-medium">{{ logToDelete.entity_type?.split('\\').pop() }} #{{ logToDelete.entity_id }}</span></div>
                        <div><span class="text-gray-500">Date:</span> <span class="font-medium">{{ formatDate(logToDelete.created_at) }}</span></div>
                        <div><span class="text-gray-500">By:</span> <span class="font-medium">{{ logToDelete.performer?.name || 'System' }}</span></div>
                    </div>
                </div>
            </div>
        </AppModal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watchEffect } from 'vue'
import { auditApi } from '@/api/audit'
import { useAuthStore } from '@/stores/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppAvatar from '@/components/ui/AppAvatar.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppAlert from '@/components/ui/AppAlert.vue'
import { Teleport } from 'vue'

defineOptions({ name: 'AuditLogsView' })

const authStore = useAuthStore()
const loading = ref(false)
const error = ref(null)
const logs = ref([])

const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 10,
    from: 0,
    to: 0
})

const filterOptions = reactive({ actions: [], entity_types: [], performers: [] })

const filters = reactive({
    action: '',
    entity_type: '',
    performed_by: '',
    from_date: '',
    to_date: '',
    search: ''
})

const selectedLogs = ref([])
const showDeleteModal = ref(false)
const logToDelete = ref(null)
const isBulkDelete = ref(false)
const showDetailsModal = ref(false)
const selectedLogDetails = ref(null)

// Template ref for select-all checkbox
const selectAllCheckbox = ref(null)

let debounceTimer = null

// Computed properties
const isSuperAdmin = computed(() => {
    return authStore.user?.roles?.some(r => r.name === 'super-admin') || false
})

const isAllSelected = computed(() => {
    return logs.value.length > 0 && selectedLogs.value.length === logs.value.length
})

const deleteModalTitle = computed(() => {
    return isBulkDelete.value ? `Delete ${selectedLogs.value.length} Logs` : 'Delete Audit Log'
})

const deleteModalMessage = computed(() => {
    return isBulkDelete.value
        ? `You are about to permanently delete ${selectedLogs.value.length} audit log entries.`
        : 'You are about to permanently delete this audit log entry.'
})

// Lifecycle
onMounted(() => {
    fetchFilterOptions()
    fetchLogs()
})

onUnmounted(() => {
    clearTimeout(debounceTimer)
})

// Watch for checkbox indeterminate state
watchEffect(() => {
    if (selectAllCheckbox.value) {
        const isIndeterminate = selectedLogs.value.length > 0 && selectedLogs.value.length < logs.value.length
        selectAllCheckbox.value.indeterminate = isIndeterminate
        selectAllCheckbox.value.checked = isAllSelected.value
    }
})

// Methods
const debouncedApplyFilters = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        pagination.value.current_page = 1
        fetchLogs()
    }, 400)
}

const applyFilters = () => {
    clearTimeout(debounceTimer)
    pagination.value.current_page = 1
    fetchLogs()
}

const clearSearch = () => {
    filters.search = ''
    applyFilters()
}

const resetFilters = () => {
    clearTimeout(debounceTimer)
    Object.keys(filters).forEach(key => filters[key] = '')
    pagination.value.per_page = 10
    pagination.value.current_page = 1
    selectedLogs.value = []
    error.value = null
    fetchLogs()
}

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedLogs.value = []
    } else {
        selectedLogs.value = logs.value.map(log => log.id)
    }
}

const confirmDelete = (log) => {
    logToDelete.value = log
    isBulkDelete.value = false
    showDeleteModal.value = true
}

const confirmBulkDelete = () => {
    logToDelete.value = null
    isBulkDelete.value = true
    showDeleteModal.value = true
}

const executeDelete = async () => {
    try {
        loading.value = true
        error.value = null

        if (isBulkDelete.value) {
            await auditApi.bulkDelete({ ids: selectedLogs.value })
        } else {
            await auditApi.delete(logToDelete.value.id)
        }

        selectedLogs.value = []
        showDeleteModal.value = false
        logToDelete.value = null
        await fetchLogs()
    } catch (err) {
        console.error('Failed to delete logs:', err)
        error.value = err.response?.data?.message || 'Failed to delete audit log(s)'
        if (err.response?.status === 401) {
            authStore.logout()
        } else if (err.response?.status === 403) {
            error.value = 'You do not have permission to delete audit logs.'
        }
    } finally {
        loading.value = false
    }
}

const fetchFilterOptions = async () => {
    try {
        error.value = null
        const response = await auditApi.getFilters()
        filterOptions.actions = response.data?.actions || []
        filterOptions.entity_types = response.data?.entity_types || []
        filterOptions.performers = response.data?.performers || []
    } catch (err) {
        console.error('Failed to load filter options:', err)
        if (err.response?.status !== 401) {
            error.value = 'Failed to load filter options'
        }
    }
}

const fetchLogs = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await auditApi.getAll({
            ...filters,
            page: pagination.value.current_page,
            per_page: pagination.value.per_page
        })

        const data = response.data
        logs.value = data.data || []
        pagination.value = {
            current_page: data.current_page || 1,
            last_page: data.last_page || 1,
            total: data.total || 0,
            per_page: data.per_page || 10,
            from: data.from || 0,
            to: data.to || 0
        }

        // Clear orphaned selections
        selectedLogs.value = selectedLogs.value.filter(id =>
            logs.value.some(log => log.id === id)
        )
    } catch (err) {
        console.error('Failed to fetch logs:', err)
        error.value = err.response?.data?.message || 'Failed to fetch audit logs'
        if (err.response?.status === 401) {
            authStore.logout()
        }
    } finally {
        loading.value = false
    }
}

const changePage = (page) => {
    pagination.value.current_page = page
    fetchLogs()
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

const formatDate = (d) => {
    if (!d) return '-'
    return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatTime = (d) => {
    if (!d) return '-'
    return new Date(d).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

const formatAction = (a) => {
    if (!a) return '-'
    return a.replace(/_/g, ' ').toUpperCase()
}

const getActionColor = (a) => {
    if (!a) return 'gray'
    if (a.includes('attached') || a === 'created' || a === 'login') return 'success'
    if (a.includes('detached') || a === 'deleted' || a === 'logout') return 'error'
    if (a === 'updated') return 'warning'
    return 'gray'
}

const formatJson = (val) => {
    if (!val) return 'None'
    return typeof val === 'object' ? JSON.stringify(val, null, 2) : String(val)
}

const hasChanges = (log) => {
    return (log.old_values && Object.keys(log.old_values).length > 0) ||
        (log.new_values && Object.keys(log.new_values).length > 0)
}

const showDetails = (log) => {
    selectedLogDetails.value = {
        action: formatAction(log.action),
        performed_by: log.performer?.name || 'System',
        old_values: log.old_values,
        new_values: log.new_values,
        user_agent: log.user_agent || 'Unknown'
    }
    showDetailsModal.value = true
}

const closeDetails = () => {
    showDetailsModal.value = false
    selectedLogDetails.value = null
}

// Client-side CSV export (no backend call)
const exportLogs = () => {
    try {
        const headers = ['Timestamp', 'Action', 'Target', 'ID', 'User', 'IP', 'Agent']
        const rows = logs.value.map(l => [
            l.created_at,
            l.action,
            l.entity_type?.split('\\').pop() || 'Unknown',
            l.entity_id,
            l.performer?.name || 'System',
            l.ip_address || 'No IP',
            l.user_agent || ''
        ])

        const csvContent = [headers, ...rows].map(row =>
            row.map(field => `"${String(field).replace(/"/g, '""')}"`).join(',')
        ).join('\n')

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
        const link = document.createElement('a')
        const url = URL.createObjectURL(blob)

        link.href = url
        link.download = `audit-logs-${new Date().toISOString().slice(0,10)}.csv`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)
    } catch (err) {
        console.error('Export failed:', err)
        error.value = 'Failed to export logs'
    }
}
</script>

<style scoped>
tr { transition: background-color 0.2s ease; }
input[type="checkbox"] { cursor: pointer; }

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}
.animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    z-index: 50;
    backdrop-filter: blur(4px);
}

.modal-container {
    background: white;
    border-radius: 1rem;
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(to right, #f9fafb, #ffffff);
}

.modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
}

.btn-close {
    background: #f3f4f6;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    color: #6b7280;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-close:hover {
    color: #ef4444;
    background: #fee2e2;
}

.modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
    background: #fafafa;
}

.info-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
    margin-bottom: 1.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: #111827;
}

.action-badge {
    background: #4f46e5;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
}

.diff-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.diff-column {
    display: flex;
    flex-direction: column;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.diff-column.old-state { border: 2px solid #fecaca; }
.diff-column.new-state { border: 2px solid #a7f3d0; }

.diff-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1rem;
    font-weight: 800;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.old-state .diff-header { background: #fee2e2; color: #dc2626; }
.new-state .diff-header { background: #d1fae5; color: #059669; }

.diff-content {
    flex: 1;
    overflow: auto;
    max-height: 400px;
}

.diff-content.old { background: #fef2f2; }
.diff-content.new { background: #ecfdf5; }

.json-viewer {
    margin: 0;
    padding: 1.25rem;
    font-size: 0.8125rem;
    line-height: 1.6;
    font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace;
    white-space: pre-wrap;
    word-break: break-word;
}

.diff-content.old .json-viewer { color: #991b1b; }
.diff-content.new .json-viewer { color: #065f46; }

.user-agent-section {
    background: white;
    padding: 1rem 1.25rem;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb;
}

.user-agent-label {
    margin: 0 0 0.5rem;
    font-size: 0.625rem;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.user-agent-value {
    font-size: 0.75rem;
    color: #6b7280;
    font-family: monospace;
    font-style: italic;
    word-break: break-all;
    background: #f9fafb;
    padding: 0.75rem;
    border-radius: 0.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    background: white;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover { background: #e5e7eb; }

@media (max-width: 768px) {
    .diff-grid { grid-template-columns: 1fr; }
    .info-bar { flex-direction: column; gap: 0.75rem; align-items: flex-start; }
}
</style>
