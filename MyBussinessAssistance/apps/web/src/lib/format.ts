import type { SupportedCurrency } from "@mba/shared";

function intlLocale(locale: string) {
  return locale === "km" ? "km-KH" : "en-US";
}

export function formatCurrency(
  value: number,
  currency: SupportedCurrency = "USD",
  locale = "en",
) {
  return new Intl.NumberFormat(intlLocale(locale), {
    style: "currency",
    currency,
    maximumFractionDigits: currency === "KHR" ? 0 : 2,
    minimumFractionDigits: currency === "KHR" ? 0 : 2,
  }).format(value);
}

export function formatNumber(value: number, locale = "en") {
  return new Intl.NumberFormat(intlLocale(locale)).format(value);
}

export function formatPercent(value: number, locale = "en") {
  return new Intl.NumberFormat(intlLocale(locale), {
    style: "percent",
    maximumFractionDigits: 1,
  }).format(value);
}

export function formatDate(value: Date | string | number, locale = "en") {
  const date = value instanceof Date ? value : new Date(value);
  return new Intl.DateTimeFormat(intlLocale(locale), {
    timeZone: "Asia/Phnom_Penh",
    year: "numeric",
    month: "short",
    day: "numeric",
  }).format(date);
}

export function formatDateTime(value: Date | string | number, locale = "en") {
  const date = value instanceof Date ? value : new Date(value);
  return new Intl.DateTimeFormat(intlLocale(locale), {
    timeZone: "Asia/Phnom_Penh",
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(date);
}
