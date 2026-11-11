import { useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Pencil, Plus, Power, Search, UserCog } from "lucide-react";
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
  createEmployee,
  deactivateEmployee,
  fetchBranches,
  fetchEmployees,
  updateEmployee,
  type Employee,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { fromDateInput, toDateInput } from "@/lib/date-input";
import { formatDate } from "@/lib/format";
import { formatCents, formatQuantity } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

type EmployeeForm = {
  name: string;
  branchId: string;
  phone: string;
  email: string;
  position: string;
  department: string;
  salary: string;
  currency: string;
  hireDate: string;
  notes: string;
  status: string;
};

const emptyForm: EmployeeForm = {
  name: "",
  branchId: "",
  phone: "",
  email: "",
  position: "",
  department: "",
  salary: "0",
  currency: "USD",
  hireDate: "",
  notes: "",
  status: "active",
};

function toForm(employee: Employee): EmployeeForm {
  return {
    name: employee.name,
    branchId: employee.branchId ?? "",
    phone: employee.phone ?? "",
    email: employee.email ?? "",
    position: employee.position ?? "",
    department: employee.department ?? "",
    salary: (employee.salaryCents / 100).toFixed(2),
    currency: employee.currency,
    hireDate: employee.hireDate ? toDateInput(employee.hireDate) : "",
    notes: employee.notes ?? "",
    status: employee.status,
  };
}

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

