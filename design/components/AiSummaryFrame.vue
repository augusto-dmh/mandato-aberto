<script setup>
// An AI summary of an official text is shown only once a person reviewed it, always framed and labelled.
import { formatDate } from "./format.js";

defineProps({
  text: { type: String, required: true },
  reviewedAt: { type: String, default: null },
  officialUrl: { type: String, required: true },
  reportUrl: { type: String, required: true },
  sample: { type: Boolean, default: false },
});
</script>

<template>
  <aside v-if="reviewedAt" class="ma-ai" aria-label="Resumo gerado por IA">
    <p class="ma-ai__label">
      <span v-if="sample" class="ma-ai__sample">Exemplo</span>
      Resumo gerado por IA a partir do texto oficial, revisado em <span class="ma-num">{{ formatDate(reviewedAt) }}</span>
    </p>
    <p class="ma-ai__text">{{ text }}</p>
    <p class="ma-ai__links"><a :href="officialUrl">Ler o texto oficial</a> · <a :href="reportUrl">Reportar erro neste resumo</a></p>
  </aside>
</template>
