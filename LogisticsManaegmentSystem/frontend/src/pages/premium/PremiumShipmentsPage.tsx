import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Clock } from 'lucide-react'
import { getPaginated } from '@/lib/api'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Pagination } from '@/components/ui/Pagination'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { Badge } from '@/components/ui/Badge'
import { EmptyState } from '@/components/ui/States'
import { Skeleton } from '@/components/ui/Skeleton'
import { RequirePermission } from '@/components/guards/ProtectedRoute'
import type { PaginationMeta } from '@/types/api'

interface PremiumShipment {
  id: number
  reference: string
  status: string
  status_label: string
  is_late: boolean
  is_closed: boolean
  etd: string | null
  eta: string | null
  consignee: { code: string; name: string } | null
  carrier: { code: string; name: string } | null
  vessel: { name: string; imo_number: string | null } | null
  created_at: string
}

interface PremiumShipmentsResponse {
  data: PremiumShipment[]
  meta: PaginationMeta
}

export function PremiumShipmentsPage() {
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)

  const { data, isPending } = useQuery<PremiumShipmentsResponse>({
    queryKey: ['premium', 'shipments', page, perPage],
    queryFn: () =>
      getPaginated<PremiumShipment>(`/shipments?page=${page}&per_page=${perPage}`),
  })

  return (
    <RequirePermission permission="premium.view">
      <div className="space-y-4 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div className="min-w-0">
            <h1 className="text-lg font-semibold text-ink-900 tracking-tight">
              Premium <span className="text-premium-700">Shipments</span>
            </h1>
            <p className="text-xs text-ink-500 mt-1 leading-relaxed">
              Priority consignments and VIP customer deliveries.
            </p>
          </div>
        </div>

        {isPending ? (
          <div className="h-96 flex items-center justify-center">
            <SkeletonTable rows={4} />
          </div>
        ) : (data?.data ?? []).length === 0 ? (
          <EmptyState title="No premium shipments" description="Priority consignments will appear here once booked for VIP customers." />
        ) : (
          <>
            <Card>
              <CardHeader title="Priority Consignments" description={data ? `Showing ${data.meta.from} to ${data.meta.to} of ${data.meta.total}` : undefined} />
              <CardBody className="p-0">
                <Table>
                  <THead>
                    <TR>
                      <TH>Reference</TH>
                      <TH>Customer</TH>
                      <TH>Carrier</TH>
                      <TH>Vessel</TH>
                      <TH align="right">ETD</TH>
                      <TH align="right">ETA</TH>
                      <TH>Status</TH>
                      <TH align="right" className="min-w-[70px]">Priority</TH>
                    </TR>
                  </THead>
                  <TBody>
                    {data!.data.map((shipment) => (
                      <TR key={shipment.id}>
                        <TD className="font-medium text-ink-900 truncate max-w-xs">
                          {shipment.reference}
                        </TD>
                        <TD className="tabular text-sm text-ink-700 truncate max-w-xs">
                          {shipment.consignee?.name ?? <span className="text-ink-300">&mdash;</span>}
                          {shipment.consignee && (
                            <span className="ml-1 text-xs text-premium-700">
                              <Badge tone="premium">VIP</Badge>
                            </span>
                          )}
                        </TD>
                        <TD className="tabular text-sm text-ink-700 truncate max-w-xs">
                          {shipment.carrier?.name ?? <span className="text-ink-300">&mdash;</span>}
                        </TD>
                        <TD className="tabular text-sm text-ink-700 truncate max-w-xs">
                          {shipment.vessel?.name ?? <span className="text-ink-300">&mdash;</span>}
                        </TD>
                        <TD align="right" className="tabular text-sm text-ink-700">
                          {shipment.etd ?? <span className="text-ink-300">&mdash;</span>}
                        </TD>
                        <TD align="right" className="tabular text-sm text-ink-700">
                          {shipment.eta ?? <span className="text-ink-300">&mdash;</span>}
                        </TD>
                        <TD>
                          <Badge tone={shipment.is_late ? 'premium' : 'neutral'} dot>
                            {shipment.status_label}
                          </Badge>
                        </TD>
                        <TD align="right">
                          {shipment.is_late ? (
                            <Badge tone="premium">
                              <Clock className="size-3.5 mr-1" aria-hidden />
                              Late
                            </Badge>
                          ) : (
                            <Badge tone="success">On time</Badge>
                          )}
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
          <Skeleton className="h-3.5 w-32" />
          <Skeleton className="h-3.5 w-24" />
          <Skeleton className="h-3.5 w-20" />
          <Skeleton className="h-3.5 w-16" />
          <Skeleton className="h-3.5 w-16" />
          <Skeleton className="h-3.5 w-20" />
          <Skeleton className="h-3.5 w-16" />
        </div>
      ))}
    </div>
  )
}
