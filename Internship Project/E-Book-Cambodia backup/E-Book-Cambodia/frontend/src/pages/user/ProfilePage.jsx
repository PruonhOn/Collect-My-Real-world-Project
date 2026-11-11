import { useState } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { updateProfile, getPayments, avatarUrl } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'
import { Link } from 'react-router-dom'

const inputClass =
  'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500'
const labelClass = 'block text-sm font-medium text-gray-700 mb-1'

const statusColor = (status) => {
  if (status === 'completed') return 'bg-emerald-100 text-emerald-700'
  if (status === 'pending') return 'bg-amber-100 text-amber-700'
  return 'bg-red-100 text-red-600'
}

export default function ProfilePage() {
  const { user, setUser } = useAuth()
  const { t } = useLanguage()
  const [tab, setTab] = useState('profile')

  const [form, setForm] = useState({
    name: user?.name || '',
    phone: user?.phone || '',
  })
  const [message, setMessage] = useState('')
  const [error, setError] = useState('')
  const [avatar, setAvatar] = useState(null)
  const [avatarPreview, setAvatarPreview] = useState(null)

  const [pwForm, setPwForm] = useState({
    current_password: '',
    password: '',
    password_confirmation: '',
  })
  const [pwMessage, setPwMessage] = useState('')
  const [pwError, setPwError] = useState('')

  const { data: paymentsData } = useQuery({
    queryKey: ['my-payments'],
    queryFn: () => getPayments({ per_page: 15 }),
    enabled: tab === 'purchases',
  })

  const updateMutation = useMutation({
    mutationFn: updateProfile,
    onSuccess: (res) => {
      setUser(res.data.user)
      setMessage(t('profileUpdated'))
      setTimeout(() => setMessage(''), 3000)
    },
    onError: (err) => {
      setError(err.response?.data?.message || 'Update failed')
      setTimeout(() => setError(''), 3000)
    },
  })

  const passwordMutation = useMutation({
    mutationFn: () =>
      updateProfile({
        current_password: pwForm.current_password,
        password: pwForm.password,
        password_confirmation: pwForm.password_confirmation,
      }),
    onSuccess: () => {
      setPwForm({ current_password: '', password: '', password_confirmation: '' })
      setPwMessage(t('passwordChanged'))
      setTimeout(() => setPwMessage(''), 3000)
    },
    onError: (err) => {
      const errors = err.response?.data?.errors
      setPwError(
        errors?.current_password?.[0] ||
          errors?.password?.[0] ||
          err.response?.data?.message ||
          'Update failed'
      )
      setTimeout(() => setPwError(''), 4000)
    },
  })

  const handleSubmit = (e) => {
    e.preventDefault()

    if (avatar) {
      const data = new FormData()
      data.append('name', form.name)
      if (form.phone) data.append('phone', form.phone)
      data.append('avatar', avatar)
      updateMutation.mutate(data)
      return
    }

    updateMutation.mutate({ name: form.name, phone: form.phone || '' })
  }

  const handleAvatarChange = (e) => {
    const file = e.target.files?.[0]
    if (!file) return
    setAvatar(file)
    setAvatarPreview(URL.createObjectURL(file))
  }

  const handleAvatarRemove = () => {
    setAvatar(null)
    setAvatarPreview(null)
  }

  const handlePasswordSubmit = (e) => {
    e.preventDefault()
    passwordMutation.mutate()
  }

  const displayAvatar = avatarPreview || avatarUrl(user)
  const createdAt = user?.created_at ? new Date(user.created_at).toLocaleDateString() : ''

  const purchases = paymentsData?.data?.data || []

  const tabClass = (active) =>
    `px-4 py-2 rounded-xl text-sm font-medium transition-colors ${
      active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'
    }`

  const tabs = [
    { key: 'profile', label: t('personalInfo') },
    { key: 'purchases', label: t('myPurchasesTab') },
    { key: 'security', label: t('security') },
  ]

  return (
    <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div className="h-32 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500" />
        <div className="px-6 md:px-8 pb-8">
          <div className="flex flex-col sm:flex-row sm:items-end gap-4 -mt-14">
            {displayAvatar ? (
              <img
                src={displayAvatar}
                alt={user?.name}
                className="w-28 h-28 rounded-full object-cover border-4 border-white shadow-md bg-white"
              />
            ) : (
              <div className="w-28 h-28 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-5xl font-bold border-4 border-white shadow-md">
                {user?.name?.charAt(0)?.toUpperCase()}
              </div>
            )}
            <div className="sm:pb-1">
              <h1 className="text-2xl font-bold text-gray-900">{user?.name}</h1>
              <p className="text-gray-500">{user?.email}</p>
              <div className="flex flex-wrap gap-2 mt-2">
                <span className="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full capitalize">
                  {user?.role || 'reader'}
                </span>
                {user?.is_vip && (
                  <span className="text-xs bg-amber-400 text-amber-900 px-2 py-1 rounded-full font-bold">
                    VIP
                  </span>
                )}
              </div>
              <div className="mt-3">
                <Link
                  to="/vip"
                  className="inline-flex items-center gap-1 text-sm font-medium text-amber-600 hover:text-amber-700"
                >
                  VIP
                  {user?.is_vip && user?.vip_until
                    ? ` · ${t('vipExpires')}: ${new Date(user.vip_until).toLocaleDateString()}`
                    : ` · ${t('becomeVip')}`}
                </Link>
              </div>
            </div>
          </div>

          <div className="flex flex-wrap gap-2 mt-6 border-b border-gray-100 pb-4">
            {tabs.map((item) => (
              <button key={item.key} className={tabClass(tab === item.key)} onClick={() => setTab(item.key)}>
                {item.label}
              </button>
            ))}
            <Link to="/my-library" className="px-4 py-2 rounded-xl text-sm font-medium text-indigo-600 hover:bg-indigo-50">
              {t('myLibrary')}
            </Link>
          </div>

          <div className="mt-6">
            {tab === 'profile' && (
              <ProfileTab
                t={t}
                form={form}
                setForm={setForm}
                user={user}
                message={message}
                error={error}
                avatar={avatar}
                avatarPreview={avatarPreview}
                handleAvatarChange={handleAvatarChange}
                handleAvatarRemove={handleAvatarRemove}
                handleSubmit={handleSubmit}
                isPending={updateMutation.isPending}
                createdAt={createdAt}
                labelClass={labelClass}
                inputClass={inputClass}
              />
            )}
            {tab === 'purchases' && <PurchasesTab t={t} purchases={purchases} />}
            {tab === 'security' && (
              <SecurityTab
                t={t}
                pwForm={pwForm}
                setPwForm={setPwForm}
                pwMessage={pwMessage}
                pwError={pwError}
                handlePasswordSubmit={handlePasswordSubmit}
                passwordMutation={passwordMutation}
                labelClass={labelClass}
                inputClass={inputClass}
              />
            )}
          </div>
        </div>
      </div>
    </div>
  )
}

