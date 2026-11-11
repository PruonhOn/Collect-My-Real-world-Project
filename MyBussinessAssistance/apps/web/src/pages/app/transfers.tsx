import { useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import {
  ArrowLeftRight,
  Ban,
  CheckCircle2,
  Plus,
  Search,
  X,
} from "lucide-react";
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
  cancelTransfer,
  completeTransfer,
  createTransfer,
  fetchBranches,
  fetchProducts,
  fetchTransfers,
  type TransferSummary,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { formatDate } from "@/lib/format";
import { formatQuantity } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

type TransferLine = {
  key: string;
  productId: string;
  quantity: string;
};

function newLine(): TransferLine {
  return {
    key: crypto.randomUUID(),
    productId: "",
    quantity: "1",
  };
}

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

type PendingAction = {
  transfer: TransferSummary;
  type: "complete" | "cancel";
};

export function TransfersPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, can } = useBusinessContext();
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
  const [fromBranchId, setFromBranchId] = useState("");
  const [toBranchId, setToBranchId] = useState("");
  const [note, setNote] = useState("");
  const [lines, setLines] = useState<TransferLine[]>([newLine()]);
  const [pendingAction, setPendingAction] = useState<PendingAction | null>(null);

  const transfersQuery = useQuery({
    queryKey: ["transfers", workspaceId],
    queryFn: () => fetchTransfers(workspaceId),
  });
  const productsQuery = useQuery({
    queryKey: ["products", workspaceId],
    queryFn: () => fetchProducts(workspaceId, { status: "active" }),
  });
  const branchesQuery = useQuery({
    queryKey: ["branches", workspaceId],
    queryFn: () => fetchBranches(workspaceId),
    retry: false,
  });

  const transfers = transfersQuery.data?.transfers ?? [];
  const products = productsQuery.data?.products ?? [];
  const branches = branchesQuery.data?.branches ?? [];

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return transfers.filter((transfer) => {
      if (statusFilter && transfer.status !== statusFilter) return false;
      if (!term) return true;
      return [
        transfer.number,
        transfer.fromBranchName,
        transfer.toBranchName,
        transfer.note ?? "",
      ]
        .join(" ")
        .toLowerCase()
        .includes(term);
    });
  }, [transfers, search, statusFilter]);

  const createMutation = useMutation({
    mutationFn: () =>
      createTransfer(workspaceId, {
        fromBranchId,
        toBranchId,
        note: note.trim() || undefined,
        items: lines
          .filter((line) => line.productId && Number(line.quantity) > 0)
          .map((line) => ({
            productId: line.productId,
            quantity: Number(line.quantity),
          })),
      }),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({
          queryKey: ["transfers", workspaceId],
        }),
        queryClient.invalidateQueries({
          queryKey: ["inventory", workspaceId],
        }),
      ]);
      showNotice(t("business.transfers.created"));
      setOpen(false);
    },
    onError: (error) => setFormError(errorMessage(error)),
  });

  const completeMutation = useMutation({
    mutationFn: (transferId: string) =>
      completeTransfer(workspaceId, transferId),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({
          queryKey: ["transfers", workspaceId],
        }),
        queryClient.invalidateQueries({
          queryKey: ["inventory", workspaceId],
        }),
        queryClient.invalidateQueries({
          queryKey: ["products", workspaceId],
        }),
      ]);
      showNotice(t("business.transfers.completed"));
      setPendingAction(null);
    },
    onError: (error) => {
      showNotice(errorMessage(error), "error");
      setPendingAction(null);
    },
  });

  const cancelMutation = useMutation({
    mutationFn: (transferId: string) =>
      cancelTransfer(workspaceId, transferId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["transfers", workspaceId],
      });
      showNotice(t("business.transfers.cancelled"));
      setPendingAction(null);
    },
    onError: (error) => {
      showNotice(errorMessage(error), "error");
      setPendingAction(null);
    },
  });

  function openCreate() {
    setFormError(null);
    setFromBranchId(
      branches.find((branch) => branch.isDefault)?.id ?? branches[0]?.id ?? "",
    );
    setToBranchId(branches.find((branch) => !branch.isDefault)?.id ?? "");
    setNote("");
    setLines([newLine()]);
    setOpen(true);
  }

  function updateLine(key: string, patch: Partial<TransferLine>) {
    setLines((current) =>
      current.map((line) => (line.key === key ? { ...line, ...patch } : line)),
    );
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    setFormError(null);
    if (!fromBranchId || !toBranchId) {
      setFormError(t("business.transfers.selectBranches"));
      return;
    }
    if (fromBranchId === toBranchId) {
      setFormError(t("business.transfers.sameBranch"));
      return;
    }
    if (!lines.some((line) => line.productId && Number(line.quantity) > 0)) {
      setFormError(t("business.transfers.noItems"));
      return;
    }
    createMutation.mutate();
  }

  function confirmAction() {
    if (!pendingAction) return;
    if (pendingAction.type === "complete") {
      completeMutation.mutate(pendingAction.transfer.id);
    } else {
      cancelMutation.mutate(pendingAction.transfer.id);
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.transfers.title")}
        description={t("business.transfers.subtitle")}
        actions={
          can("inventory:transfer") ? (
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4" />
              {t("business.transfers.add")}
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
              {t("business.transfers.pending")}
            </p>
            <p className="mt-1 text-2xl font-bold">
              {formatQuantity(
                transfersQuery.data?.summary?.pending ?? 0,
                locale,
              )}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">
              {t("business.transfers.completed")}
            </p>
            <p className="mt-1 text-2xl font-bold">
              {formatQuantity(
                transfersQuery.data?.summary?.completed ?? 0,
                locale,
              )}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">
              {t("business.transfers.total")}
            </p>
            <p className="mt-1 text-2xl font-bold">
              {formatQuantity(
                transfersQuery.data?.summary?.count ?? 0,
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
                placeholder={t("business.transfers.searchPlaceholder")}
                className="pl-9"
              />
            </div>
            <Select
              value={statusFilter}
              onChange={(event) => setStatusFilter(event.target.value)}
              className="sm:w-44"
            >
              <option value="">{t("business.transfers.allStatuses")}</option>
              <option value="pending">{t("business.transfers.pending")}</option>
              <option value="completed">
                {t("business.transfers.completed")}
              </option>
              <option value="cancelled">
                {t("business.transfers.cancelled")}
              </option>
            </Select>
          </div>

          {transfersQuery.isLoading ? (
            <div className="space-y-2">
              {[0, 1, 2, 3].map((index) => (
                <Skeleton key={index} className="h-11 w-full" />
              ))}
            </div>
          ) : transfersQuery.isError ? (
            <p className="py-10 text-center text-sm text-destructive">
              {errorMessage(transfersQuery.error)}
            </p>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon={ArrowLeftRight}
              title={t("business.transfers.emptyTitle")}
              description={t("business.transfers.emptyBody")}
              action={
                can("inventory:transfer") ? (
                  <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" />
                    {t("business.transfers.add")}
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("business.transfers.number")}</TableHead>
                  <TableHead>{t("business.transfers.route")}</TableHead>
                  <TableHead>{t("business.transfers.items")}</TableHead>
                  <TableHead>{t("common.date")}</TableHead>
                  <TableHead>{t("common.status")}</TableHead>
                  <TableHead className="text-right">
                    {t("common.actions")}
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((transfer) => (
                  <TableRow key={transfer.id}>
                    <TableCell>
                      <div className="font-mono text-xs font-medium">
                        {transfer.number}
                      </div>
                      {transfer.note ? (
                        <div className="max-w-[14rem] truncate text-xs text-muted-foreground">
                          {transfer.note}
                        </div>
                      ) : null}
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-1.5 text-sm">
                        <span>{transfer.fromBranchName}</span>
                        <ArrowLeftRight className="h-3.5 w-3.5 text-muted-foreground" />
                        <span>{transfer.toBranchName}</span>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div>
                        {t("business.transfers.itemCount", {
                          count: transfer.itemCount,
                        })}
                      </div>
                      <div className="text-xs text-muted-foreground">
                        {t("business.transfers.quantity", {
                          count: transfer.totalQuantity,
                        })}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div>{formatDate(transfer.createdAt, locale)}</div>
                      {transfer.completedAt ? (
                        <div className="text-xs text-muted-foreground">
                          {formatDate(transfer.completedAt, locale)}
                        </div>
                      ) : null}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={transfer.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {transfer.status === "pending" &&
                      can("inventory:transfer") ? (
                        <div className="flex items-center justify-end gap-1">
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() =>
                              setPendingAction({
                                transfer,
                                type: "complete",
                              })
                            }
                            aria-label={t("business.transfers.complete")}
                          >
                            <CheckCircle2 className="h-4 w-4 text-success" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() =>
                              setPendingAction({
                                transfer,
                                type: "cancel",
                              })
                            }
                            aria-label={t("business.transfers.cancelAction")}
                          >
                            <Ban className="h-4 w-4 text-destructive" />
                          </Button>
                        </div>
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
        title={t("business.transfers.add")}
        size="lg"
        footer={
          <>
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
              form="transfer-form"
              disabled={createMutation.isPending}
            >
              {createMutation.isPending
                ? t("common.saving")
                : t("business.transfers.create")}
            </Button>
          </>
        }
      >
        <form id="transfer-form" className="space-y-4" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field
              label={t("business.transfers.fromBranch")}
              htmlFor="transfer-from"
              required
            >
              <Select
                id="transfer-from"
                value={fromBranchId}
                onChange={(event) => setFromBranchId(event.target.value)}
                required
              >
                <option value="">{t("business.transfers.selectBranch")}</option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field
              label={t("business.transfers.toBranch")}
              htmlFor="transfer-to"
              required
            >
              <Select
                id="transfer-to"
                value={toBranchId}
                onChange={(event) => setToBranchId(event.target.value)}
                required
              >
                <option value="">{t("business.transfers.selectBranch")}</option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name}
                  </option>
                ))}
              </Select>
            </Field>
          </div>

          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <p className="text-sm font-semibold">
                {t("business.transfers.items")}
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
              {lines.map((line) => (
                <div
                  key={line.key}
                  className="grid gap-2 rounded-lg border p-3 sm:grid-cols-[1fr_7rem_auto] sm:items-end"
                >
                  <Field label={t("business.sales.item")}>
                    <Select
                      value={line.productId}
                      onChange={(event) =>
                        updateLine(line.key, {
                          productId: event.target.value,
                        })
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
              ))}
            </div>
          </div>

          <Field label={t("common.notes")} htmlFor="transfer-note">
            <Textarea
              id="transfer-note"
              value={note}
              onChange={(event) => setNote(event.target.value)}
            />
          </Field>
          <p className="text-xs text-muted-foreground">
            {t("business.transfers.stockHint")}
          </p>
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
          pendingAction?.type === "complete"
            ? t("business.transfers.completeTitle")
            : t("business.transfers.cancelTitle")
        }
        description={
          pendingAction?.type === "complete"
            ? t("business.transfers.completeBody", {
                number: pendingAction.transfer.number,
              })
            : t("business.transfers.cancelBody", {
                number: pendingAction?.transfer.number ?? "",
              })
        }
        confirmLabel={
          pendingAction?.type === "complete"
            ? t("business.transfers.complete")
            : t("business.transfers.cancelAction")
        }
        cancelLabel={t("common.cancel")}
        pending={completeMutation.isPending || cancelMutation.isPending}
        destructive={pendingAction?.type === "cancel"}
      />
    </div>
  );
}
