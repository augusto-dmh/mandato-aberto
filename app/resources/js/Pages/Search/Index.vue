<script setup>
// `/busca/`: members of one legislature in alphabetical order only, with no indicator (app-home S2).
// A plain GET form answered by the server; the page links no client script (door 2). The query is shown
// only as the input's value (AC 20).
import PublicLayout from "../../Components/PublicLayout.vue";
import SearchForm from "../../Components/SearchForm.vue";

defineProps({
  meta: { type: Object, required: true },
  form: { type: Object, default: null }, // null before the first import
  countLine: { type: String, default: "" },
  pastNote: { type: String, default: null },
  rows: { type: Array, default: () => [] }, // [{ house, id, name, houseName, party, uf, href }]
  pager: { type: Object, default: null }, // { label, prev, next }; null on a single page
  clearUrl: { type: String, default: "/busca/" },
});
</script>

<template>
  <PublicLayout>
    <main class="ma-wrap ma-search-page">
      <h1 class="ma-t-display-2">{{ meta.title }}</h1>
      <template v-if="form">
        <SearchForm :form="form" />
        <p v-if="pastNote" class="ma-results__note ma-t-small">{{ pastNote }}</p>
        <template v-if="rows.length">
          <p class="ma-results__count ma-t-small">{{ countLine }}</p>
          <ol class="ma-results">
            <li v-for="r in rows" :key="`${r.house}-${r.id}`">
              <a :href="r.href"><span class="ma-result__name">{{ r.name }}</span> <span class="ma-muted">{{ r.houseName }}</span> <span class="ma-muted ma-result__party">{{ r.party }} · {{ r.uf }}</span></a>
            </li>
          </ol>
          <nav v-if="pager" class="ma-pages" aria-label="Páginas">
            <a v-if="pager.prev" :href="pager.prev" rel="prev">Página anterior</a>
            <span class="ma-pages__current">{{ pager.label }}</span>
            <a v-if="pager.next" :href="pager.next" rel="next">Próxima página</a>
          </nav>
        </template>
        <p v-else class="ma-empty">Nenhum parlamentar encontrado com esses filtros. <a :href="clearUrl">Limpar filtros</a></p>
      </template>
      <p v-else class="ma-empty">Ainda não há dados importados.</p>
    </main>
  </PublicLayout>
</template>
