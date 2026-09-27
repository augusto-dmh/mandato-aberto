/**
 * Words the site never writes in its own copy (AGENTS.md, AD-004, legal research section 2.3).
 * Official text quoted from the Câmara - a proposition summary, a roll-call description - is not
 * our copy and is not checked here.
 */
import { readdirSync, readFileSync, statSync } from "node:fs";
import { extname, join } from "node:path";

export const FORBIDDEN_TERMS = [
  "faltou",
  "faltas",
  "ausente",
  "ausência",
  "presença",
  "aprovação",
  "intenção de voto",
  "favorito",
  "ranking",
  "líder",
  "chance de reeleição",
  "pesquisa",
  "mentiu",
  "traiu",
  "corrupto",
  "governista",
  "fiel",
  "infiel",
  "rebelde",
];

const escape = (text: string) => text.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

/** Whole word, case-insensitive, any run of whitespace between words. */
export const termPattern = (term: string) =>
  new RegExp(`(?<![\\p{L}\\p{N}])${term.split(" ").map(escape).join("\\s+")}(?![\\p{L}\\p{N}])`, "iu");

const SCANNED = new Set([".astro", ".vue", ".ts"]);

export function findForbidden(dir: string, options: { exclude?: string[] } = {}) {
  const hits: { file: string; term: string }[] = [];
  const walk = (current: string) => {
    for (const name of readdirSync(current).sort()) {
      const path = join(current, name);
      if (statSync(path).isDirectory()) walk(path);
      else if (SCANNED.has(extname(name)) && !options.exclude?.includes(path)) {
        const text = readFileSync(path, "utf8");
        for (const term of FORBIDDEN_TERMS) if (termPattern(term).test(text)) hits.push({ file: path, term });
      }
    }
  };
  walk(dir);
  return hits;
}
