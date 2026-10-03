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

/** Accessible sentence for one vote: "Nome, PARTIDO-UF, votou Não". */
export function voteSentence(vote, secret = false, who = "") {
  const { kind, label } = voteCase(vote, secret);
  const prefix = who ? `${who}, ` : "";
  return ["yes", "no", "abstention", "obstruction"].includes(kind) ? `${prefix}votou ${label}` : `${prefix}${label}`;
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
      return [{ tag: "circle", attrs: { cx: 4, cy: 12, r: 2, fill: ink } }];
    case "other":
      return [{ tag: "circle", attrs: { cx: 4, cy: 12, r: 2, fill: "none", stroke: ink, "stroke-width": 1 } }];
    default:
      return [];
  }
}

/** The legend order used wherever marks are explained. */
export const LEGEND = ["Sim", "Não", "Abstenção", "Obstrução", "Artigo 17", ""];
