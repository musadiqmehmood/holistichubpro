<template>
    <div class="space-y-8">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-semibold text-neutral-90">Taxes &amp; Tax Groups</h1>
            <p class="mt-1 text-sm text-neutral-50">Manage tax rates and tax groups for your business.</p>
        </div>

        <!-- ================================================================ -->
        <!-- Section A: Taxes                                                  -->
        <!-- ================================================================ -->
        <AppCard :padding="'none'">
            <!-- Card Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-6 py-4 border-b border-neutral-10">
                <h2 class="text-base font-semibold text-neutral-80">Taxes</h2>
                <div class="flex flex-wrap gap-2">
                    <template v-if="authStore.hasPermission('taxes.create')">
                        <AppButton variant="outlined" color="neutral" size="small" :loading="settingsStore.importing" @click="openImportDialog('taxes')">
                            Import CSV
                        </AppButton>
                        <AppButton variant="outlined" color="neutral" size="small" :loading="settingsStore.exporting" @click="settingsStore.exportTaxes()">
                            Export CSV
                        </AppButton>
                    </template>
                    <AppButton v-if="authStore.hasPermission('taxes.create')" variant="filled" color="primary" size="small" @click="openTaxModal()">
                        + Add Tax
                    </AppButton>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 px-6 py-4 border-b border-neutral-10 bg-neutral-5/50">
                <input
                    v-model="settingsStore.taxFilters.search"
                    type="search"
                    class="block rounded-lg border border-neutral-30 bg-white px-3 py-2 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 w-56"
                    placeholder="Search taxes..."
                    @input="debouncedFetchTaxes"
                />
                <select v-model="settingsStore.taxFilters.status" class="block rounded-lg border border-neutral-30 bg-white px-3 py-2 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 w-36" @change="fetchTaxesNow">
                    <option value="">All Statuses</option>
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                </select>
                <select v-model="settingsStore.taxFilters.per_page" class="block rounded-lg border border-neutral-30 bg-white px-3 py-2 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 w-32" @change="fetchTaxesNow">
                    <option :value="10">10 / page</option>
                    <option :value="25">25 / page</option>
                    <option :value="50">50 / page</option>
                    <option :value="100">100 / page</option>
                </select>
            </div>

            <!-- Table -->
            <div v-if="settingsStore.loading.taxes" class="p-6 space-y-3 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-10 bg-neutral-10 rounded-lg"></div>
            </div>

            <AppTable v-else :columns="taxColumns" :data="safeTaxes">
                <template #percentage="{ item }">
                    <span class="font-mono text-sm">{{ item.percentage }}%</span>
                </template>
                <template #status="{ item }">
                    <AppBadge :color="item.status ? 'success' : 'gray'" :text="item.status ? 'Active' : 'Inactive'" />
                </template>
                <template #actions="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <AppButton
                            v-if="authStore.hasPermission('taxes.edit')"
                            variant="tonal"
                            color="primary"
                            size="small"
                            @click="openTaxModal(item)"
                        >
                            Edit
                        </AppButton>
                        <AppButton
                            v-if="authStore.hasPermission('taxes.delete')"
                            variant="tonal"
                            color="error"
                            size="small"
                            @click="confirmDelete('tax', item)"
                        >
                            Delete
                        </AppButton>
                    </div>
                </template>
                <template v-if="settingsStore.taxes.meta?.last_page > 1" #footer>
                    <AppPagination
                        :current-page="settingsStore.taxes.meta.current_page"
                        :last-page="settingsStore.taxes.meta.last_page"
                        :total="settingsStore.taxes.meta.total"
                        :from="settingsStore.taxes.meta.from"
                        :to="settingsStore.taxes.meta.to"
                        @change="p => { settingsStore.taxFilters.page = p; fetchTaxesNow() }"
                    />
                </template>
            </AppTable>
        </AppCard>

        <!-- ================================================================ -->
        <!-- Section B: Tax Groups                                             -->
        <!-- ================================================================ -->
        <AppCard :padding="'none'">
            <!-- Card Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-6 py-4 border-b border-neutral-10">
                <h2 class="text-base font-semibold text-neutral-80">Tax Groups</h2>
                <div class="flex flex-wrap gap-2">
                    <AppButton
                        v-if="authStore.hasPermission('tax_groups.create')"
                        variant="filled"
                        color="primary"
                        size="small"
                        @click="openTaxGroupModal()"
                    >
                        + Add Tax Group
                    </AppButton>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 px-6 py-4 border-b border-neutral-10 bg-neutral-5/50">
                <input
                    v-model="settingsStore.taxGroupFilters.search"
                    type="search"
                    class="block rounded-lg border border-neutral-30 bg-white px-3 py-2 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 w-56"
                    placeholder="Search tax groups..."
                    @input="debouncedFetchTaxGroups"
                />
                <select v-model="settingsStore.taxGroupFilters.status" class="block rounded-lg border border-neutral-30 bg-white px-3 py-2 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 w-36" @change="fetchTaxGroupsNow">
                    <option value="">All Statuses</option>
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                </select>
            </div>

            <!-- Table -->
            <div v-if="settingsStore.loading.taxGroups" class="p-6 space-y-3 animate-pulse">
                <div v-for="i in 3" :key="i" class="h-10 bg-neutral-10 rounded-lg"></div>
            </div>

            <AppTable v-else :columns="taxGroupColumns" :data="safeTaxGroups">
                <template #taxes_detail="{ item }">
                    <div class="flex flex-wrap gap-1">
                        <span
                            v-for="tax in (item.taxes_detail ?? [])"
                            :key="tax.id"
                            class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700"
                        >
                            {{ tax.name }} ({{ tax.percentage }}%)
                        </span>
                        <span v-if="!item.taxes_detail?.length" class="text-xs text-neutral-40">—</span>
                    </div>
                </template>
                <template #calculated_percentage="{ item }">
                    <span class="font-mono text-sm font-semibold text-neutral-80">{{ item.calculated_percentage }}%</span>
                </template>
                <template #status="{ item }">
                    <AppBadge :color="item.status ? 'success' : 'gray'" :text="item.status ? 'Active' : 'Inactive'" />
                </template>
                <template #actions="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <AppButton
                            v-if="authStore.hasPermission('tax_groups.edit')"
                            variant="tonal"
                            color="primary"
                            size="small"
                            @click="openTaxGroupModal(item)"
                        >
                            Edit
                        </AppButton>
                        <AppButton
                            v-if="authStore.hasPermission('tax_groups.delete')"
                            variant="tonal"
                            color="error"
                            size="small"
                            @click="confirmDelete('taxGroup', item)"
                        >
                            Delete
                        </AppButton>
                    </div>
                </template>
                <template v-if="settingsStore.taxGroups.meta?.last_page > 1" #footer>
                    <AppPagination
                        :current-page="settingsStore.taxGroups.meta.current_page"
                        :last-page="settingsStore.taxGroups.meta.last_page"
                        :total="settingsStore.taxGroups.meta.total"
                        :from="settingsStore.taxGroups.meta.from"
                        :to="settingsStore.taxGroups.meta.to"
                        @change="p => { settingsStore.taxGroupFilters.page = p; fetchTaxGroupsNow() }"
                    />
                </template>
            </AppTable>
        </AppCard>

        <!-- ================================================================ -->
        <!-- Tax Create / Edit Modal                                           -->
        <!-- ================================================================ -->
        <AppModal
            :is-open="taxModalOpen"
            :title="taxForm.id ? 'Edit Tax' : 'Add Tax'"
            :show-confirm="false"
            @close="taxModalOpen = false"
        >
            <form id="taxForm" @submit.prevent="handleTaxSubmit" class="space-y-5">
                <AppFormField label="Name" id="tax_name" required :error="taxErrors.name?.[0]">
                    <input
                        id="tax_name"
                        v-model="taxForm.name"
                        type="text"
                        class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed"
                        placeholder="e.g. VAT 10%"
                        required
                    />
                </AppFormField>

                <AppFormField label="Percentage (%)" id="tax_percentage" required :error="taxErrors.percentage?.[0]">
                    <input
                        id="tax_percentage"
                        v-model.number="taxForm.percentage"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed"
                        required
                    />
                </AppFormField>

                <AppFormField label="Status">
                    <label class="inline-flex cursor-pointer items-center gap-3">
                        <div class="relative">
                            <input type="checkbox" v-model="taxForm.status" class="peer sr-only" />
                            <div class="h-6 w-11 rounded-full bg-neutral-20 transition-colors peer-checked:bg-primary-600"></div>
                            <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm text-neutral-70">{{ taxForm.status ? 'Active' : 'Inactive' }}</span>
                    </label>
                </AppFormField>
            </form>

            <template #actions>
                <AppButton variant="tonal" color="neutral" @click="taxModalOpen = false">Cancel</AppButton>
                <AppButton
                    type="submit"
                    form="taxForm"
                    variant="filled"
                    color="primary"
                    :loading="settingsStore.saving"
                >
                    {{ taxForm.id ? 'Update Tax' : 'Create Tax' }}
                </AppButton>
            </template>
        </AppModal>

        <!-- ================================================================ -->
        <!-- Tax Group Create / Edit Modal                                     -->
        <!-- ================================================================ -->
        <AppModal
            :is-open="taxGroupModalOpen"
            :title="taxGroupForm.id ? 'Edit Tax Group' : 'Add Tax Group'"
            :show-confirm="false"
            @close="taxGroupModalOpen = false"
        >
            <form id="taxGroupForm" @submit.prevent="handleTaxGroupSubmit" class="space-y-5">
                <AppFormField label="Group Name" id="tg_name" required :error="taxGroupErrors.name?.[0]">
                    <input
                        id="tg_name"
                        v-model="taxGroupForm.name"
                        type="text"
                        class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed"
                        placeholder="e.g. Standard Tax"
                        required
                    />
                </AppFormField>

                <AppFormField label="Select Taxes" required :error="taxGroupErrors.tax_ids?.[0]">
                    <div class="max-h-48 overflow-y-auto rounded-lg border border-neutral-20 p-3 space-y-2 bg-neutral-5/50">
                        <label
                            v-for="tax in activeTaxes"
                            :key="tax.id"
                            class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-1.5 hover:bg-white transition-colors"
                        >
                            <input
                                type="checkbox"
                                :value="tax.id"
                                v-model="taxGroupForm.tax_ids"
                                class="h-4 w-4 rounded border-neutral-30 text-primary-600 focus:ring-primary-500"
                            />
                            <span class="text-sm text-neutral-70">{{ tax.name }} ({{ tax.percentage }}%)</span>
                        </label>
                        <p v-if="!activeTaxes.length" class="text-sm text-neutral-40 py-2 text-center">No active taxes available.</p>
                    </div>
                    <div class="mt-2 flex items-center gap-2 text-sm">
                        <span class="text-neutral-50">Computed Total:</span>
                        <span class="font-semibold text-neutral-90 font-mono">{{ computedTaxGroupPercentage }}%</span>
                    </div>
                </AppFormField>

                <AppFormField label="Status">
                    <label class="inline-flex cursor-pointer items-center gap-3">
                        <div class="relative">
                            <input type="checkbox" v-model="taxGroupForm.status" class="peer sr-only" />
                            <div class="h-6 w-11 rounded-full bg-neutral-20 transition-colors peer-checked:bg-primary-600"></div>
                            <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm text-neutral-70">{{ taxGroupForm.status ? 'Active' : 'Inactive' }}</span>
                    </label>
                </AppFormField>
            </form>

            <template #actions>
                <AppButton variant="tonal" color="neutral" @click="taxGroupModalOpen = false">Cancel</AppButton>
                <AppButton
                    type="submit"
                    form="taxGroupForm"
                    variant="filled"
                    color="primary"
                    :loading="settingsStore.saving"
                >
                    {{ taxGroupForm.id ? 'Update Tax Group' : 'Create Tax Group' }}
                </AppButton>
            </template>
        </AppModal>

        <!-- ================================================================ -->
        <!-- Delete Confirmation Modal                                         -->
        <!-- ================================================================ -->
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
import { ref, reactive, computed, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore }     from '@/stores/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppTable from '@/components/ui/AppTable.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppPagination from '@/components/ui/AppPagination.vue'

