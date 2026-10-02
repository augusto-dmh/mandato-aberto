# design-system verification

**Verdict**: FAIL
**Profile**: ui
**Diff range**: c9b1a4a..d9d3b903e906dc551b49df5dd1b65892db7bc782
**Round**: 2 - scoped
**Verifier**: independent sub-agent (author != verifier)

Scope: the fix `83f5eb6..d9d3b90` (17 files: plan S5 and Out of scope rows, checks C29-C38 and round-2 proofs, `MandateScore`, `NDeM`, `OfficialPhoto`, the three screens, `components.css`, both direction token files, `e2e/setup.ts`, three test files) plus every round-1 verdict that was not PASS (C12, C15, C27, the C4 survivor, every Uncovered and Unproven cell, the Test policy rows). Round 1 is committed at 22774f9. Proofs re-ran in full at d9d3b90: `npm run build` exit 0; `npx vitest run tests/ --reporter=verbose` 36 passed, 0 failed, each named test listed individually; `npx playwright test --reporter=list` 11 passed, 0 failed.

The fix closes most of round 1. FAIL remains for four reasons:
- C27 is open, as expected.
- The plan's assumption row for the per-year score rows, cited by `checks.md` Handoff and by the brief, does not exist in `plan.md` at d9d3b90.
- Two arrangements the binding sources decide still have no check: the roll-call top and the card content.
- In a CI-equivalent tree the design job cannot load `tests/prototype.test.ts`.

## Binding sources

verified at d9d3b90 (re-judged only where the fix touched a screen or the plan; unchanged sources carried from 83f5eb6)

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `research/05-grilling-escopo-v2.md` decision 10 | carried from 83f5eb6 - yes | none | publishing the prototype as an artifact (Flow step 5) still has no check; process step, low rank |
| `research/06-...` section 3 / a1 section 3, P1 "todo número carrega a sua nota" | yes, re-read against the fix | none | resolved for the lede (C36) and the roll-call result (C36). Still open: the three profile stats (`design/screens/Profile.vue:44-57`) share one `SourceNote` with no marker, and no check reads that note |
| P2 "a fonte fica dentro do enquadramento" | yes | none | not touched by the fix: the partitura section (`Profile.vue` score section) carries no source and collection-date footer inside its frame, and no check exists. No Out of scope row covers it |
| P4 tabular figures | yes | none | resolved: C35, every digit-bearing element on the six pages (`e2e/screens.spec.ts:209-210`), via `.ma-page` `font-variant-numeric` (`styles/components.css:77`) |
| P5 unit bar / P11 download | yes | none | resolved by new Out of scope rows (`plan.md:146-148`); the table half of P11 is C13 |
| P7 accent single role | yes | none | still no check on where the accent is used (code uses it only on links, focus and the table `summary`); low rank |
| P9 passe-partout light in dark | yes | none | resolved: `mat` token plus C33 (`tests/tokens.test.ts:143-144`, `e2e/screens.spec.ts:190`) |
| `06` section 4 / a1 section 4, "nada de azul gov.br" | yes | none | resolved: Plenário accent now `oklch(0.46 0.2 300)` / dark `oklch(0.78 0.11 305)`, C34. Verifier recomputed with the published OKLab matrix: minimum distance 0.128 (Diário light to `#1351B4`), all eight ≥ 0.1 |
| a1 section 4 / 6.3 "nunca no card" | yes | none | resolved enough: C38 asserts no "%", no other deputy's name and no AI frame, and one shared template |
| a1 section 6.1 profile hero | yes | none | hero arrangement resolved (C32: photo left of name at 1280, regions hero, lede, indicators, score top to bottom, photo above name at 360). Month axis resolved as ticks (C37). **Still open:** the partitura is one row per year (`design/components/MandateScore.vue:18-27`) where a1 6.1 draws one full-width strip. The brief and `checks.md` Handoff ("The score row deviation ... is an assumption row in the plan") say a plan assumption records this. `plan.md` Assumptions at d9d3b90 holds six rows and none mentions the score rows (`grep -n "6.1\|year" plan.md` matches only lines 118 and 146). The deviation is neither checked nor recorded |
| a1 section 6.2 roll-call page | yes | none | grouped list resolved (C30); empty roll call resolved (C31); tally note resolved (C36); per-party orientation, "Citar" and download excluded (`plan.md:146-147`, the orientation reason confirmed: `etl/schema/roll-call.schema.json:52-57` carries only `governmentOrientation`). **Still open, arrangement:** the top of the screen - eyebrow, title, ementa, then AI frame, result, utilities "no topo, não no rodapé" (`design/screens/RollCall.vue:22-50`) - has no structure check; C32 covers the profile only |
| a1 section 6.3 share card | yes | none | short address excluded (`plan.md:149`). **Still open, arrangement and content:** no check reads that the card holds the photo, name, partido-UF, the "n de m" figures, the mini partitura and the line "Fonte: Câmara dos Deputados, dados de DD/MM/AAAA" (`design/screens/Card.vue:20-43`). C38 proves two cards are the same shape, not that the shape is the one the source draws |
| `etl/schema/*.json` | yes | none | empty bases resolved (C29). Not raised in round 1 and still open: `exercisePeriods` may be empty (fixture deputy 103: `[]`), and `scripts/contract.mjs:53` falls back to `meta.generatedAt`. Deputy 103's card then reads "desde 27/09/2026", a mandate start date the record does not hold. No check; low rank |
| `site/src/lib/format.ts` | carried from 83f5eb6 - yes | none | `NO_BASE` now honoured on every screen (C29) |

