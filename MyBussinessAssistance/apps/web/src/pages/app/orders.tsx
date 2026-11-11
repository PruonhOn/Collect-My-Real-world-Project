import { useEffect, useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import {
  Ban,
  CheckCircle2,
  ClipboardList,
  PackageCheck,
  Plus,
  Search,
  X,
} from "lucide-react";
import { PAYMENT_METHODS } from "@mba/shared";
import { ConfirmDialog } from "@/components/app/confirm-dialog";
import { EmptyState } from "@/components/app/empty-state";
import { PageHeader } from "@/components/app/page-header";
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
  createOrder,
  fetchBranches,
  fetchCustomers,
  fetchOrders,
  fetchProducts,
  fulfillOrder,
  updateOrderStatus,
  type OrderSummary,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { fromDateInput, todayInput } from "@/lib/date-input";
import { formatDate } from "@/lib/format";
import { formatCents, formatNumber } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

type OrderLine = {
  key: string;
  productId: string;
  quantity: string;
  unitPrice: string;
  discount: string;
};

function newLine(): OrderLine {
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

type PendingAction = {
  order: OrderSummary;
  type: "cancel" | "fulfill";
};

export function OrdersPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [open, setOpen] = useState(false);
  const [notice, setNotice] = useState<{
    message: string;
    tone: "success" | "error";
  } | null>(null);
  const showNotice = (message: string, tone: "success" | "error" = "success") =>
    setNotice({ message, tone });
  const [formError, setFormError] = useState<string | null>(null);
  const [branchId, setBranchId] = useState("");
  const [customerId, setCustomerId] = useState("");
  const [paymentMethod, setPaymentMethod] = useState("cash");
  const [orderDate, setOrderDate] = useState(todayInput());
  const [expectedDate, setExpectedDate] = useState("");
  const [note, setNote] = useState("");
  const [discount, setDiscount] = useState("0");
  const [paid, setPaid] = useState("0");
  const [paidTouched, setPaidTouched] = useState(false);
  const [lines, setLines] = useState<OrderLine[]>([newLine()]);
  const [pendingAction, setPendingAction] = useState<PendingAction | null>(null);

  const ordersQuery = useQuery({
    queryKey: ["orders", workspaceId],
    queryFn: () => fetchOrders(workspaceId),
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

  const orders = ordersQuery.data?.orders ?? [];
  const products = productsQuery.data?.products ?? [];
  const customers = customersQuery.data?.customers ?? [];
  const branches = branchesQuery.data?.branches ?? [];

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return orders.filter((order) => {
      if (statusFilter && order.status !== statusFilter) return false;
      if (!term) return true;
      return [order.number, order.customerName ?? "", order.branchName]
        .join(" ")
        .toLowerCase()
        .includes(term);
    });
  }, [orders, search, statusFilter]);

  const subtotalCents = useMemo(
    () =>
      lines.reduce((sum, line) => {
        const quantity = Number(line.quantity || 0);
        const price = Number(line.unitPrice || 0);
        const lineDiscount = Number(line.discount || 0);
        return (
          sum +
          Math.max(
            0,
            Math.round(quantity * price * 100) -
              Math.round(lineDiscount * 100),
          )
        );
      }, 0),
    [lines],
  );

  const totalCents = Math.max(
    0,
    subtotalCents - Math.round(Number(discount || 0) * 100),
  );

  useEffect(() => {
    if (!paidTouched) setPaid((totalCents / 100).toFixed(2));
  }, [totalCents, paidTouched]);

  const createMutation = useMutation({
    mutationFn: () =>
      createOrder(workspaceId, {
        branchId,
        customerId: customerId || null,
        paymentMethod,
        discountCents: Math.round(Number(discount || 0) * 100),
        paidCents: Math.round(Number(paid || 0) * 100),
        orderDate: fromDateInput(orderDate),
        expectedDate: expectedDate
          ? fromDateInput(expectedDate) ?? null
          : null,
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
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["orders", workspaceId],
      });
      showNotice(t("business.orders.created"));
      setOpen(false);
    },
    onError: (error) => setFormError(errorMessage(error)),
  });

  const statusMutation = useMutation({
    mutationFn: ({
      orderId,
      status,
    }: {
      orderId: string;
      status: "pending" | "confirmed" | "cancelled";
    }) => updateOrderStatus(workspaceId, orderId, status),
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["orders", workspaceId],
      });
      showNotice(t("business.orders.statusUpdated"));
    },
    onError: (error) => showNotice(errorMessage(error), "error"),
  });

  const fulfillMutation = useMutation({
    mutationFn: (orderId: string) => fulfillOrder(workspaceId, orderId),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["orders", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["sales", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["products", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["inventory", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["dashboard", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["reports", workspaceId] }),
      ]);
      showNotice(t("business.orders.fulfilled"));
      setPendingAction(null);
    },
    onError: (error) => {
      showNotice(errorMessage(error), "error");
      setPendingAction(null);
    },
  });

  function openCreate() {
    setFormError(null);
    setBranchId(
      branches.find((branch) => branch.isDefault)?.id ?? branches[0]?.id ?? "",
    );
    setCustomerId("");
    setPaymentMethod("cash");
    setOrderDate(todayInput());
    setExpectedDate("");
    setNote("");
    setDiscount("0");
    setPaid("0");
    setPaidTouched(false);
    setLines([newLine()]);
    setOpen(true);
  }

  function updateLine(key: string, patch: Partial<OrderLine>) {
    setLines((current) =>
      current.map((line) => (line.key === key ? { ...line, ...patch } : line)),
    );
  }

  function onProductChange(line: OrderLine, productId: string) {
    const product = products.find((item) => item.id === productId);
    updateLine(line.key, {
      productId,
      unitPrice: product
        ? (product.sellingPriceCents / 100).toFixed(2)
        : "0",
    });
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    setFormError(null);
    if (!branchId) {
      setFormError(t("business.orders.selectBranch"));
      return;
    }
    if (!lines.some((line) => line.productId && Number(line.quantity) > 0)) {
      setFormError(t("business.orders.noItems"));
      return;
    }
    createMutation.mutate();
  }

  function confirmAction() {
    if (!pendingAction) return;
    if (pendingAction.type === "fulfill") {
      fulfillMutation.mutate(pendingAction.order.id);
    } else {
      statusMutation.mutate(
        {
          orderId: pendingAction.order.id,
          status: "cancelled",
        },
        { onSettled: () => setPendingAction(null) },
      );
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.orders.title")}
        description={t("business.orders.subtitle")}
        actions={
          can("sale:create") ? (
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4" />
              {t("business.orders.add")}
            </Button>
          ) : undefined
        }
      />

      {notice ? (
        <div
          className={
            notice.tone === "error"
              ? "rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-2.5 text-sm text-destructive"
              : "rounded-lg border border-success/30 bg-success/10 px-4 py-2.5 text-sm text-success"
          }
        >
          {notice.message}
        </div>
      ) : null}

      <div className="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">
              {t("business.orders.openOrders")}
            </p>
            <p className="mt-1 text-2xl font-bold">
              {formatNumber(
                (ordersQuery.data?.summary?.pending ?? 0) +
                  (ordersQuery.data?.summary?.confirmed ?? 0),
                locale,
              )}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">
              {t("business.orders.fulfilled")}
            </p>
            <p className="mt-1 text-2xl font-bold">
              {formatNumber(
                ordersQuery.data?.summary?.fulfilled ?? 0,
                locale,
              )}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">
              {t("business.orders.totalValue")}
            </p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(
                ordersQuery.data?.summary?.totalCents ?? 0,
                workspace.defaultCurrency,
                locale,
              )}
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
                placeholder={t("business.orders.searchPlaceholder")}
                className="pl-9"
              />
            </div>
            <Select
              value={statusFilter}
              onChange={(event) => setStatusFilter(event.target.value)}
              className="sm:w-44"
            >
              <option value="">{t("business.orders.allStatuses")}</option>
              <option value="pending">{t("business.orders.pending")}</option>
              <option value="confirmed">
                {t("business.orders.confirmed")}
              </option>
              <option value="fulfilled">
                {t("business.orders.fulfilled")}
              </option>
              <option value="cancelled">
                {t("business.orders.cancelled")}
              </option>
            </Select>
          </div>

          {ordersQuery.isLoading ? (
            <div className="space-y-2">
              {[0, 1, 2, 3].map((index) => (
                <Skeleton key={index} className="h-11 w-full" />
              ))}
            </div>
          ) : ordersQuery.isError ? (
            <p className="py-10 text-center text-sm text-destructive">
              {errorMessage(ordersQuery.error)}
            </p>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon={ClipboardList}
              title={t("business.orders.emptyTitle")}
              description={t("business.orders.emptyBody")}
              action={
                can("sale:create") ? (
                  <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" />
                    {t("business.orders.add")}
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("business.orders.number")}</TableHead>
                  <TableHead>{t("business.sales.customer")}</TableHead>
                  <TableHead>{t("business.inventory.branch")}</TableHead>
                  <TableHead>{t("common.date")}</TableHead>
                  <TableHead className="text-right">
                    {t("common.total")}
                  </TableHead>
                  <TableHead>{t("common.status")}</TableHead>
                  <TableHead className="text-right">
                    {t("common.actions")}
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((order) => (
                  <TableRow key={order.id}>
                    <TableCell>
                      <div className="font-mono text-xs font-medium">
                        {order.number}
                      </div>
                      <div className="text-xs text-muted-foreground">
                        {t("business.orders.itemCount", {
                          count: order.itemCount,
                        })}
                      </div>
                    </TableCell>
                    <TableCell>
                      {order.customerName ?? t("business.sales.walkIn")}
                    </TableCell>
                    <TableCell>{order.branchName}</TableCell>
                    <TableCell>
                      <div>{formatDate(order.orderDate, locale)}</div>
                      {order.expectedDate ? (
                        <div className="text-xs text-muted-foreground">
                          {t("business.orders.expected", {
                            date: formatDate(order.expectedDate, locale),
                          })}
                        </div>
                      ) : null}
                    </TableCell>
                    <TableCell className="text-right font-medium">
                      {formatCents(order.totalCents, order.currency, locale)}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={order.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-1">
                        {order.status === "pending" && can("sale:update") ? (
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() =>
                              statusMutation.mutate({
                                orderId: order.id,
                                status: "confirmed",
                              })
                            }
                            aria-label={t("business.orders.confirm")}
                          >
                            <CheckCircle2 className="h-4 w-4 text-success" />
                          </Button>
                        ) : null}
                        {(order.status === "pending" ||
                          order.status === "confirmed") &&
                        can("sale:update") ? (
                          <>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() =>
                                setPendingAction({
                                  order,
                                  type: "fulfill",
                                })
                              }
                              aria-label={t("business.orders.fulfill")}
                            >
                              <PackageCheck className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() =>
                                setPendingAction({ order, type: "cancel" })
                              }
                              aria-label={t("business.orders.cancelOrder")}
                            >
                              <Ban className="h-4 w-4 text-destructive" />
                            </Button>
                          </>
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
        title={t("business.orders.add")}
        size="xl"
        footer={
          <>
            <div className="mr-auto text-sm">
              <span className="text-muted-foreground">
                {t("common.total")}:{" "}
              </span>
              <span className="text-lg font-bold">
                {formatCents(
                  totalCents,
                  workspace.defaultCurrency,
                  locale,
                )}
              </span>
            </div>
            <Button
              type="button"
              variant="outline"
              onClick={() => setOpen(false)}
              disabled={createMutation.isPending}
            >
              {t("common.cancel")}
            </Button>
            <Button
              type="submit"
              form="order-form"
              disabled={createMutation.isPending}
            >
              {createMutation.isPending
                ? t("common.saving")
                : t("business.orders.create")}
            </Button>
          </>
        }
      >
        <form id="order-form" className="space-y-5" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-3">
            <Field
              label={t("business.inventory.branch")}
              htmlFor="order-branch"
              required
            >
              <Select
                id="order-branch"
                value={branchId}
                onChange={(event) => setBranchId(event.target.value)}
                required
              >
                <option value="">{t("business.orders.selectBranch")}</option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field
              label={t("business.sales.customer")}
              htmlFor="order-customer"
            >
              <Select
                id="order-customer"
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
            <Field
              label={t("business.sales.paymentMethod")}
              htmlFor="order-method"
            >
              <Select
                id="order-method"
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
            <Field label={t("business.orders.orderDate")} htmlFor="order-date">
              <Input
                id="order-date"
                type="date"
                value={orderDate}
                onChange={(event) => setOrderDate(event.target.value)}
              />
            </Field>
            <Field
              label={t("business.orders.expectedDate")}
              htmlFor="order-expected"
            >
              <Input
                id="order-expected"
                type="date"
                value={expectedDate}
                onChange={(event) => setExpectedDate(event.target.value)}
              />
            </Field>
            <Field
              label={t("business.orders.discount")}
              htmlFor="order-discount"
            >
              <Input
                id="order-discount"
                type="number"
                min="0"
                step="0.01"
                value={discount}
                onChange={(event) => setDiscount(event.target.value)}
              />
            </Field>
            <Field label={t("business.sales.paidAmount")} htmlFor="order-paid">
              <Input
                id="order-paid"
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
              <p className="text-sm font-semibold">
                {t("business.orders.items")}
              </p>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() =>
                  setLines((current) => [...current, newLine()])
                }
              >
                <Plus className="h-4 w-4" />
                {t("business.sales.addItem")}
              </Button>
            </div>
            <div className="space-y-2">
              {lines.map((line) => {
                const lineTotal = Math.max(
                  0,
                  Math.round(
                    Number(line.quantity || 0) *
                      Number(line.unitPrice || 0) *
                      100,
                  ) - Math.round(Number(line.discount || 0) * 100),
                );
                return (
                  <div
                    key={line.key}
                    className="grid gap-2 rounded-lg border p-3 sm:grid-cols-[1fr_5rem_7rem_7rem_auto] sm:items-end"
                  >
                    <Field label={t("business.sales.item")}>
                      <Select
                        value={line.productId}
                        onChange={(event) =>
                          onProductChange(line, event.target.value)
                        }
                      >
                        <option value="">
                          {t("business.sales.selectProduct")}
                        </option>
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
                          updateLine(line.key, {
                            quantity: event.target.value,
                          })
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
                          updateLine(line.key, {
                            unitPrice: event.target.value,
                          })
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
                          updateLine(line.key, {
                            discount: event.target.value,
                          })
                        }
                      />
                    </Field>
                    <div className="flex items-center justify-end gap-1 pb-1 sm:pb-0">
                      <span className="text-sm font-medium">
                        {formatCents(
                          lineTotal,
                          workspace.defaultCurrency,
                          locale,
                        )}
                      </span>
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() =>
                          setLines((current) =>
                            current.length === 1
                              ? [newLine()]
                              : current.filter(
                                  (item) => item.key !== line.key,
                                ),
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
            <p className="text-xs text-muted-foreground">
              {t("business.orders.totalHint")}
            </p>
          </div>

          <Field label={t("business.sales.notes")} htmlFor="order-note">
            <Textarea
              id="order-note"
              value={note}
              onChange={(event) => setNote(event.target.value)}
            />
          </Field>
          {formError ? (
            <p className="text-sm text-destructive">{formError}</p>
          ) : null}
        </form>
      </Dialog>

      <ConfirmDialog
        open={Boolean(pendingAction)}
        onClose={() => setPendingAction(null)}
        onConfirm={confirmAction}
        title={
          pendingAction?.type === "fulfill"
            ? t("business.orders.fulfillTitle")
            : t("business.orders.cancelTitle")
        }
        description={
          pendingAction?.type === "fulfill"
            ? t("business.orders.fulfillBody", {
                number: pendingAction.order.number,
              })
            : t("business.orders.cancelBody", {
                number: pendingAction?.order.number ?? "",
              })
        }
        confirmLabel={
          pendingAction?.type === "fulfill"
            ? t("business.orders.fulfill")
            : t("business.orders.cancelOrder")
        }
        cancelLabel={t("common.cancel")}
        pending={
          fulfillMutation.isPending || statusMutation.isPending
        }
        destructive={pendingAction?.type === "cancel"}
      />
    </div>
  );
}
