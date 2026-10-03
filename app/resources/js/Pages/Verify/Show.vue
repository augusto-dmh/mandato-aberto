<script setup>
// `/verificar/{code}/`: exactly what the card of this code showed, and whether the current data still match (share-cards S5).
import { formatDate, formatNumber, NO_BASE } from "mandato-design/components/format.js";
import { positionCase } from "mandato-design/components/vote.js";
import PublicLayout from "../../Components/PublicLayout.vue";

const props = defineProps({
  code: { type: String, default: null }, // null: no stored code matched (AC 39)
  image: { type: Object, default: null }, // { src, alt }, or null when the subject is gone (S5 amendment)
  state: { type: String, default: null }, // equal | changed | gone (AC 37)
  dataDate: { type: String, default: null }, // DD/MM/AAAA, the Brasília day of the card's data
  subjectUrl: { type: String, default: null },
  collected: { type: String, default: null },
  card: { type: Object, default: null }, // the payload's values: a member's or a roll call's
  howACodeLooks: { type: String, default: null },
  verifyUrl: { type: String, default: "/verificar/" },
});

const voteLabel = (v) => positionCase({ house: props.card.house, position: v.position }).label;
</script>

<template>
  <PublicLayout>
    <main v-if="code === null" class="ma-wrap">
      <h1 class="ma-t-display-2">Código não encontrado</h1>
      <p class="ma-lede ma-t-body">{{ howACodeLooks }}</p>
      <p class="ma-t-small"><a :href="verifyUrl">Digitar outro código</a></p>
    </main>

    <main v-else class="ma-wrap ma-verify">
      <h1 class="ma-t-display-2">Código <span class="ma-num">{{ code }}</span></h1>

      <p v-if="state === 'equal'" class="ma-lede ma-t-body">Os dados atuais são iguais aos do card.</p>
      <p v-else-if="state === 'changed'" class="ma-lede ma-t-body">
        Os dados mudaram desde <span class="ma-num">{{ dataDate }}</span>. O card mostra os dados daquela data.{{ " " }}<a :href="subjectUrl">Ver dados atuais</a>
      </p>
      <p v-else class="ma-lede ma-t-body">Este registro não está nos dados atuais.</p>

      <img v-if="image" class="ma-verify__card" :src="image.src" :alt="image.alt" width="1200" height="630" />

      <section class="ma-section" aria-label="Dados do card">
        <template v-if="card.kind === 'member'">
          <p class="ma-t-micro ma-eyebrow">{{ card.houseName }} · <span class="ma-num">{{ card.legislature }}ª</span> legislatura</p>
          <h2 class="ma-t-title-1">{{ card.name }}</h2>
          <p class="ma-t-small">{{ card.party }} · {{ card.uf }}</p>
          <p class="ma-t-small ma-muted">
            <template v-if="card.photoSha256">Foto oficial: arquivo <span class="ma-num">{{ card.photoSha256 }}</span></template>
            <template v-else>Sem foto oficial: o card mostra as iniciais do nome.</template>
          </p>
          <p class="ma-t-small">Nas votações sobre propostas e emendas</p>
          <dl class="ma-verify__figures">
            <div v-for="f in card.figures" :key="f.label">
              <dt>{{ f.label }}</dt>{{ " " }}
              <dd v-if="f.total > 0" class="ma-num">{{ formatNumber(f.count) }} de {{ formatNumber(f.total) }}</dd>
              <dd v-else class="ma-empty">{{ NO_BASE }}</dd>
            </div>
          </dl>
          <template v-if="card.votes.length">
            <h3 class="ma-t-title-3">{{ card.votes.length === 1 ? "Votação nominal" : "Votações nominais" }} do plenário com registro, da mais antiga à mais recente</h3>
            <ol class="ma-verify__votes">
              <li v-for="v in card.votes" :key="v.rollCallId"><a :href="v.href" class="ma-num">{{ formatDate(v.date) }}</a>{{ " " }}{{ voteLabel(v) }}</li>
            </ol>
          </template>
          <p v-else class="ma-empty">Nenhuma votação nominal do plenário com registro nesta legislatura.</p>
        </template>

        <template v-else>
          <p class="ma-t-micro ma-eyebrow">{{ card.houseName }}</p>
          <h2 class="ma-t-title-1">{{ card.heading }}</h2>
          <p class="ma-t-small">{{ card.classification }}</p>
          <p class="ma-t-title-3">{{ card.result }}</p>
          <p v-if="card.tallies" class="ma-num">{{ card.tallies.join(" · ") }}</p>
          <p v-else class="ma-empty">{{ card.noTallies }}</p>
        </template>
      </section>

      <p class="ma-t-small">{{ collected }}</p>
      <p class="ma-t-small"><a :href="subjectUrl">{{ card.kind === "member" ? `Página de ${card.name}` : `Página da votação ${card.heading}` }}</a></p>
    </main>
  </PublicLayout>
</template>
