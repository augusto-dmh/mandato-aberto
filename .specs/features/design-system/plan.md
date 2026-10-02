# design-system: tokens, vote encoding and core components, proven on real data in two directions

## Problem

The v2 (AD-013, `research/05-grilling-escopo-v2.md`) will build its pages in a Laravel + Inertia + Vue application, and nothing today tells those pages how a number, a vote, a source or an official photo looks. The only visual language is the MVP's single stylesheet (`site/src/styles/global.css`, 655 lines): one red accent (`#b3261e`), Source Serif 4 and Inter, no dark theme, no token a second renderer can read, and vote options rendered as text only. Every page built without a system decides those things again, and the share card, rendered by a separate pipeline, drifts from the pages.

The maintainer asked for design at the level of Apple and Spotify and explicitly non-generic (chat, 2026-09-30). The research (`research/06-pesquisa-design-e-concorrentes.md`) found the Brazilian field crowded with scores and rankings, and found the transferable part of Apple to be rigour: a short type scale, a footnote on every number, contrast by construction and an official object nobody alters. It also found two hard limits: the Câmara's official photos are 354 × 472 px, so the profile hero cannot be photographic, and red/green vote colours read as right and wrong. The MVP's home weighs about 311 KB against 33 to 40 KB for the lightest competitors. No usage figure exists for the v2; it does not exist yet.

When this ships, `design/` holds one token source and a set of Vue components that render the product's core units (the "n de m" number with its source note, the vote mark, the term's score of votes, the official photo, the tally of a roll call, the AI summary frame) under two candidate directions from the same components. A prototype renders a real deputy profile, a real roll call and a share card from the ETL contract in both directions, in light and dark, and is published for the maintainer to choose one. The choice is recorded and the other direction is deleted.

## Flow

Reuses the ETL contract as-is (AD-002) and the MVP's forbidden-terms list (`site/src/lib/forbidden-terms.ts`); the site is not changed and nothing is recomputed from raw data.

1. `design/tokens/*.json` (door 2) -> token build script (new, no door - placement per conventions) - validates contrast pairs, writes `design/dist/tokens.css` with one block per direction and theme
2. contract directory, default `data/out/` (exists, produced by `mandato-etl build`) -> prototype script (new, no door) - rejects any `schema_version` other than `2`, picks one deputy and one roll call, hands plain records to the components
3. `design/components/*.vue` (door 1, door 4, door 5) - server-rendered to static HTML with `tokens.css`, once per direction, to `design/dist/prototype/<direction>/{profile,roll-call,card}.html`
4. `design/dist/prototype/` -> Playwright (door 3) - screenshots at 360 and 1280 px, light and dark, and the layout checks
5. out: the prototype published as an Artifact for the maintainer; the chosen direction becomes AD-015 in `.specs/STATE.md`

## Impact

| Front | What changes |
| --- | --- |
| domain | new term: "partitura do mandato" (`MandateScore`) - one column per nominal roll call in chronological order; copy names it "votações do mandato", never "desempenho" |
| domain | new term: vote mark - the shape-and-position encoding of a vote option (door 5); replaces the MVP's text-only vote label wherever a vote is drawn |
| domain | existing term: accent colour meant "numbers and links" in the MVP (`global.css` line 1); in `design/` it means "interaction and the item the reader searched for" only - the MVP keeps its meaning until `site/` is retired |
| stored data | nothing to migrate - `design/` stores nothing and reads the contract read-only |
| other features | `site/` untouched; the future `app/` will depend on `design/` (door 1); `etl/` untouched |

## Relations

`None - no stored-data shape change`. The entities are the ETL's (`etl/schema/*.json`).

## Surface

`None - nothing consumed outside`. The prototype is static files reviewed by the maintainer; the package boundary consumed by the future `app/` is door 1.

## Landing

