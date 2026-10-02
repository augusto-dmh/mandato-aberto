# app-home: home, search across both houses, and the legislature overview

## Problem

The v2 app has a page per member and per roll call (app-skeleton, app-contract-v3) and no way in. A visitor who arrives at the root address finds nothing. Someone who knows a senator's name but not the Senate's numeric id cannot reach the page. Someone who wants to know what the 58th legislature has done so far, in either house, has only the official portals, one roll call at a time. Every v2 page, and every link the launch on 2027-02-01 (v2 grilling decision 2) sends out, assumes a home and a search exist, and no approved plan covers them: app-skeleton and app-contract-v3 both list "Home, search, listings" as out of scope.

The MVP's answers do not carry over as they are. Its home shipped every deputy to the browser and filtered there (research 02, "Busca: índice no cliente sobre `deputados.json`", 513 records). It weighed about 311 KB against 33 KB for Meu Congresso and 40 KB for Placar (research 06, section 2). The v2 has two houses and two legislatures, about 594 seats and more member-legislature rows once substitutes are counted. Its Vue and Inertia client bundle alone is 58,270 bytes gzip (measured on the app-skeleton build, 2026-10-02), before any data. The research also asks for an aggregate view of the legislature (a1 section 6.4) and warns that its most tempting chart, a participation histogram with a "where is [name]" marker, compares people even without names (research 06 section 8 item 2).

Who pays: the maintainer, against a fixed date, and every visitor on a phone network. The source gives no other evidence.

When this ships, `/` says in one sentence what the site is and offers a search form that works without JavaScript. `/busca/` lists deputies and senators in alphabetical order, filtered by house, UF, party, legislature and whether they are in exercise, 50 per page, with no indicator anywhere on the list. `/legislaturas/{n}/` shows, per house, how many nominal, secret and symbolic plenary roll calls happened, how many members held a mandate and how many propositions they presented, each number with its note. It also shows a month-by-month calendar of roll calls and the latest roll calls. It names no person. None of the three pages sends a script or a cookie, and each first load stays under a byte budget a test measures.

## Flow

This reuses the cookie-free `public` middleware group and the server-written share tags (app-skeleton doors 8 and 9), the v3 stored schema and the path builders for member and roll-call pages (app-contract-v3 doors 3 and 4), the anchors of `/metodologia/` (app-contract-v3 AC 49), the `Collator('pt_BR')` ordering the skeleton already uses for vote groups, and the design package's `SourceNote` and tokens. No count is computed differently from the ETL's definitions, and no new table is created.

1. `GET /`, `/busca/?...`, `/legislaturas/{n}/` -> routes (door 1) in the `public` group (exists, skeleton door 9)
2. controllers (new, no door - placement per conventions) read `Legislature`, `Member`, `Membership`, `ExercisePeriod`, `RollCall`, `Proposition`, `Authorship`, `ContractImport` (exist, app-contract-v3 door 3)
3. search only: SQL narrows memberships to one legislature and the house, UF and party filters; the name match and the order run in PHP over that set (door 3); the page slice of 50 goes on
4. Inertia response with the `meta` prop (exists, skeleton door 8), now carrying `robots` and `hydrate` (door 2)
5. Blade root view (exists) writes the head tags; the Inertia SSR server (exists) renders the body from `mandato-design` components (exists); when SSR returned a body and `hydrate` is false, the view links the stylesheet and no client script (door 2)
6. out: complete HTML with no `<script src>`; if SSR is down, the same head tags plus the client entry, which renders the body in the browser

## Impact