Plan changes made by the builder in the fix (S5 AC 27-36 and four Out of scope rows) are accepted here as resolving their gaps. They were added after the human-reviewed plan, so the maintainer still has to confirm them.

## Checks

C1-C3, C5, C6, C8, C10, C14, C17, C18, C19-C25, C28 carried from 83f5eb6 for evidence; their proofs re-ran green at d9d3b90. Every other row verified at d9d3b90.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | four blocks, every token as `--ma-*` | vitest "defines every token in four blocks" ✓ | `design/tests/tokens.test.ts:36`, `:44`, `:47` (carried; `mat` included by the same loop) | PASS |
| C2 | every declared pair meets its floor | vitest "every declared pair meets its floor" ✓ | `tokens.test.ts:57` `toBeGreaterThanOrEqual(p.min)`; `:58` | PASS |
| C3 | every text token paired with every surface | vitest ✓ | `tokens.test.ts:75` | PASS |
| C4 | pair below floor -> non-zero, pair and ratio with two decimals | vitest "fails the build on a pair below its floor" ✓ | `tokens.test.ts:89` `run.status not 0`; `:91` `toContain("diario dark muted on raised: 4.11:1 is below 4.5:1")`; `:92` `not.toContain("muted on paper")`. The boundary is now 0.39 below the floor (F1 killed) | PASS |
| C5 | one accent, no valence or party names | vitest ✓ | `tokens.test.ts:99` 23 contract parties added; `:100`-`:103` assertions (carried shape) | PASS |
| C6 | at most 10 type sizes | vitest ✓ | `tokens.test.ts` "at most ten type sizes" `toBeLessThanOrEqual(10)` | PASS |
| C7 | `.ma-num` tabular; component numbers inside it; round 2: every digit tabular | vitest ✓ ×2; playwright "every digit is tabular" ✓ | `components.test.ts:61` (carried); `e2e/screens.spec.ts:210` `expect(plain, ...).toEqual([])` | PASS |
| C8 | local OFL fonts, no third-party host | vitest ✓; playwright ✓ | `prototype.test.ts` fonts test (carried); `e2e/screens.spec.ts:20` | PASS |
| C9 | n de m on one baseline, no % | vitest ✓; playwright ✓ | `components.test.ts:33` `toBe("412 de 450")`; `e2e/screens.spec.ts:35` `expect(n).toBeGreaterThanOrEqual(2 * m)` closes the round-1 display-vs-body precision gap | PASS |
| C10 | total 0 -> NO_BASE | vitest ✓ | carried | PASS |
| C11 | NDeM note links; round 2 SourceNote alone | vitest ✓ ×3 | `components.test.ts:155-158` `[["Câmara dos Deputados", note.sourceUrl], ["Como calculamos", note.methodUrl]]`; `:163` `toEqual([note.sourceUrl])`; `:164` | PASS |
| C12 | seven vote cases: shape, position, label | vitest "VoteMark encodes every vote case" ✓ | obstruction now `components.test.ts:90-91` straddles y 12, `:93-94` hatch inside the square; unknown value `:110` `cy "12"`. F2 killed | PASS |
| C13 | chronological, linked, table; round 2 no votes | vitest ✓ ×2 | `components.test.ts:169-170` rows and table rows `toHaveLength(0)` | PASS |
| C14 | official photo untouched | playwright ✓ | carried | PASS |
| C15 | initials "LB" in a same-size frame, no `<img>` | vitest ✓; playwright "initials frame matches the photo frame" ✓ | `e2e/screens.spec.ts:147` `img` count 0; `:149` `toHaveText("LB")`; `:151-152` mat width and height within 1 px of the photo mat, profile 1280/360 and card, both directions. F5 killed | PASS |
| C16 | 19 cells; round 2 zero counts | vitest ✓ ×2 | `components.test.ts:183` cells 0; `:184` `["0","0","0"]` | PASS |
| C17 | AI frame label and links | vitest ✓ | carried | PASS |
| C18 | no review -> nothing | vitest ✓ | carried | PASS |
| C19 | no forbidden term | vitest ✓ | carried; but see Coverage, assemblies | PASS |
| C20 | three screens per direction | vitest ✓ | carried | PASS |
| C21 | schema_version 1 rejected | vitest ✓ | carried | PASS |
| C22 | no script, count in HTML | vitest ✓ | carried (NDeM now inserts a literal space between spans; assertion unchanged and green) | PASS |
| C23 | reduced motion 0s | playwright ✓ | carried | PASS |
| C24 | no scroll at 360 | playwright ✓ | carried | PASS |
| C25 | card 1200 × 630, longest name | playwright ✓ | carried | PASS |
| C26 | theme on each of the three screens | playwright "theme follows the system" ✓ | `e2e/screens.spec.ts:133` `expect(body, \`${d} ${s} ${theme}\`).toBe(probe)` inside the screens loop | PASS |
| C27 | tokens hold base + chosen; AD-015 recorded | `ls tokens` -> `base.json diario.json plenario.json`; no `\| AD-015 \|` row | no evidence - open by design, waits for the maintainer to choose diario or plenario | FAIL |
| C28 | README and package exports | vitest ✓ ×2 | carried | PASS |
| C29 | deputy 103: lede and card figures 1, 3 read NO_BASE, figure 2 "0 de 1", no "0 de 0" | vitest "empty bases never read 0 de 0" ✓ | `prototype.test.ts:85-86`, `:90-93`. F6 killed | PASS |
| C30 | groups Sim, Não, Abstenção, Obstrução, Art. 17, Registro sem voto, other; counts; pt-BR order; row labels | vitest "roll call groups deputies by vote" ✓ | `prototype.test.ts:124-131` full `toEqual` over 7 groups, "Davi Rocha" before "Érico Alves" | PASS |
| C31 | roll call without votes | vitest ✓ | `prototype.test.ts:145` message; `:146` `.ma-group` length 0 | PASS |
| C32 | profile arrangement at 1280 and 360 | playwright "profile arrangement" ✓ | `e2e/screens.spec.ts:166` photo right edge ≤ name x; `:168` tops sorted; `:173` photo above name at 360 | PASS |
| C33 | mat light in dark | vitest ✓; playwright ✓ | `tokens.test.ts:143-144`; `e2e/screens.spec.ts:190` `expect(mat, ...).toBe(probe)` on profile and card | PASS |
| C34 | accents ≥ 0.1 OKLab from gov.br blues | vitest ✓ | `tokens.test.ts:134` `toBeGreaterThanOrEqual(0.1)`. Precision nit: `:120` uses `0.808885698` where Ottosson's matrix reads `0.8086757660`; verifier's recompute with the published value gives minimum 0.128, same verdict | PASS |
| C35 | every digit tabular on six pages | playwright ✓ | `e2e/screens.spec.ts:209` non-empty set; `:210` `toEqual([])`. F7 killed | PASS |
| C36 | lede and result markers -> notes with sourceUrl | vitest ✓ | `prototype.test.ts:155` `toBe(DEPUTY.sourceUrl)`; `:159` note inside `.ma-result`; `:160` `toBe(ROLL_CALL.sourceUrl)` | PASS |
| C37 | month ticks at x 0.5, 4.5 / 0.5 | vitest ✓ | `components.test.ts:178` `toEqual([[0.5, 4.5], [0.5]])` | PASS |
| C38 | one card template; no %, other name, AI frame | vitest ✓ | `prototype.test.ts:176` `toBe(shape(hb))`; `:179-181`. Precision: `Card.vue:25` adds `ma-card__name--long` above 40 characters, so the shared sequence holds only for names up to 40 (the longest real name has 35) | PASS |

