import { describe, it, expect, beforeEach } from 'vitest'
import client from './client'

describe('api client', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  const requestHandler = client.interceptors.request.handlers[0]
  const responseHandler = client.interceptors.response.handlers[0]

  it('sets the Authorization header when a token exists', () => {
    localStorage.setItem('token', 'my-token')
    const config = requestHandler.fulfilled({ headers: {} })
    expect(config.headers.Authorization).toBe('Bearer my-token')
  })

  it('does not set the Authorization header without a token', () => {
    const config = requestHandler.fulfilled({ headers: {} })
    expect(config.headers.Authorization).toBeUndefined()
  })

  it('on 401 removes credentials and redirects to login for protected endpoints', async () => {
    localStorage.setItem('token', 'my-token')
    localStorage.setItem('user', '{"id":1}')

    global.window.location.href = '/books/1'
    await expect(
      responseHandler.rejected({
        response: { status: 401 },
        config: { url: '/library' },
      }),
    ).rejects.toBeTruthy()

    expect(localStorage.getItem('token')).toBeNull()
    expect(localStorage.getItem('user')).toBeNull()
  })

  it('does not clear credentials for 401 on the login endpoint', async () => {
    localStorage.setItem('token', 'my-token')
    localStorage.setItem('user', '{"id":1}')

    await expect(
      responseHandler.rejected({
        response: { status: 401 },
        config: { url: '/login' },
      }),
    ).rejects.toBeTruthy()

    expect(localStorage.getItem('token')).toBe('my-token')
  })

  it('only reacts to 401 status codes', async () => {
    localStorage.setItem('user', '{"id":1}')
    await expect(
      responseHandler.rejected({
        response: { status: 422 },
        config: { url: '/library' },
      }),
    ).rejects.toBeTruthy()
    expect(localStorage.getItem('user')).not.toBeNull()
  })
})