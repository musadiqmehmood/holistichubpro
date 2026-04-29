<template>
    <button
        :class="[
      'relative inline-flex items-center justify-center gap-2 font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
      computedClasses,
      sizeClasses,
      shapeClasses,
    ]"
        :disabled="disabled || loading"
        v-bind="$attrs"
    >
    <span v-if="loading" class="absolute inset-0 flex items-center justify-center">
      <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </span>
        <span :class="{ 'opacity-0': loading }" class="flex items-center gap-2">
      <slot />
    </span>
    </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    variant: { type: String, default: 'filled' }, // filled, outlined, tonal, text
    color: { type: String, default: 'primary' }, // primary, error, neutral
    size: { type: String, default: 'medium' },
    shape: { type: String, default: 'rounded' },
    disabled: Boolean,
    loading: Boolean,
})

const computedClasses = computed(() => {
    const base = ''
    const styles = {
        primary: {
            filled: 'bg-[var(--primary-color)] text-white hover:bg-[var(--primary-dark)] shadow-sm border border-transparent focus:ring-[var(--primary-color)]',
            outlined: 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-sm focus:ring-[var(--primary-color)]',
            tonal: 'bg-[var(--primary-color)]/10 text-[var(--primary-dark)] hover:bg-[var(--primary-color)]/20 border border-transparent focus:ring-[var(--primary-color)]',
            text: 'bg-transparent text-[var(--primary-color)] hover:bg-[var(--primary-color)]/10 focus:ring-[var(--primary-color)]'
        },
        error: {
            filled: 'bg-[var(--error-color)] text-white hover:bg-red-600 shadow-sm border border-transparent focus:ring-[var(--error-color)]',
            outlined: 'bg-white border border-red-300 text-red-700 hover:bg-red-50 shadow-sm focus:ring-[var(--error-color)]',
            tonal: 'bg-red-50 text-red-700 hover:bg-red-100 border border-transparent focus:ring-[var(--error-color)]',
            text: 'bg-transparent text-[var(--error-color)] hover:bg-red-50 focus:ring-[var(--error-color)]'
        },
        neutral: {
            filled: 'bg-gray-800 text-white hover:bg-gray-900 shadow-sm border border-transparent focus:ring-gray-500',
            outlined: 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-sm focus:ring-gray-500',
            tonal: 'bg-gray-100 text-gray-800 hover:bg-gray-200 border border-transparent focus:ring-gray-500',
            text: 'bg-transparent text-gray-600 hover:bg-gray-100 focus:ring-gray-500'
        }
    }
    return styles[props.color]?.[props.variant] || styles.primary.filled
})

const sizeClasses = computed(() => ({
    small: 'px-3 py-1.5 text-xs',
    medium: 'px-4 py-2 text-sm',
    large: 'px-6 py-3 text-base'
})[props.size] || 'px-4 py-2 text-sm')

const shapeClasses = computed(() => ({
    rounded: 'rounded-lg',
    pill: 'rounded-full',
    square: 'rounded-none'
})[props.shape] || 'rounded-lg')
</script>
