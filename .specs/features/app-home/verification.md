# app-home verification

**Verdict**: FAIL
**Profile**: ui
**Diff range**: 645a5fc..a76e907414887bdaa5fab4f0685a3bb82e792443
**Round**: 1 - full
**Verifier**: independent sub-agent (author != verifier)

Verified at `a76e907`. The feature's own changes are `git diff a76e907^2 a76e907` (29 files: three controllers, `HouseActivity`, `SearchForm`, `SearchKey`, `PublicUrl`, `Labels::sourcesLine`, `BudgetSeeder`, `app.blade.php`, `PublicLayout.vue`, three pages, two components, `app.css`, four test files, `Pest.php` helpers, `tests/budget/first-load.mjs`, README, AGENTS, STATE). Merge resolutions: `8957670` conflicted only on `.specs/STATE.md` and kept AD-016 to AD-018 (contract-v3) and AD-019 (this plan) in number order; `a76e907` merged with no conflict hunk (`git show --cc a76e907` gives 0 `@@@` hunks). `design/`, `etl/` and `site/` are untouched by this feature's own diff, as the plan's Impact table says.

All 51 checks are proven at `HEAD`: every named test exists once, ran and passed. All 5 injected faults were killed. The verdict is FAIL because of two arrangement gaps. The plan's Observable rows put the home and overview structure in a fixed order, and no check asserts that order (verify.md: "an element or an arrangement a binding source decides that no check covers" fails the feature).

## Binding sources

The plan names no source as "binding". This report treats as binding the sources the brief named and the plan cites for these screens: the plan's Criteria and Observable rows (there is no design screen for the home, search or overview; `design/screens/` holds only `Card`, `Chrome`, `Profile` and `RollCall`), `research/06` sections 2, 3, 4 and 8 item 2, `.specs/STATE.md` AD-019, `design/README.md` (tokens and colour roles, `SourceNote`), `AGENTS.md` (forbidden vocabulary) and `app/README.md`.

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| plan S1 + Observable `document home copy` ("structure ... AC 1 (one sentence), AC 2 (search), AC 3 (counts, then the overview link)") - screen `/` | yes - `.specs/features/app-home/plan.md` lines 64-76, 214 | none | home order lede -> search form -> house blocks: C1 asserts that the lede follows the `<h1>` (`HomePageTest.php:44`) and C3 asserts that the overview link follows the last house block (`HomePageTest.php:131-132`). No check places the `<form>` after the lede or before the house blocks. `rg compareDocumentPosition\|nextElementSibling` over the four new test files finds only those two plus `LegislaturePageTest.php:105` |
| plan S3 + Observable `document overview copy` ("AC 22 to 27: counts with notes, then ... the roll-call pages (AC 27)"), AC 22 sequence (`<h1>`, the line `De ... a ...`, one section per house), AC 29 ("the counts and, in place of the calendar and the list") - screen `/legislaturas/{n}/` | yes - plan lines 103-122, 215 | none | inside a house section, the order counts -> calendar -> table -> recent list is not asserted. C23, C26, C28 and C29 each query their own selector inside the section (`LegislaturePageTest.php:87, 150, 207, 234`), and only count -> note adjacency is structural (`:105`). The dates line sitting after the `<h1>` is asserted by text only (`:85`) |
| plan S2 (AC 6-21) + Observable `collection search results` ("no grouping by house or party, one list") - screen `/busca/` with filters, the empty state and the no-match state | yes - plan lines 78-101, 211-213 | none | - (each copy, presence, absence, count and order element maps to C6-C22. "One list" is held by C12's interleaved order (`SearchPageTest.php:130-140`, Otávio Brandão (Senate) between two deputies), so a list grouped by house cannot pass. The plan does not decide the `<h1>` `Buscar parlamentares`; it repeats AC 21's title and is asserted inside `#app` by C38 (`LightPagesTest.php:44, 64`). Not a contradiction) |
| plan S4, S5 (AC 34-43), doors 1-3, Surface | yes - plan lines 42-60, 124-150 | none | - |
| `.specs/STATE.md` AD-019 (no per-person distribution on the overview) | yes - row AD-019 | none | - (C34 `LegislaturePageTest.php:339-347`; no histogram or member-binned table in `Legislatures/Show.vue`) |
| `research/06` sec. 2 (page weight 311 / 33 / 40 KB), sec. 3 P1, P5, P7, P10, P11, P13, sec. 4 traps, sec. 8 item 2 | yes - lines 13-72, 93-104 | none | - (P1: every count has a `SourceNote`, C3/C24. P7: no accent on the calendar bar, C27. P10: SSR, no script, C38. P11: calendar plus table, C26/C28. P13: no ordering of people, C15/C34. Sec. 8 item 2: rejected, AD-019) |
| `design/README.md` tokens and `SourceNote` | yes - Tokens, Components | none | - (`--ma-color-muted` is `oklch(0.46 0 0)`, achromatic, role `text` (4.5:1, above the 3:1 `graphic` floor), from `design/tokens/plenario.json`; `SourceNote` gets `index`, `sourceUrl`, `sourceLabel`, `methodUrl`, `collectedAt` as the README lists, `HouseActivity.php` counts()) |
| `AGENTS.md` forbidden vocabulary | yes - "Linguagem do produto" | none | - (C41, 26 terms, `LightPagesTest.php:120-137`) |
| `app/README.md` | yes - "Run the SSR server", "Page weight" | none | - |

