import { useMemo, useState, type ChangeEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { ArrowLeft, Calendar, Clock, Plus, Trash2, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Dialog } from "@/components/ui/dialog";
import { Field } from "@/components/ui/field";
import { EmptyState } from "@/components/app/empty-state";
import { PageHeader } from "@/components/app/page-header";
import {
  cancelPayrollRun,
  createPayrollRun,
  fetchBranches,
  fetchEmployees,
  fetchPayrollRuns,
  payPayrollRun,
} from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { formatCents, inputToCents, centsToInput } from "@/lib/money";
import { formatDate } from "@/lib/format";
import { useBusinessContext } from "@/lib/use-business";

type RunStatus = "draft" | "partial" | "paid" | "cancelled";

const STATUS_I18N: Record<RunStatus, string> = {
  draft: "business.payroll.statusDraft",
  partial: "business.payroll.statusPartial",
  paid: "business.payroll.statusPaid",
  cancelled: "business.payroll.statusCancelled",
};

function errorMessage(error: unknown) {
  return error instanceof Error ? error.message : "Unable to load payroll runs.";
}

function toLocalDateTimeInput(value: number) {
  const date = new Date(value);
  const pad = (part: number) => String(part).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(
    date.getDate(),
  )}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

type BadgeVariant =
  | "default"
  | "secondary"
  | "outline"
  | "destructive"
  | "accent"
  | "success";

const STATUS_VARIANT: Record<RunStatus, BadgeVariant> = {
  draft: "default",
  partial: "accent",
  paid: "success",
  cancelled: "destructive",
};

function statusColor(status: string): BadgeVariant {
  return STATUS_VARIANT[status as RunStatus] ?? "default";
}

function monthBounds() {
  const now = Date.now();
  const d = new Date(now);
  const start = new Date(d.getFullYear(), d.getMonth(), 1).getTime();
  const end = new Date(
    d.getFullYear(),
    d.getMonth() + 1,
    0,
    23,
    59,
    59,
    999,
  ).getTime();
  return { start, end };
}

type PayrollItemInput = {
  employeeId: string;
  salaryCents: number;
  allowancesCents: number;
  deductionsCents: number;
};

export function PayrollPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, workspace, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const canCreate = can("payroll:create");
  const canUpdate = can("payroll:update");
  const canDelete = can("payroll:delete");

  const { start: periodStart, end: periodEnd } = monthBounds();

  const [filterStatus, setFilterStatus] = useState<RunStatus | "">("");
  const [openCreate, setOpenCreate] = useState(false);
  const [openPay, setOpenPay] = useState(false);
  const [selectedRun, setSelectedRun] = useState<{
    id: string;
    number: string;
    status: string;
    totalCents: number;
    paidCents: number;
  } | null>(null);
  const [error, setError] = useState<string | null>(null);
  const queryClient = useQueryClient();

  const branchesQuery = useQuery({
    queryKey: ["payroll-branches", workspaceId],
    queryFn: () => fetchBranches(workspaceId),
    enabled: Boolean(workspaceId),
  });
  const branches = branchesQuery.data?.branches ?? [];

  const employeesQuery = useQuery({
    queryKey: ["payroll-employees", workspaceId],
    queryFn: () => fetchEmployees(workspaceId),
    enabled: Boolean(workspaceId),
  });
  const employees = (employeesQuery.data?.employees ?? []).filter(
    (e) => e.status === "active",
  );

  const runsQuery = useQuery({
    queryKey: ["payroll-runs", workspaceId, filterStatus],
    queryFn: () =>
      fetchPayrollRuns(workspaceId, {
        status: filterStatus || undefined,
      }),
    enabled: Boolean(workspaceId),
  });
  const runs = runsQuery.data?.runs ?? [];

  // Create form state
  const [createBranchId, setCreateBranchId] = useState<string | null>(
    branches[0]?.id ?? null,
  );
  const [createPeriodStart, setCreatePeriodStart] = useState(periodStart);
  const [createPeriodEnd, setCreatePeriodEnd] = useState(periodEnd);
  const [createNote, setCreateNote] = useState("");
  const [createItems, setCreateItems] = useState<PayrollItemInput[]>([]);

  // Pay dialog state
  const [payAmount, setPayAmount] = useState("");

  const createTotalCents = useMemo(
    () =>
      createItems.reduce(
        (sum, item) =>
          sum +
          Math.max(
            0,
            item.salaryCents + item.allowancesCents - item.deductionsCents,
          ),
        0,
      ),
    [createItems],
  );


  const createRunMutation = useMutation({
    mutationFn: () =>
      createPayrollRun(workspaceId, {
        branchId: createBranchId,
        periodStart: createPeriodStart,
        periodEnd: createPeriodEnd,
        note: createNote || undefined,
        items: createItems.map((item) => ({
          employeeId: item.employeeId,
          salaryCents: item.salaryCents,
          allowancesCents: item.allowancesCents,
          deductionsCents: item.deductionsCents,
        })),
      }),
    onSuccess: () => {
      void queryClient.invalidateQueries({
        queryKey: ["payroll-runs", workspaceId],
      });
      setOpenCreate(false);
      resetCreateForm();
    },
    onError: (cause: unknown) => {
      setError(
        cause instanceof ApiClientError
          ? cause.message
          : "Unable to create payroll run.",
      );
    },
  });

  const payRunMutation = useMutation({
    mutationFn: () =>
      payPayrollRun(workspaceId, selectedRun!.id, inputToCents(payAmount)),
    onSuccess: () => {
      void queryClient.invalidateQueries({
        queryKey: ["payroll-runs", workspaceId],
      });
      setOpenPay(false);
      setSelectedRun(null);
    },
    onError: (cause: unknown) => {
      setError(
        cause instanceof ApiClientError
          ? cause.message
          : "Unable to record payment.",
      );
    },
  });

  const cancelRunMutation = useMutation({
    mutationFn: (runId: string) => cancelPayrollRun(workspaceId, runId),
    onSuccess: () => {
      void queryClient.invalidateQueries({
        queryKey: ["payroll-runs", workspaceId],
      });
      setSelectedRun(null);
    },
    onError: (cause: unknown) =>
      setError(
        cause instanceof ApiClientError ? cause.message : "Unable to cancel.",
      ),
  });

  const resetCreateForm = () => {
    setCreateBranchId(branches[0]?.id ?? null);
    setCreatePeriodStart(periodStart);
    setCreatePeriodEnd(periodEnd);
    setCreateNote("");
    setCreateItems([]);
    setError(null);
  };

  const addEmployeeToRun = (employeeId: string) => {
    if (createItems.some((item) => item.employeeId === employeeId)) return;
    const employee = employees.find((e) => e.id === employeeId);
    setCreateItems([
      ...createItems,
      {
        employeeId,
        salaryCents: employee?.salaryCents ?? 0,
        allowancesCents: 0,
        deductionsCents: 0,
      },
    ]);
  };

  const removeItem = (employeeId: string) => {
    setCreateItems(createItems.filter((item) => item.employeeId !== employeeId));
  };

  const updateItem = (
    employeeId: string,
    field: "salaryCents" | "allowancesCents" | "deductionsCents",
    value: string,
  ) => {
    setCreateItems(
      createItems.map((item) =>
        item.employeeId === employeeId
          ? { ...item, [field]: Math.max(0, inputToCents(value)) }
          : item,
      ),
    );
  };

  const handleView = (run: {
    id: string;
    number: string;
    status: string;
    totalCents: number;
    paidCents: number;
  }) => {
    setSelectedRun(run);
    setPayAmount("");
    setOpenPay(true);
  };

  const handleCancel = (run: { id: string }) => {
    if (
      !window.confirm(
        t("business.payroll.confirmCancel", {
          defaultMessage: "Cancel this payroll run?",
        }),
      )
    )
      return;
    void cancelRunMutation.mutate(run.id);
  };

  return (
    <div className="space-y-6">
      <PageHeader
        title={t("nav.payroll")}
        description={t("business.payroll.subtitle")}
        actions={
          canCreate ? (
            <Button
              onClick={() => {
                setOpenCreate(true);
                resetCreateForm();
              }}
            >
              <Plus className="h-4 w-4" />
              {t("business.payroll.newRun")}
            </Button>
          ) : undefined
        }
      />

      {error ? (
        <Card>
          <CardContent className="p-4 text-sm text-destructive">{error}</CardContent>
        </Card>
      ) : null}

      <div className="grid gap-4 lg:grid-cols-12">
        <div className="lg:col-span-12 space-y-4">
          <Card>
            <CardContent className="p-4 sm:p-5">
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <Select
                  value={filterStatus}
                  onChange={(event) =>
                    setFilterStatus(
                      (event.target.value as RunStatus | "") ?? "",
                    )
                  }
                  className="w-full sm:w-48"
                >
                  <option value="">{t("business.payroll.allStatuses")}</option>
                  {Object.entries(STATUS_I18N).map(([key, labelKey]) => (
                     <option key={key} value={key}>{t(labelKey)}</option>
                   ))}
                </Select>
              </div>

              {runsQuery.isLoading ? (
                <div className="space-y-2">
                  {[0, 1, 2].map((index) => (
                    <div key={index} className="h-11 w-full animate-pulse rounded-md bg-secondary" />
                  ))}
                </div>
              ) : runsQuery.isError ? (
                <div className="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-6 text-center text-sm text-destructive">
                  {errorMessage(runsQuery.error)}
                </div>
              ) : runs.length === 0 ? (
                <EmptyState
                  icon={Clock}
                  title={t("business.payroll.emptyTitle")}
                  description={t("business.payroll.emptyBody")}
                />
              ) : (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>{t("business.payroll.number")}</TableHead>
                      <TableHead>{t("business.payroll.period")}</TableHead>
                      <TableHead className="text-right">
                        {t("common.total")}
                      </TableHead>
                      <TableHead className="text-right">{t("common.paid")}</TableHead>
                      <TableHead>{t("common.status")}</TableHead>
                      <TableHead className="text-right">
                        {t("common.actions")}
                      </TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {runs.map((run) => (
                      <TableRow key={run.id}>
                        <TableCell className="font-mono text-xs font-medium">
                          {run.number}
                        </TableCell>
                        <TableCell className="text-sm">
                          {formatDate(run.periodStart, locale)} –{" "}
                          {formatDate(run.periodEnd, locale)}
                        </TableCell>
                        <TableCell className="text-right text-sm font-medium">
                          {formatCents(
                            run.totalCents,
                            workspace.defaultCurrency,
                            locale,
                          )}
                        </TableCell>
                        <TableCell className="text-right text-sm font-medium">
                          {formatCents(
                            run.paidCents,
                            workspace.defaultCurrency,
                            locale,
                          )}
                        </TableCell>
                        <TableCell>
                          <Badge variant={statusColor(run.status)}>
                            {t(STATUS_I18N[run.status as RunStatus] ?? "business.payroll.statusDraft")}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-right">
                          <div className="flex items-center gap-1">
                            <Button
                              size="icon"
                              variant="outline"
                              onClick={() => handleView(run)}
                            >
                              <Plus className="h-3 w-3" />
                            </Button>
                            {canUpdate &&
                            run.status === "draft" ? (
                              <Button
                                size="icon"
                                variant="outline"
                                onClick={() => {
                                  setSelectedRun(run);
                                  setPayAmount("");
                                  setOpenPay(true);
                                }}
                              >
                                <Calendar className="h-3 w-3" />
                              </Button>
                            ) : null}
                            {canDelete &&
                            run.status !== "paid" ? (
                              <Button
                                size="icon"
                                variant="destructive"
                                onClick={() => handleCancel(run)}
                              >
                                <Trash2 className="h-3 w-3" />
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
        </div>
      </div>

      {/* Pay dialog */}
      {selectedRun && (
        <Dialog
          open
          onClose={() => setSelectedRun(null)}
          title={t("business.payroll.runTitle", {
            number: selectedRun.number,
          })}
          size="sm"
        >
          <div className="p-6 space-y-4">
            <div className="grid grid-cols-2 gap-4 text-sm">
              <div>
                <p className="text-xs text-muted-foreground">
                  {t("business.payroll.status")}
                </p>
                <p className="font-medium capitalize">
                  {selectedRun.status}
                </p>
              </div>
              <div className="text-right">
                <p className="text-xs text-muted-foreground">
                  {t("business.payroll.total")}
                </p>
                <p className="font-semibold">
                  {formatCents(
                    selectedRun.totalCents,
                    workspace.defaultCurrency,
                    locale,
                  )}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">
                  {t("business.payroll.paid")}
                </p>
                <p className="font-medium">
                  {formatCents(
                    selectedRun.paidCents,
                    workspace.defaultCurrency,
                    locale,
                  )}
                </p>
              </div>
              <div className="text-right">
                <p className="text-xs text-muted-foreground">
                  {t("business.payroll.balance")}
                </p>
                <p className="font-medium">
                  {formatCents(
                    selectedRun.totalCents - selectedRun.paidCents,
                    workspace.defaultCurrency,
                    locale,
                  )}
                </p>
              </div>
            </div>

            {selectedRun.status === "draft" && (
              <div>
                <Field
                  label={t("business.payroll.amountPaid")}
                  htmlFor="payroll-amount"
                >
                  <Input
                    id="payroll-amount"
                    type="number"
                    step="0.01"
                    min="0"
                    value={payAmount}
                    onChange={(event: ChangeEvent<HTMLInputElement>) =>
                      setPayAmount(event.target.value)
                    }
                    placeholder="0.00"
                    className="w-full"
                  />
                </Field>
              </div>
            )}

            <div className="flex justify-end gap-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => setSelectedRun(null)}
                disabled={payRunMutation.isPending || cancelRunMutation.isPending}
              >
                {t("common.cancel")}
              </Button>
              {selectedRun.status === "draft" ? (
                <Button
                  onClick={() => void payRunMutation.mutate()}
                  disabled={
                    payRunMutation.isPending ||
                    !Number.isFinite(inputToCents(payAmount)) ||
                    inputToCents(payAmount) <= 0
                  }
                >
                  {payRunMutation.isPending ? (
                    <Clock className="h-4 w-4 animate-spin" />
                  ) : (
                    <ArrowLeft className="h-4 w-4" />
                  )}
                  <span className="ml-2">{t("common.save")}</span>
                </Button>
              ) : (
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => handleCancel(selectedRun)}
                  disabled={
                    cancelRunMutation.isPending ||
                    payRunMutation.isPending ||
                    selectedRun.status === "paid"
                  }
                >
                  {selectedRun.status === "paid"
                    ? t("business.payroll.alreadyPaid")
                    : t("business.payroll.cancelRun")}
                </Button>
              )}
            </div>
          </div>
        </Dialog>
      )}

      {/* Create run dialog */}
      {openCreate && (
        <Dialog
          open
          onClose={() => setOpenCreate(false)}
          title={t("business.payroll.newRun")}
          size="lg"
        >
          <div className="p-6 space-y-5 max-w-2xl">
            <div className="grid gap-4 sm:grid-cols-3">
              <div className="space-y-2">
                <label className="text-xs font-medium text-muted-foreground block">
                  {t("business.payroll.branch")}
                </label>
                <Select
                  value={createBranchId ?? ""}
                  onChange={(event) =>
                    setCreateBranchId(event.target.value || null)
                  }
                  className="w-full"
                >
                  <option value="">{t("business.payroll.selectBranch")}</option>
                  {branches.map((branch) => (
                    <option key={branch.id} value={branch.id}>
                      {branch.name}
                    </option>
                  ))}
                </Select>
              </div>
              <div className="space-y-2">
                <label className="text-xs font-medium text-muted-foreground block">
                  {t("business.payroll.periodStart")}
                </label>
                <Input
                  type="datetime-local"
                  value={toLocalDateTimeInput(createPeriodStart)}
                  onChange={(event: ChangeEvent<HTMLInputElement>) =>
                    setCreatePeriodStart(new Date(event.target.value).getTime())
                  }
                  className="w-full text-xs"
                />
              </div>
              <div className="space-y-2">
                <label className="text-xs font-medium text-muted-foreground block">
                  {t("business.payroll.periodEnd")}
                </label>
                <Input
                  type="datetime-local"
                  value={toLocalDateTimeInput(createPeriodEnd)}
                  onChange={(event: ChangeEvent<HTMLInputElement>) =>
                    setCreatePeriodEnd(new Date(event.target.value).getTime())
                  }
                  className="w-full text-xs"
                />
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-xs font-medium text-muted-foreground block">
                {t("business.payroll.note")}
              </label>
              <Input
                value={createNote}
                onChange={(event: ChangeEvent<HTMLInputElement>) =>
                  setCreateNote(event.target.value)
                }
                placeholder={t("business.payroll.notePlaceholder")}
                className="w-full"
              />
            </div>

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="font-medium">
                  {t("business.payroll.employees")}
                </span>
                <Badge variant="secondary">{createItems.length}</Badge>
              </div>
              <div className="space-y-1 max-h-64 overflow-y-auto border rounded p-2 bg-secondary/50">
                {employees.length === 0 ? (
                  <p className="text-xs text-muted-foreground text-center py-4">
                    {t("business.payroll.noEmployees")}
                  </p>
                ) : (
                  employees.map((employee) => (
                    <button
                      key={employee.id}
                      type="button"
                      onClick={() => addEmployeeToRun(employee.id)}
                      disabled={createItems.some((i) => i.employeeId === employee.id)}
                      className={`w-full flex items-center justify-between px-3 py-2 text-sm border rounded hover:bg-accent/50 disabled:opacity-50 ${
                        createItems.some((i) => i.employeeId === employee.id)
                          ? "bg-primary/10 text-primary"
                          : ""
                      }`}
                    >
                      <span className="truncate">
                        {employee.name}
                      </span>
                      <span className="font-mono text-[10px] text-muted-foreground">
                        {formatCents(
                          employee.salaryCents,
                          workspace.defaultCurrency,
                          locale,
                        )}
                      </span>
                    </button>
                  ))
                )}
              </div>
            </div>

            {createItems.length > 0 && (
              <div className="border-t pt-4 space-y-2">
                <div className="flex justify-between text-sm font-medium">
                  <span>{t("business.payroll.total")}</span>
                  <span>
                    {formatCents(
                      createTotalCents,
                      workspace.defaultCurrency,
                      locale,
                    )}
                  </span>
                </div>
                <div className="grid grid-cols-3 gap-2 text-xs">
                  <div>
                    <div className="text-muted-foreground">
                      {t("business.payroll.salary")}
                    </div>
                    <div className="font-medium">
                      {formatCents(
                        createItems.reduce((s, i) => s + i.salaryCents, 0),
                        workspace.defaultCurrency,
                        locale,
                      )}
                    </div>
                  </div>
                  <div>
                    <div className="text-muted-foreground">
                      {t("business.payroll.allowances")}
                    </div>
                    <div className="font-medium">
                      {formatCents(
                        createItems.reduce((s, i) => s + i.allowancesCents, 0),
                        workspace.defaultCurrency,
                        locale,
                      )}
                    </div>
                  </div>
                  <div>
                    <div className="text-muted-foreground">
                      {t("business.payroll.deductions")}
                    </div>
                    <div className="font-medium">
                      {formatCents(
                        createItems.reduce((s, i) => s + i.deductionsCents, 0),
                        workspace.defaultCurrency,
                        locale,
                      )}
                    </div>
                  </div>
                </div>

                <div className="mt-3 space-y-2">
                  {createItems.map((item) => {
                    const employee = employees.find((e) => e.id === item.employeeId);
                    return (
                      <div
                        key={item.employeeId}
                        className="flex items-center justify-between gap-2 text-xs p-1.5"
                      >
                        <div className="flex-1 min-w-0 truncate">
                          {employee?.name ?? item.employeeId}
                        </div>
                        <div className="flex gap-1">
                          <Input
                            value={centsToInput(item.salaryCents)}
                            onChange={(event: ChangeEvent<HTMLInputElement>) =>
                              updateItem(item.employeeId, "salaryCents", event.target.value)
                            }
                            className="w-20 text-xs"
                          />
                          <Input
                            value={centsToInput(item.allowancesCents)}
                            onChange={(event: ChangeEvent<HTMLInputElement>) =>
                              updateItem(item.employeeId, "allowancesCents", event.target.value)
                            }
                            className="w-20 text-xs"
                          />
                          <Input
                            value={centsToInput(item.deductionsCents)}
                            onChange={(event: ChangeEvent<HTMLInputElement>) =>
                              updateItem(item.employeeId, "deductionsCents", event.target.value)
                            }
                            className="w-20 text-xs"
                          />
                            <Button
                            size="icon"
                            variant="destructive"
                            onClick={() => removeItem(item.employeeId)}
                          >
                            <X className="h-3 w-3" />
                          </Button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}

            <div className="flex justify-end gap-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => setOpenCreate(false)}
                disabled={createRunMutation.isPending}
              >
                {t("common.cancel")}
              </Button>
              <Button
                onClick={() => void createRunMutation.mutate()}
                disabled={
                  !canCreate ||
                  createItems.length === 0 ||
                  createPeriodEnd <= createPeriodStart ||
                  createRunMutation.isPending
                }
              >
                {createRunMutation.isPending ? (
                  <Clock className="h-4 w-4 animate-spin" />
                ) : (
                  <Plus className="h-4 w-4" />
                )}
                <span className="ml-2">{t("business.payroll.createRun")}</span>
              </Button>
            </div>
          </div>
        </Dialog>
      )}
    </div>
  );
}
