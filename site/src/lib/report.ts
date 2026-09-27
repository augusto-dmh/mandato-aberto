/**
 * The error report: the page composes a `mailto:` on the visitor's machine and sends nothing itself
 * (plan door 4). Runs in the browser and in tests.
 */
import { CORRECTIONS_EMAIL } from "./site";

export interface Report {
  page: string;
  problem: string;
  source: string;
  email: string;
}

// A string, not a literal, so the shipped script carries the pattern exactly as the plan states it.
const SITE_PATH = new RegExp("^/[A-Za-z0-9/_-]{1,200}$");

const NOT_GIVEN = "(não informado)";

/** The `p` query when it is a path of this site, otherwise empty. */
export function pagePathFromQuery(search: string): string {
  const p = new URLSearchParams(search).get("p") ?? "";
  return SITE_PATH.test(p) ? p : "";
}

export function reportMailto(report: Report): string {
  const lines = [
    `Página com o erro: ${report.page}`,
    `O que está errado: ${report.problem}`,
    `Onde está o dado correto: ${report.source || NOT_GIVEN}`,
    `Seu e-mail: ${report.email || NOT_GIVEN}`,
  ];
  const subject = encodeURIComponent(`Erro em ${report.page}`);
  return `mailto:${CORRECTIONS_EMAIL}?subject=${subject}&body=${encodeURIComponent(lines.join("\n"))}`;
}
