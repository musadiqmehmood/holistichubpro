<template>
    <div class="relative" ref="containerRef">
        <!-- Label -->
        <label v-if="label" class="block text-sm font-medium text-[var(--text-secondary)] mb-1">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- Legacy slot mode — renders native <select> passed by parent -->
        <div v-if="hasSlot" class="relative">
            <slot :id="id" :class="selectClasses" />
            <div v-if="hint" class="mt-1 text-xs text-[var(--text-secondary)] opacity-70">{{ hint }}</div>
            <div v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</div>
        </div>

        <!-- New searchable dropdown mode -->
        <template v-else>
            <button
                type="button"
                @click="toggleOpen"
                :class="[
                    'w-full flex items-center justify-between border rounded-lg bg-[var(--surface-color)] text-[var(--text-primary)] border-[var(--border-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition-all',
                    size === 'small' ? 'px-3 py-1.5 text-sm' : 'px-4 py-2 text-base',
                    disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                    isOpen ? 'ring-2 ring-[var(--primary-color)] border-transparent' : '',
                    error ? 'border-red-500' : ''
                ]"
                :disabled="disabled"
            >
                <span :class="{ 'text-[var(--text-secondary)] opacity-60': !displayLabel }">
                    {{ displayLabel || placeholder }}
                </span>
                <svg
                    :class="['w-4 h-4 text-[var(--text-secondary)] transition-transform duration-200', isOpen && shouldSearch ? 'rotate-180' : '']"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Dropdown panel -->
            <transition
                enter-active-class="transition ease-out duration-100"
                enter-from-class="transform opacity-0 scale-95"
                enter-to-class="transform opacity-100 scale-100"
                leave-active-class="transition ease-in duration-75"
                leave-from-class="transform opacity-100 scale-100"
                leave-to-class="transform opacity-0 scale-95"
            >
                <div
                    v-if="isOpen"
                    class="absolute z-50 mt-1 w-full bg-[var(--surface-color)] border border-[var(--border-color)] rounded-lg shadow-lg max-h-64 flex flex-col"
                >
                    <!-- Search input (only when searchable + enough options) -->
                    <div v-if="shouldSearch" class="p-2 border-b border-[var(--border-color)]">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input
                                ref="searchInput"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search..."
                                class="w-full pl-9 pr-3 py-1.5 text-sm bg-[var(--background-color)] border border-[var(--border-color)] rounded-md focus:outline-none focus:ring-1 focus:ring-[var(--primary-color)] text-[var(--text-primary)] placeholder-[var(--text-secondary)]"
                                @keydown.esc="closeDropdown"
                                @keydown.down.prevent="highlightNext"
                                @keydown.up.prevent="highlightPrev"
                                @keydown.enter.prevent="selectHighlighted"
                            />
                        </div>
                    </div>

                    <!-- Options list -->
                    <div class="overflow-y-auto flex-1 py-1" ref="listRef">
                        <div
                            v-if="filteredOptions.length === 0"
                            class="px-4 py-3 text-sm text-[var(--text-secondary)] text-center"
                        >
                            No results
                        </div>
                        <button
                            v-for="(option, index) in filteredOptions"
                            :key="getValue(option)"
                            type="button"
                            @click="selectOption(option)"
                            @mouseenter="highlightedIndex = index"
                            :class="[
                                'w-full text-left px-4 py-2 text-sm transition-colors flex items-center justify-between',
                                index === highlightedIndex ? 'bg-[var(--primary-color)] bg-opacity-10 text-[var(--primary-color)]' : 'text-[var(--text-primary)] hover:bg-[var(--background-color)]',
                                isSelected(option) ? 'font-semibold' : ''
                            ]"
                        >
                            <span>{{ getLabel(option) }}</span>
                            <svg
                                v-if="isSelected(option)"
                                class="w-4 h-4 text-[var(--primary-color)] flex-shrink-0"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </transition>

            <!-- Hint / Error -->
            <div v-if="hint" class="mt-1 text-xs text-[var(--text-secondary)] opacity-70">{{ hint }}</div>
            <div v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</div>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount, useSlots } from 'vue'