| One-way door | Literal shape | Alternative rejected |
| --- | --- | --- |
| 1. `design/` is a private npm package the app depends on | `design/package.json` `{"name": "mandato-design", "private": true}`; exports `./tokens.css` and `./components/*`; the app will declare `"mandato-design": "file:../design"` | components written inside `app/`: the app does not exist and the grilling (decision 10) orders design before Laravel; throwaway HTML mockups: rewritten later, and the card would drift from the pages (principle 15) |
| 2. Token source format and CSS naming | W3C Design Tokens Community Group JSON, `{"color": {"ink": {"$type": "color", "$value": "oklch(0.21 0.01 80)"}}}`; one file per direction plus `base.json`; CSS custom property `--ma-<group>-<name>` (`--ma-color-ink`, `--ma-type-display-1-size`); colours in OKLCH | a Tailwind config as source: couples tokens to one CSS framework and the card renderer cannot read it; Style Dictionary: a dependency for a single CSS output |
| 3. Playwright as a dev dependency for screenshots and layout checks | `"@playwright/test"` in `design/package.json` `devDependencies`, Chromium only | manual screenshots: no exit code, so no check can rest on them |
| 4. Component names the app will copy | `NDeM`, `SourceNote`, `VoteMark`, `MandateScore`, `OfficialPhoto`, `TallyBar`, `AiSummaryFrame` | Portuguese component names: the project writes identifiers in English (`AGENTS.md`) |
| 5. Vote encoding used across the product | `yes`: filled mark above the baseline; `no`: filled mark below; `abstention`: hollow short mark on the baseline; `obstruction`: hatched short mark on the baseline; `not-recorded`: gap; every mark in ink tones, never a hue, and always with a text label | green/red by option: reads as right and wrong and fails colour-blind readers (HIG Charts, research section 3 principle 6); party colours: more than twenty parties and colour becomes a flag |
| 6. Vote values door 5 did not name, found in the contract while writing the checks (`Artigo 17`: 1,042 records; empty vote on a secret ballot) | `article-17`: small filled dot on the baseline, label "Art. 17 (presidente da sessão)"; empty vote with `secret: true`: gap, label "Votação secreta"; empty vote otherwise: `not-recorded`, label "Registro sem voto"; any other value: hollow dot on the baseline with the raw value as label | folding `Artigo 17` into `not-recorded`: the record says the session president did not vote by rule, which is not a missing record; labels follow the MVP's `voteLabel` (`site/src/lib/format.ts`) |

- Nothing else in this change is hard to reverse

## Criteria

### S1: one token source, contrast by construction (P1)

The two directions exist as tokens that pass the accessibility floor in light and dark before any screen uses them.

**Acceptance Criteria**

1. WHEN `npm run build` runs in `design/` THEN the system SHALL write `design/dist/tokens.css` defining every token of `design/tokens/*.json` as a `--ma-*` custom property, in four blocks: `diario` light, `diario` dark, `plenario` light, `plenario` dark
2. The system SHALL give every text colour token, against each surface token it is declared for, a WCAG 2.2 contrast ratio of at least 4.5:1, and every graphic and focus token at least 3:1, in all four blocks
3. IF a declared pair falls below its floor THEN the token build SHALL exit non-zero and print the pair and its ratio
4. The system SHALL define exactly one accent colour per direction and no colour token named after a vote option, a party or a valence (`yes`, `no`, `good`, `bad`, `success`, `danger`, `warning`, any party acronym)
5. The system SHALL define at most ten type sizes per direction, and every style used for a number SHALL set `font-variant-numeric: tabular-nums lining-nums`
6. The system SHALL load every font from files inside `design/` under the SIL Open Font License, with no request to a third-party host

**Independent test:** run the build, open `tokens.css`, run the contrast test; break one colour and watch the build fail.

### S2: the product's core units as components (P1)

Each recurring unit of the product renders the same way wherever it appears.

**Acceptance Criteria**

