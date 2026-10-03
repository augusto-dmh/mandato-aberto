<script setup>
// One vote, encoded by position and shape (plan doors 5 and 6); colour is always currentColor.
// Contract v2 passes `vote` and `secret`; contract v3 passes `house`, `position` and `official` (app-contract-v3 door 5).
import { computed } from "vue";
import { caseSentence, markShapes, positionCase, voteCase } from "./vote.js";

const props = defineProps({
  vote: { type: String, default: "" },
  secret: { type: Boolean, default: false },
  who: { type: String, default: "" },
  house: { type: String, default: "camara" },
  position: { type: String, default: null },
  official: { type: String, default: null },
});
const vcase = computed(() =>
  props.position === null
    ? voteCase(props.vote, props.secret)
    : positionCase({ house: props.house, position: props.position, official: props.official }),
);
const kind = computed(() => vcase.value.kind);
const shapes = computed(() => markShapes(kind.value));
</script>

<template>
  <svg
    :class="['ma-vote', `ma-vote--${kind}`]"
    role="img"
    :aria-label="caseSentence(vcase, who)"
    viewBox="0 0 8 24"
    width="8"
    height="24"
  >
    <line class="ma-vote__baseline" x1="0" x2="8" y1="12" y2="12" stroke="currentColor" stroke-width="0.5" stroke-opacity="0.4" />
    <component :is="s.tag" v-for="(s, i) in shapes" :key="i" v-bind="s.attrs" />
  </svg>
</template>
