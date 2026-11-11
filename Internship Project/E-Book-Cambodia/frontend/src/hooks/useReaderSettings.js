import { useCallback, useEffect, useRef, useState } from 'react'
import { saveReaderPreferences } from '../api'
import {
  DEFAULT_SETTINGS,
  loadSettings,
  normalizeSettings,
  saveSettings,
} from '../utils/readerSettings'

/**
 * Local-first reader settings. Signed-in readers also sync the values to their
 * account (debounced), which is what makes "continue on another device" work.
 */
export function useReaderSettings({ user, isAuthenticated }) {
  const [settings, setSettings] = useState(loadSettings)
  const settingsRef = useRef(settings)
  const timerRef = useRef(null)
  const appliedForRef = useRef(null)

  const apply = useCallback((next) => {
    settingsRef.current = next
    setSettings(next)
    saveSettings(next)
  }, [])

  useEffect(() => {
    const userId = user?.id ?? null
    if (!userId || appliedForRef.current === userId) return
    appliedForRef.current = userId
    const remote = normalizeSettings(user?.reader_preferences)
    if (!remote) return
    apply({ ...DEFAULT_SETTINGS, ...remote })
  }, [apply, user])

  const update = useCallback(
    (patch) => {
      const next = normalizeSettings({ ...settingsRef.current, ...patch })
      if (!next) return
      apply(next)

      if (!isAuthenticated) return
      window.clearTimeout(timerRef.current)
      timerRef.current = window.setTimeout(() => {
        saveReaderPreferences(next).catch(() => {
          // Offline or signed out: local settings remain authoritative.
        })
      }, 700)
    },
    [apply, isAuthenticated],
  )

  useEffect(
    () => () => {
      window.clearTimeout(timerRef.current)
    },
    [],
  )

  return [settings, update]
}

