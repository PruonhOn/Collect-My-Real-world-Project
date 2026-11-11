import { useState } from 'react'
import { Link } from 'react-router-dom'
import AuthLayout from '../../components/auth/AuthLayout'
import { forgotPassword } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

export default function ForgotPasswordPage() {
  const { t } = useLanguage()
  const [email, setEmail] = useState('')
  const [error, setError] = useState('')
  const [sent, setSent] = useState(false)
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await forgotPassword({ email })
      setSent(true)
    } catch (err) {
      const errors = err.response?.data?.errors
      if (errors) {
        setError(Object.values(errors).flat().join(', '))
      } else {
        setError(err.response?.data?.message || t('error'))
      }
    } finally {
      setLoading(false)
    }
  }

  const inputClass =
    'w-full px-4 py-3 rounded-xl border border-gray-200 shadow-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'

  return (
    <AuthLayout title={t('forgotPassword')} subtitle={t('forgotPasswordSub')}>
      {sent ? (
        <div className="space-y-5">
          <div className="p-4 bg-emerald-50 border border-emerald-100 rounded-xl text-sm text-emerald-700 flex items-start gap-2">
            <svg className="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
            </svg>
            {t('resetLinkSent')}
          </div>
          <Link
            to="/login"
            className="block w-full py-3 text-center bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/25"
          >
            {t('backToLogin')}
          </Link>
        </div>
      ) : (
        <>
          {error && (
            <div className="mb-5 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('email')}</label>
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className={inputClass}
                placeholder="you@example.com"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60 transition-colors shadow-lg shadow-indigo-600/25"
            >
              {loading ? t('loading') : t('sendResetLink')}
            </button>
          </form>

          <p className="mt-6 text-center text-sm text-gray-500">
            <Link to="/login" className="text-indigo-600 font-semibold hover:text-indigo-700">
              {t('backToLogin')}
            </Link>
          </p>
        </>
      )}
    </AuthLayout>
  )
}
