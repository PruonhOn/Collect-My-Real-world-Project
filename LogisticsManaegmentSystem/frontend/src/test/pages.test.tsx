import type { ReactElement } from 'react'
import { cleanup, render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { afterEach, describe, expect, it, vi, type Mock } from 'vitest'
import { AuthContext } from '@/features/auth/auth-context'
import { ToastProvider } from '@/components/ui/Toast'
import { ApiRequestError } from '@/lib/api'
import { LoginPage } from '@/pages/auth/LoginPage'
import { ForbiddenPage, NotFoundPage } from '@/pages/ErrorPages'
import type { CurrentUser } from '@/types/api'

/**
 * Mount-level smoke tests. These prove the component tree actually renders and
 * is wired to the auth context, which a type check alone cannot show.
 */

// Unmount between tests explicitly. Without this a previous render stays in the
// document and `getByLabelText` reports duplicate matches.
afterEach(cleanup)

function renderWithProviders(ui: ReactElement, user: CurrentUser | null = null) {
  return render(
    <MemoryRouter>
      <ToastProvider>
        <AuthContext.Provider value={authValue(user)}>{ui}</AuthContext.Provider>
      </ToastProvider>
    </MemoryRouter>,
  )
}

function authValue(user: CurrentUser | null, login: Mock = vi.fn().mockResolvedValue(undefined)) {
  return {
    user,
    isLoading: false,
    isAuthenticated: user !== null,
    login,
    loginWithGoogle: vi.fn().mockResolvedValue(undefined),
    logout: vi.fn().mockResolvedValue(undefined),
    refresh: vi.fn().mockResolvedValue(undefined),
    can: () => true,
    canAll: () => true,
    hasRole: () => true,
  }
}

/** Mounts the login screen in a signed-out state with a stubbed login(). */
function renderLogin(login: Mock) {
  return render(
    <MemoryRouter>
      <ToastProvider>
        <AuthContext.Provider value={authValue(null, login)}>
          <LoginPage />
        </AuthContext.Provider>
      </ToastProvider>
    </MemoryRouter>,
  )
}

describe('LoginPage', () => {
  it('renders both credential fields and the submit control', () => {
    renderWithProviders(<LoginPage />)

    expect(screen.getByLabelText(/^email address/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/^password/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument()
  })

  it('keeps submit disabled until both fields are filled', async () => {
    const user = userEvent.setup()
    renderWithProviders(<LoginPage />)

    const submit = screen.getByRole('button', { name: /sign in/i })
    expect(submit).toBeDisabled()

    await user.type(screen.getByLabelText(/^email address/i), 'ops@acme.test')
    expect(submit).toBeDisabled()

    await user.type(screen.getByLabelText(/^password/i), 'secret-password')
    expect(submit).toBeEnabled()
  })

  it('surfaces the API message verbatim on a rejected sign-in', async () => {
    const user = userEvent.setup()
    const login = vi
      .fn()
      .mockRejectedValue(new ApiRequestError(401, 'These credentials do not match our records.'))

    renderLogin(login)

    await user.type(screen.getByLabelText(/^email address/i), 'ops@acme.test')
    await user.type(screen.getByLabelText(/^password/i), 'wrong-password')
    await user.click(screen.getByRole('button', { name: /sign in/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/do not match our records/i)
  })

  it('falls back to a generic message when the failure is not an API error', async () => {
    const user = userEvent.setup()
    // A network-level TypeError must not leak a stack trace or internals.
    const login = vi.fn().mockRejectedValue(new TypeError('Failed to fetch'))

    renderLogin(login)

    await user.type(screen.getByLabelText(/^email address/i), 'ops@acme.test')
    await user.type(screen.getByLabelText(/^password/i), 'secret-password')
    await user.click(screen.getByRole('button', { name: /sign in/i }))

    expect(await screen.findByRole('alert')).toHaveTextContent(/unable to sign in/i)
  })

  it('re-enables the submit button after a failed attempt', async () => {
    const user = userEvent.setup()
    const login = vi.fn().mockRejectedValue(new Error('nope'))

    renderLogin(login)

    await user.type(screen.getByLabelText(/^email address/i), 'ops@acme.test')
    await user.type(screen.getByLabelText(/^password/i), 'secret-password')
    await user.click(screen.getByRole('button', { name: /sign in/i }))

    // A permanently locked-out button would strand the user with no way to retry.
    await waitFor(() => {
      expect(screen.getByRole('button', { name: /sign in/i })).toBeEnabled()
    })
  })

  it('offers a route to password recovery', () => {
    renderWithProviders(<LoginPage />)

    expect(screen.getByRole('link', { name: /forgot/i })).toBeInTheDocument()
  })
})

describe('error pages', () => {
  it('ForbiddenPage explains the restriction and links home', () => {
    renderWithProviders(<ForbiddenPage />)

    expect(screen.getByRole('heading', { name: /do not have access/i })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /back to dashboard/i })).toBeInTheDocument()
  })

  it('NotFoundPage reports a missing page', () => {
    renderWithProviders(<NotFoundPage />)

    expect(screen.getByRole('heading', { name: /page not found/i })).toBeInTheDocument()
  })
})
