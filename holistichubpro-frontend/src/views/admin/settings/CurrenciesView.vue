<template>
    <div>
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-90">Currency List</h1>
            <p class="mt-1 text-sm text-neutral-50">Manage currencies supported by your store.</p>
        </div>

        <AppCard :padding="'none'">
            <!-- Card Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-6 py-4 border-b border-neutral-10">
                <h2 class="text-base font-semibold text-neutral-80">Currencies</h2>
                <div class="flex flex-wrap gap-2">
                    <template v-if="authStore.hasPermission('currencies.create')">
                        <AppButton variant="outlined" color="neutral" size="small" :loading="settingsStore.importing" @click="openImportDialog">
                            Import CSV
                        </AppButton>
                        <AppButton variant="outlined" color="neutral" size="small" :loading="settingsStore.exporting" @click="settingsStore.exportCurrencies()">
                            Export CSV
                        </AppButton>
                        <AppButton variant="filled" color="primary" size="small" @click="openModal()">
                            + Add Currency
                        </AppButton>
                    </template>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 px-6 py-4 border-b border-neutral-10 bg-neutral-5/50">
                <input
                    v-model="settingsStore.currencyFilters.search"
                    type="search"
                    class="block rounded-lg border border-neutral-30 bg-white px-3 py-2 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 w-56"
                    placeholder="Search currencies..."
                    @input="debouncedFetch"
                />
                <select v-model="settingsStore.currencyFilters.status" class="block rounded-lg border border-neutral-30 bg-white px-3 py-2 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 w-36" @change="fetchNow">
                    <option value="">All Statuses</option>
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                </select>
                <select v-model="settingsStore.currencyFilters.per_page" class="block rounded-lg border border-neutral-30 bg-white px-3 py-2 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 w-32" @change="fetchNow">
                    <option :value="10">10 / page</option>
                    <option :value="25">25 / page</option>
                    <option :value="50">50 / page</option>
                    <option :value="100">100 / page</option>
                </select>
            </div>

            <!-- Loading skeleton -->
            <div v-if="settingsStore.loading.currencies" class="p-6 space-y-3 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-10 bg-neutral-10 rounded-lg"></div>
            </div>

            <!-- Table -->
            <AppTable v-else :columns="columns" :data="settingsStore.currencies.data">
                <template #code="{ item }">
                    <span class="rounded-md bg-neutral-10 px-2 py-0.5 font-mono text-xs font-semibold text-neutral-70">
                        {{ item.code }}
                    </span>
                </template>
                <template #symbol="{ item }">
                    <span class="font-semibold text-neutral-80 text-base">{{ item.symbol }}</span>
                </template>
                <template #status="{ item }">
                    <AppBadge :color="item.status ? 'success' : 'gray'" :text="item.status ? 'Active' : 'Inactive'" />
                </template>
                <template #actions="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <AppButton
                            v-if="authStore.hasPermission('currencies.edit')"
                            variant="tonal"
                            color="primary"
                            size="small"
                            @click="openModal(item)"
                        >
                            Edit
                        </AppButton>
                        <AppButton
                            v-if="authStore.hasPermission('currencies.delete')"
                            variant="tonal"
                            color="error"
                            size="small"
                            @click="confirmDelete(item)"
                        >
                            Delete
                        </AppButton>
                    </div>
                </template>
                <template v-if="settingsStore.currencies.meta?.last_page > 1" #footer>
                    <AppPagination
                        :current-page="settingsStore.currencies.meta.current_page"
                        :last-page="settingsStore.currencies.meta.last_page"
                        :total="settingsStore.currencies.meta.total"
                        :from="settingsStore.currencies.meta.from"
                        :to="settingsStore.currencies.meta.to"
                        @change="p => { settingsStore.currencyFilters.page = p; fetchNow() }"
                    />
                </template>
            </AppTable>
        </AppCard>

        <!-- ── Create / Edit Modal ──────────────────────────────────────────── -->
        <AppModal
            :is-open="modalOpen"
            :title="form.id ? 'Edit Currency' : 'Add Currency'"
            :show-confirm="false"
            @close="modalOpen = false"
        >
            <form id="currencyForm" @submit.prevent="handleSubmit" class="space-y-5">
                <AppFormField label="Name" id="curr_name" required :error="errors.name?.[0]">
                    <input
                        id="curr_name"
                        v-model="form.name"
                        type="text"
                        class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed"
                        placeholder="e.g. US Dollar"
                        required
                    />
                </AppFormField>

                <AppFormField
                    label="Code"
                    id="curr_code"
                    required
                    :error="errors.code?.[0]"
                    hint="3 characters, e.g. USD, EUR, GBP"
                >
                    <input
                        id="curr_code"
                        v-model="form.code"
                        type="text"
                        class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed uppercase font-mono tracking-widest"
                        placeholder="USD"
                        maxlength="3"
                        required
                        @input="form.code = form.code.toUpperCase()"
                    />
                </AppFormField>

                <AppFormField label="Symbol" id="curr_symbol" required :error="errors.symbol?.[0]">
                    <input
                        id="curr_symbol"
                        v-model="form.symbol"
                        type="text"
                        class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed"
                        placeholder="$"
                        maxlength="10"
                        required
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
                    form="currencyForm"
                    variant="filled"
                    color="primary"
                    :loading="settingsStore.saving"
                >
                    {{ form.id ? 'Update Currency' : 'Create Currency' }}
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
                Are you sure you want to delete
                <strong class="text-neutral-90">{{ deleteTarget?.name }} ({{ deleteTarget?.code }})</strong>?
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

