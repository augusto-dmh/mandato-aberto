# site checks

Profile: light
Plan: `.specs/features/site/plan.md`

## Intent

41 checks in 7 slices · 7 one-way doors approved + 1 amended during checks (static `@fontsource` for the card renderer, see `## Handoff`) · 2 open, of which 1 blocks go-live (final `.org` domain)

All proofs run from the repository root. `T` below abbreviates `npm --prefix site test --` (vitest). Unit
tests live next to each other in `site/tests/*.test.ts`; `site/tests/build.test.ts` runs one real
`astro build` of the fixture contract in `site/tests/fixtures/out/` (3 deputies, 7 roll calls,
`generatedAt 2026-09-27T12:00:00Z`) with `SITE_URL=https://preview.example.org` (not the default, so a
hard-coded origin fails), `MANDATO_PHOTOS=off` and a photo cache seeded only with a synthetic
400x300 JPEG for deputy 101 (landscape, so a forced 3:4 crop is visible), then inspects its `dist/`.

Interpretation settled while deriving: the "clear" control of AC 9 returns every filter to its
initial state, which leaves "Em exercício" checked - the plan's independent test for S2 reads
"clear the filters and see the full in-exercise list".

## Checks

### S1 - Contract to pages · 6 files · 16 KB · ~4k

**C1** - Building the fixture writes `index.html`, `deputados/101/index.html`, `deputados/102/index.html`, `deputados/103/index.html` and `votacoes/{100-1,100-2,100-3,100-4,200-1,200-2,200-3}/index.html`, and no other page under `deputados/` or `votacoes/` (AC 1, Surface 404)
Proof: `T tests/build.test.ts -t "writes one page per deputy and per roll call"`

**C2** - `loadContract` on a directory whose `meta.json` has `schema_version: 2` throws exactly `Unsupported data contract: meta.json has schema_version 2; this site reads 1`, and `astro build` over that directory exits non-zero with that line on its output (AC 2, door 2)
Proof: `T tests/data.test.ts -t "rejects schema_version 2"`
Proof: `T tests/build.test.ts -t "fails on schema_version 2"`

**C3** - `loadContract` on a directory without `meta.json` throws exactly `No meta.json in <absolute dir>`, and `astro build` over it exits non-zero with that line on its output (AC 3)
Proof: `T tests/data.test.ts -t "rejects a directory without meta.json"`
Proof: `T tests/build.test.ts -t "fails without meta.json"`

**C4** - `collectedAt("2026-09-27T12:00:00Z")` returns `Dados coletados em 27/09/2026 às 09:00 (horário de Brasília)` and `collectedAt("2026-09-28T02:30:00Z")` returns `Dados coletados em 27/09/2026 às 23:30 (horário de Brasília)` (AC 4)
Proof: `T tests/format.test.ts -t "collection date in Brasília time"`

**C5** - Each of the 11 built pages contains `Dados coletados em 27/09/2026 às 09:00 (horário de Brasília)` (AC 4)
Proof: `T tests/build.test.ts -t "every page states the collection date"`

**C6** - With `MANDATO_DATA_DIR` unset the data directory resolves to `<repo>/data/out`; with it set to a relative path it resolves against `site/`; no file under `site/src/` other than `lib/data.ts` contains `MANDATO_DATA_DIR`, `data/out` or an `import` of a `.json` file (door 2)
Proof: `T tests/data.test.ts -t "data directory"`
Proof: `T tests/data.test.ts -t "is the only reader of the contract"`

### S2 - Home · 5 files · 14 KB · ~4k

**C7** - `sortByName` orders `Zélia (1)`, `Érico (2)`, `Eduardo (3)`, `álvaro (4)` as `álvaro`, `Eduardo`, `Érico`, `Zélia` (AC 5)
Proof: `T tests/search.test.ts -t "orders by accent-stripped name"`

**C8** - The built home, read without running any script, lists exactly two deputies in the order 101, 102, each an `<a href="/deputados/{id}/">` carrying name, party and UF (`Ana Souza`, `PT`, `SP`; `Bruno Lima`, `PT`, `RJ`); both carry the badge `Candidatura em 2026`; 101 carries `<img src="/fotos/101.jpg"`, 102 no image; 103 (out of exercise) is absent (AC 5, AC 11)
Proof: `T tests/build.test.ts -t "home lists in-exercise deputies as static links"`

