<script setup>
// The member share card (share-cards door 4): one template for everyone, in three formats; only the content changes.
import { computed } from "vue";
import { NO_BASE, formatCollected, formatNumber } from "./format.js";
import { HOUSE_NAMES, PHOTO_CREDITS } from "./card.js";
import MandateScore from "./MandateScore.vue";
import OfficialPhoto from "./OfficialPhoto.vue";

const props = defineProps({
  format: { type: String, default: "og", validator: (f) => ["og", "feed", "story"].includes(f) },
  house: { type: String, required: true }, // camara | senado
  legislature: { type: Number, required: true },
  member: { type: Object, required: true }, // { name, party, uf }
  figures: { type: Array, required: true }, // [{ count, total, label }], the merit basis
  votes: { type: Array, required: true }, // [{ rollCallId, date, position, official }] (app-contract-v3 door 5)
  photo: { type: String, default: null },
  photoCredit: { type: String, default: null },
  generatedAt: { type: String, required: true },
  code: { type: String, default: null },
  verifyHost: { type: String, default: null },
});
const LONG_NAME = 40;
const houseName = computed(() => HOUSE_NAMES[props.house]);
</script>

<template>
  <article :class="['ma-card', `ma-card--${format}`]" :aria-label="`Card de ${member.name}`">
    <p class="ma-t-micro ma-eyebrow ma-card__eyebrow">Mandato Aberto · {{ houseName }} · <span class="ma-num">{{ legislature }}ª</span> legislatura</p>
    <OfficialPhoto :src="photo" :name="member.name" :credit="photoCredit ?? PHOTO_CREDITS[house]" />
    <div class="ma-card__body">
      <h1 :class="['ma-card__name', { 'ma-card__name--long': member.name.length > LONG_NAME }]">{{ member.name }}</h1>
      <p class="ma-t-small ma-muted">{{ member.party }} · {{ member.uf }}</p>
      <p class="ma-card__basis ma-t-small">Nas votações sobre propostas e emendas</p>
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
      <template v-if="votes.length">
        <p class="ma-card__score-label ma-t-micro"><span class="ma-num">{{ formatNumber(votes.length) }}</span> {{ votes.length === 1 ? "votação nominal" : "votações nominais" }} do plenário com registro, da mais antiga à mais recente</p>
        <!-- at most 8 px per roll call, so a short mandate is not drawn as a few stretched bars -->
        <div class="ma-card__score" :style="{ maxWidth: `${votes.length * 8}px` }"><MandateScore :votes="votes" :house="house" compact /></div>
      </template>
      <p v-else class="ma-card__score-label ma-t-micro">Nenhuma votação nominal do plenário com registro nesta legislatura.</p>
      <p class="ma-card__foot ma-t-micro">
        <span>Fonte: {{ houseName }}, dados de <span class="ma-num">{{ formatCollected(generatedAt) }}</span></span>{{ " " }}<span v-if="code">Código <span class="ma-num">{{ code }}</span> · confira em {{ verifyHost }}/verificar/</span>
      </p>
    </div>
  </article>
</template>
