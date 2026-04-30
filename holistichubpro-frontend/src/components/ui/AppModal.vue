<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
        >
            <Dialog
                v-if="isOpen"
                :open="isOpen"
                @close="handleClose"
                class="relative z-[100]"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" />

                <!-- Modal Container -->
                <div class="fixed inset-0 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
                    <DialogPanel
                        class="relative bg-[var(--surface-color)] rounded-2xl sm:rounded-[28px] shadow-2xl w-full max-w-lg flex flex-col overflow-hidden border border-[var(--border-color)]"
                        :class="panelClass"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 pt-6 pb-2">
                            <DialogTitle as="h2" class="text-xl font-semibold text-[var(--heading-color)]">
                                {{ title }}
                            </DialogTitle>
                            <button
                                @click="handleClose"
                                class="text-[var(--text-secondary)] hover:text-[var(--text-primary)] hover:bg-[var(--border-color)]/30 p-2 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--border-color)]"
                                :aria-label="'Close ' + title"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-6 py-4 overflow-y-auto max-h-[65vh]">
                            <slot />
                        </div>

                        <!-- Footer Actions -->
                        <div v-if="$slots.actions || showConfirm" class="flex items-center justify-end gap-2 px-6 py-4 mt-2 border-t border-[var(--border-color)]">
                            <slot name="actions">
                                <AppButton @click="handleClose" variant="tonal" color="neutral">
                                    {{ cancelText }}
                                </AppButton>
                                <AppButton
                                    v-if="showConfirm"
                                    @click="$emit('confirm')"
                                    :loading="loading"
                                    variant="filled"
                                    :color="confirmColor"
                                >
                                    {{ confirmText }}
                                </AppButton>
                            </slot>
                        </div>
                    </DialogPanel>
                </div>
            </Dialog>
        </Transition>
    </Teleport>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import AppButton from './AppButton.vue'

const props = defineProps({
    isOpen: Boolean,
    title: String,
    confirmText: { type: String, default: 'Confirm' },
    cancelText: { type: String, default: 'Cancel' },
    showConfirm: { type: Boolean, default: true },
    loading: { type: Boolean, default: false },
    closeOnBackdrop: { type: Boolean, default: true },
    confirmColor: { type: String, default: 'primary' },
    panelClass: { type: String, default: '' },
})

const emit = defineEmits(['close', 'confirm'])

const handleClose = () => {
    if (props.closeOnBackdrop) {
        emit('close')
    }
}
</script>
