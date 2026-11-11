import { useQuery } from '@tanstack/react-query'
import { useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import api from '../api/client'
import BookCard from '../components/books/BookCard'
import EmptyState from '../components/common/EmptyState'
import Loading from '../components/common/Loading'
import ErrorState from '../components/common/ErrorState'
import { useAuth } from '../context/AuthContext'
import { useI18n } from '../i18n'

const selectClass = 'select'

export default function Books() {
  const { t, locale } = useI18n()
  const { isAuthenticated } = useAuth()
  const [searchParams, setSearchParams] = useSearchParams()
  const category = searchParams.get('category') || ''
  const author = searchParams.get('author') || ''
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [debouncedSearch, setDebouncedSearch] = useState('')
  const [type, setType] = useState('')
  const [sort, setSort] = useState('newest')
  const [minRating, setMinRating] = useState('')
  const [wishlistOnly, setWishlistOnly] = useState(false)

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['books', { page, debouncedSearch, type, sort, minRating, wishlistOnly, category, author, locale }],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: String(page),
        per_page: '12',
        type,
        sort,
      })
      if (debouncedSearch) params.set('search', debouncedSearch)
      if (category) params.set('category', category)
      if (author) params.set('author', author)
      if (minRating) params.set('min_rating', minRating)
      if (wishlistOnly) params.set('wishlist', '1')
      const res = await api.get(`/books?${params.toString()}`)
      return res.data.data
    },
  })

  const handleSearch = (value) => {
    setSearch(value)
    clearTimeout(handleSearch._timer)
    handleSearch._timer = setTimeout(() => {
      setDebouncedSearch(value)
      setPage(1)
    }, 400)
  }

  const clearFilter = () => {
    setSearchParams({})
    setPage(1)
  }

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState retry={refetch} />

  const books = data?.books || []
  const pagination = data?.pagination || {}

  return (
    <div className="page">
      <div className="max-w-2xl">
        <p className="section-eyebrow">Library</p>
        <h1 className="section-title">{t('book.allBooks')}</h1>
      </div>

      {(category || author) && (
        <div className="mt-5 flex items-center gap-2">
          <span className="rounded-xl bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700">
            {category ? `${t('book.category')}: ${category}` : `${t('book.byAuthor')}: ${author}`}
          </span>
          <button
            onClick={clearFilter}
            className="rounded-xl bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-200"
          >
            ✕ {t('common.clear')}
          </button>
        </div>
      )}

      <div className="card mt-7 p-4 sm:p-5">
        <div className="relative">
          <svg
            className="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-slate-400"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
          >
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.35-4.35" />
          </svg>
          <input
            type="search"
            value={search}
            onChange={(e) => handleSearch(e.target.value)}
            placeholder={t('common.search')}
            className="input pl-10"
          />
        </div>
        <div className="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <select value={type} onChange={(e) => { setType(e.target.value); setPage(1) }} className={selectClass}>
            <option value="">{t('book.allTypes')}</option>
            <option value="FREE">{t('book.free')}</option>
            <option value="PAID">{t('book.paid')}</option>
          </select>
          <select value={sort} onChange={(e) => { setSort(e.target.value); setPage(1) }} className={selectClass}>
            <option value="newest">{t('book.sortNewest')}</option>
            <option value="popular">{t('book.sortPopular')}</option>
            <option value="top_rated">{t('book.sortTopRated')}</option>
            <option value="most_read">{t('book.sortMostRead')}</option>
            <option value="price_asc">{t('book.sortPriceAsc')}</option>
            <option value="price_desc">{t('book.sortPriceDesc')}</option>
            <option value="oldest">{t('book.sortOldest')}</option>
          </select>
          <select value={minRating} onChange={(e) => { setMinRating(e.target.value); setPage(1) }} className={selectClass}>
            <option value="">{t('book.minRatingAny')}</option>
            {[4, 3, 2].map((n) => (
              <option key={n} value={n}>{t('book.minRatingValue', { min: n })}</option>
            ))}
          </select>
          {isAuthenticated ? (
            <button
              type="button"
              onClick={() => { setWishlistOnly((v) => !v); setPage(1) }}
              className={`inline-flex items-center justify-center gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold ring-1 transition ${
                wishlistOnly
                  ? 'bg-rose-50 text-rose-600 ring-rose-200'
                  : 'bg-white text-slate-600 ring-slate-300 hover:bg-slate-50'
              }`}
              title={t('wishlist.title')}
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill={wishlistOnly ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="2">
                <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
              </svg>
              {wishlistOnly && t('wishlist.title')}
            </button>
          ) : (
            <div />
          )}
        </div>
      </div>

      {wishlistOnly && (
        <p className="mt-3 text-sm font-medium text-rose-600">• {t('wishlist.showingOnly')}</p>
      )}

      {books.length === 0 ? (
        <EmptyState title={t('book.noBooks')} description={t('book.noBooksDesc')} />
      ) : (
        <div className="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {books.map((book) => (
            <BookCard key={book.id} book={book} />
          ))}
        </div>
      )}

      {pagination.last_page > 1 && (
        <div className="mt-10 flex items-center justify-center gap-2">
          <button
            onClick={() => setPage(page - 1)}
            disabled={page <= 1}
            className="btn-secondary px-4 py-2"
          >
            ←
          </button>
          <span className="px-3 text-sm font-medium text-slate-600">
            {page} / {pagination.last_page}
          </span>
          <button
            onClick={() => setPage(page + 1)}
            disabled={page >= pagination.last_page}
            className="btn-secondary px-4 py-2"
          >
            →
          </button>
        </div>
      )}
    </div>
  )
}