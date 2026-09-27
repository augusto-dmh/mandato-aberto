/** The three `{count, total}` indicators, labelled as the plan's `Impact` names them (AD-004). */
export const INDICATORS = [
  {
    field: "participation",
    anchor: "participacao",
    label: "Participação em votações nominais do plenário",
    base: "votações nominais do plenário realizadas enquanto em exercício, descontadas as licenças; conta cada votação com qualquer voto registrado, inclusive Art. 17.",
  },
  {
    field: "governmentAlignment",
    anchor: "alinhamento-governo",
    label: "Votos iguais à orientação do governo",
    base: "votos Sim, Não, Abstenção ou Obstrução em votações nas quais o governo orientou uma dessas opções.",
  },
  {
    field: "partyAlignment",
    anchor: "alinhamento-partido",
    label: "Votos iguais à maioria do próprio partido",
    base: "votos Sim, Não, Abstenção ou Obstrução em votações nas quais os demais deputados do partido tiveram um voto majoritário.",
  },
] as const;
