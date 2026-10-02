# design-system verification

**Verdict**: FAIL
**Profile**: ui
**Diff range**: c9b1a4a..1b079cf3c458d761f5f5513be2d9110e42de1f88
**Round**: 3 - scoped
**Verifier**: independent sub-agent (author != verifier)

**Scope.** The fix `d9d3b90..1b079cf` touched 14 files:

- `ci.yml`
- `plan.md` (AC 37-41 and Assumptions)
- `checks.md` (C39-C43)
- `README.md`
- `SourceNote.vue`
- the three screens
- `contract.mjs`
- `components.css`
- `e2e/screens.spec.ts`
- `prototype.test.ts`
- `tokens.test.ts`

Round 3 also re-judges every round-2 verdict that was not PASS: C27, the roll-call and card arrangements, the proof environments, P1 stats, P2 score note, P7, the empty `exercisePeriods` fallback and the missing score-layout assumption. Round 2 is committed at 75c188f.

**Proofs re-ran in full at 1b079cf:**

- `npm run build`: exit 0.
- `npx vitest run tests/ --reporter=verbose`: 39 passed, 0 failed, each named test listed individually.
- `npx playwright test --reporter=list`: 13 passed, 0 failed.
- The same three commands in a fresh CI-equivalent worktree, with `npm ci` in `design/` and `npm ci --prefix ../site`: 39 passed and 13 passed.

**Verdict.** Every code gap from round 2 is closed and every fault was killed. The verdict stays FAIL for two reasons:

- C27 is open by design.
- Three plan rows are recorded but not confirmed by the maintainer. One of them is a recorded departure from a binding source (score layout versus a1 6.1). They are listed below as open questions, not passes.

Nothing else blocks.

## Binding sources

Verified at 1b079cf for every screen and plan row the fix touched. The remaining rows are carried from d9d3b90.

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `research/05-grilling-escopo-v2.md` decision 10 | carried from d9d3b90 - yes | none | Publishing the prototype as an artifact for validation (plan Flow step 5) has no check. This is a process step that goes with C27's choice. Low rank. |
| `06` section 3 / a1 section 3, P1 "todo número carrega a sua nota" | yes | none | **Resolved.** The three stats now each carry a marker to note 4, linking `sourceUrl` (C41, `design/tests/prototype.test.ts:174-178`). The lede and tally were already covered in round 2 (C36). |
| P2 "a fonte fica dentro do enquadramento" | yes | none | **Resolved.** The score section ends with a `SourceNote` carrying the source, "dados de DD/MM/AAAA" and the method link (`design/screens/Profile.vue:70-75`, C41). |
| P7 accent single role | yes | none | **Resolved.** C43. The four rules using `--ma-color-accent` are `.ma-page a`, `.ma-page :focus-visible`, `.ma-score__col:focus-visible` and `.ma-score__table summary` (`design/styles/components.css:95-375`). |
| a1 section 6.1 profile, score layout | yes - plan Assumptions row `plan.md:167` read | **Open question for the maintainer, not settled.** The plan now records "one row per year ... instead of the single full-width strip of research a1 section 6.1". The rationale is about 1 px per vote at 1120 px. The row is marked Confirmed `n`. A recorded departure from a binding source that the maintainer has not accepted is a decision still pending, so it does not pass silently. | Month ticks are covered by C37. |
| a1 section 6.2 roll-call | yes | none | **Resolved.** C39 checks the regions eyebrow, title, ementa when present, AI frame, result, utilities, groups, strictly top to bottom (`e2e/screens.spec.ts:220-224`). The fix moved the AI frame above the result as the source orders it (`design/screens/RollCall.vue:32-34`). |
| a1 section 6.3 card | yes | none | **Resolved.** C40 checks the photo left of the body; then eyebrow, name, party-UF, figures, score caption, score and footer top to bottom; 3 figures; and the footer "Fonte: Câmara dos Deputados, dados de DD/MM/AAAA" (`e2e/screens.spec.ts:233-239`). |
| Plan Out of scope rows added in round 2 (download, citar, per-party orientation, unit bar, card short address) | yes - `plan.md:146-149` and Assumptions `plan.md:168` | **Open question for the maintainer.** The plan's own row says the builder wrote them after the plan review, and they are Confirmed `n`. Each excludes an element a1 6.1-6.3 draws. They are reasoned, and the per-party orientation reason was verified against `etl/schema/roll-call.schema.json:52-57`, but they are policy only the maintainer can accept. | - |
| Plan Assumptions row "Which pages AC 22 covers" (`plan.md:166`) | yes | **Open question for the maintainer** (Confirmed `n`). It exempts the card from the 360 px rule. C24 already measured only profile and roll-call, so this records an existing scope rather than changing one. | - |
| `etl/schema/*.json`, empty `exercisePeriods` | yes | none | **Resolved.** The `?? null` fallback is at `design/scripts/contract.mjs:54`, the `since` guards are at `Card.vue:26` and `Profile.vue:35-36`, and C42 covers it. |
| `site/src/lib/format.ts` | yes | none | Precision note. The new collection dates (`SourceNote.vue` `formatDate(collectedAt)`, and the card footer) take the UTC date of `generatedAt`. The MVP's `collectedDate` converts to Brasília time first (`site/src/lib/format.ts`, `brasiliaParts`). The two agree for every `generatedAt` after 03:00 UTC: the fixture is at 12:00Z, `data/out` at 18:02Z. A run between 00:00 and 03:00 UTC would show the next day. Low rank. |
| `research/06` section 4, a1 section 4, directions | carried from d9d3b90 - yes | none | - |

