import { useQuery } from '@tanstack/react-query'
import { FileText, Truck } from 'lucide-react'
import { Link } from 'react-router-dom'
import { get } from '@/lib/api'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { StatCard } from '@/components/ui/StatCard'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { Button } from '@/components/ui/Button'
import { RequirePermission } from '@/components/guards/ProtectedRoute'

interface OperationalReport {
  pending_customs: number
  pending_delivery: number
}

export function OperationalReportsPage() {
  const { data } = useQuery<OperationalReport>({
    queryKey: ['reports', 'operational'],
    queryFn: () => get<OperationalReport>('/reports/operational'),
  })

  const pendingCustoms = data?.pending_customs ?? 0
  const pendingDelivery = data?.pending_delivery ?? 0

  return (
    <RequirePermission permission="report.view">
      <div className="space-y-4 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-lg font-semibold text-ink-900">Operational Reports</h1>
            <p className="text-xs text-ink-500">Work that needs attention right now.</p>
          </div>
          <Link to="/reports/operational/export">
            <Button>Export CSV</Button>
          </Link>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <StatCard
            label="Pending Customs"
            value={pendingCustoms.toLocaleString()}
            icon={FileText}
            tone="warning"
          />
          <StatCard
            label="Pending Delivery"
            value={pendingDelivery.toLocaleString()}
            icon={Truck}
            tone="warning"
          />
        </div>

        <Card>
          <CardHeader title="Operations Queue" description="Consignments awaiting the next step" />
          <CardBody className="p-0">
            <Table>
              <THead>
                <TR>
                  <TH>Queue</TH>
                  <TH align="right">Count</TH>
                  <TH>Next step</TH>
                </TR>
              </THead>
              <TBody>
                <TR>
                  <TD>Customs clearance</TD>
                  <TD align="right" className="tabular">{pendingCustoms.toLocaleString()}</TD>
                  <TD>
                    <Link to="/operations/customs" className="text-brand-700 text-xs font-medium hover:underline">
                      View customs
                    </Link>
                  </TD>
                </TR>
                <TR>
                  <TD>Delivery booking</TD>
                  <TD align="right" className="tabular">{pendingDelivery.toLocaleString()}</TD>
                  <TD>
                    <Link to="/operations/deliveries" className="text-brand-700 text-xs font-medium hover:underline">
                      View deliveries
                    </Link>
                  </TD>
                </TR>
              </TBody>
            </Table>
          </CardBody>
        </Card>
      </div>
    </RequirePermission>
  )
}
