<script setup>
// Masthead and footer shared by the prototype pages.
import { formatDate } from "../components/format.js";

defineOptions({ inheritAttrs: false });

defineProps({
  direction: { type: String, required: true },
  generatedAt: { type: String, required: true },
});
</script>

<template>
  <header class="ma-masthead">
    <div class="ma-wrap ma-masthead__inner">
      <a class="ma-wordmark ma-t-title-3" href="#">Mandato Aberto</a>
      <span class="ma-t-micro ma-eyebrow">Protótipo · direção {{ direction }}</span>
    </div>
  </header>
  <slot />
  <footer class="ma-footer">
    <div class="ma-wrap ma-t-small">
      <p>Dados abertos da Câmara dos Deputados, coletados em <span class="ma-num">{{ formatDate(generatedAt) }}</span>. Cada número leva à fonte oficial e ao método.</p>
      <p>Protótipo do sistema de design da versão 2. Não é uma página publicada do Mandato Aberto.</p>
    </div>
  </footer>
</template>
