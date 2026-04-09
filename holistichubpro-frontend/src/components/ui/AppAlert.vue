<template>
    <div
        :class="[
      'p-4 rounded-lg flex items-start gap-3',
      typeClasses,
    ]"
    >
        <span class="text-lg">{{ iconMap[type] }}</span>
        <div class="flex-1 text-sm">
            <slot />
        </div>
        <button v-if="dismissible" @click="$emit('dismiss')" class="text-current opacity-70 hover:opacity-100">
            ×
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: { type: String, default: 'info', validator: (v) => ['info', 'success', 'warning', 'error'].includes(v) },
    dismissible: Boolean,
})

defineEmits(['dismiss'])

const iconMap = {
    info: 'ℹ️',
    success: '✅',
    warning: '⚠️',
    error: '❌',
}

const typeClasses = computed(() => {
    const classes = {
        info: 'bg-info/10 text-info border border-info/20',
        success: 'bg-success/10 text-success border border-success/20',
        warning: 'bg-warning/10 text-warning border border-warning/20',
        error: 'bg-error/10 text-error border border-error/20',
    }
    return classes[props.type]
})
</script>
