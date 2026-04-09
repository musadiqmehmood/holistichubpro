import api from './axios'

export const rolesApi = {
    getAll: () => api.get('/admin/roles'),
    get: (id) => api.get(`/admin/roles/${id}`),
    create: (data) => api.post('/admin/roles', data),
    update: (id, data) => api.put(`/admin/roles/${id}`, data),
    delete: (id) => api.delete(`/admin/roles/${id}`),
}