const props = defineProps({
    label:       { type: String,  default: '' },
    id:          { type: String,  default: '' },
    required:    { type: Boolean, default: false },
    error:       { type: String,  default: '' },
    hint:        { type: String,  default: '' },
    size:        { type: String,  default: 'medium' },
    disabled:    { type: Boolean, default: false },
    /** Array of options: strings, numbers, or objects */
    options:     { type: Array,   default: () => [] },
    /** v-model value */
    modelValue:  { type: [String, Number, Object, null], default: null },
    /** Object key for display label */
    optionLabel: { type: String,  default: 'label' },
    /** Object key for option value */
    optionValue: { type: String,  default: 'value' },
    /** Placeholder when nothing selected */
    placeholder: { type: String,  default: 'Select...' },
    /** Enable search when options > 7 (default true) */
    searchable:  { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'change'])
const slots = useSlots()

// Legacy slot detection
const hasSlot = computed(() => !!slots.default)

// ── State ─────────────────────────────────────────────
const isOpen           = ref(false)
const searchQuery      = ref('')
const highlightedIndex = ref(0)
const containerRef     = ref(null)
const searchInput      = ref(null)
const listRef          = ref(null)

// Show search when enabled AND enough options
const shouldSearch = computed(() => props.searchable && props.options.length > 7)

// ── Filtered options ──────────────────────────────────
const filteredOptions = computed(() => {
    if (!shouldSearch.value || !searchQuery.value) return props.options
    const q = searchQuery.value.toLowerCase()
    return props.options.filter(opt => String(getLabel(opt)).toLowerCase().includes(q))
})

// ── Display label ─────────────────────────────────────
const displayLabel = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') return ''
    const found = props.options.find(opt => getValue(opt) === props.modelValue)
    return found ? getLabel(found) : ''
})

// ── Helpers ───────────────────────────────────────────
function getLabel(option) {
    if (typeof option === 'string' || typeof option === 'number') return option
    return option?.[props.optionLabel] ?? option?.name ?? JSON.stringify(option)
}

function getValue(option) {
    if (typeof option === 'string' || typeof option === 'number') return option
    return option?.[props.optionValue] ?? option?.id ?? option
}

function isSelected(option) {
    return getValue(option) === props.modelValue
}

// ── Actions ───────────────────────────────────────────
function toggleOpen() {
    if (props.disabled) return
    isOpen.value ? closeDropdown() : openDropdown()
}

function openDropdown() {
    isOpen.value = true
    searchQuery.value = ''
    highlightedIndex.value = filteredOptions.value.findIndex(isSelected)
    if (highlightedIndex.value < 0) highlightedIndex.value = 0
    nextTick(() => {
        searchInput.value?.focus()
        scrollToHighlighted()
    })
}

function closeDropdown() {
    isOpen.value = false
    searchQuery.value = ''
}

function selectOption(option) {
    emit('update:modelValue', getValue(option))
    emit('change', option)
    closeDropdown()
}

function selectHighlighted() {
    const opt = filteredOptions.value[highlightedIndex.value]
    if (opt) selectOption(opt)
}

function highlightNext() {
    if (highlightedIndex.value < filteredOptions.value.length - 1) {
        highlightedIndex.value++
        scrollToHighlighted()
    }
}

function highlightPrev() {
    if (highlightedIndex.value > 0) {
        highlightedIndex.value--
        scrollToHighlighted()
    }
}

function scrollToHighlighted() {
    nextTick(() => {
        const el = listRef.value?.children[highlightedIndex.value]
        if (el) el.scrollIntoView({ block: 'nearest' })
    })
}

// ── Click outside ─────────────────────────────────────
function onClickOutside(e) {
    if (containerRef.value && !containerRef.value.contains(e.target)) closeDropdown()
}
onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))

watch(filteredOptions, () => { highlightedIndex.value = 0 })

// ── Legacy select classes ─────────────────────────────
const selectClasses = computed(() => {
    const base = 'block w-full border rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent bg-[var(--surface-color)] text-[var(--text-primary)] border-[var(--border-color)]'
    const sizeClass = props.size === 'small' ? 'px-3 py-1.5 text-sm' : 'px-4 py-2 text-base'
    return `${base} ${sizeClass}`
})
</script>
