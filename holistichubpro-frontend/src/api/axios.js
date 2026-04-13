// src/api/axios.js
import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
    withCredentials: true,
    headers: {
        // ❌ REMOVED: 'Content-Type': 'application/json' – let browser set it for FormData
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
            if (import.meta.env.DEV) console.log(`[API] Adding token to ${config.url}`)
        } else {
            if (import.meta.env.DEV) console.warn(`[API] No token found for ${config.url}`)
        }

        // ✅ If the request contains FormData, remove the Content-Type header
        // so that the browser sets the correct multipart boundary.
        if (config.data instanceof FormData) {
            delete config.headers['Content-Type']
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
