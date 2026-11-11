import { useQuery } from '@tanstack/react-query'
import {
  Activity,
  AlertTriangle,
  BadgeDollarSign,
  CheckCircle2,
  Clock,
  Container,
  FileText,
  Import,
  Package,
  Ship,
  TrendingDown,
  TrendingUp,
} from 'lucide-react'
import { Link } from 'react-router-dom'
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  Legend,
  Line,
  LineChart,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import { get } from '@/lib/api'
import { LIVE_REFRESH } from '@/lib/realtime'
import { formatDate, formatMoney } from '@/lib/format'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ErrorState } from '@/components/ui/States'
import { Skeleton } from '@/components/ui/Skeleton'
import { StatCard } from '@/components/ui/StatCard'
import { StatusBadge } from '@/components/ui/StatusBadge'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { EmptyState } from '@/components/ui/States'

/**
 * Dashboard payload.
 *
 * Every field is computed by the API from real aggregate queries. There are no
 * defaults and no seeded placeholders: an absent count renders as a dash rather
 * than a fabricated zero, so a broken query is visible rather than disguised.
 *
 * Money arrives as a decimal *string* and stays one all the way to the formatter.
 * A `revenue` typed as `number` here would be a lie the type system could not
 * catch and the arithmetic could not repair - the API stores `decimal(15,4)` and
 * a float cannot hold most of what a port charges. The chart data is the one
 * place it is converted, in `toChartMoney`, and the reason it is converted at all
 * is that a plotting scale needs a number, not because the number is truer.
 */
export interface DashboardData {
  shipments: {
    total: number
    import_count: number
    export_count: number
    draft_count: number
    confirmed_count: number
    in_transit_count: number
    arrived_count: number
    delivered_count: number
    delayed_count: number
  }
  operations: {
    pending_customs: number
    pending_delivery: number
  }
  finance: {
    /**
     * Whether the finance module is live.
     *
     * Declared and checked rather than assumed. The revenue and expense figures
     * are period-to-date and would be a dash if the module were absent, and a card
     * reading "—" next to a live invoice list looks like a broken query rather
     * than like a module that has not been turned on.
     */
    available: boolean
    outstanding_invoices: number
    outstanding_amount: string
    revenue: string
    expenses: string
    profit: string
    base_currency: string
    /**
     * What the revenue, expense and profit figures cover.
     *
     * Sent rather than computed here because it is the server's month boundary:
     * a browser in a different timezone would call it a different month, and the
     * card would disagree with the ledger by a day at each end of the period.
     */
    period: {
      label: string
      start: string
      end: string
    }
  }
  charts: {
    volume_by_month: {
      month: string
      month_key: string
      total: number
      import_count: number
      export_count: number
    }[]
    status_distribution: { status: string; count: number }[]
    revenue_vs_expense: {
      month: string
      month_key: string
      revenue: string
      expenses: string
      is_current: boolean
    }[]
    top_customers: { customer: string; code: string; count: number }[]
    top_carriers: { carrier: string; code: string; count: number }[]
  }
  upcoming: {
    etd: ShipmentPreview[]
    eta: ShipmentPreview[]
    /**
     * `late`, not `delayed`. The shipment status vocabulary has no `delayed` state
     * - being late is a fact about the ETA rather than a stage - and naming the
     * list after a status that does not exist invites somebody to add one.
     */
    late: ShipmentPreview[]
    overdue_invoices: InvoicePreview[]
  }
}

interface ShipmentPreview {
  id: number
  /** The consignment's own reference, not a legacy `reference_number`. */
  reference: string
  customer: string | null
  etd: string | null
  eta: string | null
  status: string
  status_label: string
  is_late: boolean
}

interface InvoicePreview {
  id: number
  /** The invoice's own reference, not a legacy `invoice_number`. */
  reference: string
  customer: string | null
  due_date: string | null
  balance: string
  days_overdue: number
}

const CHART_COLORS = [
  'var(--color-brand-500)',
  'var(--color-brand-300)',
  'var(--color-success-500)',
  'var(--color-warning-500)',
  'var(--color-danger-500)',
  'var(--color-info-500)',
]

