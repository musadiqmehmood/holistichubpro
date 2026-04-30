<template>
    <div class="space-y-1">
        <label v-if="label" :for="id" class="block text-sm font-medium text-[var(--text-secondary)]">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="relative">
            <slot />
            <div v-if="hint" class="mt-1 text-xs text-[var(--text-secondary)] opacity-70">{{ hint }}</div>
            <div v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    label: String,
    id: String,
    required: Boolean,
    error: String,
    hint: String,
})
</script>
