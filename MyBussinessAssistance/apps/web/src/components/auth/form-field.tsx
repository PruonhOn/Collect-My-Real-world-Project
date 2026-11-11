import type { ReactNode } from "react";
import { AlertCircle } from "lucide-react";
import { cn } from "@/lib/utils";

type FormFieldProps = {
  label: string;
  htmlFor: string;
  hint?: string;
  error?: string;
  children: ReactNode;
};

export function FormField({
  label,
  htmlFor,
  hint,
  error,
  children,
}: FormFieldProps) {
  return (
    <div className="space-y-1.5">
      <label htmlFor={htmlFor} className="text-sm font-medium">
        {label}
      </label>
      {children}
      {error ? (
        <p className="text-xs text-destructive">{error}</p>
      ) : hint ? (
        <p className="text-xs text-muted-foreground">{hint}</p>
      ) : null}
    </div>
  );
}

export function FormError({ message }: { message: string | null }) {
  if (!message) return null;

  return (
    <div
      role="alert"
      className="flex items-start gap-2 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2.5 text-sm text-destructive"
    >
      <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
      <span>{message}</span>
    </div>
  );
}

export function FormSuccess({ message }: { message: string | null }) {
  if (!message) return null;

  return (
    <div className="rounded-md border border-success/30 bg-success/10 px-3 py-2.5 text-sm text-success">
      {message}
    </div>
  );
}

export function DevLink({
  label,
  href,
}: {
  label: string;
  href: string;
}) {
  return (
    <div className="rounded-md border border-dashed bg-secondary/50 p-3 text-xs">
      <p className="mb-1 font-medium text-muted-foreground">{label}</p>
      <a className={cn("break-all text-primary underline")} href={href}>
        {href}
      </a>
    </div>
  );
}
