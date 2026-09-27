import { cpSync, mkdtempSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join, relative, resolve } from "node:path";
import { afterEach, describe, expect, it, vi } from "vitest";

import { dataDir, loadContract } from "../src/lib/data";

const SITE = resolve(__dirname, "..");
const REPO = resolve(SITE, "..");
const FIXTURE = join(__dirname, "fixtures", "out");
const SCHEMAS = join(REPO, "etl", "schema");

const temps: string[] = [];
function copyFixture(): string {
  const dir = mkdtempSync(join(tmpdir(), "mandato-site-"));
  temps.push(dir);
  cpSync(FIXTURE, dir, { recursive: true });
  return dir;
}
afterEach(() => {
  temps.splice(0).forEach((dir) => rmSync(dir, { recursive: true, force: true }));
  vi.unstubAllEnvs();
});

const readJson = (path: string) => JSON.parse(readFileSync(path, "utf8"));

describe("loadContract", () => {
  it("rejects schema_version 2", () => {
    const dir = copyFixture();
    const meta = readJson(join(dir, "meta.json"));
    writeFileSync(join(dir, "meta.json"), JSON.stringify({ ...meta, schema_version: 2 }));
    expect(() => loadContract(dir)).toThrowError(
      new Error("Unsupported data contract: meta.json has schema_version 2; this site reads 1"),
    );
  });

  it("rejects a directory without meta.json", () => {
    const dir = copyFixture();
    rmSync(join(dir, "meta.json"));
    expect(() => loadContract(dir)).toThrowError(new Error(`No meta.json in ${dir}`));
  });

  it("reads only schema fields", () => {
    const dir = copyFixture();
    const injected = {
      cpf: "52998224725",
      email: "ana@example.org",
      telefone: "(61) 3215-0000",
      endereco: "Anexo IV, gabinete 999",
      dataNascimento: "1970-05-01",
    };
    const inject = (file: string, edit: (doc: any) => void) => {
      const doc = readJson(join(dir, file));
      edit(doc);
      writeFileSync(join(dir, file), JSON.stringify(doc));
    };
    inject("deputies.json", (doc) => Object.assign(doc[0], injected));
    inject("deputies/101.json", (doc) => {
      Object.assign(doc, injected);
      Object.assign(doc.votes[0], injected);
      Object.assign(doc.authored[0], injected);
      Object.assign(doc.exercisePeriods[0], injected);
      Object.assign(doc.candidacy2026, injected);
      Object.assign(doc.participation, injected);
    });
    inject("roll-calls/100-1.json", (doc) => {
      Object.assign(doc, injected);
      Object.assign(doc.votes[0], injected);
      Object.assign(doc.proposition, injected);
      Object.assign(doc.tallies, injected);
    });

    const contract = loadContract(dir);
    const deputy = contract.deputy(101);
    const rollCall = contract.rollCall("100-1");

    const deputies = readJson(join(SCHEMAS, "deputies.schema.json")).$defs;
    const deputySchema = readJson(join(SCHEMAS, "deputy.schema.json"));
    const rollCallSchema = readJson(join(SCHEMAS, "roll-call.schema.json"));
    const keys = (schema: any) => Object.keys(schema.properties).sort();
    const sorted = (value: object) => Object.keys(value).sort();

    expect(sorted(contract.deputies[0])).toEqual(keys(deputies.deputy));
    expect(sorted(deputy)).toEqual(keys(deputySchema));
    expect(sorted(deputy.votes[0])).toEqual(keys(deputySchema.$defs.vote));
    expect(sorted(deputy.authored[0])).toEqual(keys(deputySchema.$defs.authored));
    expect(sorted(deputy.exercisePeriods[0])).toEqual(keys(deputySchema.$defs.period));
    expect(sorted(deputy.candidacy2026!)).toEqual(keys(deputySchema.$defs.candidacy));
    expect(sorted(deputy.participation)).toEqual(keys(deputySchema.$defs.indicator));
    expect(sorted(rollCall)).toEqual(keys(rollCallSchema));
    expect(sorted(rollCall.votes[0])).toEqual(keys(rollCallSchema.$defs.vote));
    expect(sorted(rollCall.proposition!)).toEqual(keys(rollCallSchema.$defs.proposition));
    expect(sorted(rollCall.tallies)).toEqual(keys(rollCallSchema.$defs.tallies));

    const serialised = JSON.stringify([contract.meta, contract.deputies, contract.rollCalls, deputy, rollCall]);
    for (const value of Object.values(injected)) expect(serialised).not.toContain(value);
  });
});

describe("configuration", () => {
  it("data directory", () => {
    vi.stubEnv("MANDATO_DATA_DIR", "");
    delete process.env.MANDATO_DATA_DIR;
    expect(dataDir()).toBe(join(REPO, "data", "out"));
    vi.stubEnv("MANDATO_DATA_DIR", "tests/fixtures/out");
    expect(dataDir()).toBe(FIXTURE);
  });

  it("is the only reader of the contract", () => {
    const files: string[] = [];
    const walk = (dir: string) => {
      for (const name of readdirSync(dir)) {
        const path = join(dir, name);
        if (statSync(path).isDirectory()) walk(path);
        else files.push(path);
      }
    };
    walk(join(SITE, "src"));
    const offenders = files
      .filter((file) => relative(join(SITE, "src"), file) !== join("lib", "data.ts"))
      .filter((file) => {
        const text = readFileSync(file, "utf8");
        return /MANDATO_DATA_DIR|data\/out|import[^;]*\.json["']/.test(text);
      })
      .map((file) => relative(SITE, file));
    expect(files.length).toBeGreaterThan(5);
    expect(offenders).toEqual([]);
  });

  it("site origin defaults to the placeholder domain", async () => {
    vi.stubEnv("SITE_URL", "");
    delete process.env.SITE_URL;
    vi.resetModules();
    const config = (await import("../astro.config.mjs")).default;
    expect(config.site).toBe("https://mandatoaberto.org");
  });
});
