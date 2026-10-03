import { readFileSync, readdirSync } from "node:fs";
import { join, resolve } from "node:path";
import { describe, expect, it } from "vitest";

const ROOT = resolve(__dirname, "..");
const COMPONENTS = ["NDeM", "SourceNote", "VoteMark", "MandateScore", "OfficialPhoto", "TallyBar", "AiSummaryFrame"];

describe("package", () => {
  it("readme documents every component", () => {
    const readme = readFileSync(join(ROOT, "README.md"), "utf8");
    const sections = readme.split(/^### /m).slice(1);
    const byName = new Map(sections.map((s) => [s.split("\n")[0].trim(), s]));
    for (const name of COMPONENTS) {
      const section = byName.get(name);
      expect(section, name).toBeDefined();
      expect(section).toMatch(/\*\*Inputs:\*\*/);
      expect(section).toMatch(/\*\*(Empty|Missing-data) state:\*\*/);
      expect(section).toMatch(/\*\*Principle:\*\*/);
    }
  });

  it("package exports tokens and components", () => {
    const pkg = JSON.parse(readFileSync(join(ROOT, "package.json"), "utf8"));
    expect(pkg.name).toBe("mandato-design");
    expect(pkg.private).toBe(true);
    expect(pkg.exports["./tokens.css"]).toBe("./dist/tokens.css");
    expect(pkg.exports["./components/*"]).toBe("./components/*");
    const files = readdirSync(join(ROOT, "components")).filter((f) => f.endsWith(".vue")).map((f) => f.replace(".vue", ""));
    expect(files.sort()).toEqual([...COMPONENTS].sort());
  });
});
