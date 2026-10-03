# App skeleton verification

**Verdict**: PASS
**Profile**: standard
**Diff range**: 7589df9..2bb22c1f4a1006bbfebd3e8636db2ea189c3595f
**Round**: 2 - scoped
**Verifier**: independent sub-agent (author != verifier)

Round 1 (`2c4543a`) failed on three members of plan sets that no proof asserted: the house check on `propositions` and `roll_calls`, the `X-Inertia` JSON output of both pages, and the `mandato:import` fallback to `config('mandato.contract_dir')` when `dir` is omitted. The fix `2bb22c1` touches tests and `checks.md` only. It adds a table-driven house-check test, a `dir`-omitted import test and a new check C49 for the Inertia JSON visit. All 49 checks' proofs were re-run at `2bb22c1` and pass: 68 tests, 554 assertions, Pint, Larastan, both builds and the C33 curl. Five new faults were injected on the three new assertion surfaces. Each was killed, and each only by the new tests. Coverage recomputed for the three rows the fix touched now has no unproven member.

Scope of this round, per `verify.md` "Re-verifying after a fix": the fix's diff, plus the three non-PASS Coverage rows of round 1. Proofs re-ran in full at HEAD. Sections that only say `carried from 2c4543a` were not re-reviewed.

## Judgement of the checks.md edits

The fixer edited C14 and C15 and added C49 after approval. Each edit strengthens or extends the approved claim. None weakens it, and none retrofits it to what the code happens to do:

- **C14**: before, the house check was claimed only for `members`, and only `presidencia` was rejected. Now it is claimed for `members`, `propositions` and `roll_calls`, and `presidencia`, `Camara` and `''` are rejected, each with SQLSTATE `23514` and the constraint name `{table}_house_check`. This is exactly door 4's "house restricted to `camara`, `senado`" (`plan.md:69`, `:88`) over every table the migration constrains (`app/database/migrations/2026_10_02_000000_create_mandate_tables.php:114-115`). The old proof test was split: the natural-key half and the `text` half are unchanged under the name `enforces the natural keys and text source ids` (`app/tests/Feature/SchemaTest.php:14-52`). Its house half moved to `rejects a house outside camara and senado` (`:54-74`) and kept the `senado` acceptance (`:57`). Nothing the old proof asserted was dropped.
- **C15**: the claim keeps every round-1 assertion (`app/tests/Feature/ImportTest.php:305-312`). It adds the behaviour door 5 names, `dir` default `config('mandato.contract_dir')` (`plan.md:89`), together with the Observable "every flag and its default" (`plan.md:236`). The new half has two sides. Pointed at the fixture, the command exits 0 with C3's line. Pointed at `/nonexistent-contract-dir`, it exits 2 with that path. The second side is what rules out a hardcoded default.
- **C49**: new. It proves a member the plan already listed in Surface (`plan.md:76-77`, "`X-Inertia` JSON on Inertia visits"), so it adds no new obligation. Precision note: it asserts `props.meta.title` as a non-empty string, not a literal. The literal is asserted over HTML by C20 and C29 from the same `meta` prop, and C49 asserts the component by exact value.
- The checks.md Coverage table gained a Surface-outputs row and a `dir`-argument row, and door 4 now lists 10 members. C15 joins the exit-code claim list. The build note records the round-1 gaps. All of this matches the diff.

## Binding sources

carried from 2c4543a. The fix touched no source, plan or interface, and step 1 is `ui`-only.

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `.specs/STATE.md` AD-002, AD-013, AD-014, AD-015 | yes (round 1) | none | - |
| `research/05-grilling-escopo-v2.md` decisions 1-4 + premissas | yes (round 1) | none | - |
| `.specs/features/design-system/plan.md` door 1 | yes (round 1) | none | - |
| MVP strings adopted by AC 14, 17, 19, 22 | yes (round 1) | none | - |

## Checks

