import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { useI18n } from '../i18n'

function BrandPanel() {
  return (
    <div className="relative hidden flex-col justify-between overflow-hidden p-12 lg:flex">
      <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800" />
      <div className="pointer-events-none absolute -top-16 -right-16 h-64 w-64 rounded-full bg-white/10 blur-2xl" />
      <div className="pointer-events-none absolute -bottom-20 -left-10 h-72 w-72 rounded-full bg-white/10 blur-2xl" />

      <div className="relative flex items-center gap-2.5">
        <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-lg font-extrabold text-indigo-700 shadow-lg">
          Pi
        </span>
        <span className="text-xl font-extrabold tracking-tight text-white">
          Pi<span className="text-indigo-200">-Education</span>
        </span>
      </div>

      <div className="relative">
        <h2 className="text-3xl font-extrabold leading-tight tracking-tight text-white">
          Your multilingual <span className="text-indigo-200">digital library</span>.
        </h2>
        <p className="mt-4 max-w-sm text-base leading-relaxed text-indigo-100">
          Read free and premium education books in Khmer, English, Chinese and French — anywhere you go.
        </p>
        <div className="mt-8 space-y-3">
          {['Free books — no account required', 'VIP access to the full library', 'Reading progress saved automatically'].map((item) => (
            <div key={item} className="flex items-center gap-3 text-sm font-medium text-white/90">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-white/20 text-white">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M20 6L9 17l-5-5" />
                </svg>
              </span>
              {item}
            </div>
          ))}
        </div>
      </div>

      <p className="relative text-sm text-indigo-200">© {new Date().getFullYear()} Pi-Education</p>
    </div>
  )
}

export default function Login() {
  const { t } = useI18n()
  const { login } = useAuth()
  const navigate = useNavigate()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    setError('')
    try {
      const user = await login(email, password)
      navigate(user?.roles?.includes('admin') ? '/admin' : '/dashboard')
    } catch (err) {
      setError(err.response?.data?.message || t('common.error'))
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="mx-auto grid min-h-[calc(100vh-4rem)] max-w-7xl lg:grid-cols-2">
      <BrandPanel />

      <div className="flex flex-col justify-center px-4 py-16 sm:px-10">
        <div className="mx-auto w-full max-w-md">
          <div className="mb-8 lg:hidden">
            <div className="flex items-center gap-2.5">
              <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-extrabold text-white">
                Pi
              </span>
              <span className="text-xl font-extrabold tracking-tight text-slate-900">
                Pi<span className="text-indigo-600">-Education</span>
              </span>
            </div>
          </div>

          <h1 className="text-3xl font-extrabold tracking-tight text-slate-900">{t('common.login')}</h1>
          <p className="mt-2 text-slate-500">Welcome back! Enter your details to continue.</p>

          <div className="card mt-8 p-8">
            {error && (
              <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-5">
              <div>
                <label className="label">Email</label>
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  autoComplete="email"
                  className="input"
                />
              </div>
              <div>
                <label className="label">Password</label>
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  autoComplete="current-password"
                  className="input"
                />
              </div>
              <button type="submit" disabled={loading} className="btn-primary w-full py-3">
                {loading ? t('common.loading') : t('common.login')}
              </button>
            </form>
          </div>

          <p className="mt-8 text-center text-sm text-slate-500">
            New to Pi-Education?{' '}
            <Link to="/register" className="font-semibold text-indigo-600 hover:text-indigo-700">
              {t('common.register')}
            </Link>
          </p>
        </div>
      </div>
    </div>
  )
}