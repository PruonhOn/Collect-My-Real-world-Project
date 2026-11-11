import { useQuery } from '@tanstack/react-query'
import { useMemo } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { getBooks, getCategories } from '../../api'
import BookGrid from '../../components/BookGrid'
import { useLanguage } from '../../context/LanguageContext'
import {
  baseNameOf,
  gradeKey,
  gradeNavLabel,
  isGradeSub,
  parseGradeName,
  resolveCategoryParam,
  slugOfName,
  visibleCategoriesForStudy,
} from '../../utils/grades'

const FEATURES = [
  { icon: '📖', title: 'teacherResources', desc: 'teacherTagline' },
  { icon: '🌍', title: 'featureMultiLang', desc: 'featureMultiLangDesc' },
  { icon: '🎯', title: 'featureFree', desc: 'featureFreeDesc' },
  { icon: '🧩', title: 'featureQuizzes', desc: 'featureQuizzesDesc' },
]

export default function TeacherPage() {
  const { t, locale } = useLanguage()
  const [searchParams] = useSearchParams()
  const category = searchParams.get('category') || ''
  const grade = searchParams.get('grade') || ''

  const { data: categoriesData } = useQuery({
    queryKey: ['categories', locale],
    refetchInterval: 30000,
    queryFn: () => getCategories({ locale }),
  })

  const categories = categoriesData?.data?.categories || []

  const parents = useMemo(
    () => visibleCategoriesForStudy(categories).filter((cat) => !isGradeSub(cat)),
    [categories]
  )

  const gradeGroups = useMemo(() => {
    const groups = {}
    categories.filter(isGradeSub).forEach((sub) => {
      const base = baseNameOf(sub)
      if (!groups[base]) groups[base] = []
      const num = parseGradeName(sub)
      groups[base].push({
        id: String(sub.id),
        num,
        key: gradeKey(num),
        label: gradeNavLabel(locale, t, base, num),
      })
    })
    Object.values(groups).forEach((levels) =>
      levels.sort((a, b) => (a.num === -1 ? 0 : a.num) - (b.num === -1 ? 0 : b.num))
    )
    return groups
  }, [categories, locale, t])

  const activeParent = useMemo(() => {
    const key = String(category).toLowerCase()
    if (!key) return null
    const parent =
      parents.find(
        (cat) =>
          String(cat.id) === String(category) ||
          String(cat.slug).toLowerCase() === key ||
          slugOfName(cat.name) === key
      ) || null
    if (parent) return parent
    const sub = categories.find((cat) => isGradeSub(cat) && String(cat.id) === String(category))
    if (!sub) return null
    return parents.find((cat) => cat.name === baseNameOf(sub)) || null
  }, [parents, categories, category])

  const activeGrades = activeParent ? gradeGroups[activeParent.name] || [] : []

  const activeChildId = useMemo(() => {
    if (!activeParent || activeGrades.length === 0) return null
    const byGrade =
      grade !== '' && grade !== undefined && grade !== null
        ? activeGrades.find((lvl) => lvl.key === String(grade))
        : null
    if (byGrade) return byGrade.id
    return activeGrades.some((lvl) => lvl.id === String(category)) ? String(category) : null
  }, [activeParent, activeGrades, category, grade])

  const apiCategory = resolveCategoryParam(categories, category, grade) || undefined

  const { data: booksData, isLoading } = useQuery({
    queryKey: ['books', 'teacher', apiCategory, locale],
    refetchInterval: 30000,
    queryFn: () =>
      getBooks({
        category: apiCategory,
        per_page: 16,
        audience: 'teacher',
        locale,
      }),
  })

  const books = booksData?.data?.data || []
  const total = booksData?.data?.total ?? books.length

  const subjectHref = (name) => `/teacher?category=${slugOfName(name)}`

  const pillClass = (active, base = 'bg-white text-gray-700 border border-gray-200 hover:border-emerald-300 hover:text-emerald-700') =>
    active
      ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/30'
      : base

  return (
    <div>
      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-emerald-700 via-teal-600 to-cyan-600 text-white">
        <div className="absolute -left-24 -top-24 w-72 h-72 bg-white/10 rounded-full blur-2xl" />
        <div className="absolute -right-24 -bottom-24 w-72 h-72 bg-white/10 rounded-full blur-2xl" />
        <div className="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 text-center">
          <div className="text-5xl mb-4">👩‍🏫</div>
          <span className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-white text-xs font-semibold mb-4">
            🇰🇭 {t('appName')}
          </span>
          <h1 className="text-4xl md:text-5xl font-bold leading-tight mb-4">{t('teacher')}</h1>
          <p className="text-lg text-emerald-100 mb-8">{t('teacherTagline')}</p>
          <div className="flex flex-wrap items-center justify-center gap-3">
            <a
              href="#teacher-resources"
              className="inline-flex items-center justify-center h-11 px-6 text-sm font-semibold bg-white text-emerald-700 rounded-xl shadow-sm hover:bg-emerald-50 transition-colors"
            >
              {t('exploreBooks')}
            </a>
            <Link
              to="/contact"
              className="inline-flex items-center justify-center h-11 px-6 text-sm font-semibold bg-emerald-500 text-white rounded-xl ring-1 ring-white/40 hover:bg-emerald-400 transition-colors"
            >
              {t('teacherCtaAction')}
            </Link>
          </div>
        </div>
      </section>

      {/* Why teachers choose us */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-2 py-12">
        <div className="text-center mb-8">
          <h2 className="text-2xl sm:text-3xl font-bold text-gray-900">{t('teacherWhyTitle')}</h2>
          <p className="mt-2 text-gray-500 text-sm sm:text-base">{t('teacherWhySub')}</p>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
          {FEATURES.map((feature) => (
            <div
              key={feature.title}
              className="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 text-center hover:shadow-lg hover:-translate-y-1 transition-all"
            >
              <div className="w-12 h-12 mx-auto rounded-2xl bg-emerald-50 text-2xl flex items-center justify-center mb-3">
                {feature.icon}
              </div>
              <h3 className="font-semibold text-gray-900 mb-1.5">{t(feature.title)}</h3>
              <p className="text-sm text-gray-500">{t(feature.desc)}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Category browser */}
      <section id="teacher-resources" className="scroll-mt-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <div className="text-center mb-6">
          <h2 className="text-2xl sm:text-3xl font-bold text-gray-900">{t('teacherCategoryTitle')}</h2>
          <p className="mt-2 text-gray-500 text-sm sm:text-base">{t('teacherCategorySub')}</p>
        </div>

        <div className="sticky top-16 lg:top-[7rem] z-30 bg-white/85 backdrop-blur-xl border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
          <div className="flex items-center gap-2 overflow-x-auto py-2.5 px-4 category-tab-scroll">
            <Link
              to="/teacher"
              aria-selected={category === ''}
              className={`shrink-0 whitespace-nowrap px-4 py-1.5 text-sm font-medium rounded-full transition-all duration-200 active:scale-95 ${pillClass(category === '')}`}
            >
              {t('filterAll')}
            </Link>
            {parents.map((parent) => {
              const hasChildren = (gradeGroups[parent.name]?.length ?? 0) > 0
              const active = activeParent?.id === parent.id
              return (
                <Link
                  key={String(parent.id)}
                  to={subjectHref(parent.name)}
                  aria-selected={active}
                  className={`shrink-0 inline-flex items-center gap-1.5 whitespace-nowrap px-4 py-1.5 text-sm font-medium rounded-full transition-all duration-200 active:scale-95 ${pillClass(active)}`}
                >
                  {parent.display_name || parent.name}
                  {hasChildren && (
                    <svg
                      className={`w-3.5 h-3.5 transition-transform duration-200 ${active ? 'rotate-180' : ''}`}
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      aria-hidden="true"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  )}
                </Link>
              )
            })}
          </div>

          {activeGrades.length > 0 && (
            <div className="flex items-center gap-2 overflow-x-auto border-t border-gray-100 bg-emerald-50/60 py-2 px-4 category-tab-scroll">
              <Link
                to={subjectHref(activeParent.name)}
                aria-selected={activeChildId === null}
                className={`shrink-0 whitespace-nowrap px-3 py-1 text-xs font-medium rounded-full transition-all duration-200 active:scale-95 ${pillClass(
                  activeChildId === null,
                  'bg-emerald-50 text-emerald-700 border border-emerald-100 hover:border-emerald-300 hover:bg-emerald-100'
                )}`}
              >
                {t('gradeAll')}
              </Link>
              {activeGrades.map((child) => {
                const childActive = String(activeChildId) === String(child.id)
                return (
                  <Link
                    key={child.id}
                    to={`/teacher?category=${child.id}&grade=${child.key}`}
                    aria-selected={childActive}
                    className={`shrink-0 whitespace-nowrap px-3 py-1 text-xs font-medium rounded-full transition-all duration-200 active:scale-95 ${pillClass(
                      childActive,
                      'bg-emerald-50 text-emerald-700 border border-emerald-100 hover:border-emerald-300 hover:bg-emerald-100'
                    )}`}
                  >
                    {child.label}
                  </Link>
                )
              })}
            </div>
          )}
        </div>

        {/* Results */}
        <div className="mt-8">
          <div className="flex items-center justify-between gap-3 mb-4">
            <h3 className="text-lg font-bold text-gray-900">
              {t('teacherResources')}
              <span className="ml-2 text-sm font-medium text-gray-400">
                {total} {t('resultsLabel')}
              </span>
            </h3>
            <Link
              to="/books"
              className="text-sm font-medium text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1"
            >
              {t('allBooks')} →
            </Link>
          </div>
          <BookGrid books={books} loading={isLoading} emptyMessage={t('noBooks')} />
        </div>
      </section>

      {/* CTA */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-14">
        <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-700 via-teal-600 to-cyan-600 text-white p-8 sm:p-12 text-center">
          <div className="absolute -right-16 -top-16 w-56 h-56 bg-white/10 rounded-full blur-2xl" />
          <h3 className="text-2xl sm:text-3xl font-bold mb-3">{t('teacherCtaTitle')}</h3>
          <p className="text-emerald-100 mb-6 max-w-xl mx-auto">{t('teacherCtaSub')}</p>
          <Link
            to="/contact"
            className="inline-flex items-center justify-center h-11 px-6 text-sm font-semibold bg-white text-emerald-700 rounded-xl shadow-sm hover:bg-emerald-50 transition-colors"
          >
            {t('teacherCtaAction')} →
          </Link>
        </div>
      </section>

      <style>{`
        .category-tab-scroll {
          scrollbar-width: none;
          -ms-overflow-style: none;
        }
        .category-tab-scroll::-webkit-scrollbar {
          display: none;
        }
      `}</style>
    </div>
  )
}