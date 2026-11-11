import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { getPaginated } from '@/lib/api'
import { LIVE_REFRESH } from '@/lib/realtime'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Pagination } from '@/components/ui/Pagination'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { EmptyState } from '@/components/ui/States'
import { RequirePermission } from '@/components/guards/ProtectedRoute'
import type { PaginationMeta } from '@/types/api'

interface AdminActivityLog {
  id: number
  action: string
  causer_type: string
  causer_id: number | null
  subject_type: string | null
  subject_id: number | null
  properties: Record<string, unknown> | null
  ip_address: string | null
  user_agent: string | null
  created_at: string
}

interface ActivityLogResponse {
  data: AdminActivityLog[]
  meta: PaginationMeta
}

export function ActivityLogsPage() {
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)

  const { data, isPending } = useQuery<ActivityLogResponse>({
    queryKey: ['admin', 'activity-log', page, perPage],
    queryFn: () =>
      getPaginated<AdminActivityLog>(`/admin/activity-log?page=${page}&per_page=${perPage}`),
    refetchInterval: LIVE_REFRESH.fast,
  })

  return (
    <RequirePermission permission="activity_log.view">
      <div className="space-y-4 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-lg font-semibold text-ink-900">Activity Logs</h1>
            <p className="text-xs text-ink-500">Append-only record of what authenticated actors did, newest first.</p>
          </div>
        </div>

        {isPending ? (
          <div className="h-96 flex items-center justify-center">
            <div className="animate-pulse rounded-full h-12 w-12 bg-ink-200" />
          </div>
        ) : (data?.data ?? []).length === 0 ? (
          <EmptyState title="No activity logged" />
        ) : (
          <>
            <Card>
              <CardHeader title="Activity Log" description="Who did what, to what, and when." />
              <CardBody className="p-0">
                <Table>
                  <THead>
                    <TR>
                      <TH>Action</TH>
                      <TH>Actor</TH>
                      <TH>Subject</TH>
                      <TH align="right">IP address</TH>
                      <TH align="right">When</TH>
                    </TR>
                  </THead>
                  <TBody>
                    {data!.data.map((log: AdminActivityLog) => (
                      <TR key={log.id}>
                        <TD className="font-medium text-ink-900 truncate max-w-xs">
                          {log.action}
                        </TD>
                        <TD className="tabular text-sm text-ink-500 truncate max-w-xs">
                          {log.causer_type}{log.causer_id !== null ? `#${log.causer_id}` : ''}
                        </TD>
                        <TD className="tabular text-sm text-ink-500 truncate max-w-xs">
                          {log.subject_type}{log.subject_id !== null ? `#${log.subject_id}` : ''}
                        </TD>
                        <TD align="right" className="tabular text-sm text-ink-500">
                          {log.ip_address ?? '—'}
                        </TD>
                        <TD align="right" className="tabular text-sm text-ink-500">
                          {new Date(log.created_at).toLocaleString('en-GB', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                          })}
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
