<template>
    <div class="space-y-1">
        <label class="block text-sm font-medium" style="color: var(--text-secondary)">{{ label }}</label>
        <div class="flex items-center gap-3">
            <input
                type="color"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                class="h-10 w-10 rounded-lg border cursor-pointer"
                style="border-color: var(--border-color)"
            />
            <input
                type="text"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                class="flex-1 border rounded-lg px-3 py-1.5 text-sm font-mono"
                style="border-color: var(--border-color); background-color: var(--surface-color); color: var(--text-primary)"
            />
            <div
                class="h-8 w-8 rounded-lg border"
                :style="{ backgroundColor: modelValue, borderColor: 'var(--border-color)' }"
            ></div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    modelValue: { type: String, required: true },
    label: { type: String, default: '' }
})
defineEmits(['update:modelValue'])
</script>
