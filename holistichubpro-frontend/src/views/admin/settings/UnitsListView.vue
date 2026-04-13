<template>
    <div>
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-90">Units List</h1>
            <p class="mt-1 text-sm text-neutral-50">Manage units of measure for products and services.</p>
        </div>

        <AppCard :padding="'none'">
            <!-- Card Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-6 py-4 border-b border-neutral-10">
                <h2 class="text-base font-semibold text-neutral-80">Units</h2>
                <div class="flex flex-wrap gap-2">
                    <template v-if="authStore.hasPermission('units.create')">
                        <AppButton variant="outlined" color="neutral" size="small" :loading="settingsStore.importing" @click="openImportDialog">
                            Import CSV
                        </AppButton>
                        <AppButton variant="outlined" color="neutral" size="small" :loading="settingsStore.exporting" @click="settingsStore.exportUnits()">
                            Export CSV
                        </AppButton>
                        <AppButton variant="filled" color="primary" size="small" @click="openModal()">
                            + Add Unit
                        </AppButton>
                    </template>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 px-6 py-4 border-b border-neutral-10 bg-neutral-5/50">
                <input
                    v-model="settingsStore.unitFilters.search"
                    type="search"
                    class="block rounded-lg border border-neutral-30 bg-white px-3 py-2 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 w-56"
                    placeholder="Search units..."
                    @input="debouncedFetch"
                />
                <select v-model="settingsStore.unitFilters.status" class="block rounded-lg border border-neutral-30 bg-white px-3 py-2 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 w-36" @change="fetchNow">
                    <option value="">All Statuses</option>
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                </select>
                <select v-model="settingsStore.unitFilters.per_page" class="block rounded-lg border border-neutral-30 bg-white px-3 py-2 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 w-32" @change="fetchNow">
                    <option :value="10">10 / page</option>
                    <option :value="25">25 / page</option>
                    <option :value="50">50 / page</option>
                    <option :value="100">100 / page</option>
                </select>
            </div>

            <!-- Loading skeleton -->
            <div v-if="settingsStore.loading.units" class="p-6 space-y-3 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-10 bg-neutral-10 rounded-lg"></div>
            </div>

            <!-- Table -->
            <AppTable v-else :columns="columns" :data="settingsStore.units.data">
                <template #description="{ item }">
                    <span class="text-neutral-50 text-sm">{{ item.description || '—' }}</span>
                </template>
                <template #status="{ item }">
                    <AppBadge :color="item.status ? 'success' : 'gray'" :text="item.status ? 'Active' : 'Inactive'" />
                </template>
                <template #actions="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <AppButton
                            v-if="authStore.hasPermission('units.edit')"
                            variant="tonal"
                            color="primary"
                            size="small"
                            @click="openModal(item)"
                        >
                            Edit
                        </AppButton>
                        <AppButton
                            v-if="authStore.hasPermission('units.delete')"
                            variant="tonal"
                            color="error"
                            size="small"
                            @click="confirmDelete(item)"
                        >
                            Delete
                        </AppButton>
                    </div>
                </template>
                <template v-if="settingsStore.units.meta?.last_page > 1" #footer>
                    <AppPagination
                        :current-page="settingsStore.units.meta.current_page"
                        :last-page="settingsStore.units.meta.last_page"
                        :total="settingsStore.units.meta.total"
                        :from="settingsStore.units.meta.from"
                        :to="settingsStore.units.meta.to"
                        @change="p => { settingsStore.unitFilters.page = p; fetchNow() }"
                    />
                </template>
            </AppTable>
        </AppCard>

        <!-- ── Create / Edit Modal ──────────────────────────────────────────── -->
        <AppModal
            :is-open="modalOpen"
            :title="form.id ? 'Edit Unit' : 'Add Unit'"
            :show-confirm="false"
            @close="modalOpen = false"
        >
            <form id="unitForm" @submit.prevent="handleSubmit" class="space-y-5">
                <AppFormField label="Name" id="unit_name" required :error="errors.name?.[0]">
                    <input
                        id="unit_name"
                        v-model="form.name"
                        type="text"
                        class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed"
                        placeholder="e.g. Piece, Kilogram"
                        required
                    />
                </AppFormField>

                <AppFormField label="Description" id="unit_desc" :error="errors.description?.[0]">
                    <textarea
                        id="unit_desc"
                        v-model="form.description"
                        class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed resize-none"
                        rows="3"
                        placeholder="Optional description..."
                    />
                </AppFormField>

                <AppFormField label="Status">
                    <label class="inline-flex cursor-pointer items-center gap-3">
                        <div class="relative">
                            <input type="checkbox" v-model="form.status" class="peer sr-only" />
                            <div class="h-6 w-11 rounded-full bg-neutral-20 transition-colors peer-checked:bg-primary-600"></div>
                            <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm text-neutral-70">{{ form.status ? 'Active' : 'Inactive' }}</span>
                    </label>
                </AppFormField>
            </form>

            <template #actions>
                <AppButton variant="tonal" color="neutral" @click="modalOpen = false">Cancel</AppButton>
                <AppButton
                    type="submit"
                    form="unitForm"
                    variant="filled"
                    color="primary"
                    :loading="settingsStore.saving"
                >
                    {{ form.id ? 'Update Unit' : 'Create Unit' }}
                </AppButton>
            </template>
        </AppModal>

        <!-- ── Delete Confirmation Modal ────────────────────────────────────── -->
        <AppModal
            :is-open="deleteModalOpen"
            title="Confirm Delete"
            confirm-text="Delete"
            confirm-color="error"
            :loading="settingsStore.saving"
            @close="deleteModalOpen = false"
            @confirm="handleDelete"
        >
            <p class="text-sm text-neutral-60">
                Are you sure you want to delete <strong class="text-neutral-90">{{ deleteTarget?.name }}</strong>?
                This action cannot be undone.
            </p>
        </AppModal>

        <!-- Hidden CSV import input -->
        <input ref="importInput" type="file" class="hidden" accept=".csv,.txt" @change="handleImportFile" />
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppTable from '@/components/ui/AppTable.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppPagination from '@/components/ui/AppPagination.vue'

