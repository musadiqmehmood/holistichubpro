<template>
    <div>
        <label class="block text-xs font-medium text-neutral-50 mb-1">Select Branch</label>
        <select
            v-model="selectedBranchId"
            @change="handleBranchChange"
            class="w-full border border-neutral-30 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
        >
            <option value="">All Branches</option>
            <option v-for="branch in branchStore.branches" :key="branch.id" :value="branch.id">
                {{ branch.name }}
            </option>
        </select>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useBranchStore } from '@/stores/branch'
import { useAuthStore } from '@/stores/auth'
import { branchesApi } from '@/api/branches'

defineOptions({
    name: 'BranchSelector'
})

const branchStore = useBranchStore()
const authStore = useAuthStore()
const selectedBranchId = ref('')

onMounted(async () => {
    if (!authStore.isSuperAdmin) return
    try {
        const response = await branchesApi.getAll()
        const branches = response.data.data || response.data
        branchStore.setBranches(branches)
        if (branchStore.selectedBranch) {
            selectedBranchId.value = branchStore.selectedBranch.id
        }
    } catch (error) {
        console.error('Failed to fetch branches:', error)
    }
})

watch(() => branchStore.selectedBranch, (newBranch) => {
    if (newBranch) {
        selectedBranchId.value = newBranch.id
    } else {
        selectedBranchId.value = ''
    }
})

const handleBranchChange = () => {
    // ✅ F-22: Explicitly handle empty string case
    let branchId = null
    if (selectedBranchId.value === '') {
        branchId = null
    } else {
        branchId = parseInt(selectedBranchId.value, 10)
    }

    const branch = branchStore.branches.find(b => b.id === branchId)
    branchStore.setSelectedBranch(branch || null)
}
</script>
