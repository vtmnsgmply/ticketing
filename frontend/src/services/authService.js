import { apiRequest, setAuthToken } from './apiClient'

let currentUserRequest = null

export async function login(credentials) {
  const response = await apiRequest('/login', {
    method: 'POST',
    body: JSON.stringify(credentials),
  })

  setAuthToken(response.data.token)
  currentUserRequest = null

  return response.data.user
}

export async function logout() {
  try {
    await apiRequest('/logout', {
      method: 'POST',
    })
  } finally {
    setAuthToken(null)
    currentUserRequest = null
  }
}

export async function getCurrentUser() {
  if (!currentUserRequest) {
    currentUserRequest = apiRequest('/me')
      .then((response) => response.data.user)
      .catch((error) => {
        currentUserRequest = null
        throw error
      })
  }

  return currentUserRequest
}
