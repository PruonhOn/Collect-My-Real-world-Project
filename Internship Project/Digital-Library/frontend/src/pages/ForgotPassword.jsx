import { useState } from 'react'
import { Link } from 'react-router-dom'
import client from '../api/client'
import { useToast } from '../context/ToastContext'
import { useLanguage } from '../context/LanguageContext'

export default function ForgotPassword() {
  const { t } = useLanguage()
  const { toast } = useToast()
  const [email, setEmail] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [sent, setSent] = useState(false)
  const [error, setError] = useState('')

  const handleSubmit = async (e) => {
    e.preventDefault()
    setSubmitting(true)
    setError('')
    try {
      await client.post('/forgot-password', { email })
      setSent(true)
      toast(t('resetLinkSent'), 'success')
    } catch (err) {
      setError(err.response?.data?.message || t('errSendResetLink'))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="min-h-[70vh] flex items-center justify-center py-12">
      <div className="w-full max-w-md">
        <div className="bg-white rounded-2xl shadow-lg p-8">
          <h1 className="text-2xl font-bold text-gray-900 text-center">{t('forgotPasswordTitle')}</h1>
          <p className="text-sm text-gray-500 text-center mt-1 mb-6">{t('forgotPasswordSubtitle')}</p>

          {sent ? (
            <div className="text-center">
              <p className="text-emerald-600 font-medium mb-4">{t('resetLinkSent')}</p>
              <Link to="/login" className="text-indigo-600 font-medium hover:underline">
                {t('backToLogin')}
              </Link>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="input-field"
                  placeholder="you@example.com"
                />
                {error && <p className="text-sm text-red-600 mt-1">{error}</p>}
              </div>
              <button
                type="submit"
                disabled={submitting}
                className="w-full py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {submitting ? t('processing') : t('sendResetLink')}
              </button>
            </form>
          )}

          <p className="text-sm text-gray-600 text-center mt-6">
            <Link to="/login" className="text-indigo-600 font-medium hover:underline">
              {t('backToLogin')}
            </Link>
          </p>
        </div>
      </div>
    </div>
  )
}