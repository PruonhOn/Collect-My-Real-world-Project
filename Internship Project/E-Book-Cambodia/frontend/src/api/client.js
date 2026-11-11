import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    Accept: 'application/json',
  },
})

const AUTH_ATTEMPT_PATHS = ['/login', '/register', '/google-login', '/author/login']

const isAuthorPath = (url) => {
  const u = url.startsWith('/') ? url : `/${url}`
  return u === '/author/login' || u.startsWith('/author/')
}

const isAuthAttempt = (url) => {
  const u = url.startsWith('/') ? url : `/${url}`
  return AUTH_ATTEMPT_PATHS.includes(u)
}

api.interceptors.request.use((config) => {
  const url = config.url || ''
  const token = localStorage.getItem(isAuthorPath(url) ? 'authorToken' : 'token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  const locale = localStorage.getItem('locale') || 'km'
  if (config.method === 'get') {
    config.params = { ...(config.params || {}), locale }
  } else {
    config.headers['Accept-Language'] = locale
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const url = error.config?.url || ''
      const authorRoute = isAuthorPath(url)
      if (authorRoute) {
        localStorage.removeItem('authorToken')
        localStorage.removeItem('author')
      } else {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      }

      if (!isAuthAttempt(url)) {
        window.dispatchEvent(new CustomEvent(authorRoute ? 'author:unauthorized' : 'auth:unauthorized'))
      }
    }
    return Promise.reject(error)
  }
)

export default api