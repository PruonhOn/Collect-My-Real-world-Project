import type { AxiosError, AxiosInstance, AxiosRequestConfig } from 'axios'
import axios from 'axios'
import type { ApiError } from '@/types/api'

const TOKEN_STORAGE_KEY = 'logistics.token'

/**
 * The bearer token is held in localStorage.
 *
 * This is a deliberate, documented trade-off: it is readable by any XSS payload
 * on the page. The mitigations are a strict Content-Security-Policy, never
 * rendering server HTML through dangerouslySetInnerHTML, and short token
 * lifetimes with server-side revocation. A httpOnly cookie would resist XSS
 * better but reintroduces CSRF surface and blocks the same-origin multi-host
 * deployment the Docker/Nginx setup uses.
 */
export const tokenStorage = {
  get(): string | null {
    try {
      return window.localStorage.getItem(TOKEN_STORAGE_KEY)
    } catch {
      // Private browsing / storage disabled.
      return null
    }
  },
  set(token: string): void {
    try {
      window.localStorage.setItem(TOKEN_STORAGE_KEY, token)
    } catch {
      /* ignore */
    }
  },
  clear(): void {
    try {
      window.localStorage.removeItem(TOKEN_STORAGE_KEY)
    } catch {
      /* ignore */
    }
  },
}

/** A normalised failure, so the UI never has to parse Axios internals. */
export class ApiRequestError extends Error {
  readonly status: number
  readonly errors: Record<string, string[] | string>

  constructor(status: number, message: string, errors: Record<string, string[] | string> = {}) {
    super(message)
    this.name = 'ApiRequestError'
    this.status = status
    this.errors = errors
  }

  /** Field-level messages, ready to hand to a form library. */
  get fieldErrors(): Record<string, string> {
    return Object.fromEntries(
      Object.entries(this.errors).map(([field, value]) => [
        field,
        Array.isArray(value) ? (value[0] ?? '') : value,
      ]),
    )
  }
}

type UnauthorizedHandler = () => void

let onUnauthorized: UnauthorizedHandler = () => {}

/**
 * Registered by the auth provider so that a 401 from any request tears down
 * the session exactly once, no matter how many requests fail in parallel.
 */
export function setUnauthorizedHandler(handler: UnauthorizedHandler): void {
  onUnauthorized = handler
}

const http: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? '/api/v1',
  timeout: 30_000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

http.interceptors.request.use((config) => {
  const token = tokenStorage.get()

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  // Let the browser set the multipart boundary for FormData uploads.
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type']
  }

  return config
})

/**
 * Reads the JSON error envelope out of a response that was asked for as a blob.
 *
 * A download requests `responseType: 'blob'`, so when it fails its error body is a
 * `Blob` too and `body.message` is undefined - which would turn every failure into
 * the generic "An unexpected error occurred". That matters most for the document
 * download's 409, where the server's message is the entire point: it says the file
 * is no longer on the disk and should be uploaded again, and a generic error would
 * leave the user re-clicking a button that cannot work.
 *
 * Returns undefined for a body that is not the envelope - an HTML error page from a
 * proxy, or an empty 502 - so the caller falls back to its own messages.
 */
async function readBlobErrorEnvelope(blob: Blob): Promise<ApiError | undefined> {
  try {
    const parsed: unknown = JSON.parse(await blob.text())

    return typeof parsed === 'object' && parsed !== null ? (parsed as ApiError) : undefined
  } catch {
    return undefined
  }
}

http.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<ApiError | Blob>) => {
    const status = error.response?.status ?? 0
    const body =
      error.response?.data instanceof Blob
        ? await readBlobErrorEnvelope(error.response.data)
        : (error.response?.data as ApiError | undefined)

    // The backend always answers with the {success,message,errors} envelope.
    // A non-conforming body means the request never reached the API (network,
    // DNS, CORS) or an edge proxy returned HTML.
    const message =
      body?.message ??
      (error.code === 'ECONNABORTED'
        ? 'The request timed out. Please try again.'
        : status === 0
          ? 'Unable to reach the server. Check your connection and try again.'
          : 'An unexpected error occurred.')

    const errors = body?.errors ?? {}

    if (status === 401) {
      tokenStorage.clear()
      onUnauthorized()
    }

    return Promise.reject(new ApiRequestError(status, message, errors))
  },
)

/** Unwraps the success envelope so callers receive `data` directly. */
export async function get<T>(
  url: string,
  config?: AxiosRequestConfig,
): Promise<T> {
  const response = await http.get<{ data: T }>(url, config)
  return response.data.data
}

/** Returns the full paginated envelope including `meta`. */
export async function getPaginated<T>(
  url: string,
  params?: Record<string, unknown>,
): Promise<{ data: T[]; meta: import('@/types/api').PaginationMeta }> {
  const response = await http.get<{
    data: T[]
    meta: import('@/types/api').PaginationMeta
  }>(url, { params })

  return { data: response.data.data, meta: response.data.meta }
}

export async function post<T>(
  url: string,
  payload?: unknown,
  config?: AxiosRequestConfig,
): Promise<T> {
  const response = await http.post<{ data: T }>(url, payload, config)
  return response.data.data
}

export async function put<T>(
  url: string,
  payload?: unknown,
  config?: AxiosRequestConfig,
): Promise<T> {
  const response = await http.put<{ data: T }>(url, payload, config)
  return response.data.data
}

