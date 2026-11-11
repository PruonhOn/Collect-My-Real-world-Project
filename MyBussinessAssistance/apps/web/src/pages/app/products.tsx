import { useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import {
  AlertTriangle,
  Package,
  Pencil,
  Plus,
  Search,
  Tags,
  Trash2,
} from "lucide-react";
import { PRODUCT_UNITS } from "@mba/shared";
import { EmptyState } from "@/components/app/empty-state";
import { PageHeader } from "@/components/app/page-header";
import { StatusBadge } from "@/components/app/status-badge";
import { ConfirmDialog } from "@/components/app/confirm-dialog";
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
  createCategory,
  createProduct,
  deleteCategory,
  fetchCategories,
  fetchProducts,
  updateProduct,
  type Category,
  type Product,
  type ProductInput,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { formatCents, formatQuantity } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

type ProductForm = {
  name: string;
  sku: string;
  barcode: string;
  categoryId: string;
  description: string;
  unit: string;
  costPrice: string;
  sellingPrice: string;
  taxRate: string;
  trackInventory: boolean;
  lowStockThreshold: string;
  openingStock: string;
  status: string;
};

const emptyForm: ProductForm = {
  name: "",
  sku: "",
  barcode: "",
  categoryId: "",
  description: "",
  unit: "pcs",
  costPrice: "0",
  sellingPrice: "0",
  taxRate: "",
  trackInventory: true,
  lowStockThreshold: "5",
  openingStock: "0",
  status: "active",
};

function toForm(product: Product): ProductForm {
  return {
    name: product.name,
    sku: product.sku,
    barcode: product.barcode ?? "",
    categoryId: product.categoryId ?? "",
    description: product.description ?? "",
    unit: product.unit,
    costPrice: (product.costPriceCents / 100).toFixed(2),
    sellingPrice: (product.sellingPriceCents / 100).toFixed(2),
    taxRate:
      product.taxRateBasisPoints === null
        ? ""
        : String(product.taxRateBasisPoints / 100),
    trackInventory: product.trackInventory,
    lowStockThreshold: String(product.lowStockThreshold),
    openingStock: "0",
    status: product.status,
  };
}

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

export function ProductsPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [categoryFilter, setCategoryFilter] = useState("");
  const [statusFilter, setStatusFilter] = useState("");
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editing, setEditing] = useState<Product | null>(null);
  const [form, setForm] = useState<ProductForm>(emptyForm);
  const [formError, setFormError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [categoriesOpen, setCategoriesOpen] = useState(false);
  const [newCategory, setNewCategory] = useState("");
  const [categoryToDelete, setCategoryToDelete] = useState<Category | null>(null);

  const productsQuery = useQuery({
    queryKey: ["products", workspaceId],
    queryFn: () => fetchProducts(workspaceId),
  });
  const categoriesQuery = useQuery({
    queryKey: ["categories", workspaceId],
    queryFn: () => fetchCategories(workspaceId),
  });

  const categories = categoriesQuery.data?.categories ?? [];
  const products = productsQuery.data?.products ?? [];

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return products.filter((product) => {
      if (statusFilter && product.status !== statusFilter) return false;
      if (categoryFilter && product.categoryId !== categoryFilter) return false;
      if (!term) return true;
      return [product.name, product.sku, product.barcode ?? ""]
        .join(" ")
        .toLowerCase()
        .includes(term);
    });
  }, [products, search, statusFilter, categoryFilter]);

  const lowStockCount = products.filter(
    (product) => product.trackInventory && product.lowStock,
  ).length;

  const saveMutation = useMutation({
    mutationFn: async (payload: ProductInput) => {
      if (editing) {
        return updateProduct(workspaceId, editing.id, {
          ...payload,
          status: form.status,
        });
      }
      return createProduct(workspaceId, payload);
    },
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["products", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["inventory", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["categories", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["dashboard", workspaceId] }),
      ]);
      setNotice(editing ? t("business.products.updated") : t("business.products.created"));
      setDialogOpen(false);
      setEditing(null);
    },
    onError: (error) => setFormError(errorMessage(error)),
  });

  const categoryMutation = useMutation({
    mutationFn: () => createCategory(workspaceId, { name: newCategory.trim() }),
    onSuccess: async () => {
      setNewCategory("");
await queryClient.invalidateQueries({
        queryKey: ["categories", workspaceId],
      });
    },
  });

  const deleteCategoryMutation = useMutation({
    mutationFn: (categoryId: string) => deleteCategory(workspaceId, categoryId),
    onError: () => setCategoryToDelete(null),
    onSuccess: async () => {
      setCategoryToDelete(null);
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["categories", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["products", workspaceId] }),
      ]);
    },
  });

  function openCreate() {
    setEditing(null);
    setForm(emptyForm);
    setFormError(null);
    setDialogOpen(true);
  }

  function openEdit(product: Product) {
    setEditing(product);
    setForm(toForm(product));
    setFormError(null);
    setDialogOpen(true);
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    setFormError(null);
    const payload: ProductInput = {
      name: form.name.trim(),
      sku: form.sku.trim(),
      barcode: form.barcode.trim() || undefined,
      categoryId: form.categoryId || null,
      description: form.description.trim() || undefined,
      unit: form.unit,
      costPriceCents: Math.round(Number(form.costPrice || 0) * 100),
      sellingPriceCents: Math.round(Number(form.sellingPrice || 0) * 100),
      taxRateBasisPoints:
        form.taxRate.trim() === ""
          ? null
          : Math.round(Number(form.taxRate) * 100),
      trackInventory: form.trackInventory,
      lowStockThreshold: Number(form.lowStockThreshold || 0),
      openingStock: editing ? undefined : Number(form.openingStock || 0),
    };
    saveMutation.mutate(payload);
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.products.title")}
        description={t("business.products.subtitle")}
        actions={
          <>
            <Button variant="outline" onClick={() => setCategoriesOpen(true)}>
              <Tags className="h-4 w-4" />
              {t("business.products.manageCategories")}
            </Button>
            {can("product:create") ? (
              <Button onClick={openCreate}>
                <Plus className="h-4 w-4" />
                {t("business.products.add")}
              </Button>
            ) : null}
          </>
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
              <Package className="h-5 w-5" />
            </span>
            <div>
              <p className="text-xs text-muted-foreground">
                {t("business.products.title")}
              </p>
              <p className="text-xl font-bold">{formatQuantity(products.length, locale)}</p>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="flex items-center gap-3 p-5">
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/15 text-accent-foreground">
              <Tags className="h-5 w-5" />
            </span>
            <div>
              <p className="text-xs text-muted-foreground">
                {t("business.products.categories")}
              </p>
              <p className="text-xl font-bold">{formatQuantity(categories.length, locale)}</p>
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
                {t("business.products.lowStock")}
              </p>
              <p className="text-xl font-bold">{formatQuantity(lowStockCount, locale)}</p>
            </div>
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
                placeholder={t("business.products.searchPlaceholder")}
                className="pl-9"
              />
            </div>
            <Select
              value={categoryFilter}
              onChange={(event) => setCategoryFilter(event.target.value)}
              className="sm:w-48"
            >
              <option value="">{t("business.products.allCategories")}</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </Select>
            <Select
              value={statusFilter}
              onChange={(event) => setStatusFilter(event.target.value)}
              className="sm:w-40"
            >
              <option value="">{t("common.all")}</option>
              <option value="active">{t("business.products.active")}</option>
              <option value="inactive">{t("business.products.inactive")}</option>
            </Select>
          </div>

          {productsQuery.isLoading ? (
            <div className="space-y-2">
              {[0, 1, 2, 3].map((index) => (
                <Skeleton key={index} className="h-11 w-full" />
              ))}
            </div>
          ) : productsQuery.isError ? (
            <div className="py-10 text-center text-sm text-destructive">
              {errorMessage(productsQuery.error)}
            </div>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon={Package}
              title={t("business.products.emptyTitle")}
              description={t("business.products.emptyBody")}
              action={
                can("product:create") ? (
                  <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" />
                    {t("business.products.add")}
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("common.name")}</TableHead>
                  <TableHead>{t("business.products.sku")}</TableHead>
                  <TableHead>{t("business.products.category")}</TableHead>
                  <TableHead className="text-right">{t("business.products.stock")}</TableHead>
                  <TableHead className="text-right">{t("business.products.costPrice")}</TableHead>
                  <TableHead className="text-right">{t("business.products.sellingPrice")}</TableHead>
                  <TableHead>{t("common.status")}</TableHead>
                  <TableHead className="text-right">{t("common.actions")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((product) => (
                  <TableRow key={product.id}>
                    <TableCell>
                      <div className="font-medium">{product.name}</div>
                      <div className="text-xs text-muted-foreground">
                        {product.unit}
                      </div>
                    </TableCell>
                    <TableCell className="font-mono text-xs">{product.sku}</TableCell>
                    <TableCell>{product.categoryName ?? "—"}</TableCell>
                    <TableCell className="text-right">
                      <div className="flex items-center justify-end gap-2">
                        <span>{product.trackInventory ? formatQuantity(product.totalStock ?? 0, locale) : "—"}</span>
                        {product.lowStock ? (
                          <Badge variant="destructive">
                            {t("business.products.lowStockLabel")}
                          </Badge>
                        ) : null}
                      </div>
                    </TableCell>
                    <TableCell className="text-right">
                      {formatCents(product.costPriceCents, workspace.defaultCurrency, locale)}
                    </TableCell>
                    <TableCell className="text-right font-medium">
                      {formatCents(product.sellingPriceCents, workspace.defaultCurrency, locale)}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={product.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {can("product:update") ? (
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => openEdit(product)}
                          aria-label={t("common.edit")}
                        >
                          <Pencil className="h-4 w-4" />
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
        open={dialogOpen}
        onClose={() => setDialogOpen(false)}
        title={
          editing ? t("business.products.edit") : t("business.products.add")
        }
        size="lg"
        footer={
          <>
            <Button
              type="button"
              variant="outline"
              onClick={() => setDialogOpen(false)}
              disabled={saveMutation.isPending}
            >
              {t("common.cancel")}
            </Button>
            <Button type="submit" form="product-form" disabled={saveMutation.isPending}>
              {saveMutation.isPending
                ? t("common.saving")
                : t("business.products.saveProduct")}
            </Button>
          </>
        }
      >
        <form id="product-form" className="space-y-4" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label={t("common.name")} htmlFor="product-name" required>
              <Input
                id="product-name"
                value={form.name}
                onChange={(event) => setForm({ ...form, name: event.target.value })}
                required
              />
            </Field>
            <Field label={t("business.products.sku")} htmlFor="product-sku" required>
              <Input
                id="product-sku"
                value={form.sku}
                onChange={(event) => setForm({ ...form, sku: event.target.value })}
                required
              />
            </Field>
            <Field label={t("business.products.barcode")} htmlFor="product-barcode">
              <Input
                id="product-barcode"
                value={form.barcode}
                onChange={(event) => setForm({ ...form, barcode: event.target.value })}
              />
            </Field>
            <Field label={t("business.products.category")} htmlFor="product-category">
              <Select
                id="product-category"
                value={form.categoryId}
                onChange={(event) => setForm({ ...form, categoryId: event.target.value })}
              >
                <option value="">{t("business.products.noCategory")}</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t("business.products.unit")} htmlFor="product-unit">
              <Select
                id="product-unit"
                value={form.unit}
                onChange={(event) => setForm({ ...form, unit: event.target.value })}
              >
                {PRODUCT_UNITS.map((unit) => (
                  <option key={unit} value={unit}>
                    {unit}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t("business.products.taxRate")} htmlFor="product-tax" hint={t("business.products.taxRateHint")}>
              <Input
                id="product-tax"
                type="number"
                min="0"
                step="0.01"
                value={form.taxRate}
                onChange={(event) => setForm({ ...form, taxRate: event.target.value })}
              />
            </Field>
            <Field label={t("business.products.costPrice")} htmlFor="product-cost">
              <Input
                id="product-cost"
                type="number"
                min="0"
                step="0.01"
                value={form.costPrice}
                onChange={(event) => setForm({ ...form, costPrice: event.target.value })}
              />
            </Field>
            <Field label={t("business.products.sellingPrice")} htmlFor="product-price">
              <Input
                id="product-price"
                type="number"
                min="0"
                step="0.01"
                value={form.sellingPrice}
                onChange={(event) => setForm({ ...form, sellingPrice: event.target.value })}
              />
            </Field>
            <Field label={t("business.products.lowStockThreshold")} htmlFor="product-threshold">
              <Input
                id="product-threshold"
                type="number"
                min="0"
                step="1"
                value={form.lowStockThreshold}
                onChange={(event) => setForm({ ...form, lowStockThreshold: event.target.value })}
              />
            </Field>
            {!editing ? (
              <Field label={t("business.products.openingStock")} htmlFor="product-opening" hint={t("business.products.stockHint")}>
                <Input
                  id="product-opening"
                  type="number"
                  min="0"
                  step="1"
                  value={form.openingStock}
                  onChange={(event) => setForm({ ...form, openingStock: event.target.value })}
                />
              </Field>
            ) : (
              <Field label={t("business.products.status")} htmlFor="product-status">
                <Select
                  id="product-status"
                  value={form.status}
                  onChange={(event) => setForm({ ...form, status: event.target.value })}
                >
                  <option value="active">{t("business.products.active")}</option>
                  <option value="inactive">{t("business.products.inactive")}</option>
                </Select>
              </Field>
            )}
          </div>

          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={form.trackInventory}
              onChange={(event) =>
                setForm({ ...form, trackInventory: event.target.checked })
              }
              className="h-4 w-4 rounded border-input"
            />
            {t("business.products.trackInventory")}
          </label>

          <Field label={t("common.description")} htmlFor="product-description">
            <Textarea
              id="product-description"
              value={form.description}
              onChange={(event) => setForm({ ...form, description: event.target.value })}
            />
          </Field>

          {formError ? <p className="text-sm text-destructive">{formError}</p> : null}
        </form>
      </Dialog>

      <Dialog
        open={categoriesOpen}
        onClose={() => setCategoriesOpen(false)}
        title={t("business.products.categories")}
        description={t("business.products.manageCategories")}
        size="md"
      >
        <div className="space-y-4">
          {can("product:create") ? (
            <form
              className="flex gap-2"
              onSubmit={(event) => {
                event.preventDefault();
                if (newCategory.trim()) categoryMutation.mutate();
              }}
            >
              <Input
                value={newCategory}
                onChange={(event) => setNewCategory(event.target.value)}
                placeholder={t("business.products.categoryName")}
              />
              <Button type="submit" disabled={categoryMutation.isPending || !newCategory.trim()}>
                <Plus className="h-4 w-4" />
                {t("common.add")}
              </Button>
            </form>
          ) : null}
          {categoryMutation.isError ? (
            <p className="text-sm text-destructive">
              {errorMessage(categoryMutation.error)}
            </p>
          ) : null}
          {deleteCategoryMutation.isError ? (
            <p className="text-sm text-destructive">
              {errorMessage(deleteCategoryMutation.error)}
            </p>
          ) : null}
          <div className="divide-y rounded-lg border">
            {categories.length === 0 ? (
              <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                {t("business.products.noCategories")}
              </p>
            ) : (
              categories.map((category) => (
                <div
                  key={category.id}
                  className="flex items-center justify-between gap-3 px-3 py-2.5"
                >
                  <div>
                    <p className="text-sm font-medium">{category.name}</p>
                    <p className="text-xs text-muted-foreground">
                      {t("business.products.productCount", {
                        count: category.productCount ?? 0,
                      })}
                    </p>
                  </div>
                  {can("product:delete") ? (
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => setCategoryToDelete(category)}
                      aria-label={t("common.delete")}
                    >
                      <Trash2 className="h-4 w-4 text-destructive" />
                    </Button>
                  ) : null}
                </div>
              ))
            )}
          </div>
        </div>
      </Dialog>

      <ConfirmDialog
        open={Boolean(categoryToDelete)}
        onClose={() => setCategoryToDelete(null)}
        onConfirm={() =>
          categoryToDelete && deleteCategoryMutation.mutate(categoryToDelete.id)
        }
        title={t("common.delete")}
        description={categoryToDelete?.name ?? ""}
        confirmLabel={t("common.delete")}
        cancelLabel={t("common.cancel")}
        pending={deleteCategoryMutation.isPending}
      />
    </div>
  );
}
