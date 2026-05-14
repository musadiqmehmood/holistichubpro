<template>
    <div>
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-neutral-90">Database Backup</h1>
            <p class="mt-1 text-sm text-neutral-50">Create and manage SQL backups. Backups older than 7 days are automatically removed.</p>
        </div>

        <!-- Access denied -->
        <AppAlert v-if="!authStore.isSuperAdmin" type="error">
            You do not have permission to access this page. This section is restricted to Super Admins only.
        </AppAlert>

        <template v-else>
            <!-- Create Backup Card -->
            <AppCard class="mb-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-80">Create New Backup</h2>
                        <p class="text-sm text-neutral-50 mt-1">Generates a full SQL dump of the current database.</p>
                    </div>
                    <AppButton variant="filled" color="primary" :loading="creating" @click="handleCreateBackup">
                        Create Backup Now
                    </AppButton>
                </div>
            </AppCard>

            <!-- Backup List Card -->
            <AppCard :padding="'none'">
                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-10">
                    <h2 class="text-base font-semibold text-neutral-80">Existing Backups</h2>
                    <AppButton variant="outlined" color="neutral" size="small" :loading="loadingList" @click="fetchBackups">
                        Refresh
                    </AppButton>
                </div>

                <!-- Loading skeleton -->
                <div v-if="loadingList" class="p-6 space-y-3 animate-pulse">
                    <div v-for="i in 3" :key="i" class="h-10 bg-neutral-10 rounded-lg"></div>
                </div>

                <!-- Table -->
                <AppTable v-else :columns="columns" :data="backups">
                    <template #filename="{ item }">
                        <span class="font-mono text-xs text-neutral-80">{{ item.filename }}</span>
                    </template>
                    <template #size="{ item }">
                        <span class="text-sm text-neutral-60">{{ formatSize(item.size) }}</span>
                    </template>
                    <template #created_at="{ item }">
                        <span class="text-sm text-neutral-60">{{ item.created_at }}</span>
                    </template>
                    <template #actions="{ item }">
                        <div class="flex items-center justify-end gap-2">
                            <AppButton
                                variant="tonal"
                                color="primary"
                                size="small"
                                :loading="downloadingFile === item.filename"
                                @click="handleDownload(item.filename)"
                            >
                                Download
                            </AppButton>
                            <AppButton
                                variant="tonal"
                                color="error"
                                size="small"
                                @click="confirmDelete(item)"
                            >
                                Delete
                            </AppButton>
                        </div>
                    </template>
                </AppTable>
            </AppCard>
        </template>

        <!-- ── Delete Confirmation Modal ────────────────────────────────────── -->
        <AppModal
            :is-open="deleteModalOpen"
            title="Delete Backup"
            confirm-text="Delete"
            confirm-color="error"
            :loading="deleting"
            @close="deleteModalOpen = false"
            @confirm="handleDelete"
        >
            <p class="text-sm text-neutral-60">
                Are you sure you want to permanently delete
                <strong class="text-neutral-90 font-mono">{{ deleteTarget?.filename }}</strong>?
                This action cannot be undone.
            </p>
        </AppModal>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { settingsApi } from '@/api/settings'
import AppCard from '@/components/ui/AppCard.vue'
import AppTable from '@/components/ui/AppTable.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppAlert from '@/components/ui/AppAlert.vue'
import AppModal from '@/components/ui/AppModal.vue'

defineOptions({ name: 'DatabaseBackupView' })

const authStore = useAuthStore()
const uiStore   = useUiStore()

const columns = [
    { key: 'filename',   label: 'Filename' },
    { key: 'size',       label: 'Size' },
    { key: 'created_at', label: 'Created At' },
]

const backups         = ref([])
const loadingList     = ref(false)
const creating        = ref(false)
const downloadingFile = ref(null)
const deleting        = ref(false)
const deleteModalOpen = ref(false)
const deleteTarget    = ref(null)

onMounted(() => {
    if (authStore.isSuperAdmin) fetchBackups()
})

async function fetchBackups() {
    loadingList.value = true
    try {
        const res = await settingsApi.listBackups()
        // ApiResponse envelope: { success, message, data: [...] }
        backups.value = res.data?.data ?? res.data
    } catch {
        uiStore.addNotification({ type: 'error', message: 'Failed to load backups' })
    } finally {
        loadingList.value = false
    }
}

async function handleCreateBackup() {
    creating.value = true
    try {
        const res = await settingsApi.createBackup()
        const payload = res.data?.data ?? res.data
        uiStore.addNotification({ type: 'success', message: `Backup created: ${payload?.filename ?? 'backup'}` })
        await fetchBackups()
    } catch (e) {
        uiStore.addNotification({
            type: 'error',
            message: e.response?.data?.message ?? 'Backup creation failed',
        })
    } finally {
        creating.value = false
    }
}

async function handleDownload(filename) {
    downloadingFile.value = filename
    try {
        const res = await settingsApi.downloadBackup(filename)
        const url = URL.createObjectURL(new Blob([res.data], { type: 'application/octet-stream' }))
        const a   = Object.assign(document.createElement('a'), { href: url, download: filename })
        document.body.appendChild(a)
        a.click()
        a.remove()
        URL.revokeObjectURL(url)
    } catch {
        uiStore.addNotification({ type: 'error', message: 'Download failed' })
    } finally {
        downloadingFile.value = null
    }
}

function confirmDelete(backup) {
    deleteTarget.value = backup
    deleteModalOpen.value = true
}

async function handleDelete() {
    deleting.value = true
    try {
        await settingsApi.deleteBackup(deleteTarget.value.filename)
        uiStore.addNotification({ type: 'success', message: 'Backup deleted successfully' })
        deleteModalOpen.value = false
        await fetchBackups()
    } catch {
        uiStore.addNotification({ type: 'error', message: 'Failed to delete backup' })
    } finally {
        deleting.value = false
    }
}

function formatSize(bytes) {
    if (!bytes) return '0 B'
    if (bytes < 1024)            return `${bytes} B`
    if (bytes < 1024 * 1024)     return `${(bytes / 1024).toFixed(1)} KB`
    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`
}
</script>
