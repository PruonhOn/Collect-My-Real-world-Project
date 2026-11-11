import { useQuery } from '@tanstack/react-query'
import { useEffect, useState } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { getBooks, getCategories, getStats, coverUrl } from '../../api'
import BookSlider from '../../components/BookSlider'
import FreeBooksSection from '../../components/catalog/FreeBooksSection'
import VipPromotion from '../../components/catalog/VipPromotion'
import VipBookBadge from '../../components/membership/VipBookBadge'
import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'
import { getBookAccess } from '../../utils/membership'
import LockIcon from '../../components/membership/LockIcon'
import { categoryLink, isMainCategory } from '../../utils/grades'

const NEW_BOOK_CUTOFF = Date.now() - 60 * 24 * 60 * 60 * 1000

const PLACEHOLDER_COLORS = [
  'from-indigo-500 to-purple-600',
  'from-blue-500 to-cyan-600',
  'from-emerald-500 to-teal-600',
  'from-rose-500 to-pink-600',
  'from-amber-500 to-orange-600',
  'from-sky-500 to-indigo-600',
]

const KEYWORD_EMOJIS = [
  ['literature', '📖'],
  ['education', '🎓'],
  ['learn', '📚'],
  ['history', '🏛️'],
  ['technology', '💻'],
  ['business', '💼'],
  ['health', '💊'],
  ['math', '🔢'],
  ['mathematics', '🔢'],
  ['science', '🔬'],
  ['art', '🎨'],
  ['language', '🗣️'],
]

const CATEGORY_TINTS = [
  'bg-indigo-50 text-indigo-600 ring-indigo-100',
  'bg-emerald-50 text-emerald-600 ring-emerald-100',
  'bg-amber-50 text-amber-600 ring-amber-100',
  'bg-sky-50 text-sky-600 ring-sky-100',
  'bg-rose-50 text-rose-600 ring-rose-100',
  'bg-violet-50 text-violet-600 ring-violet-100',
  'bg-teal-50 text-teal-600 ring-teal-100',
]

function gradientFor(book) {
  return PLACEHOLDER_COLORS[Number(book.id) % PLACEHOLDER_COLORS.length]
}

function categoryEmoji(name = '') {
  const lower = name.toLowerCase()
  const found = KEYWORD_EMOJIS.find(([kw]) => lower.includes(kw))
  return found ? found[1] : '📚'
}

function isNewBook(book) {
  return !!book.created_at && new Date(book.created_at).getTime() > NEW_BOOK_CUTOFF
}

function CoverImage({ book, gradient }) {
  const src = coverUrl(book)
  if (src) {
    return <img src={src} alt={book.title} loading="lazy" className="w-full h-full object-contain bg-white p-1.5" />
  }
  return (
    <div className={`w-full h-full bg-gradient-to-br ${gradient} flex items-center justify-center p-3`}>
      <div className="text-white font-bold text-center text-xs leading-snug font-serif line-clamp-4">{book.title}</div>
    </div>
  )
}

function HeroViewCount({ book, className = '' }) {
  const { t } = useLanguage()
  const views = Number(book.view_count || 0).toLocaleString()
  const label = `${views} ${t('views')}`

  return (
    <span
      className={`inline-flex items-center gap-1 rounded-full bg-black/50 px-1.5 py-0.5 text-[9px] font-semibold text-white ${className}`}
      title={label}
      aria-label={label}
    >
      <svg className="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
      </svg>
      {views}
    </span>
  )
}

function StatusBadge({ book, small = false }) {
  const { t } = useLanguage()
  const { isVip } = useMembership()
  const access = getBookAccess(book, { isVip })
  const base = `inline-flex items-center gap-1 font-bold rounded-md shadow-sm ${
    small ? 'text-[8px] px-1.5 py-0.5' : 'text-[10px] px-2 py-0.5'
  }`
  if (book.is_vip_only) {
    return <VipBookBadge size={small ? 'xs' : 'sm'} />
  }
  if (book.is_free) {
    return <span className={`${base} bg-emerald-500 text-white`}>🆓 {t('free')}</span>
  }
  return (
    <span className={`${base} bg-white/95 text-indigo-700`}>
      {access.state === 'premium' && <LockIcon className="w-3 h-3" />}
      ${Number(book.price || 0).toFixed(2)}
    </span>
  )
}

