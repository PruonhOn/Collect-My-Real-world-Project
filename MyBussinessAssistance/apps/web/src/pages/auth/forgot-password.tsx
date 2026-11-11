import { useState, type FormEvent } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Loader2 } from "lucide-react";
import { AuthLayout } from "@/components/auth/auth-layout";
import {
  DevLink,
  FormError,
  FormField,
  FormSuccess,
} from "@/components/auth/form-field";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ApiClientError } from "@/lib/api";
import { forgotPassword } from "@/lib/auth-api";

export function ForgotPasswordPage() {
  const { t } = useTranslation();  const [email, setEmail] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [devToken, setDevToken] = useState<string | null>(null);
  const [pending, setPending] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setMessage(null);
    setDevToken(null);
    setPending(true);

    try {
      const result = await forgotPassword(email);
      setMessage(
        result.emailSent === false
          ? t("auth.emailSendFailed")
          : t("auth.resetSent"),
      );
      setDevToken(result.devResetToken ?? null);
    } catch (cause) {
      setError(
        cause instanceof ApiClientError
          ? cause.message
          : "Unable to send the reset link. Please try again.",
      );
    } finally {
      setPending(false);
    }
  }

  return (
    <AuthLayout
      title={t("auth.forgotTitle")}
      subtitle={t("auth.forgotSubtitle")}
      footer={
        <Link
          className="font-medium text-primary hover:underline"
          to="/login"
        >
          {t("auth.backToSignIn")}
        </Link>
      }
    >
      <form className="space-y-4" onSubmit={handleSubmit}>
        <FormError message={error} />
        <FormSuccess message={message} />

        <FormField label={t("auth.email")} htmlFor="email">
          <Input
            id="email"
            type="email"
            autoComplete="email"
            required
            value={email}
            onChange={(event) => setEmail(event.target.value)}
          />
        </FormField>

        <Button className="w-full" size="lg" disabled={pending}>
          {pending ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" />
              {t("auth.sending")}
            </>
          ) : (
            t("auth.sendReset")
          )}
        </Button>

        {devToken ? (
          <DevLink
            label={t("auth.devToken")}
            href={`/reset-password?token=${encodeURIComponent(devToken)}`}
          />
        ) : null}
      </form>
    </AuthLayout>
  );
}
