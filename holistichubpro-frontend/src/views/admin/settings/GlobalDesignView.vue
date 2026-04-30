<template>
    <div class="space-y-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold" style="color: var(--heading-color)">Global Design</h1>
            <p class="mt-1 text-sm" style="color: var(--text-secondary)">
                Customize the visual appearance of your entire application.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Controls -->
            <div class="lg:col-span-2 space-y-6">
                <AppCard padding="medium">
                    <form @submit.prevent="handleSave" class="space-y-6">
                        <!-- Theme Mode (instant toggle) -->
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-semibold" style="color: var(--heading-color)">Theme Mode</h3>
                                <p class="text-sm" style="color: var(--text-secondary)">
                                    {{ form.darkMode ? 'Dark mode is active.' : 'Switch to dark appearance instantly.' }}
                                </p>
                            </div>
                            <AppToggle v-model="form.darkMode" @update:modelValue="toggleTheme" />
                        </div>

                        <!-- Dark mode info message -->
                        <div v-if="form.darkMode" class="p-4 rounded-lg border" style="background: var(--primary-color)/10; border-color: var(--primary-color)/20;">
                            <p class="text-sm font-medium" style="color: var(--primary-color)">
                                🌙 Dark Mode is active. All color settings below are disabled – dark mode uses its own professional palette.
                                Disable Dark Mode to customize individual colors.
                            </p>
                        </div>

                        <!-- Settings disabled when dark -->
                        <div :class="{ 'opacity-50 pointer-events-none': form.darkMode }">
                            <!-- Brand Colors -->
                            <div class="mb-6">
                                <h3 class="text-base font-semibold mb-3" style="color: var(--heading-color)">Brand Colors</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <ColorPicker v-model="form.primaryColor" label="Primary Color" />
                                    <ColorPicker v-model="form.secondaryColor" label="Secondary Color" />
                                </div>
                            </div>

                            <!-- Background & Surface -->
                            <div class="mb-6">
                                <h3 class="text-base font-semibold mb-3" style="color: var(--heading-color)">Background & Surfaces</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <ColorPicker v-model="form.backgroundColor" label="Background Color" />
                                    <ColorPicker v-model="form.surfaceColor" label="Surface Color (Cards)" />
                                </div>
                            </div>

                            <!-- Text Colors -->
                            <div class="mb-6">
                                <h3 class="text-base font-semibold mb-3" style="color: var(--heading-color)">Text Colors</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    <ColorPicker v-model="form.headingColor" label="Heading Color" />
                                    <ColorPicker v-model="form.subheadingColor" label="Subheading Color" />
                                    <ColorPicker v-model="form.textPrimary" label="Primary Text" />
                                    <ColorPicker v-model="form.textSecondary" label="Secondary Text" />
                                    <ColorPicker v-model="form.paragraphColor" label="Paragraph Color" />
                                </div>
                            </div>

                            <!-- Menu & Borders -->
                            <div class="mb-6">
                                <h3 class="text-base font-semibold mb-3" style="color: var(--heading-color)">Menu & Borders</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <ColorPicker v-model="form.menuBg" label="Menu Background" />
                                    <ColorPicker v-model="form.menuText" label="Menu Text Color" />
                                    <ColorPicker v-model="form.borderColor" label="Form Border Color" />
                                </div>
                            </div>

                            <!-- Button Colors -->
                            <div class="mb-6">
                                <h3 class="text-base font-semibold mb-3" style="color: var(--heading-color)">Button Colors</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <ColorPicker v-model="form.btnBg" label="Button Background" />
                                    <ColorPicker v-model="form.btnText" label="Button Text" />
                                    <ColorPicker v-model="form.btnHoverBg" label="Button Hover Background" />
                                </div>
                            </div>
                        </div>

                        <!-- Typography (always enabled) -->
                        <div>
                            <h3 class="text-base font-semibold mb-3" style="color: var(--heading-color)">Typography</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <AppFormField label="Font Family">
                                    <select
                                        v-model="form.fontFamily"
                                        class="w-full border rounded-lg px-3 py-2 text-sm"
                                        style="border-color: var(--border-color); background-color: var(--surface-color); color: var(--text-primary)"
                                    >
                                        <option value="Montserrat, Roboto, system-ui, sans-serif">Montserrat (default)</option>
                                        <option value="Roboto, system-ui, sans-serif">Roboto</option>
                                        <option value="system-ui, sans-serif">System UI</option>
                                        <option value="'Open Sans', sans-serif">Open Sans</option>
                                        <option value="'Poppins', sans-serif">Poppins</option>
                                    </select>
                                </AppFormField>
                                <AppFormField label="Base Font Size">
                                    <select
                                        v-model="form.fontSizeBase"
                                        class="w-full border rounded-lg px-3 py-2 text-sm"
                                        style="border-color: var(--border-color); background-color: var(--surface-color); color: var(--text-primary)"
                                    >
                                        <option value="14px">14px</option>
                                        <option value="16px">16px</option>
                                        <option value="18px">18px</option>
                                        <option value="20px">20px</option>
                                    </select>
                                </AppFormField>
                                <AppFormField label="Heading Scale">
                                    <select
                                        v-model="form.fontSizeHeadingScale"
                                        class="w-full border rounded-lg px-3 py-2 text-sm"
                                        style="border-color: var(--border-color); background-color: var(--surface-color); color: var(--text-primary)"
                                    >
                                        <option value="1">1x</option>
                                        <option value="1.15">1.15x</option>
                                        <option value="1.25">1.25x</option>
                                        <option value="1.5">1.5x</option>
                                    </select>
                                </AppFormField>
                                <AppFormField label="Body Line Height">
                                    <select
                                        v-model="form.bodyLineHeight"
                                        class="w-full border rounded-lg px-3 py-2 text-sm"
                                        style="border-color: var(--border-color); background-color: var(--surface-color); color: var(--text-primary)"
                                    >
                                        <option value="1.5">1.5 (Normal)</option>
                                        <option value="1.25">1.25 (Tight)</option>
                                        <option value="1.75">1.75 (Relaxed)</option>
                                        <option value="2">2 (Loose)</option>
                                    </select>
                                </AppFormField>
                                <AppFormField label="Letter Spacing">
                                    <select
                                        v-model="form.letterSpacing"
                                        class="w-full border rounded-lg px-3 py-2 text-sm"
                                        style="border-color: var(--border-color); background-color: var(--surface-color); color: var(--text-primary)"
                                    >
                                        <option value="normal">Normal</option>
                                        <option value="-0.05em">Tight (-0.05em)</option>
                                        <option value="0.05em">Wide (+0.05em)</option>
                                        <option value="0.1em">Extra Wide (+0.1em)</option>
                                    </select>
                                </AppFormField>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t" style="border-color: var(--border-color)">
                            <AppButton type="button" variant="tonal" color="neutral" @click="resetDefaults">
                                Reset to Defaults
                            </AppButton>
                            <AppButton
                                type="submit"
                                variant="filled"
                                color="primary"
                                :loading="settingsStore.saving"
                            >
                                Apply Changes
                            </AppButton>
                        </div>
                    </form>
                </AppCard>
            </div>

            <!-- Right: Live Preview -->
            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-4">
                    <AppCard padding="medium">
                        <h3 class="text-base font-semibold mb-2" style="color: var(--heading-color)">Live Preview</h3>
                        <div
                            class="space-y-4 transition-all duration-300"
                            :style="{
                fontFamily: form.fontFamily,
                fontSize: form.fontSizeBase,
                lineHeight: form.bodyLineHeight,
                letterSpacing: form.letterSpacing
              }"
                        >
                            <div>
                                <h1 :style="{ fontSize: 'calc(1.5rem * ' + form.fontSizeHeadingScale + ')', color: form.headingColor }">Heading 1</h1>
                                <h2 :style="{ fontSize: 'calc(1.25rem * ' + form.fontSizeHeadingScale + ')', color: form.headingColor }">Heading 2</h2>
                                <h3 :style="{ fontSize: 'calc(1.125rem * ' + form.fontSizeHeadingScale + ')', color: form.subheadingColor }">Heading 3</h3>
                            </div>
                            <p :style="{ color: form.paragraphColor }">
                                This is a paragraph of example text to demonstrate how your body copy will appear.
                            </p>
                            <div class="flex gap-2">
                                <AppButton variant="filled" color="primary" size="small">Primary</AppButton>
                                <AppButton variant="outlined" color="neutral" size="small">Secondary</AppButton>
                            </div>
                            <input
                                type="text"
                                class="w-full border rounded-lg px-3 py-2 text-sm"
                                placeholder="Sample input"
                                :style="{
                  borderColor: form.borderColor,
                  backgroundColor: form.surfaceColor,
                  color: form.textPrimary
                }"
                            />
                            <div
                                class="p-3 rounded-lg flex items-center gap-2"
                                :style="{ backgroundColor: form.menuBg, color: form.menuText }"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span class="text-sm">Menu item</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-8 w-8 rounded-lg" :style="{ backgroundColor: form.secondaryColor }"></div>
                                <span class="text-sm" :style="{ color: form.secondaryColor }">Secondary Color</span>
                            </div>
                            <div
                                class="px-3 py-2 rounded-lg text-sm font-medium text-center"
                                :style="{ backgroundColor: form.btnBg, color: form.btnText }"
                            >
                                Custom Button
                            </div>
                        </div>
                    </AppCard>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import { useSettingsStore } from '@/stores/settings'
