<template>
    <div>
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-90">SMTP Settings</h1>
            <p class="mt-1 text-sm text-neutral-50">Configure outgoing email settings for your application.</p>
        </div>

        <AppCard>
            <!-- Loading skeleton -->
            <div v-if="settingsStore.loading.smtp" class="space-y-5 animate-pulse">
                <div v-for="i in 5" :key="i" class="h-10 bg-neutral-10 rounded-lg"></div>
            </div>

            <form v-else @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Enable SMTP Toggle -->
                <AppFormField label="Enable SMTP" :error="errors.status?.[0]">
                    <AppToggle v-model="form.status">
                        {{ form.status ? 'Enabled' : 'Disabled' }}
                    </AppToggle>
                </AppFormField>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <AppFormField label="SMTP Host" id="host" required :error="errors.host?.[0]">
                        <input
                            id="host"
                            v-model="form.host"
                            type="text"
                            class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed"
                            placeholder="smtp.mailtrap.io"
                            required
                        />
                    </AppFormField>

                    <AppFormField label="SMTP Port" id="port" required :error="errors.port?.[0]">
                        <input
                            id="port"
                            v-model.number="form.port"
                            type="number"
                            class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed"
                            placeholder="587"
                            min="1"
                            max="65535"
                            required
                        />
                    </AppFormField>

                    <AppFormField label="Username" id="username" required :error="errors.username?.[0]">
                        <input
                            id="username"
                            v-model="form.username"
                            type="text"
                            class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed"
                            autocomplete="off"
                            required
                        />
                    </AppFormField>

                    <AppFormField
                        label="Password"
                        id="smtp_password"
                        :error="errors.password?.[0]"
                        hint="Leave blank to keep the existing password."
                    >
                        <input
                            id="smtp_password"
                            v-model="form.password"
                            type="password"
                            class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed"
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                    </AppFormField>

                    <AppFormField label="Encryption" id="encryption" required :error="errors.encryption?.[0]">
                        <select
                            id="encryption"
                            v-model="form.encryption"
                            class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm text-neutral-90 placeholder:text-neutral-40 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:bg-neutral-10 disabled:cursor-not-allowed"
                            required
                        >
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="none">None</option>
                        </select>
                    </AppFormField>
                </div>

                <div class="flex items-center justify-between border-t border-neutral-10 pt-5">
                    <AppButton
                        type="button"
                        variant="tonal"
                        color="primary"
                        :loading="testingSmtp"
                        @click="handleTestEmail"
                    >
                        Send Test Email
                    </AppButton>

                    <AppButton
                        type="submit"
                        variant="filled"
                        color="primary"
                        :loading="settingsStore.saving"
                    >
                        Save SMTP Settings
                    </AppButton>
                </div>
            </form>
        </AppCard>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import AppCard from '@/components/ui/AppCard.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppToggle from '@/components/ui/AppToggle.vue'

defineOptions({ name: 'SmtpSettingsView' })

const settingsStore = useSettingsStore()

const testingSmtp = ref(false)
const errors      = reactive({})

const form = reactive({
    status:     false,
    host:       '',
    port:       587,
    username:   '',
    password:   '',
    encryption: 'tls',
})

onMounted(async () => {
    await settingsStore.fetchSmtpSettings()
    const s = settingsStore.smtpSettings
    if (s) {
        form.status     = s.status     ?? false
        form.host       = s.host       ?? ''
        form.port       = s.port       ?? 587
        form.username   = s.username   ?? ''
        form.password   = ''
        form.encryption = s.encryption ?? 'tls'
    }
})

async function handleSubmit() {
    Object.keys(errors).forEach(k => delete errors[k])

    const payload = {
        status:     form.status,
        host:       form.host,
        port:       form.port,
        username:   form.username,
        encryption: form.encryption,
    }

    if (form.password && form.password.trim() && form.password !== '••••••••') {
        payload.password = form.password
    }

    try {
        await settingsStore.updateSmtpSettings(payload)
        form.password = ''
    } catch (e) {
        if (e.response?.data?.errors) Object.assign(errors, e.response.data.errors)
    }
}

async function handleTestEmail() {
    testingSmtp.value = true
    try {
        await settingsStore.testSmtp()
    } finally {
        testingSmtp.value = false
    }
}
</script>
