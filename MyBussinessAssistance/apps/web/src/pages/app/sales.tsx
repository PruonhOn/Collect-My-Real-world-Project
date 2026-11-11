import { useEffect, useMemo, useState, type FormEvent } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import {
  Eye,
  Plus,
  Search,
  ShoppingCart,
  Trash2,
  Wallet,
  X,
} from "lucide-react";
import { PAYMENT_METHODS } from "@mba/shared";
import { ConfirmDialog } from "@/components/app/confirm-dialog";
import { EmptyState } from "@/components/app/empty-state";
import { PageHeader } from "@/components/app/page-header";
import { PaymentDialog } from "@/components/app/payment-dialog";
import { StatusBadge } from "@/components/app/status-badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Dialog } from "@/components/ui/dialog";
import { Field } from "@/components/ui/field";
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
import { Textarea } from "@/components/ui/textarea";
import {
  createSale,
  fetchBranches,
  fetchCustomers,
  fetchProducts,
  fetchSales,
  updateSalePayment,
  voidSale,
  type SaleSummary,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { fromDateInput, todayInput } from "@/lib/date-input";
import { formatDate } from "@/lib/format";
import { formatCents, formatNumber } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

type SaleLine = {
  key: string;
  productId: string;
  quantity: string;
  unitPrice: string;
  discount: string;
};

function newLine(): SaleLine {
  return {
    key: crypto.randomUUID(),
    productId: "",
    quantity: "1",
    unitPrice: "0",
    discount: "0",
  };
}

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

export function SalesPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const navigate = useNavigate();
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [open, setOpen] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [branchId, setBranchId] = useState("");
  const [customerId, setCustomerId] = useState("");
  const [paymentMethod, setPaymentMethod] = useState("cash");
  const [saleDate, setSaleDate] = useState(todayInput());
  const [dueDate, setDueDate] = useState("");
  const [note, setNote] = useState("");
  const [paid, setPaid] = useState("0");
  const [paidTouched, setPaidTouched] = useState(false);
  const [lines, setLines] = useState<SaleLine[]>([newLine()]);
  const [paymentSale, setPaymentSale] = useState<SaleSummary | null>(null);
  const [paymentError, setPaymentError] = useState<string | null>(null);
  const [saleToVoid, setSaleToVoid] = useState<SaleSummary | null>(null);

  const salesQuery = useQuery({
    queryKey: ["sales", workspaceId],
    queryFn: () => fetchSales(workspaceId),
  });
  const productsQuery = useQuery({
    queryKey: ["products", workspaceId],
    queryFn: () => fetchProducts(workspaceId, { status: "active" }),
  });
  const customersQuery = useQuery({
    queryKey: ["customers", workspaceId],
    queryFn: () => fetchCustomers(workspaceId),
  });
  const branchesQuery = useQuery({
    queryKey: ["branches", workspaceId],
    queryFn: () => fetchBranches(workspaceId),
    retry: false,
  });

  const sales = salesQuery.data?.sales ?? [];
  const products = productsQuery.data?.products ?? [];
  const customers = customersQuery.data?.customers ?? [];
  const branches = branchesQuery.data?.branches ?? [];

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

  const subtotalCents = useMemo(
    () =>
      lines.reduce((sum, line) => {
        const quantity = Number(line.quantity || 0);
        const price = Number(line.unitPrice || 0);
        const discount = Number(line.discount || 0);
        return sum + Math.max(0, Math.round(quantity * price * 100) - Math.round(discount * 100));
      }, 0),
    [lines],
  );

  useEffect(() => {
    if (!paidTouched) setPaid((subtotalCents / 100).toFixed(2));
  }, [subtotalCents, paidTouched]);

  const createMutation = useMutation({
    mutationFn: () =>
      createSale(workspaceId, {
        branchId,
        customerId: customerId || null,
        paymentMethod,
        paidCents: Math.round(Number(paid || 0) * 100),
        discountCents: 0,
        saleDate: fromDateInput(saleDate),
        dueDate: dueDate ? fromDateInput(dueDate) ?? null : null,
        note: note.trim() || undefined,
        items: lines
          .filter((line) => line.productId && Number(line.quantity) > 0)
          .map((line) => ({
            productId: line.productId,
            quantity: Number(line.quantity),
            unitPriceCents: Math.round(Number(line.unitPrice || 0) * 100),
            discountCents: Math.round(Number(line.discount || 0) * 100),
          })),
      }),
    onSuccess: async (data) => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["sales", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["products", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["inventory", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["dashboard", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["reports", workspaceId] }),
      ]);
      setNotice(t("business.sales.saleCompleted"));
      setOpen(false);
      navigate(`/app/invoices/${data.sale.id}`);
    },
    onError: (error) => setFormError(errorMessage(error)),
  });

  const paymentMutation = useMutation({
    mutationFn: ({ saleId, paidCents }: { saleId: string; paidCents: number }) =>
      updateSalePayment(workspaceId, saleId, paidCents),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["sales", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["dashboard", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["reports", workspaceId] }),
      ]);
      setNotice(t("business.sales.paymentSaved"));
      setPaymentSale(null);
    },
    onError: (error) => setPaymentError(errorMessage(error)),
  });

  const voidMutation = useMutation({
    mutationFn: (saleId: string) => voidSale(workspaceId, saleId),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["sales", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["products", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["inventory", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["dashboard", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["reports", workspaceId] }),
      ]);
      setNotice(t("business.sales.voided"));
      setSaleToVoid(null);
    },
  });

  function openCreate() {
    setFormError(null);
    setBranchId(branches.find((branch) => branch.isDefault)?.id ?? branches[0]?.id ?? "");
    setCustomerId("");
    setPaymentMethod("cash");
    setSaleDate(todayInput());
    setDueDate("");
    setNote("");
    setPaid("0");
    setPaidTouched(false);
    setLines([newLine()]);
    setOpen(true);
  }

  function updateLine(key: string, patch: Partial<SaleLine>) {
    setLines((current) =>
      current.map((line) => (line.key === key ? { ...line, ...patch } : line)),
    );
  }

  function onProductChange(line: SaleLine, productId: string) {
    const product = products.find((item) => item.id === productId);
    updateLine(line.key, {
      productId,
      unitPrice: product ? (product.sellingPriceCents / 100).toFixed(2) : "0",
    });
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    setFormError(null);
    if (!branchId) {
      setFormError(t("business.sales.selectBranch"));
      return;
    }
    if (!lines.some((line) => line.productId && Number(line.quantity) > 0)) {
      setFormError(t("business.sales.noItems"));
      return;
    }
    createMutation.mutate();
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.sales.title")}
        description={t("business.sales.subtitle")}
        actions={
          can("sale:create") ? (
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4" />
              {t("business.sales.newSale")}
            </Button>
          ) : undefined
        }
      />

      {notice ? (
        <div className="rounded-lg border border-success/30 bg-success/10 px-4 py-2.5 text-sm text-success">
          {notice}
        </div>
      ) : null}

      <div className="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">{t("business.reports.revenue")}</p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(salesQuery.data?.summary?.totalCents ?? 0, workspace.defaultCurrency, locale)}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">{t("business.sales.balance")}</p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(salesQuery.data?.summary?.outstandingCents ?? 0, workspace.defaultCurrency, locale)}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">{t("business.sales.title")}</p>
            <p className="mt-1 text-2xl font-bold">{formatNumber(salesQuery.data?.summary?.count ?? 0, locale)}</p>
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
              icon={ShoppingCart}
              title={t("business.sales.emptyTitle")}
              description={t("business.sales.emptyBody")}
              action={
                can("sale:create") ? (
                  <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" />
                    {t("business.sales.newSale")}
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("business.sales.invoice")}</TableHead>
                  <TableHead>{t("business.sales.customer")}</TableHead>
                  <TableHead>{t("business.inventory.branch")}</TableHead>
                  <TableHead>{t("common.date")}</TableHead>
                  <TableHead className="text-right">{t("common.total")}</TableHead>
                  <TableHead className="text-right">{t("common.balance")}</TableHead>
                  <TableHead>{t("common.status")}</TableHead>
                  <TableHead className="text-right">{t("common.actions")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((sale) => (
                  <TableRow key={sale.id}>
                    <TableCell>
                      <div className="font-mono text-xs font-medium">{sale.number}</div>
                      <div className="text-xs text-muted-foreground">
                        {t("business.sales.itemCount", { count: sale.itemCount })}
                      </div>
                    </TableCell>
                    <TableCell>{sale.customerName ?? t("business.sales.walkIn")}</TableCell>
                    <TableCell>{sale.branchName}</TableCell>
                    <TableCell>{formatDate(sale.saleDate, locale)}</TableCell>
                    <TableCell className="text-right font-medium">
                      {formatCents(sale.totalCents, sale.currency, locale)}
                    </TableCell>
                    <TableCell className="text-right">
                      {formatCents(sale.balanceCents, sale.currency, locale)}
                    </TableCell>
                    <TableCell>
                      <div className="flex flex-col gap-1">
                        <StatusBadge status={sale.status} />
                        <span className="text-[10px] capitalize text-muted-foreground">
                          {sale.paymentStatus}
                        </span>
                      </div>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-1">
                        <Button asChild variant="ghost" size="icon">
                          <Link to={`/app/invoices/${sale.id}`} aria-label={t("business.sales.viewInvoice")}>
                            <Eye className="h-4 w-4" />
                          </Link>
                        </Button>
                        {sale.balanceCents > 0 && sale.status !== "void" && can("sale:update") ? (
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => {
                              setPaymentError(null);
                              setPaymentSale(sale);
                            }}
                            aria-label={t("business.sales.recordPayment")}
                          >
                            <Wallet className="h-4 w-4" />
                          </Button>
                        ) : null}
                        {sale.status !== "void" && can("sale:delete") ? (
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => setSaleToVoid(sale)}
                            aria-label={t("business.sales.voidSale")}
                          >
                            <Trash2 className="h-4 w-4 text-destructive" />
                          </Button>
                        ) : null}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <Dialog
        open={open}
        onClose={() => setOpen(false)}
        title={t("business.sales.newSale")}
        size="xl"
        footer={
          <>
            <div className="mr-auto text-sm">
              <span className="text-muted-foreground">{t("common.total")}: </span>
              <span className="text-lg font-bold">
                {formatCents(subtotalCents, workspace.defaultCurrency, locale)}
              </span>
            </div>
            <Button type="button" variant="outline" onClick={() => setOpen(false)} disabled={createMutation.isPending}>
              {t("common.cancel")}
            </Button>
            <Button type="submit" form="sale-form" disabled={createMutation.isPending}>
              {createMutation.isPending ? t("common.saving") : t("business.sales.completeSale")}
            </Button>
          </>
        }
      >
        <form id="sale-form" className="space-y-5" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-3">
            <Field label={t("business.inventory.branch")} htmlFor="sale-branch" required>
              <Select
                id="sale-branch"
                value={branchId}
                onChange={(event) => setBranchId(event.target.value)}
                required
              >
                <option value="">{t("business.sales.selectBranch")}</option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t("business.sales.customer")} htmlFor="sale-customer">
              <Select
                id="sale-customer"
                value={customerId}
                onChange={(event) => setCustomerId(event.target.value)}
              >
                <option value="">{t("business.sales.walkIn")}</option>
                {customers.map((customer) => (
                  <option key={customer.id} value={customer.id}>
                    {customer.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t("business.sales.paymentMethod")} htmlFor="sale-method">
              <Select
                id="sale-method"
                value={paymentMethod}
                onChange={(event) => setPaymentMethod(event.target.value)}
              >
                {PAYMENT_METHODS.map((method) => (
                  <option key={method} value={method}>
                    {t(`business.payments.${method}`)}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t("business.sales.saleDate")} htmlFor="sale-date">
              <Input
                id="sale-date"
                type="date"
                value={saleDate}
                onChange={(event) => setSaleDate(event.target.value)}
              />
            </Field>
            <Field label={t("business.sales.dueDate")} htmlFor="sale-due">
              <Input
                id="sale-due"
                type="date"
                value={dueDate}
                onChange={(event) => setDueDate(event.target.value)}
              />
            </Field>
            <Field label={t("business.sales.paidAmount")} htmlFor="sale-paid">
              <Input
                id="sale-paid"
                type="number"
                min="0"
                step="0.01"
                value={paid}
                onChange={(event) => {
                  setPaidTouched(true);
                  setPaid(event.target.value);
                }}
              />
            </Field>
          </div>

          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <p className="text-sm font-semibold">{t("business.sales.items")}</p>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setLines((current) => [...current, newLine()])}
              >
                <Plus className="h-4 w-4" />
                {t("business.sales.addItem")}
              </Button>
            </div>
            <div className="space-y-2">
              {lines.map((line) => {
                const lineTotal = Math.max(
                  0,
                  Math.round(Number(line.quantity || 0) * Number(line.unitPrice || 0) * 100) -
                    Math.round(Number(line.discount || 0) * 100),
                );
                return (
                  <div
                    key={line.key}
                    className="grid gap-2 rounded-lg border p-3 sm:grid-cols-[1fr_5rem_7rem_7rem_auto] sm:items-end"
                  >
                    <Field label={t("business.sales.item")}>
                      <Select
                        value={line.productId}
                        onChange={(event) => onProductChange(line, event.target.value)}
                      >
                        <option value="">{t("business.sales.selectProduct")}</option>
                        {products.map((product) => (
                          <option key={product.id} value={product.id}>
                            {product.name} ({product.sku})
                          </option>
                        ))}
                      </Select>
                    </Field>
                    <Field label={t("business.sales.quantity")}>
                      <Input
                        type="number"
                        min="1"
                        step="1"
                        value={line.quantity}
                        onChange={(event) =>
                          updateLine(line.key, { quantity: event.target.value })
                        }
                      />
                    </Field>
                    <Field label={t("business.sales.unitPrice")}>
                      <Input
                        type="number"
                        min="0"
                        step="0.01"
                        value={line.unitPrice}
                        onChange={(event) =>
                          updateLine(line.key, { unitPrice: event.target.value })
                        }
                      />
                    </Field>
                    <Field label={t("business.sales.discountPerLine")}>
                      <Input
                        type="number"
                        min="0"
                        step="0.01"
                        value={line.discount}
                        onChange={(event) =>
                          updateLine(line.key, { discount: event.target.value })
                        }
                      />
                    </Field>
                    <div className="flex items-center justify-end gap-1 pb-1 sm:pb-0">
                      <span className="text-sm font-medium">
                        {formatCents(lineTotal, workspace.defaultCurrency, locale)}
                      </span>
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                          setLines((current) =>
                            current.length === 1
                              ? [newLine()]
                              : current.filter((item) => item.key !== line.key),
                          )
                        }
                        aria-label={t("business.sales.remove")}
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                );
              })}
            </div>
            <p className="text-xs text-muted-foreground">{t("business.sales.totalHint")}</p>
          </div>

          <Field label={t("business.sales.notes")} htmlFor="sale-note">
            <Textarea
              id="sale-note"
              value={note}
              onChange={(event) => setNote(event.target.value)}
            />
          </Field>
          {formError ? <p className="text-sm text-destructive">{formError}</p> : null}
        </form>
      </Dialog>

      <PaymentDialog
        open={Boolean(paymentSale)}
        onClose={() => setPaymentSale(null)}
        totalCents={paymentSale?.totalCents ?? 0}
        paidCents={paymentSale?.paidCents ?? 0}
        currency={paymentSale?.currency ?? workspace.defaultCurrency}
        locale={locale}
        pending={paymentMutation.isPending}
        error={paymentError}
        onSubmit={(paidCents) => {
          if (paymentSale) paymentMutation.mutate({ saleId: paymentSale.id, paidCents });
        }}
      />

      <ConfirmDialog
        open={Boolean(saleToVoid)}
        onClose={() => setSaleToVoid(null)}
        onConfirm={() => saleToVoid && voidMutation.mutate(saleToVoid.id)}
        title={t("business.sales.voidTitle")}
        description={t("business.sales.voidBody")}
        confirmLabel={t("business.sales.voidConfirm")}
        cancelLabel={t("common.cancel")}
        pending={voidMutation.isPending}
      />
    </div>
  );
}
