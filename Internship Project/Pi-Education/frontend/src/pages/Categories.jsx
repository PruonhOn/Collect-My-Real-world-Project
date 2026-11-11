import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import api from '../api/client'
import EmptyState from '../components/common/EmptyState'
import Loading from '../components/common/Loading'
import ErrorState from '../components/common/ErrorState'
import { useI18n } from '../i18n'

export default function Categories() {
  const { t, locale } = useI18n()

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['categories', locale],
    queryFn: async () => {
      const res = await api.get(`/categories?per_page=50`)
      return res.data.data.categories
    },
  })

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState retry={refetch} />

  const categories = data || []

  return (
    <div className="page">
      <div className="max-w-2xl">
        <p className="section-eyebrow">Explore</p>
        <h1 className="section-title">{t('book.browseCategories')}</h1>
      </div>

      {categories.length === 0 ? (
        <EmptyState />
      ) : (
        <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {categories.map((category) => (
            <Link
              key={category.id}
              to={`/books?category=${category.slug}`}
              className="card card-hover group flex items-center gap-4 p-6"
            >
              <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-xl font-extrabold text-white shadow-lg shadow-indigo-500/25">
                {category.name?.charAt(0).toUpperCase()}
              </div>
              <div className="min-w-0 flex-1">
                <h3 className="font-bold text-slate-900 group-hover:text-indigo-700">{category.name}</h3>
                {category.description && (
                  <p className="mt-1 truncate text-sm text-slate-500">{category.description}</p>
                )}
              </div>
              <span className="shrink-0 rounded-full bg-indigo-50 px-3 py-1 text-sm font-bold text-indigo-600 ring-1 ring-indigo-100">
                {category.books_count}
              </span>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}