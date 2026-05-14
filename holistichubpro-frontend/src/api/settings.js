import api from './axios'

/**
 * Escape a single value for RFC 4180-compliant CSV.
 * Wraps in double-quotes if the value contains commas,
 * newlines, or double-quotes (which are doubled).
 */
function escapeCsv(val) {
    const str = String(val ?? '')
    if (/[\",\n\r]/.test(str)) {
        return '"' + str.replace(/"/g, '""') + '"'
    }
    return str
}

/**
 * Helper: trigger client-side CSV download from a JSON array.
 * No external library — native Blob API only.
 * Includes UTF-8 BOM so Excel opens non-ASCII chars correctly.
 */
export function downloadCsv(rows, filename) {
    if (!rows || !rows.length || !rows[0]) return
    const headers = Object.keys(rows[0])
    const lines = [
        headers.map(escapeCsv).join(','),
        ...rows.map(r => headers.map(h => escapeCsv(r[h])).join(',')),
    ]
    // Prepend UTF-8 BOM so Excel renders special characters correctly
    const bom = '\uFEFF'
    const csv = bom + lines.join('\n')
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }))
    const a   = Object.assign(document.createElement('a'), { href: url, download: filename })
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
}

export const settingsApi = {
    // ── Site Settings ──────────────────────────────────────────────────────────
    getSite:    ()     => api.get('/admin/site-settings'),
    // FormData passed — axios auto-sets Content-Type with boundary
    updateSite: (form) => api.post('/admin/site-settings', form),

    // ── Store Settings ─────────────────────────────────────────────────────────
    getStore:    ()     => api.get('/admin/store-settings'),
    updateStore: (form) => api.post('/admin/store-settings', form),

    // ── SMTP ───────────────────────────────────────────────────────────────────
    getSmtp:    ()     => api.get('/admin/smtp-settings'),
    updateSmtp: (data) => api.put('/admin/smtp-settings', data),
    testSmtp:   ()     => api.post('/admin/smtp-settings/test'),

    // ── Taxes ──────────────────────────────────────────────────────────────────
    getTaxes:    (params) => api.get('/admin/taxes', { params }),
    createTax:   (data)   => api.post('/admin/taxes', data),
    updateTax:   (id, d)  => api.put(`/admin/taxes/${id}`, d),
    deleteTax:   (id)     => api.delete(`/admin/taxes/${id}`),
    exportTaxes: (params) => api.get('/admin/taxes/export', { params }),
    importTaxes: (file)   => {
        const f = new FormData()
        f.append('file', file)
        return api.post('/admin/taxes/import', f)
    },

    // ── Tax Groups ─────────────────────────────────────────────────────────────
    getTaxGroups:   (params) => api.get('/admin/tax-groups', { params }),
    createTaxGroup: (data)   => api.post('/admin/tax-groups', data),
    updateTaxGroup: (id, d)  => api.put(`/admin/tax-groups/${id}`, d),
    deleteTaxGroup: (id)     => api.delete(`/admin/tax-groups/${id}`),

    // ── Units ──────────────────────────────────────────────────────────────────
    getUnits:    (params) => api.get('/admin/units', { params }),
    createUnit:  (data)   => api.post('/admin/units', data),
    updateUnit:  (id, d)  => api.put(`/admin/units/${id}`, d),
    deleteUnit:  (id)     => api.delete(`/admin/units/${id}`),
    exportUnits: (params) => api.get('/admin/units/export', { params }),
    importUnits: (file)   => {
        const f = new FormData()
        f.append('file', file)
        return api.post('/admin/units/import', f)
    },

    // ── Payment Types ──────────────────────────────────────────────────────────
    getPaymentTypes:    (params) => api.get('/admin/payment-types', { params }),
    createPaymentType:  (data)   => api.post('/admin/payment-types', data),
    updatePaymentType:  (id, d)  => api.put(`/admin/payment-types/${id}`, d),
    deletePaymentType:  (id)     => api.delete(`/admin/payment-types/${id}`),
    exportPaymentTypes: (params) => api.get('/admin/payment-types/export', { params }),
    importPaymentTypes: (file)   => {
        const f = new FormData()
        f.append('file', file)
        return api.post('/admin/payment-types/import', f)
    },

    // ── Currencies ─────────────────────────────────────────────────────────────
    getCurrencies:    (params) => api.get('/admin/currencies', { params }),
    createCurrency:   (data)   => api.post('/admin/currencies', data),
    updateCurrency:   (id, d)  => api.put(`/admin/currencies/${id}`, d),
    deleteCurrency:   (id)     => api.delete(`/admin/currencies/${id}`),
    exportCurrencies: (params) => api.get('/admin/currencies/export', { params }),
    importCurrencies: (file)   => {
        const f = new FormData()
        f.append('file', file)
        return api.post('/admin/currencies/import', f)
    },

    // ── Database Backup ────────────────────────────────────────────────────────
    createBackup:   ()         => api.post('/admin/backup'),
    listBackups:    ()         => api.get('/admin/backups'),
    downloadBackup: (filename) => api.get(`/admin/backups/${filename}/download`, { responseType: 'blob' }),
    deleteBackup:   (filename) => api.delete(`/admin/backups/${filename}`),
}

export const designSettingsApi = {
    get: () => api.get('/design-settings'),
    save: (settings) => api.put('/design-settings', { settings }),
}
