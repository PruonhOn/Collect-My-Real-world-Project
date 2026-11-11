import { useState, type FormEvent } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Loader2 } from "lucide-react";
import { AuthLayout } from "@/components/auth/auth-layout";
import { FormError, FormField } from "@/components/auth/form-field";
import { GoogleButton } from "@/components/auth/google-button";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ApiClientError } from "@/lib/api";
import { useAuth } from "@/providers/auth-provider";

export function RegisterPage() {
  const { t, i18n } = useTranslation();
  const { signUp } = useAuth();
  const navigate = useNavigate();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [error, setError] = useState<string | null>(null);
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
      const locale = i18n.language.startsWith("km") ? "km" : "en";
      await signUp({
        name,
        email,
        password,
        locale,
        ...(phone.trim() ? { phone: phone.trim() } : {}),
      });
      navigate("/onboarding", { replace: true });
    } catch (cause) {
      setError(
        cause instanceof ApiClientError
          ? cause.message
          : "Unable to create your account. Please try again.",
      );
    } finally {
      setPending(false);
    }
  }

  return (
    <AuthLayout
      title={t("auth.registerTitle")}
      subtitle={t("auth.registerSubtitle")}
      footer={
        <span>
          {t("auth.haveAccount")}{" "}
          <Link className="font-medium text-primary hover:underline" to="/login">
            {t("auth.signInButton")}
          </Link>
        </span>
      }
    >
      <form className="space-y-4" onSubmit={handleSubmit}>
        <FormError message={error} />

        <FormField label={t("auth.name")} htmlFor="name">
          <Input
            id="name"
            autoComplete="name"
            required
            minLength={2}
            value={name}
            onChange={(event) => setName(event.target.value)}
          />
        </FormField>

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

        <FormField label={t("auth.phoneOptional")} htmlFor="phone">
          <Input
            id="phone"
            type="tel"
            autoComplete="tel"
            placeholder="+855 12 345 678"
            value={phone}
            onChange={(event) => setPhone(event.target.value)}
          />
        </FormField>

        <div className="grid gap-4 sm:grid-cols-2">
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
        </div>

        <Button className="w-full" size="lg" disabled={pending}>
          {pending ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" />
              {t("auth.registering")}
            </>
          ) : (
            t("auth.registerButton")
          )}
        </Button>

        <GoogleButton />

        <p className="text-center text-xs leading-relaxed text-muted-foreground">
          {t("auth.terms")}
        </p>
      </form>
    </AuthLayout>
  );
}
