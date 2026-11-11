import type { ReactNode } from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '@/features/auth/auth-context'
import { FullPageLoader } from '@/components/ui/FullPageLoader'

/**
 * Blocks a route until a session exists.
 *
 * The `state.from` handoff means a user who was deep-linked to a page they are
 * allowed to see lands back there after signing in, rather than being dumped
 * on the dashboard.
 */
export function ProtectedRoute({ children }: { children: ReactNode }) {
  const { isAuthenticated, isLoading } = useAuth()
  const location = useLocation()

  if (isLoading) {
    return <FullPageLoader label="Restoring your session" />
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace state={{ from: location.pathname + location.search }} />
  }

  return <>{children}</>
}

/**
 * Client-side navigation guard for a single permission.
 *
 * Purely cosmetic. If a user deep-links to a page they lack the permission
 * for, the API behind it returns 403 and the page shows an error - this only
 * avoids sending them somewhere that is guaranteed to fail.
 */
export function RequirePermission({
  permission,
  children,
}: {
  permission: string | string[]
  children: ReactNode
}) {
  const { can, isLoading, isAuthenticated } = useAuth()
  const location = useLocation()

  if (isLoading) {
    return <FullPageLoader label="Checking your access" />
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace state={{ from: location.pathname + location.search }} />
  }

  if (!can(permission)) {
    return <Navigate to="/forbidden" replace />
  }

  return <>{children}</>
}
