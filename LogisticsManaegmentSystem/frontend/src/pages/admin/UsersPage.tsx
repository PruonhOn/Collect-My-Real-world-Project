import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Pencil, Plus, Trash2 } from 'lucide-react'
import { Link } from 'react-router-dom'
import { getPaginated, del } from '@/lib/api'
import { Card, CardBody } from '@/components/ui/Card'
import { Pagination } from '@/components/ui/Pagination'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { EmptyState, ErrorState } from '@/components/ui/States'
import { RequirePermission } from '@/components/guards/ProtectedRoute'
import { useToast } from '@/components/ui/toast-context'
import type { PaginationMeta } from '@/types/api'

interface AdminUser {
  id: number
  name: string
  email: string
  phone: string | null
  avatar_path: string | null
  is_active: boolean
  last_login_at: string | null
  roles: string[]
  is_protected: boolean
  created_at: string
  updated_at: string
}

interface UsersResponse {
  data: AdminUser[]
  meta: PaginationMeta
}

export function UsersPage() {
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const toast = useToast()

  const { data, isPending, error } = useQuery<UsersResponse>({
    queryKey: ['admin', 'users', page, perPage],
    queryFn: () => getPaginated<AdminUser>(`/admin/users?page=${page}&per_page=${perPage}`),
  })

  const handleDelete = async (user: AdminUser) => {
    if (!window.confirm(`Deactivate "${user.name}"? This cannot be undone.`)) return
    try {
      await del(`/admin/users/${user.id}`)
      toast.success('User deleted.')
      window.location.reload()
    } catch {
      toast.error('Could not delete user.')
    }
  }

  return (
    <RequirePermission permission="user.view">
      <div className="space-y-4 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-lg font-semibold text-ink-900">Users</h1>
            <p className="text-xs text-ink-500">Accounts that can sign in, with their roles and status.</p>
          </div>
          <Link to="/admin/users/create">
            <Button>
              <Plus className="size-4 mr-2" aria-hidden />
              Add user
            </Button>
          </Link>
        </div>

        {error && <ErrorState title="Could not load users" onRetry={() => window.location.reload()} />}

        {isPending ? (
          <div className="h-96 flex items-center justify-center">
            <div className="animate-pulse rounded-full h-12 w-12 bg-ink-200" />
          </div>
        ) : data?.data.length === 0 ? (
          <EmptyState title="No users" />
        ) : (
          <>
            <Card>
              <CardBody className="p-0">
                <Table>
                  <THead>
                    <TR>
                      <TH>Name</TH>
                      <TH>Email</TH>
                      <TH>Roles</TH>
                      <TH>Status</TH>
                      <TH align="right">Last login</TH>
                      <TH align="right">Created</TH>
                      <TH align="right" className="min-w-[90px]">Actions</TH>
                    </TR>
                  </THead>
                  <TBody>
                    {data!.data.map((user: AdminUser) => (
                      <TR key={user.id}>
                        <TD>
                          <Link to={`/admin/users/${user.id}`} className="font-medium text-ink-900 hover:underline">
                            {user.name}
                          </Link>
                        </TD>
                        <TD className="tabular text-ink-500">{user.email}</TD>
                        <TD>
                          {user.roles.map((role: string) => (
                            <Badge key={role} tone="info">
                              {role}
                            </Badge>
                          ))}
                        </TD>
                        <TD>
                          <Badge tone={user.is_active ? 'success' : 'danger'}>
                            {user.is_active ? 'Active' : 'Inactive'}
                          </Badge>
                        </TD>
                        <TD align="right" className="tabular">
                          {user.last_login_at ? new Date(user.last_login_at).toLocaleDateString('en-GB') : '—'}
                        </TD>
                        <TD align="right" className="tabular">
                          {new Date(user.created_at).toLocaleDateString('en-GB')}
                        </TD>
                        <TD align="right">
                          <Link to={`/admin/users/${user.id}/edit`}>
                            <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                              <Pencil className="size-4" aria-hidden />
                            </Button>
                          </Link>
                          <Button
                            variant="danger"
                            size="sm"
                            className="h-8 w-8 p-0 ml-1"
                            onClick={() => handleDelete(user)}
                            disabled={user.is_protected}
                          >
                            <Trash2 className="size-4" aria-hidden />
                          </Button>
                        </TD>
                      </TR>
                    ))}
                  </TBody>
                </Table>
              </CardBody>
            </Card>

            <Pagination
              meta={data!.meta}
              perPage={perPage}
              onPageChange={setPage}
              onPerPageChange={setPerPage}
            />
          </>
        )}
      </div>
    </RequirePermission>
  )
}
