<script setup>
// The share card template: identical for every deputy, only the content changes.
import { NO_BASE, formatDate, formatNumber } from "../components/format.js";
import MandateScore from "../components/MandateScore.vue";
import OfficialPhoto from "../components/OfficialPhoto.vue";

defineOptions({ inheritAttrs: false });

defineProps({
  generatedAt: { type: String, required: true },
  deputy: { type: Object, required: true },
  figures: { type: Array, required: true }, // [{ count, total, label }]
  votes: { type: Array, required: true },
  photo: { type: String, default: null },
});
const LONG_NAME = 40;
</script>

<template>
  <div class="ma-card-page">
    <article class="ma-card" :aria-label="`Card de ${deputy.name}`">
      <OfficialPhoto :src="photo" :name="deputy.name" />
      <div class="ma-card__body">
        <p class="ma-t-micro ma-eyebrow">Mandato Aberto · Câmara dos Deputados</p>
        <h1 :class="['ma-card__name', { 'ma-card__name--long': deputy.name.length > LONG_NAME }]">{{ deputy.name }}</h1>
        <p class="ma-t-small ma-muted">{{ deputy.party }} · {{ deputy.uf }} · desde <span class="ma-num">{{ formatDate(deputy.since) }}</span></p>
        <div class="ma-card__figures">
          <p v-for="f in figures" :key="f.label" class="ma-card__figure">
            <template v-if="f.total > 0">
              <span class="ma-card__n ma-num">{{ formatNumber(f.count) }}</span>{{ " " }}<span class="ma-card__m">de <span class="ma-num">{{ formatNumber(f.total) }}</span></span>{{ " " }}<span class="ma-card__label ma-t-small">{{ f.label }}</span>
            </template>
            <template v-else>
              <span class="ma-card__label ma-t-small">{{ f.label }}:</span>{{ " " }}<span class="ma-card__m ma-empty">{{ NO_BASE }}</span>
            </template>
          </p>
        </div>
        <p class="ma-card__score-label ma-t-micro">{{ formatNumber(votes.length) }} votações nominais com registro, da mais antiga à mais recente</p>
        <MandateScore :votes="votes" compact />
        <p class="ma-card__foot ma-t-micro">
          <span>Fonte: Câmara dos Deputados, dados de <span class="ma-num">{{ formatDate(generatedAt) }}</span></span>
          <span>mandato aberto</span>
        </p>
      </div>
    </article>
  </div>
</template>
