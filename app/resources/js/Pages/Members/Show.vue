<script setup>
// A deputy's or a senator's mandate: design/screens/Profile.vue drawn from the database, one page per legislature.
import MandateScore from "mandato-design/components/MandateScore.vue";
import NDeM from "mandato-design/components/NDeM.vue";
import OfficialPhoto from "mandato-design/components/OfficialPhoto.vue";
import SourceNote from "mandato-design/components/SourceNote.vue";
import { formatNumber } from "mandato-design/components/format.js";
import { computed } from "vue";
import PublicLayout from "../../Components/PublicLayout.vue";

const props = defineProps({
  member: { type: Object, required: true },
  photo: { type: Object, default: null }, // { url, credit } on our origin, or null: the initials frame
  mandate: { type: Object, required: true },
  legislatures: { type: Array, required: true }, // [{ number, label, href, current }], newest first; empty with one mandate
  indicators: { type: Array, required: true }, // [{ label, bases: [merit, all] }]
  votes: { type: Array, required: true }, // [{ rollCallId, date, title, position, official, href }]
  classificationUrl: { type: String, required: true },
  proposicoesMethodUrl: { type: String, required: true },
  symbolicMethodUrl: { type: String, required: true },
  scoreMethodUrl: { type: String, required: true },
  sources: { type: Array, default: () => [] },
});

const senate = computed(() => props.member.house === "senado");
const hrefs = computed(() => Object.fromEntries(props.votes.map((v) => [v.rollCallId, v.href])));
// Notes 1-6 are the indicators'; the symbolic count has the next one only when there is a count to note.
const symbolicNote = computed(() => (props.mandate.symbolicMerit === null ? null : 7));
const noteIndex = computed(() => (symbolicNote.value ?? 6) + 1);
</script>

<template>
  <PublicLayout>
    <main class="ma-wrap">
      <section class="ma-hero">
        <OfficialPhoto :name="member.name" v-bind="photo ? { src: photo.url, credit: photo.credit } : {}" />
        <div class="ma-hero__text">
          <p class="ma-t-micro ma-eyebrow">{{ member.houseName }} · {{ mandate.legislature }}ª legislatura · {{ mandate.party }} · {{ mandate.uf }}</p>
          <h1 class="ma-hero__name ma-t-display-2">{{ member.name }}</h1>
          <nav v-if="legislatures.length" class="ma-legislatures" aria-label="Legislaturas">
            <ul>
              <li v-for="l in legislatures" :key="l.number">
                <a :href="l.href" :aria-current="l.current ? 'page' : undefined">{{ l.label }}</a>
              </li>
            </ul>
          </nav>
          <p v-else class="ma-legislature ma-t-small">{{ mandate.label }}</p>
        </div>
      </section>

      <p class="ma-lede ma-t-body">
        Votações sobre propostas e emendas são as que decidem um projeto, uma proposta de emenda à Constituição, uma medida
        provisória, uma emenda ou um destaque. As demais votações nominais tratam de procedimento, como requerimentos e
        recursos. A classificação segue <a :href="classificationUrl">regras publicadas</a>.
      </p>

      <section class="ma-section" aria-label="Indicadores">
        <div v-for="i in indicators" :key="i.label" class="ma-indicator">
          <h2 class="ma-t-title-3">{{ i.label }}</h2>
          <div class="ma-indicators">
            <NDeM v-for="b in i.bases" :key="b.note.index" v-bind="b" />
          </div>
        </div>

        <p v-if="mandate.symbolicMerit === null" class="ma-symbolic ma-t-small">
          {{ senate ? "O Senado Federal" : "A Câmara dos Deputados" }} não publica votações simbólicas como registros de votação; por isso elas não aparecem aqui.
        </p>
        <p v-else-if="mandate.symbolicMerit === 0" class="ma-symbolic ma-t-small">
          Nenhuma votação simbólica sobre propostas e emendas ocorreu no plenário durante o exercício nesta legislatura.<a class="ma-note-ref" :href="`#nota-${symbolicNote}`" :aria-describedby="`nota-${symbolicNote}`"><sup class="ma-num">{{ symbolicNote }}</sup></a>
        </p>
        <p v-else class="ma-symbolic ma-t-small">
          Durante o exercício nesta legislatura, o plenário também decidiu <span class="ma-num">{{ formatNumber(mandate.symbolicMerit) }}</span>
          {{ mandate.symbolicMerit === 1 ? "votação simbólica" : "votações simbólicas" }} sobre propostas e emendas.<a class="ma-note-ref" :href="`#nota-${symbolicNote}`" :aria-describedby="`nota-${symbolicNote}`"><sup class="ma-num">{{ symbolicNote }}</sup></a> Votação simbólica não registra o voto de cada parlamentar.
        </p>
        <SourceNote v-if="symbolicNote" :index="symbolicNote" :source-url="member.sourceUrl" :source-label="member.houseName" :method-url="symbolicMethodUrl" />

        <div class="ma-stats">
          <div class="ma-stat">
            <span class="ma-t-small ma-muted">Proposições de autoria</span>
            <span class="ma-t-title-1 ma-num">{{ formatNumber(mandate.authoredCount) }}<a class="ma-note-ref" :href="`#nota-${noteIndex}`" :aria-describedby="`nota-${noteIndex}`"><sup class="ma-num">{{ noteIndex }}</sup></a></span>
          </div>
          <div class="ma-stat">
            <span class="ma-t-small ma-muted">Como primeiro signatário</span>
            <span class="ma-t-title-1 ma-num">{{ formatNumber(mandate.firstSignerCount) }}<a class="ma-note-ref" :href="`#nota-${noteIndex}`" :aria-describedby="`nota-${noteIndex}`"><sup class="ma-num">{{ noteIndex }}</sup></a></span>
          </div>
          <div class="ma-stat">
            <span class="ma-t-small ma-muted">Requerimentos</span>
            <span class="ma-t-title-1 ma-num">{{ formatNumber(mandate.requirementsCount) }}<a class="ma-note-ref" :href="`#nota-${noteIndex}`" :aria-describedby="`nota-${noteIndex}`"><sup class="ma-num">{{ noteIndex }}</sup></a></span>
          </div>
        </div>
        <SourceNote :index="noteIndex" :source-url="member.sourceUrl" :source-label="member.houseName" :method-url="proposicoesMethodUrl" />
      </section>

      <section class="ma-section">
        <div class="ma-section__head">
          <h2 class="ma-t-title-2">Votações do mandato</h2>
          <p class="ma-t-small ma-muted">
            Cada traço é uma votação do plenário com registro neste mandato, da mais antiga para a mais recente. Sim fica acima da linha, Não abaixo; as demais opções têm marca própria. Cada traço leva à votação.
          </p>
        </div>
        <MandateScore :votes="votes" :house="member.house" :href="(id) => hrefs[id]" />
        <SourceNote
          :index="noteIndex + 1"
          :source-url="member.sourceUrl"
          :source-label="member.houseName"
          :method-url="scoreMethodUrl"
          :collected-at="sources[0]?.collectedAt ?? ''"
        />
      </section>
    </main>
  </PublicLayout>
</template>
