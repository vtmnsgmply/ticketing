import { apiRequest } from './apiClient'

let ticketOptionsRequest = null

function toQuery(params = {}) {
  const query = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      query.set(key, value)
    }
  })

  return query.toString()
}

export function getTicketOptions() {
  if (!ticketOptionsRequest) {
    ticketOptionsRequest = apiRequest('/ticket-options')
      .then((response) => response.data)
      .catch((error) => {
        ticketOptionsRequest = null
        throw error
      })
  }

  return ticketOptionsRequest
}

export function getTickets(params) {
  const query = toQuery(params)
  return apiRequest(`/tickets${query ? `?${query}` : ''}`).then((response) => response.data)
}

export function getTicket(id) {
  return apiRequest(`/tickets/${id}`).then((response) => response.data.ticket)
}

export function createTicket(data) {
  const body = toTicketBody(data)
  return apiRequest('/tickets', {
    method: 'POST',
    body,
  }).then((response) => response.data.ticket)
}

export function updateTicket(id, data) {
  return apiRequest(`/tickets/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data),
  }).then((response) => response.data.ticket)
}

export function replyToTicket(id, data, options = {}) {
  const body = toTicketBody(data)
  return apiRequest(`/tickets/${id}/reply`, {
    method: 'POST',
    body,
    onUploadProgress: options.onUploadProgress,
    signal: options.signal,
  }).then((response) => response.data.ticket)
}

export function addInternalNote(id, data, options = {}) {
  const body = toTicketBody(data)
  return apiRequest(`/tickets/${id}/internal-note`, {
    method: 'POST',
    body,
    onUploadProgress: options.onUploadProgress,
    signal: options.signal,
  }).then((response) => response.data.ticket)
}

export function deleteTicketMessage(ticketId, messageId) {
  return apiRequest(`/tickets/${ticketId}/messages/${messageId}`, {
    method: 'DELETE',
  }).then((response) => response.data.ticket)
}

export function reactToTicketMessage(ticketId, messageId, reaction) {
  return apiRequest(`/tickets/${ticketId}/messages/${messageId}/reactions`, {
    method: 'POST',
    body: JSON.stringify({ reaction }),
  }).then((response) => response.data.ticket)
}

export function markTicketRead(ticketId) {
  return apiRequest(`/tickets/${ticketId}/read`, {
    method: 'POST',
  }).then((response) => response.data.ticket)
}

export function assignTicket(id, data) {
  return apiRequest(`/tickets/${id}/assign`, {
    method: 'POST',
    body: JSON.stringify(data),
  }).then((response) => response.data.ticket)
}

export function changeStatus(id, data) {
  return apiRequest(`/tickets/${id}/status`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  }).then((response) => response.data.ticket)
}

export function changePriority(id, data) {
  return apiRequest(`/tickets/${id}/priority`, {
    method: 'PATCH',
    body: JSON.stringify(data),
  }).then((response) => response.data.ticket)
}

export function reopenTicket(id) {
  return apiRequest(`/tickets/${id}/reopen`, { method: 'POST' }).then((response) => response.data.ticket)
}

export function closeTicket(id) {
  return apiRequest(`/tickets/${id}/close`, { method: 'POST' }).then((response) => response.data.ticket)
}

export function cancelTicket(id) {
  return apiRequest(`/tickets/${id}/cancel`, { method: 'POST' }).then((response) => response.data.ticket)
}

export function getActivity(id) {
  return apiRequest(`/tickets/${id}/activity`).then((response) => response.data.activity)
}

function toTicketBody(data) {
  const attachments = data.attachments ?? []
  if (!attachments.length) return JSON.stringify(data)

  const form = new FormData()
  Object.entries(data).forEach(([key, value]) => {
    if (key === 'attachments' || value === undefined || value === null || value === '') return
    form.append(key, value)
  })
  attachments.forEach((file) => form.append('attachments[]', file))
  return form
}
