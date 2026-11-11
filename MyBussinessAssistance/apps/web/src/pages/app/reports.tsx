import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import {
  ArrowDownRight,
  ArrowUpRight,
  Banknote,
  CircleDollarSign,
  Download,
  Loader2,
  Printer,
  Receipt,
  ShoppingBag,
  TrendingUp,
  Wallet,
} from "lucide-react";
import {
  Area,
  AreaChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import { EmptyState } from "@/components/app/empty-state";
import { PageHeader } from "@/components/app/page-header";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { exportReportCsv, fetchReport } from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { fromDateInput, monthStartInput, todayInput } from "@/lib/date-input";
import { formatDate } from "@/lib/format";
import { formatCents, formatNumber } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

export function ReportsPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const canExport = can("report:export");

  const [fromInput, setFromInput] = useState(monthStartInput());
  const [toInput, setToInput] = useState(todayInput());
  const [exporting, setExporting] = useState(false);
  const [exportError, setExportError] = useState<string | null>(null);
  const [range, setRange] = useState({
    from: fromDateInput(monthStartInput()) ?? Date.now(),
    to: (fromDateInput(todayInput()) ?? Date.now()) + 86_399_999,
  });

  const reportQuery = useQuery({
    queryKey: ["reports", workspaceId, range.from, range.to],
    queryFn: () => fetchReport(workspaceId, range),
  });

  const data = reportQuery.data;

  const series = useMemo(
    () =>
      (data?.series ?? []).map((point) => ({
        ...point,
        sales: point.salesCents / 100,
        expenses: point.expenseCents / 100,
      })),
    [data],
  );

  const maxExpense = Math.max(
    1,
    ...(data?.expenseByCategory ?? []).map((entry) => entry.amountCents),
  );

  async function handleExport() {
    setExporting(true);
    setExportError(null);
    try {
      await exportReportCsv(workspaceId, range);
    } catch (cause) {
      setExportError(errorMessage(cause));
    } finally {
      setExporting(false);
    }
  }

  return (
    <div className="space-y-6">
      <div className="hidden print:block">
        <h2 className="text-xl font-bold">{t("business.reports.title")}</h2>
        <p className="text-sm text-muted-foreground">
          {formatDate(range.from, locale)} – {formatDate(range.to, locale)}
        </p>
      </div>

      <PageHeader
        title={t("business.reports.title")}
        description={t("business.reports.subtitle")}
        actions={
          canExport ? (
            <>
              <Button
                variant="outline"
                onClick={() => void handleExport()}
                disabled={exporting}
              >
                {exporting ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                  <Download className="h-4 w-4" />
                )}
                {exporting
                  ? t("business.reports.exporting")
                  : t("business.reports.exportCsv")}
              </Button>
              <Button variant="outline" onClick={() => window.print()}>
                <Printer className="h-4 w-4" />
                {t("business.reports.exportPdf")}
              </Button>
            </>
          ) : null
        }
      />

      {exportError ? (
        <Card>
          <CardContent className="p-4 text-sm text-destructive">
            {exportError}
          </CardContent>
        </Card>
      ) : null}

      <Card className="print:hidden">
        <CardContent className="flex flex-col gap-3 p-4 sm:flex-row sm:items-end sm:p-5">
          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground" htmlFor="report-from">
              {t("business.reports.from")}
            </label>
            <Input
              id="report-from"
              type="date"
              value={fromInput}
              onChange={(event) => setFromInput(event.target.value)}
              className="sm:w-44"
            />
          </div>
          <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground" htmlFor="report-to">
              {t("business.reports.to")}
            </label>
            <Input
              id="report-to"
              type="date"
              value={toInput}
              onChange={(event) => setToInput(event.target.value)}
              className="sm:w-44"
            />
          </div>
          <Button
            onClick={() =>
              setRange({
                from: fromDateInput(fromInput) ?? Date.now(),
                to: (fromDateInput(toInput) ?? Date.now()) + 86_399_999,
              })
            }
          >
            {t("business.reports.apply")}
          </Button>
        </CardContent>
      </Card>

      {reportQuery.isLoading ? (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {[0, 1, 2, 3, 4, 5, 6, 7].map((index) => (
            <Skeleton key={index} className="h-24 w-full" />
          ))}
        </div>
      ) : reportQuery.isError || !data ? (
        <Card>
          <CardContent className="py-12 text-center text-sm text-destructive">
            {errorMessage(reportQuery.error)}
          </CardContent>
        </Card>
      ) : (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {[
              {
                label: t("business.reports.revenue"),
                value: data.summary.revenueCents,
                icon: CircleDollarSign,
                tone: "text-primary bg-primary/10",
              },
              {
                label: t("business.reports.cogs"),
                value: data.summary.costCents,
                icon: ShoppingBag,
                tone: "text-muted-foreground bg-secondary",
              },
              {
                label: t("business.reports.grossProfit"),
                value: data.summary.grossProfitCents,
                icon: TrendingUp,
                tone: "text-success bg-success/10",
              },
              {
                label: t("business.reports.expenses"),
                value: data.summary.expenseCents,
                icon: Receipt,
                tone: "text-destructive bg-destructive/10",
              },
              {
                label: t("business.reports.netProfit"),
                value: data.summary.netProfitCents,
                icon: data.summary.netProfitCents >= 0 ? ArrowUpRight : ArrowDownRight,
                tone:
                  data.summary.netProfitCents >= 0
                    ? "text-success bg-success/10"
                    : "text-destructive bg-destructive/10",
              },
              {
                label: t("business.reports.purchases"),
                value: data.summary.purchaseCents,
                icon: Banknote,
                tone: "text-accent-foreground bg-accent/15",
              },
              {
                label: t("business.reports.averageSale"),
                value: data.summary.averageSaleCents,
                icon: CircleDollarSign,
                tone: "text-primary bg-primary/10",
              },
              {
                label: t("business.reports.outstanding"),
                value: data.summary.outstandingCents,
                icon: Wallet,
                tone: "text-accent-foreground bg-accent/15",
              },
            ].map((stat) => {
              const Icon = stat.icon;
              return (
                <Card key={stat.label}>
                  <CardContent className="flex items-start gap-3 p-5">
                    <span className={`flex h-10 w-10 items-center justify-center rounded-lg ${stat.tone}`}>
                      <Icon className="h-5 w-5" />
                    </span>
                    <div className="min-w-0">
                      <p className="text-xs text-muted-foreground">{stat.label}</p>
                      <p className="mt-1 truncate text-xl font-bold">
                        {formatCents(stat.value, workspace.defaultCurrency, locale)}
                      </p>
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>

          <div className="grid gap-4 lg:grid-cols-3">
            <Card className="lg:col-span-2">
              <CardHeader>
                <CardTitle>{t("business.reports.dailyFlow")}</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="h-64 w-full">
                  <ResponsiveContainer width="100%" height="100%">
                    <AreaChart data={series} margin={{ left: -12, right: 8 }}>
                      <defs>
                        <linearGradient id="reportRevenue" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="0%" stopColor="hsl(var(--primary))" stopOpacity={0.3} />
                          <stop offset="100%" stopColor="hsl(var(--primary))" stopOpacity={0} />
                        </linearGradient>
                        <linearGradient id="reportExpense" x1="0" y1="0" x2="0" y2="1">
                          <stop offset="0%" stopColor="hsl(var(--destructive))" stopOpacity={0.2} />
                          <stop offset="100%" stopColor="hsl(var(--destructive))" stopOpacity={0} />
                        </linearGradient>
                      </defs>
                      <CartesianGrid strokeDasharray="3 3" stroke="hsl(var(--border))" vertical={false} />
                      <XAxis
                        dataKey="date"
                        tickLine={false}
                        axisLine={false}
                        tick={{ fontSize: 10, fill: "hsl(var(--muted-foreground))" }}
                        tickFormatter={(value) => String(value).slice(5)}
                      />
                      <YAxis
                        tickLine={false}
                        axisLine={false}
                        tick={{ fontSize: 10, fill: "hsl(var(--muted-foreground))" }}
                      />
                      <Tooltip
                        contentStyle={{
                          background: "hsl(var(--card))",
                          border: "1px solid hsl(var(--border))",
                          borderRadius: 12,
                          fontSize: 12,
                        }}
                        formatter={(value: number) =>
                          formatCents(Math.round(value * 100), workspace.defaultCurrency, locale)
                        }
                      />
                      <Area
                        type="monotone"
                        dataKey="sales"
                        name={t("business.reports.revenueSeries")}
                        stroke="hsl(var(--primary))"
                        strokeWidth={2}
                        fill="url(#reportRevenue)"
                      />
                      <Area
                        type="monotone"
                        dataKey="expenses"
                        name={t("business.reports.expenseSeries")}
                        stroke="hsl(var(--destructive))"
                        strokeWidth={2}
                        fill="url(#reportExpense)"
                      />
                    </AreaChart>
                  </ResponsiveContainer>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>{t("business.reports.expenseBreakdown")}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {data.expenseByCategory.length === 0 ? (
                  <p className="py-8 text-center text-sm text-muted-foreground">
                    {t("business.reports.empty")}
                  </p>
                ) : (
                  data.expenseByCategory.map((entry) => (
                    <div key={entry.category}>
                      <div className="flex items-center justify-between text-xs">
                        <span className="font-medium">
                          {t(`business.expenses.categories.${entry.category}`)}
                        </span>
                        <span className="text-muted-foreground">
                          {formatCents(entry.amountCents, workspace.defaultCurrency, locale)}
                        </span>
                      </div>
                      <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-secondary">
                        <div
                          className="h-full rounded-full bg-destructive/70"
                          style={{ width: `${(entry.amountCents / maxExpense) * 100}%` }}
                        />
                      </div>
                    </div>
                  ))
                )}
              </CardContent>
            </Card>
          </div>

          <div className="grid gap-4 lg:grid-cols-3">
            <Card className="lg:col-span-2">
              <CardHeader>
                <CardTitle>{t("business.reports.topProducts")}</CardTitle>
              </CardHeader>
              <CardContent>
                {data.topProducts.length === 0 ? (
                  <EmptyState
                    icon={ShoppingBag}
                    title={t("business.reports.empty")}
                  />
                ) : (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>{t("business.reports.product")}</TableHead>
                        <TableHead className="text-right">{t("business.reports.quantity")}</TableHead>
                        <TableHead className="text-right">{t("business.reports.revenue")}</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {data.topProducts.map((product, index) => (
                        <TableRow key={`${product.productName}-${index}`}>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-secondary text-xs font-semibold">
                                {index + 1}
                              </span>
                              <span className="font-medium">{product.productName}</span>
                            </div>
                          </TableCell>
                          <TableCell className="text-right">
                            {formatNumber(product.quantity, locale)}
                          </TableCell>
                          <TableCell className="text-right font-medium">
                            {formatCents(product.revenueCents, workspace.defaultCurrency, locale)}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>{t("business.reports.paymentBreakdown")}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {data.paymentBreakdown.length === 0 ? (
                  <p className="py-8 text-center text-sm text-muted-foreground">
                    {t("business.reports.empty")}
                  </p>
                ) : (
                  data.paymentBreakdown.map((entry) => (
                    <div
                      key={entry.method}
                      className="flex items-center justify-between rounded-lg bg-secondary/60 px-3 py-2"
                    >
                      <span className="text-sm capitalize">
                        {t(`business.payments.${entry.method}`)}
                      </span>
                      <span className="text-sm font-semibold">
                        {formatCents(entry.totalCents, workspace.defaultCurrency, locale)}
                      </span>
                    </div>
                  ))
                )}
                <p className="pt-2 text-xs text-muted-foreground">
                  {t("business.reports.customers")}:{" "}
                  {formatNumber(data.summary.customerCount, locale)} ·{" "}
                  {t("business.reports.sales")}:{" "}
                  {formatNumber(data.summary.salesCount, locale)}
                </p>
                <p className="text-xs text-muted-foreground">
                  {formatDate(data.range.from, locale)} – {formatDate(data.range.to, locale)}
                </p>
              </CardContent>
            </Card>
          </div>
        </>
      )}
    </div>
  );
}
