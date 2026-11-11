import type { AxiosAdapter, AxiosResponse, InternalAxiosRequestConfig } from 'axios'
import { AxiosError as AxiosErrorClass } from 'axios'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import http, {
  ApiRequestError,
  get,
  getPaginated,
  setUnauthorizedHandler,
  tokenStorage,
} from '@/lib/api'

/**
 * Drives the real interceptor chain by swapping Axios' adapter, so the request
 * and response interceptors under test are the ones the app actually uses.
 */
function respondWith(body: unknown, status = 200): void {
  const adapter: AxiosAdapter = async (config) =>
    ({
      data: body,
      status,
      statusText: '',
      headers: {},
      config: config as InternalAxiosRequestConfig,
    }) as AxiosResponse

  http.defaults.adapter = adapter
}

function failWith(status: number, body: unknown, code = 'ERR_BAD_RESPONSE'): void {
  const adapter: AxiosAdapter = async (config) => {
    const error = new AxiosErrorClass(
      'Request failed',
      code,
      config as InternalAxiosRequestConfig,
      {},
      {
        data: body,
        status,
        statusText: '',
        headers: {},
        config: config as InternalAxiosRequestConfig,
      } as AxiosResponse,
    )

    throw error
  }

  http.defaults.adapter = adapter
}

/**
 * Captures the config the request interceptor produced, so a test can assert on
 * the outgoing headers.
 */
function captureSentConfig(): { current: InternalAxiosRequestConfig | undefined } {
  const captured: { current: InternalAxiosRequestConfig | undefined } = { current: undefined }

  const adapter: AxiosAdapter = async (config) => {
    captured.current = config as InternalAxiosRequestConfig
    return {
      data: { success: true, message: 'ok', data: null },
      status: 200,
      statusText: '',
      headers: {},
      config: config as InternalAxiosRequestConfig,
    } as AxiosResponse
  }

  http.defaults.adapter = adapter

  return captured
}

beforeEach(() => {
  tokenStorage.clear()
  setUnauthorizedHandler(() => {})
})

afterEach(() => {
  tokenStorage.clear()
  vi.restoreAllMocks()
})

describe('tokenStorage', () => {
  it('round-trips a token', () => {
    tokenStorage.set('abc123')
    expect(tokenStorage.get()).toBe('abc123')
  })

  it('returns null when nothing is stored', () => {
    expect(tokenStorage.get()).toBeNull()
  })

  it('clears the token', () => {
    tokenStorage.set('abc123')
    tokenStorage.clear()
    expect(tokenStorage.get()).toBeNull()
  })
})

describe('get', () => {
  it('unwraps the success envelope so callers receive data directly', async () => {
    respondWith({ success: true, message: 'ok', data: { id: 7, name: 'Acme' } })

    await expect(get<{ id: number }>('/customers/7')).resolves.toEqual({ id: 7, name: 'Acme' })
  })
})

describe('getPaginated', () => {
  it('returns both the rows and the pagination meta', async () => {
    respondWith({
      success: true,
      message: 'ok',
      data: [{ id: 1 }, { id: 2 }],
      meta: { current_page: 2, last_page: 5, per_page: 25, total: 101, from: 26, to: 50 },
    })

    const result = await getPaginated<{ id: number }>('/shipments', { page: 2 })

    expect(result.data).toHaveLength(2)
    expect(result.meta.current_page).toBe(2)
    expect(result.meta.total).toBe(101)
  })
})

describe('request interceptor', () => {
  it('attaches the bearer token when one is stored', async () => {
    tokenStorage.set('secret-token')
    const sent = captureSentConfig()

    await get('/ping')

    expect(sent.current?.headers.Authorization).toBe('Bearer secret-token')
  })

  it('sends no Authorization header when signed out', async () => {
    const sent = captureSentConfig()

    await get('/ping')

    expect(sent.current?.headers.Authorization).toBeUndefined()
  })
})

describe('response interceptor', () => {
  it('surfaces the API message on a 422', async () => {
    failWith(422, {
      success: false,
      message: 'The given data was invalid.',
      errors: { reference_number: ['This reference has already been taken.'] },
    })

    await expect(get('/shipments')).rejects.toMatchObject({
      name: 'ApiRequestError',
      status: 422,
      message: 'The given data was invalid.',
    })
  })

  it('clears the stored token and notifies the handler on a 401', async () => {
    tokenStorage.set('stale-token')
    const onUnauthorized = vi.fn()
    setUnauthorizedHandler(onUnauthorized)

    failWith(401, { success: false, message: 'Unauthenticated.', errors: {} })

    await expect(get('/auth/me')).rejects.toBeInstanceOf(ApiRequestError)
    expect(tokenStorage.get()).toBeNull()
    expect(onUnauthorized).toHaveBeenCalledTimes(1)
  })

  it('does not clear the token on a non-401 failure', async () => {
    tokenStorage.set('good-token')
    setUnauthorizedHandler(vi.fn())

    failWith(500, { success: false, message: 'Server error.', errors: {} })

    await expect(get('/shipments')).rejects.toBeInstanceOf(ApiRequestError)
    expect(tokenStorage.get()).toBe('good-token')
  })

  it('reports a friendly message when the API is unreachable', async () => {
    failWith(0, undefined, 'ERR_NETWORK')

    await expect(get('/shipments')).rejects.toMatchObject({
      status: 0,
      message: 'Unable to reach the server. Check your connection and try again.',
    })
  })

  it('reports a timeout distinctly from other network faults', async () => {
    failWith(0, undefined, 'ECONNABORTED')

    await expect(get('/shipments')).rejects.toMatchObject({
      message: 'The request timed out. Please try again.',
    })
  })

  it('falls back to a generic message when an edge proxy returns a non-JSON body', async () => {
    failWith(502, '<html>Bad Gateway</html>')

    await expect(get('/shipments')).rejects.toMatchObject({
      status: 502,
      message: 'An unexpected error occurred.',
    })
  })
})

describe('ApiRequestError', () => {
  it('flattens array validation messages for form libraries', () => {
    const error = new ApiRequestError(422, 'Invalid.', {
      email: ['The email field is required.'],
      name: 'Name is taken.',
    })

    expect(error.fieldErrors).toEqual({
      email: 'The email field is required.',
      name: 'Name is taken.',
    })
  })

  it('defaults errors to an empty object rather than null', () => {
    expect(new ApiRequestError(404, 'Not found.').fieldErrors).toEqual({})
  })
})
