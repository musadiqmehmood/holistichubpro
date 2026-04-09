<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 to-primary-100 p-4">
        <!-- ✅ F-36: Changed to-primary-100 (secondary-50 was not guaranteed) -->
        <div class="w-full max-w-md">
            <AppCard padding="large" class="shadow-elevation-3">
                <div class="text-center mb-6">
                    <h1 class="text-headline-small font-bold text-primary-600">HolisticHubPro</h1>
                    <p class="text-body-medium text-neutral-60 mt-2">Sign in to your account</p>
                </div>

                <AppAlert v-if="error" type="error" class="mb-4">{{ error }}</AppAlert>
                <AppAlert v-if="isLocked" type="warning" class="mb-4">
                    Account locked. Try again in {{ lockoutMinutes }} minutes.
                </AppAlert>

                <form @submit.prevent="handleLogin" class="space-y-4">
                    <AppFormField label="Email" id="email" :error="errors.email">
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="admin@example.com"
                            required
                            :disabled="loading || isLocked"
                            class="block w-full border rounded-lg px-4 py-2 text-base focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent disabled:bg-neutral-10"
                            :class="errors.email ? 'border-error' : 'border-neutral-30'"
                        />
                    </AppFormField>

                    <AppFormField label="Password" id="password" :error="errors.password">
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                required
                                :disabled="loading || isLocked"
                                class="block w-full border rounded-lg px-4 py-2 text-base focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent disabled:bg-neutral-10 pr-10"
                                :class="errors.password ? 'border-error' : 'border-neutral-30'"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-neutral-50 hover:text-neutral-70"
                            >
                                {{ showPassword ? '🙈' : '👁' }}
                            </button>
                        </div>
                    </AppFormField>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" v-model="form.remember" class="rounded border-neutral-30 text-primary-600 focus:ring-primary-500" />
                            <span class="text-sm text-neutral-70">Remember me</span>
                        </label>
                    </div>

                    <AppButton type="submit" variant="filled" size="large" class="w-full" :loading="loading" :disabled="!isValid">
                        Sign In
                    </AppButton>
                </form>

                <p class="text-center text-xs text-neutral-50 mt-6">
                    Demo: admin@holistichubpro.com / Admin@123!
                </p>
            </AppCard>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppAlert from '@/components/ui/AppAlert.vue'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({ email: '', password: '', remember: false })
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')
const errors = ref({})
const isLocked = ref(false)
const lockoutMinutes = ref(30)

// ✅ F-37: Changed min length from 6 to 8 (matches backend requirement)
const isValid = computed(() => form.email.includes('@') && form.password.length >= 8)

const handleLogin = async () => {
    loading.value = true
    error.value = ''
    errors.value = {}
    isLocked.value = false

    try {
        // ✅ F-38: Pass full form including remember flag
        const result = await authStore.login({
            email: form.email,
            password: form.password,
            remember: form.remember
        })
        if (result.success) {
            router.push('/dashboard')
        } else if (result.requiresPasswordChange) {
            router.push('/change-password?forced=true')
        } else if (result.requiresVerification) {
            router.push('/verify-email')
        } else {
            if (authStore.error?.includes('locked')) {
                isLocked.value = true
                const match = authStore.error.match(/(\d+)/)
                if (match) lockoutMinutes.value = parseInt(match[1])
            }
            error.value = authStore.error || 'Login failed'
        }
    } catch (err) {
        error.value = 'An unexpected error occurred'
    } finally {
        loading.value = false
    }
}
</script>