| Front | What changes |
| --- | --- |
| domain | new term: `current legislature` - the highest legislature number held by any stored membership, either house. Not the same as app-contract-v3's "default mandate" (the highest legislature one member holds), which stays per member |
| domain | new term: `in exercise` (`em exercício`) - a membership of the current legislature with an exercise period ending on or after the Brasília date of its house's latest `ContractImport.generatedAt`. Contract-v3 AC 11 closes an open period at build time, so a member still sitting has a period ending on the import day. Nobody branches on it today; meus-eleitos AC 22 may reuse it |
| domain | new term: `plenary roll call` in the app - Câmara roll calls with `organ` `PLEN` (the value of `compute.PLENARY` in the ETL), and every Senate roll call (etl-senado emits plenary roll calls only). Today only the ETL branches on it, in Python |
| `app.blade.php` (skeleton door 8) | gains an optional `meta.robots` tag and the `hydrate` switch (door 2). Every existing page omits both and keeps today's output byte for byte |
| `PublicLayout.vue` | the wordmark becomes a link to `/`, and the masthead gains `Buscar parlamentar` linking to `/busca/`, on every public page. The footer of the three new pages names both houses (AC 42). The member and roll-call footers stay per house (app-contract-v3 AC 32) |
| stored data | nothing: no migration, no import change. Every count is read from rows app-contract-v3 already stores |
| `design/`, `etl/`, `site/` | untouched. The overview's figures are a number plus `SourceNote`; if building finds a recurring unit, it moves into `design/` additively in its own commit |
| CI | nothing new; job `app` runs the new tests |
| dependency on other features | builds after app-contract-v3 (the v3 schema, Senate member paths, `/metodologia/` anchors), which builds after app-skeleton and contract-v3. Real Senate data needs etl-senado; tests do not. Presidência numbers wait for the app to read contract v4 (etl-presidencia door 1) |

## Relations

`None - no stored-data shape change`. The three pages read the app-contract-v3 schema as it is.

## Surface

Only routes this adds. All are `GET`, in the cookie-free `public` group, and answer with and without the trailing slash (skeleton AC 28).

| Route | In | Out | Status |
| --- | --- | --- | --- |
| `GET /` | none | HTML (Inertia `Home/Index`) · `meta` prop · `X-Inertia` JSON on Inertia visits | `200` |
| `GET /busca/` | query `q`, `casa`, `uf`, `partido`, `legislatura`, `situacao`, `pagina`, all optional | HTML (Inertia `Search/Index`): effective filters, filter options, result rows `{house, id, name, party, uf, href}`, `page`, `pages`, `total` · `meta` prop with `robots` | `200`, `404` |
| `GET /legislaturas/{n}/` | `n` digits, a stored legislature | HTML (Inertia `Legislatures/Show`): per house counts with notes, months, recent roll calls · `meta` prop | `200`, `404` |

## Landing

| One-way door | Literal shape | Alternative rejected |
| --- | --- | --- |
| 1. Public URL shapes for search and the overview | `Route::get('/', ...)->name('home')`; `Route::get('/busca/', ...)->name('search')` with query keys `q`, `casa` (`camara`, `senado`), `uf` (two-letter code), `partido`, `legislatura` (digits), `situacao` (`exercicio`, `todos`), `pagina` (digits); `Route::get('/legislaturas/{n}/', ...)->whereNumber('n')->name('legislatures.show')`; no `/legislaturas/` index route | results on `/?q=`: the home's canonical and preview would change with the query, and the home would have to be `noindex`. English query keys (`house`, `party`): the paths are pt-BR (`/deputados/`, `/votacoes/`), and a shared search link is read by people. Singular `/legislatura/{n}/`: top-level collections are plural (`/deputados/`, `/senadores/`, `/votacoes/`); the singular is already the member-scoped segment (`/deputados/{id}/legislatura/{n}/`) |
| 2. Pages that send no client script | `meta` prop gains optional `robots` (string) and `hydrate` (bool, default `true`); `app.blade.php` renders `<meta name="robots" content="{{ $meta['robots'] }}">` only when set; when `hydrate` is `false` and the SSR gateway returned a body, the view emits `@vite('resources/css/app.css')` only, otherwise `@vite(['resources/js/app.js'])` as today. `Home/Index`, `Search/Index`, `Legislatures/Show` pass `hydrate: false`. How the view learns that SSR answered is settled against inertia-laravel 3.5 while building (its v1 and v2 Blade directives keep the result in `$__inertiaSsrResponse`; not verified for v3) | hydrating every page: 58,270 bytes gzip of JS for pages whose only interaction is a native GET form, more than the whole home of either competitor measured (33 and 40 KB). Blade-only templates for these three pages: a second rendering of `SourceNote` and the tokens outside `mandato-design`, which skeleton door 2 rejected because two copies drift. Dropping the script even when SSR fails: the body would be empty |
| 3. How search matches and orders | `App\Support\SearchKey::of(string $s): string` = Unicode NFD, remove combining marks (`\p{Mn}`), `mb_strtolower`, collapse runs of whitespace, trim. A row matches when every space-separated token of `SearchKey::of($q)` is a substring of `SearchKey::of($member->name)`. SQL selects the memberships of one legislature with the house, UF and party filters; PHP filters by name and sorts with `Collator('pt_BR')` on the name, then house (`camara` before `senado`), then source id; 50 rows per page. Re-decide when a search spans more than about 5,000 rows, such as propositions: then a stored key with an index | PostgreSQL `unaccent`: an extension to install on the production database, not `IMMUTABLE` so it cannot back an index, and a second normaliser that can disagree with the PHP one on the same name. Full-text search (`tsvector`, `portuguese`): stems names ("Silva", "Santos") and orders by relevance, which is an ordering other than alphabetical. `pg_trgm`: orders by similarity, same objection, plus an extension. A client index of every member: about 1,200 member-legislature rows plus the 58 KB runtime to filter them, and the page would still need this server path to work without JavaScript |

