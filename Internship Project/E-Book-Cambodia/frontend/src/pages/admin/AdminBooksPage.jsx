import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { adminGetBooks, adminUpdateBook, adminDeleteBook, coverUrl } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

export default function AdminBooksPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')

  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-books', search, page],
    refetchInterval: 15000,
    queryFn: () => adminGetBooks({ search, per_page: 15, page }),
  })

  const books = data?.data?.data || []
  const pagination = data?.data
  const lastPage = pagination?.last_page || 1
  const perPage = pagination?.per_page || 15

  const deleteMutation = useMutation({
    mutationFn: (id) => adminDeleteBook(id),
    onSuccess: () => {
      queryClient.invalidateQueries(['admin-books'])
      queryClient.invalidateQueries(['admin-dashboard'])
    },
  })

  const publishMutation = useMutation({
    mutationFn: (data) => adminUpdateBook(data.id, { is_published: data.is_published }),
    onSuccess: () => {
      queryClient.invalidateQueries(['admin-books'])
    },
  })

  const handleDelete = (book) => {
    if (window.confirm(`${t('confirmDelete')} "${book.title}"?`)) {
      deleteMutation.mutate(book.id)
    }
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('booksManagement')}</h1>
          <p className="text-gray-500 text-sm mt-1">{books.length} {t('books')}</p>
        </div>
        <div className="flex gap-3">
          <input
            type="text"
            placeholder={t('search')}
            value={search}
            onChange={(e) => { setSearch(e.target.value); setPage(1) }}
            className="px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <Link
            to="/admin/books/new"
            className="px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 whitespace-nowrap"
          >
            + {t('addBook')}
          </Link>
        </div>
      </div>

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : books.length === 0 ? (
          <div className="p-8 text-center text-gray-500">{t('noBooks')}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('title')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('bookCategories')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('views')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('price')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('bookType')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('status')}</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('edit')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {books.map((book, index) => (
                  <tr key={book.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {(page - 1) * perPage + index + 1}
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        {book.cover_image ? (
                          <img
                            src={coverUrl(book)}
                            alt={book.title}
                            className="w-10 h-14 object-contain bg-white p-1.5 rounded"
                          />
                        ) : (
                          <div className="w-10 h-14 rounded bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-[10px] text-white font-bold px-1">
                            {book.title?.slice(0, 12)}
                          </div>
                        )}
                        <div className="text-sm font-medium text-gray-900 max-w-[200px] truncate">
                          {book.title}
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">{book.category?.name || '-'}</td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {Number(book.view_count || 0).toLocaleString()}
                    </td>
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">
                      {book.is_free ? t('free') : `$${book.price}`}
                    </td>
                    <td className="px-6 py-4 text-sm">
                      <span className={`text-xs font-medium px-2 py-1 rounded-full ${
                        book.is_free ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700'
                      }`}>
                        {book.is_free ? t('free') : t('paid')}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm">
                      <button
                        onClick={() => publishMutation.mutate({ id: book.id, is_published: !book.is_published })}
                        className={`text-xs font-medium px-2 py-1 rounded-full ${
                          book.is_published ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'
                        }`}
                      >
                        {book.is_published ? t('published') : t('draft')}
                      </button>
                    </td>
                    <td className="px-6 py-4 text-right text-sm space-x-2 whitespace-nowrap">
                      <Link to={`/admin/books/${book.id}/edit`} className="text-indigo-600 hover:text-indigo-800">
                        {t('edit')}
                      </Link>
                      <button
                        onClick={() => handleDelete(book)}
                        className="text-red-600 hover:text-red-800"
                      >
                        {t('delete')}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {lastPage > 1 && (
        <div className="mt-6 flex items-center justify-center gap-4">
          <button
            disabled={page <= 1}
            onClick={() => setPage((p) => Math.max(1, p - 1))}
            className="px-4 py-2 text-sm border border-gray-200 rounded-xl disabled:opacity-40 hover:bg-gray-50"
          >
            {t('previous')}
          </button>
          <span className="text-sm text-gray-600">
            Page {page} of {lastPage}
          </span>
          <button
            disabled={page >= lastPage}
            onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
            className="px-4 py-2 text-sm border border-gray-200 rounded-xl disabled:opacity-40 hover:bg-gray-50"
          >
            {t('next')}
          </button>
        </div>
      )}
    </div>
  )
}