defineOptions({ name: 'TaxListView' })

const settingsStore = useSettingsStore()
const authStore     = useAuthStore()

// ── Null-safe table data (backend may return non-array or include null items) ─
const safeTaxes     = computed(() => Array.isArray(settingsStore.taxes.data)     ? settingsStore.taxes.data.filter(Boolean)     : [])
const safeTaxGroups = computed(() => Array.isArray(settingsStore.taxGroups.data) ? settingsStore.taxGroups.data.filter(Boolean) : [])

// ── Table columns ────────────────────────────────────────────────────────────
const taxColumns = [
    { key: 'name',       label: 'Name' },
    { key: 'percentage', label: 'Percentage (%)' },
    { key: 'status',     label: 'Status' },
]

const taxGroupColumns = [
    { key: 'name',                   label: 'Group Name' },
    { key: 'taxes_detail',           label: 'Sub-Taxes' },
    { key: 'calculated_percentage',  label: 'Total %' },
    { key: 'status',                 label: 'Status' },
]

// ── Tax Modal ─────────────────────────────────────────────────────────────────
const taxModalOpen = ref(false)
const taxErrors    = reactive({})
const taxForm      = reactive({ id: null, name: '', percentage: 0, status: true })

function openTaxModal(tax = null) {
    Object.keys(taxErrors).forEach(k => delete taxErrors[k])
    if (tax) {
        const c = JSON.parse(JSON.stringify(tax))
        Object.assign(taxForm, { id: c.id, name: c.name, percentage: c.percentage, status: c.status })
    } else {
        Object.assign(taxForm, { id: null, name: '', percentage: 0, status: true })
    }
    taxModalOpen.value = true
}