Deviations listed in the Handoff, judged one by one:

- **Singular count labels at 1.** Accepted, not a contradiction. The plan's templates read `{n} votações ...`, and with `n` = 1 that literal is ungrammatical. The check changes only the number agreement and keeps the words, following the plan's own AC 6 precedent. It was written into `checks.md` at `4dc8f18`, before any code.
- **Local gzip proxy for C44.** Accepted. AC 39's HTML budget and the Assumptions' totals both assume gzip transport. `php artisan serve` compresses nothing. The proxy gzips text at level 6, leaves the font as it is, and forwards `Host`, so the same-origin rule is still tested. It was written into C44 at `4dc8f18`. Observation: this assumes the production server gzips text, and only checks.md and app/README record that. The deploy feature inherits the assumption.
- **`SsrState::dispatch` for door 2.** Accepted. `vendor/inertiajs/inertia-laravel/src/Ssr/SsrState.php:39-46` caches the dispatch in a scoped singleton (`ServiceProvider.php:32`). `Directive.php:19, 41` uses the same call, so SSR runs once per request. inertia-laravel is `v3.5.1` in `composer.lock`.
- **Parties of the chosen legislature on `/busca/`.** Accepted. AC 14 validates `partido` against the chosen legislature, and AC 20 says the form shows effective values, so the options have to match the validation set.
- **C34 16 -> 18.** Accepted. The search fixture holds 9 Câmara members (101-103 plus 104-108 and 1001) and 9 Senate members (9101-9109). The test asserts 18 (`LegislaturePageTest.php:331`).
- **Select labels, numeric id tiebreak, pagination key order and page 1 dropping `pagina`.** All fill values the plan left open, and none contradicts it.

## Checks

