import { useEffect, useState } from "react";
import { useTranslation } from "react-i18next";
import { Dialog } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Field } from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import { formatCents } from "@/lib/money";

type PaymentDialogProps = {
  open: boolean;
  onClose: () => void;
  title?: string;
  totalCents: number;
  paidCents: number;
  currency: string;
  locale: string;
  pending?: boolean;
  error?: string | null;
  onSubmit: (paidCents: number) => void;
};

export function PaymentDialog({
  open,
  onClose,
  title,
  totalCents,
  paidCents,
  currency,
  locale,
  pending,
  error,
  onSubmit,
}: PaymentDialogProps) {
  const { t } = useTranslation();
  const [value, setValue] = useState((paidCents / 100).toFixed(2));

  useEffect(() => {
    if (open) setValue((paidCents / 100).toFixed(2));
  }, [open, paidCents]);

  return (
    <Dialog
      open={open}
      onClose={onClose}
      title={title ?? t("business.sales.recordPayment")}
      size="sm"
      footer={
        <>
          <Button type="button" variant="outline" onClick={onClose} disabled={pending}>
            {t("common.cancel")}
          </Button>
          <Button
            type="button"
            onClick={() => onSubmit(Math.min(totalCents, Math.round(Number(value) * 100)))}
            disabled={pending || !Number.isFinite(Number(value))}
          >
            {pending ? t("common.saving") : t("common.save")}
          </Button>
        </>
      }
    >
      <div className="space-y-4">
        <div className="rounded-lg bg-secondary px-3 py-2.5 text-sm">
          <div className="flex items-center justify-between">
            <span className="text-muted-foreground">{t("common.total")}</span>
            <span className="font-semibold">{formatCents(totalCents, currency, locale)}</span>
          </div>
          <div className="mt-1 flex items-center justify-between">
            <span className="text-muted-foreground">{t("common.balance")}</span>
            <span className="font-semibold">
              {formatCents(Math.max(0, totalCents - paidCents), currency, locale)}
            </span>
          </div>
        </div>
        <Field label={t("business.sales.paidAmount")} htmlFor="payment-amount">
          <Input
            id="payment-amount"
            type="number"
            min="0"
            step="0.01"
            max={totalCents / 100}
            value={value}
            onChange={(event) => setValue(event.target.value)}
          />
        </Field>
        {error ? <p className="text-sm text-destructive">{error}</p> : null}
      </div>
    </Dialog>
  );
}