verified at 2bb22c1. Before the run, inside the builder's container `mandato-aberto-laravel.test-1` (mounts this worktree), I ran `sail npm --prefix /var/www/design ci && ... run build` (exit 0) and `sail npm ci && sail npm run build` (exit 0). Then `inertia:stop-ssr` and a detached `inertia:start-ssr`, with `/health` `OK`. The run itself was `php artisan test --log-junit` with exit 0. JUnit shows tests=68, assertions=554, errors=0, failures=0, skipped=0. All 50 distinct `--filter` names in `checks.md` appear in the JUnit testcase names (the `<test name>` placeholder in the preamble aside). Citations were refreshed for the three test files the fix touched. Rows for untouched files keep their round-1 citations, and their tests passed in this run.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | fixture import counts and indicators, exit 0 | junit `imports every record of the fixture` passed | `app/tests/Feature/ImportTest.php:16-44` | PASS |
| C2 | 16 votes with vote, party, partyMajority | junit `stores every vote of the deputy files` passed | `app/tests/Feature/ImportTest.php:63,66` | PASS |
| C3 | summary line and contract_imports row | junit `reports and records the import` passed | `app/tests/Feature/ImportTest.php:72,76-79` | PASS |
| C4 | schema_version 3 refused, exit 1, counts unchanged | junit `refuses an unsupported schema version` passed | `app/tests/Feature/ImportTest.php:92-94` | PASS |
| C5 | each missing file refused | junit 5 data sets of `refuses a contract with a missing file` passed | `app/tests/Feature/ImportTest.php:105-108` | PASS |
| C6 | each broken file refused with pointer | junit 5 data sets of `refuses a file that fails its schema` passed | `app/tests/Feature/ImportTest.php:118-143` | PASS |
| C7 | missing dir exit 2, exact stderr | junit `rejects a missing directory` passed | `app/tests/Feature/ImportTest.php:149-150` | PASS |
| C8 | second import changes nothing | junit `importing twice changes nothing` passed | `app/tests/Feature/ImportTest.php:160-161` | PASS |
| C9 | sweep of the smaller snapshot | junit `sweeps what the snapshot no longer has` passed | `app/tests/Feature/ImportTest.php:210-224` | PASS |
| C10 | failed write rolls back | junit `rolls back on a failed write` passed | `app/tests/Feature/ImportTest.php:252-253` | PASS |
| C11 | lock held refuses | junit `refuses while another import holds the lock` passed | `app/tests/Feature/ImportTest.php:259,268-270` | PASS |
| C12 | dry run writes nothing | junit `dry run validates and writes nothing` passed | `app/tests/Feature/ImportTest.php:276-287` | PASS |
| C13 | no cpf or candidacy column or value | junit `persists no candidacy field and no cpf` passed | `app/tests/Feature/ImportTest.php:295,300` | PASS |
| C14 | 6 natural keys unique; house check on `members`, `propositions`, `roll_calls` accepts `senado`, rejects `presidencia`, `Camara`, `''` with `{table}_house_check`; `text` source ids | junit `enforces the natural keys and text source ids` passed; junit 3 data sets (`members`, `propositions`, `roll_calls`) of `rejects a house outside camara and senado` passed | `app/tests/Feature/SchemaTest.php:45` `toThrow(UniqueConstraintViolationException::class)` per table; `:50` `toBe('text')`; `:57` senado `toBeInt()`; `:58` the 3 bad values; `:63-64` `getCode() toBe('23514')` and `getMessage() toContain("{$table}_house_check")`; `:67-73` dataset of the 3 tables | PASS |
| C15 | `[2]`, reader per version, `contract_dir` default, and `dir` omitted reads the configured directory | junit `resolves one reader per supported version`, `defaults the contract directory`, `imports from the configured directory when none is given` passed | `app/tests/Feature/ImportTest.php:305-308`; `:312` `toBe(base_path('../data/out'))`; `:316-322` config set to the fixture, `runImport([])`, `code toBe(0)`, `out toContain('Imported schema_version 2 ... 7 propositions')`, members `toBe(3)`; `:324-328` config `/nonexistent-contract-dir`, `code toBe(2)`, `err toContain('contract directory not found: /nonexistent-contract-dir')`. `runImport([])` passes no `dir` (`app/tests/Pest.php:53-59`) | PASS |
| C16 | schema_dir default and stricter schema refuses | junit `validates against the schema directory` passed | `app/tests/Feature/ImportTest.php:332` `toBe(base_path('../etl/schema'))`; `:344-345` `toContain('deputies.json')`, `toContain('/0/uf')` | PASS |
| C17 | pgsql, server major 18 | junit `runs on postgresql 18` passed | `app/tests/Feature/SchemaTest.php:77-78` | PASS |
| C18 | profile page | junit `profile shows the deputy and the indicators` passed | `app/tests/Feature/DeputyPageTest.php:16-27` | PASS |
| C19 | 8 votes oldest first | junit `profile lists the votes oldest first` passed | `app/tests/Feature/DeputyPageTest.php:35,38` | PASS |
| C20 | 10 head tags | junit `profile head carries the share tags` passed | `app/tests/Feature/DeputyPageTest.php:45-56` | PASS |
| C21 | sem base | junit `indicator without base shows no number` passed | `app/tests/Feature/DeputyPageTest.php:65-68` | PASS |
| C22 | initials, no img | junit `profile draws the initials frame and no remote image` passed | `app/tests/Feature/DeputyPageTest.php:74-75` | PASS |
| C23 | 3 profile 404s | junit 3 data sets of `profile 404s` passed | `app/tests/Feature/DeputyPageTest.php:93-98` | PASS |
| C24 | roll-call heading, result, tally | junit `roll call shows heading result and tally` passed | `app/tests/Feature/RollCallPageTest.php:38-46` | PASS |
| C25 | group and name order | junit `orders vote groups and names` passed | `app/tests/Unit/VoteGroupsTest.php:16,22` | PASS |
| C26 | 8 groups over HTTP | junit `roll call groups every deputy by vote` passed | `app/tests/Feature/RollCallPageTest.php:59-65` | PASS |
| C27 | secret roll call | junit `secret roll call lists who voted` passed | `app/tests/Feature/RollCallPageTest.php:76-78` | PASS |
| C28 | no vote record | junit `roll call without votes says so` passed | `app/tests/Feature/RollCallPageTest.php:86-87` | PASS |
| C29 | roll-call head tags | junit `roll call head carries the share tags` passed | `app/tests/Feature/RollCallPageTest.php:93-111` | PASS |
| C30 | roll-call 404s | junit 2 data sets of `roll call 404s` passed | `app/tests/Feature/RollCallPageTest.php:117-121` | PASS |
| C31 | SSR body | junit `server renders the page body` passed | `app/tests/Feature/SharedLinksTest.php:19-28` | PASS |
| C32 | head tags survive SSR outage | junit `head tags survive an ssr outage` passed | `app/tests/Feature/SharedLinksTest.php:32,42,50-51` | PASS |
| C33 | no Set-Cookie, curl prints 0 | junit 5 data sets of `public pages set no cookie` passed; `sail artisan mandato:import ../site/tests/fixtures/out` exit 0, then `curl -sI http://localhost:8088/deputados/101/` piped into `grep -ci '^set-cookie'` printed `0` (also `0` on `/votacoes/100-1/`, `/deputados/999999/`, `/votacoes/999-9/`, `/nope`) | `app/tests/Feature/SharedLinksTest.php:73-74` | PASS |
| C34 | footer Brasília day | junit `footer carries the brasília collection day` passed | `app/tests/Feature/SharedLinksTest.php:88,97` | PASS |
| C35 | slashless paths canonical | junit `slashless paths answer with the slash canonical` passed | `app/tests/Feature/SharedLinksTest.php:105` | PASS |
| C36 | no Head import, title on navigate | junit `pages leave the share tags to blade` passed | `app/tests/Feature/SharedLinksTest.php:111` no `Head` import; `:114-115` `router.on("navigate"` and `document.title = ... meta.title` | PASS |
| C37 | cookie-free `public` group | junit `public routes use the cookie-free group` passed | `app/tests/Feature/SharedLinksTest.php:121,123` | PASS |
| C38 | MVP URL shapes, no R=301 | junit `keeps the mvp url shapes` passed | `app/tests/Feature/SharedLinksTest.php:127,136` | PASS |
| C39 | ci job app gates | junit `ci job app runs every gate` passed | `app/tests/Feature/ProjectFilesTest.php:11-32` | PASS |
| C40 | Pint clean | `sail bin pint --test` exit 0, `{"tool":"pint","result":"passed"}` | gate proof over the tree | PASS |
| C41 | Larastan level 6 clean | `sail bin phpstan analyse --no-progress` exit 0, `{"result":"passed","errors":0}` | `app/phpstan.neon:5` | PASS |
| C42 | both builds write both files | `sail npm run build` exit 0; `test -f public/build/manifest.json && test -f bootstrap/ssr/ssr.js` true | `app/package.json:9` | PASS |
| C43 | no forbidden term, matcher whole word | junit `no forbidden term in app copy`, `forbidden matcher is whole word and case-insensitive` passed | `app/tests/Feature/ForbiddenTermsTest.php:11,23-26` | PASS |
| C44 | 19 MVP terms | junit `forbidden list matches the mvp list` passed | `app/tests/Feature/ForbiddenTermsTest.php:34,36` | PASS |
| C45 | README commands | junit `readme names the commands` passed | `app/tests/Feature/ProjectFilesTest.php:40` | PASS |
| C46 | door 1 versions | junit `declares the door 1 versions` passed | `app/tests/Feature/ProjectFilesTest.php:46-67` | PASS |
| C47 | design package consumed | junit `consumes the design package` passed | `app/tests/Feature/ProjectFilesTest.php:72-80` | PASS |
| C48 | Sail runtime and mounts | junit `sail runs php 8.5 with the sibling mounts` passed | `app/tests/Feature/ProjectFilesTest.php:87-95` | PASS |
| C49 | `X-Inertia` visits to both pages answer 200 JSON with `X-Inertia: true`, the component and a `meta` prop with a title | junit 2 data sets (`profile`, `roll call`) of `inertia visits answer with json` passed | `app/tests/Feature/SharedLinksTest.php:56` version from `HandleInertiaRequests::version`; `:60` `assertOk()->assertHeader('X-Inertia', 'true')`; `:61` Content-Type `toContain('application/json')`; `:62` `json('component') toBe($component)`; `:63-64` `props.meta` non-empty array, `props.meta.title` non-empty string; `:66-67` `/deputados/101/` -> `Deputies/Show`, `/votacoes/100-1/` -> `RollCalls/Show` | PASS |

