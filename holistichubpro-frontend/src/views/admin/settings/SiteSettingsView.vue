<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-90">Site Settings</h1>
            <p class="mt-1 text-sm text-neutral-50">Configure your site name and branding.</p>
        </div>

        <AppCard>
            <div v-if="settingsStore.loading.site" class="space-y-6 animate-pulse">
                <div class="h-10 bg-neutral-10 rounded-lg w-full"></div>
                <div class="h-24 bg-neutral-10 rounded-lg w-full"></div>
            </div>

            <form v-else @submit.prevent="handleSubmit" class="space-y-6">
                <AppFormField label="Site Name" id="site_name" required :error="errors.site_name?.[0]">
                    <input
                        id="site_name"
                        v-model="form.site_name"
                        type="text"
                        class="block w-full rounded-lg border border-neutral-30 bg-white px-4 py-2.5 text-sm"
                        placeholder="HolisticHubPro"
                        required
                    />
                </AppFormField>

                <AppFormField label="Site Logo" id="site_logo" :error="errors.site_logo?.[0]">
                    <div class="flex items-start gap-4">
                        <!-- Always show the current logo URL if available, otherwise preview -->
                        <div
                            v-if="logoPreview || currentLogoUrl"
                            class="flex-shrink-0 h-20 w-20 rounded-xl border border-neutral-20 overflow-hidden bg-neutral-5"
                        >
                            <img
                                :src="logoPreview || currentLogoUrl"
                                alt="Site logo preview"
                                class="h-full w-full object-contain"
                            />
                        </div>

                        <label class="flex flex-1 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-neutral-20 px-6 py-5 hover:border-primary-400">
                            <svg class="mb-2 h-7 w-7 text-neutral-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            <span class="text-sm font-medium text-neutral-60">Click to upload image</span>
                            <span class="text-xs text-neutral-40 mt-1">PNG, JPG, GIF, SVG up to 20 MB</span>
                            <input type="file" class="hidden" accept="image/*" @change="handleLogoChange" />
                        </label>
                    </div>
                </AppFormField>

                <div class="flex items-center justify-end gap-3 border-t border-neutral-10 pt-5">
                    <AppButton type="submit" variant="filled" color="primary" :loading="settingsStore.saving">
                        Save Changes
                    </AppButton>
                </div>
            </form>
        </AppCard>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import AppCard from '@/components/ui/AppCard.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppButton from '@/components/ui/AppButton.vue'

defineOptions({ name: 'SiteSettingsView' })

const settingsStore = useSettingsStore()

const form = reactive({ site_name: '' })
const logoFile = ref(null)
const logoPreview = ref(null)
const errors = reactive({})

// This computed property will update automatically when settingsStore.siteSettings changes
const currentLogoUrl = computed(() => settingsStore.siteSettings?.site_logo_url ?? null)

onMounted(async () => {
    await settingsStore.fetchSiteSettings()
    form.site_name = settingsStore.siteSettings?.site_name ?? ''
    // Reset preview after load
    logoPreview.value = null
})

function handleLogoChange(event) {
    const file = event.target.files[0]
    if (!file) return
    logoFile.value = file
    // Revoke old preview to avoid memory leaks
    if (logoPreview.value) URL.revokeObjectURL(logoPreview.value)
    logoPreview.value = URL.createObjectURL(file)
}

async function handleSubmit() {
    Object.keys(errors).forEach(k => delete errors[k])

    const formData = new FormData()
    formData.append('site_name', form.site_name)
    if (logoFile.value) {
        formData.append('site_logo', logoFile.value)
    }

    try {
        // This will update the store and return the updated site settings object
        await settingsStore.updateSiteSettings(formData)
        // Refresh the form data from the updated store
        form.site_name = settingsStore.siteSettings?.site_name ?? ''
        // Clear preview and file input
        if (logoPreview.value) {
            URL.revokeObjectURL(logoPreview.value)
            logoPreview.value = null
        }
        logoFile.value = null
    } catch (e) {
        if (e.response?.data?.errors) Object.assign(errors, e.response.data.errors)
    }
}
</script>
