<template>
    <div class="space-y-1">
        <label v-if="label" :for="id" class="block text-sm font-medium text-[var(--text-secondary)]">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="relative">
            <slot :id="id" :class="selectClasses" />
            <div v-if="hint" class="mt-1 text-xs text-[var(--text-secondary)] opacity-70">{{ hint }}</div>
            <div v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</div>
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

const selectClasses = computed(() => {
    const base = 'block w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent bg-[var(--surface-color)] text-[var(--text-primary)] border-[var(--border-color)]'
    const sizeClass = props.size === 'small' ? 'px-3 py-1.5 text-sm' : 'px-4 py-2 text-base'
    return `${base} ${sizeClass}`
})
</script>
