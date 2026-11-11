import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import AuthLayout from '../../components/auth/AuthLayout'
import { resetPassword } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

export default function ResetPasswordPage() {
  const { t } = useLanguage()
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token') || ''
  const [form, setForm] = useState({
    email: searchParams.get('email') || '',
    password: '',
    password_confirmation: '',
  })
  const [error, setError] = useState('')
  const [done, setDone] = useState(false)
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')

    if (form.password !== form.password_confirmation) {
      setError(t('passwordsDoNotMatch'))
      return
    }

    setLoading(true)
    try {
      await resetPassword({ token, ...form })
      setDone(true)
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

  if (!token) {
    return (
      <AuthLayout title={t('resetPassword')} subtitle={t('resetLinkRequired')}>
        <div className="space-y-5">
          <div className="p-4 bg-red-50 border border-red-100 rounded-xl text-sm text-red-600">
            {t('invalidResetLink')}
          </div>
          <Link
            to="/forgot-password"
            className="block w-full py-3 text-center bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-600/25"
          >
            {t('requestNewLink')}
          </Link>
        </div>
      </AuthLayout>
    )
  }

  return (
    <AuthLayout title={t('resetPassword')} subtitle={t('resetPasswordSub')}>
      {done ? (
        <div className="space-y-5">
          <div className="p-4 bg-emerald-50 border border-emerald-100 rounded-xl text-sm text-emerald-700 flex items-start gap-2">
            <svg className="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
            </svg>
            {t('passwordResetSuccess')}
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
                value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
                className={inputClass}
                placeholder="you@example.com"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('newPassword')}</label>
              <input
                type="password"
                required
                minLength={8}
                value={form.password}
                onChange={(e) => setForm({ ...form, password: e.target.value })}
                className={inputClass}
                placeholder="••••••••"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('confirmPassword')}</label>
              <input
                type="password"
                required
                minLength={8}
                value={form.password_confirmation}
                onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
                className={inputClass}
                placeholder="••••••••"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60 transition-colors shadow-lg shadow-indigo-600/25"
            >
              {loading ? t('loading') : t('resetPassword')}
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
