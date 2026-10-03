// share-cards S4: the card templates' text, order and tree (C37, C39, C40, C44, C47).
import { describe, expect, it } from "vitest";

import MemberCard from "../components/MemberCard.vue";
import RollCallCard from "../components/RollCallCard.vue";
import Card from "../screens/Card.vue";
import { render } from "./render";

const FORMATS = ["og", "feed", "story"] as const;
const clean = (s: string | null | undefined) => (s ?? "").replace(/\s+/g, " ").trim();

const ANA = {
  house: "camara",
  legislature: 57,
  member: { name: "Ana Souza", party: "PSB", uf: "SP" },
  figures: [
    { count: 3, total: 4, label: "Participação em votações nominais do plenário" },
    { count: 2, total: 3, label: "Votos iguais à orientação do governo" },
    { count: 1, total: 2, label: "Votos iguais à maioria do próprio partido" },
  ],
  votes: [
    { rollCallId: "100-1", date: "2023-03-01", position: "yes", official: "Sim" },
    { rollCallId: "100-2", date: "2023-05-10", position: "abstention", official: "Abstenção" },
    { rollCallId: "100-3", date: "2024-03-01", position: "no", official: "Não" },
  ],
  photo: "data:image/jpeg;base64,/9j/",
  generatedAt: "2027-03-02T02:30:00Z",
  code: "20270301-K7Q29XPD",
  verifyHost: "mandato.test",
};

const ROLL_CALL = {
  house: "camara",
  heading: "PL 1/2023",
  date: "2023-03-01",
  ballot: "nominal",
  kind: "final",
  approved: true,
  tallies: { yes: 1, no: 1, others: 1 },
  generatedAt: "2027-03-02T02:30:00Z",
  code: "20270301-K7Q29XPD",
  verifyHost: "mandato.test",
};

/** The text of each region of a member card, in DOM order. */
function memberRegions(doc: Document) {
  const card = doc.querySelector(".ma-card")!;
  return [
    ".ma-card__eyebrow",
    ".ma-photo",
    "h1",
    ".ma-card__body > p.ma-muted",
    ".ma-card__basis",
    ".ma-card__figures",
    ".ma-card__score-label",
    ".ma-score",
    ".ma-card__foot",
  ].map((sel) => card.querySelector(sel));
}

function inDocumentOrder(nodes: (Element | null)[]) {
  for (let i = 1; i < nodes.length; i++) {
    // DOCUMENT_POSITION_FOLLOWING = 4
    if (!(nodes[i - 1]!.compareDocumentPosition(nodes[i]!) & 4)) return false;
  }
  return true;
}

