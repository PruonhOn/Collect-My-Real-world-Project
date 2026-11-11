import { useEffect, useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Plus, Search, ShoppingBag, Wallet, X } from "lucide-react";
import { PAYMENT_METHODS } from "@mba/shared";
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
  createPurchase,
  fetchBranches,
  fetchProducts,
  fetchPurchases,
  fetchSuppliers,
  updatePurchasePayment,
  type PurchaseSummary,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { fromDateInput, todayInput } from "@/lib/date-input";
import { formatDate } from "@/lib/format";
import { formatCents, formatNumber } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

type PurchaseLine = {
  key: string;
  productId: string;
  quantity: string;
  unitCost: string;
};

function newLine(): PurchaseLine {
  return {
    key: crypto.randomUUID(),
    productId: "",
    quantity: "1",
    unitCost: "0",
  };
}

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

export function PurchasesPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [open, setOpen] = useState(false);
  const [notice, setNotice] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [branchId, setBranchId] = useState("");
  const [supplierId, setSupplierId] = useState("");
  const [paymentMethod, setPaymentMethod] = useState("cash");
  const [purchaseDate, setPurchaseDate] = useState(todayInput());
  const [note, setNote] = useState("");
  const [paid, setPaid] = useState("0");
  const [paidTouched, setPaidTouched] = useState(false);
  const [lines, setLines] = useState<PurchaseLine[]>([newLine()]);
  const [paymentPurchase, setPaymentPurchase] = useState<PurchaseSummary | null>(null);
  const [paymentError, setPaymentError] = useState<string | null>(null);

  const purchasesQuery = useQuery({
    queryKey: ["purchases", workspaceId],
    queryFn: () => fetchPurchases(workspaceId),
  });
  const productsQuery = useQuery({
    queryKey: ["products", workspaceId],
    queryFn: () => fetchProducts(workspaceId),
  });
  const suppliersQuery = useQuery({
    queryKey: ["suppliers", workspaceId],
    queryFn: () => fetchSuppliers(workspaceId),
  });
  const branchesQuery = useQuery({
    queryKey: ["branches", workspaceId],
    queryFn: () => fetchBranches(workspaceId),
    retry: false,
  });

  const purchases = purchasesQuery.data?.purchases ?? [];
  const products = productsQuery.data?.products ?? [];
  const suppliers = suppliersQuery.data?.suppliers ?? [];
  const branches = branchesQuery.data?.branches ?? [];

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    if (!term) return purchases;
    return purchases.filter((purchase) =>
      [purchase.number, purchase.supplierName ?? "", purchase.branchName]
        .join(" ")
        .toLowerCase()
        .includes(term),
    );
  }, [purchases, search]);

  const totalCents = useMemo(
    () =>
      lines.reduce(
        (sum, line) =>
          sum +
          Math.round(
            Number(line.quantity || 0) * Number(line.unitCost || 0) * 100,
          ),
        0,
      ),
    [lines],
  );

  useEffect(() => {
    if (!paidTouched) setPaid((totalCents / 100).toFixed(2));
  }, [totalCents, paidTouched]);

  const createMutation = useMutation({
    mutationFn: () =>
      createPurchase(workspaceId, {
        branchId,
        supplierId: supplierId || null,
        paymentMethod,
        paidCents: Math.round(Number(paid || 0) * 100),
        purchaseDate: fromDateInput(purchaseDate),
        note: note.trim() || undefined,
        items: lines
          .filter((line) => line.productId && Number(line.quantity) > 0)
          .map((line) => ({
            productId: line.productId,
            quantity: Number(line.quantity),
            unitCostCents: Math.round(Number(line.unitCost || 0) * 100),
          })),
      }),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["purchases", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["products", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["inventory", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["suppliers", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["dashboard", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["reports", workspaceId] }),
      ]);
      setNotice(t("business.purchases.created"));
      setOpen(false);
    },
    onError: (error) => setFormError(errorMessage(error)),
  });

  const paymentMutation = useMutation({
    mutationFn: ({ purchaseId, paidCents }: { purchaseId: string; paidCents: number }) =>
      updatePurchasePayment(workspaceId, purchaseId, paidCents),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["purchases", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["suppliers", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["dashboard", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["reports", workspaceId] }),
      ]);
      setNotice(t("business.purchases.paymentSaved"));
      setPaymentPurchase(null);
    },
    onError: (error) => setPaymentError(errorMessage(error)),
  });

  function openCreate() {
    setFormError(null);
    setBranchId(branches.find((branch) => branch.isDefault)?.id ?? branches[0]?.id ?? "");
    setSupplierId("");
    setPaymentMethod("cash");
    setPurchaseDate(todayInput());
    setNote("");
    setPaid("0");
    setPaidTouched(false);
    setLines([newLine()]);
    setOpen(true);
  }

  function updateLine(key: string, patch: Partial<PurchaseLine>) {
    setLines((current) =>
      current.map((line) => (line.key === key ? { ...line, ...patch } : line)),
    );
  }

  function onProductChange(line: PurchaseLine, productId: string) {
    const product = products.find((item) => item.id === productId);
    updateLine(line.key, {
      productId,
      unitCost: product ? (product.costPriceCents / 100).toFixed(2) : "0",
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
      setFormError(t("business.purchases.noItems"));
      return;
    }
    createMutation.mutate();
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.purchases.title")}
        description={t("business.purchases.subtitle")}
        actions={
          can("purchase:create") ? (
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4" />
              {t("business.purchases.newPurchase")}
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
            <p className="text-xs text-muted-foreground">{t("business.reports.purchases")}</p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(purchasesQuery.data?.summary?.totalCents ?? 0, workspace.defaultCurrency, locale)}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">{t("common.balance")}</p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(purchasesQuery.data?.summary?.outstandingCents ?? 0, workspace.defaultCurrency, locale)}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">{t("business.purchases.title")}</p>
            <p className="mt-1 text-2xl font-bold">{formatNumber(purchasesQuery.data?.summary?.count ?? 0, locale)}</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardContent className="space-y-4 p-4 sm:p-5">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              placeholder={t("business.purchases.searchPlaceholder")}
              className="pl-9"
            />
          </div>

          {purchasesQuery.isLoading ? (
            <div className="space-y-2">
              {[0, 1, 2, 3].map((index) => (
                <Skeleton key={index} className="h-11 w-full" />
              ))}
            </div>
          ) : purchasesQuery.isError ? (
            <p className="py-10 text-center text-sm text-destructive">
              {errorMessage(purchasesQuery.error)}
            </p>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon={ShoppingBag}
              title={t("business.purchases.emptyTitle")}
              description={t("business.purchases.emptyBody")}
              action={
                can("purchase:create") ? (
                  <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" />
                    {t("business.purchases.newPurchase")}
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("business.purchases.purchaseNumber")}</TableHead>
                  <TableHead>{t("business.purchases.supplier")}</TableHead>
                  <TableHead>{t("business.inventory.branch")}</TableHead>
                  <TableHead>{t("common.date")}</TableHead>
                  <TableHead className="text-right">{t("common.total")}</TableHead>
                  <TableHead className="text-right">{t("common.balance")}</TableHead>
                  <TableHead>{t("common.status")}</TableHead>
                  <TableHead className="text-right">{t("common.actions")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((purchase) => (
                  <TableRow key={purchase.id}>
                    <TableCell>
                      <div className="font-mono text-xs font-medium">{purchase.number}</div>
                      <div className="text-xs text-muted-foreground">
                        {t("business.purchases.itemCount", { count: purchase.itemCount })}
                      </div>
                    </TableCell>
                    <TableCell>{purchase.supplierName ?? "—"}</TableCell>
                    <TableCell>{purchase.branchName}</TableCell>
                    <TableCell>{formatDate(purchase.purchaseDate, locale)}</TableCell>
                    <TableCell className="text-right font-medium">
                      {formatCents(purchase.totalCents, purchase.currency, locale)}
                    </TableCell>
                    <TableCell className="text-right">
                      {formatCents(purchase.balanceCents, purchase.currency, locale)}
                    </TableCell>
                    <TableCell>
                      <div className="flex flex-col gap-1">
                        <StatusBadge status={purchase.status} />
                        <span className="text-[10px] capitalize text-muted-foreground">
                          {purchase.paymentStatus}
                        </span>
                      </div>
                    </TableCell>
                    <TableCell className="text-right">
                      {purchase.balanceCents > 0 && can("purchase:update") ? (
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => {
                            setPaymentError(null);
                            setPaymentPurchase(purchase);
                          }}
                          aria-label={t("business.purchases.recordPayment")}
                        >
                          <Wallet className="h-4 w-4" />
                        </Button>
                      ) : null}
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
        title={t("business.purchases.newPurchase")}
        size="xl"
        footer={
          <>
            <div className="mr-auto text-sm">
              <span className="text-muted-foreground">{t("business.purchases.totalCost")}: </span>
              <span className="text-lg font-bold">
                {formatCents(totalCents, workspace.defaultCurrency, locale)}
              </span>
            </div>
            <Button type="button" variant="outline" onClick={() => setOpen(false)} disabled={createMutation.isPending}>
              {t("common.cancel")}
            </Button>
            <Button type="submit" form="purchase-form" disabled={createMutation.isPending}>
              {createMutation.isPending ? t("common.saving") : t("common.save")}
            </Button>
          </>
        }
      >
        <form id="purchase-form" className="space-y-5" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Field label={t("business.inventory.branch")} htmlFor="purchase-branch" required>
              <Select
                id="purchase-branch"
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
            <Field label={t("business.purchases.supplier")} htmlFor="purchase-supplier">
              <Select
                id="purchase-supplier"
                value={supplierId}
                onChange={(event) => setSupplierId(event.target.value)}
              >
                <option value="">{t("business.purchases.selectSupplier")}</option>
                {suppliers.map((supplier) => (
                  <option key={supplier.id} value={supplier.id}>
                    {supplier.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t("business.purchases.purchaseDate")} htmlFor="purchase-date">
              <Input
                id="purchase-date"
                type="date"
                value={purchaseDate}
                onChange={(event) => setPurchaseDate(event.target.value)}
              />
            </Field>
            <Field label={t("business.sales.paymentMethod")} htmlFor="purchase-method">
              <Select
                id="purchase-method"
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
                {t("business.purchases.addItem")}
              </Button>
            </div>
            <div className="space-y-2">
              {lines.map((line) => (
                <div
                  key={line.key}
                  className="grid gap-2 rounded-lg border p-3 sm:grid-cols-[1fr_6rem_8rem_auto] sm:items-end"
                >
                  <Field label={t("business.sales.item")}>
                    <Select
                      value={line.productId}
                      onChange={(event) => onProductChange(line, event.target.value)}
                    >
                      <option value="">{t("business.purchases.selectProduct")}</option>
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
                  <Field label={t("business.purchases.unitCost")}>
                    <Input
                      type="number"
                      min="0"
                      step="0.01"
                      value={line.unitCost}
                      onChange={(event) =>
                        updateLine(line.key, { unitCost: event.target.value })
                      }
                    />
                  </Field>
                  <div className="flex items-center justify-end pb-1 sm:pb-0">
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
              ))}
            </div>
            <p className="text-xs text-muted-foreground">{t("business.purchases.stockAddedNote")}</p>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <Field label={t("business.sales.paidAmount")} htmlFor="purchase-paid">
              <Input
                id="purchase-paid"
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

          <Field label={t("common.notes")} htmlFor="purchase-note">
            <Textarea
              id="purchase-note"
              value={note}
              onChange={(event) => setNote(event.target.value)}
            />
          </Field>
          {formError ? <p className="text-sm text-destructive">{formError}</p> : null}
        </form>
      </Dialog>

      <PaymentDialog
        open={Boolean(paymentPurchase)}
        onClose={() => setPaymentPurchase(null)}
        title={t("business.purchases.recordPayment")}
        totalCents={paymentPurchase?.totalCents ?? 0}
        paidCents={paymentPurchase?.paidCents ?? 0}
        currency={paymentPurchase?.currency ?? workspace.defaultCurrency}
        locale={locale}
        pending={paymentMutation.isPending}
        error={paymentError}
        onSubmit={(paidCents) => {
          if (paymentPurchase) {
            paymentMutation.mutate({ purchaseId: paymentPurchase.id, paidCents });
          }
        }}
      />
    </div>
  );
}