function NewBadge({ small = false }) {
  const { t } = useLanguage()
  const base = `inline-flex items-center gap-1 font-bold rounded-md shadow-sm ${
    small ? 'text-[8px] px-1.5 py-0.5' : 'text-[10px] px-2 py-0.5'
  }`
  return <span className={`${base} bg-rose-500 text-white`}>🆕 {t('brandNew')}</span>
}

function SectionHeader({ emoji, title, sub, to }) {
  const { t } = useLanguage()
  return (
    <div className="flex items-end justify-between gap-4 mb-6">
      <div className="min-w-0">
        <h2 className="text-xl sm:text-2xl font-bold text-gray-900">
          {emoji ? `${emoji} ` : ''}
          {title}
        </h2>
        {sub && <p className="mt-1.5 text-sm text-gray-500">{sub}</p>}
      </div>
      {to && (
        <Link
          to={to}
          className="hidden sm:inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700 shrink-0"
        >
          {t('viewAll')} <span aria-hidden="true">→</span>
        </Link>
      )}
    </div>
  )
}

function MainCover({ book }) {
  const { t } = useLanguage()
  const gradient = gradientFor(book)
  return (
    <Link
      to={`/books/${book.id}`}
      className="group relative block w-40 sm:w-48 rounded-2xl overflow-hidden shadow-2xl ring-4 ring-white -rotate-2 hover:rotate-0 transition-transform z-10"
    >
      <div className={`relative aspect-[3/4] bg-gradient-to-br ${gradient}`}>
        <CoverImage book={book} gradient={gradient} />
        <div className="absolute top-2 left-2 flex flex-col items-start gap-1">
          <StatusBadge book={book} />
          {isNewBook(book) && <NewBadge small />}
        </div>
        <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
        <div className="absolute inset-x-0 bottom-0 p-3">
          <h3 className="text-white font-bold text-sm leading-snug line-clamp-2">{book.title}</h3>
          {book.author && <p className="text-white/80 text-[11px] mt-0.5 truncate">{book.author}</p>}
          <HeroViewCount book={book} className="mt-1.5" />
        </div>
        <div className="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
          <span className="bg-white/95 text-indigo-700 text-xs font-semibold px-3 py-1.5 rounded-full shadow">
            {t('readPreview')}
          </span>
        </div>
      </div>
    </Link>
  )
}

function SideCover({ book, className = '' }) {
  const gradient = gradientFor(book)
  return (
    <Link
      to={`/books/${book.id}`}
      className={`group relative block w-24 sm:w-32 rounded-xl overflow-hidden shadow-lg ring-2 ring-white hover:ring-indigo-300 transition-all hover:-translate-y-0.5 ${className}`}
    >
      <div className={`relative aspect-[3/4] bg-gradient-to-br ${gradient}`}>
        <CoverImage book={book} gradient={gradient} />
        <div className="absolute top-1.5 left-1.5">
          <StatusBadge book={book} small />
        </div>
        {isNewBook(book) && (
          <div className="absolute top-1.5 right-1.5">
            <NewBadge small />
          </div>
        )}
        <HeroViewCount book={book} className="absolute bottom-1.5 left-1.5" />
      </div>
    </Link>
  )
}

function TinyCover({ book }) {
  const gradient = gradientFor(book)
  return (
    <Link
      to={`/books/${book.id}`}
      className="block w-16 sm:w-20 rounded-lg overflow-hidden shadow ring-2 ring-white hover:ring-indigo-300 transition-all hover:-translate-y-0.5"
    >
      <div className={`relative aspect-[3/4] bg-gradient-to-br ${gradient}`}>
        <CoverImage book={book} gradient={gradient} />
        <HeroViewCount book={book} className="absolute bottom-1 left-1" />
      </div>
    </Link>
  )
}

