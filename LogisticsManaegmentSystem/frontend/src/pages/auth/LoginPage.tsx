import { useState } from 'react'
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom'
import { ApiRequestError, post } from '@/lib/api'
import { AlertCircle, CheckCircle2, Eye, EyeOff, Lock, Mail } from 'lucide-react'
import { Button } from '@/components/ui/Button'
import { FullPageLoader } from '@/components/ui/FullPageLoader'
import { Input } from '@/components/ui/Input'
import { AuthScreen } from '@/pages/auth/AuthScreen'
import { AuthDivider, GoogleSignInButton } from '@/pages/auth/GoogleSignInButton'
import { useAuth } from '@/features/auth/auth-context'

export function LoginPage() {
  const { login, isAuthenticated, isLoading: isCheckingSession } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()

  const [email, setEmail] = useState('admin@example.com')
  const [password, setPassword] = useState('')
  const [remember, setRemember] = useState(false)
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [verificationRequired, setVerificationRequired] = useState(false)
  const [isResending, setIsResending] = useState(false)
  const [resendMessage, setResendMessage] = useState<string | null>(null)

  const verifiedStatus = new URLSearchParams(location.search).get('verified')

  if (isCheckingSession) {
    return <FullPageLoader label="Checking your session" />
  }

  if (isAuthenticated) {
    return <Navigate to="/" replace />
  }

  const from = (location.state as { from?: string } | null)?.from ?? '/dashboard'

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault()
    setError(null)
    setVerificationRequired(false)
    setResendMessage(null)
    setIsSubmitting(true)

    try {
      await login(email, password, remember)
      navigate(from, { replace: true })
    } catch (caught) {
      // The API deliberately returns one generic message for both an unknown
      // email and a wrong password, so that it cannot be used to enumerate
      // valid accounts. Surface it verbatim.
      setError(
        caught instanceof ApiRequestError
          ? caught.message
          : 'Unable to sign in. Please try again.',
      )

      // The backend flags this case explicitly; a message match would break the
      // moment the copy changes.
      if (caught instanceof ApiRequestError && 'verification_required' in caught.errors) {
        setVerificationRequired(true)
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  async function handleResend() {
    setIsResending(true)
    setResendMessage(null)

    try {
      await post('/auth/email/verification-notification', { email: email.trim() })
      setResendMessage('If that address needs verification, a new link is on its way.')
    } catch {
      setResendMessage('We could not send the email just now. Please try again shortly.')
    } finally {
      setIsResending(false)
    }
  }

  return (
    <AuthScreen variant="login">
      <div className="mb-6">
        <h1 className="text-2xl font-semibold tracking-tight text-ink-900">
          Welcome back
        </h1>
        <p className="mt-1.5 text-sm text-ink-500">
          Sign in to pick up where your shipments left off.
        </p>
      </div>

      {verifiedStatus === '1' && (
        <div
          role="status"
          className="border-success-500/30 bg-success-50 text-success-700 mb-5 flex items-start gap-2.5 rounded-lg border p-3 text-sm"
        >
          <CheckCircle2 className="mt-0.5 size-4 shrink-0" aria-hidden />
          <span>Your email is verified. You can sign in now.</span>
        </div>
      )}

      {verifiedStatus === 'already' && (
        <div
          role="status"
          className="border-info-500/30 bg-info-50 text-info-700 mb-5 flex items-start gap-2.5 rounded-lg border p-3 text-sm"
        >
          <CheckCircle2 className="mt-0.5 size-4 shrink-0" aria-hidden />
          <span>That address was already verified. Sign in below.</span>
        </div>
      )}

      {verifiedStatus === 'invalid' && (
        <div
          role="alert"
          className="border-danger-200 bg-danger-50 text-danger-700 mb-5 flex items-start gap-2.5 rounded-lg border p-3 text-sm"
        >
          <AlertCircle className="mt-0.5 size-4 shrink-0" aria-hidden />
          <span>
            This verification link is invalid or has expired. Sign in to request a new one.
          </span>
        </div>
      )}

      {error && (
        <div
          role="alert"
          className="border-danger-200 bg-danger-50 text-danger-700 mb-5 flex items-start gap-2.5 rounded-lg border p-3 text-sm"
        >
          <AlertCircle className="mt-0.5 size-4 shrink-0" aria-hidden />
          <span>{error}</span>
        </div>
      )}

      {verificationRequired && (
        <div className="border-warning-500/30 bg-warning-50 text-warning-700 mb-5 rounded-lg border p-3 text-sm">
          <p>
            We can send another link to <strong>{email.trim()}</strong>.
          </p>
          <Button
            variant="outline"
            size="sm"
            onClick={handleResend}
            loading={isResending}
            className="mt-2.5 bg-white"
          >
            Resend verification email
          </Button>
          {resendMessage && (
            <p role="status" className="mt-2 text-xs">
              {resendMessage}
            </p>
          )}
        </div>
      )}

      <GoogleSignInButton />
      <AuthDivider />

      <form onSubmit={handleSubmit} noValidate className="auth-form space-y-4">
        <Input
          label="Email address"
          type="email"
          name="email"
          autoComplete="username"
          required
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          placeholder="you@company.com"
          prefix={<Mail className="size-4" aria-hidden />}
          autoFocus
        />

        <Input
          label="Password"
          type={showPassword ? 'text' : 'password'}
          name="password"
          autoComplete="current-password"
          required
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          placeholder="••••••••"
          prefix={<Lock className="size-4" aria-hidden />}
          suffix={
            <button
              type="button"
              onClick={() => setShowPassword((shown) => !shown)}
              aria-label={showPassword ? 'Hide password' : 'Show password'}
              className="text-ink-400 hover:text-ink-700 -mr-1 rounded p-0.5"
            >
              {showPassword ? <EyeOff className="size-4" aria-hidden /> : <Eye className="size-4" aria-hidden />}
            </button>
          }
        />

        <div className="flex items-center justify-between">
          <label className="text-ink-700 flex cursor-pointer items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={remember}
              onChange={(event) => setRemember(event.target.checked)}
              className="border-ink-300 text-brand-600 focus:ring-brand-500/30 rounded"
            />
            Keep me signed in
          </label>

          <Link
            to="/forgot-password"
            className="text-brand-700 hover:text-brand-800 text-sm font-medium"
          >
            Forgot password?
          </Link>
        </div>

        {/* The form sets noValidate, so the browser will not block an empty
            submit. Gate the button instead of firing a pointless request. */}
        <Button
          type="submit"
          fullWidth
          size="lg"
          loading={isSubmitting}
          disabled={email.trim() === '' || password === ''}
          className="auth-submit"
        >
          Sign in
        </Button>
      </form>

      <p className="mt-6 text-center text-sm text-ink-500">
        New to Logistics ERP?{' '}
        <Link to="/register" className="text-brand-700 hover:text-brand-800 font-medium">
          Create an account
        </Link>
      </p>

      <p className="mt-4 text-center text-xs text-ink-500">
        Having trouble signing in? Contact your system administrator.
      </p>
    </AuthScreen>
  )
}
