# app-skeleton checks

Profile: standard
Plan: `.specs/features/app-skeleton/plan.md`

49 checks in 5 slices · 12 one-way doors (plus door 13, discovered while writing these checks: the Sail mounts the tests need) · 0 open

All commands run from `app/` with Sail up (`./vendor/bin/sail up -d`); `sail` below is `./vendor/bin/sail`. Pest proofs are `sail artisan test --filter="<test name>"`. The page proofs read server-rendered HTML, so they need the client and SSR bundles built (`sail npm run build`) and the SSR server running (`sail artisan inertia:start-ssr`, or `sail exec -d laravel.test php artisan inertia:start-ssr`); a page test fails, never skips, when the SSR server is down. "The fixture" is `site/tests/fixtures/out` read in place (plan, Assumptions). `{APP_URL}` is the value the tests set, `https://mandato.test`.

## Checks

### S1 - the contract loads into PostgreSQL · ~14 files · ~60 KB · ~15k

**C1** - Importing the fixture exits 0 and leaves exactly 3 `members` (house `camara`, source ids `101`, `102`, `103`), 1 `legislatures` row (57), 3 `memberships` in legislature 57 whose participation, governmentAlignment and partyAlignment count and total and whose authored, first-signer and requirements counts equal `deputies.json` (101: 4 de 5, 2 de 3, 2 de 3, 5, 2, 3), 8 `roll_calls`, 7 `propositions` (5 authored, 2 decided by a roll call) and 6 `authorships` (AC 1)
Proof: `sail artisan test --filter="imports every record of the fixture"`

**C2** - After the fixture import there are 16 `votes`, and each one carries the `vote`, `party` and `partyMajority` of its entry in `deputies/{id}.json`, compared one by one (AC 1)
Proof: `sail artisan test --filter="stores every vote of the deputy files"`

**C3** - A successful fixture import prints the line `Imported schema_version 2 generated 2026-09-27T12:00:00Z: 3 members, 8 roll calls, 16 votes, 7 propositions` and records one `contract_imports` row with schema_version 2, generatedAt 2026-09-27T12:00:00Z, the SHA-256 of the fixture's `meta.json` and the counts 3, 8, 16, 7 (AC 2)
Proof: `sail artisan test --filter="reports and records the import"`

**C4** - A copy of the fixture whose `meta.json` has `schema_version` 3 makes the import exit 1, print `schema_version 3 is not supported; expected one of: 2` to stderr, and leave the row count of all 8 tables equal to what a previous fixture import left (AC 3)
Proof: `sail artisan test --filter="refuses an unsupported schema version"`

**C5** - Removing any one of the 5 file kinds (`meta.json`, `deputies.json`, `roll-calls.json`, `deputies/101.json`, `roll-calls/100-1.json`) from a copy of the fixture makes the import exit 1, print that file's path to stderr, and leave all 8 row counts unchanged (AC 4)
Proof: `sail artisan test --filter="refuses a contract with a missing file"`

**C6** - Breaking one file of each of the 5 kinds makes the import exit 1, print the path and the first failing pointer to stderr (`meta.json` `/generatedAt`, `deputies.json` `/0/uf`, `roll-calls.json` `/0/date`, `deputies/101.json` `/votes/0/rollCallId`, `roll-calls/100-1.json` `/tallies/yes`), and leave all 8 row counts unchanged (AC 4)
Proof: `sail artisan test --filter="refuses a file that fails its schema"`

**C7** - `mandato:import /nonexistent` exits 2 and prints `contract directory not found: /nonexistent` to stderr (AC 5)
Proof: `sail artisan test --filter="rejects a missing directory"`

**C8** - Importing the fixture twice leaves `members`, `memberships`, `roll_calls`, `votes`, `propositions` and `authorships` with the same row count and every column other than `created_at` and `updated_at` identical, ids included (AC 6, door 6)
Proof: `sail artisan test --filter="importing twice changes nothing"`

