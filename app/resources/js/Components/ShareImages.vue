<script setup>
// The share block of a member or roll-call page (share-cards AC 42): the card in three formats and its code.
defineProps({
  card: { type: Object, required: true }, // { code, verifyUrl, formats: [{ label, url }] }
});
</script>

<template>
  <section class="ma-section ma-share">
    <div class="ma-section__head">
      <h2 class="ma-t-title-2">Imagens para compartilhar</h2>
    </div>
    <ul class="ma-share__formats">
      <li v-for="f in card.formats" :key="f.url"><a :href="f.url" download>{{ f.label }}</a></li>
    </ul>
    <p class="ma-t-small">
      <a :href="card.verifyUrl">Código de verificação: <span class="ma-num">{{ card.code }}</span></a>
    </p>
  </section>
</template>
