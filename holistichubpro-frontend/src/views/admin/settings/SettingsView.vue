<template>
    <div class="flex h-full min-h-0 gap-6">
        <!-- Settings Sidebar Card -->
        <aside class="w-64 shrink-0">
            <div
                class="sticky top-6 overflow-hidden rounded-2xl bg-white border border-neutral-10 shadow-sm"
            >
                <!-- Header -->
                <div class="px-5 pt-5 pb-3">
                    <h2 class="text-xs font-semibold uppercase tracking-widest text-neutral-40">Settings</h2>
                </div>

                <!-- Navigation Items -->
                <nav class="px-2 pb-3 space-y-0.5">
                    <router-link
                        v-for="item in navItems"
                        :key="item.routeName"
                        v-show="item.visible"
                        :to="item.to"
                        class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
                        :class="[
              isActive(item.to)
                ? 'bg-[var(--primary-color)]/10 text-[var(--primary-color)]'
                : 'text-neutral-60 hover:bg-neutral-5 hover:text-neutral-90',
            ]"
                    >
                        <component
                            :is="item.icon"
                            class="h-5 w-5 shrink-0"
                            :class="
                isActive(item.to)
                  ? 'text-[var(--primary-color)]'
                  : 'text-neutral-40 group-hover:text-neutral-60'
              "
                        />
                        <span>{{ item.name }}</span>
                    </router-link>
                </nav>
            </div>
        </aside>

        <!-- Main Settings Content -->
        <main class="flex-1 min-w-0 pb-10">
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

const route = useRoute()
const authStore = useAuthStore()

const isActive = (to) => {
    const name = typeof to === 'object' ? to.name : null
    if (!name) return false
    return route.name === name
}

const navItems = computed(() =>
    [
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
    ]
        .sort((a, b) => a.name.localeCompare(b.name))
)
</script>
