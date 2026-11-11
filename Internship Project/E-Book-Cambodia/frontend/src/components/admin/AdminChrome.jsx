import { useState } from 'react'
import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { adminGetMessagesUnreadCount, avatarUrl } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'
import { useTheme } from '../../context/ThemeContext'
import { useAdminNotifications } from '../../services/notifications'
import { useMembership } from '../../hooks/useMembership'
import BroadcastBanner from '../BroadcastBanner'
import Footer from '../Footer'
import 'flag-icons/css/flag-icons.min.css'

const SIDEBAR_ANIMATIONS = `
  .dropdown-enter { animation: navDrop .16s ease-out; }
  @keyframes navDrop { from { opacity: 0; transform: translateY(-6px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
  @media (prefers-reduced-motion: reduce) { .dropdown-enter { animation: none; } }
`

const LANGUAGES = [
  { code: 'km', label: 'ខ្មែរ', flag: 'fi-kh' },
  { code: 'en', label: 'English', flag: 'fi-gb' },
  { code: 'zh', label: '中文', flag: 'fi-cn' },
  { code: 'fr', label: 'Français', flag: 'fi-fr' },
]

/**
 * The admin portal chrome: a fixed left sidebar with every admin tool (the
 * old top navbar + "More" dropdown), plus a slim top bar holding the mobile
 * menu button and the account controls.
 *
 * Desktop: sidebar always visible, content offset with `lg:pl-64`.
 * Mobile: sidebar slides in as an overlay drawer and closes on navigation.
 */
