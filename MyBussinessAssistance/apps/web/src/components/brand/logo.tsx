import { cn } from "@/lib/utils";

type LogoProps = {
  className?: string;
  showWordmark?: boolean;
};

export function Logo({ className, showWordmark = true }: LogoProps) {
  return (
    <span className={cn("inline-flex items-center gap-2.5", className)}>
      <span className="relative inline-flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-soft">
        <svg viewBox="0 0 32 32" className="h-5 w-5" aria-hidden="true">
          <path
            d="M6 24V10a2 2 0 0 1 2-2h3.4l4.6 5.8L20.6 8H24a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4V15.5L16 21l-4-5.5V26H8a2 2 0 0 1-2-2Z"
            fill="currentColor"
          />
        </svg>
        <span className="absolute -right-1 -top-1 h-2.5 w-2.5 rounded-full bg-accent" />
      </span>
      {showWordmark ? (
        <span className="flex flex-col leading-none">
          <span className="text-sm font-semibold tracking-tight">
            MyBusiness
          </span>
          <span className="text-[11px] font-medium uppercase tracking-[0.18em] text-muted-foreground">
            Assistant
          </span>
        </span>
      ) : null}
    </span>
  );
}
