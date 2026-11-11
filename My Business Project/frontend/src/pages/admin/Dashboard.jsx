import { useCallback } from 'react'
import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts'
import {
  AlertTriangle,
  Ban,
  Building2,
  CreditCard,
  TrendingDown,
  TrendingUp,
  UserPlus,
  Users,
} from 'lucide-react'
import { adminService } from '../../services/adminService'
import { useApi } from '../../hooks/useApi'
import { apiErrorMessage } from '../../api/client'
import { formatMoney, formatPercent } from '../../utils/format'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, StatCard } from '../../components/ui/Feedback'
import { Card, CardBody, CardHeader } from '../../components/ui/Card'

export default function AdminDashboard() {
  const loader = useCallback(() => adminService.dashboard(), [])
  const { data, loading, error } = useApi(loader, [loader])

  if (loading) {
    return <LoadingBlock label="Loading platform metrics…" />
  }

  if (error) {
    return (
      <div className="space-y-6">
        <PageHeader title="Platform overview" description="Users, workspaces, revenue and churn." />
        <Alert tone="danger">{apiErrorMessage(error)}</Alert>
      </div>
    )
  }

  const metrics = data ?? {}
  const trend = (metrics.revenue_trend ?? []).map((row) => ({
    month: row.month,
    revenue: Number(row.total ?? 0) / 100,
    payments: row.payments,
  }))

  return (
    <div>
      <PageHeader title="Platform overview" description="Users, workspaces, revenue and churn." />

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          label="Total users"
          value={metrics.total_users ?? 0}
          hint={`${metrics.new_registrations ?? 0} new in 30 days`}
          icon={Users}
          tone="brand"
        />
        <StatCard
          label="Businesses"
          value={metrics.total_businesses ?? 0}
          hint={`${metrics.suspended_businesses ?? 0} suspended`}
          icon={Building2}
          tone="sky"
        />
        <StatCard
          label="MRR"
          value={formatMoney(metrics.mrr ?? 0, 'USD')}
          hint={`${metrics.active_subscriptions ?? 0} active subscriptions`}
          icon={TrendingUp}
          tone="emerald"
        />
        <StatCard
          label="Churn rate"
          value={formatPercent(metrics.churn_rate ?? 0, 2)}
          hint={`${metrics.canceled_subscriptions ?? 0} canceled`}
          icon={metrics.churn_rate > 0 ? TrendingDown : TrendingUp}
          tone="amber"
        />
      </div>

      <div className="mt-6 grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader title="Revenue trend" description="Settled payments grouped by month." />
          <CardBody>
            {trend.length === 0 ? (
              <p className="py-12 text-center text-sm text-slate-500">No settled payments yet.</p>
            ) : (
              <div className="h-72 w-full">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={trend}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                    <XAxis dataKey="month" tick={{ fontSize: 12, fill: '#64748b' }} />
                    <YAxis tick={{ fontSize: 12, fill: '#64748b' }} />
                    <Tooltip formatter={(value) => formatMoney(value * 100, 'USD')} />
                    <Bar dataKey="revenue" fill="#4f46e5" radius={[6, 6, 0, 0]} />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Subscription health" description="Lifecycle of every workspace plan." />
          <CardBody className="space-y-3 text-sm">
            <div className="flex items-center justify-between">
              <span className="text-slate-500">Active / trialing</span>
              <span className="font-semibold text-slate-900">{metrics.active_subscriptions ?? 0}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-slate-500">Past due</span>
              <span className="font-semibold text-amber-600">{metrics.past_due_subscriptions ?? 0}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-slate-500">Canceled</span>
              <span className="font-semibold text-slate-900">{metrics.canceled_subscriptions ?? 0}</span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-slate-500">Expired</span>
              <span className="font-semibold text-slate-900">{metrics.expired_subscriptions ?? 0}</span>
            </div>

            <div className="mt-4 border-t border-slate-100 pt-4">
              <p className="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                <CreditCard className="size-4" />
                Payments
              </p>
              <div className="flex items-center justify-between">
                <span className="text-slate-500">Succeeded</span>
                <span className="font-semibold text-emerald-600">{metrics.payments?.succeeded ?? 0}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-500">Failed</span>
                <span className="font-semibold text-red-600">{metrics.payments?.failed ?? 0}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-slate-500">Success rate</span>
                <span className="font-semibold text-slate-900">
                  {formatPercent(metrics.payments?.success_rate ?? 0, 1)}
                </span>
              </div>
            </div>

            <div className="mt-4 flex items-start gap-2 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
              {metrics.suspended_businesses > 0 ? (
                <>
                  <AlertTriangle className="size-4 shrink-0 text-amber-500" />
                  <span>{metrics.suspended_businesses} workspace(s) are suspended.</span>
                </>
              ) : (
                <>
                  <UserPlus className="size-4 shrink-0 text-brand-500" />
                  <span>{metrics.new_registrations ?? 0} new registrations in the last 30 days.</span>
                </>
              )}
            </div>
          </CardBody>
        </Card>
      </div>

      {metrics.suspended_businesses > 0 && (
        <div className="mt-6">
          <Alert tone="warning" title="Suspended workspaces" action={<Ban className="size-4" />}>
            Suspended businesses cannot sign in until an administrator restores them.
          </Alert>
        </div>
      )}
    </div>
  )
}