Plan hygiene, low rank:

- `plan.md:170` still reads "Open questions: none - all resolved or logged above" while three rows are Confirmed `n`.
- Five older Assumptions rows (`plan.md:160-164`) keep the malformed `|| y |` cell, which renders their Confirmed column empty. The round-3 Handoff says this was repaired.

## Checks

Rows C1-C38: evidence carried from d9d3b90, because the fix did not touch their assertions. All of their proofs re-ran green at 1b079cf. C11 and C41 share the `SourceNote` the fix extended, and both are green. Rows C39-C43 were verified at 1b079cf.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | four blocks, every token | vitest ✓ | `design/tests/tokens.test.ts:36`, `:44`, `:47` (carried) | PASS |
| C2 | pairs meet floors | vitest ✓ | `tokens.test.ts:57-58` (carried) | PASS |
| C3 | text × surface complete | vitest ✓ | `tokens.test.ts:75` (carried) | PASS |
| C4 | below-floor pair fails the build | vitest ✓ | `tokens.test.ts:91-92` (carried) | PASS |
| C5 | one accent, no valence/party names | vitest ✓ | `tokens.test.ts:99-103` (carried) | PASS |
| C6 | ≤ 10 type sizes | vitest ✓ | carried | PASS |
| C7 | tabular figures | vitest ✓ ×2; playwright ✓ | `components.test.ts:61`; `e2e/screens.spec.ts:210` (carried) | PASS |
| C8 | local OFL fonts, no third-party host | vitest ✓; playwright ✓ | carried; green in the CI-equivalent tree too | PASS |
| C9 | n de m baseline and sizes | vitest ✓; playwright ✓ | `components.test.ts:33`; `e2e/screens.spec.ts:35` (carried) | PASS |
| C10 | NDeM empty base | vitest ✓ | carried | PASS |
| C11 | NDeM and SourceNote links | vitest ✓ ×3 | `components.test.ts:155-158`, `:163-164` (carried; `collectedAt` is optional, so these stay unchanged) | PASS |
| C12 | vote cases | vitest ✓ | `components.test.ts:90-94`, `:110` (carried) | PASS |
| C13 | score order, links, no votes | vitest ✓ ×2 | carried | PASS |
| C14 | official photo untouched | playwright ✓ | carried | PASS |
| C15 | initials frame same size | vitest ✓; playwright ✓ | `e2e/screens.spec.ts:147-152` (carried) | PASS |
| C16 | tally cells, zero counts | vitest ✓ ×2 | carried | PASS |
| C17 | AI frame labelled | vitest ✓ | carried | PASS |
| C18 | unreviewed AI frame renders nothing | vitest ✓ | carried | PASS |
| C19 | no forbidden term | vitest ✓ | carried; also green in the CI-equivalent tree | PASS |
| C20 | three screens per direction | vitest ✓ | carried | PASS |
| C21 | schema_version 1 rejected | vitest ✓ | carried | PASS |
| C22 | no script, count in HTML | vitest ✓ | carried | PASS |
| C23 | reduced motion | playwright ✓ | carried | PASS |
| C24 | no scroll at 360 (profile, roll-call) | playwright ✓ | carried | PASS |
| C25 | card 1200 × 630, longest name | playwright ✓ | carried | PASS |
| C26 | theme on three screens | playwright ✓ | `e2e/screens.spec.ts:133` (carried) | PASS |
| C27 | tokens hold base + chosen; AD-015 | `ls tokens` -> `base.json diario.json plenario.json`; no `\| AD-015 \|` row in `.specs/STATE.md` | No evidence. Open by design: waits for the maintainer to choose diario or plenario. | FAIL |
| C28 | README and package exports | vitest ✓ ×2 | carried; README now lists `collectedAt` | PASS |
| C29 | empty bases never read 0 de 0 | vitest ✓ | `prototype.test.ts:85-93` (carried) | PASS |
| C30 | roll-call groups | vitest ✓ | `prototype.test.ts:124-131` (carried) | PASS |
| C31 | roll call without votes | vitest ✓ | carried | PASS |
| C32 | profile arrangement | playwright ✓ | `e2e/screens.spec.ts:166-173` (carried) | PASS |
| C33 | mat light in dark | vitest ✓; playwright ✓ | carried | PASS |
| C34 | accents away from gov.br blues | vitest ✓ | `tokens.test.ts:134`; the matrix coefficient is now the published `0.808675766` (`:120`), which closes the round-2 nit | PASS |
| C35 | every digit tabular | playwright ✓ | carried | PASS |
| C36 | lede and tally notes | vitest ✓ | carried | PASS |
| C37 | month ticks | vitest ✓ | carried | PASS |
| C38 | one card template | vitest ✓ | `prototype.test.ts` card test (carried). The fix's conditional "desde" adds a text node, not an element, so the class sequence is unchanged. | PASS |
| C39 | roll-call regions top to bottom | playwright "roll-call arrangement" ✓ | `e2e/screens.spec.ts:220` each region visible; `:223` `expect(tops, d).toEqual([...tops].sort(...))`; `:224` all tops distinct. Fault G1 was killed. | PASS |
| C40 | card composition and footer | playwright "card composition" ✓ | `e2e/screens.spec.ts:233` photo left of body; `:236` region order; `:237` 3 figures; `:239` `toContainText(/Fonte: Câmara dos Deputados, dados de \d{2}\/\d{2}\/\d{4}/)`. Fault G2 was killed. | PASS |
| C41 | score note with source and date; stat markers | vitest "score and stats carry source notes" ✓ | `prototype.test.ts:171` `toContain(\`dados de ${d}/${m}/${y}\`)`; `:172` `toBe(DEPUTY.sourceUrl)`; `:174` 3 stats; `:178` each marker target links `DEPUTY.sourceUrl`. Fault G4 was killed. | PASS |
| C42 | no "desde" without an exercise period | vitest ✓ | `prototype.test.ts:193` `not.toMatch(/desde \d/)` on profile `main` and `.ma-card`, both directions. Fault G3 was killed. | PASS |
| C43 | accent only in interaction rules | vitest "accent marks only interaction" ✓ | `tokens.test.ts:154` each selector `toMatch(/(^\|\s)a(\b\|$)\|a\.\|:focus-visible\|:hover\|summary/)`. Fault G5 was killed. Precision: the `a\.` alternative would also accept an unrelated selector that merely contains "a." (none exists today), and accent used through another custom property would escape the scan. | PASS |

