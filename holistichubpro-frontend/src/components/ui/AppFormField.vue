<template>
    <div class="space-y-1">
        <label v-if="label" :for="id" class="block text-sm font-medium text-neutral-70">
            {{ label }} <span v-if="required" class="text-error">*</span>
        </label>
        <div class="relative">
            <slot />
            <div v-if="hint" class="mt-1 text-xs text-neutral-50">{{ hint }}</div>
            <div v-if="error" class="mt-1 text-xs text-error">{{ error }}</div>
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
