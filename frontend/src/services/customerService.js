import { apiRequest } from './apiClient'

function query(params = {}) {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') search.set(key, value)
  })
  return search.toString() ? `?${search.toString()}` : ''
}

export function getCustomerDashboard() {
  return apiRequest('/customer/dashboard').then((response) => response.data)
}

export function getCustomerProfile() {
  return apiRequest('/customer/profile').then((response) => response.data.user)
}

export function updateCustomerProfile(data) {
  return apiRequest('/customer/profile', { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data.user)
}

export function changeCustomerPassword(data) {
  return apiRequest('/customer/password', { method: 'PUT', body: JSON.stringify(data) }).then((response) => response.data)
}

export function getCustomerNotifications(params) {
  return apiRequest(`/customer/notifications${query(params)}`).then((response) => response.data)
}

export function markNotificationRead(id) {
  return apiRequest(`/customer/notifications/${id}/read`, { method: 'PATCH' }).then((response) => response.data.notification)
}

export function markAllNotificationsRead() {
  return apiRequest('/customer/notifications/read-all', { method: 'POST' }).then((response) => response.data)
}
