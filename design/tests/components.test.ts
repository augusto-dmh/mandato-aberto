import { describe, expect, it } from "vitest";

import AiSummaryFrame from "../components/AiSummaryFrame.vue";
import MandateScore from "../components/MandateScore.vue";
import NDeM from "../components/NDeM.vue";
import OfficialPhoto from "../components/OfficialPhoto.vue";
import SourceNote from "../components/SourceNote.vue";
import TallyBar from "../components/TallyBar.vue";
import VoteMark from "../components/VoteMark.vue";
import { render, textWithout } from "./render";

const note = {
  index: 1,
  sourceUrl: "https://www.camara.leg.br/deputados/204526",
  methodUrl: "https://augusto-dmh.github.io/mandato-aberto/metodologia/#participacao",
};
const ndem = { count: 412, total: 450, label: "Participação em votações nominais do plenário", note };
const votes = [
  { rollCallId: "300-2", date: "2024-05-02", title: "PL 3/2024", vote: "Não", secret: false },
  { rollCallId: "100-1", date: "2023-03-01", title: "PL 1/2023", vote: "Sim", secret: false },
  { rollCallId: "200-9", date: "2023-11-20", title: "PEC 2/2023", vote: "", secret: true },
  { rollCallId: "200-1", date: "2023-11-20", title: "PEC 2/2023", vote: "Obstrução", secret: false },
];