**C9** - The built home contains none of the fixture indicator values `3 de 4`, `2 de 3`, `2 de 2`, `1 de 1`, `0 de 1`, and its form controls are exactly: a search box, a UF select with options `MG`, `RJ`, `SP`, a party select with options `NOVO`, `PT`, and the checkboxes `Em exercício` (checked) and `Candidatura em 2026` (unchecked) - no ordering control (AC 6, AC 8)
Proof: `T tests/build.test.ts -t "home shows no indicator and no ordering control"`

**C10** - `matchesQuery`: `jose` and `JOSÉ` match `José Bruno`, `sé` matches `José`, `joão` does not match `José`, an empty query matches everyone (AC 7)
Proof: `T tests/search.test.ts -t "search is accent- and case-insensitive"`

**C11** - `filterDeputies` over four deputies each failing exactly one filter (UF, party, in exercise, candidacy): each single active filter drops exactly its deputy, all four active keep only the deputy matching every one, and `initialFilters` is `{query: "", uf: "", party: "", inExercise: true, candidacy: false}` (AC 8)
Proof: `T tests/search.test.ts -t "filters combine"`

**C12** - Server-rendering the search island with only out-of-exercise deputies shows `Nenhum deputado encontrado com esses filtros.` and a button `Limpar filtros`; `clearFilters()` returns a value equal to `initialFilters` (AC 9)
Proof: `T tests/search-island.test.ts -t "empty state offers to clear the filters"`

**C13** - The built home contains `O Mandato Aberto mostra, com dados oficiais da Câmara dos Deputados, o que cada deputado federal fez na 57ª legislatura.` and the totals `3 deputados`, `7 votações nominais`, `5 proposições de autoria` (AC 10)
Proof: `T tests/build.test.ts -t "home states what the site is and the legislature totals"`

### S3 - Deputy profile and photos · 7 files · 22 KB · ~6k

**C14** - Profile 101 shows `Ana Souza`, `PT`, `SP`, `<img src="/fotos/101.jpg"` captioned `Foto: Câmara dos Deputados`, `Em exercício`, and a link to `https://www.camara.leg.br/deputados/101` labelled `Página na Câmara dos Deputados`; profile 103 shows `Fora de exercício` and not `Em exercício` (AC 12)
Proof: `T tests/build.test.ts -t "profile header"`

**C15** - `formatCount(845, 1125)` returns `845 de 1.125` and `formatCount(1234, 56789)` returns `1.234 de 56.789` (AC 13)
Proof: `T tests/format.test.ts -t "n de m with pt-BR thousands"`

**C16** - Profile 101 shows `3 de 4` in the block labelled `Participação em votações nominais do plenário`, `2 de 3` under `Votos iguais à orientação do governo`, `2 de 3` under `Votos iguais à maioria do próprio partido`; profile 102 shows `2 de 2`, `1 de 1`, `2 de 3` in the same three blocks; each block has a bar whose `--share` is `count/total` rounded to 4 places (101: `0.75`, `0.6667`, `0.6667`), a base-of-calculation line and a link to `/metodologia/#participacao`, `/metodologia/#alinhamento-governo`, `/metodologia/#alinhamento-partido` respectively (AC 13)
Proof: `T tests/build.test.ts -t "profile indicators"`

**C17** - Profile 103 (`participation` 0/0, `governmentAlignment` 0/1, `partyAlignment` 0/0) shows `Sem base de cálculo no período` and no bar in the participation and party blocks, and `0 de 1` with a bar in the government block (AC 14)
Proof: `T tests/build.test.ts -t "indicator without base"`

**C18** - Profile 101 shows `5 proposições de autoria`, `2 como primeiro signatário`, `3 requerimentos`, and lists 5 propositions in the contract order `PRC 5/2023`, `PDL 4/2023`, `PEC 3/2023`, `PLP 2/2023`, `PL 1/2023`, each with its summary, its date (`14/03/2023` for `PRC 5/2023`) and a link to its `sourceUrl` (AC 15)
Proof: `T tests/build.test.ts -t "profile authorship"`

**C19** - Profile 101 shows `Candidatura em 2026: DEPUTADO FEDERAL, PT, número 1313 (situação no TSE: APTO)` with a link to the TSE open-data portal; profile 103 contains no `Candidatura em 2026` (AC 16)
Proof: `T tests/build.test.ts -t "profile candidacy"`