Proof run for C1-C43 and C45-C51: one invocation, `sail artisan test --filter="(<the 50 names joined by |>)" --log-junit /tmp/junit-home.xml`, exit 0, 72 test cases (datasets and same-prefix variants included), 1,792 assertions. I read each name back from the JUnit file: each of the 50 names matched at least 1 testcase and none failed. A separate full run, `sail artisan test`, exit 0, had 250 passed. Each name occurs exactly once in `app/tests` (`grep -F "'<name>'"`). SSR was up (`inertia:start-ssr`, `/health` 200) after `sail npm run build` at `HEAD`.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | `/` 200, `Home/Index`, one `<h1>`, the lede right after it | "home says what the site is" ok | `app/tests/Feature/HomePageTest.php:39` `->toBe('Home/Index')`; `:42-44` `toHaveCount(1)`, `toBe('O que cada parlamentar federal fez no mandato')`, `nextElementSibling ... toBe(HOME_LEDE)` | PASS |
| C2 | search form: attributes, control order, labels, options, defaults; parties over the search fixture | "home search form works without javascript" ok | `HomePageTest.php:55-57` role/method/action; `:60` `toBe(['INPUT:q', ..., 'BUTTON:'])`; `:64-66` labels; `:68-88` option lists including the 27 UFs and `58ª legislatura (2027–2031)` selected; `:91-92` `['Todos os partidos','MDB','PL','PP','PSD','PSOL','PT']` | PASS |
| C3 | home house blocks, counts, notes, overview link after the blocks; both houses over the search fixture | "home counts the current legislature per house" ok | `HomePageTest.php:121-125` `houseBlocks(...)->toBe([...])` with note text `dados de 01/03/2027` and links; `:127-132` one link `Visão geral da 58ª legislatura`, following and outside the last block; `:139-150` Câmara `7 parlamentares`, Senado `0 votações nominais`, `3 parlamentares`, `dados de 05/03/2027` | PASS |
| C4 | no import: `<h1>`, lede, empty text, no form, no block, no overview link | "home without data says so" ok | `HomePageTest.php:158-165` `assertOk`, `toContain('Ainda não há dados importados.')`, `querySelectorAll('form')->toHaveCount(0)`, `.ma-house` 0, `a[href^="/legislaturas/"]` 0 | PASS |
| C5 | home head tags, no robots | "home head tags" ok | `HomePageTest.php:173-179` title, og:title, description, og:description, canonical and og:url `https://mandato.test/`, `meta[name="robots"]` 0 | PASS |
| C6 | `/busca/` default: `Search/Index`, `8 parlamentares, em ordem alfabética`, 8 rows in order; singular line | "search lists the members in exercise by default" ok | `app/tests/Feature/SearchPageTest.php:51` `toBe('Search/Index')`; `:53-54` count line and `resultRows ... toBe(DEFAULT_ROWS)`; `:55` `'1 parlamentar, em ordem alfabética'` | PASS |
| C7 | one `<a>` per row, exact text, href per legislature, no img/digit/`.ma-num` | "search rows link the member page of that legislature" ok | `SearchPageTest.php:63-64` `toBe('Ana Souza Câmara dos Deputados PT · SP')`, `'Paulo Silva Senado Federal PT · SP'`; `:66-68` one `a`, `img, .ma-num` 0, `not->toMatch('/\d/')`; `:72-74` `/deputados/101/legislatura/57/` `PSB · SP`, `/senadores/9101/legislatura/57/` | PASS |
| C8 | every token, case and accent folded | "search matches every token ignoring case and accents" ok | `SearchPageTest.php:81-90` `joao`, `JOÃO`, `silva joão` -> João only; `joao santos` -> `[]`; `silva` -> 4 rows in order | PASS |
| C9 | `%`, `_`, `\` literal | "search treats wildcards as literal characters" ok | `SearchPageTest.php:98-99` `resultRows ... toBe([])` and no-match text | PASS |
| C10 | first 100 characters of `q` | "search reads the first 100 characters of q" ok | `SearchPageTest.php:106` `mb_strlen ... toBe(101)`; `:110-111` João row and input value `'João'` | PASS |
| C11 | house, UF, party filters combined | "search filters by house uf and party combined" ok | `SearchPageTest.php:117-124` four href lists | PASS |
| C12 | `todos` gives 10, `exercicio` gives the 8, Otávio out by his own house's day | "search lists only members in exercise unless asked for all" ok | `SearchPageTest.php:130-142` 10-row list, `DEFAULT_ROWS`, `not->toContain('Otávio Brandão')` | PASS |
| C13 | past legislature lists all 9 whatever `situacao`, with the note; none by default | "search of a past legislature lists everyone who held a mandate" ok | `SearchPageTest.php:152-153` names and `toBe($note)`; `:155-156` `toBeNull()`, `not->toContain('terminou em')` | PASS |
| C14 | 8 out-of-set values fall back to the default, field shows its default; `57&PSB` | "search ignores a filter value outside its set" ok | `SearchPageTest.php:174-176` rows, count line, selected default per case; `:179-182` Ana Souza, Wagner Reis | PASS |
| C15 | pt-BR collation, house, numeric id; `ordem`/`sort` ignored; no other control | "search orders by name then house then id only" ok | `SearchPageTest.php:192-194` `Ágata Rocha` second and last in byte order, `['/deputados/108/','/deputados/1001/','/senadores/9107/']`; `:195-196` unchanged; `:199` controls `['q',...,'']` | PASS |
| C16 | 50 per page, pager text and links keeping params in door 1 order; none on one page | "search pages by 50 keeping the other parameters" ok (both variants) | `SearchPageTest.php:213-214` `Página 1 de 14`, next `/busca/?pagina=2`; `:217-222` pages 2 and 12 of 12; `:224` 31; `:227` `'/busca/?q=a&legislatura=58&situacao=todos'`; `:236`, `:241` collation across pages, 600 distinct; `:248-249` no `.ma-pages`, no `Página` | PASS |
| C17 | 404 for 99, 0, -1, abc, 1.5, 2; 200 for 1; empty result 200 | "search answers 404 for a page out of range" ok | `SearchPageTest.php:257-258` `assertNotFound()`, `<h1>` `Página não encontrada`; `:260` `assertOk`; `:262-263` | PASS |
| C18 | no match: text, `Limpar filtros` to `/busca/`, no list, no count | "search with no match offers to clear the filters" ok | `SearchPageTest.php:272-276` | PASS |
| C19 | no import: 200, text, no form | "search without data says so" ok | `SearchPageTest.php:282-285` | PASS |
| C20 | form shows effective values; `q` only in the input's value | "search echoes q only in its input" ok | `SearchPageTest.php:294-299` selected values; `:308` `substr_count($markup, $q)->toBe(1)`; `:310-317` title, headings, count line, empty text, every meta, `props.meta` | PASS |
| C21 | search head tags with robots `noindex, follow` | "search head tags keep the query out" ok | `SearchPageTest.php:327-334` | PASS |
| C22 | `SearchKey::of` cases | "search key folds case accents and spaces" ok | `SearchPageTest.php:338-341` four `toBe` | PASS |
| C23 | overview `<h1>`, dates, house order, 5 counts each in order, 58 Câmara | "overview counts each house activity" ok | `app/tests/Feature/LegislaturePageTest.php:81` `Legislatures/Show`; `:83-88` one `<h1>` `57ª legislatura`, `De 01/02/2023 a 31/01/2027`, h2 order, `CAMARA_57_COUNTS`/`SENADO_57_COUNTS` (`:13-27`); `:90-96` | PASS |
| C24 | each count followed by its note, house source, day and anchor; 9 unique ids | "overview counts carry their source note" ok | `LegislaturePageTest.php:105` `nextElementSibling`; `:120-124` per house note lists; `:126-127` 9 ids, 9 unique | PASS |
| C25 | Senate symbolic line in third place, no symbolic number; not on Câmara | "overview says the senate publishes no symbolic votes" ok | `LegislaturePageTest.php:135-137` | PASS |
| C26 | calendar heading, year rows, cells `{abbr} {n}`, bar heights; budget heights = round(count×100/max, 1) | "overview calendar counts roll calls by month" ok (both variants) | `LegislaturePageTest.php:149-159` heading and `calendarCells(...)->toBe([...])` with `100%`/`0%`; `:170-177` 48 cells, height and count per month (the budget dataset gives 31 and 32 per month, so 96.9% is exercised) | PASS |
| C27 | bar background `var(--ma-color-muted)`, no accent in calendar rules | "overview calendar bars use a neutral colour" ok | `LegislaturePageTest.php:188-192` `toMatch('/background(-color)?:\s*var\(--ma-color-muted\)/')`, `not->toContain('--ma-color-accent')`; rule at `app/resources/css/app.css:82-85` | PASS |
| C28 | table header, 32 rows in order with days; budget days | "overview table repeats the calendar with voting days" ok (both variants) | `LegislaturePageTest.php:206-208`; `:218-221` | PASS |
| C29 | recent lists: exact item text, order, hrefs, both houses | "overview lists the latest nominal and secret roll calls" ok | `LegislaturePageTest.php:233-244` | PASS |
| C30 | exactly 10, date desc then id desc | "overview recent list stops at 10" ok | `LegislaturePageTest.php:260-261` `toHaveCount(10)`, `toBe($expected)` (expected built in the test at `:252-258` by the plan's rule) | PASS |
| C31 | house with no import listing `n` renders only its line (3 cases) | "overview says when a house has no data" ok (3 variants) | `LegislaturePageTest.php:269-273`; `:281-282`; `:290-291` children exactly `[h2, line]`, no count, calendar, table or list | PASS |
| C32 | no nominal/secret: 5 counts, `até 01/03/2027`, no calendar, table or list | "overview without nominal roll calls says until when" ok | `LegislaturePageTest.php:299-306` | PASS |
| C33 | legislature nav, order, `aria-current`; absent with one stored | "overview navigates between legislatures" ok (both variants) | `LegislaturePageTest.php:313-318`; `:325` | PASS |
| C34 | no member name (18), no member link, no img, no `%`, no indicator words, props included | "overview names no person" ok | `LegislaturePageTest.php:331` 18; `:339`, `:342`, `:344`, `:346` | PASS |
| C35 | 404 for 56, 59, abc, 5a, `/legislaturas/` | "overview answers 404 for a legislature not stored" ok | `LegislaturePageTest.php:356-357` | PASS |
| C36 | overview head tags, no robots | "overview head tags" ok | `LegislaturePageTest.php:368-374` | PASS |
| C37 | 14 responses carry no `Set-Cookie` | "home search and overview set no cookie" ok (14 dataset cases in JUnit) | `app/tests/Feature/LightPagesTest.php:20-21` `assertHeaderMissing('Set-Cookie')`, `getCookies()->toBe([])`; dataset `:22-37` | PASS |
| C38 | SSR: no `<script src`, no modulepreload, one stylesheet `app-*.css`, `<h1>` and counts and rows inside `#app` | "light pages render complete html with no script" ok | `LightPagesTest.php:59-67`; `:70` 10 counts | PASS |
| C39 | SSR down: 200, head tags, empty `#app`, module script | "light pages fall back to the client when ssr is down" ok | `LightPagesTest.php:83`, `:87-99` (description checked only as present and equal to og:description, `:89-90`; see observations) | PASS |
| C40 | slashless forms answer with the slash canonical | "slashless home paths answer with the slash canonical" ok | `LightPagesTest.php:108-110` | PASS |
| C41 | 26 terms absent; matcher folds case, accents and spaces | "no ranking word on home search or overview" ok | `LightPagesTest.php:120` `toHaveCount(26)`; `:126` `inText(...)->toBe([])`; `:135-137` | PASS |
| C42 | HTML gzip ≤ 20,000 / 25,000 / 30,000 | "light pages stay within the html budget" ok | `LightPagesTest.php:155-156` `strlen(gzencode($html, 6))->toBeLessThanOrEqual($budget)`. Measured on the dev server: 2,578 / 4,153 / 3,830 bytes | PASS |
| C43 | linked CSS gzip ≤ 8,000 | "light pages stay within the css budget" ok | `LightPagesTest.php:167-168`. Measured: `build/assets/app-CleEVdhB.css` 4,112 bytes gzip | PASS |
| C44 | Chromium first load ≤ 125,000 / 130,000 / 135,000, same origin | `node tests/budget/first-load.mjs http://localhost:8093` exit 0: `/` 97,430, `/busca/` 99,028, `/legislaturas/58/` 98,652 bytes, 3 requests each | `app/tests/budget/first-load.mjs:21` `BUDGETS`; `:70-72` `foreign`, `total <= budget`; `:81` `process.exit(failed ? 1 : 0)`. I did not reseed: the auto-mode classifier refused `migrate:fresh` on the dev database. A read-only query showed the database already held the budget dataset (memberships 57: 340, 58: 681; 1,350 nominal, 150 secret per house, 500 Câmara symbolic; 30 parties; both imports `2031-01-31 15:00:00+00`), which matches C48 | PASS |
| C45 | hydrated pages keep the head sequence, no robots, module script | "hydrated pages keep their head" ok | `LightPagesTest.php:194-196` | PASS |
| C46 | Inertia visits answer JSON with component and title | "light pages answer inertia visits with json" ok (3 datasets) | `LightPagesTest.php:206-209` | PASS |
| C47 | route names, uris, wheres, `public` not `web`, no `legislaturas` | "home routes use the cookie-free group" ok | `LightPagesTest.php:224-230`; group at `app/bootstrap/app.php:22-25` | PASS |
| C48 | budget dataset facts | "budget dataset matches the plan" ok | `LightPagesTest.php:238-243`, `:250`, `:253-254`, `:258-265`, `:267-268` | PASS |
| C49 | footer sentence for both, Câmara only, Senate only, none; member page unchanged | "home search and overview footers name both houses" ok (both variants) | `LightPagesTest.php:278`, `:284`, `:286-287`, `:294-295`, `:300` | PASS |
| C50 | masthead wordmark to `/` and `Buscar parlamentar` on 19 pages | "every public page links home and to search" ok | `LightPagesTest.php:311-315` | PASS |
| C51 | budget scale: home counts, 150 secret, 48 cells and rows | "overview and home scale to the budget dataset" ok | `LegislaturePageTest.php:381-392` | PASS |

