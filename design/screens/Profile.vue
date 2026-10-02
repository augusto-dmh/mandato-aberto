<script setup>
import { NO_BASE, formatDate, formatNumber } from "../components/format.js";
import MandateScore from "../components/MandateScore.vue";
import NDeM from "../components/NDeM.vue";
import OfficialPhoto from "../components/OfficialPhoto.vue";
import SourceNote from "../components/SourceNote.vue";
import Chrome from "./Chrome.vue";

defineOptions({ inheritAttrs: false });

defineProps({
  direction: { type: String, required: true },
  generatedAt: { type: String, required: true },
  deputy: { type: Object, required: true },
  indicators: { type: Array, required: true },
  votes: { type: Array, required: true },
  photo: { type: String, default: null },
  methodBase: { type: String, required: true },
});
</script>

<template>
  <Chrome :direction="direction" :generated-at="generatedAt">
    <main class="ma-wrap">
      <section class="ma-hero">
        <OfficialPhoto :src="photo" :name="deputy.name" />
        <div class="ma-hero__text">
          <p class="ma-t-micro ma-eyebrow">Deputado federal · {{ deputy.party }} · {{ deputy.uf }}<template v-if="deputy.inExercise"> · em exercício</template></p>
          <h1 class="ma-hero__name ma-t-display-2">{{ deputy.name }}</h1>
        </div>
      </section>

      <p v-if="deputy.participation.total > 0" class="ma-lede ma-t-title-3">
        Registrou voto em <span class="ma-num">{{ formatNumber(deputy.participation.count) }}</span> de
        <span class="ma-num">{{ formatNumber(deputy.participation.total) }}</span> votações nominais do plenário desde
        <span class="ma-num">{{ formatDate(deputy.since) }}</span>.<a class="ma-note-ref" href="#nota-1" aria-describedby="nota-1"><sup class="ma-num">1</sup></a>
      </p>
      <p v-else class="ma-lede ma-t-title-3 ma-empty">Votações nominais do plenário: {{ NO_BASE }}.<a class="ma-note-ref" href="#nota-1" aria-describedby="nota-1"><sup class="ma-num">1</sup></a></p>

      <section class="ma-section" aria-label="Indicadores">
        <div class="ma-indicators">
          <NDeM v-for="i in indicators" :key="i.note.index" v-bind="i" />
        </div>
        <div class="ma-stats">
          <div class="ma-stat">
            <span class="ma-t-small ma-muted">Proposições de autoria</span>
            <span class="ma-t-title-1 ma-num">{{ formatNumber(deputy.authoredCount) }}</span>
          </div>
          <div class="ma-stat">
            <span class="ma-t-small ma-muted">Como primeiro signatário</span>
            <span class="ma-t-title-1 ma-num">{{ formatNumber(deputy.firstSignerCount) }}</span>
          </div>
          <div class="ma-stat">
            <span class="ma-t-small ma-muted">Requerimentos</span>
            <span class="ma-t-title-1 ma-num">{{ formatNumber(deputy.requirementsCount) }}</span>
          </div>
        </div>
        <SourceNote :index="indicators.length + 1" :source-url="deputy.sourceUrl" :method-url="`${methodBase}#proposicoes`" />
      </section>

      <section class="ma-section">
        <div class="ma-section__head">
          <h2 class="ma-t-title-2">Votações do mandato</h2>
          <p class="ma-t-small ma-muted">
            Cada traço é uma votação nominal com registro deste deputado, da mais antiga para a mais recente. Sim fica
            acima da linha, Não abaixo; as demais opções têm marca própria. Cada traço leva à votação.
          </p>
        </div>
        <MandateScore :votes="votes" />
      </section>
    </main>
  </Chrome>
</template>
