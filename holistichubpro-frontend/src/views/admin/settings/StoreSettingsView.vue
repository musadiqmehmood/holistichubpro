<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-90">Store Settings</h1>
            <p class="mt-1 text-sm text-neutral-50">Configure store information and localization preferences.</p>
        </div>

        <!-- Material Design Tabs -->
        <div class="mb-8 border-b border-gray-200">
            <nav class="-mb-px flex gap-8" aria-label="Store settings tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    class="relative pb-4 text-sm font-medium transition-colors"
                    :class="[
                        activeTab === tab.id
                            ? 'text-[var(--primary-color)] border-b-2 border-[var(--primary-color)]'
                            : 'text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <div v-if="settingsStore.loading.store" class="space-y-4 animate-pulse">
            <div class="h-10 bg-neutral-10 rounded-lg"></div>
            <div class="h-10 bg-neutral-10 rounded-lg"></div>
            <div class="h-10 bg-neutral-10 rounded-lg"></div>
        </div>

        <form v-else @submit.prevent="handleSubmit">
            <!-- Tab 1: General -->
            <div v-show="activeTab === 'general'">
                <AppCard>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <!-- All fields remain as before, only toggle is updated to AppToggle -->
                        <AppFormField label="Store Code" id="store_code" required :error="errors.store_code?.[0]">
                            <input id="store_code" v-model="form.store_code" type="text" name="store_code" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="HHP-001" required />
                        </AppFormField>

                        <AppFormField label="Store Name" id="store_name" required :error="errors.store_name?.[0]">
                            <input id="store_name" v-model="form.store_name" type="text" name="store_name" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="My Store" required />
                        </AppFormField>

                        <AppFormField label="Mobile" id="mobile" required :error="errors.mobile?.[0]">
                            <input id="mobile" v-model="form.mobile" type="text" name="mobile" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="+1234567890" required />
                        </AppFormField>

                        <AppFormField label="Email" id="email" required :error="errors.email?.[0]">
                            <input id="email" v-model="form.email" type="email" name="email" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" required />
                        </AppFormField>

                        <AppFormField label="Phone" id="phone" :error="errors.phone?.[0]">
                            <input id="phone" v-model="form.phone" type="text" name="phone" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        </AppFormField>

                        <AppFormField label="GST Number" id="gst_number" :error="errors.gst_number?.[0]">
                            <input id="gst_number" v-model="form.gst_number" type="text" name="gst_number" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        </AppFormField>

                        <AppFormField label="Tax Number" id="tax_number" :error="errors.tax_number?.[0]">
                            <input id="tax_number" v-model="form.tax_number" type="text" name="tax_number" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        </AppFormField>

                        <AppFormField label="PAN Number" id="pan_number" :error="errors.pan_number?.[0]">
                            <input id="pan_number" v-model="form.pan_number" type="text" name="pan_number" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                        </AppFormField>

                        <AppFormField label="Store Website" id="store_website" :error="errors.store_website?.[0]" class="sm:col-span-2">
                            <input id="store_website" v-model="form.store_website" type="url" name="store_website" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" placeholder="https://example.com" />
                        </AppFormField>

                        <AppFormField label="Branch" id="branch_id" required :error="errors.branch_id?.[0]" class="sm:col-span-2">
                            <AppSelect
                                v-model="form.branch_id"
                                :options="branches"
                                option-label="name"
                                option-value="id"
                                placeholder="Select branch..."
                                searchable
                            />
                        </AppFormField>

                        <AppFormField label="Bank Details" id="bank_details" :error="errors.bank_details?.[0]" class="sm:col-span-2">
                            <textarea id="bank_details" v-model="form.bank_details" name="bank_details" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 resize-none" rows="3" placeholder="Bank account details for invoices..." />
                        </AppFormField>

                        <!-- Store Logo -->
                        <AppFormField label="Store Logo" :error="errors.store_logo?.[0]">
                            <div class="space-y-3">
                                <div v-if="storeLogoPreview || storeLogoUrl" class="h-20 w-20 overflow-hidden rounded-xl border border-neutral-20 bg-neutral-5">
                                    <img :src="storeLogoPreview || storeLogoUrl" alt="Store logo" class="h-full w-full object-contain" />
                                </div>
                                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Upload Logo
                                    <input type="file" class="hidden" accept="image/*" @change="e => handleImageChange(e, 'store_logo')" />
                                </label>
                            </div>
                        </AppFormField>

                        <!-- Show Signature Toggle -->
                        <AppFormField label="Show Signature on Invoice" :error="errors.show_signature_on_invoice?.[0]">
                            <AppToggle v-model="form.show_signature_on_invoice">
                                {{ form.show_signature_on_invoice ? 'Enabled' : 'Disabled' }}
                            </AppToggle>
                        </AppFormField>

                        <!-- Signature (only when toggle on) -->
                        <AppFormField v-if="form.show_signature_on_invoice" label="Signature" :error="errors.signature?.[0]">
                            <div class="space-y-3">
                                <div v-if="signaturePreview || signatureUrl" class="h-20 w-40 overflow-hidden rounded-xl border border-neutral-20 bg-neutral-5">
                                    <img :src="signaturePreview || signatureUrl" alt="Signature" class="h-full w-full object-contain" />
                                </div>
                                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-700">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Upload Signature
                                    <input type="file" class="hidden" accept="image/*" @change="e => handleImageChange(e, 'signature')" />
                                </label>
                            </div>
                        </AppFormField>
                    </div>
                </AppCard>
            </div>

            <!-- Tab 2: Localization -->
            <div v-show="activeTab === 'localization'">
                <AppCard>
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <AppFormField label="Timezone" id="timezone" :error="errors.timezone?.[0]" class="sm:col-span-2">
                            <AppSelect v-model="form.timezone" :options="timezones" placeholder="Search timezone..." searchable />
                        </AppFormField>

                        <AppFormField label="Date Format" id="date_format" :error="errors.date_format?.[0]">
                            <AppSelect v-model="form.date_format" :options="dateFormatOptions" option-label="label" option-value="value" placeholder="Select date format..." searchable />
                            <div v-if="form.date_format" class="mt-1 text-xs text-[var(--text-secondary)]">Preview: <span class="font-mono text-[var(--primary-color)]">{{ formatDate(new Date()) }}</span></div>
                        </AppFormField>

                        <AppFormField label="Time Format" id="time_format" :error="errors.time_format?.[0]">
                            <AppSelect v-model="form.time_format" :options="timeFormatOptions" option-label="label" option-value="value" placeholder="Select time format..." searchable />
                            <div v-if="form.time_format" class="mt-1 text-xs text-[var(--text-secondary)]">Preview: <span class="font-mono text-[var(--primary-color)]">{{ formatTime(new Date()) }}</span></div>
                        </AppFormField>

                        <AppFormField label="Currency" id="currency" :error="errors.currency?.[0]">
                            <AppSelect v-model="form.currency" :options="activeCurrencies" option-label="name" option-value="code" placeholder="Search currency..." searchable />
                        </AppFormField>

                        <AppFormField label="Currency Symbol Placement" id="currency_symbol_placement" :error="errors.currency_symbol_placement?.[0]">
                            <AppSelect v-model="form.currency_symbol_placement" :options="placementOptions" option-label="label" option-value="value" placeholder="Select placement..." />
                        </AppFormField>

                        <AppFormField label="Decimals" id="decimals" :error="errors.decimals?.[0]">
                            <AppSelect v-model.number="form.decimals" :options="decimalsOptions" placeholder="Select..." />
                        </AppFormField>

                        <AppFormField label="Decimals for Quantity" id="decimals_for_quantity" :error="errors.decimals_for_quantity?.[0]">
                            <AppSelect v-model.number="form.decimals_for_quantity" :options="decimalsOptions" placeholder="Select..." />
                        </AppFormField>
                    </div>
                </AppCard>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <AppButton type="submit" variant="filled" color="primary" :loading="settingsStore.saving">
                    Save Changes
                </AppButton>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useFormatter } from '@/utils/format'