## Coverage

Recomputed from authority: the plan's Surface, door 1, AC 2/14/17 and the Observable rows, not from the code. For the arrangement row the authority is the plan's Observable "structure" rows.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| routes × statuses (5) | plan Surface | `/` 200 C1, C4 · `/busca/` 200 C6, C19 · `/busca/` 404 C17 · `/legislaturas/{n}/` 200 C23 · 404 C35 | - |
| Surface response shapes (3 HTML + Inertia JSON) | plan Surface | HTML C1, C6, C23 · `X-Inertia` JSON for all 3, C46 | - |
| trailing slash (2) | AC 37 | `/busca` and `/legislaturas/57` C40 | - |
| query keys (7) | door 1 | `q` C8-C10 · `casa` C11 · `uf` C11 · `partido` C11 · `legislatura` C13, C14 · `situacao` C12 · `pagina` C16, C17 | - |
| `casa` values (4) | door 1 + AC 14 | `camara` C11 · `senado` C6 · invalid C14 · absent C6 | - |
| `uf` values (27 codes + invalid) | AC 2 + AC 14 | the 27 listed as options C2 (`HomePageTest.php:73-76`). Validation reads the same constant (`SearchForm::UFS`, `SearchController.php` `in_array($given('uf'), SearchForm::UFS, true)`) and SP is filtered in C11 · `XX` C14 · lowercase `sp` C14 | - |
| `partido` values (4) | AC 2 + AC 11 + AC 14 | party of the chosen legislature C11 · party of another legislature C14 (`PSB`) · unknown C14 · a past legislature's party C14 (`57&PSB`) | - |
| `legislatura` values (4) | door 1 + AC 13 + AC 14 | current C14 · past C13 · not stored C14 · not digits C14 | - |
| `situacao` values (5) | door 1 + AC 12-14 | absent C6 · `exercicio` C12 · `todos` C12 · invalid C14 · ignored on a past legislature C13 | - |
| `pagina` values (7) | AC 16, AC 17 | absent C16 · in range C16 · above range C17 · 0 C17 · negative C17 · not an integer (`abc`, `1.5`) C17 · empty result C17 | - |
| `q` cases (7) | AC 8-10, AC 20 | accent C8 · case C8 · token order C8 · missing token C8 · wildcards C9 · over 100 C10 · echo C20 | - |
| in exercise (4) | plan Impact term | Câmara in/out C6, C12 · Senate in C6 · Senate out under its own day C12 | - |
| ordering keys (3) | door 3, AC 15 | collation, house, numeric id C15 | - |
| page states: home (2), search (4), overview house (4) | plan Observable | home data/no import C2-C4 · search rows/no match/no import/paged C6, C18, C19, C16 · overview counts+calendar/no import/no nominal/symbolic null C23, C31, C32, C25 | - |
| overview counts (5 × 2 houses) and label number (0, 1, many) | AC 22 | C23 (0 and 1), C51 and C3 (many) | - |
| source notes (3 anchors × 2 houses) | AC 23 | C24, C3 | - |
| roll-call result labels (3), heading forms (2), recent size (2) | AC 27 | C29, C30 | - |
| calendar cell values (0, max, fraction) | AC 25 | C26 (fixtures 0 and 100%, budget dataset 96.9%) | - |
| legislature nav (2) | AC 30 | C33 | - |
| footer houses (4), masthead pages (19), cookie-free responses (14), vocabulary terms (26) | AC 42, 43, 34, 38 | C49, C50, C37, C41, each table-driven over every member | - |
| performance budget numbers (9) | AC 39-41 + Assumptions | HTML 20,000 / 25,000 / 30,000 C42 · CSS 8,000 C43 · totals 125,000 / 130,000 / 135,000 C44 (run here) · no script C38 · same origin C44 | - |
| head tags per page (3) and SSR states (2) | AC 5, 21, 33, 35, 36 | C5, C21, C36 · C38, C39 | - |
| screen arrangement: home (4 orderings), overview section (3 orderings) | plan Observable `document home copy` / `document overview copy` "structure", AC 22, AC 29 | home `<h1>` -> lede C1 · last house block -> overview link C3 · overview houses Câmara -> Senado C23 · count -> its note C24 | home lede -> `<form>` -> house blocks (no proof); overview section counts -> calendar -> table -> recent list, and the dates line right after the `<h1>` (no proof) |

