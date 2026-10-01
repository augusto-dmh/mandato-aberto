// pt-BR formatting shared by every component; mirrors the MVP's site/src/lib/format.ts.

const NUMBER = new Intl.NumberFormat("pt-BR");

export const formatNumber = (n) => NUMBER.format(n);

/** `YYYY-MM-DD` or an ISO timestamp -> `DD/MM/AAAA`, without timezone shifts. */
export function formatDate(value) {
  const [y, m, d] = value.slice(0, 10).split("-");
  return `${d}/${m}/${y}`;
}

/** First letter of the first and last words of a name. */
export function initials(name) {
  const words = name.trim().split(/\s+/);
  const first = words[0]?.[0] ?? "";
  const last = words.length > 1 ? words.at(-1)[0] : "";
  return (first + last).toUpperCase();
}

export const NO_BASE = "Sem base de cálculo no período";
