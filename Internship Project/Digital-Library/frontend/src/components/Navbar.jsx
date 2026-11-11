import { useEffect, useState } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { useLanguage } from '../context/LanguageContext'
import LanguageSwitcher from './LanguageSwitcher'

const navLinkClass = ({ isActive }) =>
  `px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
    isActive ? 'bg-indigo-100 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
  }`

export default function Navbar() {
  const { isAuthenticated, isAdmin, user, logout } = useAuth()
  const { t } = useLanguage()
  const [mobileOpen, setMobileOpen] = useState(false)
  const [userMenu, setUserMenu] = useState(false)
  const [scrolled, setScrolled] = useState(false)
  const navigate = useNavigate()

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 4)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  useEffect(() => {
    if (!userMenu) return
    const onPointerDown = (e) => {
      if (!e.target.closest('[data-user-menu]')) setUserMenu(false)
    }
    document.addEventListener('pointerdown', onPointerDown)
    return () => document.removeEventListener('pointerdown', onPointerDown)
  }, [userMenu])

  const handleLogout = async () => {
    await logout()
    navigate('/')
    setUserMenu(false)
    setMobileOpen(false)
  }

  const links = (
    <>
      <NavLink to="/" end className={navLinkClass} onClick={() => setMobileOpen(false)}>
        {t('navHome')}
      </NavLink>
      <NavLink to="/books" className={navLinkClass} onClick={() => setMobileOpen(false)}>
        {t('navBooks')}
      </NavLink>
      <NavLink to="/categories" className={navLinkClass} onClick={() => setMobileOpen(false)}>
        {t('navCategories')}
      </NavLink>
      {isAuthenticated && (
        <NavLink to="/library" className={navLinkClass} onClick={() => setMobileOpen(false)}>
          {t('navLibrary')}
        </NavLink>
      )}
      {isAdmin && (
        <NavLink to="/admin" className={navLinkClass} onClick={() => setMobileOpen(false)}>
          {t('navAdmin')}
        </NavLink>
      )}
    </>
  )

  return (
    <nav
      className={`bg-white/85 backdrop-blur-md sticky top-0 z-50 transition-shadow duration-300 ${
        scrolled ? 'shadow-md' : 'shadow-sm'
      }`}
    >
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          <Link to="/" className="flex items-center gap-2 group">
            <span className="bg-indigo-600 text-white w-9 h-9 rounded-lg flex items-center justify-center text-lg group-hover:scale-105 transition-transform">
              📚
            </span>
            <span className="font-bold text-gray-900 text-lg">{t('appName')}</span>
          </Link>

          <div className="hidden md:flex items-center gap-1">{links}</div>

          <div className="hidden md:flex items-center gap-3">
            <LanguageSwitcher />
            {isAuthenticated ? (
              <div className="relative" data-user-menu>
                <button
                  onClick={() => setUserMenu((v) => !v)}
                  className="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                >
                  <span className="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-sm font-bold uppercase">
                    {(user?.name || 'U')[0]}
                  </span>
                  <span className="max-w-[120px] truncate">{user?.name}</span>
                  <svg className="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                {userMenu && (
                  <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 origin-top-right animate-scale-in">
                    <div className="px-4 py-2 border-b border-gray-100">
                      <p className="text-sm font-medium text-gray-900 truncate">{user?.name}</p>
                      <p className="text-xs text-gray-500 truncate">{user?.email}</p>
                    </div>
                    <Link
                      to="/sharing"
                      onClick={() => setUserMenu(false)}
                      className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                      {t('sharing')}
                    </Link>
                    <button
                      onClick={handleLogout}
                      className="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                    >
                      {t('logout')}
                    </button>
                  </div>
                )}
              </div>
            ) : (
              <>
                <Link
                  to="/login"
                  className="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                >
                  {t('login')}
                </Link>
                <Link
                  to="/register"
                  className="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                >
                  {t('register')}
                </Link>
              </>
            )}
          </div>

          <button
            className="md:hidden text-gray-600"
            onClick={() => setMobileOpen((v) => !v)}
            aria-label={t('navMenuToggle')}
          >
            {mobileOpen ? (
              <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            ) : (
              <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            )}
          </button>
        </div>
      </div>

      {mobileOpen && (
        <div className="md:hidden border-t border-gray-100 bg-white/95 backdrop-blur px-4 py-3 space-y-1 animate-slide-down">
          <div className="flex justify-end">
            <LanguageSwitcher />
          </div>
          {links}
          <div className="pt-2 border-t border-gray-100 flex flex-col gap-2 mt-2">
            {isAuthenticated ? (
              <>
                <span className="text-sm text-gray-500 px-3">{t('signedInAs')} {user?.name}</span>
                <Link
                  to="/sharing"
                  onClick={() => setMobileOpen(false)}
                  className="px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100"
                >
                  {t('sharing')}
                </Link>
                <button
                  onClick={handleLogout}
                  className="px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 text-left"
                >
                  {t('logout')}
                </button>
              </>
            ) : (
              <div className="flex gap-2">
                <Link
                  to="/login"
                  onClick={() => setMobileOpen(false)}
                  className="flex-1 text-center px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                >
                  {t('login')}
                </Link>
                <Link
                  to="/register"
                  onClick={() => setMobileOpen(false)}
                  className="flex-1 text-center px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                >
                  {t('register')}
                </Link>
              </div>
            )}
          </div>
        </div>
      )}
    </nav>
  )
}
