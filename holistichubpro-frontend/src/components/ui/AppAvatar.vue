<template>
    <div
        :class="[
            'rounded-full flex items-center justify-center font-semibold text-white',
            sizeClasses,
        ]"
        :style="{ backgroundColor: bgColor || 'var(--primary-color)' }"
    >
        <slot>
            <span v-if="src">
                <img :src="src" :alt="alt" class="rounded-full w-full h-full object-cover" />
            </span>
            <span v-else>{{ initials }}</span>
        </slot>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    src: String,
    alt: String,
    name: String,
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
    bgColor: { type: String, default: null },
})

const initials = computed(() => {
    if (!props.name) return '?'
    return props.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const sizeClasses = computed(() => {
    const sizes = { sm: 'w-8 h-8 text-xs', md: 'w-10 h-10 text-sm', lg: 'w-12 h-12 text-base' }
    return sizes[props.size]
})
</script>
