import axios from 'axios'

const API_BASE = import.meta.env.VITE_API_URL || '/api/v1'

const client = axios.create({
  baseURL: API_BASE,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

client.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type']
  }
  return config
})

client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const isAuthEndpoint =
        error.config?.url?.includes('/login') ||
        error.config?.url?.includes('/register')
      if (!isAuthEndpoint) {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        if (!window.location.pathname.includes('/login')) {
          window.location.href = '/login'
        }
      }
    }
    return Promise.reject(error)
  },
)

export default client
