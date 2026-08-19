import { apiRequest } from './apiClient'

function query(params = {}) {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') search.set(key, value)
  })

  return search.toString() ? `?${search.toString()}` : ''
}

export function getNotifications(params) {
  return apiRequest(`/notifications${query(params)}`).then((response) => response.data)
}

export function getUnreadCount() {
  return apiRequest('/notifications/unread-count').then((response) => response.data.unread_count)
}

export function markAsRead(id) {
  return apiRequest(`/notifications/${id}/read`, { method: 'PATCH' }).then((response) => response.data.notification)
}

export function markAllAsRead() {
  return apiRequest('/notifications/read-all', { method: 'POST' }).then((response) => response.data)
}

export function getNotificationPreferences() {
  return apiRequest('/notification-preferences').then((response) => response.data.preferences)
}

export function updateNotificationPreferences(data) {
  return apiRequest('/notification-preferences', { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data.preferences)
}
