<template>
    <div>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-headline-medium font-bold">Branches</h1>
            <AppButton v-if="authStore.hasPermission('branches.create')" @click="openCreateModal" variant="filled">
                Add Branch
            </AppButton>
        </div>

        <div v-if="loading" class="flex justify-center p-12">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-200 border-t-primary-600"></div>
        </div>

        <AppCard v-else-if="error" class="p-12 text-center">
            <p class="text-error mb-4">{{ error }}</p>
            <AppButton @click="fetchBranches" variant="filled">Retry</AppButton>
        </AppCard>

        <AppCard v-else-if="branches.length === 0" class="p-12 text-center">
            <p class="text-neutral-50">No branches found</p>
        </AppCard>

        <AppCard v-else>
            <AppTable :columns="columns" :data="branches">
                <template #name="{ item }">
                    <div class="font-medium">{{ item.name }}</div>
                    <div v-if="!item.is_active" class="text-xs text-warning mt-1">Inactive</div>
                </template>

                <template #contact="{ item }">
                    <div>{{ item.phone || '—' }}</div>
                    <div class="text-xs text-neutral-50">{{ item.email || '—' }}</div>
                </template>

                <template #location="{ item }">
                    <div>{{ item.city }}, {{ item.state }}</div>
                    <div class="text-xs text-neutral-50">{{ item.address }}</div>
                </template>

                <template #hours="{ item }">
                    <div>{{ item.opening_time }} - {{ item.closing_time }}</div>
                    <div class="text-xs text-neutral-50">{{ item.working_days?.slice(0,3).join(', ') }}</div>
                </template>

                <template #status="{ item }">
                    <AppBadge :text="item.is_active ? 'Active' : 'Inactive'" :color="item.is_active ? 'success' : 'gray'" />
                </template>

                <template #actions="{ item }">
                    <div class="flex gap-2">
                        <AppButton
                            v-if="authStore.hasPermission('branches.edit')"
                            size="small"
                            variant="text"
                            @click="editBranch(item)"
                        >
                            Edit
                        </AppButton>
                        <AppButton
                            v-if="authStore.hasPermission('branches.delete')"
                            size="small"
                            variant="text"
                            class="text-error"
                            @click="confirmDelete(item)"
                            :disabled="item.users_count > 0"
                            :title="item.users_count > 0 ? 'Cannot delete branch with assigned users' : ''"
                        >
                            Delete
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
                @change="changePage"
            />
        </AppCard>

        <!-- Create/Edit Modal (unchanged) -->
        <AppModal
            :is-open="showModal"
            :title="editingBranch ? 'Edit Branch' : 'Create Branch'"
            :loading="saving"
            @close="closeModal"
            @confirm="handleSubmit"
        >
            <form @submit.prevent class="space-y-4">
                <!-- form fields – same as before -->
                <AppFormField label="Name" required :error="errors.name">
                    <input v-model="form.name" type="text" required class="w-full border rounded-lg px-4 py-2" />
                </AppFormField>

                <AppFormField label="Email" :error="errors.email">
                    <input v-model="form.email" type="email" class="w-full border rounded-lg px-4 py-2" />
                </AppFormField>

                <AppFormField label="Phone" :error="errors.phone">
                    <input v-model="form.phone" type="text" class="w-full border rounded-lg px-4 py-2" />
                </AppFormField>

                <AppFormField label="Address" :error="errors.address">
                    <textarea v-model="form.address" rows="2" class="w-full border rounded-lg px-4 py-2"></textarea>
                </AppFormField>

                <div class="grid grid-cols-2 gap-4">
                    <AppFormField label="City" :error="errors.city">
                        <input v-model="form.city" type="text" class="w-full border rounded-lg px-4 py-2" />
                    </AppFormField>
                    <AppFormField label="State" :error="errors.state">
                        <input v-model="form.state" type="text" class="w-full border rounded-lg px-4 py-2" />
                    </AppFormField>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <AppFormField label="Zip Code" :error="errors.zip_code">
                        <input v-model="form.zip_code" type="text" class="w-full border rounded-lg px-4 py-2" />
                    </AppFormField>
                    <AppFormField label="Country" :error="errors.country">
                        <input v-model="form.country" type="text" class="w-full border rounded-lg px-4 py-2" />
                    </AppFormField>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <AppFormField label="Opening Time">
                        <input v-model="form.opening_time" type="time" class="w-full border rounded-lg px-4 py-2" />
                    </AppFormField>
                    <AppFormField label="Closing Time">
                        <input v-model="form.closing_time" type="time" class="w-full border rounded-lg px-4 py-2" />
                    </AppFormField>
                </div>

                <AppFormField label="Working Days">
                    <div class="grid grid-cols-4 gap-2">
                        <label v-for="day in weekDays" :key="day" class="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                :value="day"
                                v-model="form.working_days"
                                class="rounded border-neutral-30 text-primary-600"
                            />
                            {{ day.slice(0,3) }}
                        </label>
                    </div>
                </AppFormField>

                <AppFormField label="Status">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-neutral-30 text-primary-600" />
                        <span>Active</span>
                    </label>
                </AppFormField>
            </form>
        </AppModal>

        <!-- Delete Confirmation Modal -->
        <AppModal
            :is-open="showDeleteModal"
            title="Confirm Delete"
            confirm-text="Delete"
            :loading="deleting"
            @close="showDeleteModal = false"
            @confirm="deleteBranch"
        >
            <p>Delete branch <strong>"{{ branchToDelete?.name }}"</strong>? This cannot be undone.</p>
            <p v-if="branchToDelete?.users_count > 0" class="text-error text-sm mt-2">
                Cannot delete: branch has {{ branchToDelete.users_count }} users assigned.
            </p>
            <p v-else-if="branchToDelete && branchToDelete.users_count === undefined" class="text-warning text-sm mt-2">
                Note: User count not available. Deletion will be prevented by server if users exist.
            </p>
        </AppModal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { branchesApi } from '@/api/branches'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppTable from '@/components/ui/AppTable.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppPagination from '@/components/ui/AppPagination.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppFormField from '@/components/ui/AppFormField.vue'