async function handleTaxSubmit() {
    Object.keys(taxErrors).forEach(k => delete taxErrors[k])
    try {
        const payload = { name: taxForm.name, percentage: taxForm.percentage, status: taxForm.status }
        if (taxForm.id) {
            await settingsStore.updateTax(taxForm.id, payload)
        } else {
            await settingsStore.createTax(payload)
        }
        taxModalOpen.value = false
    } catch (e) {
        if (e.response?.data?.errors) Object.assign(taxErrors, e.response.data.errors)
    }
}

// ── Tax Group Modal ───────────────────────────────────────────────────────────
const taxGroupModalOpen = ref(false)
const taxGroupErrors    = reactive({})
const taxGroupForm      = reactive({ id: null, name: '', tax_ids: [], status: true })

const activeTaxes = computed(() => safeTaxes.value.filter(t => t.status))

const computedTaxGroupPercentage = computed(() => {
    const selected = activeTaxes.value.filter(t => taxGroupForm.tax_ids.includes(t.id))
    return selected.reduce((sum, t) => sum + parseFloat(t.percentage ?? 0), 0).toFixed(2)
})

function openTaxGroupModal(group = null) {
    Object.keys(taxGroupErrors).forEach(k => delete taxGroupErrors[k])
    if (group) {
        const c = JSON.parse(JSON.stringify(group))
        Object.assign(taxGroupForm, { id: c.id, name: c.name, tax_ids: c.tax_ids ?? [], status: c.status })
    } else {
        Object.assign(taxGroupForm, { id: null, name: '', tax_ids: [], status: true })
    }
    taxGroupModalOpen.value = true
}

