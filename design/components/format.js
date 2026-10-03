// pt-BR formatting shared by every component; mirrors the MVP's site/src/lib/format.ts.

const NUMBER = new Intl.NumberFormat("pt-BR");

export const formatNumber = (n) => NUMBER.format(n);

/** `YYYY-MM-DD` or an ISO timestamp -> `DD/MM/AAAA`, without timezone shifts. */
export function formatDate(value) {
  const [y, m, d] = value.slice(0, 10).split("-");
  return `${d}/${m}/${y}`;
}

/** Collection timestamp (UTC, as the ETL writes it) -> its calendar day in Brasília, `DD/MM/AAAA`; mirrors the MVP's brasiliaLocal. */
export function formatCollected(generatedAt) {
  const shifted = new Date(new Date(generatedAt).getTime() - 3 * 60 * 60 * 1000);
  return formatDate(shifted.toISOString());
}

/** First letter of the first and last words of a name. */
export function initials(name) {
  const words = name.trim().split(/\s+/);
  const first = words[0]?.[0] ?? "";
  const last = words.length > 1 ? words.at(-1)[0] : "";
  return (first + last).toUpperCase();
}

export const NO_BASE = "Sem base de cálculo no período";
