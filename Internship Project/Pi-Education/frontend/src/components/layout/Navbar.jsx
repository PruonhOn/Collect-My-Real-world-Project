import { useEffect, useRef, useState } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import NotificationBell from './NotificationBell'
import { useAuth } from '../../context/AuthContext'
import { useI18n, LANGUAGES } from '../../i18n'

const navLinks = [
  { to: '/', key: 'common.home' },
  { to: '/books', key: 'common.books' },
  { to: '/categories', key: 'common.categories' },
  { to: '/authors', key: 'common.authors' },
  { to: '/pricing', key: 'common.vip' },
]

function LanguageSelector() {
  const { locale, setLocale } = useI18n()
  return (
    <select
      value={locale}
      onChange={(e) => setLocale(e.target.value)}
      aria-label="Language"
      className="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 focus:outline-none"
    >
      {LANGUAGES.map((lang) => (
        <option key={lang.code} value={lang.code}>
          {lang.native}
        </option>
      ))}
    </select>
  )
}

function Avatar({ name }) {
  const initial = (name || '?').trim().charAt(0).toUpperCase()
  return (
    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold text-white ring-2 ring-white">
      {initial}
    </span>
  )
}

function NavButton({ to, onClick, children, variant = 'ghost' }) {
  const base =
    'inline-flex items-center justify-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40'
  const styles = {
    ghost: 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
    primary: 'bg-indigo-600 text-white hover:bg-indigo-700',
    secondary: 'bg-white text-slate-700 shadow-sm ring-1 ring-slate-300 hover:bg-slate-50',
  }
  return (
    <Link to={to} onClick={onClick} className={`${base} ${styles[variant]}`}>
      {children}
    </Link>
  )
}

export default function Navbar() {
  const { t } = useI18n()
  const { user, isAuthenticated, logout } = useAuth()
  const [open, setOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)
  const navigate = useNavigate()
  const headerRef = useRef(null)

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  useEffect(() => {
    if (!open) return
    const onPointerDown = (e) => {
      if (headerRef.current && !headerRef.current.contains(e.target)) setOpen(false)
    }
    document.addEventListener('pointerdown', onPointerDown)
    return () => document.removeEventListener('pointerdown', onPointerDown)
  }, [open])

  const handleLogout = async () => {
    await logout()
    navigate('/')
  }

  return (
    <header
      ref={headerRef}
      className={`sticky top-0 z-50 bg-white/90 backdrop-blur-md transition-shadow duration-300 ${
        scrolled ? 'shadow-lg shadow-slate-900/5' : 'border-b border-slate-200'
      }`}
    >
      <nav className="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
        <Link to="/" className="flex items-center gap-2.5">
          <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-base font-extrabold text-white">
            Pi
          </span>
          <span className="text-lg font-extrabold tracking-tight text-slate-900">
            Pi<span className="text-indigo-600">-Education</span>
          </span>
        </Link>

        <div className="hidden items-center gap-1 md:flex">
          {navLinks.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              className={({ isActive }) =>
                `rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                  isActive
                    ? 'bg-indigo-50 text-indigo-700'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                }`
              }
            >
              {t(link.key)}
            </NavLink>
          ))}
        </div>

        <div className="hidden items-center gap-2.5 md:flex">
          <LanguageSelector />
          {isAuthenticated ? (
            <>
              <NotificationBell />
              <Link
                to="/orders"
                className="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
              >
                {t('common.myOrders')}
              </Link>
              <Link
                to="/wishlist"
                className="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
              >
                {t('wishlist.title')}
              </Link>
              <Link
                to="/dashboard"
                className="flex items-center gap-2 rounded-xl border border-slate-200 py-1 pr-3 pl-1 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50"
              >
                <Avatar name={user?.name} />
                <span className="max-w-24 truncate">{user?.name}</span>
              </Link>
              <button
                onClick={handleLogout}
                className="rounded-xl px-3.5 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
              >
                {t('common.logout')}
              </button>
            </>
          ) : (
            <>
              <NavButton to="/login">{t('common.login')}</NavButton>
              <NavButton to="/register" variant="primary">
                {t('common.register')}
              </NavButton>
            </>
          )}
        </div>

        <button
          className="rounded-xl p-2 text-slate-700 transition hover:bg-slate-100 md:hidden"
          onClick={() => setOpen(!open)}
          aria-label="Toggle menu"
        >
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
            {open ? (
              <path d="M6 6l12 12M6 18L18 6" />
            ) : (
              <path d="M3 6h18M3 12h18M3 18h18" />
            )}
          </svg>
        </button>
      </nav>

      {open && (
        <div className="animate-[slide-down_.25s_ease-out] border-t border-slate-200 bg-white px-4 pb-5 md:hidden">
          <div className="flex flex-col gap-1 py-3">
            {navLinks.map((link) => (
              <NavLink
                key={link.to}
                to={link.to}
                onClick={() => setOpen(false)}
                className={({ isActive }) =>
                  `rounded-xl px-3 py-2.5 text-sm font-medium ${
                    isActive ? 'bg-indigo-50 text-indigo-700' : 'text-slate-700 hover:bg-slate-100'
                  }`
                }
              >
                {t(link.key)}
              </NavLink>
            ))}
          </div>
          <div className="flex flex-col gap-2 border-t border-slate-100 pt-3">
            <LanguageSelector />
            {isAuthenticated ? (
              <>
                <NotificationBell />
                <Link
                  to="/orders"
                  onClick={() => setOpen(false)}
                  className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-300 hover:bg-slate-50"
                >
                  {t('common.myOrders')}
                </Link>
                <Link
                  to="/wishlist"
                  onClick={() => setOpen(false)}
                  className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-300 hover:bg-slate-50"
                >
                  {t('wishlist.title')}
                </Link>
                <Link
                  to="/dashboard"
                  onClick={() => setOpen(false)}
                  className="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-300 hover:bg-slate-50"
                >
                  {user?.name}
                </Link>
                <button
                  onClick={handleLogout}
                  className="inline-flex w-full items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
                >
                  {t('common.logout')}
                </button>
              </>
            ) : (
              <>
                <Link
                  to="/login"
                  onClick={() => setOpen(false)}
                  className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-300 hover:bg-slate-50"
                >
                  {t('common.login')}
                </Link>
                <Link
                  to="/register"
                  onClick={() => setOpen(false)}
                  className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                  {t('common.register')}
                </Link>
              </>
            )}
          </div>
        </div>
      )}
    </header>
  )
}