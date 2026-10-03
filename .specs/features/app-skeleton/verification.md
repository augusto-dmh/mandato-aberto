# App skeleton verification

**Verdict**: FAIL
**Profile**: standard
**Diff range**: 7589df9..2c4543a9a13328d4880b5f1258144d6da1ffc621
**Round**: 1 - full
**Verifier**: independent sub-agent (author != verifier)

Every one of the 48 checks is proven at HEAD with a located assertion, and all 5 injected faults were killed. The verdict is FAIL because recomputing Coverage from the plan found 3 members of sets the plan names that no proof asserts: the `X-Inertia` JSON output in `Surface`, the `house` check on `roll_calls` and `propositions` (door 4 and Relations), and the `dir` argument's default in the command (door 5 and Observable "every flag and its default"). The code for all three exists and works when exercised by hand. What is missing is a test.

## Binding sources

The plan's `Sources` are `.specs/STATE.md` AD-002, AD-013, AD-014 and AD-015, `research/05-grilling-escopo-v2.md` decisions 1-4 and its assumptions table, and `.specs/features/design-system/plan.md` door 1. Step 1 is `ui`-only, so this section only records what was opened and compared.

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `.specs/STATE.md` AD-002, AD-013, AD-014, AD-015 | yes - read in full | none | - |
| `research/05-grilling-escopo-v2.md` decisions 1-4 + premissas | yes - lines 10-41 | none (PostgreSQL, Pest/Larastan/Pint, Artisan import, `app/` beside `etl/`, deploy out of scope all match) | - |
| `.specs/features/design-system/plan.md` door 1 | yes - line 43 (`"mandato-design": "file:../design"`) | none | - |
| MVP strings that AC 14, 17, 19 and 22 adopt (`site/src/pages/deputados/[id].astro:35-36`, `site/src/pages/votacoes/[id].astro:29-33`, `site/src/lib/format.ts:80-96`, `site/src/pages/404.astro:12`, `site/src/layouts/Base.astro:26`) | yes | none: C20 and C29 match the MVP's title, description and title suffix exactly. C25's order matches `GROUPS` | - |

## Checks

