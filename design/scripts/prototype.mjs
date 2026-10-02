// Renders profile, roll-call and card pages in every direction from an ETL contract directory.
//   MANDATO_DATA=<contract dir> node scripts/prototype.mjs [--deputy <id>] [--roll-call <id>] [--out <dir>]
// MANDATO_PHOTO_CACHE points at a folder of official photos named <deputy id>.jpg (default: the site's cache).
import { copyFileSync, cpSync, mkdirSync, readFileSync, readdirSync, rmSync, writeFileSync } from "node:fs";
import { basename, dirname, extname, join, resolve } from "node:path";
import { fileURLToPath } from "node:url";
import { parseArgs } from "node:util";

import { SchemaError, checkSchema, loadInputs } from "./contract.mjs";

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const DIRECTIONS = readdirSync(join(ROOT, "tokens"))
  .filter((f) => f.endsWith(".json") && f !== "base.json")
  .map((f) => f.replace(/\.json$/, ""))
  .sort();
const FONTS = [
  ["archivo", "wdth.css"],
  ["source-serif-4", "opsz.css"],
];

/** Copies the latin and latin-ext faces of each variable font package and writes one fonts.css. */
function writeFonts(out) {
  const dir = join(out, "fonts");
  mkdirSync(join(dir, "files"), { recursive: true });
  const faces = [];
  for (const [pkg, file] of FONTS) {
    const base = join(ROOT, "node_modules", "@fontsource-variable", pkg);
    const css = readFileSync(join(base, file), "utf8");
    for (const block of css.split(/\n(?=\/\*)/)) {
      if (!/-latin(-ext)?-[a-z]+-normal \*\//.test(block)) continue;
      const url = /url\(\.\/files\/([^)]+)\)/.exec(block)[1];
      copyFileSync(join(base, "files", url), join(dir, "files", url));
      faces.push(block.trim());
    }
    copyFileSync(join(base, "LICENSE"), join(dir, `LICENSE-${pkg}.txt`));
  }
  writeFileSync(join(dir, "fonts.css"), `/* SIL Open Font License 1.1; licences in this folder. */\n${faces.join("\n\n")}\n`);
}

async function main() {
  const { values } = parseArgs({
    options: { deputy: { type: "string" }, "roll-call": { type: "string" }, out: { type: "string" } },
  });
  const data = resolve(process.env.MANDATO_DATA ?? join(ROOT, "..", "data", "out"));
  const out = resolve(values.out ?? join(ROOT, "dist", "prototype"));
  const photoDir = resolve(process.env.MANDATO_PHOTO_CACHE ?? join(ROOT, "..", "site", ".cache", "photos"));

  checkSchema(data);
  const inputs = loadInputs(data, { deputy: values.deputy, rollCall: values["roll-call"], photoDir });

  rmSync(out, { recursive: true, force: true });
  mkdirSync(join(out, "photos"), { recursive: true });
  cpSync(join(ROOT, "dist", "tokens.css"), join(out, "tokens.css"));
  cpSync(join(ROOT, "styles", "components.css"), join(out, "components.css"));
  writeFonts(out);
  let photo = null;
  if (inputs.photoFile) {
    const name = `${inputs.profile.deputy.id}${extname(inputs.photoFile)}`;
    copyFileSync(inputs.photoFile, join(out, "photos", name));
    photo = `../photos/${name}`;
  }

  const { createServer } = await import("vite");
  const vue = (await import("@vitejs/plugin-vue")).default;
  const vite = await createServer({ root: ROOT, configFile: false, plugins: [vue()], logLevel: "error", appType: "custom", server: { middlewareMode: true, hmr: false } });
  try {
    const { renderPage } = await vite.ssrLoadModule("/scripts/render.js");
    const common = { generatedAt: inputs.generatedAt, photo };
    const pages = {
      profile: { ...common, ...inputs.profile, title: inputs.profile.deputy.name },
      "roll-call": { ...common, ...inputs.rollCall, title: inputs.rollCall.rollCall.title },
      card: { ...common, ...inputs.card, title: `Card de ${inputs.card.deputy.name}` },
    };
    for (const direction of DIRECTIONS) {
      mkdirSync(join(out, direction), { recursive: true });
      for (const [screen, props] of Object.entries(pages)) {
        writeFileSync(join(out, direction, `${screen}.html`), await renderPage(screen, direction, props));
      }
    }
  } finally {
    await vite.close();
  }
  console.log(`wrote ${DIRECTIONS.length * 3} pages to ${out} (deputy ${inputs.profile.deputy.id}, roll call ${inputs.rollCall.rollCall.id})`);
}

main().catch((error) => {
  console.error(error instanceof SchemaError ? error.message : error.stack ?? String(error));
  process.exit(1);
});
