import { useMemo } from 'react'
import { useAuth } from '../context/AuthContext'
import { deriveMembership } from '../utils/membership'

/**
 * Single entry point for "what tier is this person on?". Every membership
 * surface — badges, cards, upgrade prompts — reads through here instead of
 * testing `user.is_vip` on its own, so the tier has exactly one definition.
 */
export function useMembership() {
  const { user } = useAuth()

  return useMemo(() => deriveMembership(user), [user])
}
