import { useCallback, useState } from 'react'
import { toast } from 'sonner'
import { BadgeCheck, CreditCard, ExternalLink, RefreshCw, Sparkles, Tag } from 'lucide-react'
import { businessService } from '../../services/businessService'
import { useBusiness } from '../../context/BusinessContext'
import { useApi } from '../../hooks/useApi'
import { api, apiErrorMessage } from '../../api/client'
import { formatMoney, formatDate } from '../../utils/format'
import { BILLING_CYCLES, FEATURE_LABELS } from '../../utils/constants'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, StatCard } from '../../components/ui/Feedback'
import { Badge, StatusBadge } from '../../components/ui/Badge'
import { Button } from '../../components/ui/Button'
import { Card, CardBody, CardHeader } from '../../components/ui/Card'
import { Input, Select } from '../../components/ui/Form'
import { Modal } from '../../components/ui/Modal'
import { Table, Td, Th, Tr } from '../../components/ui/Table'

export default function Billing() {
  const { billing, refreshBilling, activeBusiness } = useBusiness()
  const [selectedPlan, setSelectedPlan] = useState(null)
  const [cycle, setCycle] = useState('monthly')
  const [gateway, setGateway] = useState('')
  const [couponCode, setCouponCode] = useState('')
  const [processing, setProcessing] = useState(false)
  const [pending, setPending] = useState(null)
  const [error, setError] = useState(null)

  const invoicesLoader = useCallback(() => businessService.billingInvoices({ per_page: 10 }), [])
  const { data: invoiceData } = useApi(invoicesLoader, [invoicesLoader])

  const gateways = billing?.gateways ?? []
  const plans = billing?.plans ?? []
  const subscription = billing?.subscription

  const startCheckout = async () => {
    if (!selectedPlan) {
      return
    }

    setProcessing(true)
    setError(null)

    try {
      const result = await businessService.checkout({
        plan_id: selectedPlan.id,
        billing_cycle: cycle,
        gateway: gateway || undefined,
        coupon_code: couponCode.trim() || undefined,
      })

      setPending(result)
      toast.success('Payment created. Complete the payment to activate the plan.')
    } catch (exception) {
      setError(apiErrorMessage(exception, 'Unable to start the checkout.'))
    } finally {
      setProcessing(false)
    }
  }

  const verifyPayment = async () => {
    if (!pending?.reference) {
      return
    }

    setProcessing(true)
    setError(null)

    try {
      await businessService.confirmCheckout(pending.reference)
      toast.success('Payment confirmed. Your plan is active.')
      setPending(null)
      await refreshBilling()
    } catch (exception) {
      setError(apiErrorMessage(exception, 'The payment could not be confirmed yet.'))
    } finally {
      setProcessing(false)
    }
  }

  /**
   * Development helper for the deterministic fake gateway. It posts the very
   * same webhook a provider would send, so the whole billing pipeline is
   * exercised locally. It is never available in a production build.
   */
  const simulatePayment = async () => {
    if (!pending) {
      return
    }

    setProcessing(true)
    setError(null)

    try {
      await api.post('/webhooks/fake', {
        event_id: `evt_${pending.reference}_${Date.now()}`,
        event_type: 'payment_succeeded',
        reference: pending.reference,
        amount: pending.payment?.amount ?? null,
        currency: pending.payment?.currency ?? null,
        business_id: activeBusiness?.id ?? null,
      }, {
        headers: { 'X-Fake-Signature': 'fake-webhook-secret' },
      })

      toast.success('Development payment simulated.')
      await refreshBilling()
      setPending(null)
    } catch (exception) {
      setError(apiErrorMessage(exception, 'Unable to simulate the payment.'))
    } finally {
      setProcessing(false)
    }
  }

  const changeSubscription = async (action) => {
    setProcessing(true)
    setError(null)

    try {
      await businessService.billingPortal({ action, at_period_end: action === 'cancel' })
      toast.success(action === 'cancel' ? 'Subscription cancelled.' : 'Subscription resumed.')
      await refreshBilling()
    } catch (exception) {
      setError(apiErrorMessage(exception, 'Unable to update the subscription.'))
    } finally {
      setProcessing(false)
    }
  }

  return (
    <div>
      <PageHeader
        title="Billing"
        description="Your plan, payment history and available upgrades."
        actions={
          <Button variant="secondary" onClick={() => refreshBilling()} loading={processing}>
            <RefreshCw className="size-4" />
            Refresh
          </Button>
        }
      />

      {error && (
        <div className="mb-4">
          <Alert tone="danger">{error}</Alert>
        </div>
      )}

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-1">
          <CardHeader title="Current subscription" />
          <CardBody className="space-y-3 text-sm">
            {subscription ? (
              <>
                <div className="flex items-center justify-between">
                  <span className="text-slate-500">Plan</span>
                  <span className="font-semibold text-slate-900">{subscription.plan}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-slate-500">Status</span>
                  <StatusBadge status={subscription.status} />
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-slate-500">Billing cycle</span>
                  <span className="text-slate-700">{subscription.billing_cycle ?? '—'}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-slate-500">Started</span>
                  <span className="text-slate-700">{formatDate(subscription.starts_at)}</span>
                </div>
                {subscription.trial_ends_at && (
                  <div className="flex items-center justify-between">
                    <span className="text-slate-500">Trial ends</span>
                    <span className="text-slate-700">{formatDate(subscription.trial_ends_at)}</span>
                  </div>
                )}
                {subscription.ends_at && (
                  <div className="flex items-center justify-between">
                    <span className="text-slate-500">Current period ends</span>
                    <span className="text-slate-700">{formatDate(subscription.ends_at)}</span>
                  </div>
                )}

                <div className="flex flex-wrap gap-2 pt-2">
                  {subscription.status === 'canceled' ? (
                    <Button size="sm" onClick={() => changeSubscription('resume')} loading={processing}>
                      Resume subscription
                    </Button>
                  ) : (
                    <Button size="sm" variant="secondary" onClick={() => changeSubscription('cancel')} loading={processing}>
                      Cancel subscription
                    </Button>
                  )}
                </div>
              </>
            ) : (
              <p className="text-slate-500">No subscription is attached to this business yet.</p>
            )}
          </CardBody>
        </Card>

        <Card className="lg:col-span-2">
          <CardHeader title="Usage" description="Consumption against the limits of the current plan." />
          <CardBody className="grid gap-4 sm:grid-cols-2">
            {(billing?.usage ?? []).map((entry) => (
              <StatCard
                key={entry.key}
                label={FEATURE_LABELS[entry.key] ?? entry.key}
                value={entry.limit === null ? `${entry.used}` : `${entry.used} / ${entry.limit}`}
                hint={entry.remaining === null ? 'Unlimited' : `${entry.remaining} remaining`}
              />
            ))}
          </CardBody>
        </Card>
      </div>

      <h2 className="mb-4 mt-8 text-lg font-semibold text-slate-900">Plans</h2>
      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        {plans.map((plan) => {
          const price = cycle === 'yearly' ? plan.price_yearly : plan.price_monthly
          const isCurrent = subscription?.plan_slug === plan.slug

          return (
            <Card key={plan.id} className={isCurrent ? 'ring-brand-300' : ''}>
              <CardBody>
                <div className="flex items-start justify-between gap-2">
                  <div>
                    <h3 className="text-base font-semibold text-slate-900">{plan.name}</h3>
                    <p className="mt-1 text-sm text-slate-500">{plan.description}</p>
                  </div>
                  {isCurrent && <Badge tone="brand">Current</Badge>}
                </div>

                <p className="mt-4 text-2xl font-semibold tracking-tight text-slate-900">
                  {formatMoney(price, plan.currency)}
                  <span className="ml-1 text-sm font-normal text-slate-500">
                    /{cycle === 'yearly' ? 'year' : 'month'}
                  </span>
                </p>

                <ul className="mt-4 space-y-1.5 text-sm text-slate-600">
                  {(plan.features ?? [])
                    .filter((feature) => feature.enabled)
                    .slice(0, 6)
                    .map((feature) => (
                      <li key={feature.key} className="flex items-start gap-2">
                        <BadgeCheck className="mt-0.5 size-4 shrink-0 text-emerald-500" />
                        <span>
                          {FEATURE_LABELS[feature.key] ?? feature.key}
                          {feature.limit !== null && feature.limit !== undefined ? ` (${feature.limit})` : ''}
                        </span>
                      </li>
                    ))}
                </ul>

                {plan.type !== 'free' && (
                  <Button
                    className="mt-5 w-full"
                    variant={isCurrent ? 'secondary' : 'primary'}
                    onClick={() => {
                      setSelectedPlan(plan)
                      setCycle(cycle)
                      setPending(null)
                      setError(null)
                      setCouponCode('')
                    }}
                  >
                    <Sparkles className="size-4" />
                    {isCurrent ? 'Change plan' : 'Upgrade'}
                  </Button>
                )}
              </CardBody>
            </Card>
          )
        })}
      </div>

      <Card className="mt-6">
        <CardHeader title="Billing invoices" description="Receipts issued for every settled subscription payment." />
        {(invoiceData?.data ?? []).length === 0 ? (
          <CardBody>
            <p className="text-sm text-slate-500">No billing invoices yet.</p>
          </CardBody>
        ) : (
          <Table>
            <thead className="bg-slate-50">
              <tr>
                <Th>Invoice</Th>
                <Th>Status</Th>
                <Th>Issued</Th>
                <Th className="text-right">Total</Th>
              </tr>
            </thead>
            <tbody>
              {invoiceData.data.map((invoice) => (
                <Tr key={invoice.id}>
                  <Td className="font-medium text-slate-900">{invoice.number ?? invoice.invoice_number ?? `#${invoice.id}`}</Td>
                  <Td>
                    <StatusBadge status={invoice.status} />
                  </Td>
                  <Td>{formatDate(invoice.issued_at ?? invoice.created_at)}</Td>
                  <Td className="text-right font-medium text-slate-900">
                    {formatMoney(invoice.total, invoice.currency)}
                  </Td>
                </Tr>
              ))}
            </tbody>
          </Table>
        )}
      </Card>

      <Modal
        open={Boolean(selectedPlan)}
        onClose={() => {
          setSelectedPlan(null)
          setPending(null)
          setCouponCode('')
        }}
        title={pending ? 'Complete your payment' : `Switch to ${selectedPlan?.name ?? ''}`}
        description={pending ? 'The plan is activated as soon as the provider confirms the payment.' : 'Choose a billing cycle and payment method.'}
        footer={
          pending ? (
            <>
              <Button variant="secondary" onClick={() => setPending(null)} disabled={processing}>
                Cancel
              </Button>
              {import.meta.env.DEV && pending.payment?.provider === 'fake' && (
                <Button variant="secondary" onClick={simulatePayment} loading={processing}>
                  Simulate payment (dev)
                </Button>
              )}
              <Button onClick={verifyPayment} loading={processing}>
                Verify payment
              </Button>
            </>
          ) : (
            <>
              <Button
                variant="secondary"
                onClick={() => {
                  setSelectedPlan(null)
                  setCouponCode('')
                }}
                disabled={processing}
              >
                Cancel
              </Button>
              <Button
                onClick={() => {
                  startCheckout()
                }}
                loading={processing}
              >
                Continue to payment
              </Button>
            </>
          )
        }
      >
        {pending ? (
          <div className="space-y-3 text-sm text-slate-600">
            <p>
              Reference: <span className="font-mono text-slate-900">{pending.reference}</span>
            </p>
            <p>
              Amount:{' '}
              <span className="font-medium text-slate-900">
                {formatMoney(pending.payment?.amount, pending.payment?.currency)}
              </span>
            </p>
            {pending.checkout_url && !pending.checkout_url.includes('fake-gateway.test') && (
              <a
                href={pending.checkout_url}
                target="_blank"
                rel="noreferrer"
                className="inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:underline"
              >
                <ExternalLink className="size-4" />
                Open the provider checkout
              </a>
            )}
            <Alert tone="info">
              The subscription only changes after the provider confirms the payment. The browser can never activate a plan
              on its own.
            </Alert>
          </div>
        ) : (
          <div className="space-y-4">
            <div>
              <p className="mb-1.5 text-sm font-medium text-slate-700">Billing cycle</p>
              <Select value={cycle} onChange={(event) => setCycle(event.target.value)}>
                {BILLING_CYCLES.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </Select>
            </div>

            <div>
              <p className="mb-1.5 text-sm font-medium text-slate-700">Payment method</p>
              <Select value={gateway} onChange={(event) => setGateway(event.target.value)}>
                <option value="">Default gateway</option>
                {gateways.map((name) => (
                  <option key={name} value={name}>
                    {name}
                  </option>
                ))}
              </Select>
            </div>

            <div>
              <p className="mb-1.5 text-sm font-medium text-slate-700">Coupon code</p>
              <div className="flex items-center gap-2">
                <Tag className="size-4 text-slate-400" />
                <Input value={couponCode} onChange={(event) => setCouponCode(event.target.value)} placeholder="Enter coupon code" />
              </div>
            </div>

            <div className="rounded-lg bg-slate-50 p-4">
              <div className="flex items-center justify-between text-sm">
                <span className="text-slate-500">Amount</span>
                <span className="font-semibold text-slate-900">
                  {formatMoney(
                    cycle === 'yearly' ? selectedPlan?.price_yearly : selectedPlan?.price_monthly,
                    selectedPlan?.currency,
                  )}
                </span>
              </div>
              <div className="mt-2 flex items-center gap-2 text-xs text-slate-500">
                <CreditCard className="size-3.5" />
                Charged to the business, not to your personal account.
              </div>
            </div>
          </div>
        )}
      </Modal>
    </div>
  )
}
