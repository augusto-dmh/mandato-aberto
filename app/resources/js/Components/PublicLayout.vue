<script setup>
// Masthead and footer of every public page: design/screens/Chrome.vue without the prototype lines.
// The masthead leads home and to search (app-home AC 43). The footer names each house whose data the page
// shows, with the Brasília day of its latest import (AC 32); a page spanning both houses passes one
// `sourcesLine` sentence instead (app-home AC 42).
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { formatCollected } from "mandato-design/components/format.js";

const page = usePage();
const sources = computed(() => page.props.sources ?? []);
const spansHouses = computed(() => "sourcesLine" in page.props);
</script>

<template>
  <header class="ma-masthead">
    <div class="ma-wrap ma-masthead__inner">
      <a class="ma-wordmark ma-t-title-3" href="/">Mandato Aberto</a>
      <a class="ma-t-small" href="/busca/">Buscar parlamentar</a>
    </div>
  </header>
  <slot />
  <footer class="ma-footer">
    <div class="ma-wrap ma-t-small">
      <template v-if="spansHouses">
        <p v-if="page.props.sourcesLine">{{ page.props.sourcesLine }}</p>
      </template>
      <template v-else>
        <p v-for="s in sources" :key="s.source">{{ s.source }}, coletados em <span class="ma-num">{{ formatCollected(s.collectedAt) }}</span>.</p>
        <p v-if="sources.length">Cada número leva à fonte oficial e ao método.</p>
      </template>
    </div>
  </footer>
</template>
