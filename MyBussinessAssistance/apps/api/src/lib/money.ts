export function taxFromSubtotal(
  subtotalCents: number,
  rateBasisPoints: number,
) {
  if (!Number.isFinite(subtotalCents) || !Number.isFinite(rateBasisPoints)) {
    return 0;
  }
  return Math.max(0, Math.round((subtotalCents * rateBasisPoints) / 10_000));
}

export function paymentStatus(totalCents: number, paidCents: number) {
  if (paidCents <= 0) return "unpaid";
  if (paidCents >= totalCents) return "paid";
  return "partial";
}

export function normalizeDiscount(discountCents: number, subtotalCents: number) {
  return Math.min(Math.max(0, discountCents), Math.max(0, subtotalCents));
}
