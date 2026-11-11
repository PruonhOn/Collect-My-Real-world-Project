import { useEffect, useRef, useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { CheckCircle2, Loader2, Mail, MailWarning } from "lucide-react";
import { AuthLayout } from "@/components/auth/auth-layout";
import { DevLink, FormError, FormSuccess } from "@/components/auth/form-field";
import { Button } from "@/components/ui/button";
import { resendVerification, verifyEmail } from "@/lib/auth-api";
import { useAuth } from "@/providers/auth-provider";

type State = "idle" | "checking" | "success" | "error";

export function VerifyEmailPage() {
  const { t } = useTranslation();
  const { user, refresh } = useAuth();
  const [searchParams] = useSearchParams();
  const token = searchParams.get("token") ?? "";
  const [state, setState] = useState<State>(token ? "checking" : "idle");
  const [resendMessage, setResendMessage] = useState<string | null>(null);
  const [devToken, setDevToken] = useState<string | null>(null);
  const [resending, setResending] = useState(false);
  const started = useRef(false);

  useEffect(() => {
    if (!token || started.current) return;
    started.current = true;

    verifyEmail(token)
      .then(async () => {
        setState("success");
        if (user) {
          await refresh();
        }
      })
      .catch(() => setState("error"));
  }, [token, user, refresh]);

  async function handleResend() {
    setResendMessage(null);
    setResending(true);

    try {
      const result = await resendVerification();
      setResendMessage(
        result.emailSent === false
          ? t("auth.emailSendFailed")
          : t("auth.resendSent"),
      );
      setDevToken(result.devVerificationToken ?? null);
    } catch {
      setResendMessage(t("auth.verifyError"));
    } finally {
      setResending(false);
    }
  }

  return (
    <AuthLayout
      title={t("auth.verifyTitle")}
      subtitle={t("auth.signInSubtitle")}
      footer={
        <Link className="font-medium text-primary hover:underline" to="/login">
          {t("auth.backToSignIn")}
        </Link>
      }
    >
      <div className="space-y-4">
        {state === "checking" ? (
          <div className="flex items-center gap-2 rounded-md border bg-secondary/40 px-3 py-3 text-sm text-muted-foreground">
            <Loader2 className="h-4 w-4 animate-spin" />
            {t("auth.verifyChecking")}
          </div>
        ) : state === "success" ||
          (state === "idle" && Boolean(user?.emailVerifiedAt)) ? (
          <>
            <div className="flex items-center gap-2 rounded-md border border-success/30 bg-success/10 px-3 py-3 text-sm font-medium text-success">
              <CheckCircle2 className="h-4 w-4" />
              {t("auth.verifySuccess")}
            </div>
            <Button asChild className="w-full" size="lg">
              <Link to="/app">{t("auth.goToDashboard")}</Link>
            </Button>
          </>
        ) : (
          <>
            {state === "idle" ? (
              <div className="flex items-center gap-2 rounded-md border bg-secondary/40 px-3 py-3 text-sm text-muted-foreground">
                <Mail className="h-4 w-4" />
                {t("auth.verifySent")}
              </div>
            ) : (
              <div className="flex items-center gap-2 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-3 text-sm font-medium text-destructive">
                <MailWarning className="h-4 w-4" />
                {t("auth.verifyError")}
              </div>
            )}

            <FormSuccess message={resendMessage} />

            {user ? (
              <Button
                className="w-full"
                variant="outline"
                size="lg"
                onClick={handleResend}
                disabled={resending}
              >
                {resending ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    {t("auth.sending")}
                  </>
                ) : (
                  t("auth.resend")
                )}
              </Button>
            ) : state === "error" ? (
              <FormError message={t("auth.backToSignIn")} />
            ) : null}

            {devToken ? (
              <DevLink
                label={t("auth.devToken")}
                href={`/verify-email?token=${encodeURIComponent(devToken)}`}
              />
            ) : null}
          </>
        )}
      </div>
    </AuthLayout>
  );
}
