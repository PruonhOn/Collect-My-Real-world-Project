import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { LanguageProvider } from '../context/LanguageContext'
import { useAuth as mockUseAuth } from '../context/AuthContext'
import { useToast as mockUseToast } from '../context/ToastContext'
import client from '../api/client'
import VerifyEmailBanner from './VerifyEmailBanner'

vi.mock('../api/client', () => ({ default: { post: vi.fn() } }))
vi.mock('../context/AuthContext', () => ({ useAuth: vi.fn() }))
vi.mock('../context/ToastContext', () => ({ useToast: vi.fn() }))

function renderBanner() {
  return render(
    <LanguageProvider>
      <VerifyEmailBanner />
    </LanguageProvider>,
  )
}

describe('VerifyEmailBanner', () => {
  beforeEach(() => {
    window.localStorage.setItem('dl_language', 'en')
    vi.clearAllMocks()
    mockUseToast.mockReturnValue({ toast: vi.fn() })
  })

  it('is hidden for guest visitors', () => {
    mockUseAuth.mockReturnValue({ isAuthenticated: false, user: null })
    renderBanner()
    expect(screen.queryByTestId('verify-banner')).not.toBeInTheDocument()
  })

  it('is hidden for users who already verified their email', () => {
    mockUseAuth.mockReturnValue({
      isAuthenticated: true,
      user: { id: 1, email: 'a@b.com', email_verified_at: '2026-01-01T00:00:00.000000Z' },
    })
    renderBanner()
    expect(screen.queryByTestId('verify-banner')).not.toBeInTheDocument()
  })

  it('shows for unverified users with their email address', () => {
    mockUseAuth.mockReturnValue({
      isAuthenticated: true,
      user: { id: 1, email: 'reader@example.com', email_verified_at: null },
    })
    renderBanner()
    expect(screen.getByTestId('verify-banner')).toBeInTheDocument()
    expect(screen.getByText(/reader@example\.com/)).toBeInTheDocument()
  })

  it('resends the verification email when the button is clicked', async () => {
    const user = userEvent.setup()
    const toast = vi.fn()
    mockUseToast.mockReturnValue({ toast })
    mockUseAuth.mockReturnValue({
      isAuthenticated: true,
      user: { id: 1, email: 'reader@example.com', email_verified_at: null },
    })
    client.post.mockResolvedValue({ data: { message: 'ok' } })

    renderBanner()
    await user.click(screen.getByRole('button', { name: /resend/i }))

    expect(client.post).toHaveBeenCalledWith('/email/verification-notification')
    await waitFor(() => expect(toast).toHaveBeenCalledWith(expect.anything(), 'success'))
  })
})