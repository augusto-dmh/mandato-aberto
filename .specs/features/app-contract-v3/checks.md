# app-contract-v3 checks

Profile: ui
Plan: `.specs/features/app-contract-v3/plan.md`

72 checks in 8 slices · 6 one-way doors (door 6 discovered while deriving these checks, from resolved open question 2) · 1 open, blocking go-live only (plan open question 1)

All commands run from `app/` with this worktree's Sail project up: `app/.env` sets `COMPOSE_PROJECT_NAME=mandato-acv3`, `APP_PORT=8091`, `FORWARD_DB_PORT=54341`, `VITE_PORT=5181`; `sail` is `./vendor/bin/sail`. Pest proofs are `sail artisan test --filter="<test name>"`; page proofs read server-rendered HTML, so they need `sail npm run build` and `sail exec -d -u sail laravel.test php artisan inertia:start-ssr`, and a page test fails, never skips, when SSR is down (skeleton `requireSsr`). Design proofs are `sail npm --prefix /var/www/design test -- <file> -t "<name>"`. `{APP_URL}` is the value `phpunit.xml` sets, `https://mandato.test`.

"The Câmara fixture" is `app/tests/fixtures/v3/camara/`, "the Senate fixture" `app/tests/fixtures/v3/senado/`, "the parent" `app/tests/fixtures/v3/` (plan, Assumptions: test data). Their content, which every value below refers to:

- Câmara: `generatedAt` `2027-03-02T02:30:00Z`, legislatures 57 (`2023-02-01`..`2027-01-31`) and 58 (`2027-02-01`..`2031-01-31`), `classification.version` 1, the 11 rules of `etl/src/mandato_etl/rules/camara.json` in file order. Members 101 Ana Souza (57 PSB-SP with two exercise periods, 58 PT-SP), 102 Bruno Lima (57 PL-RJ), 103 Carla Dias (57 MDB-MG): 4 mandates. Ana's 57th: participation merit 3/4 all 7/10, government merit 2/3 all 4/6, party merit 1/2 all 5/8, `symbolicMerit` 2, authored 2, first signer 1, requirements 1; her 58th: participation 1/1 and 1/1, government 0/0 and 0/0, party 1/1 and 1/1, `symbolicMerit` 0. Bruno: every merit total 0, participation all 3/5, `symbolicMerit` 1. Roll calls (8): `100-1` 2023-03-01 PLEN nominal final `camara.09` on PL 1/2023 (101 `Sim`, 102 `Não`, 103 `Artigo 17`); `200-1` 2023-04-01 CCJC nominal procedural `camara.04` (101 `Sim`); `100-2` 2023-05-10 PLEN nominal amendment `camara.08`, no proposition, rejected (101 `Abstenção`, 102 `Obstrução`, 103 empty -> `notVoting`); `100-3` 2024-03-01 PLEN nominal procedural `camara.04` on PL 1/2023, result null (101 `Não`, 102 `Sim`); `100-4` 2024-08-01 PLEN symbolic final `camara.09` on PEC 3/2024, tallies null, no roll-call file; `100-5` 2025-08-01 PLEN secret final `camara.11`, tallies null, no vote; `100-6` 2025-09-01 PLEN secret unclassified, tallies 2/1/0 (101, 102, 103 empty -> `secret`); `300-1` 2027-02-15 PLEN nominal final `camara.09`, legislature 58 (101 `Sim`). 13 votes. Propositions (5): 5001 PL 1/2023 presented 2023-02-10 (authors 101 first signer, 102), 5003 PEC 3/2024 presented 2024-02-01 (103 first signer), 5005 REQ 5/2023 presented 2023-01-15 (101), 5006 PL 6/2027 presented 2027-02-10 (101 first signer, 102), 5007 INC 7/2024 presented null (102). One full text, `full-texts/5001.json`. Coverage: 57 through 2025-09-01, 4 nominal, 2 secret, 1 symbolic, 1 unclassified; 58 through 2027-02-15, 1, 0, 0, 0.
- Senate: `generatedAt` `2027-03-05T12:00:00Z`, legislature 57 only, `classification.version` 2, rules `senado.01` (final) and `senado.02` (procedural). Members, each with a 57th mandate only and `symbolicMerit` null: 9101 Rosa Andrade (PT-SP), 9102 Sérgio Prado (PL-RJ), 9103 Teresa Lins (MDB-BA), 9104 Ubiratan Costa (PSD-AM), 9105 Vera Dantas (PP-GO), 9106 Wagner Reis (PSB-PE). Roll calls (2): `6923` 2025-04-01 PLEN nominal unclassified on PL 1/2025, tallies 1/1/0 (9101 `Sim`, 9102 `Não`, 9103 `P-NRV`, 9104 `Presidente (art. 51 RISF)`, 9105 `Licença`, 9106 `NCom`); `7001` 2025-06-10 PLEN secret final `senado.01`, no proposition, tallies 40/20/1 (9101 and 9102 `Votou` -> `secret`, 9103 `Licença`, 9104 `AP`, 9105 `MIS`, 9106 `NA`). 12 votes. Proposition 160000 PL 1/2025 presented 2025-01-10 (9101 first signer). No full text. Coverage: 57 through 2025-06-10, 1 nominal, 1 secret, symbolic null, 1 unclassified.

