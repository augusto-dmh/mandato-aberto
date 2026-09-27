import { mkdtempSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { extname, join, resolve } from "node:path";
import { afterEach, describe, expect, it, vi } from "vitest";

import { withBase } from "../src/lib/urls";

const SRC = resolve(__dirname, "..", "src");
const SCANNED = new Set([".astro", ".vue", ".ts", ".md"]);
/**
 * A site-relative link written without `withBase`: `href="/`, `src="/` and the expression forms that
 * write the same root link (`href={\`/`, `:href="\`/`, `src={"/`). Protocol-relative `//` is not a site path.
 */
const ROOT_LINK = /(?<![\w-]):?(?:href|src)=(?:"`|"|'|\{\s*[`"'])\/(?!\/)/;

function findRootLinks(dir: string, exclude: string[] = []): string[] {
  const hits: string[] = [];
  const walk = (current: string) => {
    for (const name of readdirSync(current).sort()) {
      const path = join(current, name);
      if (statSync(path).isDirectory()) walk(path);
      else if (SCANNED.has(extname(name)) && !exclude.includes(path) && ROOT_LINK.test(readFileSync(path, "utf8"))) hits.push(path);
    }
  };
  walk(dir);
  return hits;
}

afterEach(() => vi.unstubAllEnvs());

describe("withBase", () => {
  it("withBase joins the base", () => {
    const cases: [string, string, string][] = [
      ["/mandato-aberto/", "/", "/mandato-aberto/"],
      ["/mandato-aberto/", "/deputados/1/", "/mandato-aberto/deputados/1/"],
      ["/mandato-aberto/", "/cards/site.png", "/mandato-aberto/cards/site.png"],
      ["/mandato-aberto", "/", "/mandato-aberto/"],
      ["/mandato-aberto", "/deputados/1/", "/mandato-aberto/deputados/1/"],
      ["/mandato-aberto", "/cards/site.png", "/mandato-aberto/cards/site.png"],
      ["/", "/", "/"],
      ["/", "/deputados/1/", "/deputados/1/"],
      ["/", "/cards/site.png", "/cards/site.png"],
    ];
    for (const [base, path, expected] of cases) {
      vi.stubEnv("BASE_URL", base);
      expect(withBase(path), `${base} + ${path}`).toBe(expected);
    }
  });
});

describe("root links", () => {
  it("site source writes no root link", () => {
    expect(findRootLinks(SRC, [join(SRC, "lib", "urls.ts"), join(SRC, "lib", "forbidden-terms.ts")])).toEqual([]);
  });

  it("root link scan names the file", () => {
    const dir = mkdtempSync(join(tmpdir(), "mandato-links-"));
    writeFileSync(join(dir, "a.astro"), '<a href="/x/">x</a>\n');
    writeFileSync(join(dir, "b.vue"), '<template><img src="/y.png" /></template>\n');
    writeFileSync(join(dir, "c.astro"), "<a href={`/z/${id}/`}>z</a>\n");
    writeFileSync(join(dir, "ok.astro"), '<a href={withBase("/x/")}>x</a>\n');
    expect(findRootLinks(dir)).toEqual([join(dir, "a.astro"), join(dir, "b.vue"), join(dir, "c.astro")]);
    rmSync(dir, { recursive: true });
  });
});