import api from '@/api/axios'
import AppCard from '@/components/ui/AppCard.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppToggle from '@/components/ui/AppToggle.vue'
import AppSelect from '@/components/ui/AppSelect.vue'

defineOptions({ name: 'StoreSettingsView' })

const settingsStore = useSettingsStore()
const { formatDate, formatTime } = useFormatter()

const activeTab = ref('general')
const tabs = [
    { id: 'general', label: 'General' },
    { id: 'localization', label: 'Localization' },
]

// Static option lists for searchable dropdowns
const dateFormatOptions = [
    { value: 'Y-m-d', label: 'YYYY-MM-DD (2025-01-31)' },
    { value: 'd/m/Y', label: 'DD/MM/YYYY (31/01/2025)' },
    { value: 'm/d/Y', label: 'MM/DD/YYYY (01/31/2025)' },
    { value: 'd-M-Y', label: 'DD-Mon-YYYY (31-Jan-2025)' },
    { value: 'd.m.Y', label: 'DD.MM.YYYY (31.01.2025)' },
    { value: 'M d, Y', label: 'Mon DD, YYYY (Jan 31, 2025)' },
]

const timeFormatOptions = [
    { value: 'H:i', label: '24-hour (13:05)' },
    { value: 'H:i:s', label: '24-hour with seconds (13:05:09)' },
    { value: 'h:i A', label: '12-hour (01:05 PM)' },
]

