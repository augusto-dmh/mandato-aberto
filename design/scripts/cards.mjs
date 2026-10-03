// Writes one HTML page per share card for the browser tests: node scripts/cards.mjs <pages module> <out dir>
// The pages module default-exports { name: { kind, props } }; each page loads ../tokens.css, ../components.css and ../fonts/fonts.css.
import { mkdirSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const [pagesModule, out] = process.argv.slice(2);
const pages = (await import(pathToFileURL(resolve(pagesModule)).href)).default;

const { createServer } = await import("vite");
const vue = (await import("@vitejs/plugin-vue")).default;
const vite = await createServer({ root: ROOT, configFile: false, plugins: [vue()], logLevel: "error", appType: "custom", server: { middlewareMode: true, hmr: false } });
try {
  const { renderCardPage } = await vite.ssrLoadModule("/scripts/render.js");
  mkdirSync(out, { recursive: true });
  for (const [name, { kind, props }] of Object.entries(pages)) {
    writeFileSync(join(out, `${name}.html`), await renderCardPage(kind, props));
  }
} finally {
  await vite.close();
}
