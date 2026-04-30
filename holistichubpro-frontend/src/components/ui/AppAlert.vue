<template>
    <div
        :class="[
            'p-4 rounded-lg flex items-start gap-3 border',
            typeClasses
        ]"
    >
        <span class="text-lg">{{ iconMap[type] }}</span>
        <div class="flex-1 text-sm">
            <slot />
        </div>
        <button v-if="dismissible" @click="$emit('dismiss')" class="text-current opacity-70 hover:opacity-100">
            ×
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: { type: String, default: 'info', validator: (v) => ['info', 'success', 'warning', 'error'].includes(v) },
    dismissible: Boolean,
})

defineEmits(['dismiss'])

const iconMap = {
    info: 'ℹ️',
    success: '✅',
    warning: '⚠️',
    error: '❌',
}

const typeClasses = computed(() => {
    const base = 'border'
    switch (props.type) {
        case 'info':
            return `bg-[var(--info-color)]/10 text-[var(--info-color)] border-[var(--info-color)]/20`
        case 'success':
            return `bg-[var(--success-color)]/10 text-[var(--success-color)] border-[var(--success-color)]/20`
        case 'warning':
            return `bg-[var(--warning-color)]/10 text-[var(--warning-color)] border-[var(--warning-color)]/20`
        case 'error':
            return `bg-[var(--error-color)]/10 text-[var(--error-color)] border-[var(--error-color)]/20`
        default:
            return `bg-[var(--info-color)]/10 text-[var(--info-color)] border-[var(--info-color)]/20`
    }
})
</script>
