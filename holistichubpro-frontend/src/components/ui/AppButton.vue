<template>
    <button
        :class="[
      'app-btn',
      `app-btn--${variant}`,
      `app-btn--${size}`,
      `app-btn--${shape}`,
      { 'opacity-60 pointer-events-none': disabled || loading }
    ]"
        :disabled="disabled || loading"
        v-bind="$attrs"
    >
    <span v-if="loading" class="absolute inset-0 flex items-center justify-center">
      <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </span>
        <span :class="{ 'opacity-0': loading }" class="inline-flex items-center gap-2">
      <slot />
    </span>
    </button>
</template>

<script setup>
defineProps({
    variant: { type: String, default: 'filled' },   // filled | outlined | tonal | text
    color:    { type: String, default: 'primary' },  // reserved for future use
    size:     { type: String, default: 'medium' },   // small | medium | large
    shape:    { type: String, default: 'rounded' },  // rounded | pill | square
    disabled: Boolean,
    loading:  Boolean,
})
</script>

<style scoped>
/* Base button reset */
.app-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
    outline: none;
    cursor: pointer;
}

/* Focus ring */
.app-btn:focus-visible {
    box-shadow: 0 0 0 2px var(--btn-bg, var(--primary-color));
    outline: 2px solid transparent;
    outline-offset: 2px;
}

/* ===== Variants ===== */
.app-btn--filled {
    background-color: var(--btn-bg, var(--primary-color));
    color: var(--btn-text, #ffffff);
    border: 1px solid transparent;
}
.app-btn--filled:hover:not(:disabled) {
    background-color: var(--btn-hover-bg, var(--primary-dark));
}

.app-btn--outlined {
    background-color: transparent;
    color: var(--btn-bg, var(--primary-color));
    border: 1px solid var(--btn-bg, var(--primary-color));
}
.app-btn--outlined:hover:not(:disabled) {
    background-color: var(--btn-bg, var(--primary-color));
    color: var(--btn-text, #ffffff);
}

.app-btn--tonal {
    background-color: color-mix(in srgb, var(--btn-bg, var(--primary-color)) 15%, transparent);
    color: var(--btn-bg, var(--primary-color));
    border: 1px solid transparent;
}
.app-btn--tonal:hover:not(:disabled) {
    background-color: color-mix(in srgb, var(--btn-bg, var(--primary-color)) 25%, transparent);
}

.app-btn--text {
    background-color: transparent;
    color: var(--btn-bg, var(--primary-color));
    border: 1px solid transparent;
}
.app-btn--text:hover:not(:disabled) {
    background-color: color-mix(in srgb, var(--btn-bg, var(--primary-color)) 10%, transparent);
}

/* ===== Sizes ===== */
.app-btn--small {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}
.app-btn--medium {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}
.app-btn--large {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
}

/* ===== Shapes ===== */
.app-btn--rounded {
    border-radius: 0.5rem;
}
.app-btn--pill {
    border-radius: 9999px;
}
.app-btn--square {
    border-radius: 0;
}
</style>
