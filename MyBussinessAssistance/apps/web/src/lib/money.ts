import type { SupportedCurrency } from "@mba/shared";
import { formatCurrency, formatNumber } from "./format";

export { formatNumber };

export function formatCents(
  cents: number,
  currency: string = "USD",
  locale = "en",
) {
  return formatCurrency(
    cents / 100,
    currency as SupportedCurrency,
    locale,
  );
}

export function formatQuantity(value: number, locale = "en") {
  return formatNumber(value, locale);
}

export function centsToInput(cents: number) {
  return (cents / 100).toFixed(2);
}

export function inputToCents(value: string) {
  const parsed = Number(value);
  if (!Number.isFinite(parsed)) return 0;
  return Math.round(parsed * 100);
}

export function formatCentsInput(cents: number) {
  return cents % 100 === 0
    ? String(cents / 100)
    : (cents / 100).toFixed(2);
}

export function clampPercent(value: number) {
  return Math.min(100, Math.max(0, value));
}
