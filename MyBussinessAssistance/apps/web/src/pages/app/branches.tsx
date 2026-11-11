import { useMemo, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Building2, Pencil, Plus, Power, Search } from "lucide-react";
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
import {
  createBranch,
  deactivateBranch,
  updateBranch,
  type Branch,
} from "@/lib/auth-api";
import { fetchBranches } from "@/lib/business-api";
import { ApiClientError } from "@/lib/api";
import { formatQuantity } from "@/lib/money";
import { useBusinessContext } from "@/lib/use-business";

type BranchForm = {
  name: string;
  code: string;
  phone: string;
  address: string;
  status: string;
};

const emptyForm: BranchForm = {
  name: "",
  code: "",
  phone: "",
  address: "",
  status: "active",
};

function toForm(branch: Branch): BranchForm {
  return {
    name: branch.name,
    code: branch.code,
    phone: branch.phone ?? "",
    address: branch.address ?? "",
    status: branch.status,
  };
}

function errorMessage(error: unknown) {
  return error instanceof ApiClientError ? error.message : "Request failed.";
}

export function BranchesPage() {
  const { t, i18n } = useTranslation();
  const { workspaceId, can } = useBusinessContext();
  const locale = i18n.language.startsWith("km") ? "km" : "en";
  const queryClient = useQueryClient();

  const [search, setSearch] = useState("");
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Branch | null>(null);
  const [form, setForm] = useState<BranchForm>(emptyForm);
  const [formError, setFormError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [deactivating, setDeactivating] = useState<Branch | null>(null);

  const branchesQuery = useQuery({
    queryKey: ["branches", workspaceId],
    queryFn: () => fetchBranches(workspaceId),
  });

  const branches = branchesQuery.data?.branches ?? [];

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    if (!term) return branches;
    return branches.filter((branch) =>
      [branch.name, branch.code, branch.phone ?? "", branch.address ?? ""]
        .join(" ")
        .toLowerCase()
        .includes(term),
    );
  }, [branches, search]);

  const saveMutation = useMutation({
    mutationFn: () =>
      editing
        ? updateBranch(workspaceId, editing.id, {
            name: form.name.trim(),
            phone: form.phone.trim() || undefined,
            address: form.address.trim() || undefined,
            status: form.status as "active" | "inactive",
          })
        : createBranch(workspaceId, {
            name: form.name.trim(),
            code: form.code.trim(),
            phone: form.phone.trim() || undefined,
            address: form.address.trim() || undefined,
          }),
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["branches", workspaceId],
      });
      setNotice(
        editing
          ? t("business.branches.updated")
          : t("business.branches.created"),
      );
      setOpen(false);
      setEditing(null);
    },
    onError: (error) => setFormError(errorMessage(error)),
  });

  const deactivateMutation = useMutation({
    mutationFn: (branchId: string) => deactivateBranch(workspaceId, branchId),
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["branches", workspaceId],
      });
      setNotice(t("business.branches.deactivated"));
      setDeactivating(null);
    },
  });

  function openCreate() {
    setEditing(null);
    setForm(emptyForm);
    setFormError(null);
    setOpen(true);
  }

  function openEdit(branch: Branch) {
    setEditing(branch);
    setForm(toForm(branch));
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
        title={t("business.branches.title")}
        description={t("business.branches.subtitle")}
        actions={
          can("branch:manage") ? (
            <Button onClick={openCreate}>
              <Plus className="h-4 w-4" />
              {t("business.branches.add")}
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
              {t("business.branches.total")}
            </p>
            <p className="mt-1 text-2xl font-bold">
              {formatQuantity(branches.length, locale)}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">
              {t("business.branches.activeCount")}
            </p>
            <p className="mt-1 text-2xl font-bold">
              {formatQuantity(
                branches.filter((branch) => branch.status === "active").length,
                locale,
              )}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-5">
            <p className="text-xs text-muted-foreground">
              {t("business.branches.default")}
            </p>
            <p className="mt-1 truncate text-lg font-semibold">
              {branches.find((branch) => branch.isDefault)?.name ?? "—"}
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
              placeholder={t("business.branches.searchPlaceholder")}
              className="pl-9"
            />
          </div>

          {branchesQuery.isLoading ? (
            <div className="space-y-2">
              {[0, 1, 2, 3].map((index) => (
                <Skeleton key={index} className="h-11 w-full" />
              ))}
            </div>
          ) : branchesQuery.isError ? (
            <p className="py-10 text-center text-sm text-destructive">
              {errorMessage(branchesQuery.error)}
            </p>
          ) : filtered.length === 0 ? (
            <EmptyState
              icon={Building2}
              title={t("business.branches.emptyTitle")}
              description={t("business.branches.emptyBody")}
              action={
                can("branch:manage") ? (
                  <Button onClick={openCreate}>
                    <Plus className="h-4 w-4" />
                    {t("business.branches.add")}
                  </Button>
                ) : undefined
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("common.name")}</TableHead>
                  <TableHead>{t("common.phone")}</TableHead>
                  <TableHead>{t("common.address")}</TableHead>
                  <TableHead>{t("common.status")}</TableHead>
                  <TableHead className="text-right">
                    {t("common.actions")}
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filtered.map((branch) => (
                  <TableRow key={branch.id}>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        <span className="font-medium">{branch.name}</span>
                        {branch.isDefault ? (
                          <span className="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-medium text-muted-foreground">
                            {t("business.branches.default")}
                          </span>
                        ) : null}
                      </div>
                      <div className="font-mono text-xs text-muted-foreground">
                        {branch.code}
                      </div>
                    </TableCell>
                    <TableCell>{branch.phone ?? "—"}</TableCell>
                    <TableCell className="max-w-xs truncate">
                      {branch.address ?? "—"}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={branch.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {can("branch:manage") ? (
                        <div className="flex justify-end gap-1">
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => openEdit(branch)}
                            aria-label={t("common.edit")}
                          >
                            <Pencil className="h-4 w-4" />
                          </Button>
                          {!branch.isDefault && branch.status === "active" ? (
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => setDeactivating(branch)}
                              aria-label={t("business.branches.deactivate")}
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
          editing ? t("business.branches.edit") : t("business.branches.add")
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
              form="branch-form"
              disabled={saveMutation.isPending}
            >
              {saveMutation.isPending ? t("common.saving") : t("common.save")}
            </Button>
          </>
        }
      >
        <form id="branch-form" className="space-y-4" onSubmit={submit}>
          <div className="grid gap-4 sm:grid-cols-2">
            <Field label={t("common.name")} htmlFor="branch-name" required>
              <Input
                id="branch-name"
                value={form.name}
                onChange={(event) =>
                  setForm({ ...form, name: event.target.value })
                }
                required
              />
            </Field>
            <Field
              label={t("business.branches.code")}
              htmlFor="branch-code"
              required={!editing}
            >
              <Input
                id="branch-code"
                value={form.code}
                onChange={(event) =>
                  setForm({ ...form, code: event.target.value })
                }
                disabled={Boolean(editing)}
                required={!editing}
              />
            </Field>
            <Field label={t("common.phone")} htmlFor="branch-phone">
              <Input
                id="branch-phone"
                value={form.phone}
                onChange={(event) =>
                  setForm({ ...form, phone: event.target.value })
                }
              />
            </Field>
            <Field label={t("common.status")} htmlFor="branch-status">
              <Select
                id="branch-status"
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
          <Field label={t("common.address")} htmlFor="branch-address">
            <Input
              id="branch-address"
              value={form.address}
              onChange={(event) =>
                setForm({ ...form, address: event.target.value })
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
        title={t("business.branches.deactivateTitle")}
        description={t("business.branches.deactivateBody", {
          name: deactivating?.name ?? "",
        })}
        confirmLabel={t("business.branches.deactivate")}
        cancelLabel={t("common.cancel")}
        pending={deactivateMutation.isPending}
      />
    </div>
  );
}
