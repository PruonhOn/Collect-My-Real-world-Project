import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import api from '../../api/client'
import Loading from '../../components/common/Loading'
import ErrorState from '../../components/common/ErrorState'
import Modal from '../../components/common/Modal'
import { useI18n } from '../../i18n'

const LOCALES = ['en', 'km', 'zh', 'fr']
const emptyTranslations = () => Object.fromEntries(LOCALES.map((l) => [l, { name: '', description: '' }]))
const inputClass =
  'w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 transition hover:border-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 focus:outline-none'

export default function AdminCategories() {
  const { t } = useI18n()
  const queryClient = useQueryClient()
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState(null)
  const [modalOpen, setModalOpen] = useState(false)
  const [form, setForm] = useState(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-categories', page],
    queryFn: async () => {
      const res = await api.get('/admin/categories', { params: { page, per_page: 10 } })
      return res.data.data
    },
  })

  const save = useMutation({
    mutationFn: async () => {
      const translations = {}
      Object.entries(form.translations).forEach(([loc, fields]) => {
        if ((fields.name || '').trim()) {
          translations[loc] = { name: fields.name, description: (fields.description || '').trim() || null }
        }
      })
      const payload = { slug: form.slug, status: form.status, translations }
      return editing ? api.put(`/admin/categories/${editing.id}`, payload) : api.post('/admin/categories', payload)
    },
    onSuccess: () => {
      setModalOpen(false)
      queryClient.invalidateQueries({ queryKey: ['admin-categories'] })
      queryClient.invalidateQueries({ queryKey: ['admin-options'] })
      queryClient.invalidateQueries({ queryKey: ['admin-dashboard'] })
    },
  })

  const remove = useMutation({
    mutationFn: (id) => api.delete(`/admin/categories/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-categories'] })
    },
  })

  const openCreate = () => {
    setEditing(null)
    setForm({ slug: '', status: 'active', translations: emptyTranslations() })
    setModalOpen(true)
  }

  const openEdit = (cat) => {
    setEditing(cat)
    const translations = emptyTranslations()
    Object.entries(cat.translations ?? {}).forEach(([loc, fields]) => {
      translations[loc] = { ...translations[loc], ...fields }
    })
    setForm({ slug: cat.slug, status: cat.status === 'inactive' ? 'inactive' : 'active', translations })
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

  const categories = data?.categories ?? []
  const pagination = data?.pagination

  return (
    <div>
      <div className="flex items-center justify-between">
        <div>
          <p className="section-eyebrow">Admin</p>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">{t('admin.categories')}</h1>
        </div>
        <button type="button" onClick={openCreate} className="btn-primary">
          + {t('common.create')}
        </button>
      </div>

      <div className="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
            <tr>
              <th className="px-4 py-3">{t('common.name')}</th>
              <th className="px-4 py-3">{t('admin.slug')}</th>
              <th className="px-4 py-3">{t('common.status')}</th>
              <th className="px-4 py-3">{t('common.actions')}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {categories.map((cat) => (
              <tr key={cat.id} className="hover:bg-slate-50">
                <td className="px-4 py-3 font-medium text-slate-900">{cat.name}</td>
                <td className="px-4 py-3 text-slate-500">{cat.slug}</td>
                <td className="px-4 py-3">
                  <span
                    className={`inline-block rounded-full px-2.5 py-1 text-xs font-semibold ${
                      cat.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'
                    }`}
                  >
                    {cat.status}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <div className="flex gap-2">
                    <button
                      type="button"
                      onClick={() => openEdit(cat)}
                      className="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                    >
                      {t('common.edit')}
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        if (window.confirm(t('admin.confirmDelete'))) remove.mutate(cat.id)
                      }}
                      className="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100"
                    >
                      {t('common.delete')}
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {categories.length === 0 && (
              <tr>
                <td colSpan="4" className="px-4 py-8 text-center text-sm text-slate-500">
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

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editing ? t('common.edit') : t('common.create')} width="max-w-2xl">
        {form && (
          <div className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <label className="block text-sm">
                <span className="mb-1 block font-medium text-slate-700">{t('admin.slug')}</span>
                <input value={form.slug} onChange={(e) => set('slug', e.target.value)} className={inputClass} />
              </label>
              <label className="block text-sm">
                <span className="mb-1 block font-medium text-slate-700">{t('common.status')}</span>
                <select value={form.status} onChange={(e) => set('status', e.target.value)} className={inputClass}>
                  <option value="active">active</option>
                  <option value="inactive">inactive</option>
                </select>
              </label>
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
                        value={form.translations[loc].name}
                        onChange={(e) => setTranslation(loc, 'name', e.target.value)}
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
                  </div>
                ))}
              </div>
            </div>

            {save.isError && <p className="text-sm text-red-600">{t('common.error')}</p>}

            <div className="flex justify-end">
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