import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { authorGetBooks, authorDeleteBook, authorSubmitBook, coverUrl } from '../../api'
import BookStatusBadge from '../../components/BookStatusBadge'
import { useLanguage } from '../../context/LanguageContext'

const WORKFLOW_STATUSES = ['draft', 'pending', 'approved', 'rejected', 'published']

export default function AuthorBooksPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['author-books', { search, status, page }],
    refetchInterval: 15000,
    queryFn: () => authorGetBooks({ search: search || undefined, status: status || undefined, page }),
    placeholderData: (prev) => prev,
  })

  const books = data?.data?.data || []
  const paginator = data?.data

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['author-books'] })
    queryClient.invalidateQueries({ queryKey: ['author-stats'] })
  }

  const deleteMutation = useMutation({
    mutationFn: (id) => authorDeleteBook(id),
    onSuccess: invalidate,
  })

  const submitMutation = useMutation({
    mutationFn: (id) => authorSubmitBook(id),
    onSuccess: invalidate,
  })

  const handleDelete = (book) => {
    if (window.confirm(`${t('confirmDelete')} "${book.title}"?`)) {
      deleteMutation.mutate(book.id)
    }
  }

  const handleSubmit = (book) => {
    if (window.confirm(`${t('confirmSubmit')} "${book.title}"?`)) {
      submitMutation.mutate(book.id)
    }
  }

  const inputClass =
    'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-500'

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('myBooks')}</h1>
          <p className="text-gray-500 text-sm mt-1">
            {paginator?.total ?? 0} {t('books')}
          </p>
        </div>
        <Link to="/author/books/new" className="px-4 py-2 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700">
          + {t('addBook')}
        </Link>
      </div>

      <div className="grid md:grid-cols-2 gap-4 mb-6">
        <input
          type="text"
          value={search}
          onChange={(e) => {
            setSearch(e.target.value)
            setPage(1)
          }}
          placeholder="Search..."
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
          <option value="">{t('filterAll')}</option>
          {WORKFLOW_STATUSES.map((value) => (
            <option key={value} value={value}>
              {t(`status_${value}`)}
            </option>
          ))}
        </select>
      </div>

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : books.length === 0 ? (
          <div className="p-8 text-center text-gray-500">{t('noAuthorBooks')}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('title')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('bookCategories')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('views')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('bookSold')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('status')}</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('edit')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {books.map((book) => {
                  const editable = book.status === 'draft' || book.status === 'rejected'

                  return (
                    <tr key={book.id} className="hover:bg-gray-50 align-top">
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          {book.cover_image ? (
                            <img src={coverUrl(book)} alt={book.title} className="w-9 h-12 object-contain bg-white p-1.5 rounded-md shrink-0" />
                          ) : (
                            <div className="w-9 h-12 rounded-md bg-indigo-50 flex items-center justify-center text-base shrink-0">📖</div>
                          )}
                          <div>
                            <span className="text-sm font-medium text-gray-900">{book.title}</span>
                            {book.status === 'rejected' && book.review_note && (
                              <p className="mt-1 text-xs text-red-600">{book.review_note}</p>
                            )}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600">{book.category?.name || '—'}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">{book.view_count ?? 0}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">{book.purchases_count ?? 0}</td>
                      <td className="px-6 py-4">
                        <BookStatusBadge book={book} />
                      </td>
                      <td className="px-6 py-4">
                        <div className="flex items-center justify-end gap-4">
                          {editable ? (
                            <>
                              <Link to={`/author/books/${book.id}`} className="text-indigo-600 hover:text-indigo-800">
                                {t('edit')}
                              </Link>
                              <button
                                onClick={() => handleSubmit(book)}
                                disabled={submitMutation.isPending}
                                className="text-amber-600 hover:text-amber-800 disabled:opacity-50"
                              >
                                {book.status === 'rejected' ? t('resubmit') : t('submitForReview')}
                              </button>
                              <button
                                onClick={() => handleDelete(book)}
                                disabled={deleteMutation.isPending}
                                className="text-red-600 hover:text-red-800 disabled:opacity-50"
                              >
                                {t('delete')}
                              </button>
                            </>
                          ) : (
                            <span className="text-sm text-gray-400">{t('lockedForReview')}</span>
                          )}
                        </div>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
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