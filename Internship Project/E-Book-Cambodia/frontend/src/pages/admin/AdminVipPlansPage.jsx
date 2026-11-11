import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { adminDeleteVipPlan, adminGetVipPlans, adminUpdateVipPlan } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

const currency = new Intl.NumberFormat('en-US', {
  style: 'currency',
  currency: 'USD',
})

const emptyForm = {
  name: '',
  slug: '',
  description: '',
  price: '',
  is_active: true,
  is_test: false,
  duration_hours: '',
}

/**
 * VIP card management. Every plan — production card and test cards alike —
 * is edited here: price directly on the row, or the whole card (name, slug,
 * description, price, type, term, status) through the edit dialog. Values
 * always come from `GET /admin/vip-plans` and are written back with
 * `PUT /admin/vip-plans/{id}`; nothing is cached locally.
 *
 * `duration_days` is not editable: the API pins production cards to the
 * fixed one-year term, so only the hourly term of a test card can change.
 */
export default function AdminVipPlansPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()

  const [priceRowId, setPriceRowId] = useState(null)
  const [priceDraft, setPriceDraft] = useState('')
  const [editing, setEditing] = useState(null)
  const [form, setForm] = useState(emptyForm)
  const [error, setError] = useState('')
  const [saved, setSaved] = useState('')

  const { data, isLoading, isError, refetch, isFetching } = useQuery({
    queryKey: ['admin-vip-plans'],
    refetchInterval: 60000,
    queryFn: () => adminGetVipPlans().then((res) => res.data),
  })

  const plans = data?.plans || []

  const flash = (message) => {
    setSaved(message)
    setTimeout(() => setSaved(''), 3000)
  }

  const fail = (err) => {
    const errors = err.response?.data?.errors
    const first = errors ? Object.values(errors)[0][0] : null
    setError(first || err.response?.data?.message || t('error'))
    setTimeout(() => setError(''), 5000)
  }

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['admin-vip-plans'] })
    queryClient.invalidateQueries({ queryKey: ['vip-plans'] })
  }

  const updateMutation = useMutation({
    mutationFn: ({ id, data }) => adminUpdateVipPlan(id, data),
    onSuccess: () => {
      invalidate()
      setEditing(null)
      setPriceRowId(null)
      flash(t('vipPlanSaved'))
    },
    onError: fail,
  })

  const deleteMutation = useMutation({
    mutationFn: adminDeleteVipPlan,
    onSuccess: () => {
      invalidate()
      setEditing(null)
      flash(t('vipPlanSaved'))
    },
    onError: fail,
  })

  useEffect(() => {
    if (!editing) return undefined
    const onKeyDown = (e) => {
      if (e.key === 'Escape') setEditing(null)
    }
    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [editing])

  const startPriceEdit = (plan) => {
    setEditing(null)
    setError('')
    setSaved('')
    setPriceRowId(plan.id)
    setPriceDraft(String(Number(plan.price)))
  }

  const cancelPriceEdit = () => {
    setPriceRowId(null)
    setPriceDraft('')
    setError('')
  }

  const savePrice = (plan) => {
    const price = Number(priceDraft)
    if (!Number.isFinite(price) || price < 0.01) {
      setError(`${t('price')} ≥ 0.01`)
      return
    }
    updateMutation.mutate({ id: plan.id, data: { price } })
  }

  const openEditor = (plan) => {
    setPriceRowId(null)
    setError('')
    setSaved('')
    setEditing(plan)
    setForm({
      name: plan.name || '',
      slug: plan.slug || '',
      description: plan.description || '',
      price: String(Number(plan.price)),
      is_active: !!plan.is_active,
      is_test: !!plan.is_test,
      duration_hours: plan.duration_hours ?? '',
    })
  }

  const submitEditor = (e) => {
    e.preventDefault()
    setError('')

    const price = Number(form.price)
    if (!Number.isFinite(price) || price < 0.01) {
      setError(`${t('price')} ≥ 0.01`)
      return
    }
    if (form.is_test) {
      const hours = Number(form.duration_hours)
      if (!Number.isInteger(hours) || hours < 1) {
        setError(`${t('duration')} ≥ 1 ${t('hours')}`)
        return
      }
    }

    updateMutation.mutate({
      id: editing.id,
      data: {
        name: form.name,
        slug: form.slug,
        description: form.description,
        price,
        is_active: form.is_active,
        is_test: form.is_test,
        duration_hours: form.is_test ? Number(form.duration_hours) : null,
      },
    })
  }

  const confirmDelete = () => {
    if (!editing) return
    if (window.confirm(`${t('confirmDelete')} — ${editing.name}`)) {
      deleteMutation.mutate(editing.id)
    }
  }

  const fieldClass =
    'w-full px-3 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white'

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('adminVipPlans')}</h1>
          <p className="text-gray-500 text-sm mt-1">{t('adminVipPlansDesc')}</p>
        </div>
        <button
          type="button"
          onClick={() => refetch()}
          disabled={isFetching}
          className="self-start px-4 py-2 text-sm border border-gray-200 rounded-xl hover:bg-gray-50 disabled:opacity-60"
        >
          {isFetching ? t('loading') : t('refresh')}
        </button>
      </div>

      {saved && (
        <div role="status" className="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm">
          {saved}
        </div>
      )}
      {error && (
        <div role="alert" className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">
          {error}
        </div>
      )}

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500" role="status">
            {t('loading')}
          </div>
        ) : isError ? (
          <div className="p-8 text-center">
            <p className="text-red-600 mb-4">{t('error')}</p>
            <button type="button" onClick={() => refetch()} className="text-indigo-600 font-medium">
              {t('retry')}
            </button>
          </div>
        ) : plans.length === 0 ? (
          <div className="p-8 text-center text-gray-500">{t('noPlans')}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {t('name')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Slug
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {t('duration')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    Type
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {t('status')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {t('price')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {t('actions')}
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {plans.map((plan) => {
                  const quickEdit = priceRowId === plan.id
                  return (
                    <tr key={plan.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 text-sm text-gray-500">{plan.id}</td>
                      <td className="px-6 py-4">
                        <div className="text-sm font-medium text-gray-900">{plan.name}</div>
                        {plan.description && (
                          <div className="text-xs text-gray-500 mt-0.5 max-w-xs truncate">
                            {plan.description}
                          </div>
                        )}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600">{plan.slug}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">
                        {plan.is_test
                          ? `${plan.duration_hours ?? 0} ${t('hours')}`
                          : `${plan.duration_days ?? 365} ${t('days')}`}
                      </td>
                      <td className="px-6 py-4">
                        <span
                          className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${
                            plan.is_test
                              ? 'bg-amber-50 text-amber-700'
                              : 'bg-indigo-50 text-indigo-700'
                          }`}
                        >
                          {plan.is_test ? t('testOnly') : 'VIP'}
                        </span>
                      </td>
                      <td className="px-6 py-4">
                        <span
                          className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${
                            plan.is_active
                              ? 'bg-emerald-50 text-emerald-700'
                              : 'bg-gray-100 text-gray-500'
                          }`}
                        >
                          <span
                            className={`w-1.5 h-1.5 rounded-full ${
                              plan.is_active ? 'bg-emerald-500' : 'bg-gray-400'
                            }`}
                          />
                          {plan.is_active ? t('active') : t('inactive')}
                        </span>
                      </td>
                      <td className="px-6 py-4">
                        {quickEdit ? (
                          <div className="flex items-center gap-2">
                            <label className="sr-only" htmlFor={`price-${plan.id}`}>
                              {t('price')}
                            </label>
                            <div className="flex items-center gap-1">
                              <span className="text-sm text-gray-500">$</span>
                              <input
                                id={`price-${plan.id}`}
                                type="number"
                                step="0.01"
                                min="0.01"
                                autoFocus
                                value={priceDraft}
                                onChange={(e) => setPriceDraft(e.target.value)}
                                onKeyDown={(e) => {
                                  if (e.key === 'Enter') savePrice(plan)
                                  if (e.key === 'Escape') cancelPriceEdit()
                                }}
                                className="w-28 px-3 py-1.5 text-sm border border-indigo-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                              />
                            </div>
                            <button
                              type="button"
                              onClick={() => savePrice(plan)}
                              disabled={updateMutation.isPending}
                              className="px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-60"
                            >
                              {updateMutation.isPending ? t('loading') : t('save')}
                            </button>
                            <button
                              type="button"
                              onClick={cancelPriceEdit}
                              className="px-3 py-1.5 text-xs border border-gray-200 rounded-lg hover:bg-gray-50"
                            >
                              {t('cancel')}
                            </button>
                          </div>
                        ) : (
                          <div className="flex items-center gap-2">
                            <span className="text-sm font-semibold text-gray-900">
                              {currency.format(Number(plan.price))}
                            </span>
                            <button
                              type="button"
                              onClick={() => startPriceEdit(plan)}
                              aria-label={`${t('edit')} ${t('price')} — ${plan.name}`}
                              className="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs border border-gray-200 rounded-lg text-indigo-600 hover:bg-indigo-50"
                            >
                              <svg
                                className="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                              >
                                <path
                                  strokeLinecap="round"
                                  strokeLinejoin="round"
                                  strokeWidth="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                              </svg>
                              {t('edit')}
                            </button>
                          </div>
                        )}
                      </td>
                      <td className="px-6 py-4">
                        <button
                          type="button"
                          onClick={() => openEditor(plan)}
                          className="px-3 py-1.5 text-xs font-semibold border border-gray-200 rounded-lg hover:bg-gray-50"
                        >
                          {t('edit')}
                        </button>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <p className="mt-3 text-xs text-gray-400">{t('adminVipPlansNote')}</p>

      {editing && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50"
          onMouseDown={(e) => {
            if (e.target === e.currentTarget) setEditing(null)
          }}
        >
          <div
            role="dialog"
            aria-modal="true"
            aria-label={editing.name}
            className="w-full max-w-2xl bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto"
          >
            <form onSubmit={submitEditor}>
              <div className="px-6 pt-6 pb-4 border-b border-gray-100">
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <h2 className="text-lg font-bold text-gray-900">{editing.name}</h2>
                    <p className="text-xs text-gray-500 mt-0.5">
                      {editing.is_test ? t('testOnly') : 'VIP'} · {t('edit')}
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => setEditing(null)}
                    aria-label={t('cancel')}
                    className="p-1.5 -m-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100"
                  >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <div className="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1" htmlFor="vp-name">
                    {t('name')} *
                  </label>
                  <input
                    id="vp-name"
                    type="text"
                    value={form.name}
                    onChange={(e) => setForm({ ...form, name: e.target.value })}
                    className={fieldClass}
                    required
                    maxLength={255}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1" htmlFor="vp-slug">
                    Slug *
                  </label>
                  <input
                    id="vp-slug"
                    type="text"
                    value={form.slug}
                    onChange={(e) => setForm({ ...form, slug: e.target.value })}
                    className={fieldClass}
                    required
                    maxLength={255}
                  />
                </div>

                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-1" htmlFor="vp-desc">
                    {t('description')}
                  </label>
                  <textarea
                    id="vp-desc"
                    rows={3}
                    value={form.description}
                    onChange={(e) => setForm({ ...form, description: e.target.value })}
                    className={fieldClass}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1" htmlFor="vp-price">
                    {t('price')} * (USD)
                  </label>
                  <input
                    id="vp-price"
                    type="number"
                    step="0.01"
                    min="0.01"
                    value={form.price}
                    onChange={(e) => setForm({ ...form, price: e.target.value })}
                    className={fieldClass}
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1" htmlFor="vp-term">
                    {t('duration')}
                  </label>
                  {form.is_test ? (
                    <div className="flex items-center gap-2">
                      <input
                        id="vp-term"
                        type="number"
                        min="1"
                        step="1"
                        value={form.duration_hours}
                        onChange={(e) => setForm({ ...form, duration_hours: e.target.value })}
                        className={fieldClass}
                        required
                      />
                      <span className="text-sm text-gray-500 whitespace-nowrap">{t('hours')}</span>
                    </div>
                  ) : (
                    <div className="w-full px-3 py-2.5 rounded-xl border border-dashed border-gray-200 text-sm text-gray-500 bg-gray-50">
                      365 {t('days')} · {t('adminVipFixedTerm')}
                    </div>
                  )}
                </div>

                <div>
                  <span className="block text-sm font-medium text-gray-700 mb-1">Type</span>
                  <label className="flex items-center gap-2 text-sm text-gray-700">
                    <input
                      type="checkbox"
                      checked={form.is_test}
                      onChange={(e) => setForm({ ...form, is_test: e.target.checked })}
                      className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    {t('testOnly')}
                  </label>
                  {form.is_test && (
                    <p className="mt-1 text-xs text-amber-600">{t('adminVipTestModeNote')}</p>
                  )}
                </div>

                <div>
                  <span className="block text-sm font-medium text-gray-700 mb-1">{t('status')}</span>
                  <label className="flex items-center gap-2 text-sm text-gray-700">
                    <input
                      type="checkbox"
                      checked={form.is_active}
                      onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
                      className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                    {t('active')}
                  </label>
                </div>
              </div>

              <div className="px-6 py-4 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-3">
                <button
                  type="button"
                  onClick={confirmDelete}
                  disabled={deleteMutation.isPending}
                  className="px-4 py-2 text-sm font-semibold text-red-600 border border-red-200 rounded-xl hover:bg-red-50 disabled:opacity-60"
                >
                  {deleteMutation.isPending ? t('loading') : t('delete')}
                </button>
                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    onClick={() => setEditing(null)}
                    className="px-4 py-2 text-sm border border-gray-200 rounded-xl hover:bg-gray-50"
                  >
                    {t('cancel')}
                  </button>
                  <button
                    type="submit"
                    disabled={updateMutation.isPending}
                    className="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 disabled:opacity-60"
                  >
                    {updateMutation.isPending ? t('loading') : t('save')}
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
