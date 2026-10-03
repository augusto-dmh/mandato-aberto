// The share cards the browser tests open (share-cards C37, C43, C45, C46): every format of each card.
export const LONGEST = "Luiz Philippe de Orleans e Bragança";
export const NAME_48 = "Maria Aparecida de Albuquerque Cavalcanti Pintos"; // 48 characters
export const HEADING_48 = "Votação nominal de 01/03/2023 · Requerimento 123".padEnd(48, "x").slice(0, 48);
export const FORMATS = { og: [1200, 630], feed: [1080, 1350], story: [1080, 1920] };

const LABELS = ["Participação em votações nominais do plenário", "Votos iguais à orientação do governo", "Votos iguais à maioria do próprio partido"];
const POSITIONS = ["yes", "no", "yes", "abstention", "yes", "obstruction", "no", "notVoting", "yes", "presiding"];
const votes = Array.from({ length: 240 }, (_, i) => ({
  rollCallId: `${1000 + i}-1`,
  date: `20${23 + Math.floor(i / 80)}-${String(1 + (Math.floor(i / 8) % 10)).padStart(2, "0")}-${String(1 + (i % 8) * 3).padStart(2, "0")}`,
  position: POSITIONS[i % POSITIONS.length],
  official: null,
}));
const common = { house: "camara", legislature: 57, votes, generatedAt: "2027-03-02T02:30:00Z", code: "20270301-K7Q29XPD", verifyHost: "mandato.test" };

const pages = {};
for (const format of Object.keys(FORMATS)) {
  pages[`member-${format}`] = {
    kind: "member",
    props: { ...common, format, member: { name: LONGEST, party: "PL", uf: "SP" }, figures: LABELS.map((label, i) => ({ count: 300 + i, total: 400, label })), photo: "photo-480x600.svg" },
  };
  pages[`member48-${format}`] = {
    kind: "member",
    props: { ...common, format, house: "senado", member: { name: NAME_48, party: "REPUBLICANOS", uf: "SP" }, figures: LABELS.map((label) => ({ count: 1234, total: 2345, label })), photo: "photo-480x600.svg" },
  };
  pages[`short-${format}`] = {
    kind: "member",
    props: { ...common, format, votes: votes.slice(0, 2), member: { name: LONGEST, party: "PL", uf: "SP" }, figures: LABELS.map((label) => ({ count: 1, total: 2, label })), photo: "photo-480x600.svg" },
  };
  pages[`nophoto-${format}`] = {
    kind: "member",
    props: { ...common, format, member: { name: LONGEST, party: "PL", uf: "SP" }, figures: LABELS.map((label) => ({ count: 0, total: 0, label })), photo: null },
  };
  // a second member and a second roll call, with nothing in common but the template (C83)
  pages[`other-${format}`] = {
    kind: "member",
    props: {
      ...common,
      format,
      house: "senado",
      legislature: 56,
      votes: votes.slice(0, 37).map((v, i) => ({ ...v, position: POSITIONS[(i * 3 + 1) % POSITIONS.length] })),
      member: { name: "Ana Souza", party: "PSB", uf: "RJ" },
      figures: LABELS.map((label, i) => ({ count: 7 * i, total: 90 + i, label })),
      photo: null,
    },
  };
  pages[`rollcall2-${format}`] = {
    kind: "roll_call",
    props: { ...common, format, house: "senado", heading: "PEC 7/2021", date: "2021-11-09", ballot: "nominal", kind: "amendment", approved: false, tallies: { yes: 12, no: 61, others: 2 } },
  };
  pages[`rollcall-${format}`] = {
    kind: "roll_call",
    props: { ...common, format, heading: HEADING_48, date: "2023-03-01", ballot: "nominal", kind: "final", approved: true, tallies: { yes: 400, no: 100, others: 13 } },
  };
}
export default pages;
