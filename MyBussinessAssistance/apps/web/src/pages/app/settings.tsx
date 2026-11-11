import { useEffect, useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Building2, Loader2, ShieldCheck, Users } from "lucide-react";
import { FormError, FormField } from "@/components/auth/form-field";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Skeleton } from "@/components/ui/skeleton";
import { ApiClientError } from "@/lib/api";
import { cn } from "@/lib/utils";
import {
  createBranch,
  fetchMembers,
  fetchWorkspace,
  inviteMember,
  removeMember,
  updateMemberRole,
  updateWorkspace,
  type MembersResponse,
  type WorkspaceDetail,
} from "@/lib/auth-api";
import { useAuth } from "@/providers/auth-provider";

const selectClass =
  "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50";

function errorMessage(cause: unknown, fallback: string) {
  return cause instanceof ApiClientError ? cause.message : fallback;
}

function formatDate(value: number | null, locale: string) {
  if (!value) return "—";
  return new Intl.DateTimeFormat(locale, { dateStyle: "medium" }).format(
    new Date(value),
  );
}

export function SettingsPage() {
  const { t, i18n } = useTranslation();
  const { activeWorkspaceId } = useAuth();
  const queryClient = useQueryClient();
  const locale = i18n.language.startsWith("km") ? "km-KH" : "en-US";

  const workspaceQuery = useQuery({
    queryKey: ["workspace", activeWorkspaceId],
    queryFn: () => fetchWorkspace(activeWorkspaceId as string),
    enabled: Boolean(activeWorkspaceId),
  });

  const membersQuery = useQuery({
    queryKey: ["workspace-members", activeWorkspaceId],
    queryFn: () => fetchMembers(activeWorkspaceId as string),
    enabled:
      Boolean(activeWorkspaceId) &&
      Boolean(
        workspaceQuery.data?.permissions?.includes("member:read") ?? false,
      ),
  });

  if (!activeWorkspaceId) return null;

  if (workspaceQuery.isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-64" />
        <Skeleton className="h-40 w-full" />
        <Skeleton className="h-64 w-full" />
      </div>
    );
  }

  if (workspaceQuery.isError || !workspaceQuery.data) {
    return (
      <Card>
        <CardContent className="p-6 text-sm text-destructive">
          {t("settings.loadError")}
        </CardContent>
      </Card>
    );
  }

  const detail = workspaceQuery.data;
  const detailPermissions = detail.permissions ?? [];
  const canManage = detailPermissions.includes("settings:manage");
  const canManageBranches = detailPermissions.includes("branch:manage");
  const canInvite = detailPermissions.includes("member:invite");
  const canManageMembers = detailPermissions.includes("member:manage");

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold tracking-tight">
          {t("settings.title")}
        </h2>
        <p className="mt-1 text-sm text-muted-foreground">
          {t("settings.subtitle")}
        </p>
      </div>

      <ProfileCard
        detail={detail}
        canManage={canManage}
        locale={locale}
        onSaved={() => {
          void queryClient.invalidateQueries({
            queryKey: ["workspace", activeWorkspaceId],
          });
          void queryClient.invalidateQueries({ queryKey: ["auth-me"] });
        }}
      />

      <SubscriptionCard detail={detail} locale={locale} />

      <MembersCard
        workspaceId={activeWorkspaceId}
        data={membersQuery.data}
        loading={membersQuery.isLoading}
        error={membersQuery.isError}
        canInvite={canInvite}
        canManage={canManageMembers}
        locale={locale}
        onChanged={() =>
          void queryClient.invalidateQueries({
            queryKey: ["workspace-members", activeWorkspaceId],
          })
        }
      />

      <BranchesCard
        workspaceId={activeWorkspaceId}
        detail={detail}
        canManage={canManageBranches}
        onChanged={() =>
          void queryClient.invalidateQueries({
            queryKey: ["workspace", activeWorkspaceId],
          })
        }
      />
    </div>
  );
}

