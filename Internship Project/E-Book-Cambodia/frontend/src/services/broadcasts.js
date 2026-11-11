import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { getBroadcastMessages } from '../api'

const STORAGE_KEY = 'broadcast-seen'

export const broadcastKeys = {
  all: ['broadcast-messages'],
}

function readSeen() {
  try {
    const parsed = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '[]')
    return Array.isArray(parsed) ? parsed : []
  } catch {
    return []
  }
}

/**
 * The announcements an admin published, split by display style.
 *
 * Dismissals live in localStorage keyed by message id, so closing a message is
 * remembered without asking an anonymous visitor to sign in. The server still
 * owns what is live.
 */
export function useBroadcastMessages() {
  const [seen, setSeen] = useState(readSeen)

  const { data } = useQuery({
    queryKey: broadcastKeys.all,
    refetchInterval: 15000,
    queryFn: () => getBroadcastMessages(),
    staleTime: 60_000,
    refetchOnWindowFocus: false,
  })

  const dismiss = (id) => {
    setSeen((current) => {
      if (current.includes(id)) return current
      const next = [...current, id]
      try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next))
      } catch {
        // Private browsing can refuse writes; the message just reappears.
      }
      return next
    })
  }

  const { banners, popups } = useMemo(() => {
    const pending = (data?.data?.messages ?? []).filter((m) => !seen.includes(m.id))
    return {
      banners: pending.filter((m) => m.type === 'banner'),
      popups: pending.filter((m) => m.type === 'popup'),
    }
  }, [data, seen])

  return { banners, popups, dismiss }
}