## Test policy rows

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `app/Support/SearchKey.php` | own layer C22 (`SearchPageTest.php:338-341`, all 4 transformations) · boundary C8, C9 | yes |
| Decides, not reached across a boundary (boundary is its own layer) | `SearchController.php`, `SearchForm.php`, `HomeController.php`, `LegislatureController.php`, `HouseActivity.php`, `Labels::sourcesLine`, `app.blade.php`, `PublicLayout.vue` | one asserted case per row of each decision table at the HTTP boundary: 6 validations C14/C10 · in exercise C12 · current or past C13 · 3 ordering keys C15 · page bounds and empty exception C17 · 3 page states C6/C18/C19 · counts, house states, symbolic null, singular or plural, calendar range and fraction, recent cap C3/C23-C33/C51 · `robots` and `hydrate` × SSR C21/C5/C36/C38/C39/C45 · footer 4 combinations C49 | yes |
| Entry point that decides nothing | `routes/public.php` (3 routes) | boundary: accepted C1/C6/C23 · rejected C35 (`/legislaturas/abc/`, `5a`, the missing index) · trailing slash C40 · group C47 | yes |
| Instrumentation, pass-throughs | `PublicUrl::legislature`, `PublicUrl::search`, `BudgetSeeder` (fixture), `first-load.mjs` (measurement) | covered by their consumers: C16 (link order), C3/C33 (overview hrefs), C48 (seeder facts) | yes |

