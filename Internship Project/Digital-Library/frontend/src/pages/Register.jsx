import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { useLanguage } from '../context/LanguageContext'

export default function Register() {
  const { t } = useLanguage()
  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
  })
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)
  const { register } = useAuth()
  const { toast } = useToast()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const next = searchParams.get('next') || '/'

  const handleSubmit = async (e) => {
    e.preventDefault()
    setErrors({})
    setSubmitting(true)
    try {
      const data = await register(form)
      toast(t('registerSuccess'), 'success')
      if (!data.user?.email_verified_at) {
        toast(t('registerVerifyNote', { email: data.user?.email || form.email }), 'info')
      }
      navigate(next)
    } catch (err) {
      const resp = err.response?.data
      if (resp?.errors) setErrors(resp.errors)
      toast(resp?.message || t('registerFailed'), 'error')
    } finally {
      setSubmitting(false)
    }
  }

  const field = (name) => ({
    value: form[name],
    onChange: (e) => setForm((f) => ({ ...f, [name]: e.target.value })),
  })

  const err = (name) => errors[name] && (
    <p className="text-sm text-red-600 mt-1">{errors[name][0]}</p>
  )

  return (
    <div className="min-h-[70vh] flex items-center justify-center py-12">
      <div className="w-full max-w-md">
        <div className="bg-white rounded-2xl shadow-lg p-8">
          <h1 className="text-2xl font-bold text-gray-900 text-center">{t('registerTitle')}</h1>
          <p className="text-sm text-gray-500 text-center mt-1 mb-6">
            {t('registerSubtitle')}
          </p>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('name')}</label>
              <input type="text" required {...field('name')} className="input-field" placeholder={t('yourName')} />
              {err('name')}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
              <input type="email" required {...field('email')} className="input-field" placeholder="you@example.com" />
              {err('email')}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('phone')} — {t('phoneOptional')}</label>
              <input type="tel" {...field('phone')} className="input-field" placeholder="+855..." />
              {err('phone')}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('password')}</label>
              <input type="password" required {...field('password')} className="input-field" placeholder="••••••••" />
              {err('password')}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('confirmPassword')}</label>
              <input type="password" required {...field('password_confirmation')} className="input-field" placeholder="••••••••" />
              {err('password_confirmation')}
            </div>
            <button
              type="submit"
              disabled={submitting}
              className="w-full py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {submitting ? t('processing') : t('register')}
            </button>
          </form>
          <p className="text-sm text-gray-600 text-center mt-6">
            {t('haveAccount')}{' '}
            <Link to={`/login?next=${encodeURIComponent(next)}`} className="text-indigo-600 font-medium hover:underline">
              {t('login')}
            </Link>
          </p>
        </div>
      </div>
    </div>
  )
}
