import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  adminGetBookReviews,
  adminGetBookReview,
  adminReviewBook,
  adminPublishBook,
  adminUnpublishBook,
  adminBookPreviewUrl,
  coverUrl,
} from '../../api'
import BookStatusBadge from '../../components/BookStatusBadge'
import { useLanguage } from '../../context/LanguageContext'

const REVIEW_STATUSES = ['pending', 'approved', 'rejected', 'published', 'draft']

/**
 * The admin review queue: what authors have submitted, what still needs a
 * decision, and what is already live. Approving, rejecting and publishing all
 * happen here, so the review trail has one home.
 */
export default function AdminBookReviewPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [status, setStatus] = useState('pending')
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [selectedId, setSelectedId] = useState(null)
  const [note, setNote] = useState('')
  const [error, setError] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['admin-book-reviews', { status, search, page }],
    refetchInterval: 10000,
    queryFn: () =>
      adminGetBookReviews({ status, search: search || undefined, per_page: 15, page }),
    placeholderData: (prev) => prev,
  })

  const { data: detail } = useQuery({
    queryKey: ['admin-book-review', selectedId],
    refetchInterval: 10000,
    queryFn: () => adminGetBookReview(selectedId),
    enabled: !!selectedId,
  })

  const book = detail?.data?.book

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['admin-book-reviews'] })
    queryClient.invalidateQueries({ queryKey: ['admin-book-review', selectedId] })
    queryClient.invalidateQueries({ queryKey: ['admin-books'] })
    queryClient.invalidateQueries({ queryKey: ['admin-dashboard'] })
  }

  const handleError = (err) => {
    const errors = err.response?.data?.errors
    setError(errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message || '')
  }

  const reviewMutation = useMutation({
    mutationFn: ({ id, decision }) => adminReviewBook(id, { decision, review_note: note || null }),
    onSuccess: () => {
      setNote('')
      invalidate()
    },
    onError: handleError,
  })

  const publishMutation = useMutation({
    mutationFn: (id) => adminPublishBook(id),
    onSuccess: invalidate,
    onError: handleError,
  })

  const unpublishMutation = useMutation({
    mutationFn: (id) => adminUnpublishBook(id),
    onSuccess: invalidate,
    onError: handleError,
  })

  const books = data?.data?.data || []
  const paginator = data?.data

  const inputClass =
    'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-500'

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">{t('bookReview')}</h1>
        <p className="text-gray-500 text-sm mt-1">{t('bookReviewSubtitle')}</p>
      </div>

      {error && <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>}

      <div className="grid md:grid-cols-2 gap-4 mb-6">
        <input
          type="text"
          value={search}
          onChange={(e) => {
            setSearch(e.target.value)
            setPage(1)
          }}
          placeholder={t('searchTitleOrAuthor')}
          className={inputClass}
        />
        <select
          value={status}
          onChange={(e) => {
            setStatus(e.target.value)
            setPage(1)
          }}
          className={inputClass}
        >
          {REVIEW_STATUSES.map((value) => (
            <option key={value} value={value}>
              {t(`status_${value}`)}
            </option>
          ))}
        </select>
      </div>

      <div className="grid lg:grid-cols-2 gap-6">
        <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
          {isLoading ? (
            <div className="p-8 text-center text-gray-500">{t('loading')}</div>
          ) : books.length === 0 ? (
            <div className="p-8 text-center text-gray-500">{t('noReviews')}</div>
          ) : (
            <ul className="divide-y divide-gray-200">
              {books.map((row) => (
                <li key={row.id}>
                  <button
                    onClick={() => {
                      setSelectedId(row.id)
                      setNote('')
                      setError('')
                    }}
                    className={`w-full text-left px-6 py-4 hover:bg-gray-50 flex items-center gap-3 ${
                      selectedId === row.id ? 'bg-emerald-50' : ''
                    }`}
                  >
                    {row.cover_image ? (
                      <img
                        src={coverUrl(row)}
                        alt={row.title}
                        className="w-9 h-12 object-contain bg-white p-1.5 rounded-md shrink-0"
                      />
                    ) : (
                      <div className="w-9 h-12 rounded-md bg-indigo-50 flex items-center justify-center shrink-0">
                        📖
                      </div>
                    )}
                    <div className="min-w-0 flex-1">
                      <p className="text-sm font-medium text-gray-900 truncate">{row.title}</p>
                      <p className="text-xs text-gray-500 truncate">
                        {row.authorRelation?.name || row.author}
                      </p>
                    </div>
                    <BookStatusBadge book={row} />
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>

        <div className="bg-white rounded-2xl shadow-sm p-6">
          {!selectedId ? (
            <p className="text-gray-500 text-sm">{t('selectBookToReview')}</p>
          ) : !book ? (
            <p className="text-gray-500 text-sm">{t('loading')}</p>
          ) : (
            <div className="space-y-5">
              <div>
                <h2 className="text-lg font-semibold text-gray-900">{book.title}</h2>
                <p className="text-sm text-gray-500 mt-1">
                  {book.authorRelation?.name || book.author} · {t(`status_${book.status}`)}
                </p>
              </div>

              <dl className="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <dt className="text-gray-500">{t('bookCategories')}</dt>
                  <dd className="text-gray-900">{book.category?.name || '—'}</dd>
                </div>
                <div>
                  <dt className="text-gray-500">{t('language')}</dt>
                  <dd className="text-gray-900">{book.language || '—'}</dd>
                </div>
                <div>
                  <dt className="text-gray-500">{t('pages')}</dt>
                  <dd className="text-gray-900">{book.pages ?? '—'}</dd>
                </div>
                <div>
                  <dt className="text-gray-500">{t('bookSold')}</dt>
                  <dd className="text-gray-900">{book.purchases_count ?? 0}</dd>
                </div>
              </dl>

              {book.tags?.length > 0 && (
                <div className="flex flex-wrap gap-2">
                  {book.tags.map((tag) => (
                    <span key={tag.id} className="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs">
                      {tag.name}
                    </span>
                  ))}
                </div>
              )}

              <p className="text-sm text-gray-600">{book.description}</p>

              {book.review_note && (
                <div className="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm">
                  <span className="font-semibold">{t('lastReviewNote')}: </span>
                  {book.review_note}
                </div>
              )}

              {book.file_path && (
                <a
                  href={adminBookPreviewUrl(book)}
                  target="_blank"
                  rel="noreferrer"
                  className="inline-block px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-700 hover:bg-gray-50"
                >
                  {t('previewFile')}
                </a>
              )}

              {book.status === 'pending' && (
                <div className="space-y-3 border-t border-gray-100 pt-5">
                  <label className="block text-sm font-medium text-gray-700">
                    {t('reviewNote')}
                  </label>
                  <textarea
                    rows={3}
                    value={note}
                    onChange={(e) => setNote(e.target.value)}
                    placeholder={t('reviewNotePlaceholder')}
                    className={inputClass}
                  />
                  <div className="flex flex-wrap gap-3">
                    <button
                      onClick={() => reviewMutation.mutate({ id: book.id, decision: 'approved' })}
                      disabled={reviewMutation.isPending}
                      className="px-4 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 disabled:opacity-60"
                    >
                      {t('approve')}
                    </button>
                    <button
                      onClick={() => reviewMutation.mutate({ id: book.id, decision: 'rejected' })}
                      disabled={reviewMutation.isPending}
                      className="px-4 py-2 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 disabled:opacity-60"
                    >
                      {t('reject')}
                    </button>
                  </div>
                </div>
              )}

              {book.status === 'approved' && (
                <div className="border-t border-gray-100 pt-5">
                  <button
                    onClick={() => publishMutation.mutate(book.id)}
                    disabled={publishMutation.isPending}
                    className="px-4 py-2 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 disabled:opacity-60"
                  >
                    {t('publishNow')}
                  </button>
                </div>
              )}

              {book.status === 'published' && (
                <div className="border-t border-gray-100 pt-5">
                  <Link
                    to={`/admin/books/${book.id}`}
                    className="inline-block px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-700 hover:bg-gray-50 mr-3"
                  >
                    {t('edit')}
                  </Link>
                  <button
                    onClick={() => unpublishMutation.mutate(book.id)}
                    disabled={unpublishMutation.isPending}
                    className="px-4 py-2 border border-amber-300 text-amber-700 font-medium rounded-xl hover:bg-amber-50 disabled:opacity-60"
                  >
                    {t('unpublish')}
                  </button>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {paginator && paginator.last_page > 1 && (
        <div className="flex items-center justify-center gap-3 mt-6">
          <button
            onClick={() => setPage((p) => Math.max(1, p - 1))}
            disabled={paginator.current_page <= 1}
            className="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40"
          >
            {t('previous')}
          </button>
          <span className="text-sm text-gray-500">
            {paginator.current_page} / {paginator.last_page}
          </span>
          <button
            onClick={() => setPage((p) => Math.min(paginator.last_page, p + 1))}
            disabled={paginator.current_page >= paginator.last_page}
            className="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40"
          >
            {t('next')}
          </button>
        </div>
      )}
    </div>
  )
}
