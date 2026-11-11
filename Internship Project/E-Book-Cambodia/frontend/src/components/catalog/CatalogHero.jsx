import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { getBooks, coverUrl } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

const NEW_WINDOW_MS = 60 * 24 * 60 * 60 * 1000
const NEW_BOOK_CUTOFF = Date.now() - NEW_WINDOW_MS
const PLACEHOLDERS = [
  'from-indigo-500 to-purple-600',
  'from-blue-500 to-cyan-600',
  'from-emerald-500 to-teal-600',
  'from-rose-500 to-pink-600',
]

function isNewBook(book) {
  return !!book.created_at && new Date(book.created_at).getTime() > NEW_BOOK_CUTOFF
}

function HeroCover({ book, rotate, className, float, chipClass, linkClass }) {
  const { t } = useLanguage()
  const gradient = PLACEHOLDERS[book.id % PLACEHOLDERS.length]
  const views = Number(book.view_count || 0).toLocaleString()
  const viewLabel = `${views} ${t('views')}`
  let chip = null
  if (book.is_vip_only) chip = t('vipAccess')
  else if (book.is_free) chip = t('free')
  else if (isNewBook(book)) chip = t('brandNew')

  return (
    <div className={`absolute ${float} ${className}`}>
      <Link
        to={`/books/${book.id}`}
        className={`relative block w-36 sm:w-40 aspect-[3/4] rounded-xl overflow-hidden shadow-xl ring-1 ring-black/5 hover:ring-indigo-300 transition-all ${rotate} ${linkClass || ''}`}
      >
        {coverUrl(book) ? (
          <img src={coverUrl(book)} alt={book.title} loading="lazy" className="w-full h-full object-contain bg-white p-1.5" />
        ) : (
          <div className={`w-full h-full bg-gradient-to-br ${gradient} flex items-center justify-center p-3`}>
            <div className="text-white text-xs font-bold text-center font-serif line-clamp-5">{book.title}</div>
          </div>
        )}
        <div className="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/60 to-transparent pointer-events-none" />
        <span
          className="absolute bottom-2 left-2 inline-flex items-center gap-1 rounded-full bg-black/50 px-1.5 py-0.5 text-[9px] font-semibold text-white"
          title={viewLabel}
          aria-label={viewLabel}
        >
          <svg className="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          {views}
        </span>
      </Link>
      {chip && (
        <span className={`absolute -bottom-2 left-1/2 -translate-x-1/2 text-[10px] font-bold px-2.5 py-1 rounded-full shadow ${chipClass}`}>
          {chip}
        </span>
      )}
    </div>
  )
}

export default function CatalogHero({ stats }) {
  const { t } = useLanguage()

  const { data } = useQuery({
    queryKey: ['books', 'catalog-hero'],
    refetchInterval: 60000,
    queryFn: () => getBooks({ sort: 'popular', per_page: 5 }),
  })
  const books = data?.data?.data || []

  const handleStartReading = () => {
    document.getElementById('library')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  const handleSearch = () => {
    document.getElementById('catalog-search')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    window.setTimeout(() => document.getElementById('catalog-search-input')?.focus(), 500)
  }

  const trust = []
  if (stats?.books) trust.push(`${stats.books}+ ${t('statBooks')}`)
  if (stats?.categories) trust.push(`${stats.categories} ${t('statCategories')}`)
  if (stats?.languagesCount) trust.push(`${stats.languagesCount} ${t('statLanguages')}`)

  const heroBook = books[0]
  const leftBook = books[1] || heroBook
  const rightBook = books[2] || heroBook

  return (
    <section className="relative overflow-hidden bg-gradient-to-br from-indigo-50 via-white to-purple-50">
      <div className="absolute -top-24 -right-24 w-96 h-96 bg-indigo-200/40 rounded-full blur-3xl pointer-events-none" aria-hidden="true" />
      <div className="absolute -bottom-32 -left-24 w-96 h-96 bg-amber-100/50 rounded-full blur-3xl pointer-events-none" aria-hidden="true" />

      <div className="relative max-w-[78rem] mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-6 items-center">
          <div className="max-w-xl">
            <span className="inline-flex items-center gap-2 text-xs font-semibold text-indigo-700 bg-white/80 border border-indigo-100 rounded-full px-3.5 py-1.5 shadow-sm">
              <span aria-hidden="true">🇰🇭</span>
              {t('heroChip')}
            </span>

            <h1 className="mt-4 text-3xl sm:text-4xl xl:text-[2.75rem] font-extrabold text-gray-900 leading-[1.25]">
              {t('catalogHeroTitle1')}{' '}
              <span className="text-indigo-600">{t('catalogHeroTitle2')}</span>
            </h1>

            <p className="mt-4 text-base sm:text-lg text-gray-600 leading-relaxed">
              {t('catalogHeroSubtitle')}
            </p>

            <div className="mt-7 flex flex-wrap gap-3">
              <button
                type="button"
                onClick={handleStartReading}
                className="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/20 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                {t('startReading')}
              </button>
              <button
                type="button"
                onClick={handleSearch}
                className="inline-flex items-center gap-2 px-6 py-3 bg-white text-gray-800 font-semibold rounded-xl border border-gray-200 shadow-sm hover:border-indigo-300 hover:text-indigo-700 hover:-translate-y-0.5 transition-all"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                {t('exploreBooks')}
              </button>
            </div>

            {trust.length > 0 && (
              <div className="mt-8 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-500">
                {trust.map((item, i) => (
                  <span key={i} className="inline-flex items-center gap-1.5 font-medium">
                    <span className="w-1.5 h-1.5 rounded-full bg-indigo-400" aria-hidden="true" />
                    {item}
                  </span>
                ))}
              </div>
            )}
          </div>

          {heroBook && (
            <div className="relative h-80 sm:h-96 lg:h-[420px] max-w-md mx-auto w-full">
              <div className="absolute top-6 right-6 w-56 h-56 sm:w-64 sm:h-64 bg-gradient-to-br from-indigo-300/50 to-purple-300/50 rounded-full blur-2xl pointer-events-none" aria-hidden="true" />

              <HeroCover
                book={leftBook}
                rotate="-rotate-6"
                className="left-0 top-12 z-0"
                float="animate-float-slower"
                chipClass="bg-emerald-500 text-white"
              />

<HeroCover
                book={heroBook}
                rotate="rotate-2"
                className="left-1/2 -translate-x-1/2 top-0 z-10"
                linkClass="w-40 sm:w-48 shadow-2xl ring-2 ring-white/60"
                float="animate-float-slow"
                chipClass="bg-rose-500 text-white"
              />

              <HeroCover
                book={rightBook}
                rotate="rotate-6"
                className="right-0 top-14 z-0"
                float="animate-float-slower"
                chipClass="bg-amber-400 text-amber-900"
              />

              <span className="absolute left-0 bottom-0 z-20 inline-flex items-center gap-1.5 text-[11px] font-bold text-indigo-700 bg-white rounded-full px-3 py-1.5 shadow-md border border-indigo-100">
                📚 {t('trendingBooks')}
              </span>
              <span className="absolute right-0 -top-1 z-20 inline-flex items-center gap-1.5 text-[11px] font-bold text-rose-600 bg-white rounded-full px-3 py-1.5 shadow-md border border-rose-100">
                🎁 {stats?.freeBooks ? `${stats.freeBooks} ${t('freeBooks')}` : t('learnAnytime')}
              </span>
            </div>
          )}
        </div>
      </div>
    </section>
  )
}