import type { ReactNode } from 'react'
import { useAuth } from '@/features/auth/auth-context'

/**
 * Renders `children` only when the current user holds the permission.
 *
 * UX affordance only. The backend enforces the same rule, so hiding a button
 * never becomes the only thing standing between a user and an unauthorised
 * write.
 */
export function Can({
  permission,
  all,
  fallback = null,
  children,
}: {
  permission: string | string[]
  /** When true, every entry in `permission` is required. */
  all?: boolean
  fallback?: ReactNode
  children: ReactNode
}) {
  const { can, canAll } = useAuth()

  const allowed = all
    ? canAll(Array.isArray(permission) ? permission : [permission])
    : can(permission)

  return <>{allowed ? children : fallback}</>
}

/** Inverse of <Can>, for the occasional "only when NOT permitted" case. */
export function Cannot({
  permission,
  children,
}: {
  permission: string | string[]
  children: ReactNode
}) {
  const { can } = useAuth()
  return <>{can(permission) ? null : children}</>
}