## Coverage

Rows touched by the fix were verified at 1b079cf. The others are carried from d9d3b90, and all their proofs re-ran green.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| token blocks (4) | carried | C1 | - |
| declared contrast pairs (24) | carried | C2 | - |
| vote cases, doors 5 and 6 (7 + unknown) | carried | C12 | - |
| components, door 4 (7) | carried | C9, C11, C12, C13, C14, C16, C17 | - |
| component empty states (7) | `design/README.md`, which now lists SourceNote `collectedAt` (optional, rendered only when given) | C10, C11, C12, C13, C15, C16, C18 | - |
| screen empty states (3) | carried | C29, C31 | - |
| roll-call group order (7) | carried | C30 | - |
| page background per theme (12) | carried | C26 | - |
| digit-bearing elements (6 pages) | carried | C35 | - |
| screen arrangements (3) | a1 sections 6.1-6.3 | profile C32 · roll-call C39 · card C40 | - |
| profile numbers with a note (lede, 3 indicators, 3 stats, score) | `Profile.vue` at 1b079cf | lede C36 · indicators C11 · stats C41 · score C41 | - |
| accent rules in `components.css` (4) | `grep` of `--ma-color-accent` | `a`, `:focus-visible` ×2, `summary` -> C43 | - |
| proof environments (2) | the real tree, plus a fresh worktree reproducing the CI `design` job: `npm ci`, `npm ci --prefix ../site`, `npm run build`, `npm test`, `npm run test:e2e`. The job definition was parsed with `yaml.safe_load`: `working-directory: design`, and the steps exactly as listed (`.github/workflows/ci.yml:59-85`). | real tree 39 + 13 · CI-equivalent 39 + 13 | - |
| pages at 360 px (8), reduced motion (6), schema_version (2) | carried | C24, C23, C20/C21 | - |

