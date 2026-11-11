import { useState, type FormEvent } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Loader2 } from "lucide-react";
import { AuthLayout } from "@/components/auth/auth-layout";
import { FormError, FormField } from "@/components/auth/form-field";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { ApiClientError } from "@/lib/api";
import { useAuth } from "@/providers/auth-provider";

export function OnboardingPage() {
  const { t, i18n } = useTranslation();
  const { user, workspaces, createWorkspace, signOut } = useAuth();
  const navigate = useNavigate();
  const [name, setName] = useState("");
  const [currency, setCurrency] = useState<"USD" | "KHR">("USD");
  const [locale, setLocale] = useState<"en" | "km">(
    i18n.language.startsWith("km") ? "km" : "en",
  );
  const [error, setError] = useState<string | null>(null);
  const [pending, setPending] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setPending(true);

    try {
      await createWorkspace({ name, defaultCurrency: currency, locale });
      navigate("/app", { replace: true });
    } catch (cause) {
      setError(
        cause instanceof ApiClientError
          ? cause.message
          : "Unable to create the workspace. Please try again.",
      );
    } finally {
      setPending(false);
    }
  }

  return (
    <AuthLayout
      title={t("auth.onboardingTitle")}
      subtitle={t("auth.onboardingSubtitle")}
      footer={
        <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
          <button
            type="button"
            className="font-medium text-primary hover:underline"
            onClick={() => void signOut()}
          >
            {t("auth.signOut")}
          </button>
          {workspaces.length > 0 ? (
            <Link className="hover:text-foreground" to="/app">
              {t("auth.goToDashboard")}
            </Link>
          ) : null}
        </div>
      }
    >
      <form className="space-y-4" onSubmit={handleSubmit}>
        <FormError message={error} />

        {user && !user.emailVerifiedAt ? (
          <div className="rounded-md border border-accent/30 bg-accent/10 px-3 py-2.5 text-xs text-accent-foreground">
            {t("auth.verifyBanner")}{" "}
            <Link className="font-medium underline" to="/verify-email">
              {t("auth.verifyNow")}
            </Link>
          </div>
        ) : null}

        <FormField label={t("auth.businessName")} htmlFor="workspaceName">
          <Input
            id="workspaceName"
            required
            minLength={2}
            maxLength={120}
            placeholder="Dara Mart"
            value={name}
            onChange={(event) => setName(event.target.value)}
          />
        </FormField>

        <div className="grid gap-4 sm:grid-cols-2">
          <FormField label={t("auth.currency")} htmlFor="currency">
            <select
              id="currency"
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              value={currency}
              onChange={(event) =>
                setCurrency(event.target.value as "USD" | "KHR")
              }
            >
              <option value="USD">USD ($)</option>
              <option value="KHR">KHR (៛)</option>
            </select>
          </FormField>

          <FormField label={t("auth.language")} htmlFor="locale">
            <select
              id="locale"
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              value={locale}
              onChange={(event) =>
                setLocale(event.target.value as "en" | "km")
              }
            >
              <option value="en">English</option>
              <option value="km">ខ្មែរ</option>
            </select>
          </FormField>
        </div>

        <Button className="w-full" size="lg" disabled={pending}>
          {pending ? (
            <>
              <Loader2 className="h-4 w-4 animate-spin" />
              {t("auth.creatingWorkspace")}
            </>
          ) : (
            t("auth.createWorkspace")
          )}
        </Button>
      </form>
    </AuthLayout>
  );
}
