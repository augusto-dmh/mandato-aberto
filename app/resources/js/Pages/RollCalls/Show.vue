<script setup>
// A roll call of either house: design/screens/RollCall.vue drawn from the database, each name linking to its page.
import SourceNote from "mandato-design/components/SourceNote.vue";
import TallyBar from "mandato-design/components/TallyBar.vue";
import VoteMark from "mandato-design/components/VoteMark.vue";
import { formatDate, formatNumber } from "mandato-design/components/format.js";
import { senateDescription } from "mandato-design/components/vote.js";
import PublicLayout from "../../Components/PublicLayout.vue";
import ShareImages from "../../Components/ShareImages.vue";

defineProps({
  rollCall: { type: Object, required: true },
  groups: { type: Array, required: true }, // [{ position, label, entries: [{ memberId, name, party, uf, href, position, official }] }]
  card: { type: Object, required: true }, // share-cards AC 42
  sources: { type: Array, default: () => [] },
});
</script>

<template>
  <PublicLayout>
    <main class="ma-wrap">
      <header class="ma-rollcall__head">
        <p class="ma-t-micro ma-eyebrow">
          {{ rollCall.organLabel }} · <span class="ma-num">{{ formatDate(rollCall.date) }}</span>
        </p>
        <h1 class="ma-t-display-2">{{ rollCall.title }}</h1>
        <p class="ma-rollcall__kind ma-t-small">
          <span class="ma-rollcall__classification">{{ rollCall.classification }}</span> ·
          <a :href="rollCall.rule.href">{{ rollCall.rule.label }}</a>
        </p>
        <p v-if="rollCall.summary" class="ma-quote"><span class="ma-t-micro ma-eyebrow">Ementa oficial</span><br />{{ rollCall.summary }}</p>
        <p class="ma-t-small ma-muted">{{ rollCall.description }}</p>
      </header>

      <section class="ma-result" aria-label="Resultado">
        <p class="ma-t-title-1">{{ rollCall.resultLabel }}<a class="ma-note-ref" href="#nota-1" aria-describedby="nota-1"><sup class="ma-num">1</sup></a></p>
        <TallyBar v-if="rollCall.tallies" v-bind="rollCall.tallies" />
        <p v-else-if="rollCall.ballot !== 'symbolic'" class="ma-empty">Placar não publicado pela Casa.</p>
        <SourceNote :index="1" :source-url="rollCall.sourceUrl" :source-label="rollCall.sourceLabel" />
        <p v-if="rollCall.ballot !== 'symbolic'" class="ma-t-small">
          <template v-if="rollCall.governmentOrientation">Orientação do governo: {{ rollCall.governmentOrientation }}</template>
          <template v-else>Sem orientação do governo registrada</template>
        </p>
      </section>

      <div class="ma-utilities">
        <a :href="rollCall.sourceUrl">{{ rollCall.sourceLink }}</a>
        <a :href="`#votacao-${rollCall.id}`">Link permanente</a>
      </div>

      <section :id="`votacao-${rollCall.id}`" class="ma-section">
        <div class="ma-section__head">
          <h2 class="ma-t-title-2">{{ rollCall.membersHeading }}</h2>
          <p v-if="groups.length" class="ma-t-small ma-muted">Em ordem alfabética dentro de cada opção. Partido na data da votação.</p>
        </div>
        <p v-if="rollCall.ballot === 'symbolic'" class="ma-empty">Votação simbólica: não há registro do voto de cada parlamentar nem placar.</p>
        <p v-else-if="groups.length === 0" class="ma-empty">Nenhum voto individual registrado nesta votação.</p>
        <div v-else class="ma-groups">
          <section v-for="g in groups" :key="g.position" class="ma-group">
            <h3 class="ma-group__head ma-t-title-3">
              <VoteMark :house="rollCall.house" :position="g.position" />
              {{ g.label }} <span class="ma-num ma-muted">{{ formatNumber(g.entries.length) }}</span>
            </h3>
            <ul class="ma-group__list">
              <li v-for="e in g.entries" :key="e.memberId">
                <VoteMark :house="rollCall.house" :position="e.position" :official="e.official" :who="`${e.name}, ${e.party}-${e.uf}`" />
                <a :href="e.href">{{ e.name }}</a> <span class="ma-group__party ma-muted">{{ e.party }}-{{ e.uf }}</span>
                <span v-if="rollCall.house === 'senado' && e.position === 'notVoting'" class="ma-group__official ma-muted">Registro do Senado: {{ senateDescription(e.official) }}</span>
              </li>
            </ul>
          </section>
        </div>
      </section>

      <ShareImages :card="card" />
    </main>
  </PublicLayout>
</template>
