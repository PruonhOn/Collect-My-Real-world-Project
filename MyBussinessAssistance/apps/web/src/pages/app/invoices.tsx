import { useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Eye, FileText, Plus, Search } from "lucide-react";
import { EmptyState } from "@/components/app/empty-state";
import { PageHeader } from "@/components/app/page-header";
import { StatusBadge } from "@/components/app/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { fetchSales } from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { formatDate } from "@/lib/format";
import { formatCents, formatNumber } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

export function InvoicesPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";

  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("");

  const salesQuery = useQuery({
    queryKey: ["sales", workspaceId],
    queryFn: () => fetchSales(workspaceId),
  });

  const sales = salesQuery.data?.sales ?? [];

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return sales.filter((sale) => {
      if (statusFilter && sale.status !== statusFilter) return false;
      if (!term) return true;
      return [sale.number, sale.customerName ?? "", sale.branchName]
        .join(" ")
        .toLowerCase()
        .includes(term);
    });
  }, [sales, search, statusFilter]);

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("nav.invoices")}
        description={t("business.sales.subtitle")}
        actions={
          can("sale:create") ? (
            <Button asChild>
              <Link to="/app/sales">
                <Plus className="h-4 w-4" />
                {t("business.sales.newSale")}
              </Link>
            </Button>
          ) : undefined
        }
      />

      <div className="grid gap-4 sm:grid-cols-2">
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">{t("business.reports.revenue")}</p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(salesQuery.data?.summary.totalCents ?? 0, workspace.defaultCurrency, locale)}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">{t("business.invoice.balance")}</p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(salesQuery.data?.summary.outstandingCents ?? 0, workspace.defaultCurrency, locale)}
            </p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardContent className="space-y-4 p-4 sm:p-5">
          <div className="flex flex-col gap-2 sm:flex-row">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder={t("business.sales.searchPlaceholder")}
                className="pl-9"
              />
            </div>
            <Select
              value={statusFilter}
              onChange={(event) => setStatusFilter(event.target.value)}
              className="sm:w-44"
            >
              <option value="">{t("business.sales.allStatuses")}</option>
              <option value="completed">{t("business.sales.completed")}</option>
              <option value="void">{t("business.sales.voidStatus")}</option>
            </Select>
          </div>

          {salesQuery.isLoading ? (
            <div className="space-y-2">
              {[0, 1, 2, 3].map((index) => (
                <Skeleton key={index} className="h-11 w-full" />
              ))}
            </div>
          ) : salesQuery.isError ? (
            <p className="py-10 text-center text-sm text-destructive">
              {errorMessage(salesQuery.error)}
            </p>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon={FileText}
              title={t("business.sales.emptyTitle")}
              description={t("business.sales.emptyBody")}
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("business.invoice.number")}</TableHead>
                  <TableHead>{t("business.invoice.billTo")}</TableHead>
                  <TableHead>{t("common.date")}</TableHead>
                  <TableHead>{t("business.invoice.dueDate")}</TableHead>
                  <TableHead className="text-right">{t("common.total")}</TableHead>
                  <TableHead className="text-right">{t("business.invoice.balance")}</TableHead>
                  <TableHead>{t("common.status")}</TableHead>
                  <TableHead className="text-right">{t("common.actions")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((sale) => (
                  <TableRow key={sale.id}>
                    <TableCell className="font-mono text-xs font-medium">
                      {sale.number}
                    </TableCell>
                    <TableCell>{sale.customerName ?? t("business.sales.walkIn")}</TableCell>
                    <TableCell>{formatDate(sale.saleDate, locale)}</TableCell>
                    <TableCell>
                      {sale.dueDate ? formatDate(sale.dueDate, locale) : "—"}
                    </TableCell>
                    <TableCell className="text-right font-medium">
                      {formatCents(sale.totalCents, sale.currency, locale)}
                    </TableCell>
                    <TableCell className="text-right">
                      {formatCents(sale.balanceCents, sale.currency, locale)}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={sale.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      <Button asChild variant="outline" size="sm">
                        <Link to={`/app/invoices/${sale.id}`}>
                          <Eye className="h-4 w-4" />
                          {t("common.view")}
                        </Link>
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
