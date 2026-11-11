import { QueryClient } from '@tanstack/react-query'
import { ApiRequestError } from '@/lib/api'

/**
 * Shared TanStack Query client.
 *
 * Retries are suppressed for 4xx responses: a 403 or a 422 will fail again on
 * every retry, and retrying only delays showing the user the real answer.
 * 5xx and network failures are worth retrying.
 */
export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      gcTime: 5 * 60_000,
      refetchOnWindowFocus: false,
      // Every live surface polls, and polling that stops at the first dropped
      // packet leaves the screen showing a gap rather than the next successful
      // tick: a reconnect re-fetches whatever the connection outage made stale.
      refetchOnReconnect: true,
      retry: (failureCount, error) => {
        if (error instanceof ApiRequestError && error.status >= 400 && error.status < 500) {
          return false
        }
        return failureCount < 2
      },
    },
    mutations: {
      // Never auto-retry a mutation: a retried "create invoice" is a duplicate
      // invoice.
      retry: false,
    },
  },
})
