// share-cards S4 in a browser: every format's arrangement, fit and photo (C37, C43, C45, C46, C78).
import { expect, test, type Page } from "@playwright/test";

// @ts-expect-error plain ES module shared with scripts/cards.mjs
import { FORMATS, LONGEST } from "./cards.pages.mjs";

const NAMES = Object.keys(FORMATS) as ("og" | "feed" | "story")[];

async function open(page: Page, name: string) {
  await page.setViewportSize({ width: 1400, height: 2100 });
  await page.goto(`/cards/${name}.html`);
  await page.evaluate(() => document.fonts.ready);
}

async function overflowing(page: Page) {
  return page.locator(".ma-card").evaluate((root) => {
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
}

test("card regions in order", async ({ page }) => {
  for (const format of NAMES) {
    await open(page, `member-${format}`);
    const box = async (sel: string) => (await page.locator(`.ma-card ${sel}`).first().boundingBox())!;
    const text = [".ma-card__eyebrow", "h1", ".ma-card__body > p.ma-muted", ".ma-card__basis", ".ma-card__figures", ".ma-card__score-label", ".ma-score", ".ma-card__foot"];
    const tops = await Promise.all(text.map(async (sel) => (await box(sel)).y));
    expect(tops, format).toEqual([...tops].sort((a, b) => a - b));
    expect(new Set(tops).size, format).toBe(text.length);
    const [eyebrow, photo, name, body] = await Promise.all([box(".ma-card__eyebrow"), box(".ma-photo"), box("h1"), box(".ma-card__body")]);
    if (format === "og") {
      expect(photo.x + photo.width, "photo left of the text").toBeLessThanOrEqual(body.x);
      expect(eyebrow.x, "eyebrow above the text column").toBeGreaterThanOrEqual(body.x);
    } else {
      expect(eyebrow.y + eyebrow.height, `${format} eyebrow above photo`).toBeLessThanOrEqual(photo.y);
      expect(photo.y + photo.height, `${format} photo above name`).toBeLessThanOrEqual(name.y);
    }
  }
});

test("feed and story stack the figures and size the score by its votes", async ({ page }) => {
  for (const format of ["feed", "story"]) {
    // a mandate of 240 votes fills the column; one of 2 draws 8 px per vote, from the left
    for (const [name, votes] of [[`member-${format}`, 240], [`short-${format}`, 2]] as const) {
      await open(page, name);
      const figures = await page.locator(".ma-card__figure").evaluateAll((els) => els.map((el) => el.getBoundingClientRect().toJSON()));
      expect(figures, name).toHaveLength(3);
      for (let i = 1; i < figures.length; i++) {
        expect(figures[i].top, `${name} figure ${i + 1} below figure ${i}`).toBeGreaterThanOrEqual(figures[i - 1].bottom - 0.5);
        expect(Math.abs(figures[i].left - figures[0].left), `${name} figures share a left edge`).toBeLessThan(0.5);
      }
      const { score, body, zoom } = await page.locator(".ma-card__body").evaluate((el) => ({
        score: el.querySelector(".ma-card__score")!.getBoundingClientRect().toJSON(),
        body: el.getBoundingClientRect().toJSON(),
        zoom: Number(getComputedStyle(el).zoom),
      }));
      expect(Math.abs(score.left - body.left), `${name} score left-aligned`).toBeLessThan(0.5);
      expect(Math.abs(score.width - Math.min(body.width, votes * 8 * zoom)), `${name} score width`).toBeLessThan(0.5);
    }
  }
});

test("card name is set as stored", async ({ page }) => {
  for (const format of NAMES) {
    await open(page, `member-${format}`);
    const h1 = page.locator(".ma-card h1");
    expect(await h1.evaluate((el) => el.textContent)).toBe(LONGEST);
    expect(await h1.evaluate((el) => getComputedStyle(el).textTransform), format).toBe("none");
  }
});

test("every card format fits its box", async ({ page }) => {
  for (const format of NAMES) {
    const [width, height] = FORMATS[format];
    for (const name of [`member48-${format}`, `member-${format}`, `nophoto-${format}`, `rollcall-${format}`]) {
      await open(page, name);
      const box = (await page.locator(".ma-card").boundingBox())!;
      expect([box.width, box.height], name).toEqual([width, height]);
      expect(await overflowing(page), name).toEqual([]);
    }
    await open(page, `member48-${format}`);
    await expect(page.locator(".ma-card__figure").first()).toContainText("1.234 de 2.345");
  }
});

test("card photo is whole and untouched", async ({ page }) => {
  for (const format of NAMES) {
    await open(page, `member-${format}`);
    const img = page.locator(".ma-card .ma-photo__img");
    await expect(img).toHaveCount(1);
    const box = (await img.boundingBox())!;
    const style = await img.evaluate((el: HTMLImageElement) => {
      const c = getComputedStyle(el);
      return { fit: c.objectFit, filter: c.filter, blend: c.mixBlendMode, transform: c.transform, natural: [el.naturalWidth, el.naturalHeight] };
    });
    expect(style.natural).toEqual([480, 600]);
    // the mat is 3:4; contain draws the whole 4:5 photo inside it
    expect(Math.abs(box.width / box.height - 3 / 4), format).toBeLessThan(0.01);
    expect(box.width).toBeLessThanOrEqual(354);
    expect(box.height).toBeLessThanOrEqual(472);
    expect(style.fit).toBe("contain");
    const scale = Math.min(box.width / 480, box.height / 600);
    expect(scale, `${format} at most native size`).toBeLessThanOrEqual(1);
    expect(Math.abs((480 * scale) / (600 * scale) - 4 / 5)).toBeLessThan(0.01);
    expect([style.filter, style.blend, style.transform], format).toEqual(["none", "normal", "none"]);
    await expect(page.locator(".ma-card .ma-photo__credit")).toHaveText("Foto: Câmara dos Deputados");
  }
});

// C83: a card is one template; no colour belongs to a person (research/design-anexos/a1 6.3, "cor própria por pessoa").
async function paint(page: Page, name: string) {
  await open(page, name);
  return page.locator(".ma-card").evaluate((root) => {
    const read = (el: Element) => {
      const c = getComputedStyle(el);
      return { color: c.color, background: c.backgroundColor, border: c.borderColor, fill: c.fill, stroke: c.stroke };
    };
    const strip = root.querySelector(".ma-score__strip");
    const ink = strip ? getComputedStyle(strip).color : null;
    const inMark = (el: Element) => el.closest(".ma-score__col") !== null;
    const tuples: [string, string][] = [...root.querySelectorAll("*"), root].map((el) => {
      const { color, background, border, fill } = read(el);
      return [`${el.tagName}.${el.getAttribute("class") ?? ""}`, JSON.stringify(inMark(el) ? { color, background, border } : { color, background, border, fill })];
    });
    // a mark draws with the strip's own colour or nothing, whichever shape its position has
    const marks = [...root.querySelectorAll(".ma-score__col *")].flatMap((el) => {
      const { fill, stroke } = read(el);
      return [fill, stroke].filter((v) => v !== "none" && v !== ink);
    });
    return { tuples, marks, hasStrip: strip !== null };
  });
}

test("no card draws a colour of its own per subject", async ({ page }) => {
  const GROUPS = { member: ["member", "member48", "short", "nophoto", "other"], rollcall: ["rollcall", "rollcall2"] };
  for (const format of NAMES) {
    for (const [group, names] of Object.entries(GROUPS)) {
      const seen = new Map<string, Map<string, string>>();
      for (const name of names) {
        const { tuples, marks, hasStrip } = await paint(page, `${name}-${format}`);
        expect(hasStrip, `${name}-${format} has a score strip`).toBe(group === "member");
        expect(marks, `${name}-${format} marks use the strip colour or none`).toEqual([]);
        for (const [key, tuple] of tuples) {
          if (!seen.has(key)) seen.set(key, new Map());
          seen.get(key)!.set(name, tuple);
        }
      }
      for (const [key, byPage] of seen) {
        expect(new Set(byPage.values()).size, `${format} ${group} ${key}: ${JSON.stringify([...byPage])}`).toBe(1);
      }
      // the comparison compares something: the shared regions are drawn by more than one card
      expect([...seen.values()].filter((byPage) => byPage.size === names.length).length, `${format} ${group} shared elements`).toBeGreaterThan(8);
    }
  }
});
