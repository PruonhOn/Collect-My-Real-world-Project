import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import client from '../api/client'
import { useToast } from '../context/ToastContext'
import { useLanguage } from '../context/LanguageContext'

export default function ResetPassword() {
  const { t } = useLanguage()
  const { toast } = useToast()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const token = searchParams.get('token')
  const email = searchParams.get('email') || ''

  const [form, setForm] = useState({ password: '', password_confirmation: '' })
  const [errors, setErrors] = useState({})
  const [submitting, setSubmitting] = useState(false)

  if (!token) {
    return (
      <div className="min-h-[70vh] flex items-center justify-center py-12">
        <div className="w-full max-w-md text-center">
          <p className="text-gray-600 mb-4">{t('invalidResetLink')}</p>
          <Link to="/forgot-password" className="text-indigo-600 font-medium hover:underline">
            {t('forgotPasswordTitle')}
          </Link>
        </div>
      </div>
    )
  }

  const field = (name) => ({
    value: form[name],
    onChange: (e) => setForm((f) => ({ ...f, [name]: e.target.value })),
  })

  const handleSubmit = async (e) => {
    e.preventDefault()
    setErrors({})
    setSubmitting(true)
    try {
      await client.post('/reset-password', {
        token,
        email,
        password: form.password,
        password_confirmation: form.password_confirmation,
      })
      toast(t('passwordResetSuccess'), 'success')
      navigate('/login')
    } catch (err) {
      const resp = err.response?.data
      if (resp?.errors) setErrors(resp.errors)
      toast(resp?.message || t('errResetPassword'), 'error')
    } finally {
      setSubmitting(false)
    }
  }

  const err = (name) => errors[name] && (
    <p className="text-sm text-red-600 mt-1">{errors[name][0]}</p>
  )

  return (
    <div className="min-h-[70vh] flex items-center justify-center py-12">
      <div className="w-full max-w-md">
        <div className="bg-white rounded-2xl shadow-lg p-8">
          <h1 className="text-2xl font-bold text-gray-900 text-center">{t('resetPasswordTitle')}</h1>
          <p className="text-sm text-gray-500 text-center mt-1 mb-6">
            {t('resetPasswordSubtitle', { email })}
          </p>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('newPassword')}</label>
              <input
                type="password"
                required
                {...field('password')}
                className="input-field"
                placeholder="••••••••"
              />
              {err('password')}
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('confirmNewPassword')}</label>
              <input
                type="password"
                required
                {...field('password_confirmation')}
                className="input-field"
                placeholder="••••••••"
              />
            </div>
            <button
              type="submit"
              disabled={submitting}
              className="w-full py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {submitting ? t('processing') : t('resetPasswordBtn')}
            </button>
          </form>
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