// src/stores/branch.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useAuthStore } from './auth'

export const useBranchStore = defineStore('branch', () => {
    const authStore = useAuthStore()

    const selectedBranch = ref(null)
    const branches = ref([])

    const currentBranch = computed(() => {
        if (authStore.isSuperAdmin && selectedBranch.value) {
            return selectedBranch.value
        }
        return authStore.user?.branch || null
    })

    const canSwitchBranch = computed(() => authStore.isSuperAdmin)

    const userBranchId = computed(() => {
        if (authStore.isSuperAdmin && selectedBranch.value) {
            return selectedBranch.value.id
        }
        return authStore.user?.branch_id
    })

    const setSelectedBranch = (branch) => {
        selectedBranch.value = branch
    }

    const setBranches = (branchList) => {
        branches.value = branchList
    }

    return {
        selectedBranch,
        branches,
        currentBranch,
        canSwitchBranch,
        userBranchId,
        setSelectedBranch,
        setBranches,
    }
})