**C20** - Profile 101 lists 7 votes linked to `/votacoes/100-4/`, `/votacoes/200-3/`, `/votacoes/100-3/`, `/votacoes/100-2/`, `/votacoes/200-2/`, `/votacoes/200-1/`, `/votacoes/100-1/` in that order, under year headings `2025`, `2024`, `2023`; the `100-1` row shows `01/03/2023`, `Plenário`, `PL 1/2023` and `Sim`; the `100-3` row (no proposition) shows `Votação 100-3`; the `200-1` row shows `CCJC` (AC 17)
Proof: `T tests/build.test.ts -t "profile votes"`

**C21** - `periodLabel` renders a closed period `2023-02-01T12:05:00`-`2024-03-01T00:00:00` as `01/02/2023 a 01/03/2024` and a period ending at the build instant in Brasília as `desde 10/01/2025`; profile 102 shows both of its periods that way and profile 101 shows `desde 01/02/2023` (AC 18)
Proof: `T tests/format.test.ts -t "exercise periods"`
Proof: `T tests/build.test.ts -t "profile exercise periods"`

**C22** - `voteLabel("Artigo 17")` is `Art. 17 (presidente da sessão)`, `voteLabel("")` is `Registro sem voto`, `voteLabel("Sim")` is `Sim`; profile 102 shows `Art. 17 (presidente da sessão)` and profile 101 shows `Registro sem voto` (AC 19)
Proof: `T tests/format.test.ts -t "vote labels"`
Proof: `T tests/build.test.ts -t "profile vote labels"`

**C23** - Profiles 102 and 103 (no cached photo) contain no `<img` and no `Foto: Câmara dos Deputados`; `dist/fotos/101.jpg` is byte-identical to the cached file; `dist/fotos/102.jpg` and `dist/fotos/103.jpg` do not exist (AC 20, door 4, Surface `/fotos`)
Proof: `T tests/build.test.ts -t "missing photo renders nothing"`

**C24** - `fetchPhotos` against a local server: a `200` JPEG writes `{cache}/{id}.jpg` byte-identical and returns its path; a `404`, a `200` whose body does not start with the JPEG marker `FF D8 FF`, and a response slower than the timeout each return `null` and leave no file; an id already cached issues 0 requests; `MANDATO_PHOTOS=off` issues 0 requests and returns only cached paths (door 4, AC 20)
Proof: `T tests/photos.test.ts -t "photo download"`

### S4 - Roll-call page · 3 files · 8 KB · ~2k

**C25** - Page `100-1` shows `01/03/2023`, `Plenário`, `Votação 100-1`, `PL 1/2023` linked to `https://www.camara.leg.br/propostas-legislativas/5001`, `Dispõe sobre X.`, `Aprovada`, the tallies `Sim 2`, `Não 1`, `Outros 0`, and `Orientação do governo: Sim` (AC 21)
Proof: `T tests/build.test.ts -t "roll-call header"`

**C26** - Page `100-2` shows `Rejeitada`; page `100-3` shows `Resultado não informado`, `Sem orientação do governo registrada` and no link to `propostas-legislativas` (AC 21)
Proof: `T tests/build.test.ts -t "roll-call without result, orientation or proposition"`

**C27** - `groupVotes` over eight votes covering `Sim`, `Não`, `Abstenção`, `Obstrução`, `Artigo 17`, `` and names out of order returns the groups `Sim`, `Não`, `Abstenção`, `Obstrução`, `Art. 17`, `Registro sem voto` in that order, each sorted by accent-stripped name (AC 22)
Proof: `T tests/format.test.ts -t "roll-call vote groups"`

**C28** - Page `100-1` lists under `Sim (2)` the links `/deputados/101/` `Ana Souza` `PT-SP` and `/deputados/102/` `Bruno Lima` `PT-RJ`, and under `Não (1)` `/deputados/103/` `Carla Dias` `NOVO-MG` (AC 22)
Proof: `T tests/build.test.ts -t "roll-call votes grouped and linked"`

**C29** - Each of the 7 roll-call pages links its `sourceUrl` (`https://dadosabertos.camara.leg.br/api/v2/votacoes/{id}`) with the text `Registro oficial na Câmara dos Deputados` (AC 23)
Proof: `T tests/build.test.ts -t "roll-call source link"`

### S5 - Share cards and link preview · 5 files · 12 KB · ~3k

**C30** - `dist/cards/deputados/101.png`, `102.png`, `103.png` and `dist/cards/site.png` are PNGs whose IHDR reads 1200x630, and `dist/cards/deputados/` holds no other file (AC 24, AC 26, Surface `/cards`)
Proof: `T tests/build.test.ts -t "share cards are 1200x630 PNGs"`

