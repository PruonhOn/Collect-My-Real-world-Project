import {
  useCallback,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react'
import { ApiRequestError, get, post, setUnauthorizedHandler, tokenStorage } from '@/lib/api'
import { can as canAny, canAll as hasEvery, hasRole as isInRole } from '@/lib/permissions'
import type { CurrentUser } from '@/types/api'
import { AuthContext, type AuthContextValue } from '@/features/auth/auth-context'

const ALLOW_ALL_SESSIONS = import.meta.env.VITE_ALLOW_ALL_SESSIONS === 'true'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<CurrentUser | null>(
    ALLOW_ALL_SESSIONS
      ? {
          id: 1,
          name: 'Administrator',
          email: 'admin@erp.local',
          phone: null,
          avatar_path: null,
          is_active: true,
          last_login_at: null,
          customer: null,
          roles: ['super-admin'],
          permissions: ['*'],
        }
      : null
  )
  const [isLoading, setIsLoading] = useState(!ALLOW_ALL_SESSIONS)

  const clearSession = useCallback(() => {
    tokenStorage.clear()
    setUser(null)
  }, [])

  // Any 401 from any request drops the session exactly once.
  useEffect(() => {
    setUnauthorizedHandler(() => {
      setUser(null)
    })
  }, [])

  // On boot, validate any stored token. A stale or revoked token is discarded
  // here rather than on the first protected request.
  useEffect(() => {
    let cancelled = false

    async function restore() {
      if (ALLOW_ALL_SESSIONS) {
        setIsLoading(false)
        return
      }

      if (!tokenStorage.get()) {
        setIsLoading(false)
        return
      }

      try {
        const current = await get<CurrentUser>('/auth/me')
        if (!cancelled) setUser(current)
      } catch (error) {
        if (!cancelled) {
          // 401 already cleared the token via the interceptor.
          if (!(error instanceof ApiRequestError) || error.status !== 401) {
            tokenStorage.clear()
          }
          setUser(null)
        }
      } finally {
        if (!cancelled) setIsLoading(false)
      }
    }

    void restore()

    return () => {
      cancelled = true
    }
  }, [])

  const login = useCallback(async (email: string, password: string, remember = false) => {
    const result = await post<{ token: string; user: CurrentUser }>('/auth/login', {
      email,
      password,
      remember,
    })

    tokenStorage.set(result.token)
    setUser(result.user)
  }, [])

  const loginWithGoogle = useCallback(async (code: string) => {
    const result = await post<{ token: string; user: CurrentUser }>('/auth/google/exchange', {
      code,
    })

    tokenStorage.set(result.token)
    setUser(result.user)
  }, [])

  const logout = useCallback(async () => {
    try {
      // Best effort: revoke server-side so the token dies immediately even if
      // this tab is closed before the request completes.
      await post('/auth/logout')
    } catch {
      // Ignore. The local token is cleared regardless.
    } finally {
      clearSession()
    }
  }, [clearSession])

  const refresh = useCallback(async () => {
    try {
      setUser(await get<CurrentUser>('/auth/me'))
    } catch {
      clearSession()
    }
  }, [clearSession])

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      isLoading,
      isAuthenticated: user !== null,
      login,
      loginWithGoogle,
      logout,
      refresh,
      // A string or an array of strings means "any of these"; use canAll when
      // every one is required.
      can: (permission) => ALLOW_ALL_SESSIONS || canAny(user?.permissions, permission),
      canAll: (permissions) => ALLOW_ALL_SESSIONS || hasEvery(user?.permissions, permissions),
      hasRole: (role) => ALLOW_ALL_SESSIONS || isInRole(user?.roles, role),
    }),
    [user, isLoading, login, loginWithGoogle, logout, refresh],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
