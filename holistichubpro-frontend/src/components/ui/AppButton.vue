<template>
    <button
        :class="[
            'relative inline-flex items-center justify-center gap-2 font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed',
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
    size: { type: String, default: 'medium' }, // small, medium, large
    shape: { type: String, default: 'rounded' }, // rounded, pill, square
    disabled: Boolean,
    loading: Boolean,
})

const computedClasses = computed(() => {
    const styles = {
        primary: {
            filled: 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm border border-transparent focus:ring-blue-500',
            outlined: 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 shadow-sm focus:ring-blue-500',
            tonal: 'bg-blue-50 text-blue-700 hover:bg-blue-100 border border-transparent focus:ring-blue-500',
            text: 'bg-transparent text-blue-600 hover:bg-blue-50 focus:ring-blue-500'
        },
        error: {
            filled: 'bg-red-600 text-white hover:bg-red-700 shadow-sm border border-transparent focus:ring-red-500',
            outlined: 'bg-white border border-red-300 text-red-700 hover:bg-red-50 shadow-sm focus:ring-red-500',
            tonal: 'bg-red-50 text-red-700 hover:bg-red-100 border border-transparent focus:ring-red-500',
            text: 'bg-transparent text-red-600 hover:bg-red-50 focus:ring-red-500'
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

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'small': return 'px-3 py-1.5 text-xs'
        case 'medium': return 'px-4 py-2 text-sm'
        case 'large': return 'px-6 py-3 text-base'
        default: return 'px-4 py-2 text-sm'
    }
})

const shapeClasses = computed(() => {
    switch (props.shape) {
        case 'rounded': return 'rounded-lg'
        case 'pill': return 'rounded-full'
        case 'square': return 'rounded-none'
        default: return 'rounded-lg'
    }
})
</script>
