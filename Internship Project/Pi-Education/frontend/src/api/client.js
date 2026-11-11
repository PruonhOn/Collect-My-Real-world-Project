import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  const locale = localStorage.getItem('locale') || 'km'
  if (locale) {
    config.headers['X-Locale'] = locale
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('access_token')
      localStorage.removeItem('user')
    }
    return Promise.reject(error)
  },
)

export default api
