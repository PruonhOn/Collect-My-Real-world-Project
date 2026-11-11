import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { get } from '@/lib/api'
import { formatNumber } from '@/lib/format'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { EmptyState } from '@/components/ui/States'
import { Button } from '@/components/ui/Button'
import { RequirePermission } from '@/components/guards/ProtectedRoute'

interface CustomerReportRow {
  customer: string
  code: string
  total: number
}

export function CustomerReportsPage() {
  const { data } = useQuery<CustomerReportRow[]>({
    queryKey: ['reports', 'customers'],
    queryFn: () => get<CustomerReportRow[]>('/reports/customers'),
  })

  return (
    <RequirePermission permission="report.view">
      <div className="space-y-4 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-lg font-semibold text-ink-900">Customer Reports</h1>
            <p className="text-xs text-ink-500">Shipment volume by shipper, top 20.</p>
          </div>
          <Link to="/reports/customers/export">
            <Button>Export CSV</Button>
          </Link>
        </div>

        <Card>
          <CardHeader title="Top Shippers by Shipment Count" />
          <CardBody className="p-0">
            {(data ?? []).length === 0 ? (
              <EmptyState title="No customer data" />
            ) : (
              <Table>
                <THead>
                  <TR>
                    <TH>Shipper</TH>
                    <TH>Code</TH>
                    <TH align="right">Shipments</TH>
                  </TR>
                </THead>
                <TBody>
                  {data!.map((row) => (
                    <TR key={row.code}>
                      <TD className="min-w-0">
                        <div className="truncate font-medium text-ink-900">{row.customer}</div>
                      </TD>
                      <TD className="tabular">{row.code}</TD>
                      <TD align="right" className="tabular">
                        {formatNumber(row.total)}
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
