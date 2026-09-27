# site: static pages per deputy and per roll call over the ETL contract

## Problem

A voter who wants to know how a federal deputy acted in the 57th legislature has to assemble it from the Câmara portal: the deputy page lists no alignment with the government or the party, roll calls are browsed one session at a time, and nothing gives a single URL that can be shared with "this is what this deputy did, with the source". The ETL feature now publishes that data for 643 deputies and 1,597 nominal roll calls in `data/out/` (schema version 1, `etl/schema/*.json`), but nobody can read it: `main` has no site. The first round is on 2026-10-04; the grilling (`research/02-grilling-escopo-mvp.md`, decisions 2, 4, 9, 10, 11) sets launch before it and the target date is 2026-10-02 (decision 13, still unconfirmed). No usage figure exists yet; the prototype was never public.

When this ships, `npm run build` in `site/` turns `data/out/` into a static site: a home page to find a deputy by name, UF, party, in-exercise status and 2026 candidacy; one page per deputy with the four indicators as "n de m" next to their official source; one page per roll call showing how every deputy voted; and one share image per deputy generated at build time. Every page states the collection date and links each number to its source.

## Flow

Reuses the ETL contract as-is: the site never reads `data/raw/` and never recomputes an indicator (AD-002).

1. `data/out/*.json` (exists, produced by `mandato-etl build`) -> `site/src/lib/data.ts` (new, door 2) - reads the contract once per build, rejects any `schema_version` other than `1`, hands typed records to the pages
2. `data.ts` -> `site/src/lib/photos.ts` (new, door 4) - downloads each `photoUrl` once into `site/.cache/photos/`, hands the local path or `null` to pages and cards
3. `data.ts` -> Astro pages (new, door 1) - `/` (home), `/deputados/{id}/`, `/votacoes/{id}/`, each rendered to static HTML; the home search is one Vue island fed the trimmed deputy list as props
4. `data.ts` + `photos.ts` -> `site/src/lib/cards.ts` (new, door 3) - renders one 1200x630 PNG per deputy with satori + resvg to `/cards/deputados/{id}.png`
5. out: `site/dist/`, plain files, deployable to any static host (AD-001); the `launch` feature adds the deploy, the legal pages and the footer

## Impact

| Front | What changes |
| --- | --- |
| domain | new copy term: "participação em votações nominais do plenário" - the only label for `participation`; "faltou", "ausência" and "presença" are never rendered for it (AD-004) |
| domain | new copy term: "votos iguais à orientação do governo" - label for `governmentAlignment`; "governista" and any adjective are never rendered |
| domain | new copy term: "votos iguais à maioria do próprio partido" - label for `partyAlignment`; "fiel", "infiel" and "rebelde" are never rendered |
| stored data | nothing to migrate - the site stores nothing; it reads `data/out/` at build time |
| other features | `etl-camara` is consumed read-only through its schemas; a contract change there is a `schema_version` bump that fails this build on purpose (door 2) |

## Relations

`None - no stored-data shape change`. The entities are the ETL's (`etl/schema/*.json`); the site adds none.

## Surface

Public URLs are consumed outside the codebase the moment someone shares one, so they are listed here.

| Route | In | Out | Status |
| --- | --- | --- | --- |
| `GET /` | none | home HTML | `200` |
| `GET /deputados/{id}/` | deputy id from `deputies.json` | profile HTML | `200`, `404` for an unknown id |
| `GET /votacoes/{id}/` | roll-call id from `roll-calls.json` | roll-call HTML | `200`, `404` for an unknown id |
| `GET /cards/deputados/{id}.png` | deputy id | 1200x630 PNG | `200`, `404` for an unknown id |
| `GET /fotos/{id}.jpg` | deputy id | official photo as downloaded | `200`, `404` when the download failed |

## Landing