- Nothing else in this change is hard to reverse

## Criteria

### S1: The home says what the site is and starts a search (P1)

`/` is one sentence of purpose, a search form that works without JavaScript, and the current legislature's headline counts.

**Acceptance Criteria**

1. WHEN `GET /` is requested THEN the system SHALL respond 200 with Inertia component `Home/Index`, the only `<h1>` reading `O que cada parlamentar federal fez no mandato`, and below it the paragraph `O Mandato Aberto mostra como cada parlamentar votou e o que apresentou na Câmara dos Deputados e no Senado Federal, com dados abertos das duas Casas e a fonte e o método de cada número.`
2. WHEN the home renders with at least one `ContractImport` THEN it SHALL contain `<form role="search" method="get" action="/busca/">` with a text input `q` labelled `Nome`, selects `casa` (`Todas as Casas`, `Câmara dos Deputados`, `Senado Federal`), `uf` (`Todas as UFs`, then the 27 UF codes in alphabetical order), `partido` (`Todos os partidos`, then the distinct parties of current-legislature memberships in pt-BR alphabetical order), `legislatura` (one option per stored legislature with a membership, `{n}ª legislatura ({start year}–{end year})`, newest first, the current one selected), `situacao` (`Em exercício` selected, `Todos`), and a submit button `Buscar`
3. WHEN a house has a `ContractImport` listing the current legislature THEN the home SHALL render, under a heading with that house's name, `{n} votações nominais no plenário` and `{n} parlamentares com mandato na legislatura` with the values of AC 22, each with the `SourceNote` of AC 23, followed by one link `Visão geral da {n}ª legislatura` to `/legislaturas/{n}/`
4. IF no `ContractImport` exists THEN the home SHALL render `Ainda não há dados importados.` in place of the form and the counts
5. WHEN the home renders THEN the head SHALL hold `<title>O que cada parlamentar federal fez no mandato - Mandato Aberto</title>`, `og:title` without the suffix, the AC 1 paragraph in `description` and `og:description`, `canonical` and `og:url` equal to `{APP_URL}/`, and no `robots` tag

**Independent test:** import the v3 fixtures of both houses, request `/`, read the `<h1>`, the form's fields and options, and the counts; empty the database and request it again.

### S2: Search lists members alphabetically, without JavaScript (P1)

`/busca/` answers a plain GET form with the matching deputies and senators of one legislature, in alphabetical order, with no indicator.

**Acceptance Criteria**

