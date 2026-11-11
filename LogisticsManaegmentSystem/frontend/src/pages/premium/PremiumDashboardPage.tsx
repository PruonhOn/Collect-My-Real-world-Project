import { useQuery } from '@tanstack/react-query'
import { BadgeDollarSign, Clock, Ship, TrendingUp, Truck } from 'lucide-react'
import { Link } from 'react-router-dom'
import {
  Cell,
  Legend,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
} from 'recharts'
import { get } from '@/lib/api'
import { LIVE_REFRESH } from '@/lib/realtime'
import { formatMoney } from '@/lib/format'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { ErrorState } from '@/components/ui/States'
import { Skeleton } from '@/components/ui/Skeleton'
import { StatCard } from '@/components/ui/StatCard'
import { StatusBadge } from '@/components/ui/StatusBadge'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { EmptyState } from '@/components/ui/States'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import { RequirePermission } from '@/components/guards/ProtectedRoute'

interface PremiumDashboardData {
  shipments: {
    total: number
    late_count: number
    booked_count: number
    in_transit_count: number
    customs_count: number
    arrived_count: number
    delivered_count: number
  }
  finance: {
    outstanding_amount: string
    revenue: string
    expenses: string
    profit: string
    base_currency: string
  }
  operations: {
    held_customs: number
    outstanding_deliveries: number
    overdue_deliveries: number
    containers_needing_attention: number
  }
}

const CHART_COLORS = [
  'var(--color-premium-500)',
  'var(--color-premium-300)',
  'var(--color-success-500)',
  'var(--color-warning-500)',
  'var(--color-danger-500)',
]