**C31** - `cardTree` for deputy 101 with a 400x300 photo contains, in reading order, `Ana Souza`, `PT · SP`, `Participação em votações nominais do plenário` `3 de 4`, `Votos iguais à orientação do governo` `2 de 3`, `Votos iguais à maioria do próprio partido` `2 de 3`, `Proposições de autoria` `5`, `Fonte: Câmara dos Deputados - dados abertos`, `Dados coletados em 27/09/2026 às 09:00 (horário de Brasília)`, and one image whose width:height is 4:3; for 102 the three values read `2 de 2`, `1 de 1`, `2 de 3`; for 103 without a photo there is no image and the two zero-base indicators read `Sem base de cálculo no período` (AC 24, door 4 uncropped)
Proof: `T tests/cards.test.ts -t "deputy card content"`

**C32** - Profile 101's head carries `og:title` `Ana Souza (PT-SP) na 57ª legislatura`, an `og:description`, `og:image` `https://preview.example.org/cards/deputados/101.png`, `og:url` and `<link rel="canonical">` `https://preview.example.org/deputados/101/`, and `twitter:card` `summary_large_image` (AC 25)
Proof: `T tests/build.test.ts -t "profile share tags"`

**C33** - With `SITE_URL` unset the site origin is `https://mandatoaberto.org` (AC 25, assumption `SITE_URL`)
Proof: `T tests/data.test.ts -t "site origin defaults to the placeholder domain"`

**C34** - The home and page `100-1` carry `og:image` `https://preview.example.org/cards/site.png`, `twitter:card` `summary_large_image`, `og:title`, `og:description`, and `og:url` equal to their canonical (`https://preview.example.org/`, `https://preview.example.org/votacoes/100-1/`) (AC 26)
Proof: `T tests/build.test.ts -t "home and roll-call share tags"`

### S6 - Descriptive language and provenance · 4 files · 6 KB · ~2k

**C35** - `FORBIDDEN_TERMS` contains at least `faltou`, `faltas`, `ausente`, `aprovação`, `intenção de voto`, `favorito`, `ranking`, `líder`, `chance de reeleição`, `pesquisa`, `mentiu`, `traiu`, `corrupto`, `governista`, `fiel`, `infiel`, `rebelde`, `ausência`, `presença` (AC 27, Impact)
Proof: `T tests/language.test.ts -t "forbidden list holds every required term"`

**C36** - `findForbidden` over every `.astro`, `.vue` and `.ts` file under `site/src/` except `lib/forbidden-terms.ts` returns 0 hits, whole word and case-insensitive; the same scan over a temporary directory holding a `.astro` file with `Faltou` returns 1 hit naming that file and term (AC 27)
Proof: `T tests/language.test.ts -t "site source uses no forbidden term"`
Proof: `T tests/language.test.ts -t "scan catches a forbidden term in a template"`

**C37** - The visible text (markup, `<style>` and `<script>` removed) of each of the 11 built pages contains no forbidden term and no `%` (AC 27, AC 28)
Proof: `T tests/build.test.ts -t "built pages use no forbidden term and no percentage"`

**C38** - No text node of the card trees for 101, 102 and 103 contains `%` (AC 28)
Proof: `T tests/cards.test.ts -t "card shows no percentage"`

**C39** - `loadContract` on a copy of the fixture with `cpf`, `email`, `telefone`, `endereco` and `dataNascimento` injected into `deputies.json[0]`, `deputies/101.json` and `roll-calls/100-1.json` returns records whose keys are exactly the `properties` of the matching `etl/schema/*.json`, and the serialised records contain none of the injected values (AC 29)
Proof: `T tests/data.test.ts -t "reads only schema fields"`

### S7 - Build in CI · 2 files · 3 KB · ~1k

**C40** - `.github/workflows/ci.yml` has a `site` job with `working-directory: site`, Node 24, and the steps `npm ci`, `npm test` and `npm run build` with `MANDATO_DATA_DIR: tests/fixtures/out`, and the `etl` job runs `uv run mandato-etl validate ../site/tests/fixtures/out` (AC 30)
Proof: `grep -A30 '^  site:' .github/workflows/ci.yml | grep -F -e 'npm ci' -e 'npm test' -e 'npm run build' -e 'MANDATO_DATA_DIR: tests/fixtures/out' -e "node-version: 24" -c | grep -qx 5`
Proof: `grep -qF 'mandato-etl validate ../site/tests/fixtures/out' .github/workflows/ci.yml`

