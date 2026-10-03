<script setup>
// `/metodologia/`: what each number counts, the classification rules each house's latest import applied, and its coverage.
import { formatNumber } from "mandato-design/components/format.js";
import { SENATE_NOT_VOTING, positionCase, voteCase } from "mandato-design/components/vote.js";
import PublicLayout from "../../Components/PublicLayout.vue";

defineProps({
  houses: { type: Array, required: true }, // [{ house, name, ofName, imported, version, rules: [...], coverage: [...] }], Câmara first
  sources: { type: Array, default: () => [] },
});

// How each official record appears on this site (plan door 5), from the same encoding the pages draw with.
const records = [
  ...[
    ["Sim", "Sim", false],
    ["Não", "Não", false],
    ["Abstenção", "Abstenção", false],
    ["Obstrução", "Obstrução", false],
    ["Artigo 17", "Artigo 17", false],
    ["(vazio, em votação secreta)", "", true],
    ["(vazio)", "", false],
  ].map(([shown, official, secret]) => ({ house: "Câmara dos Deputados", official: shown, label: voteCase(official, secret).label })),
  { house: "Senado Federal", official: "Presidente (art. 51 RISF)", label: positionCase({ house: "senado", position: "presiding" }).label },
  { house: "Senado Federal", official: "Votou", label: positionCase({ house: "senado", position: "secret" }).label },
  ...Object.keys(SENATE_NOT_VOTING).map((official) => ({
    house: "Senado Federal",
    official,
    label: positionCase({ house: "senado", position: "notVoting", official }).label,
  })),
];
</script>