Skeleton proofs this feature supersedes (plan Impact: skeleton AC 1, 2, 3, 12, 14, 18 to 22, 27, and door 5's reader): skeleton C1-C4, C12, C15, C18-C22, C24-C29, C34; their tests are replaced by the checks below that prove the superseding criteria. Skeleton proofs that still hold are re-pointed at the v3 fixtures with their assertions unchanged, except where an approved door here changes the asserted value: C5, C6 (now C9, C10), C7 (C20), C8 (C14), C9 (C15), C10 (C21), C11 (C22), C13 (C23, which excepts exactly door 3's `roll_calls.ballot` from the `ballot` column-name rule), C14 (C29, row shapes updated to door 3's columns), C16 (C24), C17 (C30), C23 (C40), C30 (C55), C33 (C67), C35 (C70), C36-C38 (C71, C72), C39-C46, C48 (unchanged tests), C47 (C72, page names `Members/Show`, `RollCalls/Show`, `Methodology/Show`).

## Checks

### S1 - each house directory loads on its own · ~12 files · ~95 KB · ~24k

**C1** - Importing the Câmara fixture exits 0 and leaves 3 `members` of house `camara` (101, 102, 103) with name, party, uf, `photo_url` and `source_url` of `members.json`, and 4 `memberships`: (101, 57) party PSB uf SP, (101, 58) PT SP, (102, 57) PL RJ, (103, 57) MDB MG, each with the 12 `{participation,government_alignment,party_alignment}_{all,merit}_{count,total}` values, `symbolic_merit`, `authored_count`, `first_signer_count` and `requirements_count` of its mandate, compared field by field against `members.json` (AC 1)
Proof: `sail artisan test --filter="imports the members and mandates of a house"`

**C2** - After the Câmara import, `exercise_periods` holds 5 rows: (101, 57) `2023-02-01 00:00:00`..`2024-06-01 00:00:00` and `2024-09-01 00:00:00`..`2027-02-01 00:00:00`, (101, 58) `2027-02-01 00:00:00`..`2027-03-01 09:00:00`, (102, 57) and (103, 57) `2023-02-01 00:00:00`..`2027-02-01 00:00:00` (AC 1)
Proof: `sail artisan test --filter="stores each exercise period of a mandate"`

**C3** - After the Câmara import, `roll_calls` holds 8 rows whose `legislature_number`, `date`, `organ`, `description`, `approved`, `ballot`, `kind`, `kind_rule`, tallies, `government_orientation` and `source_url` equal `roll-calls.json` field by field, with `opening_description` and `last_presentation_description` from `roll-calls/<id>.json` (`100-5` opening `Votação secreta em turno único.`, `100-1` last presentation `Apresentação do Projeto de Lei n. 1/2023`) and both null for symbolic `100-4` (AC 2)
Proof: `sail artisan test --filter="imports every roll call with ballot and kind"`

**C4** - After the Câmara import, `votes` holds 13 rows, each with the `official`, `position`, `party` and `party_majority` of its entry in `roll-calls/<id>.json`, compared one by one (`100-2`/103 official `` position `notVoting`; `100-6`/101 official `` position `secret`; `100-1`/103 `Artigo 17` `presiding`) (AC 2)
Proof: `sail artisan test --filter="imports every vote with official and position"`

**C5** - After the Câmara import, `propositions` holds 5 rows with `type`, `number`, `year`, `summary`, `presented_on`, `status` and `source_url` of `propositions.json` (5005 `REQ` 5 2023, 5007 `presented_on` null), `classification_rules` holds 11 rows of house `camara` whose `rule_id` in `position` order is `camara.01` .. `camara.11` with the file's `kind`, `field`, `pattern` and `description`, and `full_texts` holds 1 row for proposition 5001 with the file's `source_url`, `document_sha256`, `extractor`, `extracted_at` and `text` (AC 3)
Proof: `sail artisan test --filter="imports propositions rules and full texts"`

**C6** - After the Câmara import, `authorships` holds exactly 4 rows: (101, 57, 5001, first signer true), (102, 57, 5001, false), (103, 57, 5003, true), (101, 58, 5006, true); 5005 (presented 2023-01-15, before legislature 57), 5007 (presented null) and 102's authorship of 5006 (no 58th mandate) have none (AC 4)
Proof: `sail artisan test --filter="attaches authorship to the mandate of the presentation date"`

**C7** - After importing both fixtures, the 6 Senate memberships have `symbolic_merit` null and Câmara (102, 57) has 1, (101, 58) has 0; `100-4` and `100-5` have `tally_yes`, `tally_no` and `tally_others` null and `100-6` has 2, 1, 0 (AC 5)
Proof: `sail artisan test --filter="keeps absent counts null and never zero"`

**C8** - Importing the Câmara fixture prints exactly `Imported schema_version 3 camara generated 2027-03-02T02:30:00Z: 3 members, 4 mandates, 8 roll calls, 13 votes, 5 propositions, 1 full texts` and records one `contract_imports` row with house `camara`, `schema_version` 3, `generated_at` 2027-03-02T02:30:00Z, the SHA-256 of the fixture's `meta.json`, `classification_version` 1, `coverage` equal to `meta.json`'s `coverage` array, and the counts 3, 4, 8, 13, 5, 1; the Senate fixture prints `Imported schema_version 3 senado generated 2027-03-05T12:00:00Z: 6 members, 6 mandates, 2 roll calls, 12 votes, 1 propositions, 0 full texts` (AC 6)
Proof: `sail artisan test --filter="reports and records one import per house"`

**C9** - A copy of the Câmara fixture whose `meta.json` has `schema_version` 2, and one with 4, each make the import exit 1, print exactly `schema_version 2 is not supported; expected one of: 3` (resp. `4`) to stderr, and leave the row count of all 10 contract tables (`legislatures`, `members`, `memberships`, `exercise_periods`, `propositions`, `roll_calls`, `votes`, `authorships`, `classification_rules`, `full_texts`) plus `contract_imports` equal to a previous full import's (AC 7)
Proof: `sail artisan test --filter="refuses a schema version other than 3"`

**C10** - After a full import of both fixtures, removing each of `members.json`, `roll-calls.json`, `propositions.json`, `classification-rules.json` and `roll-calls/100-1.json` from a Câmara copy makes the import exit 1, print that file's path to stderr and leave every row of house `camara` unchanged; breaking one file of each of the 7 kinds makes it exit 1 and print the path and the pointer: `meta.json` `/generatedAt`, `members.json` `/0/mandates/0/participation/merit/total`, `roll-calls.json` `/0/ballot`, `propositions.json` `/0/presentedAt`, `classification-rules.json` `/0/kind`, `roll-calls/100-1.json` `/votes/0/position`, `full-texts/5001.json` `/documentSha256`, again changing no row (AC 8)
Proof: `sail artisan test --filter="refuses a house with a missing file"`
Proof: `sail artisan test --filter="refuses a house file that fails its v3 schema"`

**C11** - A Senate copy with one vote's `official` set to `LS`, `LP` or `LAP` (each in turn) makes the import exit 1, print `roll-calls/6923.json` and `/votes/0/official` to stderr, and leaves 0 `votes` whose `official` is any of the three (AD-018) (AC 9)
Proof: `sail artisan test --filter="refuses a raw senate leave code"`

**C12** - Each of 13 unresolved references in a fixture copy makes the import exit 1, print `<file>: <reason>: <id>` to stderr and change no row of that house: vote member 9999 (`roll-calls/6923.json`, `9999`), author member 9999 (`propositions.json`, `9999`), nominal roll call without its file (`roll-calls/100-1.json`, `100-1`), a file for symbolic `100-4` (`roll-calls/100-4.json`, `100-4`), a file for id `555-5` absent from the index (`roll-calls/555-5.json`, `555-5`), `kindRule` `camara.99` (`roll-calls.json`, `100-1`), house `senado` on member 101, roll call `100-1`, proposition 5001, rule `camara.01`, roll-call file `100-1` and full text 5001 (6 cases, each naming its file and id), roll-call legislature 59 (`roll-calls.json`, `100-1`), mandate legislature 59 (`members.json`, `101`), full text for proposition 9999 (`full-texts/9999.json`, `9999`); beyond AC 10, a roll call whose `propositionId` 4242 is absent from `propositions.json` (`roll-calls.json`, `100-1`) is refused the same way (AC 10)
Proof: `sail artisan test --filter="refuses a house whose references do not resolve"`

**C13** - After the Câmara import, a Senate copy whose `meta.legislatures` gives 57 as `2023-02-02`..`2027-01-31` exits 1, prints exactly `legislature 57 dates differ: stored 2023-02-01..2027-01-31, contract 2023-02-02..2027-01-31` to stderr and writes no row of house `senado`; the same copy with `--dry-run` exits 1 with the same line (AC 11, AC 20)
Proof: `sail artisan test --filter="refuses legislature dates that differ from the stored ones"`

**C14** - Importing the parent twice leaves `members`, `memberships`, `exercise_periods`, `roll_calls`, `votes`, `propositions`, `authorships`, `classification_rules` and `full_texts` with the same row count and every column other than `created_at` and `updated_at` identical, ids included (AC 12)
Proof: `sail artisan test --filter="importing a house twice changes nothing"`

**C15** - After a full Câmara import, a copy without roll call `100-3`, without 102's vote on `100-1`, without 103's mandate (103 stays in `members.json` with `mandates: []`), without Ana's second 57th exercise period and without 102's authorship of 5001 leaves no `roll_calls` row `100-3` nor its 2 votes, no vote of 102 on `100-1`, no membership, vote or exercise period of 103 in 57, 1 exercise period for (101, 57), no authorship (102, 5001), and keeps the `members` row of 103 and the `propositions` row 5001 (AC 13)
Proof: `sail artisan test --filter="sweeps each scope the house lists"`

**C16** - After a full Câmara import, a copy whose `meta.legislatures` and `coverage` hold only 57, with Ana's 58th mandate and roll call `300-1` (and its file) removed, exits 0 and leaves the (101, 58) membership, its exercise period, roll call `300-1`, its vote and the authorship (101, 58, 5006) identical to before (AC 14)
Proof: `sail artisan test --filter="leaves a legislature the house does not list"`

**C17** - After importing both fixtures, a Câmara copy whose rules are reversed with `camara.06` removed (and no roll call using it), whose `full-texts/5001.json` is removed and which adds `full-texts/5003.json` leaves `classification_rules` of house `camara` equal to the copy's 10 rules in the copy's order (`camara.11` first), `full_texts` holding only proposition 5003, and every row of house `senado` in `members`, `memberships`, `exercise_periods`, `roll_calls`, `votes`, `propositions`, `authorships` and `classification_rules` identical to before (AC 15)
Proof: `sail artisan test --filter="replaces a house's rules and full texts as a set"`

**C18** - `mandato:import <parent>` exits 0, prints the Câmara line of C8 and then the Senate line of C8, records 2 `contract_imports` rows (house `camara` with the lower id, then `senado`), and a parent holding only `camara/` imports only the Câmara (AC 16)
Proof: `sail artisan test --filter="imports camara then senado from a parent directory"`

**C19** - From an empty database, a parent copy whose Senate vote on `6923` names member 9999 exits 1, leaves the Câmara rows of C1-C6 and 1 `contract_imports` row (house `camara`), 0 rows of house `senado`, and prints `<parent>/senado` and `9999` to stderr; after both fixtures were imported, the same copy leaves every row of both houses identical; a parent copy whose Câmara `meta.json` has `schema_version` 2 still imports the Senate and exits 1 naming `<parent>/camara` (AC 17)
Proof: `sail artisan test --filter="keeps the other house when one house fails"`

**C20** - `mandato:import` on an empty temporary directory exits 1 and prints exactly `no contract found in <dir>`, and on `/nonexistent` exits 2 and prints `contract directory not found: /nonexistent` (AC 18, skeleton AC 5)
Proof: `sail artisan test --filter="finds no contract in a directory without houses"`
Proof: `sail artisan test --filter="rejects a missing directory"`

**C21** - With a trigger raising on every insert or update of `authorships`, a changed Câmara copy (101 renamed `Ana Souza Lima`, 101's vote on `100-1` changed to `Não`/`no`) after a full import of both fixtures exits 1 and leaves all 11 tables equal, row by row, to their state before the run (skeleton AC 8)
Proof: `sail artisan test --filter="rolls back a house on a failed write"`

**C22** - While a second connection holds advisory lock `57210057`, `mandato:import <parent>` exits 1, prints `another import is running` once to stderr, and all 11 tables hold 0 rows (skeleton AC 9)
Proof: `sail artisan test --filter="refuses while another import holds the lock"`

**C23** - After importing both fixtures, no column of the 11 tables other than `roll_calls.ballot` has a name containing `cpf`, `candidacy`, `office`, `ballot` or `situation`, and a dump of every row contains none of `DEPUTADO FEDERAL`, `SENADOR`, `1313`, `APTO`, `consulta_cand_2026_BRASIL.csv` (skeleton AC 11)
Proof: `sail artisan test --filter="persists no candidacy field and no cpf"`

**C24** - `config('mandato.schema_dir')` defaults to `base_path('../etl/schema')`, and with it pointed at a copy of `etl/schema` whose `v3/members.schema.json` requires `uf` to match `^ZZ$`, the Câmara import exits 1 naming `members.json` and `/0/uf` (door 1, skeleton door 7)
Proof: `sail artisan test --filter="validates against the v3 schema directory"`

**C25** - Every file of `etl/tests/fixtures/v3/senado/` passes its v3 schema through the importer's validator in place, and a temporary copy of it with members 9103, 9104 and 9105 added to `members.json` (the in-place directory names them only in votes) imports with exit 0, leaving 5 votes on `6923` whose `official` values are `Sim`, `Não`, `P-NRV`, `Presidente (art. 51 RISF)` and `Licença` (plan, Assumptions: test data)
Proof: `sail artisan test --filter="loads the etl's own senate fixture"`

### S2 - the import command and the reader seam · ~4 files · ~12 KB · ~3k

**C26** - `ContractReaders::SUPPORTED_SCHEMA_VERSIONS` is `[3]`, `ContractReaders::for(3)` is a `V3Reader`, `for(2)` and `for(4)` are null, and neither `App\Contract\V2Reader` nor `app/Contract/V2Reader.php` exists (door 1)
Proof: `sail artisan test --filter="resolves only the v3 reader"`

**C27** - `config('mandato.contract_dir')` defaults to `base_path('../data/v3')`, and `mandato:import` with no argument and that config pointed at the parent imports both houses with exit 0 (AC 19, door 2)
Proof: `sail artisan test --filter="defaults the contract directory to data v3"`

**C28** - `mandato:import <parent> --dry-run` exits 0, prints `Would import schema_version 3 camara generated 2027-03-02T02:30:00Z: 3 members, 4 mandates, 8 roll calls, 13 votes, 5 propositions, 1 full texts` and then `Would import schema_version 3 senado generated 2027-03-05T12:00:00Z: 6 members, 6 mandates, 2 roll calls, 12 votes, 1 propositions, 0 full texts`, and leaves 0 rows in all 11 tables; on a Câmara copy with `schema_version` 2 it exits 1 with 0 rows (AC 20)
Proof: `sail artisan test --filter="dry run checks each house and writes nothing"`

### S3 - the stored schema · ~3 files · ~20 KB · ~5k

**C29** - The skeleton's 6 natural keys reject a duplicate with a unique violation, `members.house` rejects `presidencia`, and every `source_id` column is `text`, with rows shaped by door 3's columns (skeleton door 4, unchanged by door 3)
Proof: `sail artisan test --filter="enforces the natural keys and the house check"`

**C30** - The test suite runs on `pgsql` against PostgreSQL 18 (skeleton door 3)
Proof: `sail artisan test --filter="runs on postgresql 18"`

**C31** - Each of 9 invalid writes fails with SQLSTATE `23514` (check violation): roll call `ballot` `open`, roll call `kind` `important`, tallies (1, null, null), `government_orientation` `liberado`, vote `position` `other`, vote `party_majority` `Sim`, rule `house` `presidencia`, rule `kind` `unclassified`, contract import `house` `presidencia` (AC 22, door 3)
Proof: `sail artisan test --filter="enforces the v3 enum and tally checks"`

**C32** - A second `classification_rules` row (`camara`, `camara.01`), a second `full_texts` row for one proposition and a second `exercise_periods` row with the same membership and `starts_at` each fail with SQLSTATE `23505`; a `contract_imports` row with `house` null and a `memberships` row with `party` null fail with `23502`; deleting a membership deletes its exercise periods and deleting a proposition deletes its full text; a roll call with `kind_rule` `camara.99` and no such rule is accepted (door 3)
Proof: `sail artisan test --filter="enforces the v3 keys cascades and nullability"`

**C33** - Rolling back the door 3 migration, inserting one row into each of the 8 skeleton tables in the skeleton's shape, and migrating again leaves 0 rows in all 8, and a Câmara import then exits 0 (AC 21)
Proof: `sail artisan test --filter="the v3 migration empties the v2 rows"`

### S4 - member pages for both houses · ~8 files · ~45 KB · ~11k

**C34** - After importing the parent, `GET /deputados/101/` responds 200 with component `Members/Show`, exactly one `<h1>` reading `Ana Souza`, and the eyebrow `Câmara dos Deputados · 58ª legislatura · PT · SP`; `GET /deputados/101/legislatura/57/` responds 200 with eyebrow `Câmara dos Deputados · 57ª legislatura · PSB · SP` and `/deputados/101/legislatura/58/` with the 58th's eyebrow (AC 23, AC 25)
Proof: `sail artisan test --filter="deputy page shows the latest mandate and each legislature"`

**C35** - `GET /senadores/9101/` and `/senadores/9101/legislatura/57/` respond 200 with component `Members/Show`, one `<h1>` `Rosa Andrade` and eyebrow `Senado Federal · 57ª legislatura · PT · SP` (AC 24, AC 25)
Proof: `sail artisan test --filter="senator page shows the mandate"`

**C36** - `/deputados/101/` renders `nav[aria-label="Legislaturas"]` with 2 links in order `58ª legislatura (2027–2031)` -> `/deputados/101/legislatura/58/` carrying `aria-current="page"` and `57ª legislatura (2023–2027)` -> `/deputados/101/legislatura/57/` without it; on `/deputados/101/legislatura/57/` the current mark is on the 57th link (AC 26)
Proof: `sail artisan test --filter="member with two mandates lists the legislatures"`

**C37** - `/deputados/102/` and `/senadores/9101/` render the text `57ª legislatura (2023–2027)` and no `nav[aria-label="Legislaturas"]` (AC 27)
Proof: `sail artisan test --filter="member with one mandate names its legislature"`

**C38** - The head of `/deputados/101/legislatura/57/` holds exactly title `Ana Souza (PSB-SP) na 57ª legislatura - Mandato Aberto`, `og:title` `Ana Souza (PSB-SP) na 57ª legislatura`, `description` and `og:description` `Votos, participação em votações nominais e proposições de Ana Souza (PSB-SP) na Câmara dos Deputados, com dados oficiais e a base de cada número.`, and the 4 fixed tags of skeleton C20; `/senadores/9101/` holds title `Rosa Andrade (PT-SP) na 57ª legislatura - Mandato Aberto` and description `Votos, participação em votações nominais e proposições de Rosa Andrade (PT-SP) no Senado Federal, com dados oficiais e a base de cada número.` (AC 28)
Proof: `sail artisan test --filter="member head carries the share tags"`

**C39** - `canonical` and `og:url` are `{APP_URL}/deputados/101/` on both `/deputados/101/` and `/deputados/101/legislatura/58/`, `{APP_URL}/deputados/101/legislatura/57/` on the 57th, and `{APP_URL}/senadores/9101/` on both `/senadores/9101/` and `/senadores/9101/legislatura/57/` (AC 29, door 4)
Proof: `sail artisan test --filter="member canonical is the bare path for the latest mandate"`

**C40** - Each of 9 paths responds 404 with `<html lang="pt-BR">` and the only `<h1>` `Página não encontrada`: `/deputados/abc/`, `/deputados/999999/`, `/deputados/9101/`, `/deputados/102/legislatura/58/`, `/deputados/101/legislatura/56/`, `/deputados/101/legislatura/abc/`, `/senadores/101/`, `/senadores/9101/legislatura/58/`, `/senadores/abc/` (AC 30)
Proof: `sail artisan test --filter="member pages 404"`

**C41** - `/deputados/101/legislatura/57/` renders 4 `.ma-score__col` links in order `/votacoes/100-1/`, `/votacoes/100-2/`, `/votacoes/100-3/`, `/votacoes/100-6/` (committee `200-1`, symbolic `100-4` and vote-less `100-5` absent), with table labels `Sim`, `Abstenção`, `Não`, `Votação secreta`; `/deputados/101/` renders 1 column, `/votacoes/300-1/`; `/senadores/9101/` renders `/senado/votacoes/6923/` then `/senado/votacoes/7001/` labelled `Sim`, `Votou (votação secreta)`; `/senadores/9104/` labels `6923` `Presidente da sessão (art. 51 RISF)` and contains no `Art. 17` (AC 31, door 5)
Proof: `sail artisan test --filter="partitura draws each plenary vote by position"`

**C42** - `/senadores/9103/` labels both of its columns `Não registrou voto`, and neither its HTML nor its Inertia props contain `P-NRV`, `Presente, não registrou voto`, `Licença` or `Registro do Senado` (resolved open question 2, door 6)
Proof: `sail artisan test --filter="senator page says only that no vote was recorded"`

**C43** - The footer of `/deputados/101/` reads `Dados abertos da Câmara dos Deputados, coletados em 01/03/2027` (generatedAt `2027-03-02T02:30:00Z`, the Brasília day before) and that of `/senadores/9101/` `Dados abertos do Senado Federal, coletados em 05/03/2027`; after a later Câmara `contract_imports` row with generatedAt `2027-04-10T15:00:00Z`, the deputy footer reads `10/04/2027` and the senator's still `05/03/2027` (AC 32)
Proof: `sail artisan test --filter="member footer carries its house's collection day"`

**C44** - `/deputados/101/` and `/senadores/9101/` render `.ma-photo__initials` (`AS`, `RA`), contain 0 `<img>` elements, and do not contain the members' `photoUrl` (AC 33)
Proof: `sail artisan test --filter="member page draws initials and no remote image"`

### S5 - both bases, described plainly · ~3 files · ~20 KB · ~5k

**C45** - `/deputados/101/legislatura/57/` renders 3 indicator groups headed `Participação em votações nominais do plenário`, `Votos iguais à orientação do governo`, `Votos iguais à maioria do próprio partido`, each with 2 `.ma-ndem` labelled `nas votações sobre propostas e emendas` then `em todas as votações nominais do plenário`, the six values in order `3 de 4`, `7 de 10`, `2 de 3`, `4 de 6`, `1 de 2`, `5 de 8`, and each group's two method links `{APP_URL}/metodologia/#participacao`, `#alinhamento-governo`, `#alinhamento-partido` (AC 34)
Proof: `sail artisan test --filter="indicators show the merit base then all votes"`

**C46** - Every member page renders once, before the first `.ma-ndem`, the AC 35 paragraph verbatim, its `regras publicadas` an `<a href="/metodologia/#classificacao">` (AC 35)
Proof: `sail artisan test --filter="member page explains the two bases once"`

**C47** - `/deputados/102/` renders `Sem base de cálculo no período` and no digit in each of its 3 merit `.ma-ndem` values and `3 de 5` in its participation `all`; `/deputados/101/` renders it in both government blocks (AC 36)
Proof: `sail artisan test --filter="a basis without total shows no number"`

**C48** - `/deputados/101/legislatura/57/` renders `Durante o exercício nesta legislatura, o plenário também decidiu 2 votações simbólicas sobre propostas e emendas. Votação simbólica não registra o voto de cada parlamentar.`, `/deputados/102/` the same with `1 votação simbólica`, `/deputados/101/` `Nenhuma votação simbólica sobre propostas e emendas ocorreu no plenário durante o exercício nesta legislatura.` (AC 37)
Proof: `sail artisan test --filter="member page states the symbolic count"`

**C49** - `/senadores/9101/` renders `O Senado Federal não publica votações simbólicas como registros de votação; por isso elas não aparecem aqui.` and no `votações simbólicas sobre propostas` sentence, and a Câmara (102, 57) membership with `symbolic_merit` set to null renders the sentence with `A Câmara dos Deputados` (AC 38)
Proof: `sail artisan test --filter="member page says when a house publishes no symbolic votes"`

**C50** - The HTML of the 6 member pages, the 9 roll-call pages and `/metodologia/` of the fixtures contains, accent- and case-insensitively as whole words, none of `importante`, `importantes`, `relevante`, `relevantes` nor any of the 19 forbidden terms; the matcher flags `IMPORTANTE` and `Relevantes` and passes `importância` (AC 39)
Proof: `sail artisan test --filter="no ranking word in rendered pages"`

### S6 - roll-call pages for both houses · ~8 files · ~40 KB · ~10k

**C51** - `GET /senado/votacoes/6923/` responds 200 with component `RollCalls/Show`, `<h1>` `PL 1/2025`, and 6 voter rows linking to `/senadores/9101/` .. `/senadores/9106/` (AC 40)
Proof: `sail artisan test --filter="senate roll call page lists every senator"`

**C52** - The `<h1>` reads `PL 1/2023` on `/votacoes/100-1/` and `/votacoes/100-3/`, `PEC 3/2024` on `/votacoes/100-4/`, `Votação nominal de 10/05/2023` on `/votacoes/100-2/`, `Votação secreta de 01/08/2025` on `/votacoes/100-5/`, `Votação secreta de 10/06/2025` on `/senado/votacoes/7001/` (AC 41)
Proof: `sail artisan test --filter="roll call heading names the proposition or the ballot"`

**C53** - The classification line reads `Votação nominal · Decisão sobre a proposta` with link `regra camara.09` -> `/metodologia/#regra-camara-09` on `100-1`, `Votação nominal · Emenda, destaque ou parte do texto` `regra camara.08` on `100-2`, `Votação nominal · Procedimento` `regra camara.04` on `100-3`, `Votação simbólica · Decisão sobre a proposta` on `100-4`, `Votação secreta · Sem regra correspondente` with link `como as votações são classificadas` -> `/metodologia/#classificacao` on `100-6`, `Votação secreta · Decisão sobre a proposta` `regra senado.01` -> `/metodologia/#regra-senado-01` on `7001` (AC 42)
Proof: `sail artisan test --filter="roll call names its ballot kind and rule"`

**C54** - `VoteGroups::of` over one entry per position, given in reverse, returns groups `yes`, `no`, `abstention`, `obstruction`, `presiding`, `secret`, `notVoting` headed `Sim`, `Não`, `Abstenção`, `Obstrução`, `Art. 17 (presidente da sessão)`, `Deputados que votaram`, `Sem voto registrado` for `camara` and `Presidente da sessão (art. 51 RISF)`, `Senadores que votaram` at the presiding and secret places for `senado`; names `Zuleica`, `Érico`, `Abel`, `Ágata`, `Edu` in one group come out `Abel`, `Ágata`, `Edu`, `Érico`, `Zuleica` (AC 43)
Proof: `sail artisan test --filter="orders vote groups by position"`

**C55** - `/senado/votacoes/6923/` renders 4 `.ma-group` in order `Sim` [Rosa Andrade], `Não` [Sérgio Prado], `Presidente da sessão (art. 51 RISF)` [Ubiratan Costa], `Sem voto registrado` [Teresa Lins, Vera Dantas, Wagner Reis], whose rows' `svg.ma-vote` labels end `Sem voto: Presente, não registrou voto`, `Sem voto: Licença`, `Sem voto: Não compareceu` and whose `.ma-muted` notes read `Registro do Senado: Presente, não registrou voto`, `Registro do Senado: Licença`, `Registro do Senado: Não compareceu`; `/senado/votacoes/7001/` renders `Senadores que votaram` [Rosa Andrade, Sérgio Prado] labelled `Votou (votação secreta)` and `Sem voto registrado` with notes `Licença`, `Atividade parlamentar`, `Missão da Casa no País ou no exterior`, `Dispositivo não citado`; `/votacoes/100-2/` renders `Abstenção`, `Obstrução`, `Sem voto registrado` [Carla Dias, label `Registro sem voto`, no `Registro do Senado`]; `/votacoes/100-6/` one group `Deputados que votaram` [Ana Souza, Bruno Lima, Carla Dias]; every row links to its member's page (AC 43)
Proof: `sail artisan test --filter="roll call groups every member by position"`

**C56** - `/votacoes/100-4/` renders `Votação simbólica: não há registro do voto de cada parlamentar nem placar.`, no `.ma-tally` and no `.ma-group` (AC 44)
Proof: `sail artisan test --filter="symbolic roll call has no tally and no group"`

**C57** - `/votacoes/100-5/` renders `Placar não publicado pela Casa.`, no `.ma-tally`, and `Nenhum voto individual registrado nesta votação`; `/votacoes/100-6/` renders a `.ma-tally` writing `2`, `1`, `0` (AC 45, skeleton AC 21)
Proof: `sail artisan test --filter="secret roll call without a tally says so"`

**C58** - The head of `/senado/votacoes/6923/` holds title `PL 1/2025: como cada senador votou - Mandato Aberto`, `og:title` without the suffix, `canonical` and `og:url` `{APP_URL}/senado/votacoes/6923/` and the 4 fixed tags; `/senado/votacoes/7001/` title `Votação secreta de 10/06/2025: votação secreta - Mandato Aberto`; `/votacoes/100-4/` `PEC 3/2024: votação simbólica - Mandato Aberto`; `/votacoes/100-1/` `PL 1/2023: como cada deputado votou - Mandato Aberto` with description `Votação nominal de 01/03/2023 (Plenário) na Câmara dos Deputados, com o voto de cada deputado e o registro oficial.`; `/votacoes/100-6/` `Votação secreta de 01/09/2025: votação secreta - Mandato Aberto`; `/votacoes/200-1/`'s description contains `(CCJC)` (AC 46)
Proof: `sail artisan test --filter="roll call head carries the share tags by ballot"`

**C59** - Each of 7 paths responds 404 with the skeleton's page: `/votacoes/999-9/`, `/votacoes/abc/`, `/votacoes/6923/`, `/senado/votacoes/100-1/`, `/senado/votacoes/9999/`, `/senado/votacoes/abc/`, `/senado/votacoes/6923-1/` (AC 47)
Proof: `sail artisan test --filter="roll call pages 404"`

**C60** - `positionCase` returns, table-driven over all 14 (house, position) pairs, kinds `yes`, `no`, `abstention`, `obstruction`, `presiding`, `secret`, `not-voting` and labels `Sim`, `Não`, `Abstenção`, `Obstrução`, then `Art. 17 (presidente da sessão)`, `Votação secreta`, `Registro sem voto` for `camara` and `Presidente da sessão (art. 51 RISF)`, `Votou (votação secreta)` for `senado`; for `senado` `notVoting` with official `P-NRV`, `AP`, `MIS`, `NCom`, `NA`, `Licença` and `XYZ` the labels `Sem voto: Presente, não registrou voto`, `Sem voto: Atividade parlamentar`, `Sem voto: Missão da Casa no País ou no exterior`, `Sem voto: Não compareceu`, `Sem voto: Dispositivo não citado`, `Sem voto: Licença`, `Sem voto: XYZ`, and with official null `Não registrou voto`; for each Câmara position the label equals `voteCase` of the equivalent official (`Sim`, `Não`, `Abstenção`, `Obstrução`, `Artigo 17`, `` secret, `` open); `markShapes` draws `presiding` as the `article-17` dot and `secret`, `not-voting` as gaps (AC 48, door 5, door 6)
Proof: `sail npm --prefix /var/www/design test -- tests/vote.test.ts -t "positionCase"`

**C61** - `VoteMark` with `house` `senado`, `position` `presiding` renders `svg.ma-vote--presiding` with aria-label `Ana Souza, PT-SP, Presidente da sessão (art. 51 RISF)`; `MandateScore` with `house` `senado` and votes carrying `position` draws one column per vote with the door 5 shapes, a legend of 6 entries ending `Presidente da sessão (art. 51 RISF)`, `Não registrou voto`, and the old `vote`/`secret` inputs still render the skeleton's cases (door 5)
Proof: `sail npm --prefix /var/www/design test -- tests/components.test.ts -t "by position"`

### S7 - the methodology page · ~4 files · ~25 KB · ~6k

**C62** - After importing the parent, `GET /metodologia/` responds 200 with component `Methodology/Show`, one `<h1>` `Metodologia`, and 10 `section[id]` in order `bases`, `participacao`, `alinhamento-governo`, `alinhamento-partido`, `votacoes-simbolicas`, `proposicoes`, `tipos-de-votacao`, `classificacao`, `registros-sem-voto`, `cobertura`, each containing its copy of the AC 49 table verbatim, and `registros-sem-voto` a table `Casa`, `Registro oficial`, `Como aparece aqui` with 15 rows (7 Câmara, 8 Senado: `Presidente (art. 51 RISF)`, `Votou`, `P-NRV`, `AP`, `MIS`, `NCom`, `NA`, `Licença`) labelled as in door 5, followed by the AD-018 sentence (AC 49)
Proof: `sail artisan test --filter="methodology page has every section in order"`

**C63** - With both fixtures imported, `classificacao` renders `Regras da Câmara dos Deputados, versão 1` with a table `Regra`, `Tipo`, `Campo`, `Padrão`, `Descrição` of 11 rows `regra-camara-01` .. `regra-camara-11` whose cells equal the fixture's rules in order (kind as the AC 42 label, pattern inside `<code>`), and `Regras do Senado Federal, versão 2` with 2 rows `regra-senado-01`, `regra-senado-02` (AC 50)
Proof: `sail artisan test --filter="methodology lists each house's rules in stored order"`

**C64** - With only the Câmara imported, the `classificacao` and `cobertura` sections each contain `Ainda não há dados importados do Senado Federal.` and 0 Senate rows; with nothing imported, each also contains `Ainda não há dados importados da Câmara dos Deputados.` and no `table` (AC 51)
Proof: `sail artisan test --filter="methodology says when a house has no import"`

**C65** - With both fixtures imported, `cobertura` holds `Quantas votações cada importação trouxe, por Casa e legislatura.` and rows `Câmara dos Deputados`, `57ª`, `01/09/2025`, `4`, `2`, `1`, `1`; `Câmara dos Deputados`, `58ª`, `15/02/2027`, `1`, `0`, `0`, `0`; `Senado Federal`, `57ª`, `10/06/2025`, `1`, `1`, `não publicadas pela Casa`, `1`, under columns `Casa`, `Legislatura`, `Dados até`, `Nominais`, `Secretas`, `Simbólicas`, `Sem regra correspondente` (AC 52)
Proof: `sail artisan test --filter="methodology shows coverage per house and legislature"`

**C66** - The head of `/metodologia/` holds title `Metodologia - Mandato Aberto`, `canonical` and `og:url` `{APP_URL}/metodologia/`; every `methodUrl` link on the 6 member pages starts with `https://mandato.test/metodologia/#`, and no page of C50 contains `augusto-dmh.github.io/mandato-aberto/metodologia` (AC 53, AC 54)
Proof: `sail artisan test --filter="methodology head and every method link point to the app"`

### S8 - new pages keep the skeleton's guarantees · ~5 files · ~25 KB · ~6k

**C67** - None of 15 responses sends `Set-Cookie`: `/deputados/101/`, `/deputados/101/legislatura/57/`, `/senadores/9101/`, `/senadores/9101/legislatura/57/`, `/votacoes/100-1/`, `/senado/votacoes/6923/`, `/metodologia/` (200), and `/deputados/999999/`, `/deputados/101/legislatura/56/`, `/senadores/101/`, `/senadores/9101/legislatura/58/`, `/votacoes/999-9/`, `/senado/votacoes/100-1/`, `/senado/votacoes/9999/`, `/deputados/abc/` (404) (AC 55)
Proof: `sail artisan test --filter="public pages set no cookie"`
Proof: `curl -sI http://localhost:8091/senadores/9101/ | grep -ci '^set-cookie'` prints `0` after `sail artisan mandato:import tests/fixtures/v3`

**C68** - With SSR up, the initial HTML of `/deputados/101/legislatura/57/` holds inside `#app` the `<h1>` `Ana Souza` and the six values of C45; `/senadores/9101/legislatura/57/` its `<h1>`; `/votacoes/100-1/` and `/senado/votacoes/6923/` their `<h1>`; `/metodologia/` its `<h1>` and the 7 numbers of each C65 row (AC 56)
Proof: `sail artisan test --filter="server renders every new page body"`

**C69** - With `inertia.ssr.url` pointed at a closed port, `/deputados/101/legislatura/57/`, `/senadores/9101/`, `/senado/votacoes/6923/` and `/metodologia/` each respond 200 with the title, `og:title`, `canonical`, `og:url` of C38, C39, C58 and C66, the 4 fixed tags, and an empty `#app` (AC 57)
Proof: `sail artisan test --filter="head tags survive an ssr outage"`

**C70** - Each of the 7 Surface routes requested without the trailing slash (`/deputados/101`, `/deputados/101/legislatura/57`, `/senadores/9101`, `/senadores/9101/legislatura/57`, `/votacoes/100-1`, `/senado/votacoes/6923`, `/metodologia`) responds 200 with the canonical of its slash form (AC 58)
Proof: `sail artisan test --filter="slashless paths answer with the slash canonical"`

**C71** - Routes `deputies.show`, `deputies.legislature`, `senators.show`, `senators.legislature`, `roll-calls.show`, `senate-roll-calls.show` and `methodology` carry middleware `public` and not `web`, with URIs `deputados/{id}`, `deputados/{id}/legislatura/{n}`, `senadores/{id}`, `senadores/{id}/legislatura/{n}`, `votacoes/{id}`, `senado/votacoes/{id}`, `metodologia` and wheres `id` `[0-9]+` (both `n` `[0-9]+`), `[0-9]+-[0-9]+` and `[0-9]+`; no file under `app/` or `resources/js/` other than `app/Support/PublicUrl.php` contains a string literal starting `/deputados/`, `/senadores/`, `/votacoes/` or `/senado/votacoes/`; `PublicUrl::member('senado', '9101')` is `/senadores/9101/`, `::member('camara', '101', 57)` `/deputados/101/legislatura/57/`, `::rollCall('senado', '6923')` `/senado/votacoes/6923/`, `::rollCall('camara', '100-1')` `/votacoes/100-1/` (door 4, skeleton door 9 and 10)
Proof: `sail artisan test --filter="public routes use the cookie-free group"`
Proof: `sail artisan test --filter="public urls have one builder"`

**C72** - The 3 page components `Members/Show.vue`, `RollCalls/Show.vue` and `Methodology/Show.vue` import from `mandato-design/components/`, `Deputies/Show.vue` no longer exists, and no file under `resources/js` imports `Head` from `@inertiajs/vue3` (skeleton doors 2 and 8)
Proof: `sail artisan test --filter="consumes the design package"`
Proof: `sail artisan test --filter="pages leave the share tags to blade"`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| `GET /deputados/{id}/` statuses (2) | 200 C34 · 404 C40 | - |
| `GET /deputados/{id}/legislatura/{n}/` statuses (2) | 200 C34 · 404 C40 | - |
| `GET /senadores/{id}/` statuses (2) | 200 C35 · 404 C40 | - |
| `GET /senadores/{id}/legislatura/{n}/` statuses (2) | 200 C35 · 404 C40 | - |
| `GET /votacoes/{id}/` statuses (2) | 200 C52 · 404 C59 | - |
| `GET /senado/votacoes/{id}/` statuses (2) | 200 C51 · 404 C59 | - |
| `GET /metodologia/` statuses (1) | 200 C62 | - |
| `mandato:import` exit codes (3) | 0 C1 · 1 C9 · 2 C20 | - |
| import refusal causes (9) | unsupported version C9 · missing file C10 · schema failure C10 · leave code C11 · unresolved reference C12 · legislature dates C13 · no contract C20 · failed write C21 · lock held C22 | - |
| house files, missing (5) | C10, table-driven over all 5 | - |
| house file kinds, invalid (7) | C10, table-driven over all 7 | - |
| raw leave codes (3) | `LS` C11 · `LP` C11 · `LAP` C11 | - |
| unresolved references of AC 10 (13) | C12, table-driven over all 13 | - |
| records carrying `house` (6) | member C12 · roll-calls entry C12 · proposition C12 · rule C12 · roll-call file C12 · full text C12 | - |
| entities of Relations (12) | `Legislature` C13 · `Member` C1 · `Membership` C1 · `ExercisePeriod` C2 · `RollCall` C3 · `Vote` C4 · `Proposition` C5 · `ClassificationRule` C5 · `FullText` C5 · `Authorship` C6 · `ContractImport` C8 · `House` C29, C31 | - |
| authorship attachment (3) | presentedAt inside a held legislature C6 · presentedAt null C6 · no mandate in that legislature C6 | - |
| nullable counts (2) | `symbolicMerit` null C7 · tallies null C7 | - |
| swept on a smaller house (5) | roll call C15 · vote C15 · mandate C15 · exercise period C15 · authorship C15 | - |
| never swept (2) | member C15 · proposition C15 | - |
| replaced as a house set (2) | classification rules C17 · full texts C17 | - |
| import write paths (4) | real run C1 · dry run C28 · failed write C21 · parent with a failing house C19 | - |
| parent-directory runs (4) | both houses C18 · Câmara only C18 · Senate fails C19 · Câmara fails C19 | - |
| new stored constraints of door 3 (17) | `ballot` check C31 · roll-call `kind` check C31 · tallies all-or-none C31 · `government_orientation` check C31 · `position` check C31 · `party_majority` check C31 · rule `house` check C31 · rule `kind` check C31 · import `house` check C31 · rule unique (house, rule id) C32 · full text unique per proposition C32 · exercise period unique (membership, start) C32 · import `house` not null C32 · mandate `party` not null C32 · exercise periods cascade C32 · full text cascade C32 · `kind_rule` without foreign key C32 | - |
| member page decisions (6) | latest mandate by default C34 · legislature path C34, C35 · two mandates nav C36 · one mandate text C37 · latest canonical C39 · earlier canonical C39 | - |
| member 404 causes (6) | not digits C40 · unknown id C40 · other house's id C40 · legislature not held C40 · legislature not digits C40 · senate path with deputy id C40 | - |
| houses on member pages (2) | `camara` C34, C38, C43 · `senado` C35, C38, C43 | - |
| indicators × bases (6) | participation merit C45 · participation all C45 · government merit C45 · government all C45 · party merit C45 · party all C45 | - |
| basis states (2) | total > 0 C45 · total 0 C47 | - |
| `symbolicMerit` states (4) | n > 1 C48 · n = 1 C48 · 0 C48 · null C49 | - |
| ballot labels shown (3) | `Votação nominal` C52, C53 · `Votação secreta` C52, C53 · `Votação simbólica` C53 | - |
| kind labels shown (4) | `final` C53 · `amendment` C53 · `procedural` C53 · `unclassified` C53 | - |
| rule link forms (2) | `regra {kindRule}` C53 · `como as votações são classificadas` C53 | - |
| roll-call heading (2) | proposition C52 · ballot and date C52 | - |
| position groups and labels, camara (7) | C54, table-driven over all 7 · page C55 | - |
| position groups and labels, senado (7) | C54, table-driven over all 7 · page C55 | - |
| Senate `notVoting` officials of door 5 (7) | `P-NRV` C55, C60 · `AP` C55, C60 · `MIS` C55, C60 · `NCom` C55, C60 · `NA` C55, C60 · `Licença` C55, C60 · any other C60 | - |
| non-vote wording by page (2) | roll-call page `Registro do Senado:` C55 · member page `Não registrou voto` C42 | - |
| roll-call tally states (3) | present C57 · symbolic C56 · secret without tally C57 | - |
| roll-call head variants (6) | Senate nominal C58 · Senate secret C58 · Câmara symbolic C58 · Câmara nominal C58 · Câmara secret C58 · organ other than `PLEN` C58 | - |
| roll-call 404 causes (7) | C59, table-driven over all 7 | - |
| methodology sections (10) | C62, table-driven over all 10 | - |
| methodology house parts (4) | Câmara imported C63 · Senate imported C63 · Senate absent C64 · Câmara absent C64 | - |
| coverage symbolic cell (2) | integer C65 · null C65 | - |
| method links (1 origin) | `{APP_URL}/metodologia/#` C45, C66 | - |
| cookie-free responses (15) | C67, table-driven over all 15 | - |
| SSR states (2) | running C68 · unreachable C69 | - |
| trailing slash (7 routes) | C70, table-driven over all 7 | - |
| footer by house (2) | `camara` C43 · `senado` C43 | - |
| ranking words (23) | C50, table-driven over all 23 | - |
| Landing doors (6) | 1 C26, C24 · 2 C18, C27, C28 · 3 C31, C32, C33 · 4 C39, C71 · 5 C41, C60, C61 · 6 C42, C55, C60 | - |
| startup config: `public` group (1 shared assembly) | `bootstrap/app.php`, read by the HTTP kernel and the test harness alike, C71 | - |
| startup config: contract and schema dirs (1 shared assembly) | `config/mandato.php`, read by the command and the tests alike, C24, C27 | - |

- Claims naming a status code, route or response shape: C34-C47, C51-C59, C62-C70 - each proof crosses the HTTP boundary through Laravel's test client and reads the server-rendered HTML
- Claims naming an exit code or a printed line: C8-C13, C18-C22, C27, C28 - each proof runs the Artisan command and reads its exit code, stdout and stderr apart
- No other check claims more than the single case its proof exercises

## Test policy

The skeleton's rows (`.specs/features/app-skeleton/checks.md`, Test policy) still answer both questions for `app/`, and this feature builds under them unchanged; the design package's own suite answers them for `design/` (one asserted case per encoding row, `design/tests/components.test.ts`). What is new is where the decision tables sit:

| Code | Required proofs | Coverage expectation |
| --- | --- | --- |
| Decides, reached across a boundary | one at the boundary **and** one at its own layer | the contract at the boundary; one asserted case per row of the decision table at its own layer |
| Decides, not reached across a boundary | one at its own layer | one asserted case per row of the decision table |
| Entry point that decides nothing | one at the boundary | accepted input, each rejected input, each error path |
| Instrumentation, pass-throughs | none of its own | covered by its consumer's proof |

Evidence (shapes the doors plan; the build may name files differently):

- `V3Reader`: 13 reference rules plus 6 house checks plus schema validation per 7 file kinds -> decides, reached only through the command; its own layer is the command (no other caller), so every row is asserted at the Artisan boundary (C9-C13)
- `ImportContract` parent mode: 4 branches (house dir, parent with both, parent with one, neither) into 3 exit codes -> decides; C18-C20 at the boundary
- `Importer`: upsert, sweep per 5 kinds, house-set replacement of 2 kinds, legislature date guard -> decides; its own layer is the database, proven through the command (C13-C17, C21)
- `positionCase`: 14 (house, position) rows plus 7 Senate officials plus the null official -> decides, reached across HTTP; own layer C60 (table-driven), boundary C41, C42, C55
- `VoteGroups`: an ordering rule over 7 positions and a house-dependent heading -> decides, reached across HTTP; own layer C54, boundary C55
- profile copy branches: basis empty (2 rows), symbolic sentence (4 rows), nav vs text (2 rows), canonical (2 rows) -> decides, reached across HTTP; every row asserted at the boundary (C36-C39, C47-C49), where the fixture reaches each row cheaply
- roll-call presentation: heading (2), kind line (4 kinds + 2 link forms), tally (3), head title (6) -> decides; every row at the boundary (C52, C53, C56-C58)
- controllers and `PublicUrl`: map rows to props and paths -> instrumentation except `PublicUrl`'s house branch, proven at its own layer by C71
- closest analogue: the skeleton's `VoteGroups` (own layer C25 there, now C54) and `voteCase` (design components test, one case per value)

Cost: 3 proofs at their own layer (C54, C60, C71's `PublicUrl` cases) beyond the boundary proofs. Without these rows, the Senate's 7 official labels would be proven only by the 6 the fixture holds, and the unknown-value fallback by none.

## Swept

- validation: C9, C10, C11, C12, C13, C40, C59
- failure modes: C19, C21, C69
- idempotency: C14
- authorization: n/a - public read-only pages with no account; the import runs only from the shell (door 2), never over HTTP; C67 keeps every page cookie-free
- concurrency: C22
- data lifecycle: C15, C16, C17, C33, C23
- dependency failure: C24, C69
- state transitions: n/a - no entity carries a status; each import replaces one house's snapshot per listed legislature (C15, C16) and its rule and text sets (C17)
- observability: C8, C18, C19, C28

## Handoff

Size from `wc -c` on the files each slice reads and writes, divided by four. Read once: the plan (31 KB), the v3 schemas (22 KB), the skeleton's import, page and test code it rewrites (`app/app` 38 KB, `app/tests` 54 KB, pages 12 KB), `design/components` (13 KB) and its components test (12 KB) = 182 KB = ~45k. Written: S1 fixtures (~30 KB) plus reader, importer, command and tests (~65 KB) = ~24k; S2 ~3k; S3 migration and schema tests ~5k; S4 controller, page and tests ~11k; S5 ~5k; S6 controller, page, presenter, `vote.js`, components and tests ~10k; S7 ~6k; S8 ~6k. Total ~45k + 70k = ~115k, under the 150k budget - one builder

- Mechanism: one builder (the estimate fits; no ask)