## Coverage

The three rows the fix touched were verified at 2bb22c1 by recomputing each from its plan authority. Every other row is carried from 2c4543a, where all of them were fully proven. The fix touched no production file, so no set gained a member.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| Surface outputs per route (3 each: HTML, `meta` prop, `X-Inertia` JSON) - verified at 2bb22c1 | `plan.md:76-77` column Out | HTML C18 `DeputyPageTest.php:16`, C24 `RollCallPageTest.php:38` · `meta` prop C20, C29 (Blade reads it), C49 `SharedLinksTest.php:63-64` · `X-Inertia` JSON of `/deputados/{id}/` and `/votacoes/{id}/` C49 `SharedLinksTest.php:60-62,66-67` | - |
| one-way constraints of door 4 and Relations (10: 6 natural keys, `Legislature` key, house check on 3 tables, `text` ids) - verified at 2bb22c1 | `plan.md:66-69,88`; migration `:114-115` adds the check to `members`, `propositions`, `roll_calls` | 6 natural keys C14 `SchemaTest.php:45` · `Legislature` keyed by number by construction (round 1) · house check on `members`, `propositions`, `roll_calls` C14 `SchemaTest.php:57-73` dataset, each table rejecting 3 values with its own named constraint · `text` source ids C14 `SchemaTest.php:50` | - |
| `mandato:import` arguments and flags with defaults (2: `dir`, `--dry-run`) - verified at 2bb22c1 | `plan.md:89` door 5 + `:236` Observable | `--dry-run` C12 `ImportTest.php:276` · `dir` given C1-C12 · `dir` omitted falls back to `config('mandato.contract_dir')` (`app/app/Console/Commands/ImportContract.php:25`) C15 `ImportTest.php:316-328` | - |
| route statuses (2 + 2), exit codes (3), refusal causes (6), file kinds (5 + 5), entities (9), swept (4) / never swept (2), write path (3), profile figures (6) / base (2), 404 causes (3 + 2), heading (2) / result label (3), group order (8), ballots (3), AC 14 tags (10), AC 22 tags, SSR states (2), cookie-free responses (5), footer day (2) / trailing slash (4), CI gates (5), forbidden roots (3) / terms (19), README commands (4), startup config (2) - carried from 2c4543a | as in round 1 | as in round 1. Citations into `SharedLinksTest.php` move by +15 from line 55 on (C33 now `:73-74`, C34 `:88,97`, C35 `:105`), and into `ImportTest.php` by +16 from line 315 on (C16 `:332,344-345`) | - |
| Landing doors (13) - verified at 2bb22c1 for doors 4 and 5, carried from 2c4543a for the rest | plan Landing | 1 C46 · 2 C47 · 3 C17 · 4 C14 (all 3 house-check tables) · 5 C15 (with the `dir` fallback) · 6 C8-C11 · 7 C16 · 8 C20, C32, C36 · 9 C33, C37 · 10 C35, C38 · 11 C48 · 12 C39 · 13 C48 | - |

