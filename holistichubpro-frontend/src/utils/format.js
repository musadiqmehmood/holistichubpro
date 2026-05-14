/**
 * src/utils/format.js
 *
 * Global formatting utility driven by Store Settings.
 * Uses native Intl.DateTimeFormat — no external date library required.
 *
 * Usage:
 *   import { useFormatter } from '@/utils/format'
 *   const { formatDate, formatTime, formatDateTime, formatCurrency, parseDate } = useFormatter()
 */
import { useSettingsStore } from '@/stores/settings'

/** PHP date format → Intl.DateTimeFormat options */
const DATE_MAP = {
    'Y-m-d':   { year: 'numeric', month: '2-digit', day: '2-digit', parts: ['year', 'month', 'day'], sep: '-' },
    'd/m/Y':   { year: 'numeric', month: '2-digit', day: '2-digit', parts: ['day', 'month', 'year'], sep: '/' },
    'm/d/Y':   { year: 'numeric', month: '2-digit', day: '2-digit', parts: ['month', 'day', 'year'], sep: '/' },
    'd-M-Y':   { year: 'numeric', month: 'short',   day: '2-digit', parts: ['day', 'month', 'year'], sep: '-' },
    'd.m.Y':   { year: 'numeric', month: '2-digit', day: '2-digit', parts: ['day', 'month', 'year'], sep: '.' },
    'M d, Y':  { year: 'numeric', month: 'short',   day: '2-digit', parts: ['month', 'day', 'year'], sep: ' ' },
}

const TIME_MAP = {
    'H:i':   { hour: '2-digit', minute: '2-digit', hour12: false },
    'H:i:s': { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false },
    'h:i A': { hour: '2-digit', minute: '2-digit', hour12: true },
}

export const useFormatter = () => {
    const settingsStore = useSettingsStore()

    const getSettings = () => settingsStore.storeSettings || {}

    const s = getSettings()
    const dateFormat = s.date_format || 'Y-m-d'
    const timeFormat = s.time_format || 'H:i'

    /**
     * Format a date value (Date, string, timestamp) using the store's date format.
     */
    const formatDate = (date) => {
        if (!date) return ''
        const d = toValidDate(date)
        if (!d) return ''

        const cfg = DATE_MAP[dateFormat] || DATE_MAP['Y-m-d']
        const formatter = new Intl.DateTimeFormat('en-GB', {
            year: cfg.year,
            month: cfg.month,
            day: cfg.day,
        })

        const parts = formatter.formatToParts(d)
        const get = (type) => parts.find(p => p.type === type)?.value ?? ''

        const vals = cfg.parts.map(p => get(p))

        // Handle special "M d, Y" comma separator
        if (dateFormat === 'M d, Y') {
            return `${vals[0]} ${vals[1]}, ${vals[2]}`
        }
        return vals.join(cfg.sep)
    }

    /**
     * Format a time value using the store's time format.
     */
    const formatTime = (time) => {
        if (!time) return ''
        const d = toValidDate(time)
        if (!d) return ''

        const cfg = TIME_MAP[timeFormat] || TIME_MAP['H:i']
        return new Intl.DateTimeFormat('en-GB', cfg).format(d)
    }

    /**
     * Format as date + time combined.
     */
    const formatDateTime = (date) => {
        if (!date) return ''
        const d = formatDate(date)
        const t = formatTime(date)
        return d && t ? `${d} ${t}` : (d || t || '')
    }

    /**
     * Format a number as currency with the configured symbol and decimals.
     */
    const formatCurrency = (amount, symbol = null) => {
        const settings = getSettings()
        const sym       = symbol ?? (settings.currency_symbol || '$')
        const placement = settings.currency_symbol_placement || 'before'
        const decimals  = settings.decimals ?? 2

        const formatted = Number(amount).toFixed(decimals)
        return placement === 'before' ? `${sym}${formatted}` : `${formatted}${sym}`
    }

    /**
     * Parse a user-entered date string back to a Date object.
     * Returns null if unparseable.
     */
    const parseDate = (input) => {
        if (!input || typeof input !== 'string') return null

        try {
            switch (dateFormat) {
                case 'Y-m-d': {
                    const [y, m, d] = input.split('-').map(Number)
                    if (y && m && d) return new Date(y, m - 1, d)
                    break
                }
                case 'd/m/Y':
                case 'm/d/Y': {
                    const [a, b, y] = input.split('/').map(Number)
                    if (a && b && y) {
                        const isUS = dateFormat === 'm/d/Y'
                        return new Date(y, isUS ? a - 1 : b - 1, isUS ? b : a)
                    }
                    break
                }
                case 'd.m.Y': {
                    const [d, m, y] = input.split('.').map(Number)
                    if (d && m && y) return new Date(y, m - 1, d)
                    break
                }
                case 'd-M-Y':
                case 'M d, Y': {
                    const parsed = new Date(input)
                    if (!isNaN(parsed.getTime())) return parsed
                    break
                }
                default:
                    return new Date(input)
            }
        } catch {
            return null
        }
        return null
    }

    return {
        formatDate,
        formatTime,
        formatDateTime,
        formatCurrency,
        parseDate,
    }
}

/**
 * Normalise any input to a valid Date object.
 */
function toValidDate(input) {
    if (input instanceof Date) return isNaN(input.getTime()) ? null : input
    if (typeof input === 'string') {
        const d = new Date(input)
        return isNaN(d.getTime()) ? null : d
    }
    if (typeof input === 'number') {
        const d = new Date(input)
        return isNaN(d.getTime()) ? null : d
    }
    return null
}