const placementOptions = [
    { value: 'before', label: 'Before amount ($100)' },
    { value: 'after', label: 'After amount (100$)' },
]

const decimalsOptions = [0, 1, 2, 3]

const branches = ref([])
const timezones = ref([])
const storeLogoPreview = ref(null)
const signaturePreview = ref(null)
const storeLogoFile = ref(null)
const signatureFile = ref(null)
const errors = reactive({})

const storeLogoUrl = computed(() => settingsStore.storeSettings?.store_logo_url ?? null)
const signatureUrl = computed(() => settingsStore.storeSettings?.signature_url ?? null)

const form = reactive({
    store_code: '',
    store_name: '',
    mobile: '',
    email: '',
    phone: '',
    gst_number: '',
    tax_number: '',
    pan_number: '',
    store_website: '',
    show_signature_on_invoice: false,
    bank_details: '',
    branch_id: '',
    timezone: 'UTC',
    date_format: 'Y-m-d',
    time_format: 'H:i:s',
    currency: 'USD',
    currency_symbol_placement: 'before',
    decimals: 2,
    decimals_for_quantity: 2,
})

const activeCurrencies = computed(() => settingsStore.currencies.data?.filter(c => c.status) ?? [])

function updateFormFromStore() {
    const s = settingsStore.storeSettings
    if (s) {
        form.store_code = s.store_code ?? ''
        form.store_name = s.store_name ?? ''
        form.mobile = s.mobile ?? ''
        form.email = s.email ?? ''
        form.phone = s.phone ?? ''
        form.gst_number = s.gst_number ?? ''
        form.tax_number = s.tax_number ?? ''
        form.pan_number = s.pan_number ?? ''
        form.store_website = s.store_website ?? ''
        form.show_signature_on_invoice = s.show_signature_on_invoice ?? false
        form.bank_details = s.bank_details ?? ''
        form.branch_id = s.branch_id ?? ''
        form.timezone = s.timezone ?? 'UTC'
        form.date_format = s.date_format ?? 'Y-m-d'
        form.time_format = s.time_format ?? 'H:i:s'
        form.currency = s.currency ?? 'USD'
        form.currency_symbol_placement = s.currency_symbol_placement ?? 'before'
        form.decimals = s.decimals ?? 2
        form.decimals_for_quantity = s.decimals_for_quantity ?? 2
    }
}

onMounted(async () => {
    await Promise.all([
        settingsStore.fetchStoreSettings(),
        settingsStore.fetchCurrencies(),
        fetchBranches(),
        fetchOptions(),
    ])
    updateFormFromStore()
})

watch(() => settingsStore.storeSettings, () => {
    updateFormFromStore()
    storeLogoPreview.value = null
    signaturePreview.value = null
    storeLogoFile.value = null
    signatureFile.value = null
}, { deep: true })

async function fetchBranches() {
    try {
        const res = await api.get('/admin/branches', { params: { per_page: 200 } })
        branches.value = res.data.data ?? res.data
    } catch {}
}

async function fetchOptions() {
    try {
        const res = await api.get('/admin/settings/options')
        timezones.value = res.data.timezones ?? []
    } catch {}
}

function handleImageChange(event, field) {
    const file = event.target.files[0]
    if (!file) return
    const preview = URL.createObjectURL(file)
    if (field === 'store_logo') {
        if (storeLogoPreview.value) URL.revokeObjectURL(storeLogoPreview.value)
        storeLogoPreview.value = preview
        storeLogoFile.value = file
    } else if (field === 'signature') {
        if (signaturePreview.value) URL.revokeObjectURL(signaturePreview.value)
        signaturePreview.value = preview
        signatureFile.value = file
    }
}

async function handleSubmit() {
    Object.keys(errors).forEach(k => delete errors[k])

    const formData = new FormData()
    Object.entries(form).forEach(([k, v]) => {
        if (v !== null && v !== undefined) {
            formData.append(k, typeof v === 'boolean' ? (v ? '1' : '0') : v)
        }
    })
    if (storeLogoFile.value) formData.append('store_logo', storeLogoFile.value)
    if (signatureFile.value) formData.append('signature', signatureFile.value)

    try {
        await settingsStore.updateStoreSettings(formData)
        await settingsStore.fetchStoreSettings()
        storeLogoFile.value = null
        signatureFile.value = null
        storeLogoPreview.value = null
        signaturePreview.value = null
    } catch (e) {
        if (e.response?.data?.errors) Object.assign(errors, e.response.data.errors)
    }
}
</script>
