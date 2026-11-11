import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import translations from './translations'

const I18nContext = createContext(null)

export const LANGUAGES = [
  { code: 'km', label: 'ភាសាខ្មែរ', native: 'ខ្មែរ' },
  { code: 'en', label: 'English', native: 'English' },
  { code: 'zh', label: '中文', native: '中文' },
  { code: 'fr', label: 'Français', native: 'Français' },
]

export function I18nProvider({ children }) {
  const [locale, setLocale] = useState(() => {
    return localStorage.getItem('locale') || 'km'
  })

  useEffect(() => {
    localStorage.setItem('locale', locale)
    document.documentElement.lang = locale
  }, [locale])

  const t = useCallback(
    (key, vars) => {
      const table = translations[locale] || translations.en
      let str = table[key] || translations.en[key] || key
      if (vars) {
        Object.entries(vars).forEach(([k, v]) => {
          str = str.replace(new RegExp(`\\$\\{${k}\\}`, 'g'), String(v))
        })
      }
      return str
    },
    [locale],
  )

  const value = useMemo(() => ({ locale, setLocale, t }), [locale, setLocale, t])

  return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>
}

export function useI18n() {
  const ctx = useContext(I18nContext)
  if (!ctx) {
    throw new Error('useI18n must be used within I18nProvider')
  }
  return ctx
}
