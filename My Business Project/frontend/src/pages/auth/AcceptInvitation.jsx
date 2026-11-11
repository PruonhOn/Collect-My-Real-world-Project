import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { toast } from 'sonner'
import { useAuth } from '../../context/AuthContext'
import { authService } from '../../services/authService'
import { apiErrorMessage } from '../../api/client'
import { Alert, LoadingBlock } from '../../components/ui/Feedback'
import { Button } from '../../components/ui/Button'

export default function AcceptInvitation() {
  const { token } = useParams()
  const { isAuthenticated, user, signOut } = useAuth()
  const navigate = useNavigate()
  const [invitation, setInvitation] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [accepting, setAccepting] = useState(false)

  useEffect(() => {
    let cancelled = false

    authService
      .invitation(token)
      .then((data) => {
        if (!cancelled) {
          setInvitation(data.data)
        }
      })
      .catch((exception) => {
        if (!cancelled) {
          setError(apiErrorMessage(exception, 'This invitation is invalid or has expired.'))
        }
      })
      .finally(() => {
        if (!cancelled) {
          setLoading(false)
        }
      })

    return () => {
      cancelled = true
    }
  }, [token])

  const accept = async () => {
    setAccepting(true)
    setError(null)

    try {
      await authService.acceptInvitation(token)
      toast.success('Invitation accepted.')
      navigate('/dashboard', { replace: true })
    } catch (exception) {
      setError(apiErrorMessage(exception, 'Unable to accept the invitation.'))
    } finally {
      setAccepting(false)
    }
  }

  if (loading) {
    return (
      <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <LoadingBlock label="Loading invitation…" />
      </div>
    )
  }

  const emailMatches = invitation?.email && user?.email
    ? invitation.email.toLowerCase() === user.email.toLowerCase()
    : true

  return (
    <div className="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
      <h1 className="text-xl font-semibold tracking-tight text-slate-900">Business invitation</h1>

      {error && (
        <div className="mt-4">
          <Alert tone="danger">{error}</Alert>
        </div>
      )}

      {invitation && (
        <div className="mt-4 space-y-3 text-sm text-slate-600">
          <p>
            You have been invited to join <span className="font-medium text-slate-900">{invitation.business?.name}</span> as{' '}
            <span className="font-medium text-slate-900">{invitation.role}</span>.
          </p>
          {invitation.email && <p>Invitation address: {invitation.email}</p>}
          <p>Status: {invitation.status}</p>
        </div>
      )}

      <div className="mt-6 space-y-3">
        {!isAuthenticated && (
          <Alert tone="info" title="Sign in to continue">
            <Link to="/login" className="font-medium underline">
              Sign in
            </Link>{' '}
            with the invited email address, or{' '}
            <Link to="/register" className="font-medium underline">
              create an account
            </Link>
            , then open this link again.
          </Alert>
        )}

        {isAuthenticated && !emailMatches && (
          <Alert tone="warning" title="Different account">
            You are signed in as {user?.email}. This invitation was issued to another address. Sign out and sign in
            with the invited address.
          </Alert>
        )}

        <div className="flex flex-wrap gap-2">
          <Button onClick={accept} loading={accepting} disabled={!isAuthenticated || !emailMatches}>
            Accept invitation
          </Button>
          {isAuthenticated && (
            <Button variant="secondary" onClick={() => signOut()}>
              Sign out
            </Button>
          )}
          <Link
            to="/dashboard"
            className="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
          >
            Skip for now
          </Link>
        </div>
      </div>
    </div>
  )
}
