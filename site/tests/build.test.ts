/**
 * One real `astro build` of the fixture contract (3 deputies, 8 roll calls, 100-6 secret), inspected on disk.
 *
 * SITE_URL differs from the default so a hard-coded origin fails; photo downloads are off and the
 * cache holds only a synthetic 400x300 JPEG for deputy 101, so 102 and 103 have no photo.
 */
import { spawnSync } from "node:child_process";
import { copyFileSync, cpSync, existsSync, mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { join, resolve } from "node:path";
import { afterAll, beforeAll, describe, expect, it } from "vitest";

import { FORBIDDEN_TERMS, termPattern } from "../src/lib/forbidden-terms";
import { CORRECTIONS_EMAIL, MAINTAINERS } from "../src/lib/site";

const SITE = resolve(__dirname, "..");
const FIXTURE = join(__dirname, "fixtures", "out");
const PHOTO = join(__dirname, "fixtures", "photos", "101.jpg");
const CORRECTIONS = join(__dirname, "fixtures", "corrections");
const ORIGIN = "https://preview.example.org";
const COLLECTED = "Dados coletados em 27/09/2026 às 09:00 (horário de Brasília)";
const ROLL_CALLS = ["100-1", "100-2", "100-3", "100-4", "100-6", "200-1", "200-2", "200-3"];

let work: string;
let dist: string;
let cache: string;

function astroBuild(dataDir: string, outDir: string, env: Record<string, string> = {}) {
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
      MANDATO_CORRECTIONS_DIR: CORRECTIONS,
      CF_ANALYTICS_TOKEN: "",
      ...env,
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
const notFound = () => readFileSync(join(dist, "404.html"), "utf8");
/** The pages the launch adds: the five legal and channel pages and `404.html`. */
const launchPages = () => [...["metodologia", "quem-somos", "dados-e-privacidade", "correcoes", "reportar-erro"].map(page), notFound()];

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

  it("fails on schema_version 3", () => {
    const dir = join(work, "v3");
    cpSync(FIXTURE, dir, { recursive: true });
    const meta = JSON.parse(readFileSync(join(dir, "meta.json"), "utf8"));
    writeFileSync(join(dir, "meta.json"), JSON.stringify({ ...meta, schema_version: 3 }));
    const result = astroBuild(dir, join(work, "dist-v3"));
    expect(result.status).not.toBe(0);
    expect(result.stdout + result.stderr).toContain(
      "Unsupported data contract: meta.json has schema_version 3; this site reads 2",
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
    expect(pages).toHaveLength(12);
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
    for (const total of ["3 deputados", "8 votações nominais", "5 proposições de autoria"]) expect(text).toContain(total);
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
      ["participacao", "Participação em votações nominais do plenário", "4 de 5", "0.8", "3 de 3"],
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
    const participation = visible(element(profile(101), "section", 'id="participacao"'));
    expect(participation).toMatch(/Base de cálculo: [^]*inclusive Art\. 17 e votações secretas\. Como este número é calculado$/);
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
    expect(linked).toEqual(["100-6", "100-4", "200-3", "100-3", "100-2", "200-2", "200-1", "100-1"]);
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

  it("profile secret vote", () => {
    const votes = element(profile(101), "section", 'id="votos"');
    const vote = (id: string) => visible(element(element(votes, "li", `data-roll-call="${id}"`), "span", 'class="vote"'));
    expect(vote("100-6")).toBe("Votação secreta");
    expect(vote("100-3")).toBe("Registro sem voto");
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

  it("secret roll call", () => {
    const html = rollCall("100-6");
    const text = visible(html);
    expect(text).toContain("Votação secreta: a Câmara registra quem votou, não o voto de cada deputado.");
    expect(text).toContain("Totais oficiais da Câmara Sim 12 Não 5 Outros 2");
    expect(text).not.toContain("Registro sem voto");
    expect(meta(html, "og:title")).toBe("Votação nominal de 01/08/2025: votação secreta");
    expect(meta(html, "og:description")).toBe(
      "Votação secreta de 01/08/2025 (Plenário) na Câmara dos Deputados, com os totais oficiais e os deputados que votaram.",
    );
    const votes = element(html, "section", 'class="section"');
    expect(visible(votes)).toMatch(/^Quem votou Em ordem alfabética\. Partido na data da votação\. Deputados que votaram \(3\)/);
    expect([...html.matchAll(/<section\b[^>]*class="vote-group"/g)]).toHaveLength(1);
    const group = element(html, "section", 'class="vote-group"');
    expect(visible(group)).toMatch(/^Deputados que votaram \(3\)/);
    const entries = [...group.matchAll(/<li[^>]*>([\s\S]*?)<\/li>/g)].map((m) => ({
      href: /href="([^"]*)"/.exec(m[1])?.[1],
      text: visible(m[1]),
    }));
    expect(entries).toEqual([
      { href: "/deputados/101/", text: "Ana Souza PT-SP" },
      { href: "/deputados/102/", text: "Bruno Lima PT-RJ" },
      { href: "/deputados/103/", text: "Carla Dias NOVO-MG" },
    ]);
  });

  it("open roll calls carry no secret notice", () => {
    const html = rollCall("100-1");
    const text = visible(html);
    expect(text).not.toContain("Votação secreta");
    expect(text).not.toContain("Totais oficiais da Câmara");
    expect(visible(element(html, "section", 'data-vote="Sim"'))).toMatch(/^Sim \(2\)/);
    expect(visible(element(html, "section", 'data-vote="Não"'))).toMatch(/^Não \(1\)/);
    const open = rollCall("100-3");
    expect(visible(open)).not.toContain("Votação secreta");
    // Astro renders an empty attribute bare: `data-vote`, not `data-vote=""`.
    expect(visible(element(open, "section", "data-vote(?!=)"))).toMatch(/^Registro sem voto \(1\)/);
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
    for (const html of [...allPages(), ...launchPages()]) {
      const text = visible(html);
      expect(text).not.toContain("%");
      for (const term of FORBIDDEN_TERMS) expect(text).not.toMatch(termPattern(term));
    }
  });
});

// Launch: the legal pages, the correction channel and the host files.

const reportPage = () => page("reportar-erro");
/** Every `<script src>` of a page that points into this site's `/_astro/`, read from `dist/`. */
const localScripts = (html: string) =>
  [...html.matchAll(/<script\b[^>]*\bsrc="(\/_astro\/[^"]+)"/g)].map((m) => readFileSync(join(dist, m[1]), "utf8"));

describe("launch S2 report an error", () => {
  it("report link on profile and roll call", () => {
    for (const id of [101, 103]) {
      expect(profile(id)).toContain(`<a href="/reportar-erro/?p=/deputados/${id}/">Reportar erro nesta página</a>`);
    }
    for (const id of ["100-1", "100-6"]) {
      expect(rollCall(id)).toContain(`<a href="/reportar-erro/?p=/votacoes/${id}/">Reportar erro nesta página</a>`);
    }
  });

  it("report form fields and no submission target", () => {
    const html = reportPage();
    const forms = [...html.matchAll(/<form\b[^>]*>/g)].map((m) => m[0]);
    expect(forms).toHaveLength(1);
    expect(forms[0]).not.toMatch(/\baction=/);
    expect(forms[0]).not.toMatch(/\bmethod=/);
    const form = element(html, "form", "");
    const fields: [string, string, string][] = [
      ["Página com o erro", "input", "page"],
      ["O que está errado", "textarea", "problem"],
      ["Onde está o dado correto (link para a fonte oficial, se tiver)", "input", "source"],
      ["Seu e-mail, se quiser resposta", "input", "email"],
    ];
    for (const [label, tag, name] of fields) {
      const labelTag = new RegExp(`<label\\b[^>]*\\bfor="([^"]+)"[^>]*>${label.replace(/[()]/g, "\\$&")}</label>`).exec(form);
      expect(labelTag, label).not.toBeNull();
      const controls = [...form.matchAll(new RegExp(`<${tag}\\b[^>]*\\bname="${name}"[^>]*>`, "g"))].map((m) => m[0]);
      expect(controls, name).toHaveLength(1);
      expect(controls[0]).toContain(`id="${labelTag![1]}"`);
    }
    expect(form).toContain('<button type="submit">Enviar por e-mail</button>');
    for (const text of [html, ...localScripts(html)]) {
      expect(text).not.toContain("fetch(");
      expect(text).not.toContain("XMLHttpRequest");
    }
  });

  it("report page script prefills from the query", () => {
    const html = reportPage();
    const scripts = localScripts(html);
    expect(scripts).toHaveLength(1);
    expect(scripts[0]).toContain("^/[A-Za-z0-9/_-]{1,200}$");
    expect(scripts[0]).toContain("mailto:");
  });

  it("report page without javascript", () => {
    const noscript = element(reportPage(), "noscript", "");
    expect(noscript).toContain(`<a href="mailto:${CORRECTIONS_EMAIL}">${CORRECTIONS_EMAIL}</a>`);
    expect(noscript).toContain("Sem JavaScript, escreva para o endereço acima com os quatro itens do formulário.");
  });

  it("report page deadlines", () => {
    expect(reportPage()).toContain(
      'Toda mensagem recebe triagem em até 48 horas. A correção, ou a resposta do parlamentar, é publicada em até 7 dias na página <a href="/correcoes/">Correções</a>.',
    );
  });
});

const methodology = () => page("metodologia");
const SECTIONS = ["participacao", "alinhamento-governo", "alinhamento-partido", "proposicoes", "candidatura-2026", "fontes"];
/** A methodology section: from its `<h2 id>` to the next `<h2` or the end of `<main>`. */
function methodSection(id: string): string {
  const html = methodology();
  const start = html.indexOf(`<h2 id="${id}">`);
  if (start < 0) throw new Error(`no <h2 id="${id}">`);
  const next = html.indexOf("<h2", start + 1);
  return html.slice(start, next < 0 ? html.indexOf("</main>") : next);
}
const expectSentences = (html: string, sentences: string[]) => {
  const text = visible(html);
  for (const sentence of sentences) expect(text).toContain(sentence);
};

describe("launch S3 methodology", () => {
  it("methodology sections in order", () => {
    const html = methodology();
    const h2s = [...html.matchAll(/<h2\b([^>]*)>/g)].map((m) => /id="([^"]*)"/.exec(m[1])?.[1]);
    expect(h2s.slice(0, SECTIONS.length)).toEqual(SECTIONS);
  });

  it("methodology participation", () => {
    expectSentences(methodSection("participacao"), [
      "Conta: votações nominais do plenário em que o deputado tem registro com qualquer valor: Sim, Não, Abstenção, Obstrução, Art. 17 ou registro em votação secreta.",
      "Base: votações nominais do plenário realizadas enquanto o deputado estava em exercício, segundo o histórico de situações publicado pela Câmara. Só os períodos com situação Exercício entram; licença e qualquer outra situação ficam de fora.",
      "Votações em comissões não entram neste número.",
      "Os dados abertos não informam por que um deputado não tem registro em uma votação. Por isso o site não atribui motivo a nenhum registro que não existe.",
    ]);
  });

  it("methodology government alignment", () => {
    expectSentences(methodSection("alinhamento-governo"), [
      "Conta: votos Sim, Não, Abstenção ou Obstrução iguais à orientação da bancada GOVERNO na mesma votação.",
      "Base: votos Sim, Não, Abstenção ou Obstrução em votações em que a orientação GOVERNO foi um desses quatro valores.",
      "Orientação Liberado, votação sem orientação registrada, registro Art. 17 e votação secreta ficam fora da conta e da base.",
    ]);
  });

  it("methodology party alignment", () => {
    expectSentences(methodSection("alinhamento-partido"), [
      "O partido é o registrado no voto, não o atual.",
      "A maioria é calculada entre os outros deputados do mesmo partido na mesma votação, sobre os mesmos quatro valores, sem o voto do próprio deputado.",
      "Empate, ou nenhum outro deputado do partido na votação, deixa a votação fora da conta e da base.",
    ]);
  });

  it("methodology propositions", () => {
    expectSentences(methodSection("proposicoes"), [
      "Conta PL, PLP, PEC, PDL e PRC apresentados a partir de 01/02/2023 em que o deputado consta como proponente.",
      "Primeiro signatário: o deputado é o primeiro na ordem de assinatura.",
      "REQ, RIC e INC são contados à parte, como requerimentos.",
    ]);
  });

  it("methodology candidacy", () => {
    const html = methodSection("candidatura-2026");
    expect(html).toContain('<a href="https://dadosabertos.tse.jus.br/dataset/candidatos-2026">');
    expectSentences(html, [
      "O cruzamento usa nome civil, data de nascimento e UF. O CPF não é lido.",
      "Um deputado que corresponde a mais de uma candidatura não recebe selo.",
      "A situação exibida é a que consta no arquivo do TSE na data da última atualização manual, registrada no histórico do repositório.",
    ]);
  });

  it("methodology sources", () => {
    const html = methodSection("fontes");
    const kinds = ["votacoes", "votacoesVotos", "votacoesOrientacoes", "votacoesProposicoes", "proposicoes", "proposicoesAutores"];
    for (const kind of kinds) {
      expect(html).toContain(`href="https://dadosabertos.camara.leg.br/arquivos/${kind}/csv/${kind}-2023.csv"`);
    }
    expect(html).toContain('href="https://dadosabertos.camara.leg.br/arquivos/deputados/csv/deputados.csv"');
    const items = [...html.matchAll(/<li\b[^>]*>([\s\S]*?)<\/li>/g)].map((m) => m[1]);
    const apiItem = items.find((item) => item.includes('href="https://dadosabertos.camara.leg.br/api/v2/deputados"'));
    expect(apiItem).toBeDefined();
    expect(visible(apiItem!)).toContain("/deputados/{id}/historico");
    expect(html).toContain('href="https://dadosabertos.tse.jus.br/dataset/candidatos-2026"');
    expectSentences(html, [
      "Os arquivos dos anos seguintes têm o mesmo nome, com o ano trocado.",
      "A 57ª legislatura começou em 01/02/2023.",
      "Os dados são reconstruídos todos os dias; cada página mostra a data da coleta.",
    ]);
  });

  it("methodology secret ballots", () => {
    expectSentences(methodology(), [
      "Em uma votação secreta, a Câmara registra quem votou, não o voto de cada deputado. Os totais exibidos são os oficiais da Câmara.",
    ]);
  });

  it("methodology deputy set", () => {
    expectSentences(methodology(), [
      "O site lista todo deputado com pelo menos um registro de voto na 57ª legislatura, inclusive suplentes e deputados fora de exercício.",
      "Em exercício significa que o deputado consta na lista atual de deputados da Câmara.",
    ]);
  });

  it("methodology photos", () => {
    expectSentences(methodology(), [
      "As fotos são as oficiais da Câmara dos Deputados, exibidas sem recorte ou filtro, com o crédito Foto: Câmara dos Deputados.",
    ]);
  });

  it("profile methodology links resolve", () => {
    const anchors = [...profile(101).matchAll(/href="\/metodologia\/#([^"]+)"/g)].map((m) => m[1]);
    expect(new Set(anchors)).toEqual(new Set(["participacao", "alinhamento-governo", "alinhamento-partido", "proposicoes"]));
    const ids = new Set([...methodology().matchAll(/\bid="([^"]+)"/g)].map((m) => m[1]));
    for (const anchor of anchors) expect(ids.has(anchor), anchor).toBe(true);
  });
});

const REPO = "https://github.com/augusto-dmh/mandato-aberto";
const mailtoLink = `<a href="mailto:${CORRECTIONS_EMAIL}">${CORRECTIONS_EMAIL}</a>`;
const about = () => page("quem-somos");
const privacy = () => page("dados-e-privacidade");
const expectHtml = (html: string, fragments: string[]) => {
  for (const fragment of fragments) expect(html).toContain(fragment);
};

describe("launch S4 who we are and privacy", () => {
  it("quem somos", () => {
    const html = about();
    const text = visible(html);
    expect(MAINTAINERS.length).toBeGreaterThan(0);
    for (const { name, city } of MAINTAINERS) {
      expect(text).toContain(name);
      expect(text).toContain(city);
    }
    expect(html).toContain(`<a href="mailto:${CORRECTIONS_EMAIL}">`);
    expectHtml(html, [
      "O Mandato Aberto é mantido por pessoas físicas, sem vínculo com partidos, candidatos, federações ou campanhas.",
      "Não recebe dinheiro nem qualquer vantagem de partidos, candidatos, campanhas ou empresas, e não paga impulsionamento de conteúdo.",
    ]);
  });

  it("quem somos code and rebuild", () => {
    expectHtml(about(), [
      `<a href="${REPO}">`,
      'O site é reconstruído todos os dias a partir das fontes listadas em <a href="/metodologia/#fontes">Metodologia e fontes</a>.',
    ]);
  });

  it("privacy fields", () => {
    const html = privacy();
    const items = [...html.matchAll(/<li\b[^>]*>([\s\S]*?)<\/li>/g)].map((m) => visible(m[1]));
    for (const item of [
      "nome parlamentar",
      "partido",
      "UF",
      "foto oficial",
      "períodos em exercício",
      "votos em votações nominais",
      "proposições de autoria",
      "para quem é candidato em 2026: cargo, partido, número e situação no TSE",
    ]) {
      expect(items, item).toContain(item);
    }
    expectHtml(html, [
      "O nome civil e a data de nascimento publicados pela Câmara são lidos só para cruzar com o registro do TSE e nunca são exibidos.",
    ]);
  });

  it("privacy purpose basis and controllers", () => {
    expectHtml(privacy(), [
      "Finalidade: dar acesso público aos atos do mandato de cada deputado federal.",
      "Base legal: art. 7º, IX e §3º da Lei 13.709/2018 (LGPD), combinado com o art. 8º da Lei 12.527/2011 (LAI).",
      'Controladores: as pessoas físicas identificadas em <a href="/quem-somos/">Quem somos</a>.',
      `Para exercer os direitos do art. 18 da LGPD, escreva para ${mailtoLink}.`,
    ]);
  });

  it("privacy no other field and form", () => {
    expectHtml(privacy(), [
      "Nenhum CPF, telefone, endereço, e-mail, cor, raça, religião ou qualquer outro campo das fontes é tratado.",
      "O formulário de erro não envia nada ao site: a mensagem só sai do seu programa de e-mail, quando você a envia.",
    ]);
  });

  it("privacy cookies analytics and host", () => {
    expectHtml(privacy(), [
      "O site não grava cookie nem guarda nada no seu navegador.",
      "A contagem de visitas vem do Cloudflare Web Analytics, sem cookie e sem identificador individual.",
      'A hospedagem (Cloudflare) processa as requisições sob a <a href="https://www.cloudflare.com/privacypolicy/">política de privacidade dela</a>.',
    ]);
  });

  it("privacy balancing test link", () => {
    expect(privacy()).toContain(`<a href="${REPO}/blob/main/research/03-teste-de-balanceamento-lgpd.md">teste de balanceamento</a>`);
  });
});

const correctionsPage = () => page("correcoes");
const entries = (html: string) => [...html.matchAll(/<article\b[^>]*>([\s\S]*?)<\/article>/g)].map((m) => m[1]);

describe("launch S5 corrections", () => {
  it("corrections page lists records", () => {
    const [first, second, ...rest] = entries(correctionsPage());
    expect(rest).toEqual([]);
    expect(visible(first)).toContain("25/09/2026");
    expect(first).toContain('<a href="/votacoes/100-1/">/votacoes/100-1/</a>');
    expect(first).toContain('<a href="/deputados/102/">/deputados/102/</a>');
    expect(visible(first)).toContain("Resposta publicada");
    expect(visible(first)).toContain(
      "Relato de que o voto de Bruno Lima na votação 100-1 não corresponde à posição do deputado sobre a proposta.",
    );
    const text = visible(second);
    for (const value of ["20/09/2026", "Corrigido", "Resolvido em 22/09/2026", "Base de cálculo da participação corrigida (PR #9)"]) {
      expect(text).toContain(value);
    }
    expect(second).toContain('<a href="/deputados/101/">/deputados/101/</a>');
  });

  it("corrections page renders a reply", () => {
    const [first, second] = entries(correctionsPage());
    const reply = "Votei Sim na votação 100-1 porque o texto final incluiu a emenda que apresentei. Peço que esta resposta acompanhe o registro.";
    expect(visible(first)).toContain(reply);
    const heading = first.indexOf("<h3>Resposta do parlamentar</h3>");
    expect(heading).toBeGreaterThanOrEqual(0);
    expect(first.indexOf(reply)).toBeGreaterThan(heading);
    expect(second).not.toContain("Resposta do parlamentar");
  });

  it("corrections page empty state", () => {
    const empty = join(work, "corrections-empty");
    mkdirSync(empty);
    const out = join(work, "dist-corrections-empty");
    const result = astroBuild(FIXTURE, out, { MANDATO_CORRECTIONS_DIR: empty });
    expect(result.status, result.stderr).toBe(0);
    const html = readFileSync(join(out, "correcoes", "index.html"), "utf8");
    expect(visible(html)).toContain("Nenhuma correção registrada até 27/09/2026.");
    expect(html).not.toContain("<article");
  });

  it("build fails on a malformed correction", () => {
    const bad = join(work, "corrections-bad");
    mkdirSync(bad);
    writeFileSync(join(bad, "2026-09-04-d.md"), "---\nreceivedAt: 2026-09-04\npages: [/deputados/101/]\nstatus: fixed\n---\n\nRelato.\n");
    const result = astroBuild(FIXTURE, join(work, "dist-corrections-bad"), { MANDATO_CORRECTIONS_DIR: bad });
    expect(result.status).not.toBe(0);
    expect(result.stdout + result.stderr).toContain("2026-09-04-d.md");
  });

  it("corrections page policy", () => {
    expectHtml(correctionsPage(), [
      `Qualquer pessoa pode reportar um erro pelo <a href="/reportar-erro/">formulário</a> ou pelo e-mail ${mailtoLink}.`,
      "Toda mensagem recebe triagem em até 48 horas.",
      "Um erro confirmado é corrigido, e a resposta de um parlamentar é publicada nesta página com o mesmo destaque do dado contestado, em até 7 dias.",
    ]);
  });
});

/** The nine kinds of page that carry the legal footer. */
const footerPages = (): [string, string][] => [
  ["home", home()],
  ["profile 101", profile(101)],
  ["roll call 100-1", rollCall("100-1")],
  ["404", notFound()],
  ...["metodologia", "quem-somos", "dados-e-privacidade", "correcoes", "reportar-erro"].map((p): [string, string] => [p, page(p)]),
];
const footer = (html: string) => element(html, "footer", "");

describe("launch S1 legal footer and 404", () => {
  it("footer legal sentence on every page", () => {
    const pages = footerPages();
    expect(pages).toHaveLength(9);
    for (const [name, html] of pages) {
      expect(footer(html), name).toContain(
        "Este site não apoia nem se opõe a candidaturas, partidos ou federações. Todos os dados provêm de fontes oficiais indicadas em cada página. Não recebe recursos de partidos, candidatos ou campanhas.",
      );
    }
  });

  it("footer credits", () => {
    for (const [name, html] of footerPages()) {
      expect(footer(html), name).toContain(
        'Dados: <a href="https://dadosabertos.camara.leg.br/">Câmara dos Deputados</a> e <a href="https://dadosabertos.tse.jus.br/">TSE</a> (dados abertos). Fotos: Câmara dos Deputados.',
      );
    }
  });

  it("footer links", () => {
    const links = [
      '<a href="/metodologia/">Metodologia e fontes</a>',
      '<a href="/quem-somos/">Quem somos</a>',
      '<a href="/dados-e-privacidade/">Dados e privacidade</a>',
      '<a href="/correcoes/">Correções</a>',
      '<a href="/reportar-erro/">Reportar erro</a>',
      '<a href="https://github.com/augusto-dmh/mandato-aberto">Código-fonte</a> ',
    ];
    for (const [name, html] of footerPages()) for (const link of links) expect(footer(html), name).toContain(link);
  });

  it("404 page", () => {
    expect(existsSync(join(dist, "404.html"))).toBe(true);
    const html = notFound();
    expect(/<title>([^<]*)<\/title>/.exec(html)?.[1]).toBe("Página não encontrada - Mandato Aberto");
    expect(/<h1\b[^>]*>([^<]*)<\/h1>/.exec(html)?.[1]).toBe("Página não encontrada");
    expect(html).toContain("O endereço pode ter sido digitado errado ou a página pode ter deixado de existir.");
    expect(html).toContain('<a href="/">Voltar à busca de deputados</a>');
    const indexes: string[] = [];
    const walk = (dir: string) => {
      for (const entry of readdirSync(dir, { withFileTypes: true })) {
        if (entry.isDirectory()) walk(join(dir, entry.name));
        else if (entry.name === "index.html") indexes.push(join(dir, entry.name));
      }
    };
    walk(dist);
    expect(indexes.length).toBeGreaterThan(12);
    for (const file of indexes) expect(readFileSync(file, "utf8"), file).not.toContain("Página não encontrada");
  });
});

describe("launch S6 host files", () => {
  it("host config files", () => {
    expect(readFileSync(join(dist, "_redirects"), "utf8")).toBe(
      "https://www.preview.example.org/* https://preview.example.org/:splat 301\n",
    );
    expect(readFileSync(join(dist, "_headers"), "utf8")).toBe(
      [
        "/*",
        "  X-Content-Type-Options: nosniff",
        "  Referrer-Policy: strict-origin-when-cross-origin",
        "  X-Frame-Options: SAMEORIGIN",
        "",
      ].join("\n"),
    );
  });
});
