/**
 * 1200x630 share cards rendered at build time: satori (tree -> SVG) + resvg (SVG -> PNG), door 3.
 * satori reads WOFF but not WOFF2, so the cards load the static `@fontsource` files (card fonts door).
 */
import { Resvg } from "@resvg/resvg-js";
import { readFileSync } from "node:fs";
import { createRequire } from "node:module";
import { join } from "node:path";
import satori from "satori";

import type { DeputySummary, Indicator, Meta } from "./data";
import { collectedAt, formatCount, formatNumber, NO_BASE } from "./format";
import { INDICATORS } from "./indicators";

export interface Node {
  type: string;
  props: { style?: Record<string, unknown>; children?: Child | Child[]; [key: string]: unknown };
}
type Child = Node | string | null;

export interface CardPhoto {
  src: string;
  width: number;
  height: number;
}

const WIDTH = 1200;
const HEIGHT = 630;
const PHOTO_BOX = { width: 270, height: 360 };
const INK = "#1A1A1A";
const MUTED = "#5C5A55";
const PAPER = "#F7F5F0";
const ACCENT = "#B3261E";
const SERIF = "Source Serif 4";
const SANS = "Inter";

const el = (type: string, style: Record<string, unknown>, children?: Child | Child[], extra = {}): Node => ({
  type,
  props: { style, children, ...extra },
});

/** Width and height from the first JPEG start-of-frame marker, or null for anything else. */
function jpegSize(bytes: Buffer): { width: number; height: number } | null {
  if (bytes[0] !== 0xff || bytes[1] !== 0xd8) return null;
  let at = 2;
  while (at + 9 < bytes.length) {
    if (bytes[at] !== 0xff) return null;
    const marker = bytes[at + 1];
    const isFrame = marker >= 0xc0 && marker <= 0xcf && ![0xc4, 0xc8, 0xcc].includes(marker);
    if (isFrame) return { height: bytes.readUInt16BE(at + 5), width: bytes.readUInt16BE(at + 7) };
    at += 2 + bytes.readUInt16BE(at + 2);
  }
  return null;
}

export function photoForCard(path: string | null): CardPhoto | null {
  if (!path) return null;
  const bytes = readFileSync(path);
  const size = jpegSize(bytes);
  return size && { src: `data:image/jpeg;base64,${bytes.toString("base64")}`, ...size };
}

/** The whole photo scaled into the box, keeping its proportions: never cropped. */
function fitted(photo: CardPhoto) {
  const scale = Math.min(PHOTO_BOX.width / photo.width, PHOTO_BOX.height / photo.height);
  return { width: Math.round(photo.width * scale), height: Math.round(photo.height * scale) };
}

const valueOf = (i: Indicator) => (i.total === 0 ? NO_BASE : formatCount(i.count, i.total));

function cell(label: string, value: string, small = false): Node {
  return el(
    "div",
    { display: "flex", flexDirection: "column", width: "48%", borderTop: `2px solid ${INK}`, paddingTop: 8, marginTop: 18 },
    [
      el("div", { fontFamily: SANS, fontSize: 19, color: MUTED, lineHeight: 1.25 }, label),
      el("div", { fontFamily: SERIF, fontWeight: 700, fontSize: small ? 28 : 44, color: ACCENT, marginTop: 4 }, value),
    ],
  );
}

function frame(main: Node[], meta: Meta): Node {
  return el(
    "div",
    { width: WIDTH, height: HEIGHT, display: "flex", flexDirection: "column", backgroundColor: PAPER, color: INK, padding: "44px 56px", fontFamily: SANS },
    [
      el("div", { display: "flex", fontSize: 20, letterSpacing: 2, color: ACCENT, fontWeight: 700 }, "MANDATO ABERTO · 57ª LEGISLATURA"),
      el("div", { display: "flex", flexGrow: 1, marginTop: 24, marginBottom: 16 }, main),
      el("div", { display: "flex", justifyContent: "space-between", fontSize: 18, color: MUTED, borderTop: `1px solid ${MUTED}`, paddingTop: 12 }, [
        el("div", {}, "Fonte: Câmara dos Deputados - dados abertos"),
        el("div", {}, collectedAt(meta.generatedAt)),
      ]),
    ],
  );
}

export function cardTree(deputy: DeputySummary, meta: Meta, photo: CardPhoto | null): Node {
  const left: Node[] = [];
  if (photo) {
    const size = fitted(photo);
    left.push(
      el("div", { display: "flex", flexDirection: "column", width: PHOTO_BOX.width, marginRight: 48 }, [
        el("img", { width: size.width, height: size.height }, null, { src: photo.src, width: size.width, height: size.height }),
        el("div", { fontSize: 15, color: MUTED, marginTop: 8 }, "Foto: Câmara dos Deputados"),
      ]),
    );
  }
  const right = el("div", { display: "flex", flexDirection: "column", flexGrow: 1, flexBasis: 0 }, [
    el("div", { fontFamily: SERIF, fontWeight: 700, fontSize: deputy.name.length > 22 ? 44 : 56, lineHeight: 1.05 }, deputy.name),
    el("div", { fontSize: 24, color: MUTED, marginTop: 6 }, `${deputy.party} · ${deputy.uf}`),
    el("div", { display: "flex", flexWrap: "wrap", justifyContent: "space-between" }, [
      ...INDICATORS.map((i) => cell(i.label, valueOf(deputy[i.field]), deputy[i.field].total === 0)),
      cell("Proposições de autoria", formatNumber(deputy.authoredCount)),
    ]),
  ]);
  return frame([...left, right], meta);
}

export function siteCardTree(meta: Meta): Node {
  return frame(
    [
      el("div", { display: "flex", flexDirection: "column", justifyContent: "center" }, [
        el("div", { fontFamily: SERIF, fontWeight: 700, fontSize: 64, lineHeight: 1.1 }, "O que cada deputado federal fez no mandato"),
        el("div", { fontSize: 28, color: MUTED, marginTop: 20 }, "Votos, participação e proposições com dados oficiais da Câmara dos Deputados."),
        el("div", { display: "flex", fontFamily: SERIF, fontSize: 32, color: ACCENT, marginTop: 32 }, [
          el("div", { marginRight: 40 }, `${formatNumber(meta.counts.deputies)} deputados`),
          el("div", {}, `${formatNumber(meta.counts.rollCalls)} votações nominais`),
        ]),
      ]),
    ],
    meta,
  );
}

let fonts: { name: string; data: Buffer; weight: 400 | 700; style: "normal" }[] | undefined;

function loadFonts() {
  const require = createRequire(join(process.cwd(), "package.json"));
  const file = (pkg: string, name: string) => readFileSync(require.resolve(`${pkg}/files/${name}`));
  fonts ??= ["latin", "latin-ext"].flatMap((subset) => [
    { name: SANS, data: file("@fontsource/inter", `inter-${subset}-400-normal.woff`), weight: 400 as const, style: "normal" as const },
    { name: SANS, data: file("@fontsource/inter", `inter-${subset}-700-normal.woff`), weight: 700 as const, style: "normal" as const },
    { name: SERIF, data: file("@fontsource/source-serif-4", `source-serif-4-${subset}-700-normal.woff`), weight: 700 as const, style: "normal" as const },
  ]);
  return fonts;
}

export async function renderCard(tree: Node): Promise<Buffer> {
  const svg = await satori(tree as any, { width: WIDTH, height: HEIGHT, fonts: loadFonts() });
  return new Resvg(svg, { fitTo: { mode: "width", value: WIDTH } }).render().asPng();
}
