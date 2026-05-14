// src/stores/auth.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/api/axios'
import { authApi } from '@/api/auth'
import { useSettingsStore } from '@/stores/settings'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const token = ref(localStorage.getItem('token') || null)
    const loading = ref(false)
    const error = ref(null)
    const requiresPasswordChange = ref(false)
    const requiresVerification = ref(false)

    const storedRequiresChange = localStorage.getItem('requires_password_change')
    if (storedRequiresChange === 'true') {
        requiresPasswordChange.value = true
    }

    const storedUser = localStorage.getItem('user')
    if (storedUser) {
        try {
            user.value = JSON.parse(storedUser)
            if (import.meta.env.DEV) console.log('[AuthStore] Loaded user from localStorage:', user.value?.email)
        } catch (e) {
            console.error('[AuthStore] Failed to parse user from localStorage:', e)
            localStorage.removeItem('user')
        }
    }

    const isAuthenticated = computed(() => !!token.value && !!user.value)

    const isSuperAdmin = computed(() => {
        if (!user.value?.roles) return false
        return user.value.roles.some(role => role.name === 'super-admin')
    })

    const isAdmin = computed(() => {
        if (!user.value?.roles) return false
        return user.value.roles.some(role => ['admin', 'super-admin'].includes(role.name))
    })

    const userPermissions = computed(() => {
        if (!user.value) return []

        if (user.value.all_permissions && Array.isArray(user.value.all_permissions)) {
            return user.value.all_permissions.map(p => p.toLowerCase())
        }

        const perms = new Set()
        if (user.value.permissions && Array.isArray(user.value.permissions)) {
            user.value.permissions.forEach(p => {
                if (typeof p === 'string') perms.add(p.toLowerCase())
                else if (p.name) perms.add(p.name.toLowerCase())
            })
        }
        if (user.value.roles && Array.isArray(user.value.roles)) {
            user.value.roles.forEach(role => {
                if (role.permissions && Array.isArray(role.permissions)) {
                    role.permissions.forEach(p => {
                        if (typeof p === 'string') perms.add(p.toLowerCase())
                        else if (p.name) perms.add(p.name.toLowerCase())
                    })
                }
            })
        }
        return Array.from(perms)
    })

    const userRoles = computed(() => {
        return user.value?.roles?.map(r => r.name) || []
    })

    const userBranch = computed(() => {
        return user.value?.branch || null
    })

    const hasPermission = (permission) => {
        if (!permission) return false
        if (isSuperAdmin.value) return true
        const perms = userPermissions.value
        if (!perms || perms.length === 0) return false
        return perms.includes(permission.toLowerCase().trim())
    }

    const hasRole = (role) => {
        if (!role || !userRoles.value.length) return false
        return userRoles.value.includes(role)
    }

    const setToken = (newToken) => {
        token.value = newToken
        if (newToken) {
            localStorage.setItem('token', newToken)
            api.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
        } else {
            localStorage.removeItem('token')
            delete api.defaults.headers.common['Authorization']
        }
    }

    const setRequiresPasswordChange = (value) => {
        requiresPasswordChange.value = value
        if (value) {
            localStorage.setItem('requires_password_change', 'true')
        } else {
            localStorage.removeItem('requires_password_change')
        }
    }

    const setUser = (userData) => {
        user.value = userData
        if (userData) {
            localStorage.setItem('user', JSON.stringify(userData))
        } else {
            localStorage.removeItem('user')
        }
    }

    const clearAuth = () => {
        setToken(null)
        setUser(null)
        setRequiresPasswordChange(false)
        requiresVerification.value = false
        error.value = null
    }

    const login = async (credentials) => {
        loading.value = true
        error.value = null

        try {
            const response = await authApi.login(credentials)
            if (import.meta.env.DEV) console.log('[AuthStore] Login response:', response.data)

            const { user: userData, token: authToken } = response.data

            // Normal success case
            if (authToken && userData) {
                setToken(authToken)
                setUser(userData)

                // Design settings are fetched by the router navigation guard
                // via fetchUser() — no need to double-fetch here.
                return { success: true }
            }

            return { success: false, error: 'Invalid response from server' }
        } catch (err) {
            if (import.meta.env.DEV) console.error('[AuthStore] Login error:', err)

            if (err.response?.status === 403) {
                const data = err.response.data
                if (data?.requires_password_change) {
                    const tempToken = data?.password_token
                    if (tempToken) {
                        setToken(tempToken)
                        setRequiresPasswordChange(true)
                        return {
                            success: false,
                            requiresPasswordChange: true,
                            passwordToken: tempToken
                        }
                    }
                }
            }

            let errorMessage = 'Login failed'
            if (err.response) {
                switch (err.response.status) {
                    case 401:
                        errorMessage = 'Invalid email or password'
                        break
                    case 423:
                        errorMessage = `Account locked. Try again in ${err.response.data?.retry_after_minutes || 30} minutes.`
                        break
                    case 429:
                        errorMessage = 'Too many attempts. Please try again later.'
                        break
                    default:
                        errorMessage = err.response.data?.message || 'Login failed. Please try again.'
                }
            } else if (err.request) {
                errorMessage = 'Network error. Please check your connection.'
            }
            error.value = errorMessage
            return { success: false, error: errorMessage }
        } finally {
            loading.value = false
        }
    }

    const fetchUser = async () => {
        try {
            const response = await authApi.getUser()
            if (import.meta.env.DEV) console.log('[AuthStore] fetchUser response:', response.data)
            setUser(response.data)

            const settingsStore = useSettingsStore()
            settingsStore.fetchDynamicSettings()

            return response.data
        } catch (err) {
            if (import.meta.env.DEV) console.error('[AuthStore] fetchUser error:', err)
            if (err.response?.status === 401) {
                clearAuth()
                throw new Error('Session expired. Please login again.')
            }
            throw err
        }
    }

    const changePassword = async (data) => {
        loading.value = true
        error.value = null
        try {
            const response = await authApi.changePassword(data)
            if (response.data?.requires_relogin) {
                clearAuth()
                return { success: true, requiresRelogin: true }
            }
            if (response.data?.user) {
                setUser(response.data.user)
            }
            setRequiresPasswordChange(false)
            return { success: true }
        } catch (err) {
            if (import.meta.env.DEV) console.error('[AuthStore] Change password error:', err)
            let errorMessage = 'Failed to change password'
            if (err.response?.data?.errors) {
                const errors = err.response.data.errors
                errorMessage = Object.values(errors).flat().join(', ')
            } else if (err.response?.data?.message) {
                errorMessage = err.response.data.message
            }
            error.value = errorMessage
            return { success: false, error: errorMessage }
        } finally {
            loading.value = false
        }
    }

    const logout = async () => {
        try {
            await authApi.logout()
        } catch (err) {
            if (import.meta.env.DEV) console.error('[AuthStore] Logout error:', err)
        } finally {
            clearAuth()
        }
    }

    return {
        user,
        token,
        loading,
        error,
        requiresPasswordChange,
        requiresVerification,
        isAuthenticated,
        isSuperAdmin,
        isAdmin,
        userPermissions,
        userRoles,
        userBranch,
        hasPermission,
        hasRole,
        login,
        logout,
        fetchUser,
        changePassword,
        setUser,
        setToken,
        clearAuth
    }
})
