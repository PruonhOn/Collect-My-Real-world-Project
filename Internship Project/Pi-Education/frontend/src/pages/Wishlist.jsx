import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import api from '../api/client'
import BookCard from '../components/books/BookCard'
import EmptyState from '../components/common/EmptyState'
import Loading from '../components/common/Loading'
import ErrorState from '../components/common/ErrorState'
import { useI18n } from '../i18n'

export default function Wishlist() {
  const { t, locale } = useI18n()

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['wishlist', locale],
    queryFn: async () => {
      const res = await api.get('/wishlist', { params: { per_page: 24 } })
      return res.data.data
    },
  })

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState retry={refetch} />

  const books = data?.books || []

  return (
    <div className="page">
      <div className="max-w-2xl">
        <p className="section-eyebrow">Saved</p>
        <h1 className="section-title">{t('wishlist.title')}</h1>
        {books.length > 0 && <p className="section-subtitle">{t('wishlist.showingOnly')}</p>}
      </div>

      {books.length === 0 ? (
        <div className="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white/50">
          <EmptyState title={t('wishlist.empty')} description={t('wishlist.emptyDesc')} />
          <div className="pb-10 text-center">
            <Link to="/books" className="btn-primary">
              {t('common.books')}
            </Link>
          </div>
        </div>
      ) : (
        <div className="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {books.map((book) => (
            <BookCard key={book.id} book={book} />
          ))}
        </div>
      )}
    </div>
  )
}