<template>
  <PublicLayout>
    <main class="ma-wrap ma-method">
      <h1 class="ma-t-display-2">Metodologia</h1>

      <section id="bases" class="ma-section">
        <h2 class="ma-t-title-2">Duas bases para cada indicador</h2>
        <p>Cada indicador aparece em duas bases. "Todas as votações nominais do plenário" conta toda votação nominal ou secreta do plenário. "Votações sobre propostas e emendas" conta só as classificadas como decisão sobre a proposta ou como emenda, destaque ou parte do texto, pelas regras publicadas nesta página. Nenhuma pessoa escolhe votação por votação.</p>
      </section>

      <section id="participacao" class="ma-section">
        <h2 class="ma-t-title-2">Participação em votações nominais do plenário</h2>
        <p>Base: votações nominais e secretas do plenário na legislatura, realizadas enquanto o parlamentar estava em exercício. Conta: aquelas com voto registrado, inclusive a presidência da sessão e o voto em votação secreta. Licenças, missões e registros sem voto entram na base e não na conta.</p>
      </section>

      <section id="alinhamento-governo" class="ma-section">
        <h2 class="ma-t-title-2">Votos iguais à orientação do governo</h2>
        <p>Base: votos Sim, Não, Abstenção ou Obstrução do parlamentar em votações do plenário em que o governo orientou Sim, Não, Abstenção ou Obstrução. Conta: votos iguais à orientação do governo. Votações secretas e orientações "Liberado" ficam fora da base.</p>
      </section>

      <section id="alinhamento-partido" class="ma-section">
        <h2 class="ma-t-title-2">Votos iguais à maioria do próprio partido</h2>
        <p>Base: votos Sim, Não, Abstenção ou Obstrução do parlamentar em votações do plenário em que a maioria dos demais membros do mesmo partido votou uma dessas opções, sem empate. Conta: votos iguais a essa maioria.</p>
      </section>

      <section id="votacoes-simbolicas" class="ma-section">
        <h2 class="ma-t-title-2">Votações simbólicas</h2>
        <p>Na votação simbólica, o plenário decide sem registrar o voto de cada parlamentar. A página do parlamentar informa quantas votações simbólicas sobre propostas e emendas ocorreram durante o exercício; esse número não entra em nenhum indicador. O Senado Federal não publica votações simbólicas como registros de votação.</p>
      </section>

      <section id="proposicoes" class="ma-section">
        <h2 class="ma-t-title-2">Proposições</h2>
        <p>Contamos as proposições apresentadas dentro da legislatura. Câmara: PL, PLP, PEC, PDL e PRC como autoria; REQ, RIC e INC como requerimentos. Senado: PL, PLP, PEC, PDL e PRS como autoria; RQS, REQ e INS como requerimentos.</p>
      </section>

      <section id="tipos-de-votacao" class="ma-section">
        <h2 class="ma-t-title-2">Tipos de votação</h2>
        <p>Nominal: cada voto fica registrado com o nome do parlamentar. Secreta: a Casa registra quem votou, não como votou. Simbólica: o resultado é proclamado sem registro individual.</p>
      </section>

      <section id="classificacao" class="ma-section">
        <h2 class="ma-t-title-2">Classificação das votações</h2>
        <p>As regras são aplicadas na ordem da tabela ao texto oficial da votação, sem acentos, em minúsculas e com espaços simples; vale a primeira que corresponder. Uma votação sem regra correspondente entra em "todas as votações nominais do plenário" e fica fora da base de propostas e emendas.</p>
        <div v-for="h in houses" :key="h.house" class="ma-method__house">
          <template v-if="h.imported">
            <h3 class="ma-t-title-3">Regras {{ h.ofName }}, versão {{ h.version }}</h3>
            <div class="ma-table">
              <table>
                <thead>
                  <tr><th scope="col">Regra</th><th scope="col">Tipo</th><th scope="col">Campo</th><th scope="col">Padrão</th><th scope="col">Descrição</th></tr>
                </thead>
                <tbody>
                  <tr v-for="r in h.rules" :id="r.anchor" :key="r.id">
                    <td>{{ r.id }}</td>
                    <td>{{ r.kind }}</td>
                    <td><code>{{ r.field }}</code></td>
                    <td><code>{{ r.pattern }}</code></td>
                    <td>{{ r.description }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
          <p v-else class="ma-empty">Ainda não há dados importados {{ h.ofName }}.</p>
        </div>
      </section>

      <section id="registros-sem-voto" class="ma-section">
        <h2 class="ma-t-title-2">Registros sem voto</h2>
        <div class="ma-table">
          <table>
            <thead>
              <tr><th scope="col">Casa</th><th scope="col">Registro oficial</th><th scope="col">Como aparece aqui</th></tr>
            </thead>
            <tbody>
              <tr v-for="r in records" :key="`${r.house}-${r.official}`">
                <td>{{ r.house }}</td>
                <td>{{ r.official }}</td>
                <td>{{ r.label }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p>O Senado publica o tipo de licença de cada senador; aqui todas as licenças aparecem como "Licença".</p>
        <p>Na página de cada senador, os registros sem voto aparecem como "Não registrou voto"; o registro oficial aparece na página da votação, como "Registro do Senado".</p>
      </section>

      <section id="cobertura" class="ma-section">
        <h2 class="ma-t-title-2">Cobertura</h2>
        <p>Quantas votações cada importação trouxe, por Casa e legislatura.</p>
        <div v-for="h in houses" :key="h.house" class="ma-method__house">
          <div v-if="h.imported" class="ma-table">
            <table>
              <thead>
                <tr>
                  <th scope="col">Casa</th><th scope="col">Legislatura</th><th scope="col">Dados até</th><th scope="col">Nominais</th>
                  <th scope="col">Secretas</th><th scope="col">Simbólicas</th><th scope="col">Sem regra correspondente</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="c in h.coverage" :key="c.legislature">
                  <td>{{ h.name }}</td>
                  <td class="ma-num">{{ c.legislature }}</td>
                  <td class="ma-num">{{ c.through ?? "sem votações" }}</td>
                  <td class="ma-num">{{ formatNumber(c.nominal) }}</td>
                  <td class="ma-num">{{ formatNumber(c.secret) }}</td>
                  <td :class="{ 'ma-num': c.symbolic !== null }">{{ c.symbolic === null ? "não publicadas pela Casa" : formatNumber(c.symbolic) }}</td>
                  <td class="ma-num">{{ formatNumber(c.unclassified) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="ma-empty">Ainda não há dados importados {{ h.ofName }}.</p>
        </div>
      </section>
    </main>
  </PublicLayout>
</template>
