<template>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 py-3 bg-[var(--surface-color)] border-t border-[var(--border-color)]">
        <p class="text-sm text-[var(--text-secondary)] whitespace-nowrap">
            Showing {{ effectiveFrom }} to {{ effectiveTo }} of {{ effectiveTotal }} results
        </p>
        <nav class="inline-flex items-center -space-x-px rounded-md shadow-sm">
            <button @click="$emit('change', currentPageVal - 1)" :disabled="currentPageVal <= 1" class="relative inline-flex items-center rounded-l-md border border-[var(--border-color)] bg-[var(--surface-color)] px-3 py-2 text-sm font-medium text-[var(--text-secondary)] hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Previous
            </button>
            <button v-for="page in pages" :key="page" @click="page !== '...' && $emit('change', page)" :disabled="page === '...'" :class="[page === currentPageVal ? 'z-10 bg-[var(--primary-color)]/10 border-[var(--primary-color)] text-[var(--primary-color)] font-semibold' : 'bg-[var(--surface-color)] border-[var(--border-color)] text-[var(--text-secondary)] hover:bg-gray-50', 'relative inline-flex items-center border px-4 py-2 text-sm font-medium']">
                {{ page }}
            </button>
            <button @click="$emit('change', currentPageVal + 1)" :disabled="currentPageVal >= lastPageVal" class="relative inline-flex items-center rounded-r-md border border-[var(--border-color)] bg-[var(--surface-color)] px-3 py-2 text-sm font-medium text-[var(--text-secondary)] hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                Next
            </button>
        </nav>
    </div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ currentPage: Number, lastPage: Number, total: Number, from: Number, to: Number, perPage: { type: Number, default: 15 } })
defineEmits(['change'])

const currentPageVal = computed(() => Number.isFinite(props.currentPage) ? props.currentPage : 1)
const lastPageVal = computed(() => Number.isFinite(props.lastPage) ? props.lastPage : 1)
const totalVal = computed(() => Number.isFinite(props.total) ? props.total : 0)
const effectiveFrom = computed(() => { if (props.from > 0) return props.from; if (totalVal.value === 0) return 0; return (currentPageVal.value - 1) * props.perPage + 1 })
const effectiveTo = computed(() => { if (props.to > 0) return props.to; if (totalVal.value === 0) return 0; return Math.min(currentPageVal.value * props.perPage, totalVal.value) })
const effectiveTotal = computed(() => totalVal.value)
const pages = computed(() => {
    const current = currentPageVal.value, last = lastPageVal.value, delta = 2, range = []
    if (last <= 1) return []
    for (let i = 1; i <= last; i++) { if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) range.push(i) }
    const withDots = []; let l; range.forEach(i => { if (l) { if (i - l === 2) withDots.push(l + 1); else if (i - l !== 1) withDots.push('...') } withDots.push(i); l = i })
    return withDots
})
</script>
