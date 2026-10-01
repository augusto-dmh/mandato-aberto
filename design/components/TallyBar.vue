<script setup>
// One cell per vote counted, in the order Sim, Não, outros; the counts are always written out.
import { computed } from "vue";
import { formatNumber } from "./format.js";

const props = defineProps({
  yes: { type: Number, required: true },
  no: { type: Number, required: true },
  others: { type: Number, required: true },
});
const groups = computed(() => [
  { kind: "yes", label: "Sim", count: props.yes },
  { kind: "no", label: "Não", count: props.no },
  { kind: "others", label: "Outros registros", count: props.others },
]);
</script>

<template>
  <div class="ma-tally">
    <div class="ma-tally__cells" aria-hidden="true">
      <template v-for="g in groups" :key="g.kind">
        <span v-for="i in g.count" :key="`${g.kind}-${i}`" :class="['ma-tally__cell', `ma-tally__cell--${g.kind}`]"></span>
      </template>
    </div>
    <dl class="ma-tally__counts">
      <div v-for="g in groups" :key="g.kind" :class="['ma-tally__count', `ma-tally__count--${g.kind}`]">
        <dt>{{ g.label }}</dt>
        <dd class="ma-num">{{ formatNumber(g.count) }}</dd>
      </div>
    </dl>
  </div>
</template>
