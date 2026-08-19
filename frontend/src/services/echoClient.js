import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { apiBaseUrl, getAuthToken } from './apiClient'

let echo = null
let echoAuthToken = null
const localHostnames = new Set(['localhost', '127.0.0.1', '0.0.0.0'])

function apiRoot() {
  return apiBaseUrl.replace(/\/api\/?$/, '')
}

function isRemoteBrowserHost() {
  return typeof window !== 'undefined' && !localHostnames.has(window.location.hostname)
}

function resolveReverbHost(configuredHost) {
  const host = configuredHost || 'localhost'

  if (isRemoteBrowserHost() && localHostnames.has(host)) {
    return window.location.hostname
  }

  return host
}

function resolveReverbScheme(configuredScheme, host) {
  if (isRemoteBrowserHost() && host === window.location.hostname) {
    return window.location.protocol === 'https:' ? 'https' : 'http'
  }

  return configuredScheme || 'http'
}

function resolveReverbPort(configuredPort, scheme, host) {
  const port = Number(configuredPort ?? 8080)

  if (isRemoteBrowserHost() && host === window.location.hostname && (!configuredPort || port === 8080)) {
    return scheme === 'https' ? 443 : 80
  }

  return Number.isFinite(port) ? port : 8080
}

export function getEchoClient() {
  const key = import.meta.env.VITE_REVERB_APP_KEY
  const host = resolveReverbHost(import.meta.env.VITE_REVERB_HOST)
  const scheme = resolveReverbScheme(import.meta.env.VITE_REVERB_SCHEME, host)
  const port = resolveReverbPort(import.meta.env.VITE_REVERB_PORT, scheme, host)
  const token = getAuthToken()

  if (!key || !token) {
    disconnectEcho()
    return null
  }

  if (echo && echoAuthToken === token) return echo
  disconnectEcho()

  window.Pusher = Pusher

  echo = new Echo({
    broadcaster: 'reverb',
    key,
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${apiRoot()}/api/broadcasting/auth`,
    auth: {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${token}`,
      },
    },
  })
  echoAuthToken = token

  return echo
}

export function disconnectEcho() {
  if (!echo) return
  echo.disconnect()
  echo = null
  echoAuthToken = null
}
