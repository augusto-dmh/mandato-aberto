/**
 * One real `astro build` of the fixture contract (3 deputies, 7 roll calls), inspected on disk.
 *
 * SITE_URL differs from the default so a hard-coded origin fails; photo downloads are off and the
 * cache holds only a synthetic 400x300 JPEG for deputy 101, so 102 and 103 have no photo.
 */
import { spawnSync } from "node:child_process";
import { copyFileSync, cpSync, existsSync, mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { join, resolve } from "node:path";
import { afterAll, beforeAll, describe, expect, it } from "vitest";

import { FORBIDDEN_TERMS, termPattern } from "../src/lib/forbidden-terms";

const SITE = resolve(__dirname, "..");
const FIXTURE = join(__dirname, "fixtures", "out");
const PHOTO = join(__dirname, "fixtures", "photos", "101.jpg");
const ORIGIN = "https://preview.example.org";
const COLLECTED = "Dados coletados em 27/09/2026 às 09:00 (horário de Brasília)";
const ROLL_CALLS = ["100-1", "100-2", "100-3", "100-4", "200-1", "200-2", "200-3"];

let work: string;
let dist: string;
let cache: string;

function astroBuild(dataDir: string, outDir: string) {
  return spawnSync(process.execPath, [join(SITE, "node_modules", "astro", "bin", "astro.mjs"), "build", "--outDir", outDir], {
    cwd: SITE,
    encoding: "utf8",
    env: {
      ...process.env,
      MANDATO_DATA_DIR: dataDir,
      MANDATO_PHOTOS: "off",
      MANDATO_PHOTO_CACHE: cache,
      SITE_URL: ORIGIN,
      ASTRO_TELEMETRY_DISABLED: "1",
    },
  });
}

beforeAll(() => {
  // Astro renames assets into outDir, which fails across filesystems: keep the build next to the site.
  mkdirSync(join(SITE, ".cache"), { recursive: true });
  work = mkdtempSync(join(SITE, ".cache", "test-build-"));
  cache = join(work, "photos");
  mkdirSync(cache);
  copyFileSync(PHOTO, join(cache, "101.jpg"));
  dist = join(work, "dist");
  const result = astroBuild(FIXTURE, dist);
  if (result.status !== 0) throw new Error(`astro build failed:\n${result.stdout}\n${result.stderr}`);
});
afterAll(() => rmSync(work, { recursive: true, force: true }));

const page = (path: string) => readFileSync(join(dist, path, "index.html"), "utf8");
const home = () => page("");
const profile = (id: number) => page(`deputados/${id}`);
const rollCall = (id: string) => page(`votacoes/${id}`);
const allPages = () => [home(), ...[101, 102, 103].map(profile), ...ROLL_CALLS.map(rollCall)];

const decode = (text: string) =>
  text
    .replace(/&quot;/g, '"')
    .replace(/&#39;|&#x27;/g, "'")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/&nbsp;/g, " ")
    .replace(/&amp;/g, "&");

/** Text a reader sees: markup, styles and scripts removed, whitespace collapsed. */
function visible(html: string): string {
  return decode(
    html
      .replace(/<script[\s\S]*?<\/script>/g, " ")
      .replace(/<style[\s\S]*?<\/style>/g, " ")
      .replace(/<[^>]+>/g, " "),
  )
    .replace(/\s+/g, " ")
    .trim();
}

/** Inner HTML of the first element whose opening tag matches `attrs`, e.g. `id="participacao"`. */
function element(html: string, tag: string, attrs: string): string {
  const open = new RegExp(`<${tag}\\b[^>]*${attrs}[^>]*>`).exec(html);
  if (!open) throw new Error(`no <${tag} ${attrs}>`);
  let depth = 1;
  const tags = new RegExp(`<(/?)${tag}\\b[^>]*>`, "g");
  tags.lastIndex = open.index + open[0].length;
  for (let m = tags.exec(html); m; m = tags.exec(html)) {
    depth += m[1] ? -1 : 1;
    if (depth === 0) return html.slice(open.index + open[0].length, m.index);
  }
  throw new Error(`unclosed <${tag} ${attrs}>`);
}

const meta = (html: string, key: string) =>
  decode(new RegExp(`<meta (?:property|name)="${key}" content="([^"]*)"`).exec(html)?.[1] ?? "");
const canonical = (html: string) => /<link rel="canonical" href="([^"]*)"/.exec(html)?.[1];

function pngSize(path: string) {
  const bytes = readFileSync(path);
  expect(bytes.subarray(1, 4).toString("latin1")).toBe("PNG");
  return { width: bytes.readUInt32BE(16), height: bytes.readUInt32BE(20) };
}

