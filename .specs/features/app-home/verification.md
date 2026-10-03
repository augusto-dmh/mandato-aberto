# app-home verification

**Verdict**: PASS
**Profile**: ui
**Diff range**: 645a5fc..750b697e0f9de45dc73a41c6dc7abb6f0f69ec08
**Round**: 2 - scoped
**Verifier**: independent sub-agent (author != verifier)

Round 2 runs at `750b697`. Round 1 (`a76e907`, report `ef8e9ff`) failed on two arrangement gaps: the order of the overview sections and the home's order from the lede to the counts. The fix is `750b697` and changes tests only: `app/tests/Pest.php` (new `expectDocumentOrder`), `app/tests/Feature/HomePageTest.php`, `app/tests/Feature/LegislaturePageTest.php`, plus `checks.md` (C2, C3 and C23 amended, one Handoff line). `git diff --stat a76e907 750b697 -- app/resources app/app app/routes app/database app/bootstrap app/config app/tests/budget` is empty, so no page, controller, route, seeder or budget script changed. `plan.md` is also unchanged (`git diff a76e907 HEAD -- .specs/features/app-home/plan.md` is empty).

This round covers that diff and the two non-PASS verdicts. Step 1 was redone for the home and overview arrangement. Every proof was re-run at `750b697`, one named test per invocation. Three new faults were injected on the surfaces the fix created. Everything else is marked `carried from a76e907`. The two gaps are closed: each new assertion fails when a section is moved, and the amended claims add to the approved ones without loosening any.

## Binding sources

The plan names no source as "binding". This report treats as binding the sources the brief named and the plan cites for these screens: the plan's Criteria and Observable rows (the home, search and overview have no design screen; `design/screens/` holds only `Card`, `Chrome`, `Profile` and `RollCall`), `research/06` sections 2, 3, 4 and 8 item 2, `.specs/STATE.md` AD-019, `design/README.md` (tokens and colour roles, `SourceNote`), `AGENTS.md` (forbidden vocabulary) and `app/README.md`.

The rows for `/` and `/legislaturas/{n}/` were re-opened and re-judged at `750b697`. The other rows are carried from `a76e907`, because none of their screens, sources or checks were touched.

| Source | Opened | Contradiction | Uncovered | Note |
| --- | --- | --- | --- | --- |
| plan S1 AC 1-3 + Observable `document home copy` ("AC 1 (one sentence), AC 2 (search), AC 3 (counts, then the overview link)") - screen `/` | yes - `.specs/features/app-home/plan.md:70-72`, `:214` (verified at 750b697) | none | - | The order `<h1>` -> lede -> `<form>` -> first `.ma-house` is asserted by C2 at `app/tests/Feature/HomePageTest.php:89-92`. The test also checks that the form does not contain the block. C3 asserts the full sequence through the last block and the overview link at `HomePageTest.php:135-136`. C1 keeps lede adjacency at `:43`. The template agrees: `app/resources/js/Pages/Home/Index.vue` renders `h1`, `p.ma-lede`, then inside `v-if="form"` the `SearchForm`, `section.ma-house` and `p > a` in that order. The `v-else` empty text is C4. Fault 1 below is killed by both C2 and C3 |
| plan S3 AC 22-29 + Observable `document overview copy` ("counts with notes, then ... the roll-call pages (AC 27)") - screen `/legislaturas/{n}/` | yes - `plan.md:109-116`, `:215` (verified at 750b697) | none | - | C23 asserts that the `<h1>`'s next element sibling is `.ma-legislature__dates` (`app/tests/Feature/LegislaturePageTest.php:90`). For each house it then asserts this order: `h1`, dates line, `h2`, first `.ma-count`, last `.ma-count`, `.ma-cal`, `.ma-cal-table`, `.ma-recent` (`:91-104`), and the Câmara section before the Senate one (`:105`). AC 29's "in place of the calendar and the list" state stays with C32. The nav sits between the dates line and the sections, and the plan does not decide its place (AC 30 only requires it to exist), so it is not an uncovered arrangement. Faults 2 and 3 below are killed by C23 |
| plan S2 (AC 6-21) + Observable `collection search results` - screen `/busca/` | yes (carried from a76e907) | none | - | "One list" is held by C12's interleaved order (`app/tests/Feature/SearchPageTest.php:130-140`) |
| plan S4, S5 (AC 34-43), doors 1-3, Surface | yes (carried from a76e907) | none | - | - |
| `.specs/STATE.md` AD-019 (no per-person distribution on the overview) | yes (carried from a76e907) | none | - | C34 at `LegislaturePageTest.php:356-363` (moved by +17 lines, unchanged) |
| `research/06` sec. 2, 3 (P1, P5, P7, P10, P11, P13), 4, 8 item 2 | yes (carried from a76e907) | none | - | - |
| `design/README.md` tokens and `SourceNote` | yes (carried from a76e907) | none | - | - |
| `AGENTS.md` forbidden vocabulary | yes (carried from a76e907) | none | - | C41 |
| `app/README.md` | yes (carried from a76e907) | none | - | - |

