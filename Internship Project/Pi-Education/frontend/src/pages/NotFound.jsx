import { Link } from 'react-router-dom'
import { useI18n } from '../i18n'

export default function NotFound() {
  const { t } = useI18n()
  return (
    <div className="flex min-h-[70vh] flex-col items-center justify-center gap-8 px-4 text-center">
      <div className="flex h-20 w-20 items-center justify-center rounded-3xl bg-indigo-600 text-2xl font-extrabold text-white shadow-lg shadow-indigo-600/20">
        Pi
      </div>
      <div>
        <p className="text-8xl font-extrabold tracking-tight text-slate-200">404</p>
        <h1 className="mt-3 text-xl font-bold text-slate-900">{t('common.notFound')}</h1>
        <p className="mt-2 text-slate-500">The page you are looking for doesn't exist or has been moved.</p>
      </div>
      <Link
        to="/"
        className="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white transition duration-150 hover:bg-indigo-700"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
          <path d="M9 22V12h6v10" />
        </svg>
        {t('common.home')}
      </Link>
    </div>
  )
}