describe("S1 contract to pages", () => {
  it("writes one page per deputy and per roll call", () => {
    expect(existsSync(join(dist, "index.html"))).toBe(true);
    expect(readdirSync(join(dist, "deputados")).sort()).toEqual(["101", "102", "103"]);
    expect(readdirSync(join(dist, "votacoes")).sort()).toEqual(ROLL_CALLS);
    for (const id of [101, 102, 103]) expect(existsSync(join(dist, "deputados", `${id}`, "index.html"))).toBe(true);
    for (const id of ROLL_CALLS) expect(existsSync(join(dist, "votacoes", id, "index.html"))).toBe(true);
  });

  it("fails on schema_version 2", () => {
    const dir = join(work, "v2");
    cpSync(FIXTURE, dir, { recursive: true });
    const meta = JSON.parse(readFileSync(join(dir, "meta.json"), "utf8"));
    writeFileSync(join(dir, "meta.json"), JSON.stringify({ ...meta, schema_version: 2 }));
    const result = astroBuild(dir, join(work, "dist-v2"));
    expect(result.status).not.toBe(0);
    expect(result.stdout + result.stderr).toContain(
      "Unsupported data contract: meta.json has schema_version 2; this site reads 1",
    );
  });

  it("fails without meta.json", () => {
    const dir = join(work, "empty");
    mkdirSync(dir);
    const result = astroBuild(dir, join(work, "dist-empty"));
    expect(result.status).not.toBe(0);
    expect(result.stdout + result.stderr).toContain(`No meta.json in ${dir}`);
  });

  it("every page states the collection date", () => {
    const pages = allPages();
    expect(pages).toHaveLength(11);
    for (const html of pages) expect(visible(html)).toContain(COLLECTED);
  });
});

describe("S2 home", () => {
  it("home lists in-exercise deputies as static links", () => {
    const html = home();
    const linked = [...html.matchAll(/href="\/deputados\/(\d+)\/"/g)].map((m) => Number(m[1]));
    expect(linked).toEqual([101, 102]);
    const item101 = visible(element(html, "a", 'href="/deputados/101/"'));
    const item102 = visible(element(html, "a", 'href="/deputados/102/"'));
    for (const text of ["Ana Souza", "PT", "SP", "Candidatura em 2026"]) expect(item101).toContain(text);
    for (const text of ["Bruno Lima", "PT", "RJ", "Candidatura em 2026"]) expect(item102).toContain(text);
    expect(element(html, "a", 'href="/deputados/101/"')).toContain('<img src="/fotos/101.jpg"');
    expect(element(html, "a", 'href="/deputados/102/"')).not.toContain("<img");
    expect(visible(html)).not.toContain("Carla Dias");
  });

  it("home shows no indicator and no ordering control", () => {
    const html = home();
    const text = visible(html);
    for (const value of ["3 de 4", "2 de 3", "2 de 2", "1 de 1", "0 de 1"]) expect(text).not.toContain(value);
    expect(text).not.toMatch(/ordenar/i);

    const controls = [...html.matchAll(/<(input|select)\b[^>]*>/g)].map((m) => m[0]);
    const names = controls.map((tag) => /name="([^"]*)"/.exec(tag)?.[1]);
    expect(names).toEqual(["q", "uf", "partido", "exercicio", "candidatura"]);
    expect(controls[0]).toMatch(/type="search"/);
    expect(controls[3]).toMatch(/type="checkbox"/);
    expect(controls[3]).toMatch(/\bchecked\b/);
    expect(controls[4]).toMatch(/type="checkbox"/);
    expect(controls[4]).not.toMatch(/\bchecked\b/);
    const options = (name: string) =>
      [...element(html, "select", `name="${name}"`).matchAll(/<option value="([^"]*)"/g)].map((m) => m[1]).filter(Boolean);
    expect(options("uf")).toEqual(["MG", "RJ", "SP"]);
    expect(options("partido")).toEqual(["NOVO", "PT"]);
    expect(text).toContain("Em exercício");
    expect(text).toContain("Candidatura em 2026");
  });

  it("home states what the site is and the legislature totals", () => {
    const text = visible(home());
    expect(text).toContain(
      "O Mandato Aberto mostra, com dados oficiais da Câmara dos Deputados, o que cada deputado federal fez na 57ª legislatura.",
    );
    for (const total of ["3 deputados", "7 votações nominais", "5 proposições de autoria"]) expect(text).toContain(total);
  });
});

