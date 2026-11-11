import { useState } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { updateProfile, getPayments, getLibrary, avatarUrl, coverUrl } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'
import MembershipBadge from '../../components/membership/MembershipBadge'
import MembershipCard from '../../components/membership/MembershipCard'
import PremiumAvatar from '../../components/membership/PremiumAvatar'
import VipCtaButton from '../../components/membership/VipCtaButton'
import VipExpiryNote from '../../components/membership/VipExpiryNote'
import CrownIcon from '../../components/membership/CrownIcon'
import { Link, useNavigate } from 'react-router-dom'

const inputClass =
  'w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500'
const labelClass = 'block text-sm font-medium text-gray-700 mb-1'

const statusColor = (status) => {
  if (status === 'completed') return 'bg-emerald-100 text-emerald-700'
  if (status === 'pending') return 'bg-amber-100 text-amber-700'
  return 'bg-red-100 text-red-600'
}

/** Decorative light for the banner: one white bloom, one gold bloom. */
const BANNER_GLOW =
  'radial-gradient(circle at 15% 25%, rgba(255,255,255,0.22), transparent 45%), radial-gradient(circle at 85% 15%, rgba(251,191,36,0.30), transparent 40%)'

export default function ProfilePage() {
  const { user, setUser, logout, loading } = useAuth()
  const { t } = useLanguage()
  const { isVip } = useMembership()
  const navigate = useNavigate()
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

  const { data: paymentsData, isLoading: paymentsLoading, isError: paymentsError, refetch: refetchPayments } =
    useQuery({
      queryKey: ['my-payments'],
      refetchInterval: 15000,
      queryFn: () => getPayments({ per_page: 15 }),
      enabled: tab === 'purchases',
    })

  // The dashboard and the Books Read tab read the same /library payload: it
  // already carries the paid purchases ("owned"), the shared books and the
  // per-book progress, so one request feeds both surfaces.
  const {
    data: libraryData,
    isLoading: libraryLoading,
    isError: libraryError,
    refetch: refetchLibrary,
  } = useQuery({
    queryKey: ['my-library'],
    refetchInterval: 15000,
    queryFn: () => getLibrary(),
  })

  const updateMutation = useMutation({
    mutationFn: updateProfile,
    onSuccess: (res) => {
      setUser(res.data.user)
      setMessage(t('profileUpdated'))
      setTimeout(() => setMessage(''), 3000)
    },
    onError: (err) => {
      setError(err.response?.data?.message || t('error'))
      setTimeout(() => setError(''), 4000)
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
          t('error')
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

  const handleLogout = () => {
    logout()
    navigate('/login', { replace: true })
  }

  const displayAvatar = avatarPreview || avatarUrl(user)
  const createdAt = user?.created_at ? new Date(user.created_at).toLocaleDateString() : ''

  const purchases = paymentsData?.data?.data || []

  // De-duplicate by book id because a book can be reachable through more than
  // one library bucket (owned + shared, for instance).
  const libraryRes = libraryData?.data
  const ownedBooks = libraryRes?.owned || []
  const sharedBooks = libraryRes?.shared || []
  const vipBooks = libraryRes?.vip || []

  const libraryItems = (() => {
    const merged = [...ownedBooks, ...sharedBooks, ...vipBooks]
    const byBookId = new Map()
    for (const item of merged) {
      if (!byBookId.has(item.id)) byBookId.set(item.id, item)
    }
    return [...byBookId.values()]
  })()

  const readingItems = libraryItems.filter((item) => Number(item.progress || 0) > 0)
  const inProgressCount = readingItems.filter((item) => Number(item.progress || 0) < 100).length
  const completedCount = readingItems.filter((item) => Number(item.progress || 0) >= 100).length
  const averageProgress = readingItems.length
    ? Math.round(
        readingItems.reduce((sum, item) => sum + Number(item.progress || 0), 0) / readingItems.length
      )
    : 0

  const readingList = readingItems
    .slice()
    .sort((a, b) => Number(b.progress || 0) - Number(a.progress || 0))

  const stats = [
    { key: 'purchased', label: t('purchasedBooks'), value: ownedBooks.length, tone: 'indigo' },
    { key: 'reading', label: t('readingNow'), value: inProgressCount, tone: 'blue' },
    { key: 'progress', label: t('avgProgress'), value: `${averageProgress}%`, tone: 'amber', progress: averageProgress },
    { key: 'completed', label: t('booksCompleted'), value: completedCount, tone: 'emerald' },
  ]

  const tabClass = (active) => {
    const base = 'px-4 py-2 rounded-xl text-sm font-medium transition-colors'
    if (!active) return `${base} text-gray-600 hover:bg-gray-100`
    return isVip
      ? `${base} bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white shadow-[0_0_15px_rgba(212,175,55,0.3)] border border-amber-400/40 hover:shadow-[0_0_20px_rgba(212,175,55,0.5)]`
      : `${base} bg-indigo-600 text-white`
  }

  const tabs = [
    { key: 'profile', label: t('personalInfo') },
    { key: 'membership', label: t('membership') },
    { key: 'books', label: t('myBooksReadTab') },
    { key: 'purchases', label: t('myPurchasesTab') },
    { key: 'security', label: t('security') },
  ]

  if (loading) {
    return (
      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center text-gray-500" role="status">
        {t('loading')}
      </div>
    )
  }

  return (
    <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      {/* Profile header: navy-and-gold banner for VIP, clean blue for members. */}
      <header className="bg-white rounded-3xl shadow-sm overflow-hidden">
        <div
            className={`relative h-32 sm:h-40 ${
              isVip
                ? 'bg-gradient-to-br from-indigo-950 via-indigo-900 to-indigo-950 border-b border-amber-400/30 shadow-[0_0_25px_rgba(212,175,55,0.15)]'
                : 'bg-gradient-to-br from-blue-700 via-indigo-700 to-blue-900'
            }`}
            style={{ backgroundImage: BANNER_GLOW }}
          >
            <div
              className="absolute inset-0 bg-gradient-to-t from-black/25 to-transparent"
              aria-hidden="true"
            />
            {isVip ? (
              <CrownIcon className="absolute right-6 top-5 w-12 h-12 sm:w-16 sm:h-16 text-amber-200/40" />
          ) : (
            <div
              className="absolute right-6 top-5 w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-white/10 rotate-12"
              aria-hidden="true"
            />
          )}
            <div
              className={`absolute inset-x-0 bottom-0 h-0.5 ${
                isVip
                  ? 'bg-gradient-to-r from-transparent via-amber-400 to-transparent shadow-[0_0_15px_rgba(212,175,55,0.4)]'
                  : 'bg-gradient-to-r from-transparent via-white/60 to-transparent'
              }`}
              aria-hidden="true"
            />
        </div>

        <div className="px-5 sm:px-8 pb-6">
          <div className="flex flex-col gap-4 -mt-12 sm:-mt-14 sm:flex-row sm:items-end sm:justify-between">
            <div className="flex flex-col sm:flex-row sm:items-end gap-4">
              <PremiumAvatar
                src={displayAvatar}
                name={user?.name}
                size="w-24 h-24 sm:w-28 sm:h-28"
                tone="soft"
                initialsClassName="text-4xl sm:text-5xl font-bold"
                className="ring-4 ring-white shadow-lg bg-white"
              />
              <div className="sm:pb-1">
                <h1 className="text-2xl font-bold text-gray-900">{user?.name}</h1>
                <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500 mt-1">
                  <span className="inline-flex items-center gap-1.5 min-w-0">
                    <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span className="truncate">{user?.email}</span>
                  </span>
                  {createdAt && (
                    <span className="text-gray-400">
                      {t('memberSince')}: {createdAt}
                    </span>
                  )}
                </p>
                <div className="flex flex-wrap items-center gap-2 mt-2.5">
                  <MembershipBadge size="md" full />
                  {isVip ? (
                    <>
                      <VipExpiryNote className="text-xs text-gray-500" />
                      <Link
                        to="/vip"
                        className="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] border border-amber-400/40 shadow-[0_0_10px_rgba(212,175,55,0.2)] hover:shadow-[0_0_15px_rgba(212,175,55,0.4)] hover:from-[#d4a737] hover:to-[#c49a30] transition-all duration-200"
                      >
                        {t('vipRenew')}
                      </Link>
                    </>
                  ) : (
                    <VipCtaButton size="sm" />
                  )}
                </div>
              </div>
            </div>

            <div className="flex flex-wrap items-center gap-2 sm:pb-1 shrink-0">
              <button
                type="button"
                onClick={() => setTab('profile')}
                className={`inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl transition-colors ${
                  isVip
                  ? 'bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white shadow-[0_0_15px_rgba(212,175,55,0.3)] border border-amber-400/40 hover:shadow-[0_0_20px_rgba(212,175,55,0.5)] hover:from-[#d4a737] hover:to-[#c49a30] transition-all duration-200'
                  : 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm'
                }`}
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                {t('editProfile')}
              </button>
              <button
                type="button"
                onClick={handleLogout}
                className="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 border border-red-200 bg-red-50/60 rounded-xl transition-colors hover:bg-red-50"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                {t('logout')}
              </button>
            </div>
          </div>
        </div>
      </header>

      {/* Learning dashboard: every figure comes from GET /library plus the
          membership fields the API already returns — nothing is derived or
          invented client-side. */}
      <section
        aria-labelledby="stats-heading"
        className="bg-white rounded-3xl shadow-sm p-5 sm:p-6"
      >
        <div className="flex flex-wrap items-center justify-between gap-3 mb-4">
          <h2 id="stats-heading" className="text-lg font-bold text-gray-900">
            {t('myStats')}
          </h2>
          <Link
            to="/my-library"
            className="text-sm font-medium text-indigo-600 hover:text-indigo-700"
          >
            {t('myLibrary')} &rarr;
          </Link>
        </div>

        {libraryLoading ? (
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4" role="status">
            {[0, 1, 2, 3].map((i) => (
              <div key={i} className="rounded-2xl border border-gray-100 bg-gray-50 h-28 animate-pulse" />
            ))}
          </div>
        ) : libraryError ? (
          <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3">
            <p className="text-sm text-red-600">{t('error')}</p>
            <button
              type="button"
              onClick={() => refetchLibrary()}
              className="text-sm font-semibold text-red-600 hover:text-red-700"
            >
              {t('retry')}
            </button>
          </div>
        ) : (
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            {stats.map((stat) => (
              <StatCard
                key={stat.key}
                label={stat.label}
                value={stat.value}
                tone={stat.tone}
                progress={stat.progress}
              />
            ))}
          </div>
        )}

        <div className="mt-4">
          {isVip ? (
          <div className="flex flex-wrap items-center gap-x-3 gap-y-2 rounded-2xl border border-amber-400/40 bg-gradient-to-br from-[#BF953F]/10 via-[#FCF6BA]/10 to-[#B38728]/10 px-4 py-3 backdrop-blur-md shadow-[0_0_15px_rgba(212,175,55,0.15)]">
            <span className="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white shrink-0 shadow-[0_0_10px_rgba(212,175,55,0.3)]">
              <CrownIcon className="w-4 h-4" />
            </span>
            <div className="min-w-0">
              <p className="text-[10px] font-bold uppercase tracking-wide text-amber-300/90">
                {t('membershipStatus')}
              </p>
              <p className="text-sm font-bold text-white leading-tight">{t('vipMember')}</p>
            </div>
            <VipExpiryNote className="text-xs text-amber-100/90" />
            <Link
              to="/vip"
              className="ml-auto inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-white bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] border border-amber-400/40 shadow-[0_0_10px_rgba(212,175,55,0.2)] hover:shadow-[0_0_15px_rgba(212,175,55,0.4)] hover:from-[#d4a737] hover:to-[#c49a30] transition-all duration-200"
            >
              {t('vipRenew')}
            </Link>
          </div>
          ) : (
            <div className="flex flex-wrap items-center gap-3 rounded-2xl border border-blue-200/70 bg-gradient-to-r from-blue-50/70 to-white px-4 py-3">
              <div className="min-w-0">
                <p className="text-[10px] font-bold uppercase tracking-wide text-blue-600/80">
                  {t('membershipStatus')}
                </p>
                <p className="text-sm font-bold text-gray-900 leading-tight">{t('normalMember')}</p>
              </div>
              <MembershipBadge size="sm" />
              <div className="ml-auto">
                <VipCtaButton size="sm" />
              </div>
            </div>
          )}
        </div>
      </section>

      <div className="bg-white rounded-3xl shadow-sm overflow-hidden">
        <div className="flex flex-wrap items-center gap-2 px-5 sm:px-6 pt-5 border-b border-gray-100">
          <div
            className="flex flex-wrap gap-2"
            role="tablist"
            aria-label={t('accountInfo')}
          >
            {tabs.map((item) => (
              <button
                key={item.key}
                role="tab"
                type="button"
                aria-selected={tab === item.key}
                className={`${tabClass(tab === item.key)} mb-3`}
                onClick={() => setTab(item.key)}
              >
                {item.label}
              </button>
            ))}
          </div>
          <div className="flex flex-wrap gap-2 ml-auto mb-3">
            <Link
              to="/my-library"
              className="px-4 py-2 rounded-xl text-sm font-medium text-indigo-600 hover:bg-indigo-50"
            >
              {t('myLibrary')}
            </Link>
            <Link
              to="/messages"
              className="px-4 py-2 rounded-xl text-sm font-medium text-indigo-600 hover:bg-indigo-50"
            >
              {t('myMessages')}
            </Link>
          </div>
        </div>

        <div className="px-5 sm:px-6 py-6" role="tabpanel">
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
          {tab === 'membership' && <MembershipCard />}
          {tab === 'books' && (
            <BooksReadTab t={t} items={readingList} isLoading={libraryLoading} isError={libraryError} onRetry={refetchLibrary} />
          )}
          {tab === 'purchases' && (
            <PurchasesTab
              t={t}
              purchases={purchases}
              isLoading={paymentsLoading}
              isError={paymentsError}
              onRetry={refetchPayments}
            />
          )}
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
  )
}

const STAT_TONES = {
  indigo: { icon: 'bg-indigo-50 text-indigo-600', bar: 'bg-indigo-600' },
  blue: { icon: 'bg-blue-50 text-blue-600', bar: 'bg-blue-600' },
  amber: { icon: 'bg-amber-50 text-amber-600', bar: 'bg-amber-500' },
  emerald: { icon: 'bg-emerald-50 text-emerald-600', bar: 'bg-emerald-500' },
}

function StatCard({ label, value, tone = 'indigo', progress }) {
  const palette = STAT_TONES[tone] || STAT_TONES.indigo

  return (
    <div className="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition-shadow hover:shadow-sm">
      <span
        className={`inline-flex items-center justify-center w-9 h-9 rounded-xl ${palette.icon}`}
        aria-hidden="true"
      >
        <StatIcon tone={tone} />
      </span>
      <div className="mt-3 text-2xl font-bold text-gray-900">{value}</div>
      <div className="text-xs font-medium text-gray-500 mt-0.5">{label}</div>
      {typeof progress === 'number' && (
        <div
          className="mt-2 bg-gray-200 rounded-full h-1.5"
          role="progressbar"
          aria-valuenow={Math.min(progress, 100)}
          aria-valuemin={0}
          aria-valuemax={100}
          aria-label={label}
        >
          <div
            className={`h-1.5 rounded-full ${palette.bar}`}
            style={{ width: `${Math.min(progress, 100)}%` }}
          />
        </div>
      )}
    </div>
  )
}

function StatIcon({ tone }) {
  const common = {
    className: 'w-4 h-4',
    fill: 'none',
    stroke: 'currentColor',
    viewBox: '0 0 24 24',
  }

  if (tone === 'blue') {
    return (
      <svg {...common}>
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
      </svg>
    )
  }

  if (tone === 'amber') {
    return (
      <svg {...common}>
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
    )
  }

  if (tone === 'emerald') {
    return (
      <svg {...common}>
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    )
  }

  return (
    <svg {...common}>
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
    </svg>
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
      <div className="mb-5">
        <h2 className="text-lg font-bold text-gray-900">{t('accountInfo')}</h2>
        <p className="text-sm text-gray-500 mt-0.5">{t('personalInfo')}</p>
      </div>

      {message && (
        <div
          role="status"
          className="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm"
        >
          {message}
        </div>
      )}
      {error && (
        <div role="alert" className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="flex flex-wrap items-center gap-4">
          <PremiumAvatar
            src={avatarSrc}
            name={user?.name}
            size="w-20 h-20"
            tone="soft"
            initialsClassName="text-3xl font-bold"
            className="ring-2 ring-gray-200 shadow-sm"
          />
          <div className="flex flex-col gap-2">
            <label className="cursor-pointer inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:bg-gray-50">
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 9l2-2h4l2-2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <circle cx="12" cy="13" r="3" />
              </svg>
              {t('changePhoto')}
              <input
                type="file"
                accept="image/*"
                className="hidden"
                onChange={handleAvatarChange}
                aria-label={t('changePhoto')}
              />
            </label>
            {avatar && (
              <button
                type="button"
                onClick={handleAvatarRemove}
                className="text-xs text-red-500 hover:text-red-600 text-left"
              >
                {t('removePhoto')}
              </button>
            )}
          </div>
        </div>

        <div className="grid sm:grid-cols-2 gap-4">
          <div>
            <label className={labelClass} htmlFor="profile-name">
              {t('name')}
            </label>
            <input
              id="profile-name"
              type="text"
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
              className={inputClass}
              required
            />
          </div>
          <div>
            <label className={labelClass} htmlFor="profile-phone">
              {t('phone')}
            </label>
            <input
              id="profile-phone"
              type="tel"
              value={form.phone}
              onChange={(e) => setForm({ ...form, phone: e.target.value })}
              className={inputClass}
              placeholder="+855 ..."
            />
          </div>
        </div>
        <div>
          <label className={labelClass} htmlFor="profile-email">
            {t('email')}
          </label>
          <input
            id="profile-email"
            type="email"
            value={user?.email || ''}
            disabled
            className={`${inputClass} bg-gray-50 text-gray-500 cursor-not-allowed`}
          />
        </div>

        <div className="flex flex-wrap items-center gap-3 pt-1">
          <button
            type="submit"
            disabled={isPending}
            className="px-8 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60 transition-colors"
          >
            {isPending ? t('loading') : t('save')}
          </button>
          <div className="flex flex-wrap items-center gap-2 text-xs text-gray-500">
            {createdAt && (
              <span className="px-2.5 py-1 rounded-full bg-gray-50 border border-gray-100">
                {t('memberSince')}: {createdAt}
              </span>
            )}
            <span className="px-2.5 py-1 rounded-full bg-gray-50 border border-gray-100 capitalize">
              {t('role')}: {user?.role === 'admin' ? t('roleAdmin') : t('roleMember')}
            </span>
            <MembershipBadge size="xs" />
          </div>
        </div>
      </form>
    </div>
  )
}

function BooksReadTab({ t, items, isLoading, isError, onRetry }) {
  if (isLoading) {
    return (
      <div className="text-center py-16 text-gray-500" role="status">
        {t('loading')}
      </div>
    )
  }

  if (isError) {
    return (
      <div className="text-center py-16">
        <p className="text-red-600 mb-4">{t('error')}</p>
        <button type="button" onClick={() => onRetry()} className="text-indigo-600 font-medium">
          {t('retry')}
        </button>
      </div>
    )
  }

  if (items.length === 0) {
    return (
      <div className="text-center py-16">
        <p className="text-gray-500 mb-4">{t('noBooksReadYet')}</p>
        <Link to="/books" className="text-indigo-600 font-medium">
          {t('allBooks')}
        </Link>
      </div>
    )
  }

  const completed = items.filter((item) => Number(item.progress || 0) >= 100).length

  return (
    <div>
      <div className="flex flex-wrap items-end justify-between gap-3 mb-5">
        <div>
          <h2 className="text-lg font-bold text-gray-900">{t('booksRead')}</h2>
          <p className="text-sm text-gray-500 mt-0.5">{t('booksReadSub')}</p>
        </div>
        <div className="flex items-center gap-2 text-xs">
          <span className="px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 font-medium">
            {items.length - completed} {t('booksInProgress')}
          </span>
          <span className="px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 font-medium">
            {completed} {t('booksCompleted')}
          </span>
        </div>
      </div>

      <div className="space-y-3">
        {items.map((item) => {
          const progress = Math.min(Math.round(Number(item.progress || 0)), 100)
          return (
            <Link
              key={item.id}
              to={`/reader/${item.id}`}
              className="flex items-center gap-4 bg-gray-50 rounded-xl p-3 hover:bg-gray-100 transition-colors"
            >
              {coverUrl(item) ? (
                <img
                  src={coverUrl(item)}
                  alt=""
                  className="w-12 h-16 object-contain bg-white p-1 rounded-lg shrink-0"
                />
              ) : (
                <div className="w-12 h-16 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 shrink-0" />
              )}
              <div className="flex-1 min-w-0">
                <div className="font-semibold text-gray-900 truncate">{item.title}</div>
                {item.author && <div className="text-xs text-gray-500 mt-0.5 truncate">{item.author}</div>}
                <div className="mt-2 flex items-center gap-2">
                  <div className="bg-gray-200 rounded-full h-1.5 flex-1">
                    <div
                      className={`h-1.5 rounded-full ${progress >= 100 ? 'bg-emerald-500' : 'bg-indigo-600'}`}
                      style={{ width: `${progress}%` }}
                    />
                  </div>
                  <span className="text-[10px] text-gray-500 w-8 text-right">{progress}%</span>
                </div>
              </div>
              <span
                className={`shrink-0 text-xs font-medium px-2.5 py-1 rounded-full ${
                  progress >= 100 ? 'bg-emerald-50 text-emerald-700' : 'bg-indigo-50 text-indigo-700'
                }`}
              >
                {progress >= 100 ? t('booksCompleted') : t('resumeReading')}
              </span>
            </Link>
          )
        })}
      </div>
    </div>
  )
}

function PurchasesTab({ t, purchases, isLoading, isError, onRetry }) {
  if (isLoading) {
    return (
      <div className="text-center py-16 text-gray-500" role="status">
        {t('loading')}
      </div>
    )
  }

  if (isError) {
    return (
      <div className="text-center py-16">
        <p className="text-red-600 mb-4">{t('error')}</p>
        <button type="button" onClick={() => onRetry()} className="text-indigo-600 font-medium">
          {t('retry')}
        </button>
      </div>
    )
  }

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
    <div>
      <div className="mb-5">
        <h2 className="text-lg font-bold text-gray-900">{t('myPurchasesTab')}</h2>
        <p className="text-sm text-gray-500 mt-0.5">{t('purchaseStatus')}</p>
      </div>
      <div className="space-y-4">
        {purchases.map((payment) => (
          <div key={payment.id} className="bg-gray-50 rounded-xl p-4 flex items-center gap-4">
            <div className="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center">
              <span className="text-indigo-600 font-bold">${payment.amount}</span>
            </div>
            <div className="flex-1 min-w-0">
              <div className="font-semibold text-gray-900 capitalize">{payment.method}</div>
              <div className="text-sm text-gray-500 mt-1">
                {new Date(payment.created_at).toLocaleDateString()}
              </div>
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
      <div className="mb-5">
        <h2 className="text-lg font-bold text-gray-900">{t('security')}</h2>
        <p className="text-sm text-gray-500 mt-0.5">{t('changePassword')}</p>
      </div>

      {pwMessage && (
        <div role="status" className="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">
          {pwMessage}
        </div>
      )}
      {pwError && (
        <div role="alert" className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">
          {pwError}
        </div>
      )}
      <form onSubmit={handlePasswordSubmit} className="space-y-4 max-w-md">
        <div>
          <label className={labelClass} htmlFor="pw-current">
            {t('currentPassword')}
          </label>
          <input
            id="pw-current"
            type="password"
            autoComplete="current-password"
            value={pwForm.current_password}
            onChange={(e) => setPwForm({ ...pwForm, current_password: e.target.value })}
            className={inputClass}
            required
          />
        </div>
        <div>
          <label className={labelClass} htmlFor="pw-new">
            {t('newPassword')}
          </label>
          <input
            id="pw-new"
            type="password"
            autoComplete="new-password"
            value={pwForm.password}
            onChange={(e) => setPwForm({ ...pwForm, password: e.target.value })}
            className={inputClass}
            required
          />
        </div>
        <div>
          <label className={labelClass} htmlFor="pw-confirm">
            {t('confirmPassword')}
          </label>
          <input
            id="pw-confirm"
            type="password"
            autoComplete="new-password"
            value={pwForm.password_confirmation}
            onChange={(e) => setPwForm({ ...pwForm, password_confirmation: e.target.value })}
            className={inputClass}
            required
          />
        </div>
        <button
          type="submit"
          disabled={passwordMutation.isPending}
          className="w-full sm:w-auto px-8 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60 transition-colors"
        >
          {passwordMutation.isPending ? t('loading') : t('changePassword')}
        </button>
      </form>
    </div>
  )
}
