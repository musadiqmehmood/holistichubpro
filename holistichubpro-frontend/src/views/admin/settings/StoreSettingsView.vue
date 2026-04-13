<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-90">Store Settings</h1>
            <p class="mt-1 text-sm text-neutral-60">Configure store information and localization preferences.</p>
        </div>

        <div class="mb-6 border-b border-neutral-20">
            <nav class="-mb-px flex gap-6">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    class="whitespace-nowrap border-b-2 pb-3 px-1 text-sm font-medium transition-colors"
                    :class="[
                        activeTab === tab.id
                            ? 'border-primary-600 text-primary-700'
                            : 'border-transparent text-neutral-60 hover:border-neutral-30 hover:text-neutral-70',
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
                            <select id="branch_id" name="branch_id" v-model="form.branch_id" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20" required>
                                <option value="">Select branch</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
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

                        <!-- Show Signature on Invoice Toggle -->
                        <AppFormField label="Show Signature on Invoice" :error="errors.show_signature_on_invoice?.[0]">
                            <label class="inline-flex cursor-pointer items-center gap-3">
                                <div class="relative">
                                    <input type="checkbox" v-model="form.show_signature_on_invoice" name="show_signature_on_invoice" class="peer sr-only" />
                                    <div class="h-6 w-11 rounded-full bg-neutral-20 transition-colors peer-checked:bg-primary-600"></div>
                                    <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></div>
                                </div>
                                <span class="text-sm text-neutral-70">{{ form.show_signature_on_invoice ? 'Enabled' : 'Disabled' }}</span>
                            </label>
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
                            <select id="timezone" name="timezone" v-model="form.timezone" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option v-for="tz in timezones" :key="tz" :value="tz">{{ tz }}</option>
                            </select>
                        </AppFormField>

                        <AppFormField label="Date Format" id="date_format" :error="errors.date_format?.[0]">
                            <select id="date_format" name="date_format" v-model="form.date_format" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option value="Y-m-d">YYYY-MM-DD (2026-01-31)</option>
                                <option value="d/m/Y">DD/MM/YYYY (31/01/2026)</option>
                                <option value="m/d/Y">MM/DD/YYYY (01/31/2026)</option>
                                <option value="d-M-Y">DD-Mon-YYYY (31-Jan-2026)</option>
                            </select>
                        </AppFormField>

                        <AppFormField label="Time Format" id="time_format" :error="errors.time_format?.[0]">
                            <select id="time_format" name="time_format" v-model="form.time_format" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option value="H:i:s">24 Hour (14:30:00)</option>
                                <option value="H:i">24 Hour Short (14:30)</option>
                                <option value="h:i A">12 Hour (02:30 PM)</option>
                            </select>
                        </AppFormField>

                        <AppFormField label="Currency" id="currency" :error="errors.currency?.[0]">
                            <select id="currency" name="currency" v-model="form.currency" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option v-for="c in activeCurrencies" :key="c.code" :value="c.code">
                                    {{ c.name }} ({{ c.code }})
                                </option>
                            </select>
                        </AppFormField>

                        <AppFormField label="Currency Symbol Placement" id="currency_symbol_placement" :error="errors.currency_symbol_placement?.[0]">
                            <select id="currency_symbol_placement" name="currency_symbol_placement" v-model="form.currency_symbol_placement" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option value="before">Before Amount ($100)</option>
                                <option value="after">After Amount (100$)</option>
                            </select>
                        </AppFormField>

                        <AppFormField label="Decimals" id="decimals" :error="errors.decimals?.[0]">
                            <select id="decimals" name="decimals" v-model.number="form.decimals" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option :value="0">0</option>
                                <option :value="1">1</option>
                                <option :value="2">2</option>
                                <option :value="3">3</option>
                            </select>
                        </AppFormField>

                        <AppFormField label="Decimals for Quantity" id="decimals_for_quantity" :error="errors.decimals_for_quantity?.[0]">
                            <select id="decimals_for_quantity" name="decimals_for_quantity" v-model.number="form.decimals_for_quantity" class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                <option :value="0">0</option>
                                <option :value="1">1</option>
                                <option :value="2">2</option>
                                <option :value="3">3</option>
                            </select>
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
import api from '@/api/axios'
import AppCard from '@/components/ui/AppCard.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppButton from '@/components/ui/AppButton.vue'

defineOptions({ name: 'StoreSettingsView' })

const settingsStore = useSettingsStore()

const activeTab = ref('general')
const tabs = [
    { id: 'general', label: 'General' },
    { id: 'localization', label: 'Localization' },
]

const branches = ref([])
const timezones = ref([])
const storeLogoPreview = ref(null)
const signaturePreview = ref(null)
const storeLogoFile = ref(null)
const signatureFile = ref(null)
const errors = reactive({})

// Computed URLs – these will automatically update when storeSettings changes
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

// Populate form from store data
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

// Watch for store changes (e.g., after update)
watch(() => settingsStore.storeSettings, () => {
    updateFormFromStore()
    // Clear previews after successful save
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
        // The store will be updated via the watch, but we also re-fetch to be safe
        await settingsStore.fetchStoreSettings()
        // Clear file inputs and previews
        storeLogoFile.value = null
        signatureFile.value = null
        storeLogoPreview.value = null
        signaturePreview.value = null
    } catch (e) {
        if (e.response?.data?.errors) Object.assign(errors, e.response.data.errors)
    }
}
</script>
