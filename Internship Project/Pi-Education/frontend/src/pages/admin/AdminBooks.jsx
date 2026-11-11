import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import api from '../../api/client'
import Loading from '../../components/common/Loading'
import ErrorState from '../../components/common/ErrorState'
import Modal from '../../components/common/Modal'
import { LANGUAGES, useI18n } from '../../i18n'

const LOCALES = ['en', 'km', 'zh', 'fr']
const emptyTranslations = () =>
  Object.fromEntries(LOCALES.map((l) => [l, { title: '', description: '', content: '' }]))

function buildPayload(form) {
  const translations = {}
  Object.entries(form.translations).forEach(([loc, fields]) => {
    const title = (fields.title || '').trim()
    if (title) {
      translations[loc] = {
        title,
        description: (fields.description || '').trim(),
        content: (fields.content || '').trim(),
      }
    }
  })
  return {
    slug: form.slug,
    type: form.type,
    price: Number(form.price),
    status: form.status,
    language: form.language,
    cover_image: form.cover_image || null,
    category_ids: form.category_ids,
    author_ids: form.author_ids,
    translations,
  }
}

const inputClass =
  'w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 transition hover:border-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 focus:outline-none'

export default function AdminBooks() {
  const { t } = useI18n()
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-books', page, search],
    queryFn: async () => {
      const res = await api.get('/admin/books', { params: { page, per_page: 10, search } })
      return res.data.data
    },
  })

  const { data: meta } = useQuery({
    queryKey: ['admin-options'],
    queryFn: async () => {
      const [cats, auth] = await Promise.all([
        api.get('/categories', { params: { per_page: 100 } }),
        api.get('/authors', { params: { per_page: 100 } }),
      ])
      return {
        categories: cats.data.data.categories ?? cats.data.data,
        authors: auth.data.data.authors ?? auth.data.data,
      }
    },
  })

  const save = useMutation({
    mutationFn: async () => {
      const payload = buildPayload(form)
      if (editing) {
        return api.put(`/admin/books/${editing.id}`, payload)
      }
      return api.post('/admin/books', payload)
    },
    onSuccess: () => {
      setModalOpen(false)
      queryClient.invalidateQueries({ queryKey: ['admin-books'] })
      queryClient.invalidateQueries({ queryKey: ['admin-dashboard'] })
    },
  })

  const remove = useMutation({
    mutationFn: (id) => api.delete(`/admin/books/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-books'] })
      queryClient.invalidateQueries({ queryKey: ['admin-dashboard'] })
    },
  })

  const openCreate = () => {
    setEditing(null)
    setForm({
      slug: '',
      type: 'FREE',
      price: 0,
      status: 'DRAFT',
      language: 'km',
      cover_image: '',
      category_ids: [],
      author_ids: [],
      translations: emptyTranslations(),
    })
    setModalOpen(true)
  }

  const openEdit = (book) => {
    setEditing(book)
    const translations = emptyTranslations()
    Object.entries(book.translations ?? {}).forEach(([loc, fields]) => {
      translations[loc] = { ...translations[loc], ...fields }
    })
    setForm({
      slug: book.slug,
      type: book.type,
      price: book.price,
      status: book.status,
      language: book.language,
      cover_image: book.cover_image || '',
      category_ids: book.category_ids ?? [],
      author_ids: book.author_ids ?? [],
      translations,
    })
    setModalOpen(true)
  }

  const set = (key, value) => setForm((f) => ({ ...f, [key]: value }))
  const setTranslation = (loc, key, value) =>
    setForm((f) => ({
      ...f,
      translations: { ...f.translations, [loc]: { ...f.translations[loc], [key]: value } },
    }))

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState />

  const books = data?.books ?? []
  const pagination = data?.pagination

  return (
    <div>
      <div className="flex items-center justify-between gap-4">
        <div>
          <p className="section-eyebrow">Admin</p>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">{t('admin.books')}</h1>
        </div>
        <button type="button" onClick={openCreate} className="btn-primary">
          + {t('admin.addBook')}
        </button>
      </div>

      <input
        value={search}
        onChange={(e) => {
          setSearch(e.target.value)
          setPage(1)
        }}
        placeholder={t('admin.searchPlaceholder')}
        className="input mt-6 max-w-sm"
      />

      <div className="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
            <tr>
              <th className="px-4 py-3">{t('admin.titleField')}</th>
              <th className="px-4 py-3">{t('admin.slug')}</th>
              <th className="px-4 py-3">{t('admin.type')}</th>
              <th className="px-4 py-3">{t('common.status')}</th>
              <th className="px-4 py-3">{t('admin.language')}</th>
              <th className="px-4 py-3">{t('common.actions')}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {books.map((book) => (
              <tr key={book.id} className="hover:bg-slate-50">
                <td className="px-4 py-3 font-medium text-slate-900">
                  {book.translations?.en?.title || '—'}
                </td>
                <td className="px-4 py-3 text-slate-500">{book.slug}</td>
                <td className="px-4 py-3">
                  <span
                    className={`inline-block rounded-full px-2.5 py-1 text-xs font-semibold ${
                      book.type === 'FREE' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700'
                    }`}
                  >
                    {book.type}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <span
                    className={`inline-block rounded-full px-2.5 py-1 text-xs font-semibold ${
                      book.status === 'PUBLISHED'
                        ? 'bg-emerald-100 text-emerald-700'
                        : book.status === 'DRAFT'
                          ? 'bg-amber-100 text-amber-700'
                          : 'bg-slate-100 text-slate-600'
                    }`}
                  >
                    {book.status}
                  </span>
                </td>
                <td className="px-4 py-3 uppercase text-slate-500">{book.language}</td>
                <td className="px-4 py-3">
                  <div className="flex gap-2">
                    <button
                      type="button"
                      onClick={() => openEdit(book)}
                      className="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                    >
                      {t('common.edit')}
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        if (window.confirm(t('admin.confirmDelete'))) remove.mutate(book.id)
                      }}
                      className="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100"
                    >
                      {t('common.delete')}
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {books.length === 0 && (
              <tr>
                <td colSpan="6" className="px-4 py-8 text-center text-sm text-slate-500">
                  {t('admin.noData')}
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {pagination && pagination.last_page > 1 && (
        <div className="mt-4 flex justify-center gap-2">
          <button
            type="button"
            disabled={page <= 1}
            onClick={() => setPage((p) => p - 1)}
            className="btn-secondary px-3 py-1.5"
          >
            ←
          </button>
          <span className="px-3 py-1.5 text-sm text-slate-600">
            {pagination.current_page} / {pagination.last_page}
          </span>
          <button
            type="button"
            disabled={page >= pagination.last_page}
            onClick={() => setPage((p) => p + 1)}
            className="btn-secondary px-3 py-1.5"
          >
            →
          </button>
        </div>
      )}

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editing ? t('admin.editBook') : t('admin.addBook')} width="max-w-3xl">
        {form && (
          <div className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <label className="block text-sm">
                <span className="mb-1 block font-medium text-slate-700">{t('admin.slug')}</span>
                <input value={form.slug} onChange={(e) => set('slug', e.target.value)} className={inputClass} />
              </label>
              <label className="block text-sm">
                <span className="mb-1 block font-medium text-slate-700">{t('common.price')}</span>
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  value={form.price}
                  onChange={(e) => set('price', e.target.value)}
                  className={inputClass}
                />
              </label>
              <label className="block text-sm">
                <span className="mb-1 block font-medium text-slate-700">{t('admin.type')}</span>
                <select value={form.type} onChange={(e) => set('type', e.target.value)} className={inputClass}>
                  <option value="FREE">{t('admin.free')}</option>
                  <option value="PAID">{t('admin.paid')}</option>
                </select>
              </label>
              <label className="block text-sm">
                <span className="mb-1 block font-medium text-slate-700">{t('common.status')}</span>
                <select value={form.status} onChange={(e) => set('status', e.target.value)} className={inputClass}>
                  <option value="DRAFT">{t('admin.statusDraft')}</option>
                  <option value="PUBLISHED">{t('admin.statusPublished')}</option>
                  <option value="ARCHIVED">{t('admin.statusArchived')}</option>
                </select>
              </label>
              <label className="block text-sm">
                <span className="mb-1 block font-medium text-slate-700">{t('admin.language')}</span>
                <select value={form.language} onChange={(e) => set('language', e.target.value)} className={inputClass}>
                  {LANGUAGES.map((lang) => (
                    <option key={lang.code} value={lang.code}>
                      {lang.native}
                    </option>
                  ))}
                </select>
              </label>
              <label className="block text-sm">
                <span className="mb-1 block font-medium text-slate-700">{t('admin.coverImage')}</span>
                <input
                  value={form.cover_image}
                  onChange={(e) => set('cover_image', e.target.value)}
                  className={inputClass}
                />
              </label>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <span className="mb-1 block text-sm font-medium text-slate-700">{t('admin.categoriesField')}</span>
                <div className="grid grid-cols-2 gap-2">
                  {(meta?.categories ?? []).map((cat) => (
                    <label key={cat.id} className="flex items-center gap-2 text-sm text-slate-700">
                      <input
                        type="checkbox"
                        checked={form.category_ids.includes(cat.id)}
                        onChange={(e) => {
                          const next = e.target.checked
                            ? [...form.category_ids, cat.id]
                            : form.category_ids.filter((id) => id !== cat.id)
                          set('category_ids', next)
                        }}
                        className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20"
                      />
                      {cat.name}
                    </label>
                  ))}
                </div>
              </div>
              <div>
                <span className="mb-1 block text-sm font-medium text-slate-700">{t('admin.authorsField')}</span>
                <div className="grid grid-cols-2 gap-2">
                  {(meta?.authors ?? []).map((author) => (
                    <label key={author.id} className="flex items-center gap-2 text-sm text-slate-700">
                      <input
                        type="checkbox"
                        checked={form.author_ids.includes(author.id)}
                        onChange={(e) => {
                          const next = e.target.checked
                            ? [...form.author_ids, author.id]
                            : form.author_ids.filter((id) => id !== author.id)
                          set('author_ids', next)
                        }}
                        className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20"
                      />
                      {author.name}
                    </label>
                  ))}
                </div>
              </div>
            </div>

            <div>
              <span className="mb-2 block text-sm font-medium text-slate-700">{t('admin.translations')}</span>
              <div className="grid gap-4 md:grid-cols-2">
                {LOCALES.map((loc) => (
                  <div key={loc} className="rounded-xl border border-slate-200 p-4">
                    <p className="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">{loc.toUpperCase()}</p>
                    <label className="block text-sm">
                      <span className="mb-1 block font-medium text-slate-700">{t('admin.titleField')}</span>
                      <input
                        value={form.translations[loc].title}
                        onChange={(e) => setTranslation(loc, 'title', e.target.value)}
                        className={inputClass}
                      />
                    </label>
                    <label className="mt-2 block text-sm">
                      <span className="mb-1 block font-medium text-slate-700">{t('admin.descriptionField')}</span>
                      <textarea
                        value={form.translations[loc].description}
                        onChange={(e) => setTranslation(loc, 'description', e.target.value)}
                        rows="2"
                        className={inputClass}
                      />
                    </label>
                    <label className="mt-2 block text-sm">
                      <span className="mb-1 block font-medium text-slate-700">{t('admin.contentField')}</span>
                      <textarea
                        value={form.translations[loc].content}
                        onChange={(e) => setTranslation(loc, 'content', e.target.value)}
                        rows="3"
                        className={inputClass}
                      />
                    </label>
                  </div>
                ))}
              </div>
            </div>

            {save.isError && <p className="text-sm text-red-600">{t('common.error')}</p>}

            <div className="flex justify-end gap-3">
              <button
                type="button"
                disabled={save.isPending}
                onClick={() => save.mutate()}
                className="btn-primary"
              >
                {save.isPending ? t('common.loading') : t('common.save')}
              </button>
            </div>
          </div>
        )}
      </Modal>
    </div>
  )
}