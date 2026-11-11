import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  adminGetAuthors,
  adminGetAuthor,
  adminCreateAuthor,
  adminUpdateAuthor,
  adminDeleteAuthor,
  adminUpdateAuthorStatus,
  adminResetAuthorPassword,
  adminVerifyAuthorEmail,
  authorPhotoUrl,
} from '../../api'
import { useLanguage } from '../../context/LanguageContext'

export default function AdminAuthorsPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [editing, setEditing] = useState(null)
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    status: 'active',
    specialization: '',
    organization: '',
    school: '',
    bio: '',
  })
  const [error, setError] = useState('')
  const [viewId, setViewId] = useState(null)
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-authors', { search, statusFilter, page }],
    refetchInterval: 15000,
    queryFn: () => adminGetAuthors({ search: search || undefined, status: statusFilter || undefined, page }),
    placeholderData: (prev) => prev,
  })

  const authors = data?.data?.authors?.data || []
  const paginator = data?.data?.authors

  const { data: salesData, isLoading: salesLoading } = useQuery({
    queryKey: ['admin-author-sales', viewId],
    refetchInterval: 15000,
    queryFn: () => adminGetAuthor(viewId),
    enabled: !!viewId,
  })

  const sales = salesData?.data

  const resetFields = () => {
    setEditing(null)
    setForm({
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      phone: '',
      status: 'active',
      specialization: '',
      organization: '',
      school: '',
      bio: '',
    })
    setError('')
  }

  const advanceQuery = () => {
    queryClient.invalidateQueries({ queryKey: ['admin-authors'] })
    queryClient.invalidateQueries({ queryKey: ['authors-all'] })
    setShowForm(false)
    resetFields()
  }

  const formatErrors = (err) => {
    const errors = err.response?.data?.errors
    return errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message
  }

  const createMutation = useMutation({
    mutationFn: (data) => adminCreateAuthor(data),
    onSuccess: advanceQuery,
    onError: (err) => setError(formatErrors(err)),
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, data }) => adminUpdateAuthor(id, data),
    onSuccess: advanceQuery,
    onError: (err) => setError(formatErrors(err)),
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => adminDeleteAuthor(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-authors'] })
      queryClient.invalidateQueries({ queryKey: ['authors-all'] })
    },
  })

  const statusMutation = useMutation({
    mutationFn: ({ id, status }) => adminUpdateAuthorStatus(id, status),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-authors'] }),
    onError: (err) => setError(formatErrors(err)),
  })

  const resetMutation = useMutation({
    mutationFn: ({ id, password }) => adminResetAuthorPassword(id, { password, password_confirmation: password }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-authors'] }),
    onError: (err) => setError(formatErrors(err)),
  })

  const verifyMutation = useMutation({
    mutationFn: (id) => adminVerifyAuthorEmail(id),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin-authors'] }),
    onError: (err) => setError(formatErrors(err)),
  })

  const handleSubmit = (e) => {
    e.preventDefault()
    setError('')
    if (editing) {
      const data = { ...form }
      if (!data.password) {
        delete data.password
        delete data.password_confirmation
      }
      updateMutation.mutate({ id: editing.id, data })
    } else {
      createMutation.mutate(form)
    }
  }

  const startEdit = (author) => {
    setEditing(author)
    setShowForm(true)
    setForm({
      name: author.name || '',
      email: author.email || '',
      password: '',
      password_confirmation: '',
      phone: author.phone || '',
      status: author.status || 'active',
      specialization: author.specialization || '',
      organization: author.organization || '',
      school: author.school || '',
      bio: author.bio || '',
    })
  }

  const handleDelete = (author) => {
    const message =
      author.books_count > 0
        ? `${t('confirmDelete')} "${author.name}"? ${t('deleteAuthorBooksWarning')}`
        : `${t('confirmDelete')} "${author.name}"?`
    if (window.confirm(message)) {
      deleteMutation.mutate(author.id)
    }
  }

  const handleResetPassword = (author) => {
    const password = window.prompt(`${t('resetAuthorPassword')}: ${author.name}`)
    if (!password) return
    resetMutation.mutate({ id: author.id, password })
  }

  const handleToggleStatus = (author) => {
    const target = author.status === 'suspended' ? 'active' : 'suspended'
    if (
      window.confirm(
        `${t('confirmDelete')} ${target === 'suspended' ? t('suspendAuthor') : t('activateAuthor')} "${author.name}"?`
      )
    ) {
      statusMutation.mutate({ id: author.id, status: target })
    }
  }

  const inputClass =
    'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500'

  const StatusBadge = ({ author }) =>
    author.status === 'suspended' ? (
      <span className="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
        {t('suspended')}
      </span>
    ) : (
      <span className="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
        {t('active')}
      </span>
    )

  const VerifiedBadge = ({ author }) =>
    author.email_verified_at ? (
      <span className="inline-block px-2 py-0.5 rounded-full text-[11px] font-medium bg-green-50 text-green-700">
        ✓ {t('authorEmailVerified')}
      </span>
    ) : (
      <span className="inline-block px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-600">
        {t('authorEmailUnverified')}
      </span>
    )

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('authorsManagement')}</h1>
          <p className="text-gray-500 text-sm mt-1">
            {paginator?.total ?? 0} {t('authors')}
          </p>
        </div>
        <button
          onClick={() => {
            setShowForm((v) => !v)
            resetFields()
          }}
          className="px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700"
        >
          + {t('addAuthor')}
        </button>
      </div>

      {error && (
        <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>
      )}

      {showForm && (
        <form onSubmit={handleSubmit} className="bg-white rounded-2xl shadow-sm p-6 mb-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            {editing ? t('editAuthor') : t('addAuthor')}
          </h2>
          <div className="grid md:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorName')} *</label>
              <input type="text" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('email')} *</label>
              <input
                type="email"
                required={!editing}
                value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
                className={inputClass}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {editing ? `${t('password')} (${t('optional')})` : `${t('password')} *`}
              </label>
              <input
                type="password"
                autoComplete="new-password"
                required={!editing}
                value={form.password}
                onChange={(e) => setForm({ ...form, password: e.target.value })}
                className={inputClass}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('confirmPassword')}</label>
              <input
                type="password"
                autoComplete="new-password"
                required={!editing}
                value={form.password_confirmation}
                onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })}
                className={inputClass}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('phone')}</label>
              <input type="text" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('status')}</label>
              <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value })} className={inputClass}>
                <option value="active">{t('active')}</option>
                <option value="suspended">{t('suspended')}</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorSpecialization')}</label>
              <input type="text" value={form.specialization} onChange={(e) => setForm({ ...form, specialization: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorOrganization')}</label>
              <input type="text" value={form.organization} onChange={(e) => setForm({ ...form, organization: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorSchool')}</label>
              <input type="text" value={form.school} onChange={(e) => setForm({ ...form, school: e.target.value })} className={inputClass} />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('authorBio')}</label>
              <textarea rows={2} value={form.bio} onChange={(e) => setForm({ ...form, bio: e.target.value })} className={inputClass} />
            </div>
          </div>
          <div className="flex gap-3 mt-4">
            <button
              type="submit"
              disabled={createMutation.isPending || updateMutation.isPending}
              className="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-60"
            >
              {t('save')}
            </button>
            <button
              type="button"
              onClick={() => { setShowForm(false); setEditing(null); setError('') }}
              className="px-6 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50"
            >
              {t('cancel')}
            </button>
          </div>
        </form>
      )}

      <div className="grid md:grid-cols-2 gap-4 mb-6">
        <input
          type="text"
          value={search}
          onChange={(e) => {
            setSearch(e.target.value)
            setPage(1)
          }}
          placeholder={t('search')}
          className={inputClass}
        />
        <select
          value={statusFilter}
          onChange={(e) => {
            setStatusFilter(e.target.value)
            setPage(1)
          }}
          className={inputClass}
        >
          <option value="">{t('filterAll')}</option>
          <option value="active">{t('active')}</option>
          <option value="suspended">{t('suspended')}</option>
        </select>
      </div>

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : authors.length === 0 ? (
          <div className="p-8 text-center text-gray-500">{t('noAuthors')}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('authorName')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('email')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('books')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('status')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('email')}</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('actions')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {authors.map((author) => (
                  <tr key={author.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 text-sm text-gray-500">{author.id}</td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        {author.photo ? (
                          <img src={authorPhotoUrl(author)} alt="" className="w-8 h-8 rounded-full object-cover bg-gray-100" />
                        ) : (
                          <div className="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-sm">✍️</div>
                        )}
                        <div>
                          <p className="text-sm font-medium text-gray-900">{author.name}</p>
                          {author.last_login_at && (
                            <p className="text-[11px] text-gray-400">
                              {t('authorLastLogin')}: {new Date(author.last_login_at).toLocaleDateString()}
                            </p>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">{author.email || '—'}</td>
                    <td className="px-6 py-4 text-sm text-gray-600">{author.books_count ?? 0}</td>
                    <td className="px-6 py-4"><StatusBadge author={author} /></td>
                    <td className="px-6 py-4"><VerifiedBadge author={author} /></td>
                    <td className="px-6 py-4">
                      <div className="flex items-center justify-end gap-2 flex-wrap max-w-md ml-auto">
                        <button onClick={() => setViewId(author.id)} className="text-sm text-gray-500 hover:text-gray-800">
                          {t('viewBooks')}
                        </button>
                        {!author.email_verified_at && (
                          <button onClick={() => verifyMutation.mutate(author.id)} className="text-sm text-green-600 hover:text-green-800">
                            {t('verifyEmail')}
                          </button>
                        )}
                        <button onClick={() => handleResetPassword(author)} className="text-sm text-amber-600 hover:text-amber-800">
                          {t('resetPassword')}
                        </button>
                        <button onClick={() => handleToggleStatus(author)} className="text-sm text-orange-600 hover:text-orange-800">
                          {author.status === 'suspended' ? t('activateAuthor') : t('suspendAuthor')}
                        </button>
                        <button onClick={() => startEdit(author)} className="text-sm text-indigo-600 hover:text-indigo-800">
                          {t('edit')}
                        </button>
                        <button onClick={() => handleDelete(author)} className="text-sm text-red-600 hover:text-red-800">
                          {t('delete')}
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
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

      {viewId && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setViewId(null)} />
          <div className="relative w-full max-w-2xl max-h-[85vh] overflow-y-auto bg-white rounded-2xl shadow-2xl">
            <div className="p-6 border-b border-gray-100 sticky top-0 bg-white z-10 flex items-center justify-between">
              <div>
                <p className="text-xs font-semibold uppercase tracking-wider text-indigo-600">{t('authorSales')}</p>
                <h2 className="text-lg font-bold text-gray-900">
                  {sales?.author?.name}
                  {sales?.author?.email ? <span className="text-sm font-normal text-gray-400"> · {sales.author.email}</span> : null}
                </h2>
              </div>
              <button onClick={() => setViewId(null)} className="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500">✕</button>
            </div>

            {salesLoading ? (
              <div className="p-8 text-center text-gray-500">{t('loading')}</div>
            ) : sales ? (
              <div className="p-6 space-y-6">
                {sales.summary && (
                  <div className="grid grid-cols-3 gap-4">
                    {[
                      { label: t('books'), value: sales.summary.books, tint: 'bg-indigo-50 text-indigo-700' },
                      { label: t('soldBooks'), value: sales.summary.sold, tint: 'bg-emerald-50 text-emerald-700' },
                      { label: t('totalRevenue'), value: `$${sales.summary.revenue.toLocaleString(undefined, { minimumFractionDigits: 2 })}`, tint: 'bg-amber-50 text-amber-700' },
                    ].map((stat) => (
                      <div key={stat.label} className={`rounded-xl p-4 ${stat.tint}`}>
                        <p className="text-2xl font-bold">{stat.value}</p>
                        <p className="text-xs opacity-80">{stat.label}</p>
                      </div>
                    ))}
                  </div>
                )}

                {sales.books?.length > 0 ? (
                  <div className="space-y-3">
                    {sales.books.map((book) => (
                      <div key={book.id} className="flex items-center gap-4 p-4 rounded-xl border border-gray-100 hover:bg-gray-50">
                        {book.cover_image ? (
                          <img src={book.cover_image} alt="" className="w-12 h-16 object-contain bg-white p-1.5 rounded-lg shrink-0" />
                        ) : (
                          <div className="shrink-0 w-12 h-16 rounded-lg bg-indigo-50 flex items-center justify-center text-lg">📖</div>
                        )}
                        <div className="min-w-0 flex-1">
                          <p className="text-sm font-semibold text-gray-900 truncate">{book.title}</p>
                          <p className="text-xs text-gray-500">{book.category?.name || '—'}</p>
                        </div>
                        <div className="text-right shrink-0">
                          <p className="text-sm font-bold text-gray-900">${book.revenue.toLocaleString(undefined, { minimumFractionDigits: 2 })}</p>
                          <p className="text-xs text-gray-500">{book.purchases_count} {t('soldBooks')}</p>
                          <p className="text-xs text-gray-500">
                            {Number(book.view_count || 0).toLocaleString()} {t('views')}
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-center text-gray-400 text-sm py-6">{t('noBooks')}</p>
                )}
              </div>
            ) : null}
          </div>
        </div>
      )}
    </div>
  )
}
