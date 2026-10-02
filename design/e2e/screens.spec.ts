import { readFileSync } from "node:fs";
import { join } from "node:path";
import { expect, test, type Page } from "@playwright/test";

import { LONGEST, ROOT } from "./setup";

const DIRECTIONS = ["plenario"] as const; // AD-015
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
      const [n, m] = await Promise.all(
        [".ma-ndem__n", ".ma-ndem__m"].map((sel) => value.locator(`> ${sel}`).evaluate((el) => parseFloat(getComputedStyle(el).fontSize))),
      );
      expect(n).toBeGreaterThanOrEqual(2 * m);
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
      for (const s of SCREENS) {
        await open(page, d, s);
        const [body, probe] = await page.evaluate((value) => {
          const el = document.createElement("div");
          el.style.background = value;
          document.body.append(el);
          return [getComputedStyle(document.body).backgroundColor, getComputedStyle(el).backgroundColor];
        }, expected[theme]);
        expect(body, `${d} ${s} ${theme}`).toBe(probe);
      }
      await context.close();
    }
  }
});

test("initials frame matches the photo frame", async ({ page }) => {
  for (const d of DIRECTIONS)
    for (const [s, width] of [["profile", 1280], ["profile", 360], ["card", 1280]] as const) {
      await page.setViewportSize({ width, height: 900 });
      await open(page, d, s);
      const withPhoto = (await page.locator(".ma-photo__mat").boundingBox())!;
      await page.goto(`/nophoto/${d}/${s}.html`);
      await expect(page.locator(".ma-photo__img")).toHaveCount(0);
      const initials = page.locator(".ma-photo__initials");
      await expect(initials).toHaveText("LB");
      const without = (await page.locator(".ma-photo__mat").boundingBox())!;
      expect(Math.abs(without.width - withPhoto.width), `${d} ${s} ${width} width`).toBeLessThanOrEqual(1);
      expect(Math.abs(without.height - withPhoto.height), `${d} ${s} ${width} height`).toBeLessThanOrEqual(1);
      const box = (await initials.boundingBox())!;
      expect(Math.abs(box.width / box.height - 3 / 4)).toBeLessThan(0.01);
    }
});

test("profile arrangement", async ({ page }) => {
  for (const d of DIRECTIONS) {
    await page.setViewportSize({ width: 1280, height: 900 });
    await open(page, d, "profile");
    const box = async (sel: string) => (await page.locator(sel).first().boundingBox())!;
    const [photo, name, hero, lede, indicators, score] = await Promise.all(
      [".ma-hero .ma-photo", ".ma-hero__name", ".ma-hero", ".ma-lede", ".ma-indicators", ".ma-score"].map(box),
    );
    expect(photo.x + photo.width, `${d} photo left of name`).toBeLessThanOrEqual(name.x);
    const tops = [hero, lede, indicators, score].map((b) => b.y);
    expect(tops, `${d} region order`).toEqual([...tops].sort((a, b) => a - b));
    expect(hero.y + hero.height).toBeLessThanOrEqual(lede.y);
    await page.setViewportSize({ width: 360, height: 800 });
    await open(page, d, "profile");
    const [photo2, name2] = await Promise.all([".ma-hero .ma-photo", ".ma-hero__name"].map(box));
    expect(photo2.y + photo2.height, `${d} photo above name at 360`).toBeLessThanOrEqual(name2.y);
  }
});

test("photo mat stays light in dark", async ({ browser }) => {
  for (const d of DIRECTIONS) {
    const raised = JSON.parse(readFileSync(join(ROOT, "tokens", `${d}.json`), "utf8")).color.raised.$value;
    const context = await browser.newContext({ colorScheme: "dark" });
    const page = await context.newPage();
    for (const s of ["profile", "card"]) {
      await open(page, d, s);
      const [mat, probe] = await page.evaluate((value) => {
        const el = document.createElement("div");
        el.style.background = value;
        document.body.append(el);
        return [getComputedStyle(document.querySelector(".ma-photo__mat")!).backgroundColor, getComputedStyle(el).backgroundColor];
      }, raised);
      expect(mat, `${d} ${s}`).toBe(probe);
    }
    await context.close();
  }
});

test("every digit is tabular", async ({ page }) => {
  for (const d of DIRECTIONS)
    for (const s of SCREENS) {
      await open(page, d, s);
      const plain = await page.evaluate(() =>
        [...document.body.querySelectorAll("*")]
          .filter((el) => [...el.childNodes].some((n) => n.nodeType === 3 && /\d/.test(n.textContent ?? "")))
          .filter((el) => !getComputedStyle(el).fontVariantNumeric.includes("tabular-nums"))
          .map((el) => el.outerHTML.slice(0, 80)),
      );
      const counted = await page.evaluate(
        () => [...document.body.querySelectorAll("*")].filter((el) => [...el.childNodes].some((n) => n.nodeType === 3 && /\d/.test(n.textContent ?? ""))).length,
      );
      expect(counted, `${d} ${s} has numbers`).toBeGreaterThan(0);
      expect(plain, `${d} ${s}`).toEqual([]);
    }
});

test("roll-call arrangement", async ({ page }) => {
  for (const d of DIRECTIONS) {
    await open(page, d, "roll-call");
    const top = async (sel: string) => (await page.locator(sel).first().boundingBox())!.y;
    // the official summary (ementa) exists only when the roll call has a proposition; every other region is always there
    const always = [".ma-rollcall__head .ma-eyebrow", ".ma-rollcall__head h1", ".ma-ai", ".ma-result", ".ma-utilities", ".ma-groups"];
    for (const sel of always) await expect(page.locator(sel).first(), `${d} ${sel}`).toBeVisible();
    const order = (await page.locator(".ma-quote").count()) ? [...always.slice(0, 2), ".ma-quote", ...always.slice(2)] : always;
    const tops = await Promise.all(order.map(top));
    expect(tops, d).toEqual([...tops].sort((a, b) => a - b));
    expect(new Set(tops).size).toBe(order.length);
  }
});

test("card composition", async ({ page }) => {
  for (const d of DIRECTIONS) {
    await open(page, d, "card");
    const box = async (sel: string) => (await page.locator(`.ma-card ${sel}`).first().boundingBox())!;
    const [photo, body] = await Promise.all([box(".ma-photo"), box(".ma-card__body")]);
    expect(photo.x + photo.width, `${d} photo left of body`).toBeLessThanOrEqual(body.x);
    const order = [".ma-eyebrow", "h1", ".ma-card__body > p.ma-muted", ".ma-card__figures", ".ma-card__score-label", ".ma-score", ".ma-card__foot"];
    const tops = await Promise.all(order.map(async (sel) => (await box(sel)).y));
    expect(tops, d).toEqual([...tops].sort((a, b) => a - b));
    await expect(page.locator(".ma-card__figure")).toHaveCount(3);
    await expect(page.locator(".ma-card .ma-card__body > p.ma-muted")).toContainText(/^[A-Z]+ · [A-Z]{2}/);
    await expect(page.locator(".ma-card__foot")).toContainText(/Fonte: Câmara dos Deputados, dados de \d{2}\/\d{2}\/\d{4}/);
  }
});