The Pest proofs ran as one invocation at HEAD `2c4543a`, inside the builder's Sail container with fresh `design` and `app` builds and the SSR server restarted on the new bundle: `docker exec ... php artisan test --log-junit` exit 0. JUnit shows tests=62, assertions=510, errors=0, failures=0, skipped=0, and every check-named test appears by name. The compact `sail artisan test` run also exited 0 with `{"result":"passed","tests":62,"passed":62}`.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | fixture import: 3 members, 1 legislature, 3 memberships with every indicator, 8 roll calls, 7 propositions, 6 authorships, exit 0 | junit `imports every record of the fixture` passed | `app/tests/Feature/ImportTest.php:16` `expect($result['code'])->toBe(0)`; `:17-21` members `toBe([... '101' ... '102' ... '103'])`; `:22` legislatures `toBe([57])`; `:36-38` 101 `toBe([4, 5, 2, 3, 2, 3, 5, 2, 3])`; `:40-44` counts 8, 7, 6 | PASS |
| C2 | 16 votes, each with the vote, party and partyMajority of its deputy-file entry | junit `stores every vote of the deputy files` passed | `app/tests/Feature/ImportTest.php:63` `expect($expected)->toHaveCount(16)`; `:66` `expect($stored)->toBe($expected)` | PASS |
| C3 | prints the summary line and records contract_imports with version, generatedAt, SHA-256 and counts | junit `reports and records the import` passed | `app/tests/Feature/ImportTest.php:72` `toBe('Imported schema_version 2 generated 2026-09-27T12:00:00Z: 3 members, 8 roll calls, 16 votes, 7 propositions')`; `:76-79` schema_version 2, Zulu generatedAt, `hash_file('sha256', ...)`, `[3, 8, 16, 7]` | PASS |
| C4 | schema_version 3: exit 1, exact stderr, row counts unchanged | junit `refuses an unsupported schema version` passed | `app/tests/Feature/ImportTest.php:92-94` `code toBe(1)`, `err toBe('schema_version 3 is not supported; expected one of: 2')`, `tableCounts() toBe($before)` | PASS |
| C5 | each of the 5 files missing: exit 1, path on stderr, counts unchanged | junit 5 data sets of `refuses a contract with a missing file` passed | `app/tests/Feature/ImportTest.php:105-107` `code toBe(1)`, `err toContain("{$dir}/{$file}")`, counts unchanged; `:108` dataset of all 5 files | PASS |
| C6 | each of the 5 files broken: exit 1, path and first pointer, counts unchanged | junit 5 data sets of `refuses a file that fails its schema` passed | `app/tests/Feature/ImportTest.php:118-121` code 1, path, `toContain($pointer)`, counts; `:123-143` pointers `/generatedAt`, `/0/uf`, `/0/date`, `/votes/0/rollCallId`, `/tallies/yes` | PASS |
| C7 | missing dir: exit 2 and exact stderr | junit `rejects a missing directory` passed | `app/tests/Feature/ImportTest.php:149-150` `code toBe(2)`, `err toBe('contract directory not found: /nonexistent')` | PASS |
| C8 | second import leaves 6 tables identical except timestamps, ids included | junit `importing twice changes nothing` passed | `app/tests/Feature/ImportTest.php:160-161` `not->toBeEmpty()` and `$second[$table] toBe($first[$table])` (dump keeps `id`, `app/tests/Pest.php:79` strips only the timestamps) | PASS |
| C9 | the smaller snapshot sweeps roll call, votes, membership and authorship, and keeps member 103 and proposition 6005 | junit `sweeps what the snapshot no longer has` passed | `app/tests/Feature/ImportTest.php:210-221` `200-3` absent, vote absent, membership and votes of 103 absent, 102/100-4 absent, authorship 101/6005 absent, `$member103 not->toBeNull()`, proposition 6005 `toBeTrue()`; `:224` total `16 - 1 - 3 - 1` | PASS |
| C10 | a trigger failure rolls every table back row by row, exit 1 | junit `rolls back on a failed write` passed | `app/tests/Feature/ImportTest.php:252-253` `code toBe(1)`, `tableDump() toBe($before)` (all 8 tables with timestamps) | PASS |
| C11 | lock held by a second connection: exit 1, exact stderr, 0 rows | junit `refuses while another import holds the lock` passed | `app/tests/Feature/ImportTest.php:259` second connection `pg_advisory_lock`; `:268-270` code 1, `err toBe('another import is running')`, `array_sum(tableCounts()) toBe(0)` | PASS |
| C12 | dry run: exit 0, `Would import ...` line, 0 rows; on v3 exit 1, 0 rows | junit `dry run validates and writes nothing` passed | `app/tests/Feature/ImportTest.php:276-278` code 0, exact line, 0 rows; `:286-287` code 1, 0 rows | PASS |
| C13 | no cpf or candidacy column, and no candidacy value in any row | junit `persists no candidacy field and no cpf` passed | `app/tests/Feature/ImportTest.php:295` `not->toMatch('/cpf\|candidacy\|office\|ballot\|situation/i')`; `:300` `not->toContain($value)` over the 5 values | PASS |
| C14 | 6 natural keys reject duplicates, `members.house` rejects `presidencia` and accepts `senado`, source_id is `text` | junit `enforces the natural keys and the house check` passed | `app/tests/Feature/SchemaTest.php:45` `toThrow(UniqueConstraintViolationException::class)` per table; `:48` senado `toBeInt()`; `:53` `getCode() toBe('23514')`; `:58` `toBe('text')` for 3 tables | PASS |
| C15 | `SUPPORTED_SCHEMA_VERSIONS` [2], v2 has a reader, v1 and v3 none, contract_dir default | junit `resolves one reader per supported version` and `defaults the contract directory` passed | `app/tests/Feature/ImportTest.php:305-308` `toBe([2])`, `toBeInstanceOf(V2Reader::class)`, `for(1)`/`for(3) toBeNull()`; `:312` `toBe(base_path('../data/out'))` | PASS |
| C16 | schema_dir default, and a stricter schema copy names deputies.json and /0/uf | junit `validates against the schema directory` passed | `app/tests/Feature/ImportTest.php:316` `toBe(base_path('../etl/schema'))`; `:327-329` code 1, `toContain('deputies.json')`, `toContain('/0/uf')` | PASS |
| C17 | suite runs on pgsql, server major 18 | junit `runs on postgresql 18` passed | `app/tests/Feature/SchemaTest.php:63-64` driver `toBe('pgsql')`, `intdiv(server_version_num, 10000) toBe(18)` | PASS |
| C18 | profile 200 `Deputies/Show`, one h1, eyebrow, three NDeM values, three counts | junit `profile shows the deputy and the indicators` passed | `app/tests/Feature/DeputyPageTest.php:16` `assertOk()->assertInertia(... component('Deputies/Show'))`; `:19-21` h1 count 1 `Ana Souza`, eyebrow `Deputado federal · PT · SP`; `:24` `toBe(['4 de 5', '2 de 3', '2 de 3'])`; `:27` `toBe(['5', '2', '3'])` | PASS |
| C19 | 8 score columns and 8 table rows, oldest first, linking to /votacoes/{id}/ | junit `profile lists the votes oldest first` passed | `app/tests/Feature/DeputyPageTest.php:35` columns `toBe(...)`; `:38` table rows `toBe(...)` against the 8-id order at `:32` | PASS |
| C20 | head holds the 10 tags with exact values, once each | junit `profile head carries the share tags` passed | `app/tests/Feature/DeputyPageTest.php:45-56` `expect($tags)->toBe([...])`: every key a one-element list with the literal value | PASS |
| C21 | 103: two "Sem base" blocks with no digit, and `0 de 1` | junit `indicator without base shows no number` passed | `app/tests/Feature/DeputyPageTest.php:65-68` both `toBe('Sem base de cálculo no período')`, `not->toMatch('/\d/')`, `toBe('0 de 1')` | PASS |
| C22 | initials `AS`, no `<img>` | junit `profile draws the initials frame and no remote image` passed | `app/tests/Feature/DeputyPageTest.php:74-75` `toBe('AS')`, `querySelectorAll('img') toHaveCount(0)` | PASS |
| C23 | 999999, abc and a senado 555 all 404 with lang pt-BR and the one h1 | junit 3 data sets of `profile 404s` passed | `app/tests/Feature/DeputyPageTest.php:93-97` `assertNotFound()`, lang `toBe('pt-BR')`, h1 count 1 `toBe('Página não encontrada')`; `:98` 3 paths | PASS |
| C24 | 100-1: `RollCalls/Show`, h1, Aprovada, tally 2/1/0; 100-4 date heading; Rejeitada; não informado | junit `roll call shows heading result and tally` passed | `app/tests/Feature/RollCallPageTest.php:38` component; `:40-42` `PL 1/2023`, `Aprovada`, `toBe(['2', '1', '0'])`; `:44-46` `Votação nominal de 01/06/2025`, `Rejeitada`, `Resultado não informado` | PASS |
| C25 | group order over 8 values and pt-BR name order | junit `orders vote groups and names` passed | `app/tests/Unit/VoteGroupsTest.php:16` `toBe(['Sim', 'Não', 'Abstenção', 'Obstrução', 'Artigo 17', '', 'Beta', 'Zeta'])`; `:22` `toBe(['Abel', 'Ágata', 'Edu', 'Érico', 'Zuleica'])` | PASS |
| C26 | 8 `.ma-group` in that order, every row with `svg.ma-vote` and a profile link | junit `roll call groups every deputy by vote` passed | `app/tests/Feature/RollCallPageTest.php:59` `toHaveCount(8)`; `:63-65` one row, `svg.ma-vote not->toBeNull()`, href `toBe("/deputados/{$deputy}/")` in expected order | PASS |
| C27 | secret 100-6: one group `Deputados que votaram` with the 3 names | junit `secret roll call lists who voted` passed | `app/tests/Feature/RollCallPageTest.php:76-78` `toHaveCount(1)`, `toStartWith('Deputados que votaram')`, `toBe(['Ana Souza', 'Bruno Lima', 'Carla Dias'])` | PASS |
| C28 | no vote record: the sentence and no group | junit `roll call without votes says so` passed | `app/tests/Feature/RollCallPageTest.php:86-87` `toContain('Nenhum voto individual registrado nesta votação')`, `.ma-group toHaveCount(0)` | PASS |
| C29 | 100-1 has all 10 tags exactly; 100-6 has the secret title and description; 200-1 writes `(CCJC)` | junit `roll call head carries the share tags` passed | `app/tests/Feature/RollCallPageTest.php:93-104` `expect($open)->toBe([...])` with all 10; `:107-109` secret title, description, og:url; `:111` `toContain('(CCJC)')` | PASS |
| C30 | 999-9 and abc 404 with the C23 page | junit 2 data sets of `roll call 404s` passed | `app/tests/Feature/RollCallPageTest.php:117-121` `assertNotFound()`, lang, h1 `Página não encontrada` | PASS |
| C31 | SSR up: `#app` holds the h1 and every number of both pages | junit `server renders the page body` passed | `app/tests/Feature/SharedLinksTest.php:21-23` inside `getElementById('app')`: `Ana Souza`, `['4 de 5', '2 de 3', '2 de 3']`, `['5', '2', '3']`; `:27-28` `PL 1/2023`, `['2', '1', '0']` | PASS |
| C32 | SSR at a closed port: 200, the C20/C29 tags, empty `#app` | junit `head tags survive an ssr outage` passed | `app/tests/Feature/SharedLinksTest.php:32` `inertia.ssr.url` to `127.0.0.1:9`; `:36` `assertOk()`; `:40-49` title, og:title, canonical, og:url, og:type, og:site_name, og:locale, twitter:card by value, description present once and equal to og:description; `:51` `childElementCount toBe(0)`. Precision note: here the description is asserted by presence and equality, not by literal (its literal is asserted by C20/C29 from the same Blade line) | PASS |
| C33 | no Set-Cookie on the 2 pages and 3 404s, and the curl prints 0 | junit 5 data sets of `public pages set no cookie` passed; `sail artisan mandato:import ../site/tests/fixtures/out` exit 0, then `curl -sI http://localhost:8088/deputados/101/ \| grep -ci '^set-cookie'` printed `0` (the same curl on the other 4 paths also printed 0) | `app/tests/Feature/SharedLinksTest.php:58-59` `assertStatus($status)->assertHeaderMissing('Set-Cookie')`, `getCookies() toBe([])` | PASS |
| C34 | footer shows the Brasília day 27/09/2026, then 30/09/2026 | junit `footer carries the brasília collection day` passed | `app/tests/Feature/SharedLinksTest.php:73` `toContain('... coletados em 27/09/2026')`; `:82` `toContain('... coletados em 30/09/2026')` after the `2026-10-01T02:30:00Z` row | PASS |
| C35 | slashless paths: 200 and the slash canonical | junit `slashless paths answer with the slash canonical` passed | `app/tests/Feature/SharedLinksTest.php:89-90` `assertOk()`, canonical `toBe(["https://mandato.test{$canonical}"])` | PASS |
| C36 | no `Head` import, and app.js sets document.title from meta.title on navigate | junit `pages leave the share tags to blade` passed | `app/tests/Feature/SharedLinksTest.php:96` `not->toMatch(... Head ... @inertiajs/vue3)`; `:99-100` `router.on("navigate"`, `document.title = ... meta.title` | PASS |
| C37 | both routes carry `public` and not `web`; the group is exactly the two middleware | junit `public routes use the cookie-free group` passed | `app/tests/Feature/SharedLinksTest.php:106` `toContain('public')->not->toContain('web')`; `:108` `toBe([SubstituteBindings::class, HandleInertiaRequests::class])` | PASS |
| C38 | digit and digits-digits constraints, and no redirect rule in .htaccess | junit `keeps the mvp url shapes` passed | `app/tests/Feature/SharedLinksTest.php:112-115` wheres `toBe(['id' => '[0-9]+'])` and `toBe(['id' => '[0-9]+-[0-9]+'])`; `:117-119` `12a`, `100`, `100-1-2` 404; `:121` `.htaccess not->toContain('R=301')`; file read at `app/public/.htaccess:1-20` | PASS |
| C39 | ci job app: postgres:18, PHP 8.5 with pdo_pgsql and intl, Node 24, every gate, no continue-on-error, phpstan level 6 | junit `ci job app runs every gate` passed | `app/tests/Feature/ProjectFilesTest.php:11` `toBe('postgres:18')`; `:16-18` `8.5`, `pdo_pgsql`, `intl`, `24`; `:26` each of the 9 commands in its dir; `:29`, `:31` no `continue-on-error`; `:32` level `toBe(6)`; `.github/workflows/ci.yml` job `app` read in the diff | PASS |
| C40 | Pint finds nothing | `sail bin pint --test` exit 0, `{"tool":"pint","result":"passed"}` | command output (gate proof) - `app/pint` default preset over the tree; no config file | PASS |
| C41 | Larastan level 6 clean | `sail bin phpstan analyse --no-progress` exit 0, `{"result":"passed","errors":0}` | `app/phpstan.neon:5` `level: 6` | PASS |
| C42 | client and SSR build exit 0 and write both files | `sail npm run build` exit 0; `test -f public/build/manifest.json && test -f bootstrap/ssr/ssr.js` true | `app/package.json:9` `"build": "vite build && vite build --ssr"`; both files listed after the build | PASS |
| C43 | no forbidden term in app/resources/lang; the matcher is whole word and case-insensitive | junit `no forbidden term in app copy` and `forbidden matcher is whole word and case-insensitive` passed | `app/tests/Feature/ForbiddenTermsTest.php:11` `expect($hits)->toBe([])`; `:23-26` hits are exactly `a.vue`/`faltou` and `b.blade.php`/`intenção de voto` (so `Aprovada` and `faltoso` pass) | PASS |
| C44 | config list holds all 19 MVP terms | junit `forbidden list matches the mvp list` passed | `app/tests/Feature/ForbiddenTermsTest.php:34` `toHaveCount(19)`; `:36` `config('forbidden-terms') toContain($term)` | PASS |
| C45 | README names the 4 commands | junit `readme names the commands` passed | `app/tests/Feature/ProjectFilesTest.php:40` `toContain($command)` over the 4 commands | PASS |
| C46 | composer.json and package.json declare the door 1 and door 7 literals | junit `declares the door 1 versions` passed | `app/tests/Feature/ProjectFilesTest.php:46-57` `toMatchArray([...])` for the 9 composer constraints; `:60-67` for the 6 npm constraints | PASS |
| C47 | `file:../design`, `ssr.noExternal`, `./styles/*` export, pages import components | junit `consumes the design package` passed | `app/tests/Feature/ProjectFilesTest.php:72-73` `toBe('file:../design')`, noExternal regex; `:76-77` `toBe('./styles/*')`; `:80` `from "mandato-design/components/` in both pages | PASS |
| C48 | Sail runtime 8.5, postgres:18-alpine, 5 sibling mounts | junit `sail runs php 8.5 with the sibling mounts` passed | `app/tests/Feature/ProjectFilesTest.php:87-95` context `toBe('./vendor/laravel/sail/runtimes/8.5')`, image `toBe('postgres:18-alpine')`, volumes `toContain(...)` all 5 | PASS |

