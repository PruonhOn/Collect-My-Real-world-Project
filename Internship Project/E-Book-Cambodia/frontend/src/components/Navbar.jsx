import { useEffect, useState } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { useAuth } from '../context/AuthContext'
import { useLanguage } from '../context/LanguageContext'
import { useTheme } from '../context/ThemeContext'
import { avatarUrl, getMyMessagesUnreadCount } from '../api'
import { useAdminNotifications } from '../services/notifications'
import { useMembership } from '../hooks/useMembership'
import MembershipBadge from './membership/MembershipBadge'
import PremiumAvatar from './membership/PremiumAvatar'
import VipExpiryNote from './membership/VipExpiryNote'
import VipCtaButton from './membership/VipCtaButton'
import CrownIcon from './membership/CrownIcon'
import NotificationBell from './NotificationBell'
import 'flag-icons/css/flag-icons.min.css'

/**
 * Single source of truth for the public nav order, rendered by both the
 * desktop row and the mobile drawer. `label` receives `t` so the list stays
 * translatable; `audience` hides an item instead of rendering a dead link.
 */
const NAV_ITEMS = [
  { to: '/', label: (t) => t('home'), end: true },
  { to: '/about', label: (t) => t('about'), end: true },
  { to: '/organization', label: (t) => t('orgStructure'), end: true },
  { to: '/new-arrivals', label: (t) => t('newArrival'), end: true },
  { to: '/books', label: (t) => t('studyBook'), end: true },
  { to: '/teacher', label: (t) => t('teacherBook'), end: true },
  {
    to: '/competitive-exams',
    label: (t) => t('competitiveExams'),
    end: true,
  },
  { to: '/vip', label: (t) => t('vip'), end: true, audience: 'nonAdmin' },
  { to: '/events', label: (t) => t('events'), end: true },
  { to: '/collaboration', label: (t) => t('collaboration'), end: true },
  { to: '/contact', label: (t) => t('contact'), end: true },
  {
    to: '/admin/notifications',
    label: (t) => t('notifications'),
    end: true,
    audience: 'admin',
    badge: true,
  },
  { to: '/profile', label: (t) => t('profile'), end: true, audience: 'user' },
]

const NAV_ANIMATIONS = `
  .dropdown-enter { animation: navDrop .16s ease-out; }
  @keyframes navDrop { from { opacity: 0; transform: translateY(-6px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
  .mobile-enter { animation: mobileIn .22s ease-out; }
  @keyframes mobileIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
  @media (prefers-reduced-motion: reduce) { .dropdown-enter, .mobile-enter { animation: none; } }
`

