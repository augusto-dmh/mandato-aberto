import { readFileSync } from "node:fs";
import { join } from "node:path";
import { describe, expect, it } from "vitest";

import { cardTree, photoForCard, type Node } from "../src/lib/cards";
import { loadContract } from "../src/lib/data";

const contract = loadContract(join(__dirname, "fixtures", "out"));
const deputy = (id: number) => contract.deputies.find((d) => d.id === id)!;
const photo = photoForCard(join(__dirname, "fixtures", "photos", "101.jpg"));

function texts(node: Node | string | null | undefined, out: string[] = []): string[] {
  if (node == null) return out;
  if (typeof node === "string") {
    out.push(node);
    return out;
  }
  const children = node.props.children;
  for (const child of Array.isArray(children) ? children : [children]) texts(child as Node, out);
  return out;
}

function images(node: Node | string | null | undefined, out: Node[] = []): Node[] {
  if (node == null || typeof node === "string") return out;
  if (node.type === "img") out.push(node);
  const children = node.props.children;
  for (const child of Array.isArray(children) ? children : [children]) images(child as Node, out);
  return out;
}

/** Asserts `expected` appears in `actual` in order, each item as a whole text node. */
function expectInOrder(actual: string[], expected: string[]) {
  let at = 0;
  for (const item of expected) {
    const found = actual.indexOf(item, at);
    expect(found, `"${item}" after position ${at} in ${JSON.stringify(actual)}`).toBeGreaterThanOrEqual(0);
    at = found + 1;
  }
}

const PARTICIPATION = "Participação em votações nominais do plenário";
const GOVERNMENT = "Votos iguais à orientação do governo";
const PARTY = "Votos iguais à maioria do próprio partido";

describe("deputy card content", () => {
  it("deputy card content", () => {
    expect(photo).not.toBeNull();
    const tree101 = cardTree(deputy(101), contract.meta, photo);
    expectInOrder(texts(tree101), [
      "Ana Souza",
      "PT · SP",
      PARTICIPATION,
      "4 de 5",
      GOVERNMENT,
      "2 de 3",
      PARTY,
      "2 de 3",
      "Proposições de autoria",
      "5",
      "Fonte: Câmara dos Deputados - dados abertos",
      "Dados coletados em 27/09/2026 às 09:00 (horário de Brasília)",
    ]);
    const [image, ...rest] = images(tree101);
    expect(rest).toEqual([]);
    const { width, height } = image.props.style as { width: number; height: number };
    expect(width / height).toBeCloseTo(4 / 3, 2);
    expect(texts(tree101)).toContain("Foto: Câmara dos Deputados");

    const tree102 = cardTree(deputy(102), contract.meta, null);
    expectInOrder(texts(tree102), [PARTICIPATION, "3 de 3", GOVERNMENT, "1 de 1", PARTY, "2 de 3"]);

    const tree103 = cardTree(deputy(103), contract.meta, null);
    expect(images(tree103)).toEqual([]);
    expect(texts(tree103)).not.toContain("Foto: Câmara dos Deputados");
    expectInOrder(texts(tree103), [
      PARTICIPATION,
      "Sem base de cálculo no período",
      GOVERNMENT,
      "0 de 1",
      PARTY,
      "Sem base de cálculo no período",
    ]);
  });

  it("card shows no percentage", () => {
    for (const id of [101, 102, 103]) {
      for (const text of texts(cardTree(deputy(id), contract.meta, id === 101 ? photo : null))) {
        expect(text).not.toContain("%");
      }
    }
  });

  it("reads the size of the photo it embeds", () => {
    const bytes = readFileSync(join(__dirname, "fixtures", "photos", "101.jpg"));
    expect(photo!.width).toBe(400);
    expect(photo!.height).toBe(300);
    expect(photo!.src).toBe(`data:image/jpeg;base64,${bytes.toString("base64")}`);
  });
});