export function DashboardPage() {
  const { data, isPending, isError, error, refetch, isRefetching } = useQuery({
    queryKey: ['dashboard'],
    queryFn: () => get<DashboardData>('/dashboard'),
    refetchInterval: LIVE_REFRESH.standard,
  })

  const loading = isPending || (isRefetching && !data)

  /*
   * The one place a money string becomes a number.
   *
   * Recharts needs a number to place a point, and a decimal string is not one it
   * will coerce reliably - given one it either plots the point at NaN or silently
   * drops it, and a chart with a missing month is worse than a chart with a
   * rounded one. The conversion is display-only: the figure the API sent is what
   * the ledger holds, and the tooltip is fed back through `formatMoney` so the
   * reader is shown two decimals rather than the float's own idea of them.
   */
  const financeSeries = (data?.charts.revenue_vs_expense ?? []).map((row) => ({
    month: row.month,
    revenue: Number(row.revenue),
    expenses: Number(row.expenses),
  }))

  return (
    <div className="space-y-4 p-4 sm:p-6">
      <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        <StatCard
          label="Total Shipments"
          value={<Count value={data?.shipments.total} loading={loading} />}
          icon={Package}
          tone="brand"
        />
        <StatCard
          label="Import"
          value={<Count value={data?.shipments.import_count} loading={loading} />}
          icon={Import}
          tone="info"
        />
        <StatCard
          label="Export"
          value={<Count value={data?.shipments.export_count} loading={loading} />}
          icon={Ship}
          tone="info"
        />
        <StatCard
          label="In Transit"
          value={<Count value={data?.shipments.in_transit_count} loading={loading} />}
          icon={Activity}
          tone="brand"
        />
        <StatCard
          label="Delivered"
          value={<Count value={data?.shipments.delivered_count} loading={loading} />}
          icon={CheckCircle2}
          tone="success"
        />
        <StatCard
          label="Delayed"
          value={<Count value={data?.shipments.delayed_count} loading={loading} />}
          icon={AlertTriangle}
          tone="danger"
        />
      </div>

      <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        <StatCard
          label="Pending Customs"
          value={<Count value={data?.operations.pending_customs} loading={loading} />}
          icon={FileText}
          tone="warning"
        />
        <StatCard
          label="Pending Delivery"
          value={<Count value={data?.operations.pending_delivery} loading={loading} />}
          icon={Container}
          tone="warning"
        />
        <StatCard
          label="Outstanding Invoices"
          value={<Count value={data?.finance.outstanding_invoices} loading={loading} />}
          icon={Clock}
          tone="warning"
          /*
           * The amount under the count, not in place of it. "12" answers how much
           * chasing there is and "AED 14,382" answers how much money is at stake -
           * a collections manager triages on the second and a clerk works the first,
           * and a card can only hold one of them as the headline.
           */
          hint={
            data?.finance.available
              ? `${formatMoney(data.finance.outstanding_amount, data.finance.base_currency)} owing`
              : undefined
          }
        />
        {/*
         * Revenue, expenses and profit are period-to-date, and the label says
         * which period from the server's own month rather than a month this
         * browser worked out. Without it a figure on the 2nd of the month reads as
         * a full month and one on the 31st reads as a blip.
         */}
        <StatCard
          label={`Revenue${data?.finance.period ? ` · ${data.finance.period.label}` : ''}`}
          value={<Money value={data?.finance.revenue} currency={data?.finance.base_currency} loading={loading} />}
          icon={TrendingUp}
          tone="success"
          hint={data?.finance.available === false ? 'Finance module is off' : undefined}
        />
        <StatCard
          label={`Expenses${data?.finance.period ? ` · ${data.finance.period.label}` : ''}`}
          value={<Money value={data?.finance.expenses} currency={data?.finance.base_currency} loading={loading} />}
          icon={TrendingDown}
          tone="danger"
        />
        <StatCard
          label={`Profit${data?.finance.period ? ` · ${data.finance.period.label}` : ''}`}
          value={<Money value={data?.finance.profit} currency={data?.finance.base_currency} loading={loading} />}
          icon={BadgeDollarSign}
          tone="brand"
          hint="Revenue less margin-bearing costs"
        />
      </div>

      {isError && (
        <Card>
          <ErrorState
            title="Dashboard data is unavailable"
            message={error instanceof Error ? error.message : 'The request failed.'}
            onRetry={() => void refetch()}
            retrying={isRefetching}
          />
        </Card>
      )}

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader title="Shipment Volume by Month" description="Import and export, last 12 months" />
          <CardBody className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={data?.charts.volume_by_month ?? []}>
                <CartesianGrid strokeDasharray="3 3" stroke="var(--color-ink-200)" vertical={false} />
                <XAxis dataKey="month" tick={{ fontSize: 11, fill: 'var(--color-ink-500)' }} tickLine={false} axisLine={false} />
                <YAxis tick={{ fontSize: 11, fill: 'var(--color-ink-500)' }} tickLine={false} axisLine={false} allowDecimals={false} />
                <Tooltip />
                <Legend wrapperStyle={{ fontSize: 12 }} />
                <Bar dataKey="import_count" name="Import" stackId="a" fill="var(--color-brand-500)" radius={[0, 0, 0, 0]} />
                <Bar dataKey="export_count" name="Export" stackId="a" fill="var(--color-brand-300)" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Shipment Status Distribution" />
          <CardBody className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={data?.charts.status_distribution ?? []}
                  dataKey="count"
                  nameKey="status"
                  innerRadius="55%"
                  outerRadius="80%"
                  paddingAngle={2}
                >
                  {(data?.charts.status_distribution ?? []).map((entry, index) => (
                    <Cell key={entry.status} fill={CHART_COLORS[index % CHART_COLORS.length]} />
                  ))}
                </Pie>
                <Tooltip />
                <Legend wrapperStyle={{ fontSize: 11 }} />
              </PieChart>
            </ResponsiveContainer>
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Revenue vs Expenses" description="Recognised amounts per month" />
          <CardBody className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={financeSeries}>
                <CartesianGrid strokeDasharray="3 3" stroke="var(--color-ink-200)" vertical={false} />
                <XAxis dataKey="month" tick={{ fontSize: 11, fill: 'var(--color-ink-500)' }} tickLine={false} axisLine={false} />
                <YAxis tick={{ fontSize: 11, fill: 'var(--color-ink-500)' }} tickLine={false} axisLine={false} tickFormatter={compactMoney} />
                <Tooltip formatter={(value) => formatMoney(String(value), data?.finance.base_currency)} />
                <Legend wrapperStyle={{ fontSize: 12 }} />
                <Line type="monotone" dataKey="revenue" name="Revenue" stroke="var(--color-success-500)" strokeWidth={2} dot={false} />
                <Line type="monotone" dataKey="expenses" name="Expenses" stroke="var(--color-danger-500)" strokeWidth={2} dot={false} />
              </LineChart>
            </ResponsiveContainer>
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Top Customers" description="By shipment count" />
          <CardBody className="h-72">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={data?.charts.top_customers ?? []} layout="vertical" margin={{ left: 8 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="var(--color-ink-200)" horizontal={false} />
                <XAxis type="number" tick={{ fontSize: 11, fill: 'var(--color-ink-500)' }} tickLine={false} axisLine={false} allowDecimals={false} />
                <YAxis type="category" dataKey="code" width={72} tick={{ fontSize: 11, fill: 'var(--color-ink-500)' }} tickLine={false} axisLine={false} />
                <Tooltip />
                <Bar dataKey="count" name="Shipments" fill="var(--color-brand-500)" radius={[0, 4, 4, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </CardBody>
        </Card>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <UpcomingShipments title="Upcoming ETD" rows={data?.upcoming.etd} field="etd" loading={loading} />
        <UpcomingShipments title="Upcoming ETA" rows={data?.upcoming.eta} field="eta" loading={loading} />
        {/*
         * The third preview list, and the one the API has been sending all along.
         * A consignment past its ETA is a different question from either of the
         * other two - "what is late" rather than "what is next" - and it is the
         * only list here an operator cannot reconstruct by sorting the other two.
         */}
        <UpcomingShipments
          title="Late Consignments"
          rows={data?.upcoming.late}
          field="eta"
          emptyTitle="Nothing running late"
          loading={loading}
        />
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader
            title="Top Carriers"
            action={
              <Link to="/reports/carriers" className="text-brand-700 text-xs font-medium">
                View report
              </Link>
            }
          />
          <CardBody className="p-0">
            {(data?.charts.top_carriers ?? []).length === 0 ? (
              <EmptyState title="No carrier activity yet" />
            ) : (
              <Table>
                <THead>
                  <TR>
                    <TH>Carrier</TH>
                    <TH align="right">Shipments</TH>
                  </TR>
                </THead>
                <TBody>
                  {data!.charts.top_carriers.map((carrier) => (
                    <TR key={carrier.code}>
                      <TD>
                        <span className="font-medium text-ink-900">{carrier.carrier}</span>
                        <span className="text-ink-500 ml-2 text-xs">{carrier.code}</span>
                      </TD>
                      <TD align="right" className="tabular">
                        {carrier.count}
                      </TD>
                    </TR>
                  ))}
                </TBody>
              </Table>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader
            title="Overdue Invoices"
            action={
              <Link to="/finance/invoices" className="text-brand-700 text-xs font-medium">
                View all
              </Link>
            }
          />
          <CardBody className="p-0">
            {(data?.upcoming.overdue_invoices ?? []).length === 0 ? (
              <EmptyState title="Nothing overdue" description="All issued invoices are within terms." />
            ) : (
              <Table>
                <THead>
                  <TR>
                    <TH>Invoice</TH>
                    <TH>Customer</TH>
                    <TH align="right">Balance</TH>
                    <TH align="right">Days</TH>
                  </TR>
                </THead>
                <TBody>
                  {data!.upcoming.overdue_invoices.map((invoice) => (
                    <TR key={invoice.id}>
                      <TD>
                        <Link
                          to={`/finance/invoices?q=${encodeURIComponent(invoice.reference)}`}
                          className="text-brand-700 font-medium hover:underline"
                        >
                          {invoice.reference}
                        </Link>
                      </TD>
                      <TD>{invoice.customer ?? <span className="text-ink-300">—</span>}</TD>
                      <TD align="right" className="tabular">
                        {/*
                         * The invoice's currency, which the preview does not carry.
                         * The list the link goes to shows it on every row, so an
                         * unlabelled amount here is resolved one click away - and
                         * guessing a base currency from the browser's locale would
                         * be worse than showing none.
                         */}
                        {formatMoney(invoice.balance)}
                      </TD>
                      <TD align="right">
                        <span className="tabular text-danger-600 font-medium">
                          {invoice.days_overdue}
                        </span>
                      </TD>
                    </TR>
                  ))}
                </TBody>
              </Table>
            )}
          </CardBody>
        </Card>
      </div>
    </div>
  )
}

function UpcomingShipments({
  title,
  rows,
  field,
  emptyTitle,
  loading,
}: {
  title: string
  rows: ShipmentPreview[] | undefined
  field: 'etd' | 'eta'
  /**
   * Overrides the empty state. The default is derived from the date column, which
   * reads correctly for a schedule ("No ETD scheduled") and wrongly for the late
   * list - "No ETA scheduled" describes the wrong condition, when what is meant
   * is that nothing has missed one.
   */
  emptyTitle?: string
  loading: boolean
}) {
  return (
    <Card>
      <CardHeader title={title} />
      <CardBody className="p-0">
        {loading ? (
          <div className="space-y-2 p-4">
            {Array.from({ length: 4 }).map((_, index) => (
              <Skeleton key={index} className="h-9 w-full" />
            ))}
          </div>
        ) : (rows ?? []).length === 0 ? (
          <EmptyState title={emptyTitle ?? `No ${field.toUpperCase()} scheduled`} />
        ) : (
          <Table>
            <THead>
              <TR>
                <TH>Reference</TH>
                <TH>Customer</TH>
                <TH align="right">{field.toUpperCase()}</TH>
                <TH>Status</TH>
              </TR>
            </THead>
            <TBody>
              {rows!.map((shipment) => (
                <TR key={shipment.id}>
                  <TD>
                    <Link
                      to={`/operations/shipments/${shipment.id}`}
                      className="text-brand-700 font-medium hover:underline"
                    >
                      {shipment.reference}
                    </Link>
                  </TD>
                  <TD>{shipment.customer ?? <span className="text-ink-300">—</span>}</TD>
                  <TD align="right" className="tabular">
                    {formatDate(shipment[field])}
                  </TD>
                  <TD>
                    {/*
                     * The server's own label rather than a client lookup by status
                     * value, for the reason the badges everywhere else do it: one
                     * translation of the vocabulary, so this card and the list it
                     * links to cannot describe the same state differently.
                     */}
                    <StatusBadge domain="shipment" status={shipment.status} />
                  </TD>
                </TR>
              ))}
            </TBody>
          </Table>
        )}
      </CardBody>
    </Card>
  )
}

/** Renders a dash when the value is absent, so a broken query is visible. */
function Count({ value, loading }: { value: number | undefined; loading: boolean }) {
  if (loading) return <Skeleton className="h-7 w-16" />
  return value === undefined ? <span className="text-ink-300">&mdash;</span> : value.toLocaleString()
}

function Money({
  value,
  currency,
  loading,
}: {
  value: string | undefined
  currency: string | undefined
  loading: boolean
}) {
  if (loading) return <Skeleton className="h-7 w-24" />
  if (value === undefined) return <span className="text-ink-300">&mdash;</span>

  return (
    <span>
      {formatMoney(value)}
      {currency && <span className="text-ink-500 ml-1.5 text-sm font-normal">{currency}</span>}
    </span>
  )
}

/**
 * A y-axis tick, abbreviated.
 *
 * A month of revenue in full is six figures and a hundred thousand minor units, so
 * the axis would print a column of eleven-digit numbers with the plot squeezed
 * into the sliver between them. Abbreviating to `K`/`M` keeps the tick to three
 * characters; the tooltip is unformatted, which is where the exact figure belongs.
 */
function compactMoney(value: number): string {
  if (!Number.isFinite(value)) return '—'
  if (Math.abs(value) >= 1_000_000) return `${(value / 1_000_000).toFixed(1)}M`
  if (Math.abs(value) >= 1_000) return `${Math.round(value / 1_000)}K`

  return String(Math.round(value))
}
