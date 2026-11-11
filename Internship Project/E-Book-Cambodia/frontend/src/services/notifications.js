import { useQuery, useQueryClient } from '@tanstack/react-query'
import { NOTIFICATION_ENDPOINTS } from '../api'

export const NOTIFICATION_SCOPES = ['member', 'admin', 'author']

/** Prefix key so one invalidation can refresh whichever scopes are on screen. */
export const notificationKeys = {
  all: ['notifications'],
  feed: (scope) => ['notifications', scope],
}

/**
 * The bell's data source, shared by every portal.
 *
 * `scope` decides both the endpoint and the query key: a member, an admin and
 * an author each see their own feed, and none of them can read another's
 * because the server scopes the notifiable to the bearer token.
 */
export function useNotifications(scope, enabled = true) {
  const queryClient = useQueryClient()
  const queryKey = notificationKeys.feed(scope)
  const endpoints = NOTIFICATION_ENDPOINTS[scope]

  const { data, isLoading } = useQuery({
    queryKey,
    queryFn: () => endpoints.list(),
    refetchInterval: 5000,
    refetchIntervalInBackground: true,
    refetchOnWindowFocus: true,
    enabled: enabled && !!endpoints,
  })

  const list = data?.data?.notifications ?? []
  const unreadCount = data?.data?.unread_count ?? list.filter((n) => !n.read).length

  // Flipped locally first so the badge and the row react on click instead of
  // waiting for the next poll; a failure puts the truth back.
  const markOneRead = (id) => {
    queryClient.setQueryData(queryKey, (current) => {
      if (!current) return current
      return {
        ...current,
        data: {
          ...current.data,
          notifications: (current.data.notifications || []).map((n) =>
            n.id === id && !n.read ? { ...n, read: true } : n,
          ),
        },
      }
    })
    endpoints.markRead(id).catch(() => {
      queryClient.invalidateQueries({ queryKey })
    })
  }

  const markAllRead = () => {
    queryClient.setQueryData(queryKey, (current) => {
      if (!current) return current
      return {
        ...current,
        data: {
          ...current.data,
          unread_count: 0,
          notifications: (current.data.notifications || []).map((n) =>
            n.read ? n : { ...n, read: true },
          ),
        },
      }
    })
    endpoints
      .markAllRead()
      .catch(() => {})
      .finally(() => {
        queryClient.invalidateQueries({ queryKey })
      })
  }

  return { data: list, unreadCount, isLoading, markOneRead, markAllRead }
}

/** Kept for the admin shell, which only ever wants the admin feed. */
export function useAdminNotifications(enabled = true) {
  return useNotifications('admin', enabled)
}