function ProfileCard({
  detail,
  canManage,
  locale,
  onSaved,
}: {
  detail: WorkspaceDetail;
  canManage: boolean;
  locale: string;
  onSaved: () => void;
}) {
  const { t } = useTranslation();
  const [name, setName] = useState(detail.workspace.name);
  const [currency, setCurrency] = useState(
    detail.workspace.defaultCurrency as "USD" | "KHR",
  );
  const [language, setLanguage] = useState(
    detail.workspace.locale as "en" | "km",
  );
  const [taxEnabled, setTaxEnabled] = useState(
    detail.settings?.taxEnabled ?? false,
  );
  const [taxRate, setTaxRate] = useState(
    String((detail.settings?.taxRateBasisPoints ?? 0) / 100),
  );
  const [invoicePrefix, setInvoicePrefix] = useState(
    detail.settings?.invoicePrefix ?? "INV",
  );
  const [exchangeRate, setExchangeRate] = useState(
    String(detail.settings?.exchangeRateKhrPerUsd ?? 4100),
  );
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setName(detail.workspace.name);
    setCurrency(detail.workspace.defaultCurrency as "USD" | "KHR");
    setLanguage(detail.workspace.locale as "en" | "km");
    setTaxEnabled(detail.settings?.taxEnabled ?? false);
    setTaxRate(String((detail.settings?.taxRateBasisPoints ?? 0) / 100));
    setInvoicePrefix(detail.settings?.invoicePrefix ?? "INV");
    setExchangeRate(String(detail.settings?.exchangeRateKhrPerUsd ?? 4100));
  }, [detail]);

  const mutation = useMutation({
    mutationFn: () =>
      updateWorkspace(detail.workspace.id, {
        name,
        defaultCurrency: currency,
        locale: language,
        taxEnabled,
        taxRateBasisPoints: Math.round(
          Math.max(0, Number.parseFloat(taxRate) || 0) * 100,
        ),
        invoicePrefix,
        exchangeRateKhrPerUsd: Math.round(
          Math.max(1, Number.parseFloat(exchangeRate) || 4100),
        ),
      }),
    onSuccess: () => {
      setError(null);
      setMessage(t("settings.saved"));
      onSaved();
    },
    onError: (cause) =>
      setError(errorMessage(cause, t("settings.loadError"))),
  });

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage(null);
    setError(null);
    mutation.mutate();
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Building2 className="h-4 w-4 text-primary" />
          {t("settings.profileTitle")}
        </CardTitle>
        <CardDescription>{t("settings.profileSubtitle")}</CardDescription>
      </CardHeader>
      <CardContent>
        <form className="space-y-4" onSubmit={handleSubmit}>
          <FormError message={error} />
          {message ? (
            <div className="rounded-md border border-success/30 bg-success/10 px-3 py-2.5 text-sm text-success">
              {message}
            </div>
          ) : null}

          <div className="grid gap-4 sm:grid-cols-2">
            <FormField label={t("settings.businessName")} htmlFor="ws-name">
              <Input
                id="ws-name"
                required
                minLength={2}
                disabled={!canManage}
                value={name}
                onChange={(event) => setName(event.target.value)}
              />
            </FormField>

            <FormField label={t("settings.currency")} htmlFor="ws-currency">
              <select
                id="ws-currency"
                className={selectClass}
                disabled={!canManage}
                value={currency}
                onChange={(event) =>
                  setCurrency(event.target.value as "USD" | "KHR")
                }
              >
                <option value="USD">USD ($)</option>
                <option value="KHR">KHR (៛)</option>
              </select>
            </FormField>

            <FormField label={t("settings.language")} htmlFor="ws-locale">
              <select
                id="ws-locale"
                className={selectClass}
                disabled={!canManage}
                value={language}
                onChange={(event) =>
                  setLanguage(event.target.value as "en" | "km")
                }
              >
                <option value="en">English</option>
                <option value="km">ខ្មែរ</option>
              </select>
            </FormField>

            <FormField label={t("settings.invoicePrefix")} htmlFor="ws-prefix">
              <Input
                id="ws-prefix"
                maxLength={12}
                disabled={!canManage}
                value={invoicePrefix}
                onChange={(event) => setInvoicePrefix(event.target.value)}
              />
            </FormField>

            <FormField label={t("settings.exchangeRate")} htmlFor="ws-rate">
              <Input
                id="ws-rate"
                type="number"
                min={1}
                step={1}
                disabled={!canManage}
                value={exchangeRate}
                onChange={(event) => setExchangeRate(event.target.value)}
              />
            </FormField>

            <FormField label={t("settings.taxRate")} htmlFor="ws-tax-rate">
              <Input
                id="ws-tax-rate"
                type="number"
                min={0}
                max={100}
                step={0.01}
                disabled={!canManage || !taxEnabled}
                value={taxRate}
                onChange={(event) => setTaxRate(event.target.value)}
              />
            </FormField>
          </div>

          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              className="h-4 w-4 rounded border-input accent-primary"
              disabled={!canManage}
              checked={taxEnabled}
              onChange={(event) => setTaxEnabled(event.target.checked)}
            />
            {t("settings.taxEnabled")}
          </label>

          {canManage ? (
            <Button type="submit" disabled={mutation.isPending}>
              {mutation.isPending ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  {t("settings.saving")}
                </>
              ) : (
                t("settings.save")
              )}
            </Button>
          ) : (
            <p className="text-sm text-muted-foreground">
              {t("settings.manageDenied")}
            </p>
          )}
        </form>
      </CardContent>
    </Card>
  );
}

