import { useEffect, useState } from 'react'
import client from '../../api/client'
import { useToast } from '../../context/ToastContext'
import { useLanguage } from '../../context/LanguageContext'
import Spinner from '../../components/Spinner'
import ErrorState from '../../components/ErrorState'
import EmptyState from '../../components/EmptyState'
import Pagination from '../../components/Pagination'

export default function Users() {
  const { t } = useLanguage()
  const [users, setUsers] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const { toast } = useToast()

  useEffect(() => {
    setLoading(true)
    client
      .get(`/admin/users?page=${page}&search=${search}`)
      .then(({ data }) => {
        setUsers(data.data || [])
        setMeta(data.meta || {})
      })
      .catch(() => setError(t('errLoadUsers')))
      .finally(() => setLoading(false))
  }, [page, search, t])

  const changeRole = async (user, role) => {
    try {
      await client.patch(`/admin/users/${user.id}/role`, { role })
      toast(`${t('roleChangedTo')} ${role === 'admin' ? t('roleAdmin') : t('roleReader')}`)
      setUsers((prev) => prev.map((u) => (u.id === user.id ? { ...u, role } : u)))
    } catch (err) {
      toast(err.response?.data?.message || t('errChangeRole'), 'error')
    }
  }

  const remove = async (user) => {
    if (!window.confirm(t('deleteUserConfirm', { name: user.name }))) return
    try {
      await client.delete(`/admin/users/${user.id}`)
      toast(t('userDeleted'), 'info')
      setUsers((prev) => prev.filter((u) => u.id !== user.id))
    } catch (err) {
      toast(err.response?.data?.message || t('errDeleteUser'), 'error')
    }
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">{t('usersTitle')}</h1>

      <input
        type="text"
        value={search}
        onChange={(e) => { setSearch(e.target.value); setPage(1) }}
        placeholder={t('searchNameEmail')}
        className="w-full max-w-sm px-4 py-2 border border-gray-300 rounded-lg mb-4"
      />

      {error ? (
        <ErrorState message={error} />
      ) : loading ? (
        <Spinner />
      ) : users.length === 0 ? (
        <EmptyState title={t('noUsersFound')} />
      ) : (
        <>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thUser')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thRole')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thPurchases')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thJoined')}</th>
                    <th className="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">{t('thActions')}</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {users.map((user) => (
                    <tr key={user.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-3">
                          <span className="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold uppercase">
                            {user.name[0]}
                          </span>
                          <div>
                            <p className="font-medium text-gray-900">{user.name}</p>
                            <p className="text-xs text-gray-500">{user.email}</p>
                          </div>
                        </div>
                      </td>
                      <td className="px-4 py-3">
                        <select
                          value={user.role}
                          onChange={(e) => changeRole(user, e.target.value)}
                          className="px-2 py-1 border border-gray-300 rounded-lg text-sm bg-white"
                        >
                          <option value="reader">{t('roleReader')}</option>
                          <option value="admin">{t('roleAdmin')}</option>
                        </select>
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-600">{user.purchases_count || 0}</td>
                      <td className="px-4 py-3 text-sm text-gray-500">
                        {new Date(user.created_at).toLocaleDateString()}
                      </td>
                      <td className="px-4 py-3 text-right">
                        <button
                          onClick={() => remove(user)}
                          className="text-sm text-red-500 hover:text-red-700"
                        >
                          {t('deleteUser')}
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      )}
    </div>
  )
}