defineOptions({ name: 'UnitsListView' })

const settingsStore = useSettingsStore()
const authStore     = useAuthStore()

const columns = [
    { key: 'name',        label: 'Name' },
    { key: 'description', label: 'Description' },
    { key: 'status',      label: 'Status' },
]

// ── Modal state ───────────────────────────────────────────────────────────────
const modalOpen       = ref(false)
const deleteModalOpen = ref(false)
const deleteTarget    = ref(null)
const importInput     = ref(null)
const errors          = reactive({})

const form = reactive({ id: null, name: '', description: '', status: true })

function openModal(unit = null) {
    Object.keys(errors).forEach(k => delete errors[k])
    if (unit) {
        const c = JSON.parse(JSON.stringify(unit))
        Object.assign(form, { id: c.id, name: c.name, description: c.description ?? '', status: c.status })
    } else {
        Object.assign(form, { id: null, name: '', description: '', status: true })
    }
    modalOpen.value = true
}

async function handleSubmit() {
    Object.keys(errors).forEach(k => delete errors[k])
    try {
        const payload = { name: form.name, description: form.description, status: form.status }
        if (form.id) {
            await settingsStore.updateUnit(form.id, payload)
        } else {
            await settingsStore.createUnit(payload)
        }
        modalOpen.value = false
    } catch (e) {
        if (e.response?.data?.errors) Object.assign(errors, e.response.data.errors)
    }
}

function confirmDelete(unit) {
    deleteTarget.value = unit
    deleteModalOpen.value = true
}

async function handleDelete() {
    try {
        await settingsStore.deleteUnit(deleteTarget.value.id)
        deleteModalOpen.value = false
    } catch {}
}

// ── Import ────────────────────────────────────────────────────────────────────
function openImportDialog() {
    importInput.value.value = ''
    importInput.value.click()
}

async function handleImportFile(event) {
    const file = event.target.files[0]
    if (file) await settingsStore.importUnits(file)
}

// ── Debounce & fetch ──────────────────────────────────────────────────────────
let debounceTimer = null

function debouncedFetch() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(fetchNow, 400)
}

function fetchNow() {
    settingsStore.unitFilters.page = 1
    settingsStore.fetchUnits()
}

onMounted(() => settingsStore.fetchUnits())
</script>

