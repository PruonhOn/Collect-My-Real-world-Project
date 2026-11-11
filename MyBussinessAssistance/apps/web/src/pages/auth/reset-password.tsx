import { useState, type FormEvent } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Loader2 } from "lucide-react";
import { AuthLayout } from "@/components/auth/auth-layout";
import {
  FormError,
  FormField,
  FormSuccess,
} from "@/components/auth/form-field";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ApiClientError } from "@/lib/api";
import { resetPassword } from "@/lib/auth-api";

export function ResetPasswordPage() {
  const { t } = useTranslation();
  const [searchParams] = useSearchParams();
  const token = searchParams.get("token") ?? "";
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);
  const [pending, setPending] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);

    if (password !== confirmPassword) {
      setError(t("auth.passwordsMismatch"));
      return;
    }

    setPending(true);

    try {
      await resetPassword(token, password);
      setDone(true);
    } catch (cause) {
      setError(
        cause instanceof ApiClientError
          ? cause.message
          : "Unable to reset your password. Please try again.",
      );
    } finally {
      setPending(false);
    }
  }

  return (
    <AuthLayout
      title={t("auth.resetTitle")}
      subtitle={t("auth.resetSubtitle")}
      footer={
        <Link className="font-medium text-primary hover:underline" to="/login">
          {t("auth.backToSignIn")}
        </Link>
      }
    >
      {!token ? (
        <FormError message={t("auth.resetInvalid")} />
      ) : done ? (
        <div className="space-y-4">
          <FormSuccess message={t("auth.resetDone")} />
          <Button asChild className="w-full" size="lg">
            <Link to="/login">{t("auth.signInButton")}</Link>
          </Button>
        </div>
      ) : (
        <form className="space-y-4" onSubmit={handleSubmit}>
          <FormError message={error} />

          <FormField
            label={t("auth.password")}
            htmlFor="password"
            hint={t("auth.passwordHint")}
          >
            <Input
              id="password"
              type="password"
              autoComplete="new-password"
              required
              minLength={10}
              value={password}
              onChange={(event) => setPassword(event.target.value)}
            />
          </FormField>

          <FormField
            label={t("auth.confirmPassword")}
            htmlFor="confirmPassword"
          >
            <Input
              id="confirmPassword"
              type="password"
              autoComplete="new-password"
              required
              value={confirmPassword}
              onChange={(event) => setConfirmPassword(event.target.value)}
            />
          </FormField>

          <Button className="w-full" size="lg" disabled={pending}>
            {pending ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin" />
                {t("auth.resetting")}
              </>
            ) : (
              t("auth.resetButton")
            )}
          </Button>
        </form>
      )}
    </AuthLayout>
  );
}
