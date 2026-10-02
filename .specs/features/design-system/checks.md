# design-system checks

Profile: ui
Plan: `.specs/features/design-system/plan.md`

38 checks in 5 slices · 6 one-way doors · 1 open, which blocks go-live (C27 waits for the maintainer's choice). C29-C38 and the extra proofs marked "round 2" were added on 2026-10-02 to close the gaps of verification round 1; no proof was removed or weakened

All commands run from `design/`. `npx vitest run <file> -t "<name>"` for unit and render tests; `npx playwright test <file> -g "<name>"` for layout tests against the generated prototype.

## Checks

### S1 - one token source, contrast by construction · ~8 files · ~30 KB · ~8k

**C1** - `npm run build` writes `dist/tokens.css` with four blocks, `diario` light, `diario` dark, `plenario` light, `plenario` dark, and each block defines every colour token of its direction as `--ma-color-<name>`; every non-colour token of `base.json` and the direction file is defined as `--ma-<group>-<name>` (AC 1)
Proof: `npx vitest run tests/tokens.test.ts -t "defines every token in four blocks"`

**C2** - Every declared pair meets its floor in all four blocks: text pairs at least 4.5:1, graphic and focus pairs at least 3:1, computed with the WCAG 2.2 relative-luminance formula from the OKLCH value (AC 2)
Proof: `npx vitest run tests/tokens.test.ts -t "every declared pair meets its floor"`

**C3** - Every colour token whose role is text is declared against every surface token of its direction, so no text colour escapes C2 (AC 2)
Proof: `npx vitest run tests/tokens.test.ts -t "every text token is paired with every surface"`

**C4** - A token file with a pair below its floor makes the build exit non-zero and print the pair and its ratio with two decimals (AC 3)
Proof: `npx vitest run tests/tokens.test.ts -t "fails the build on a pair below its floor"`

**C5** - Each direction defines exactly one colour token whose name starts with `accent`, and no colour token name contains `yes`, `no`, `good`, `bad`, `success`, `danger`, `warning` or a party acronym from the contract (AC 4)
Proof: `npx vitest run tests/tokens.test.ts -t "one accent and no valence names"`

**C6** - Each direction defines at most 10 type sizes (AC 5)
Proof: `npx vitest run tests/tokens.test.ts -t "at most ten type sizes"`

**C7** - `.ma-num` sets `font-variant-numeric: tabular-nums lining-nums`, and every number rendered by `NDeM`, `TallyBar` and the `MandateScore` table sits inside a `.ma-num` element (AC 5)
Proof: `npx vitest run tests/tokens.test.ts -t "numeric class sets tabular lining figures"`
Proof: `npx vitest run tests/components.test.ts -t "numbers carry the numeric class"`
Proof (round 2): `npx playwright test e2e/screens.spec.ts -g "every digit is tabular"`

**C8** - Every `@font-face` of the prototype points to a relative file under `dist/prototype/fonts/`, each font package shipped declares the SIL Open Font License, and loading the six pages requests no host other than the local file server (AC 6)
Proof: `npx vitest run tests/prototype.test.ts -t "fonts are local OFL files"`
Proof: `npx playwright test e2e/screens.spec.ts -g "no third-party request"`

### S2 - the product's core units as components · ~10 files · ~35 KB · ~9k

**C9** - `NDeM` with `{count: 412, total: 450}` renders "412" in the display element and "de 450" in the body element inside one container whose computed `align-items` is `baseline`, and renders no "%" (AC 7)
Proof: `npx vitest run tests/components.test.ts -t "NDeM renders n de m"`
Proof: `npx playwright test e2e/screens.spec.ts -g "n de m shares a baseline"`

**C10** - `NDeM` with `total: 0` renders "Sem base de cálculo no período" and no digit (AC 8)
Proof: `npx vitest run tests/components.test.ts -t "NDeM with an empty base"`

**C11** - Every `NDeM` renders a `SourceNote` marker linking to a note whose links are the record's `sourceUrl` and the methodology anchor of the indicator (AC 9)
Proof: `npx vitest run tests/components.test.ts -t "NDeM carries its source note"`
Proof (round 2): `npx vitest run tests/components.test.ts -t "SourceNote links source and method"`
Proof (round 2): `npx vitest run tests/components.test.ts -t "SourceNote without a method"`

**C12** - `VoteMark` draws each of the seven vote cases of doors 5 and 6 with its shape, its position and its accessible label, and uses no fill or stroke other than `currentColor` (AC 10)
Proof: `npx vitest run tests/components.test.ts -t "VoteMark encodes every vote case"`

**C13** - `MandateScore` renders one column per vote in ascending date order, each linking to `/votacoes/<rollCallId>/`, and a table with date, proposition and vote label for every column (AC 11)
Proof: `npx vitest run tests/components.test.ts -t "MandateScore is chronological and linked"`
Proof (round 2): `npx vitest run tests/components.test.ts -t "MandateScore without votes"`

**C14** - `OfficialPhoto` renders at a 3:4 box no larger than 354 × 472 CSS px, with computed `filter: none`, `mix-blend-mode: normal`, `transform: none`, `object-fit` other than `cover`, and the credit "Foto: Câmara dos Deputados" (AC 12)
Proof: `npx playwright test e2e/screens.spec.ts -g "official photo is untouched"`

**C15** - `OfficialPhoto` with no photo renders the initials of the name ("LB" for "Luiz Philippe de Orleans e Bragança": first and last word) in a frame of the same size and no `<img>` (AC 13)
Proof: `npx vitest run tests/components.test.ts -t "OfficialPhoto without a photo"`
Proof (round 2): `npx playwright test e2e/screens.spec.ts -g "initials frame matches the photo frame"`

**C16** - `TallyBar` with `{yes: 12, no: 5, others: 2}` renders 19 cells in the order yes, no, others, and the counts "12", "5" and "2" as text (AC 14)
Proof: `npx vitest run tests/components.test.ts -t "TallyBar renders one cell per vote"`
Proof (round 2): `npx vitest run tests/components.test.ts -t "TallyBar with zero counts"`

**C17** - `AiSummaryFrame` with `reviewedAt: "2026-09-30"` renders "Resumo gerado por IA a partir do texto oficial, revisado em 30/09/2026" and links to the given official URL and error-report URL (AC 15)
Proof: `npx vitest run tests/components.test.ts -t "AiSummaryFrame is labelled"`

**C18** - `AiSummaryFrame` without `reviewedAt` renders no element (AC 16)
Proof: `npx vitest run tests/components.test.ts -t "AiSummaryFrame without review renders nothing"`

**C19** - No file under `dist/prototype/` and no component source contains a term of the MVP's forbidden list (`site/src/lib/forbidden-terms.ts`) (AC 17)
Proof: `npx vitest run tests/prototype.test.ts -t "no forbidden term in rendered output"`

### S3 - a real profile, roll call and card in both directions · ~8 files · ~30 KB · ~8k

**C20** - `npm run prototype` over the contract fixture writes `profile.html`, `roll-call.html` and `card.html` under `dist/prototype/diario/` and `dist/prototype/plenario/`, each naming the chosen deputy or roll call (AC 18)
Proof: `npx vitest run tests/prototype.test.ts -t "writes three screens per direction"`

**C21** - A contract whose `meta.json` declares `schema_version: 1` makes the prototype exit non-zero with "schema_version 1" in stderr (AC 19)
Proof: `npx vitest run tests/prototype.test.ts -t "rejects another schema version"`

**C22** - The six pages contain no `<script>` and hold the deputy's participation count and total as text in the HTML (AC 20)
Proof: `npx vitest run tests/prototype.test.ts -t "screens read without javascript"`

**C23** - Under `reducedMotion: "reduce"`, every element of the six pages computes `transition-duration` and `animation-duration` of `0s` (AC 21)
Proof: `npx playwright test e2e/screens.spec.ts -g "reduced motion"`

**C24** - At 360 px wide, `profile` and `roll-call` have `scrollWidth` at most 360 in both directions and both themes (AC 22)
Proof: `npx playwright test e2e/screens.spec.ts -g "no horizontal scroll at 360"`

**C25** - `card.html` rendered for "Luiz Philippe de Orleans e Bragança" has a 1200 × 630 card box, and no descendant overflows its own box or the card's box, in both directions (AC 23)
Proof: `npx playwright test e2e/screens.spec.ts -g "card fits the longest name"`

**C26** - Under `colorScheme: "dark"` the page background of each of the three screens equals the direction's dark `paper` token, and under light it equals the light one (AC 24)
Proof: `npx playwright test e2e/screens.spec.ts -g "theme follows the system"`

### S4 - one direction chosen and recorded · 3 files · ~10 KB · ~3k

**C27** - After the maintainer's choice, `design/tokens/` holds exactly `base.json` and the chosen direction's file, and `.specs/STATE.md` holds a row starting `| AD-015 |` (AC 25) - blocks go-live: waits for the maintainer
Proof: `test "$(ls tokens | sort | tr '\n' ' ')" = "base.json $CHOSEN.json " && grep -q '^| AD-015 |' ../.specs/STATE.md`

**C28** - `design/README.md` has one section per component of door 4, each naming its inputs, its empty or missing-data state and the research principle it implements; `package.json` is named `mandato-design`, is private and exports `./tokens.css` and `./components/*` (AC 26, doors 1 and 4)
Proof: `npx vitest run tests/package.test.ts -t "readme documents every component"`
Proof: `npx vitest run tests/package.test.ts -t "package exports tokens and components"`

### S5 - gaps of verification round 1 (round 2) · ~10 files · ~40 KB · ~10k

**C29** - For deputy 103 of the fixture (participation 0/0, government 0/1, party 0/0) the profile lede and the card's first and third figures read "Sem base de cálculo no período", the second reads "0 de 1", and no page contains "0 de 0" (AC 27)
Proof: `npx vitest run tests/prototype.test.ts -t "empty bases never read 0 de 0"`

**C30** - A roll call holding every vote value renders groups in the order Sim, Não, Abstenção, Obstrução, Art. 17, Registro sem voto, then other values, each heading with its count, names in pt-BR order ("Davi Rocha" before "Érico Alves"), and each row's mark labelled with the deputy's sentence (AC 28)
Proof: `npx vitest run tests/prototype.test.ts -t "roll call groups deputies by vote"`

**C31** - A roll call with no individual votes renders "Nenhum voto individual registrado nesta votação." and no group, in both directions (AC 29)
Proof: `npx vitest run tests/prototype.test.ts -t "roll call without individual votes"`

**C32** - At 1280 px the photo's right edge is left of the name, and hero, lede, indicators and score start top to bottom in that order; at 360 px the photo ends above the name (AC 30)
Proof: `npx playwright test e2e/screens.spec.ts -g "profile arrangement"`

**C33** - The `mat` token equals the light `raised` value in both themes, and in the dark theme the computed mat background on profile and card equals it (AC 31)
Proof: `npx vitest run tests/tokens.test.ts -t "photo mat stays light in the dark theme"`
Proof: `npx playwright test e2e/screens.spec.ts -g "photo mat stays light in dark"`

**C34** - Every accent value, light and dark, of both directions sits at an OKLab distance of at least 0.1 from `#1351B4` and `#155BCB` (AC 32)
Proof: `npx vitest run tests/tokens.test.ts -t "accents keep away from the gov.br blues"`

**C35** - On the six pages, every element with a digit in its own text computes `font-variant-numeric` containing `tabular-nums` (AC 33)
Proof: `npx playwright test e2e/screens.spec.ts -g "every digit is tabular"`

**C36** - The profile lede's marker points to a note linking the deputy's `sourceUrl`, and the roll-call result's marker points to a note inside the result region linking the roll call's `sourceUrl` (AC 34)
Proof: `npx vitest run tests/prototype.test.ts -t "source notes on the lede and the tally"`

**C37** - For votes on 01/03/2023, 20/11/2023, 20/11/2023 and 02/05/2024, the 2023 row has month ticks at x 0.5 and 4.5 and the 2024 row at x 0.5 (AC 35)
Proof: `npx vitest run tests/components.test.ts -t "MandateScore ticks each month"`

**C38** - Cards for deputies 101 and 102 share the same element and class sequence, and neither contains "%", the other deputy's name or an AI frame (AC 36)
Proof: `npx vitest run tests/prototype.test.ts -t "every card shares one template"`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| token blocks (4) | `diario` light C1 · `diario` dark C1 · `plenario` light C1 · `plenario` dark C1 | - |
| declared contrast pairs (24) | C2, table-driven over all 24 (3 text colours × 2 surfaces × 2 themes × 2 directions); C3 proves the list is complete for text | - |
| vote cases, doors 5 and 6 (7) | `Sim` C12 · `Não` C12 · `Abstenção` C12 · `Obstrução` C12 · `Artigo 17` C12 · empty, not secret C12 · empty, secret C12; table-driven, plus unknown value C12 | - |
| components, door 4 (7) | `NDeM` C9 · `SourceNote` C11 · `VoteMark` C12 · `MandateScore` C13 · `OfficialPhoto` C14 · `TallyBar` C16 · `AiSummaryFrame` C17 | - |
| component empty states (6) | `NDeM` total 0 C10 · `OfficialPhoto` no photo C15 · `AiSummaryFrame` unreviewed C18 · `SourceNote` no method C11 · `MandateScore` no votes C13 · `TallyBar` zero counts C16 | - |
| screen empty states (3) | profile lede total 0 C29 · card figure total 0 C29 · roll call without votes C31 | - |
| roll-call group order (7) | `Sim` C30 · `Não` C30 · `Abstenção` C30 · `Obstrução` C30 · `Artigo 17` C30 · empty C30 · other value C30 | - |
| page background per theme (12) | profile×diario×light C26 · profile×diario×dark C26 · roll-call×diario×light C26 · roll-call×diario×dark C26 · card×diario×light C26 · card×diario×dark C26 · profile×plenario×light C26 · profile×plenario×dark C26 · roll-call×plenario×light C26 · roll-call×plenario×dark C26 · card×plenario×light C26 · card×plenario×dark C26 | - |
| pages at 360 px (8) | profile×diario×light C24 · profile×diario×dark C24 · profile×plenario×light C24 · profile×plenario×dark C24 · roll-call×diario×light C24 · roll-call×diario×dark C24 · roll-call×plenario×light C24 · roll-call×plenario×dark C24 | - |
| pages under reduced motion (6) | diario profile C23 · diario roll-call C23 · diario card C23 · plenario profile C23 · plenario roll-call C23 · plenario card C23 | - |
| contract `schema_version` (2) | `2` accepted C20 · other rejected C21 | - |
| Landing doors (6) | 1 package C28 · 2 token format C1 · 3 Playwright C14, C23-C26 · 4 names C28 · 5 vote encoding C12 · 6 extra vote values C12 | - |

- Claims about computed layout and style (C9 baseline, C14, C23-C26) are proven in a browser, not by reading CSS text
- No other check claims more than the case its proof exercises

## Test policy

| Code | Required proofs | Coverage expectation |
| --- | --- | --- |
| Token build (decides: contrast floor, naming, gamut) | one at its own layer | every declared pair; one failing case |
| Vote option mapping (decides: seven cases plus unknown) | one at its own layer | one asserted case per row |
| Components (decide: empty states, ordering) | one render test per component | each empty state and the normal case |
| Layout and computed style | one browser test | every page × direction × theme member listed in Coverage |
| Prototype script (decides: schema version, defaults) | one at its own layer, run as a child process | accepted and rejected version |

Evidence: the repo's closest analogue is `site/tests/`, where `format.test.ts` proves the vote-label table case by case and `build.test.ts` runs the build as a child process; this follows the same levels. Browser-level tests do not exist in the repo yet, which is door 3.

## Swept

- validation: C3, C4, C34 (token values), C21 (contract version)
- failure modes: C4, C21 - both exit non-zero with the cause on stderr
- idempotency: n/a - the build and the prototype overwrite `dist/` from the same inputs; nothing accumulates
- authorization: n/a - no accounts and no server in this feature
- concurrency: n/a - single local process, no shared state
- data lifecycle: n/a - `design/` stores nothing; `dist/` is regenerated and gitignored
- dependency failure: C15 - a missing photo renders initials; fonts are local files (C8), so no font host can fail
- state transitions: C27 - two directions become one, once
- observability: n/a - a local build tool; failures print to stderr (C4, C21)

## Handoff

- S1 = 8k, S2 enters at 17k, S3 at 25k, S4 at 28k, plus about 30k for reading the contract, the MVP formats and running Playwright: about 58k, under the 150k budget - one builder
- C27 cannot close in this build: it waits for the maintainer to choose between the two directions on the published prototype

- **Boundary:** C1-C26 and C28 green in this build; C27 open until the maintainer chooses a direction
- **Settled mid-build:** C10's "no digit" is read as no number of the record: the footnote marker and its note keep their index digit, because C11 requires every `NDeM` to carry its note, the empty one included. `NDeM` lost its optional unit after the first screenshots showed it repeating the label. The share card's footer names the site instead of repeating the photo credit, which `OfficialPhoto` already prints. CI gains a `design` job running both suites
- **Abandoned:** a 30%-wide photo column beside the name on phones - the name broke one word per line at 360 px; the photo now sits above the name below 734 px
- **Boundary (round 2):** C29-C38 added and green with the extra proofs on C7, C11, C13, C15, C16; C27 still open
- **Settled mid-build (round 2):** Plenário's accent moved from blue `oklch(0.47 0.21 264)` to violet `oklch(0.46 0.2 300)`, away from the gov.br blues. A new `mat` colour token, role `decor`, keeps the photo mat light in dark mode. The page sets tabular figures globally. Explicit spaces now sit between the number spans, because Vue's whitespace condensing glued "0de 1votos" for screen readers and copy. The score row deviation from research a1 section 6.1 is an assumption row in the plan
