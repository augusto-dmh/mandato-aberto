import { readFileSync } from "node:fs";
import { join } from "node:path";
import { expect, test, type Page } from "@playwright/test";

import { LONGEST, ROOT } from "./setup";

const DIRECTIONS = ["diario", "plenario"] as const;
const SCREENS = ["profile", "roll-call", "card"] as const;
const THEMES = ["light", "dark"] as const;

async function open(page: Page, direction: string, screen: string) {
  await page.goto(`/${direction}/${screen}.html`);
  await page.evaluate(() => document.fonts.ready);
}

test("no third-party request", async ({ page, baseURL }) => {
  const hosts = new Set<string>();
  page.on("request", (r) => hosts.add(new URL(r.url()).host));
  for (const d of DIRECTIONS) for (const s of SCREENS) await open(page, d, s);
  expect([...hosts]).toEqual([new URL(baseURL!).host]);
});

test("n de m shares a baseline", async ({ page }) => {
  for (const d of DIRECTIONS) {
    await open(page, d, "profile");
    const values = page.locator(".ma-ndem__value:has(.ma-ndem__n)");
    expect(await values.count()).toBeGreaterThan(0);
    for (const value of await values.all()) {
      expect(await value.evaluate((el) => getComputedStyle(el).alignItems)).toBe("baseline");
      await expect(value.locator("> .ma-ndem__n")).toHaveCount(1);
      await expect(value.locator("> .ma-ndem__m")).toHaveCount(1);
    }
  }
});

test("official photo is untouched", async ({ page }) => {
  for (const d of DIRECTIONS)
    for (const s of ["profile", "card"]) {
      await open(page, d, s);
      const img = page.locator(".ma-photo__img");
      await expect(img).toHaveCount(1);
      const box = (await img.boundingBox())!;
      expect(box.width).toBeLessThanOrEqual(354);
      expect(box.height).toBeLessThanOrEqual(472);
      expect(Math.abs(box.width / box.height - 3 / 4)).toBeLessThan(0.01);
      const style = await img.evaluate((el) => {
        const c = getComputedStyle(el);
        return { filter: c.filter, blend: c.mixBlendMode, transform: c.transform, fit: c.objectFit };
      });
      expect(style.filter).toBe("none");
      expect(style.blend).toBe("normal");
      expect(style.transform).toBe("none");
      expect(style.fit).not.toBe("cover");
      await expect(page.locator(".ma-photo__credit")).toHaveText("Foto: Câmara dos Deputados");
    }
});

test("reduced motion", async ({ browser }) => {
  const context = await browser.newContext({ reducedMotion: "reduce" });
  const page = await context.newPage();
  for (const d of DIRECTIONS)
    for (const s of SCREENS) {
      await open(page, d, s);
      const moving = await page.evaluate(() =>
        [...document.querySelectorAll("*")]
          .map((el) => {
            const c = getComputedStyle(el);
            return `${c.transitionDuration}|${c.animationDuration}`;
          })
          .filter((v) => v.split(/[|,]\s*/).some((t) => t !== "0s")),
      );
      expect(moving, `${d} ${s}`).toEqual([]);
    }
  await context.close();
});

test("no horizontal scroll at 360", async ({ browser }) => {
  for (const theme of THEMES) {
    const context = await browser.newContext({ viewport: { width: 360, height: 800 }, colorScheme: theme });
    const page = await context.newPage();
    for (const d of DIRECTIONS)
      for (const s of ["profile", "roll-call"]) {
        await open(page, d, s);
        const width = await page.evaluate(() => document.documentElement.scrollWidth);
        expect(width, `${s} ${d} ${theme}`).toBeLessThanOrEqual(360);
      }
    await context.close();
  }
});

test("card fits the longest name", async ({ page }) => {
  for (const d of DIRECTIONS) {
    await open(page, d, "card");
    await expect(page.locator(".ma-card h1")).toHaveText(LONGEST);
    const card = page.locator(".ma-card");
    const box = (await card.boundingBox())!;
    expect([box.width, box.height]).toEqual([1200, 630]);
    const overflowing = await card.evaluate((root) => {
      const outer = root.getBoundingClientRect();
      return [...root.querySelectorAll("*")]
        .filter((el) => {
          const r = el.getBoundingClientRect();
          if (r.width === 0 && r.height === 0) return false;
          const outside = r.left < outer.left - 0.5 || r.right > outer.right + 0.5 || r.top < outer.top - 0.5 || r.bottom > outer.bottom + 0.5;
          const clipped = el.clientWidth > 0 && (el.scrollWidth > el.clientWidth + 1 || el.scrollHeight > el.clientHeight + 1);
          return outside || clipped;
        })
        .map((el) => el.outerHTML.slice(0, 80));
    });
    expect(overflowing, d).toEqual([]);
  }
});

test("theme follows the system", async ({ browser }) => {
  for (const d of DIRECTIONS) {
    const paper = JSON.parse(readFileSync(join(ROOT, "tokens", `${d}.json`), "utf8")).color.paper;
    const expected = { light: paper.$value, dark: paper.$extensions.mandato.dark };
    for (const theme of THEMES) {
      const context = await browser.newContext({ colorScheme: theme });
      const page = await context.newPage();
      await open(page, d, "profile");
      const [body, probe] = await page.evaluate((value) => {
        const el = document.createElement("div");
        el.style.background = value;
        document.body.append(el);
        return [getComputedStyle(document.body).backgroundColor, getComputedStyle(el).backgroundColor];
      }, expected[theme]);
      expect(body, `${d} ${theme}`).toBe(probe);
      await context.close();
    }
  }
});
