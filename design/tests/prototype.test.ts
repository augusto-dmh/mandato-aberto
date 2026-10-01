import { spawnSync } from "node:child_process";
import { cpSync, existsSync, mkdtempSync, readFileSync, readdirSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join, resolve } from "node:path";
import { Window } from "happy-dom";
import { beforeAll, describe, expect, it } from "vitest";

import { FORBIDDEN_TERMS, termPattern } from "../../site/src/lib/forbidden-terms";

const ROOT = resolve(__dirname, "..");
const FIXTURE = resolve(ROOT, "..", "site", "tests", "fixtures", "out");
const OUT = mkdtempSync(join(tmpdir(), "mandato-prototype-"));
const DIRECTIONS = ["diario", "plenario"];
const SCREENS = ["profile", "roll-call", "card"];
const deputies = JSON.parse(readFileSync(join(FIXTURE, "deputies.json"), "utf8"));
const rollCalls = JSON.parse(readFileSync(join(FIXTURE, "roll-calls.json"), "utf8"));
// fixture defaults: in-exercise deputy with the longest name; plenary roll call with the most votes
const DEPUTY = [...deputies].filter((d) => d.inExercise).sort((a, b) => b.name.length - a.name.length || a.id - b.id)[0];
const total = (r: { tallies: Record<string, number> }) => r.tallies.yes + r.tallies.no + r.tallies.others;
const ROLL_CALL = [...rollCalls].filter((r) => r.organ === "PLEN").sort((a, b) => total(b) - total(a) || a.id.localeCompare(b.id))[0];

function prototype(data: string, out: string) {
  return spawnSync("node", ["scripts/prototype.mjs", "--out", out], {
    cwd: ROOT,
    encoding: "utf8",
    env: { ...process.env, MANDATO_DATA: data, MANDATO_PHOTO_CACHE: join(OUT, "no-photos") },
  });
}

const page = (direction: string, screen: string) => readFileSync(join(OUT, direction, `${screen}.html`), "utf8");
const parse = (html: string) => {
  const w = new Window();
  w.document.write(html);
  return w.document as unknown as Document;
};

beforeAll(() => {
  expect(spawnSync("node", ["scripts/build-tokens.mjs"], { cwd: ROOT }).status).toBe(0);
  const run = prototype(FIXTURE, OUT);
  expect(run.status, run.stderr).toBe(0);
});

describe("prototype", () => {
  it("writes three screens per direction", () => {
    const [deputy, rollCall] = [DEPUTY, ROLL_CALL];
    for (const d of DIRECTIONS) {
      expect(readdirSync(join(OUT, d)).sort()).toEqual(["card.html", "profile.html", "roll-call.html"]);
      expect(parse(page(d, "profile")).querySelector("h1")?.textContent).toBe(deputy.name);
      expect(parse(page(d, "card")).querySelector("h1")?.textContent).toBe(deputy.name);
      const rc = parse(page(d, "roll-call"));
      expect(rc.querySelector("h1")?.textContent).toBe(rollCall.proposition?.title ?? rollCall.description);
      expect(rc.querySelector(`[id="votacao-${rollCall.id}"]`)).not.toBeNull();
    }
  });

  it("rejects another schema version", () => {
    const data = mkdtempSync(join(tmpdir(), "mandato-v1-"));
    cpSync(FIXTURE, data, { recursive: true });
    const meta = JSON.parse(readFileSync(join(data, "meta.json"), "utf8"));
    writeFileSync(join(data, "meta.json"), JSON.stringify({ ...meta, schema_version: 1 }));
    const run = prototype(data, join(data, "out"));
    expect(run.status).not.toBe(0);
    expect(run.stderr).toContain("schema_version 1");
    expect(existsSync(join(data, "out"))).toBe(false);
  });

  it("screens read without javascript", () => {
    const deputy = JSON.parse(readFileSync(join(FIXTURE, "deputies", `${DEPUTY.id}.json`), "utf8"));
    for (const d of DIRECTIONS) {
      for (const s of SCREENS) expect(page(d, s), `${d} ${s}`).not.toMatch(/<script/i);
      const doc = parse(page(d, "profile"));
      const first = doc.querySelector(".ma-ndem")!;
      expect(first.querySelector(".ma-ndem__n")?.textContent?.trim()).toBe(String(deputy.participation.count));
      expect(first.querySelector(".ma-ndem__m")?.textContent?.replace(/\s+/g, " ").trim()).toBe(`de ${deputy.participation.total}`);
    }
  });

  it("fonts are local OFL files", () => {
    const css = readFileSync(join(OUT, "fonts", "fonts.css"), "utf8");
    const urls = [...css.matchAll(/url\(([^)]+)\)/g)].map((m) => m[1]);
    expect(urls.length).toBeGreaterThanOrEqual(8);
    for (const url of urls) {
      expect(url).toMatch(/^\.\/files\/[\w.-]+\.woff2$/);
      expect(existsSync(join(OUT, "fonts", url))).toBe(true);
    }
    const licences = readdirSync(join(OUT, "fonts")).filter((f) => f.startsWith("LICENSE-"));
    expect(licences.sort()).toEqual(["LICENSE-archivo.txt", "LICENSE-inter.txt", "LICENSE-newsreader.txt", "LICENSE-source-serif-4.txt"]);
    for (const l of licences) expect(readFileSync(join(OUT, "fonts", l), "utf8")).toContain("SIL Open Font License");
    for (const d of DIRECTIONS) {
      const links = [...parse(page(d, "profile")).querySelectorAll("link[rel=stylesheet]")].map((l) => l.getAttribute("href"));
      for (const href of links) expect(href).toMatch(/^\.\.\//);
    }
  });

  it("no forbidden term in rendered output", () => {
    const sources = ["components", "screens"].flatMap((dir) =>
      readdirSync(join(ROOT, dir)).map((f) => [join(dir, f), readFileSync(join(ROOT, dir, f), "utf8")]),
    );
    const rendered = DIRECTIONS.flatMap((d) => SCREENS.map((s) => [`${d}/${s}.html`, parse(page(d, s)).body.textContent ?? ""]));
    expect(sources.length).toBeGreaterThanOrEqual(10);
    const hits = [...sources, ...rendered].flatMap(([file, text]) =>
      FORBIDDEN_TERMS.filter((term) => termPattern(term).test(text)).map((term) => `${file}: ${term}`),
    );
    expect(hits).toEqual([]);
    // the scan itself catches a term
    expect(termPattern("faltou").test("Faltou à sessão")).toBe(true);
  });
});
