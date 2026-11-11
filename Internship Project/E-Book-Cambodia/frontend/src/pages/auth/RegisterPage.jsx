import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import AuthLayout from '../../components/auth/AuthLayout'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'

export default function RegisterPage() {
  const { register } = useAuth()
  const { t } = useLanguage()
  const navigate = useNavigate()
  const [form, setForm] = useState({ name: '', email: '', phone: '', password: '', password_confirmation: '' })
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')

    if (form.password !== form.password_confirmation) {
      setError('Passwords do not match')
      return
    }

    setLoading(true)
    try {
      await register(form)
      navigate('/')
    } catch (err) {
      const errors = err.response?.data?.errors
      if (errors) {
        setError(Object.values(errors).flat().join(', '))
      } else {
        setError(err.response?.data?.message || 'Registration failed')
      }
    } finally {
      setLoading(false)
    }
  }

  const inputClass =
    'w-full px-4 py-3 rounded-xl border border-gray-200 shadow-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'

  return (
    <AuthLayout title={t('createAccount')} subtitle={`${t('welcome')} ${t('appName')}`}>
      {error && (
        <div className="mb-5 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('name')}</label>
          <input
            type="text"
            required
            value={form.name}
            onChange={(e) => setForm({ ...form, name: e.target.value })}
            className={inputClass}
            placeholder="Your name"
          />
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
            <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('phone')}</label>
            <input
              type="tel"
              value={form.phone}
              onChange={(e) => setForm({ ...form, phone: e.target.value })}
              className={inputClass}
              placeholder="+855 12 345 678"
            />
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('password')}</label>
            <input
              type="password"
              required
              minLength={6}
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
              minLength={6}
              value={form.password_confirmation}
              onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
              className={inputClass}
              placeholder="••••••••"
            />
          </div>
        </div>

        <button
          type="submit"
          disabled={loading}
          className="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60 transition-colors shadow-lg shadow-indigo-600/25"
        >
          {loading ? t('loading') : t('createAccount')}
        </button>
      </form>

      <p className="mt-6 text-center text-sm text-gray-500">
        {t('haveAccount')}{' '}
        <Link to="/login" className="text-indigo-600 font-semibold hover:text-indigo-700">
          {t('login')}
        </Link>
      </p>
    </AuthLayout>
  )
}