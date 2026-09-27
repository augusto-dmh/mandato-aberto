/**
 * Correction records (plan door 3): one `corrections/YYYY-MM-DD-<slug>.md` per reported error, with a
 * fixed frontmatter and an optional `## Resposta` section holding the parliamentarian's reply verbatim.
 * A malformed record fails the build, naming the file.
 */
import { existsSync, readdirSync, readFileSync } from "node:fs";
import { join, resolve } from "node:path";

const STATUS_LABELS: Record<string, string> = {
  triage: "Em análise",
  corrected: "Corrigido",
  "reply-published": "Resposta publicada",
  "no-change": "Sem alteração",
};

export interface Correction {
  /** The file name without `.md`. */
  id: string;
  receivedAt: string;
  pages: string[];
  status: string;
  resolvedAt: string | null;
  action: string | null;
  body: string;
  reply: string | null;
}

/** `MANDATO_CORRECTIONS_DIR`, resolved against the site directory; defaults to the repository's `corrections`. */
export function correctionsDir(): string {
  return resolve(process.cwd(), process.env.MANDATO_CORRECTIONS_DIR || "../corrections");
}

export function statusLabel(status: string): string {
  const label = STATUS_LABELS[status];
  if (!label) throw new Error(`Unknown correction status: ${status}`);
  return label;
}

const DATE = /^\d{4}-\d{2}-\d{2}$/;
const unquote = (value: string) => value.trim().replace(/^(["'])(.*)\1$/, "$2");

/** The fixed keys only: `key: value` lines, and `pages` as `[a, b]` or as `- a` lines. */
function parseFrontmatter(text: string): Record<string, string | string[]> {
  const fields: Record<string, string | string[]> = {};
  let list: string[] | undefined;
  for (const line of text.split("\n")) {
    if (!line.trim()) continue;
    const item = /^\s+-\s+(.*)$/.exec(line);
    if (item && list) {
      list.push(unquote(item[1]));
      continue;
    }
    const pair = /^([A-Za-z]+):\s*(.*)$/.exec(line);
    if (!pair) throw new Error(`unreadable frontmatter line: ${line}`);
    const [, key, value] = pair;
    if (value === "") {
      list = [];
      fields[key] = list;
    } else if (value.startsWith("[") && value.endsWith("]")) {
      list = undefined;
      fields[key] = value.slice(1, -1).split(",").map(unquote).filter(Boolean);
    } else {
      list = undefined;
      fields[key] = unquote(value);
    }
  }
  return fields;
}

function parse(file: string, text: string): Correction {
  const match = /^---\n([\s\S]*?)\n---\n?([\s\S]*)$/.exec(text.replace(/\r\n/g, "\n"));
  if (!match) throw new Error("no frontmatter");
  const fields = parseFrontmatter(match[1]);
  const string = (key: string) => {
    const value = fields[key];
    if (value === undefined) return null;
    if (typeof value !== "string") throw new Error(`${key} must be a single value`);
    return value;
  };
  const receivedAt = string("receivedAt");
  if (receivedAt === null) throw new Error("missing receivedAt");
  if (!DATE.test(receivedAt)) throw new Error(`receivedAt must be YYYY-MM-DD, got ${receivedAt}`);
  if (!file.startsWith(`${receivedAt}-`)) throw new Error(`file name does not start with its receivedAt ${receivedAt}`);
  const pages = fields.pages;
  if (pages === undefined) throw new Error("missing pages");
  if (!Array.isArray(pages) || pages.length === 0) throw new Error("pages must be a non-empty list of site paths");
  for (const path of pages) if (!path.startsWith("/")) throw new Error(`pages must be site paths, got ${path}`);
  const status = string("status");
  if (status === null) throw new Error("missing status");
  statusLabel(status);
  const resolvedAt = string("resolvedAt");
  if (resolvedAt !== null && !DATE.test(resolvedAt)) throw new Error(`resolvedAt must be YYYY-MM-DD, got ${resolvedAt}`);

  const [body, reply] = match[2].split(/^## Resposta[ \t]*$/m);
  return {
    id: file.replace(/\.md$/, ""),
    receivedAt,
    pages,
    status,
    resolvedAt,
    action: string("action"),
    body: body.trim(),
    reply: reply === undefined ? null : reply.trim(),
  };
}

/** Every `*.md` record in `dir`, newest `receivedAt` first. */
export function readCorrections(dir: string): Correction[] {
  if (!existsSync(dir)) throw new Error(`No corrections directory at ${dir}`);
  const records = readdirSync(dir)
    .filter((name) => name.endsWith(".md"))
    .map((name) => {
      try {
        return parse(name, readFileSync(join(dir, name), "utf8"));
      } catch (error) {
        throw new Error(`Invalid correction record ${name}: ${(error as Error).message}`);
      }
    });
  return records.sort((a, b) => b.receivedAt.localeCompare(a.receivedAt) || b.id.localeCompare(a.id));
}

/** Blank-line separated paragraphs of a record's body or reply. */
export const paragraphs = (text: string) => text.split(/\n\s*\n/).map((p) => p.replace(/\s+/g, " ").trim()).filter(Boolean);