**C41** - The CI commands exit 0 locally: `npm --prefix site ci`, `npm --prefix site test`, `MANDATO_DATA_DIR=tests/fixtures/out MANDATO_PHOTOS=off npm --prefix site run build`, `uv run --directory etl mandato-etl validate ../site/tests/fixtures/out` (AC 30)
Proof: `npm --prefix site ci && npm --prefix site test && MANDATO_DATA_DIR=tests/fixtures/out MANDATO_PHOTOS=off npm --prefix site run build && uv run --directory etl mandato-etl validate ../site/tests/fixtures/out`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| `GET /` statuses (1) | 200 C1 | - |
| `GET /deputados/{id}/` statuses (2) | 200 C1 · 404 C1 | - |
| `GET /votacoes/{id}/` statuses (2) | 200 C1 · 404 C1 | - |
| `GET /cards/deputados/{id}.png` statuses (2) | 200 C30 · 404 C30 | - |
| `GET /fotos/{id}.jpg` statuses (2) | 200 C23 · 404 C23 | - |
| Landing doors (8) | URL scheme C1 · data layer C2 · data layer C6 · card renderer C30 · card fonts C30 · photo delivery C23 · photo delivery C24 · fonts self-hosted C41 · static output C41 · dependency set C41 | - |
| contract failure modes (2) | wrong `schema_version` C2 · missing `meta.json` C3 | - |
| home filters and query (5) | UF C11 · party C11 · in exercise C11 · candidacy C11 · query C10 | - |
| indicators on the profile (4) | participation C16 · governmentAlignment C16 · partyAlignment C16 · authorship C18 | - |
| indicator base (3 cases) | `total > 0` C16 · `count = 0, total > 0` C17 · `total = 0` C17 | - |
| roll-call result (3) | `true` C25 · `false` C26 · `null` C26 | - |
| government orientation (2) | present C25 · absent C26 | - |
| roll-call vote groups (6) | `Sim` C27 · `Não` C27 · `Abstenção` C27 · `Obstrução` C27 · `Art. 17` C27 · `Registro sem voto` C27 | - |
| vote labels (3) | `Artigo 17` C22 · empty C22 · other C22 | - |
| exercise period (2) | closed C21 · open C21 | - |
| photo download outcomes (6) | 200 JPEG C24 · 404 C24 · non-JPEG body C24 · timeout C24 · cached C24 · downloads off C24 | - |
| pages carrying share tags (3 kinds) | profile C32 · home C34 · roll call C34 | - |
| pages carrying the collection date (3 kinds) | home C5 · profile C5 · roll call C5 | - |
| forbidden terms (19) | C35, table-driven over all 19 · source scan C36 · built pages C37 | - |
| personal fields never read (5) | `cpf` C39 · `email` C39 · `telefone` C39 · `endereco` C39 · `dataNascimento` C39 | - |
| startup config: data dir and site origin (2 assemblies) | `astro build` in the build test C1 · CI build C41 | - |

- Claims naming a route or a status: C1, C23, C30 - each is proven on the built `dist/`, the files a static host serves
- No other check claims more than the cases its proof exercises

## Swept

- validation: C2, C3 - the contract version and presence are the only input the build validates; field shapes are the ETL's `mandato-etl validate` (C40)
- failure modes: C2, C3, C24
- idempotency: C24 - a cached photo is not fetched again; the build is otherwise a pure function of `data/out/`
- authorization: n/a - public static site, no accounts
- concurrency: n/a - one build process; photo downloads are independent files keyed by id
- data lifecycle: C39 - the site persists nothing but `site/.cache/photos/`, which holds only the official photo
- dependency failure: C24 - a Câmara photo outage renders pages without the photo; the contract is local
- state transitions: n/a - no stateful entity; the home filters are view state (C11, C12)
- observability: n/a - no logging requirement; a failed photo download prints one warning line, not asserted

## Handoff

- Size: S1 4k + S2 4k + S3 6k + S4 2k + S5 3k + S6 2k + S7 1k = 22k of new code and tests, plus ~30k to read `plan.md`, this file, the schemas and the fixture = 52k, under the 150k budget - one builder
- Mechanism: one builder (fits; no ask)
- Landing amended before build, by the maintainer's choice on 2026-09-27: `@fontsource-variable/*` ship only `woff2`, which satori cannot read; the card renderer loads `woff` files from `@fontsource/source-serif-4` and `@fontsource/inter` (added as runtime dependencies), the pages keep the variable packages
