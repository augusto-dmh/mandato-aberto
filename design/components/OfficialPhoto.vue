<script setup>
// The official photo is an untouchable object: 3:4, never above its native 354 x 472, no filter, credit always.
import { initials } from "./format.js";

defineProps({
  src: { type: String, default: null },
  name: { type: String, required: true },
  credit: { type: String, default: "Foto: Câmara dos Deputados" },
});
</script>

<template>
  <figure class="ma-photo">
    <div :class="['ma-photo__mat', { 'ma-photo__mat--photo': src }]">
      <img v-if="src" class="ma-photo__img" :src="src" width="354" height="472" :alt="`Foto oficial de ${name}`" decoding="async" />
      <div v-else class="ma-photo__initials" role="img" :aria-label="`Sem foto oficial de ${name}`">{{ initials(name) }}</div>
    </div>
    <figcaption v-if="src" class="ma-photo__credit">{{ credit }}</figcaption>
  </figure>
</template>
