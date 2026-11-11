import { Link } from 'react-router-dom'
import { useI18n } from '../../i18n'

const exploreLinks = [
  { to: '/books', key: 'common.books' },
  { to: '/categories', key: 'common.categories' },
  { to: '/authors', key: 'common.authors' },
  { to: '/pricing', key: 'common.vip' },
]

const aboutLinks = [
  { to: '/about', key: 'common.about' },
  { to: '/contact', key: 'common.contact' },
]

const languages = [
  { code: 'km', native: 'ភាសាខ្មែរ' },
  { code: 'en', native: 'English' },
  { code: 'zh', native: '中文' },
  { code: 'fr', native: 'Français' },
]

export default function Footer() {
  const { t } = useI18n()
  return (
    <footer className="border-t border-slate-200/80 bg-white">
      <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <div className="grid gap-10 md:grid-cols-4">
          <div>
            <div className="flex items-center gap-2.5">
              <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-base font-extrabold text-white">
                Pi
              </span>
              <span className="text-lg font-extrabold tracking-tight text-slate-900">
                Pi<span className="text-indigo-600">-Education</span>
              </span>
            </div>
            <p className="mt-4 text-sm leading-relaxed text-slate-500">
              Your digital library for education books. Learn anything, anywhere, in any language.
            </p>
          </div>

          <div>
            <h4 className="text-sm font-bold tracking-wide text-slate-900 uppercase">{t('common.explore')}</h4>
            <ul className="mt-4 space-y-3 text-sm">
              {exploreLinks.map((link) => (
                <li key={link.to}>
                  <Link to={link.to} className="text-slate-500 transition hover:text-indigo-600">
                    {t(link.key)}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h4 className="text-sm font-bold tracking-wide text-slate-900 uppercase">{t('common.about')}</h4>
            <ul className="mt-4 space-y-3 text-sm">
              {aboutLinks.map((link) => (
                <li key={link.to}>
                  <Link to={link.to} className="text-slate-500 transition hover:text-indigo-600">
                    {t(link.key)}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h4 className="text-sm font-bold tracking-wide text-slate-900 uppercase">{t('common.languages')}</h4>
            <ul className="mt-4 flex flex-wrap gap-2">
              {languages.map((lang) => (
                <li
                  key={lang.code}
                  className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-medium text-slate-600"
                >
                  {lang.native}
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div className="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-200 pt-6 sm:flex-row">
          <p className="text-xs text-slate-400">© {new Date().getFullYear()} Pi-Education. All rights reserved.</p>
          <p className="text-xs text-slate-400">
            Built for multilingual education · Khmer · English · 中文 · Français
          </p>
        </div>
      </div>
    </footer>
  )
}