import { useState, type FormEvent } from "react";
import { Link, useLocation, useNavigate, useSearchParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Loader2 } from "lucide-react";
import { AuthLayout } from "@/components/auth/auth-layout";
import {
  FormError,
  FormField,
} from "@/components/auth/form-field";
import { GoogleButton } from "@/components/auth/google-button";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ApiClientError } from "@/lib/api";
import { useAuth } from "@/providers/auth-provider";

type LocationState = { from?: string };

export function LoginPage() {
  const { t } = useTranslation();
  const { signIn } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [searchParams] = useSearchParams();
  const [email, setEmail] = useState("mybussiness@123");
  const [password, setPassword] = useState("admin123");
  const [error, setError] = useState<string | null>(() =>
    searchParams.get("error") === "google" ? t("auth.googleError") : null,
  );
  const [pending, setPending] = useState(false);

  const from = (location.state as LocationState | null)?.from ?? "/app";

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setPending(true);

    try {
      await signIn(email, password);
      navigate(from, { replace: true });
    } catch (cause) {
      setError(
        cause instanceof ApiClientError
          ? cause.message
          : "Unable to sign in. Please try again.",
      );
    } finally {
      setPending(false);
    }
  }

  return (
    <AuthLayout
      title={t("auth.signInTitle")}
      subtitle={t("auth.signInSubtitle")}
      footer={
        <span>
          {t("auth.noAccount")}{" "}
          <Link className="font-medium text-primary hover:underline" to="/register">
            {t("auth.createAccount")}
          </Link>
        </span>
      }
    >
      <form className="space-y-4" onSubmit={handleSubmit}>
        <FormError message={error} />

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

        <FormField label={t("auth.password")} htmlFor="password">
          <Input
            id="password"
            type="password"
            autoComplete="current-password"
            required
            value={password}
            onChange={(event) => setPassword(event.target.value)}
          />
        </FormField>

        <div className="flex justify-end">
          <Link
            className="text-xs font-medium text-primary hover:underline"
            to="/forgot-password"
          >
            {t("auth.forgot")}
          </Link>
        </div>

        <Button className="w-full" size="lg" disabled={pending}>
          {pending ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" />
              {t("auth.signingIn")}
            </>
          ) : (
            t("auth.signInButton")
          )}
        </Button>

        <GoogleButton />
      </form>
    </AuthLayout>
  );
}
