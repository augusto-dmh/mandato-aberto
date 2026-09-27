import { describe, expect, it } from "vitest";

import { reportMailto } from "../src/lib/report";
import { CORRECTIONS_EMAIL } from "../src/lib/site";

describe("report form", () => {
  it("composes the mailto", () => {
    const body = (lines: string[]) => encodeURIComponent(lines.join("\n"));
    const subject = `mailto:${CORRECTIONS_EMAIL}?subject=${encodeURIComponent("Erro em /deputados/101/")}&body=`;

    expect(reportMailto({ page: "/deputados/101/", problem: "Voto errado", source: "https://x.gov.br/1", email: "a@b.c" })).toBe(
      subject +
        body([
          "Página com o erro: /deputados/101/",
          "O que está errado: Voto errado",
          "Onde está o dado correto: https://x.gov.br/1",
          "Seu e-mail: a@b.c",
        ]),
    );
    expect(reportMailto({ page: "/deputados/101/", problem: "Voto errado", source: "", email: "" })).toBe(
      subject +
        body([
          "Página com o erro: /deputados/101/",
          "O que está errado: Voto errado",
          "Onde está o dado correto: (não informado)",
          "Seu e-mail: (não informado)",
        ]),
    );
  });
});
