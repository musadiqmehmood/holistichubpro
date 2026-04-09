import api from './axios'

export const authApi = {
    login: (credentials) => api.post('/login', credentials),               // was '/api/login'
    logout: () => api.post('/logout'),                                    // was '/api/logout'
    getUser: () => api.get('/user'),                                      // was '/api/user'
    changePassword: (data) => api.post('/change-password', data),         // was '/api/change-password'
    resendVerification: (data) => api.post('/email/verification-notification', data), // was '/api/email/...'
}
