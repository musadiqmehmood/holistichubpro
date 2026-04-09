// src/api/audit.js
import api from './axios'  // ✅ F-02 + F-05: Use shared axios instance

export const auditApi = {
    getAll: (params) => api.get('/admin/audit-logs', { params }),

    getFilters: () => api.get('/admin/audit-logs/filters'),

    // Single delete
    delete: (id) => api.delete(`/admin/audit-logs/${id}`),

    // ✅ F-03: Bulk delete - changed from DELETE to POST
    bulkDelete: (data) => api.post('/admin/audit-logs/bulk-delete', data),

    // Clear all
    clearAll: () => api.delete('/admin/audit-logs/clear-all'),

    // ✅ F-04: Export removed – client-side CSV export in AuditLogsView.vue
}

export default auditApi