## Test policy rows

Verified at 1b079cf.

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Token build | `scripts/build-tokens.mjs`, `scripts/color.mjs`, `tokens/*.json`, and the CSS rule scan of C43 | own layer | yes |
| Vote option mapping | `components/vote.js` | own layer | yes (untouched; carried) |
| Components | `components/*.vue` (`SourceNote` touched) | render test, empty and normal case | yes: `SourceNote` with and without a method; the score note with `collectedAt` is read through the screen (C41) |
| Layout and computed style | `styles/components.css`, `screens/*.vue` | browser test per listed member | yes: C39 and C40 added for the two remaining screens |
| Prototype script | `scripts/prototype.mjs`, `scripts/contract.mjs` | own layer, child process | yes: C42 spawns the script with an emptied `exercisePeriods`; green in both environments |

Swept rows re-read: the `contract.mjs:54` fallback is now `null`, and the validation and failure paths are unchanged (`build-tokens.mjs:110-112`, `contract.mjs:18-19`, `prototype.mjs:88-90`).

## Faults injected

Verified at 1b079cf.

Scratch: `git worktree add --detach <scratchpad>/wt3 HEAD`. It started with no `site/node_modules`, then got `npm ci` in `design/` and `npm ci --prefix ../site`, which is the CI job's install. The real tree's `git status --porcelain` was empty before. After `git worktree remove --force` it was still empty, and `git worktree list` showed only the real tree.

| Mutation | Location | Killed |
| --- | --- | --- |
| G1: AI frame moved below the utilities | `design/screens/RollCall.vue:32-34`; playwright "roll-call arrangement" | yes, 1 failed |
| G2: card footer "Fonte: Câmara dos Deputados, dados de" changed to "... · dados de" | `design/screens/Card.vue` footer; playwright "card composition" | yes, `toContainText` failed |
| G3: `since` fallback `?? null` changed back to `?? meta.generatedAt` | `design/scripts/contract.mjs:54`; "no start date without an exercise period" | yes, `diario profile ... not to match /desde \d/` |
| G4: `:collected-at="generatedAt"` removed from the score note | `design/screens/Profile.vue:74`; "score and stats carry source notes" | yes, `... to contain 'dados de 27/09/2026'` |
| G5: `.ma-eyebrow` coloured with `--ma-color-accent` | `design/styles/components.css` `.ma-eyebrow`; "accent marks only interaction" | yes, `'.ma-eyebrow' to match ...` |

Open questions for the maintainer. These are policy. They are neither passes nor defects:

1. Score layout: one row per year with month ticks, instead of a1 6.1's single full-width strip (`plan.md:167`, Confirmed `n`).
2. The round-2 Out of scope rows: download/citar, per-party orientation, unit bar, card short address (`plan.md:146-149`, `:168`, Confirmed `n`).
3. AC 22 limited to profile and roll-call (`plan.md:166`, Confirmed `n`).
4. C27: choose diario or plenario, then delete the other direction's file and record AD-015.

## Gate

- `npm run build`: exit 0.
- `npx vitest run tests/ --reporter=verbose`: 39 passed, 0 failed.
- `npx playwright test --reporter=list`: 13 passed, 0 failed.
- CI-equivalent worktree: 39 passed and 13 passed.
- `python3 /home/augusto/.claude/skills/tlc-spec-lean/scripts/validate_verification.py design-system`: exit 1, "verdict is FAIL - route the ranked gaps back as fixes, then re-verify" (1 error, 0 warnings; no row contradicts the verdict).