| One-way door | Literal shape | Alternative rejected |
| --- | --- | --- |
| Public URL scheme | `/deputados/{id}/`, `/votacoes/{id}/`, `/cards/deputados/{id}.png`, `/fotos/{id}.jpg`; `{id}` is the Câmara id, trailing slash, no name in the path | `/deputados/{id}-{nome}/` - a parliamentary name changes during a mandate and every shared link to the old name breaks or needs a redirect table a static host cannot keep |
| Single data layer | `site/src/lib/data.ts` is the only module that reads `data/out/`; the directory comes from `MANDATO_DATA_DIR` (default `../data/out`); `schema_version !== 1` throws and fails the build | pages importing JSON directly - a contract bump would render silently wrong pages instead of failing, and AD-002's future swap to a backend would touch every page |
| Share-card renderer | `satori` (JSX-like tree -> SVG) + `@resvg/resvg-js` (SVG -> PNG) at build time, fonts from `@fontsource`, no browser | a headless browser screenshot - Chromium in CI, minutes per build; an SVG `og:image` - WhatsApp, X and Facebook do not render SVG previews |
| Photo delivery | each official `photoUrl` downloaded once per build into `site/.cache/photos/{id}.jpg` (gitignored, restored from the Actions cache by `launch`) and served from our origin at `/fotos/{id}.jpg`, never resized-cropped or filtered, always captioned "Foto: Câmara dos Deputados"; a failed download renders no photo | hotlinking `camara.leg.br` - every visitor's IP goes to a third party and a Câmara outage breaks our pages; copies committed to git - the legal research says cache from the official URL, not copies in a public repository |
| Fonts | self-hosted `@fontsource-variable/source-serif-4` (headlines, numbers) and `@fontsource-variable/inter` (text), bundled into `dist/` | Google Fonts CDN - one third-party request per visit, which breaks the "no individual identifier" promise the analytics premise makes |
| Rendering mode | Astro `output: "static"`, no adapter, Vue only as an island on the home search | SSR or an adapter - a server to keep up during the campaign, against AD-001 |
| Frontend dependency set | runtime: `astro`, `@astrojs/vue`, `vue`, `satori`, `@resvg/resvg-js`, the two `@fontsource-variable` packages; dev: `vitest`; `package-lock.json` committed; Node 24 | a UI kit or CSS framework - the editorial direction (decision 10) is a handful of type and spacing rules, not components |

- Nothing else in this change is hard to reverse

## Criteria

### S1: The contract becomes pages (P1)

The build reads the ETL output once and refuses a contract it does not know.

**Acceptance Criteria**

1. WHEN `npm run build` runs with `MANDATO_DATA_DIR` pointing at a valid `data/out/` THEN the system SHALL write `dist/index.html`, one `dist/deputados/{id}/index.html` per entry of `deputies.json` and one `dist/votacoes/{id}/index.html` per entry of `roll-calls.json`
2. IF `meta.json` carries a `schema_version` other than `1` THEN the build SHALL exit non-zero with a message naming the found version
3. IF `MANDATO_DATA_DIR` holds no `meta.json` THEN the build SHALL exit non-zero with a message naming the directory
4. The system SHALL render on every page the collection date from `meta.generatedAt` as `Dados coletados em DD/MM/AAAA às HH:MM (horário de Brasília)`

**Independent test:** build against the fixture contract and count the generated pages; build against a copy with `schema_version: 2` and see it fail.

### S2: Home - find a deputy (P1)

A visitor reaches any deputy's page from the home in a few keystrokes, without being shown a comparison.

**Acceptance Criteria**

5. WHEN the home loads THEN the system SHALL list the deputies with `inExercise: true`, ordered alphabetically by accent-stripped name, each showing photo, name, party, UF and the 2026 candidacy badge when present, and linking to `/deputados/{id}/`
6. The home list SHALL show no indicator value and SHALL offer no ordering other than alphabetical
7. WHEN the visitor types in the search box THEN the system SHALL keep the deputies whose accent-stripped, casefolded name contains the typed text, accent-stripped and casefolded
8. WHEN the visitor picks a UF, a party, "Em exercício" or "Candidatura em 2026" THEN the system SHALL keep only the deputies matching every active filter; "Em exercício" SHALL start checked
9. IF no deputy matches THEN the system SHALL show `Nenhum deputado encontrado com esses filtros.` and a control that clears every filter
10. The home SHALL show one sentence stating what the site is and the legislature totals from `meta.counts`: deputies, nominal roll calls and authored propositions
11. WHILE JavaScript is unavailable the home SHALL still list every in-exercise deputy with its link

**Independent test:** open the built home, type "jose", tick a UF, see only matching deputies; clear the filters and see the full in-exercise list.

### S3: Deputy profile (P1)

One page answers "what did this deputy do", each number with its base and its source.

**Acceptance Criteria**