const authStore = useAuthStore()
const uiStore = useUiStore()

const branches = ref([])
const loading = ref(false)
const error = ref(null)
const pagination = ref({ current_page: 1, last_page: 1, total: 0, from: 0, to: 0 })

const showModal = ref(false)
const editingBranch = ref(null)
const saving = ref(false)
const errors = ref({})

const showDeleteModal = ref(false)
const branchToDelete = ref(null)
const deleting = ref(false)

const form = ref({
    name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    zip_code: '',
    country: 'Pakistan',
    opening_time: '',
    closing_time: '',
    working_days: [],
    is_active: true,
})

const weekDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']

const columns = [
    { key: 'name', label: 'Branch' },
    { key: 'contact', label: 'Contact' },
    { key: 'location', label: 'Location' },
    { key: 'hours', label: 'Hours' },
    { key: 'status', label: 'Status' },
]

onMounted(() => {
    fetchBranches()
})

const fetchBranches = async (page = 1) => {
    loading.value = true
    error.value = null
    try {
        const params = { page, per_page: pagination.value.per_page || 10 }
        const response = await branchesApi.getAll(params)
        const data = response.data

        // Laravel paginator structure: { data: [], current_page, last_page, total, from, to, per_page }
        branches.value = data.data || []
        pagination.value = {
            current_page: data.current_page || 1,
            last_page: data.last_page || 1,
            total: data.total || 0,
            from: data.from || 0,
            to: data.to || 0,
            per_page: data.per_page || 10,
        }
    } catch (err) {
        error.value = err.response?.data?.message || err.message
        if (err.response?.status === 401) authStore.logout()
    } finally {
        loading.value = false
    }
}

const changePage = (page) => {
    pagination.value.current_page = page
    fetchBranches(page)
}

const openCreateModal = () => {
    editingBranch.value = null
    form.value = {
        name: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        state: '',
        zip_code: '',
        country: 'Pakistan',
        opening_time: '',
        closing_time: '',
        working_days: [],
        is_active: true,
    }
    errors.value = {}
    showModal.value = true
}

const editBranch = (branch) => {
    editingBranch.value = branch
    const clonedBranch = JSON.parse(JSON.stringify(branch))
    form.value = { ...clonedBranch, working_days: clonedBranch.working_days || [] }
    errors.value = {}
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    editingBranch.value = null
}

const handleSubmit = async () => {
    saving.value = true
    errors.value = {}
    try {
        const payload = {
            ...form.value,
            opening_time: form.value.opening_time === '' ? null : form.value.opening_time,
            closing_time: form.value.closing_time === '' ? null : form.value.closing_time,
        }

        if (editingBranch.value) {
            await branchesApi.update(editingBranch.value.id, payload)
            uiStore.addNotification({ type: 'success', message: 'Branch updated successfully' })
        } else {
            await branchesApi.create(payload)
            uiStore.addNotification({ type: 'success', message: 'Branch created successfully' })
        }
        await fetchBranches(pagination.value.current_page)
        closeModal()
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors
        } else {
            const errorMsg = err.response?.data?.message || 'Failed to save branch'
            uiStore.addNotification({ type: 'error', message: errorMsg })
        }
    } finally {
        saving.value = false
    }
}

const confirmDelete = (branch) => {
    branchToDelete.value = branch
    showDeleteModal.value = true
}

const deleteBranch = async () => {
    if (!branchToDelete.value) return
    deleting.value = true
    try {
        await branchesApi.delete(branchToDelete.value.id)
        uiStore.addNotification({ type: 'success', message: 'Branch deleted successfully' })
        await fetchBranches(pagination.value.current_page)
        showDeleteModal.value = false
        branchToDelete.value = null
    } catch (err) {
        const errorMsg = err.response?.data?.message || 'Failed to delete branch'
        uiStore.addNotification({ type: 'error', message: errorMsg })
    } finally {
        deleting.value = false
    }
}
</script>
