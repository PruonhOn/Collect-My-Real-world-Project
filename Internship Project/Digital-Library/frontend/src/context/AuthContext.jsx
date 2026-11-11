import { createContext, useCallback, useContext, useEffect, useState } from 'react'
import client from '../api/client'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem('user') || 'null')
    } catch {
      return null
    }
  })
  const [token, setToken] = useState(() => localStorage.getItem('token'))
  const [loading, setLoading] = useState(Boolean(localStorage.getItem('token')))

  const applyAuth = useCallback(({ token: t, user: u }) => {
    setToken(t)
    setUser(u)
    localStorage.setItem('token', t)
    localStorage.setItem('user', JSON.stringify(u))
  }, [])

  const register = useCallback(
    async (payload) => {
      const { data } = await client.post('/register', payload)
      applyAuth(data)
      return data
    },
    [applyAuth],
  )

  const login = useCallback(
    async (payload) => {
      const { data } = await client.post('/login', payload)
      applyAuth(data)
      return data
    },
    [applyAuth],
  )

  const logout = useCallback(async () => {
    try {
      if (token) await client.post('/logout')
    } catch {
      /* ignore */
    }
    setUser(null)
    setToken(null)
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }, [token])

  const updateProfile = useCallback(async (payload) => {
    const { data } = await client.put('/me', payload)
    setUser(data.user)
    localStorage.setItem('user', JSON.stringify(data.user))
    return data
  }, [])

  useEffect(() => {
    if (!token) {
      setLoading(false)
      return
    }
    client
      .get('/me')
      .then(({ data }) => {
        setUser(data.user)
        localStorage.setItem('user', JSON.stringify(data.user))
      })
      .catch(() => {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      })
      .finally(() => setLoading(false))
  }, [token])

  const value = {
    user,
    token,
    loading,
    isAuthenticated: Boolean(token),
    isAdmin: user?.role === 'admin',
    register,
    login,
    logout,
    updateProfile,
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth must be used within AuthProvider')
  return ctx
}
