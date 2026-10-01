import { spawnSync } from "node:child_process";
import { cpSync, mkdtempSync, readFileSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join, resolve } from "node:path";
import { describe, expect, it } from "vitest";

import { buildTokens, pairs, readTokens } from "../scripts/build-tokens.mjs";

const ROOT = resolve(__dirname, "..");
const TOKENS = join(ROOT, "tokens");
const FIXTURE = resolve(ROOT, "..", "site", "tests", "fixtures", "out");
const { base, directions } = readTokens(TOKENS);
const { css, errors } = buildTokens(TOKENS);

type Tree = Record<string, unknown>;
const leaves = (tree: Tree, path: string[] = []): string[][] =>
  Object.entries(tree)
    .filter(([k]) => !k.startsWith("$"))
    .flatMap(([k, v]) =>
      v && typeof v === "object" && "$value" in (v as Tree) ? [[...path, k]] : leaves(v as Tree, [...path, k]),
    );

/** The declarations block that follows a marker comment, up to the next marker or the end. */
function block(marker: string): string {
  const start = css.indexOf(`/* ${marker} */`);
  expect(start, `block ${marker}`).toBeGreaterThan(-1);
  const next = css.indexOf("/* ", start + 3);
  return css.slice(start, next === -1 ? undefined : next);
}

describe("tokens", () => {
  it("defines every token in four blocks", () => {
    expect(errors).toEqual([]);
    expect(Object.keys(directions).sort()).toEqual(["diario", "plenario"]);
    const root = css.slice(css.indexOf(":root"), css.indexOf("/* diario light */"));
    for (const path of leaves(base as Tree)) expect(root).toContain(`--ma-${path.join("-")}:`);
    for (const [name, direction] of Object.entries(directions)) {
      const all = leaves(direction as Tree);
      const colourVars = all.filter((p) => p[0] === "color").map((p) => `--ma-${p.join("-")}:`);
      const otherVars = all.filter((p) => p[0] !== "color").map((p) => `--ma-${p.join("-")}:`);
      expect(colourVars.length).toBeGreaterThan(0);
      const light = block(`${name} light`);
      const dark = block(`${name} dark`);
      for (const v of [...colourVars, ...otherVars]) expect(light).toContain(v);
      for (const v of colourVars) {
        // dark values appear twice: under the system preference and under an explicit data-theme="dark"
        expect(dark.split(v).length - 1).toBe(2);
      }
    }
  });

  it("every declared pair meets its floor", () => {
    for (const [name, direction] of Object.entries(directions)) {
      const all = pairs(direction);
      expect(new Set(all.map((p: { theme: string }) => p.theme))).toEqual(new Set(["light", "dark"]));
      for (const p of all) {
        expect(p.ratio, `${name} ${p.theme} ${p.fg} on ${p.bg}`).toBeGreaterThanOrEqual(p.min);
        expect([4.5, 3]).toContain(p.min);
      }
    }
  });

  it("every text token is paired with every surface", () => {
    for (const direction of Object.values(directions) as Tree[]) {
      const colour = direction.color as Record<string, { $extensions: { mandato: { role: string } } }>;
      const byRole = (role: string) => Object.keys(colour).filter((k) => colour[k].$extensions.mandato.role === role);
      const expected = ["light", "dark"].flatMap((theme) =>
        byRole("text").flatMap((fg) => byRole("surface").map((bg) => `${theme} ${fg}/${bg} 4.5`)),
      );
      expect(byRole("text").length).toBeGreaterThan(0);
      expect(byRole("surface").length).toBeGreaterThan(0);
      const actual = pairs(direction)
        .filter((p: { min: number }) => p.min === 4.5)
        .map((p: { theme: string; fg: string; bg: string; min: number }) => `${p.theme} ${p.fg}/${p.bg} ${p.min}`);
      expect(actual.sort()).toEqual(expected.sort());
    }
  });

  it("fails the build on a pair below its floor", () => {
    const dir = mkdtempSync(join(tmpdir(), "mandato-tokens-"));
    cpSync(TOKENS, dir, { recursive: true });
    const file = join(dir, "diario.json");
    const tokens = JSON.parse(readFileSync(file, "utf8"));
    tokens.color.muted.$extensions.mandato.dark = "oklch(0.3 0.01 70)";
    writeFileSync(file, JSON.stringify(tokens));
    const run = spawnSync("node", [join(ROOT, "scripts", "build-tokens.mjs"), dir, join(dir, "out.css")], {
      encoding: "utf8",
    });
    expect(run.status).not.toBe(0);
    expect(run.stderr).toMatch(/diario dark muted on paper: \d+\.\d{2}:1 is below 4\.5:1/);
  });

  it("one accent and no valence names", () => {
    const parties = new Set<string>();
    for (const d of JSON.parse(readFileSync(join(FIXTURE, "deputies.json"), "utf8"))) parties.add(d.party.toLowerCase());
    const banned = ["yes", "no", "good", "bad", "success", "danger", "warning", ...parties];
    for (const direction of Object.values(directions) as Tree[]) {
      const names = Object.keys(direction.color as Tree);
      expect(names.filter((n) => n.startsWith("accent"))).toHaveLength(1);
      for (const n of names) for (const word of banned) expect(n.split("-")).not.toContain(word);
    }
  });

  it("at most ten type sizes", () => {
    for (const direction of Object.values(directions) as Tree[]) {
      const sizes = leaves(direction.type as Tree).filter((p) => p.at(-1) === "size");
      expect(sizes.length).toBeGreaterThan(0);
      expect(sizes.length).toBeLessThanOrEqual(10);
    }
  });

  it("numeric class sets tabular lining figures", () => {
    const styles = readFileSync(join(ROOT, "styles", "components.css"), "utf8");
    const rule = /\.ma-num\s*\{([^}]*)\}/.exec(styles);
    expect(rule).not.toBeNull();
    expect(rule![1]).toMatch(/font-variant-numeric:\s*tabular-nums lining-nums/);
  });
});
