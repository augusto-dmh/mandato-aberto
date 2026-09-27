import { mkdtempSync, rmSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join, resolve } from "node:path";
import { describe, expect, it } from "vitest";

import { FORBIDDEN_TERMS, findForbidden } from "../src/lib/forbidden-terms";

const SRC = resolve(__dirname, "..", "src");

describe("descriptive language", () => {
  it("forbidden list holds every required term", () => {
    const required = [
      "faltou",
      "faltas",
      "ausente",
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
      "ausência",
      "presença",
    ];
    for (const term of required) expect(FORBIDDEN_TERMS).toContain(term);
  });

  it("site source uses no forbidden term", () => {
    const hits = findForbidden(SRC, { exclude: [join(SRC, "lib", "forbidden-terms.ts")] });
    expect(hits).toEqual([]);
  });

  it("scan catches a forbidden term in a template", () => {
    const dir = mkdtempSync(join(tmpdir(), "mandato-terms-"));
    writeFileSync(join(dir, "Page.astro"), "<p>Faltou à sessão</p>\n");
    writeFileSync(join(dir, "ok.ts"), "export const label = 'fielmente descrito';\n");
    writeFileSync(join(dir, "notes.txt"), "faltou\n");
    expect(findForbidden(dir)).toEqual([{ file: join(dir, "Page.astro"), term: "faltou" }]);
    rmSync(dir, { recursive: true });
  });

  it("scan covers markdown", () => {
    const dir = mkdtempSync(join(tmpdir(), "mandato-terms-"));
    writeFileSync(join(dir, "page.md"), "Faltou à sessão\n");
    writeFileSync(join(dir, "ok.ts"), "export const label = 'fielmente descrito';\n");
    expect(findForbidden(dir)).toEqual([{ file: join(dir, "page.md"), term: "faltou" }]);
    rmSync(dir, { recursive: true });
  });
});
