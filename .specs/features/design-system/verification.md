# design-system verification

**Verdict**: FAIL
**Profile**: ui
**Diff range**: c9b1a4a..83f5eb606217a4fcb94901a8f714ef53c1ae1671
**Round**: 1 - full
**Verifier**: independent sub-agent (author != verifier)

Verified at 83f5eb6. Proofs: `npm run build` exit 0; `npx vitest run tests/ --reporter=verbose` 24 passed, 0 failed, each named test listed individually; `npx playwright test --reporter=list` 7 passed, 0 failed. FAIL comes from: C27 open (expected), C15 and C12 with unproven clauses (surviving mutants), a C4 boundary survivor, unproven coverage members, two unmet Test policy rows, and elements and arrangements the binding sources decide that no check covers. Two of the uncovered items are real defects shown on the rendered output (empty base on the profile lede and the card; numbers without tabular figures).

## Binding sources

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `research/05-grilling-escopo-v2.md` decision 10 (lines 12-25) | yes - read in full | none: plan builds tokens, type, components and three screens (profile, roll-call, card) before Laravel | publishing in an artifact for validation (decision 10 "validadas em artifact") is plan Flow step 5 with no check; only C27 waits for the choice - process step, low rank |
| `research/06-pesquisa-design-e-concorrentes.md` section 3 (principles 1-15) | yes - lines 38-58 | none in the checks | P1 "todo número carrega a sua nota": C11 narrows it to `NDeM`; profile lede numbers (`screens/Profile.vue:33-37`) and stat figures (`Profile.vue:44-56`, one shared note with no marker) and the roll-call tally/result (`screens/RollCall.vue:31-38`) carry no note and no check · P11 "tabela, fontes e download": download absent, no check, not in Out of scope · P2 "fonte dentro do enquadramento": the partitura section (`Profile.vue:60-69`) has no source footer inside its frame, no check · P4 "algarismos tabulares em todo número": C7 covers three components only; `screens/Card.vue:34` and `components/MandateScore.vue:64` render counts without `.ma-num` (computed `font-variant-numeric: normal` in Chromium, measured) |
| `research/06-...` section 4 (traps) | yes - lines 60-71 | none in the checks | "Parecer site oficial. Nada de azul gov.br": no check; Plenário accent `oklch(0.47 0.21 264)` = `#184ace` (`tokens/plenario.json:270`) sits next to gov.br blues `#1351B4` (oklch 0.460 0.169 260.1) and `#155BCB` (oklch 0.500 0.187 260.2), measured with the repo's own `toHex` and an independent sRGB->OKLab conversion |
| `research/06-...` section 5 (directions) | yes - lines 73-83 | none: Diário = Newsreader + Inter with the partitura, Plenário = Archivo + Source Serif 4, MVP red dropped - matches plan Assumptions | - |
| `research/design-anexos/a1-referencias-de-design.md` section 3 (P1-P15 full) | yes - lines 162-195 | none in the checks | P9 "no modo escuro, o passe-partout fica claro": no check, and the code darkens it - `.ma-photo__mat` uses `--ma-color-raised` (`styles/components.css:258-259`), computed `oklch(0.245 0.008 70)` Diário dark and `oklch(0.2 0 0)` Plenário dark · P5 "Perfil: barra de unidades em que cada célula é uma votação": not drawn on the profile, no check, not in Out of scope · P7 accent lights "apenas o deputado buscado": C5 counts accent tokens only; where the accent is used is unchecked |
| `a1` section 4 (traps table) | yes - lines 198-219 | none | "Nunca no card": comparison, percentile, own colour, AI sentence, share counts - no check asserts their absence on `card.html` (C19 catches only vocabulary) |
| `a1` section 5 (three directions) | yes - lines 221-257 | none | - |
| `a1` section 6.1 profile hero | yes - lines 261-267 | none | **Arrangement:** photo left / name right / metadata line / template sentence / n de m / partitura full width below - no check on the hero's regions or their order (C24 only measures overflow; C22 only reads one count). Measured at 1280: photo at x 124-278, name at x 337, so the code follows the source but nothing guards it · partitura drawn as one row per year with year labels (`MandateScore.vue:18-27`) where the source draws one full-width strip with a month axis - a different composition with no check either way · hover/focus "data, proposição e voto" (`MandateScore.vue:53` `<title>`) unchecked · "citar este perfil" absent, no check, not excluded |
| `a1` section 6.2 roll-call page | yes - lines 269-276 | none in the checks | **The screen's main region has no check:** the list of every deputy grouped by vote option, alphabetical within each group, with per-row label "Nome, PARTIDO-UF, votou X" (`RollCall.vue:49-67`, `scripts/contract.mjs:82-90`) - C12 tests `VoteMark` alone; no proof reads the groups, their order, their counts or completeness on `roll-call.html` · ementa at top, AI frame placement, result line - unchecked · party orientation table ("seguiram 45 de 48") absent, no check, not excluded · utilities "Baixar CSV/JSON", "Citar" absent; "Link permanente" and source link present but unchecked · P1 "placar com nota para a página oficial da votação": no note · quorum marker: excluded in plan Out of scope (fine) |
| `a1` section 6.3 share card | yes - lines 278-285 | none in the checks | card content (photo, name, partido-UF, up to four n de m, mini partitura, "Fonte: Câmara dos Deputados · dados de DD/MM/AAAA", short address) - no check reads any of it except the name (C20, C25); the footer prints "mandato aberto" (`Card.vue:38`) where the source names the short address (Handoff records the choice; no check) · three formats and verification code: excluded in Out of scope (fine) |
| `etl/schema/*.json` (contract) | yes - `deputy`, `roll-call`, `meta` read; every field the prototype reads exists in the schema | none | `participation/governmentAlignment/partyAlignment.total` may be 0 (fixture deputy 103); the card (`Card.vue:28-31`) and the profile lede (`Profile.vue:33-37`) render "0 de 0" instead of `NO_BASE` - reproduced with `--deputy 103` (lede "Registrou voto em 0 de 0 votações nominais", card "0 de 0 votações nominais ..."). AC 8 holds only inside `NDeM`; the plan's Observable row "screen profile · empty state -> AC 8" is not met by the screen. On 01/02/2027 every deputy starts at total 0 |
| `site/src/lib/format.ts` (MVP copy) | yes - lines 1-80 | none: `NO_BASE`, `voteLabel` cases, `resultLabel`, `byName` mirrored in `components/format.js`, `components/vote.js:6-18`, `scripts/contract.mjs:23-24,91` | the MVP's `formatCount` pairs every count with `NO_BASE` at total 0; the design screens bypass it (row above) |

