import { createContext, useContext } from 'react'
import type { ThemeId } from '@/lib/themes'

export interface ThemeContextValue {
  /** The theme template currently applied to the document. */
  theme: ThemeId
  /** Apply a template. Persisted, so the choice survives a reload. */
  setTheme: (theme: ThemeId) => void
}

export const ThemeContext = createContext<ThemeContextValue | null>(null)

export function useTheme(): ThemeContextValue {
  const context = useContext(ThemeContext)

  if (!context) {
    throw new Error('useTheme must be used inside a <ThemeProvider>.')
  }

  return context
}
