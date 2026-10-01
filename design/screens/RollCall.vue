<script setup>
import AiSummaryFrame from "../components/AiSummaryFrame.vue";
import { formatDate, formatNumber } from "../components/format.js";
import TallyBar from "../components/TallyBar.vue";
import VoteMark from "../components/VoteMark.vue";
import Chrome from "./Chrome.vue";

defineOptions({ inheritAttrs: false });

defineProps({
  direction: { type: String, required: true },
  generatedAt: { type: String, required: true },
  rollCall: { type: Object, required: true },
  groups: { type: Array, required: true }, // [{ value, label, entries: [{ name, party, uf, vote }] }]
  summary: { type: Object, default: null },
});
</script>

<template>
  <Chrome :direction="direction" :generated-at="generatedAt">
    <main class="ma-wrap">
      <header class="ma-rollcall__head">
        <p class="ma-t-micro ma-eyebrow">
          Votação nominal · {{ rollCall.organLabel }} · <span class="ma-num">{{ formatDate(rollCall.date) }}</span>
        </p>
        <h1 class="ma-t-display-2">{{ rollCall.title }}</h1>
        <p v-if="rollCall.summary" class="ma-quote"><span class="ma-t-micro ma-eyebrow">Ementa oficial</span><br />{{ rollCall.summary }}</p>
        <p class="ma-t-small ma-muted">{{ rollCall.description }}</p>
      </header>

      <section class="ma-result" aria-label="Resultado">
        <p class="ma-t-title-1">{{ rollCall.resultLabel }}</p>
        <TallyBar v-bind="rollCall.tallies" />
        <p class="ma-t-small">
          <template v-if="rollCall.governmentOrientation">Orientação do governo: {{ rollCall.governmentOrientation }}</template>
          <template v-else>Sem orientação do governo registrada</template>
        </p>
      </section>

      <div class="ma-utilities">
        <a :href="rollCall.sourceUrl">Dados abertos da Câmara sobre esta votação</a>
        <a :href="`#votacao-${rollCall.id}`">Link permanente</a>
      </div>

      <section v-if="summary" class="ma-section">
        <AiSummaryFrame v-bind="summary" />
      </section>

      <section class="ma-section" :id="`votacao-${rollCall.id}`">
        <div class="ma-section__head">
          <h2 class="ma-t-title-2">Como cada deputado votou</h2>
          <p class="ma-t-small ma-muted">Em ordem alfabética dentro de cada opção. Partido na data da votação.</p>
        </div>
        <div class="ma-groups">
          <section v-for="g in groups" :key="g.value" class="ma-group">
            <h3 class="ma-group__head ma-t-title-3">
              <VoteMark :vote="g.value" :secret="rollCall.secret" />
              {{ g.label }} <span class="ma-num ma-muted">{{ formatNumber(g.entries.length) }}</span>
            </h3>
            <ul class="ma-group__list">
              <li v-for="e in g.entries" :key="e.deputyId">
                <VoteMark :vote="e.vote" :secret="rollCall.secret" :who="`${e.name}, ${e.party}-${e.uf}`" />
                {{ e.name }} <span class="ma-group__party ma-muted">{{ e.party }}-{{ e.uf }}</span>
              </li>
            </ul>
          </section>
        </div>
      </section>
    </main>
  </Chrome>
</template>
