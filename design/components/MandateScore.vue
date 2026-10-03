<script setup>
// "Partitura do mandato": one column per nominal roll call, oldest first, one row per year.
import { computed } from "vue";
import { formatDate } from "./format.js";
import { LEGEND, POSITION_LEGEND, markShapes, positionCase, voteCase } from "./vote.js";
import VoteMark from "./VoteMark.vue";

const props = defineProps({
  votes: { type: Array, required: true }, // [{ rollCallId, date, title, vote, secret }] or, with `house`, [{ rollCallId, date, title, position, official }]
  house: { type: String, default: null }, // contract v3: marks follow `position`, labels the house (app-contract-v3 door 5)
  href: { type: Function, default: (id) => `/votacoes/${id}/` },
  compact: { type: Boolean, default: false },
});

const COLUMN = 4;
const ordered = computed(() =>
  [...props.votes].sort((a, b) => (a.date === b.date ? a.rollCallId.localeCompare(b.rollCallId) : a.date < b.date ? -1 : 1)),
);
const rows = computed(() => {
  if (props.compact) return [{ year: "", votes: ordered.value }];
  const byYear = new Map();
  for (const v of ordered.value) {
    const year = v.date.slice(0, 4);
    if (!byYear.has(year)) byYear.set(year, []);
    byYear.get(year).push(v);
  }
  return [...byYear].map(([year, votes]) => ({ year, votes }));
});
const widest = computed(() => Math.max(1, ...rows.value.map((r) => r.votes.length)));
const caseOf = (v) => (props.house ? positionCase({ house: props.house, position: v.position, official: v.official ?? null }) : voteCase(v.vote, v.secret));
const label = (v) => caseOf(v).label;
const legend = computed(() =>
  props.house
    ? POSITION_LEGEND.map((position) => ({ key: position, mark: { house: props.house, position }, label: positionCase({ house: props.house, position }).label }))
    : LEGEND.map((value) => ({ key: value, mark: { vote: value }, label: voteCase(value).label })),
);
/** Index of the first vote of each month in a row: where a month tick goes. */
const monthStarts = (votes) => votes.flatMap((v, i) => (i === 0 || v.date.slice(0, 7) !== votes[i - 1].date.slice(0, 7) ? [i] : []));
</script>

<template>
  <div :class="['ma-score', { 'ma-score--compact': compact }]">
    <div v-for="row in rows" :key="row.year" class="ma-score__row">
      <span v-if="row.year" class="ma-score__year ma-num">{{ row.year }}</span>
      <svg
        class="ma-score__strip"
        :viewBox="`0 0 ${row.votes.length * COLUMN} ${compact ? 24 : 28}`"
        preserveAspectRatio="none"
        :style="{ width: `${(row.votes.length / widest) * 100}%` }"
        role="img"
        :aria-label="`${row.votes.length} ${row.votes.length === 1 ? 'votação nominal' : 'votações nominais'}${row.year ? ` em ${row.year}` : ''}`"
      >
        <line x1="0" :x2="row.votes.length * COLUMN" y1="12" y2="12" stroke="currentColor" stroke-width="0.5" stroke-opacity="0.4" vector-effect="non-scaling-stroke" />
        <template v-if="!compact">
          <line
            v-for="i in monthStarts(row.votes)"
            :key="`m-${i}`"
            class="ma-score__month"
            :x1="i * COLUMN + 0.5"
            :x2="i * COLUMN + 0.5"
            y1="25"
            y2="28"
            stroke="currentColor"
            stroke-width="1"
            vector-effect="non-scaling-stroke"
          />
        </template>
        <component
          :is="compact ? 'g' : 'a'"
          v-for="(v, i) in row.votes"
          :key="v.rollCallId"
          class="ma-score__col"
          :href="compact ? undefined : href(v.rollCallId)"
          :transform="`translate(${i * COLUMN} 0)`"
        >
          <title v-if="!compact">{{ formatDate(v.date) }} · {{ v.title }} · {{ label(v) }}</title>
          <rect v-if="!compact" class="ma-score__hit" x="0" y="0" :width="COLUMN" height="24" fill="none" pointer-events="all" />
          <component :is="s.tag" v-for="(s, j) in markShapes(caseOf(v).kind)" :key="j" v-bind="s.attrs" transform="scale(0.5 1)" />
        </component>
      </svg>
    </div>
    <template v-if="!compact">
      <ul class="ma-score__legend">
        <li v-for="item in legend" :key="item.key"><VoteMark v-bind="item.mark" />{{ item.label }}</li>
      </ul>
      <details class="ma-score__table">
        <summary>{{ ordered.length === 1 ? "Ver a 1 votação como tabela" : `Ver as ${ordered.length} votações como tabela` }}</summary>
        <table>
          <thead>
            <tr><th scope="col">Data</th><th scope="col">Proposição</th><th scope="col">Voto</th></tr>
          </thead>
          <tbody>
            <tr v-for="v in ordered" :key="v.rollCallId">
              <td class="ma-num">{{ formatDate(v.date) }}</td>
              <td><a :href="href(v.rollCallId)">{{ v.title }}</a></td>
              <td>{{ label(v) }}</td>
            </tr>
          </tbody>
        </table>
      </details>
    </template>
  </div>
</template>