### Amended claims (C2, C3, C23) against the approved ones

`git show 750b697 -- .specs/features/app-home/checks.md`, read line by line:

- **C2**: the approved text is kept word for word. The amendment adds one sentence: in the SSR HTML the `<h1>`, the paragraph after it, the `<form>` and the first `.ma-house` come in that order, and the form does not contain the block. It also adds `Observable document home copy` to the reference. This adds an obligation and drops none. **Strengthens.**
- **C3**: the approved text is kept, including "after the house blocks". The amendment appends "so the whole page reads, in document order: `<h1>`, lede, search form, first house block, last house block, overview link, the link outside every block". That is a superset of the old single ordering (last block -> link). **Strengthens.**
- **C23**: the approved text is kept, including every count string and its order. The amendment inserts the adjacency of `<h1>` and the dates line, and the in-section order `h2` -> first count -> last count -> `.ma-cal` -> `.ma-cal-table` -> `.ma-recent`, with AC 25-27 and AC 29 added to the reference. The plan's AC 22 already lists `<h1>`, the dates line and the sections in sequence, and the Observable row puts counts before the roll-call pages. The calendar-before-table-before-list order is a choice the plan leaves open (AC 26: "also render the equivalent table"), and the check fixes it to what the page already renders. Nothing in the plan contradicts it. **Strengthens.**

The `Handoff` line the fix added says each assertion was seen failing and no production file changed. I confirmed both on my own: the production diff is empty (above), and the faults below fail them.