**C9** - Importing a copy of the fixture without roll call `200-3`, without deputy 103, without 102's vote on `100-4` and without 101's authorship of proposition 6005, after a full import, leaves no `roll_calls` row `200-3` and no vote on it, no membership and no vote of 103, no vote of 102 on `100-4`, no authorship of 101 on 6005, and keeps the `members` row of 103 and the `propositions` row 6005 (AC 7, door 6)
Proof: `sail artisan test --filter="sweeps what the snapshot no longer has"`

**C10** - With a trigger that raises on every insert or update of `authorships`, an import of a changed copy of the fixture (101 renamed, 101's vote on `100-1` changed) after a full import exits 1 and leaves all 8 tables equal, row by row, to their state before the run (AC 8, door 6)
Proof: `sail artisan test --filter="rolls back on a failed write"`

**C11** - While a second database connection holds the import advisory lock, the import exits 1, prints `another import is running` to stderr, and all 8 tables still hold 0 rows (AC 9, door 6)
Proof: `sail artisan test --filter="refuses while another import holds the lock"`

**C12** - `mandato:import <fixture> --dry-run` exits 0, prints `Would import schema_version 2 generated 2026-09-27T12:00:00Z: 3 members, 8 roll calls, 16 votes, 7 propositions`, and leaves 0 rows in all 8 tables; on the `schema_version` 3 copy it exits 1 and leaves 0 rows (AC 10)
Proof: `sail artisan test --filter="dry run validates and writes nothing"`

**C13** - No column of the 8 tables has a name containing `cpf`, `candidacy`, `office`, `ballot` or `situation`, and a dump of every row of the 8 tables after the fixture import contains none of `DEPUTADO FEDERAL`, `SENADOR`, `1313`, `APTO`, `consulta_cand_2026_BRASIL.csv` (AC 11)
Proof: `sail artisan test --filter="persists no candidacy field and no cpf"`

**C14** - Each of the 6 natural keys of door 4 (`members` house + source_id, `roll_calls` house + source_id, `propositions` house + source_id, `memberships` member + legislature, `votes` roll call + member, `authorships` membership + proposition) rejects a duplicate with a unique violation, each of `members`, `propositions` and `roll_calls` accepts house `senado` and rejects `presidencia`, `Camara` and the empty string with a check violation naming `{table}_house_check`, and every `source_id` column has type `text` (door 4)
Proof: `sail artisan test --filter="enforces the natural keys and text source ids"`
Proof: `sail artisan test --filter="rejects a house outside camara and senado"`

**C15** - `SUPPORTED_SCHEMA_VERSIONS` is `[2]`, version 2 resolves to its own reader and versions 1 and 3 resolve to none, `config('mandato.contract_dir')` defaults to `base_path('../data/out')`, and `mandato:import` with no `dir` argument reads that directory: pointed at the fixture it exits 0 with the line of C3, pointed at `/nonexistent-contract-dir` it exits 2 naming that path (door 5)
Proof: `sail artisan test --filter="resolves one reader per supported version"`
Proof: `sail artisan test --filter="defaults the contract directory"`
Proof: `sail artisan test --filter="imports from the configured directory when none is given"`

**C16** - `config('mandato.schema_dir')` defaults to `base_path('../etl/schema')`, and with it pointed at a copy of `etl/schema` whose `deputies.schema.json` requires `uf` to match `^ZZ$`, the fixture import exits 1 naming `deputies.json` and `/0/uf` (door 7)
Proof: `sail artisan test --filter="validates against the schema directory"`

**C17** - The test suite runs on the `pgsql` driver against a PostgreSQL server whose major version is 18 (door 3)
Proof: `sail artisan test --filter="runs on postgresql 18"`

### S2 - the deputy profile · ~8 files · ~40 KB · ~10k

**C18** - After the fixture import, `GET /deputados/101/` responds 200 with Inertia component `Deputies/Show`, and its HTML has exactly one `<h1>`, reading `Ana Souza`, the eyebrow `Deputado federal · PT · SP`, three `.ma-ndem` blocks reading `4 de 5`, `2 de 3` and `2 de 3`, and the authored, first-signer and requirements counts `5`, `2` and `3` (AC 12)
Proof: `sail artisan test --filter="profile shows the deputy and the indicators"`

**C19** - The profile of 101 renders 8 `.ma-score__col` links, in the order `/votacoes/100-1/`, `/votacoes/200-1/`, `/votacoes/200-2/`, `/votacoes/100-2/`, `/votacoes/100-3/`, `/votacoes/200-3/`, `/votacoes/100-4/`, `/votacoes/100-6/`, and a table of 8 rows in the same order (AC 13)
Proof: `sail artisan test --filter="profile lists the votes oldest first"`

**C20** - The head of `/deputados/101/` holds exactly one `<title>`, reading `Ana Souza (PT-SP) na 57ª legislatura - Mandato Aberto`, and the other 9 tags of AC 14 with their values: `description` and `og:description` `Votos, participação em votações nominais e proposições de Ana Souza (PT-SP) na Câmara dos Deputados, com dados oficiais e a base de cada número.`, `og:title` `Ana Souza (PT-SP) na 57ª legislatura`, `canonical` and `og:url` `{APP_URL}/deputados/101/`, `og:type` `website`, `og:site_name` `Mandato Aberto`, `og:locale` `pt_BR`, `twitter:card` `summary` (AC 14, door 8)
Proof: `sail artisan test --filter="profile head carries the share tags"`

**C21** - The profile of 103 renders `Sem base de cálculo no período` in the participation and party-alignment blocks with no digit outside their note marker, and `0 de 1` in the government-alignment block (AC 15)
Proof: `sail artisan test --filter="indicator without base shows no number"`

**C22** - The profile of 101 renders the initials frame `.ma-photo__initials` reading `AS` and contains no `<img>` element (AC 16)
Proof: `sail artisan test --filter="profile draws the initials frame and no remote image"`

**C23** - `GET /deputados/999999/`, `GET /deputados/abc/` and `GET /deputados/555/` for a member with house `senado` and source id `555` each respond 404 with `<html lang="pt-BR">` and the only `<h1>` reading `Página não encontrada` (AC 17)
Proof: `sail artisan test --filter="profile 404s"`

### S3 - the roll-call page · ~6 files · ~30 KB · ~8k

**C24** - After the fixture import, `GET /votacoes/100-1/` responds 200 with Inertia component `RollCalls/Show`, `<h1>` `PL 1/2023`, result `Aprovada` and a `TallyBar` writing `2`, `1` and `0`; `/votacoes/100-4/` has `<h1>` `Votação nominal de 01/06/2025`; `/votacoes/100-2/` reads `Rejeitada`; `/votacoes/100-3/` reads `Resultado não informado` (AC 18)
Proof: `sail artisan test --filter="roll call shows heading result and tally"`

**C25** - Grouping votes `Zeta`, ``, `Artigo 17`, `Beta`, `Obstrução`, `Abstenção`, `Não`, `Sim` yields groups in the order `Sim`, `Não`, `Abstenção`, `Obstrução`, `Artigo 17`, ``, `Beta`, `Zeta`, and names `Zuleica`, `Érico`, `Abel`, `Ágata`, `Edu` inside one group come out `Abel`, `Ágata`, `Edu`, `Érico`, `Zuleica` (AC 19)
Proof: `sail artisan test --filter="orders vote groups and names"`

**C26** - An open roll call with one vote of each of those 8 values renders 8 `.ma-group` sections in that order, and every row holds an `svg.ma-vote` and a link to `/deputados/{deputyId}/` (AC 19)
Proof: `sail artisan test --filter="roll call groups every deputy by vote"`

**C27** - `/votacoes/100-6/` (secret) renders exactly one `.ma-group`, labelled `Deputados que votaram`, listing `Ana Souza`, `Bruno Lima` and `Carla Dias` (AC 20)
Proof: `sail artisan test --filter="secret roll call lists who voted"`

**C28** - A roll call with no vote record renders `Nenhum voto individual registrado nesta votação` and no `.ma-group` (AC 21)
Proof: `sail artisan test --filter="roll call without votes says so"`

**C29** - The head of `/votacoes/100-1/` holds title `PL 1/2023: como cada deputado votou - Mandato Aberto`, `og:title` `PL 1/2023: como cada deputado votou`, description and `og:description` `Votação nominal de 01/03/2023 (Plenário) na Câmara dos Deputados, com o voto de cada deputado e o registro oficial.`, `canonical` and `og:url` `{APP_URL}/votacoes/100-1/` and the 4 fixed tags of C20; `/votacoes/100-6/` holds title `Votação nominal de 01/08/2025: votação secreta - Mandato Aberto` and description `Votação secreta de 01/08/2025 (Plenário) na Câmara dos Deputados, com os totais oficiais e os deputados que votaram.`; `/votacoes/200-1/` writes the organ as `(CCJC)` (AC 22)
Proof: `sail artisan test --filter="roll call head carries the share tags"`

**C30** - `GET /votacoes/999-9/` and `GET /votacoes/abc/` respond 404 with the page of C23 (AC 23)
Proof: `sail artisan test --filter="roll call 404s"`

### S4 - shared links get a complete page · ~6 files · ~20 KB · ~5k

**C31** - With the SSR server running, the initial HTML of `/deputados/101/` holds inside the `#app` element the `<h1>` `Ana Souza` and the numbers `4`, `5`, `2`, `3`, `5`, `2`, `3` of C18, and that of `/votacoes/100-1/` holds inside `#app` the `<h1>` `PL 1/2023` and the tally `2`, `1`, `0` (AC 24)
Proof: `sail artisan test --filter="server renders the page body"`

**C32** - With `inertia.ssr.url` pointed at a closed port, `/deputados/101/` and `/votacoes/100-1/` respond 200 with every tag of C20 and C29 respectively and an empty `#app` element (AC 25, door 8)
Proof: `sail artisan test --filter="head tags survive an ssr outage"`

**C33** - None of `/deputados/101/` (200), `/votacoes/100-1/` (200), `/deputados/999999/` (404), `/votacoes/999-9/` (404) and `/deputados/abc/` (404) sends a `Set-Cookie` header (AC 26, door 9)
Proof: `sail artisan test --filter="public pages set no cookie"`
Proof: `curl -sI http://localhost:${APP_PORT:-80}/deputados/101/ | grep -ci '^set-cookie'` prints `0` after `sail artisan mandato:import ../site/tests/fixtures/out`

**C34** - Both pages' footer reads `Dados abertos da Câmara dos Deputados, coletados em 27/09/2026` after the fixture import (generatedAt `2026-09-27T12:00:00Z`), and `... coletados em 30/09/2026` once a later `contract_imports` row with generatedAt `2026-10-01T02:30:00Z` exists (AC 27)
Proof: `sail artisan test --filter="footer carries the brasília collection day"`

**C35** - `GET /deputados/101` and `GET /votacoes/100-1` (no trailing slash) respond 200 with canonical `{APP_URL}/deputados/101/` and `{APP_URL}/votacoes/100-1/` (AC 28, door 10)
Proof: `sail artisan test --filter="slashless paths answer with the slash canonical"`

**C36** - No file under `resources/js` imports `Head` from `@inertiajs/vue3`, and `resources/js/app.js` sets `document.title` from `meta.title` on every `navigate` event (door 8)
Proof: `sail artisan test --filter="pages leave the share tags to blade"`

**C37** - Routes `deputies.show` and `roll-calls.show` carry middleware `public` and not `web`, and the `public` group is exactly `SubstituteBindings`, `HandleInertiaRequests` (door 9)
Proof: `sail artisan test --filter="public routes use the cookie-free group"`

**C38** - `deputies.show` only matches digits and `roll-calls.show` only `digits-digits`, and `public/.htaccess` holds no trailing-slash redirect rule (door 10)
Proof: `sail artisan test --filter="keeps the mvp url shapes"`

**C49** - With `X-Inertia: true` and the `X-Inertia-Version` the app computes, `GET /deputados/101/` and `GET /votacoes/100-1/` respond 200 with `X-Inertia: true`, a JSON content type, `component` `Deputies/Show` and `RollCalls/Show` respectively, and a non-empty `props.meta` array holding a non-empty `title` (Surface)
Proof: `sail artisan test --filter="inertia visits answer with json"`

### S5 - quality gates · ~8 files · ~15 KB · ~4k

**C39** - `.github/workflows/ci.yml` job `app` has a `postgres:18` service, sets up PHP 8.5 with `pdo_pgsql` and `intl` and Node 24, runs `npm ci` and `npm run build` in `design/`, `composer install`, `pint --test`, `phpstan analyse`, `npm ci` and `npm run build` in `app/`, starts `inertia:start-ssr` and runs `php artisan test`, and no step sets `continue-on-error`; `phpstan.neon` sets level 6 (AC 29, door 12)
Proof: `sail artisan test --filter="ci job app runs every gate"`

**C40** - Pint finds nothing to fix (AC 29)
Proof: `sail bin pint --test`

**C41** - Larastan at level 6 reports no error (AC 29)
Proof: `sail bin phpstan analyse --no-progress`

**C42** - The client and SSR build exits 0 and writes `public/build/manifest.json` and `bootstrap/ssr/ssr.js` (AC 29)
Proof: `sail npm run build && test -f public/build/manifest.json && test -f bootstrap/ssr/ssr.js`

**C43** - No file under `app/`, `resources/` or `lang/` contains a forbidden term as a whole word, and the matcher flags `Faltou` and `Intenção  de voto` in a scratch directory while passing `Aprovada` and `faltoso` (AC 30)
Proof: `sail artisan test --filter="no forbidden term in app copy"`
Proof: `sail artisan test --filter="forbidden matcher is whole word and case-insensitive"`

**C44** - `config('forbidden-terms')` contains every one of the 19 terms parsed from `site/src/lib/forbidden-terms.ts` (AC 30)
Proof: `sail artisan test --filter="forbidden list matches the mvp list"`

**C45** - `app/README.md` contains `./vendor/bin/sail up -d`, `sail artisan mandato:import`, `inertia:start-ssr` and `sail artisan test` (AC 31)
Proof: `sail artisan test --filter="readme names the commands"`

**C46** - `composer.json` and `package.json` declare the literal constraints of door 1 (`php` `^8.4`, `laravel/framework` `^13.34`, `inertiajs/inertia-laravel` `^3.5`, `pestphp/pest` `^5.3`, `pestphp/pest-plugin-laravel` `^5.0`, `larastan/larastan` `^3.12`, `laravel/pint` `^1.32`, `laravel/sail` `^1.68`, `opis/json-schema` `^2.6`, `vue` `^3.5.43`, `@inertiajs/vue3` `^3.8`, `@inertiajs/vite` `^3.8`, `vite` `^8.3`, `laravel-vite-plugin` `^3.2`, `@vitejs/plugin-vue` `^6.0.9`) (door 1, door 7)
Proof: `sail artisan test --filter="declares the door 1 versions"`

**C47** - `package.json` depends on `"mandato-design": "file:../design"`, `vite.config.js` lists `mandato-design` in `ssr.noExternal`, `design/package.json` exports `"./styles/*": "./styles/*"`, and both page components import from `mandato-design/components/` (door 2)
Proof: `sail artisan test --filter="consumes the design package"`

**C48** - `compose.yaml` builds `laravel.test` from Sail's `runtimes/8.5`, runs `pgsql` on `postgres:18-alpine`, and mounts `../design:/var/www/design`, `../etl:/var/www/etl:ro`, `../data:/var/www/data:ro`, `../site:/var/www/site:ro` and `../.github:/var/www/.github:ro` (door 11, door 13)
Proof: `sail artisan test --filter="sail runs php 8.5 with the sibling mounts"`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| `GET /deputados/{id}/` statuses (2) | 200 C18 · 404 C23 | - |
| Surface outputs of both routes (3 each) | HTML C18, C24 · `meta` prop C20, C29, C49 · `X-Inertia` JSON C49 | - |
| `GET /votacoes/{id}/` statuses (2) | 200 C24 · 404 C30 | - |
| `mandato:import` exit codes (3) | 0 C1 · 1 C4 · 2 C7 | - |
| import refusal causes (6) | unsupported version C4 · missing file C5 · schema failure C6 · missing directory C7 · failed write C10 · lock held C11 | - |
| contract file kinds, missing (5) | C5, table-driven over all 5 | - |
| contract file kinds, invalid (5) | C6, table-driven over all 5 | - |
| entities of Relations (9) | `Member` C1 · `Membership` C1 · `Legislature` C1 · `RollCall` C1 · `Proposition` C1 · `Authorship` C1 · `Vote` C2 · `ContractImport` C3 · `House` C14 | - |
| swept on a smaller snapshot (4) | roll call C9 · vote C9 · membership C9 · authorship C9 | - |
| never swept (2) | member C9 · proposition C9 | - |
| one-way constraints of door 4 (10) | `members` key C14 · `roll_calls` key C14 · `propositions` key C14 · `memberships` key C14 · `votes` key C14 · `authorships` key C14 · house check on `members` C14 · house check on `propositions` C14 · house check on `roll_calls` C14 · `text` source ids C14 | - |
| import write path (3) | real run C1 · dry run C12 · failed run C10 | - |
| profile figures (6) | participation C18 · governmentAlignment C18 · partyAlignment C18 · authored C18 · first signer C18 · requirements C18 | - |
| indicator base (2) | total > 0 C18 · total 0 C21 | - |
| profile 404 causes (3) | unknown digits C23 · not digits C23 · `senado` member C23 | - |
| roll-call 404 causes (2) | unknown `digits-digits` C30 · malformed C30 | - |
| roll-call heading (2) | proposition C24 · none C24 | - |
| result label (3) | `true` C24 · `false` C24 · `null` C24 | - |
| vote group order (8) | C25, table-driven over all 8 positions · boundary C26 | - |
| roll-call ballots (3) | open with records C26 · secret C27 · no record C28 | - |
| head tags (10) | C20, table-driven over all 10 · C29 · C32 | - |
| roll-call meta variants (3) | open C29 · secret C29 · organ other than `PLEN` C29 | - |
| SSR states (2) | running C31 · unreachable C32 | - |
| cookie-free responses (5) | profile 200 C33 · roll call 200 C33 · profile 404 C33 · roll call 404 C33 · unmatched 404 C33 | - |
| footer Brasília day (2) | same day as UTC C34 · day before UTC C34 | - |
| trailing slash (4) | profile with slash C18 · profile without C35 · roll call with slash C24 · roll call without C35 | - |
| CI gates of AC 29 (5) | pint C39, C40 · phpstan C39, C41 · client build C39, C42 · SSR build C39, C42 · Pest C39 | - |
| forbidden-term roots (3) | `app/` C43 · `resources/` C43 · `lang/` C43 | - |
| forbidden terms (19) | C44, table-driven over all 19 | - |
| README commands (4) | Sail C45 · import C45 · SSR C45 · tests C45 | - |
| `mandato:import` `dir` argument (2) | given C1-C12 · omitted, falls back to `contract_dir` C15 | - |
| Landing doors (13) | 1 C46 · 2 C47 · 3 C17 · 4 C14 · 5 C15 · 6 C8, C9, C10, C11 · 7 C16 · 8 C20, C32, C36 · 9 C33, C37 · 10 C35, C38 · 11 C48 · 12 C39 · 13 C48 | - |
| startup config: `public` group (1 shared assembly) | `bootstrap/app.php`, read by the HTTP kernel and the test harness alike, C37 | - |
| startup config: SSR URL (1 shared assembly) | `config/inertia.php`, read by the app and the tests alike, C31, C32 | - |

- Claims naming a status code, route or response shape: C18, C20, C23, C24, C29, C30, C31, C32, C33, C35, C49 - each proof crosses the HTTP boundary through Laravel's test client
- Claims naming an exit code or a printed line: C3, C4, C5, C6, C7, C11, C12, C15 - each proof runs the Artisan command and reads its exit code and output
- No other check claims more than the single case its proof exercises

## Test policy

The repo's guidelines say where tests live and that behaviour ships with a test (AGENTS.md); nothing says which level proves which code in `app/`, which did not exist before this feature. These rows are the bar this build runs under.

| Code | Required proofs | Coverage expectation |
| --- | --- | --- |
| Decides, reached across a boundary | one at the boundary **and** one at its own layer | the contract at the boundary; one asserted case per row of the decision table at its own layer |
| Decides, not reached across a boundary | one at its own layer | one asserted case per row of the decision table |
| Entry point that decides nothing | one at the boundary | accepted input, each rejected input, each error path |
| Instrumentation, pass-throughs | none of its own | covered by its consumer's proof |

Evidence (shapes planned by the doors; the build may name files differently):

- import command: dispatches over 6 refusal causes into 3 exit codes -> decides, reached across the Artisan boundary; proven per cause at the boundary (C4-C7, C10-C12), since the command is the layer that decides the code
- version seam (door 5): maps supported versions to readers -> decides; proven at its own layer (C15) and at the boundary (C4)
- importer (door 6): upsert, sweep per 4 kinds, lock, transaction -> decides; its own layer is the database, so its proofs (C8-C11) run against PostgreSQL through the command
- vote grouping (AC 19): an ordering rule over 8 positions plus a collation -> decides, reached across HTTP; own layer C25, boundary C26
- roll-call presentation: heading (2 rows), result label (3 rows), meta variant (2 rows plus organ) -> decides, reached across HTTP; every row asserted at the boundary (C24, C29), where the rows are cheap to reach with the fixture
- controllers: read rows and hand props to Inertia -> instrumentation; covered by the page checks
- closest analogue in the repo: `design/scripts/contract.mjs` grouping and `site/src/lib/format.ts` `groupVotes`, both proven at their own layer with one case per value

Cost: 1 proof at its own layer (C25) beyond the boundary proofs. Without these rows, the group ordering would be proven only by the fixture, which holds 5 of its 8 positions.

## Swept

- validation: C5, C6, C7, C23, C30
- failure modes: C10, C32
- idempotency: C8
- authorization: n/a - public read-only pages with no account; the import runs only from the shell (door 5) and never over HTTP; C33 keeps the pages cookie-free
- concurrency: C11
- data lifecycle: C9, C13
- dependency failure: C32, C16
- state transitions: n/a - no entity carries a status; an import replaces one scope's snapshot, proven by C9
- observability: C3, C4, C6

## Handoff

- Size from the plan's doors and the files each slice reads (`wc -c`: `etl/schema` 18 KB, the fixture 14 KB, `design/components` + `screens` 25 KB, `site` format and pages 12 KB) plus the files it writes: S1 = 15k, S2 = 10k (25k), S3 = 8k (33k), S4 = 5k (38k), S5 = 4k (42k), plus the Laravel scaffold read once (~10k) = ~52k, under the 150k budget - one builder
- Mechanism: one builder (the estimate fits; no ask)
- **Boundary:** C1-C48 closed at `edb2511` (scaffold `e8b5952`, import `0ae8353`, design export `1b3cbfc`, pages `a36c27d`, gates `edb2511`); every named proof run at that commit inside Sail with the SSR server up: 62 Pest tests green, `pint --test` and `phpstan analyse` (level 6) clean, `npm run build` writes both bundles, the C33 `curl` prints `0`
- **Settled mid-build:** nothing asked or answered; door 13 (Sail mounts `../site` and `../.github` for the tests) was found while writing these checks and landed in `plan.md` in the checks commit, before any code
- **Abandoned:** loading the ETL schemas into opis as they are - their `$id` is relative and opis requires an absolute root id, so the in-memory copy is anchored at its file URI, the files themselves untouched; reading the `public` group from the router in C37's test - the router only receives groups when the HTTP kernel handles a request, so the test reads them from the kernel `bootstrap/app.php` configures; a second request in one test reusing Inertia's scoped `SsrState` (it answered with the first page's body) - `tests/TestCase.php` forgets scoped instances before each request, as a fresh production request does
- **Proof gaps of verification round 1:** added `rejects a house outside camara and senado` (C14, table-driven over `members`, `propositions`, `roll_calls`; the natural-key test lost its house half and is now `enforces the natural keys and text source ids`), `imports from the configured directory when none is given` (C15) and `inertia visits answer with json` (new C49, Surface); each failed when its behaviour was broken (check dropped from `propositions` and from `roll_calls`; `dir` fallback pointed elsewhere; `meta` prop renamed; component renamed) and passed once restored; production code untouched
