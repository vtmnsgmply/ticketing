import { apiRequest } from './apiClient'

function query(params = {}) {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') search.set(key, value)
  })
  return search.toString() ? `?${search.toString()}` : ''
}

export function getManagerDashboard() {
  return apiRequest('/manager/dashboard').then((response) => response.data)
}

export function getManagerTeam(params) {
  return apiRequest(`/manager/team${query(params)}`).then((response) => response.data.team)
}

export function getManagerProfile() {
  return apiRequest('/manager/profile').then((response) => response.data.user)
}

export function updateManagerProfile(data) {
  return apiRequest('/manager/profile', { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data.user)
}

export function changeManagerPassword(data) {
  return apiRequest('/manager/password', { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data)
}

export function getManagerNotifications(params) {
  return apiRequest(`/manager/notifications${query(params)}`).then((response) => response.data)
}

export function markManagerNotificationRead(id) {
  return apiRequest(`/manager/notifications/${id}/read`, { method: 'PATCH' }).then((response) => response.data.notification)
}

export function markAllManagerNotificationsRead() {
  return apiRequest('/manager/notifications/read-all', { method: 'POST' }).then((response) => response.data)
}

export function getManagerModerationEvents(params) {
  return apiRequest(`/manager/moderation/events${query(params)}`).then((response) => response.data)
}

export function reviewManagerModerationEvent(id) {
  return apiRequest(`/manager/moderation/events/${id}/review`, { method: 'PATCH' }).then((response) => response.data)
}

export function dismissManagerModerationEvent(id) {
  return apiRequest(`/manager/moderation/events/${id}/dismiss`, { method: 'PATCH' }).then((response) => response.data)
}

export function escalateManagerModerationEvent(id) {
  return apiRequest(`/manager/moderation/events/${id}/escalate`, { method: 'PATCH' }).then((response) => response.data)
}
