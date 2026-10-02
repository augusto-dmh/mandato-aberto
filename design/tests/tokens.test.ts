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
    // just under the floor on one surface only: 4.11:1 against raised, 4.58:1 against paper
    tokens.color.muted.$extensions.mandato.dark = "oklch(0.6 0.01 70)";
    writeFileSync(file, JSON.stringify(tokens));
    const run = spawnSync("node", [join(ROOT, "scripts", "build-tokens.mjs"), dir, join(dir, "out.css")], {
      encoding: "utf8",
    });
    expect(run.status).not.toBe(0);
    expect(run.stderr).toContain("diario dark muted on raised: 4.11:1 is below 4.5:1");
    expect(run.stderr).not.toContain("muted on paper");
  });

  it("one accent and no valence names", () => {
    const parties = new Set<string>();
    for (const d of JSON.parse(readFileSync(join(FIXTURE, "deputies.json"), "utf8"))) parties.add(d.party.toLowerCase());
    // parties with seats in the 57th legislature, from the contract's vote records (data/out, 2026-09-27)
    for (const p of ["avante", "cidadania", "mdb", "novo", "patriota", "pcdob", "pdt", "pl", "pode", "pp", "pros", "psb", "psc", "psd", "psdb", "psol", "pt", "ptb", "pv", "rede", "republicanos", "solidariedade", "união"])
      parties.add(p);
    const banned = ["yes", "no", "good", "bad", "success", "danger", "warning", ...parties];
    for (const direction of Object.values(directions) as Tree[]) {
      const names = Object.keys(direction.color as Tree);
      expect(names.filter((n) => n.startsWith("accent"))).toHaveLength(1);
      for (const n of names) for (const word of banned) expect(n.split("-")).not.toContain(word);
    }
  });

  it("accents keep away from the gov.br blues", () => {
    const srgbToOklab = (hex: string) => {
      const [r, g, b] = [1, 3, 5]
        .map((i) => parseInt(hex.slice(i, i + 2), 16) / 255)
        .map((u) => (u <= 0.04045 ? u / 12.92 : ((u + 0.055) / 1.055) ** 2.4));
      const l = Math.cbrt(0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b);
      const m = Math.cbrt(0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b);
      const q = Math.cbrt(0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b);
      return [
        0.2104542553 * l + 0.793617785 * m - 0.0040720468 * q,
        1.9779984951 * l - 2.428592205 * m + 0.4505937099 * q,
        0.0259040371 * l + 0.7827717662 * m - 0.808885698 * q,
      ];
    };
    const oklab = (value: string) => {
      const [L, C, h] = /oklch\(([\d.]+) ([\d.]+) ([\d.]+)\)/.exec(value)!.slice(1).map(Number);
      return [L, C * Math.cos((h * Math.PI) / 180), C * Math.sin((h * Math.PI) / 180)];
    };
    const gov = ["#1351B4", "#155BCB"].map(srgbToOklab);
    expect(gov[0][0]).toBeCloseTo(0.47, 1); // sanity: the reference blue converts to a mid lightness
    for (const [name, direction] of Object.entries(directions) as [string, Tree][]) {
      const accent = (direction.color as Record<string, { $value: string; $extensions: { mandato: { dark: string } } }>).accent;
      for (const value of [accent.$value, accent.$extensions.mandato.dark]) {
        for (const g of gov) {
          const [a, b] = [oklab(value), g];
          expect(Math.hypot(a[0] - b[0], a[1] - b[1], a[2] - b[2]), `${name} ${value}`).toBeGreaterThanOrEqual(0.1);
        }
      }
    }
  });

  it("photo mat stays light in the dark theme", () => {
    for (const direction of Object.values(directions) as Tree[]) {
      const colour = direction.color as Record<string, { $value: string; $extensions: { mandato: { dark: string; role: string } } }>;
      expect(colour.mat.$value).toBe(colour.raised.$value);
      expect(colour.mat.$extensions.mandato.dark).toBe(colour.raised.$value);
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