6. WHEN `GET /busca/` is requested with no query parameter THEN the system SHALL respond 200 with Inertia component `Search/Index` listing the in-exercise memberships of the current legislature in both houses, page 1, and the line `{total} parlamentares, em ordem alfabética` (`1 parlamentar, em ordem alfabética` when the total is 1)
7. WHEN a result row renders THEN it SHALL be one link to the member page of that legislature (the bare `/deputados/{id}/` or `/senadores/{id}/` for the current legislature, `/{deputados|senadores}/{id}/legislatura/{n}/` for an earlier one) holding the name, `Câmara dos Deputados` or `Senado Federal`, and `{party} · {uf}` of that membership, and SHALL hold no indicator value, no count and no image
8. WHEN `q` is given THEN the system SHALL keep only rows whose name contains every space-separated token of `q` ignoring case and accents, in any order: `joao`, `JOÃO` and `silva joão` each match `João da Silva`, and `joao santos` does not
9. WHEN `q` contains `%`, `_` or `\` THEN the system SHALL treat them as literal characters, so `%` matches only names that contain `%`
10. WHEN `q` is longer than 100 characters after trimming THEN the system SHALL use its first 100 characters
11. WHEN `casa`, `uf` or `partido` holds an allowed value THEN the system SHALL keep only memberships of that house, of that UF, or of that party in the chosen legislature, all given filters combined
12. WHILE `situacao` is `exercicio` or absent and the chosen legislature is the current one, the system SHALL list only in-exercise memberships (Impact, `in exercise`); WHEN `situacao` is `todos` THEN it SHALL list every membership of the chosen legislature
13. WHEN `legislatura` names a stored legislature other than the current one THEN the system SHALL list every membership of that legislature whatever `situacao` says, and SHALL render `A {n}ª legislatura terminou em DD/MM/AAAA; a lista mostra todos que tiveram mandato nela.`
14. IF `casa`, `uf`, `partido`, `legislatura` or `situacao` holds a value outside its allowed set (house codes, the 27 UF codes, the parties of the chosen legislature, the stored legislatures, `exercicio` and `todos`) THEN the system SHALL respond 200 as if that parameter were absent, and the form SHALL show that field's default option selected
15. The system SHALL order rows by name with the pt-BR collation, then `camara` before `senado`, then source id, and SHALL offer no other order
16. WHEN the result has more than 50 rows THEN the page SHALL show 50 rows, the text `Página {p} de {pages}`, and links `Página anterior` and `Próxima página` (each absent on the first and last page) that keep every other parameter
17. IF `pagina` is not an integer from 1 to `pages`, and the result is not empty, THEN the system SHALL respond 404 with the skeleton's `Página não encontrada` page
18. IF filters are valid and no row matches THEN the page SHALL render `Nenhum parlamentar encontrado com esses filtros.` and a link `Limpar filtros` to `/busca/`, and no list
19. IF no `ContractImport` exists THEN `/busca/` SHALL respond 200 with `Ainda não há dados importados.` and no form
20. WHEN `/busca/` renders THEN it SHALL repeat the AC 2 form with every field showing its effective value, and the text of `q` SHALL appear in the HTML only as that input's `value` and in the `data-page` props, never in the title, a heading, the count line or a meta tag
21. WHEN `/busca/` renders THEN the head SHALL hold `<title>Buscar parlamentares - Mandato Aberto</title>`, the description `Busque deputados federais e senadores por nome, Casa, UF, partido e legislatura, em ordem alfabética.` in `description` and `og:description`, `canonical` and `og:url` equal to `{APP_URL}/busca/` with no query string, and `<meta name="robots" content="noindex, follow">`

**Independent test:** import fixtures holding an accented name, a senator and a deputy with the same surname, a member out of exercise and a member with mandates in 57 and 58. Request `/busca/?q=joao`, `?casa=senado&uf=SP`, `?situacao=todos`, `?legislatura=57`, `?uf=XX`, `?pagina=99` and `?q=zzz`, and read rows, order, notes and statuses.

### S3: The legislature overview counts what each house did (P1)

`/legislaturas/{n}/` gives each house's activity in the legislature as counts with notes, a calendar by month and the latest roll calls, and names no person.

**Acceptance Criteria**

22. WHEN `GET /legislaturas/{n}/` is requested for a stored legislature THEN the system SHALL respond 200 with Inertia component `Legislatures/Show`, the only `<h1>` `{n}ª legislatura`, the line `De DD/MM/AAAA a DD/MM/AAAA` with its stored dates, and one section per house, Câmara first, headed `Câmara dos Deputados` and `Senado Federal`, each rendering in this order: `{n} votações nominais no plenário`, `{n} votações secretas no plenário`, `{n} votações simbólicas no plenário` (plenary roll calls of that house and legislature with `ballot` `nominal`, `secret`, `symbolic`), `{n} parlamentares com mandato na legislatura` (memberships of that house and legislature), and `{n} proposições apresentadas por parlamentares (PL, PLP, PEC, PDL e PRC)` (`PRS` in place of `PRC` for the Senate: distinct propositions of those types with an authorship on a membership of that house and legislature)
23. WHEN a count of AC 22 renders THEN it SHALL carry a `SourceNote` with `sourceLabel` `Câmara dos Deputados` or `Senado Federal`, `sourceUrl` `https://dadosabertos.camara.leg.br/` or `https://legis.senado.leg.br/dadosabertos/`, `collectedAt` the house's latest `ContractImport.generatedAt`, and `methodUrl` `{APP_URL}/metodologia/#tipos-de-votacao` for the three roll-call counts, `#cobertura` for members and `#proposicoes` for propositions
24. IF the house's latest `ContractImport.coverage` row for the legislature has a null symbolic count THEN the section SHALL render `Votações simbólicas no plenário: não publicadas pela Casa.` in place of that count and no number
25. WHEN a house has plenary `nominal` or `secret` roll calls in the legislature THEN its section SHALL render a calendar headed `Votações nominais e secretas no plenário, por mês`: one row per year, one cell per month from the legislature's start month to the month of the house's latest such roll call, each cell holding the month abbreviation (`jan` to `dez`) and the number of those roll calls written out, a month with none showing `0`, and a bar whose height is that number over the house's highest month, in a neutral graphic colour (never the accent)
26. WHEN the calendar renders THEN the section SHALL also render the equivalent table with columns `Mês`, `Votações`, `Dias com votação`, one row per calendar cell in date order, `Dias com votação` counting distinct dates with at least one of those roll calls
27. WHEN a house has plenary `nominal` or `secret` roll calls in the legislature THEN its section SHALL render, headed `Votações nominais e secretas mais recentes`, its 10 latest such roll calls by date and then id, newest first, each with the date `DD/MM/AAAA`, the heading of app-contract-v3 AC 41, `{ballot label} · {kind label}` with app-contract-v3 AC 42 labels, the result `Aprovada`, `Rejeitada` or `Resultado não informado`, and a link to its roll-call page
28. IF a house has no `ContractImport` listing legislature `n` THEN its section SHALL render only `Ainda não há dados da Câmara dos Deputados para a {n}ª legislatura.` (or `do Senado Federal`)
29. IF a house's import lists legislature `n` and it has no plenary `nominal` or `secret` roll call in it THEN the section SHALL render the AC 22 counts and, in place of the calendar and the list, `Nenhuma votação nominal ou secreta no plenário até DD/MM/AAAA.` with the Brasília date of that import's `generatedAt`
30. WHEN more than one legislature is stored THEN the page SHALL render a `<nav aria-label="Legislaturas">` listing each as `{n}ª legislatura ({start year}–{end year})`, newest first, each linking to `/legislaturas/{n}/`, the rendered one marked `aria-current="page"`
31. The overview SHALL render no member name, no link to `/deputados/` or `/senadores/`, no per-member value, and no chart or table whose rows or bins are members or counts of members by an indicator
32. IF `{n}` is not all digits or not a stored legislature THEN the system SHALL respond 404 with the skeleton's `Página não encontrada` page
33. WHEN the overview renders THEN the head SHALL hold `<title>{n}ª legislatura: votações e proposições na Câmara e no Senado - Mandato Aberto</title>`, `og:title` without the suffix, the description `Quantas votações nominais, secretas e simbólicas e quantas proposições houve na {n}ª legislatura, por Casa, com dados oficiais e a fonte de cada número.` in `description` and `og:description`, and `canonical` and `og:url` equal to `{APP_URL}/legislaturas/{n}/`

