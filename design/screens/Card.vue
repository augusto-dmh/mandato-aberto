<script setup>
// The prototype's share card: the package's MemberCard in its Open Graph format (share-cards door 4).
// The prototype reads contract v2, a Câmara-only 57th legislature with official vote strings, so its votes are mapped to positions here.
import { computed } from "vue";
import MemberCard from "../components/MemberCard.vue";

defineOptions({ inheritAttrs: false });

const props = defineProps({
  generatedAt: { type: String, required: true },
  deputy: { type: Object, required: true },
  figures: { type: Array, required: true }, // [{ count, total, label }]
  votes: { type: Array, required: true }, // [{ rollCallId, date, title, vote, secret }]
  photo: { type: String, default: null },
});

const POSITIONS = { Sim: "yes", "Não": "no", "Abstenção": "abstention", "Obstrução": "obstruction", "Artigo 17": "presiding" };
const positioned = computed(() =>
  props.votes.map((v) => ({ rollCallId: v.rollCallId, date: v.date, position: v.secret ? "secret" : POSITIONS[v.vote] ?? "notVoting", official: v.vote })),
);
</script>

<template>
  <div class="ma-card-page">
    <MemberCard
      format="og"
      house="camara"
      :legislature="57"
      :member="{ name: deputy.name, party: deputy.party, uf: deputy.uf }"
      :figures="figures"
      :votes="positioned"
      :photo="photo"
      :generated-at="generatedAt"
    />
  </div>
</template>
