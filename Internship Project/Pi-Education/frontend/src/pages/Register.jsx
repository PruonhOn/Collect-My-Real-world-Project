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

export default function Register() {
  const { t } = useI18n()
  const { register } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' })
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    setError('')
    try {
      await register(form)
      navigate('/dashboard')
    } catch (err) {
      const errors = err.response?.data?.errors
      if (errors) {
        const first = Object.values(errors)[0]
        setError(Array.isArray(first) ? first[0] : String(first))
      } else {
        setError(err.response?.data?.message || t('common.error'))
      }
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

          <h1 className="text-3xl font-extrabold tracking-tight text-slate-900">{t('common.register')}</h1>
          <p className="mt-2 text-slate-500">Create your free account and start reading.</p>

          <div className="card mt-8 p-8">
            {error && (
              <div className="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label className="label">Name</label>
              <input type="text" name="name" value={form.name} onChange={handleChange} required className="input" />
            </div>
            <div>
              <label className="label">Email</label>
              <input type="email" name="email" value={form.email} onChange={handleChange} required autoComplete="email" className="input" />
            </div>
            <div className="grid gap-5 sm:grid-cols-2">
              <div>
                <label className="label">Password</label>
                <input
                  type="password"
                  name="password"
                  value={form.password}
                  onChange={handleChange}
                  required
                  minLength={8}
                  autoComplete="new-password"
                  className="input"
                />
              </div>
              <div>
                <label className="label">Confirm Password</label>
                <input
                  type="password"
                  name="password_confirmation"
                  value={form.password_confirmation}
                  onChange={handleChange}
                  required
                  autoComplete="new-password"
                  className="input"
                />
              </div>
            </div>
            <button type="submit" disabled={loading} className="btn-primary w-full py-3">
              {loading ? t('common.loading') : t('common.register')}
            </button>
            </form>
          </div>

          <p className="mt-8 text-center text-sm text-slate-500">
            Already have an account?{' '}
            <Link to="/login" className="font-semibold text-indigo-600 hover:text-indigo-700">
              {t('common.login')}
            </Link>
          </p>
        </div>
      </div>
    </div>
  )
}