Per-screen enumeration summary (what a selector reaches -> check):

- **profile**: photo C14 · no-photo C15 · n de m C9/C10/C11 · partitura order/links/table C13 · 360 px C24 · motion C23 · theme C26 · no JS C22 — uncovered: hero arrangement (regions and order), partitura row-per-year arrangement and missing month axis, lede/stat notes, unit bar, download, cite link, dark mat, empty base in the lede.
- **roll-call**: tally C16 (component only) · AI frame C17/C18 (component only) · 360 px C24 · motion C23 — uncovered: the grouped name list (order, grouping, counts, completeness), header order (eyebrow, title, ementa), result line, utilities, tally note, orientation table, empty roll call (Observable row claims AC 8 but the screen uses no `NDeM`), theme (C26 proves profile only).
- **card**: 1200 × 630 and no overflow C25 · photo C14 · motion C23 — uncovered: content set and order, "never on card" absences, empty base ("0 de 0"), non-tabular count `Card.vue:34`, theme (C26 proves profile only).

## Checks

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | four blocks, every token as `--ma-*` | `npx vitest run tests/` - "defines every token in four blocks" ✓ | `design/tests/tokens.test.ts:36` `expect(root).toContain(\`--ma-${path.join("-")}:\`)`; `:44` `expect(light).toContain(v)`; `:47` `expect(dark.split(v).length - 1).toBe(2)` | PASS |
| C2 | every declared pair meets its floor, WCAG from OKLCH | same run - "every declared pair meets its floor" ✓ | `tokens.test.ts:57` `expect(p.ratio, ...).toBeGreaterThanOrEqual(p.min)`; `:58` `expect([4.5, 3]).toContain(p.min)`. OKLab matrices in `scripts/color.mjs:133-140` checked against Ottosson's published values; 24 pairs recomputed (lowest 6.06:1) | PASS |
| C3 | every text token paired with every surface | same run - "every text token is paired with every surface" ✓ | `tokens.test.ts:75` `expect(actual.sort()).toEqual(expected.sort())` | PASS |
| C4 | pair below floor -> non-zero exit, pair and ratio with two decimals | same run - "fails the build on a pair below its floor" ✓ | `tokens.test.ts:89` `expect(run.status).not.toBe(0)`; `:90` `toMatch(/diario dark muted on paper: \d+\.\d{2}:1 is below 4\.5:1/)`. Precision gap: the injected pair is 1.33:1, so a floor shifted by up to 3.17 survives (fault F1) | PASS |
| C5 | one accent, no valence or party names | same run - "one accent and no valence names" ✓ | `tokens.test.ts:99` `toHaveLength(1)`; `:100` `expect(n.split("-")).not.toContain(word)`. Precision gap: party list read from the 3-deputy fixture, not the contract; actual names (paper, raised, ink, muted, accent, rule) checked by hand against it | PASS |
| C6 | at most 10 type sizes | same run - "at most ten type sizes" ✓ | `tokens.test.ts:108` `expect(sizes.length).toBeLessThanOrEqual(10)` (8 per direction) | PASS |
| C7 | `.ma-num` sets tabular lining; numbers of NDeM, TallyBar, MandateScore table inside it | same run - "numeric class sets tabular lining figures" ✓, "numbers carry the numeric class" ✓ | `tokens.test.ts:116` `toMatch(/font-variant-numeric:\s*tabular-nums lining-nums/)`; `components.test.ts:61` `expect(el.closest(".ma-num"), el.outerHTML).not.toBeNull()`. As written PASS; AC 5 "every style used for a number" is wider - see Coverage | PASS |
| C8 | fonts local OFL files, no third-party host | same run - "fonts are local OFL files" ✓; playwright "no third-party request" ✓ | `prototype.test.ts:83` `toMatch(/^\.\/files\/[\w.-]+\.woff2$/)`; `:87-88` licence files `toContain("SIL Open Font License")`; `e2e/screens.spec.ts:20` `expect([...hosts]).toEqual([new URL(baseURL!).host])` | PASS |
| C9 | 412 in display el., "de 450" in body el., computed `align-items: baseline`, no % | vitest "NDeM renders n de m" ✓; playwright "n de m shares a baseline" ✓ | `components.test.ts:27` `.toBe("412")`; `:28` `.toBe("de 450")`; `:30` `not.toContain("%")`; `e2e/screens.spec.ts:29` `.toBe("baseline")`. Precision gap: "display" vs "body" style is a class name, no size asserted | PASS |
| C10 | total 0 -> NO_BASE, no digit | vitest "NDeM with an empty base" ✓ | `components.test.ts:36` `toContain("Sem base de cálculo no período")`; `:38` `not.toMatch(/\d/)` (note index excluded, as Handoff records) | PASS |
| C11 | NDeM marker -> note with sourceUrl and method anchor | vitest "NDeM carries its source note" ✓ | `components.test.ts:47` `expect(links).toEqual([note.sourceUrl, note.methodUrl])` | PASS |
| C12 | seven vote cases: shape, position, label; only currentColor | vitest "VoteMark encodes every vote case" ✓ | `components.test.ts:108` class per kind; `:110` `aria-label` per case; `:115` fill/stroke in `["currentColor","none"]`; `:69`, `:74`, `:80-81`, `:90` positions for yes, no, abstention, article-17. **Obstruction position is never asserted** (`:84-87` checks only fill none and a path) - fault F2 moved it above the baseline and the test passed | FAIL |
| C13 | chronological, each column -> `/votacoes/<id>/`, table date/proposition/vote | vitest "MandateScore is chronological and linked" ✓ | `components.test.ts:127` hrefs `toEqual(expected.map(...))`; `:131-136` rows; `:138` table links | PASS |
| C14 | photo 3:4, ≤ 354 × 472, no filter/blend/transform, fit ≠ cover, credit | playwright "official photo is untouched" ✓ | `e2e/screens.spec.ts:43-45` box and ratio; `:50-53` `filter "none"`, `blend "normal"`, `transform "none"`, `fit not "cover"`; `:54` `toHaveText("Foto: Câmara dos Deputados")` | PASS |
| C15 | no photo -> initials "LB" in a frame **of the same size**, no `<img>` | vitest "OfficialPhoto without a photo" ✓ | `components.test.ts:143` `img` null; `:145` `toBe("LB")`; `:146` inside `.ma-photo__mat`. "Same size" has no assertion and no browser proof (level gap) - fault F5 made the frame square and both suites stayed green | FAIL |
| C16 | 19 cells yes, no, others; counts as text | vitest "TallyBar renders one cell per vote" ✓ | `components.test.ts:152` cells `toEqual([...12 yes, ...5 no, ...2 others])`; `:153` `toEqual(["12","5","2"])` | PASS |
| C17 | label with 30/09/2026, official and report links | vitest "AiSummaryFrame is labelled" ✓ | `components.test.ts:164` `toBe("Resumo gerado por IA a partir do texto oficial, revisado em 30/09/2026")`; `:166-169` links | PASS |
| C18 | no `reviewedAt` -> no element | vitest "AiSummaryFrame without review renders nothing" ✓ | `components.test.ts:174` `toBe("")` | PASS |
| C19 | no forbidden term in dist/prototype or component source | vitest "no forbidden term in rendered output" ✓ | `prototype.test.ts:104` `expect(hits).toEqual([])`; `:106` self-check. Precision gap: rendered pages are scanned as body `textContent` (attributes and `<head>` skipped); verifier scanned raw HTML of `dist/prototype/*/*.html` and `dist/e2e/*/*.html`: 0 hits | PASS |
| C20 | three screens per direction, naming the chosen deputy and roll call | vitest "writes three screens per direction" ✓ | `prototype.test.ts:47` file list; `:48-49` h1 `toBe(deputy.name)`; `:51-52` roll-call title and anchor | PASS |
| C21 | schema_version 1 -> non-zero, "schema_version 1" on stderr | vitest "rejects another schema version" ✓ | `prototype.test.ts:62` `not.toBe(0)`; `:63` `toContain("schema_version 1")`; `:64` nothing written | PASS |
| C22 | no `<script>`, participation count/total in HTML | vitest "screens read without javascript" ✓ | `prototype.test.ts:70` `not.toMatch(/<script/i)`; `:73-74` count and "de total" | PASS |
| C23 | reduced motion -> 0s on every element of six pages | playwright "reduced motion" ✓ | `e2e/screens.spec.ts:72` `expect(moving, ...).toEqual([])` | PASS |
| C24 | 360 px: profile and roll-call scrollWidth ≤ 360, both directions, both themes | playwright "no horizontal scroll at 360" ✓ | `e2e/screens.spec.ts:85` `toBeLessThanOrEqual(360)` | PASS |
| C25 | card 1200 × 630 with the longest name, nothing overflows | playwright "card fits the longest name" ✓ | `e2e/screens.spec.ts:94` name; `:97` `toEqual([1200, 630])`; `:110` `overflowing toEqual([])`. Longest in-exercise name in `data/out/deputies.json` confirmed as this one (35 chars, 643 records) | PASS |
| C26 | dark -> dark `paper`, light -> light `paper` | playwright "theme follows the system" ✓ | `e2e/screens.spec.ts:128` `expect(body, ...).toBe(probe)` - profile only; see Coverage | PASS |
| C27 | tokens hold base + chosen; AD-015 in STATE.md | `ls tokens` -> `base.json diario.json plenario.json`; `grep -c '^\| AD-015 \|' ../.specs/STATE.md` -> 0, exit 1 | no evidence - open by design, waits for the maintainer to choose diario or plenario (blocks go-live per checks.md) | FAIL |
| C28 | README section per component with inputs/empty state/principle; package name, private, exports | vitest "readme documents every component" ✓, "package exports tokens and components" ✓ | `package.test.ts:15` section defined; `:16-18` Inputs / Empty-or-Missing state / Principle; `:24-27` name, private, two exports; `:29` component files equal door 4 | PASS |

