// src/api/axios.js
import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',  // ✅ includes /api
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})

// Request interceptor - Add token to EVERY request
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token')
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
            // ✅ F-07: Only log in development
            if (import.meta.env.DEV) console.log(`[API] Adding token to ${config.url}`)
        } else {
            if (import.meta.env.DEV) console.warn(`[API] No token found for ${config.url}`)
        }
        return config
    },
    (error) => Promise.reject(error)
)

// Response interceptor - Handle errors properly
api.interceptors.response.use(
    (response) => response,
    (error) => {
        const { response } = error

        if (response) {
            if (import.meta.env.DEV) console.error(`[API Error] ${response.status} on ${response.config?.url}:`, response.data)

            switch (response.status) {
                case 401:
                    // ✅ F-08: Prevent infinite redirect on login page
                    if (window.location.pathname !== '/login') {
                        localStorage.removeItem('token')
                        localStorage.removeItem('user')
                        window.location.href = '/login'
                    }
                    break

                case 403:
                    if (import.meta.env.DEV) console.error('[API] 403 Forbidden:', response.data?.message)
                    break

                case 419:
                    // ✅ F-09: CSRF token expired - clear session and redirect
                    if (import.meta.env.DEV) console.error('[API] CSRF token expired')
                    localStorage.removeItem('token')
                    localStorage.removeItem('user')
                    if (window.location.pathname !== '/login') {
                        window.location.href = '/login'
                    }
                    break

                case 500:
                    if (import.meta.env.DEV) console.error('[API] Server error:', response.data)
                    break
            }
        } else {
            if (import.meta.env.DEV) console.error('[API] Network error:', error.message)
        }

        return Promise.reject(error)
    }
)

export default api
