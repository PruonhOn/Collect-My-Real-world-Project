import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { getBooks, getLibrary, coverUrl } from '../../api'
import { useAuth } from '../../context/AuthContext'
import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'
import BookGrid from '../../components/BookGrid'
import CrownIcon from '../../components/membership/CrownIcon'
import VipBenefits from '../../components/membership/VipBenefits'
import VipExpiryNote from '../../components/membership/VipExpiryNote'

const QUICK_LINKS = [
  { key: 'continueReading', to: '#continue-reading', icon: 'resume', hash: true },
  { key: 'myLibrary', to: '/my-library', icon: 'library' },
  { key: 'newArrival', to: '/new-arrivals', icon: 'sparkle' },
  { key: 'bookCategories', to: '/categories', icon: 'grid' },
]

function QuickIcon({ name, className = '' }) {
  const paths = {
    resume: 'M8 5v14l11-7z',
    library: 'M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 016.5 22H20V2H6.5A2.5 2.5 0 004 4.5v15z',
    sparkle: 'M12 3l1.9 5.7L19.6 10l-5.7 1.9L12 17.6l-1.9-5.7L4.4 10l5.7-1.9L12 3z',
    grid: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
  }
  return (
    <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d={paths[name]} />
    </svg>
  )
}

function ContinueReadingCard({ item }) {
  const { t } = useLanguage()
  const progress = Math.min(Math.round(Number(item.progress || 0)), 99)
  return (
    <Link
      to={`/reader/${item.id}`}
      className="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-3.5 shadow-sm transition-all duration-200 hover:border-amber-200 hover:shadow-md hover:-translate-y-0.5"
    >
      {coverUrl(item) ? (
        <img
          src={coverUrl(item)}
          alt=""
          className="w-14 h-[4.75rem] object-contain bg-gray-50 p-1 rounded-lg shrink-0 ring-1 ring-gray-100"
        />
      ) : (
        <div className="w-14 h-[4.75rem] rounded-lg bg-gradient-to-br from-indigo-950 via-indigo-900 to-indigo-950 shrink-0 ring-1 ring-amber-300/40" />
      )}
      <div className="flex-1 min-w-0">
        <div className="font-semibold text-gray-900 truncate group-hover:text-indigo-700 transition-colors">
          {item.title}
        </div>
        {item.author && <div className="text-xs text-gray-500 mt-0.5 truncate">{item.author}</div>}
        <div className="mt-2.5 flex items-center gap-2">
          <div className="bg-gray-100 rounded-full h-1.5 flex-1 overflow-hidden">
            <div className="h-1.5 rounded-full bg-gradient-to-r from-amber-400 to-amber-500" style={{ width: `${progress}%` }} />
          </div>
          <span className="text-[10px] font-semibold text-amber-700 w-8 text-right">{progress}%</span>
        </div>
      </div>
      <span className="shrink-0 text-xs font-bold px-3 py-1.5 rounded-full bg-indigo-950 text-amber-300 group-hover:bg-indigo-900 transition-colors">
        {t('resumeReading')}
      </span>
    </Link>
  )
}

/**
 * The VIP member's home on /vip: a navy-and-gold welcome with the real
 * membership dates, quick links to the reading surfaces the platform already
 * has, the books currently in progress, and the popular VIP catalogue.
 *
 * Everything is data the API already serves — `GET /me` for the membership,
 * `GET /library` for reading progress, `GET /books?vip=1` for the catalogue.
 * Access itself is still decided by the backend on every read.
 */
