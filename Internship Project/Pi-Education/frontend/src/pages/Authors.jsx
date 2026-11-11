import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import api from '../api/client'
import EmptyState from '../components/common/EmptyState'
import Loading from '../components/common/Loading'
import ErrorState from '../components/common/ErrorState'
import { useI18n } from '../i18n'

export default function Authors() {
  const { t, locale } = useI18n()

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['authors', locale],
    queryFn: async () => {
      const res = await api.get(`/authors?per_page=50`)
      return res.data.data.authors
    },
  })

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState retry={refetch} />

  const authors = data || []

  return (
    <div className="page">
      <div className="max-w-2xl">
        <p className="section-eyebrow">Explore</p>
        <h1 className="section-title">{t('book.authorsHeading')}</h1>
      </div>

      {authors.length === 0 ? (
        <EmptyState />
      ) : (
        <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {authors.map((author) => (
            <Link
              key={author.id}
              to={`/books?author=${author.slug}`}
              className="card card-hover group flex items-center gap-4 p-5"
            >
              <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-2xl font-extrabold text-white shadow-lg shadow-indigo-500/25 ring-4 ring-white">
                {author.name?.charAt(0).toUpperCase() || '?'}
              </div>
              <div className="min-w-0 flex-1">
                <h3 className="font-bold text-slate-900 group-hover:text-indigo-700">{author.name}</h3>
                <p className="mt-1 text-sm text-slate-500">
                  {author.books_count} {t('common.books')}
                </p>
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}