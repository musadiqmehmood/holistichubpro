/**
 * src/utils/format.js
 *
 * Global formatting utility driven by Store Settings.
 * This ensures that dates, times, and currencies are consistent across the entire system.
 */
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'
import dayjs from 'dayjs'

export const useFormatter = () => {
    const settingsStore = useSettingsStore()
    const authStore = useAuthStore()

    /**
     * Get the current active settings (either from the store or fallback to user branch settings)
     */
    const getSettings = () => {
        return settingsStore.storeSettings || {}
    }

    /**
     * Format Date based on dynamic settings
     */
    const formatDate = (date) => {
        if (!date) return ''
        const format = getSettings().date_format || 'YYYY-MM-DD'
        // Map PHP formats to Day.js formats if necessary
        const mappedFormat = format
            .replace('Y', 'YYYY')
            .replace('m', 'MM')
            .replace('d', 'DD')
            .replace('M', 'MMM')

        return dayjs(date).format(mappedFormat)
    }

    /**
     * Format Time based on dynamic settings
     */
    const formatTime = (time) => {
        if (!time) return ''
        const format = getSettings().time_format || 'HH:mm'
        // Map PHP formats to Day.js formats
        const mappedFormat = format
            .replace('H', 'HH')
            .replace('h', 'hh')
            .replace('i', 'mm')
            .replace('s', 'ss')
            .replace('A', 'A')

        // If it's just a time string (HH:mm:ss), we need to prefix a date for dayjs to parse it
        const datePrefix = dayjs().format('YYYY-MM-DD')
        return dayjs(`${datePrefix} ${time}`).format(mappedFormat)
    }

    /**
     * Format Currency based on dynamic settings
     */
    const formatCurrency = (amount) => {
        const settings = getSettings()
        const symbol = settings.currency_symbol || '$'
        const placement = settings.currency_symbol_placement || 'before'
        const decimals = settings.decimals ?? 2

        const formattedAmount = Number(amount).toFixed(decimals)

        return placement === 'before'
            ? `${symbol}${formattedAmount}`
            : `${formattedAmount}${symbol}`
    }

    return {
        formatDate,
        formatTime,
        formatCurrency
    }
}