**Independent test:** import the Câmara fixture only and request `/legislaturas/57/`: the Câmara counts equal hand counts over the fixture, the calendar cells match a `GROUP BY` month, the Senate section reads `Ainda não há dados`. Import the Senate fixture and request again: the symbolic line reads `não publicadas pela Casa`. Grep the HTML for `/deputados/`.

### S4: Light, cookie-free, complete without JavaScript (P1)

The three pages keep the skeleton's guarantees and stay inside a byte budget.

**Acceptance Criteria**

34. The system SHALL send no `Set-Cookie` header on any response of `/`, `/busca/`, `/legislaturas/{n}/` or their 404s
35. WHILE the Inertia SSR server is running THEN the initial HTML of the three pages SHALL contain the `<h1>` text, every count of AC 3 and AC 22, and every result row of AC 7 inside the app root element, and SHALL contain no `<script src>` and no `<link rel="modulepreload">`
36. IF the SSR server is unreachable THEN the three pages SHALL respond 200 with every head tag of AC 5, AC 21 and AC 33 and SHALL include the client entry script
37. WHEN `/busca` or `/legislaturas/{n}` is requested without the trailing slash THEN the system SHALL respond 200 with the same canonical as the slash form
38. The HTML of the three pages SHALL contain none of `importante`, `importantes`, `relevante`, `relevantes`, `mais votado`, `menos votado`, `posição`, nor any term of the skeleton's forbidden list, as whole words, case- and accent-insensitively, outside official text quoted from a house
39. WHEN rendered over the budget dataset (Observable) with SSR running THEN the gzip-compressed HTML SHALL be at most 20,000 bytes for `/`, 25,000 bytes for `/busca/` and 30,000 bytes for `/legislaturas/{n}/`
40. The stylesheets the three pages link SHALL total at most 8,000 bytes gzip-compressed
41. WHEN each of the three pages is loaded in Chromium over the budget dataset with an empty cache THEN the sum of the transfer sizes of every response SHALL be at most 125,000 bytes for `/`, 130,000 bytes for `/busca/` and 135,000 bytes for `/legislaturas/{n}/`, and no request SHALL go to another origin