## Coverage

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| token blocks (4) | `dist/tokens.css` markers | diario light/dark, plenario light/dark -> C1 | - |
| declared contrast pairs (24) | `pairs()` over the token files, ratios recomputed by verifier | 24 text pairs -> C2; no graphic or focus tokens exist (focus uses `accent`, role text, 4.5) | - |
| vote cases, doors 5 and 6 (7 + unknown) | contract data `data/out/roll-calls/*` distinct values: Sim, Não, Artigo 17, Abstenção, Obstrução, "" (all 887 on secret ballots) + non-secret empty from door 6 | kind and label all 8 -> C12; position: yes, no, abstention, article-17 -> C12 | obstruction position, unknown-value position |
| components, door 4 (7) | plan Landing door 4 | 7 -> C9, C11, C12, C13, C14, C16, C17 | - |
| component empty states (7) | `design/README.md` (the AC 26 document declares one per component) | NDeM C10 · OfficialPhoto C15 (size unproven) · AiSummaryFrame C18 · VoteMark gap C12 | SourceNote without `methodUrl`, MandateScore with no votes, TallyBar with zero counts, OfficialPhoto frame size |
| screen empty states (3) | plan Observable rows | none | profile (lede renders "0 de 0", `Profile.vue:33-37`), roll-call with no recorded votes (Observable maps it to AC 8 but the screen has no `NDeM`), card ("0 de 0", `Card.vue:28-31`) |
| pages at 360 px (8) | 2 screens × 2 directions × 2 themes | all 8 -> C24 (loop at `e2e/screens.spec.ts:78-86`) | - |
| pages under reduced motion (6) | 3 screens × 2 directions | all 6 -> C23 | - |
| pages under theme (12) | AC 24 applies to every screen: 3 screens × 2 directions × 2 themes; no row in checks.md | profile × 2 × 2 -> C26 | roll-call × 2 directions × 2 themes, card × 2 directions × 2 themes (8) |
| numbers with tabular figures (AC 5) | every digit-bearing count on the six pages, measured in Chromium | NDeM, TallyBar, MandateScore table -> C7 | `Card.vue:34` "N votações nominais com registro", `MandateScore.vue:64` "Ver as N votações como tabela" (computed `normal`); lede, stats and group counts carry `.ma-num` but no proof |
| contract `schema_version` (2) | `contract.mjs:18` | 2 accepted C20 · other rejected C21 | - |
| Landing doors (6) | plan Landing | 1 C28 · 2 C1 · 3 C14, C23-C26 · 4 C28 · 5 C12 · 6 C12 | door 5 obstruction "on the baseline" (see vote cases) |

