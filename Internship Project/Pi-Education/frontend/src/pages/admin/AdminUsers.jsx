import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import api from '../../api/client'
import Loading from '../../components/common/Loading'
import ErrorState from '../../components/common/ErrorState'
import Modal from '../../components/common/Modal'
import { useI18n } from '../../i18n'

const inputClass =
  'w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm text-slate-800 transition hover:border-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 focus:outline-none'

export default function AdminUsers() {
  const { t } = useI18n()
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [editing, setEditing] = useState(null)
  const [modalOpen, setModalOpen] = useState(false)
  const [form, setForm] = useState(null)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-users', page, search],
    queryFn: async () => {
      const res = await api.get('/admin/users', { params: { page, per_page: 15, search } })
      return res.data.data
    },
  })

  const save = useMutation({
    mutationFn: () =>
      api.patch(`/admin/users/${editing.id}`, {
        role: form.role,
        status: form.status,
        vip_until: form.vip_until || null,
      }),
    onSuccess: () => {
      setModalOpen(false)
      setEditing(null)
      queryClient.invalidateQueries({ queryKey: ['admin-users'] })
      queryClient.invalidateQueries({ queryKey: ['admin-dashboard'] })
    },
  })

  const openEdit = (user) => {
    setEditing(user)
    setForm({
      role: user.roles?.includes('admin') ? 'admin' : 'user',
      status: user.status === 'inactive' ? 'inactive' : 'active',
      vip_until: user.vip_until ? user.vip_until.slice(0, 10) : '',
    })
    setModalOpen(true)
  }

  if (isLoading) return <Loading full />
  if (isError) return <ErrorState />

  const users = data?.users ?? []
  const pagination = data?.pagination

  return (
    <div>
      <div>
      <p className="section-eyebrow">Admin</p>
      <h1 className="text-2xl font-extrabold tracking-tight text-slate-900">{t('admin.users')}</h1>
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
              <th className="px-4 py-3">{t('common.name')}</th>
              <th className="px-4 py-3">Email</th>
              <th className="px-4 py-3">{t('admin.role')}</th>
              <th className="px-4 py-3">{t('common.status')}</th>
              <th className="px-4 py-3">VIP</th>
              <th className="px-4 py-3">{t('common.actions')}</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {users.map((user) => (
              <tr key={user.id} className="hover:bg-slate-50">
                <td className="px-4 py-3 font-medium text-slate-900">{user.name}</td>
                <td className="px-4 py-3 text-slate-600">{user.email}</td>
                <td className="px-4 py-3">
                  <span
                    className={`inline-block rounded-full px-2.5 py-1 text-xs font-medium ${
                      user.roles?.includes('admin')
                        ? 'bg-indigo-100 text-indigo-700'
                        : 'bg-slate-100 text-slate-600'
                    }`}
                  >
                    {user.roles?.join(', ') || 'user'}
                  </span>
                </td>
                <td className="px-4 py-3">
                  <span
                    className={`inline-block rounded-full px-2.5 py-1 text-xs font-medium ${
                      user.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
                    }`}
                  >
                    {user.status}
                  </span>
                </td>
                <td className="px-4 py-3">
                  {user.is_vip ? (
                    <span className="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-700">VIP</span>
                  ) : (
                    <span className="text-xs text-slate-400">—</span>
                  )}
                </td>
                <td className="px-4 py-3">
                  <button
                    type="button"
                    onClick={() => openEdit(user)}
                    className="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200"
                  >
                    {t('common.edit')}
                  </button>
                </td>
              </tr>
            ))}
            {users.length === 0 && (
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

      <Modal open={modalOpen} onClose={() => setModalOpen(false)} title={t('common.edit')} width="max-w-md">
        {form && (
          <div className="space-y-4">
            <label className="block text-sm">
              <span className="mb-1 block font-medium text-slate-700">{t('admin.role')}</span>
              <select value={form.role} onChange={(e) => setForm((f) => ({ ...f, role: e.target.value }))} className={inputClass}>
                <option value="user">user</option>
                <option value="admin">admin</option>
              </select>
            </label>
            <label className="block text-sm">
              <span className="mb-1 block font-medium text-slate-700">{t('common.status')}</span>
              <select
                value={form.status}
                onChange={(e) => setForm((f) => ({ ...f, status: e.target.value }))}
                className={inputClass}
              >
                <option value="active">active</option>
                <option value="inactive">inactive</option>
              </select>
            </label>
            <label className="block text-sm">
              <span className="mb-1 block font-medium text-slate-700">{t('admin.vipUntil')}</span>
              <input
                type="date"
                value={form.vip_until}
                onChange={(e) => setForm((f) => ({ ...f, vip_until: e.target.value }))}
                className={inputClass}
              />
            </label>
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