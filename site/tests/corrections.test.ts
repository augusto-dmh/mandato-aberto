import { existsSync, mkdtempSync, rmSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join, resolve } from "node:path";
import { describe, expect, it } from "vitest";

import { correctionsDir, readCorrections, statusLabel } from "../src/lib/corrections";

const FIXTURE = join(__dirname, "fixtures", "corrections");
const REPO = resolve(__dirname, "..", "..");

describe("corrections", () => {
  it("reads records newest first", () => {
    const records = readCorrections(FIXTURE);
    expect(records.map((r) => r.id)).toEqual(["2026-09-25-votacao-100-1", "2026-09-20-participacao-101"]);
    const [reply, corrected] = records;
    expect(reply).toMatchObject({
      receivedAt: "2026-09-25",
      pages: ["/votacoes/100-1/", "/deputados/102/"],
      status: "reply-published",
      resolvedAt: null,
      action: null,
    });
    expect(reply.body).not.toBe("");
    expect(reply.reply).not.toBeNull();
    expect(corrected).toMatchObject({
      resolvedAt: "2026-09-22",
      action: "Base de cálculo da participação corrigida (PR #9)",
      reply: null,
    });
  });

  it.each([
    ["triage", "Em análise"],
    ["corrected", "Corrigido"],
    ["reply-published", "Resposta publicada"],
    ["no-change", "Sem alteração"],
  ])("status labels: %s", (status, label) => {
    expect(statusLabel(status)).toBe(label);
  });

  it("status labels: any other value throws", () => {
    expect(() => statusLabel("fixed")).toThrow();
  });

  it("rejects a malformed record", () => {
    const records: Record<string, string> = {
      "2026-09-01-a.md": "pages: [/deputados/101/]\nstatus: triage",
      "2026-09-02-b.md": "receivedAt: 2026-09-02\nstatus: triage",
      "2026-09-03-c.md": "receivedAt: 2026-09-03\npages: [/deputados/101/]",
      "2026-09-04-d.md": "receivedAt: 2026-09-04\npages: [/deputados/101/]\nstatus: fixed",
      "2026-09-05-e.md": "receivedAt: 2026-09-06\npages: [/deputados/101/]\nstatus: triage",
    };
    for (const [name, frontmatter] of Object.entries(records)) {
      const dir = mkdtempSync(join(tmpdir(), "mandato-corrections-"));
      writeFileSync(join(dir, name), `---\n${frontmatter}\n---\n\nRelato.\n`);
      expect(() => readCorrections(dir), name).toThrow(name);
      rmSync(dir, { recursive: true });
    }
  });

  it("default directory", () => {
    const saved = process.env.MANDATO_CORRECTIONS_DIR;
    delete process.env.MANDATO_CORRECTIONS_DIR;
    try {
      expect(correctionsDir()).toBe(join(REPO, "corrections"));
    } finally {
      if (saved !== undefined) process.env.MANDATO_CORRECTIONS_DIR = saved;
    }
    expect(existsSync(join(REPO, "corrections", ".gitkeep"))).toBe(true);
    // Real records may land here after launch; the marker file is never one of them.
    const ids = readCorrections(join(REPO, "corrections")).map((r) => r.id);
    expect(ids.some((id) => id.includes("gitkeep"))).toBe(false);
  });
});
