// The product's vote encoding (plan Landing doors 5 and 6): position and shape carry the option,
// colour never does. Every shape is drawn in currentColor on a 24-unit-high box whose baseline is y = 12.

export const BASELINE = 12;

const KNOWN = {
  Sim: { kind: "yes", label: "Sim" },
  "Não": { kind: "no", label: "Não" },
  "Abstenção": { kind: "abstention", label: "Abstenção" },
  "Obstrução": { kind: "obstruction", label: "Obstrução" },
  "Artigo 17": { kind: "article-17", label: "Art. 17 (presidente da sessão)" },
};

/** Classifies a contract vote value; `secret` is the roll call's secret-ballot flag. */
export function voteCase(vote, secret = false) {
  if (vote === "") return secret ? { kind: "secret", label: "Votação secreta" } : { kind: "not-recorded", label: "Registro sem voto" };
  return KNOWN[vote] ?? { kind: "other", label: vote };
}

const COMMON = {
  yes: { kind: "yes", label: "Sim" },
  no: { kind: "no", label: "Não" },
  abstention: { kind: "abstention", label: "Abstenção" },
  obstruction: { kind: "obstruction", label: "Obstrução" },
};

const BY_HOUSE = {
  camara: {
    presiding: "Art. 17 (presidente da sessão)",
    secret: "Votação secreta",
    notVoting: "Registro sem voto",
  },
  senado: {
    presiding: "Presidente da sessão (art. 51 RISF)",
    secret: "Votou (votação secreta)",
  },
};

/** The Senate's own description of each code it records instead of a vote (plan door 5). */
export const SENATE_NOT_VOTING = {
  "P-NRV": "Presente, não registrou voto",
  AP: "Atividade parlamentar",
  MIS: "Missão da Casa no País ou no exterior",
  NCom: "Não compareceu",
  NA: "Dispositivo não citado",
  "Licença": "Licença",
};

/** The Senate's description of an official non-vote code, or the code itself when it has none. */
export const senateDescription = (official) => SENATE_NOT_VOTING[official] ?? official;

/**
 * Classifies a contract v3 vote (plan doors 5 and 6): the mark follows `position`, the label the house.
 * A Senate `notVoting` record without its official value (member pages never receive it) reads "Não registrou voto".
 */
export function positionCase({ house, position, official = null }) {
  if (COMMON[position]) return COMMON[position];
  const kind = position === "notVoting" ? "not-voting" : position;
  if (house === "senado" && position === "notVoting") {
    return { kind, label: official == null ? "Não registrou voto" : `Sem voto: ${senateDescription(official)}` };
  }
  return { kind, label: BY_HOUSE[house]?.[position] ?? official ?? position };
}

/** Accessible sentence for a classified vote: "Nome, PARTIDO-UF, votou Não". */
export function caseSentence({ kind, label }, who = "") {
  const prefix = who ? `${who}, ` : "";
  return ["yes", "no", "abstention", "obstruction"].includes(kind) ? `${prefix}votou ${label}` : `${prefix}${label}`;
}

/** Accessible sentence for one vote: "Nome, PARTIDO-UF, votou Não". */
export function voteSentence(vote, secret = false, who = "") {
  return caseSentence(voteCase(vote, secret), who);
}

/** Shapes for one mark in an 8 x 24 box: [{tag, attrs}]. A gap is an empty list. */
export function markShapes(kind) {
  const ink = "currentColor";
  switch (kind) {
    case "yes":
      return [{ tag: "rect", attrs: { x: 1, y: 2, width: 6, height: 10, fill: ink } }];
    case "no":
      return [{ tag: "rect", attrs: { x: 1, y: 12, width: 6, height: 10, fill: ink } }];
    case "abstention":
      return [{ tag: "rect", attrs: { x: 1.5, y: 9.5, width: 5, height: 5, fill: "none", stroke: ink, "stroke-width": 1 } }];
    case "obstruction":
      return [
        { tag: "rect", attrs: { x: 1.5, y: 9.5, width: 5, height: 5, fill: "none", stroke: ink, "stroke-width": 1 } },
        { tag: "path", attrs: { d: "M1.5 12.5 L4.5 9.5 M3.5 14.5 L6.5 11.5", fill: "none", stroke: ink, "stroke-width": 1 } },
      ];
    case "article-17":
    case "presiding":
      return [{ tag: "circle", attrs: { cx: 4, cy: 12, r: 2, fill: ink } }];
    case "other":
      return [{ tag: "circle", attrs: { cx: 4, cy: 12, r: 2, fill: "none", stroke: ink, "stroke-width": 1 } }];
    default:
      return [];
  }
}

/** The legend order used wherever marks are explained. */
export const LEGEND = ["Sim", "Não", "Abstenção", "Obstrução", "Artigo 17", ""];

/** The same legend by position, for a house's member page. */
export const POSITION_LEGEND = ["yes", "no", "abstention", "obstruction", "presiding", "notVoting"];
