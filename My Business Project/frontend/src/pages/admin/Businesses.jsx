import { useCallback, useState } from 'react'
import { toast } from 'sonner'
import { Building2, Lock, LockOpen, Search, Trash2 } from 'lucide-react'
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
import { FormRow, Textarea } from '../../components/ui/Form'
import { Modal } from '../../components/ui/Modal'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'

export default function AdminBusinesses() {
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [suspendTarget, setSuspendTarget] = useState(null)
  const [deleteTarget, setDeleteTarget] = useState(null)
  const [reason, setReason] = useState('')
  const [reasonError, setReasonError] = useState(null)
  const [busy, setBusy] = useState(false)

  const debouncedSearch = useDebouncedValue(search, 350)

  const loader = useCallback(
    () => adminService.businesses({ search: debouncedSearch || undefined, status: status || undefined, page }),
    [debouncedSearch, status, page],
  )

  const { data, loading, error, reload } = useApi(loader, [debouncedSearch, status, page])

  const businesses = data?.data ?? []

  const closeDialogs = () => {
    setSuspendTarget(null)
    setDeleteTarget(null)
    setReason('')
    setReasonError(null)
  }

  const suspend = async () => {
    if (!suspendTarget) {
      return
    }

    if (reason.trim().length < 5) {
      setReasonError('Give a short reason (at least 5 characters).')
      return
    }

    setBusy(true)

    try {
      await adminService.suspendBusiness(suspendTarget.id, reason)
      toast.success('Business suspended.')
      closeDialogs()
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to suspend the business.'))
    } finally {
      setBusy(false)
    }
  }

  const unsuspend = async (business) => {
    try {
      await adminService.unsuspendBusiness(business.id, 'Restored by administrator')
      toast.success('Business restored.')
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to restore the business.'))
    }
  }

  const destroy = async () => {
    if (!deleteTarget) {
      return
    }

    if (reason.trim().length < 5) {
      setReasonError('Give a short reason (at least 5 characters).')
      return
    }

    setBusy(true)

    try {
      await adminService.deleteBusiness(deleteTarget.id, reason)
      toast.success('Business deleted.')
      closeDialogs()
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to delete the business.'))
    } finally {
      setBusy(false)
    }
  }

  return (
    <div>
      <PageHeader title="Businesses" description="Every workspace registered on the platform." />

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
              placeholder="Search by business name"
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
            <option value="suspended">Suspended</option>
          </select>
        </div>

        {loading && <LoadingBlock />}

        {error && !loading && (
          <div className="p-5">
            <Alert tone="danger">{apiErrorMessage(error)}</Alert>
          </div>
        )}

        {!loading && !error && businesses.length === 0 && (
          <EmptyState icon={Building2} title="No businesses found" description="Try a different search or filter." />
        )}

        {!loading && !error && businesses.length > 0 && (
          <>
            <Table>
              <thead className="bg-slate-50">
                <tr>
                  <Th>Business</Th>
                  <Th>Owner</Th>
                  <Th>Plan</Th>
                  <Th>Status</Th>
                  <Th>Created</Th>
                  <Th className="text-right">Actions</Th>
                </tr>
              </thead>
              <tbody>
                {businesses.map((business) => (
                  <Tr key={business.id}>
                    <Td>
                      <p className="font-medium text-slate-900">{business.name}</p>
                      <p className="text-xs text-slate-500">
                        {business.country ?? '—'} · {business.currency ?? '—'}
                      </p>
                    </Td>
                    <Td>{business.owner?.email ?? '—'}</Td>
                    <Td>
                      {business.subscription?.plan?.name ?? <span className="text-slate-400">none</span>}
                    </Td>
                    <Td>
                      {business.is_suspended ? (
                        <Badge tone="danger">suspended</Badge>
                      ) : (
                        <StatusBadge status="active" tone="success" />
                      )}
                    </Td>
                    <Td>{formatDate(business.created_at)}</Td>
                    <Td>
                      <div className="flex items-center justify-end gap-1">
                        {business.is_suspended ? (
                          <button
                            type="button"
                            title="Restore business"
                            onClick={() => unsuspend(business)}
                            className="rounded-md p-1.5 text-slate-500 transition hover:bg-emerald-50 hover:text-emerald-600"
                          >
                            <LockOpen className="size-4" />
                          </button>
                        ) : (
                          <button
                            type="button"
                            title="Suspend business"
                            onClick={() => {
                              setSuspendTarget(business)
                              setReason('')
                              setReasonError(null)
                            }}
                            className="rounded-md p-1.5 text-slate-500 transition hover:bg-amber-50 hover:text-amber-600"
                          >
                            <Lock className="size-4" />
                          </button>
                        )}
                        <button
                          type="button"
                          title="Delete business"
                          onClick={() => {
                            setDeleteTarget(business)
                            setReason('')
                            setReasonError(null)
                          }}
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

      <Modal
        open={Boolean(suspendTarget)}
        onClose={closeDialogs}
        title="Suspend business"
        description="Suspended workspaces lose access until they are restored."
        footer={
          <>
            <Button variant="secondary" onClick={closeDialogs} disabled={busy}>
              Cancel
            </Button>
            <Button variant="warning" onClick={suspend} loading={busy}>
              Suspend
            </Button>
          </>
        }
      >
        <FormRow label="Reason" htmlFor="suspend-reason" error={reasonError}>
          <Textarea
            id="suspend-reason"
            rows={3}
            value={reason}
            onChange={(event) => setReason(event.target.value)}
            placeholder="Payment fraud investigation"
          />
        </FormRow>
      </Modal>

      <Modal
        open={Boolean(deleteTarget)}
        onClose={closeDialogs}
        title="Delete business"
        description={
          deleteTarget
            ? `${deleteTarget.name} and every record inside it are permanently removed.`
            : ''
        }
        footer={
          <>
            <Button variant="secondary" onClick={closeDialogs} disabled={busy}>
              Cancel
            </Button>
            <Button variant="danger" onClick={destroy} loading={busy}>
              Delete
            </Button>
          </>
        }
      >
        <FormRow label="Reason" htmlFor="delete-reason" error={reasonError}>
          <Textarea
            id="delete-reason"
            rows={3}
            value={reason}
            onChange={(event) => setReason(event.target.value)}
            placeholder="Requested by the business owner"
          />
        </FormRow>
      </Modal>
    </div>
  )
}
