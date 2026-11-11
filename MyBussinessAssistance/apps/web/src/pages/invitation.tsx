import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Loader2, MailCheck } from "lucide-react";
import { AuthLayout } from "@/components/auth/auth-layout";
import { FormError } from "@/components/auth/form-field";
import { Button } from "@/components/ui/button";
import { ApiClientError } from "@/lib/api";
import {
  acceptInvitation,
  fetchInvitation,
  type InvitationPreview,
} from "@/lib/auth-api";
import { useAuth } from "@/providers/auth-provider";

export function InvitationPage() {
  const { t } = useTranslation();
  const { token = "" } = useParams();
  const { user, refresh, switchWorkspace, signOut } = useAuth();
  const navigate = useNavigate();
  const [invitation, setInvitation] = useState<InvitationPreview | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [pending, setPending] = useState(false);

  useEffect(() => {
    let cancelled = false;

    fetchInvitation(token)
      .then((result) => {
        if (!cancelled) setInvitation(result.invitation);
      })
      .catch(() => {
        if (!cancelled) setError(t("auth.invitationInvalid"));
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, [token, t]);

  async function handleAccept() {
    setError(null);
    setPending(true);

    try {
      const result = await acceptInvitation(token);
      await refresh();
      switchWorkspace(result.workspaceId);
      navigate("/app", { replace: true });
    } catch (cause) {
      setError(
        cause instanceof ApiClientError
          ? cause.message
          : "Unable to accept this invitation.",
      );
    } finally {
      setPending(false);
    }
  }

  const emailMismatch =
    invitation && user && invitation.email.toLowerCase() !== user.email.toLowerCase();

  return (
    <AuthLayout
      title={t("auth.invitationTitle")}
      subtitle={
        invitation
          ? t("auth.invitationSubtitle", {
              workspace: invitation.workspaceName,
              role: invitation.roleName,
            })
          : t("auth.signInSubtitle")
      }
      footer={
        <div className="flex flex-wrap items-center gap-4">
          <Link className="font-medium text-primary hover:underline" to="/app">
            {t("auth.goToDashboard")}
          </Link>
          <button
            type="button"
            className="hover:text-foreground"
            onClick={() => void signOut()}
          >
            {t("auth.signOut")}
          </button>
        </div>
      }
    >
      <div className="space-y-4">
        {loading ? (
          <div className="flex items-center gap-2 rounded-md border bg-secondary/40 px-3 py-3 text-sm text-muted-foreground">
            <Loader2 className="h-4 w-4 animate-spin" />
            {t("common.loading")}
          </div>
        ) : error ? (
          <FormError message={error} />
        ) : invitation ? (
          <>
            <div className="flex items-start gap-3 rounded-lg border bg-card p-4">
              <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <MailCheck className="h-5 w-5" />
              </span>
              <div className="min-w-0 text-sm">
                <p className="font-semibold">{invitation.workspaceName}</p>
                <p className="text-muted-foreground">{invitation.roleName}</p>
                <p className="mt-1 break-all text-xs text-muted-foreground">
                  {invitation.email}
                </p>
              </div>
            </div>

            {emailMismatch ? (
              <FormError
                message={t("auth.invitationEmailMismatch", {
                  email: invitation.email,
                })}
              />
            ) : (
              <Button
                className="w-full"
                size="lg"
                onClick={handleAccept}
                disabled={pending}
              >
                {pending ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    {t("auth.accepting")}
                  </>
                ) : (
                  t("auth.acceptInvitation")
                )}
              </Button>
            )}
          </>
        ) : null}
      </div>
    </AuthLayout>
  );
}
