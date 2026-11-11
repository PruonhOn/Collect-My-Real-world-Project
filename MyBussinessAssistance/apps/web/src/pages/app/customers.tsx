import { useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Pencil, Plus, Search, Users } from "lucide-react";
import { CUSTOMER_TYPES } from "@mba/shared";
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
  createCustomer,
  fetchCustomers,
  updateCustomer,
  type Customer,
  type CustomerInput,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { formatCents, formatQuantity } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

type CustomerForm = {
  name: string;
  type: string;
  phone: string;
  email: string;
  address: string;
  creditLimit: string;
  notes: string;
  status: string;
};

const emptyForm: CustomerForm = {
  name: "",
  type: "individual",
  phone: "",
  email: "",
  address: "",
  creditLimit: "0",
  notes: "",
  status: "active",
};

function toForm(customer: Customer): CustomerForm {
  return {
    name: customer.name,
    type: customer.type,
    phone: customer.phone ?? "",
    email: customer.email ?? "",
    address: customer.address ?? "",
    creditLimit: (customer.creditLimitCents / 100).toFixed(2),
    notes: customer.notes ?? "",
    status: customer.status,
  };
}

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

export function CustomersPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Customer | null>(null);
  const [form, setForm] = useState<CustomerForm>(emptyForm);
  const [formError, setFormError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  const customersQuery = useQuery({
    queryKey: ["customers", workspaceId],
    queryFn: () => fetchCustomers(workspaceId),
  });

  const customers = customersQuery.data?.customers ?? [];

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    if (!term) return customers;
    return customers.filter((customer) =>
      [customer.name, customer.phone ?? "", customer.email ?? ""]
        .join(" ")
        .toLowerCase()
        .includes(term),
    );
  }, [customers, search]);

  const outstanding = customers.reduce(
    (sum, customer) => sum + (customer.outstandingCents ?? 0),
    0,
  );

  const saveMutation = useMutation({
    mutationFn: (input: CustomerInput) =>
      editing
        ? updateCustomer(workspaceId, editing.id, input)
        : createCustomer(workspaceId, input),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["customers", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["sales", workspaceId] }),
      ]);
      setNotice(
        editing ? t("business.customers.updated") : t("business.customers.created"),
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

  function openEdit(customer: Customer) {
    setEditing(customer);
    setForm(toForm(customer));
    setFormError(null);
    setOpen(true);
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    setFormError(null);
    saveMutation.mutate({
      name: form.name.trim(),
      type: form.type,
      phone: form.phone.trim() || undefined,
      email: form.email.trim() || undefined,
      address: form.address.trim() || undefined,
      creditLimitCents: Math.round(Number(form.creditLimit || 0) * 100),
      notes: form.notes.trim() || undefined,
      status: form.status,
    });
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.customers.title")}
        description={t("business.customers.subtitle")}
        actions={
          can("customer:create") ? (
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4" />
              {t("business.customers.add")}
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
            <p className="text-xs text-muted-foreground">{t("business.customers.title")}</p>
            <p className="mt-1 text-2xl font-bold">{formatQuantity(customers.length, locale)}</p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">{t("business.customers.outstanding")}</p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(outstanding, workspace.defaultCurrency, locale)}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">{t("business.customers.totalSales")}</p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(
                customers.reduce((sum, customer) => sum + (customer.totalSalesCents ?? 0), 0),
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
              placeholder={t("business.customers.searchPlaceholder")}
              className="pl-9"
            />
          </div>

          {customersQuery.isLoading ? (
            <div className="space-y-2">
              {[0, 1, 2, 3].map((index) => (
                <Skeleton key={index} className="h-11 w-full" />
              ))}
            </div>
          ) : customersQuery.isError ? (
            <p className="py-10 text-center text-sm text-destructive">
              {errorMessage(customersQuery.error)}
            </p>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon={Users}
              title={t("business.customers.emptyTitle")}
              description={t("business.customers.emptyBody")}
              action={
                can("customer:create") ? (
                  <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" />
                    {t("business.customers.add")}
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("common.name")}</TableHead>
                  <TableHead>{t("business.customers.type")}</TableHead>
                  <TableHead>{t("common.phone")}</TableHead>
                  <TableHead className="text-right">{t("business.customers.outstanding")}</TableHead>
                  <TableHead className="text-right">{t("business.customers.totalSales")}</TableHead>
                  <TableHead>{t("common.status")}</TableHead>
                  <TableHead className="text-right">{t("common.actions")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((customer) => (
                  <TableRow key={customer.id}>
                    <TableCell>
                      <div className="font-medium">{customer.name}</div>
                      <div className="text-xs text-muted-foreground">
                        {customer.email ?? customer.address ?? t("business.customers.noContact")}
                      </div>
                    </TableCell>
                    <TableCell className="capitalize">{customer.type}</TableCell>
                    <TableCell>{customer.phone ?? "—"}</TableCell>
                    <TableCell className="text-right font-medium">
                      {formatCents(customer.outstandingCents ?? 0, workspace.defaultCurrency, locale)}
                    </TableCell>
                    <TableCell className="text-right">
                      <div>{formatCents(customer.totalSalesCents ?? 0, workspace.defaultCurrency, locale)}</div>
                      <div className="text-xs text-muted-foreground">
                        {t("business.customers.salesCount")}: {customer.saleCount ?? 0}
                      </div>
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={customer.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {can("customer:update") ? (
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => openEdit(customer)}
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
        title={editing ? t("business.customers.edit") : t("business.customers.add")}
        size="lg"
        footer={
          <>
            <Button type="button" variant="outline" onClick={() => setOpen(false)} disabled={saveMutation.isPending}>
              {t("common.cancel")}
            </Button>
            <Button type="submit" form="customer-form" disabled={saveMutation.isPending}>
              {saveMutation.isPending ? t("common.saving") : t("common.save")}
            </Button>
          </>
        }
      >
        <form id="customer-form" className="space-y-4" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label={t("common.name")} htmlFor="customer-name" required>
              <Input
                id="customer-name"
                value={form.name}
                onChange={(event) => setForm({ ...form, name: event.target.value })}
                required
              />
            </Field>
            <Field label={t("business.customers.type")} htmlFor="customer-type">
              <Select
                id="customer-type"
                value={form.type}
                onChange={(event) => setForm({ ...form, type: event.target.value })}
              >
                {CUSTOMER_TYPES.map((type) => (
                  <option key={type} value={type}>
                    {t(`business.customers.${type}`)}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t("common.phone")} htmlFor="customer-phone">
              <Input
                id="customer-phone"
                value={form.phone}
                onChange={(event) => setForm({ ...form, phone: event.target.value })}
              />
            </Field>
            <Field label={t("common.email")} htmlFor="customer-email">
              <Input
                id="customer-email"
                type="email"
                value={form.email}
                onChange={(event) => setForm({ ...form, email: event.target.value })}
              />
            </Field>
            <Field label={t("business.customers.creditLimit")} htmlFor="customer-credit">
              <Input
                id="customer-credit"
                type="number"
                min="0"
                step="0.01"
                value={form.creditLimit}
                onChange={(event) => setForm({ ...form, creditLimit: event.target.value })}
              />
            </Field>
            <Field label={t("common.status")} htmlFor="customer-status">
              <Select
                id="customer-status"
                value={form.status}
                onChange={(event) => setForm({ ...form, status: event.target.value })}
              >
                <option value="active">{t("business.products.active")}</option>
                <option value="inactive">{t("business.products.inactive")}</option>
              </Select>
            </Field>
          </div>
          <Field label={t("common.address")} htmlFor="customer-address">
            <Input
              id="customer-address"
              value={form.address}
              onChange={(event) => setForm({ ...form, address: event.target.value })}
            />
          </Field>
          <Field label={t("common.notes")} htmlFor="customer-notes">
            <Textarea
              id="customer-notes"
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
