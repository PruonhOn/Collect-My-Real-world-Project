import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { setUnauthorizedHandler, TOKEN_STORAGE_KEY } from '../api/client'
import { authService } from '../services/authService'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [token, setToken] = useState(() => localStorage.getItem(TOKEN_STORAGE_KEY))
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(Boolean(localStorage.getItem(TOKEN_STORAGE_KEY)))

  const persistToken = useCallback((value) => {
    if (value) {
      localStorage.setItem(TOKEN_STORAGE_KEY, value)
    } else {
      localStorage.removeItem(TOKEN_STORAGE_KEY)
    }

    setToken(value)
  }, [])

  const signOut = useCallback(
    async ({ notifyServer = true } = {}) => {
      if (notifyServer && localStorage.getItem(TOKEN_STORAGE_KEY)) {
        try {
          await authService.logout()
        } catch {
          // The session is dropped locally even when the server call fails.
        }
      }

      persistToken(null)
      setUser(null)
      setLoading(false)
    },
    [persistToken],
  )

  useEffect(() => {
    setUnauthorizedHandler(() => {
      setToken(null)
      setUser(null)
    })
  }, [])

  useEffect(() => {
    let cancelled = false

    if (!token) {
      return undefined
    }

    authService
      .me()
      .then((data) => {
        if (!cancelled) {
          setUser(data)
        }
      })
      .catch(() => {
        if (!cancelled) {
          persistToken(null)
          setUser(null)
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false)
        }
      })

    return () => {
      cancelled = true
    }
  }, [token, persistToken])

  const login = useCallback(
    async (credentials) => {
      const result = await authService.login(credentials)
      persistToken(result.token)
      setUser(result.user)
      setLoading(false)

      return result
    },
    [persistToken],
  )

  const register = useCallback(
    async (payload) => {
      const result = await authService.register(payload)
      persistToken(result.token)
      setUser(result.user)
      setLoading(false)

      return result
    },
    [persistToken],
  )

  const refreshUser = useCallback(async () => {
    if (!token) {
      return null
    }

    const data = await authService.me()
    setUser(data)

    return data
  }, [token])

  const value = useMemo(
    () => ({
      user,
      token,
      loading,
      isAuthenticated: Boolean(user),
      isSuperAdmin: Boolean(user?.is_super_admin),
      login,
      register,
      signOut,
      refreshUser,
    }),
    [user, token, loading, login, register, signOut, refreshUser],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used inside an AuthProvider.')
  }

  return context
}

export default AuthContext
