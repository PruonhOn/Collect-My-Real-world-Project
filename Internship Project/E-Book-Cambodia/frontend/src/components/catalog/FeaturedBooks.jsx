import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { getBooks } from '../../api'
import BookGrid from '../BookGrid'
import { useLanguage } from '../../context/LanguageContext'

export default function FeaturedBooks() {
  const { t } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['books', 'trending'],
    refetchInterval: 60000,
    queryFn: () => getBooks({ sort: 'popular', per_page: 8 }),
  })
  const books = data?.data?.data || []

  if (!isLoading && books.length === 0) return null

  return (
    <section className="max-w-[78rem] mx-auto px-4 sm:px-6 lg:px-8 pt-14">
      <div className="flex items-end justify-between gap-4 mb-6">
        <div>
          <h2 className="text-2xl sm:text-3xl font-bold text-gray-900">{t('trendingBooks')}</h2>
          <p className="mt-1.5 text-gray-500 text-sm sm:text-base">{t('trendingSub')}</p>
        </div>
        <Link
          to="/books?sort=popular"
          className="hidden sm:inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700 shrink-0"
        >
          {t('viewAll')} <span aria-hidden="true">→</span>
        </Link>
      </div>
      <BookGrid books={books} loading={isLoading} />
      <div className="mt-6 text-center sm:hidden">
        <Link
          to="/books?sort=popular"
          className="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-700"
        >
          {t('viewAll')} <span aria-hidden="true">→</span>
        </Link>
      </div>
    </section>
  )
}