## Test policy rows

carried from 2c4543a. The fix touched only test files and `checks.md`, and no row classifies a test file. Every row was met in round 1.

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `ImportContract.php`, `ContractReaders.php`, `Importer.php`, `VoteGroups.php`, roll-call presentation in `RollCallController.php` | boundary and own layer | yes. The `ImportContract.php` `dir` fallback now also has its boundary proof (C15) |
| Decides, not reached across a boundary | zero files classified | own layer | yes |
| Entry point that decides nothing | `routes/public.php` | boundary: accepted, each rejected input, each error path | yes |
| Instrumentation, pass-throughs | `DeputyController.php`, `HandleInertiaRequests.php`, models, `Dates.php` | none of its own | yes. `HandleInertiaRequests.php` is additionally reached by C49's JSON visit |

## Faults injected

Round 2: verified at 2bb22c1. Isolation: `git worktree add --detach /tmp/verify-app-skeleton-r2 HEAD` (at `2bb22c1`), with the gitignored `vendor`, `node_modules`, `design/dist` and the two bundles copied in. `.env` was copied with `APP_PORT=8098`, `FORWARD_DB_PORT=54350` and `VITE_PORT=5190`. Containers came from the scratch's own `compose.yaml` under compose project `verify-r2` (`verify-r2-laravel.test-1` mounting the scratch at `/var/www/html`, and `verify-r2-pgsql-1`), with their own SSR server. The scratch baseline was 68 passed. Each mutant ran the full suite and was reverted with `git checkout -- <file>` in the scratch, and the scratch porcelain was empty after each revert. Afterwards I ran `docker compose -p verify-r2 down -v`, which removed the containers, the `verify-r2_sail` network and the `verify-r2_sail-pgsql` volume. Then `git worktree remove --force` and `git worktree prune`, and `/tmp/verify-app-skeleton-r2` is gone. The real tree's `git status --porcelain` was empty before and after, compared with `diff`. The builder's containers were never mutated, and its SSR server is running (`/health` `OK`).

