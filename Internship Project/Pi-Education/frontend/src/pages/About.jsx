import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import api from '../api/client'
import { useI18n } from '../i18n'

const values = [
  {
    titleKey: 'about.freeTitle',
    descKey: 'about.freeDesc',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" />
      </svg>
    ),
  },
  {
    titleKey: 'about.multilingualTitle',
    descKey: 'about.multilingualDesc',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="10" />
        <path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z" />
      </svg>
    ),
  },
  {
    titleKey: 'about.progressTitle',
    descKey: 'about.progressDesc',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M22 11.08V12a10 10 0 11-5.93-9.14" />
        <path d="M22 4L12 14.01l-3-3" />
      </svg>
    ),
  },
]

export default function About() {
  const { t } = useI18n()

  const { data: stats } = useQuery({
    queryKey: ['public-stats'],
    queryFn: async () => {
      const res = await api.get('/stats')
      return res.data.data
    },
    staleTime: 5 * 60 * 1000,
  })

  const displayStats = stats
    ? [
        { value: `${stats.languages}+`, label: t('about.langLabel') },
        { value: stats.books.toLocaleString(), label: t('about.booksLabel') },
        { value: '100%', label: t('about.digitalLabel') },
        { value: '24/7', label: t('about.accessLabel') },
      ]
    : [
        { value: '4+', label: t('about.langLabel') },
        { value: '—', label: t('about.booksLabel') },
        { value: '100%', label: t('about.digitalLabel') },
        { value: '24/7', label: t('about.accessLabel') },
      ]

  return (
    <div>
      <section className="hero-radial border-b border-slate-200/70 bg-white">
        <div className="mx-auto max-w-7xl px-4 py-20 text-center sm:px-6">
          <p className="section-eyebrow">{t('common.about')}</p>
          <h1 className="mx-auto mt-3 max-w-3xl text-5xl font-extrabold leading-[1.05] tracking-tight text-slate-900 sm:text-6xl">
            {t('about.heroTitle')}
          </h1>
          <p className="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">
            {t('about.heroSubtitle')}
          </p>

          <div className="mx-auto mt-14 grid max-w-3xl grid-cols-2 gap-6 sm:grid-cols-4 sm:gap-4">
            {displayStats.map((s) => (
              <div key={s.label} className="card card-hover flex flex-col items-center px-4 py-6">
                <p className="text-3xl font-extrabold tracking-tight text-indigo-600">{s.value}</p>
                <p className="mt-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase">{s.label}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-20 sm:px-6">
        <div className="grid gap-10 lg:grid-cols-2 lg:items-center">
          <div>
            <p className="section-eyebrow">{t('about.missionEyebrow')}</p>
            <h2 className="section-title">{t('about.missionTitle')}</h2>
            <p className="section-subtitle mt-4">
              {t('about.missionDesc')}
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <Link to="/books" className="btn-primary px-6 py-3">
                {t('hero.cta')}
              </Link>
              <Link to="/pricing" className="btn-secondary px-6 py-3">
                {t('common.vip')}
              </Link>
            </div>
          </div>

          <div className="space-y-4">
            {values.map((v) => (
              <div key={v.titleKey} className="card card-hover flex items-start gap-5 p-6">
                <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white shadow-lg shadow-indigo-500/20">
                  {v.icon}
                </div>
                <div>
                  <h3 className="font-bold text-slate-900">{t(v.titleKey)}</h3>
                  <p className="mt-1 text-sm leading-relaxed text-slate-500">{t(v.descKey)}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 pb-24 sm:px-6">
        <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-600 to-violet-700 px-6 py-16 text-center shadow-2xl shadow-indigo-600/20 sm:px-12">
          <div className="pointer-events-none absolute -top-24 -right-24 h-64 w-64 rounded-full bg-white/10 blur-2xl" />
          <div className="pointer-events-none absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-white/10 blur-2xl" />
          <h2 className="relative text-4xl font-extrabold tracking-tight text-white">{t('about.ctaTitle')}</h2>
          <p className="relative mx-auto mt-4 max-w-xl text-base text-indigo-100">
            {t('about.ctaDesc')}
          </p>
          <div className="relative mt-8 flex flex-wrap justify-center gap-3">
            <Link
              to="/books"
              className="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-indigo-700 shadow-lg transition hover:bg-indigo-50"
            >
              {t('hero.cta')}
            </Link>
          </div>
        </div>
      </section>
    </div>
  )
}
