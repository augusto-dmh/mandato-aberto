import { describe, expect, it } from "vitest";

import AiSummaryFrame from "../components/AiSummaryFrame.vue";
import MandateScore from "../components/MandateScore.vue";
import NDeM from "../components/NDeM.vue";
import OfficialPhoto from "../components/OfficialPhoto.vue";
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
        expect(svg.querySelector("rect")!.getAttribute("fill")).toBe("none");
        expect(svg.querySelector("path")).not.toBeNull();
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
