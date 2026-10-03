<script setup>
// One count of a house's activity with its source note right below (app-home AC 3, AC 23, AC 24).
import SourceNote from "mandato-design/components/SourceNote.vue";

defineProps({
  count: { type: Object, required: true }, // { value, label, note }; value and note null when the house publishes no such count
});
</script>

<template>
  <p class="ma-count"><span v-if="count.value !== null" class="ma-num ma-count__value">{{ count.value }}</span>{{ count.value !== null ? " " : "" }}{{ count.label }}</p>
  <SourceNote v-if="count.note" v-bind="count.note" />
</template>
