import { useMemo } from 'react'
import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'
import {
  baseNameOf,
  gradeKey,
  gradeNavLabel,
  isGradeSub,
  parseGradeName,
  slugOfName,
} from '../../utils/grades'

export default function CategoryNav({
  category = '',
  grade = '',
  categories = [],
  basePath = '/books',
}) {
  const { locale, t } = useLanguage()

  const parents = useMemo(() => categories.filter((cat) => !isGradeSub(cat)), [categories])

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

  if (parents.length === 0) return null

  const subjectHref = (name) => `${basePath}?category=${slugOfName(name)}`

  const pillClass = (active, base = 'bg-white text-gray-600 border border-gray-200 hover:border-indigo-300 hover:text-indigo-600') =>
    active
      ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30'
      : base

  return (
      <div className="sticky top-16 lg:top-[7rem] z-30 bg-white/85 backdrop-blur-xl border-b border-gray-100/80">
      <div
        className="flex items-center gap-2 overflow-x-auto py-2 px-4 sm:px-6 lg:px-8 category-nav-scroll"
        role="tablist"
        aria-label={t('bookCategories')}
      >
        <Link
          to={basePath}
          role="tab"
          aria-selected={category === ''}
          className={`shrink-0 whitespace-nowrap px-4 py-1.5 text-sm font-medium rounded-full transition-all duration-200 active:scale-95 ${pillClass(category === '')}`}
        >
          {t('filterAll')}
        </Link>

        <Link
          to="/new-arrivals"
          className={`shrink-0 whitespace-nowrap px-4 py-1.5 text-sm font-medium rounded-full transition-all duration-200 active:scale-95 ${pillClass(false)}`}
        >
          {t('newArrival')}
        </Link>

        {parents.map((parent) => {
          const hasChildren = (gradeGroups[parent.name]?.length ?? 0) > 0
          const active = activeParent?.id === parent.id
          return (
            <Link
              key={String(parent.id)}
              to={subjectHref(parent.name)}
              role="tab"
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
        <div
          className="flex items-center gap-2 overflow-x-auto border-t border-gray-100/80 bg-indigo-50/70 py-2 px-4 sm:px-6 lg:px-8 category-nav-scroll"
          role="tablist"
          aria-label={t('browseByGrade')}
        >
          <Link
            to={subjectHref(activeParent.name)}
            role="tab"
            aria-selected={activeChildId === null}
            className={`shrink-0 whitespace-nowrap px-3 py-1 text-xs font-medium rounded-full transition-all duration-200 active:scale-95 ${pillClass(
              activeChildId === null,
              'bg-indigo-50 text-indigo-700 border border-indigo-100 hover:border-indigo-300 hover:bg-indigo-100'
            )}`}
          >
            {t('gradeAll')}
          </Link>
          {activeGrades.map((child) => {
            const childActive = String(activeChildId) === String(child.id)
            return (
              <Link
                key={child.id}
                to={`${basePath}?category=${child.id}&grade=${child.key}`}
                role="tab"
                aria-selected={childActive}
                className={`shrink-0 whitespace-nowrap px-3 py-1 text-xs font-medium rounded-full transition-all duration-200 active:scale-95 ${pillClass(
                  childActive,
                  'bg-indigo-50 text-indigo-700 border border-indigo-100 hover:border-indigo-300 hover:bg-indigo-100'
                )}`}
              >
                {child.label}
              </Link>
            )
          })}
        </div>
      )}

      <style>{`
        .category-nav-scroll {
          scrollbar-width: none;
          -ms-overflow-style: none;
        }
        .category-nav-scroll::-webkit-scrollbar {
          display: none;
        }
      `}</style>
    </div>
  )
}