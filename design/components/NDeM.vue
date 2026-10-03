<script setup>
// "n de m": the product's unit. The count is the hero, the base is always visible, no percentage.
import { NO_BASE, formatNumber } from "./format.js";
import SourceNote from "./SourceNote.vue";

defineProps({
  count: { type: Number, required: true },
  total: { type: Number, required: true },
  label: { type: String, required: true },
  note: { type: Object, required: true }, // { index, sourceUrl, sourceLabel?, methodUrl }
});
</script>

<template>
  <figure class="ma-ndem">
    <figcaption class="ma-ndem__label">{{ label }}</figcaption>
    <p v-if="total > 0" class="ma-ndem__value">
      <span class="ma-ndem__n ma-num">{{ formatNumber(count) }}</span>{{ " " }}<span class="ma-ndem__m">de <span class="ma-num">{{ formatNumber(total) }}</span></span>
      <a class="ma-note-ref" :href="`#nota-${note.index}`" :aria-describedby="`nota-${note.index}`"><sup class="ma-num">{{ note.index }}</sup></a>
    </p>
    <p v-else class="ma-ndem__value ma-ndem__value--empty">
      <span class="ma-ndem__empty">{{ NO_BASE }}</span>
      <a class="ma-note-ref" :href="`#nota-${note.index}`" :aria-describedby="`nota-${note.index}`"><sup class="ma-num">{{ note.index }}</sup></a>
    </p>
    <SourceNote v-bind="note" />
  </figure>
</template>
