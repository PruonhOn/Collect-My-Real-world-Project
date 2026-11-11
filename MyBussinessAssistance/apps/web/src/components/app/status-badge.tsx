import { Badge } from "@/components/ui/badge";

const tones: Record<
  string,
  "default" | "secondary" | "accent" | "success" | "outline" | "destructive"
> = {
  active: "success",
  completed: "success",
  received: "success",
  paid: "success",
  approved: "success",
  recorded: "default",
  partial: "accent",
  confirmed: "accent",
  fulfilled: "success",
  unpaid: "outline",
  pending: "outline",
  void: "destructive",
  inactive: "secondary",
  cancelled: "destructive",
};

export function StatusBadge({ status }: { status: string }) {
  return (
    <Badge variant={tones[status] ?? "secondary"} className="capitalize">
      {status.replace(/_/g, " ")}
    </Badge>
  );
}
