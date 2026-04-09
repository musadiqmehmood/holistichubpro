<template>
  <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold', colorClasses]">
    <slot>{{ text }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    text: String,
    color: { type: String, default: 'gray' },
})

const colorClasses = computed(() => {
    const colors = {
        gray: 'bg-gray-100 text-gray-700 border border-gray-200',
        primary: 'bg-blue-100 text-blue-700 border border-blue-200',
        success: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        warning: 'bg-amber-100 text-amber-700 border border-amber-200',
        error: 'bg-red-100 text-red-700 border border-red-200',
    }
    return colors[props.color] || colors.gray
})
</script>
