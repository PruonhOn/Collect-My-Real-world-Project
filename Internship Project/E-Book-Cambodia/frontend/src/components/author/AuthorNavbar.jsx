import { useEffect, useState } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import { useAuthor } from '../../context/AuthorContext'
import { useLanguage } from '../../context/LanguageContext'
import { useTheme } from '../../context/ThemeContext'
import { authorPhotoUrl } from '../../api'
import NotificationBell from '../NotificationBell'
import 'flag-icons/css/flag-icons.min.css'

const NAV_ANIMATIONS = `
  .dropdown-enter { animation: navDrop .16s ease-out; }
  @keyframes navDrop { from { opacity: 0; transform: translateY(-6px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }
  .mobile-enter { animation: mobileIn .22s ease-out; }
  @keyframes mobileIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
  @media (prefers-reduced-motion: reduce) { .dropdown-enter, .mobile-enter { animation: none; } }
`

export default function AuthorNavbar() {
  const { author, logout } = useAuthor()
  const { t, locale, changeLocale } = useLanguage()
  const { theme, toggleTheme } = useTheme()
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
    navigate('/login', { replace: true })
  }

  const items = [
    { to: '/author', label: t('authorDashboard'), end: true },
    { to: '/author/books', label: t('myBooks') },
    { to: '/author/books/new', label: t('addBook') },
    { to: '/author/profile', label: t('authorProfile') },
  ]

  const navLinkClass = ({ isActive }) =>
    `inline-flex items-center shrink-0 whitespace-nowrap px-2.5 py-1.5 rounded-lg text-[13px] xl:text-sm font-medium leading-6 transition-all duration-200 active:scale-[0.96] ${
      isActive
        ? 'text-emerald-700 bg-emerald-50/80 shadow-sm'
        : 'text-gray-600 hover:text-emerald-700 hover:bg-emerald-50/60'
    }`

  const avatar = authorPhotoUrl(author) ? (
    <img src={authorPhotoUrl(author)} alt={author?.name} className="w-8 h-8 rounded-full object-cover" />
  ) : (
    <span className="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm font-semibold">
      {author?.name?.charAt(0)?.toUpperCase() || '?'}
    </span>
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
          <div className="flex items-center justify-between gap-2 sm:gap-4 h-16 lg:h-[4.5rem]">
            {/* Brand */}
            <div className="flex items-center gap-2 sm:gap-3 min-w-0">
              <button
                type="button"
                onClick={() => setLogoOpen(true)}
                className="shrink-0 p-0 bg-transparent border-0 cursor-pointer group"
                title="View logo"
              >
                <img
                  src="/pi-logo.jpg"
                  alt="Pi Education Cambodia logo"
                  className="w-11 h-11 lg:w-12 lg:h-12 rounded-xl object-cover ring-1 ring-gray-900/5 shadow-sm transition-all duration-200 group-hover:ring-emerald-200 group-hover:shadow-md group-hover:-translate-y-0.5"
                />
              </button>
              <Link to="/author" className="flex flex-col justify-center min-w-0 leading-tight">
                <span className="font-bold text-gray-900 text-[15px] sm:text-lg truncate">
                  {t('authorDashboard')}
                </span>
                <span className="text-[11px] sm:text-xs text-emerald-600 truncate">
                  Pi Education Cambodia
                </span>
              </Link>
            </div>

            {/* Author workspace nav */}
            <nav
              className="hidden lg:flex items-center justify-center flex-1 flex-nowrap gap-0.5 min-w-0"
              aria-label="Author navigation"
            >
              {items.map((item) => (
                <NavLink key={item.to} to={item.to} end={item.end} className={navLinkClass}>
                  {item.label}
                </NavLink>
              ))}
            </nav>

            {/* Right controls */}
            <div className="flex items-center gap-1.5 sm:gap-2">
              <NotificationBell scope="author" accent="emerald" />
              <div className="relative">
                <button
                  type="button"
                  onClick={toggleTheme}
                  aria-label={t('theme')}
                  title={theme === 'dark' ? t('lightMode') : t('darkMode')}
                  className="relative inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white/60 shadow-sm transition-all duration-200 hover:border-emerald-200 hover:bg-white hover:text-emerald-700 active:scale-95"
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
                            locale === lang.code ? 'bg-emerald-50 text-emerald-700 font-semibold' : ''
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
                  onClick={() => setUserMenuOpen(!userMenuOpen)}
                  aria-label={t('profile')}
                  aria-expanded={userMenuOpen}
                  className="flex items-center gap-2 p-1 rounded-full ring-2 ring-transparent transition-all duration-200 hover:ring-emerald-200 hover:-translate-y-0.5 active:scale-95"
                >
                  {avatar}
                  <span className="hidden xl:flex items-center gap-1.5 text-sm text-gray-700 font-medium">
                    {author?.name}
                    <span className="px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700">
                      {t('roleAuthor')}
                    </span>
                  </span>
                </button>

                {userMenuOpen && (
                  <>
                    <div className="fixed inset-0 z-40" onClick={() => setUserMenuOpen(false)} />
                    <div className="absolute right-0 mt-2 w-56 bg-white/95 backdrop-blur-xl rounded-xl shadow-lg border border-gray-100 py-2 z-50 origin-top-right dropdown-enter">
                      <div className="px-4 py-2.5 border-b border-gray-100 flex items-center gap-2.5 mb-1">
                        {avatar}
                        <div className="min-w-0">
                          <p className="text-sm font-semibold text-gray-900 truncate">{author?.name}</p>
                          <p className="inline-block mt-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700">
                            {t('roleAuthor')}
                          </p>
                        </div>
                      </div>
                      <Link
                        to="/author/profile"
                        onClick={() => setUserMenuOpen(false)}
                        className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                      >
                        {t('authorProfile')}
                      </Link>
                      <Link
                        to="/author/books"
                        onClick={() => setUserMenuOpen(false)}
                        className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                      >
                        {t('myBooks')}
                      </Link>
                      <Link
                        to="/"
                        onClick={() => setUserMenuOpen(false)}
                        className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                      >
                        {t('backToHome')}
                      </Link>
                      <button
                        onClick={handleLogout}
                        className="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                      >
                        {t('authorLogout')}
                      </button>
                    </div>
                  </>
                )}
              </div>

              <button
                type="button"
                onClick={handleLogout}
                title={t('authorLogout')}
                aria-label={t('authorLogout')}
                className="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-red-200 bg-red-50/70 text-red-600 shadow-sm transition-all duration-200 hover:border-red-300 hover:bg-red-50 hover:text-red-700 active:scale-95"
              >
                <svg className="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
              </button>

              <button
                className="lg:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg text-gray-600 border border-gray-200 bg-white/60 shadow-sm transition-all duration-200 hover:bg-white hover:text-emerald-700 active:scale-95"
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
        </div>

        {/* Mobile menu */}
        {mobileOpen && (
          <div className="lg:hidden border-t border-gray-100 bg-white/95 backdrop-blur-xl px-4 pt-3 pb-4 space-y-1 mobile-enter shadow-lg shadow-gray-900/5">
            <Link
              to="/author/profile"
              onClick={() => setMobileOpen(false)}
              className="flex items-center gap-2.5 py-2.5 px-3 rounded-xl text-sm font-medium text-emerald-700 bg-emerald-50/80 mb-1 transition-colors"
            >
              {avatar}
              <span className="min-w-0">
                <span className="block font-semibold truncate">{author?.name}</span>
                <span className="inline-block mt-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700">
                  {t('roleAuthor')}
                </span>
              </span>
            </Link>
            {items.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                end={item.end}
                onClick={() => setMobileOpen(false)}
                className={({ isActive }) =>
                  `block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 mb-1 ${isActive ? 'text-emerald-700 bg-emerald-50' : 'text-gray-700 hover:text-emerald-700 hover:bg-emerald-50/60'}`
                }
              >
                {item.label}
              </NavLink>
            ))}
            <div className="pt-2 mt-2 border-t border-gray-100">
              <button
                onClick={handleLogout}
                className="inline-flex items-center justify-center w-full h-10 px-4 text-sm font-medium text-red-600 border border-gray-200 rounded-lg transition-colors hover:bg-red-50"
              >
                {t('authorLogout')}
              </button>
            </div>
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