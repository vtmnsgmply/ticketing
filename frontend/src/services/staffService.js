import { apiRequest } from './apiClient'

function query(params = {}) {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') search.set(key, value)
  })
  return search.toString() ? `?${search.toString()}` : ''
}

export function getStaffDashboard() {
  return apiRequest('/staff/dashboard').then((response) => response.data)
}

export function getStaffProfile() {
  return apiRequest('/staff/profile').then((response) => response.data.user)
}

export function updateStaffProfile(data) {
  return apiRequest('/staff/profile', { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data.user)
}

export function changeStaffPassword(data) {
  return apiRequest('/staff/password', { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data)
}

export function getStaffNotifications(params) {
  return apiRequest(`/staff/notifications${query(params)}`).then((response) => response.data)
}

export function markStaffNotificationRead(id) {
  return apiRequest(`/staff/notifications/${id}/read`, { method: 'PATCH' }).then((response) => response.data.notification)
}

export function markAllStaffNotificationsRead() {
  return apiRequest('/staff/notifications/read-all', { method: 'POST' }).then((response) => response.data)
}