Swept rows that cite existing code: concurrency "an import ... commits per house in one transaction". It is there: `app/app/Import/Importer.php:66` `$db->transaction(...)`, under the advisory lock described in `app/Console/Commands/ImportContract.php:17`. Authorization cites C37, which is proven above.

## Faults injected

Done in scratch worktree `/tmp/home-verify` at `HEAD`, Sail project `mandato-homeverify` (APP_PORT 8193, FORWARD_DB_PORT 54443, VITE_PORT 5283), with its own SSR server and the `HEAD` build copied in. Each fault ran its narrowest proof, then was restored with `git -C /tmp/home-verify checkout -- <file>`. Afterwards I ran `sail down -v` (containers, network and volume `mandato-homeverify_sail-pgsql` removed) and `git worktree remove --force` plus `prune`. The real tree's `git status --porcelain` was empty before and after; the only file it now shows is this report.

| Mutation | Location | Killed |
| --- | --- | --- |
| search ordered by house first (Senate before Câmara), then name | `app/app/Http/Controllers/SearchController.php` `usort` comparator | yes - "search orders by name then house then id only" failed at `SearchPageTest.php:192` (`'Zélia Moura'` where `'Ágata Rocha'` was expected) |
| accents kept: `Normalizer::FORM_C` and no `\p{Mn}` removal | `app/app/Support/SearchKey.php` `of()` | yes - "search matches every token ignoring case and accents" failed at `SearchPageTest.php:81` (`q=joao` gave `[]`) |
| query leaks into the title: `$meta['title'] .= " para {$q}"` | `SearchController.php` after `$q = ...` | yes - "search echoes q only in its input" failed at `SearchPageTest.php:308` (`joao` found 4 times) |
| overview carries member names: prop `authors` = every `members.name` | `app/app/Http/Controllers/LegislatureController.php` render props | yes - "overview names no person" failed at `LegislaturePageTest.php:339` (`'Ana Souza'`) |
| home links the client script: `'hydrate' => true` | `app/app/Http/Controllers/HomeController.php` `meta` | yes - "light pages render complete html with no script" failed at `LightPagesTest.php:59` (`<script ... src=` found) |

