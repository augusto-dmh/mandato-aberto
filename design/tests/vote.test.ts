import { describe, expect, it } from "vitest";

import { markShapes, positionCase, voteCase } from "../components/vote.js";

// Check C60 of .specs/features/app-contract-v3/checks.md (app-contract-v3 doors 5 and 6).

const KINDS = {
  yes: "yes",
  no: "no",
  abstention: "abstention",
  obstruction: "obstruction",
  presiding: "presiding",
  secret: "secret",
  notVoting: "not-voting",
};

describe("positionCase", () => {
  it("positionCase gives each house and position its mark and label", () => {
    const table: [string, string, string][] = [
      ["camara", "yes", "Sim"],
      ["camara", "no", "Não"],
      ["camara", "abstention", "Abstenção"],
      ["camara", "obstruction", "Obstrução"],
      ["camara", "presiding", "Art. 17 (presidente da sessão)"],
      ["camara", "secret", "Votação secreta"],
      ["camara", "notVoting", "Registro sem voto"],
      ["senado", "yes", "Sim"],
      ["senado", "no", "Não"],
      ["senado", "abstention", "Abstenção"],
      ["senado", "obstruction", "Obstrução"],
      ["senado", "presiding", "Presidente da sessão (art. 51 RISF)"],
      ["senado", "secret", "Votou (votação secreta)"],
      ["senado", "notVoting", "Não registrou voto"],
    ];
    expect(table).toHaveLength(14);
    for (const [house, position, label] of table) {
      expect(positionCase({ house, position }), `${house} ${position}`).toEqual({ kind: KINDS[position as keyof typeof KINDS], label });
    }
  });

  it("positionCase writes the Senate's description of each non-vote code", () => {
    const table: [string, string][] = [
      ["P-NRV", "Sem voto: Presente, não registrou voto"],
      ["AP", "Sem voto: Atividade parlamentar"],
      ["MIS", "Sem voto: Missão da Casa no País ou no exterior"],
      ["NCom", "Sem voto: Não compareceu"],
      ["NA", "Sem voto: Dispositivo não citado"],
      ["Licença", "Sem voto: Licença"],
      ["XYZ", "Sem voto: XYZ"],
    ];
    for (const [official, label] of table) {
      expect(positionCase({ house: "senado", position: "notVoting", official }), official).toEqual({ kind: "not-voting", label });
    }
    expect(positionCase({ house: "senado", position: "notVoting", official: null }).label).toBe("Não registrou voto");
  });

  it("positionCase labels a Câmara vote as voteCase labels its official value", () => {
    const table: [string, string, boolean][] = [
      ["yes", "Sim", false],
      ["no", "Não", false],
      ["abstention", "Abstenção", false],
      ["obstruction", "Obstrução", false],
      ["presiding", "Artigo 17", false],
      ["secret", "", true],
      ["notVoting", "", false],
    ];
    for (const [position, official, secret] of table) {
      expect(positionCase({ house: "camara", position, official }).label, position).toBe(voteCase(official, secret).label);
    }
  });

  it("positionCase marks presiding as a dot and the two non-votes as gaps", () => {
    expect(markShapes("presiding")).toEqual(markShapes("article-17"));
    expect(markShapes("presiding")).toHaveLength(1);
    expect(markShapes("secret")).toEqual([]);
    expect(markShapes("not-voting")).toEqual([]);
    expect(markShapes("yes")[0].attrs.y + markShapes("yes")[0].attrs.height).toBeLessThanOrEqual(12);
    expect(markShapes("no")[0].attrs.y).toBeGreaterThanOrEqual(12);
  });
});