describe("components", () => {
  it("NDeM renders n de m", async () => {
    const { doc } = await render(NDeM, ndem);
    expect(doc.querySelector(".ma-ndem__n")?.textContent?.trim()).toBe("412");
    expect(doc.querySelector(".ma-ndem__m")?.textContent?.replace(/\s+/g, " ").trim()).toBe("de 450");
    expect(doc.querySelector(".ma-ndem__n")?.parentElement).toBe(doc.querySelector(".ma-ndem__m")?.parentElement);
    expect(doc.body.textContent).not.toContain("%");
    // read aloud or copied, the number and its base stay separate words
    expect(textWithout(doc.querySelector(".ma-ndem__value")!, ".ma-note-ref").replace(/\s+/g, " ").trim()).toBe("412 de 450");
  });

  it("NDeM with an empty base", async () => {
    const { doc } = await render(NDeM, { ...ndem, count: 0, total: 0 });
    const figure = doc.querySelector(".ma-ndem")!;
    expect(figure.textContent).toContain("Sem base de cálculo no período");
    // the footnote marker and its note carry the note's index, which is not a number of the record
    expect(textWithout(figure, ".ma-note-ref, .ma-note")).not.toMatch(/\d/);
  });

  it("NDeM carries its source note", async () => {
    const { doc } = await render(NDeM, ndem);
    const ref = doc.querySelector("a.ma-note-ref")!;
    const target = doc.getElementById(ref.getAttribute("href")!.slice(1))!;
    expect(target).not.toBeNull();
    const links = [...target.querySelectorAll("a")].map((a) => a.getAttribute("href"));
    expect(links).toEqual([note.sourceUrl, note.methodUrl]);
  });

  it("numbers carry the numeric class", async () => {
    const cases = [
      await render(NDeM, ndem),
      await render(TallyBar, { yes: 12, no: 5, others: 2 }),
      await render(MandateScore, { votes }),
    ];
    for (const { doc } of cases) {
      const numbers = [
        ...doc.querySelectorAll(".ma-ndem__n, .ma-ndem__m .ma-num, .ma-tally__counts dd, .ma-score__table td:first-child"),
      ];
      expect(numbers.length).toBeGreaterThan(0);
      for (const el of numbers) expect(el.closest(".ma-num"), el.outerHTML).not.toBeNull();
    }
  });

  it("VoteMark encodes every vote case", async () => {
    const table: [string, boolean, string, string, (svg: Element) => void][] = [
      ["Sim", false, "yes", "Ana Souza, PT-SP, votou Sim", (svg) => {
        const r = svg.querySelector("rect")!;
        expect(Number(r.getAttribute("y")) + Number(r.getAttribute("height"))).toBeLessThanOrEqual(12);
        expect(r.getAttribute("fill")).toBe("currentColor");
      }],
      ["Não", false, "no", "Ana Souza, PT-SP, votou Não", (svg) => {
        const r = svg.querySelector("rect")!;
        expect(Number(r.getAttribute("y"))).toBeGreaterThanOrEqual(12);
        expect(r.getAttribute("fill")).toBe("currentColor");
      }],
      ["Abstenção", false, "abstention", "Ana Souza, PT-SP, votou Abstenção", (svg) => {
        const r = svg.querySelector("rect")!;
        expect(r.getAttribute("fill")).toBe("none");
        expect(Number(r.getAttribute("y"))).toBeLessThan(12);
        expect(Number(r.getAttribute("y")) + Number(r.getAttribute("height"))).toBeGreaterThan(12);
        expect(svg.querySelector("path")).toBeNull();
      }],
      ["Obstrução", false, "obstruction", "Ana Souza, PT-SP, votou Obstrução", (svg) => {
        const r = svg.querySelector("rect")!;
        expect(r.getAttribute("fill")).toBe("none");
        expect(Number(r.getAttribute("y"))).toBeLessThan(12);
        expect(Number(r.getAttribute("y")) + Number(r.getAttribute("height"))).toBeGreaterThan(12);
        const ys = [...svg.querySelector("path")!.getAttribute("d")!.matchAll(/[\d.]+ ([\d.]+)/g)].map((m) => Number(m[1]));
        expect(Math.min(...ys)).toBeGreaterThanOrEqual(Number(r.getAttribute("y")));
        expect(Math.max(...ys)).toBeLessThanOrEqual(Number(r.getAttribute("y")) + Number(r.getAttribute("height")));
      }],
      ["Artigo 17", false, "article-17", "Ana Souza, PT-SP, Art. 17 (presidente da sessão)", (svg) => {
        const c = svg.querySelector("circle")!;
        expect(c.getAttribute("cy")).toBe("12");
        expect(c.getAttribute("fill")).toBe("currentColor");
      }],
      ["", false, "not-recorded", "Ana Souza, PT-SP, Registro sem voto", (svg) => {
        expect(svg.querySelectorAll("rect, circle, path")).toHaveLength(0);
      }],
      ["", true, "secret", "Ana Souza, PT-SP, Votação secreta", (svg) => {
        expect(svg.querySelectorAll("rect, circle, path")).toHaveLength(0);
      }],
      ["Presente", false, "other", "Ana Souza, PT-SP, Presente", (svg) => {
        const c = svg.querySelector("circle")!;
        expect(c.getAttribute("fill")).toBe("none");
        expect(c.getAttribute("cy")).toBe("12");
      }],
    ];
    const kinds = new Set<string>();
    for (const [vote, secret, kind, label, shape] of table) {
      const { doc } = await render(VoteMark, { vote, secret, who: "Ana Souza, PT-SP" });
      const svg = doc.querySelector("svg.ma-vote")!;
      expect(svg.classList.contains(`ma-vote--${kind}`), vote).toBe(true);
      expect(svg.getAttribute("role")).toBe("img");
      expect(svg.getAttribute("aria-label")).toBe(label);
      shape(svg);
      for (const el of svg.querySelectorAll("*")) {
        for (const attr of ["fill", "stroke"]) {
          const value = el.getAttribute(attr);
          if (value !== null) expect(["currentColor", "none"], `${vote} ${el.tagName} ${attr}`).toContain(value);
        }
      }
      kinds.add(kind);
    }
    expect(kinds.size).toBe(8);
  });

  it("MandateScore is chronological and linked", async () => {
    const { doc } = await render(MandateScore, { votes });
    const expected = ["100-1", "200-1", "200-9", "300-2"];
    const hrefs = [...doc.querySelectorAll(".ma-score__strip a.ma-score__col")].map((a) => a.getAttribute("href"));
    expect(hrefs).toEqual(expected.map((id) => `/votacoes/${id}/`));
    const rows = [...doc.querySelectorAll(".ma-score__table tbody tr")].map((tr) =>
      [...tr.querySelectorAll("td")].map((td) => td.textContent?.trim()),
    );
    expect(rows).toEqual([
      ["01/03/2023", "PL 1/2023", "Sim"],
      ["20/11/2023", "PEC 2/2023", "Obstrução"],
      ["20/11/2023", "PEC 2/2023", "Votação secreta"],
      ["02/05/2024", "PL 3/2024", "Não"],
    ]);
    const tableLinks = [...doc.querySelectorAll(".ma-score__table tbody a")].map((a) => a.getAttribute("href"));
    expect(tableLinks).toEqual(expected.map((id) => `/votacoes/${id}/`));
  });

  it("SourceNote links source and method", async () => {
    const { doc } = await render(SourceNote, note);
    const p = doc.querySelector("p.ma-note")!;
    expect(p.id).toBe("nota-1");
    expect(p.querySelector("sup")?.textContent).toBe("1");
    expect([...p.querySelectorAll("a")].map((a) => [a.textContent, a.getAttribute("href")])).toEqual([
      ["Câmara dos Deputados", note.sourceUrl],
      ["Como calculamos", note.methodUrl],
    ]);
  });

  it("SourceNote dates the collection in Brasília", async () => {
    // 02:00 UTC on the 28th is still the 27th in Brasília (UTC-3), as the MVP's brasiliaLocal reads it
    const { doc } = await render(SourceNote, { ...note, collectedAt: "2026-09-28T02:00:00Z" });
    expect(doc.body.textContent!.replace(/\s+/g, " ")).toContain("dados de 27/09/2026");
    const late = await render(SourceNote, { ...note, collectedAt: "2026-09-28T03:00:00Z" });
    expect(late.doc.body.textContent!.replace(/\s+/g, " ")).toContain("dados de 28/09/2026");
  });

  it("SourceNote without a method", async () => {
    const { doc } = await render(SourceNote, { index: 2, sourceUrl: note.sourceUrl });
    expect([...doc.querySelectorAll("a")].map((a) => a.getAttribute("href"))).toEqual([note.sourceUrl]);
    expect(doc.body.textContent).not.toContain("Como calculamos");
  });

  it("VoteMark draws a v3 vote by position", async () => {
    const { doc } = await render(VoteMark, { house: "senado", position: "presiding", official: "Presidente (art. 51 RISF)", who: "Ana Souza, PT-SP" });
    const svg = doc.querySelector("svg.ma-vote")!;
    expect(svg.classList.contains("ma-vote--presiding")).toBe(true);
    expect(svg.getAttribute("aria-label")).toBe("Ana Souza, PT-SP, Presidente da sessão (art. 51 RISF)");
    expect(svg.querySelector("circle")?.getAttribute("fill")).toBe("currentColor");

    const yes = (await render(VoteMark, { house: "camara", position: "yes", official: "Sim", who: "Ana Souza, PT-SP" })).doc.querySelector("svg.ma-vote")!;
    expect(yes.getAttribute("aria-label")).toBe("Ana Souza, PT-SP, votou Sim");
    expect(yes.classList.contains("ma-vote--yes")).toBe(true);
  });

  it("MandateScore draws v3 votes by position", async () => {
    const byPosition = [
      { rollCallId: "6923", date: "2025-04-01", title: "PL 1/2025", position: "yes", official: "Sim" },
      { rollCallId: "7001", date: "2025-06-10", title: "Votação secreta de 10/06/2025", position: "secret", official: "Votou" },
      { rollCallId: "7002", date: "2025-07-01", title: "Votação nominal de 01/07/2025", position: "presiding", official: "Presidente (art. 51 RISF)" },
      { rollCallId: "7003", date: "2025-08-01", title: "Votação nominal de 01/08/2025", position: "notVoting", official: null },
    ];
    const { doc } = await render(MandateScore, { votes: byPosition, house: "senado", href: (id: string) => `/senado/votacoes/${id}/` });
    const cols = [...doc.querySelectorAll(".ma-score__col")];
    expect(cols.map((c) => c.getAttribute("href"))).toEqual(["/senado/votacoes/6923/", "/senado/votacoes/7001/", "/senado/votacoes/7002/", "/senado/votacoes/7003/"]);
    expect(cols.map((c) => c.querySelectorAll("rect:not(.ma-score__hit), circle, path").length)).toEqual([1, 0, 1, 0]);
    expect(cols[0].querySelector("rect:not(.ma-score__hit)")?.getAttribute("y")).toBe("2");
    expect([...doc.querySelectorAll(".ma-score__table tbody td:last-child")].map((td) => td.textContent?.trim())).toEqual([
      "Sim", "Votou (votação secreta)", "Presidente da sessão (art. 51 RISF)", "Não registrou voto",
    ]);
    const legend = [...doc.querySelectorAll(".ma-score__legend li")].map((li) => li.textContent?.trim());
    expect(legend).toEqual(["Sim", "Não", "Abstenção", "Obstrução", "Presidente da sessão (art. 51 RISF)", "Não registrou voto"]);
    expect(doc.body.textContent).not.toContain("Art. 17");

    // The v2 inputs still draw the skeleton's cases.
    const old = (await render(MandateScore, { votes })).doc;
    expect([...old.querySelectorAll(".ma-score__legend li")].map((li) => li.textContent?.trim())).toEqual([
      "Sim", "Não", "Abstenção", "Obstrução", "Art. 17 (presidente da sessão)", "Registro sem voto",
    ]);
    expect([...old.querySelectorAll(".ma-score__table tbody td:last-child")].map((td) => td.textContent?.trim())).toEqual([
      "Sim", "Obstrução", "Votação secreta", "Não",
    ]);
  });

  it("MandateScore names one vote in the singular", async () => {
    const toggle = async (v: typeof votes) => (await render(MandateScore, { votes: v })).doc.querySelector(".ma-score__table summary")?.textContent?.trim();
    expect(await toggle(votes.slice(0, 1))).toBe("Ver a 1 votação como tabela");
    expect(await toggle(votes)).toBe("Ver as 4 votações como tabela");
  });

  it("MandateScore without votes", async () => {
    const { doc } = await render(MandateScore, { votes: [] });
    expect(doc.querySelectorAll(".ma-score__row")).toHaveLength(0);
    expect(doc.querySelectorAll(".ma-score__table tbody tr")).toHaveLength(0);
  });

  it("MandateScore ticks each month", async () => {
    const { doc } = await render(MandateScore, { votes });
    const ticks = (row: Element) => [...row.querySelectorAll("line.ma-score__month")].map((l) => Number(l.getAttribute("x1")));
    const rows = [...doc.querySelectorAll(".ma-score__row")];
    // 2023: 01/03 (march), 20/11 and 20/11 (november) -> ticks at votes 0 and 1; 2024: 02/05 -> tick at vote 0
    expect(rows.map(ticks)).toEqual([[0.5, 4.5], [0.5]]);
  });

  it("TallyBar with zero counts", async () => {
    const { doc } = await render(TallyBar, { yes: 0, no: 0, others: 0 });
    expect(doc.querySelectorAll(".ma-tally__cell")).toHaveLength(0);
    expect([...doc.querySelectorAll(".ma-tally__counts dd")].map((d) => d.textContent?.trim())).toEqual(["0", "0", "0"]);
  });

  it("OfficialPhoto without a photo", async () => {
    const { doc } = await render(OfficialPhoto, { src: null, name: "Luiz Philippe de Orleans e Bragança" });
    expect(doc.querySelector("img")).toBeNull();
    const initials = doc.querySelector(".ma-photo__initials")!;
    expect(initials.textContent?.trim()).toBe("LB");
    expect(initials.closest(".ma-photo__mat")).not.toBeNull();
  });

  it("TallyBar renders one cell per vote", async () => {
    const { doc } = await render(TallyBar, { yes: 12, no: 5, others: 2 });
    const cells = [...doc.querySelectorAll(".ma-tally__cell")].map((c) => c.className.split("--")[1]);
    expect(cells).toEqual([...Array(12).fill("yes"), ...Array(5).fill("no"), ...Array(2).fill("others")]);
    expect([...doc.querySelectorAll(".ma-tally__counts dd")].map((d) => d.textContent?.trim())).toEqual(["12", "5", "2"]);
  });

  it("AiSummaryFrame is labelled", async () => {
    const { doc } = await render(AiSummaryFrame, {
      text: "Texto.",
      reviewedAt: "2026-09-30",
      officialUrl: "https://www.camara.leg.br/proposicoesWeb/fichadetramitacao?idProposicao=1",
      reportUrl: "https://augusto-dmh.github.io/mandato-aberto/reportar-erro/",
    });
    const label = doc.querySelector(".ma-ai__label")!.textContent!.replace(/\s+/g, " ").trim();
    expect(label).toBe("Resumo gerado por IA a partir do texto oficial, revisado em 30/09/2026");
    const links = [...doc.querySelectorAll(".ma-ai a")].map((a) => a.getAttribute("href"));
    expect(links).toEqual([
      "https://www.camara.leg.br/proposicoesWeb/fichadetramitacao?idProposicao=1",
      "https://augusto-dmh.github.io/mandato-aberto/reportar-erro/",
    ]);
  });

  it("AiSummaryFrame without review renders nothing", async () => {
    const { html } = await render(AiSummaryFrame, { text: "Texto.", officialUrl: "https://x", reportUrl: "https://y" });
    expect(html.replace(/<!--[\s\S]*?-->/g, "").trim()).toBe("");
  });
});