function HeroSkeleton() {
  return (
    <div className="relative max-w-md mx-auto w-full">
      <div className="flex items-end justify-center">
        <div className="w-24 sm:w-32 animate-pulse rounded-xl bg-gray-200 aspect-[3/4] -mr-6" />
        <div className="w-40 sm:w-48 animate-pulse rounded-2xl bg-gray-200 aspect-[3/4] shadow-2xl" />
        <div className="w-24 sm:w-32 animate-pulse rounded-xl bg-gray-200 aspect-[3/4] -ml-6" />
      </div>
      <div className="mt-4 flex justify-center gap-3">
        <div className="w-16 sm:w-20 animate-pulse rounded-lg bg-gray-200 aspect-[3/4]" />
        <div className="w-16 sm:w-20 animate-pulse rounded-lg bg-gray-200 aspect-[3/4]" />
        <div className="w-16 sm:w-20 animate-pulse rounded-lg bg-gray-200 aspect-[3/4]" />
      </div>
    </div>
  )
}

const HERO_INTERVAL_MS = 3500
const HERO_DOTS_LIMIT = 8

function HeroBooks({ books = [], loading }) {
  const [index, setIndex] = useState(0)
  const [paused, setPaused] = useState(false)
  const n = books.length

  useEffect(() => {
    if (paused || n === 0) return
    const timer = setInterval(() => setIndex((i) => (i + 1) % n), HERO_INTERVAL_MS)
    return () => clearInterval(timer)
  }, [paused, n])

  if (loading) return <HeroSkeleton />
  if (n === 0) return null

  const at = (offset) => books[(index + offset + n) % n]

  const main = books[index]
  const left = at(-1)
  const right = at(1)
  const tiny = [at(2), at(3), at(4)]
  const dots = books.slice(0, HERO_DOTS_LIMIT)

  return (
    <div
      className="relative max-w-md mx-auto w-full"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
    >
      <div className="flex items-end justify-center">
        <div key={`hero-left-${index}`} className="hero-flow-side hero-flow-side-l">
          <SideCover book={left} className="rotate-6 -mr-6 sm:-mr-8" />
        </div>
        <div key={`hero-main-${index}`} className="relative z-10 hero-flow-main">
          <MainCover book={main} />
        </div>
        <div key={`hero-right-${index}`} className="hero-flow-side hero-flow-side-r">
          <SideCover book={right} className="-rotate-6 -ml-6 sm:-ml-8" />
        </div>
      </div>

      {tiny.length > 0 && (
        <div className="mt-4 flex justify-center gap-3">
          {tiny.map((book, i) => (
            <div key={`hero-tiny-${index}-${i}`} className="hero-flow-tiny">
              <TinyCover book={book} />
            </div>
          ))}
        </div>
      )}

      {dots.length > 1 && (
        <div className="mt-5 flex items-center justify-center gap-2">
          {dots.map((_, i) => (
            <button
              key={i}
              type="button"
              onClick={() => setIndex(i)}
              aria-label={`Go to slide ${i + 1}`}
              className={`rounded-full transition-all duration-300 ${
                i === index ? 'w-7 h-2.5 bg-indigo-600' : 'w-2.5 h-2.5 bg-gray-300 hover:bg-indigo-300'
              }`}
            />
          ))}
        </div>
      )}

      <style>{`
        @keyframes heroFlowMain {
          from { opacity: 0; transform: translateY(16px) scale(.94); }
          to   { opacity: 1; transform: none; }
        }
        @keyframes heroFlowSideL {
          from { opacity: 0; transform: translateX(-28px) rotate(-16deg); }
          to   { opacity: 1; transform: none; }
        }
        @keyframes heroFlowSideR {
          from { opacity: 0; transform: translateX(28px) rotate(16deg); }
          to   { opacity: 1; transform: none; }
        }
        @keyframes heroFlowTiny {
          from { opacity: 0; transform: translateY(12px); }
          to   { opacity: 1; transform: none; }
        }
        .hero-flow-main { animation: heroFlowMain 0.55s ease backwards; }
        .hero-flow-side-l { animation: heroFlowSideL 0.55s ease backwards; }
        .hero-flow-side-r { animation: heroFlowSideR 0.55s ease backwards; }
        .hero-flow-tiny { animation: heroFlowTiny 0.55s ease backwards; }
        @media (prefers-reduced-motion: reduce) {
          .hero-flow-main, .hero-flow-side-l, .hero-flow-side-r, .hero-flow-tiny { animation: none; }
        }
      `}</style>
    </div>
  )
}