import { useUiStore } from '@/stores/ui'    // ✅ for success toast
import AppCard from '@/components/ui/AppCard.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppToggle from '@/components/ui/AppToggle.vue'
import ColorPicker from './ColorPicker.vue'

defineOptions({ name: 'GlobalDesignView' })

const settingsStore = useSettingsStore()
const uiStore = useUiStore()

const defaults = {
    darkMode: false,
    primaryColor: '#6366f1',
    secondaryColor: '#8b5cf6',
    backgroundColor: '#f8fafc',
    surfaceColor: '#ffffff',
    textPrimary: '#0f172a',
    textSecondary: '#475569',
    headingColor: '#0f172a',
    subheadingColor: '#334155',
    paragraphColor: '#475569',
    borderColor: '#e2e8f0',
    menuBg: '#ffffff',
    menuText: '#0f172a',
    btnBg: '#6366f1',
    btnText: '#ffffff',
    btnHoverBg: '#4f46e5',
    fontFamily: 'Montserrat, Roboto, system-ui, sans-serif',
    fontSizeBase: '16px',
    fontSizeHeadingScale: '1.25',
    bodyLineHeight: '1.5',
    letterSpacing: 'normal'
}

const form = reactive({ ...defaults })

// Instant theme toggle
const toggleTheme = (value) => {
    settingsStore.updateDynamicSetting('themeMode', value ? 'dark' : 'light')
}

onMounted(() => {
    const ds = settingsStore.dynamicSettings
    form.darkMode = ds.themeMode === 'dark'
    Object.keys(defaults).forEach(key => {
        if (ds[key] !== undefined) form[key] = ds[key]
    })
})

const handleSave = () => {
    Object.keys(form).forEach(key => {
        if (key === 'darkMode') return
        settingsStore.updateDynamicSetting(key, form[key])
    })
    // ✅ Success toast
    uiStore.addNotification({ type: 'success', message: 'Design settings saved successfully!' })
}

const resetDefaults = () => {
    Object.assign(form, defaults)
}
</script>
