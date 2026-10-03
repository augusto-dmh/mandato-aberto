<script setup>
// `/verificar/`: where a reader types the code printed on a card (share-cards AC 40).
import PublicLayout from "../../Components/PublicLayout.vue";

defineProps({
  howACodeLooks: { type: String, required: true },
});
</script>

<template>
  <PublicLayout>
    <main class="ma-wrap">
      <h1 class="ma-t-display-2">Verificar um card</h1>
      <p class="ma-lede ma-t-body">Cada card do Mandato Aberto traz um código. Com ele, esta página mostra os dados que o card mostrou e diz se ainda são os atuais.</p>
      <form class="ma-verify__form" method="get" action="/verificar/">
        <label for="codigo">Código de verificação</label>
        <input id="codigo" name="codigo" type="text" autocomplete="off" spellcheck="false" placeholder="20270930-K7Q29XPD" />
        <button type="submit">Verificar</button>
      </form>
      <p class="ma-t-small ma-muted">{{ howACodeLooks }}</p>
    </main>
  </PublicLayout>
</template>
