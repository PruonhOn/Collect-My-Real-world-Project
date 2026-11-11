import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import api from '../api/client'
import { useI18n } from '../i18n'

const features = [
  {
    key: 'free',
    value: 'Free books',
    descKey: 'feature.freeDesc',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83" />
      </svg>
    ),
  },
  {
    key: 'paid',
    value: 'Premium books',
    descKey: 'feature.paidDesc',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M4 19.5A2.5 2.5 0 016.5 17H20" />
        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" />
      </svg>
    ),
  },
  {
    key: 'multilingual',
    value: '4 languages',
    descKey: 'feature.multilingualDesc',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="10" />
        <path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z" />
      </svg>
    ),
  },
]

const featureTitle = { free: 'feature.freeTitle', paid: 'feature.paidTitle', multilingual: 'feature.multilingualTitle' }

export default function Home() {
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
        { value: `${stats.languages}+`, label: t('home.languages') },
        { value: stats.books.toLocaleString(), label: t('home.books') },
        { value: '100%', label: t('home.digital') },
        { value: '24/7', label: t('home.access') },
      ]
    : [
        { value: '4+', label: t('home.languages') },
        { value: '—', label: t('home.books') },
        { value: '100%', label: t('home.digital') },
        { value: '24/7', label: t('home.access') },
      ]

  return (
    <div>
      <section className="hero-radial border-b border-slate-200/70 bg-white">
        <div className="mx-auto max-w-7xl px-4 py-24 text-center sm:px-6 sm:py-28">
          <span className="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-4 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <circle cx="12" cy="12" r="10" />
              <path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z" />
            </svg>
            {t('common.languages')} · Khmer · English · 中文 · Français
          </span>

          <h1 className="mx-auto mt-6 max-w-3xl text-5xl font-extrabold leading-[1.05] tracking-tight text-slate-900 sm:text-6xl">
            {t('hero.title')}
          </h1>
          <p className="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-600">{t('hero.subtitle')}</p>
          <div className="mt-10 flex flex-wrap justify-center gap-3">
            <Link to="/books" className="btn-primary px-8 py-3.5 text-base">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M4 19.5A2.5 2.5 0 016.5 17H20" />
                <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" />
              </svg>
              {t('hero.cta')}
            </Link>
            <Link to="/pricing" className="btn-secondary px-8 py-3.5 text-base">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <path d="M20 6L9 17l-5-5" />
              </svg>
              {t('common.vip')}
            </Link>
          </div>

          <div className="mx-auto mt-16 grid max-w-3xl grid-cols-2 gap-6 sm:grid-cols-4 sm:gap-4">
            {displayStats.map((s) => (
              <div
                key={s.label}
                className="card card-hover flex flex-col items-center px-4 py-6"
              >
                <p className="text-3xl font-extrabold tracking-tight text-indigo-600">{s.value}</p>
                <p className="mt-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase">{s.label}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-20 sm:px-6">
        <div className="mx-auto max-w-2xl text-center">
          <p className="section-eyebrow">{t('home.whyTitle')}</p>
          <h2 className="section-title">{t('home.featuresTitle')}</h2>
          <p className="section-subtitle mx-auto">{t('home.featuresSubtitle')}</p>
        </div>

        <div className="mt-14 grid gap-6 md:grid-cols-3">
          {features.map((f) => (
            <div key={f.key} className="card card-hover group flex flex-col items-start p-8 text-left">
              <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white shadow-lg shadow-indigo-500/25 transition group-hover:scale-105">
                {f.icon}
              </div>
              <h3 className="mt-6 text-lg font-bold text-slate-900">{t(featureTitle[f.key])}</h3>
              <p className="mt-2 text-sm leading-relaxed text-slate-500">{t(f.descKey)}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 pb-24 sm:px-6">
        <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-indigo-600 to-violet-700 px-6 py-16 text-center shadow-2xl shadow-indigo-600/20 sm:px-12">
          <div className="pointer-events-none absolute -top-24 -right-24 h-64 w-64 rounded-full bg-white/10 blur-2xl" />
          <div className="pointer-events-none absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-white/10 blur-2xl" />
          <h2 className="relative text-4xl font-extrabold tracking-tight text-white sm:text-5xl">{t('home.ctaTitle')}</h2>
          <p className="relative mx-auto mt-4 max-w-xl text-base text-indigo-100">
            {t('home.ctaDesc')}
          </p>
          <div className="relative mt-9 flex flex-wrap justify-center gap-3">
            <Link
              to="/books"
              className="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-8 py-3.5 text-base font-semibold text-indigo-700 shadow-lg transition hover:bg-indigo-50"
            >
              {t('hero.cta')}
            </Link>
            <Link
              to="/pricing"
              className="inline-flex items-center justify-center gap-2 rounded-xl border border-white/40 px-8 py-3.5 text-base font-semibold text-white backdrop-blur-sm transition hover:bg-white/10"
            >
              {t('common.vip')}
            </Link>
          </div>
        </div>
      </section>
    </div>
  )
}
