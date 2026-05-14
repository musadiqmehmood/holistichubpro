<template>
    <div class="relative" ref="containerRef">
        <!-- Trigger button -->
        <label v-if="label" class="block text-sm font-medium text-[var(--text-secondary)] mb-1">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>
        <button
            type="button"
            @click="toggleOpen"
            :class="[
                'w-full flex items-center justify-between border rounded-lg bg-[var(--surface-color)] text-[var(--text-primary)] border-[var(--border-color)] focus:outline-none focus:ring-2 focus:ring-[var(--primary-color)] focus:border-transparent transition-all',
                size === 'small' ? 'px-3 py-1.5 text-sm' : 'px-4 py-2 text-base',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                isOpen ? 'ring-2 ring-[var(--primary-color)] border-transparent' : ''
            ]"
            :disabled="disabled"
        >
            <span :class="{ 'text-[var(--text-secondary)] opacity-60': !displayLabel }">
                {{ displayLabel || placeholder }}
            </span>
            <svg
                :class="['w-4 h-4 text-[var(--text-secondary)] transition-transform duration-200', isOpen ? 'rotate-180' : '']"
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
                <!-- Search input -->
                <div class="p-2 border-b border-[var(--border-color)]">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--text-secondary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input
                            ref="searchInput"
                            v-model="searchQuery"
                            type="text"
                            :placeholder="searchPlaceholder"
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
                        {{ noResultsText }}
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

        <!-- Error / hint -->
        <div v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</div>
        <div v-else-if="hint" class="mt-1 text-xs text-[var(--text-secondary)] opacity-70">{{ hint }}</div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
    /** Array of options. Can be strings, objects, or { value, label } */
    options:      { type: Array,   required: true },
    /** v-model binding */
    modelValue:   { type: [String, Number, Object, null], default: null },
    /** Field name for label when options are objects */
    labelKey:     { type: String,  default: 'label' },
    /** Field name for value when options are objects */
    valueKey:     { type: String,  default: 'value' },
    /** Input label text */
    label:        { type: String,  default: '' },
    /** Placeholder when nothing selected */
    placeholder:  { type: String,  default: 'Select an option' },
    /** Placeholder for the search box */
    searchPlaceholder: { type: String, default: 'Search...' },
    /** Text when no results match */
    noResultsText:{ type: String,  default: 'No results found' },
    /** Error message */
    error:        { type: String,  default: '' },
    /** Hint text below input */
    hint:         { type: String,  default: '' },
    /** Required indicator */
    required:     { type: Boolean, default: false },
    /** Disable the control */
    disabled:     { type: Boolean, default: false },
    /** Input size */
    size:         { type: String,  default: 'medium' },
})

const emit = defineEmits(['update:modelValue', 'change'])

// ── State ─────────────────────────────────────────────
const isOpen          = ref(false)
const searchQuery     = ref('')
const highlightedIndex = ref(0)
const containerRef    = ref(null)
const searchInput     = ref(null)
const listRef         = ref(null)

// ── Filtered options ──────────────────────────────────
const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options
    const q = searchQuery.value.toLowerCase()
    return props.options.filter(opt => {
        const label = String(getLabel(opt)).toLowerCase()
        return label.includes(q)
    })
})

// ── Display label for selected value ──────────────────
const displayLabel = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') return ''
    const found = props.options.find(opt => getValue(opt) === props.modelValue)
    return found ? getLabel(found) : ''
})

// ── Helpers ───────────────────────────────────────────
function getLabel(option) {
    if (typeof option === 'string') return option
    if (typeof option === 'object' && option !== null) {
        return option[props.labelKey] ?? option.name ?? JSON.stringify(option)
    }
    return String(option)
}

function getValue(option) {
    if (typeof option === 'string') return option
    if (typeof option === 'number') return option
    if (typeof option === 'object' && option !== null) {
        return option[props.valueKey] ?? option.id ?? option
    }
    return option
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
    highlightedIndex.value = 0
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
        const list = listRef.value
        if (!list) return
        const el = list.children[highlightedIndex.value]
        if (el) el.scrollIntoView({ block: 'nearest' })
    })
}

// ── Close on click outside ────────────────────────────
function onClickOutside(e) {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        closeDropdown()
    }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))

// Reset highlight when filtered list changes
watch(filteredOptions, () => { highlightedIndex.value = 0 })
</script>
