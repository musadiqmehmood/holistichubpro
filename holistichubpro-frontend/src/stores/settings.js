// src/stores/settings.js
import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import { settingsApi, downloadCsv } from '@/api/settings'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { designSettingsApi } from '@/api/settings'

export const useSettingsStore = defineStore('settings', () => {
    const uiStore = useUiStore()
    const authStore = useAuthStore()

    // Singleton settings (unchanged)
    const siteSettings  = ref(JSON.parse(localStorage.getItem('site_settings')) || {})
    const storeSettings = ref(JSON.parse(localStorage.getItem('store_settings')) || {})
    const smtpSettings  = ref({})

    // ── Dynamic UI settings – now per‑user ──────────────────────────────
    const dynamicSettingsKey = () => `dynamic_settings_${authStore.user?.id || 'guest'}`

    const dynamicSettings = ref({
        themeMode: 'light',
        primaryColor: '#6366f1',
        secondaryColor: '#8b5cf6',
        backgroundColor: '#f8fafc',
        surfaceColor: '#ffffff',
        textPrimary: '#0f172a',
        textSecondary: '#475569',
        headingColor: '#0f172a',
        subheadingColor: '#334155',
        paragraphColor: '#475569',
        borderColor: '#e2e8f0',
        menuBg: '#ffffff',
        menuText: '#0f172a',
        fontFamily: 'Montserrat, Roboto, system-ui, sans-serif',
        fontSizeBase: '16px',
        fontSizeHeadingScale: '1.25',
        showIcons: false,
        sidebarCollapsed: false,
        dateFormat: 'YYYY-MM-DD',
        timeFormat: 'HH:mm',
        currencySymbol: '$',
        currencyPlacement: 'before',
        decimals: 2,
        // Button defaults — missing in original, referenced in applyThemeVariables()
        btnBg: '#6366f1',
        btnText: '#ffffff',
        btnHoverBg: '#4f46e5',
    })

    // Resource lists (unchanged)
    const taxes        = ref({ data: [], meta: {} })
    const taxGroups    = ref({ data: [], meta: {} })
    const units        = ref({ data: [], meta: {} })
    const paymentTypes = ref({ data: [], meta: {} })
    const currencies   = ref({ data: [], meta: {} })

    // Filters – per_page defaults to 10 (unchanged)
    const taxFilters         = reactive({ search: '', status: '', page: 1, per_page: 10, sort_by: 'name', sort_dir: 'asc' })
    const taxGroupFilters    = reactive({ search: '', status: '', page: 1, per_page: 10, sort_by: 'name', sort_dir: 'asc' })
    const unitFilters        = reactive({ search: '', status: '', page: 1, per_page: 10, sort_by: 'name', sort_dir: 'asc' })
    const paymentTypeFilters = reactive({ search: '', status: '', page: 1, per_page: 10, sort_by: 'name', sort_dir: 'asc' })
    const currencyFilters    = reactive({ search: '', status: '', page: 1, per_page: 10, sort_by: 'name', sort_dir: 'asc' })

    // Loading flags (unchanged)
    const loading = reactive({
        site: false, store: false, smtp: false,
        taxes: false, taxGroups: false, units: false,
        paymentTypes: false, currencies: false,
    })
    const saving    = ref(false)
    const importing = ref(false)
    const exporting = ref(false)

    // ── Helper: fetch for paginated lists ──────────────────────────────
    // Reads data + meta from the ApiResponse envelope:
    //   { success, message, data: [...], meta: { current_page, last_page, ... } }
    async function fetchList(key, apiFn, filters, store) {
        loading[key] = true
        try {
            const res = await apiFn({ ...filters })
            const responseData = res.data
            // ApiResponse envelope — extract items from .data, pagination from .meta
            const payload = responseData.data ?? responseData
            const meta    = responseData.meta ?? responseData
            store.value = {
                data: Array.isArray(payload) ? payload : (payload.data ?? []),
                meta: {
                    current_page: meta.current_page ?? 1,
                    last_page:    meta.last_page    ?? 1,
                    total:        meta.total        ?? 0,
                    from:         meta.from         ?? 0,
                    to:           meta.to           ?? 0,
                    per_page:     meta.per_page     ?? filters.per_page ?? 10,
                }
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
            const responseData = res.data
            const payload = responseData.data ?? responseData
            const meta    = responseData.meta ?? responseData
            taxGroups.value = {
                data: Array.isArray(payload) ? payload : (payload.data ?? []),
                meta: {
                    current_page: meta.current_page ?? 1,
                    last_page:    meta.last_page    ?? 1,
                    total:        meta.total        ?? 0,
                    from:         meta.from         ?? 0,
                    to:           meta.to           ?? 0,
                    per_page:     meta.per_page     ?? taxGroupFilters.per_page ?? 10,
                },
            }
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: e.response?.data?.message ?? 'Failed to load tax groups' })
        } finally {
            loading.taxGroups = false
        }
    }

    // Generic save helper — unwraps ApiResponse envelope { success, message, data }
    async function saveItem(apiFn, successMsg) {
        saving.value = true
        try {
            const res = await apiFn()
            uiStore.addNotification({ type: 'success', message: successMsg })
            return res.data?.data ?? res.data
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

    // Import/Export helpers (unchanged)
    async function importItems(apiFn, file, refetchFn) {
        importing.value = true
        try {
            const res = await apiFn(file)
            const payload = res.data?.data ?? res.data
            const { imported, skipped, errors } = payload
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
            // ApiResponse envelope: extract the actual data array
            const rows = res.data?.data ?? res.data
            downloadCsv(rows, filename)
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: 'Export failed' })
        } finally {
            exporting.value = false
        }
    }

    // ── Site Settings (unchanged) ───────────────────────────────────────
    const fetchSiteSettings = async () => {
        loading.site = true
        try {
            const res = await settingsApi.getSite()
            const payload = res.data?.data ?? res.data
            siteSettings.value = payload
            localStorage.setItem('site_settings', JSON.stringify(payload))
            if (payload?.site_name) document.title = payload.site_name
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: 'Failed to load site settings' })
        } finally {
            loading.site = false
        }
    }

    const updateSiteSettings = async (form) => {
        const data = await saveItem(() => settingsApi.updateSite(form), 'Site settings saved')
        siteSettings.value = data
        localStorage.setItem('site_settings', JSON.stringify(data))
        if (data.site_name) document.title = data.site_name
        return data
    }

    // ── Store Settings (unchanged) ──────────────────────────────────────
    const fetchStoreSettings = async (branchId = null) => {
        loading.store = true
        try {
            const targetBranchId = branchId || authStore.user?.branch_id
            const res = await settingsApi.getStore({ branch_id: targetBranchId })
            const data = res.data?.data ?? res.data
            storeSettings.value = data
            localStorage.setItem('store_settings', JSON.stringify(data))
            return data
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: 'Failed to load store settings' })
        } finally {
            loading.store = false
        }
    }

    const updateStoreSettings = async (form) => {
        const data = await saveItem(() => settingsApi.updateStore(form), 'Store settings saved')
        storeSettings.value = data
        localStorage.setItem('store_settings', JSON.stringify(data))
        return data
    }

    // ── SMTP Settings (unchanged) ───────────────────────────────────────
    const fetchSmtpSettings = async () => {
        loading.smtp = true
        try {
            const res = await settingsApi.getSmtp()
            smtpSettings.value = res.data?.data ?? res.data
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
            const payload = res.data?.data ?? res.data
            uiStore.addNotification({ type: 'success', message: payload?.message ?? 'Test completed' })
        } catch (e) {
            uiStore.addNotification({ type: 'error', message: e.response?.data?.message ?? 'Test email failed' })
        }
    }

    // ── Server‑Synced Dynamic Settings (Reload‑Proof) ──
    const fetchDynamicSettings = async () => {
        // Step 1: Load from localStorage first for instant UI response
        const localKey = dynamicSettingsKey()
        const localData = localStorage.getItem(localKey)
        let localApplied = false
        if (localData) {
            try {
                Object.assign(dynamicSettings.value, JSON.parse(localData))
                applyThemeVariables()
                localApplied = true
            } catch (e) { /* ignore */ }
        }

        // Step 2: Fetch latest settings from server (takes priority)
        try {
            const response = await designSettingsApi.get()
            const serverSettings = response.data

            // If server returned usable data, apply immediately
            if (serverSettings && Object.keys(serverSettings).length > 0) {
                // Server data replaces localStorage copy
                dynamicSettings.value = { ...dynamicSettings.value, ...serverSettings }
                // Persist to localStorage so next load is instant
                localStorage.setItem(localKey, JSON.stringify(dynamicSettings.value))
                applyThemeVariables()
                console.log('✅ Design settings loaded from server')
            } else {
                // Server returned empty and nothing in localStorage — defaults remain
                if (!localApplied) {
                    console.log('ℹ️ No settings found, using defaults')
                }
            }
        } catch (error) {
            // Server failed — localStorage copy continues to work, else fall back to defaults
            console.warn('⚠️ Server design settings failed, using localStorage fallback')
            if (!localApplied) {
                // Nothing in localStorage either — factory defaults are used
                applyThemeVariables()
            }
        }
    }

    const updateDynamicSetting = async (key, value) => {
        // Immediate state update
        dynamicSettings.value[key] = value

        // Always persist to localStorage first — fastest, works offline
        const localKey = dynamicSettingsKey()
        localStorage.setItem(localKey, JSON.stringify(dynamicSettings.value))

        // Fire-and-forget: sync to server in the background
        try {
            await designSettingsApi.save(dynamicSettings.value)
            console.log('✅ Design settings saved to server')
        } catch (error) {
            // Server failed — no problem, data is safe in localStorage
            console.warn('⚠️ Design settings not saved to server, will retry on next sync')
        }

        // Apply theme variables immediately
        applyThemeVariables()
    }

    const applyThemeVariables = () => {
        const root = document.documentElement
        const ds = dynamicSettings.value

        // Typography (always applied)
        root.style.setProperty('--font-family', ds.fontFamily)
        root.style.fontSize = ds.fontSizeBase
        root.style.setProperty('--heading-scale', ds.fontSizeHeadingScale)

        if (ds.themeMode === 'dark') {
            root.classList.add('dark')
            const colorVars = [
                '--primary-color', '--primary-light', '--primary-dark',
                '--secondary-color', '--background-color', '--surface-color',
                '--text-primary', '--text-secondary', '--heading-color',
                '--subheading-color', '--paragraph-color', '--border-color',
                '--menu-bg', '--menu-text',
                '--btn-bg', '--btn-text', '--btn-hover-bg'
            ]
            colorVars.forEach(v => root.style.removeProperty(v))
        } else {
            root.classList.remove('dark')
            // Light mode – set all custom colours
            root.style.setProperty('--primary-color', ds.primaryColor)
            root.style.setProperty('--primary-light', ds.secondaryColor)
            root.style.setProperty('--primary-dark', adjustColor(ds.primaryColor, -20))
            root.style.setProperty('--secondary-color', ds.secondaryColor)
            root.style.setProperty('--background-color', ds.backgroundColor)
            root.style.setProperty('--surface-color', ds.surfaceColor)
            root.style.setProperty('--text-primary', ds.textPrimary)
            root.style.setProperty('--text-secondary', ds.textSecondary)
            root.style.setProperty('--heading-color', ds.headingColor)
            root.style.setProperty('--subheading-color', ds.subheadingColor)
            root.style.setProperty('--paragraph-color', ds.paragraphColor)
            root.style.setProperty('--border-color', ds.borderColor)
            root.style.setProperty('--menu-bg', ds.menuBg)
            root.style.setProperty('--menu-text', ds.menuText)
            // Button colours
            root.style.setProperty('--btn-bg', ds.btnBg)
            root.style.setProperty('--btn-text', ds.btnText)
            root.style.setProperty('--btn-hover-bg', ds.btnHoverBg)
        }
    }


    /**
     * Darken or lighten a hex colour.
     * @param {string} hex   — 6-char hex with leading # (e.g. '#6366f1')
     * @param {number} amount — negative = darker, positive = lighter
     * @returns {string}    — adjusted hex colour
     */
    function adjustColor(hex, amount) {
        const num = parseInt(hex.replace('#', ''), 16)
        if (Number.isNaN(num)) return hex

        const r = Math.min(255, Math.max(0, (num >> 16) + amount))
        const g = Math.min(255, Math.max(0, ((num >> 8) & 0x00FF) + amount))
        const b = Math.min(255, Math.max(0, (num & 0x0000FF) + amount))

        return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)
    }

    // ── Resource CRUD (unchanged) ───────────────────────────────────────
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

    // ── Return everything ────────────────────────────────────────────────
    return {
        siteSettings, storeSettings, smtpSettings, dynamicSettings,
        taxes, taxGroups, units, paymentTypes, currencies,
        taxFilters, taxGroupFilters, unitFilters, paymentTypeFilters, currencyFilters,
        loading, saving, importing, exporting,

        fetchSiteSettings, updateSiteSettings,
        fetchStoreSettings, updateStoreSettings,
        fetchSmtpSettings, updateSmtpSettings, testSmtp,

        fetchDynamicSettings, updateDynamicSetting, applyThemeVariables, toggleSidebar: () => updateDynamicSetting('sidebarCollapsed', !dynamicSettings.value.sidebarCollapsed),

        fetchTaxes, createTax, updateTax, deleteTax, importTaxes, exportTaxes,
        fetchTaxGroups, createTaxGroup, updateTaxGroup, deleteTaxGroup,

        fetchUnits, createUnit, updateUnit, deleteUnit, importUnits, exportUnits,

        fetchPaymentTypes, createPaymentType, updatePaymentType, deletePaymentType, importPaymentTypes, exportPaymentTypes,

        fetchCurrencies, createCurrency, updateCurrency, deleteCurrency, importCurrencies, exportCurrencies,
    }
})
