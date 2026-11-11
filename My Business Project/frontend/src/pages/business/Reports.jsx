import { useCallback, useMemo, useState } from 'react'
import { Bar, BarChart, CartesianGrid, Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Download, FileSpreadsheet } from 'lucide-react'
import { toast } from 'sonner'
import { businessService } from '../../services/businessService'
import { useBusiness } from '../../context/BusinessContext'
import { useApi } from '../../hooks/useApi'
import { apiErrorMessage, downloadFile } from '../../api/client'
import { formatMoney, formatDate } from '../../utils/format'
import { PageHeader } from '../../components/PageHeader'
import { Alert, LoadingBlock, StatCard } from '../../components/ui/Feedback'
import { Button } from '../../components/ui/Button'
import { Card, CardBody, CardHeader } from '../../components/ui/Card'
import { Input } from '../../components/ui/Form'
import { Table, Td, Th, Tr } from '../../components/ui/Table'
import { FeatureGate, UpgradePrompt } from '../../components/FeatureGate'

const STATUS_COLORS = ['#3355ff', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#a855f7']

function startOfMonth() {
  const date = new Date()

  return new Date(date.getFullYear(), date.getMonth(), 1).toISOString().slice(0, 10)
}

function today() {
  return new Date().toISOString().slice(0, 10)
}

export default function Reports() {
  const { activeBusiness, allows } = useBusiness()
  const currency = activeBusiness?.currency ?? 'USD'

  const [from, setFrom] = useState(startOfMonth())
  const [to, setTo] = useState(today())

  const salesLoader = useCallback(() => businessService.salesReport({ from, to }), [from, to])
  const { data: sales, loading, error } = useApi(salesLoader, [salesLoader], {
    immediate: allows('advanced_reports'),
  })

  const profitLoader = useCallback(() => businessService.profitReport({ from, to }), [from, to])
  const { data: profit } = useApi(profitLoader, [profitLoader], { immediate: allows('profit_reports') })

  const exportLoader = useCallback(() => businessService.exportInfo(), [])
  const { data: exportInfo } = useApi(exportLoader, [exportLoader])

  const daily = useMemo(
    () => (sales?.daily ?? []).map((entry) => ({ ...entry, label: String(entry.date).slice(5) })),
    [sales],
  )

  const byStatus = useMemo(
    () => (sales?.by_status ?? []).map((entry) => ({ ...entry, label: entry.status })),
    [sales],
  )

  const downloadExport = async (resource, format) => {
    try {
      await downloadFile(`/business/exports/${resource}`, `${resource}-export.${format}`, { format })
      toast.success('Export downloaded.')
    } catch (exception) {
      toast.error(apiErrorMessage(exception, 'Unable to download the export.'))
    }
  }

  return (
    <div>
      <PageHeader
        title="Reports"
        description="Sales, profit and product performance for any date range."
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Input type="date" value={from} onChange={(event) => setFrom(event.target.value)} className="w-40" />
            <Input type="date" value={to} onChange={(event) => setTo(event.target.value)} className="w-40" />
          </div>
        }
      />

      {!allows('advanced_reports') && (
        <div className="mb-6">
          <UpgradePrompt feature="advanced_reports" />
        </div>
      )}

      {allows('advanced_reports') && (
        <>
          {error && (
            <div className="mb-4">
              <Alert tone="danger">{apiErrorMessage(error)}</Alert>
            </div>
          )}

          {loading && <LoadingBlock />}

          {!loading && sales && (
            <>
              <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Orders" value={sales.totals.orders} icon={FileSpreadsheet} />
                <StatCard label="Sales" value={formatMoney(sales.totals.sales, currency)} tone="emerald" />
                <StatCard label="Discounts" value={formatMoney(sales.totals.discount, currency)} tone="amber" />
                <StatCard label="Tax collected" value={formatMoney(sales.totals.tax, currency)} tone="sky" />
              </div>

              <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                  <CardHeader
                    title="Daily sales"
                    description={`${formatDate(sales.from)} → ${formatDate(sales.to)}`}
                  />
                  <CardBody>
                    {daily.length === 0 ? (
                      <p className="py-8 text-center text-sm text-slate-500">No sales in this period.</p>
                    ) : (
                      <div className="h-72 w-full">
                        <ResponsiveContainer width="100%" height="100%">
                          <BarChart data={daily} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
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
                              contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 12 }}
                            />
                            <Legend />
                            <Bar dataKey="sales" name="Sales" fill="#3355ff" radius={[6, 6, 0, 0]} />
                            <Bar dataKey="orders" name="Orders" fill="#38bdf8" radius={[6, 6, 0, 0]} />
                          </BarChart>
                        </ResponsiveContainer>
                      </div>
                    )}
                  </CardBody>
                </Card>

                <Card>
                  <CardHeader title="Orders by status" />
                  <CardBody>
                    {byStatus.length === 0 ? (
                      <p className="py-8 text-center text-sm text-slate-500">No orders in this period.</p>
                    ) : (
                      <div className="h-64 w-full">
                        <ResponsiveContainer width="100%" height="100%">
                          <PieChart>
                            <Pie
                              data={byStatus}
                              dataKey="orders"
                              nameKey="label"
                              innerRadius={50}
                              outerRadius={80}
                              paddingAngle={2}
                            >
                              {byStatus.map((entry, index) => (
                                <Cell key={entry.status} fill={STATUS_COLORS[index % STATUS_COLORS.length]} />
                              ))}
                            </Pie>
                            <Tooltip contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 12 }} />
                            <Legend />
                          </PieChart>
                        </ResponsiveContainer>
                      </div>
                    )}
                  </CardBody>
                </Card>
              </div>
            </>
          )}

          <div className="mt-6">
            <FeatureGate feature="profit_reports">
              <Card>
                <CardHeader
                  title="Profit and best sellers"
                  description={
                    profit?.profit_available
                      ? 'Profit uses the recorded cost price of each product.'
                      : 'Add cost prices to your products to unlock profit figures.'
                  }
                />
                <CardBody>
                  {profit && (
                    <>
                      <div className="grid gap-4 sm:grid-cols-3">
                        <StatCard label="Revenue" value={formatMoney(profit.revenue, currency)} />
                        <StatCard
                          label="Profit"
                          value={profit.profit_available ? formatMoney(profit.profit, currency) : 'Not available'}
                          tone="emerald"
                        />
                        <StatCard label="Products sold" value={profit.by_product?.length ?? 0} tone="sky" />
                      </div>

                      {profit.by_product?.length > 0 && (
                        <Table className="mt-5">
                          <thead className="bg-slate-50">
                            <tr>
                              <Th>Product</Th>
                              <Th>Quantity</Th>
                              <Th className="text-right">Revenue</Th>
                              <Th className="text-right">Profit</Th>
                            </tr>
                          </thead>
                          <tbody>
                            {profit.by_product.map((row) => (
                              <Tr key={row.name}>
                                <Td className="font-medium text-slate-900">{row.name}</Td>
                                <Td>{row.quantity}</Td>
                                <Td className="text-right">{formatMoney(row.revenue, currency)}</Td>
                                <Td className="text-right">
                                  {row.profit === null ? '—' : formatMoney(row.profit, currency)}
                                </Td>
                              </Tr>
                            ))}
                          </tbody>
                        </Table>
                      )}
                    </>
                  )}
                </CardBody>
              </Card>
            </FeatureGate>
          </div>
        </>
      )}

      <Card className="mt-6">
        <CardHeader
          title="Exports"
          description="Download your business data for reporting or backup."
          actions={
            exportInfo && !exportInfo.can_export_excel ? (
              <span className="text-xs text-slate-500">Excel exports require Pro</span>
            ) : null
          }
        />
        <CardBody className="flex flex-wrap gap-2">
          {(exportInfo?.resources ?? []).map((resource) => (
            <div key={resource} className="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2">
              <span className="text-sm font-medium capitalize text-slate-700">{resource}</span>
              <Button size="sm" variant="secondary" onClick={() => downloadExport(resource, 'csv')}>
                <Download className="size-3.5" />
                CSV
              </Button>
              {exportInfo?.can_export_excel && (
                <Button size="sm" variant="secondary" onClick={() => downloadExport(resource, 'xlsx')}>
                  <Download className="size-3.5" />
                  Excel
                </Button>
              )}
            </div>
          ))}
          {(exportInfo?.resources ?? []).length === 0 && (
            <p className="text-sm text-slate-500">Loading export options…</p>
          )}
        </CardBody>
      </Card>
    </div>
  )
}
