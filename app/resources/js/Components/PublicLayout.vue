<script setup>
// Masthead and footer of every public page: design/screens/Chrome.vue without the prototype lines.
// The footer names each house whose data the page shows, with the Brasília day of its latest import (AC 32).
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { formatCollected } from "mandato-design/components/format.js";

const page = usePage();
const sources = computed(() => page.props.sources ?? []);
</script>

<template>
  <header class="ma-masthead">
    <div class="ma-wrap ma-masthead__inner">
      <p class="ma-wordmark ma-t-title-3">Mandato Aberto</p>
    </div>
  </header>
  <slot />
  <footer class="ma-footer">
    <div class="ma-wrap ma-t-small">
      <p v-for="s in sources" :key="s.source">{{ s.source }}, coletados em <span class="ma-num">{{ formatCollected(s.collectedAt) }}</span>.</p>
      <p v-if="sources.length">Cada número leva à fonte oficial e ao método.</p>
    </div>
  </footer>
</template>
