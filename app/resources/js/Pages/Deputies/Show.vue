<script setup>
// The deputy profile: design/screens/Profile.vue drawn from the database.
import MandateScore from "mandato-design/components/MandateScore.vue";
import NDeM from "mandato-design/components/NDeM.vue";
import OfficialPhoto from "mandato-design/components/OfficialPhoto.vue";
import SourceNote from "mandato-design/components/SourceNote.vue";
import { NO_BASE, formatNumber } from "mandato-design/components/format.js";
import PublicLayout from "../../Components/PublicLayout.vue";

defineProps({
  deputy: { type: Object, required: true },
  indicators: { type: Array, required: true },
  votes: { type: Array, required: true },
  methodBase: { type: String, required: true },
  collectedAt: { type: String, default: null },
});
</script>

<template>
  <PublicLayout>
    <main class="ma-wrap">
      <section class="ma-hero">
        <!-- No src: the official photo is served from our origin only once a photo cache exists (plan, open question 2). -->
        <OfficialPhoto :name="deputy.name" />
        <div class="ma-hero__text">
          <p class="ma-t-micro ma-eyebrow">Deputado federal · {{ deputy.party }} · {{ deputy.uf }}</p>
          <h1 class="ma-hero__name ma-t-display-2">{{ deputy.name }}</h1>
        </div>
      </section>

      <p v-if="indicators[0].total > 0" class="ma-lede ma-t-title-3">
        Registrou voto em <span class="ma-num">{{ formatNumber(indicators[0].count) }}</span> de
        <span class="ma-num">{{ formatNumber(indicators[0].total) }}</span> votações nominais do plenário.<a class="ma-note-ref" href="#nota-1" aria-describedby="nota-1"><sup class="ma-num">1</sup></a>
      </p>
      <p v-else class="ma-lede ma-t-title-3 ma-empty">Votações nominais do plenário: {{ NO_BASE }}.<a class="ma-note-ref" href="#nota-1" aria-describedby="nota-1"><sup class="ma-num">1</sup></a></p>

      <section class="ma-section" aria-label="Indicadores">
        <div class="ma-indicators">
          <NDeM v-for="i in indicators" :key="i.note.index" v-bind="i" />
        </div>
        <div class="ma-stats">
          <div class="ma-stat">
            <span class="ma-t-small ma-muted">Proposições de autoria</span>
            <span class="ma-t-title-1 ma-num">{{ formatNumber(deputy.authoredCount) }}<a class="ma-note-ref" :href="`#nota-${indicators.length + 1}`" :aria-describedby="`nota-${indicators.length + 1}`"><sup class="ma-num">{{ indicators.length + 1 }}</sup></a></span>
          </div>
          <div class="ma-stat">
            <span class="ma-t-small ma-muted">Como primeiro signatário</span>
            <span class="ma-t-title-1 ma-num">{{ formatNumber(deputy.firstSignerCount) }}<a class="ma-note-ref" :href="`#nota-${indicators.length + 1}`" :aria-describedby="`nota-${indicators.length + 1}`"><sup class="ma-num">{{ indicators.length + 1 }}</sup></a></span>
          </div>
          <div class="ma-stat">
            <span class="ma-t-small ma-muted">Requerimentos</span>
            <span class="ma-t-title-1 ma-num">{{ formatNumber(deputy.requirementsCount) }}<a class="ma-note-ref" :href="`#nota-${indicators.length + 1}`" :aria-describedby="`nota-${indicators.length + 1}`"><sup class="ma-num">{{ indicators.length + 1 }}</sup></a></span>
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
        <SourceNote
          :index="indicators.length + 2"
          :source-url="deputy.sourceUrl"
          :method-url="`${methodBase}#participacao`"
          :collected-at="collectedAt ?? ''"
        />
      </section>
    </main>
  </PublicLayout>
</template>