7. WHEN `NDeM` receives `{count, total}` with `total` above 0 THEN the system SHALL render `count` in the display style and "de `total`" in the body style on the same baseline, and SHALL render no percentage larger than the body style
8. IF `NDeM` receives `total` equal to 0 THEN the system SHALL render "Sem base de cálculo no período" (the MVP's `NO_BASE`, `site/src/lib/format.ts:19`) and no number
9. The system SHALL attach to every `NDeM` a `SourceNote` marker whose note holds the official `sourceUrl` from the contract and a link to the methodology anchor of that indicator
10. WHEN `VoteMark` renders an option THEN the system SHALL draw the shape and position of door 5 and SHALL expose the option as text to assistive technology ("Nome, PARTIDO-UF, votou Não")
11. WHEN `MandateScore` receives a deputy's votes THEN the system SHALL render one column per roll call in chronological order, each column linking to that roll call, and an equivalent table listing date, proposition and vote
12. WHEN `OfficialPhoto` receives a photo THEN the system SHALL render it at 3:4, no larger than 354 × 472 CSS px, with no CSS `filter`, `mix-blend-mode`, `transform` or cropping `object-fit`, and with the credit "Foto: Câmara dos Deputados"
13. IF `OfficialPhoto` receives no photo THEN the system SHALL render the deputy's initials in a neutral frame of the same size and no silhouette
14. WHEN `TallyBar` receives a roll call's tallies THEN the system SHALL render one cell per vote counted, grouped yes, no, others, with each group's count as text
15. WHEN `AiSummaryFrame` receives a summary with a review date THEN the system SHALL render it in a visually distinct frame labelled "Resumo gerado por IA a partir do texto oficial, revisado em DD/MM/AAAA", with links to the official full text and to the error report
16. IF `AiSummaryFrame` receives a summary without a review date THEN the system SHALL render nothing
17. The rendered HTML of every component and screen SHALL contain no term from the MVP's forbidden-terms list

**Independent test:** render each component with fixture data and read the HTML; run the forbidden-terms scan on `design/dist/`.

### S3: a real profile, roll call and card in both directions (P1)

The maintainer sees the system on real data, not lorem ipsum.

**Acceptance Criteria**

18. WHEN `npm run prototype` runs with `MANDATO_DATA` pointing at a contract directory THEN the system SHALL write `profile.html`, `roll-call.html` and `card.html` for each of the two directions, rendered from one deputy and one roll call of that directory
19. IF the contract's `meta.json` declares a `schema_version` other than `2` THEN the prototype SHALL exit non-zero and name the version it found
20. The system SHALL render every number of the three screens in the HTML itself, so each screen reads completely with JavaScript disabled
21. WHILE the reader's system requests reduced motion the system SHALL run no CSS animation or transition longer than 0 ms
22. WHEN a screen is laid out at 360 px wide THEN the system SHALL produce no horizontal page scroll, in both directions and both themes
23. WHEN `card.html` renders THEN the system SHALL lay it out at exactly 1200 × 630 px with no content clipped, for the deputy with the longest name in the contract directory
24. WHEN the reader's system prefers a dark scheme THEN the system SHALL apply that direction's dark block

**Independent test:** run the prototype against `data/out/`, open the six files, run the Playwright suite.

### S4: one direction chosen and recorded (P2)

The comparison ends in a decision, not two living themes.

**Acceptance Criteria**

25. WHEN the maintainer names the chosen direction THEN `design/tokens/` SHALL hold only `base.json` and that direction's file, and `.specs/STATE.md` SHALL hold AD-015 naming the direction and the rejected one
26. The system SHALL ship `design/README.md` listing, for each component of door 4, its inputs, its empty and missing-data states and the research principle it implements

**Independent test:** after the choice, list `design/tokens/` and read AD-015 and the README.

### S5: gaps found by the Verifier in round 1 (P1)

Added on 2026-10-02 from `verification.md` round 1; each line closes a gap the binding sources or the Observable table already implied.

**Acceptance Criteria**

27. IF an indicator shown in the profile lede or on the card has `total` 0 THEN the system SHALL render "Sem base de cálculo no período" in its place and no "0 de 0"
28. WHEN the roll-call screen renders THEN the system SHALL list the deputies in groups ordered `Sim`, `Não`, `Abstenção`, `Obstrução`, `Artigo 17`, empty, then any other value alphabetically, with names in pt-BR alphabetical order inside a group, each heading showing its count and each row carrying a `VoteMark` with the deputy's sentence
29. IF a roll call has no individual votes THEN the roll-call screen SHALL render "Nenhum voto individual registrado nesta votação" and no group
30. WHEN the profile is laid out at 1280 px THEN the system SHALL place the photo left of the name and the regions top to bottom as hero, lede, indicators, score; and at 360 px it SHALL place the photo above the name
31. WHILE the dark theme applies the system SHALL keep the mat around an official photo in the light theme's `raised` colour (research a1 principle 9)
32. The system SHALL keep every accent colour at an OKLab distance of at least 0.1 from the gov.br blues `#1351B4` and `#155BCB` (research section 4, "parecer site oficial")
33. The system SHALL compute `font-variant-numeric` with `tabular-nums` on every element of the six pages whose own text contains a digit
34. The system SHALL attach a source-note marker to the numbers of the profile lede and to the roll-call tally, each linking to the official source
35. WHEN `MandateScore` renders a row THEN the system SHALL draw a tick below the strip at the first vote of each month
36. The card SHALL render no "%", no AI summary frame and no other deputy's name, and two cards for different deputies SHALL share the same element and class sequence

**Independent test:** run the prototype for the fixture deputy with an empty base and for a roll call without votes; run the browser suite.

## Out of scope

| Excluded | Why |
| --- | --- |
| The Laravel application skeleton | its own feature after this one (grilling decision 10 orders design first) |
| Production share-card generation (satori pipeline, three sizes, verification code) | needs the app's routes; this feature only fixes the card's layout and tokens |
| Home, search, overview, comparator and account screens | entregas 2 and 3; the overview histogram needs its own decision (research section 8, item 2) |
| Senate- and Presidência-specific components | the ETL for those houses does not exist yet; the same units apply once it does |
| Quorum marker on the tally | the contract carries no required-quorum field (`etl/schema/roll-call.schema.json`) |
| AI summary generation | the frame is designed here; generation waits for the association (grilling decision 7) |
| "Gastos" block | research section 8, item 1, undecided |
| Download buttons (CSV/JSON), "Citar este perfil", "Citar" and permalink formats | need the app's routes and a public domain (AD-012 deferred the domain); research a1 sections 6.1 to 6.2 |
| Per-party orientation table on the roll call | the contract carries only the government orientation (`etl/schema/roll-call.schema.json`) |
| Unit bar under each "n de m" (one cell per vote) | `MandateScore` already draws one column per vote; a second per-vote bar per indicator waits for the profile feature |
| The card's short address | no public domain yet (AD-012); the footer names the site meanwhile |

## Assumptions

| Assumption | Chosen default | Rationale | Confirmed? |
| --- | --- | --- | --- |
| Which directions are prototyped | "Diário" (Newsreader + Inter) with the score element, and "Plenário" (Archivo + Source Serif 4) as contrast | recommendation of `research/design-anexos/a1-referencias-de-design.md` section 5; "Instrumento" alone tends to generic SaaS || y |
| Accent colour | not the MVP red; a dark blue-green in "Diário" and one accent in "Plenário", both chosen by the contrast rule | red reads as alarm and has strong party association in Brazil (research section 5) || y |
| Which deputy and roll call the prototype shows | `--deputy` and `--roll-call` flags; defaults are the in-exercise deputy with the longest name and the plenary roll call with the most recorded votes | stresses the layout with the hardest real case || y |
| Sample AI summary in the prototype | one hand-written sample marked "exemplo" in the prototype only | no AI runs before the association exists; the frame still needs content to be judged || y |
| Verification profile | `ui` for this feature, declared in `AGENTS.md` next to the `etl-camara` line | the whole deliverable is screens; `light` would not open the binding sources nor enumerate copy per screen || y |
| Package manager and runtime | npm and Node 24, as `site/` | one toolchain in the repository || y |

**Open questions:** none - all resolved or logged above.

## Observable

| Surface | Decision | Landing |
| --- | --- | --- |
| screen `profile` | empty state | AC 8, AC 13 |
| screen `profile` | loading state | n/a - static HTML with every number server-rendered (AC 20) |
| screen `profile` | error state | AC 19 at generation time; no runtime fetch exists |
| screen `profile` | unauthorised state | n/a - public data, no accounts in this feature |
| screen `profile` | density and ordering | AC 11 (chronological), AC 7 |
| screen `roll-call` | empty state | AC 8 for a roll call with no recorded votes |
| screen `roll-call` | density and ordering | AC 14 |
| screen `card` | density | AC 23 |
| all screens | destructive action | n/a - nothing is changed by a reader |
| document `design/README.md` | structure, depth, next step for the reader | AC 26 |

## Sources

- `research/05-grilling-escopo-v2.md` decision 10 - design system is the first v2 feature, validated before Laravel
- `research/06-pesquisa-design-e-concorrentes.md` sections 3 to 5 - principles, traps and directions this plan binds to
- `etl/schema/*.json` - the contract the prototype reads
