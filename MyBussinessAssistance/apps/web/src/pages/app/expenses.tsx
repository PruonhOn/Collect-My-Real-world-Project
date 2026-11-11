import { useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Plus, Receipt, Search, Trash2 } from "lucide-react";
import { EXPENSE_CATEGORIES, PAYMENT_METHODS } from "@mba/shared";
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
  createExpense,
  fetchBranches,
  fetchExpenses,
  voidExpense,
  type Expense,
  type ExpenseInput,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { formatDate } from "@/lib/format";
import { fromDateInput, todayInput } from "@/lib/date-input";
import { formatCents } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

type ExpenseForm = {
  category: string;
  description: string;
  amount: string;
  paymentMethod: string;
  branchId: string;
  vendor: string;
  reference: string;
  expenseDate: string;
  note: string;
};

const emptyForm: ExpenseForm = {
  category: "other",
  description: "",
  amount: "0",
  paymentMethod: "cash",
  branchId: "",
  vendor: "",
  reference: "",
  expenseDate: todayInput(),
  note: "",
};

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

export function ExpensesPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [categoryFilter, setCategoryFilter] = useState("");
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState<ExpenseForm>(emptyForm);
  const [formError, setFormError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [expenseToVoid, setExpenseToVoid] = useState<Expense | null>(null);

  const expensesQuery = useQuery({
    queryKey: ["expenses", workspaceId],
    queryFn: () => fetchExpenses(workspaceId),
  });
  const branchesQuery = useQuery({
    queryKey: ["branches", workspaceId],
    queryFn: () => fetchBranches(workspaceId),
    retry: false,
  });

  const expenses = expensesQuery.data?.expenses ?? [];
  const branches = branchesQuery.data?.branches ?? [];

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return expenses.filter((expense) => {
      if (categoryFilter && expense.category !== categoryFilter) return false;
      if (!term) return true;
      return [expense.description, expense.vendor ?? "", expense.reference ?? ""]
        .join(" ")
        .toLowerCase()
        .includes(term);
    });
  }, [expenses, search, categoryFilter]);

  const total = expenses
    .filter((expense) => expense.status !== "void")
    .reduce((sum, expense) => sum + expense.amountCents, 0);

  const createMutation = useMutation({
    mutationFn: (input: ExpenseInput) => createExpense(workspaceId, input),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["expenses", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["dashboard", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["reports", workspaceId] }),
      ]);
      setNotice(t("business.expenses.created"));
      setOpen(false);
      setForm(emptyForm);
    },
    onError: (error) => setFormError(errorMessage(error)),
  });

  const voidMutation = useMutation({
    mutationFn: (expenseId: string) => voidExpense(workspaceId, expenseId),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ["expenses", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["dashboard", workspaceId] }),
        queryClient.invalidateQueries({ queryKey: ["reports", workspaceId] }),
      ]);
      setNotice(t("business.expenses.voided"));
      setExpenseToVoid(null);
    },
  });

  function openCreate() {
    setForm({ ...emptyForm, branchId: branches.find((branch) => branch.isDefault)?.id ?? "" });
    setFormError(null);
    setOpen(true);
  }

  function submit(event: FormEvent) {
    event.preventDefault();
    setFormError(null);
    createMutation.mutate({
      category: form.category,
      description: form.description.trim(),
      amountCents: Math.round(Number(form.amount || 0) * 100),
      paymentMethod: form.paymentMethod,
      branchId: form.branchId || null,
      vendor: form.vendor.trim() || undefined,
      reference: form.reference.trim() || undefined,
      expenseDate: fromDateInput(form.expenseDate),
      note: form.note.trim() || undefined,
    });
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("business.expenses.title")}
        description={t("business.expenses.subtitle")}
        actions={
          can("expense:create") ? (
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4" />
              {t("business.expenses.add")}
            </Button>
          ) : undefined
        }
      />

      {notice ? (
        <div className="rounded-lg border border-success/30 bg-success/10 px-4 py-2.5 text-sm text-success">
          {notice}
        </div>
      ) : null}

      <Card>
        <CardContent className="flex items-center justify-between p-5">
          <div>
            <p className="text-sm text-muted-foreground">{t("business.expenses.total")}</p>
            <p className="mt-1 text-2xl font-bold">
              {formatCents(total, workspace.defaultCurrency, locale)}
            </p>
          </div>
          <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-destructive/10 text-destructive">
            <Receipt className="h-5 w-5" />
          </span>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="space-y-4 p-4 sm:p-5">
          <div className="flex flex-col gap-2 sm:flex-row">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder={t("business.expenses.searchPlaceholder")}
                className="pl-9"
              />
            </div>
            <Select
              value={categoryFilter}
              onChange={(event) => setCategoryFilter(event.target.value)}
              className="sm:w-48"
            >
              <option value="">{t("common.all")}</option>
              {EXPENSE_CATEGORIES.map((category) => (
                <option key={category} value={category}>
                  {t(`business.expenses.categories.${category}`)}
                </option>
              ))}
            </Select>
          </div>

          {expensesQuery.isLoading ? (
            <div className="space-y-2">
              {[0, 1, 2, 3].map((index) => (
                <Skeleton key={index} className="h-11 w-full" />
              ))}
            </div>
          ) : expensesQuery.isError ? (
            <p className="py-10 text-center text-sm text-destructive">
              {errorMessage(expensesQuery.error)}
            </p>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon={Receipt}
              title={t("business.expenses.emptyTitle")}
              description={t("business.expenses.emptyBody")}
              action={
                can("expense:create") ? (
                  <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" />
                    {t("business.expenses.add")}
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("business.expenses.description")}</TableHead>
                  <TableHead>{t("business.expenses.category")}</TableHead>
                  <TableHead>{t("business.expenses.vendor")}</TableHead>
                  <TableHead>{t("business.inventory.branch")}</TableHead>
                  <TableHead>{t("common.date")}</TableHead>
                  <TableHead className="text-right">{t("common.amount")}</TableHead>
                  <TableHead>{t("common.status")}</TableHead>
                  <TableHead className="text-right">{t("common.actions")}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((expense) => (
                  <TableRow key={expense.id}>
                    <TableCell>
                      <div className="font-medium">{expense.description}</div>
                      <div className="text-xs text-muted-foreground">
                        {expense.reference ?? expense.paymentMethod}
                      </div>
                    </TableCell>
                    <TableCell>{t(`business.expenses.categories.${expense.category}`)}</TableCell>
                    <TableCell>{expense.vendor ?? "â€”"}</TableCell>
                    <TableCell>{expense.branchName ?? t("business.expenses.noBranch")}</TableCell>
                    <TableCell>{formatDate(expense.expenseDate, locale)}</TableCell>
                    <TableCell className="text-right font-medium">
                      {formatCents(expense.amountCents, expense.currency, locale)}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={expense.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {expense.status !== "void" && can("expense:delete") ? (
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => setExpenseToVoid(expense)}
                          aria-label={t("business.expenses.voidAction")}
                        >
                          <Trash2 className="h-4 w-4 text-destructive" />
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
        title={t("business.expenses.add")}
        size="lg"
        footer={
          <>
            <Button type="button" variant="outline" onClick={() => setOpen(false)} disabled={createMutation.isPending}>
              {t("common.cancel")}
            </Button>
            <Button type="submit" form="expense-form" disabled={createMutation.isPending}>
              {createMutation.isPending ? t("common.saving") : t("common.save")}
            </Button>
          </>
        }
      >
        <form id="expense-form" className="space-y-4" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label={t("business.expenses.description")} htmlFor="expense-description" required>
              <Input
                id="expense-description"
                value={form.description}
                onChange={(event) => setForm({ ...form, description: event.target.value })}
                required
              />
            </Field>
            <Field label={t("business.expenses.category")} htmlFor="expense-category">
              <Select
                id="expense-category"
                value={form.category}
                onChange={(event) => setForm({ ...form, category: event.target.value })}
              >
                {EXPENSE_CATEGORIES.map((category) => (
                  <option key={category} value={category}>
                    {t(`business.expenses.categories.${category}`)}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t("business.expenses.amount")} htmlFor="expense-amount" required>
              <Input
                id="expense-amount"
                type="number"
                min="0"
                step="0.01"
                value={form.amount}
                onChange={(event) => setForm({ ...form, amount: event.target.value })}
                required
              />
            </Field>
            <Field label={t("business.expenses.expenseDate")} htmlFor="expense-date">
              <Input
                id="expense-date"
                type="date"
                value={form.expenseDate}
                onChange={(event) => setForm({ ...form, expenseDate: event.target.value })}
              />
            </Field>
            <Field label={t("business.expenses.paymentMethod")} htmlFor="expense-method">
              <Select
                id="expense-method"
                value={form.paymentMethod}
                onChange={(event) => setForm({ ...form, paymentMethod: event.target.value })}
              >
                {PAYMENT_METHODS.map((method) => (
                  <option key={method} value={method}>
                    {t(`business.payments.${method}`)}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t("business.inventory.branch")} htmlFor="expense-branch">
              <Select
                id="expense-branch"
                value={form.branchId}
                onChange={(event) => setForm({ ...form, branchId: event.target.value })}
              >
                <option value="">{t("business.expenses.noBranch")}</option>
                {branches.map((branch) => (
                  <option key={branch.id} value={branch.id}>
                    {branch.name}
                  </option>
                ))}
              </Select>
            </Field>
            <Field label={t("business.expenses.vendor")} htmlFor="expense-vendor">
              <Input
                id="expense-vendor"
                value={form.vendor}
                onChange={(event) => setForm({ ...form, vendor: event.target.value })}
              />
            </Field>
            <Field label={t("business.expenses.reference")} htmlFor="expense-reference">
              <Input
                id="expense-reference"
                value={form.reference}
                onChange={(event) => setForm({ ...form, reference: event.target.value })}
              />
            </Field>
          </div>
          <Field label={t("common.notes")} htmlFor="expense-note">
            <Textarea
              id="expense-note"
              value={form.note}
              onChange={(event) => setForm({ ...form, note: event.target.value })}
            />
          </Field>
          {formError ? <p className="text-sm text-destructive">{formError}</p> : null}
        </form>
      </Dialog>

      <ConfirmDialog
        open={Boolean(expenseToVoid)}
        onClose={() => setExpenseToVoid(null)}
        onConfirm={() => expenseToVoid && voidMutation.mutate(expenseToVoid.id)}
        title={t("business.expenses.voidTitle")}
        description={t("business.expenses.voidBody")}
        confirmLabel={t("business.expenses.voidAction")}
        cancelLabel={t("common.cancel")}
        pending={voidMutation.isPending}
      />
    </div>
  );
}