**Independent test:** with SSR up, `curl` each page, grep for `<script src` and the counts, gzip the body and compare with the budget; stop SSR and `curl` again; load each page in Playwright with cache disabled and sum `transferSize`.

### S5: Every page leads home and to search (P2)

The masthead and footer that every public page shares point to the new pages and name both houses where a page spans both.

**Acceptance Criteria**

42. WHEN the home, search or overview renders THEN its footer SHALL read `Dados abertos da Câmara dos Deputados, coletados em DD/MM/AAAA, e do Senado Federal, coletados em DD/MM/AAAA.`, naming only the houses that have a `ContractImport`, with the Brasília date of each house's latest `generatedAt`, and no footer data line when none exists
43. The masthead of every public page SHALL render the wordmark `Mandato Aberto` as a link to `/` and a link `Buscar parlamentar` to `/busca/`

**Independent test:** request a member page, a roll-call page and the three new pages, and read the masthead links and footer lines; import only the Câmara and read the footer again.

## Out of scope

| Excluded | Why |
| --- | --- |
| Participation histogram, or any distribution of a per-member indicator, on the overview or anywhere aggregate | research 06 section 8 item 2 asks for a decision; this plan decides against it (Assumptions). A distribution with a "where is [name]" marker is a percentile for a named person, which the card already forbids (a1 6.3, "nunca no card: ... percentil"). Participation bases differ by exercise periods, so bins mix unequal denominators. GovTrack withdrew its report cards in 2024 after "most liberal" became campaign material. The research 02 premise holds: "lista com percentuais lado a lado é quase ranking". The profile already compares a member with their own base (P5, n of m) |
| Flows (bills presented -> approved -> sanctioned or vetoed; MPs -> converted or lapsed; vetoes -> kept or overridden) and Presidência counts | MP and veto fates arrive in contract v4 (etl-presidencia door 1, March 2027 at the earliest); the proposition `status` in v3 is free text and the Câmara's diverges from itself (etl-presidencia Problem: MP that became Lei 14.696/2023 marked "Aguardando Encaminhamento") |
| A day-by-day calendar, a roll-call listing route, day or month drill-down | no plan defines roll-call listings; the month cells carry their number in text and the 10 latest link to their pages; listings belong with exploration (v2 grilling decision 5, delivery 3) |
| Type-ahead results while typing | the GET form is the contract; live narrowing needs the client runtime door 2 keeps off these pages and a browser harness the app does not have |
| Proposition search | its own surface; door 3 names the point where it needs a stored key |
| Photos in search results | photo-cache feature (skeleton and app-contract-v3 out of scope); text rows keep the page in budget |
| Share card images (`og:image`) and `sitemap.xml` | card feature and the deploy feature |
| Candidacy 2026 filter | the election ends 2026-10-26, before the app is public; candidacy data stays out of the database (skeleton) |
| Pages per party or UF, comparator, editorial retrospective | MVP decision 11, v2 delivery 3, and a1 6.4's optional scrollytelling, each with its own decision |
| A `/legislaturas/` index or a "current legislature" alias path | the home links the current overview; an alias would change what a shared link shows on 2027-02-01 |

