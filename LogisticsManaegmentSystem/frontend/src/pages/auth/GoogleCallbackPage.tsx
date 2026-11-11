import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { AlertCircle } from 'lucide-react'
import { ApiRequestError } from '@/lib/api'
import { FullPageLoader } from '@/components/ui/FullPageLoader'
import { useAuth } from '@/features/auth/auth-context'

/**
 * Landing page for the API's Google callback redirect.
 *
 * The redirect carries a short-lived, single-use code rather than a token, so
 * the page redeems it over a normal POST and then enters the app. Nothing is
 * stored or logged here beyond the session the auth provider already manages.
 */
export function GoogleCallbackPage() {
  const [searchParams] = useSearchParams()
  const { loginWithGoogle } = useAuth()
  const navigate = useNavigate()

  const code = searchParams.get('code')
  const errorParam = searchParams.get('error')

  const [error, setError] = useState<string | null>(null)
  // React StrictMode runs effects twice in development. The exchange code is
  // single-use, so the second run must not burn it a second time.
  const started = useRef(false)

  useEffect(() => {
    if (errorParam) return

    if (!code) {
      setError('Google did not return a sign-in code. Please try again.')
      return
    }

    if (started.current) return
    started.current = true

    loginWithGoogle(code)
      .then(() => navigate('/', { replace: true }))
      .catch((caught: unknown) => {
        setError(
          caught instanceof ApiRequestError
            ? caught.message
            : 'Unable to finish Google sign-in. Please try again.',
        )
      })
  }, [code, errorParam, loginWithGoogle, navigate])

  const message = error ?? errorParam

  if (!message) {
    return <FullPageLoader label="Finishing Google sign-in" />
  }

  return (
    <div className="bg-ink-100 flex min-h-screen items-center justify-center px-4">
      <div className="border-ink-200 w-full max-w-md rounded-2xl border bg-white p-8 text-center shadow-[0_24px_60px_-24px_rgba(15,23,42,0.28)]">
        <span className="bg-danger-50 text-danger-600 mx-auto flex size-12 items-center justify-center rounded-full">
          <AlertCircle className="size-6" aria-hidden />
        </span>

        <h1 className="text-ink-900 mt-4 text-xl font-semibold tracking-tight">
          Google sign-in failed
        </h1>
        <p role="alert" className="text-ink-500 mt-2 text-sm">
          {message}
        </p>

        <Link
          to="/login"
          className="border-ink-300 text-ink-700 hover:bg-ink-50 mt-6 inline-flex h-10 items-center justify-center rounded-md border bg-white px-4 text-sm font-medium shadow-xs transition-colors"
        >
          Back to sign in
        </Link>
      </div>
    </div>
  )
}
