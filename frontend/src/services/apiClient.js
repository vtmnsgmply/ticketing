export const apiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api'
const tokenStorageKey = 'ticketing_auth_token'
const pendingGetRequests = new Map()

function parseJsonPayload(rawPayload) {
  if (!rawPayload) return null

  try {
    return JSON.parse(rawPayload)
  } catch {
    const trimmed = rawPayload.trim()
    const objectStart = trimmed.indexOf('{')
    const objectEnd = trimmed.lastIndexOf('}')
    const arrayStart = trimmed.indexOf('[')
    const arrayEnd = trimmed.lastIndexOf(']')
    const hasObject = objectStart !== -1 && objectEnd > objectStart
    const hasArray = arrayStart !== -1 && arrayEnd > arrayStart

    if (!hasObject && !hasArray) return null

    const start = hasArray && (!hasObject || arrayStart < objectStart) ? arrayStart : objectStart
    const end = hasArray && start === arrayStart ? arrayEnd : objectEnd

    try {
      return JSON.parse(trimmed.slice(start, end + 1))
    } catch {
      return null
    }
  }
}

export function getAuthToken() {
  return window.localStorage.getItem(tokenStorageKey)
}

export function setAuthToken(token) {
  if (token) {
    window.localStorage.setItem(tokenStorageKey, token)
    return
  }

  window.localStorage.removeItem(tokenStorageKey)
}

export async function apiRequest(path, options = {}) {
  const token = getAuthToken()
  const method = options.method ?? 'GET'
  const headers = {
    Accept: 'application/json',
    ...options.headers,
  }

  if (!(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json'
  }

  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  if (options.body instanceof FormData && typeof options.onUploadProgress === 'function') {
    return uploadRequest(path, { ...options, headers })
  }

  const request = async () => {
    const response = await fetch(`${apiBaseUrl}${path}`, {
      ...options,
      headers,
    })

    const contentType = response.headers.get('content-type') ?? ''
    const isJson = contentType.includes('application/json')
    const rawPayload = await response.text()
    let payload = null

    if (rawPayload && isJson) {
      payload = parseJsonPayload(rawPayload)
    }

    if (response.ok && rawPayload && isJson && payload === null) {
      throw new Error('The backend returned invalid JSON. Check the backend logs.')
    }

    if (!response.ok) {
      const fallback = rawPayload.trim().startsWith('<')
        ? `Request failed with status ${response.status}. The backend returned an HTML/PHP error page.`
        : (rawPayload.trim() || `Request failed with status ${response.status}.`)
      const error = new Error(payload?.message ?? fallback)
      error.status = response.status
      error.payload = payload ?? rawPayload
      throw error
    }

    return payload
  }

  if (method.toUpperCase() !== 'GET') {
    return request()
  }

  const requestKey = `${token ?? 'guest'}:${path}`
  if (pendingGetRequests.has(requestKey)) {
    return pendingGetRequests.get(requestKey)
  }

  const pending = request().finally(() => {
    pendingGetRequests.delete(requestKey)
  })
  pendingGetRequests.set(requestKey, pending)

  return pending
}

function uploadRequest(path, options) {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest()
    xhr.open(options.method ?? 'POST', `${apiBaseUrl}${path}`)

    Object.entries(options.headers ?? {}).forEach(([key, value]) => {
      xhr.setRequestHeader(key, value)
    })

    xhr.upload.onprogress = (event) => {
      if (!event.lengthComputable) return
      options.onUploadProgress(Math.round((event.loaded / event.total) * 100))
    }

    xhr.onload = () => {
      const contentType = xhr.getResponseHeader('content-type') ?? ''
      const isJson = contentType.includes('application/json')
      const rawPayload = xhr.responseText ?? ''
      let payload = null

      if (rawPayload && isJson) {
        payload = parseJsonPayload(rawPayload)
      }

      if (xhr.status >= 200 && xhr.status < 300 && rawPayload && isJson && payload === null) {
        reject(new Error('The backend returned invalid JSON. Check the backend logs.'))
        return
      }

      if (xhr.status < 200 || xhr.status >= 300) {
        const fallback = rawPayload.trim().startsWith('<')
          ? `Request failed with status ${xhr.status}. The backend returned an HTML/PHP error page.`
          : (rawPayload.trim() || `Request failed with status ${xhr.status}.`)
        const error = new Error(payload?.message ?? fallback)
        error.status = xhr.status
        error.payload = payload ?? rawPayload
        reject(error)
        return
      }

      options.onUploadProgress(100)
      resolve(payload)
    }

    xhr.onerror = () => reject(new Error('Network error while uploading.'))
    xhr.onabort = () => reject(new Error('Upload cancelled.'))
    options.signal?.addEventListener('abort', () => xhr.abort(), { once: true })
    xhr.send(options.body)
  })
}
