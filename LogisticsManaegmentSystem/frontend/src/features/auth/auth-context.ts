import { createContext, useContext } from 'react'
import type { CurrentUser } from '@/types/api'

export interface AuthContextValue {
  user: CurrentUser | null
  /** True while the initial session check is in flight. */
  isLoading: boolean
  isAuthenticated: boolean
  login: (email: string, password: string, remember?: boolean) => Promise<void>
  /**
   * Redeems the one-time code the API appends to the Google callback redirect.
   * The Google flow cannot return a token directly, so the callback page calls
   * this and then navigates into the app.
   */
  loginWithGoogle: (code: string) => Promise<void>
  logout: () => Promise<void>
  refresh: () => Promise<void>
  /** A string or array of strings means "any of these". */
  can: (permission: string | string[]) => boolean
  canAll: (permissions: string[]) => boolean
  hasRole: (role: string) => boolean
}

export const AuthContext = createContext<AuthContextValue | null>(null)

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used inside an <AuthProvider>.')
  }

  return context
}