function SubscriptionCard({
  detail,
  locale,
}: {
  detail: WorkspaceDetail;
  locale: string;
}) {
  const { t } = useTranslation();
  const subscription = detail.subscription;

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <ShieldCheck className="h-4 w-4 text-primary" />
          {t("settings.subscriptionTitle")}
        </CardTitle>
      </CardHeader>
      <CardContent className="grid gap-4 text-sm sm:grid-cols-3">
        <div>
          <p className="text-muted-foreground">{t("settings.plan")}</p>
          <p className="mt-1 font-medium">
            {subscription?.planName ?? "—"}
          </p>
        </div>
        <div>
          <p className="text-muted-foreground">{t("settings.status")}</p>
          <p className="mt-1">
            {subscription ? (
              <Badge variant="secondary">{subscription.status}</Badge>
            ) : (
              "—"
            )}
          </p>
        </div>
        <div>
          <p className="text-muted-foreground">{t("settings.trialEndsAt")}</p>
          <p className="mt-1 font-medium">
            {subscription?.trialEndsAt
              ? formatDate(subscription.trialEndsAt, locale)
              : t("settings.noTrial")}
          </p>
        </div>
      </CardContent>
    </Card>
  );
}

function MembersCard({
  workspaceId,
  data,
  loading,
  error,
  canInvite,
  canManage,
  locale,
  onChanged,
}: {
  workspaceId: string;
  data: MembersResponse | undefined;
  loading: boolean;
  error: boolean;
  canInvite: boolean;
  canManage: boolean;
  locale: string;
  onChanged: () => void;
}) {
  const { t } = useTranslation();
  const [email, setEmail] = useState("");
  const [roleId, setRoleId] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [devToken, setDevToken] = useState<string | null>(null);

  const assignableRoles = (data?.roles ?? []).filter(
    (role) => role.key !== "owner",
  );

  useEffect(() => {
    if (!roleId && assignableRoles.length > 0) {
      setRoleId(assignableRoles[0].id);
    }
  }, [assignableRoles, roleId]);

  const inviteMutation = useMutation({
    mutationFn: () => inviteMember(workspaceId, { email, roleId }),
    onSuccess: (result) => {
      setEmail("");
      setFormError(null);
      setMessage(
        result.emailSent
          ? t("settings.inviteSent")
          : t("settings.inviteEmailFailed"),
      );
      setDevToken(result.devInvitationToken ?? null);
      onChanged();
    },
    onError: (cause) =>
      setFormError(errorMessage(cause, t("settings.inviteEmailFailed"))),
  });

  const roleMutation = useMutation({
    mutationFn: (input: { memberId: string; roleId: string }) =>
      updateMemberRole(workspaceId, input.memberId, input.roleId),
    onSuccess: () => {
      setMessage(t("settings.roleUpdated"));
      onChanged();
    },
    onError: (cause) =>
      setFormError(errorMessage(cause, t("settings.loadError"))),
  });

  const removeMutation = useMutation({
    mutationFn: (memberId: string) => removeMember(workspaceId, memberId),
    onSuccess: () => {
      setMessage(t("settings.memberRemoved"));
      onChanged();
    },
    onError: (cause) =>
      setFormError(errorMessage(cause, t("settings.loadError"))),
  });

  function handleInvite(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage(null);
    setFormError(null);
    setDevToken(null);
    inviteMutation.mutate();
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Users className="h-4 w-4 text-primary" />
          {t("settings.membersTitle")}
        </CardTitle>
        <CardDescription>{t("settings.membersSubtitle")}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-5">
        <FormError message={formError} />
        {message ? (
          <div className="rounded-md border border-success/30 bg-success/10 px-3 py-2.5 text-sm text-success">
            {message}
          </div>
        ) : null}
        {devToken ? (
          <div className="rounded-md border border-dashed bg-secondary/50 p-3 text-xs">
            <p className="mb-1 font-medium text-muted-foreground">
              {t("settings.inviteLink")}
            </p>
            <a
              className="break-all text-primary underline"
              href={`/invitations/${encodeURIComponent(devToken)}`}
            >
              {`${window.location.origin}/invitations/${devToken}`}
            </a>
          </div>
        ) : null}

        {canInvite ? (
          <form
            className="grid gap-3 sm:grid-cols-[1fr_180px_auto] sm:items-end"
            onSubmit={handleInvite}
          >
            <FormField label={t("settings.email")} htmlFor="invite-email">
              <Input
                id="invite-email"
                type="email"
                required
                value={email}
                onChange={(event) => setEmail(event.target.value)}
              />
            </FormField>
            <FormField label={t("settings.role")} htmlFor="invite-role">
              <select
                id="invite-role"
                className={selectClass}
                value={roleId}
                onChange={(event) => setRoleId(event.target.value)}
              >
                {assignableRoles.map((role) => (
                  <option key={role.id} value={role.id}>
                    {role.name}
                  </option>
                ))}
              </select>
            </FormField>
            <Button
              type="submit"
              disabled={inviteMutation.isPending || !roleId}
            >
              {inviteMutation.isPending ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  {t("settings.inviting")}
                </>
              ) : (
                t("settings.sendInvite")
              )}
            </Button>
          </form>
        ) : null}

        {loading ? (
          <Skeleton className="h-24 w-full" />
        ) : error ? (
          <p className="text-sm text-destructive">{t("settings.loadError")}</p>
        ) : (
          <div className="space-y-3">
            <div className="divide-y rounded-lg border">
              {(data?.members ?? []).map((member) => (
                <div
                  key={member.memberId}
                  className="flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-center"
                >
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium">
                      {member.name}
                    </p>
                    <p className="truncate text-xs text-muted-foreground">
                      {member.email}
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    {canManage && member.roleKey !== "owner" ? (
                      <select
                        className={cn(selectClass, "h-9 w-44")}
                        value={member.roleId}
                        onChange={(event) =>
                          roleMutation.mutate({
                            memberId: member.memberId,
                            roleId: event.target.value,
                          })
                        }
                        disabled={roleMutation.isPending}
                      >
                        {assignableRoles.map((role) => (
                          <option key={role.id} value={role.id}>
                            {role.name}
                          </option>
                        ))}
                      </select>
                    ) : (
                      <Badge variant="outline">{member.roleName}</Badge>
                    )}
                    <span className="text-xs text-muted-foreground">
                      {member.joinedAt
                        ? formatDate(member.joinedAt, locale)
                        : "—"}
                    </span>
                    {canManage && member.roleKey !== "owner" ? (
                      <Button
                        variant="ghost"
                        size="sm"
                        className="text-destructive"
                        onClick={() => removeMutation.mutate(member.memberId)}
                        disabled={removeMutation.isPending}
                      >
                        {t("settings.remove")}
                      </Button>
                    ) : null}
                  </div>
                </div>
              ))}
            </div>

            <div>
              <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {t("settings.pending")}
              </p>
              {data?.invitations.length ? (
                <div className="divide-y rounded-lg border">
                  {data.invitations.map((invitation) => (
                    <div
                      key={invitation.id}
                      className="flex items-center justify-between gap-3 px-3 py-2.5 text-sm"
                    >
                      <span className="truncate">{invitation.email}</span>
                      <span className="flex shrink-0 items-center gap-2">
                        <Badge variant="outline">{invitation.roleName}</Badge>
                        <span className="text-xs text-muted-foreground">
                          {formatDate(invitation.expiresAt, locale)}
                        </span>
                      </span>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground">
                  {t("settings.noPending")}
                </p>
              )}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

function BranchesCard({
  workspaceId,
  detail,
  canManage,
  onChanged,
}: {
  workspaceId: string;
  detail: WorkspaceDetail;
  canManage: boolean;
  onChanged: () => void;
}) {
  const { t } = useTranslation();
  const [name, setName] = useState("");
  const [code, setCode] = useState("");
  const [phone, setPhone] = useState("");
  const [address, setAddress] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const mutation = useMutation({
    mutationFn: () =>
      createBranch(workspaceId, {
        name,
        code,
        ...(phone.trim() ? { phone: phone.trim() } : {}),
        ...(address.trim() ? { address: address.trim() } : {}),
      }),
    onSuccess: () => {
      setName("");
      setCode("");
      setPhone("");
      setAddress("");
      setMessage(t("settings.branchAdded"));
      setError(null);
      onChanged();
    },
    onError: (cause) =>
      setError(errorMessage(cause, t("settings.loadError"))),
  });

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage(null);
    setError(null);
    mutation.mutate();
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Building2 className="h-4 w-4 text-primary" />
          {t("settings.branchesTitle")}
        </CardTitle>
        <CardDescription>{t("settings.branchesSubtitle")}</CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <FormError message={error} />
        {message ? (
          <div className="rounded-md border border-success/30 bg-success/10 px-3 py-2.5 text-sm text-success">
            {message}
          </div>
        ) : null}

        <div className="divide-y rounded-lg border">
          {detail.branches.map((branch) => (
            <div
              key={branch.id}
              className="flex items-center justify-between gap-3 px-3 py-2.5 text-sm"
            >
              <div className="min-w-0">
                <p className="truncate font-medium">{branch.name}</p>
                <p className="text-xs text-muted-foreground">
                  {branch.code}
                  {branch.address ? ` · ${branch.address}` : ""}
                </p>
              </div>
              {branch.isDefault ? (
                <Badge variant="secondary">{t("settings.default")}</Badge>
              ) : null}
            </div>
          ))}
        </div>

        {canManage ? (
          <form className="space-y-3" onSubmit={handleSubmit}>
            <div className="grid gap-3 sm:grid-cols-2">
              <FormField label={t("settings.branchName")} htmlFor="branch-name">
                <Input
                  id="branch-name"
                  required
                  minLength={2}
                  value={name}
                  onChange={(event) => setName(event.target.value)}
                />
              </FormField>
              <FormField label={t("settings.branchCode")} htmlFor="branch-code">
                <Input
                  id="branch-code"
                  required
                  maxLength={24}
                  value={code}
                  onChange={(event) => setCode(event.target.value)}
                />
              </FormField>
              <FormField label={t("settings.phone")} htmlFor="branch-phone">
                <Input
                  id="branch-phone"
                  type="tel"
                  placeholder="+855 12 345 678"
                  value={phone}
                  onChange={(event) => setPhone(event.target.value)}
                />
              </FormField>
              <FormField label={t("settings.address")} htmlFor="branch-address">
                <Input
                  id="branch-address"
                  maxLength={300}
                  value={address}
                  onChange={(event) => setAddress(event.target.value)}
                />
              </FormField>
            </div>
            <Button type="submit" variant="outline" disabled={mutation.isPending}>
              {mutation.isPending ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  {t("settings.adding")}
                </>
              ) : (
                t("settings.addBranch")
              )}
            </Button>
          </form>
        ) : null}
      </CardContent>
    </Card>
  );
}
