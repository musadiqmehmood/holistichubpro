<template>
    <div :class="['bg-[var(--surface-color)] rounded-xl shadow-sm border border-[var(--border-color)]', paddingClasses]">
        <slot />
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    padding: { type: String, default: 'medium' },
})

const paddingClasses = computed(() => {
    switch (props.padding) {
        case 'none': return 'p-0'
        case 'small': return 'p-4'
        case 'medium': return 'p-6'
        case 'large': return 'p-8'
        default: return 'p-6'
    }
})
</script>