function ProfileTab({
  t,
  form,
  setForm,
  user,
  message,
  error,
  avatar,
  avatarPreview,
  handleAvatarChange,
  handleAvatarRemove,
  handleSubmit,
  isPending,
  createdAt,
  labelClass,
  inputClass,
}) {
  const avatarSrc = avatarPreview || avatarUrl(user)

  return (
    <div>
      {message && (
        <div className="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{message}</div>
      )}
      {error && <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="flex items-center gap-4">
          {avatarSrc ? (
            <img
              src={avatarSrc}
              alt={user?.name}
              className="w-20 h-20 rounded-full object-cover border-2 border-gray-200 bg-white shadow-sm"
            />
          ) : (
            <div className="w-20 h-20 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-3xl font-bold border-2 border-gray-200">
              {user?.name?.charAt(0)?.toUpperCase()}
            </div>
          )}
          <div className="flex flex-col gap-2">
            <label className="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50">
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 9l2-2h4l2-2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <circle cx="12" cy="13" r="3" />
              </svg>
              {t('changePhoto')}
              <input type="file" accept="image/*" className="hidden" onChange={handleAvatarChange} />
            </label>
            {avatar && (
              <button type="button" onClick={handleAvatarRemove} className="text-xs text-red-500 hover:text-red-600 text-left">
                {t('removePhoto')}
              </button>
            )}
          </div>
        </div>

        <div className="grid sm:grid-cols-2 gap-4">
          <div>
            <label className={labelClass}>{t('name')}</label>
            <input
              type="text"
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>{t('phone')}</label>
            <input
              type="tel"
              value={form.phone}
              onChange={(e) => setForm({ ...form, phone: e.target.value })}
              className={inputClass}
              placeholder="+855 ..."
            />
          </div>
        </div>
        <div>
          <label className={labelClass}>{t('email')}</label>
          <input
            type="email"
            value={user?.email || ''}
            disabled
            className="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-gray-500"
          />
        </div>
        {createdAt && (
          <p className="text-xs text-gray-400">
            {t('memberSince')}: {createdAt}
          </p>
        )}
        <button
          type="submit"
          disabled={isPending}
          className="w-full sm:w-auto px-8 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
        >
          {isPending ? t('loading') : t('save')}
        </button>
      </form>
    </div>
  )
}

function PurchasesTab({ t, purchases }) {
  if (purchases.length === 0) {
    return (
      <div className="text-center py-16">
        <p className="text-gray-500 mb-4">{t('noPurchases')}</p>
        <Link to="/books" className="text-indigo-600 font-medium">
          {t('allBooks')}
        </Link>
      </div>
    )
  }

  return (
    <div className="space-y-4">
      {purchases.map((payment) => (
        <div key={payment.id} className="bg-gray-50 rounded-xl p-4 flex items-center gap-4">
          <div className="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center">
            <span className="text-indigo-600 font-bold">${payment.amount}</span>
          </div>
          <div className="flex-1 min-w-0">
            <div className="font-semibold text-gray-900 capitalize">{payment.method}</div>
            <div className="text-sm text-gray-500 mt-1">{new Date(payment.created_at).toLocaleDateString()}</div>
          </div>
          <span
            className={`inline-block text-xs font-medium px-2 py-1 rounded-full capitalize ${statusColor(
              payment.status
            )}`}
          >
            {payment.status}
          </span>
        </div>
      ))}
    </div>
  )
}

function SecurityTab({
  t,
  pwForm,
  setPwForm,
  pwMessage,
  pwError,
  handlePasswordSubmit,
  passwordMutation,
  labelClass,
  inputClass,
}) {
  return (
    <div>
      {pwMessage && (
        <div className="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">{pwMessage}</div>
      )}
      {pwError && <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{pwError}</div>}
      <form onSubmit={handlePasswordSubmit} className="space-y-4 max-w-md">
        <div>
          <label className={labelClass}>{t('currentPassword')}</label>
          <input
            type="password"
            value={pwForm.current_password}
            onChange={(e) => setPwForm({ ...pwForm, current_password: e.target.value })}
            className={inputClass}
          />
        </div>
        <div>
          <label className={labelClass}>{t('newPassword')}</label>
          <input
            type="password"
            value={pwForm.password}
            onChange={(e) => setPwForm({ ...pwForm, password: e.target.value })}
            className={inputClass}
          />
        </div>
        <div>
          <label className={labelClass}>{t('confirmPassword')}</label>
          <input
            type="password"
            value={pwForm.password_confirmation}
            onChange={(e) => setPwForm({ ...pwForm, password_confirmation: e.target.value })}
            className={inputClass}
          />
        </div>
        <button
          type="submit"
          disabled={passwordMutation.isPending}
          className="w-full sm:w-auto px-8 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
        >
          {passwordMutation.isPending ? t('loading') : t('changePassword')}
        </button>
      </form>
    </div>
  )
}