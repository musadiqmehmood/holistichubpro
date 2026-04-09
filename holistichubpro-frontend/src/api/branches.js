import api from './axios'

export const branchesApi = {
    getAll: (params) => api.get('/admin/branches', { params }),
    get: (id) => api.get(`/admin/branches/${id}`),
    create: (data) => api.post('/admin/branches', data),
    update: (id, data) => api.put(`/admin/branches/${id}`, data),
    delete: (id) => api.delete(`admin/branches/${id}`),
}
