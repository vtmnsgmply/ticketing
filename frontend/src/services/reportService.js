import { apiRequest } from './apiClient'

function query(params = {}) {
  const search = new URLSearchParams()
  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') search.set(key, value)
  })

  return search.toString() ? `?${search.toString()}` : ''
}

export function getReportOverview(role, params) {
  const prefix = role === 'administrator' ? '/admin' : role === 'manager' ? '/manager' : ''

  return apiRequest(`${prefix}/reports/overview${query(params)}`).then((response) => response.data)
}
