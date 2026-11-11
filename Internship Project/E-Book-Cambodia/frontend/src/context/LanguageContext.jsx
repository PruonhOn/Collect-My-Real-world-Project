import { createContext, useContext, useMemo, useState } from 'react'
import { getTranslation } from '../i18n/translations'

const LanguageContext = createContext(null)

export function LanguageProvider({ children }) {
  const [locale, setLocale] = useState(() => localStorage.getItem('locale') || 'km')

  const changeLocale = (lang) => {
    setLocale(lang)
    localStorage.setItem('locale', lang)
  }

  const t = useMemo(() => (key) => getTranslation(locale, key), [locale])

  const value = useMemo(() => ({ locale, changeLocale, t }), [locale, t])

  return <LanguageContext.Provider value={value}>{children}</LanguageContext.Provider>
}

export function useLanguage() {
  const context = useContext(LanguageContext)
  if (!context) {
    throw new Error('useLanguage must be used within LanguageProvider')
  }
  return context
}