describe("S3 profile", () => {
  it("profile header", () => {
    const html = profile(101);
    const text = visible(html);
    for (const value of ["Ana Souza", "PT", "SP", "Em exercício", "Foto: Câmara dos Deputados"]) expect(text).toContain(value);
    expect(html).toContain('<img src="/fotos/101.jpg"');
    expect(html).toMatch(/<a href="https:\/\/www\.camara\.leg\.br\/deputados\/101"[^>]*>\s*Página na Câmara dos Deputados\s*<\/a>/);
    const text103 = visible(profile(103));
    expect(text103).toContain("Fora de exercício");
    expect(text103).not.toContain("Em exercício");
  });

  it("profile indicators", () => {
    const blocks = [
      ["participacao", "Participação em votações nominais do plenário", "3 de 4", "0.75", "2 de 2"],
      ["alinhamento-governo", "Votos iguais à orientação do governo", "2 de 3", "0.6667", "1 de 1"],
      ["alinhamento-partido", "Votos iguais à maioria do próprio partido", "2 de 3", "0.6667", "2 de 3"],
    ];
    for (const [id, label, value101, share101, value102] of blocks) {
      const block = element(profile(101), "section", `id="${id}"`);
      const text = visible(block);
      expect(text).toContain(label);
      expect(text).toContain(value101);
      expect(text).toMatch(/Base de cálculo: \S/);
      expect(block).toContain(`style="--share: ${share101}"`);
      expect(block).toContain(`href="/metodologia/#${id}"`);
      expect(visible(element(profile(102), "section", `id="${id}"`))).toContain(value102);
    }
  });

  it("indicator without base", () => {
    const html = profile(103);
    for (const id of ["participacao", "alinhamento-partido"]) {
      const block = element(html, "section", `id="${id}"`);
      expect(visible(block)).toContain("Sem base de cálculo no período");
      expect(block).not.toContain("--share");
    }
    const government = element(html, "section", 'id="alinhamento-governo"');
    expect(visible(government)).toContain("0 de 1");
    expect(visible(government)).not.toContain("Sem base de cálculo no período");
    expect(government).toContain('style="--share: 0"');
  });

  it("profile authorship", () => {
    const html = profile(101);
    const text = visible(html);
    for (const value of ["5 proposições de autoria", "2 como primeiro signatário", "3 requerimentos"]) {
      expect(text).toContain(value);
    }
    const list = element(html, "ol", 'class="authored"');
    const titles = [...visible(list).matchAll(/\b(PRC|PDL|PEC|PLP|PL) (\d+\/\d{4})/g)].map((m) => `${m[1]} ${m[2]}`);
    expect(titles).toEqual(["PRC 5/2023", "PDL 4/2023", "PEC 3/2023", "PLP 2/2023", "PL 1/2023"]);
    for (const id of [6001, 6002, 6003, 6004, 6005]) {
      expect(list).toContain(`href="https://www.camara.leg.br/propostas-legislativas/${id}"`);
      expect(visible(list)).toContain(`Ementa ${id}`);
    }
    expect(visible(element(list, "li", ""))).toContain("14/03/2023");
  });

  it("profile candidacy", () => {
    const html = profile(101);
    expect(visible(html)).toContain("Candidatura em 2026: DEPUTADO FEDERAL, PT, número 1313 (situação no TSE: APTO)");
    expect(html).toContain('href="https://dadosabertos.tse.jus.br/dataset/candidatos-2026"');
    expect(visible(profile(103))).not.toContain("Candidatura em 2026");
  });

  it("profile votes", () => {
    const html = profile(101);
    const votes = element(html, "section", 'id="votos"');
    const linked = [...votes.matchAll(/href="\/votacoes\/([^/"]+)\/"/g)].map((m) => m[1]);
    expect(linked).toEqual(["100-4", "200-3", "100-3", "100-2", "200-2", "200-1", "100-1"]);
    const years = [...votes.matchAll(/<h3[^>]*>\s*(\d{4})\s*<\/h3>/g)].map((m) => m[1]);
    expect(years).toEqual(["2025", "2024", "2023"]);
    const row = (id: string) => visible(element(votes, "li", `data-roll-call="${id}"`));
    for (const value of ["01/03/2023", "Plenário", "PL 1/2023", "Sim"]) expect(row("100-1")).toContain(value);
    expect(row("100-3")).toContain("Votação 100-3");
    expect(row("200-1")).toContain("CCJC");
  });

  it("profile exercise periods", () => {
    const text102 = visible(profile(102));
    expect(text102).toContain("01/02/2023 a 01/03/2024");
    expect(text102).toContain("desde 10/01/2025");
    expect(visible(profile(101))).toContain("desde 01/02/2023");
  });

  it("profile vote labels", () => {
    expect(visible(profile(102))).toContain("Art. 17 (presidente da sessão)");
    expect(visible(profile(101))).toContain("Registro sem voto");
  });

  it("missing photo renders nothing", () => {
    for (const id of [102, 103]) {
      const html = profile(id);
      expect(html).not.toContain("<img");
      expect(visible(html)).not.toContain("Foto: Câmara dos Deputados");
    }
    expect(readFileSync(join(dist, "fotos", "101.jpg")).equals(readFileSync(PHOTO))).toBe(true);
    expect(existsSync(join(dist, "fotos", "102.jpg"))).toBe(false);
    expect(existsSync(join(dist, "fotos", "103.jpg"))).toBe(false);
  });
});

