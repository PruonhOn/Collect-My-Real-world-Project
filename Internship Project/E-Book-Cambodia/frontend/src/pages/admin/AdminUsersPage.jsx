import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { adminGetUsers, adminUpdateUserRole, adminDeleteUser } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

export default function AdminUsersPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-users', search, page],
    refetchInterval: 10000,
    queryFn: () => adminGetUsers({ search, per_page: 15, page }),
  })

  const users = data?.data?.data || []
  const pagination = data?.data
  const lastPage = pagination?.last_page || 1

  const roleMutation = useMutation({
    mutationFn: ({ id, role }) => adminUpdateUserRole(id, role),
    onSuccess: () => queryClient.invalidateQueries(['admin-users']),
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => adminDeleteUser(id),
    onSuccess: () => queryClient.invalidateQueries(['admin-users']),
  })

  const handleDelete = (user) => {
    if (window.confirm(`${t('confirmDelete')} "${user.name}"?`)) {
      deleteMutation.mutate(user.id)
    }
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('usersManagement')}</h1>
          <p className="text-gray-500 text-sm mt-1">{t('people')}</p>
        </div>
        <input
          type="text"
          placeholder={t('search')}
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1) }}
          className="px-4 py-2 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        />
      </div>

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('name')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('email')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('role')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('purchases')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('joined')}</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('delete')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {users.map((user) => (
                  <tr key={user.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 text-sm text-gray-500">{user.id}</td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                          {user.name?.charAt(0)?.toUpperCase()}
                        </div>
                        <div className="text-sm font-medium text-gray-900">{user.name}</div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">{user.email}</td>
                    <td className="px-6 py-4">
                      <select
                        value={user.role}
                        onChange={(e) => roleMutation.mutate({ id: user.id, role: e.target.value })}
                        className="text-sm px-2 py-1 rounded-lg border border-gray-200"
                      >
                        <option value="reader">reader</option>
                        <option value="admin">admin</option>
                      </select>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">{user.purchases_count}</td>
                    <td className="px-6 py-4 text-sm text-gray-500">
                      {new Date(user.created_at).toLocaleDateString()}
                    </td>
                    <td className="px-6 py-4 text-right text-sm">
                      <button onClick={() => handleDelete(user)} className="text-red-600 hover:text-red-800 font-medium">
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
          <span className="text-sm text-gray-600">Page {page} of {lastPage}</span>
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
