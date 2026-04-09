// src/api/permissions.js
import api from './axios'

export const permissionsApi = {
    getAll: () => api.get('/admin/permissions'),
    create: (data) => api.post('/admin/permissions', data),
    update: (id, data) => api.put(`/admin/permissions/${id}`, data),
    delete: (id) => api.delete(`/admin/permissions/${id}`),
}
