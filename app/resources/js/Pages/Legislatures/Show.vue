<script setup>
// `/legislaturas/{n}/`: each house's activity in a legislature as counts with notes, a calendar by month
// and the latest roll calls. No person is named and nothing is ordered by a member's indicator (AD-019).
// Rendered on the server only: the page links no client script (door 2).
import HouseCount from "../../Components/HouseCount.vue";
import PublicLayout from "../../Components/PublicLayout.vue";

defineProps({
  meta: { type: Object, required: true },
  legislature: { type: Object, required: true }, // { number, dates }
  legislatures: { type: Array, default: () => [] }, // [{ label, href, current }], newest first; empty with one stored
  houses: { type: Array, required: true }, // Câmara first: { house, name, missing } or { house, name, counts, none | years, months, recent }
});
</script>

<template>
  <PublicLayout>
    <main class="ma-wrap ma-overview">
      <h1 class="ma-t-display-2">{{ legislature.number }}ª legislatura</h1>
      <p class="ma-legislature__dates ma-t-small">{{ legislature.dates }}</p>
      <nav v-if="legislatures.length" class="ma-legislatures" aria-label="Legislaturas">
        <ul>
          <li v-for="l in legislatures" :key="l.href">
            <a :href="l.href" :aria-current="l.current ? 'page' : undefined">{{ l.label }}</a>
          </li>
        </ul>
      </nav>

      <section v-for="h in houses" :key="h.house" class="ma-house ma-section">
        <h2 class="ma-t-title-2">{{ h.name }}</h2>
        <p v-if="h.missing" class="ma-empty">{{ h.missing }}</p>
        <template v-else>
          <HouseCount v-for="c in h.counts" :key="c.label" :count="c" />
          <p v-if="h.none" class="ma-empty">{{ h.none }}</p>
          <template v-else>
            <div class="ma-cal" aria-hidden="true">
              <h3 class="ma-t-title-3">Votações nominais e secretas no plenário, por mês</h3>
              <div v-for="y in h.years" :key="y.year" class="ma-cal__year">
                <span class="ma-cal__label ma-num">{{ y.year }}</span>
                <ol>
                  <li v-for="c in y.cells" :key="c.month" class="ma-cal__cell">
                    <span class="ma-cal__track"><span class="ma-cal__bar" :style="`height: ${c.height}`"></span></span>
                    <span class="ma-cal__month">{{ c.month }}</span>{{ " " }}<span class="ma-num">{{ c.count }}</span>
                  </li>
                </ol>
              </div>
            </div>
            <div class="ma-cal-table ma-table">
              <table>
                <caption class="ma-t-small">Votações nominais e secretas no plenário, por mês</caption>
                <thead>
                  <tr><th scope="col">Mês</th><th scope="col">Votações</th><th scope="col">Dias com votação</th></tr>
                </thead>
                <tbody>
                  <tr v-for="m in h.months" :key="m.label">
                    <th scope="row">{{ m.label }}</th><td class="ma-num">{{ m.count }}</td><td class="ma-num">{{ m.days }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="ma-recent">
              <h3 class="ma-t-title-3">Votações nominais e secretas mais recentes</h3>
              <ol>
                <li v-for="r in h.recent" :key="r.href">
                  <a :href="r.href"><span class="ma-num">{{ r.date }}</span> · <strong>{{ r.title }}</strong> · {{ r.classification }} · {{ r.result }}</a>
                </li>
              </ol>
            </div>
          </template>
        </template>
      </section>
    </main>
  </PublicLayout>
</template>
