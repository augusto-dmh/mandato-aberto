<script setup>
// Masthead and footer of every public page: design/screens/Chrome.vue without the prototype lines.
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { formatCollected } from "mandato-design/components/format.js";

const page = usePage();
const collectedAt = computed(() => page.props.collectedAt);
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
      <p v-if="collectedAt">Dados abertos da Câmara dos Deputados, coletados em <span class="ma-num">{{ formatCollected(collectedAt) }}</span>. Cada número leva à fonte oficial e ao método.</p>
    </div>
  </footer>
</template>
