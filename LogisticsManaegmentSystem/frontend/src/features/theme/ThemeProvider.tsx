import { useCallback, useEffect, useMemo, useState, type ReactNode } from 'react'
import { DEFAULT_THEME, isThemeId, type ThemeId } from '@/lib/themes'
import { ThemeContext, type ThemeContextValue } from '@/features/theme/theme-context'

const STORAGE_KEY = 'theme'

/**
 * Reads the stored choice once, synchronously.
 *
 * This runs during the provider's first render rather than in an effect, so
 * the correct theme is applied before the browser paints. An effect-based read
 * would show a frame of the default theme, then a visible swap to the stored
 * one.
 */
function readStoredTheme(): ThemeId {
  try {
    const stored = window.localStorage.getItem(STORAGE_KEY)

    return isThemeId(stored) ? stored : DEFAULT_THEME
  } catch {
    // Private browsing and blocked storage both throw on access; the default
    // theme is the correct answer either way.
    return DEFAULT_THEME
  }
}

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [theme, setThemeState] = useState<ThemeId>(readStoredTheme)

  useEffect(() => {
    // The selector the stylesheet hangs each template off. Keeping the
    // attribute on <html> means the whole document - including portals and
    // the scrollbar - is themed, with no per-tree mounting order to worry
    // about.
    document.documentElement.dataset.theme = theme
  }, [theme])

  const setTheme = useCallback((next: ThemeId) => {
    setThemeState(next)

    try {
      window.localStorage.setItem(STORAGE_KEY, next)
    } catch {
      // Ignore. The theme still applies for this session; it simply is not
      // remembered across reloads.
    }
  }, [])

  const value = useMemo<ThemeContextValue>(
    () => ({ theme, setTheme }),
    [theme, setTheme],
  )

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
}
