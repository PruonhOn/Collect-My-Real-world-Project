import { useQuery } from '@tanstack/react-query'
import { Lock } from 'lucide-react'
import { Link } from 'react-router-dom'
import { get } from '@/lib/api'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/States'
import { RequirePermission } from '@/components/guards/ProtectedRoute'

interface AdminRole {
  id: number
  name: string
  guard_name: string
  permissions: string[]
}

export function RolesPage() {
  const { data, isPending } = useQuery<AdminRole[]>({
    queryKey: ['admin', 'roles'],
    queryFn: () => get<AdminRole[]>('/admin/roles'),
  })

  return (
    <RequirePermission permission="role.view">
      <div className="space-y-4 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-lg font-semibold text-ink-900">Roles & Permissions</h1>
            <p className="text-xs text-ink-500">What each role is called and which permissions it grants.</p>
          </div>
          <Link to="/admin/roles/create">
            <Button>Add role</Button>
          </Link>
        </div>

        {isPending ? (
          <div className="h-96 flex items-center justify-center">
            <div className="animate-pulse rounded-full h-12 w-12 bg-ink-200" />
          </div>
        ) : (data ?? []).length === 0 ? (
          <EmptyState title="No roles" />
        ) : (
          <Card>
            <CardHeader title="Roles" description="Each row lists the permissions the role grants." />
            <CardBody className="p-0">
              <Table>
                <THead>
                  <TR>
                    <TH>Name</TH>
                    <TH>Guard</TH>
                    <TH align="right">Permissions</TH>
                    <TH>Details</TH>
                  </TR>
                </THead>
                <TBody>
                  {data!.map((role: AdminRole) => (
                    <TR key={role.id}>
                      <TD>
                        <Link to={`/admin/roles/${role.name}`} className="font-medium text-ink-900 hover:underline">
                          {role.name}
                        </Link>
                        <span className="ml-2 inline-block text-xs text-ink-400">
                          <Lock className="size-3.5 inline mr-1" aria-hidden />
                          {role.guard_name}
                        </span>
                      </TD>
                      <TD align="right" className="tabular font-medium">
                        {role.permissions.length}
                      </TD>
                      <TD>
                        {role.permissions.slice(0, 4).map((perm: string) => (
                          <Badge key={perm} tone="info">
                            {perm}
                          </Badge>
                        ))}
                        {role.permissions.length > 4 && (
                          <Badge tone="neutral">
                            +{role.permissions.length - 4} more
                          </Badge>
                        )}
                      </TD>
                    </TR>
                  ))}
                </TBody>
              </Table>
            </CardBody>
          </Card>
        )}
      </div>
    </RequirePermission>
  )
}
