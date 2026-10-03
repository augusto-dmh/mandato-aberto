<script setup>
// `/`: one sentence of purpose, the search form and the current legislature's headline counts (app-home S1).
// Rendered on the server only: the page links no client script (door 2).
import HouseCount from "../../Components/HouseCount.vue";
import PublicLayout from "../../Components/PublicLayout.vue";
import SearchForm from "../../Components/SearchForm.vue";

defineProps({
  meta: { type: Object, required: true },
  lede: { type: String, required: true },
  form: { type: Object, default: null }, // null before the first import
  houses: { type: Array, default: () => [] }, // [{ house, name, counts }], Câmara first, only houses listing the current legislature
  overview: { type: Object, default: null }, // { label, href }
});
</script>

<template>
  <PublicLayout>
    <main class="ma-wrap ma-home">
      <h1 class="ma-t-display-2">{{ meta.title }}</h1>
      <p class="ma-lede ma-t-title-3">{{ lede }}</p>

      <template v-if="form">
        <SearchForm :form="form" />
        <section v-for="h in houses" :key="h.house" class="ma-house ma-section">
          <h2 class="ma-t-title-2">{{ h.name }}</h2>
          <HouseCount v-for="c in h.counts" :key="c.label" :count="c" />
        </section>
        <p v-if="overview" class="ma-section"><a :href="overview.href">{{ overview.label }}</a></p>
      </template>
      <p v-else class="ma-empty">Ainda não há dados importados.</p>
    </main>
  </PublicLayout>
</template>
