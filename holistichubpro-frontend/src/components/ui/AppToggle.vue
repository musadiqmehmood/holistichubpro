<template>
    <label
        class="inline-flex items-center gap-3 cursor-pointer select-none"
        :class="{ 'opacity-60 cursor-not-allowed': disabled }"
    >
        <input
            type="checkbox"
            :checked="modelValue"
            :disabled="disabled"
            @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
            class="sr-only"
        />

        <div
            class="relative shrink-0 rounded-full transition-all duration-200 ease-in-out"
            :class="[
        disabled ? 'cursor-not-allowed' : 'cursor-pointer',
        'w-11 h-6',
        modelValue ? 'border-transparent' : 'border-gray-300 bg-gray-200',
      ]"
            :style="modelValue ? { backgroundColor: 'var(--primary-color)' } : {}"
        >
            <div
                class="absolute top-1/2 -translate-y-1/2 rounded-full bg-white shadow-sm transition-all duration-200 ease-in-out w-5 h-5"
                :class="modelValue ? 'left-[calc(100%-1.25rem)]' : 'left-0.5'"
            />
        </div>

        <span v-if="label" class="text-sm font-medium text-gray-700">{{ label }}</span>
        <slot v-else />
    </label>
</template>

<script setup lang="ts">
defineProps<{
    modelValue: boolean
    label?: string
    disabled?: boolean
}>()
defineEmits<{
    (e: 'update:modelValue', value: boolean): void
}>()
</script>
