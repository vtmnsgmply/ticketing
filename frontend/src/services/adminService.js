import { apiRequest } from './apiClient'

function query(params = {}) {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      search.set(key, value)
    }
  })

  return search.toString() ? `?${search.toString()}` : ''
}

let slaRulesRequest = null

function getSlaRules() {
  if (!slaRulesRequest) {
    slaRulesRequest = apiRequest('/admin/sla')
      .then((response) => response.data.sla_rules)
      .finally(() => {
        slaRulesRequest = null
      })
  }

  return slaRulesRequest
}

export const adminService = {
  getDashboard: () => apiRequest('/admin/dashboard').then((response) => response.data),
  getUsers: (params) => apiRequest(`/admin/users${query(params)}`).then((response) => response.data),
  createUser: (data) => apiRequest('/admin/users', { method: 'POST', body: JSON.stringify(data) }).then((response) => response.data),
  updateUser: (id, data) => apiRequest(`/admin/users/${id}`, { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data),
  changeUserStatus: (id, isActive) =>
    apiRequest(`/admin/users/${id}/status`, { method: 'PATCH', body: JSON.stringify({ is_active: isActive }) }).then((response) => response.data),
  changeUserRole: (id, roleId) =>
    apiRequest(`/admin/users/${id}/role`, { method: 'PATCH', body: JSON.stringify({ role_id: roleId }) }).then((response) => response.data),
  getRoles: () => apiRequest('/admin/roles').then((response) => response.data.roles),
  getDepartments: (params) => apiRequest(`/admin/departments${query(params)}`).then((response) => response.data),
  saveDepartment: (data, id) =>
    apiRequest(id ? `/admin/departments/${id}` : '/admin/departments', { method: id ? 'PUT' : 'POST', body: JSON.stringify(data) }).then((response) => response.data),
  changeDepartmentStatus: (id, isActive) =>
    apiRequest(`/admin/departments/${id}/status`, { method: 'PATCH', body: JSON.stringify({ is_active: isActive }) }).then((response) => response.data),
  getCategories: (params) => apiRequest(`/admin/categories${query(params)}`).then((response) => response.data),
  saveCategory: (data, id) =>
    apiRequest(id ? `/admin/categories/${id}` : '/admin/categories', { method: id ? 'PUT' : 'POST', body: JSON.stringify(data) }).then((response) => response.data),
  changeCategoryStatus: (id, isActive) =>
    apiRequest(`/admin/categories/${id}/status`, { method: 'PATCH', body: JSON.stringify({ is_active: isActive }) }).then((response) => response.data),
  getPriorities: () => apiRequest('/admin/priorities').then((response) => response.data.priorities),
  updatePriority: (id, data) => apiRequest(`/admin/priorities/${id}`, { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data),
  changePriorityStatus: (id, isActive) =>
    apiRequest(`/admin/priorities/${id}/status`, { method: 'PATCH', body: JSON.stringify({ is_active: isActive }) }).then((response) => response.data),
  getSla: getSlaRules,
  updateSla: (id, data) => apiRequest(`/admin/sla/${id}`, { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data),
  getSystemSettings: () => apiRequest('/admin/settings/system').then((response) => response.data.settings),
  updateSystemSettings: (data) => apiRequest('/admin/settings/system', { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data),
  getEmailSettings: () => apiRequest('/admin/settings/email').then((response) => response.data.settings),
  updateEmailSettings: (data) => apiRequest('/admin/settings/email', { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data),
  testEmailSettings: (data) => apiRequest('/admin/settings/email/test', { method: 'POST', body: JSON.stringify(data) }).then((response) => response.data),
  getAuditLogs: (params) => apiRequest(`/admin/audit-logs${query(params)}`).then((response) => response.data),
  getAuditLog: (id) => apiRequest(`/admin/audit-logs/${id}`).then((response) => response.data.audit_log),
  getAuditActions: () => apiRequest('/admin/audit-logs/actions').then((response) => response.data.actions),
  getAuditEntityTypes: () => apiRequest('/admin/audit-logs/entity-types').then((response) => response.data.entity_types),
  getModerationSettings: () => apiRequest('/admin/moderation/settings').then((response) => response.data.settings),
  updateModerationSettings: (data) => apiRequest('/admin/moderation/settings', { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data),
  getBlockedWords: (params) => apiRequest(`/admin/moderation/blocked-words${query(params)}`).then((response) => response.data),
  saveBlockedWord: (data, id) =>
    apiRequest(id ? `/admin/moderation/blocked-words/${id}` : '/admin/moderation/blocked-words', { method: id ? 'PUT' : 'POST', body: JSON.stringify(data) }).then((response) => response.data),
  enableBlockedWord: (id) => apiRequest(`/admin/moderation/blocked-words/${id}/enable`, { method: 'PATCH' }).then((response) => response.data),
  disableBlockedWord: (id) => apiRequest(`/admin/moderation/blocked-words/${id}/disable`, { method: 'PATCH' }).then((response) => response.data),
  getBlockedWordVariants: (id) => apiRequest(`/admin/moderation/blocked-words/${id}/variants`).then((response) => response.data.variants),
  saveBlockedWordVariant: (wordId, data, variantId) =>
    apiRequest(
      variantId ? `/admin/moderation/blocked-words/${wordId}/variants/${variantId}` : `/admin/moderation/blocked-words/${wordId}/variants`,
      { method: variantId ? 'PUT' : 'POST', body: JSON.stringify(data) },
    ).then((response) => response.data),
  deleteBlockedWordVariant: (wordId, variantId) =>
    apiRequest(`/admin/moderation/blocked-words/${wordId}/variants/${variantId}`, { method: 'DELETE' }).then((response) => response.data),
  getModerationEvents: (params) => apiRequest(`/admin/moderation/events${query(params)}`).then((response) => response.data),
  reviewModerationEvent: (id) => apiRequest(`/admin/moderation/events/${id}/review`, { method: 'PATCH' }).then((response) => response.data),
  dismissModerationEvent: (id) => apiRequest(`/admin/moderation/events/${id}/dismiss`, { method: 'PATCH' }).then((response) => response.data),
  escalateModerationEvent: (id) => apiRequest(`/admin/moderation/events/${id}/escalate`, { method: 'PATCH' }).then((response) => response.data),
}
