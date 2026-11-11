import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import AuthLayout from '../../components/auth/AuthLayout'
import { socialLinks } from '../../components/Footer'
import { useAuth } from '../../context/AuthContext'
import { useAuthor } from '../../context/AuthorContext'
import { useLanguage } from '../../context/LanguageContext'

export default function LoginPage() {
  const { login } = useAuth()
  const authorContext = useAuthor()
  const { t } = useLanguage()
  const navigate = useNavigate()
  const [form, setForm] = useState({ email: '', password: '' })
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const user = await login(form)
      navigate(user.role === 'admin' ? '/admin' : '/')
    } catch (err) {
      if (err.response?.status !== 401) {
        setError(err.response?.data?.message || 'Login failed')
        return
      }
      try {
        await authorContext.login(form)
        navigate('/author', { replace: true })
      } catch (authorErr) {
        if (authorErr.response?.status === 403) {
          setError(authorErr.response?.data?.message || 'Your account has been suspended.')
        } else {
          setError(err.response?.data?.message || 'Login failed')
        }
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <AuthLayout title={t('login')} subtitle={`${t('welcome')} ${t('appName')}`} showSocial={false}>
      {error && (
        <div className="mb-5 p-3 bg-red-50 text-red-600 rounded-xl text-sm flex items-start gap-2">
          <svg className="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {error}
        </div>
      )}

      <div className="flex items-start gap-4">
        <form onSubmit={handleSubmit} className="flex-1 min-w-0 space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('email')}</label>
          <div className="relative">
            <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" d="M3 8l7.9 5.26a2 2 0 002.2 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
            </span>
            <input
              type="email"
              required
              value={form.email}
              onChange={(e) => setForm({ ...form, email: e.target.value })}
              className="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 shadow-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              placeholder="you@example.com"
            />
          </div>
        </div>

        <div>
          <div className="flex items-center justify-between mb-1.5">
            <label className="block text-sm font-medium text-gray-700">{t('password')}</label>
            <Link to="/forgot-password" className="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
              {t('forgotPassword')}
            </Link>
          </div>
          <div className="relative">
            <span className="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </span>
            <input
              type={showPassword ? 'text' : 'password'}
              required
              value={form.password}
              onChange={(e) => setForm({ ...form, password: e.target.value })}
              className="w-full pl-11 pr-11 py-3 rounded-xl border border-gray-200 shadow-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              placeholder="••••••••"
            />
            <button
              type="button"
              onClick={() => setShowPassword((s) => !s)}
              aria-label={showPassword ? 'Hide password' : 'Show password'}
              className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
            >
              {showPassword ? (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7 1.075-3.387 3.725-5.943 7.668-6.975M9.879 9.878a3 3 0 104.243 4.243M9.88 9.88l4.242 4.242M9.88 9.88L3 3m8.63 8.63l8.87 8.87" />
                </svg>
              ) : (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm-3 7c-4.478 0-8.268-2.943-9.543-7 1.275-4.057 5.065-7 9.543-7s8.268 2.943 9.543 7c-1.275 4.057-5.065 7-9.543 7z" />
                </svg>
              )}
            </button>
          </div>
        </div>

        <button
          type="submit"
          disabled={loading}
          className="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60 transition-colors shadow-lg shadow-indigo-600/25"
        >
          {loading ? t('loading') : t('login')}
        </button>
        </form>
      </div>

      <p className="mt-6 text-center text-sm text-gray-500">
        {t('noAccount')}{' '}
        <Link to="/register" className="text-indigo-600 font-semibold hover:text-indigo-700">
          {t('register')}
        </Link>
      </p>

      <div className="mt-4 flex flex-wrap items-center justify-end gap-2">
        {socialLinks.map((link) => (
          <a
            key={link.label}
            href={link.href}
            target={link.href.startsWith('mailto') ? undefined : '_blank'}
            rel={link.href.startsWith('mailto') ? undefined : 'noreferrer'}
            aria-label={link.label}
            title={link.label}
            className="w-9 h-9 rounded-full border border-gray-200 bg-white text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 hover:border-indigo-200 shadow-sm flex items-center justify-center transition-colors"
          >
            {link.icon}
          </a>
        ))}
      </div>

      <div className="mt-6 space-y-2">
        <div className="p-3 bg-gray-50 border border-gray-100 rounded-xl text-xs text-gray-500 text-center">
          Demo admin: admin@example.com / password
        </div>
        <div className="p-3 bg-emerald-50/60 border border-emerald-100 rounded-xl text-xs text-emerald-700 text-center">
          Demo author: author@example.com / password
        </div>
      </div>
    </AuthLayout>
  )
}