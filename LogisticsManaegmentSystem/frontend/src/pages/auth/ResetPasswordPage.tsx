import { useSearchParams } from 'react-router-dom'
import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ApiRequestError, post } from '@/lib/api'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { AuthLayout } from '@/pages/auth/ForgotPasswordPage'

/**
 * Password reset.
 *
 * The token arrives in the query string from the emailed link. It is exchanged
 * once, server-side, for a new password; the URL is then cleared with
 * `replace` so the token does not linger in the address bar or in history.
 */
export function ResetPasswordPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const navigate = useNavigate()

  const token = searchParams.get('token') ?? ''
  const email = searchParams.get('email') ?? ''

  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const mismatch = passwordConfirmation.length > 0 && password !== passwordConfirmation

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault()
    setError(null)
    setIsSubmitting(true)

    try {
      await post('/auth/reset-password', {
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })

      // Drop the token from the URL before navigating.
      setSearchParams({}, { replace: true })
      navigate('/login', { replace: true, state: { passwordReset: true } })
    } catch (caught) {
      setError(
        caught instanceof ApiRequestError
          ? caught.message
          : 'Unable to reset your password. Please request a new link.',
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  if (!token) {
    return (
      <AuthLayout>
        <h1 className="text-lg font-semibold text-ink-900">Reset link is invalid</h1>
        <p className="mt-2 text-sm text-ink-500">
          This password reset link is missing its token. Reset links expire after 60 minutes and
          can only be used once.
        </p>
        <Link to="/forgot-password" className="mt-6 inline-block">
          <Button variant="primary">Request a new link</Button>
        </Link>
      </AuthLayout>
    )
  }

  return (
    <AuthLayout>
      <h1 className="text-lg font-semibold text-ink-900">Choose a new password</h1>
      <p className="mt-1.5 text-sm text-ink-500">
        Your new password must be at least 10 characters and include letters, numbers and a
        symbol.
      </p>

      {error && (
        <div
          role="alert"
          className="border-danger-200 bg-danger-50 text-danger-700 mt-5 rounded-lg border p-3 text-sm"
        >
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} noValidate className="mt-6 space-y-4">
        <Input
          label="New password"
          type="password"
          required
          autoComplete="new-password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          autoFocus
        />

        <Input
          label="Confirm new password"
          type="password"
          required
          autoComplete="new-password"
          value={passwordConfirmation}
          onChange={(event) => setPasswordConfirmation(event.target.value)}
          error={mismatch ? 'The two passwords do not match.' : undefined}
        />

        <Button
          type="submit"
          fullWidth
          size="lg"
          loading={isSubmitting}
          disabled={mismatch || password.length === 0}
        >
          Reset password
        </Button>
      </form>
    </AuthLayout>
  )
}
