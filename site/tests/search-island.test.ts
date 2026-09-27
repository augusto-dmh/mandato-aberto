import { createSSRApp } from "vue";
import { renderToString } from "vue/server-renderer";
import { afterEach, describe, expect, it, vi } from "vitest";

import DeputySearch from "../src/components/DeputySearch.vue";
import { clearFilters, initialFilters } from "../src/lib/search";

afterEach(() => vi.unstubAllEnvs());

describe("DeputySearch", () => {
  it("empty state offers to clear the filters", async () => {
    const deputies = [
      { id: 7, name: "Fora Um", party: "PT", uf: "SP", inExercise: false, candidate: false, photo: null },
      { id: 8, name: "Fora Dois", party: "PL", uf: "RJ", inExercise: false, candidate: true, photo: null },
    ];
    const html = await renderToString(createSSRApp(DeputySearch, { deputies }));
    expect(html).toContain("Nenhum deputado encontrado com esses filtros.");
    expect(html).toMatch(/<button[^>]*>\s*Limpar filtros\s*<\/button>/);
    expect(html).not.toContain("/deputados/7/");
    expect(clearFilters()).toEqual(initialFilters());
  });

  it("links each deputy under the base", async () => {
    vi.stubEnv("BASE_URL", "/mandato-aberto/");
    const deputies = [
      { id: 7, name: "Dentro Um", party: "PT", uf: "SP", inExercise: true, candidate: false, photo: null },
      { id: 8, name: "Dentro Dois", party: "PL", uf: "RJ", inExercise: true, candidate: true, photo: null },
    ];
    const html = await renderToString(createSSRApp(DeputySearch, { deputies }));
    expect([...html.matchAll(/href="([^"]*)"/g)].map((m) => m[1])).toEqual([
      "/mandato-aberto/deputados/7/",
      "/mandato-aberto/deputados/8/",
    ]);
  });
});