Each new mutant is invisible to every round-1 test: in each run, the only failures are the new tests.

| Mutation | Location | Killed |
| --- | --- | --- |
| house check dropped from `propositions` (loop over `['members', 'roll_calls']`) | `app/database/migrations/2026_10_02_000000_create_mandate_tables.php:114` | yes - only `rejects a house outside camara and senado` (propositions) failed: "a propositions row of house 'presidencia' was accepted"; 67 passed |
| `roll_calls` check also accepts `''` (the other two tables unchanged) | `app/database/migrations/2026_10_02_000000_create_mandate_tables.php:115` | yes - only `rejects a house outside camara and senado` (roll_calls) failed: "a roll_calls row of house '' was accepted"; 67 passed |
| component renamed to `RollCalls/Detail` on `X-Inertia` visits only (HTML visits keep `RollCalls/Show`) | `app/app/Http/Controllers/RollCallController.php:32` | yes - only `inertia visits answer with json` (roll call) failed: `-'RollCalls/Show' +'RollCalls/Detail'`; 67 passed |
| `X-Inertia` header stripped before the Inertia middleware, so Inertia visits get the HTML page | `app/app/Http/Middleware/HandleInertiaRequests.php` (added `handle()` override) | yes - only the 2 data sets of `inertia visits answer with json` failed: "Header [X-Inertia] not present on response"; 66 passed |
| omitted `dir` ignores config (`config('mandato.contract_dir')` -> `base_path('../data/out')`) | `app/app/Console/Commands/ImportContract.php:25` | yes - only `imports from the configured directory when none is given` failed at `ImportTest.php:320` (2 is not 0); 67 passed |
| sweep stops deleting votes (round 1) | `app/app/Import/Importer.php:137` | yes - carried from 2c4543a |
| version seam accepts 3 (round 1) | `app/app/Contract/ContractReaders.php:13` | yes - carried from 2c4543a |
| `og:url` drops the trailing slash (round 1) | `app/resources/views/app.blade.php:19` | yes - carried from 2c4543a |
| `public` group gains `StartSession` (round 1) | `app/bootstrap/app.php:23` | yes - carried from 2c4543a |
| unknown vote values sort first (round 1) | `app/app/Presenters/VoteGroups.php:48` | yes - carried from 2c4543a |

