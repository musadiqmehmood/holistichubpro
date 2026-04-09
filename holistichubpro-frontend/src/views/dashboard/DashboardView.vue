<template>
    <div>
        <h1 class="text-headline-medium font-bold mb-6">Dashboard</h1>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Total Users Stat -->
            <AppCard padding="medium">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-primary-100 text-primary-600">
                        <UsersIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm text-neutral-60">Total Users</p>
                        <p class="text-headline-small font-semibold">{{ stats.totalUsers ?? '—' }}</p>
                    </div>
                </div>
            </AppCard>

            <!-- Today's Appointments Stat (placeholder – replace with real endpoint) -->
            <AppCard padding="medium">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-success/10 text-success">
                        <CalendarIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm text-neutral-60">Today's Appointments</p>
                        <p class="text-headline-small font-semibold">{{ stats.todayAppointments ?? '—' }}</p>
                    </div>
                </div>
            </AppCard>

            <!-- Revenue Stat (placeholder – replace with real endpoint) -->
            <AppCard padding="medium">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full bg-warning/10 text-warning">
                        <CurrencyDollarIcon class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-sm text-neutral-60">Revenue (Today)</p>
                        <p class="text-headline-small font-semibold">{{ stats.todayRevenue ?? '—' }}</p>
                    </div>
                </div>
            </AppCard>
        </div>

        <!-- Recent Activity -->
        <div class="mt-8">
            <h2 class="text-title-large font-semibold mb-4">Recent Activity</h2>
            <AppCard>
                <div v-if="recentActivitiesLoading" class="p-8 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-4 border-primary-200 border-t-primary-600 mx-auto"></div>
                </div>
                <div v-else-if="recentActivities.length === 0" class="p-8 text-center text-neutral-50">
                    No recent activity
                </div>
                <div v-else>
                    <div
                        v-for="activity in recentActivities"
                        :key="activity.id"
                        class="p-4 border-b border-neutral-20 flex items-center gap-3"
                    >
                        <!-- ✅ F-41: Changed bg-primary to bg-primary-500 -->
                        <div
                            class="w-2 h-2 rounded-full"
                            :class="getActivityColor(activity.action)"
                        ></div>
                        <span class="text-sm">{{ formatActivityMessage(activity) }}</span>
                        <span class="text-xs text-neutral-60 ml-auto">{{ formatRelativeTime(activity.created_at) }}</span>
                    </div>
                </div>
            </AppCard>
        </div>

        <!-- User Info Card (unchanged) -->
        <div class="mt-8 bg-white p-6 rounded-xl shadow-elevation-1">
            <h2 class="text-title-large font-semibold mb-4">Welcome, {{ authStore.user?.name }}!</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-neutral-60">Email</p>
                    <p class="font-medium">{{ authStore.user?.email }}</p>
                </div>
                <div>
                    <p class="text-sm text-neutral-60">Branch</p>
                    <p class="font-medium">{{ authStore.userBranch?.name || '—' }}</p>
                </div>
                <div>
                    <p class="text-sm text-neutral-60">Roles</p>
                    <div class="flex flex-wrap gap-1 mt-1">
                        <AppBadge v-for="role in authStore.user?.roles" :key="role.id" :text="role.name" :color="getRoleColor(role.name)" />
                    </div>
                </div>
                <div>
                    <p class="text-sm text-neutral-60">Last Login</p>
                    <p class="font-medium">{{ formatDate(authStore.user?.last_login_at) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { auditApi } from '@/api/audit'
import { usersApi } from '@/api/users'
import { UsersIcon, CalendarIcon, CurrencyDollarIcon } from '@heroicons/vue/24/outline'
import AppCard from '@/components/ui/AppCard.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const authStore = useAuthStore()

// Stats data
const stats = ref({
    totalUsers: null,
    todayAppointments: null,
    todayRevenue: null,
})

// Recent activity
const recentActivities = ref([])
const recentActivitiesLoading = ref(false)

// Fetch total users count
const fetchTotalUsers = async () => {
    try {
        const response = await usersApi.getAll({ per_page: 1 })
        stats.value.totalUsers = response.data.total || 0
    } catch (err) {
        console.error('Failed to fetch user count:', err)
        stats.value.totalUsers = 'Error'
    }
}

// Fetch audit logs for recent activity (F-40)
const fetchRecentActivities = async () => {
    recentActivitiesLoading.value = true
    try {
        const response = await auditApi.getAll({ per_page: 5 })
        const data = response.data
        recentActivities.value = data.data || data || []
    } catch (err) {
        console.error('Failed to fetch recent activities:', err)
        recentActivities.value = []
    } finally {
        recentActivitiesLoading.value = false
    }
}

// Helper: format activity message
const formatActivityMessage = (log) => {
    const action = log.action?.replace(/_/g, ' ') || 'Action'
    const entity = log.entity_type?.split('\\').pop() || 'Item'
    const performer = log.performer?.name || 'System'
    return `${performer} ${action} ${entity} #${log.entity_id}`
}

// Helper: relative time (simple version)
const formatRelativeTime = (dateStr) => {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    const now = new Date()
    const diffMs = now - date
    const diffMins = Math.floor(diffMs / 60000)
    const diffHours = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMins < 1) return 'Just now'
    if (diffMins < 60) return `${diffMins} min ago`
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`
    return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`
}

// Activity dot color based on action type
const getActivityColor = (action) => {
    if (!action) return 'bg-neutral-30'
    if (action === 'created' || action === 'login') return 'bg-success'
    if (action === 'updated') return 'bg-primary-500'
    if (action === 'deleted' || action === 'logout') return 'bg-error'
    return 'bg-warning'
}

// Format date for user info
const formatDate = (date) => {
    if (!date) return 'Never'
    return new Date(date).toLocaleString()
}

// Role badge colors
const getRoleColor = (role) => {
    const map = {
        'super-admin': 'error',
        admin: 'warning',
        manager: 'primary',
        stylist: 'success',
        receptionist: 'gray'
    }
    return map[role] || 'gray'
}

// Placeholder for appointments/revenue – replace with real endpoints
// For now, we'll leave as '—' or you can fetch from your appointment/invoice APIs.
// Example: if you have an appointments API, call it here.
const fetchAppointmentsAndRevenue = async () => {
    // TODO: Replace with actual API calls
    stats.value.todayAppointments = '—'
    stats.value.todayRevenue = '—'
}

onMounted(async () => {
    await Promise.all([
        fetchTotalUsers(),
        fetchRecentActivities(),
        fetchAppointmentsAndRevenue()
    ])
})
</script>
