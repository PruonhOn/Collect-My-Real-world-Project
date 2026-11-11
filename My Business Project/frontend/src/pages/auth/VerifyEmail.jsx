import { useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { toast } from 'sonner'
import { useAuth } from '../../context/AuthContext'
import { authService } from '../../services/authService'
import { apiErrorMessage } from '../../api/client'
import { Alert } from '../../components/ui/Feedback'
import { Button } from '../../components/ui/Button'

const MESSAGES = {
  verified: { tone: 'success', title: 'Email verified', body: 'Your email address is verified. You can use every feature of your workspace.' },
  'already-verified': { tone: 'info', title: 'Already verified', body: 'This email address was verified earlier. Nothing else to do.' },
  invalid: { tone: 'danger', title: 'Invalid verification link', body: 'The link is invalid or has expired. Request a new one below.' },
}

export default function VerifyEmail() {
  const [searchParams] = useSearchParams()
  const { user } = useAuth()
  const status = searchParams.get('status') ?? 'invalid'
  const [sending, setSending] = useState(false)
  const [error, setError] = useState(null)

  const resend = async () => {
    setSending(true)
    setError(null)

    try {
      const result = await authService.sendVerificationNotification()
      toast.success(result?.message ?? 'Verification link sent.')
    } catch (exception) {
      setError(apiErrorMessage(exception, 'Unable to send the verification link.'))
    } finally {
      setSending(false)
    }
  }

  useEffect(() => {
    if (status === 'verified') {
      toast.success('Email verified successfully.')
    }
  }, [status])

  const message = MESSAGES[status] ?? MESSAGES.invalid

  return (
    <div className="mx-auto max-w-lg rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
      <h1 className="text-xl font-semibold tracking-tight text-slate-900">Email verification</h1>

      <div className="mt-4">
        <Alert tone={message.tone} title={message.title}>
          {message.body}
        </Alert>
      </div>

      {error && (
        <div className="mt-4">
          <Alert tone="danger">{error}</Alert>
        </div>
      )}

      <div className="mt-6 flex flex-wrap gap-2">
        <Link
          to="/dashboard"
          className="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-700"
        >
          Go to dashboard
        </Link>
        {user && (
          <Button variant="secondary" onClick={resend} loading={sending}>
            Send a new link
          </Button>
        )}
      </div>
    </div>
  )
}
