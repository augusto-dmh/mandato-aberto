// The card renderer (share-cards door 3), built by Vite SSR to bootstrap/cards/render.mjs.
//   node bootstrap/cards/render.mjs [--html] < {"kind", "format", "props", "photo"}
// Renders the design package's card with Vue, then screenshots it in headless Chromium, which may load
// nothing but data: URIs. Writes the PNG (or, with --html, the page) to stdout.
// Exit 0 rendered, 1 render failed (reason on stderr), 2 invalid input.
import { readFileSync } from "node:fs";
import { createRequire } from "node:module";
import { dirname, join } from "node:path";
import { pathToFileURL } from "node:url";

import MemberCard from "mandato-design/components/MemberCard.vue";
import RollCallCard from "mandato-design/components/RollCallCard.vue";
import components from "mandato-design/styles/components.css?raw";
import tokens from "mandato-design/tokens.css?raw";
import { createSSRApp, h } from "vue";
import { renderToString } from "vue/server-renderer";

export const FORMATS = { og: { width: 1200, height: 630 }, feed: { width: 1080, height: 1350 }, story: { width: 1080, height: 1920 } };
const CARDS = { member: MemberCard, roll_call: RollCallCard };
const FONTS = [
  ["@fontsource-variable/archivo", "wdth.css"],
  ["@fontsource-variable/source-serif-4", "opsz.css"],
];

class InvalidInput extends Error {}

/** The latin and latin-ext faces of the two variable fonts, each file inlined as a data: URI. */
function fontFaces() {
  const require = createRequire(import.meta.url);
  const faces = [];
  for (const [pkg, file] of FONTS) {
    const css = readFileSync(require.resolve(`${pkg}/${file}`), "utf8");
    const dir = dirname(require.resolve(`${pkg}/${file}`));
    for (const block of css.split(/\n(?=\/\*)/)) {
      if (!/-latin(-ext)?-[a-z]+-normal \*\//.test(block)) continue;
      faces.push(
        block.replace(/url\(\.\/files\/([^)]+)\)/, (_, name) => `url(data:font/woff2;base64,${readFileSync(join(dir, "files", name)).toString("base64")})`),
      );
    }
  }
  return faces.join("\n");
}

function parse(text) {
  let input;
  try {
    input = JSON.parse(text);
  } catch {
    throw new InvalidInput("input is not JSON");
  }
  if (!CARDS[input?.kind]) throw new InvalidInput(`unknown kind: ${input?.kind}`);
  if (!FORMATS[input.format]) throw new InvalidInput(`unknown format: ${input.format}`);
  if (input.photo !== null && input.photo !== undefined && !(typeof input.photo === "string" && input.photo.startsWith("data:image/jpeg;base64,"))) {
    throw new InvalidInput("photo is neither null nor a data:image/jpeg;base64, URI");
  }
  if (typeof input.props !== "object" || input.props === null) throw new InvalidInput("props is not an object");
  return input;
}

/** The whole page of one card: its markup and every style inline. */
export async function cardPage({ kind, format, props, photo }) {
  const card = await renderToString(createSSRApp({ render: () => h(CARDS[kind], { ...props, format, ...(kind === "member" ? { photo: photo ?? null } : {}) }) }));
  return `<!doctype html>
<html lang="pt-BR" data-direction="plenario" data-theme="light">
<head>
<meta charset="utf-8">
<style>
${fontFaces()}
${tokens}
${components}
body { margin: 0; }
</style>
</head>
<body class="ma-page">${card}</body>
</html>`;
}

/** Screenshots the `.ma-card` of `html` at `size`; every request that is not a data: URI is aborted and listed. */
export async function capture(html, { width, height }) {
  const { chromium } = await import("playwright-core");
  const browser = await chromium.launch();
  const blocked = [];
  try {
    const context = await browser.newContext({ viewport: { width, height }, deviceScaleFactor: 1, colorScheme: "light" });
    await context.route("**/*", (route) => {
      blocked.push(route.request().url());
      return route.abort();
    });
    const page = await context.newPage();
    await page.setContent(html, { waitUntil: "load" });
    await page.evaluate(() => document.fonts.ready).catch(() => {});
    const png = await page.locator(".ma-card").screenshot({ type: "png" });
    return { png, blocked };
  } finally {
    await browser.close();
  }
}

async function main() {
  const htmlOnly = process.argv.includes("--html");
  let input;
  try {
    input = parse(readFileSync(0, "utf8"));
  } catch (error) {
    process.stderr.write(`${error.message}\n`);
    return 2;
  }
  try {
    const html = await cardPage(input);
    if (htmlOnly) {
      process.stdout.write(html);
      return 0;
    }
    const { png, blocked } = await capture(html, FORMATS[input.format]);
    for (const url of blocked) process.stderr.write(`blocked ${url}\n`);
    await new Promise((resolve) => process.stdout.write(png, resolve));
    return 0;
  } catch (error) {
    process.stderr.write(`${String(error?.message ?? error).split("\n")[0]}\n`);
    return 1;
  }
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  process.exitCode = await main();
}
