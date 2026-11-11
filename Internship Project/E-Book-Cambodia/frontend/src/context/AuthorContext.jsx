import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { authorLogin as authorLoginApi, authorLogout as authorLogoutApi, authorMe } from '../api'

const AuthorContext = createContext(null)

const TOKEN_KEY = 'authorToken'
const AUTHOR_KEY = 'author'

export function AuthorProvider({ children }) {
  const [author, setAuthor] = useState(() => {
    const stored = localStorage.getItem(AUTHOR_KEY)
    return stored ? JSON.parse(stored) : null
  })
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem(TOKEN_KEY)
    if (token && !author) {
      authorMe()
        .then((res) => {
          setAuthor(res.data.author)
          localStorage.setItem(AUTHOR_KEY, JSON.stringify(res.data.author))
        })
        .catch(() => {
          localStorage.removeItem(TOKEN_KEY)
          localStorage.removeItem(AUTHOR_KEY)
        })
        .finally(() => setLoading(false))
    } else {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    const onUnauthorized = () => {
      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(AUTHOR_KEY)
      setAuthor(null)
      setLoading(false)
    }
    window.addEventListener('author:unauthorized', onUnauthorized)
    return () => window.removeEventListener('author:unauthorized', onUnauthorized)
  }, [])

  const login = useCallback(async (credentials) => {
    const res = await authorLoginApi(credentials)
    const { token, author } = res.data
    localStorage.setItem(TOKEN_KEY, token)
    localStorage.setItem(AUTHOR_KEY, JSON.stringify(author))
    setAuthor(author)
    return author
  }, [])

  const logout = useCallback(async () => {
    try {
      await authorLogoutApi()
    } catch {
      // ignore
    }
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(AUTHOR_KEY)
    setAuthor(null)
  }, [])

  const updateAuthor = useCallback((updatedAuthor) => {
    setAuthor(updatedAuthor)
    localStorage.setItem(AUTHOR_KEY, JSON.stringify(updatedAuthor))
  }, [])

  const value = useMemo(
    () => ({
      author,
      setAuthor: updateAuthor,
      isAuthor: !!author,
      login,
      logout,
      loading,
    }),
    [author, login, logout, updateAuthor, loading]
  )

  return <AuthorContext.Provider value={value}>{children}</AuthorContext.Provider>
}

export function useAuthor() {
  const context = useContext(AuthorContext)
  if (!context) {
    throw new Error('useAuthor must be used within AuthorProvider')
  }
  return context
}