import { createContext, useCallback, useContext, useMemo, useState } from 'react'
import api from '../api/client'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem('user') || 'null')
    } catch {
      return null
    }
  })
  const [token, setToken] = useState(() => localStorage.getItem('access_token'))

  const login = useCallback(async (email, password) => {
    const res = await api.post('/auth/login', { email, password })
    const { token: t, user: u } = res.data.data
    localStorage.setItem('access_token', t)
    localStorage.setItem('user', JSON.stringify(u))
    setToken(t)
    setUser(u)
    return u
  }, [])

  const register = useCallback(async (payload) => {
    const res = await api.post('/auth/register', payload)
    const { token: t, user: u } = res.data.data
    localStorage.setItem('access_token', t)
    localStorage.setItem('user', JSON.stringify(u))
    setToken(t)
    setUser(u)
    return u
  }, [])

  const logout = useCallback(async () => {
    try {
      if (token) {
        await api.post('/auth/logout')
      }
    } catch {
      // ignore network errors on logout
    }
    localStorage.removeItem('access_token')
    localStorage.removeItem('user')
    setToken(null)
    setUser(null)
  }, [token])

  const refreshUser = useCallback(async () => {
    if (!token) return null
    try {
      const res = await api.get('/auth/me')
      localStorage.setItem('user', JSON.stringify(res.data.data))
      setUser(res.data.data)
      return res.data.data
    } catch {
      localStorage.removeItem('access_token')
      localStorage.removeItem('user')
      setToken(null)
      setUser(null)
      return null
    }
  }, [token])

  const value = useMemo(
    () => ({ user, token, isAuthenticated: Boolean(token), login, register, logout, refreshUser }),
    [user, token, login, register, logout, refreshUser],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return ctx
}