describe("share cards", () => {
  it("member card text in order", async () => {
    for (const format of FORMATS) {
      for (const [house, eyebrow, credit] of [
        ["camara", "Mandato Aberto · Câmara dos Deputados · 57ª legislatura", "Foto: Câmara dos Deputados"],
        ["senado", "Mandato Aberto · Senado Federal · 57ª legislatura", "Foto: Agência Senado"],
      ]) {
        const { doc } = await render(MemberCard, { ...ANA, house, format });
        const card = doc.querySelector(".ma-card")!;
        expect(card.className).toContain(`ma-card--${format}`);
        const regions = memberRegions(doc);
        expect(regions.every(Boolean), format).toBe(true);
        expect(inDocumentOrder(regions), format).toBe(true);
        expect(card.querySelectorAll("h1")).toHaveLength(1);
        expect(clean(regions[0]!.textContent)).toBe(eyebrow);
        expect(clean(card.querySelector(".ma-photo__credit")!.textContent)).toBe(credit);
        expect(clean(regions[2]!.textContent)).toBe("Ana Souza");
        expect(clean(regions[3]!.textContent)).toBe("PSB · SP");
        expect(clean(regions[4]!.textContent)).toBe("Nas votações sobre propostas e emendas");
        expect([...card.querySelectorAll(".ma-card__figure")].map((f) => clean(f.textContent))).toEqual([
          "3 de 4 Participação em votações nominais do plenário",
          "2 de 3 Votos iguais à orientação do governo",
          "1 de 2 Votos iguais à maioria do próprio partido",
        ]);
        expect(clean(regions[6]!.textContent)).toBe("3 votações nominais do plenário com registro, da mais antiga à mais recente");
        expect(regions[7]!.className).toContain("ma-score--compact");
        expect(clean(regions[8]!.textContent)).toBe(
          `Fonte: ${house === "camara" ? "Câmara dos Deputados" : "Senado Federal"}, dados de 01/03/2027 Código 20270301-K7Q29XPD · confira em mandato.test/verificar/`,
        );
      }
    }
  });

  it("member card empty states", async () => {
    for (const format of FORMATS) {
      const figures = ANA.figures.map((f) => ({ ...f, count: 0, total: 0 }));
      const { doc } = await render(MemberCard, { ...ANA, format, figures, votes: [] });
      const texts = [...doc.querySelectorAll(".ma-card__figure")].map((f) => clean(f.textContent));
      expect(texts).toEqual(ANA.figures.map((f) => `${f.label}: Sem base de cálculo no período`));
      for (const t of texts) expect(t).not.toMatch(/\d/);
      expect(doc.querySelector(".ma-score")).toBeNull();
      expect(clean(doc.querySelector(".ma-card__score-label")!.textContent)).toBe("Nenhuma votação nominal do plenário com registro nesta legislatura.");
    }
  });

  it("roll-call card text in order", async () => {
    const regions = [".ma-card__eyebrow", "h1", ".ma-card__body > p.ma-muted", ".ma-card__result", ".ma-card__tally", ".ma-card__foot"];
    const cases: [Record<string, unknown>, string, string | null][] = [
      [{}, "Aprovada", "1 Sim · 1 Não · 1 outros votos"],
      [{ approved: null }, "Resultado não informado", "1 Sim · 1 Não · 1 outros votos"],
      [{ approved: false }, "Rejeitada", "1 Sim · 1 Não · 1 outros votos"],
      [{ ballot: "symbolic", tallies: null }, "Aprovada", null],
      [{ ballot: "secret", tallies: null }, "Aprovada", null],
    ];
    for (const format of FORMATS) {
      for (const [change, result, tally] of cases) {
        const { doc } = await render(RollCallCard, { ...ROLL_CALL, ...change, format });
        const card = doc.querySelector(".ma-card")!;
        const nodes = regions.map((sel) => card.querySelector(sel));
        expect(nodes.every(Boolean)).toBe(true);
        expect(inDocumentOrder(nodes)).toBe(true);
        expect(card.querySelectorAll("h1")).toHaveLength(1);
        expect(clean(nodes[0]!.textContent)).toBe("Mandato Aberto · Câmara dos Deputados");
        expect(clean(nodes[1]!.textContent)).toBe("PL 1/2023");
        const ballot = { nominal: "Votação nominal", symbolic: "Votação simbólica", secret: "Votação secreta" }[(change.ballot as string) ?? "nominal"];
        expect(clean(nodes[2]!.textContent)).toBe(`${ballot} · Decisão sobre a proposta · 01/03/2023`);
        expect(clean(nodes[3]!.textContent)).toBe(result);
        if (tally) {
          expect(clean(card.querySelector(".ma-card__tallies")!.textContent)).toBe(tally);
          expect(card.querySelector(".ma-card__tally .ma-tally")).not.toBeNull();
        } else {
          expect(card.querySelector(".ma-tally")).toBeNull();
          expect(clean(nodes[4]!.textContent)).toBe(
            change.ballot === "symbolic" ? "Votação simbólica: não há registro do voto de cada parlamentar nem placar." : "Placar não publicado pela Casa.",
          );
        }
        expect(clean(nodes[5]!.textContent)).toBe("Fonte: Câmara dos Deputados, dados de 01/03/2027 Código 20270301-K7Q29XPD · confira em mandato.test/verificar/");
      }
    }
    const { doc } = await render(RollCallCard, { ...ROLL_CALL, house: "senado", tallies: { yes: 40, no: 20, others: 1 } });
    expect(clean(doc.querySelector(".ma-card__eyebrow")!.textContent)).toBe("Mandato Aberto · Senado Federal");
    expect(clean(doc.querySelector(".ma-card__tallies")!.textContent)).toBe("40 Sim · 20 Não · 1 outros votos");
  });

  it("every member card shares one tree", async () => {
    const shape = (doc: Document) =>
      [...doc.querySelectorAll(".ma-card *")]
        .filter((el) => !el.closest(".ma-score__col") && !el.parentElement!.closest(".ma-photo"))
        .map((el) => `${el.tagName}.${el.getAttribute("class") ?? ""}`)
        .join(" ");
    const other = {
      ...ANA,
      member: { name: "Luiz Philippe de Orleans e Bragança", party: "PL", uf: "SP" },
      figures: [
        { count: 1234, total: 2345, label: ANA.figures[0].label },
        { count: 0, total: 9, label: ANA.figures[1].label },
        { count: 7, total: 8, label: ANA.figures[2].label },
      ],
      votes: [{ rollCallId: "9-9", date: "2025-01-01", position: "presiding", official: "Artigo 17" }],
      photo: null,
    };
    for (const format of FORMATS) {
      const [a, b] = await Promise.all([render(MemberCard, { ...ANA, format }), render(MemberCard, { ...other, format })]);
      expect(shape(b.doc), format).toBe(shape(a.doc));
    }
  });

  it("the prototype card is the member card", async () => {
    const { doc } = await render(Card, {
      generatedAt: "2026-09-27T12:00:00Z",
      deputy: { name: "Ana Souza", party: "PSB", uf: "SP" },
      figures: ANA.figures,
      votes: [{ rollCallId: "1-1", date: "2023-03-01", title: "PL 1/2023", vote: "Sim", secret: false }],
    });
    const card = doc.querySelector(".ma-card-page > .ma-card")!;
    expect(card.className).toBe("ma-card ma-card--og");
    expect(clean(card.querySelector("h1")!.textContent)).toBe("Ana Souza");
    expect(card.querySelectorAll(".ma-score__col")).toHaveLength(1);
  });
});