export default function VipDashboard() {
  const { t } = useLanguage()
  const { user } = useAuth()
  const { isTestVip, vipUntil, daysUntilExpiry } = useMembership()

  const { data: libraryData } = useQuery({
    queryKey: ['my-library'],
    refetchInterval: 15000,
    queryFn: () => getLibrary(),
  })

  const library = libraryData?.data
  const continueReading = [
    ...(library?.owned || []),
    ...(library?.vip || []),
    ...(library?.shared || []),
  ]
    .filter((item) => Number(item.progress) > 0 && Number(item.progress) < 100)
    .slice(0, 4)

  const { data: vipBooksData, isLoading: vipBooksLoading } = useQuery({
    queryKey: ['books', 'vip-dashboard', 'popular'],
    refetchInterval: 15000,
    queryFn: () => getBooks({ vip: '1', sort: 'popular', per_page: 8 }),
  })
  const vipBooks = vipBooksData?.data?.data || []

  return (
    <div className="space-y-10">
      {/* Welcome — navy-and-gold, with the real dates from /me */}
      <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-indigo-900 to-indigo-950 border border-amber-400/30 shadow-xl shadow-[0_0_30px_rgba(212,175,55,0.2)]">
        <div className="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-amber-400/10 blur-3xl" aria-hidden="true" />
        <div className="relative px-6 py-8 sm:px-10 sm:py-10 flex flex-wrap items-center justify-between gap-6">
          <div className="min-w-0">
            <div className="inline-flex items-center gap-2 rounded-full bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white px-3 py-1 text-xs font-bold uppercase tracking-wider shadow-[0_0_15px_rgba(212,175,55,0.3)] border border-amber-400/30 backdrop-blur-md">
              <CrownIcon className="w-3.5 h-3.5" />
              {t('vipMember')}
              {isTestVip && (
                <span className="text-[9px] font-bold bg-[#B38728] text-[#FCF6BA] px-1.5 py-0.5 rounded">
                  {t('testOnly')}
                </span>
              )}
            </div>
            <h1 className="mt-3 text-2xl sm:text-3xl font-bold text-white">
              {t('vipWelcomeBack')}, {user?.name}
            </h1>
            {vipUntil && (
              <p className="mt-2 text-sm text-amber-200/90 flex flex-wrap items-center gap-x-2">
                <VipExpiryNote />
                {daysUntilExpiry !== null && (
                  <span className="text-amber-200/80">
                    ({daysUntilExpiry} {t('days')})
                  </span>
                )}
              </p>
            )}
          </div>
          <a
            href="#renew"
            className="inline-flex items-center gap-2 h-11 px-5 rounded-xl bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white text-sm font-bold shadow-[0_0_15px_rgba(212,175,55,0.3)] border border-amber-400/40 hover:shadow-[0_0_25px_rgba(212,175,55,0.5)] hover:from-[#d4a737] hover:to-[#c49a30] hover:-translate-y-0.5 active:scale-95 transition-all duration-200"
          >
            <CrownIcon className="w-4 h-4" />
            {t('vipRenew')}
          </a>
        </div>

        {/* Quick access to the reading surfaces */}
        <div className="relative grid grid-cols-2 lg:grid-cols-4 gap-px bg-white/10 border-t border-white/10">
          {QUICK_LINKS.map((link) => {
            const content = (
              <>
                <span className="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 text-amber-300 shrink-0">
                  <QuickIcon name={link.icon} className="w-5 h-5" />
                </span>
                <span className="text-sm font-semibold text-white text-left leading-snug">{t(link.key)}</span>
              </>
            )
            return link.hash ? (
              <a
                key={link.key}
                href={link.to}
                className="flex items-center gap-3 px-5 py-4 transition-colors duration-200 hover:bg-white/[0.07]"
              >
                {content}
              </a>
            ) : (
              <Link
                key={link.key}
                to={link.to}
                className="flex items-center gap-3 px-5 py-4 transition-colors duration-200 hover:bg-white/[0.07]"
              >
                {content}
              </Link>
            )
          })}
        </div>
      </div>

      {/* Continue reading — real progress from the library endpoint */}
      <section id="continue-reading" className="scroll-mt-28">
        <div className="flex items-end justify-between gap-3 mb-4">
          <h2 className="text-lg font-bold text-gray-900">{t('resumeReading')}</h2>
          <Link to="/my-library" className="text-sm font-medium text-indigo-600 hover:text-indigo-700">
            {t('allBooks')} →
          </Link>
        </div>
        {continueReading.length > 0 ? (
          <div className="grid sm:grid-cols-2 gap-3">
            {continueReading.map((item) => (
              <ContinueReadingCard key={`${item.source}-${item.id}`} item={item} />
            ))}
          </div>
        ) : (
          <div className="rounded-2xl border border-dashed border-gray-200 bg-white/60 px-6 py-10 text-center">
            <p className="text-gray-500 text-sm">{t('noBooksReadYet')}</p>
            <Link
              to="/books"
              className="mt-3 inline-flex items-center h-10 px-5 rounded-xl bg-indigo-950 text-amber-300 text-sm font-bold hover:bg-indigo-900 transition-colors"
            >
              {t('startReading')}
            </Link>
          </div>
        )}
      </section>

      {/* Premium catalogue — VIP-accessible books, normal BookCards */}
      <section>
        <div className="flex items-end justify-between gap-3 mb-4">
          <h2 className="flex items-center gap-2 text-lg font-bold text-gray-900">
            <CrownIcon className="w-4.5 h-4.5 text-amber-500" />
            {t('premiumBooks')}
          </h2>
          <Link to="/books?vip=1" className="text-sm font-medium text-indigo-600 hover:text-indigo-700">
            {t('allBooks')} →
          </Link>
        </div>
        <BookGrid books={vipBooks} loading={vipBooksLoading} />
      </section>

      {/* Compact benefits — only what the backend actually grants */}
      <section className="grid lg:grid-cols-2 gap-6">
        <div className="rounded-2xl border border-amber-400/30 bg-gradient-to-br from-white via-amber-50/40 to-white p-6 shadow-[0_0_15px_rgba(212,175,55,0.15)] backdrop-blur-md">
          <VipBenefits showTitle />
        </div>
        <div className="rounded-2xl border border-amber-400/30 bg-gradient-to-br from-indigo-950 via-indigo-900 to-indigo-950 p-6 text-white shadow-[0_0_20px_rgba(212,175,55,0.15)] backdrop-blur-md">
          <h3 className="flex items-center gap-2 text-sm font-bold text-amber-200 mb-2">
            <CrownIcon className="w-4 h-4" />
            {t('renewMembership')}
          </h3>
          <p className="text-sm text-amber-100/90 leading-relaxed">
            {t('vipBenefits')}
          </p>
          <a
            href="#renew"
            className="mt-4 inline-flex items-center gap-2 h-10 px-5 rounded-xl bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white text-sm font-bold shadow-[0_0_15px_rgba(212,175,55,0.3)] border border-amber-400/40 hover:shadow-[0_0_25px_rgba(212,175,55,0.5)] hover:from-[#d4a737] hover:to-[#c49a30] hover:-translate-y-0.5 active:scale-95 transition-all duration-200"
          >
            <CrownIcon className="w-3.5 h-3.5" />
            {t('renewMembership')}
            <span aria-hidden="true">↓</span>
          </a>
        </div>
      </section>
    </div>
  )
}
