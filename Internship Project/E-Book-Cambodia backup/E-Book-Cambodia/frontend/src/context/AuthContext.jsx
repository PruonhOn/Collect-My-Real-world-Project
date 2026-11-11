import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { login as loginApi, logout as logoutApi, getMe, register as registerApi } from '../api'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(() => {
    const stored = localStorage.getItem('user')
    return stored ? JSON.parse(stored) : null
  })
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem('token')
    if (token && !user) {
      getMe()
        .then((res) => {
          setUser(res.data.user)
          localStorage.setItem('user', JSON.stringify(res.data.user))
        })
        .catch(() => {
          localStorage.removeItem('token')
          localStorage.removeItem('user')
        })
        .finally(() => setLoading(false))
    } else {
      setLoading(false)
    }
  }, [])

  const login = useCallback(async (credentials) => {
    const res = await loginApi(credentials)
    const { token, user } = res.data
    localStorage.setItem('token', token)
    localStorage.setItem('user', JSON.stringify(user))
    setUser(user)
    return user
  }, [])

  const register = useCallback(async (data) => {
    const res = await registerApi(data)
    const { token, user } = res.data
    localStorage.setItem('token', token)
    localStorage.setItem('user', JSON.stringify(user))
    setUser(user)
    return user
  }, [])

  const logout = useCallback(async () => {
    try {
      await logoutApi()
    } catch {
      // ignore
    }
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    setUser(null)
  }, [])

  const updateUser = useCallback((updatedUser) => {
    setUser(updatedUser)
    localStorage.setItem('user', JSON.stringify(updatedUser))
  }, [])

  const isAdmin = useMemo(() => user?.role === 'admin', [user])

  const value = useMemo(
    () => ({
      user,
      setUser: updateUser,
      isAdmin,
      isAuthenticated: !!user,
      login,
      register,
      logout,
      loading,
    }),
    [user, isAdmin, login, register, logout, updateUser, loading]
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return context
}