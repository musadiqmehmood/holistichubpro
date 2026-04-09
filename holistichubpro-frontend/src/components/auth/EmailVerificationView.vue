<template>
    <div class="min-h-screen flex items-center justify-center bg-neutral-10 p-4">
        <AppCard padding="large" class="w-full max-w-md text-center">
            <h2 class="text-headline-small font-bold mb-2">Verify Your Email</h2>
            <p class="text-body-medium text-neutral-60 mb-6">
                We've sent a verification link to your email address. Please check your inbox and click the link to verify your account.
            </p>

            <AppAlert v-if="error" type="error" class="mb-4">{{ error }}</AppAlert>
            <AppAlert v-if="success" type="success" class="mb-4">{{ success }}</AppAlert>

            <AppButton @click="resend" variant="filled" class="w-full" :loading="loading">
                Resend Verification Email
            </AppButton>

            <p class="text-sm text-neutral-50 mt-4">
                Already verified? <router-link to="/login" class="text-primary-600">Sign in</router-link>
            </p>
        </AppCard>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { authApi } from '@/api/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppAlert from '@/components/ui/AppAlert.vue'

const authStore = useAuthStore()
const loading = ref(false)
const error = ref('')
const success = ref('')

const resend = async () => {
    loading.value = true
    error.value = ''
    success.value = ''

    try {
        // ✅ F-35: Pass the user's email (backend expects email in request body)
        const userEmail = authStore.user?.email
        if (!userEmail) {
            error.value = 'User email not found. Please log in again.'
            return
        }
        await authApi.resendVerification({ email: userEmail })
        success.value = 'Verification email sent! Please check your inbox.'
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to resend verification email'
    } finally {
        loading.value = false
    }
}
</script>