## Coverage

verified at d9d3b90 for every row the fix touched; token blocks, contrast pairs, components door 4, 360 px, reduced motion and schema_version carried from 83f5eb6 (their proofs re-ran green)

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| token blocks (4) | carried from 83f5eb6 | C1 | - |
| declared contrast pairs (24) | `pairs()` at d9d3b90: `mat` is role `decor`, so the count is unchanged | C2 | - |
| vote cases, doors 5 and 6 (7 + unknown) | contract values in `data/out/roll-calls/*` | kind, label and position for all 8 -> C12 | - |
| components, door 4 (7) | carried from 83f5eb6 | C9, C11, C12, C13, C14, C16, C17 | - |
| component empty states (7) | `design/README.md` | NDeM C10 · SourceNote C11 · VoteMark gap C12 · MandateScore C13 · OfficialPhoto C15 · TallyBar C16 · AiSummaryFrame C18 | - |
| screen empty states (3) | plan Observable | profile C29 · card C29 · roll-call C31 | - |
| roll-call group order (7) | AC 28 | C30 | - |
| page background per theme (12) | 3 screens × 2 directions × 2 themes | C26 | - |
| digit-bearing elements (6 pages) | Chromium walk over every element | C35 | - |
| pages at 360 px (8) | carried from 83f5eb6 | C24 | - |
| pages under reduced motion (6) | carried from 83f5eb6 | C23 | - |
| contract `schema_version` (2) | carried from 83f5eb6 | C20, C21 | - |
| screen arrangements (3) | a1 sections 6.1-6.3 | profile C32 | roll-call top region order, card content composition |
| assemblies that run the proofs (2) | the real tree, and the CI `design` job (`.github/workflows/ci.yml`, `npm ci` in `design/` only) | real tree: 36 + 11 green | CI-equivalent tree: `tests/prototype.test.ts` fails to load. It imports `site/src/lib/forbidden-terms.ts`, whose `site/tsconfig.json` extends `astro/tsconfigs/strict`, and `site/node_modules` is absent there. Reproduced in a fresh worktree with only `design/` installed: `[TSCONFIG_ERROR] Failed to load tsconfig 'astro/tsconfigs/strict'`, 0 tests. With `site/node_modules` linked the file passes 10/10. The proofs of C8, C19-C22, C29-C31, C36 and C38 would be red on the PR |

