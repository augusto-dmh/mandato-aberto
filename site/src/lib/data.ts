/**
 * The only module that reads the ETL contract (`etl/schema/*.json`, AD-002).
 *
 * Every record is rebuilt field by field from the schema, so a column the ETL might add by
 * mistake - or any personal field - never reaches a page.
 */
import { existsSync, readFileSync } from "node:fs";
import { join, resolve } from "node:path";

export const SCHEMA_VERSION = 1;

export interface Indicator {
  count: number;
  total: number;
}

export interface Candidacy {
  office: string;
  party: string;
  ballotNumber: string;
  situation: string;
}

export interface DeputySummary {
  id: number;
  name: string;
  party: string;
  uf: string;
  photoUrl: string;
  inExercise: boolean;
  sourceUrl: string;
  participation: Indicator;
  governmentAlignment: Indicator;
  partyAlignment: Indicator;
  authoredCount: number;
  firstSignerCount: number;
  requirementsCount: number;
  candidacy2026: Candidacy | null;
}

export interface Period {
  start: string;
  end: string;
}

export interface Authored {
  id: number;
  type: string;
  number: number;
  year: number;
  summary: string;
  presentedAt: string;
  status: string | null;
  firstSigner: boolean;
  sourceUrl: string;
}

export interface DeputyVote {
  rollCallId: string;
  vote: string;
  party: string;
  partyMajority: string | null;
}

export interface Deputy extends DeputySummary {
  exercisePeriods: Period[];
  authored: Authored[];
  votes: DeputyVote[];
}

export interface Proposition {
  id: number;
  title: string;
  summary: string | null;
}

export interface RollCallSummary {
  id: string;
  date: string;
  organ: string;
  description: string;
  proposition: Proposition | null;
  approved: boolean | null;
  tallies: { yes: number; no: number; others: number };
  governmentOrientation: string | null;
  sourceUrl: string;
}

export interface RollCallVote {
  deputyId: number;
  vote: string;
  party: string;
}

export interface RollCall extends RollCallSummary {
  votes: RollCallVote[];
}

export interface Meta {
  schema_version: number;
  generatedAt: string;
  years: number[];
  counts: { deputies: number; rollCalls: number; propositions: number };
}

export interface Contract {
  meta: Meta;
  deputies: DeputySummary[];
  rollCalls: RollCallSummary[];
  deputy(id: number): Deputy;
  rollCall(id: string): RollCall;
}

/** `MANDATO_DATA_DIR`, resolved against the site directory; defaults to the repository's `data/out`. */
export function dataDir(): string {
  return resolve(process.cwd(), process.env.MANDATO_DATA_DIR || "../data/out");
}

const indicator = (i: any): Indicator => ({ count: i.count, total: i.total });

const candidacy = (c: any): Candidacy | null =>
  c === null ? null : { office: c.office, party: c.party, ballotNumber: c.ballotNumber, situation: c.situation };

const summary = (d: any): DeputySummary => ({
  id: d.id,
  name: d.name,
  party: d.party,
  uf: d.uf,
  photoUrl: d.photoUrl,
  inExercise: d.inExercise,
  sourceUrl: d.sourceUrl,
  participation: indicator(d.participation),
  governmentAlignment: indicator(d.governmentAlignment),
  partyAlignment: indicator(d.partyAlignment),
  authoredCount: d.authoredCount,
  firstSignerCount: d.firstSignerCount,
  requirementsCount: d.requirementsCount,
  candidacy2026: candidacy(d.candidacy2026),
});

const deputyDoc = (d: any): Deputy => ({
  ...summary(d),
  exercisePeriods: d.exercisePeriods.map((p: any) => ({ start: p.start, end: p.end })),
  authored: d.authored.map((a: any) => ({
    id: a.id,
    type: a.type,
    number: a.number,
    year: a.year,
    summary: a.summary,
    presentedAt: a.presentedAt,
    status: a.status,
    firstSigner: a.firstSigner,
    sourceUrl: a.sourceUrl,
  })),
  votes: d.votes.map((v: any) => ({
    rollCallId: v.rollCallId,
    vote: v.vote,
    party: v.party,
    partyMajority: v.partyMajority,
  })),
});

const rollCallSummary = (r: any): RollCallSummary => ({
  id: r.id,
  date: r.date,
  organ: r.organ,
  description: r.description,
  proposition: r.proposition === null ? null : { id: r.proposition.id, title: r.proposition.title, summary: r.proposition.summary },
  approved: r.approved,
  tallies: { yes: r.tallies.yes, no: r.tallies.no, others: r.tallies.others },
  governmentOrientation: r.governmentOrientation,
  sourceUrl: r.sourceUrl,
});

const rollCallDoc = (r: any): RollCall => ({
  ...rollCallSummary(r),
  votes: r.votes.map((v: any) => ({ deputyId: v.deputyId, vote: v.vote, party: v.party })),
});

const readJson = (path: string) => JSON.parse(readFileSync(path, "utf8"));

/** Reads `meta.json` and the two indexes; the per-record files are read on demand. */
export function loadContract(dir: string): Contract {
  const metaPath = join(dir, "meta.json");
  if (!existsSync(metaPath)) throw new Error(`No meta.json in ${dir}`);
  const raw = readJson(metaPath);
  if (raw.schema_version !== SCHEMA_VERSION) {
    throw new Error(
      `Unsupported data contract: meta.json has schema_version ${raw.schema_version}; this site reads ${SCHEMA_VERSION}`,
    );
  }
  const meta: Meta = {
    schema_version: raw.schema_version,
    generatedAt: raw.generatedAt,
    years: [...raw.years],
    counts: { deputies: raw.counts.deputies, rollCalls: raw.counts.rollCalls, propositions: raw.counts.propositions },
  };
  return {
    meta,
    deputies: readJson(join(dir, "deputies.json")).map(summary),
    rollCalls: readJson(join(dir, "roll-calls.json")).map(rollCallSummary),
    deputy: (id) => deputyDoc(readJson(join(dir, "deputies", `${id}.json`))),
    rollCall: (id) => rollCallDoc(readJson(join(dir, "roll-calls", `${id}.json`))),
  };
}

let current: { dir: string; contract: Contract } | undefined;

/** The contract at `dataDir()`, read once per build. */
export function contract(): Contract {
  const dir = dataDir();
  if (current?.dir !== dir) current = { dir, contract: loadContract(dir) };
  return current.contract;
}
