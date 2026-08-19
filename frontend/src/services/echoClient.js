import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { apiBaseUrl, getAuthToken } from './apiClient'

let echo = null
let echoAuthToken = null

function apiRoot() {
  return apiBaseUrl.replace(/\/api\/?$/, '')
}

export function getEchoClient() {
  const key = import.meta.env.VITE_REVERB_APP_KEY
  const host = import.meta.env.VITE_REVERB_HOST ?? 'localhost'
  const port = Number(import.meta.env.VITE_REVERB_PORT ?? 8080)
  const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http'
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