function FeaturedCard({ book }) {
  const { t } = useLanguage()
  const gradient = gradientFor(book)
  return (
    <Link
      to={`/books/${book.id}`}
      className="group bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all flex flex-col"
    >
      <div className={`relative aspect-[3/4] overflow-hidden bg-gradient-to-br ${gradient}`}>
        <CoverImage book={book} gradient={gradient} />
        <div className="absolute top-2 left-2">
          <StatusBadge book={book} />
        </div>
        {isNewBook(book) && (
          <div className="absolute top-2 right-2">
            <NewBadge />
          </div>
        )}
      </div>

      <div className="p-3.5 flex flex-col flex-1">
        {(book.category?.display_name || book.category?.name) && (
          <p className="text-[10px] font-semibold text-indigo-500 uppercase tracking-wide truncate">
            {book.category.display_name || book.category.name}
          </p>
        )}
        <h3 className="mt-1 font-semibold text-gray-900 text-sm leading-snug line-clamp-2 group-hover:text-indigo-600 transition-colors">
          {book.title}
        </h3>
        {book.author && <p className="mt-1 text-[11px] text-gray-500 truncate">{book.author}</p>}
        <div className="mt-2 flex items-center gap-3 text-[11px] text-gray-500">
          {book.pages > 0 && <span>{book.pages} {t('pages')}</span>}
          <span className="inline-flex items-center gap-1">
            <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            {Number(book.view_count || 0).toLocaleString()}
          </span>
        </div>

        <div className="mt-auto pt-2.5 flex items-center justify-between gap-2">
          <span className="text-[11px] font-semibold text-emerald-600">
            {book.is_free
              ? t('free')
              : book.is_vip_only
                ? 'VIP'
                : `$${Number(book.price || 0).toFixed(2)}`}
          </span>
          <span className="inline-flex items-center gap-1 bg-indigo-50 text-indigo-600 text-[11px] font-semibold px-2.5 py-1.5 rounded-full group-hover:bg-indigo-600 group-hover:text-white transition-colors">
            <svg className="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM10 3a7 7 0 00-7 7c0 3.86 5 9 7 9s7-5.14 7-9a7 7 0 00-7-7z" />
            </svg>
            {book.is_free ? t('readNow') : t('readPreview')}
          </span>
        </div>
      </div>
    </Link>
  )
}

function FeaturedGridSkeleton() {
  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-5">
      {[...Array(5)].map((_, i) => (
        <div key={i} className="animate-pulse">
          <div className="aspect-[3/4] bg-gray-200 rounded-2xl" />
          <div className="h-3 bg-gray-200 rounded mt-2 w-1/2" />
          <div className="h-4 bg-gray-200 rounded mt-2 w-3/4" />
          <div className="h-3 bg-gray-200 rounded mt-1.5 w-1/3" />
        </div>
      ))}
    </div>
  )
}

function CategoryCard({ cat, tint, categories }) {
  const { t } = useLanguage()
  return (
    <Link
      to={categoryLink(categories, cat)}
      className="group flex items-center gap-3 sm:gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-3.5 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 hover:border-indigo-100 transition-all"
    >
      <span className={`flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-xl ring-1 shrink-0 text-xl sm:text-2xl ${tint}`}>
        {categoryEmoji(cat.name)}
      </span>
      <span className="min-w-0">
        <span className="block text-sm sm:text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
          {cat.display_name || cat.name}
        </span>
        <span className="block mt-0.5 text-[11px] sm:text-xs text-gray-500">
          📚 {cat.books_count ?? 0} {t('statBooks')}
        </span>
      </span>
      <span
        className="ml-auto hidden sm:inline-flex text-gray-300 group-hover:text-indigo-500 group-hover:translate-x-0.5 transition-all"
        aria-hidden="true"
      >
        →
      </span>
    </Link>
  )
}

function CategoryGridSkeleton() {
  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
      {[...Array(8)].map((_, i) => (
        <div key={i} className="h-20 bg-gray-200 rounded-2xl animate-pulse" />
      ))}
    </div>
  )
}