Deviations from round 1 (singular labels at 1, the gzip proxy for C44, `SsrState::dispatch`, the chosen legislature's parties on `/busca/`, C34 16 -> 18, select labels, id tiebreak, pagination key order) are carried from a76e907, all accepted. The fix touched none of them.

## Checks

Each of the 50 Pest proofs was run alone at `750b697` inside Sail project `mandato-home` (`APP_PORT=8093`, `FORWARD_DB_PORT=54343`, `VITE_PORT=5183`), as `sail artisan test --filter="<name>"`, after `sail npm run build` at `HEAD` with the SSR server up (`/health` 200). Every invocation exited 0 and reported `"result":"passed"` with `tests` ≥ 1 and `failed` 0. I read the count from each run's JSON line, because a filter that matches nothing also exits 0 (`"tests":0`, "No tests found."), so the exit code alone proves nothing. Each name occurs exactly once in `app/tests` (`grep -rF "'<name>'"`). C44 was also re-run, not carried: the fix touched no page, but the script is cheap.

Citations in `HomePageTest.php` and `LegislaturePageTest.php` were refreshed at `750b697`: the fix removed a line at the top of the first (later lines -1, then +4 after `:88`) and inserted 17 lines at `:90` of the second. Citations in `SearchPageTest.php` and `LightPagesTest.php` are carried from a76e907, since the fix did not touch those files.

| Check | Claim | Proof run (750b697) | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | `/` 200, `Home/Index`, one `<h1>`, the lede right after it | "home says what the site is": 1 test, 6 assertions | `app/tests/Feature/HomePageTest.php:38` `toBe('Home/Index')`; `:41-43` `toHaveCount(1)`, `<h1>` text, `nextElementSibling ... toBe(HOME_LEDE)` | PASS |
| C2 | search form attributes, control order, labels, options, defaults; search-fixture parties; **`<h1>` -> lede -> form -> first block, form not containing the block** | "home search form works without javascript": 1 test, 36 assertions | `HomePageTest.php:54-56` role/method/action; `:59` control order; `:63-87` labels and option lists; `:89-91` `expectDocumentOrder(['h1', 'lede', 'search form', 'first house block'])`; `:92` `$form->contains($firstBlock)` false; `:95-96` parties. Helper at `app/tests/Pest.php:227-241`: non-null at `:230`, `compareDocumentPosition ... DOCUMENT_POSITION_FOLLOWING` at `:238` | PASS |
| C3 | home house blocks, counts, notes; **full order through the overview link**, the link outside the last block; both houses over the search fixture | "home counts the current legislature per house": 1 test, 17 assertions | `HomePageTest.php:125-129` `houseBlocks(...)`; `:131-132` one link `Visão geral da 58ª legislatura`; `:135` `expectDocumentOrder([h1, lede, search form, first house block, last house block, overview link])`; `:136` not contained; `:143-154` Câmara 7, Senado 0 and 3, `dados de 05/03/2027` | PASS |
| C4 | no import: `<h1>`, lede, empty text, no form, block or overview link | "home without data says so": 1 test, 7 assertions | `HomePageTest.php:162-169` | PASS |
| C5 | home head tags, no robots | "home head tags": 1 test, 8 assertions | `HomePageTest.php:177-183` | PASS |
| C6 | `/busca/` default list and count line | "search lists the members in exercise by default": 1 test, 7 assertions | `app/tests/Feature/SearchPageTest.php:51-55` (carried from a76e907) | PASS |
| C7 | one link per row, text, href per legislature, no indicator | "search rows link the member page of that legislature": 1 test, 32 assertions | `SearchPageTest.php:63-74` (carried from a76e907) | PASS |
| C8 | tokens, case and accent folded | "search matches every token ignoring case and accents": 1 test, 11 assertions | `SearchPageTest.php:81-90` (carried from a76e907) | PASS |
| C9 | wildcards literal | "search treats wildcards as literal characters": 1 test, 10 assertions | `SearchPageTest.php:98-99` (carried from a76e907) | PASS |
| C10 | first 100 characters | "search reads the first 100 characters of q": 1 test, 5 assertions | `SearchPageTest.php:106-111` (carried from a76e907) | PASS |
| C11 | house, UF, party combined | "search filters by house uf and party combined": 1 test, 9 assertions | `SearchPageTest.php:117-124` (carried from a76e907) | PASS |
| C12 | in exercise unless `todos` | "search lists only members in exercise unless asked for all": 1 test, 7 assertions | `SearchPageTest.php:130-142` (carried from a76e907) | PASS |
| C13 | past legislature lists everyone, with note | "search of a past legislature lists everyone who held a mandate": 1 test, 11 assertions | `SearchPageTest.php:152-156` (carried from a76e907) | PASS |
| C14 | out-of-set values fall back to default | "search ignores a filter value outside its set": 1 test, 35 assertions | `SearchPageTest.php:174-182` (carried from a76e907) | PASS |
| C15 | collation, house, numeric id only | "search orders by name then house then id only": 1 test, 10 assertions | `SearchPageTest.php:192-199` (carried from a76e907) | PASS |
| C16 | 50 per page, pager keeping params | "search pages by 50 keeping the other parameters": 2 tests, 64 assertions | `SearchPageTest.php:213-249` (carried from a76e907) | PASS |
| C17 | 404 out of range | "search answers 404 for a page out of range": 1 test, 16 assertions | `SearchPageTest.php:257-263` (carried from a76e907) | PASS |
| C18 | no match offers `Limpar filtros` | "search with no match offers to clear the filters": 1 test, 7 assertions | `SearchPageTest.php:272-276` (carried from a76e907) | PASS |
| C19 | no import on `/busca/` | "search without data says so": 1 test, 6 assertions | `SearchPageTest.php:282-285` (carried from a76e907) | PASS |
| C20 | `q` only in its input | "search echoes q only in its input": 1 test, 44 assertions | `SearchPageTest.php:294-317` (carried from a76e907) | PASS |
| C21 | search head tags, `noindex, follow` | "search head tags keep the query out": 1 test, 8 assertions | `SearchPageTest.php:327-334` (carried from a76e907) | PASS |
| C22 | `SearchKey::of` cases | "search key folds case accents and spaces": 1 test, 4 assertions | `SearchPageTest.php:338-341` (carried from a76e907) | PASS |
| C23 | overview `<h1>`, dates, house order, counts in order, 58 Câmara; **dates line adjacent to `<h1>`, per-house order h2 -> counts -> calendar -> table -> recent list** | "overview counts each house activity": 1 test, 45 assertions | `app/tests/Feature/LegislaturePageTest.php:81` component; `:83-88` `<h1>`, dates text, h2 order, `CAMARA_57_COUNTS`/`SENADO_57_COUNTS`; `:90` `nextElementSibling ... toBe(.ma-legislature__dates)`; `:92-104` `expectDocumentOrder` per house over 8 nodes; `:105` Câmara section before Senado; `:107-113` 58 Câmara | PASS |
| C24 | each count followed by its note; 9 unique ids | "overview counts carry their source note": 1 test, 6 assertions | `LegislaturePageTest.php:122` `nextElementSibling`; `:137-141` per-house notes; `:143-144` ids | PASS |
| C25 | Senate symbolic line, none on Câmara | "overview says the senate publishes no symbolic votes": 1 test, 5 assertions | `LegislaturePageTest.php:152-154` | PASS |
| C26 | calendar cells and bar heights; budget variant | "overview calendar counts roll calls by month": 2 tests, 103 assertions | `LegislaturePageTest.php:166-176`; `:187-194` | PASS |
| C27 | neutral bar colour, no accent | "overview calendar bars use a neutral colour": 1 test, 9 assertions | `LegislaturePageTest.php:205-209` | PASS |
| C28 | table rows with voting days; budget variant | "overview table repeats the calendar with voting days": 2 tests, 55 assertions | `LegislaturePageTest.php:223-225`; `:235-238` | PASS |
| C29 | recent list items, order, hrefs | "overview lists the latest nominal and secret roll calls": 1 test, 5 assertions | `LegislaturePageTest.php:250-261` | PASS |
| C30 | exactly 10, date desc then id desc | "overview recent list stops at 10": 1 test, 5 assertions | `LegislaturePageTest.php:269-278` | PASS |
| C31 | house with no import renders only its line | "overview says when a house has no data": 3 tests, 12 assertions | `LegislaturePageTest.php:286-290`; `:298-299`; `:307-308` | PASS |
| C32 | no nominal/secret: counts and `até` line, no calendar or list | "overview without nominal roll calls says until when": 1 test, 5 assertions | `LegislaturePageTest.php:316-323` | PASS |
| C33 | legislature nav | "overview navigates between legislatures": 2 tests, 7 assertions | `LegislaturePageTest.php:330-335`; `:342` | PASS |
| C34 | no member name, link, image, `%` | "overview names no person": 1 test, 872 assertions | `LegislaturePageTest.php:348`; `:356-363` | PASS |
| C35 | 404 for unknown legislature | "overview answers 404 for a legislature not stored": 1 test, 11 assertions | `LegislaturePageTest.php:373-374` | PASS |
| C36 | overview head tags | "overview head tags": 1 test, 8 assertions | `LegislaturePageTest.php:385-391` | PASS |
| C37 | no `Set-Cookie` on 14 responses | "home search and overview set no cookie": 14 tests, 53 assertions | `app/tests/Feature/LightPagesTest.php:20-37` (carried from a76e907) | PASS |
| C38 | SSR complete, no script | "light pages render complete html with no script": 1 test, 36 assertions | `LightPagesTest.php:59-70` (carried from a76e907) | PASS |
| C39 | SSR down fallback | "light pages fall back to the client when ssr is down": 1 test, 40 assertions | `LightPagesTest.php:83-99` (carried from a76e907) | PASS |
| C40 | slashless canonical | "slashless home paths answer with the slash canonical": 1 test, 7 assertions | `LightPagesTest.php:108-110` (carried from a76e907) | PASS |
| C41 | 26 terms absent | "no ranking word on home search or overview": 1 test, 19 assertions | `LightPagesTest.php:120-137` (carried from a76e907) | PASS |
| C42 | HTML gzip budgets | "light pages stay within the html budget": 1 test, 12 assertions | `LightPagesTest.php:155-156` (carried from a76e907) | PASS |
| C43 | CSS gzip budget | "light pages stay within the css budget": 1 test, 9 assertions | `LightPagesTest.php:167-168` (carried from a76e907) | PASS |
| C44 | Chromium first load ≤ 125,000 / 130,000 / 135,000, same origin | `node tests/budget/first-load.mjs http://localhost:8093` exit 0 at 750b697: `/` 97,430, `/busca/` 99,028, `/legislaturas/58/` 98,652 bytes, 3 requests each | `app/tests/budget/first-load.mjs:21`, `:70-72`, `:81` (file unchanged since a76e907). The database is the budget dataset that round 1 confirmed, and the byte counts are identical | PASS |
| C45 | hydrated pages keep their head | "hydrated pages keep their head": 1 test, 7 assertions | `LightPagesTest.php:194-196` (carried from a76e907) | PASS |
| C46 | Inertia JSON visits | "light pages answer inertia visits with json": 3 tests, 24 assertions | `LightPagesTest.php:206-209` (carried from a76e907) | PASS |
| C47 | cookie-free route group | "home routes use the cookie-free group": 1 test, 16 assertions | `LightPagesTest.php:224-230` (carried from a76e907) | PASS |
| C48 | budget dataset facts | "budget dataset matches the plan": 1 test, 24 assertions | `LightPagesTest.php:238-268` (carried from a76e907) | PASS |
| C49 | footer houses | "home search and overview footers name both houses": 2 tests, 16 assertions | `LightPagesTest.php:278-300` (carried from a76e907) | PASS |
| C50 | masthead links on 19 pages | "every public page links home and to search": 1 test, 59 assertions | `LightPagesTest.php:311-315` (carried from a76e907) | PASS |
| C51 | budget scale for home and overview | "overview and home scale to the budget dataset": 1 test, 13 assertions | `LegislaturePageTest.php:398-409` | PASS |

## Coverage

Only the arrangement row was recomputed, from the plan's Observable "structure" rows and AC 1-3, 22, 25-27 and 29 (verified at 750b697). The fix added no branch and touched no authority behind the other rows, so they are carried from a76e907 with members and proofs unchanged.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| screen arrangement: home (5 orderings), overview (4 orderings) | plan `:214`, `:215`, AC 1, 3, 22, 25-27, 29 (verified at 750b697) | home: `<h1>` -> lede C1 (`HomePageTest.php:43`) · lede -> form C2 (`:91`), C3 (`:135`) · form -> first block, not contained C2 (`:91-92`) · first -> last block C3 (`:135`) · last block -> overview link, not contained C3 (`:135-136`). Overview: `<h1>` -> dates line, adjacent C23 (`LegislaturePageTest.php:90`) · Câmara section -> Senado section C23 (`:105`) · in each house, h2 -> counts in AC 22 order -> calendar -> table -> recent list C23 (`:92-104`, counts by text at `:87-88`) · count -> its note C24 (`:122`) | - |
| routes × statuses (5), response shapes, trailing slash (2), query keys (7), `casa` (4), `uf` (27 + invalid), `partido` (4), `legislatura` (4), `situacao` (5), `pagina` (7), `q` cases (7), in exercise (4), ordering keys (3), page states, overview counts and label number, source notes, roll-call labels, calendar values, legislature nav, footer/masthead/cookie/vocabulary tables, performance budget numbers (9), head tags and SSR states | carried from a76e907 | as round 1 (C1-C51); C44 re-run at 750b697 | - |

## Test policy rows

Carried from a76e907, all met. The fix's files are test code (`app/tests/Pest.php` helper, two feature tests). No Test policy row classifies test files, so no row needed a new judgement.

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `app/Support/SearchKey.php` | own layer C22 · boundary C8, C9 (carried from a76e907) | yes |
| Decides, not reached across a boundary | `SearchController.php`, `SearchForm.php`, `HomeController.php`, `LegislatureController.php`, `HouseActivity.php`, `Labels::sourcesLine`, `app.blade.php`, `PublicLayout.vue` | one asserted case per decision-table row at the HTTP boundary (carried from a76e907) | yes |
| Entry point that decides nothing | `routes/public.php` | C1/C6/C23, C35, C40, C47 (carried from a76e907) | yes |
| Instrumentation, pass-throughs | `PublicUrl::legislature`, `PublicUrl::search`, `BudgetSeeder`, `first-load.mjs` | covered by consumers C16, C3/C33, C48 (carried from a76e907) | yes |

## Faults injected

Round 2 faults ran in scratch worktree `/tmp/home-verify2` (`git worktree add --detach /tmp/home-verify2 HEAD`). The scratch tree had its own Sail project `mandato-homeverify2` (`APP_PORT=8195`, `FORWARD_DB_PORT=54445`, `VITE_PORT=5285`) and its own SSR server, plus `vendor`, `node_modules`, `data` and `design/dist` copied in. Baseline C2+C3+C23 was green there (3 tests, 98 assertions). For each fault I edited the file from `cd /tmp/home-verify2`, rebuilt both bundles, restarted SSR, ran the narrowest proofs, and restored the file with `git -C /tmp/home-verify2 checkout -- <file>`. After the last restore the scratch porcelain was empty and C2+C3+C23 were green again. Then `sail down -v` removed the containers, network `mandato-homeverify2_sail` and volume `mandato-homeverify2_sail-pgsql`, and `git worktree remove --force` plus `prune` removed the tree. `docker ps -a`, `docker volume ls` and `docker network ls` show nothing named `homeverify2`. The real tree's `git status --porcelain` was empty before this report was written.

| Mutation | Location | Killed |
| --- | --- | --- |
| search form moved below the house blocks (between the last `section.ma-house` and the overview link) | `app/resources/js/Pages/Home/Index.vue` `<SearchForm :form="form" />` | yes - C2 failed at `app/tests/Pest.php:239` from `HomePageTest.php:91` ("first house block must come after search form"); C3 failed at the same helper line from `HomePageTest.php:135` |
| recent list moved above the calendar inside each house section | `app/resources/js/Pages/Legislatures/Show.vue` `div.ma-recent` before `div.ma-cal` | yes - C23 failed at `Pest.php:239` ("Câmara dos Deputados recent list must come after Câmara dos Deputados table"). C29 alone stayed green (1 test, 5 assertions), so C23's new assertion is what kills it |
| dates line moved away from the `<h1>`, to the end of `<main>` after the house sections | `Legislatures/Show.vue` `p.ma-legislature__dates` | yes - C23 failed at `LegislaturePageTest.php:90` ("Failed asserting that two variables reference the same object.") |
| round 1: search ordered by house first; accents kept in `SearchKey`; `q` leaking into the title; member names in overview props; `hydrate` on the home | `SearchController.php`, `SearchKey.php`, `LegislatureController.php`, `HomeController.php` | yes - all 5 killed (carried from a76e907; none of those files or their proofs changed) |

## Gate

- Each of the 50 named proofs run alone at `750b697`: 50 × exit 0, `"result":"passed"`, between 1 and 14 tests each, 0 failed
- `sail artisan test` (full suite at `750b697`): 250 passed, 0 failed, 3,487 assertions (up from 3,436 at a76e907)
- `sail bin pint --test`: passed · `sail bin phpstan analyse`: 0 errors
- `sail npm run build`: client and SSR bundles written · SSR `/health` 200 · live `curl` of `/`, `/busca/`, `/legislaturas/58/`: 200, the expected `<h1>`, 0 `<script src`
- `node tests/budget/first-load.mjs http://localhost:8093`: exit 0 (97,430 / 99,028 / 98,652 bytes)
- `mandato-home` stopped at the end with `sail stop` (containers kept, volume kept)
- `python3 /home/augusto/.claude/skills/tlc-spec-lean/scripts/validate_verification.py app-home`: exit 0 (0 errors, 0 warnings)

### Observations (not failing)

- `expectDocumentOrder` treats containment as following: `compareDocumentPosition` sets `DOCUMENT_POSITION_FOLLOWING` together with `CONTAINED_BY`. So nesting a later section inside an earlier one (for example `.ma-recent` inside `.ma-cal-table`) would still pass the order assertion. C2 guards the one plausible nesting, the house block inside the form, with `contains` (`HomePageTest.php:92`), and C3 guards the overview link against the last block (`:136`). The overview's sections are siblings in the current template. If a later change could nest them, a non-containment assertion would make the order claim fully hold.
- Over the base fixtures the home has one house block, so "first house block" and "last house block" are the same node. The helper skips that pair on purpose (`Pest.php:235`). The two-block order on the home (Câmara before Senado) is held by C3's `houseBlocks` list order over the search fixture (`HomePageTest.php:143-154`), not by the helper.
- Carried from a76e907: C39 checks the SSR-down `description` by presence and equality to `og:description`. The home form's empty `casa=&uf=&partido=` falls in C14's out-of-set class, which no proof names (probed live in round 1: 200). C44 assumes production gzips text, an obligation for the deploy feature.
