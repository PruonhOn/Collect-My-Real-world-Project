import { useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Handshake, Pencil, Plus, Search } from "lucide-react";
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
  createSupplier,
  fetchSuppliers,
  updateSupplier,
  type Supplier,
  type SupplierInput,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { formatCents, formatQuantity } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

type SupplierForm = {
  name: string;
  contactName: string;
  phone: string;
  email: string;
  address: string;
  notes: string;
  status: string;
};

const emptyForm: SupplierForm = {
  name: "",
  contactName: "",
  phone: "",
  email: "",
  address: "",
  notes: "",
  status: "active",
};

function toForm(supplier: Supplier): SupplierForm {
  return {
    name: supplier.name,
    contactName: supplier.contactName ?? "",
    phone: supplier.phone ?? "",
    email: supplier.email ?? "",
    address: supplier.address ?? "",
    notes: supplier.notes ?? "",
    status: supplier.status,
  };
}

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

export function SuppliersPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Supplier | null>(null);
  const [form, setForm] = useState<SupplierForm>(emptyForm);
  const [formError, setFormError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  const suppliersQuery = useQuery({
    queryKey: ["suppliers", workspaceId],
    queryFn: () => fetchSuppliers(workspaceId),
  });

  const suppliers = suppliersQuery.data?.suppliers ?? [];

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    if (!term) return suppliers;
    return suppliers.filter((supplier) =>
      [supplier.name, supplier.contactName ?? "", supplier.phone ?? "", supplier.email ?? ""]
        .join(" ")
        .toLowerCase()
        .includes(term),
    );
  }, [suppliers, search]);

  const saveMutation = useMutation({
    mutationFn: (input: SupplierInput) =>
      editing
        ? updateSupplier(workspaceId, editing.id, input)
        : createSupplier(workspaceId, input),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["suppliers", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["purchases", workspaceId] }),
      ]);
      setNotice(
        editing ? t("business.suppliers.updated") : t("business.suppliers.created"),
      );
      setOpen(false);
      setEditing(null);
    },
    onError: (error) => setFormError(errorMessage(error)),
  });

  function openCreate() {
    setEditing(null);
    setForm(emptyForm);
    setFormError(null);
    setOpen(true);
  }

  function openEdit(supplier: Supplier) {
    setEditing(supplier);
    setForm(toForm(supplier));
    setFormError(null);
    setOpen(true);
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    setFormError(null);
    saveMutation.mutate({
      name: form.name.trim(),
      contactName: form.contactName.trim() || undefined,
      phone: form.phone.trim() || undefined,
      email: form.email.trim() || undefined,
      address: form.address.trim() || undefined,
      notes: form.notes.trim() || undefined,
      status: form.status,
    });
  }

  const outstanding = suppliers.reduce(
    (sum, supplier) => sum + (supplier.outstandingCents ?? 0),
    0,
  );

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.suppliers.title")}
        description={t("business.suppliers.subtitle")}
        actions={
          can("supplier:create") ? (
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4" />
              {t("business.suppliers.add")}
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
            <p className="text-xs text-muted-foreground">{t("business.suppliers.title")}</p>
            <p className="mt-1 text-2xl font-bold">{formatQuantity(suppliers.length, locale)}</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">{t("business.suppliers.outstanding")}</p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(outstanding, workspace.defaultCurrency, locale)}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">{t("business.suppliers.totalPurchases")}</p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(
                suppliers.reduce((sum, supplier) => sum + (supplier.totalPurchasesCents ?? 0), 0),
                workspace.defaultCurrency,
                locale,
              )}
            </p>
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
              placeholder={t("business.suppliers.searchPlaceholder")}
              className="pl-9"
            />
          </div>

          {suppliersQuery.isLoading ? (
            <div className="space-y-2">
              {[0, 1, 2, 3].map((index) => (
                <Skeleton key={index} className="h-11 w-full" />
              ))}
            </div>
          ) : suppliersQuery.isError ? (
            <p className="py-10 text-center text-sm text-destructive">
              {errorMessage(suppliersQuery.error)}
            </p>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon={Handshake}
              title={t("business.suppliers.emptyTitle")}
              description={t("business.suppliers.emptyBody")}
              action={
                can("supplier:create") ? (
                  <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" />
                    {t("business.suppliers.add")}
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("common.name")}</TableHead>
                  <TableHead>{t("business.suppliers.contactName")}</TableHead>
                  <TableHead>{t("common.phone")}</TableHead>
                  <TableHead className="text-right">{t("business.suppliers.outstanding")}</TableHead>
                  <TableHead className="text-right">{t("business.suppliers.totalPurchases")}</TableHead>
                  <TableHead>{t("common.status")}</TableHead>
                  <TableHead className="text-right">{t("common.actions")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((supplier) => (
                  <TableRow key={supplier.id}>
                    <TableCell>
                      <div className="font-medium">{supplier.name}</div>
                      <div className="text-xs text-muted-foreground">
                        {supplier.email ?? supplier.address ?? t("business.suppliers.noContact")}
                      </div>
                    </TableCell>
                    <TableCell>{supplier.contactName ?? "—"}</TableCell>
                    <TableCell>{supplier.phone ?? "—"}</TableCell>
                    <TableCell className="text-right font-medium">
                      {formatCents(supplier.outstandingCents ?? 0, workspace.defaultCurrency, locale)}
                    </TableCell>
                    <TableCell className="text-right">
                      <div>{formatCents(supplier.totalPurchasesCents ?? 0, workspace.defaultCurrency, locale)}</div>
                      <div className="text-xs text-muted-foreground">
                        {t("business.suppliers.purchaseCount")}: {supplier.purchaseCount ?? 0}
                      </div>
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={supplier.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {can("supplier:update") ? (
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => openEdit(supplier)}
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
        open={open}
        onClose={() => setOpen(false)}
        title={editing ? t("business.suppliers.edit") : t("business.suppliers.add")}
        size="lg"
        footer={
          <>
            <Button type="button" variant="outline" onClick={() => setOpen(false)} disabled={saveMutation.isPending}>
              {t("common.cancel")}
            </Button>
            <Button type="submit" form="supplier-form" disabled={saveMutation.isPending}>
              {saveMutation.isPending ? t("common.saving") : t("common.save")}
            </Button>
          </>
        }
      >
        <form id="supplier-form" className="space-y-4" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label={t("common.name")} htmlFor="supplier-name" required>
              <Input
                id="supplier-name"
                value={form.name}
                onChange={(event) => setForm({ ...form, name: event.target.value })}
                required
              />
            </Field>
            <Field label={t("business.suppliers.contactName")} htmlFor="supplier-contact">
              <Input
                id="supplier-contact"
                value={form.contactName}
                onChange={(event) => setForm({ ...form, contactName: event.target.value })}
              />
            </Field>
            <Field label={t("common.phone")} htmlFor="supplier-phone">
              <Input
                id="supplier-phone"
                value={form.phone}
                onChange={(event) => setForm({ ...form, phone: event.target.value })}
              />
            </Field>
            <Field label={t("common.email")} htmlFor="supplier-email">
              <Input
                id="supplier-email"
                type="email"
                value={form.email}
                onChange={(event) => setForm({ ...form, email: event.target.value })}
              />
            </Field>
            <Field label={t("common.status")} htmlFor="supplier-status">
              <Select
                id="supplier-status"
                value={form.status}
                onChange={(event) => setForm({ ...form, status: event.target.value })}
              >
                <option value="active">{t("business.products.active")}</option>
                <option value="inactive">{t("business.products.inactive")}</option>
              </Select>
            </Field>
          </div>
          <Field label={t("common.address")} htmlFor="supplier-address">
            <Input
              id="supplier-address"
              value={form.address}
              onChange={(event) => setForm({ ...form, address: event.target.value })}
            />
          </Field>
          <Field label={t("common.notes")} htmlFor="supplier-notes">
            <Textarea
              id="supplier-notes"
              value={form.notes}
              onChange={(event) => setForm({ ...form, notes: event.target.value })}
            />
          </Field>
          {formError ? <p className="text-sm text-destructive">{formError}</p> : null}
        </form>
      </Dialog>
    </div>
  );
}
