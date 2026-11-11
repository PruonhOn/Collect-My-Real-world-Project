import { useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import {
  AlertTriangle,
  ArrowDownRight,
  ArrowUpRight,
  Boxes,
  History,
  Search,
  SlidersHorizontal,
} from "lucide-react";
import { EmptyState } from "@/components/app/empty-state";
import { PageHeader } from "@/components/app/page-header";
import { Badge } from "@/components/ui/badge";
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
  adjustStock,
  fetchInventory,
  fetchProducts,
  type StockLevel,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { formatDateTime, formatNumber } from "@/lib/format";
import { useBusinessContext } from "@/lib/use-business";

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

type AdjustForm = {
  branchId: string;
  productId: string;
  quantity: string;
  note: string;
};

export function InventoryPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [branchFilter, setBranchFilter] = useState("");
  const [lowOnly, setLowOnly] = useState(false);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [form, setForm] = useState<AdjustForm>({
    branchId: "",
    productId: "",
    quantity: "0",
    note: "",
  });
  const [formError, setFormError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  const inventoryQuery = useQuery({
    queryKey: ["inventory", workspaceId],
    queryFn: () => fetchInventory(workspaceId),
  });
  const productsQuery = useQuery({
    queryKey: ["products", workspaceId],
    queryFn: () => fetchProducts(workspaceId, { status: "active" }),
  });

  const levels = inventoryQuery.data?.levels ?? [];
  const branches = inventoryQuery.data?.branches ?? [];
  const movements = inventoryQuery.data?.movements ?? [];
  const products = (productsQuery.data?.products ?? []).filter(
    (product) => product.trackInventory,
  );

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return levels.filter((level) => {
      if (branchFilter && level.branchId !== branchFilter) return false;
      if (lowOnly && !level.lowStock) return false;
      if (!term) return true;
      return [level.productName, level.sku, level.branchName]
        .join(" ")
        .toLowerCase()
        .includes(term);
    });
  }, [levels, search, branchFilter, lowOnly]);

  const adjustMutation = useMutation({
    mutationFn: () =>
      adjustStock(workspaceId, {
        branchId: form.branchId,
        productId: form.productId,
        quantity: Number(form.quantity || 0),
        note: form.note.trim() || undefined,
      }),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["inventory", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["products", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["dashboard", workspaceId] }),
      ]);
      setNotice(t("business.inventory.adjustSaved"));
      setDialogOpen(false);
    },
    onError: (error) => setFormError(errorMessage(error)),
  });

  function openAdjust(level?: StockLevel) {
    setForm({
      branchId: level?.branchId ?? branchFilter ?? branches[0]?.id ?? "",
      productId: level?.productId ?? "",
      quantity: String(level?.quantity ?? 0),
      note: "",
    });
    setFormError(null);
    setDialogOpen(true);
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    setFormError(null);
    if (!form.branchId || !form.productId) {
      setFormError(t("business.inventory.noProduct"));
      return;
    }
    adjustMutation.mutate();
  }

  const selectedLevel = levels.find(
    (level) =>
      level.branchId === form.branchId && level.productId === form.productId,
  );

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.inventory.title")}
        description={t("business.inventory.subtitle")}
        actions={
          can("inventory:update") ? (
            <Button onClick={() => openAdjust()}>
              <SlidersHorizontal className="h-4 w-4" />
              {t("business.inventory.adjust")}
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
          <CardContent className="flex items-center gap-3 p-5">
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
              <Boxes className="h-5 w-5" />
            </span>
            <div>
              <p className="text-xs text-muted-foreground">
                {t("business.inventory.totalUnits")}
              </p>
              <p className="text-xl font-bold">
                {formatNumber(inventoryQuery.data?.summary.totalUnits ?? 0, locale)}
              </p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="flex items-center gap-3 p-5">
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-destructive/10 text-destructive">
              <AlertTriangle className="h-5 w-5" />
            </span>
            <div>
              <p className="text-xs text-muted-foreground">
                {t("business.inventory.lowStockItems")}
              </p>
              <p className="text-xl font-bold">
                {formatNumber(inventoryQuery.data?.summary.lowStockCount ?? 0, locale)}
              </p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="flex items-center gap-3 p-5">
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/15 text-accent-foreground">
              <History className="h-5 w-5" />
            </span>
            <div>
              <p className="text-xs text-muted-foreground">
                {t("business.inventory.movements")}
              </p>
              <p className="text-xl font-bold">{formatNumber(movements.length, locale)}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 xl:grid-cols-3">
        <Card className="xl:col-span-2">
          <CardContent className="space-y-4 p-4 sm:p-5">
            <div className="flex flex-col gap-2 sm:flex-row">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  value={search}
                  onChange={(event) => setSearch(event.target.value)}
                  placeholder={t("business.products.searchPlaceholder")}
                  className="pl-9"
                />
              </div>
              <Select
                value={branchFilter}
                onChange={(event) => setBranchFilter(event.target.value)}
                className="sm:w-48"
              >
                <option value="">{t("business.inventory.allBranches")}</option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name}
                  </option>
                ))}
              </Select>
              <Button
                type="button"
                variant={lowOnly ? "default" : "outline"}
                onClick={() => setLowOnly((current) => !current)}
              >
                <AlertTriangle className="h-4 w-4" />
                {t("business.inventory.lowStockOnly")}
              </Button>
            </div>

            {inventoryQuery.isLoading ? (
              <div className="space-y-2">
                {[0, 1, 2, 3].map((index) => (
                  <Skeleton key={index} className="h-11 w-full" />
                ))}
              </div>
            ) : inventoryQuery.isError ? (
              <p className="py-10 text-center text-sm text-destructive">
                {errorMessage(inventoryQuery.error)}
              </p>
            ) : filtered.length === 0 ? (
              <EmptyState
                icon={Boxes}
                title={t("business.inventory.noLevels")}
                description={t("business.inventory.noLevelsBody")}
              />
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>{t("business.products.title")}</TableHead>
                    <TableHead>{t("business.inventory.branch")}</TableHead>
                    <TableHead className="text-right">{t("business.products.stock")}</TableHead>
                    <TableHead>{t("common.status")}</TableHead>
                    <TableHead>{t("business.inventory.updated")}</TableHead>
                    <TableHead className="text-right">{t("common.actions")}</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filtered.map((level) => (
                    <TableRow key={level.levelId}>
                      <TableCell>
                        <div className="font-medium">{level.productName}</div>
                        <div className="font-mono text-xs text-muted-foreground">
                          {level.sku}
                        </div>
                      </TableCell>
                      <TableCell>{level.branchName}</TableCell>
                      <TableCell className="text-right">
                        <span className="font-semibold">
                          {formatNumber(level.quantity, locale)}
                        </span>{" "}
                        <span className="text-xs text-muted-foreground">
                          {level.unit}
                        </span>
                      </TableCell>
                      <TableCell>
                        {level.lowStock ? (
                          <Badge variant="destructive">{t("business.products.lowStockLabel")}</Badge>
                        ) : (
                          <Badge variant="success">{t("business.products.active")}</Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground">
                        {formatDateTime(level.updatedAt, locale)}
                      </TableCell>
                      <TableCell className="text-right">
                        {can("inventory:update") ? (
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => openAdjust(level)}
                          >
                            {t("business.inventory.adjust")}
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

        <Card>
          <CardContent className="p-4 sm:p-5">
            <div className="mb-4">
              <h3 className="font-semibold">{t("business.inventory.movements")}</h3>
              <p className="text-xs text-muted-foreground">
                {t("business.inventory.movementsSubtitle")}
              </p>
            </div>
            {movements.length === 0 ? (
              <p className="py-8 text-center text-sm text-muted-foreground">
                {t("business.inventory.noMovements")}
              </p>
            ) : (
              <div className="space-y-1">
                {movements.slice(0, 12).map((movement) => (
                  <div
                    key={movement.id}
                    className="flex items-start gap-2.5 rounded-lg px-2 py-2 hover:bg-secondary/50"
                  >
                    <span
                      className={
                        movement.quantity >= 0
                          ? "mt-0.5 text-success"
                          : "mt-0.5 text-destructive"
                      }
                    >
                      {movement.quantity >= 0 ? (
                        <ArrowUpRight className="h-4 w-4" />
                      ) : (
                        <ArrowDownRight className="h-4 w-4" />
                      )}
                    </span>
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-sm font-medium">
                        {movement.productName ?? movement.note ?? t(`business.movements.${movement.type}`)}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        {movement.branchName} · {t(`business.movements.${movement.type}`)}
                      </p>
                    </div>
                    <div className="text-right">
                      <p
                        className={
                          movement.quantity >= 0
                            ? "text-sm font-semibold text-success"
                            : "text-sm font-semibold text-destructive"
                        }
                      >
                        {movement.quantity > 0 ? "+" : ""}
                        {formatNumber(movement.quantity, locale)}
                      </p>
                      <p className="text-[10px] text-muted-foreground">
                        {formatDateTime(movement.createdAt, locale)}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      <Dialog
        open={dialogOpen}
        onClose={() => setDialogOpen(false)}
        title={t("business.inventory.adjustTitle")}
        description={t("business.inventory.adjustSubtitle")}
        size="sm"
        footer={
          <>
            <Button
              type="button"
              variant="outline"
              onClick={() => setDialogOpen(false)}
              disabled={adjustMutation.isPending}
            >
              {t("common.cancel")}
            </Button>
            <Button type="submit" form="adjust-form" disabled={adjustMutation.isPending}>
              {adjustMutation.isPending ? t("common.saving") : t("common.save")}
            </Button>
          </>
        }
      >
        <form id="adjust-form" className="space-y-4" onSubmit={submit}>
          <Field label={t("business.inventory.branch")} htmlFor="adjust-branch" required>
            <Select
              id="adjust-branch"
              value={form.branchId}
              onChange={(event) => setForm({ ...form, branchId: event.target.value })}
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
          <Field label={t("business.products.title")} htmlFor="adjust-product" required>
            <Select
              id="adjust-product"
              value={form.productId}
              onChange={(event) => setForm({ ...form, productId: event.target.value })}
              required
            >
              <option value="">{t("business.inventory.noProduct")}</option>
              {products.map((product) => (
                <option key={product.id} value={product.id}>
                  {product.name}
                </option>
              ))}
            </Select>
          </Field>
          {selectedLevel ? (
            <p className="text-xs text-muted-foreground">
              {t("business.inventory.current")}: {selectedLevel.quantity}{" "}
              {selectedLevel.unit}
            </p>
          ) : null}
          <Field label={t("business.inventory.newQuantity")} htmlFor="adjust-quantity" required>
            <Input
              id="adjust-quantity"
              type="number"
              min="0"
              step="1"
              value={form.quantity}
              onChange={(event) => setForm({ ...form, quantity: event.target.value })}
              required
            />
          </Field>
          <Field label={t("common.notes")} htmlFor="adjust-note">
            <Textarea
              id="adjust-note"
              value={form.note}
              onChange={(event) => setForm({ ...form, note: event.target.value })}
              placeholder={t("business.inventory.notePlaceholder")}
            />
          </Field>
          {formError ? <p className="text-sm text-destructive">{formError}</p> : null}
        </form>
      </Dialog>
    </div>
  );
}
