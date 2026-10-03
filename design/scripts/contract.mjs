// Reads the ETL contract (etl/schema/*.json, schema_version 2) and shapes the prototype's inputs.
import { existsSync, readFileSync } from "node:fs";
import { join } from "node:path";

import { voteCase } from "../components/vote.js";

export const SCHEMA_VERSION = 2;
export const SITE = "https://augusto-dmh.github.io/mandato-aberto";
const METHOD = `${SITE}/metodologia/`;

export class SchemaError extends Error {}

const read = (dir, ...path) => JSON.parse(readFileSync(join(dir, ...path), "utf8"));

/** Fails before anything else when the contract is not the version this package was built for. */
export function checkSchema(dir) {
  const meta = read(dir, "meta.json");
  if (meta.schema_version !== SCHEMA_VERSION)
    throw new SchemaError(`schema_version ${meta.schema_version} is not supported; expected ${SCHEMA_VERSION}`);
  return meta;
}

const sortKey = (name) => name.normalize("NFD").replace(/\p{Diacritic}/gu, "").toLocaleLowerCase("pt-BR");
const byName = (a, b) => sortKey(a.name).localeCompare(sortKey(b.name));

/** Default deputy: in exercise, longest name, lowest id on a tie (stresses every layout). */
export function pickDeputy(deputies, id) {
  if (id) return deputies.find((d) => String(d.id) === String(id)) ?? null;
  return [...deputies]
    .filter((d) => d.inExercise)
    .sort((a, b) => b.name.length - a.name.length || a.id - b.id)[0];
}

/** Default roll call: plenary, most votes counted, earliest id on a tie. */
export function pickRollCall(rollCalls, id) {
  if (id) return rollCalls.find((r) => r.id === id) ?? null;
  const total = (r) => r.tallies.yes + r.tallies.no + r.tallies.others;
  return [...rollCalls].filter((r) => r.organ === "PLEN").sort((a, b) => total(b) - total(a) || a.id.localeCompare(b.id))[0];
}

const ORDER = ["Sim", "Não", "Abstenção", "Obstrução", "Artigo 17", ""];

export function loadInputs(dir, { deputy: deputyId, rollCall: rollCallId, photoDir } = {}) {
  const meta = checkSchema(dir);
  const deputies = read(dir, "deputies.json");
  const index = read(dir, "roll-calls.json");
  const chosen = pickDeputy(deputies, deputyId);
  if (!chosen) throw new Error(`deputy ${deputyId} is not in ${dir}`);
  const chosenRollCall = pickRollCall(index, rollCallId);
  if (!chosenRollCall) throw new Error(`roll call ${rollCallId} is not in ${dir}`);

  const deputy = read(dir, "deputies", `${chosen.id}.json`);
  // no exercise period recorded -> no start date shown; never stand in the collection date for it
  const since = [...deputy.exercisePeriods].map((p) => p.start).sort()[0] ?? null;
  const byId = new Map(index.map((r) => [r.id, r]));
  const votes = deputy.votes
    .filter((v) => byId.has(v.rollCallId))
    .map((v) => {
      const r = byId.get(v.rollCallId);
      return { rollCallId: v.rollCallId, date: r.date, title: r.proposition?.title ?? r.description, vote: v.vote, secret: r.secret };
    });
  const note = (index, anchor) => ({ index, sourceUrl: deputy.sourceUrl, methodUrl: `${METHOD}#${anchor}` });
  const indicators = [
    { ...deputy.participation, label: "Participação em votações nominais do plenário", note: note(1, "participacao") },
    { ...deputy.governmentAlignment, label: "Votos iguais à orientação do governo", note: note(2, "alinhamento-governo") },
    { ...deputy.partyAlignment, label: "Votos iguais à maioria do próprio partido", note: note(3, "alinhamento-partido") },
  ];
  const figures = [
    { ...deputy.participation, label: "votações nominais do plenário com voto registrado" },
    { ...deputy.governmentAlignment, label: "votos iguais à orientação do governo" },
    { ...deputy.partyAlignment, label: "votos iguais à maioria do próprio partido" },
  ];

  const rollCall = read(dir, "roll-calls", `${chosenRollCall.id}.json`);
  const people = new Map(deputies.map((d) => [d.id, d]));
  const entries = rollCall.votes.map((v) => ({
    deputyId: v.deputyId,
    name: people.get(v.deputyId)?.name ?? `Deputado ${v.deputyId}`,
    uf: people.get(v.deputyId)?.uf ?? "",
    party: v.party,
    vote: v.vote,
  }));
  const values = [...new Set(entries.map((e) => e.vote))].sort((a, b) => {
    const [ia, ib] = [ORDER.indexOf(a), ORDER.indexOf(b)];
    return (ia === -1 ? 99 : ia) - (ib === -1 ? 99 : ib) || a.localeCompare(b);
  });
  const groups = values.map((value) => ({
    value,
    label: voteCase(value, rollCall.secret).label,
    entries: entries.filter((e) => e.vote === value).sort(byName),
  }));
  const resultLabel = rollCall.approved === true ? "Aprovada" : rollCall.approved === false ? "Rejeitada" : "Resultado não informado";
  const propositionUrl = rollCall.proposition ? `https://www.camara.leg.br/propostas-legislativas/${rollCall.proposition.id}` : rollCall.sourceUrl;

  const photoFile = photoDir ? ["jpg", "png", "svg"].map((ext) => join(photoDir, `${deputy.id}.${ext}`)).find(existsSync) : null;

  return {
    generatedAt: meta.generatedAt,
    photoFile: photoFile ?? null,
    profile: {
      deputy: { ...deputy, since },
      indicators,
      votes,
      methodBase: METHOD,
    },
    card: { deputy: { ...deputy, since }, figures, votes },
    rollCall: {
      rollCall: {
        ...rollCall,
        title: rollCall.proposition?.title ?? rollCall.description,
        summary: rollCall.proposition?.summary ?? "",
        organLabel: rollCall.organ === "PLEN" ? "Plenário" : rollCall.organ,
        resultLabel,
      },
      groups,
      summary: {
        text: "Exemplo de como um resumo aparece nesta página. O texto definitivo será gerado a partir da ementa e do inteiro teor da proposição e só será publicado depois de revisado por uma pessoa.",
        reviewedAt: meta.generatedAt.slice(0, 10),
        officialUrl: propositionUrl,
        reportUrl: `${SITE}/reportar-erro/`,
        sample: true,
      },
    },
  };
}