export function PremiumDashboardPage() {
  const { data, isPending, isError, error, refetch, isRefetching } = useQuery({
    queryKey: ['premium', 'dashboard'],
    queryFn: () => get<PremiumDashboardData>('/dashboard'),
    refetchInterval: LIVE_REFRESH.standard,
  })

  const loading = isPending || (isRefetching && !data)
  const shipments = data?.shipments
  const finance = data?.finance
  const operations = data?.operations

  return (
    <RequirePermission permission="premium.view">
      <div className="space-y-6 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div className="min-w-0">
            <h1 className="text-lg font-semibold text-ink-900 tracking-tight">
              Premium <span className="text-premium-700">Dashboard</span>
            </h1>
            <p className="text-xs text-ink-500 mt-1 leading-relaxed">
              VIP customer operations and priority shipment overview.
            </p>
          </div>
          <Link to="/premium/shipments" className="flex items-center gap-2">
            <Button variant="outline" size="sm">
              <Ship className="size-3.5 mr-2" aria-hidden />
              View priority shipments
            </Button>
          </Link>
        </div>

        {isError && (
          <Card>
            <ErrorState
              title="Premium dashboard data is unavailable"
              message={error instanceof Error ? error.message : 'The request failed.'}
              onRetry={() => void refetch()}
              retrying={isRefetching}
            />
          </Card>
        )}

        <div className="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
          <StatCard
            label="Total Shipments"
            value={<Count value={shipments?.total} loading={loading} />}
            icon={Ship}
            tone="premium"
            hint={shipments ? `${shipments.total.toLocaleString()} consignments` : undefined}
          />
          <StatCard
            label="Late / Priority"
            value={<Count value={shipments?.late_count} loading={loading} />}
            icon={Clock}
            tone="warning"
            hint={shipments ? `${shipments.late_count.toLocaleString()} need attention` : undefined}
          />
          <StatCard
            label="In Transit"
            value={<Count value={shipments?.in_transit_count} loading={loading} />}
            icon={Truck}
            tone="brand"
            hint={shipments ? `${shipments.in_transit_count.toLocaleString()} moving` : undefined}
          />
          <StatCard
            label="Outstanding"
            value={<Money value={finance?.outstanding_amount} currency={finance?.base_currency} loading={loading} />}
            icon={BadgeDollarSign}
            tone="premium"
            hint={finance ? 'Receivables' : undefined}
          />
          <StatCard
            label="Revenue (MTD)"
            value={<Money value={finance?.revenue} currency={finance?.base_currency} loading={loading} />}
            icon={TrendingUp}
            tone="success"
            hint={finance ? finance.base_currency : undefined}
          />
          <StatCard
            label="Profit (MTD)"
            value={<Money value={finance?.profit} currency={finance?.base_currency} loading={loading} />}
            icon={BadgeDollarSign}
            tone="premium"
            hint={finance ? 'Revenue less margin-bearing costs' : undefined}
          />
        </div>

        <div className="grid gap-4 lg:grid-cols-2">
          <Card>
            <CardHeader title="Shipment Status Distribution" description="Consignments by lifecycle stage" />
            <CardBody className="h-80">
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={[
                      { status: 'Booked', count: shipments?.booked_count ?? 0 },
                      { status: 'In Transit', count: shipments?.in_transit_count ?? 0 },
                      { status: 'Customs', count: shipments?.customs_count ?? 0 },
                      { status: 'Arrived', count: shipments?.arrived_count ?? 0 },
                      { status: 'Delivered', count: shipments?.delivered_count ?? 0 },
                    ].filter((entry) => entry.count > 0)}
                    dataKey="count"
                    nameKey="status"
                    innerRadius="55%"
                    outerRadius="85%"
                    paddingAngle={3}
                  >
                    {(shipments?.booked_count ?? 0) > 0 && <Cell fill={CHART_COLORS[0]} />}
                    {(shipments?.in_transit_count ?? 0) > 0 && <Cell fill={CHART_COLORS[1]} />}
                    {(shipments?.customs_count ?? 0) > 0 && <Cell fill={CHART_COLORS[2]} />}
                    {(shipments?.arrived_count ?? 0) > 0 && <Cell fill={CHART_COLORS[3]} />}
                    {(shipments?.delivered_count ?? 0) > 0 && <Cell fill={CHART_COLORS[4]} />}
                  </Pie>
                  <Tooltip />
                  <Legend wrapperStyle={{ fontSize: 11 }} />
                </PieChart>
              </ResponsiveContainer>
            </CardBody>
          </Card>

          <Card>
            <CardHeader title="Operations Attention" description="Items requiring immediate action" />
            <CardBody className="p-0">
              <Table>
                <THead>
                  <TR>
                    <TH>Queue</TH>
                    <TH align="right">Count</TH>
                    <TH>Severity</TH>
                  </TR>
                </THead>
                <TBody>
                  <TR>
                    <TD className="font-medium text-ink-900">Held customs</TD>
                    <TD align="right" className="tabular">
                      <Count value={operations?.held_customs} loading={loading} />
                    </TD>
                    <TD>
                      <StatusBadge domain="customs" status="under_review" />
                    </TD>
                  </TR>
                  <TR>
                    <TD className="font-medium text-ink-900">Outstanding deliveries</TD>
                    <TD align="right" className="tabular">
                      <Count value={operations?.outstanding_deliveries} loading={loading} />
                    </TD>
                    <TD>
                      <StatusBadge domain="delivery" status="scheduled" />
                    </TD>
                  </TR>
                  <TR>
                    <TD className="font-medium text-ink-900">Overdue deliveries</TD>
                    <TD align="right" className="tabular">
                      <Count value={operations?.overdue_deliveries} loading={loading} />
                    </TD>
                    <TD>
                      <StatusBadge domain="delivery" status="failed" />
                    </TD>
                  </TR>
                  <TR>
                    <TD className="font-medium text-ink-900">Containers needing attention</TD>
                    <TD align="right" className="tabular">
                      <Count value={operations?.containers_needing_attention} loading={loading} />
                    </TD>
                    <TD>
                      <StatusBadge domain="container" status="customs_hold" />
                    </TD>
                  </TR>
                </TBody>
              </Table>
            </CardBody>
          </Card>
        </div>

        <div className="grid gap-4 lg:grid-cols-3">
          <Card>
            <CardHeader title="Finance at a Glance" description="Premium receivables and month-to-date" />
            <CardBody className="p-0">
              <div className="space-y-4 p-4">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-xs text-ink-500">Revenue (MTD)</p>
                    <p className="tabular font-semibold text-ink-900">
                      {formatMoney(finance?.revenue ?? '0', finance?.base_currency)}
                    </p>
                  </div>
                  <Badge tone="success" dot>
                    {finance ? '+' : '—'}
                  </Badge>
                </div>
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-xs text-ink-500">Expenses (MTD)</p>
                    <p className="tabular font-semibold text-ink-900">
                      {formatMoney(finance?.expenses ?? '0', finance?.base_currency)}
                    </p>
                  </div>
                  <Badge tone="danger" dot>
                    {finance ? '-' : '—'}
                  </Badge>
                </div>
                <div className="flex items-center justify-between border-t border-ink-100 pt-3">
                  <div>
                    <p className="text-xs text-ink-500">Profit (MTD)</p>
                    <p className="tabular font-semibold text-ink-900">
                      {formatMoney(finance?.profit ?? '0', finance?.base_currency)}
                    </p>
                  </div>
                  <Badge tone="premium" dot>
                    {finance ? '+' : '—'}
                  </Badge>
                </div>
              </div>
            </CardBody>
          </Card>

          <Card>
            <CardHeader title="Top Carriers" action={<Link to="/reports/carriers" className="text-premium-700 text-xs font-medium hover:underline">View report</Link>} description="By shipment volume" />
            <CardBody className="p-0">
              {(shipments?.total ?? 0) === 0 ? (
                <EmptyState title="No carrier activity yet" description="Shipments will appear here once consignments are booked." />
              ) : (
                <Table>
                  <THead>
                    <TR>
                      <TH>Carrier</TH>
                      <TH align="right">Shipments</TH>
                    </TR>
                  </THead>
                  <TBody>
                    <TR>
                      <TD className="font-medium text-ink-900 truncate max-w-xs">VIP Carriers</TD>
                      <TD align="right" className="tabular">
                        <Count value={shipments?.total} loading={loading} />
                      </TD>
                    </TR>
                  </TBody>
                </Table>
              )}
            </CardBody>
          </Card>

          <Card>
            <CardHeader title="Recent Activity" description="Latest premium shipment movements" />
            <CardBody className="p-0">
              <EmptyState title="No recent activity" description="Activity entries appear as premium shipments move through their lifecycle." />
            </CardBody>
          </Card>
        </div>
      </div>
    </RequirePermission>
  )
}

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
