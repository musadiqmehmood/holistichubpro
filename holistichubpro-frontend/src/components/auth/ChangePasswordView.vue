<template>
    <div class="min-h-screen flex items-center justify-center bg-neutral-10 p-4">
        <AppCard padding="large" class="w-full max-w-md">
            <h2 class="text-headline-small font-bold mb-2">Change Password</h2>
            <p class="text-body-medium text-neutral-60 mb-6">
                {{ isForcedChange ? 'Your password has expired. Please create a new one.' : 'Update your password for security' }}
            </p>

            <AppAlert v-if="error" type="error" class="mb-4">{{ error }}</AppAlert>

            <form @submit.prevent="handleSubmit" class="space-y-4">
                <AppFormField v-if="!isForcedChange" label="Current Password" id="current_password">
                    <input
                        id="current_password"
                        v-model="form.current_password"
                        type="password"
                        required
                        class="block w-full border border-neutral-30 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary-500"
                    />
                </AppFormField>

                <AppFormField label="New Password" id="new_password" :error="passwordErrors">
                    <input
                        id="new_password"
                        v-model="form.new_password"
                        type="password"
                        required
                        class="block w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary-500"
                        :class="passwordErrors ? 'border-error' : 'border-neutral-30'"
                    />
                </AppFormField>
                <PasswordStrength :password="form.new_password" />

                <AppFormField label="Confirm Password" id="confirm_password">
                    <input
                        id="confirm_password"
                        v-model="form.new_password_confirmation"
                        type="password"
                        required
                        class="block w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary-500"
                        :class="!passwordsMatch && form.new_password_confirmation ? 'border-error' : 'border-neutral-30'"
                    />
                    <p v-if="!passwordsMatch && form.new_password_confirmation" class="text-xs text-error mt-1">
                        Passwords do not match
                    </p>
                </AppFormField>

                <AppButton type="submit" variant="filled" class="w-full" :loading="loading" :disabled="!isValid">
                    Change Password
                </AppButton>
            </form>
        </AppCard>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'  // ✅ F-34: Removed useRoute import
import { useAuthStore } from '@/stores/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppAlert from '@/components/ui/AppAlert.vue'
import PasswordStrength from '@/components/common/PasswordStrength.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = ref({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
})
const loading = ref(false)
const error = ref('')

// ✅ F-34: Forced change derived ONLY from authStore (not from URL query param)
const isForcedChange = computed(() => authStore.requiresPasswordChange)

const passwordsMatch = computed(() => {
    return !form.value.new_password_confirmation || form.value.new_password === form.value.new_password_confirmation
})

// ✅ F-33: Updated regex to match backend (any non-alphanumeric character)
const passwordErrors = computed(() => {
    const pwd = form.value.new_password
    if (!pwd) return ''
    const errors = []
    if (pwd.length < 8) errors.push('Minimum 8 characters')
    if (!/[A-Z]/.test(pwd)) errors.push('One uppercase')
    if (!/[a-z]/.test(pwd)) errors.push('One lowercase')
    if (!/[0-9]/.test(pwd)) errors.push('One number')
    if (!/[^A-Za-z0-9]/.test(pwd)) errors.push('One special character')
    return errors.join(', ')
})

const isValid = computed(() => {
    return !passwordErrors.value && passwordsMatch.value && form.value.new_password
})

const handleSubmit = async () => {
    loading.value = true
    error.value = ''

    try {
        const result = await authStore.changePassword({
            current_password: form.value.current_password,
            password: form.value.new_password,
            password_confirmation: form.value.new_password_confirmation
        })
        if (result.success) {
            router.push('/dashboard')
        } else {
            error.value = result.error || 'Failed to change password'
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'An error occurred'
    } finally {
        loading.value = false
    }
}
</script>
