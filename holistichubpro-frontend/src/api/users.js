// src/api/users.js
import api from './axios'

export const usersApi = {
    getAll: (params) => api.get('/admin/users', { params }),
    get: (id) => api.get(`/admin/users/${id}`),
    create: (data) => api.post('/admin/users', data),
    update: (id, data) => api.put(`/admin/users/${id}`, data),
    delete: (id) => api.delete(`/admin/users/${id}`),
    getAuditLogs: (id) => api.get(`/admin/users/${id}/audit-logs`),
    import: (data) => api.post('/admin/users/import', data),  // ✅ Required for import button
}