## Coverage

Members were recomputed from the authority for each set: `Surface` for routes, statuses and outputs, `Relations` and door 4 for constraints, door 5 for exit codes and flags, AC 14 and AC 22 for head tags, and `site/src/lib/forbidden-terms.ts:9-29` for the terms. The authority for the stored constraints is the plan. They were then located in `app/database/migrations/2026_10_02_000000_create_mandate_tables.php`.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| `GET /deputados/{id}/` statuses (2) | plan Surface | 200 C18 `DeputyPageTest.php:16` · 404 C23 `DeputyPageTest.php:93` | - |
| `GET /votacoes/{id}/` statuses (2) | plan Surface | 200 C24 `RollCallPageTest.php:38` · 404 C30 `RollCallPageTest.php:117` | - |
| Surface outputs per route (3 each: HTML, `meta` prop, `X-Inertia` JSON on Inertia visits) | plan Surface, column Out | HTML C18/C24 · `meta` prop C20/C29 (Blade reads `$page['props']['meta']`, `app/resources/views/app.blade.php:3` (the `$meta` read; tags at `:11-19`)) · `X-Inertia` JSON: none. `rg -n -i "x-inertia\|withHeaders\|getJson" app/tests` returns no match. By hand, `curl -H 'X-Inertia: true' -H 'X-Inertia-Version: …' /deputados/101/` answers 200 `application/json` `{"component":"Deputies\/Show",…}`, but no test asserts it | `X-Inertia` JSON response of `/deputados/{id}/` and `/votacoes/{id}/` |
| `mandato:import` exit codes (3) | plan door 5 | 0 `ImportTest.php:16` · 1 `ImportTest.php:92` · 2 `ImportTest.php:149` | - |
| `mandato:import` arguments and flags with defaults (2) | plan door 5 + Observable "every flag and its default" | `--dry-run` C12 `ImportTest.php:276` · `dir` omitted falls back to `config('mandato.contract_dir')` (`app/app/Console/Commands/ImportContract.php:25`): no proof. C15 asserts only the config value (`ImportTest.php:312`), and `rg -n "runImport\(" app/tests` shows every call passes `'dir'` | `dir` default used by the command |
| import refusal causes (6) | ACs 3, 4, 5, 8, 9 | C4, C5, C6, C7, C10, C11 at the cited lines | - |
| contract file kinds missing (5) / invalid (5) | AC 4 + fixture layout | C5 dataset `ImportTest.php:108` · C6 dataset `ImportTest.php:123-143` | - |
| entities of Relations (9) | plan Relations | Member, Membership, Legislature, RollCall, Proposition, Authorship C1 · Vote C2 · ContractImport C3 · House C14 | - |
| one-way constraints of door 4 and Relations (9 members; the house check spans 3 tables: migration `:114-115` adds it to `members`, `propositions` and `roll_calls`) | plan Relations + door 4 | 6 natural keys C14 `SchemaTest.php:45` · `Legislature` keyed by number: proven by construction (migration `:18` primary key, and `:38`/`:65` foreign keys reference it, so without the key every `RefreshDatabase` migration fails) · `text` source ids on 3 tables C14 `SchemaTest.php:58` · house check on `members` C14 `SchemaTest.php:48-53` · house check on `roll_calls` and `propositions`: no proof | house check on `roll_calls.house` and `propositions.house` |
| swept on a smaller snapshot (4) / never swept (2) | door 6 | C9 `ImportTest.php:210-221` | - |
| import write path (3) | AC 1, 8, 10 | real C1 · dry C12 · failed C10 | - |
| profile figures (6) / indicator base (2) | AC 12, 15 | C18 `DeputyPageTest.php:24,27` · C21 `:65-68` | - |
| profile 404 causes (3) / roll-call 404 causes (2) | AC 17, 23 | C23 dataset `DeputyPageTest.php:98` · C30 dataset `RollCallPageTest.php` (`/votacoes/999-9/`, `/votacoes/abc/`) | - |
| roll-call heading (2) / result label (3) | AC 18, `RollCallController.php:21-22,47-51` | C24 `RollCallPageTest.php:40,44-46` | - |
| vote group order (8 positions) | AC 19 | C25 `VoteGroupsTest.php:16` all 8 · C26 `RollCallPageTest.php:59-65` all 8 over HTTP | - |
| roll-call ballots (3) | AC 19-21 | open C26 · secret C27 · none C28 | - |
| head tags of AC 14 (10) | AC 14 | C20 `DeputyPageTest.php:45-56` all 10 by value | - |
| head tags of AC 22 (10 + secret variant + organ) | AC 22 | open: all 10 C29 `RollCallPageTest.php:93-104`. Secret: title, description and og:url C29 `:107-109`; og:title and og:description come from the same `meta` keys as title and description (`app.blade.php:11-12,17-18`), and the other 4 are literal Blade text. Organ C29 `:111` | - |
| SSR states (2) | AC 24, 25 | running C31 · unreachable C32 | - |
| cookie-free responses (5) | AC 26 | C33 dataset `SharedLinksTest.php:61-65` + curl | - |
| footer Brasília day (2) / trailing slash (4) | AC 27, 28 | C34 `:73,82` · C18, C24, C35 `:89-90` | - |
| CI gates of AC 29 (5) | AC 29 + door 12 | C39 `ProjectFilesTest.php:26` for all 9 steps · C40, C41, C42 run here | - |
| forbidden-term roots (3) | AC 30 | `ForbiddenTermsTest.php:11` scans `app_path()`, `resource_path()`, `lang_path()` | - |
| forbidden terms (19) | `site/src/lib/forbidden-terms.ts:9-29` | C44 `ForbiddenTermsTest.php:34,36`. By diff, `app/config/forbidden-terms.php` holds the same 19 in the same order | - |
| README commands (4) | AC 31 | C45 `ProjectFilesTest.php:40` | - |
| Landing doors (13) | plan Landing | 1 C46 · 2 C47 (the arrangement clause was read against `design/screens/Profile.vue` and `RollCall.vue`: same regions in the same order without `Chrome`/`AiSummaryFrame`, and selector-reached by C18-C29) · 3 C17 · 4 C14 (gap above) · 5 C15 (gap above) · 6 C8-C11 · 7 C16 · 8 C20, C32, C36 · 9 C33, C37 · 10 C35, C38 · 11 C48 · 12 C39 · 13 C48 | see the door 4 and door 5 rows |
| startup config: `public` group | `app/bootstrap/app.php:22-25` (read directly) | C37 `SharedLinksTest.php:108` reads the kernel that `bootstrap/app.php` builds, the same assembly the HTTP server boots | - |
| startup config: SSR URL | `app/config/inertia.php:30` (read directly) | C31 (default URL, live server), C32 (overridden) | - |

