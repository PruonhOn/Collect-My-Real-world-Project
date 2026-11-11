import { useQuery } from '@tanstack/react-query'
import { useLanguage } from '../../context/LanguageContext'
import { useAuth } from '../../context/AuthContext'
import { Link, useSearchParams } from 'react-router-dom'
import { getBooks, getCategories, getStats } from '../../api'
import BookGrid from '../../components/BookGrid'
import CatalogHero from '../../components/catalog/CatalogHero'
import SearchBar from '../../components/catalog/SearchBar'
import CategoryNav from '../../components/catalog/CategoryNav'
import FeaturedBooks from '../../components/catalog/FeaturedBooks'
import BookFilters from '../../components/catalog/BookFilters'
import FreeBooksSection from '../../components/catalog/FreeBooksSection'
import VipPromotion from '../../components/catalog/VipPromotion'
import StatsSection from '../../components/catalog/StatsSection'
import VipProfileDock from '../../components/membership/VipProfileDock'
import { useMembership } from '../../hooks/useMembership'
import {
  COMPETITIVE_EXAMS_NAME,
  gradeSubsFor,
  resolveCategoryParam,
  slugOfName,
  visibleCategoriesForStudy,
} from '../../utils/grades'

const LANGUAGE_FLAGS = [
  { code: 'km', flag: '🇰🇭' },
  { code: 'en', flag: '🇬🇧' },
  { code: 'zh', flag: '🇨🇳' },
  { code: 'fr', flag: '🇫🇷' },
]

// Single source of truth for the books page width: 2rem narrower than the
// site-wide max-w-7xl. Every section on this page must use it, including the
// ones rendered by child components, or they overhang the page by 2rem.
const BOOKS_PAGE_CONTAINER = 'max-w-[78rem] mx-auto px-4 sm:px-6 lg:px-8'

const SUBJECTS = [
  { key: 'subjectKhmer', icon: '📖', match: /khmer|ខ្មែរ/i },
  { key: 'subjectMath', icon: '🔢', match: /math|គណិត/i },
  { key: 'subjectBusiness', icon: '💼', match: /business|ជំនួញ/i },
  { key: 'subjectHistory', icon: '🏛️', match: /history|ប្រវត្តិ/i },
  { key: 'subjectHealth', icon: '💊', match: /health|សុខភាព/i },
  { key: 'subjectEducation', icon: '🎓', match: /educ|អប់រំ/i },
  { key: 'subjectTechnology', icon: '💻', match: /technolog|computer|បច្ចេក/i },
  { key: 'subjectLiterature', icon: '📚', match: /literature|អក្សរ/i },
]

const TINTS = [
  'bg-indigo-50 text-indigo-600 ring-indigo-100',
  'bg-emerald-50 text-emerald-600 ring-emerald-100',
  'bg-amber-50 text-amber-600 ring-amber-100',
  'bg-sky-50 text-sky-600 ring-sky-100',
  'bg-rose-50 text-rose-600 ring-rose-100',
  'bg-violet-50 text-violet-600 ring-violet-100',
  'bg-teal-50 text-teal-600 ring-teal-100',
]

const isGradeSub = (cat) => /[-–—]\s*(?:grade\s*\d+|kindergarten)\s*$/i.test(String(cat.name || ''))

/**
 * One list component behind two routes. `scope="study"` serves /books and keeps
 * Competitive Exams out of its category row; `scope="competitive"` serves
 * /competitive-exams and shows only that category. Splitting them stops the
 * competition books from appearing twice in the Study Book navigation.
 */