export async function patch<T>(
  url: string,
  payload?: unknown,
  config?: AxiosRequestConfig,
): Promise<T> {
  const response = await http.patch<{ data: T }>(url, payload, config)
  return response.data.data
}

export async function del<T>(
  url: string,
  config?: AxiosRequestConfig,
): Promise<T> {
  const response = await http.delete<{ data: T }>(url, config)
  return response.data.data
}

/**
 * The filename a server asked to be used, from a `Content-Disposition` header.
 *
 * Two spellings arrive in practice and the second is the one that has to be read
 * first. `filename*` is RFC 5987's form and is the only one that can carry a
 * non-ASCII name - a Chinese carrier's bill of lading arrives as
 * `filename*=UTF-8''%E8%B4%A7%E6%A8%A1.pdf` - so a header carrying both names the
 * ASCII one first for compatibility and the encoded one second, and reading the
 * plain `filename` alone would save every such document as `document.pdf`.
 *
 * Returns undefined when the header is absent or carries neither spelling. The
 * caller supplies a fallback rather than inventing one: the API sets this header on
 * every download, so its absence means something in front of the API stripped it,
 * and a `download.bin` would be less useful than the record's own filename.
 */
export function parseContentDispositionFilename(header: string | undefined): string | undefined {
  if (!header) return undefined

  // RFC 5987: charset'language'percent-encoded-value, e.g. UTF-8''a%20b.pdf
  const extended = /filename\*\s*=\s*([^']*)'[^']*'([^;]+)/i.exec(header)
  if (extended) {
    try {
      return sanitizeFilename(decodeURIComponent(extended[2].trim()))
    } catch {
      // Malformed percent-encoding: fall through to the plain form rather than
      // throwing, since a mangled name is not worth failing the download over.
    }
  }

  const plain = /filename\s*=\s*"([^"]*)"|filename\s*=\s*([^;]+)/i.exec(header)
  const name = plain?.[1] ?? plain?.[2]

  return name ? sanitizeFilename(name.trim()) : undefined
}

/**
 * Strips a filename down to something safe to hand a download attribute.
 *
 * Three hazards, all of them coming from outside: a name may carry a path, and a
 * download attribute reading `../../report.pdf` is a path the browser may resolve
 * before it writes; it may carry characters no filesystem accepts, which in Chrome
 * silently truncates the name at the first of them; and it may carry a newline, in
 * which case anything that re-sends the header can be split by it.
 *
 * The extension survives because it is part of the basename, not because it is
 * treated specially - a saved document with no extension is not openable by double
 * click, and the whole point of asking the server is to get the right one.
 */
function sanitizeFilename(name: string): string {
  const basename = name.split(/[\\/]/).pop() ?? ''
  const cleaned = basename
    // eslint-disable-next-line no-control-regex
    .replace(/[\u0000-\u001f\u007f]/g, '')
    .replace(/[<>:"|?*]/g, '_')
    .trim()

  return cleaned || 'download'
}

/**
 * Strips the client's own base path off a URL the API published.
 *
 * Axios prepends `baseURL` to every request, so a path that already contains it is
 * requested twice over: `/api/v1` plus the `api/v1/documents/12/download` that the
 * API published becomes `/api/v1/api/v1/documents/12/download`, and the answer is a
 * 404 from a route that plainly exists - which reads as a broken link rather than as
 * a doubled prefix.
 *
 * The asymmetry with every other call in this client is deliberate. Requests here
 * are written by hand as `customers`, `invoices/12/issue` - relative to the base,
 * because the developer writing them is looking at the base. `download_url` is not
 * written by hand: the API publishes it, because the storage route is the API's to
 * name and a client that assembled its own would have to know where the file lives.
 * So the reconciliation happens here, at the boundary, and nowhere else has to know
 * about both shapes.
 *
 * A path that does not start with the base is passed through untouched, so an
 * absolute URL from the API is left alone and a path served from another prefix
 * still works.
 */
function relativeToBasePath(url: string): string {
  const base = new URL(http.defaults.baseURL ?? '/', window.location.origin).pathname.replace(/\/+$/, '')

  if (!base || base === '/' || !url.startsWith(base)) return url

  return url.slice(base.length) || '/'
}

/**
 * Downloads a file through the authenticated API and saves it to the user's disk.
 *
 * A blob, an object URL and a synthetic click, in that order, rather than an anchor
 * pointing at the API: the endpoint is behind a bearer token, so a plain navigation
 * to it would arrive with no `Authorization` header and come back as a 401 - and a
 * user watching a blank tab has no way to tell that from a broken link.
 *
 * `fallbackName` is used only when the response names no file. The object URL is
 * revoked in a `finally`, since a revoked-early URL is the difference between a
 * download that works once and one that silently stops working in some browsers,
 * and it is not a resource a page unload is guaranteed to clean up.
 */
export async function downloadFile(path: string, fallbackName: string): Promise<void> {
  const response = await http.get<Blob>(relativeToBasePath(path), { responseType: 'blob' })

  const name =
    parseContentDispositionFilename(response.headers['content-disposition'] as string | undefined) ??
    fallbackName

  const url = URL.createObjectURL(response.data)

  try {
    const anchor = document.createElement('a')
    anchor.href = url
    anchor.download = name
    anchor.rel = 'noopener'
    anchor.style.display = 'none'

    document.body.append(anchor)
    anchor.click()
    anchor.remove()
  } finally {
    URL.revokeObjectURL(url)
  }
}

export default http
