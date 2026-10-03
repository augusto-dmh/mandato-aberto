// Builds the prototype once from the contract fixture, with the longest real deputy name and a synthetic 354 x 472 photo.
import { execFileSync } from "node:child_process";
import { copyFileSync, cpSync, mkdirSync, mkdtempSync, readFileSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath } from "node:url";

export const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), "..");
export const OUT = join(ROOT, "dist", "e2e");
export const LONGEST = "Luiz Philippe de Orleans e Bragança";
const FIXTURE = resolve(ROOT, "..", "site", "tests", "fixtures", "out");

export default function setup() {
  const data = mkdtempSync(join(tmpdir(), "mandato-e2e-"));
  cpSync(FIXTURE, data, { recursive: true });
  const list = JSON.parse(readFileSync(join(data, "deputies.json"), "utf8"));
  for (const d of list) if (d.id === 101) d.name = LONGEST;
  writeFileSync(join(data, "deputies.json"), JSON.stringify(list));
  const deputy = JSON.parse(readFileSync(join(data, "deputies", "101.json"), "utf8"));
  deputy.name = LONGEST;
  writeFileSync(join(data, "deputies", "101.json"), JSON.stringify(deputy));
  const photos = join(data, "photos");
  mkdirSync(photos);
  copyFileSync(join(ROOT, "tests", "fixtures", "photo-354x472.svg"), join(photos, "101.svg"));
  execFileSync("node", ["scripts/build-tokens.mjs"], { cwd: ROOT, stdio: "inherit" });
  execFileSync("node", ["scripts/prototype.mjs", "--out", OUT], {
    cwd: ROOT,
    stdio: "inherit",
    env: { ...process.env, MANDATO_DATA: data, MANDATO_PHOTO_CACHE: photos },
  });
  // the same deputy with no photo, served under /nophoto/
  execFileSync("node", ["scripts/prototype.mjs", "--out", join(OUT, "nophoto")], {
    cwd: ROOT,
    stdio: "inherit",
    env: { ...process.env, MANDATO_DATA: data, MANDATO_PHOTO_CACHE: join(data, "no-photos") },
  });
  // every share card format, beside the prototype so the pages share its tokens, styles and fonts
  execFileSync("node", ["scripts/cards.mjs", "e2e/cards.pages.mjs", join(OUT, "cards")], { cwd: ROOT, stdio: "inherit" });
  copyFileSync(join(ROOT, "tests", "fixtures", "photo-480x600.svg"), join(OUT, "cards", "photo-480x600.svg"));
}
