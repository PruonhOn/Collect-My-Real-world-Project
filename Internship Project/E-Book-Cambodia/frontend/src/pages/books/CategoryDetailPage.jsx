import { useQuery } from '@tanstack/react-query'
import { useParams, Link } from 'react-router-dom'
import { getCategory, getBooks, getCategories } from '../../api'
import BookGrid from '../../components/BookGrid'
import { useLanguage } from '../../context/LanguageContext'
import { booksBasePath, gradeKeyFromName, gradeSubsFor, slugOfName } from '../../utils/grades'

export default function CategoryDetailPage() {
  const { id } = useParams()
  const { t, locale } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['category', id, locale],
    refetchInterval: 30000,
    queryFn: () => getCategory(id),
    enabled: !!id,
  })

  const { data: categoriesData } = useQuery({
    queryKey: ['categories-all', locale],
    refetchInterval: 30000,
    queryFn: () => getCategories({ locale }),
  })

  const res = data?.data
  const category = res?.category
  const allCategories = categoriesData?.data?.categories || []
  const gradeSubs = category ? gradeSubsFor(allCategories, category) : []

  const hasGrades = gradeSubs.length > 0

  const categoryIds = [category?.id, ...gradeSubs.map((c) => c.id)]
    .filter(Boolean)
    .join(',')

  const { data: gradeBooksData, isLoading: gradeBooksLoading } = useQuery({
    queryKey: ['category-books', categoryIds, hasGrades, locale],
    refetchInterval: 30000,
    queryFn: () => getBooks({ category: categoryIds, per_page: 24, sort: 'latest', locale }),
    enabled: hasGrades && !!categoryIds,
  })

  const books = hasGrades ? (gradeBooksData?.data?.data || []) : (res?.books || [])
  const loading = hasGrades ? gradeBooksLoading : isLoading

  const browseBase = category ? booksBasePath(category.name) : '/books'
  const browseAll = category
    ? `${browseBase}?category=${slugOfName(category.name)}`
    : '/books'

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <Link to="/categories" className="text-indigo-600 text-sm hover:text-indigo-700 mb-4 inline-block">
        ← {t('back')}
      </Link>

      <div className="mb-8">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div className="flex items-center gap-4">
            <div className="w-16 h-16 rounded-2xl bg-indigo-100 flex items-center justify-center text-3xl">
              📚
            </div>
            <div>
              <h1 className="text-3xl font-bold text-gray-900">{category?.display_name || category?.name || id}</h1>
              {(category?.display_description || category?.description) && (
                <p className="mt-1 text-gray-500">{category.display_description || category.description}</p>
              )}
            </div>
          </div>
          {hasGrades && (
            <Link
              to={browseAll}
              className="inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-700"
            >
              {t('browseByGrade')} <span aria-hidden="true">→</span>
            </Link>
          )}
        </div>

        {hasGrades && (
          <div className="mt-5 flex flex-wrap items-center gap-2">
            {gradeSubs.map((gradeCat) => (
              <Link
                key={gradeCat.id}
                to={`${browseBase}?category=${slugOfName(category.name)}&grade=${gradeKeyFromName(gradeCat)}`}
                className="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100 hover:border-indigo-300 hover:bg-indigo-100 transition-colors"
              >
                {String(gradeCat.display_name || gradeCat.name).replace(
                  `${category.display_name || category.name} - `,
                  ''
                )}
              </Link>
            ))}
          </div>
        )}
      </div>

      <BookGrid books={books} loading={loading} />
    </div>
  )
}