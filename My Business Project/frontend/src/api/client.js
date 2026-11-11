import axios from 'axios'

export const TOKEN_STORAGE_KEY = 'my_business.token'
export const BUSINESS_STORAGE_KEY = 'my_business.business_id'

const baseURL = import.meta.env.VITE_API_URL || '/api/v1'

const api = axios.create({
  baseURL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

/**
 * Callback used to sign the user out when the API rejects the token.
 * The auth provider registers it to avoid a circular import.
 */
let unauthorizedHandler = null

export function setUnauthorizedHandler(handler) {
  unauthorizedHandler = handler
}

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_STORAGE_KEY)

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  const businessId = localStorage.getItem(BUSINESS_STORAGE_KEY)

  if (businessId) {
    config.headers['X-Business-Id'] = businessId
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error?.response?.status
    const url = error?.config?.url || ''

    if (status === 401 && !url.includes('/auth/login') && !url.includes('/auth/register')) {
      localStorage.removeItem(TOKEN_STORAGE_KEY)
      localStorage.removeItem(BUSINESS_STORAGE_KEY)

      if (unauthorizedHandler) {
        unauthorizedHandler()
      }
    }

    return Promise.reject(error)
  },
)

/**
 * Extract a human readable message from an API error.
 */
export function apiErrorMessage(error, fallback = 'Something went wrong. Please try again.') {
  const data = error?.response?.data

  if (data?.errors) {
    const messages = Object.values(data.errors).flat().filter(Boolean)

    if (messages.length > 0) {
      return messages[0]
    }
  }

  if (data?.message) {
    return data.message
  }

  if (error?.message && !error.message.includes('Network Error')) {
    return error.message
  }

  return fallback
}

/**
 * Field level validation errors returned by Laravel (422 responses).
 */
export function apiFieldErrors(error) {
  return error?.response?.data?.errors ?? {}
}

/**
 * True when the request was rejected because the plan limit was reached.
 */
export function isUpgradeRequired(error) {
  const data = error?.response?.data

  return Boolean(data?.upgrade_required) || error?.response?.status === 402
}

/**
 * Download a binary endpoint (CSV, XLSX, PDF) through the authenticated
 * API client and hand the file to the browser.
 */
export async function downloadFile(url, filename, params = {}) {
  const response = await api.get(url, { params, responseType: 'blob' })
  const blob = new Blob([response.data])
  const objectUrl = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = objectUrl
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(objectUrl)
}

export { api }

export default api
