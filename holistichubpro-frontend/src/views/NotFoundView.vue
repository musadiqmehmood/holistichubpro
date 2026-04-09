<template>
    <div class="min-h-screen flex items-center justify-center bg-neutral-10 p-4">
        <AppCard padding="large" class="text-center max-w-md">
            <h1 class="text-6xl font-bold text-primary-600">404</h1>
            <p class="text-headline-small mt-4">Page not found</p>
            <p class="text-neutral-60 mt-2">The page you're looking for doesn't exist or has been moved.</p>
            <router-link to="/dashboard">
                <AppButton class="mt-6" variant="filled">Go to Dashboard</AppButton>
            </router-link>
        </AppCard>
    </div>
</template>

<script setup>
import AppCard from '@/components/ui/AppCard.vue'
import AppButton from '@/components/ui/AppButton.vue'
</script>
