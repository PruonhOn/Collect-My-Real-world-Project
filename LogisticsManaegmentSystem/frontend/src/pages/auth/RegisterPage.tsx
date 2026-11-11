import { useState } from 'react'
import { Link, Navigate } from 'react-router-dom'
import { ApiRequestError, post } from '@/lib/api'
import { AlertCircle, CheckCircle2, Eye, EyeOff, Lock, Mail, MailCheck, User } from 'lucide-react'
import { Button } from '@/components/ui/Button'
import { FullPageLoader } from '@/components/ui/FullPageLoader'
import { Input } from '@/components/ui/Input'
import { AuthScreen } from '@/pages/auth/AuthScreen'
import { AuthDivider, GoogleSignInButton } from '@/pages/auth/GoogleSignInButton'
import { useAuth } from '@/features/auth/auth-context'

export function RegisterPage() {
  const { isAuthenticated, isLoading: isCheckingSession } = useAuth()

  const [name, setName] = useState('')
  const [email, setEmail] = useState('admin@example.com')
  const [password, setPassword] = useState('')
  const [passwordConfirmation, setPasswordConfirmation] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})
  const [isSubmitting, setIsSubmitting] = useState(false)

  const [registeredEmail, setRegisteredEmail] = useState<string | null>(null)
  const [isResending, setIsResending] = useState(false)
  const [resendMessage, setResendMessage] = useState<string | null>(null)
  const [resendError, setResendError] = useState<string | null>(null)

  if (isCheckingSession) {
    return <FullPageLoader label="Checking your session" />
  }

  if (isAuthenticated) {
    return <Navigate to="/" replace />
  }

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault()
    setError(null)
    setIsSubmitting(true)

    const submittedEmail = email.trim()

    try {
      await post('/auth/register', {
        name,
        email: submittedEmail,
        password,
        password_confirmation: passwordConfirmation,
      })

      // Registration no longer signs the user in. The account exists but the
      // address is unverified, and login rejects it until the emailed link is
      // opened, so hold the user here with the next step rather than bouncing
      // them to a sign-in form that will refuse them.
      setRegisteredEmail(submittedEmail)
      setPassword('')
      setPasswordConfirmation('')
      setFieldErrors({})
    } catch (caught) {
      if (caught instanceof ApiRequestError) {
        setError(caught.message)
        setFieldErrors(caught.fieldErrors)
      } else {
        setError('Unable to register. Please try again.')
        setFieldErrors({})
      }
    } finally {
      setIsSubmitting(false)
    }
  }

  async function handleResend() {
    if (!registeredEmail) return

    setIsResending(true)
    setResendMessage(null)
    setResendError(null)

    try {
      await post('/auth/email/verification-notification', { email: registeredEmail })
      setResendMessage('If that address needs verification, a new link is on its way.')
    } catch (caught) {
      setResendError(
        caught instanceof ApiRequestError
          ? caught.message
          : 'We could not send the email just now. Please try again shortly.',
      )
    } finally {
      setIsResending(false)
    }
  }

  return (
    <AuthScreen variant="register">
      {registeredEmail ? (
        <>
          <div className="mb-6">
            <span className="bg-success-50 text-success-600 flex size-12 items-center justify-center rounded-full">
              <MailCheck className="size-6" aria-hidden />
            </span>
            <h1 className="text-ink-900 mt-4 text-2xl font-semibold tracking-tight">
              Check your email
            </h1>
            <p className="text-ink-500 mt-1.5 text-sm">
              We sent a verification link to{' '}
              <strong className="text-ink-700 font-medium">{registeredEmail}</strong>. Open
              it to activate your account, then sign in.
            </p>
          </div>

          {resendMessage && (
            <div
              role="status"
              className="border-success-500/30 bg-success-50 text-success-700 mb-4 flex items-start gap-2.5 rounded-lg border p-3 text-sm"
            >
              <CheckCircle2 className="mt-0.5 size-4 shrink-0" aria-hidden />
              <span>{resendMessage}</span>
            </div>
          )}

          {resendError && (
            <div
              role="alert"
              className="border-danger-200 bg-danger-50 text-danger-700 mb-4 flex items-start gap-2.5 rounded-lg border p-3 text-sm"
            >
              <AlertCircle className="mt-0.5 size-4 shrink-0" aria-hidden />
              <span>{resendError}</span>
            </div>
          )}

          <Button
            variant="outline"
            fullWidth
            size="lg"
            onClick={handleResend}
            loading={isResending}
          >
            Resend verification email
          </Button>

          <p className="text-ink-500 mt-4 text-center text-xs">
            The link expires in 60 minutes. If it does not arrive, check your spam folder
            before requesting another.
          </p>

          <p className="text-ink-500 mt-6 text-center text-sm">
            Already verified?{' '}
            <Link to="/login" className="text-brand-700 hover:text-brand-800 font-medium">
              Sign in
            </Link>
          </p>
        </>
      ) : (
        <>
          <div className="mb-6">
            <h1 className="text-ink-900 text-2xl font-semibold tracking-tight">
              Create your account
            </h1>
            <p className="text-ink-500 mt-1.5 text-sm">
              A few details and your workspace is ready.
            </p>
          </div>

          {error && (
            <div
              role="alert"
              className="border-danger-200 bg-danger-50 text-danger-700 mb-5 flex items-start gap-2.5 rounded-lg border p-3 text-sm"
            >
              <AlertCircle className="mt-0.5 size-4 shrink-0" aria-hidden />
              <span>{error}</span>
            </div>
          )}

          <GoogleSignInButton />
          <AuthDivider />

          <form onSubmit={handleSubmit} noValidate className="auth-form space-y-4">
            <Input
              label="Full name"
              type="text"
              name="name"
              required
              value={name}
              onChange={(event) => setName(event.target.value)}
              placeholder="John Doe"
              prefix={<User className="size-4" aria-hidden />}
              error={fieldErrors.name}
              autoFocus
            />

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
              error={fieldErrors.email}
            />

            <Input
              label="Password"
              type={showPassword ? 'text' : 'password'}
              name="password"
              autoComplete="new-password"
              required
              value={password}
              onChange={(event) => setPassword(event.target.value)}
              placeholder="••••••••"
              prefix={<Lock className="size-4" aria-hidden />}
              hint="At least 10 characters with letters, numbers, and symbols"
              error={fieldErrors.password}
              suffix={
                <button
                  type="button"
                  onClick={() => setShowPassword((shown) => !shown)}
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                  className="text-ink-400 hover:text-ink-700 -mr-1 rounded p-0.5"
                >
                  {showPassword ? (
                    <EyeOff className="size-4" aria-hidden />
                  ) : (
                    <Eye className="size-4" aria-hidden />
                  )}
                </button>
              }
            />

            <Input
              label="Confirm password"
              type={showPassword ? 'text' : 'password'}
              name="password_confirmation"
              autoComplete="new-password"
              required
              value={passwordConfirmation}
              onChange={(event) => setPasswordConfirmation(event.target.value)}
              placeholder="••••••••"
              prefix={<Lock className="size-4" aria-hidden />}
              error={fieldErrors.password_confirmation}
            />

            <Button
              type="submit"
              fullWidth
              size="lg"
              loading={isSubmitting}
              disabled={
                name.trim() === '' ||
                email.trim() === '' ||
                password === '' ||
                passwordConfirmation === ''
              }
              className="auth-submit"
            >
              Sign up
            </Button>
          </form>

          <p className="text-ink-500 mt-6 text-center text-sm">
            Already have an account?{' '}
            <Link to="/login" className="text-brand-700 hover:text-brand-800 font-medium">
              Sign in
            </Link>
          </p>
        </>
      )}
    </AuthScreen>
  )
}
