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

export default function AdminPlans() {
  const { t } = useI18n()
  const queryClient = useQueryClient()
  const [editing, setEditing] = useState(null)
  const [modalOpen, setModalOpen] = useState(false)
  const [form, setForm] = useState(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-plans'],
    queryFn: async () => {
      const res = await api.get('/admin/vip/plans')
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
      const payload = {
        slug: form.slug,
        price: Number(form.price),
        duration_days: Number(form.duration_days),
        is_active: form.is_active,
        sort_order: Number(form.sort_order),
        features: (form.features || '')
          .split('\n')
          .map((f) => f.trim())
          .filter(Boolean),
        translations,
      }
      return editing ? api.put(`/admin/vip/plans/${editing.id}`, payload) : api.post('/admin/vip/plans', payload)
    },
    onSuccess: () => {
      setModalOpen(false)
      queryClient.invalidateQueries({ queryKey: ['admin-plans'] })
      queryClient.invalidateQueries({ queryKey: ['vip-plans'] })
    },
  })

  const remove = useMutation({
    mutationFn: (id) => api.delete(`/admin/vip/plans/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-plans'] })
      queryClient.invalidateQueries({ queryKey: ['vip-plans'] })
    },
  })

  const openCreate = () => {
    setEditing(null)
    setForm({ slug: '', price: 4.99, duration_days: 30, is_active: true, sort_order: 0, features: '', translations: emptyTranslations() })
    setModalOpen(true)
  }

  const openEdit = (plan) => {
    setEditing(plan)
    const translations = emptyTranslations()
    Object.entries(plan.translations ?? {}).forEach(([loc, fields]) => {
      translations[loc] = { ...translations[loc], ...fields }
    })
    setForm({
      slug: plan.slug,
      price: plan.price,
      duration_days: plan.duration_days,
      is_active: Boolean(plan.is_active),
      sort_order: plan.sort_order ?? 0,
      features: (plan.features ?? []).join('\n'),
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

  const plans = data?.plans ?? []

  return (
    <div>
      <div className="flex items-center justify-between">
        <div>
          <p className="section-eyebrow">Admin</p>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">{t('admin.plans')}</h1>
        </div>
        <button type="button" onClick={openCreate} className="btn-primary">
          + {t('common.create')}
        </button>
      </div>

      <div className="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase text-slate-500">
            <tr>
              <th className="px-4 py-3">{t('admin.titleField')}</th>
              <th className="px-4 py-3">{t('admin.slug')}</th>
              <th className="px-4 py-3">{t('common.price')}</th>
              <th className="px-4 py-3">{t('admin.durationDays')}</th>
              <th className="px-4 py-3">{t('admin.isActive')}</th>
              <th className="px-4 py-3">{t('common.actions')}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {plans.map((plan) => (
              <tr key={plan.id} className="hover:bg-slate-50">
                <td className="px-4 py-3 font-medium text-slate-900">{plan.name}</td>
                <td className="px-4 py-3 text-slate-500">{plan.slug}</td>
                <td className="px-4 py-3 font-semibold text-slate-900">${plan.price}</td>
                <td className="px-4 py-3 text-slate-600">{plan.duration_days}</td>
                <td className="px-4 py-3">
                  <span
                    className={`rounded-full px-2.5 py-1 text-xs font-medium ${
                      plan.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'
                    }`}
                  >
                    {String(plan.is_active)}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <div className="flex gap-2">
                    <button
                      type="button"
                      onClick={() => openEdit(plan)}
                      className="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                    >
                      {t('common.edit')}
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        if (window.confirm(t('admin.confirmDelete'))) remove.mutate(plan.id)
                      }}
                      className="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100"
                    >
                      {t('common.delete')}
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {plans.length === 0 && (
              <tr>
                <td colSpan="6" className="px-4 py-8 text-center text-sm text-slate-500">
                  {t('admin.noData')}
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={editing ? t('common.edit') : t('common.create')} width="max-w-2xl">
        {form && (
          <div className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-3">
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
                <span className="mb-1 block font-medium text-slate-700">{t('admin.durationDays')}</span>
                <input
                  type="number"
                  min="1"
                  value={form.duration_days}
                  onChange={(e) => set('duration_days', e.target.value)}
                  className={inputClass}
                />
              </label>
              <label className="block text-sm">
                <span className="mb-1 block font-medium text-slate-700">{t('admin.sortOrder')}</span>
                <input
                  type="number"
                  min="0"
                  value={form.sort_order}
                  onChange={(e) => set('sort_order', e.target.value)}
                  className={inputClass}
                />
              </label>
              <label className="flex items-end gap-2 pb-2 text-sm font-medium text-slate-700">
                <input
                  type="checkbox"
                  checked={form.is_active}
                  onChange={(e) => set('is_active', e.target.checked)}
                  className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20"
                />
                {t('admin.isActive')}
              </label>
            </div>

            <label className="block text-sm">
              <span className="mb-1 block font-medium text-slate-700">{t('admin.features')}</span>
              <textarea
                value={form.features}
                onChange={(e) => set('features', e.target.value)}
                rows="3"
                className={inputClass}
              />
            </label>

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