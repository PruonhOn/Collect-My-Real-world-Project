import { useState } from 'react'
import client from '../api/client'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { useLanguage } from '../context/LanguageContext'

export default function VerifyEmailBanner() {
  const { t } = useLanguage()
  const { user, isAuthenticated } = useAuth()
  const { toast } = useToast()
  const [sending, setSending] = useState(false)

  if (!isAuthenticated || !user || user.email_verified_at) return null

  const resend = async () => {
    setSending(true)
    try {
      await client.post('/email/verification-notification')
      toast(t('verificationSent'), 'success')
    } catch {
      toast(t('errSendVerification'), 'error')
    } finally {
      setSending(false)
    }
  }

  return (
    <div className="bg-amber-50 border-b border-amber-200" data-testid="verify-banner">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between gap-3">
        <p className="text-sm text-amber-800">
          <span className="font-semibold">{t('verifyEmailTitle')}.</span>{' '}
          {t('verifyEmailText', { email: user.email || '' })}
        </p>
        <button
          onClick={resend}
          disabled={sending}
          className="shrink-0 text-sm font-semibold text-amber-900 hover:underline disabled:opacity-50"
        >
          {sending ? '...' : t('resendVerification')}
        </button>
      </div>
    </div>
  )
}