async function handleTaxGroupSubmit() {
    Object.keys(taxGroupErrors).forEach(k => delete taxGroupErrors[k])
    try {
        const payload = { name: taxGroupForm.name, tax_ids: taxGroupForm.tax_ids, status: taxGroupForm.status }
        if (taxGroupForm.id) {
            await settingsStore.updateTaxGroup(taxGroupForm.id, payload)
        } else {
            await settingsStore.createTaxGroup(payload)
        }
        taxGroupModalOpen.value = false
    } catch (e) {
        if (e.response?.data?.errors) Object.assign(taxGroupErrors, e.response.data.errors)
    }
}

// ── Delete ────────────────────────────────────────────────────────────────────
const deleteModalOpen = ref(false)
const deleteTarget    = ref(null)
const deleteType      = ref('')

function confirmDelete(type, item) {
    deleteType.value   = type
    deleteTarget.value = item
    deleteModalOpen.value = true
}

async function handleDelete() {
    try {
        if (deleteType.value === 'tax') {
            await settingsStore.deleteTax(deleteTarget.value.id)
        } else if (deleteType.value === 'taxGroup') {
            await settingsStore.deleteTaxGroup(deleteTarget.value.id)
        }
        deleteModalOpen.value = false
    } catch {}
}

// ── Import ────────────────────────────────────────────────────────────────────
const importInput  = ref(null)
const importTarget = ref('')

function openImportDialog(target) {
    importTarget.value = target
    importInput.value.value = ''
    importInput.value.click()
}

async function handleImportFile(event) {
    const file = event.target.files[0]
    if (!file) return
    if (importTarget.value === 'taxes') await settingsStore.importTaxes(file)
}

// ── Debounce & fetch ──────────────────────────────────────────────────────────
let taxTimer      = null
let taxGroupTimer = null

function debouncedFetchTaxes() {
    clearTimeout(taxTimer)
    taxTimer = setTimeout(fetchTaxesNow, 400)
}

function debouncedFetchTaxGroups() {
    clearTimeout(taxGroupTimer)
    taxGroupTimer = setTimeout(fetchTaxGroupsNow, 400)
}

function fetchTaxesNow() {
    settingsStore.taxFilters.page = 1
    settingsStore.fetchTaxes()
}

function fetchTaxGroupsNow() {
    settingsStore.taxGroupFilters.page = 1
    settingsStore.fetchTaxGroups()
}

onMounted(async () => {
    await Promise.all([
        settingsStore.fetchTaxes(),
        settingsStore.fetchTaxGroups(),
    ])
})
</script>

