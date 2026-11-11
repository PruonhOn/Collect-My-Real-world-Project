import { Loader2 } from "lucide-react";
import { Logo } from "@/components/brand/logo";

export function FullPageLoader() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center gap-4 bg-background">
      <Logo />
      <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
    </div>
  );
}