export default function AdminChrome({ viewport = false, children }) {
  const { t, locale, changeLocale } = useLanguage()
  const { theme, toggleTheme } = useTheme()
  const { user, logout } = useAuth()
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [langOpen, setLangOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const location = useLocation()
  const navigate = useNavigate()
  const { unreadCount } = useAdminNotifications()
  const { isVip } = useMembership()

  // Close the mobile drawer whenever the route changes (render-phase adjust).
  const [lastPath, setLastPath] = useState(location.pathname)
  if (lastPath !== location.pathname) {
    setLastPath(location.pathname)
    if (sidebarOpen) setSidebarOpen(false)
    if (langOpen) setLangOpen(false)
    if (userMenuOpen) setUserMenuOpen(false)
  }

  const { data: messagesUnreadData } = useQuery({
    queryKey: ['admin-messages-unread-count'],
    queryFn: adminGetMessagesUnreadCount,
    refetchInterval: 5000,
    refetchOnWindowFocus: true,
  })

  const messagesUnread = messagesUnreadData?.data?.unread_count || 0

  const navItems = [
    { to: '/admin', label: t('adminDashboard'), end: true, icon: '📊' },
    { to: '/admin/books', label: t('adminBooks'), icon: '📚' },
    { to: '/admin/book-reviews', label: t('bookReview'), icon: '🗒️' },
    { to: '/admin/categories', label: t('adminCategories'), icon: '🗂️' },
    { to: '/admin/users', label: t('adminUsers'), icon: '👥' },
    { to: '/admin/authors', label: t('adminAuthors'), icon: '✍️' },
    { to: '/admin/events', label: t('adminEvents'), icon: '📅' },
    { to: '/admin/collaborators', label: t('adminPartners'), icon: '🤝' },
    { to: '/admin/organization', label: t('orgStructure'), icon: '🏢' },
    { to: '/admin/purchases', label: t('adminOrders'), icon: '🛒' },
    { to: '/admin/vip-plans', label: t('adminVipPlans'), icon: '💎' },
    { to: '/admin/vip-users', label: t('adminVipUsers'), icon: '👑' },
    { to: '/admin/vip-test-cards', label: t('adminVipTestCards'), icon: '🧪' },
    { to: '/admin/messages', label: t('adminContacts'), icon: '💬', badge: messagesUnread },
    { to: '/admin/notifications', label: t('notifications'), icon: '🔔', badge: unreadCount },
    { to: '/admin/broadcast-messages', label: t('toWebPage'), icon: '📢' },
    { to: '/admin/quizzes', label: t('manageQuizzes'), icon: '📝' },
    { to: '/admin/reviews', label: t('adminReviews'), icon: '⭐' },
    { to: '/admin/reports', label: t('adminReports'), icon: '📈' },
    { to: '/admin/settings', label: t('adminSettings'), icon: '⚙️' },
    { to: '/profile', label: t('adminProfile'), icon: '👤', end: true },
  ]

  const currentLang = LANGUAGES.find((l) => l.code === locale) || LANGUAGES[0]

  const handleLogout = () => {
    setUserMenuOpen(false)
    logout()
    navigate('/login', { replace: true })
  }

  const avatar = avatarUrl(user) ? (
    <img src={avatarUrl(user)} alt={user?.name} className="w-8 h-8 rounded-full object-cover" />
  ) : (
    <span className="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-sm font-semibold">
      {user?.name?.charAt(0)?.toUpperCase() || '?'}
    </span>
  )

  const sidebar = (
    <aside
      className={`fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 flex flex-col shadow-sm transition-transform duration-200 lg:translate-x-0 ${
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
      }`}
      aria-label="Admin navigation"
    >
      {/* Brand */}
      <Link to="/admin" className="flex shrink-0 items-center gap-3 px-5 h-16 border-b border-gray-100">
        <img
          src="/pi-logo.jpg"
          alt="Pi Education Cambodia logo"
          className="w-10 h-10 rounded-xl object-cover ring-1 ring-gray-900/5 shadow-sm"
        />
        <span className="flex flex-col min-w-0 leading-tight">
          <span className="font-bold text-gray-900 text-sm truncate">Pi Education</span>
          <span className="text-[11px] text-indigo-600 font-medium truncate">{t('adminDashboard')}</span>
        </span>
      </Link>

      {/* Scrollable nav — everything the old "More" dropdown hid is now a plain item */}
      <nav className="flex-1 min-h-0 overflow-y-auto px-3 py-4 space-y-0.5">
        {navItems.map((item) => (
          <NavLink
            key={item.to}
            to={item.to}
            end={item.end}
            onClick={() => setSidebarOpen(false)}
            className={({ isActive }) =>
              `flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors ${
                isActive
                  ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20'
                  : 'text-gray-600 hover:bg-gray-50 hover:text-indigo-700'
              }`
            }
          >
            <span className="w-5 text-center shrink-0" aria-hidden="true">{item.icon}</span>
            <span className="flex-1 min-w-0 truncate">{item.label}</span>
            {item.badge > 0 && (
              <span className="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold leading-none shrink-0">
                {item.badge > 9 ? '9+' : item.badge}
              </span>
            )}
          </NavLink>
        ))}
      </nav>
    </aside>
  )

  const overlay = sidebarOpen && (
    <div
      className="fixed inset-0 z-40 bg-black/40 lg:hidden"
      onClick={() => setSidebarOpen(false)}
      aria-hidden="true"
    />
  )

  const topbar = (
    <header className="sticky top-0 z-30 flex items-center gap-3 h-14 px-4 sm:px-6 bg-white/90 backdrop-blur-xl border-b border-gray-200 shadow-sm shadow-gray-900/[0.03]">
      {/* Mobile menu button — the sidebar is always visible on lg+ */}
      <button
        type="button"
        onClick={() => setSidebarOpen((v) => !v)}
        aria-label="Menu"
        aria-expanded={sidebarOpen}
        className="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-600 border border-gray-200 bg-white/60 shadow-sm transition-all duration-200 hover:bg-white hover:text-indigo-700 active:scale-95"
      >
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <div className="flex-1 min-w-0" />

      {/* Right controls */}
      <div className="flex shrink-0 items-center gap-1.5 sm:gap-2">
        <button
          type="button"
          onClick={toggleTheme}
          aria-label={t('theme')}
          title={theme === 'dark' ? t('lightMode') : t('darkMode')}
          className="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white/60 shadow-sm transition-all duration-200 hover:border-indigo-200 hover:bg-white hover:text-indigo-700 active:scale-95"
        >
          {theme === 'dark' ? (
            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 2a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V3a1 1 0 0 1 1-1Zm4.95 2.05a1 1 0 0 1 0 1.414l-.707.707a1 1 0 1 1-1.414-1.414l.707-.707a1 1 0 0 1 1.414 0Zm-9.9 0a1 1 0 0 1 1.414 0l.707.707A1 1 0 0 1 4.857 6.17l-.707-.707a1 1 0 0 1 0-1.414ZM10 5a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm0 2a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm-7 3a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1Zm11 0a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2h-1a1 1 0 0 1-1-1Zm-7 5a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0v-1a1 1 0 0 1 1-1Zm5 0a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0v-1a1 1 0 0 1 1-1Z" />
            </svg>
          ) : (
            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M17.293 13.293A8 8 0 0 1 6.707 2.707a8.001 8.001 0 1 0 10.586 10.586Z" />
            </svg>
          )}
        </button>

        <div className="relative">
          <button
            type="button"
            onClick={() => setLangOpen((v) => !v)}
            aria-label={t('language')}
            aria-expanded={langOpen}
            title={currentLang.label}
            className="inline-flex items-center gap-1.5 h-9 px-2.5 rounded-lg border border-gray-200 bg-white/60 shadow-sm transition-all duration-200 hover:border-gray-300 hover:bg-white active:scale-95"
          >
            <span className={`fi ${currentLang.flag} text-base leading-none`} />
            <svg className="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          {langOpen && (
            <>
              <div className="fixed inset-0 z-40" onClick={() => setLangOpen(false)} />
              <div className="absolute right-0 top-full mt-2 w-40 bg-white/95 backdrop-blur-xl rounded-xl shadow-lg border border-gray-100 py-2 z-50 origin-top-right dropdown-enter">
                {LANGUAGES.map((lang) => (
                  <button
                    key={lang.code}
                    type="button"
                    onClick={() => { changeLocale(lang.code); setLangOpen(false) }}
                    className={`flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors ${
                      locale === lang.code ? 'bg-indigo-50 text-indigo-700 font-semibold' : ''
                    }`}
                  >
                    <span className={`fi ${lang.flag} text-base leading-none`} />
                    <span>{lang.label}</span>
                  </button>
                ))}
              </div>
            </>
          )}
        </div>

        <div className="relative">
          <button
            type="button"
            onClick={() => setUserMenuOpen((v) => !v)}
            aria-label={t('profile')}
            aria-expanded={userMenuOpen}
            className="flex items-center gap-2 p-1 rounded-full ring-2 ring-transparent transition-all duration-200 hover:ring-indigo-200 hover:-translate-y-0.5 active:scale-95"
          >
            {avatar}
            <span className="hidden xl:flex items-center gap-1.5 text-sm text-gray-700 font-medium">
              {user?.name}
              <span className="px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-700">
                {t('roleAdmin')}
              </span>
            </span>
          </button>

          {userMenuOpen && (
            <>
              <div className="fixed inset-0 z-40" onClick={() => setUserMenuOpen(false)} />
              <div className="absolute right-0 top-full mt-2 w-56 bg-white/95 backdrop-blur-xl rounded-xl shadow-lg border border-gray-100 py-2 z-50 origin-top-right dropdown-enter">
                <div className="px-4 py-2.5 border-b border-gray-100 flex items-center gap-2.5 mb-1">
                  {avatar}
                  <div className="min-w-0">
                    <p className="text-sm font-semibold text-gray-900 truncate">{user?.name}</p>
                    <p className="text-xs text-gray-500 truncate">{user?.email}</p>
                  </div>
                </div>
                <Link
                  to="/profile"
                  onClick={() => setUserMenuOpen(false)}
                  className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  {t('profile')}
                </Link>
                <Link
                  to="/admin/settings"
                  onClick={() => setUserMenuOpen(false)}
                  className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  {t('adminSettings')}
                </Link>
                <Link
                  to="/"
                  onClick={() => setUserMenuOpen(false)}
                  className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  {t('home')}
                </Link>
                <button
                  type="button"
                  onClick={handleLogout}
                  className="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                >
                  {t('logout')}
                </button>
              </div>
            </>
          )}
        </div>

        <button
          type="button"
          onClick={handleLogout}
          title={t('logout')}
          aria-label={t('logout')}
          className="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-red-200 bg-red-50/70 text-red-600 shadow-sm transition-all duration-200 hover:border-red-300 hover:bg-red-50 hover:text-red-700 active:scale-95"
        >
          <svg className="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
        </button>
      </div>
    </header>
  )

  return (
    <div className={viewport ? "h-screen max-h-screen overflow-hidden bg-gray-100" : "min-h-screen bg-gray-100 flex flex-col"}>
      {isVip && <div className="vip-page-frame" aria-hidden="true" />}
      {overlay}
      {sidebar}
      <div className={`lg:pl-64 flex flex-col ${viewport ? "h-screen max-h-screen overflow-hidden" : "min-h-screen"}`}>
        {!viewport && <BroadcastBanner />}
        {topbar}
        <main className={viewport ? 'flex-1 min-h-0 h-full overflow-hidden overflow-y-hidden' : 'flex-1 min-h-0'}>
          {children}
        </main>
        {!viewport && <Footer />}
      </div>
      <style>{SIDEBAR_ANIMATIONS}</style>
    </div>
  )
}
