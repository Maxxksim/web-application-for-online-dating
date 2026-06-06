import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

let echoInstance = null
let lastToken = null

const resolveAuthEndpoint = () => {
  const override = import.meta.env.VITE_REVERB_AUTH_ENDPOINT
  if (override) return override

  const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api'
  if (baseUrl.startsWith('http')) {
    return baseUrl.replace(/\/api\/?$/, '/broadcasting/auth')
  }
  if (baseUrl.startsWith('/api')) {
    return '/broadcasting/auth'
  }
  return `${baseUrl.replace(/\/$/, '')}/broadcasting/auth`
}

const resolvePort = (scheme, rawPort) => {
  if (rawPort) return Number(rawPort)
  return scheme === 'https' ? 443 : 80
}

export const initReverb = ({ token } = {}) => {
  const appKey = import.meta.env.VITE_REVERB_APP_KEY
  if (!appKey) return null

  const authToken = token || localStorage.getItem('auth_token') || null

  if (echoInstance && lastToken === authToken) return echoInstance
  if (echoInstance) echoInstance.disconnect()

  const scheme = import.meta.env.VITE_REVERB_SCHEME || 'https'
  const host = import.meta.env.VITE_REVERB_HOST || window.location.hostname
  const port = resolvePort(scheme, import.meta.env.VITE_REVERB_PORT)

  window.Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: appKey,
    wsHost: host,
    wsPort: port,
    wssPort: port,
    forceTLS: scheme === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: resolveAuthEndpoint(),
    auth: {
      headers: {
        ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
        Accept: 'application/json',
      },
    },
    namespace: 'App\\Events',
  })

  lastToken = authToken
  return echoInstance
}

export const disconnectReverb = () => {
  if (echoInstance) {
    echoInstance.disconnect()
  }
  echoInstance = null
  lastToken = null
}