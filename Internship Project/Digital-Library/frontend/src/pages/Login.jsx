import { useEffect, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { useLanguage } from '../context/LanguageContext'

export default function Login() {
  const { t } = useLanguage()
  const [form, setForm] = useState({ email: '', password: '' })
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)
  const { login } = useAuth()
  const { toast } = useToast()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const next = searchParams.get('next') || '/'

  useEffect(() => {
    if (searchParams.get('verified') === '1') {
      toast(t('emailVerifiedBanner'), 'success')
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchParams])

  const handleSubmit = async (e) => {
    e.preventDefault()
    setErrors({})
    setSubmitting(true)
    try {
      await login(form)
      toast(t('loginSuccess'))
      navigate(next)
    } catch (err) {
      const resp = err.response?.data
      if (resp?.errors) setErrors(resp.errors)
      toast(resp?.message || t('loginFailed'), 'error')
    } finally {
      setSubmitting(false)
    }
  }

  const field = (name) => ({
    value: form[name],
    onChange: (e) => setForm((f) => ({ ...f, [name]: e.target.value })),
  })

  return (
    <div className="min-h-[70vh] flex items-center justify-center py-12">
      <div className="w-full max-w-md">
        <div className="bg-white rounded-2xl shadow-lg p-8">
          <h1 className="text-2xl font-bold text-gray-900 text-center">{t('loginTitle')}</h1>
          <p className="text-sm text-gray-500 text-center mt-1 mb-6">
            {t('loginSubtitle')}
          </p>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
              <input
                type="email"
                required
                {...field('email')}
                className="input-field"
                placeholder="you@example.com"
              />
              {errors.email && <p className="text-sm text-red-600 mt-1">{errors.email[0]}</p>}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('password')}</label>
              <input
                type="password"
                required
                {...field('password')}
                className="input-field"
                placeholder="••••••••"
              />
              {errors.password && <p className="text-sm text-red-600 mt-1">{errors.password[0]}</p>}
              <div className="text-right mt-1">
                <Link to="/forgot-password" className="text-xs text-indigo-600 hover:underline">
                  {t('forgotPasswordLink')}
                </Link>
              </div>
            </div>
            <button
              type="submit"
              disabled={submitting}
              className="w-full py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {submitting ? t('processing') : t('loginTitle')}
            </button>
          </form>
          <p className="text-sm text-gray-600 text-center mt-6">
            {t('noAccount')}{' '}
            <Link to={`/register?next=${encodeURIComponent(next)}`} className="text-indigo-600 font-medium hover:underline">
              {t('register')}
            </Link>
          </p>
        </div>
        <div className="text-center mt-4 text-xs text-gray-400">
          {t('demoAccounts')}
        </div>
      </div>
    </div>
  )
}
