<template>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 bg-white border-t border-gray-100">
        <!-- Text left -->
        <p class="text-sm text-gray-500 whitespace-nowrap">
            Showing
            <span class="font-medium text-gray-700">{{ effectiveFrom }}</span>
            to
            <span class="font-medium text-gray-700">{{ effectiveTo }}</span>
            of
            <span class="font-medium text-gray-700">{{ total }}</span>
            results
        </p>

        <!-- Buttons right -->
        <nav class="inline-flex items-center -space-x-px rounded-md shadow-sm" aria-label="Pagination">
            <!-- Previous -->
            <button
                @click="$emit('change', currentPage - 1)"
                :disabled="currentPage <= 1"
                class="relative inline-flex items-center rounded-l-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span class="hidden sm:inline">Previous</span>
            </button>

            <!-- Page numbers -->
            <button
                v-for="page in pages"
                :key="page"
                @click="page !== '...' && $emit('change', page)"
                :disabled="page === '...'"
                :class="[
          page === currentPage
            ? 'z-10 bg-[var(--primary-color)]/10 border-[var(--primary-color)] text-[var(--primary-color)] font-semibold'
            : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50',
          'relative inline-flex items-center border px-4 py-2 text-sm font-medium'
        ]"
            >
                {{ page }}
            </button>

            <!-- Next -->
            <button
                @click="$emit('change', currentPage + 1)"
                :disabled="currentPage >= lastPage"
                class="relative inline-flex items-center rounded-r-md border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span class="hidden sm:inline">Next</span>
                <svg class="w-4 h-4 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </nav>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    currentPage: { type: Number, required: true },
    lastPage:    { type: Number, required: true },
    total:       { type: Number, required: true },
    from:        { type: Number, default: 0 },
    to:          { type: Number, default: 0 },
    perPage:     { type: Number, default: 15 }
})

defineEmits(['change'])

const effectiveFrom = computed(() => {
    if (props.from > 0) return props.from
    if (props.total === 0) return 0
    return (props.currentPage - 1) * props.perPage + 1
})

const effectiveTo = computed(() => {
    if (props.to > 0) return props.to
    if (props.total === 0) return 0
    return Math.min(props.currentPage * props.perPage, props.total)
})

const pages = computed(() => {
    const current = props.currentPage
    const last = props.lastPage
    if (last <= 1) return []
    const delta = 2
    const range = []
    let l

    for (let i = 1; i <= last; i++) {
        if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
            range.push(i)
        }
    }

    const rangeWithDots = []
    range.forEach(i => {
        if (l) {
            if (i - l === 2) {
                rangeWithDots.push(l + 1)
            } else if (i - l !== 1) {
                rangeWithDots.push('...')
            }
        }
        rangeWithDots.push(i)
        l = i
    })

    return rangeWithDots
})
</script>
