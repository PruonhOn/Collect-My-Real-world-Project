import { ArrowLeft, MailCheck } from 'lucide-react'
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { ApiRequestError, post } from '@/lib/api'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'

export function ForgotPasswordPage() {
  const [email, setEmail] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [isSent, setIsSent] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault()
    setError(null)
    setIsSubmitting(true)

    try {
      await post('/auth/forgot-password', { email })
      setIsSent(true)
    } catch (caught) {
      setError(
        caught instanceof ApiRequestError
          ? caught.message
          : 'Unable to request a reset link. Please try again.',
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <AuthLayout>
      {isSent ? (
        <div className="text-center">
          <div className="bg-success-50 text-success-600 mx-auto mb-4 flex size-12 items-center justify-center rounded-full">
            <MailCheck className="size-6" aria-hidden />
          </div>

          <h1 className="text-lg font-semibold text-ink-900">Check your email</h1>
          <p className="mt-2 text-sm text-ink-500">
            If an account exists for <span className="font-medium text-ink-800">{email}</span>, a
            password reset link is on its way. The link expires in 60 minutes.
          </p>

          <Link to="/login" className="mt-6 inline-block">
            <Button variant="outline" icon={<ArrowLeft className="size-4" aria-hidden />}>
              Back to sign in
            </Button>
          </Link>
        </div>
      ) : (
        <>
          <h1 className="text-lg font-semibold text-ink-900">Reset your password</h1>
          <p className="mt-1.5 text-sm text-ink-500">
            Enter the email address for your account and we will send you a reset link.
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
              label="Email address"
              type="email"
              required
              autoComplete="username"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              placeholder="you@company.com"
              prefix={<MailIcon />}
              autoFocus
            />

            <Button type="submit" fullWidth size="lg" loading={isSubmitting}>
              Send reset link
            </Button>
          </form>

          <Link
            to="/login"
            className="text-brand-700 hover:text-brand-800 mt-6 inline-flex items-center gap-1.5 text-sm font-medium"
          >
            <ArrowLeft className="size-3.5" aria-hidden />
            Back to sign in
          </Link>
        </>
      )}
    </AuthLayout>
  )
}

function MailIcon() {
  return (
    <svg viewBox="0 0 24 24" className="size-4" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden>
      <path d="M3 7.5 12 13l9-5.5M3 7.5v9A1.5 1.5 0 0 0 4.5 18h15a1.5 1.5 0 0 0 1.5-1.5v-9M3 7.5 4.7 6A1.5 1.5 0 0 1 5.9 5.6h12.2A1.5 1.5 0 0 1 19.3 6L21 7.5" />
    </svg>
  )
}

/** Shared card shell for the two secondary auth screens. */
export function AuthLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-screen items-center justify-center bg-ink-100 px-4 py-12">
      <div className="w-full max-w-sm rounded-xl border border-ink-200 bg-white p-7 shadow-sm">
        <div className="mb-6 flex items-center gap-2.5">
          <div className="bg-brand-500 flex size-8 items-center justify-center rounded-lg">
            <svg viewBox="0 0 24 24" className="size-4.5 text-white" fill="currentColor" aria-hidden>
              <path d="M3 18h18l-2 3H5l-2-3Zm2.2-2 2.3-5.4A2 2 0 0 1 9.3 9.5h5.4a2 2 0 0 1 1.8 1.1l2.3 5.4H5.2Zm3.3-1.6h6l-1.1-2.6H8.6l-1.1 2.6ZM12 3a2.4 2.4 0 1 1 0 4.8A2.4 2.4 0 0 1 12 3Z" />
            </svg>
          </div>
          <span className="text-sm font-semibold text-ink-900">Logistics ERP</span>
        </div>

        {children}
      </div>
    </div>
  )
}
