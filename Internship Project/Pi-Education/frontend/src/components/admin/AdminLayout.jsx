import { NavLink, Outlet } from 'react-router-dom'
import { useI18n } from '../../i18n'

const links = [
  {
    to: '/admin',
    key: 'admin.dashboard',
    end: true,
    icon: (
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <rect x="3" y="3" width="7" height="7" rx="1" />
        <rect x="14" y="3" width="7" height="7" rx="1" />
        <rect x="3" y="14" width="7" height="7" rx="1" />
        <rect x="14" y="14" width="7" height="7" rx="1" />
      </svg>
    ),
  },
  {
    to: '/admin/books',
    key: 'admin.books',
    icon: (
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M4 19.5A2.5 2.5 0 016.5 17H20" />
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" />
      </svg>
    ),
  },
  {
    to: '/admin/users',
    key: 'admin.users',
    icon: (
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
      </svg>
    ),
  },
  {
    to: '/admin/categories',
    key: 'admin.categories',
    icon: (
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z" />
      </svg>
    ),
  },
  {
    to: '/admin/authors',
    key: 'admin.authors',
    icon: (
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M12 20h9M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4z" />
      </svg>
    ),
  },
  {
    to: '/admin/plans',
    key: 'admin.plans',
    icon: (
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M6 3h12l4 6-10 13L2 9z" />
        <path d="M11 3 8 9l4 13 4-13-3-6M2 9h20" />
      </svg>
    ),
  },
]

export default function AdminLayout() {
  const { t } = useI18n()

  return (
    <div className="flex min-h-screen bg-slate-100">
      <aside className="sticky top-0 flex h-screen w-64 shrink-0 flex-col bg-slate-950 text-slate-300">
        <div className="flex items-center gap-2.5 px-5 py-5">
          <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-sm font-extrabold text-white">
            Pi
          </span>
          <div>
            <p className="text-sm font-extrabold tracking-tight text-white">
              Pi<span className="text-indigo-400">-Admin</span>
            </p>
            <p className="text-[11px] text-slate-500">{t('admin.title')}</p>
          </div>
        </div>

        <nav className="mx-3 mt-2 flex-1 space-y-1">
          {links.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              end={link.end}
              className={({ isActive }) =>
                `flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition ${
                  isActive
                    ? 'bg-indigo-600 text-white'
                    : 'text-slate-400 hover:bg-white/5 hover:text-white'
                }`
              }
            >
              <span className="flex items-center">{link.icon}</span>
              {t(link.key)}
            </NavLink>
          ))}
        </nav>

        <div className="border-t border-white/5 p-3">
          <NavLink
            to="/"
            className="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium text-slate-500 transition hover:bg-white/5 hover:text-white"
          >
            <span>←</span>
            {t('admin.backToSite')}
          </NavLink>
        </div>
      </aside>

      <main className="flex-1 px-6 py-8 lg:px-10">
        <Outlet />
      </main>
    </div>
  )
}