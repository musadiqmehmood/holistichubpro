// src/stores/settings.js
import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import { settingsApi, downloadCsv } from '@/api/settings'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth' // ADDED: To access user branch context

export const useSettingsStore = defineStore('settings', () => {
    const uiStore = useUiStore()
    const authStore = useAuthStore() // ADDED

    // Singleton settings
    // CHANGE: Initialize from localStorage so the UI (Logo, Name, Formats)
    // updates immediately on page load without waiting for API.
    const siteSettings  = ref(JSON.parse(localStorage.getItem('site_settings')) || {})
    const storeSettings = ref(JSON.parse(localStorage.getItem('store_settings')) || {})
    const smtpSettings  = ref({})

    // Resource lists
    const taxes        = ref({ data: [], meta: {} })
    const taxGroups    = ref({ data: [], meta: {} })
    const units        = ref({ data: [], meta: {} })
    const paymentTypes = ref({ data: [], meta: {} })
    const currencies   = ref({ data: [], meta: {} })

    // Filters
    const taxFilters         = reactive({ search: '', status: '', page: 1, per_page: 15, sort_by: 'name', sort_dir: 'asc' })
    const taxGroupFilters    = reactive({ search: '', status: '', page: 1, per_page: 15, sort_by: 'name', sort_dir: 'asc' })
    const unitFilters        = reactive({ search: '', status: '', page: 1, per_page: 15, sort_by: 'name', sort_dir: 'asc' })
    const paymentTypeFilters = reactive({ search: '', status: '', page: 1, per_page: 15, sort_by: 'name', sort_dir: 'asc' })
    const currencyFilters    = reactive({ search: '', status: '', page: 1, per_page: 15, sort_by: 'name', sort_dir: 'asc' })

    // Loading flags
    const loading = reactive({
        site: false, store: false, smtp: false,
        taxes: false, taxGroups: false, units: false,
        paymentTypes: false, currencies: false,
    })
    const saving    = ref(false)
    const importing = ref(false)
    const exporting = ref(false)

    // Generic fetch for standard paginated responses
    async function fetchList(key, apiFn, filters, store) {
        loading[key] = true
        try {
            const res = await apiFn({ ...filters })
            store.value = {
                data: res.data.data ?? res.data,
                meta: res.data.meta ?? {},
            }
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: e.response?.data?.message ?? 'Failed to load data' })
        } finally {
            loading[key] = false
        }
    }

    // Specialised fetch for tax groups
    const fetchTaxGroups = async () => {
        loading.taxGroups = true
        try {
            const res = await settingsApi.getTaxGroups({ ...taxGroupFilters })
            const paginator = res.data.data
            taxGroups.value = {
                data: paginator.data ?? [],
                meta: paginator.meta ?? {},
            }
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: e.response?.data?.message ?? 'Failed to load tax groups' })
        } finally {
            loading.taxGroups = false
        }
    }

    // Generic save helper
    async function saveItem(apiFn, successMsg) {
        saving.value = true
        try {
            const res = await apiFn()
            uiStore.addNotification({ type: 'success', message: successMsg })
            return res.data
        } catch (e) {
            const errors = e.response?.data?.errors
            if (!errors) {
                uiStore.addNotification({ type: 'error', message: e.response?.data?.message ?? 'Operation failed' })
            }
            throw e
        } finally {
            saving.value = false
        }
    }

    // Import/Export helpers
    async function importItems(apiFn, file, refetchFn) {
        importing.value = true
        try {
            const res = await apiFn(file)
            const { imported, skipped, errors } = res.data
            uiStore.addNotification({ type: 'success', message: `Imported ${imported}, skipped ${skipped}` })
            if (errors?.length) console.warn('Import errors:', errors)
            await refetchFn()
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: e.response?.data?.message ?? 'Import failed' })
        } finally {
            importing.value = false
        }
    }

    async function exportItems(apiFn, filename, filters) {
        exporting.value = true
        try {
            const res = await apiFn({ ...filters, per_page: 10000 })
            downloadCsv(res.data, filename)
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: 'Export failed' })
        } finally {
            exporting.value = false
        }
    }

    // ── UPDATED: Site Settings ──────────────────────────────────────────────
    const fetchSiteSettings = async () => {
        loading.site = true
        try {
            const res = await settingsApi.getSite()
            siteSettings.value = res.data
            // CHANGE: Persist to localStorage for immediate UI updates (Logo/Name)
            localStorage.setItem('site_settings', JSON.stringify(res.data))

            // CHANGE: Update document title dynamically
            if (res.data.site_name) {
                document.title = res.data.site_name
            }
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: 'Failed to load site settings' })
        } finally {
            loading.site = false
        }
    }

    const updateSiteSettings = async (form) => {
        // CHANGE: Use saveItem but also update local state and storage
        const data = await saveItem(() => settingsApi.updateSite(form), 'Site settings saved')
        siteSettings.value = data
        localStorage.setItem('site_settings', JSON.stringify(data))

        if (data.site_name) document.title = data.site_name
        return data
    }

    // ── UPDATED: Store Settings (Branch Dependent) ──────────────────────────
    const fetchStoreSettings = async (branchId = null) => {
        loading.store = true
        try {
            // CHANGE: Use provided branchId or fallback to user's current branch
            const targetBranchId = branchId || authStore.user?.branch_id
            const res = await settingsApi.getStore({ branch_id: targetBranchId })

            // CHANGE: API now returns { data: {...}, options: {...} }
            const data = res.data.data || res.data
            storeSettings.value = data

            // CHANGE: Persist to localStorage so formatters can use it immediately
            localStorage.setItem('store_settings', JSON.stringify(data))

            return res.data
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: 'Failed to load store settings' })
        } finally {
            loading.store = false
        }
    }

    const updateStoreSettings = async (form) => {
        const res = await saveItem(() => settingsApi.updateStore(form), 'Store settings saved')
        // CHANGE: Update local state and storage with the returned data
        const data = res.data || res
        storeSettings.value = data
        localStorage.setItem('store_settings', JSON.stringify(data))
        return res
    }

    // ── SMTP Settings ───────────────────────────────────────────────────────
    const fetchSmtpSettings = async () => {
        loading.smtp = true
        try {
            const res = await settingsApi.getSmtp()
            smtpSettings.value = res.data
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: 'Failed to load SMTP settings' })
        } finally {
            loading.smtp = false
        }
    }

    const updateSmtpSettings = async (data) => {
        const result = await saveItem(() => settingsApi.updateSmtp(data), 'SMTP settings saved')
        smtpSettings.value = result
        return result
    }

    const testSmtp = async () => {
        try {
            const res = await settingsApi.testSmtp()
            uiStore.addNotification({ type: 'success', message: res.data.message })
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: e.response?.data?.message ?? 'Test email failed' })
        }
    }

    // ── Resource CRUD ───────────────────────────────────────────────────────
    const fetchTaxes      = () => fetchList('taxes', settingsApi.getTaxes, taxFilters, taxes)
    const createTax       = (d) => saveItem(() => settingsApi.createTax(d), 'Tax created').then(fetchTaxes)
    const updateTax       = (id, d) => saveItem(() => settingsApi.updateTax(id, d), 'Tax updated').then(fetchTaxes)
    const deleteTax       = (id) => saveItem(() => settingsApi.deleteTax(id), 'Tax deleted').then(fetchTaxes)
    const importTaxes     = (file) => importItems(settingsApi.importTaxes, file, fetchTaxes)
    const exportTaxes     = () => exportItems(settingsApi.exportTaxes, 'taxes.csv', taxFilters)

    const createTaxGroup  = (d) => saveItem(() => settingsApi.createTaxGroup(d), 'Tax group created').then(fetchTaxGroups)
    const updateTaxGroup  = (id, d) => saveItem(() => settingsApi.updateTaxGroup(id, d), 'Tax group updated').then(fetchTaxGroups)
    const deleteTaxGroup  = (id) => saveItem(() => settingsApi.deleteTaxGroup(id), 'Tax group deleted').then(fetchTaxGroups)

    const fetchUnits      = () => fetchList('units', settingsApi.getUnits, unitFilters, units)
    const createUnit      = (d) => saveItem(() => settingsApi.createUnit(d), 'Unit created').then(fetchUnits)
    const updateUnit      = (id, d) => saveItem(() => settingsApi.updateUnit(id, d), 'Unit updated').then(fetchUnits)
    const deleteUnit      = (id) => saveItem(() => settingsApi.deleteUnit(id), 'Unit deleted').then(fetchUnits)
    const importUnits     = (file) => importItems(settingsApi.importUnits, file, fetchUnits)
    const exportUnits     = () => exportItems(settingsApi.exportUnits, 'units.csv', unitFilters)

    const fetchPaymentTypes      = () => fetchList('paymentTypes', settingsApi.getPaymentTypes, paymentTypeFilters, paymentTypes)
    const createPaymentType      = (d) => saveItem(() => settingsApi.createPaymentType(d), 'Payment type created').then(fetchPaymentTypes)
    const updatePaymentType      = (id, d) => saveItem(() => settingsApi.updatePaymentType(id, d), 'Payment type updated').then(fetchPaymentTypes)
    const deletePaymentType      = (id) => saveItem(() => settingsApi.deletePaymentType(id), 'Payment type deleted').then(fetchPaymentTypes)
    const importPaymentTypes     = (file) => importItems(settingsApi.importPaymentTypes, file, fetchPaymentTypes)
    const exportPaymentTypes     = () => exportItems(settingsApi.exportPaymentTypes, 'payment-types.csv', paymentTypeFilters)

    const fetchCurrencies      = () => fetchList('currencies', settingsApi.getCurrencies, currencyFilters, currencies)
    const createCurrency       = (d) => saveItem(() => settingsApi.createCurrency(d), 'Currency created').then(fetchCurrencies)
    const updateCurrency       = (id, d) => saveItem(() => settingsApi.updateCurrency(id, d), 'Currency updated').then(fetchCurrencies)
    const deleteCurrency       = (id) => saveItem(() => settingsApi.deleteCurrency(id), 'Currency deleted').then(fetchCurrencies)
    const importCurrencies     = (file) => importItems(settingsApi.importCurrencies, file, fetchCurrencies)
    const exportCurrencies     = () => exportItems(settingsApi.exportCurrencies, 'currencies.csv', currencyFilters)

    return {
        siteSettings, storeSettings, smtpSettings,
        taxes, taxGroups, units, paymentTypes, currencies,
        taxFilters, taxGroupFilters, unitFilters, paymentTypeFilters, currencyFilters,
        loading, saving, importing, exporting,

        fetchSiteSettings, updateSiteSettings,
        fetchStoreSettings, updateStoreSettings,
        fetchSmtpSettings, updateSmtpSettings, testSmtp,

        fetchTaxes, createTax, updateTax, deleteTax, importTaxes, exportTaxes,
        fetchTaxGroups, createTaxGroup, updateTaxGroup, deleteTaxGroup,
        fetchUnits, createUnit, updateUnit, deleteUnit, importUnits, exportUnits,
        fetchPaymentTypes, createPaymentType, updatePaymentType, deletePaymentType, importPaymentTypes, exportPaymentTypes,
        fetchCurrencies, createCurrency, updateCurrency, deleteCurrency, importCurrencies, exportCurrencies,
    }
})
