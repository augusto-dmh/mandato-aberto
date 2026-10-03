<script setup>
// The roll-call share card (share-cards door 4): the result and the tally, never a member's name.
import { computed } from "vue";
import { formatCollected, formatDate, formatNumber } from "./format.js";
import { BALLOTS, HOUSE_NAMES, KINDS } from "./card.js";
import TallyBar from "./TallyBar.vue";

const props = defineProps({
  format: { type: String, default: "og", validator: (f) => ["og", "feed", "story"].includes(f) },
  house: { type: String, required: true },
  heading: { type: String, required: true },
  date: { type: String, required: true }, // YYYY-MM-DD
  ballot: { type: String, required: true }, // nominal | secret | symbolic
  kind: { type: String, required: true }, // final | amendment | procedural | unclassified
  approved: { type: Boolean, default: null },
  tallies: { type: Object, default: null }, // { yes, no, others } or null
  generatedAt: { type: String, required: true },
  code: { type: String, default: null },
  verifyHost: { type: String, default: null },
});
const houseName = computed(() => HOUSE_NAMES[props.house]);
const result = computed(() => (props.approved === true ? "Aprovada" : props.approved === false ? "Rejeitada" : "Resultado não informado"));
</script>

<template>
  <article :class="['ma-card', `ma-card--${format}`, 'ma-card--roll-call']" :aria-label="`Card da votação ${heading}`">
    <p class="ma-t-micro ma-eyebrow ma-card__eyebrow">Mandato Aberto · {{ houseName }}</p>
    <div class="ma-card__body">
      <h1 :class="['ma-card__name', { 'ma-card__name--long': heading.length > 40 }]">{{ heading }}</h1>
      <p class="ma-t-small ma-muted">{{ BALLOTS[ballot] }} · {{ KINDS[kind] }} · <span class="ma-num">{{ formatDate(date) }}</span></p>
      <p class="ma-card__result">{{ result }}</p>
      <div class="ma-card__tally">
        <p v-if="ballot === 'symbolic'" class="ma-t-small">Votação simbólica: não há registro do voto de cada parlamentar nem placar.</p>
        <p v-else-if="!tallies" class="ma-t-small">Placar não publicado pela Casa.</p>
        <template v-else>
          <p class="ma-card__tallies"><span class="ma-num">{{ formatNumber(tallies.yes) }}</span> Sim · <span class="ma-num">{{ formatNumber(tallies.no) }}</span> Não · <span class="ma-num">{{ formatNumber(tallies.others) }}</span> outros votos</p>
          <TallyBar :yes="tallies.yes" :no="tallies.no" :others="tallies.others" />
        </template>
      </div>
      <p class="ma-card__foot ma-t-micro">
        <span>Fonte: {{ houseName }}, dados de <span class="ma-num">{{ formatCollected(generatedAt) }}</span></span>{{ " " }}<span v-if="code">Código <span class="ma-num">{{ code }}</span> · confira em {{ verifyHost }}/verificar/</span>
      </p>
    </div>
  </article>
</template>
