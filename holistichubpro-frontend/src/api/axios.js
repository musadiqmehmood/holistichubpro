// src/api/axios.js
import axios from 'axios'

const api = axios.create({
    // Versioned API base URL — all requests automatically prefixed with /api/v1.
    // Override via .env: VITE_API_BASE_URL=http://localhost:8000/api/v1
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
    withCredentials: true,
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})

// Request interceptor
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token')
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }

        // ✅ ADDED: DYNAMIC BRANCH HEADER
        // Automatically inject the current branch_id into headers if available.
        // This allows the backend to be "branch-aware" for every request.
        const user = JSON.parse(localStorage.getItem('user') || '{}')
        if (user.branch_id) {
            config.headers['X-Branch-ID'] = user.branch_id
        }

        if (config.data instanceof FormData) {
            delete config.headers['Content-Type']
        }

        return config
    },
    (error) => Promise.reject(error)
)

// Response interceptor
api.interceptors.response.use(
    (response) => response,
    (error) => {
        const { response } = error

        if (response) {
            switch (response.status) {
                case 401:
                    if (window.location.pathname !== '/login') {
                        // CHANGE: Clear all storage on 401 for security
                        localStorage.clear()
                        window.location.href = '/login'
                    }
                    break
                case 419:
                    localStorage.clear()
                    if (window.location.pathname !== '/login') {
                        window.location.href = '/login'
                    }
                    break
            }
        }

        return Promise.reject(error)
    }
)

export default api
