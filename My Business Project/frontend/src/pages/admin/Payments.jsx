import { useCallback, useState } from 'react'
import { CreditCard } from 'lucide-react'
import { adminService } from '../../services/adminService'
import { useApi } from '../../hooks/useApi'
import { apiErrorMessage } from '../../api/client'
import { formatDateTime, formatMoney } from '../../utils/format'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, Pagination } from '../../components/ui/Feedback'
import { StatusBadge } from '../../components/ui/Badge'
import { Card } from '../../components/ui/Card'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'

const STATUS_TONES = {
  succeeded: 'success',
  pending: 'warning',
  failed: 'danger',
  refunded: 'info',
}

export default function AdminPayments() {
  const [status, setStatus] = useState('')
  const [provider, setProvider] = useState('')
  const [page, setPage] = useState(1)

  const loader = useCallback(
    () => adminService.payments({ status: status || undefined, provider: provider || undefined, page }),
    [status, provider, page],
  )

  const { data, loading, error } = useApi(loader, [status, provider, page])

  const payments = data?.data ?? []

  return (
    <div>
      <PageHeader title="Payments" description="Every transaction processed by the platform." />

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
            <option value="succeeded">Succeeded</option>
            <option value="pending">Pending</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
          </select>

          <select
            value={provider}
            onChange={(event) => {
              setProvider(event.target.value)
              setPage(1)
            }}
            className="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"
          >
            <option value="">All providers</option>
            <option value="fake">Fake</option>
            <option value="stripe">Stripe</option>
            <option value="paypal">PayPal</option>
          </select>
        </div>

        {loading && <LoadingBlock />}

        {error && !loading && (
          <div className="p-5">
            <Alert tone="danger">{apiErrorMessage(error)}</Alert>
          </div>
        )}

        {!loading && !error && payments.length === 0 && (
          <EmptyState icon={CreditCard} title="No payments found" description="Try another filter." />
        )}

        {!loading && !error && payments.length > 0 && (
          <>
            <Table>
              <thead className="bg-slate-50">
                <tr>
                  <Th>Reference</Th>
                  <Th>Business</Th>
                  <Th>Amount</Th>
                  <Th>Provider</Th>
                  <Th>Status</Th>
                  <Th>Paid at</Th>
                </tr>
              </thead>
              <tbody>
                {payments.map((payment) => (
                  <Tr key={payment.id}>
                    <Td>
                      <p className="font-mono text-xs text-slate-900">{payment.payment_number ?? `#${payment.id}`}</p>
                      <p className="text-xs text-slate-500">{payment.provider_payment_id ?? ''}</p>
                    </Td>
                    <Td>{payment.business?.name ?? '—'}</Td>
                    <Td className="font-medium text-slate-900">
                      {formatMoney(payment.amount, payment.currency ?? 'USD')}
                    </Td>
                    <Td className="capitalize">{payment.provider ?? '—'}</Td>
                    <Td>
                      <StatusBadge status={payment.status} tone={STATUS_TONES[payment.status]} />
                    </Td>
                    <Td className="text-xs">{formatDateTime(payment.paid_at ?? payment.created_at)}</Td>
                  </Tr>
                ))}
              </tbody>
            </Table>

            <Pagination meta={data} onPageChange={setPage} />
          </>
        )}
      </Card>
    </div>
  )
}
