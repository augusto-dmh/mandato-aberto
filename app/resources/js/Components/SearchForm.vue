<script setup>
// The search form of the home and of /busca/ (app-home AC 2, AC 20): a plain GET form, no script needed.
defineProps({
  form: { type: Object, required: true }, // { action, values, houses, ufs, parties, legislatures }
});
</script>

<template>
  <form class="ma-search" role="search" method="get" :action="form.action">
    <div class="ma-search__field ma-search__field--name">
      <label for="busca-q">Nome</label>
      <input id="busca-q" type="text" name="q" :value="form.values.q" maxlength="100" autocomplete="off" />
    </div>
    <div class="ma-search__field">
      <label for="busca-casa">Casa</label>
      <select id="busca-casa" name="casa">
        <option value="" :selected="!form.values.casa">Todas as Casas</option>
        <option v-for="h in form.houses" :key="h.value" :value="h.value" :selected="form.values.casa === h.value">{{ h.label }}</option>
      </select>
    </div>
    <div class="ma-search__field">
      <label for="busca-uf">UF</label>
      <select id="busca-uf" name="uf">
        <option value="" :selected="!form.values.uf">Todas as UFs</option>
        <option v-for="uf in form.ufs" :key="uf" :value="uf" :selected="form.values.uf === uf">{{ uf }}</option>
      </select>
    </div>
    <div class="ma-search__field">
      <label for="busca-partido">Partido</label>
      <select id="busca-partido" name="partido">
        <option value="" :selected="!form.values.partido">Todos os partidos</option>
        <option v-for="p in form.parties" :key="p" :value="p" :selected="form.values.partido === p">{{ p }}</option>
      </select>
    </div>
    <div class="ma-search__field">
      <label for="busca-legislatura">Legislatura</label>
      <select id="busca-legislatura" name="legislatura">
        <option v-for="l in form.legislatures" :key="l.value" :value="l.value" :selected="form.values.legislatura === l.value">{{ l.label }}</option>
      </select>
    </div>
    <div class="ma-search__field">
      <label for="busca-situacao">Situação</label>
      <select id="busca-situacao" name="situacao">
        <option value="exercicio" :selected="form.values.situacao === 'exercicio'">Em exercício</option>
        <option value="todos" :selected="form.values.situacao === 'todos'">Todos</option>
      </select>
    </div>
    <button type="submit">Buscar</button>
  </form>
</template>
