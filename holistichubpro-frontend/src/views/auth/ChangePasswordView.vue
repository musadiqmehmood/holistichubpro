<template>
    <div class="min-h-[calc(100vh-4rem)] flex items-center justify-center bg-[var(--background-color)] p-4">
        <AppCard padding="large" class="w-full max-w-md">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-neutral-90">Change Password</h2>
                <p class="mt-2 text-sm text-neutral-50">
                    {{ isForcedChange ? 'Your password has expired. Please create a new one.' : 'Update your password for security' }}
                </p>
            </div>

            <AppAlert v-if="error" type="error" class="mb-4">{{ error }}</AppAlert>

            <form @submit.prevent="handleSubmit" class="space-y-5">
                <!-- Current Password (only if not forced) -->
                <AppFormField v-if="!isForcedChange" label="Current Password" id="current_password" :error="errors.current_password">
                    <input
                        id="current_password"
                        v-model="form.current_password"
                        type="password"
                        class="block w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] outline-none"
                        required
                    />
                </AppFormField>

                <!-- New Password -->
                <AppFormField label="New Password" id="new_password" :error="errors.password || passwordErrorsString">
                    <input
                        id="new_password"
                        v-model="form.password"
                        type="password"
                        class="block w-full border rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] outline-none"
                        :class="passwordErrorsString ? 'border-red-300' : 'border-gray-300'"
                        required
                    />
                </AppFormField>

                <!-- Password Strength Meter -->
                <div class="mt-2">
                    <div class="flex gap-1 mb-2">
                        <div
                            v-for="i in 5"
                            :key="i"
                            class="h-1 flex-1 rounded-full transition-colors"
                            :class="i <= strengthScore ? strengthColor : 'bg-gray-200'"
                        />
                    </div>
                    <ul class="space-y-1 text-xs text-gray-500">
                        <li :class="checks.hasLength ? 'text-emerald-600' : ''">✓ At least 8 characters</li>
                        <li :class="checks.hasUpper ? 'text-emerald-600' : ''">✓ One uppercase letter</li>
                        <li :class="checks.hasLower ? 'text-emerald-600' : ''">✓ One lowercase letter</li>
                        <li :class="checks.hasNumber ? 'text-emerald-600' : ''">✓ One number</li>
                        <li :class="checks.hasSpecial ? 'text-emerald-600' : ''">✓ One special character (e.g., @, #, $, %, ^, &, *, !)</li>
                    </ul>
                </div>

                <!-- Confirm Password -->
                <AppFormField label="Confirm Password" id="password_confirmation" :error="errors.password_confirmation">
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="block w-full border rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] outline-none"
                        :class="confirmMismatch ? 'border-red-300' : 'border-gray-300'"
                        required
                    />
                    <p v-if="confirmMismatch" class="mt-1 text-xs text-red-500">Passwords do not match</p>
                </AppFormField>

                <AppButton type="submit" variant="filled" color="primary" class="w-full" :loading="loading" :disabled="!isSubmitEnabled">
                    Change Password
                </AppButton>
            </form>
        </AppCard>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppAlert from '@/components/ui/AppAlert.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const loading = ref(false)
const error = ref('')
const errors = reactive({})

// Determine if this is a forced change (password expired)
const isForcedChange = computed(() => authStore.requiresPasswordChange)

// Password strength checks
const checks = computed(() => {
    const pwd = form.password || ''
    return {
        hasLength: pwd.length >= 8,
        hasUpper: /[A-Z]/.test(pwd),
        hasLower: /[a-z]/.test(pwd),
        hasNumber: /[0-9]/.test(pwd),
        hasSpecial: /[^A-Za-z0-9]/.test(pwd),
    }
})

const strengthScore = computed(() => {
    const { hasLength, hasUpper, hasLower, hasNumber, hasSpecial } = checks.value
    let score = 0
    if (hasLength) score++
    if (hasUpper) score++
    if (hasLower) score++
    if (hasNumber) score++
    if (hasSpecial) score++
    return score
})

const strengthColor = computed(() => {
    const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-blue-500', 'bg-emerald-500']
    return colors[strengthScore.value - 1] || 'bg-gray-200'
})

const passwordErrorsString = computed(() => {
    const pwd = form.password
    if (!pwd) return ''
    const errs = []
    if (pwd.length < 8) errs.push('Min 8 characters')
    if (!/[A-Z]/.test(pwd)) errs.push('No uppercase')
    if (!/[a-z]/.test(pwd)) errs.push('No lowercase')
    if (!/[0-9]/.test(pwd)) errs.push('No number')
    if (!/[^A-Za-z0-9]/.test(pwd)) errs.push('No special char')
    return errs.join(', ')
})

const confirmMismatch = computed(() => {
    return form.password_confirmation && form.password !== form.password_confirmation
})

const isSubmitEnabled = computed(() => {
    const pwdValid = strengthScore.value === 5
    const confirmValid = form.password && form.password === form.password_confirmation
    if (isForcedChange.value) return pwdValid && confirmValid
    return form.current_password.length > 0 && pwdValid && confirmValid
})

const handleSubmit = async () => {
    loading.value = true
    error.value = ''
    Object.keys(errors).forEach(k => delete errors[k])

    try {
        const result = await authStore.changePassword({
            current_password: form.current_password,
            password: form.password,
            password_confirmation: form.password_confirmation,
        })

        if (result.success) {
            if (result.requiresRelogin) {
                router.push('/login')
            } else {
                router.push('/dashboard')
            }
        } else {
            error.value = result.error || 'Failed to change password'
        }
    } catch (err) {
        if (err.response?.data?.errors) {
            Object.assign(errors, err.response.data.errors)
            error.value = Object.values(err.response.data.errors).flat().join(', ')
        } else {
            error.value = err.response?.data?.message || 'An error occurred'
        }
    } finally {
        loading.value = false
    }
}
</script>
