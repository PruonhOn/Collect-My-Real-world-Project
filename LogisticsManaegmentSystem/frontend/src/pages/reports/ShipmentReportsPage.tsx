import { useQuery } from '@tanstack/react-query'
import { BarChart, Bar, CartesianGrid, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts'
import { Package } from 'lucide-react'
import { Link } from 'react-router-dom'
import { get } from '@/lib/api'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { StatCard } from '@/components/ui/StatCard'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/States'
import { RequirePermission } from '@/components/guards/ProtectedRoute'

interface ShipmentStatusRow {
  status: string
  total: number
}

export function ShipmentReportsPage() {
  const { data } = useQuery<ShipmentStatusRow[]>({
    queryKey: ['reports', 'shipments'],
    queryFn: () => get<ShipmentStatusRow[]>('/reports/shipments'),
  })

  return (
    <RequirePermission permission="report.view">
      <div className="space-y-4 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-lg font-semibold text-ink-900">Shipment Reports</h1>
            <p className="text-xs text-ink-500">Consignment counts grouped by lifecycle status.</p>
          </div>
          <Link to="/reports/shipments/export">
            <Button>Export CSV</Button>
          </Link>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {(data ?? []).map((row) => (
            <StatCard
              key={row.status}
              label={row.status.replace(/_/g, ' ')}
              value={row.total.toLocaleString()}
              icon={Package}
              tone="brand"
            />
          ))}
        </div>

        <Card>
          <CardHeader title="Status Distribution" description="Shipments by current status" />
          <CardBody className="h-80">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={data ?? []}>
                <CartesianGrid strokeDasharray="3 3" stroke="var(--color-ink-200)" vertical={false} />
                <XAxis dataKey="status" tick={{ fontSize: 11 }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 11 }} axisLine={false} tickLine={false} allowDecimals={false} />
                <Tooltip />
                <Bar dataKey="total" name="Shipments" fill="var(--color-brand-500)" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </CardBody>
        </Card>

        <Card>
          <CardHeader title="Shipments by Status" />
          <CardBody className="p-0">
            {(data ?? []).length === 0 ? (
              <EmptyState title="No shipment data" />
            ) : (
              <Table>
                <THead>
                  <TR>
                    <TH>Status</TH>
                    <TH align="right">Total</TH>
                  </TR>
                </THead>
                <TBody>
                  {data!.map((row) => (
                    <TR key={row.status}>
                      <TD>
                        <Badge tone="info">{row.status.replace(/_/g, ' ')}</Badge>
                      </TD>
                      <TD align="right" className="tabular">
                        {row.total.toLocaleString()}
                      </TD>
                    </TR>
                  ))}
                </TBody>
              </Table>
            )}
          </CardBody>
        </Card>
      </div>
    </RequirePermission>
  )
}
