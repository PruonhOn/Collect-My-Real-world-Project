import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { useI18n } from '../i18n'

const quickActions = [
  {
    to: '/my-books',
    titleKey: 'common.myBooks',
    desc: 'View your purchased and free books.',
    icon: (
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M4 19.5A2.5 2.5 0 016.5 17H20" />
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" />
      </svg>
    ),
  },
  {
    to: '/orders',
    titleKey: 'common.myOrders',
    desc: 'Track your orders and payments.',
    icon: (
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
        <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />
      </svg>
    ),
  },
  {
    to: '/wishlist',
    titleKey: 'wishlist.title',
    desc: 'Books you saved to read later.',
    icon: (
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
      </svg>
    ),
  },
  {
    to: '/profile',
    titleKey: 'common.profile',
    desc: 'Manage your account settings.',
    icon: (
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
        <circle cx="12" cy="7" r="4" />
      </svg>
    ),
  },
]

export default function Dashboard() {
  const { t } = useI18n()
  const { user } = useAuth()
  const initial = (user?.name || '?').trim().charAt(0).toUpperCase()

  return (
    <div className="page">
      <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-600 to-violet-700 px-6 py-9 text-white shadow-xl shadow-indigo-600/15 sm:px-10">
        <div className="pointer-events-none absolute -top-16 -right-10 h-56 w-56 rounded-full bg-white/10 blur-2xl" />
        <div className="pointer-events-none absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-white/10 blur-2xl" />
        <div className="relative flex flex-wrap items-center gap-5">
          <span className="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/20 text-2xl font-extrabold ring-2 ring-white/30">
            {initial}
          </span>
          <div>
            <p className="text-sm font-medium text-indigo-200">Welcome back,</p>
            <h1 className="text-2xl font-extrabold tracking-tight sm:text-3xl">{user?.name}</h1>
            <p className="mt-1 text-sm text-indigo-100">{t('common.dashboard')}</p>
          </div>
          <Link to="/books" className="ml-auto hidden rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-indigo-700 shadow-sm transition hover:bg-indigo-50 sm:inline-flex">
            {t('hero.cta')}
          </Link>
        </div>
      </div>

      <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {quickActions.map((action) => (
          <Link
            key={action.to}
            to={action.to}
            className="card card-hover group flex items-start gap-4 p-6"
          >
            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white shadow-lg shadow-indigo-500/20 transition group-hover:scale-105">
              {action.icon}
            </div>
            <div>
              <h3 className="font-bold text-slate-900 group-hover:text-indigo-700">{t(action.titleKey)}</h3>
              <p className="mt-1 text-sm leading-relaxed text-slate-500">{action.desc}</p>
            </div>
          </Link>
        ))}
      </div>

      <div className="card mt-8 p-6">
        <h2 className="font-bold text-slate-900">{t('common.profile')}</h2>
        <dl className="mt-4 grid gap-x-8 gap-y-4 text-sm sm:grid-cols-2">
          <div className="flex justify-between gap-4 border-b border-slate-100 pb-3 sm:block sm:border-0 sm:pb-0">
            <dt className="text-slate-500">Name</dt>
            <dd className="font-semibold text-slate-900 sm:mt-1">{user?.name}</dd>
          </div>
          <div className="flex justify-between gap-4 border-b border-slate-100 pb-3 sm:block sm:border-0 sm:pb-0">
            <dt className="text-slate-500">Email</dt>
            <dd className="font-semibold text-slate-900 sm:mt-1">{user?.email}</dd>
          </div>
          <div className="flex justify-between gap-4 border-b border-slate-100 pb-3 sm:block sm:border-0 sm:pb-0">
            <dt className="text-slate-500">Roles</dt>
            <dd className="font-semibold text-slate-900 sm:mt-1">{user?.roles?.join(', ')}</dd>
          </div>
          <div className="flex justify-between gap-4 border-b border-slate-100 pb-3 sm:block sm:border-0 sm:pb-0">
            <dt className="text-slate-500">Status</dt>
            <dd className="font-semibold text-slate-900 sm:mt-1">{user?.status}</dd>
          </div>
        </dl>
        <Link to="/profile" className="btn-primary mt-6">
          {t('common.profile')}
        </Link>
      </div>
    </div>
  )
}