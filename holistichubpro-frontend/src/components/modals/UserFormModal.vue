<template>
    <AppModal
        :is-open="isOpen"
        :title="isEditing ? 'Edit User' : 'Add User'"
        @close="handleClose"
        :show-confirm="false"
    >
        <div v-if="isEditing && isSuperAdminTarget" class="p-4 bg-red-50 border border-red-200 rounded-lg mb-4">
            <div class="flex items-center gap-3 text-red-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <div>
                    <p class="font-bold">System Protected User</p>
                    <p class="text-sm">Super Admin accounts cannot be modified.</p>
                </div>
            </div>
        </div>

        <form id="userForm" @submit.prevent="handleSubmit" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Name <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="formData.name"
                    type="text"
                    required
                    :disabled="isSuperAdminTarget"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all bg-gray-50 disabled:bg-gray-100 disabled:text-gray-500"
                    placeholder="Enter full name"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Email <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="formData.email"
                    type="email"
                    required
                    :disabled="isSuperAdminTarget"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all bg-gray-50 disabled:bg-gray-100 disabled:text-gray-500"
                    placeholder="Enter email address"
                />
            </div>

            <div v-if="isSuperAdmin && !isSuperAdminTarget">
                <label class="block text-sm font-medium text-gray-700 mb-1">Branch</label>
                <select
                    v-model="formData.branch_id"
                    required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all bg-gray-50"
                >
                    <option value="">Select Branch</option>
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                        {{ branch.name }}
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Roles <span class="text-red-500">*</span>
                </label>

                <div v-if="rolesLoading" class="text-sm text-gray-500 py-2 flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Loading roles...
                </div>
                <div v-else-if="availableRoles.length === 0" class="text-sm text-red-600 py-2">
                    No roles available. Please check API connection.
                </div>
                <div v-else class="space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-lg p-3 bg-gray-50">
                    <label
                        v-for="role in availableRoles"
                        :key="role.id"
                        class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 cursor-pointer transition-colors"
                        :class="{ 'opacity-50 cursor-not-allowed': isSuperAdminTarget }"
                    >
                        <input
                            type="checkbox"
                            :value="role.id"
                            v-model="formData.roles"
                            class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer"
                            :disabled="isSuperAdminTarget"
                        />
                        <span class="text-sm text-gray-800 font-medium">{{ formatRoleName(role.name) }}</span>
                        <span v-if="role.name === 'super-admin'" class="px-1.5 py-0.5 bg-red-100 text-red-700 text-[10px] font-bold rounded">
                            PROTECTED
                        </span>
                    </label>
                </div>
                <p v-if="rolesError" class="text-xs text-red-600 mt-1">{{ rolesError }}</p>
            </div>

            <div v-if="!isEditing">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Password <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="formData.password"
                    type="password"
                    required
                    minlength="8"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 outline-none transition-all bg-gray-50"
                    placeholder="Minimum 8 characters"
                />
                <p class="text-xs text-gray-500 mt-1.5">
                    Must be at least 8 characters with uppercase, lowercase, number and special character.
                </p>
            </div>

            <div v-if="errorMessage" class="p-3 bg-red-50 text-red-700 rounded-lg text-sm flex items-start gap-2 border border-red-200 mt-4">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ errorMessage }}</span>
            </div>
        </form>

        <template #actions>
            <AppButton
                type="button"
                @click="handleClose"
                variant="tonal"
                color="neutral"
                :disabled="isSubmitting"
            >
                Close
            </AppButton>
            <AppButton
                v-if="!isSuperAdminTarget"
                type="submit"
                form="userForm"
                :loading="isSubmitting"
                variant="filled"
                color="primary"
                :disabled="!isFormValid"
            >
                {{ isEditing ? 'Update User' : 'Create User' }}
            </AppButton>
        </template>
    </AppModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    user: { type: Object, default: null },
    branches: { type: Array, default: () => [] },
    availableRoles: { type: Array, default: () => [] },
    isSuperAdmin: { type: Boolean, default: false },
    rolesLoading: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'save'])
const authStore = useAuthStore()

const isSubmitting = ref(false)
const errorMessage = ref('')
const rolesError = ref('')

const formData = ref({
    name: '',
    email: '',
    branch_id: '',
    roles: [],
    password: ''
})

const isEditing = computed(() => !!props.user)

const isSuperAdminTarget = computed(() => {
    if (!props.user) return false
    return props.user.roles?.some(r => r.name === 'super-admin')
})

// ✅ F-23: Fixed branch validation – explicit undefined check
const isFormValid = computed(() => {
    if (isSuperAdminTarget.value) return false

    const hasName = formData.value.name.trim().length > 0
    const hasEmail = formData.value.email.trim().length > 0 && formData.value.email.includes('@')
    const hasRoles = formData.value.roles.length > 0
    // ✅ Check for null, undefined, or empty string
    const hasBranch = formData.value.branch_id != null && formData.value.branch_id !== ''
    const hasPassword = isEditing.value || formData.value.password.length >= 8
    return hasName && hasEmail && hasRoles && hasBranch && hasPassword
})

watch(() => props.isOpen, (isOpen) => {
    if (isOpen) {
        initializeForm()
        errorMessage.value = ''
        rolesError.value = ''
    }
})

watch(() => props.user, () => {
    if (props.isOpen) initializeForm()
}, { deep: true })

const initializeForm = () => {
    if (props.user) {
        const userRoleIds = props.user.roles?.map(r => r.id) || []
        formData.value = {
            name: props.user.name || '',
            email: props.user.email || '',
            branch_id: props.user.branch_id || '',
            roles: userRoleIds,
            password: ''
        }
    } else {
        const defaultBranchId = authStore.userBranch?.id || (props.branches.length > 0 ? props.branches[0].id : '')
        formData.value = {
            name: '',
            email: '',
            branch_id: defaultBranchId,
            roles: [],
            password: ''
        }
    }
}

const formatRoleName = (roleName) => {
    if (!roleName) return ''
    return roleName.split('-').map(word =>
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ')
}

const handleClose = () => {
    if (!isSubmitting.value) emit('close')
}

const handleSubmit = async () => {
    if (isSuperAdminTarget.value) {
        errorMessage.value = 'Super Admin users cannot be modified.'
        return
    }

    if (formData.value.roles.length === 0) {
        rolesError.value = 'Please select at least one role.'
        return
    }
    rolesError.value = ''

    if (!isFormValid.value) {
        errorMessage.value = 'Please fill in all required fields correctly.'
        return
    }

    const selectedRoles = props.availableRoles.filter(r => formData.value.roles.includes(r.id))
    if (selectedRoles.some(r => r.name === 'super-admin')) {
        errorMessage.value = 'Super Admin role cannot be assigned through this interface.'
        return
    }

    isSubmitting.value = true
    errorMessage.value = ''

    try {
        const payload = {
            name: formData.value.name.trim(),
            email: formData.value.email.trim(),
            branch_id: formData.value.branch_id || null,
            // ✅ Removed Urdu comment (F-24) – now clean
            roles: formData.value.roles.map(role => typeof role === 'object' ? role.id : role),
            ...(formData.value.password && { password: formData.value.password })
        }
        emit('save', { id: props.user?.id, payload, isEditing: isEditing.value })
    } catch (err) {
        errorMessage.value = err.message || 'An error occurred'
    } finally {
        isSubmitting.value = false
    }
}

const setError = (message) => {
    errorMessage.value = message
    isSubmitting.value = false
}

defineExpose({ setError })
</script>
