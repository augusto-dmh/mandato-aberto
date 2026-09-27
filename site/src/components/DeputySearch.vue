<script setup lang="ts">
/**
 * Home search island. Server-rendered with the initial filters, so the in-exercise list is plain
 * HTML without JavaScript; in the browser it only narrows that list. Alphabetical order only.
 */
import { computed, reactive } from "vue";

import { clearFilters, filterDeputies, initialFilters, type DeputyCard } from "../lib/search";
import { withBase } from "../lib/urls";

const props = defineProps<{ deputies: DeputyCard[] }>();

const filters = reactive(initialFilters());
const ufs = computed(() => [...new Set(props.deputies.map((d) => d.uf))].sort());
const parties = computed(() => [...new Set(props.deputies.map((d) => d.party))].sort());
const shown = computed(() => filterDeputies(props.deputies, filters));
const clear = () => Object.assign(filters, clearFilters());
</script>

<template>
  <section class="section" aria-labelledby="busca">
    <h2 id="busca">Encontre um deputado</h2>
    <form class="filters" role="search" @submit.prevent>
      <label class="field field--query">
        <span>Nome</span>
        <input v-model="filters.query" type="search" name="q" placeholder="Digite parte do nome" autocomplete="off" />
      </label>
      <label class="field">
        <span>UF</span>
        <select v-model="filters.uf" name="uf">
          <option value="">Todas as UFs</option>
          <option v-for="uf in ufs" :key="uf" :value="uf">{{ uf }}</option>
        </select>
      </label>
      <label class="field">
        <span>Partido</span>
        <select v-model="filters.party" name="partido">
          <option value="">Todos os partidos</option>
          <option v-for="party in parties" :key="party" :value="party">{{ party }}</option>
        </select>
      </label>
      <div class="checks">
        <label class="check"><input v-model="filters.inExercise" type="checkbox" name="exercicio" /> Em exercício</label>
        <label class="check">
          <input v-model="filters.candidacy" type="checkbox" name="candidatura" /> Candidatura em 2026
        </label>
      </div>
    </form>
    <p class="count" aria-live="polite">{{ shown.length }} {{ shown.length === 1 ? "deputado" : "deputados" }}, em ordem alfabética</p>
    <ul v-if="shown.length" class="deputy-list">
      <li v-for="d in shown" :key="d.id">
        <a :href="withBase(`/deputados/${d.id}/`)">
          <img v-if="d.photo" :src="d.photo" alt="" width="48" height="64" loading="lazy" />
          <span class="name">{{ d.name }}</span>
          <span class="where">{{ d.party }} · {{ d.uf }}</span>
          <span v-if="d.candidate" class="badge">Candidatura em 2026</span>
        </a>
      </li>
    </ul>
    <div v-else class="empty">
      <p>Nenhum deputado encontrado com esses filtros.</p>
      <button type="button" @click="clear">Limpar filtros</button>
    </div>
    <p class="note">Fotos: Câmara dos Deputados.</p>
  </section>
</template>
