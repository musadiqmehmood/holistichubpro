<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="max-w-md w-full space-y-8 p-8 text-center">
            <h2 class="text-3xl font-bold text-gray-900">Verify Email</h2>
            <p class="text-gray-600">Please verify your email address to continue.</p>

            <button
                @click="resend"
                :disabled="loading"
                class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50"
            >
                {{ loading ? 'Sending...' : 'Resend Verification Email' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { authApi } from '@/api/auth'

const loading = ref(false)

const resend = async () => {
    loading.value = true
    try {
        await authApi.resendVerification()
        alert('Verification email sent!')
    } catch (error) {
        alert('Failed to send verification email')
    } finally {
        loading.value = false
    }
}
</script>
