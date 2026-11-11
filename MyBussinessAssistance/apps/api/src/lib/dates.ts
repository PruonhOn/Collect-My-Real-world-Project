const CAMBODIA_OFFSET_MS = 7 * 60 * 60 * 1_000;
const DAY_MS = 24 * 60 * 60 * 1_000;

export function startOfMonth(dateMs = Date.now()) {
  const local = new Date(dateMs + CAMBODIA_OFFSET_MS);
  const startLocal = Date.UTC(local.getUTCFullYear(), local.getUTCMonth(), 1);
  return startLocal - CAMBODIA_OFFSET_MS;
}

export function startOfNextMonth(dateMs = Date.now()) {
  const local = new Date(dateMs + CAMBODIA_OFFSET_MS);
  const startLocal = Date.UTC(
    local.getUTCFullYear(),
    local.getUTCMonth() + 1,
    1,
  );
  return startLocal - CAMBODIA_OFFSET_MS;
}

export function startOfDay(dateMs = Date.now()) {
  const local = new Date(dateMs + CAMBODIA_OFFSET_MS);
  const startLocal = Date.UTC(
    local.getUTCFullYear(),
    local.getUTCMonth(),
    local.getUTCDate(),
  );
  return startLocal - CAMBODIA_OFFSET_MS;
}

export function startOfDaysAgo(days: number, dateMs = Date.now()) {
  return startOfDay(dateMs - (days - 1) * DAY_MS);
}

export function localDateKey(dateMs: number) {
  const local = new Date(dateMs + CAMBODIA_OFFSET_MS);
  return `${local.getUTCFullYear()}-${String(local.getUTCMonth() + 1).padStart(
    2,
    "0",
  )}-${String(local.getUTCDate()).padStart(2, "0")}`;
}

export function defaultReportRange() {
  const now = Date.now();
  return { from: startOfDaysAgo(30, now), to: now };
}
