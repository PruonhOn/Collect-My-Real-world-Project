import { createContext, useContext, useState, useCallback, useMemo } from 'react'
import { translations } from '../translations'

const LanguageContext = createContext(null)

const STORAGE_KEY = 'dl_language'

function getInitialLang() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved && translations[saved]) return saved
  } catch {}
  return 'kh'
}

export function LanguageProvider({ children }) {
  const [lang, setLangState] = useState(getInitialLang)

  const setLang = useCallback((value) => {
    if (!translations[value]) return
    setLangState(value)
    try {
      localStorage.setItem(STORAGE_KEY, value)
    } catch {}
  }, [])

  const t = useCallback(
    (key, vars) => {
      const dict = translations[lang] || translations.kh
      let str = dict[key]
      if (str === undefined) str = translations.kh[key] ?? key
      if (str !== undefined && vars) {
        for (const [k, v] of Object.entries(vars)) {
          str = str.replaceAll(`{${k}}`, v)
        }
      }
      return str
    },
    [lang],
  )

  const value = useMemo(() => ({ lang, setLang, t }), [lang, setLang, t])

  return <LanguageContext.Provider value={value}>{children}</LanguageContext.Provider>
}

export function useLanguage() {
  const ctx = useContext(LanguageContext)
  if (!ctx) throw new Error('useLanguage must be used within a LanguageProvider')
  return ctx
}