## Assumptions

| Assumption | Chosen default | Rationale | Confirmed? |
| --- | --- | --- | --- |
| Histogram decision (research 06 section 8 item 2) | rejected; on approval, record in `.specs/STATE.md` as the next AD: "aggregate pages show counts of a house's activity, never a distribution or ordering of a per-member indicator" (AC 31) | see Out of scope, first row; the rule binds later features (comparator, exploration), so it belongs in STATE, not only here | n |
| Search mechanism | server-side, plain GET; SQL filters, PHP accent-insensitive token match and pt-BR collation over one legislature's memberships (door 3, AC 8 to 15) | about 600 to 700 member rows per legislature; works without JavaScript and without a cookie; one normaliser; alphabetical order cannot be displaced by a relevance score | n |
| Results on their own route, `noindex, follow` | `/busca/` (door 1, AC 21) | the home keeps one canonical and preview; search pages are not indexed but their links are followed until a sitemap exists; the query never reaches a share preview, so a crafted link cannot put words in the site's title | n |
| Query echo | `q` appears only as the input's value (AC 20) | "Nenhum parlamentar encontrado para 'X'" with arbitrary X makes a screenshot that reads as the site's statement | n |
| Page size | 50 rows (AC 16) | 594 seats make 12 pages; 50 text rows keep `/busca/` under 25 KB gzip | n |
| No client script on the three pages | `hydrate: false` with the SSR-down fallback (door 2, AC 35, 36) | the budget cannot hold with 58 KB of JS; nothing on these pages needs it | n |
| Performance budget | HTML gzip 20 / 25 / 30 KB; CSS 8 KB; no JS; total first load 125 / 130 / 135 KB in Chromium, empty cache (AC 39 to 41) | the Archivo latin subset (90,104 bytes, AD-015) is most of it; the totals are 57 to 60% below the MVP's 311 KB. Matching 33 to 40 KB would need system fonts, which AD-015 rules out | n |
| Calendar granularity | months, with the number written in each cell and a table (AC 25, 26) | a day grid is about 1,460 cells per house, ten times the HTML, and is readable only by hovering; the month cell works in a screenshot and without interaction (P10, P11) | n |
| Recent roll calls | the 10 latest plenary `nominal` or `secret` per house (AC 27) | symbolic decisions outnumber them (5,499 symbolic Câmara plenary roll calls in the contract-v3 snapshot) and would fill the list with rows that record no vote | n |
| Party and UF shown and filtered | the membership's own party and UF in the chosen legislature (AC 7, 11) | a member who changed party appears under the party of that mandate, as the member page shows it (app-contract-v3 AC 23) | n |
| A person in both houses | two rows, one per member record | contract-v3 excludes cross-house identity (AD-003 rules out CPF); linking them would be a guess | n |
| Caching | none; every request queries | the heaviest query groups a few thousand roll calls by month; a cache keyed by the latest import is a later commit if timing shows a need | n |
| Verification profile | `ui` | three screens whose risk is copy and arrangement (anti-ranking, empty states), as app-contract-v3 chose; `light` would not enumerate copy per screen | n |

**Open questions:** none - all resolved or logged above.

## Observable

