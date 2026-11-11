import { useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { CheckCircle2 } from 'lucide-react'
import { getPaginated, patch } from '@/lib/api'
import { LIVE_REFRESH } from '@/lib/realtime'
import { notificationKeys } from '@/features/notifications/useUnreadNotifications'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Pagination } from '@/components/ui/Pagination'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/States'
import { RequirePermission } from '@/components/guards/ProtectedRoute'
import { useToast } from '@/components/ui/toast-context'
import type { PaginationMeta } from '@/types/api'

interface AdminNotification {
  id: number
  type: string
  title: string
  body: string
  read_at: string | null
  is_unread: boolean
  created_at: string
  updated_at: string
}

interface NotificationsResponse {
  data: AdminNotification[]
  meta: PaginationMeta
}

export function NotificationsPage() {
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(25)
  const toast = useToast()
  const queryClient = useQueryClient()

  const { data, isPending } = useQuery<NotificationsResponse>({
    queryKey: notificationKeys.list(page, perPage),
    queryFn: () =>
      getPaginated<AdminNotification>(`/admin/notifications?page=${page}&per_page=${perPage}`),
    refetchInterval: LIVE_REFRESH.fast,
  })

  const handleMarkRead = async (notification: AdminNotification) => {
    try {
      await patch(`/admin/notifications/${notification.id}`, { read: true })
      // Invalidate rather than reload: the list and the top-bar badge both
      // re-derive from the cache, so the row and the count catch up together
      // while the 15-second poll keeps running.
      await queryClient.invalidateQueries({ queryKey: notificationKeys.all })
      toast.success('Marked as read.')
    } catch {
      toast.error('Could not mark notification as read.')
    }
  }

  return (
    <RequirePermission permission="notification.view">
      <div className="space-y-4 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-lg font-semibold text-ink-900">Notifications</h1>
            <p className="text-xs text-ink-500">What the system has told you, newest first.</p>
          </div>
        </div>

        {isPending ? (
          <div className="h-96 flex items-center justify-center">
            <div className="animate-pulse rounded-full h-12 w-12 bg-ink-200" />
          </div>
        ) : (data?.data ?? []).length === 0 ? (
          <EmptyState title="No notifications" />
        ) : (
          <>
            <Card>
              <CardHeader title="Notifications" />
              <CardBody className="p-0">
                <Table>
                  <THead>
                    <TR>
                      <TH>Title</TH>
                      <TH>Type</TH>
                      <TH>Body</TH>
                      <TH align="right">Status</TH>
                      <TH align="right">Received</TH>
                      <TH align="right" className="min-w-[70px]">Action</TH>
                    </TR>
                  </THead>
                  <TBody>
                    {data!.data.map((n: AdminNotification) => (
                      <TR key={n.id}>
                        <TD>
                          <div className="font-medium text-ink-900">{n.title}</div>
                          <div className="mt-0.5 text-xs text-ink-500 truncate max-w-xs">{n.type}</div>
                        </TD>
                        <TD className="tabular text-sm text-ink-500 truncate max-w-xs">{n.body}</TD>
                        <TD align="right">
                          <Badge tone={n.is_unread ? 'warning' : 'success'}>
                            {n.is_unread ? 'Unread' : 'Read'}
                          </Badge>
                        </TD>
                        <TD align="right" className="tabular">
                          {new Date(n.created_at).toLocaleDateString('en-GB')}
                        </TD>
                        <TD align="right">
                          {n.is_unread && (
                            <Button variant="outline" size="sm" onClick={() => handleMarkRead(n)}>
                              <CheckCircle2 className="size-3.5 mr-1" aria-hidden />
                              Mark read
                            </Button>
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
