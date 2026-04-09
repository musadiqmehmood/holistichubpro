<template>
    <div class="space-y-1">
        <label v-if="label" :for="id" class="block text-sm font-medium text-neutral-70">
            {{ label }} <span v-if="required" class="text-error">*</span>
        </label>
        <div class="relative">
            <slot :id="id" :class="inputClasses" />
            <div v-if="hint" class="mt-1 text-xs text-neutral-50">{{ hint }}</div>
            <div v-if="error" class="mt-1 text-xs text-error">{{ error }}</div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: String,
    id: String,
    required: Boolean,
    error: String,
    hint: String,
    size: { type: String, default: 'medium' },
})

const inputClasses = computed(() => {
    let base = 'block w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent disabled:bg-neutral-10 disabled:cursor-not-allowed'
    const sizeClass = props.size === 'small' ? 'px-3 py-1.5 text-sm' : 'px-4 py-2 text-base'
    const borderClass = props.error ? 'border-error' : 'border-neutral-30'
    return `${base} ${sizeClass} ${borderClass}`
})
</script>
