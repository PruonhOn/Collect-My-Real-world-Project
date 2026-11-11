import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import api from '../api/client'
import EmptyState from '../components/common/EmptyState'
import ErrorState from '../components/common/ErrorState'
import Loading from '../components/common/Loading'
import { useI18n } from '../i18n'

export default function MyBooks() {
  const { t } = useI18n()

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['my-books'],
    queryFn: async () => {
      const res = await api.get('/my-books')
      return res.data.data.books.data
    },
  })

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState retry={refetch} />

  const books = data || []

  if (books.length === 0) {
    return (
      <div className="page">
        <p className="section-eyebrow">Library</p>
        <h1 className="section-title">{t('common.myBooks')}</h1>
        <div className="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white/50">
          <EmptyState
            title={t('book.noBooks')}
            description={t('orders.emptyDesc')}
          />
          <div className="pb-10 text-center">
            <Link to="/books" className="btn-primary">
              {t('orders.browseBooks')}
            </Link>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="page">
      <div className="max-w-2xl">
        <p className="section-eyebrow">Library</p>
        <h1 className="section-title">{t('common.myBooks')}</h1>
        <p className="section-subtitle">Continue reading where you left off.</p>
      </div>

      <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {books.map((book) => (
          <Link
            key={book.id}
            to={`/read/${book.slug}`}
            className="group overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-150 hover:border-indigo-300"
          >
            <div className="relative aspect-[3/1.4] overflow-hidden bg-slate-100">
              <img
                src={book.cover_image || '/placeholder-book.svg'}
                alt={book.title}
                className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
              />
              <span className="badge absolute top-3 left-3 bg-slate-900 text-white">
                {book.type}
              </span>
            </div>
            <div className="p-5">
              <h3 className="truncate font-bold text-slate-900 group-hover:text-indigo-700">{book.title}</h3>
              <div className="mt-4">
                <div className="flex items-center justify-between text-xs font-medium text-slate-500">
                  <span>{t('reader.saved')}</span>
                  <span>{book.progress}%</span>
                </div>
                <div className="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-200">
                  <div
                    className="h-full rounded-full bg-indigo-600 transition-all"
                    style={{ width: `${book.progress}%` }}
                  />
                </div>
              </div>
            </div>
          </Link>
        ))}
      </div>
    </div>
  )
}