| Surface | Decision | Landing |
| --- | --- | --- |
| screen `home` | empty state | AC 4 (no import); AC 3 (a house without the current legislature shows no counts) |
| screen `home` | loading state | n/a - server-rendered complete page (AC 35); no client fetch |
| screen `home` | error state | AC 36 (SSR down still serves the page) |
| screen `home` | unauthorised state | n/a - public, read-only, no account |
| screen `home` | density and ordering | AC 2 (option orders), AC 3 (houses Câmara then Senado) |
| screen `home` | destructive action confirms | n/a - no action changes data |
| screen `search` | empty state | AC 18 (no match), AC 19 (no import) |
| screen `search` | loading state | n/a - a GET form answered by a server-rendered page (AC 35) |
| screen `search` | error state | AC 17 (404 page out of range), AC 14 (invalid filter ignored), AC 36 |
| screen `search` | unauthorised state | n/a - public, read-only |
| screen `search` | density and ordering | AC 15 (alphabetical only), AC 16 (50 per page) |
| screen `search` | destructive action confirms | n/a - no action changes data |
| screen `overview` | empty state | AC 28 (house without data), AC 29 (no roll call yet), AC 24 (symbolic not published) |
| screen `overview` | loading state | n/a - server-rendered (AC 35) |
| screen `overview` | error state | AC 32 (404), AC 36 |
| screen `overview` | unauthorised state | n/a - public, read-only |
| screen `overview` | density and ordering | AC 22 (count order), AC 25 (months in date order), AC 27 (newest first), AC 30 (legislatures newest first) |
| screen `overview` | destructive action confirms | n/a - no action changes data |
| collection `search results` | grouping, naming, ordering | AC 7, AC 15; no grouping by house or party, one list |
| collection `search results` | duplicates | one row per membership of the chosen legislature (skeleton unique key member + legislature); a person in both houses is two members (Assumptions) |
| collection `search results` | the exception that does not fit | AC 13 (past legislature: in exercise does not apply) |
| document `home` copy | structure, tone, depth, what the reader does next | AC 1 (one sentence), AC 2 (search), AC 3 (counts, then the overview link) |
| document `overview` copy | structure, tone, what the reader does next | AC 22 to 27: counts with notes, then the method anchors (AC 23) and the roll-call pages (AC 27); AC 38 vocabulary |
| all new routes | response shape | AC 1 to 3, AC 6 and 7, AC 22 to 27; head tags AC 5, AC 21, AC 33 |
| all new routes | error shape and codes | AC 17, AC 32 (HTML 404 in pt-BR); AC 14 (invalid filter is not an error) |
| all new routes | who may call it | n/a - public pages, cookie-free (AC 34) |
| all new routes | versioning | door 1 and AC 37: path and query shapes do not version |
| all new routes | rate limits | n/a - each search reads one legislature's memberships (under 1,000 rows); limits sit in front of the app (skeleton, deploy feature) |
| budget dataset for AC 39 to 41 | what the measurement runs on | a seeded database with legislatures 57 and 58; in the current one, 600 Câmara and 81 Senate memberships with names of up to 40 characters, 30 parties, and 1,500 plenary `nominal` or `secret` roll calls per house spread over 48 months; `/busca/` measured with no parameter (page 1 of 50 rows) |

## Sources

- `research/02-grilling-escopo-mvp.md` "Premissas aplicadas sem perguntar", rows Home and Ordenação padrão - search plus filters, a one-sentence purpose, aggregate legislature numbers, alphabetical order, no list ordered by an indicator
- `research/06-pesquisa-design-e-concorrentes.md` section 2 (page weight 311 vs 33 and 40 KB), section 3 P1, P10, P11, P13, and section 8 item 2; `research/design-anexos/a1-referencias-de-design.md` section 6.4 - the overview's content and the histogram question
- `.worktrees/app-skeleton/.specs/features/app-skeleton/plan.md` doors 8 to 10 and `.worktrees/app-contract-v3/.specs/features/app-contract-v3/plan.md` doors 3 and 4, AC 41, 42 and 49 (both approved 2026-10-02) - share tags, cookie-free group, stored schema, member and roll-call paths, labels and methodology anchors this feature reads