defineOptions({ name: 'CurrenciesView' })

const settingsStore = useSettingsStore()
const authStore     = useAuthStore()

const columns = [
    { key: 'name',   label: 'Name' },
    { key: 'code',   label: 'Code' },
    { key: 'symbol', label: 'Symbol' },
    { key: 'status', label: 'Status' },
]

// ── Modal state ───────────────────────────────────────────────────────────────
const modalOpen       = ref(false)
const deleteModalOpen = ref(false)
const deleteTarget    = ref(null)
const importInput     = ref(null)
const errors          = reactive({})

const form = reactive({ id: null, name: '', code: '', symbol: '', status: true })

function openModal(currency = null) {
    Object.keys(errors).forEach(k => delete errors[k])
    if (currency) {
        const c = JSON.parse(JSON.stringify(currency))
        Object.assign(form, { id: c.id, name: c.name, code: c.code, symbol: c.symbol, status: c.status })
    } else {
        Object.assign(form, { id: null, name: '', code: '', symbol: '', status: true })
    }
    modalOpen.value = true
}

async function handleSubmit() {
    Object.keys(errors).forEach(k => delete errors[k])
    try {
        const payload = { name: form.name, code: form.code.toUpperCase(), symbol: form.symbol, status: form.status }
        if (form.id) {
            await settingsStore.updateCurrency(form.id, payload)
        } else {
            await settingsStore.createCurrency(payload)
        }
        modalOpen.value = false
    } catch (e) {
        if (e.response?.data?.errors) Object.assign(errors, e.response.data.errors)
    }
}

function confirmDelete(currency) {
    deleteTarget.value = currency
    deleteModalOpen.value = true
}

async function handleDelete() {
    try {
        await settingsStore.deleteCurrency(deleteTarget.value.id)
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
    if (file) await settingsStore.importCurrencies(file)
}

// ── Debounce & fetch ──────────────────────────────────────────────────────────
let debounceTimer = null

function debouncedFetch() {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(fetchNow, 400)
}

function fetchNow() {
    settingsStore.currencyFilters.page = 1
    settingsStore.fetchCurrencies()
}

onMounted(() => settingsStore.fetchCurrencies())
</script>

