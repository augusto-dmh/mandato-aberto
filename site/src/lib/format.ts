/** Copy formatting shared by pages and cards. Every number is shown as a count over its base (AD-004). */
import type { Period } from "./data";

const numbers = new Intl.NumberFormat("pt-BR");
const brasilia = new Intl.DateTimeFormat("pt-BR", {
  timeZone: "America/Sao_Paulo",
  day: "2-digit",
  month: "2-digit",
  year: "numeric",
  hour: "2-digit",
  minute: "2-digit",
  hourCycle: "h23",
});

export const formatNumber = (n: number) => numbers.format(n);

export const formatCount = (count: number, total: number) => `${formatNumber(count)} de ${formatNumber(total)}`;

export const NO_BASE = "Sem base de cálculo no período";

function brasiliaParts(iso: string) {
  const parts = Object.fromEntries(brasilia.formatToParts(new Date(iso)).map((p) => [p.type, p.value]));
  return { date: `${parts.day}/${parts.month}/${parts.year}`, time: `${parts.hour}:${parts.minute}` };
}

/** `generatedAt` (UTC) as the collection line every page carries. */
export function collectedAt(generatedAt: string): string {
  const { date, time } = brasiliaParts(generatedAt);
  return `Dados coletados em ${date} às ${time} (horário de Brasília)`;
}

/** `YYYY-MM-DD` (or a local timestamp starting with it) as `DD/MM/AAAA`. */
export function formatDate(value: string): string {
  const [year, month, day] = value.slice(0, 10).split("-");
  return `${day}/${month}/${year}`;
}

/** `generatedAt` in Brasília local time, in the ETL's `YYYY-MM-DDTHH:MM:SS` period format. */
function brasiliaLocal(generatedAt: string): string {
  const shifted = new Date(new Date(generatedAt).getTime() - 3 * 60 * 60 * 1000);
  return shifted.toISOString().slice(0, 19);
}

/** The ETL closes the current period at build time, so a period ending then is still open. */
export function periodLabel(period: Period, generatedAt: string): string {
  if (period.end === brasiliaLocal(generatedAt)) return `desde ${formatDate(period.start)}`;
  return `${formatDate(period.start)} a ${formatDate(period.end)}`;
}

export function voteLabel(vote: string): string {
  if (vote === "Artigo 17") return "Art. 17 (presidente da sessão)";
  if (vote === "") return "Registro sem voto";
  return vote;
}

export const organLabel = (organ: string) => (organ === "PLEN" ? "Plenário" : organ);

export function resultLabel(approved: boolean | null): string {
  if (approved === true) return "Aprovada";
  if (approved === false) return "Rejeitada";
  return "Resultado não informado";
}

export const sortKey = (name: string) =>
  name
    .normalize("NFD")
    .replace(/\p{Diacritic}/gu, "")
    .toLocaleLowerCase("pt-BR");

export const byName = <T extends { name: string }>(a: T, b: T) => {
  const ka = sortKey(a.name);
  const kb = sortKey(b.name);
  return ka < kb ? -1 : ka > kb ? 1 : 0;
};

const GROUPS: [string, string][] = [
  ["Sim", "Sim"],
  ["Não", "Não"],
  ["Abstenção", "Abstenção"],
  ["Obstrução", "Obstrução"],
  ["Artigo 17", "Art. 17"],
  ["", "Registro sem voto"],
];

/** Votes of one roll call grouped by value in a fixed order; any other value follows, alphabetically. */
export function groupVotes<T extends { name: string; vote: string }>(votes: T[]) {
  const known = new Set(GROUPS.map(([value]) => value));
  const others = [...new Set(votes.map((v) => v.vote).filter((v) => !known.has(v)))].sort();
  return [...GROUPS, ...others.map((value): [string, string] => [value, value])]
    .map(([value, label]) => ({ value, label, entries: votes.filter((v) => v.vote === value).sort(byName) }))
    .filter((group) => group.entries.length > 0);
}