12. WHEN `/deputados/{id}/` renders THEN the system SHALL show name, party, UF, the official photo captioned `Foto: Câmara dos Deputados`, an "Em exercício" or "Fora de exercício" label, and a link to `sourceUrl` labelled as the Câmara page
13. The profile SHALL render `participation`, `governmentAlignment` and `partyAlignment` as `<count> de <total>` with pt-BR thousands separators, each under its label from `Impact`, each with a proportion bar, a one-line base of calculation and a link to `/metodologia/#<indicator>`
14. IF an indicator has `total = 0` THEN the profile SHALL show `Sem base de cálculo no período` instead of `0 de 0` and no bar
15. The profile SHALL render the authorship indicator as `authoredCount` propositions, `firstSignerCount` as first signer, and `requirementsCount` requirements, and list each authored proposition with type, number/year, summary, date and a link to its `sourceUrl`
16. WHERE `candidacy2026` is not null the profile SHALL show `Candidatura em 2026: <office>, <party>, número <ballotNumber> (situação no TSE: <situation>)` with the TSE as source
17. The profile SHALL list every entry of `votes`, newest first, each with date, organ, proposition title or description, the vote, and a link to `/votacoes/{rollCallId}/`
18. The profile SHALL list the `exercisePeriods` as date ranges, the open one reading `desde DD/MM/AAAA`
19. The profile SHALL render the vote value `Artigo 17` as `Art. 17 (presidente da sessão)` and an empty vote as `Registro sem voto`
20. IF the photo download failed THEN the profile SHALL render no image and no photo caption

**Independent test:** open the built profile of the fixture deputy 101 and read `3 de 4`, `2 de 3`, `2 de 3`, five authored propositions and seven votes, each linked.

### S4: Roll-call page (P1)

"How did everyone vote" has its own URL.

**Acceptance Criteria**

21. WHEN `/votacoes/{id}/` renders THEN the system SHALL show date, organ, description, the proposition title and summary with a link to the proposition page when present, the result (`Aprovada`, `Rejeitada` or `Resultado não informado`), the tallies and the government orientation or `Sem orientação do governo registrada`
22. The roll-call page SHALL group `votes` by vote value in the order `Sim`, `Não`, `Abstenção`, `Obstrução`, `Art. 17`, `Registro sem voto`, alphabetical by deputy name inside each group, each deputy linked to `/deputados/{deputyId}/` with party and UF
23. The roll-call page SHALL link to its `sourceUrl` as the official Câmara record

**Independent test:** open the built page of fixture roll call `100-1` and see Sim 2, Não 1, each name linked.

### S5: Share card and link preview (P1)

A shared profile link previews as a card that reads as information, not as campaign material.

**Acceptance Criteria**

24. WHEN the build runs THEN the system SHALL write one 1200x630 PNG per deputy at `dist/cards/deputados/{id}.png` showing name, party, UF, the official photo uncropped when available, the three `{count, total}` indicators as `n de m` with their labels, the authored count, `Fonte: Câmara dos Deputados - dados abertos` and the collection date
25. The profile SHALL carry `og:title`, `og:description`, `og:image` (absolute URL of its card), `og:url`, `twitter:card = summary_large_image` and a canonical link, all built from `SITE_URL`
26. The home and each roll-call page SHALL carry the same tags with one site-wide card at `dist/cards/site.png`

**Independent test:** build, open `dist/cards/deputados/101.png` and read the fixture numbers; paste a profile's `<head>` into a preview validator.

### S6: Descriptive language and provenance (P1)

No page says more than the data does.

**Acceptance Criteria**

27. The system SHALL contain none of the terms in `site/src/lib/forbidden-terms.ts` - at least `faltou`, `faltas`, `ausente`, `aprovação`, `intenção de voto`, `favorito`, `ranking`, `líder`, `chance de reeleição`, `pesquisa`, `mentiu`, `traiu`, `corrupto`, `governista`, `fiel`, `infiel`, `rebelde` - in any text the site itself writes, checked over every `.astro`, `.vue` and `.ts` file under `site/src/`
28. The system SHALL render no percentage of any indicator
29. The system SHALL render no CPF, e-mail, phone, address or birth date, and SHALL read no field outside `etl/schema/*.json`

**Independent test:** grep the built `dist/` for each forbidden term outside official quoted text and for `%` next to an indicator; the forbidden-terms test fails when one is added to a template.

### S7: Build in CI (P2)

The site cannot regress silently.

**Acceptance Criteria**

30. WHEN a pull request runs CI THEN the system SHALL run `npm ci`, `npm test` and `npm run build` in `site/` against the fixture contract in `site/tests/fixtures/out/`, and SHALL run `mandato-etl validate` on that fixture

**Independent test:** push a branch that breaks a page and see the `site` job fail.

## Out of scope

| Excluded | Why |
| --- | --- |
| Metodologia, Quem somos, Sobre os dados, Correções pages, legal footer, "Reportar erro" button | `launch` feature (grilling decisions 6-8, legal checklist section 5); this feature only links to `/metodologia/#<indicator>` |
| Deploy, domain, analytics, daily build | `launch` feature |
| Page per party or UF, any list ordered by an indicator, comparator | grilling decision 11 and "Fora do escopo" |
| "Meus eleitos", alerts, accounts | grilling "Fora do escopo" |
| Senate, other legislatures | AD-006 |
| Anything generated by AI, comments, polls | AD-009 |
| Share card per roll call | decision 10 names a card per deputy only; roll calls use the site card |

