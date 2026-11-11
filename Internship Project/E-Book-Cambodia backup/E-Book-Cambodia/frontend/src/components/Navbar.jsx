import { useState } from 'react'
import { Link, NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { useLanguage } from '../context/LanguageContext'
import { avatarUrl } from '../api'
import 'flag-icons/css/flag-icons.min.css'

export default function Navbar() {
  const { user, isAdmin, logout } = useAuth()
  const { t, locale, changeLocale } = useLanguage()
  const [mobileOpen, setMobileOpen] = useState(false)
  const [userMenuOpen, setUserMenuOpen] = useState(false)
  const [langOpen, setLangOpen] = useState(false)
  const [logoOpen, setLogoOpen] = useState(false)
  const navigate = useNavigate()

  const LANGUAGES = [
    { code: 'km', label: 'ខ្មែរ', flag: 'fi-kh' },
    { code: 'en', label: 'English', flag: 'fi-gb' },
    { code: 'zh', label: '中文', flag: 'fi-cn' },
  ]
  const currentLang = LANGUAGES.find((l) => l.code === locale) || LANGUAGES[0]

  const handleLogout = () => {
    logout()
    setUserMenuOpen(false)
    navigate('/')
  }

  const navLinkClass = ({ isActive }) =>
    `px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
      isActive ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50'
    }`

  return (
    <>
    <nav className="bg-white shadow-sm sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-24">
          <div className="flex items-center">
            <button
              type="button"
              onClick={() => setLogoOpen(true)}
              className="p-0 bg-transparent border-0 cursor-pointer"
              title="View logo"
            >
              <img src="/pi-logo.jpg" alt="Pi Education Cambodia logo" className="w-22 h-22 rounded-xl object-cover hover:opacity-80 transition-opacity" />
            </button>
            <Link to="/" className="flex items-center">
              <div className="hidden sm:block">
                <div className="font-bold text-indigo-900 leading-tight">Pi Education Cambodia</div>
                <div className="text-xs text-gray-500">Digital Books for Every Learner</div>
              </div>
            </Link>
          </div>

          <div className="hidden md:flex items-center gap-1">
            <NavLink to="/" className={navLinkClass} end>
              {t('home')}
            </NavLink>
            <NavLink to="/books" className={navLinkClass} end>
              {t('books')}
            </NavLink>
            <NavLink to="/quizzes" className={navLinkClass} end>
              {t('quizzes')}
            </NavLink>
            <NavLink to="/categories" className={navLinkClass} end>
              {t('categories')}
            </NavLink>
            <NavLink to="/teacher" className={navLinkClass} end>
              {t('teacher')}
            </NavLink>
            <NavLink to="/student" className={navLinkClass} end>
              {t('student')}
            </NavLink>
            <NavLink to="/about" className={navLinkClass} end>
              {t('about')}
            </NavLink>
            {isAdmin && (
              <>
                <NavLink to="/admin" className={navLinkClass} end>
                  {t('adminDashboard')}
                </NavLink>
                <NavLink to="/admin/users" className={navLinkClass} end>
                  {t('adminUsers')}
                </NavLink>
              </>
            )}
          </div>

          <div className="flex items-center gap-2">
            <div className="relative">
              <button
                onClick={() => setLangOpen(!langOpen)}
                className="flex items-center justify-center px-2 py-1 rounded hover:bg-gray-100"
                aria-label={t('language')}
                title={currentLang.label}
              >
                <span className={`fi ${currentLang.flag} text-base`} />
                <svg className="w-3 h-3 ml-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
              {langOpen && (
                <>
                  <div className="fixed inset-0 z-40" onClick={() => setLangOpen(false)} />
                  <div className="absolute right-0 mt-2 w-40 bg-white rounded-xl shadow-lg py-2 border border-gray-100 z-50">
                    {LANGUAGES.map((lang) => (
                      <button
                        key={lang.code}
                        onClick={() => { changeLocale(lang.code); setLangOpen(false) }}
                        className={`flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 ${
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
              <div className="flex items-center gap-2">
                <Link
                  to="/vip"
                  className={`hidden sm:inline-block px-2.5 py-1 text-xs font-bold rounded-full ${
                    user.is_vip
                      ? 'bg-amber-400 text-amber-900 hover:bg-amber-500'
                      : 'border border-amber-400 text-amber-600 hover:bg-amber-50'
                  }`}
                >
                  VIP
                </Link>
                <Link
                  to="/profile"
                  onClick={() => setUserMenuOpen(false)}
                  className="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-sm font-medium hover:bg-indigo-100"
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    />
                  </svg>
                  {t('profile')}
                </Link>
                <div className="relative">
                <button
                  onClick={() => setUserMenuOpen(!userMenuOpen)}
                  className="flex items-center gap-2 p-1 rounded-full hover:bg-gray-100"
                >
                  {avatarUrl(user) ? (
                    <img src={avatarUrl(user)} alt={user.name} className="w-8 h-8 rounded-full object-cover" />
                  ) : (
                    <span className="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-sm font-semibold">
                      {user.name?.charAt(0).toUpperCase()}
                    </span>
                  )}
                  <span className="hidden xl:block text-sm text-gray-700">{user.name}</span>
                </button>

                {userMenuOpen && (
                  <div className="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg py-2 border border-gray-100">
                    <Link
                      to="/profile"
                      onClick={() => setUserMenuOpen(false)}
                      className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                      {t('profile')}
                    </Link>
                    <Link
                      to="/my-library"
                      onClick={() => setUserMenuOpen(false)}
                      className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                      {t('myLibrary')}
                    </Link>
                    <Link
                      to="/my-purchases"
                      onClick={() => setUserMenuOpen(false)}
                      className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                      {t('myPurchases')}
                    </Link>
                    <Link
                      to="/vip"
                      onClick={() => setUserMenuOpen(false)}
                      className="block px-4 py-2 text-sm text-amber-600 hover:bg-amber-50"
                    >
                      VIP
                    </Link>
                    {isAdmin && (
                      <>
                        <Link
                          to="/admin"
                          onClick={() => setUserMenuOpen(false)}
                          className="block px-4 py-2 text-sm text-indigo-600 hover:bg-indigo-50 border-t border-gray-100"
                        >
                          {t('adminDashboard')}
                        </Link>
                        <Link
                          to="/admin/users"
                          onClick={() => setUserMenuOpen(false)}
                          className="block px-4 py-2 text-sm text-indigo-600 hover:bg-indigo-50"
                        >
                          {t('adminUsers')}
                        </Link>
                      </>
                    )}
                    <button
                      onClick={handleLogout}
                      className="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
                    >
                      {t('logout')}
                    </button>
                  </div>
                )}
                </div>
              </div>
            ) : (
              <div className="flex items-center gap-2">
                <Link
                  to="/login"
                  className="px-4 py-2 text-sm font-medium text-indigo-600 hover:bg-indigo-50 rounded-lg"
                >
                  {t('login')}
                </Link>
                <Link
                  to="/register"
                  className="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                >
                  {t('register')}
                </Link>
              </div>
            )}

            <button
              className="md:hidden p-2 text-gray-600"
              onClick={() => setMobileOpen(!mobileOpen)}
              aria-label="Menu"
            >
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      {mobileOpen && (
        <div className="md:hidden border-t border-gray-100 bg-white px-4 py-3 space-y-1">
          {user && (
            <NavLink
              to="/profile"
              onClick={() => setMobileOpen(false)}
              className={`flex items-center gap-2 py-2 rounded-lg text-sm font-medium text-indigo-600 bg-indigo-50 px-3`}
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
              {t('profile')}
            </NavLink>
          )}
          <NavLink to="/" className={navLinkClass} end>
            {t('home')}
          </NavLink>
          <NavLink to="/books" className={navLinkClass} end>
            {t('books')}
          </NavLink>
          <NavLink to="/quizzes" className={navLinkClass} end>
            {t('quizzes')}
          </NavLink>
          <NavLink to="/categories" className={navLinkClass} end>
            {t('categories')}
          </NavLink>
          <NavLink to="/teacher" className={navLinkClass} end>
            {t('teacher')}
          </NavLink>
          <NavLink to="/student" className={navLinkClass} end>
            {t('student')}
          </NavLink>
          <NavLink to="/about" className={navLinkClass} end>
            {t('about')}
          </NavLink>
          {isAdmin && (
            <>
              <NavLink to="/admin" className={navLinkClass} end>
                {t('adminDashboard')}
              </NavLink>
              <NavLink to="/admin/users" className={navLinkClass} end>
                {t('adminUsers')}
              </NavLink>
            </>
          )}
        </div>
      )}
    </nav>

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