import { useQuery } from '@tanstack/react-query'
import { getPaginated } from '@/lib/api'
import { LIVE_REFRESH } from '@/lib/realtime'

export const notificationKeys = {
  unreadCount: ['notifications', 'unread-count'] as const,
  list: (page: number, perPage: number) => ['notifications', 'list', page, perPage] as const,
  all: ['notifications'] as const,
}

/**
 * Unread count for the top-bar badge.
 *
 * The count comes from `meta.total` on a one-row page of the unread filter,
 * because that total is the server's count of every unread row, not just the
 * one returned - counting the rows client-side would cap at the page size.
 */
export function useUnreadNotifications(enabled = true) {
  return useQuery({
    queryKey: notificationKeys.unreadCount,
    queryFn: () =>
      getPaginated<{ id: number }>('/admin/notifications?unread_only=true&per_page=1').then(
        (response) => response.meta.total,
      ),
    enabled,
    refetchInterval: LIVE_REFRESH.fast,
  })
}
