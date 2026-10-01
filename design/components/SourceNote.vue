<script setup>
// The note a footnote marker points to: the official source and how the number is computed.
defineProps({
  index: { type: Number, required: true },
  sourceUrl: { type: String, required: true },
  sourceLabel: { type: String, default: "Câmara dos Deputados" },
  methodUrl: { type: String, default: "" },
});
</script>

<template>
  <p class="ma-note" :id="`nota-${index}`">
    <sup class="ma-num">{{ index }}</sup>
    Fonte: <a :href="sourceUrl">{{ sourceLabel }}</a><template v-if="methodUrl"> · <a :href="methodUrl">Como calculamos</a></template>
  </p>
</template>
