import { Link, useParams } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { ArrowLeft, Printer } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { fetchCustomers, fetchSale } from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { formatDate } from "@/lib/format";
import { formatCents } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

export function InvoicePage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace } = useBusinessContext();
  const { saleId = "" } = useParams();
  const locale = i18n.language.startsWith("km") ? "km" : "en";

  const saleQuery = useQuery({
    queryKey: ["sale", workspaceId, saleId],
    queryFn: () => fetchSale(workspaceId, saleId),
    enabled: Boolean(saleId),
  });
  const customerId = saleQuery.data?.sale.customerId ?? null;
  const customersQuery = useQuery({
    queryKey: ["customers", workspaceId],
    queryFn: () => fetchCustomers(workspaceId),
    enabled: Boolean(customerId),
  });

  if (saleQuery.isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-9 w-48" />
        <Skeleton className="h-[32rem] w-full" />
      </div>
    );
  }

  if (saleQuery.isError || !saleQuery.data) {
    return (
      <div className="space-y-4">
        <Button asChild variant="outline" size="sm">
          <Link to="/app/sales">
            <ArrowLeft className="h-4 w-4" />
            {t("business.invoice.back")}
          </Link>
        </Button>
        <p className="text-sm text-destructive">
          {errorMessage(saleQuery.error)}
        </p>
      </div>
    );
  }

  const { sale, branch, items } = saleQuery.data;
  const customer = customersQuery.data?.customers.find(
    (item) => item.id === sale.customerId,
  );
  const isVoid = sale.status === "void";

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-2 print:hidden">
        <Button asChild variant="outline" size="sm">
          <Link to="/app/sales">
            <ArrowLeft className="h-4 w-4" />
            {t("business.invoice.back")}
          </Link>
        </Button>
        <Button size="sm" onClick={() => window.print()}>
          <Printer className="h-4 w-4" />
          {t("business.invoice.print")}
        </Button>
      </div>

      <article className="invoice-sheet mx-auto max-w-3xl rounded-xl border bg-white p-6 text-slate-900 shadow-sm print:max-w-none print:rounded-none print:border-0 print:p-0 print:shadow-none sm:p-10">
        <header className="flex flex-col gap-6 border-b pb-6 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div className="flex items-center gap-2">
              <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-900 text-lg font-bold text-white print:bg-slate-900">
                {workspace.name.charAt(0).toUpperCase()}
              </span>
              <div>
                <h1 className="text-lg font-bold">{workspace.name}</h1>
                <p className="text-xs text-slate-500">{branch.name}</p>
              </div>
            </div>
            <div className="mt-4 space-y-0.5 text-xs text-slate-500">
              {branch.address ? <p>{branch.address}</p> : null}
              {branch.phone ? <p>{branch.phone}</p> : null}
            </div>
          </div>
          <div className="text-left sm:text-right">
            <p className="text-xl font-bold uppercase tracking-widest text-slate-400">
              {t("business.invoice.invoice")}
            </p>
            <p className="mt-1 font-mono text-sm font-semibold">{sale.number}</p>
            <dl className="mt-3 space-y-1 text-xs">
              <div className="flex gap-2 sm:justify-end">
                <dt className="text-slate-500">{t("business.invoice.date")}:</dt>
                <dd className="font-medium">{formatDate(sale.saleDate, locale)}</dd>
              </div>
              {sale.dueDate ? (
                <div className="flex gap-2 sm:justify-end">
                  <dt className="text-slate-500">{t("business.invoice.dueDate")}:</dt>
                  <dd className="font-medium">{formatDate(sale.dueDate, locale)}</dd>
                </div>
              ) : null}
              <div className="flex gap-2 sm:justify-end">
                <dt className="text-slate-500">{t("business.invoice.status")}:</dt>
                <dd className="font-medium capitalize">{sale.status}</dd>
              </div>
            </dl>
          </div>
        </header>

        {isVoid ? (
          <div className="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-red-700">
            {t("business.invoice.voidedNote")}
          </div>
        ) : null}

        <section className="mt-6 grid gap-6 sm:grid-cols-2">
          <div>
            <p className="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
              {t("business.invoice.billTo")}
            </p>
            <p className="mt-1 text-sm font-semibold">
              {customer?.name ?? sale.customerName ?? t("business.invoice.walkIn")}
            </p>
            {customer?.phone ? (
              <p className="text-xs text-slate-500">{customer.phone}</p>
            ) : null}
            {customer?.email ? (
              <p className="text-xs text-slate-500">{customer.email}</p>
            ) : null}
            {customer?.address ? (
              <p className="text-xs text-slate-500">{customer.address}</p>
            ) : null}
          </div>
          <div className="sm:text-right">
            <p className="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
              {t("business.invoice.from")}
            </p>
            <p className="mt-1 text-sm font-semibold">{workspace.name}</p>
            <p className="text-xs text-slate-500">{branch.name}</p>
          </div>
        </section>

        <div className="mt-6 overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b-2 border-slate-900 text-left text-[10px] uppercase tracking-wider text-slate-500">
                <th className="py-2 pr-2 font-semibold">{t("business.invoice.item")}</th>
                <th className="px-2 py-2 text-right font-semibold">{t("business.invoice.qty")}</th>
                <th className="px-2 py-2 text-right font-semibold">{t("business.invoice.unitPrice")}</th>
                <th className="py-2 pl-2 text-right font-semibold">{t("business.invoice.amount")}</th>
              </tr>
            </thead>
            <tbody>
              {items.map((item) => (
                <tr key={item.id} className="border-b border-slate-100">
                  <td className="py-2.5 pr-2">
                    <p className="font-medium">{item.productName}</p>
                    {item.sku ? (
                      <p className="font-mono text-[10px] text-slate-400">{item.sku}</p>
                    ) : null}
                  </td>
                  <td className="px-2 py-2.5 text-right">{item.quantity}</td>
                  <td className="px-2 py-2.5 text-right">
                    {formatCents(item.unitPriceCents, sale.currency, locale)}
                  </td>
                  <td className="py-2.5 pl-2 text-right font-medium">
                    {formatCents(item.totalCents, sale.currency, locale)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="mt-4 flex justify-end">
          <dl className="w-full max-w-xs space-y-1.5 text-sm">
            <div className="flex justify-between">
              <dt className="text-slate-500">{t("business.invoice.subtotal")}</dt>
              <dd>{formatCents(sale.subtotalCents, sale.currency, locale)}</dd>
            </div>
            {sale.discountCents > 0 ? (
              <div className="flex justify-between">
                <dt className="text-slate-500">{t("business.invoice.discount")}</dt>
                <dd>-{formatCents(sale.discountCents, sale.currency, locale)}</dd>
              </div>
            ) : null}
            {sale.taxCents > 0 ? (
              <div className="flex justify-between">
                <dt className="text-slate-500">{t("business.invoice.tax")}</dt>
                <dd>{formatCents(sale.taxCents, sale.currency, locale)}</dd>
              </div>
            ) : null}
            <div className="flex justify-between border-t pt-1.5 text-base font-bold">
              <dt>{t("business.invoice.total")}</dt>
              <dd>{formatCents(sale.totalCents, sale.currency, locale)}</dd>
            </div>
            <div className="flex justify-between text-slate-500">
              <dt>{t("business.invoice.paid")}</dt>
              <dd>{formatCents(sale.paidCents, sale.currency, locale)}</dd>
            </div>
            <div className="flex justify-between font-semibold">
              <dt>{t("business.invoice.balance")}</dt>
              <dd>{formatCents(sale.balanceCents, sale.currency, locale)}</dd>
            </div>
          </dl>
        </div>

        <footer className="mt-8 border-t pt-4 text-center text-xs text-slate-500">
          <p>{t("business.invoice.thankYou")}</p>
          {sale.note ? <p className="mt-1">{sale.note}</p> : null}
          <p className="mt-3 text-[10px] text-slate-400">
            {t("business.invoice.number")}: {sale.number}
          </p>
        </footer>
      </article>
    </div>
  );
}