export function EmployeesPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Employee | null>(null);
  const [form, setForm] = useState<EmployeeForm>(emptyForm);
  const [formError, setFormError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [deactivating, setDeactivating] = useState<Employee | null>(null);

  const employeesQuery = useQuery({
    queryKey: ["employees", workspaceId],
    queryFn: () => fetchEmployees(workspaceId),
  });
  const branchesQuery = useQuery({
    queryKey: ["branches", workspaceId],
    queryFn: () => fetchBranches(workspaceId),
    retry: false,
  });

  const employees = employeesQuery.data?.employees ?? [];
  const branches = branchesQuery.data?.branches ?? [];

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    if (!term) return employees;
    return employees.filter((employee) =>
      [
        employee.name,
        employee.phone ?? "",
        employee.email ?? "",
        employee.position ?? "",
        employee.department ?? "",
      ]
        .join(" ")
        .toLowerCase()
        .includes(term),
    );
  }, [employees, search]);

  const saveMutation = useMutation({
    mutationFn: () => {
      const text = (value: string) => {
        const trimmed = value.trim();
        return trimmed || (editing ? null : undefined);
      };
      const input = {
        name: form.name.trim(),
        branchId: form.branchId || null,
        phone: text(form.phone),
        email: text(form.email),
        position: text(form.position),
        department: text(form.department),
        salaryCents: Math.round(Number(form.salary || 0) * 100),
        currency: form.currency,
        hireDate: fromDateInput(form.hireDate) ?? null,
        notes: text(form.notes),
        status: form.status,
      };
      return editing
        ? updateEmployee(workspaceId, editing.id, input)
        : createEmployee(workspaceId, input);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["employees", workspaceId],
      });
      setNotice(
        editing
          ? t("business.employees.updated")
          : t("business.employees.created"),
      );
      setOpen(false);
      setEditing(null);
    },
    onError: (error) => setFormError(errorMessage(error)),
  });

  const deactivateMutation = useMutation({
    mutationFn: (employeeId: string) =>
      deactivateEmployee(workspaceId, employeeId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["employees", workspaceId],
      });
      setNotice(t("business.employees.deactivated"));
      setDeactivating(null);
    },
  });

  function openCreate() {
    setEditing(null);
    setForm({
      ...emptyForm,
      branchId: branches.find((branch) => branch.isDefault)?.id ?? "",
      currency: workspace.defaultCurrency,
    });
    setFormError(null);
    setOpen(true);
  }

  function openEdit(employee: Employee) {
    setEditing(employee);
    setForm(toForm(employee));
    setFormError(null);
    setOpen(true);
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    setFormError(null);
    saveMutation.mutate();
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.employees.title")}
        description={t("business.employees.subtitle")}
        actions={
          can("employee:create") ? (
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4" />
              {t("business.employees.add")}
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
            <p className="text-xs text-muted-foreground">
              {t("business.employees.title")}
            </p>
            <p className="mt-1 text-2xl font-bold">
              {formatQuantity(employeesQuery.data?.summary.count ?? 0, locale)}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">
              {t("business.employees.activeCount")}
            </p>
            <p className="mt-1 text-2xl font-bold">
              {formatQuantity(
                employeesQuery.data?.summary.activeCount ?? 0,
                locale,
              )}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">
              {t("business.employees.monthlySalary")}
            </p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(
                employeesQuery.data?.summary.monthlySalaryCents ?? 0,
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
              placeholder={t("business.employees.searchPlaceholder")}
              className="pl-9"
            />
          </div>

          {employeesQuery.isLoading ? (
            <div className="space-y-2">
              {[0, 1, 2, 3].map((index) => (
                <Skeleton key={index} className="h-11 w-full" />
              ))}
            </div>
          ) : employeesQuery.isError ? (
            <p className="py-10 text-center text-sm text-destructive">
              {errorMessage(employeesQuery.error)}
            </p>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon={UserCog}
              title={t("business.employees.emptyTitle")}
              description={t("business.employees.emptyBody")}
              action={
                can("employee:create") ? (
                  <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" />
                    {t("business.employees.add")}
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("common.name")}</TableHead>
                  <TableHead>{t("business.employees.position")}</TableHead>
                  <TableHead>{t("business.inventory.branch")}</TableHead>
                  <TableHead>{t("common.phone")}</TableHead>
                  <TableHead className="text-right">
                    {t("business.employees.salary")}
                  </TableHead>
                  <TableHead>{t("common.status")}</TableHead>
                  <TableHead className="text-right">
                    {t("common.actions")}
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((employee) => (
                  <TableRow key={employee.id}>
                    <TableCell>
                      <div className="font-medium">{employee.name}</div>
                      <div className="text-xs text-muted-foreground">
                        {employee.email ??
                          employee.department ??
                          t("business.customers.noContact")}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div>{employee.position ?? "—"}</div>
                      {employee.department ? (
                        <div className="text-xs text-muted-foreground">
                          {employee.department}
                        </div>
                      ) : null}
                    </TableCell>
                    <TableCell>{employee.branchName ?? "—"}</TableCell>
                    <TableCell>{employee.phone ?? "—"}</TableCell>
                    <TableCell className="text-right font-medium">
                      {formatCents(
                        employee.salaryCents,
                        employee.currency,
                        locale,
                      )}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={employee.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {can("employee:update") ? (
                        <div className="flex justify-end gap-1">
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => openEdit(employee)}
                            aria-label={t("common.edit")}
                          >
                            <Pencil className="h-4 w-4" />
                          </Button>
                          {can("employee:delete") &&
                          employee.status === "active" ? (
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => setDeactivating(employee)}
                              aria-label={t("business.employees.deactivate")}
                            >
                              <Power className="h-4 w-4" />
                            </Button>
                          ) : null}
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
        title={
          editing ? t("business.employees.edit") : t("business.employees.add")
        }
        size="lg"
        footer={
          <>
            <Button
              type="button"
              variant="outline"
              onClick={() => setOpen(false)}
              disabled={saveMutation.isPending}
            >
              {t("common.cancel")}
            </Button>
            <Button
              type="submit"
              form="employee-form"
              disabled={saveMutation.isPending}
            >
              {saveMutation.isPending ? t("common.saving") : t("common.save")}
            </Button>
          </>
        }
      >
        <form id="employee-form" className="space-y-4" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label={t("common.name")} htmlFor="employee-name" required>
              <Input
                id="employee-name"
                value={form.name}
                onChange={(event) =>
                  setForm({ ...form, name: event.target.value })
                }
                required
              />
            </Field>
            <Field
              label={t("business.employees.position")}
              htmlFor="employee-position"
            >
              <Input
                id="employee-position"
                value={form.position}
                onChange={(event) =>
                  setForm({ ...form, position: event.target.value })
                }
              />
            </Field>
            <Field
              label={t("business.employees.department")}
              htmlFor="employee-department"
            >
              <Input
                id="employee-department"
                value={form.department}
                onChange={(event) =>
                  setForm({ ...form, department: event.target.value })
                }
              />
            </Field>
            <Field
              label={t("business.inventory.branch")}
              htmlFor="employee-branch"
            >
              <Select
                id="employee-branch"
                value={form.branchId}
                onChange={(event) =>
                  setForm({ ...form, branchId: event.target.value })
                }
              >
                <option value="">{t("business.employees.noBranch")}</option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t("common.phone")} htmlFor="employee-phone">
              <Input
                id="employee-phone"
                value={form.phone}
                onChange={(event) =>
                  setForm({ ...form, phone: event.target.value })
                }
              />
            </Field>
            <Field label={t("common.email")} htmlFor="employee-email">
              <Input
                id="employee-email"
                type="email"
                value={form.email}
                onChange={(event) =>
                  setForm({ ...form, email: event.target.value })
                }
              />
            </Field>
            <Field
              label={t("business.employees.salary")}
              htmlFor="employee-salary"
            >
              <Input
                id="employee-salary"
                type="number"
                min="0"
                step="0.01"
                value={form.salary}
                onChange={(event) =>
                  setForm({ ...form, salary: event.target.value })
                }
              />
            </Field>
            <Field
              label={t("business.employees.currency")}
              htmlFor="employee-currency"
            >
              <Select
                id="employee-currency"
                value={form.currency}
                onChange={(event) =>
                  setForm({ ...form, currency: event.target.value })
                }
              >
                <option value="USD">USD</option>
                <option value="KHR">KHR</option>
              </Select>
            </Field>
            <Field
              label={t("business.employees.hireDate")}
              htmlFor="employee-hire-date"
            >
              <Input
                id="employee-hire-date"
                type="date"
                value={form.hireDate}
                onChange={(event) =>
                  setForm({ ...form, hireDate: event.target.value })
                }
              />
            </Field>
            <Field label={t("common.status")} htmlFor="employee-status">
              <Select
                id="employee-status"
                value={form.status}
                onChange={(event) =>
                  setForm({ ...form, status: event.target.value })
                }
              >
                <option value="active">
                  {t("business.products.active")}
                </option>
                <option value="inactive">
                  {t("business.products.inactive")}
                </option>
              </Select>
            </Field>
          </div>
          <Field label={t("common.notes")} htmlFor="employee-notes">
            <Textarea
              id="employee-notes"
              value={form.notes}
              onChange={(event) =>
                setForm({ ...form, notes: event.target.value })
              }
            />
          </Field>
          {formError ? (
            <p className="text-sm text-destructive">{formError}</p>
          ) : null}
        </form>
      </Dialog>

      <ConfirmDialog
        open={Boolean(deactivating)}
        onClose={() => setDeactivating(null)}
        onConfirm={() => {
          if (deactivating) deactivateMutation.mutate(deactivating.id);
        }}
        title={t("business.employees.deactivateTitle")}
        description={t("business.employees.deactivateBody", {
          name: deactivating?.name ?? "",
        })}
        confirmLabel={t("business.employees.deactivate")}
        cancelLabel={t("common.cancel")}
        pending={deactivateMutation.isPending}
      />
    </div>
  );
}