## Test policy rows

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `ImportContract.php` (6 causes into 3 codes), `ContractReaders.php`, `Importer.php`, `VoteGroups.php`, roll-call presentation in `RollCallController.php` | boundary and own layer | yes. Command: each cause at the Artisan boundary (C4-C7, C10-C12), which is where it decides. Version seam: own layer C15 (`ImportTest.php:305-308`) plus boundary C4. Importer: C8-C11 against PostgreSQL. VoteGroups: own layer C25 plus boundary C26. Roll-call presentation decides inside the controller, so the HTTP proof is also its own-layer proof, and every decision row is asserted (heading 2, result 3, meta variants 2 + organ: C24, C29) |
| Decides, not reached across a boundary | none: every deciding class is reached through the command or HTTP | own layer | yes - no file classified |
| Entry point that decides nothing | `routes/public.php` | boundary: accepted, each rejected input, each error path | yes. Accepted C18, C24, C35. Rejected `abc`, `12a`, `100`, `100-1-2` (C23, C30, C38). Error path: unknown id 404 (C23, C30) |
| Instrumentation, pass-throughs | `DeputyController.php`, `HandleInertiaRequests.php` (`collectedAt`), models, `Dates.php` | none of its own | yes. Covered by C18-C23 (profile), C34 (`collectedAt`) and C24/C29 (`Dates::br`) |

