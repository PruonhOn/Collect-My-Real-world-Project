import { Link } from 'react-router-dom'
import { socialLinks } from '../Footer'
import StudentIllustration from './StudentIllustration'
import { useLanguage } from '../../context/LanguageContext'

export default function AuthLayout({ title, subtitle, children, showSocial = true }) {
  const { t } = useLanguage()
  const year = new Date().getFullYear()

  const features = [
    { icon: 'M4 12l3-3 3 3 4-4 3 3', text: t('featureFree') },
    { icon: 'M3 5h18M3 12h18M3 19h18', text: t('featureMultiLang') },
    { icon: 'M9 11.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zm11 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zM12 4l1.2 3.8L17 9l-3.8 1.2L12 14l-1.2-3.8L7 9l3.8-1.2L12 4z', text: t('featureQuizzes') },
  ]

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <div className="flex-1 grid lg:grid-cols-2">
        <aside className="hidden lg:flex items-center relative overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-500 to-violet-600">
          <div className="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-white/10" />
          <div className="absolute -bottom-28 -left-16 w-96 h-96 rounded-full bg-white/10" />

          <div className="relative w-full max-w-xl mx-auto px-10 py-12">
            <div className="flex items-center gap-2.5 mb-10">
              <img src="/pi-logo.jpg" alt="" className="w-9 h-9 rounded-xl object-cover shadow-md" />
              <span className="font-bold text-white text-lg tracking-tight">Pi Education Cambodia</span>
            </div>

            <div className="rounded-[2rem] bg-white/10 backdrop-blur border border-white/25 shadow-2xl p-6 sm:p-8">
              <div className="relative">
                <div className="absolute -top-3 -right-3 z-10">
                  <span className="inline-flex items-center gap-1.5 bg-amber-400 text-amber-900 text-xs font-semibold px-3 py-1.5 rounded-full shadow-md">
                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118L2.986 10.1c-.783-.57-.38-1.81.588-1.81h4.915a1 1 0 00.95-.69l1.51-4.673z" />
                    </svg>
                    {t('heroChip')}
                  </span>
                </div>
                <div className="bg-gradient-to-b from-white to-indigo-50 rounded-3xl shadow-inner p-5">
                  <StudentIllustration />
                </div>
              </div>
            </div>

            <div className="mt-10">
              <h2 className="text-white text-3xl font-extrabold leading-tight mb-3">{t('learnAnytime')}</h2>
              <p className="text-indigo-100 text-sm leading-relaxed mb-6">{t('tagline')}</p>
              <ul className="space-y-3">
                {features.map((f) => (
                  <li key={f.text} className="flex items-center gap-3 text-indigo-50 text-sm">
                    <span className="w-8 h-8 rounded-full bg-white/15 border border-white/25 flex items-center justify-center shrink-0">
                      <svg className="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" d={f.icon} />
                      </svg>
                    </span>
                    {f.text}
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </aside>

        <main className="flex flex-col min-h-full px-5 sm:px-10 py-8">
          <div className="flex-1 w-full max-w-md mx-auto flex flex-col justify-center">
            <header className="flex items-center gap-2.5 mb-2 lg:hidden">
              <img src="/pi-logo.jpg" alt="" className="w-9 h-9 rounded-xl object-cover shadow-md" />
              <span className="font-bold text-gray-900 dark:text-gray-100 text-lg tracking-tight">{t('appName')}</span>
            </header>

            <div className="mb-8">
              <Link
                to="/"
                className="hidden lg:inline-flex items-center gap-2 text-gray-400 hover:text-gray-600 text-sm font-medium mb-8 transition-colors"
              >
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {t('backToHome')}
              </Link>
              <h1 className="text-3xl font-extrabold text-gray-900 dark:text-gray-100 tracking-tight">{title}</h1>
              <p className="text-gray-500 text-sm mt-1.5">{subtitle}</p>
            </div>

            {children}
          </div>
        </main>
      </div>

      <footer className="border-t border-gray-200 bg-white">
        <div className="max-w-7xl mx-auto px-5 sm:px-10 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
          <p className="text-xs text-gray-500">&copy; {year} {t('appName')}. All rights reserved.</p>
          {showSocial && (
            <div className="flex items-center gap-1">
              {socialLinks.map((link) => (
                <a
                  key={link.label}
                  href={link.href}
                  target={link.href.startsWith('mailto') ? undefined : '_blank'}
                  rel={link.href.startsWith('mailto') ? undefined : 'noreferrer'}
                  aria-label={link.label}
                  title={link.label}
                  className="w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 flex items-center justify-center transition-colors"
                >
                  {link.icon}
                </a>
              ))}
            </div>
          )}
        </div>
      </footer>
    </div>
  )
}