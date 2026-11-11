import { useCallback, useState } from 'react'
import { toast } from 'sonner'
import { RefreshCw } from 'lucide-react'
import { adminService } from '../../services/adminService'
import { useApi } from '../../hooks/useApi'
import { apiErrorMessage } from '../../api/client'
import { formatDate, formatMoney } from '../../utils/format'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, Pagination } from '../../components/ui/Feedback'
import { StatusBadge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card } from '../../components/ui/Card'
import { FormRow, Select, Textarea } from '../../components/ui/Form'
import { Modal } from '../../components/ui/Modal'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'

const STATUSES = ['trialing', 'active', 'past_due', 'canceled', 'expired', 'suspended']

const STATUS_TONES = {
  trialing: 'info',
  active: 'success',
  past_due: 'warning',
  canceled: 'neutral',
  expired: 'neutral',
  suspended: 'danger',
}

export default function AdminSubscriptions() {
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [target, setTarget] = useState(null)
  const [nextStatus, setNextStatus] = useState('active')
  const [reason, setReason] = useState('')
  const [reasonError, setReasonError] = useState(null)
  const [busy, setBusy] = useState(false)

  const loader = useCallback(
    () => adminService.subscriptions({ status: status || undefined, page }),
    [status, page],
  )

  const { data, loading, error, reload } = useApi(loader, [status, page])

  const subscriptions = data?.data ?? []

  const openChange = (subscription) => {
    setTarget(subscription)
    setNextStatus(subscription.status)
    setReason('')
    setReasonError(null)
  }

  const submit = async () => {
    if (!target) {
      return
    }

    if (reason.trim().length < 5) {
      setReasonError('Give a short reason (at least 5 characters).')
      return
    }

    setBusy(true)

    try {
      await adminService.updateSubscription(target.id, { status: nextStatus, reason })
      toast.success('Subscription updated.')
      setTarget(null)
      reload()
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to update the subscription.'))
    } finally {
      setBusy(false)
    }
  }

  return (
    <div>
      <PageHeader title="Subscriptions" description="Plan state for every workspace on the platform." />

      <Card>
        <div className="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-3">
          <select
            value={status}
            onChange={(event) => {
              setStatus(event.target.value)
              setPage(1)
            }}
            className="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          >
            <option value="">All statuses</option>
            {STATUSES.map((option) => (
              <option key={option} value={option}>
                {option.replace('_', ' ')}
              </option>
            ))}
          </select>
        </div>

        {loading && <LoadingBlock />}

        {error && !loading && (
          <div className="p-5">
            <Alert tone="danger">{apiErrorMessage(error)}</Alert>
          </div>
        )}

        {!loading && !error && subscriptions.length === 0 && (
          <EmptyState icon={RefreshCw} title="No subscriptions found" description="Try another status filter." />
        )}

        {!loading && !error && subscriptions.length > 0 && (
          <>
            <Table>
              <thead className="bg-slate-50">
                <tr>
                  <Th>Business</Th>
                  <Th>Plan</Th>
                  <Th>Cycle</Th>
                  <Th>Status</Th>
                  <Th>Period</Th>
                  <Th className="text-right">Actions</Th>
                </tr>
              </thead>
              <tbody>
                {subscriptions.map((subscription) => (
                  <Tr key={subscription.id}>
                    <Td>
                      <p className="font-medium text-slate-900">{subscription.business?.name ?? '—'}</p>
                      <p className="text-xs text-slate-500">{subscription.business?.slug ?? ''}</p>
                    </Td>
                    <Td>{subscription.plan?.name ?? '—'}</Td>
                    <Td className="capitalize">{subscription.billing_cycle ?? 'monthly'}</Td>
                    <Td>
                      <StatusBadge status={subscription.status} tone={STATUS_TONES[subscription.status]} />
                    </Td>
                    <Td className="text-xs">
                      {formatDate(subscription.starts_at)} → {formatDate(subscription.ends_at)}
                    </Td>
                    <Td className="text-right">
                      <Button variant="ghost" size="sm" onClick={() => openChange(subscription)}>
                        Change status
                      </Button>
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
        open={Boolean(target)}
        onClose={() => setTarget(null)}
        title="Change subscription status"
        description={
          target ? `${target.business?.name ?? 'Business'} · ${target.plan?.name ?? 'plan'}` : ''
        }
        footer={
          <>
            <Button variant="secondary" onClick={() => setTarget(null)} disabled={busy}>
              Cancel
            </Button>
            <Button onClick={submit} loading={busy}>
              Apply
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <FormRow label="New status" htmlFor="subscription-status">
            <Select
              id="subscription-status"
              value={nextStatus}
              onChange={(event) => setNextStatus(event.target.value)}
            >
              {STATUSES.map((option) => (
                <option key={option} value={option}>
                  {option.replace('_', ' ')}
                </option>
              ))}
            </Select>
          </FormRow>

          <FormRow label="Reason" htmlFor="subscription-reason" error={reasonError}>
            <Textarea
              id="subscription-reason"
              rows={3}
              value={reason}
              onChange={(event) => setReason(event.target.value)}
              placeholder="Manual correction after offline payment"
            />
          </FormRow>

          {target?.plan && (
            <p className="text-xs text-slate-500">
              Plan price: {formatMoney(target.plan.price_monthly, target.plan.currency)} monthly /{' '}
              {formatMoney(target.plan.price_yearly, target.plan.currency)} yearly.
            </p>
          )}
        </div>
      </Modal>
    </div>
  )
}