## Faults injected

Isolation: `git worktree add --detach /tmp/verify-app-skeleton-wt HEAD`, with its own containers (`verify-app-skeleton-pgsql` on `postgres:18-alpine` and `verify-app-skeleton-app` from image `sail-8.5/app`, which mounts the scratch at `/var/www`) and its own SSR server. The scratch baseline was 62 passed. Each fault was reverted with `git checkout -- <file>` inside the scratch, and the scratch porcelain was empty after each revert. Afterwards the containers, the network and the worktree were removed. The real tree's `git status --porcelain` was empty before and after, compared with `diff`. The builder's containers were never mutated.

| Mutation | Location | Killed |
| --- | --- | --- |
| sweep stops deleting votes (`])->delete();` -> `])->count();`) | `app/app/Import/Importer.php:137` | yes - `sweeps what the snapshot no longer has` failed |
| version seam accepts 3 (`2 =>` -> `2, 3 =>`) | `app/app/Contract/ContractReaders.php:13` | yes - `refuses an unsupported schema version` failed on its stderr assertion |
| `og:url` drops the trailing slash | `app/resources/views/app.blade.php:19` | yes - `profile head carries the share tags` failed (`two arrays are identical`) |
| `public` group gains `StartSession` | `app/bootstrap/app.php:23` | yes - `public pages set no cookie` failed on 4 of 5 data sets (the unmatched-route 404 never enters the group) |
| unknown vote values sort before the known ones (`PHP_INT_MAX` -> `-1`) | `app/app/Presenters/VoteGroups.php:48` | yes - `orders vote groups and names` failed |