export default function BooksPage({ scope = 'study' }) {
  const { t, locale } = useLanguage()
  const { isAuthenticated } = useAuth()
  const { isVip } = useMembership()
  const [searchParams, setSearchParams] = useSearchParams()
  const isCompetitiveScope = scope === 'competitive'

  const search = searchParams.get('search') || ''
  const type = searchParams.get('type') || ''
  const grade = searchParams.get('grade') || ''
  const audience = searchParams.get('audience') || ''
  const sort = searchParams.get('sort') || 'latest'
  const vip = searchParams.get('vip') || ''
  const owned = searchParams.get('owned') || ''
  const language = searchParams.get('language') || ''
  const page = Math.max(1, Number(searchParams.get('page') || '1'))

  const { data: categoriesData } = useQuery({
    queryKey: ['categories-all', locale],
    refetchInterval: 30000,
    queryFn: () => getCategories({ locale }),
  })

  const { data: statsData } = useQuery({
    queryKey: ['platform-stats'],
    refetchInterval: 30000,
    queryFn: getStats,
  })

  const categories = categoriesData?.data?.categories || []

  // The competitive page pins itself to its own category so visiting
  // /competitive-exams without params still lists the competition books.
  const competitiveCategory = categories.find(
    (cat) => !isGradeSub(cat) && String(cat.name) === COMPETITIVE_EXAMS_NAME
  )
  const categoryParam = searchParams.get('category') || ''
  const category = isCompetitiveScope
    ? categoryParam || (competitiveCategory ? String(competitiveCategory.id) : '')
    : categoryParam

  const isDiscovery =
    !isCompetitiveScope &&
    !search && !type && !category && !grade && !audience && !vip && !owned && !language && sort === 'latest' && page === 1

  const studyCategories = visibleCategoriesForStudy(categories, {
    includeCompetitive: isCompetitiveScope,
  })
  const studyMainCategories = studyCategories.filter((cat) => !isGradeSub(cat))
  const mainCategories = categories.filter((cat) => !isGradeSub(cat))
  const subjectEntries = SUBJECTS.map((subject) => {
    const category = mainCategories.find(
      (c) => subject.match.test(c.slug) || subject.match.test(c.name)
    )
    const gradeSubs = category ? gradeSubsFor(categories, category) : []
    return {
      subject,
      category,
      hasGrades: gradeSubs.length > 0,
      slug: category ? slugOfName(category.name) : '',
      booksCount: category
        ? (category.books_count ?? 0) +
          gradeSubs.reduce((sum, sub) => sum + (sub.books_count ?? 0), 0)
        : 0,
    }
  })
  const apiCategory = resolveCategoryParam(categories, category, grade) || undefined

  // The purchased shelf only exists for a signed-in reader, so a shared or
  // stale ?owned=1 link falls back to the full catalogue for guests.
  const ownedActive = isAuthenticated && (owned === '1' || (!vip && !type && owned !== '0'))

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['books', { search, type, apiCategory, sort, vip, owned: ownedActive, language, audience, page, locale }],
    refetchInterval: 30000,
    queryFn: () =>
      getBooks({
        search: search || undefined,
        category: apiCategory,
        free: type === 'free' ? '1' : type === 'paid' ? '0' : undefined,
        vip: vip === '1' ? '1' : undefined,
        owned: ownedActive ? '1' : undefined,
        language: language || undefined,
        audience: audience || undefined,
        sort,
        per_page: 12,
        page,
        locale,
      }),
    enabled: !!categoriesData,
  })

  const res = data?.data
  const books = res?.data || []
  const pagination = res

  const statsRaw = statsData?.data?.stats
  const stats = statsRaw
    ? {
        books: Number(statsRaw.books),
        freeBooks: Number(statsRaw.free_books),
        categories: Number(statsRaw.categories),
        members: Number(statsRaw.members),
        languages: Array.isArray(statsRaw.languages) ? statsRaw.languages : [],
        languagesCount: (statsRaw.languages || []).length,
      }
    : null

  const updateParam = (key, value) => {
    const params = new URLSearchParams(searchParams)
    if (value) {
      params.set(key, value)
    } else {
      params.delete(key)
    }
    if (key !== 'page') params.delete('page')
    setSearchParams(params)
  }

  const setSearch = (value) => updateParam('search', value)
  const setCategory = (value) => {
    const params = new URLSearchParams(searchParams)
    if (value) {
      params.set('category', value)
    } else {
      params.delete('category')
    }
    params.delete('grade')
    params.delete('page')
    setSearchParams(params)
  }
  const setLanguage = (value) => updateParam('language', value)
  const setSort = (value) => updateParam('sort', value)

  const setAccess = (value) => {
    const params = new URLSearchParams(searchParams)
    if (value === 'free' || value === 'paid') {
      params.delete('vip')
      params.delete('owned')
      params.set('type', value)
    } else if (value === 'vip') {
      params.delete('type')
      params.delete('owned')
      params.set('vip', '1')
    } else if (value === 'owned') {
      params.delete('type')
      params.delete('vip')
      params.set('owned', '1')
    } else {
      params.delete('type')
      params.delete('vip')
      params.delete('owned')
    }
    params.delete('page')
    setSearchParams(params)
  }

  const access = ownedActive
    ? 'owned'
    : vip === '1'
      ? 'vip'
      : type === 'free' || type === 'paid'
        ? type
        : (isAuthenticated ? 'owned' : '')

  const clearAll = () => setSearchParams({})

  const paginationControls =
    pagination && pagination.last_page > 1 ? (
      <div className="mt-8 flex justify-center gap-2">
        <button
          disabled={pagination.current_page <= 1}
          onClick={() => updateParam('page', String(pagination.current_page - 1))}
          className="px-4 py-2 bg-white border rounded-lg text-sm text-gray-700 hover:border-indigo-300 disabled:opacity-50"
        >
          {t('previous')}
        </button>
        <span className="px-4 py-2 text-sm text-gray-600">
          {pagination.current_page} / {pagination.last_page}
        </span>
        <button
          disabled={pagination.current_page >= pagination.last_page}
          onClick={() => updateParam('page', String(pagination.current_page + 1))}
          className="px-4 py-2 bg-white border rounded-lg text-sm text-gray-700 hover:border-indigo-300 disabled:opacity-50"
        >
          {t('next')}
        </button>
      </div>
    ) : null

  return (
    <div className={isVip ? 'vip-books-frame' : undefined}>
      <VipProfileDock />
      <CategoryNav
        category={category}
        grade={grade}
        categories={studyCategories}
        basePath={isCompetitiveScope ? '/competitive-exams' : '/books'}
      />

      {isDiscovery && <CatalogHero stats={stats} />}

      <div className={BOOKS_PAGE_CONTAINER}>
        <div className="pt-8">
          <SearchBar
            value={search}
            onChange={setSearch}
            categories={studyMainCategories}
            category={category}
            onCategoryChange={setCategory}
            compact={!isDiscovery}
          />
        </div>
      </div>

      {isDiscovery ? (
        <>
          {categories.length > 0 && (
            <section id="subjects" className={`scroll-mt-24 ${BOOKS_PAGE_CONTAINER} pt-12`}>
              <div className="text-center mb-8">
                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900">{t('studySubjects')}</h2>
                <p className="mt-2 text-gray-500">{t('discoverPurposeSub')}</p>
              </div>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                {subjectEntries.map(({ subject, category, hasGrades, slug, booksCount }, i) => {
                  const to = category
                    ? `/books?category=${slug}`
                    : `/books?search=${encodeURIComponent(t(subject.key))}`
                  const tint = TINTS[i % TINTS.length]
                  const inner = (
                    <>
                      <span className={`flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-xl ring-1 shrink-0 text-xl sm:text-2xl ${tint}`}>
                        {subject.icon}
                      </span>
                      <span className="min-w-0">
                        <span className="block text-sm sm:text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
                          {t(subject.key)}
                        </span>
                        <span className="block mt-0.5 text-[11px] sm:text-xs text-gray-500">
                          {category ? `${booksCount} ${t('statBooks')}` : t('statBooks')}
                        </span>
                      </span>
                      {hasGrades && (
                        <span
                          className="ml-auto hidden sm:inline-flex text-indigo-400"
                          aria-hidden="true"
                        >
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                          </svg>
                        </span>
                      )}
                    </>
                  )
                  return (
                    <Link key={subject.key} to={to} className="group flex items-center gap-3 sm:gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-3.5 sm:p-5 hover:shadow-lg hover:-translate-y-1 hover:border-indigo-100 transition-all">
                      {inner}
                    </Link>
                  )
                })}
              </div>
            </section>
          )}

          <FeaturedBooks />

          <section id="library" className={`scroll-mt-24 ${BOOKS_PAGE_CONTAINER} pt-14`}>
            <div className="text-center mb-6">
              <h2 className="text-2xl sm:text-3xl font-bold text-gray-900">{t('allBooksTitle')}</h2>
              <p className="mt-1.5 text-gray-500 text-sm sm:text-base">{t('allBooksSub')}</p>
            </div>
            <BookFilters
              access={access}
              setAccess={setAccess}
              sort={sort}
              setSort={setSort}
              category={category}
              setCategory={setCategory}
              categories={studyMainCategories}
              language={language}
              setLanguage={setLanguage}
              languages={stats?.languages || []}
              onClear={clearAll}
            />
            <div className="mt-6">
              {isError ? (
                <div className="text-center py-10">
                  <p className="text-red-500 mb-2">{t('error')}</p>
                  <button onClick={() => refetch()} className="text-indigo-600 font-medium">
                    {t('retry')}
                  </button>
                </div>
              ) : (
                <BookGrid books={books} loading={isLoading} />
              )}
            </div>
            {paginationControls}
          </section>

          <FreeBooksSection
            freeBooksCount={stats?.freeBooks}
            containerClassName={BOOKS_PAGE_CONTAINER}
          />
          <VipPromotion containerClassName={BOOKS_PAGE_CONTAINER} />
          <StatsSection stats={stats} />
        </>
      ) : (
        <section id="library" className={`scroll-mt-24 ${BOOKS_PAGE_CONTAINER} pb-16`}>
          <div className="flex flex-wrap items-end justify-between gap-3 pt-4">
            <div>
              <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">
                {isCompetitiveScope ? t('competitiveExams') : t('allBooksTitle')}
              </h1>
              <p className="mt-1 text-sm text-gray-500">
                {pagination?.total ?? books.length} {t('resultsLabel')}
              </p>
            </div>
            {((pagination && pagination.total > 0) || books.length > 0) && (
              <button
                onClick={clearAll}
                className="text-sm font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg transition-colors"
              >
                {t('clearAll')}
              </button>
            )}
          </div>

          <div className="mt-6">
            <BookFilters
              access={access}
              setAccess={setAccess}
              sort={sort}
              setSort={setSort}
              category={category}
              setCategory={setCategory}
              categories={studyMainCategories}
              language={language}
              setLanguage={setLanguage}
              languages={stats?.languages || []}
              onClear={clearAll}
            />
          </div>

          <div className="mt-6">
            {isError ? (
              <div className="text-center py-10">
                <p className="text-red-500 mb-2">{t('error')}</p>
                <button onClick={() => refetch()} className="text-indigo-600 font-medium">
                  {t('retry')}
                </button>
              </div>
            ) : (
              <BookGrid books={books} loading={isLoading} />
            )}
          </div>

          {paginationControls}
        </section>
      )}

      <div className="mt-14 border-t border-gray-100 bg-white">
        <div className={`${BOOKS_PAGE_CONTAINER} py-8 flex flex-col sm:flex-row items-center justify-center sm:justify-between gap-4`}>
          <p className="text-sm text-gray-500 font-medium">🇰🇭 {t('knowledgeForCambodia')}</p>
          <div className="flex items-center gap-4">
            {LANGUAGE_FLAGS.map((lang) => (
              <span key={lang.code} className="flex items-center gap-1.5 text-xs text-gray-600">
                <span aria-hidden="true">{lang.flag}</span>
                {t(`lang${lang.code.charAt(0).toUpperCase()}${lang.code.slice(1)}`)}
              </span>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}