## Test policy rows

verified at d9d3b90

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Token build | `scripts/build-tokens.mjs`, `scripts/color.mjs`, `tokens/*.json` | own layer | yes - every pair (C2), and the failing case sits at 4.11:1 against a 4.5 floor (F1 killed) |
| Vote option mapping | `components/vote.js` | own layer | yes - every row, obstruction and unknown included, asserts kind, label and position (F2 killed) |
| Components | `components/*.vue` | one render test per component, empty state and normal case | yes - SourceNote has its own two tests; MandateScore, TallyBar empty states tested; OfficialPhoto frame size in the browser (F5 killed) |
| Layout and computed style | `styles/components.css`, `screens/*.vue` | one browser test per listed member | yes - 360 px, reduced motion, theme (12), photo mat, digits, profile arrangement |
| Prototype script | `scripts/prototype.mjs`, `scripts/contract.mjs` | own layer, child process | yes locally (`prototype.test.ts:22-27`); the CI gap is recorded under Coverage |

Swept rows resolving to existing, re-read at d9d3b90: `scripts/build-tokens.mjs:110-112`, `scripts/contract.mjs:18-19`, `scripts/prototype.mjs:88-90`, `components/OfficialPhoto.vue:16`; C34's constraint exists at `tokens.test.ts:134`. All present.

## Faults injected

verified at d9d3b90

Fresh scratch worktree: `git worktree add --detach <scratchpad>/wt2 HEAD`, own `npm ci` in `design/`. `site/node_modules` was symlinked into the scratch only, so `prototype.test.ts` could load (see Coverage). Real tree `git status --porcelain` was empty before. After `git worktree remove --force` it was empty, and the real `site/node_modules` was intact.

| Mutation | Location | Killed |
| --- | --- | --- |
| F1 floor check `p.ratio < p.min` -> `p.ratio < p.min - 0.5` | `design/scripts/build-tokens.mjs:92`; "fails the build on a pair below its floor" | yes - `expected +0 not to be +0` |
| F2 obstruction square and hatch moved above the baseline | `design/components/vote.js:39-40`; "VoteMark encodes every vote case" | yes - `expected 6.5 to be greater than 12` |
| F5 initials frame `aspect-ratio: 3 / 4` -> `1 / 1` | `design/styles/components.css` `.ma-photo__initials`; playwright "initials frame matches the photo frame" | yes - height difference 51.25 px |
| F6 card empty-base branch `f.total > 0` -> `f.total >= 0` | `design/screens/Card.vue:29`; "empty bases never read 0 de 0" | yes - `'0 de 0 votações nominais ...' to contain 'Sem base de cálculo no período'` |
| F7 page-wide `font-variant-numeric` removed | `design/styles/components.css:77`; playwright "every digit is tabular" | yes - 1 failed |

## Gate

`npm run build` exit 0 · `npx vitest run tests/ --reporter=verbose` - 36 passed, 0 failed · `npx playwright test --reporter=list` - 11 passed, 0 failed · `python3 /home/augusto/.claude/skills/tlc-spec-lean/scripts/validate_verification.py design-system` - exit 1: "verdict is FAIL - route the ranked gaps back as fixes, then re-verify" (1 error, 0 warnings; no row contradicts the verdict)