## Assumptions

| Assumption | Chosen default | Rationale | Confirmed? |
| --- | --- | --- | --- |
| Percent display | no percentage anywhere; `n de m` plus a proportion bar | AD-004 stores the base; a percentage beside 513 names invites the ranking the grilling ruled out | n |
| Visual direction | off-white page, near-black text, one accent (deep red `#B3261E`) for numbers and links only, Source Serif 4 for headlines and numbers, Inter for text, a 12-column editorial grid collapsing to one column under 640 px | decision 10 "tipografia forte, uma cor de destaque, números grandes com fonte logo abaixo" | n |
| Votes on the profile | every vote rendered in static HTML (about 1,100 rows for a full-term deputy, ~25 KB gzipped), grouped by year | no client-side fetch to keep working without JavaScript; size is acceptable | n |
| `SITE_URL` before the domain exists | `https://mandatoaberto.org` placeholder read from env, replaced by `launch` | the `.org` name is still pending with the maintainer | n |
| Methodology links | `/metodologia/#participacao`, `#alinhamento-governo`, `#alinhamento-partido`, `#proposicoes`; the page itself comes with `launch` | link shape fixed now so the profile does not change later | n |
| Suplentes and deputies out of exercise | reachable by search with "Em exercício" unchecked, labelled "Fora de exercício" | grilling premise "Suplentes e afastados" | n |
| Tests | `vitest` for `data.ts`, the search/filter logic and the formatters; one build-level test that runs `astro build` on the fixture and inspects `dist/` | AGENTS.md: new behaviour ships with a test | n |
| Fixture contract | `site/tests/fixtures/out/` generated once by the ETL test fixture and committed, re-validated in CI | the site's tests must not depend on a 700 MB download | n |

**Open questions:**

| # | Kind | Question | Until answered |
| --- | --- | --- | --- |
| 1 | blocks go-live | Final `.org` domain name | `SITE_URL` stays a placeholder; `og:image` URLs are wrong in production previews |
| 2 | open | Accent colour and typefaces | the defaults above stand; each is a one-line token change |

## Observable

| Surface | Decision | Landing |
| --- | --- | --- |
| screen home | empty state | AC 9 |
| screen home | loading state | AC 11 - the list is static HTML; the island only filters it |
| screen home | error state | n/a - no request at view time; a build error stops the deploy (AC 2, 3) |
| screen home | unauthorised state | n/a - public site, no accounts |
| screen home | density and ordering | AC 5, AC 6 |
| screen home | destructive action confirms | n/a - no destructive action |
| screen profile | empty state | AC 14 (indicator without base), AC 20 (no photo); a deputy with no vote cannot exist (etl AC 7) |
| screen profile | loading, error, unauthorised | n/a - static HTML, public |
| screen profile | density and ordering | AC 13, AC 17, AC 18 |
| screen roll call | empty state | AC 21 (no proposition, no orientation, no result) |
| screen roll call | density and ordering | AC 22 |
| screen roll call | loading, error, unauthorised | n/a - static HTML, public |
| all new `GET` routes | response shape, error shape | Surface table; `404` is the host's page until `launch` adds one |
| all new `GET` routes | versioning, rate limits | n/a - static files; the contract version is guarded at build (AC 2) |
| document share card | structure, tone, what the reader does next | AC 24 - descriptive numbers and source; the reader follows the link |
| document page copy | structure, tone, depth | AC 13, AC 27, AC 28 |
| command `npm run build` | output, flags, exit codes, failing halfway | AC 1-3; Astro writes to a fresh `dist/` and a failed build deploys nothing |
| collection deputies on the home | grouping, naming, ordering, duplicates, exception | AC 5-8; duplicates cannot occur (ids unique in the contract); deputies out of exercise are the exception (AC 8) |

## Sources

- `research/02-grilling-escopo-mvp.md` decisions 2, 4, 9, 10, 11 and the premises "Home", "Ordenação padrão", "Card de compartilhamento", "Busca", "Fotos", "Suplentes e afastados" - what the site shows and what it must not
- `research/01-pesquisa-juridica.md` sections 2.3 and 5 ("Produto") - forbidden vocabulary, photo handling, source on every number
- `etl/schema/*.json` - the contract the pages read
- `.specs/STATE.md` AD-001, AD-002, AD-003, AD-004, AD-009
