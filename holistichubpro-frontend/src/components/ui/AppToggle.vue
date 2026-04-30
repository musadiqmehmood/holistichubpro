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
            class="relative shrink-0 rounded-full border-2 transition-all duration-200 ease-in-out"
            :class="[
        'w-11 h-6',
        modelValue
          ? 'border-[var(--primary-color)]'
          : 'border-[var(--border-color)] bg-[var(--surface-color)]',
        disabled ? 'cursor-not-allowed' : 'cursor-pointer'
      ]"
            :style="modelValue ? { backgroundColor: 'var(--primary-color)' } : {}"
        >
            <div
                class="absolute top-1/2 -translate-y-1/2 rounded-full bg-white shadow-sm transition-all duration-200 ease-in-out w-5 h-5"
                :class="modelValue ? 'left-[calc(100%-1.25rem)]' : 'left-0.5'"
            />
        </div>

        <span v-if="label" class="text-sm font-medium text-[var(--text-primary)]">{{ label }}</span>
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
