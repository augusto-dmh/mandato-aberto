<script setup>
// One vote, encoded by position and shape (plan doors 5 and 6); colour is always currentColor.
import { computed } from "vue";
import { markShapes, voteCase, voteSentence } from "./vote.js";

const props = defineProps({
  vote: { type: String, required: true },
  secret: { type: Boolean, default: false },
  who: { type: String, default: "" },
});
const kind = computed(() => voteCase(props.vote, props.secret).kind);
const shapes = computed(() => markShapes(kind.value));
</script>

<template>
  <svg
    :class="['ma-vote', `ma-vote--${kind}`]"
    role="img"
    :aria-label="voteSentence(vote, secret, who)"
    viewBox="0 0 8 24"
    width="8"
    height="24"
  >
    <line class="ma-vote__baseline" x1="0" x2="8" y1="12" y2="12" stroke="currentColor" stroke-width="0.5" stroke-opacity="0.4" />
    <component :is="s.tag" v-for="(s, i) in shapes" :key="i" v-bind="s.attrs" />
  </svg>
</template>