describe("S4 roll call", () => {
  it("roll-call header", () => {
    const html = rollCall("100-1");
    const text = visible(html);
    for (const value of ["01/03/2023", "Plenário", "Votação 100-1", "Dispõe sobre X.", "Aprovada", "Sim 2", "Não 1", "Outros 0", "Orientação do governo: Sim"]) {
      expect(text).toContain(value);
    }
    expect(html).toMatch(/<a href="https:\/\/www\.camara\.leg\.br\/propostas-legislativas\/5001"[^>]*>\s*PL 1\/2023\s*<\/a>/);
  });

  it("roll-call without result, orientation or proposition", () => {
    expect(visible(rollCall("100-2"))).toContain("Rejeitada");
    const html = rollCall("100-3");
    expect(visible(html)).toContain("Resultado não informado");
    expect(visible(html)).toContain("Sem orientação do governo registrada");
    expect(html).not.toContain("propostas-legislativas");
  });

  it("roll-call votes grouped and linked", () => {
    const html = rollCall("100-1");
    const yes = element(html, "section", 'data-vote="Sim"');
    const no = element(html, "section", 'data-vote="Não"');
    expect(visible(yes)).toMatch(/^Sim \(2\)/);
    expect(visible(no)).toMatch(/^Não \(1\)/);
    const entries = (group: string) =>
      [...group.matchAll(/<li[^>]*>([\s\S]*?)<\/li>/g)].map((m) => ({
        href: /href="([^"]*)"/.exec(m[1])?.[1],
        text: visible(m[1]),
      }));
    expect(entries(yes)).toEqual([
      { href: "/deputados/101/", text: "Ana Souza PT-SP" },
      { href: "/deputados/102/", text: "Bruno Lima PT-RJ" },
    ]);
    expect(entries(no)).toEqual([{ href: "/deputados/103/", text: "Carla Dias NOVO-MG" }]);
  });

  it("roll-call source link", () => {
    for (const id of ROLL_CALLS) {
      expect(rollCall(id)).toMatch(
        new RegExp(`<a href="https://dadosabertos\\.camara\\.leg\\.br/api/v2/votacoes/${id}"[^>]*>\\s*Registro oficial na Câmara dos Deputados\\s*</a>`),
      );
    }
  });
});

describe("S5 share cards", () => {
  it("share cards are 1200x630 PNGs", () => {
    expect(readdirSync(join(dist, "cards", "deputados")).sort()).toEqual(["101.png", "102.png", "103.png"]);
    for (const file of ["deputados/101.png", "deputados/102.png", "deputados/103.png", "site.png"]) {
      expect(pngSize(join(dist, "cards", file))).toEqual({ width: 1200, height: 630 });
    }
  });

  it("profile share tags", () => {
    const html = profile(101);
    expect(meta(html, "og:title")).toBe("Ana Souza (PT-SP) na 57ª legislatura");
    expect(meta(html, "og:description")).not.toBe("");
    expect(meta(html, "og:image")).toBe(`${ORIGIN}/cards/deputados/101.png`);
    expect(meta(html, "og:url")).toBe(`${ORIGIN}/deputados/101/`);
    expect(canonical(html)).toBe(`${ORIGIN}/deputados/101/`);
    expect(meta(html, "twitter:card")).toBe("summary_large_image");
  });

  it("home and roll-call share tags", () => {
    for (const [html, url] of [
      [home(), `${ORIGIN}/`],
      [rollCall("100-1"), `${ORIGIN}/votacoes/100-1/`],
    ]) {
      expect(meta(html, "og:image")).toBe(`${ORIGIN}/cards/site.png`);
      expect(meta(html, "twitter:card")).toBe("summary_large_image");
      expect(meta(html, "og:title")).not.toBe("");
      expect(meta(html, "og:description")).not.toBe("");
      expect(meta(html, "og:url")).toBe(url);
      expect(canonical(html)).toBe(url);
    }
  });
});

describe("S6 language", () => {
  it("built pages use no forbidden term and no percentage", () => {
    for (const html of allPages()) {
      const text = visible(html);
      expect(text).not.toContain("%");
      for (const term of FORBIDDEN_TERMS) expect(text).not.toMatch(termPattern(term));
    }
  });
});
