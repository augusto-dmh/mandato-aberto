import { describe, expect, it } from "vitest";

import { collectedAt, formatCount, groupVotes, periodLabel, voteLabel } from "../src/lib/format";

describe("format", () => {
  it("collection date in Brasília time", () => {
    expect(collectedAt("2026-09-27T12:00:00Z")).toBe("Dados coletados em 27/09/2026 às 09:00 (horário de Brasília)");
    expect(collectedAt("2026-09-28T02:30:00Z")).toBe("Dados coletados em 27/09/2026 às 23:30 (horário de Brasília)");
  });

  it("n de m with pt-BR thousands", () => {
    expect(formatCount(845, 1125)).toBe("845 de 1.125");
    expect(formatCount(1234, 56789)).toBe("1.234 de 56.789");
  });

  it("exercise periods", () => {
    const generatedAt = "2026-09-27T12:00:00Z";
    expect(periodLabel({ start: "2023-02-01T12:05:00", end: "2024-03-01T00:00:00" }, generatedAt)).toBe(
      "01/02/2023 a 01/03/2024",
    );
    expect(periodLabel({ start: "2025-01-10T10:00:00", end: "2026-09-27T09:00:00" }, generatedAt)).toBe(
      "desde 10/01/2025",
    );
  });

  it("vote labels", () => {
    expect(voteLabel("Artigo 17")).toBe("Art. 17 (presidente da sessão)");
    expect(voteLabel("")).toBe("Registro sem voto");
    expect(voteLabel("Sim")).toBe("Sim");
  });

  it("vote labels for secret ballots", () => {
    expect(voteLabel("", true)).toBe("Votação secreta");
    expect(voteLabel("", false)).toBe("Registro sem voto");
    expect(voteLabel("Artigo 17", false)).toBe("Art. 17 (presidente da sessão)");
  });

  it("roll-call vote groups", () => {
    const votes = [
      { name: "Zeca", vote: "" },
      { name: "Úrsula", vote: "Sim" },
      { name: "Bia", vote: "Artigo 17" },
      { name: "Olga", vote: "Obstrução" },
      { name: "Abel", vote: "Sim" },
      { name: "Ícaro", vote: "Não" },
      { name: "Hugo", vote: "Não" },
      { name: "Caio", vote: "Abstenção" },
    ];
    const groups = groupVotes(votes);
    expect(groups.map((g) => g.label)).toEqual(["Sim", "Não", "Abstenção", "Obstrução", "Art. 17", "Registro sem voto"]);
    expect(groups.map((g) => g.entries.map((v) => v.name))).toEqual([
      ["Abel", "Úrsula"],
      ["Hugo", "Ícaro"],
      ["Caio"],
      ["Olga"],
      ["Bia"],
      ["Zeca"],
    ]);
  });
});
