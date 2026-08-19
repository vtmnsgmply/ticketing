import { useCallback, useEffect, useMemo, useState } from 'react'
import { getAuthToken, setAuthToken } from '../services/apiClient'
import * as authService from '../services/authService'
import { useLoadingProgress } from '../hooks/useLoadingProgress'
import { AuthContext } from './authContextValue'

const userStorageKey = 'ticketing_auth_user'

function readStoredUser() {
  try {
    const payload = window.localStorage.getItem(userStorageKey)
    return payload ? JSON.parse(payload) : null
  } catch {
    return null
  }
}

function storeUser(user) {
  if (!user) {
    window.localStorage.removeItem(userStorageKey)
    return
  }

  window.localStorage.setItem(userStorageKey, JSON.stringify(user))
}

export function AuthProvider({ children }) {
  const initialUser = getAuthToken() ? readStoredUser() : null
  const [user, setUser] = useState(initialUser)
  const [loading, setLoading] = useState(Boolean(getAuthToken() && !initialUser))
  const { completeTask, failTask, resetProgress, startTask } = useLoadingProgress()

  const refreshUser = useCallback(async () => {
    startTask('auth')
    if (!getAuthToken()) {
      setUser(null)
      storeUser(null)
      setLoading(false)
      completeTask('auth')
      completeTask('notifications')
      completeTask('workspace')
      return
    }

    const cachedUser = readStoredUser()
    if (cachedUser) {
      setUser(cachedUser)
      setLoading(false)
      completeTask('auth')
    }

    try {
      const currentUser = await authService.getCurrentUser()
      setUser(currentUser)
      storeUser(currentUser)
      completeTask('auth')
    } catch {
      setAuthToken(null)
      setUser(null)
      storeUser(null)
      completeTask('auth')
      completeTask('notifications')
      completeTask('workspace')
    } finally {
      setLoading(false)
    }
  }, [completeTask, startTask])

  useEffect(() => {
    refreshUser()
  }, [refreshUser])

  const login = useCallback(async (credentials) => {
    resetProgress()
    startTask('auth')
    try {
      const authenticatedUser = await authService.login(credentials)
      setUser(authenticatedUser)
      storeUser(authenticatedUser)
      completeTask('auth')
      return authenticatedUser
    } catch (error) {
      failTask('auth')
      throw error
    }
  }, [completeTask, failTask, resetProgress, startTask])

  const logout = useCallback(async () => {
    await authService.logout()
    setUser(null)
    storeUser(null)
    resetProgress()
  }, [resetProgress])

  const value = useMemo(
    () => ({
      user,
      loading,
      isAuthenticated: user !== null,
      login,
      logout,
      refreshUser,
    }),
    [user, loading, login, logout, refreshUser],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