## Gate

- `sail npm --prefix /var/www/design ci && ... run build` exit 0 (`wrote /var/www/design/dist/tokens.css`)
- `sail npm ci && sail npm run build` exit 0. It writes `public/build/manifest.json` and `bootstrap/ssr/ssr.js`
- SSR server restarted on the new bundle (`inertia:stop-ssr`, then `inertia:start-ssr` detached; `/health` `OK`). It was left running as found
- `sail artisan test` exit 0 - 62 passed, 0 failed, 0 skipped, 510 assertions (JUnit run)
- `sail bin pint --test` exit 0
- `sail bin phpstan analyse --no-progress` exit 0, 0 errors
- C33 `curl` printed `0` after `sail artisan mandato:import ../site/tests/fixtures/out` exit 0. This import is an upsert of the same fixture into the builder's dev database `laravel`, which already held it. `contract_imports` went from 2 rows to 3. No other table changed

Ranked gaps (each is a missing test; the code behaves correctly when exercised by hand):
1. Door 4 / Relations: the house check on `roll_calls` and `propositions` is unproven. C14 only inserts into `members` (`app/tests/Feature/SchemaTest.php:48-53`), but the migration adds the check to all three tables (`app/database/migrations/2026_10_02_000000_create_mandate_tables.php:114-115`). A mutant dropping those two tables from the loop would survive.
2. Surface Out: the `X-Inertia` JSON response is unproven. No test sends `X-Inertia`. The output exists only through the framework (`app/app/Http/Middleware/HandleInertiaRequests.php`), and the checks gave Surface outputs no Coverage row.
3. Door 5 / Observable: the command's fallback to `config('mandato.contract_dir')` when `dir` is omitted is unproven (`app/app/Console/Commands/ImportContract.php:25`). C15 checks the config value only (`app/tests/Feature/ImportTest.php:312`). A mutant that ignores the config would survive.