## Gate

- `sail artisan test` (full suite at `a76e907`): 250 passed, 0 failed, 3,436 assertions
- `sail artisan test --filter="(<50 named proofs>)"`: 72 passed, 0 failed, 1,792 assertions; every name read back individually from JUnit
- `sail bin pint --test`: passed · `sail bin phpstan analyse`: 0 errors
- `sail npm run build`: client and SSR bundles written · SSR `/health` 200
- `node tests/budget/first-load.mjs http://localhost:8093`: exit 0 (97,430 / 99,028 / 98,652 bytes)
- `python3 /home/augusto/.claude/skills/tlc-spec-lean/scripts/validate_verification.py app-home`: exit 1 (verdict is FAIL)

### Ranked gaps

1. **Overview section arrangement is uncovered** - C23, C26, C28, C29 - `app/tests/Feature/LegislaturePageTest.php:87, 150, 207, 234`. The plan's Observable row fixes "counts with notes, then ... the roll-call pages", and AC 29 puts the calendar and list where the counts end. No check asserts that inside a house section the counts come before the calendar, table and recent list, or that the dates line follows the `<h1>`. Moving the recent list above the counts in `Legislatures/Show.vue` would leave every proof green.
2. **Home arrangement is uncovered between the lede and the counts** - C1, C2, C3 - `app/tests/Feature/HomePageTest.php:44, 131-132`. The plan's Observable row fixes the structure AC 1 -> AC 2 -> AC 3. The checks prove `<h1>` -> lede and last house block -> overview link, but no check places the search `<form>` after the lede and before the house blocks. Moving `<SearchForm>` below the house sections in `Home/Index.vue` would leave every proof green.

Fix: one structural assertion each, for example `compareDocumentPosition` on lede -> form -> first `.ma-house` (amend C2 or C3), and on `.ma-count` (last) -> `.ma-cal` -> `.ma-cal-table` -> `.ma-recent` inside a section, plus `<h1>` -> `.ma-legislature__dates` (amend C23). Neither needs a code change; the current markup already renders those orders.

### Observations (not failing)

- C39 checks the SSR-down `description` only as present and equal to `og:description` (`LightPagesTest.php:89-90`), not by value. The Blade line has no branch on SSR (`app/resources/views/app.blade.php:15`), and C5, C21 and C36 assert the value with SSR up.
- The home form's own submission sends empty `casa=&uf=&partido=`, which falls in C14's "outside the set" class (`'' ∉` house codes, UFs, parties). No proof names it. Probed live on `mandato-home`: `GET /busca/?q=&casa=&uf=&partido=&legislatura=58&situacao=exercicio` returned 200 with `681 parlamentares, em ordem alfabética` and `Página 1 de 14`.
- C44 holds only if production gzips text responses. The deploy feature should carry that as an obligation.
