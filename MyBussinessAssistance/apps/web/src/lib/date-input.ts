function pad(value: number) {
  return String(value).padStart(2, "0");
}

export function toDateInput(ms: number) {
  const date = new Date(ms);
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(
    date.getDate(),
  )}`;
}

export function fromDateInput(value: string) {
  if (!value) return undefined;
  const [year, month, day] = value.split("-").map(Number);
  if (!year || !month || !day) return undefined;
  return new Date(year, month - 1, day).getTime();
}

export function monthStartInput() {
  const now = new Date();
  return toDateInput(new Date(now.getFullYear(), now.getMonth(), 1).getTime());
}

export function todayInput() {
  return toDateInput(Date.now());
}
