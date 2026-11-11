import { useCallback } from 'react'
import { Link } from 'react-router-dom'
import { Banknote, Package, ShoppingCart, TrendingUp, Users } from 'lucide-react'
import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { businessService } from '../../services/businessService'
import { useBusiness } from '../../context/BusinessContext'
import { useAuth } from '../../context/AuthContext'
import { useApi } from '../../hooks/useApi'
import { apiErrorMessage } from '../../api/client'
import { formatMoney, formatDate } from '../../utils/format'
import { STATUS_TONES } from '../../utils/constants'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, StatCard } from '../../components/ui/Feedback'
import { Badge, StatusBadge } from '../../components/ui/Badge'
import { Card, CardBody, CardHeader } from '../../components/ui/Card'
import { EmptyState, Table, Td, Th, Tr } from '../../components/ui/Table'
import { UpgradePrompt } from '../../components/FeatureGate'

export default function Dashboard() {
  const { activeBusiness, allows } = useBusiness()
  const { user } = useAuth()

  const loader = useCallback(() => businessService.dashboard(), [])
  const { data, loading, error } = useApi(loader, [loader])

  const reportLoader = useCallback(() => businessService.salesReport(), [])
  const { data: report, error: reportError } = useApi(
    reportLoader,
    [reportLoader],
    { immediate: allows('advanced_reports') },
  )

  const currency = data?.currency ?? activeBusiness?.currency ?? 'USD'

  if (loading) {
    return <LoadingBlock label="Loading dashboard…" />
  }

  if (error) {
    return <Alert tone="danger" title="Unable to load the dashboard">{apiErrorMessage(error)}</Alert>
  }

  const chartData = (report?.daily ?? []).map((entry) => ({
    ...entry,
    label: String(entry.date).slice(5),
  }))

  return (
    <div>
      <PageHeader
        title={activeBusiness?.name ?? 'Dashboard'}
        description="Sales, activity and plan usage for the selected business."
        actions={
          <Link
            to="/orders"
            className="rounded-lg bg-brand-600 px-3.5 py-2 text-sm font-medium text-white transition hover:bg-brand-700"
          >
            New order
          </Link>
        }
      />

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          label="Sales today"
          value={formatMoney(data?.today_sales, currency)}
          icon={Banknote}
          tone="emerald"
        />
        <StatCard
          label="Sales this month"
          value={formatMoney(data?.month_sales, currency)}
          icon={TrendingUp}
          tone="brand"
        />
        <StatCard label="Orders this month" value={data?.order_count ?? 0} icon={ShoppingCart} tone="sky" />
        <StatCard
          label="Customers"
          value={data?.customer_count ?? 0}
          hint={`${data?.product_count ?? 0} products`}
          icon={Users}
          tone="amber"
        />
      </div>

      <div className="mt-6 grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader
            title="Sales performance"
            description="Daily sales for the current month."
            actions={
              <Link to="/reports" className="text-sm font-medium text-brand-700 hover:underline">
                Reports
              </Link>
            }
          />
          <CardBody>
            {reportError ? (
              <UpgradePrompt
                feature="advanced_reports"
                message="Advanced reports with daily charts and profit analysis require a Pro subscription."
              />
            ) : chartData.length === 0 ? (
              <EmptyState title="No sales yet" description="Completed orders will show up here." />
            ) : (
              <div className="h-64 w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <AreaChart data={chartData} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
                    <defs>
                      <linearGradient id="salesGradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#3355ff" stopOpacity={0.35} />
                        <stop offset="95%" stopColor="#3355ff" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                    <XAxis dataKey="label" tick={{ fontSize: 12, fill: '#64748b' }} axisLine={false} tickLine={false} />
                    <YAxis
                      tick={{ fontSize: 12, fill: '#64748b' }}
                      axisLine={false}
                      tickLine={false}
                      tickFormatter={(value) => formatMoney(value, currency, { decimals: 0 })}
                    />
                    <Tooltip
                      formatter={(value) => formatMoney(value, currency)}
                      labelStyle={{ color: '#0f172a', fontWeight: 600 }}
                      contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 12 }}
                    />
                    <Area type="monotone" dataKey="sales" stroke="#3355ff" fill="url(#salesGradient)" strokeWidth={2} />
                  </AreaChart>
                </ResponsiveContainer>
              </div>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Plan usage" description="Consumption against your plan limits." />
          <CardBody className="space-y-4">
            {(data?.usage ?? []).map((entry) => (
              <div key={entry.key}>
                <div className="flex items-baseline justify-between text-sm">
                  <span className="font-medium capitalize text-slate-700">
                    {entry.key.replace('max_', '').replace(/_/g, ' ')}
                  </span>
                  <span className="text-slate-500">
                    {entry.limit === null ? `${entry.used}` : `${entry.used} / ${entry.limit}`}
                  </span>
                </div>
                <div className="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                  <div
                    className={`h-full rounded-full ${entry.percentage >= 90 ? 'bg-red-500' : 'bg-brand-500'}`}
                    style={{ width: `${Math.max(3, entry.percentage)}%` }}
                  />
                </div>
              </div>
            ))}
            {(!data?.usage || data.usage.length === 0) && (
              <p className="text-sm text-slate-500">No limits apply to this business.</p>
            )}
            <Link to="/billing" className="inline-block text-sm font-medium text-brand-700 hover:underline">
              Manage plan
            </Link>
          </CardBody>
        </Card>
      </div>

      <Card className="mt-6">
        <CardHeader
          title="Recent orders"
          actions={
            <Link to="/orders" className="text-sm font-medium text-brand-700 hover:underline">
              View all
            </Link>
          }
        />
        {(data?.recent_orders ?? []).length === 0 ? (
          <EmptyState title="No orders yet" description="Create your first order to see it listed here." />
        ) : (
          <Table>
            <thead className="bg-slate-50">
              <tr>
                <Th>Order</Th>
                <Th>Customer</Th>
                <Th>Status</Th>
                <Th>Date</Th>
                <Th className="text-right">Total</Th>
              </tr>
            </thead>
            <tbody>
              {data.recent_orders.map((order) => (
                <Tr key={order.id}>
                  <Td className="font-medium text-slate-900">{order.order_number}</Td>
                  <Td>{order.customer || 'Walk-in customer'}</Td>
                  <Td>
                    <StatusBadge status={order.status} tone={STATUS_TONES[order.status]} />
                  </Td>
                  <Td>{formatDate(order.created_at)}</Td>
                  <Td className="text-right font-medium text-slate-900">
                    {formatMoney(order.total, order.currency ?? currency)}
                  </Td>
                </Tr>
              ))}
            </tbody>
          </Table>
        )}
      </Card>

      <div className="mt-6 flex flex-wrap items-center gap-3 text-xs text-slate-500">
        <Badge tone="brand">Plan: {activeBusiness?.plan ?? 'free'}</Badge>
        <Badge tone="neutral">Status: {activeBusiness?.subscription_status ?? 'active'}</Badge>
        <span>Signed in as {user?.email}</span>
      </div>
    </div>
  )
}
