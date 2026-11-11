import { useCallback, useState } from 'react'
import { toast } from 'sonner'
import { Search, ShieldCheck, Trash2, UserCheck, UserX } from 'lucide-react'
import { adminService } from '../../services/adminService'
import { useApi } from '../../hooks/useApi'
import { useDebouncedValue } from '../../hooks/useDebouncedValue'
import { apiErrorMessage } from '../../api/client'
import { formatDate } from '../../utils/format'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, Pagination } from '../../components/ui/Feedback'
import { Badge, StatusBadge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { ConfirmDialog } from '../../components/ui/Modal'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'

export default function AdminUsers() {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [removing, setRemoving] = useState(null)
  const debouncedSearch = useDebouncedValue(search, 350)

  const loader = useCallback(
    () => adminService.users({ search: debouncedSearch || undefined, status: status || undefined, page }),
    [debouncedSearch, status, page],
  )

  const { data, loading, error, reload } = useApi(loader, [debouncedSearch, status, page])

  const users = data?.data ?? []

  const update = async (user, payload) => {
    try {
      await adminService.updateUser(user.id, payload)
      toast.success('User updated.')
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to update the user.'))
    }
  }

  const confirmDelete = async () => {
    if (!removing) {
      return
    }

    try {
      await adminService.deleteUser(removing.id)
      toast.success('User deleted.')
      setRemoving(null)
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to delete the user.'))
    }
  }

  return (
    <div>
      <PageHeader title="Users" description="Every account on the platform." />

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-3">
          <div className="relative flex-1 min-w-[220px]">
            <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input
              type="search"
              value={search}
              onChange={(event) => {
                setSearch(event.target.value)
                setPage(1)
              }}
              placeholder="Search by name or email"
              className="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
            />
          </div>

          <select
            value={status}
            onChange={(event) => {
              setStatus(event.target.value)
              setPage(1)
            }}
            className="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          >
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="disabled">Disabled</option>
          </select>
        </div>

        {loading && <LoadingBlock />}

        {error && !loading && (
          <div className="p-5">
            <Alert tone="danger">{apiErrorMessage(error)}</Alert>
          </div>
        )}

        {!loading && !error && users.length === 0 && (
          <EmptyState icon={Search} title="No users found" description="Try a different search or filter." />
        )}

        {!loading && !error && users.length > 0 && (
          <>
            <Table>
              <thead className="bg-slate-50">
                <tr>
                  <Th>User</Th>
                  <Th>Businesses</Th>
                  <Th>Status</Th>
                  <Th>Role</Th>
                  <Th>Joined</Th>
                  <Th className="text-right">Actions</Th>
                </tr>
              </thead>
              <tbody>
                {users.map((user) => (
                  <Tr key={user.id}>
                    <Td>
                      <p className="font-medium text-slate-900">{user.name}</p>
                      <p className="text-xs text-slate-500">{user.email}</p>
                    </Td>
                    <Td>
                      <span className="text-sm">
                        {user.owned_businesses_count ?? 0} owned · {user.memberships_count ?? 0} member
                      </span>
                    </Td>
                    <Td>
                      <StatusBadge
                        status={user.is_active ? 'active' : 'disabled'}
                        tone={user.is_active ? 'success' : 'danger'}
                      />
                    </Td>
                    <Td>
                      {user.is_super_admin ? (
                        <Badge tone="brand">
                          <ShieldCheck className="size-3" />
                          super admin
                        </Badge>
                      ) : (
                        <Badge tone="neutral">user</Badge>
                      )}
                    </Td>
                    <Td>{formatDate(user.created_at)}</Td>
                    <Td>
                      <div className="flex items-center justify-end gap-1">
                        <button
                          type="button"
                          title={user.is_active ? 'Disable account' : 'Enable account'}
                          onClick={() => update(user, { is_active: !user.is_active })}
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                        >
                          {user.is_active ? <UserX className="size-4" /> : <UserCheck className="size-4" />}
                        </button>
                        <button
                          type="button"
                          title={user.is_super_admin ? 'Revoke super admin' : 'Grant super admin'}
                          onClick={() => update(user, { is_super_admin: !user.is_super_admin })}
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-brand-50 hover:text-brand-600"
                        >
                          <ShieldCheck className="size-4" />
                        </button>
                        <button
                          type="button"
                          title="Delete user"
                          onClick={() => setRemoving(user)}
                          className="rounded-md p-1.5 text-slate-500 transition hover:bg-red-50 hover:text-red-600"
                        >
                          <Trash2 className="size-4" />
                        </button>
                      </div>
                    </Td>
                  </Tr>
                ))}
              </tbody>
            </Table>

            <Pagination meta={data} onPageChange={setPage} />
          </>
        )}
      </Card>

      <ConfirmDialog
        open={Boolean(removing)}
        onClose={() => setRemoving(null)}
        onConfirm={confirmDelete}
        title="Delete user"
        description={
          removing ? `${removing.name} (${removing.email}) and their owned workspaces are removed.` : ''
        }
        confirmLabel="Delete"
        tone="danger"
      />
    </div>
  )
}
