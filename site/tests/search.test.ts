import { describe, expect, it } from "vitest";

import { clearFilters, filterDeputies, initialFilters, matchesQuery, sortByName } from "../src/lib/search";

const card = (id: number, name: string, extra: object = {}) => ({
  id,
  name,
  party: "PT",
  uf: "SP",
  inExercise: true,
  candidate: false,
  photo: null,
  ...extra,
});

describe("search", () => {
  it("orders by accent-stripped name", () => {
    const sorted = sortByName([card(1, "Zélia"), card(2, "Érico"), card(3, "Eduardo"), card(4, "álvaro")]);
    expect(sorted.map((d) => d.name)).toEqual(["álvaro", "Eduardo", "Érico", "Zélia"]);
  });

  it("search is accent- and case-insensitive", () => {
    expect(matchesQuery("José Bruno", "jose")).toBe(true);
    expect(matchesQuery("José Bruno", "JOSÉ")).toBe(true);
    expect(matchesQuery("José", "sé")).toBe(true);
    expect(matchesQuery("José", "joão")).toBe(false);
    expect(matchesQuery("José", "")).toBe(true);
  });

  it("filters combine", () => {
    // Each of the first four fails exactly one filter of `active`; `all` passes every one.
    const all = card(1, "Todos", { uf: "RJ", party: "PSOL", inExercise: true, candidate: true });
    const wrongUf = card(2, "Uf", { uf: "SP", party: "PSOL", inExercise: true, candidate: true });
    const wrongParty = card(3, "Partido", { uf: "RJ", party: "PL", inExercise: true, candidate: true });
    const outOfExercise = card(4, "Fora", { uf: "RJ", party: "PSOL", inExercise: false, candidate: true });
    const notCandidate = card(5, "Nao", { uf: "RJ", party: "PSOL", inExercise: true, candidate: false });
    const deputies = [all, wrongUf, wrongParty, outOfExercise, notCandidate];
    const none = { query: "", uf: "", party: "", inExercise: false, candidacy: false };
    const ids = (filters: typeof none) => filterDeputies(deputies, filters).map((d) => d.id);

    expect(ids(none)).toEqual([1, 2, 3, 4, 5]);
    expect(ids({ ...none, uf: "RJ" })).toEqual([1, 3, 4, 5]);
    expect(ids({ ...none, party: "PSOL" })).toEqual([1, 2, 4, 5]);
    expect(ids({ ...none, inExercise: true })).toEqual([1, 2, 3, 5]);
    expect(ids({ ...none, candidacy: true })).toEqual([1, 2, 3, 4]);
    expect(ids({ query: "", uf: "RJ", party: "PSOL", inExercise: true, candidacy: true })).toEqual([1]);
    expect(ids({ ...none, query: "tod" })).toEqual([1]);

    expect(initialFilters()).toEqual({ query: "", uf: "", party: "", inExercise: true, candidacy: false });
  });

  it("clearing returns to the initial filters", () => {
    expect(clearFilters()).toEqual(initialFilters());
  });
});