## Gate

verified at 2bb22c1, in the builder's containers (`mandato-aberto-laravel.test-1`, `mandato-aberto-pgsql-1`, `APP_PORT=8088`):

- `sail npm --prefix /var/www/design ci && ... run build` exit 0 (`wrote /var/www/design/dist/tokens.css`)
- `sail npm ci && sail npm run build` exit 0. It writes `public/build/manifest.json` and `bootstrap/ssr/ssr.js`
- SSR server restarted on the rebuilt bundle (`inertia:stop-ssr`, then `sail exec -d -u sail laravel.test php artisan inertia:start-ssr`; `/health` `OK`). It was left running as found
- `php artisan test --log-junit` exit 0: 68 tests, 554 assertions, 0 failures, 0 errors, 0 skipped
- `sail bin pint --test` exit 0
- `sail bin phpstan analyse --no-progress` exit 0, 0 errors
- C33: `sail artisan mandato:import ../site/tests/fixtures/out` exit 0, then the `curl` printed `0`. The import upserts the same fixture into the builder's dev database `laravel`: `contract_imports` went from 3 rows to 4, and no other table changed

Ranked gaps: none. Round 1's three gaps are closed: house check on all three tables (C14), `X-Inertia` JSON (C49) and `dir` fallback (C15). Each is backed by a killed, discriminating mutant.
