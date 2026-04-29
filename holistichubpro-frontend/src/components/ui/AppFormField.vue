<template>
    <div class="space-y-1">
        <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="relative">
            <slot />
            <div v-if="hint && !error" class="mt-1 text-xs text-gray-400">{{ hint }}</div>
            <div v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</div>
        </div>
    </div>
</template>

<script setup>
defineProps({ label: String, id: String, required: Boolean, error: String, hint: String })
</script>
