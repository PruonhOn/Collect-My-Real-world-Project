import { useState } from 'react'
import { useAuth } from '../context/AuthContext'
import api from '../api/client'
import { useI18n, LANGUAGES } from '../i18n'

export default function Profile() {
  const { t, setLocale } = useI18n()
  const { user, refreshUser } = useAuth()
  const [form, setForm] = useState({
    name: user?.name || '',
    phone: user?.phone || '',
    locale: user?.locale || 'km',
  })
  const [pwForm, setPwForm] = useState({ current_password: '', new_password: '', new_password_confirmation: '' })
  const [msg, setMsg] = useState('')
  const [pwMsg, setPwMsg] = useState('')
  const [loading, setLoading] = useState(false)
  const [pwLoading, setPwLoading] = useState(false)

  const handleUpdate = async (e) => {
    e.preventDefault()
    setLoading(true)
    setMsg('')
    try {
      await api.put('/profile', form)
      await refreshUser()
      setLocale(form.locale)
      setMsg('Profile updated successfully.')
    } catch (err) {
      const errors = err.response?.data?.errors
      setMsg(errors ? Object.values(errors).flat().join(' ') : err.response?.data?.message || t('common.error'))
    } finally {
      setLoading(false)
    }
  }

  const handlePwChange = async (e) => {
    e.preventDefault()
    setPwLoading(true)
    setPwMsg('')
    try {
      await api.post('/profile/password', pwForm)
      setPwMsg('Password changed. Please login again with your new password.')
      setPwForm({ current_password: '', new_password: '', new_password_confirmation: '' })
    } catch (err) {
      setPwMsg(err.response?.data?.message || t('common.error'))
    } finally {
      setPwLoading(false)
    }
  }

  return (
    <div className="page max-w-2xl">
      <div className="max-w-2xl">
        <p className="section-eyebrow">Account</p>
        <h1 className="section-title">{t('common.profile')}</h1>
        <p className="section-subtitle">Manage your personal details, language and password.</p>
      </div>

      <div className="mt-8 space-y-8">
        <div className="card p-6 sm:p-8">
          <h2 className="text-lg font-bold text-slate-900">{t('common.profile')}</h2>
          {msg && (
            <div className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
              {msg}
            </div>
          )}
          <form onSubmit={handleUpdate} className="mt-5 space-y-5">
            <div>
              <label className="label">Name</label>
              <input type="text" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} required className="input" />
            </div>
            <div>
              <label className="label">Phone</label>
              <input type="text" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className="input" />
            </div>
            <div>
              <label className="label">{t('common.selectLanguage')}</label>
              <select value={form.locale} onChange={(e) => setForm({ ...form, locale: e.target.value })} className="input">
                {LANGUAGES.map((l) => (
                  <option key={l.code} value={l.code}>{l.native}</option>
                ))}
              </select>
            </div>
            <button type="submit" disabled={loading} className="btn-primary">
              {loading ? t('common.loading') : t('common.save')}
            </button>
          </form>
        </div>

        <div className="card p-6 sm:p-8">
          <h2 className="text-lg font-bold text-slate-900">Change Password</h2>
          {pwMsg && (
            <div className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
              {pwMsg}
            </div>
          )}
          <form onSubmit={handlePwChange} className="mt-5 space-y-5">
            <div>
              <label className="label">Current Password</label>
              <input
                type="password"
                value={pwForm.current_password}
                onChange={(e) => setPwForm({ ...pwForm, current_password: e.target.value })}
                required
                autoComplete="current-password"
                className="input"
              />
            </div>
            <div className="grid gap-5 sm:grid-cols-2">
              <div>
                <label className="label">New Password</label>
                <input
                  type="password"
                  value={pwForm.new_password}
                  onChange={(e) => setPwForm({ ...pwForm, new_password: e.target.value })}
                  required
                  minLength={8}
                  autoComplete="new-password"
                  className="input"
                />
              </div>
              <div>
                <label className="label">Confirm New Password</label>
                <input
                  type="password"
                  value={pwForm.new_password_confirmation}
                  onChange={(e) => setPwForm({ ...pwForm, new_password_confirmation: e.target.value })}
                  required
                  autoComplete="new-password"
                  className="input"
                />
              </div>
            </div>
            <button type="submit" disabled={pwLoading} className="btn-primary">
              {pwLoading ? t('common.loading') : 'Change Password'}
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}