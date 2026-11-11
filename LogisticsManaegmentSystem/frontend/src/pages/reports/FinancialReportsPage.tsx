import { useQuery } from '@tanstack/react-query'
import { BadgeDollarSign, TrendingDown, TrendingUp } from 'lucide-react'
import { Link } from 'react-router-dom'
import { get } from '@/lib/api'
import { formatMoney } from '@/lib/format'
import { Card, CardBody, CardHeader } from '@/components/ui/Card'
import { StatCard } from '@/components/ui/StatCard'
import { Table, TBody, TD, TH, THead, TR } from '@/components/ui/Table'
import { Button } from '@/components/ui/Button'
import { RequirePermission } from '@/components/guards/ProtectedRoute'

interface FinancialReport {
  outstanding_amount: number | string
  revenue: number | string
  paid: number | string
  expenses: number | string
  profit: number | string
}

export function FinancialReportsPage() {
  const { data } = useQuery<FinancialReport>({
    queryKey: ['reports', 'financial'],
    queryFn: () => get<FinancialReport>('/reports/financial'),
  })

  return (
    <RequirePermission permission="report.view">
      <div className="space-y-4 p-4 sm:p-6">
        <div className="flex items-start justify-between gap-4">
          <div>
            <h1 className="text-lg font-semibold text-ink-900">Financial Reports</h1>
            <p className="text-xs text-ink-500">Revenue, expenses and outstanding receivables.</p>
          </div>
          <Link to="/reports/financial/export">
            <Button>Export CSV</Button>
          </Link>
        </div>

        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <StatCard
            label="Outstanding Invoices"
            value={formatMoney(String(data?.outstanding_amount ?? 0))}
            icon={BadgeDollarSign}
            tone="warning"
          />
          <StatCard
            label="Revenue"
            value={formatMoney(String(data?.revenue ?? 0))}
            icon={TrendingUp}
            tone="success"
          />
          <StatCard
            label="Expenses"
            value={formatMoney(String(data?.expenses ?? 0))}
            icon={TrendingDown}
            tone="danger"
          />
          <StatCard
            label="Profit"
            value={formatMoney(String(data?.profit ?? 0))}
            icon={BadgeDollarSign}
            tone="brand"
          />
        </div>

        <Card>
          <CardHeader title="Financial Summary" description="Revenue, expenses and amounts still owing" />
          <CardBody className="p-0">
            <Table>
              <THead>
                <TR>
                  <TH>Measure</TH>
                  <TH align="right">Amount</TH>
                </TR>
              </THead>
              <TBody>
                <TR>
                  <TD>Revenue (paid invoices)</TD>
                  <TD align="right" className="tabular">
                    {formatMoney(String(data?.revenue ?? 0))}
                  </TD>
                </TR>
                <TR>
                  <TD>Expenses (margin-bearing)</TD>
                  <TD align="right" className="tabular">
                    {formatMoney(String(data?.expenses ?? 0))}
                  </TD>
                </TR>
                <TR>
                  <TD>Profit</TD>
                  <TD align="right" className="tabular font-medium">
                    {formatMoney(String(data?.profit ?? 0))}
                  </TD>
                </TR>
                <TR>
                  <TD>Outstanding invoices</TD>
                  <TD align="right" className="tabular">
                    {formatMoney(String(data?.outstanding_amount ?? 0))}
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
