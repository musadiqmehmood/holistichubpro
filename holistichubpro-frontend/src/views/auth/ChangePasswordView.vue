<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-60">
        <div class="max-w-md w-full space-y-8 p-8">
            <div>
                <h2 class="text-center text-3xl font-bold text-gray-900">Change Password</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    {{ isExpired ? 'Your password has expired. Please create a new one.' : 'Update your password for security' }}
                </p>
            </div>

            <form class="mt-8 space-y-6" @submit.prevent="handleSubmit">
                <!-- Current Password (only if not forced change) -->
                <div v-if="!isForcedChange">
                    <label class="block text-sm font-medium text-gray-700">Current Password</label>
                    <input
                        v-model="form.current_password"
                        type="password"
                        required
                        class="mt-1 block w-full border border-gray-500 rounded-md px-3 py-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">New Password</label>
                    <input
                        v-model="form.new_password"
                        type="password"
                        required
                        class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    <!-- ✅ FIXED: Password strength indicator -->
                    <PasswordStrength :password="form.new_password" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input
                        v-model="form.new_password_confirmation"
                        type="password"
                        required
                        class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-primary-500 focus:border-primary-500"
                    />
                    <p v-if="!passwordsMatch" class="mt-1 text-sm text-red-600">Passwords do not match</p>
                </div>

                <div v-if="error" class="text-red-600 text-sm bg-red-50 p-3 rounded">{{ error }}</div>

                <button
                    type="submit"
                    :disabled="!isValid || loading"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50"
                >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ loading ? 'Changing...' : 'Change Password' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import PasswordStrength from '@/components/common/PasswordStrength.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const form = ref({
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
})

const loading = ref(false)
const error = ref('')

// ✅ FIXED: Check if password change is forced
const isExpired = computed(() => authStore.requiresPasswordChange)
const isForcedChange = computed(() => authStore.requiresPasswordChange)

const passwordsMatch = computed(() => {
    return !form.value.new_password_confirmation ||
        form.value.new_password === form.value.new_password_confirmation
})

// ✅ FIXED: Password validation
const isValid = computed(() => {
    const pwd = form.value.new_password
    const hasMinLength = pwd.length >= 8
    const hasUpper = /[A-Z]/.test(pwd)
    const hasLower = /[a-z]/.test(pwd)
    const hasNumber = /[0-9]/.test(pwd)
    const hasSpecial = /[@$!%*?&]/.test(pwd)

    return hasMinLength && hasUpper && hasLower && hasNumber && hasSpecial && passwordsMatch.value
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