## Test policy rows

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Token build | `scripts/build-tokens.mjs`, `scripts/color.mjs`, `tokens/*.json` | own layer: `tests/tokens.test.ts` | partly - every declared pair yes (C2); one failing case yes, but it sits at 1.33:1 so the floor boundary is untested (F1 survived) -> no |
| Vote option mapping | `components/vote.js` | own layer: C12 | no - "one asserted case per row": obstruction row asserts no position (F2 survived), unknown-value row asserts no position |
| Components | `components/*.vue` | one render test per component, each empty state and normal case | no - SourceNote has no own render test and its empty state is untested; MandateScore and TallyBar empty states (declared in README) untested; OfficialPhoto empty state size unproven (F5 survived) |
| Layout and computed style | `styles/components.css`, `screens/*.vue` | one browser test, every member listed in Coverage | yes for the members checks.md lists (360 px, reduced motion); the theme set it never listed is reported under Coverage |
| Prototype script | `scripts/prototype.mjs`, `scripts/contract.mjs` | own layer, child process, accepted and rejected version | yes - `prototype.test.ts:22-27` spawns the script; C20 accepted with defaults, C21 rejected |

Swept rows resolving to existing, read in code: C3/C4 build exit `scripts/build-tokens.mjs:110-112`; C21 `scripts/contract.mjs:18-19` and `scripts/prototype.mjs:88-90`; C15 initials branch `components/OfficialPhoto.vue:16`; C8 local fonts `scripts/prototype.mjs:24-40`; C27 transition not yet happened. All present.