export default function Navbar() {
  const { user, isAdmin, logout } = useAuth()
  const { t, locale, changeLocale } = useLanguage()
  const { theme, toggleTheme } = useTheme()
  const { isVip } = useMembership()
  const [mobileOpen, setMobileOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const [langOpen, setLangOpen] = useState(false)
  const [logoOpen, setLogoOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)
  const navigate = useNavigate()

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 4)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  const LANGUAGES = [
    { code: 'km', label: 'ខ្មែរ', flag: 'fi-kh' },
    { code: 'en', label: 'English', flag: 'fi-gb' },
    { code: 'zh', label: '中文', flag: 'fi-cn' },
    { code: 'fr', label: 'Français', flag: 'fi-fr' },
  ]
  const currentLang = LANGUAGES.find((l) => l.code === locale) || LANGUAGES[0]

  const handleLogout = () => {
    logout()
    setUserMenuOpen(false)
    setMobileOpen(false)
    navigate('/')
  }

  const { data: unreadData } = useQuery({
    queryKey: ['my-messages-unread'],
    queryFn: getMyMessagesUnreadCount,
    enabled: !!user,
    refetchInterval: 3000,
    refetchIntervalInBackground: true,
    refetchOnWindowFocus: true,
  })

  // One bell for both member and admin; the scope picks the feed. It still
  // polls messages separately for the badge on the "My messages" menu item.
  const unreadMessages = unreadData?.data?.unread_count || 0

  // Shares its react-query key with the bell below, so admins reuse the same
  // request for the "Notifications" nav badge instead of polling twice.
  const { unreadCount: adminUnreadCount } = useAdminNotifications(isAdmin)

  const visibleNavItems = NAV_ITEMS.filter((item) => {
    if (item.audience === 'admin') return !!isAdmin
    if (item.audience === 'user') return !!user
    if (item.audience === 'nonAdmin') return !isAdmin
    return true
  })

  const unreadBadge = (count) =>
    count > 0 && (
      <span className="ml-2 inline-block min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold text-center leading-[18px]">
        {count > 9 ? '9+' : count}
      </span>
    )

  const navLinkClass = ({ isActive }) =>
    `inline-flex items-center shrink-0 whitespace-nowrap px-2 xl:px-2.5 py-1.5 rounded-lg text-[13px] xl:text-sm font-medium leading-6 transition-all duration-200 active:scale-[0.96] ${
      isActive
        ? 'text-indigo-700 bg-indigo-50/80 shadow-sm'
        : 'text-gray-600 hover:text-indigo-700 hover:bg-indigo-50/60'
    }`

  // PremiumAvatar keeps the plain avatar for normal members and adds the
  // gold ring only for an active membership, so the header stays neutral for
  // everyone else.
  const avatar = (
    <PremiumAvatar src={avatarUrl(user)} name={user?.name} />
  )

  return (
    <>
      <header
        className={`sticky top-0 z-50 backdrop-blur-xl bg-white/75 border-b transition-all duration-300 ${
          scrolled
            ? 'border-gray-200/80 shadow-md shadow-gray-900/5'
            : 'border-gray-100/80 shadow-sm shadow-gray-900/[0.03]'
        }`}
      >
        <div className="w-full px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between gap-2 sm:gap-4 min-h-16 py-1.5">
            {/* Brand + profile: the avatar sits directly under the logo on the
                left, so VIP members see their gold ring next to the brand. */}
            <div className="flex shrink-0 items-center gap-2 sm:gap-3">
              <div className="flex flex-col items-center gap-1">
                <button
                  type="button"
                  onClick={() => setLogoOpen(true)}
                  className="shrink-0 p-0 bg-transparent border-0 cursor-pointer group"
                  title="View logo"
                >
                  <img
                    src="/pi-logo.jpg"
                    alt="Pi Education Cambodia logo"
                    className="w-11 h-11 lg:w-12 lg:h-12 rounded-xl object-cover ring-1 ring-gray-900/5 shadow-sm transition-all duration-200 group-hover:ring-indigo-200 group-hover:shadow-md group-hover:-translate-y-0.5"
                  />
                </button>

                {user && (
                  <div className="relative">
                    <button
                      onClick={() => setUserMenuOpen(!userMenuOpen)}
                      aria-label={t('profile')}
                      aria-expanded={userMenuOpen}
                      className={`flex items-center justify-center p-0.5 rounded-full ring-2 ring-transparent transition-all duration-200 hover:-translate-y-0.5 active:scale-95 ${
                        isVip ? 'hover:ring-amber-300' : 'hover:ring-indigo-200'
                      }`}
                    >
                      {avatar}
                    </button>

                    {userMenuOpen && (
                      <>
                        <div className="fixed inset-0 z-40" onClick={() => setUserMenuOpen(false)} />
                        <div
                          className={`absolute left-0 mt-2 w-60 bg-white/95 backdrop-blur-xl rounded-xl shadow-lg border py-2 z-50 origin-top-left dropdown-enter ${
                            isVip ? 'border-amber-400/50 shadow-[0_0_20px_rgba(212,175,55,0.15)]' : 'border-gray-100'
                          }`}
                        >
                          <div
                            className={`px-4 py-3 border-b mb-1 flex items-center gap-2.5 ${
                              isVip
                                ? 'border-amber-400/40 bg-gradient-to-br from-[#BF953F]/10 via-[#FCF6BA]/10 to-[#B38728]/10 rounded-t-xl backdrop-blur-md shadow-[0_0_15px_rgba(212,175,55,0.15)]'
                                : 'border-gray-100'
                            }`}
                          >
                            {avatar}
                            <div className="min-w-0">
                              <p className={`text-sm font-semibold truncate ${isVip ? 'text-white' : 'text-gray-900'}`}>
                                {user.name}
                              </p>
                              <div className="mt-0.5 flex flex-wrap items-center gap-x-2">
                                <MembershipBadge size="xs" />
                                {isVip && (
                                  <VipExpiryNote className="text-[10px] text-amber-200/90" />
                                )}
                              </div>
                            </div>
                          </div>
                          <Link
                            to="/profile"
                            onClick={() => setUserMenuOpen(false)}
                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                          >
                            {t('profile')}
                          </Link>
                          {isAdmin ? (
                            <Link
                              to="/admin/settings"
                              onClick={() => setUserMenuOpen(false)}
                              className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                              {t('adminSettings')}
                            </Link>
                          ) : (
                            <>
                              <Link
                                to="/my-library"
                                onClick={() => setUserMenuOpen(false)}
                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                              >
                                {t('myLibrary')}
                              </Link>
                              <Link
                                to="/messages"
                                onClick={() => setUserMenuOpen(false)}
                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                              >
                                {t('myMessages')}
                                {unreadBadge(unreadMessages)}
                              </Link>
                              <Link
                                to="/my-purchases"
                                onClick={() => setUserMenuOpen(false)}
                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                              >
                                {t('myPurchases')}
                              </Link>
                              <Link
                                to="/vip"
                                onClick={() => setUserMenuOpen(false)}
                                className="flex items-center gap-2 px-4 py-2 text-sm text-white font-semibold bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] border border-amber-400/30 rounded-lg shadow-[0_0_10px_rgba(212,175,55,0.2)] hover:shadow-[0_0_15px_rgba(212,175,55,0.4)] hover:from-[#d4a737] hover:to-[#c49a30] transition-all duration-200"
                              >
                                <CrownIcon className="w-3.5 h-3.5" />
                                {t('vip')}
                              </Link>
                            </>
                          )}
                          <button
                            onClick={handleLogout}
                            className="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                          >
                            {t('logout')}
                          </button>
                        </div>
                      </>
                    )}
                  </div>
                )}
              </div>
              <Link to="/" className="flex flex-col justify-center min-w-0 leading-tight">
                <span className="font-bold text-gray-900 text-[15px] sm:text-lg truncate">
                  Pi Education Cambodia
                </span>
                <span className="text-[11px] sm:text-xs text-gray-500 truncate">
                  Digital Books for Every Learner
                </span>
              </Link>
            </div>

            {/* Right controls */}
            <div className="flex shrink-0 items-center gap-1.5 sm:gap-2">
              {isAdmin && (
                <Link
                  to="/admin"
                  className="hidden lg:inline-flex items-center shrink-0 whitespace-nowrap h-9 px-3 text-sm font-medium text-indigo-600 bg-indigo-50/80 hover:bg-indigo-100 rounded-lg transition-all duration-200 hover:-translate-y-0.5 active:scale-95"
                >
                  {t('adminDashboard')}
                </Link>
              )}
              {user && (
                <NotificationBell
                  scope={isAdmin ? 'admin' : 'member'}
                  viewAllTo={isAdmin ? '/admin/notifications' : undefined}
                />
              )}
              {/* Membership CTA: upgrade/renew for everyone below VIP, a gold
                  badge with the expiry note once VIP is active. Admins skip it. */}
              {!isAdmin && (
                <VipCtaButton size="sm" className="hidden md:inline-flex" />
              )}
              <div className="relative">
                <button
                  type="button"
                  onClick={toggleTheme}
                  aria-label={t('theme')}
                  title={theme === 'dark' ? t('lightMode') : t('darkMode')}
                  className="relative inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white/60 shadow-sm transition-all duration-200 hover:border-indigo-200 hover:bg-white hover:text-indigo-700 active:scale-95"
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
              </div>
              <div className="relative">
                <button
                  onClick={() => setLangOpen(!langOpen)}
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
                    <div className="absolute right-0 mt-2 w-40 bg-white/95 backdrop-blur-xl rounded-xl shadow-lg border border-gray-100 py-2 z-50 origin-top-right dropdown-enter">
                      {LANGUAGES.map((lang) => (
                        <button
                          key={lang.code}
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

              {user ? (
                <button
                  type="button"
                  onClick={handleLogout}
                  title={t('logout')}
                  aria-label={t('logout')}
                  className="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded-lg border border-red-200 bg-red-50/70 text-red-600 shadow-sm transition-all duration-200 hover:border-red-300 hover:bg-red-50 hover:text-red-700 active:scale-95"
                >
                  <svg className="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                </button>
              ) : (
                <div className="flex items-center gap-1.5 sm:gap-2">
                  <Link
                    to="/login"
                    className="inline-flex items-center shrink-0 whitespace-nowrap h-9 px-3 lg:px-4 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all duration-200 hover:-translate-y-0.5 active:scale-95"
                  >
                    {t('login')}
                  </Link>
                  <Link
                    to="/register"
                    className="inline-flex items-center shrink-0 whitespace-nowrap h-9 px-3 lg:px-4 text-sm font-medium bg-indigo-600 text-white rounded-lg shadow-sm shadow-indigo-600/20 transition-all duration-200 hover:bg-indigo-700 hover:shadow-md hover:-translate-y-0.5 active:scale-95"
                  >
                    {t('register')}
                  </Link>
                </div>
              )}

              <button
                className="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-600 border border-gray-200 bg-white/60 shadow-sm transition-all duration-200 hover:bg-white hover:text-indigo-700 active:scale-95"
                onClick={() => setMobileOpen(!mobileOpen)}
                aria-label="Menu"
                aria-expanded={mobileOpen}
              >
                {mobileOpen ? (
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                ) : (
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                  </svg>
                )}
              </button>
            </div>
          </div>

          {/* Page links get their own full-width row so they no longer squeeze
              between the brand and the controls. Scrolls sideways if they
              overflow, keeping the header height fixed. */}
          <nav
            className="hidden lg:flex nav-scroll-slim w-full min-w-0 flex-nowrap items-center justify-center gap-0.5 h-12"
            aria-label="Main navigation"
          >
            {visibleNavItems.map((item) => (
              <NavLink key={item.to} to={item.to} className={navLinkClass} end={item.end}>
                {item.label(t)}
                {item.badge && unreadBadge(adminUnreadCount)}
              </NavLink>
            ))}
          </nav>
        </div>

        {/* Mobile menu */}
        {mobileOpen && (
          <div className="lg:hidden border-t border-gray-100 bg-white/95 backdrop-blur-xl px-4 pt-3 pb-4 space-y-1 mobile-enter shadow-lg shadow-gray-900/5">
            {user && (
              <Link
                to="/profile"
                onClick={() => setMobileOpen(false)}
                className={`flex items-center gap-2.5 py-2.5 px-3 rounded-xl text-sm font-medium mb-1 transition-colors ${
                  isVip
                    ? 'text-white bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] border border-amber-400/40 shadow-[0_0_15px_rgba(212,175,55,0.3)]'
                    : 'text-indigo-700 bg-indigo-50/80'
                }`}
              >
                {avatar}
                <span className="min-w-0">
                  <span className="block font-semibold truncate">{user.name}</span>
                  <span className="inline-flex flex-wrap items-center gap-x-2 mt-0.5">
                    <MembershipBadge size="xs" />
                    {isVip && <VipExpiryNote className="text-[10px] text-amber-600/90" />}
                  </span>
                </span>
              </Link>
            )}
            {!isAdmin && (
              <VipCtaButton size="full" className="w-full justify-center mb-1" />
            )}
            {user && (
              <NavLink
                to="/messages"
                onClick={() => setMobileOpen(false)}
                className={({ isActive }) => `block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 mb-1 ${isActive ? 'text-indigo-700 bg-indigo-50' : 'text-gray-700 hover:text-indigo-700 hover:bg-indigo-50/60'}`}
              >
                {t('myMessages')}
                {unreadBadge(unreadMessages)}
              </NavLink>
            )}
            {isAdmin && (
              <NavLink
                to="/admin"
                onClick={() => setMobileOpen(false)}
                className={({ isActive }) => `block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 mb-1 ${isActive ? 'text-indigo-700 bg-indigo-50' : 'text-gray-700 hover:text-indigo-700 hover:bg-indigo-50/60'}`}
                end
              >
                {t('adminDashboard')}
              </NavLink>
            )}
            {visibleNavItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive }) => `block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 ${isActive ? 'text-indigo-700 bg-indigo-50' : 'text-gray-700 hover:text-indigo-700 hover:bg-indigo-50/60'}`}
                end={item.end}
                onClick={() => setMobileOpen(false)}
              >
                {item.label(t)}
                {item.badge && unreadBadge(adminUnreadCount)}
              </NavLink>
            ))}
            {!user && (
              <div className="pt-2 mt-2 border-t border-gray-100 flex flex-col gap-2">
                <Link
                  to="/login"
                  onClick={() => setMobileOpen(false)}
                  className="inline-flex items-center justify-center h-10 px-4 text-sm font-medium bg-indigo-600 text-white rounded-lg shadow-sm transition-colors hover:bg-indigo-700"
                >
                  {t('login')}
                </Link>
                <Link
                  to="/register"
                  onClick={() => setMobileOpen(false)}
                  className="inline-flex items-center justify-center h-10 px-4 text-sm font-medium text-indigo-600 border border-gray-200 rounded-lg transition-colors hover:bg-indigo-50"
                >
                  {t('register')}
                </Link>
              </div>
            )}
            {user && (
              <div className="pt-2 mt-2 border-t border-gray-100">
                <button
                  type="button"
                  onClick={handleLogout}
                  className="inline-flex items-center justify-center gap-2 w-full h-10 px-4 text-sm font-medium text-red-600 border border-gray-200 rounded-lg transition-colors hover:bg-red-50"
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  {t('logout')}
                </button>
              </div>
            )}
          </div>
        )}
        <style>{NAV_ANIMATIONS}</style>
      </header>

      {logoOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
          onClick={() => setLogoOpen(false)}
        >
          <div
            className="relative bg-white rounded-2xl shadow-2xl p-4 max-w-[90vw] max-h-[90vh]"
            onClick={(e) => e.stopPropagation()}
          >
            <button
              type="button"
              onClick={() => setLogoOpen(false)}
              className="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-gray-800 text-white flex items-center justify-center text-lg font-bold hover:bg-red-500 transition-colors shadow-lg"
            >
              ✕
            </button>
            <img
              src="/pi-logo.jpg"
              alt="Pi Education Cambodia logo preview"
              className="max-w-[80vw] max-h-[80vh] rounded-xl object-contain"
            />
          </div>
        </div>
      )}
    </>
  )
}