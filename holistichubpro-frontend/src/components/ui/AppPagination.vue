<template>
    <div class="flex items-center justify-between px-4 py-3 bg-white border-t border-neutral-20 sm:px-6">
        <div class="flex flex-1 justify-between sm:hidden">
            <AppButton size="small" variant="outlined" @click="$emit('change', currentPage - 1)" :disabled="currentPage === 1">
                Previous
            </AppButton>
            <AppButton size="small" variant="outlined" @click="$emit('change', currentPage + 1)" :disabled="currentPage === lastPage">
                Next
            </AppButton>
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <p class="text-sm text-neutral-60">
                Showing <span class="font-medium">{{ from }}</span> to <span class="font-medium">{{ to }}</span> of
                <span class="font-medium">{{ total }}</span> results
            </p>
            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                <AppButton
                    size="small"
                    variant="outlined"
                    class="rounded-l-md rounded-r-none"
                    @click="$emit('change', currentPage - 1)"
                    :disabled="currentPage === 1"
                >
                    Previous
                </AppButton>
                <button
                    v-for="page in pages"
                    :key="page"
                    @click="page !== '...' && $emit('change', page)"
                    :disabled="page === '...'"
                    class="relative inline-flex items-center px-4 py-2 text-sm font-medium border"
                    :class="[
            page === currentPage
              ? 'z-10 bg-primary-50 border-primary-500 text-primary-600'
              : 'bg-white border-neutral-30 text-neutral-60 hover:bg-neutral-10'
          ]"
                >
                    {{ page }}
                </button>
                <AppButton
                    size="small"
                    variant="outlined"
                    class="rounded-r-md rounded-l-none"
                    @click="$emit('change', currentPage + 1)"
                    :disabled="currentPage === lastPage"
                >
                    Next
                </AppButton>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import AppButton from './AppButton.vue'

const props = defineProps({
    currentPage: Number,
    lastPage: Number,
    total: Number,
    from: Number,
    to: Number,
})

defineEmits(['change'])

const pages = computed(() => {
    const current = props.currentPage
    const last = props.lastPage
    const delta = 2
    const range = []
    const rangeWithDots = []
    let l

    for (let i = 1; i <= last; i++) {
        if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
            range.push(i)
        }
    }

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