## Faults injected

Scratch: `git worktree add --detach <scratchpad>/wt HEAD`, own `npm ci`. Real tree `git status --porcelain` empty before the first worktree and empty after it was removed; the session dropped after F4, the leftover worktree was removed with `git worktree remove --force`, recreated at the same HEAD for F5, then removed; porcelain empty before and after, HEAD 83f5eb6 throughout.

| Mutation | Location | Killed |
| --- | --- | --- |
| F1 floor check `p.ratio < p.min` -> `p.ratio < p.min - 2` (build accepts text pairs down to 2.5:1) | `design/scripts/build-tokens.mjs:92`; ran "fails the build on a pair below its floor" | no - injected pair is 1.33:1 |
| F2 obstruction mark moved above the baseline (rect y 9.5 -> 1.5, hatch path shifted) | `design/components/vote.js:39-40`; ran "VoteMark encodes every vote case" | no |
| F3 MandateScore order reversed (`? -1 : 1` -> `? 1 : -1`) | `design/components/MandateScore.vue:16`; ran "MandateScore is chronological and linked" | yes - hrefs `toEqual` failed |
| F4 photo `object-fit: contain` -> `cover` | `design/styles/components.css:269`; ran playwright "official photo is untouched" | yes - `Expected: not "cover"` |
| F5 initials frame `aspect-ratio: 3 / 4` -> `1 / 1` | `design/styles/components.css` `.ma-photo__initials`; ran "OfficialPhoto without a photo" and the full playwright suite | no - 1 passed unit, 7 passed browser |

## Gate

`npm run build` exit 0 · `npx vitest run tests/ --reporter=verbose` - 24 passed, 0 failed · `npx playwright test --reporter=list` - 7 passed, 0 failed · `python3 /home/augusto/.claude/skills/tlc-spec-lean/scripts/validate_verification.py design-system` - exit 1: "verdict is FAIL - route the ranked gaps back as fixes, then re-verify" (1 error, 0 warnings; no row contradicts the verdict)