export default function HomePage() {
  const { t, locale } = useLanguage()
  const navigate = useNavigate()
  const { pathname, hash } = useLocation()
  const [query, setQuery] = useState('')

  useEffect(() => {
    if (!hash) {
      window.scrollTo({ top: 0 })
    }
  }, [pathname, hash])

  const { data: popularData, isLoading: popularLoading } = useQuery({
    queryKey: ['books', 'home-popular', locale],
    refetchInterval: 30000,
    queryFn: () => getBooks({ sort: 'popular', per_page: 16 }),
  })
  const popularBooks = popularData?.data?.data || []

  const { data: newData, isLoading: newLoading } = useQuery({
    queryKey: ['books', 'home-new', locale],
    refetchInterval: 30000,
    queryFn: () => getBooks({ sort: 'latest', per_page: 12 }),
  })
  const newBooks = newData?.data?.data || []

  const { data: categoriesData, isLoading: categoriesLoading } = useQuery({
    queryKey: ['categories', 'home', locale],
    refetchInterval: 30000,
    queryFn: () => getCategories({ locale }),
  })
  const categories = categoriesData?.data?.categories || []
  const mainCategories = categories.filter(isMainCategory)

  const { data: statsData } = useQuery({
    queryKey: ['platform-stats'],
    refetchInterval: 30000,
    queryFn: () => getStats(),
  })
  const stats = statsData?.data?.stats

  const homeBooksLoading = popularLoading || newLoading

  const heroBooks = (() => {
    const seen = new Set()
    const merged = []
    for (const book of [...newBooks, ...popularBooks]) {
      if (!seen.has(book.id)) {
        seen.add(book.id)
        merged.push(book)
      }
    }
    return merged.slice(0, 16)
  })()

  const featuredBooks = heroBooks.slice(0, 5)
  const carouselBooks = heroBooks.slice(5)

  const trust = []
  if (typeof stats?.books === 'number') trust.push(`${stats.books}+ ${t('statBooks')}`)
  if (typeof stats?.categories === 'number') trust.push(`${stats.categories} ${t('statCategories')}`)
  if (Array.isArray(stats?.languages) && stats.languages.length > 0) {
    trust.push(`${stats.languages.length} ${t('statLanguages')}`)
  }

  const submitSearch = (e) => {
    e.preventDefault()
    const value = query.trim()
    if (value) navigate(`/books?search=${encodeURIComponent(value)}`)
  }

  const scrollToFeatured = () => {
    document.getElementById('home-featured')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  return (
    <div>
      {/* ============ HERO: books first ============ */}
      <section className="relative overflow-hidden bg-white">
        <div className="absolute -top-24 -right-24 w-96 h-96 bg-indigo-100/60 rounded-full blur-3xl pointer-events-none" aria-hidden="true" />
        <div className="absolute -bottom-32 -left-24 w-96 h-96 bg-amber-100/40 rounded-full blur-3xl pointer-events-none" aria-hidden="true" />

        <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-8 items-center">
            <div className="max-w-xl min-w-0 order-2 lg:order-1">
              <span className="inline-flex items-center gap-2 text-xs font-semibold text-indigo-700 bg-indigo-50/80 border border-indigo-100 rounded-full px-3.5 py-1.5">
                <span aria-hidden="true">🇰🇭</span>
                {t('knowledgeForCambodia')}
              </span>

              <h1 className="mt-4 text-3xl sm:text-4xl font-extrabold text-gray-900 leading-[1.22]">
                {t('homeHeroTitle1')} <br />
                <span className="text-indigo-600">{t('homeHeroTitle2')}</span>
              </h1>

              <p className="mt-4 text-base sm:text-lg text-gray-600 leading-relaxed">{t('homeHeroSubtitle')}</p>

              <form onSubmit={submitSearch} className="mt-6">
                <div className="flex items-center gap-2 bg-white rounded-2xl border border-gray-200/80 p-1.5 pl-4 shadow-lg shadow-slate-200/40">
                  <svg className="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                  <input
                    type="text"
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder={t('homeSearchPlaceholder')}
                    className="w-full bg-transparent focus:outline-none text-gray-800 placeholder-gray-400 py-2.5 text-sm"
                    aria-label={t('search')}
                  />
                  <button
                    type="submit"
                    className="inline-flex items-center justify-center gap-2 rounded-xl font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors px-4 md:px-5 py-2.5 text-sm shrink-0"
                  >
                    {t('searchButton')}
                  </button>
                </div>
              </form>

              {mainCategories.length > 0 && (
                <div className="mt-3 flex items-center gap-2 overflow-x-auto scrollbar-hide py-0.5" aria-label={t('bookCategories')}>
                  {mainCategories.map((cat) => (
                    <Link
                      key={cat.id}
                      to={categoryLink(categories, cat)}
                      className="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-full px-3 py-1.5 shrink-0 whitespace-nowrap hover:border-indigo-300 hover:text-indigo-600 transition-colors"
                    >
                      <span aria-hidden="true">{categoryEmoji(cat.name)}</span>
                      {cat.display_name || cat.name}
                    </Link>
                  ))}
                </div>
              )}

              <div className="mt-7 flex flex-wrap gap-3">
                <button
                  type="button"
                  onClick={scrollToFeatured}
                  className="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/20 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                  {t('startReading')}
                </button>
                <Link
                  to="/books"
                  className="inline-flex items-center gap-2 px-6 py-3 bg-white text-gray-800 font-semibold rounded-xl border border-gray-200 shadow-sm hover:border-indigo-300 hover:text-indigo-700 hover:-translate-y-0.5 transition-all"
                >
                  {t('allBooks')}
                </Link>
              </div>

              {trust.length > 0 && (
                <div className="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-gray-500">
                  {trust.map((item, i) => (
                    <span key={i} className="inline-flex items-center gap-1.5 font-medium">
                      <span className="w-1.5 h-1.5 rounded-full bg-indigo-400" aria-hidden="true" />
                      {item}
                    </span>
                  ))}
                </div>
              )}
            </div>

            <div className="order-1 lg:order-2">
              <HeroBooks books={heroBooks} loading={homeBooksLoading} />
            </div>
          </div>
        </div>
      </section>

      {/* ============ FEATURED / HIGHLIGHT BOOKS ============ */}
      {(!homeBooksLoading && featuredBooks.length > 0) || homeBooksLoading ? (
        <section id="home-featured" className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 scroll-mt-20">
          <SectionHeader emoji="⭐" title={t('homeFeaturedTitle')} sub={t('homeFeaturedSub')} to="/books?sort=popular" />
          {homeBooksLoading ? (
            <FeaturedGridSkeleton />
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-5">
              {featuredBooks.map((book) => (
                <FeaturedCard key={book.id} book={book} />
              ))}
            </div>
          )}
        </section>
      ) : null}

      {/* ============ POPULAR BOOKS (carousel) ============ */}
      {homeBooksLoading || carouselBooks.length > 0 ? (
        <section id="home-popular" className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14">
          <SectionHeader emoji="🔥" title={t('trendingBooks')} sub={t('trendingSub')} to="/books?sort=popular" />
          <BookSlider books={carouselBooks} loading={homeBooksLoading} />
        </section>
      ) : null}

      {/* ============ NEW ARRIVAL BOOKS ============ */}
      {newLoading || newBooks.length > 0 ? (
        <section id="home-new" className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14">
          <SectionHeader emoji="🆕" title={t('newArrival')} sub={t('newArrivalSub')} to="/new-arrivals" />
          <BookSlider books={newBooks} loading={newLoading} />
        </section>
      ) : null}

      {/* ============ CATEGORIES ============ */}
      {(!categoriesLoading && mainCategories.length > 0) || categoriesLoading ? (
        <section id="home-categories" className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14">
          <SectionHeader emoji="📚" title={t('homeCategoriesTitle')} sub={t('homeCategoriesSub')} to="/categories" />
          {categoriesLoading ? (
            <CategoryGridSkeleton />
          ) : (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
              {mainCategories.map((cat, i) => (
                <CategoryCard key={cat.id} cat={cat} categories={categories} tint={CATEGORY_TINTS[i % CATEGORY_TINTS.length]} />
              ))}
            </div>
          )}
        </section>
      ) : null}

      {/* ============ FREE BOOKS ============ */}
      <FreeBooksSection freeBooksCount={stats?.free_books} />

      {/* ============ VIP HIGHLIGHT ============ */}
      <VipPromotion />

      {/* ============ BROWSE CTA ============ */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-20">
        <div className="rounded-3xl bg-white border border-gray-100 shadow-sm p-8 sm:p-10 text-center">
          <h2 className="text-xl sm:text-2xl font-bold text-gray-900 leading-snug">{t('homeCtaBrowse')}</h2>
          <Link
            to="/books"
            className="mt-5 inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl shadow-lg shadow-indigo-600/20 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all"
          >
            {t('allBooks')} <span aria-hidden="true">→</span>
          </Link>
        </div>
      </section>
    </div>
  )
}