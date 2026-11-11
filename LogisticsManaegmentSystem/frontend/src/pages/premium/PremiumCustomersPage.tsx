import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Building2 } from 'lucide-react'
import { getPaginated } from '@/lib/api'
import { formatNumber } from '@/lib/format'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Pagination } from '@/components/ui/Pagination'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { Badge } from '@/components/ui/Badge'
import { EmptyState } from '@/components/ui/States'
import { Skeleton } from '@/components/ui/Skeleton'
import { RequirePermission } from '@/components/guards/ProtectedRoute'
import type { PaginationMeta } from '@/types/api'

interface PremiumCustomer {
  id: number
  code: string
  name: string
  email: string | null
  phone: string | null
  total_shipments: number
}

interface PremiumCustomersResponse {
  data: PremiumCustomer[]
  meta: PaginationMeta
}

export function PremiumCustomersPage() {
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)

  const { data, isPending } = useQuery<PremiumCustomersResponse>({
    queryKey: ['premium', 'customers', page, perPage],
    queryFn: () =>
      getPaginated<PremiumCustomer>(`/parties?page=${page}&per_page=${perPage}&type=customer`),
  })

  return (
    <RequirePermission permission="premium.view">
      <div className="space-y-4 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div className="min-w-0">
            <h1 className="text-lg font-semibold text-ink-900 tracking-tight">
              Premium <span className="text-premium-700">Customers</span>
            </h1>
            <p className="text-xs text-ink-500 mt-1 leading-relaxed">
              VIP accounts ranked by shipment volume.
            </p>
          </div>
        </div>

        {isPending ? (
          <div className="h-96 flex items-center justify-center">
            <SkeletonTable rows={4} />
          </div>
        ) : (data?.data ?? []).length === 0 ? (
          <EmptyState title="No premium customers" description="VIP accounts will appear here once consignments are booked for them." />
        ) : (
          <>
            <Card>
              <CardHeader title="VIP Accounts" description={data ? `Showing ${data.meta.from} to ${data.meta.to} of ${data.meta.total}` : undefined} />
              <CardBody className="p-0">
                <Table>
                  <THead>
                    <TR>
                      <TH>Customer</TH>
                      <TH>Code</TH>
                      <TH>Contact</TH>
                      <TH align="right">Shipments</TH>
                      <TH>Tier</TH>
                    </TR>
                  </THead>
                  <TBody>
                    {data!.data.map((customer) => (
                      <TR key={customer.id}>
                        <TD className="font-medium text-ink-900 truncate max-w-xs">
                          {customer.name}
                        </TD>
                        <TD className="tabular text-sm text-ink-700 truncate max-w-xs">
                          {customer.code}
                        </TD>
                        <TD className="tabular text-sm text-ink-500 truncate max-w-xs">
                          {customer.email ?? customer.phone ?? <span className="text-ink-300">&mdash;</span>}
                        </TD>
                        <TD align="right" className="tabular text-sm text-ink-700">
                          {formatNumber(customer.total_shipments)}
                        </TD>
                        <TD>
                          <Badge tone="premium">
                            <Building2 className="size-3.5 mr-1" aria-hidden />
                            VIP
                          </Badge>
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

function SkeletonTable({ rows = 4 }: { rows?: number }) {
  return (
    <div className="divide-ink-200 divide-y" aria-hidden>
      {Array.from({ length: rows }).map((_, rowIndex) => (
        <div key={rowIndex} className="flex items-center gap-4 px-4 py-3">
          <Skeleton className="h-3.5 w-40" />
          <Skeleton className="h-3.5 w-24" />
          <Skeleton className="h-3.5 w-32" />
          <Skeleton className="h-3.5 w-24" />
          <Skeleton className="h-3.5 w-20" />
        </div>
      ))}
    </div>
  )
}
