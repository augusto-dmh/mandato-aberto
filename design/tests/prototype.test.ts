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

function prototype(data: string, out: string, args: string[] = []) {
  return spawnSync("node", ["scripts/prototype.mjs", "--out", out, ...args], {
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

  it("empty bases never read 0 de 0", () => {
    const out = join(OUT, "empty-base");
    const run = prototype(FIXTURE, out, ["--deputy", "103"]);
    expect(run.status, run.stderr).toBe(0);
    for (const d of DIRECTIONS) {
      const profile = parse(readFileSync(join(out, d, "profile.html"), "utf8"));
      const lede = profile.querySelector(".ma-lede")!;
      expect(lede.textContent).toContain("Sem base de cálculo no período");
      expect(lede.textContent).not.toMatch(/\b0 de 0\b/);
      const card = parse(readFileSync(join(out, d, "card.html"), "utf8"));
      const figures = [...card.querySelectorAll(".ma-card__figure")].map((f) => f.textContent!.replace(/\s+/g, " ").trim());
      // deputy 103: participation 0/0, government 0/1, party 0/0
      expect(figures[0]).toContain("Sem base de cálculo no período");
      expect(figures[1]).toMatch(/^0 de 1 /);
      expect(figures[2]).toContain("Sem base de cálculo no período");
      expect(card.body.textContent).not.toMatch(/\b0 de 0\b/);
    }
  });

  it("roll call groups deputies by vote", () => {
    const out = join(OUT, "groups");
    const data = mkdtempSync(join(tmpdir(), "mandato-groups-"));
    cpSync(FIXTURE, data, { recursive: true });
    // one roll call holding every vote value, with names out of order and an accented one
    const names: Record<number, string> = { 101: "Ana Souza", 102: "Bruno Lima", 103: "Carla Dias" };
    const list = JSON.parse(readFileSync(join(data, "deputies.json"), "utf8"));
    const extra = [
      [201, "Érico Alves", "Sim"], [202, "Davi Rocha", "Sim"], [203, "Zélia Moura", "Abstenção"],
      [204, "Ítalo Reis", "Presente"], [205, "Beatriz Nunes", "Artigo 17"],
    ] as const;
    for (const [id, name] of extra) list.push({ ...list[0], id, name, uf: "RJ" });
    writeFileSync(join(data, "deputies.json"), JSON.stringify(list));
    const rc = JSON.parse(readFileSync(join(data, "roll-calls", "100-1.json"), "utf8"));
    rc.votes = [
      { deputyId: 103, party: "PSOL", vote: "" }, { deputyId: 101, party: "PT", vote: "Não" },
      { deputyId: 102, party: "PT", vote: "Obstrução" },
      ...extra.map(([deputyId, , vote]) => ({ deputyId, party: "PL", vote })),
    ];
    writeFileSync(join(data, "roll-calls", "100-1.json"), JSON.stringify(rc));
    const run = prototype(data, out, ["--roll-call", "100-1", "--deputy", "101"]);
    expect(run.status, run.stderr).toBe(0);
    const doc = parse(readFileSync(join(out, "diario", "roll-call.html"), "utf8"));
    const groups = [...doc.querySelectorAll(".ma-group")].map((g) => ({
      head: g.querySelector(".ma-group__head")!.textContent!.replace(/\s+/g, " ").trim(),
      rows: [...g.querySelectorAll("li")].map((li) => li.querySelector("svg")!.getAttribute("aria-label")),
    }));
    expect(groups).toEqual([
      { head: "Sim 2", rows: ["Davi Rocha, PL-RJ, votou Sim", "Érico Alves, PL-RJ, votou Sim"] },
      { head: "Não 1", rows: ["Ana Souza, PT-SP, votou Não"] },
      { head: "Abstenção 1", rows: ["Zélia Moura, PL-RJ, votou Abstenção"] },
      { head: "Obstrução 1", rows: [`${names[102]}, PT-${list.find((d: { id: number }) => d.id === 102).uf}, votou Obstrução`] },
      { head: "Art. 17 (presidente da sessão) 1", rows: ["Beatriz Nunes, PL-RJ, Art. 17 (presidente da sessão)"] },
      { head: "Registro sem voto 1", rows: [`${names[103]}, PSOL-${list.find((d: { id: number }) => d.id === 103).uf}, Registro sem voto`] },
      { head: "Presente 1", rows: ["Ítalo Reis, PL-RJ, Presente"] },
    ]);
  });

  it("roll call without individual votes", () => {
    const out = join(OUT, "no-votes");
    const data = mkdtempSync(join(tmpdir(), "mandato-novotes-"));
    cpSync(FIXTURE, data, { recursive: true });
    const rc = JSON.parse(readFileSync(join(data, "roll-calls", "100-2.json"), "utf8"));
    rc.votes = [];
    writeFileSync(join(data, "roll-calls", "100-2.json"), JSON.stringify(rc));
    expect(prototype(data, out, ["--roll-call", "100-2"]).status).toBe(0);
    for (const d of DIRECTIONS) {
      const doc = parse(readFileSync(join(out, d, "roll-call.html"), "utf8"));
      expect(doc.body.textContent).toContain("Nenhum voto individual registrado nesta votação.");
      expect(doc.querySelectorAll(".ma-group")).toHaveLength(0);
    }
  });

  it("source notes on the lede and the tally", () => {
    for (const d of DIRECTIONS) {
      const profile = parse(page(d, "profile"));
      const ref = profile.querySelector(".ma-lede a.ma-note-ref")!;
      const note = profile.getElementById(ref.getAttribute("href")!.slice(1))!;
      expect(note.querySelector("a")!.getAttribute("href")).toBe(DEPUTY.sourceUrl);
      const rc = parse(page(d, "roll-call"));
      const tallyRef = rc.querySelector(".ma-result a.ma-note-ref")!;
      const tallyNote = rc.getElementById(tallyRef.getAttribute("href")!.slice(1))!;
      expect(tallyNote.closest(".ma-result")).not.toBeNull();
      expect(tallyNote.querySelector("a")!.getAttribute("href")).toBe(ROLL_CALL.sourceUrl);
    }
  });

  it("every card shares one template", () => {
    const shape = (html: string) =>
      [...parse(html).querySelectorAll(".ma-card *")]
        .filter((el) => !el.closest("svg"))
        .map((el) => `${el.tagName}.${el.className}`)
        .join(" ");
    const a = join(OUT, "card-101");
    const b = join(OUT, "card-102");
    expect(prototype(FIXTURE, a, ["--deputy", "101"]).status).toBe(0);
    expect(prototype(FIXTURE, b, ["--deputy", "102"]).status).toBe(0);
    for (const d of DIRECTIONS) {
      const [ha, hb] = [a, b].map((dir) => readFileSync(join(dir, d, "card.html"), "utf8"));
      expect(shape(ha)).toBe(shape(hb));
      for (const [html, other] of [[ha, "Bruno Lima"], [hb, "Ana Souza"]]) {
        const card = parse(html).querySelector(".ma-card")!;
        expect(card.textContent).not.toContain("%");
        expect(card.textContent).not.toContain(other);
        expect(card.querySelector(".ma-ai")).toBeNull();
      }
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
