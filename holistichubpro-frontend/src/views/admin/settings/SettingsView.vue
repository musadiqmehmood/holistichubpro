<template>
    <div class="flex h-full min-h-0">
        <!-- Settings Left Sidebar -->
        <aside class="w-56 flex-shrink-0 border-r border-neutral-20 bg-white py-6 overflow-y-auto">
            <div class="px-5 mb-4">
                <h2 class="text-xs font-semibold uppercase tracking-widest text-neutral-40">Settings</h2>
            </div>

            <nav class="space-y-0.5 px-3">
                <template v-for="item in navItems" :key="item.name">
                    <router-link
                        v-if="item.visible"
                        :to="item.to"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-neutral-60 transition-all hover:bg-neutral-5 hover:text-neutral-90"
                        :class="{
                            'bg-primary-50 text-primary-700 border border-primary-100 shadow-sm': isActive(item.to)
                        }"
                    >
                        <component
                            :is="item.icon"
                            class="h-4 w-4 flex-shrink-0"
                            :class="isActive(item.to) ? 'text-primary-600' : 'text-neutral-40'"
                        />
                        {{ item.name }}
                    </router-link>
                </template>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto p-6">
            <router-view />
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
    BuildingStorefrontIcon,
    Cog6ToothIcon,
    EnvelopeIcon,
    ReceiptPercentIcon,
    ScaleIcon,
    CreditCardIcon,
    CurrencyDollarIcon,
    KeyIcon,
    CircleStackIcon,
} from '@heroicons/vue/24/outline'

defineOptions({ name: 'SettingsView' })

const route     = useRoute()
const authStore = useAuthStore()

const isActive = (to) => {
    const name = typeof to === 'object' ? to.name : null
    if (!name) return false
    return route.name === name
}

const navItems = computed(() => [
    {
        name: 'Store Settings',
        to: { name: 'StoreSettings' },
        icon: BuildingStorefrontIcon,
        visible: authStore.hasPermission('settings.view'),
    },
    {
        name: 'Site Settings',
        to: { name: 'SiteSettings' },
        icon: Cog6ToothIcon,
        visible: authStore.hasPermission('settings.view'),
    },
    {
        name: 'SMTP Settings',
        to: { name: 'SmtpSettings' },
        icon: EnvelopeIcon,
        visible: authStore.hasPermission('settings.manage'),
    },
    {
        name: 'Tax List',
        to: { name: 'TaxSettings' },
        icon: ReceiptPercentIcon,
        visible: authStore.hasPermission('taxes.view'),
    },
    {
        name: 'Units List',
        to: { name: 'UnitsSettings' },
        icon: ScaleIcon,
        visible: authStore.hasPermission('units.view'),
    },
    {
        name: 'Payment Types',
        to: { name: 'PaymentTypes' },
        icon: CreditCardIcon,
        visible: authStore.hasPermission('payment_types.view'),
    },
    {
        name: 'Currency List',
        to: { name: 'Currencies' },
        icon: CurrencyDollarIcon,
        visible: authStore.hasPermission('currencies.view'),
    },
    {
        name: 'Change Password',
        to: { name: 'SettingsPassword' },
        icon: KeyIcon,
        visible: true,
    },
    {
        name: 'Database Backup',
        to: { name: 'DatabaseBackup' },
        icon: CircleStackIcon,
        visible: authStore.isSuperAdmin,
    },
])
</script>
