# etl-camara checks

Profile: standard
Plan: `.specs/features/etl-camara/plan.md`

## Intent

45 checks in 6 slices (C39-C45 added in verification round 1) · 9 one-way doors (7 approved + 2 added: in-package schema validator, raw cache redaction) · 2 open, of which 1 blocks go-live (S5 on fixture until the TSE file exists)

All proofs run from the repository root. `P` below abbreviates `uv run --directory etl pytest`.
Fixtures are hand-built under `etl/tests/fixtures/`; the clock is pinned at `2026-09-27T12:00:00Z`
(`2026-09-27T09:00:00` Brasília, the zone of every Câmara timestamp) unless a check says otherwise.

## Checks

### S1 - Download and snapshot · 3 files · 30 KB · ~8k

**C1** - With an empty `data/raw/` and `--years 2023`, the build requests exactly 7 files - `votacoes-2023.csv`, `votacoesVotos-2023.csv`, `votacoesOrientacoes-2023.csv`, `votacoesProposicoes-2023.csv`, `proposicoes-2023.csv`, `proposicoesAutores-2023.csv`, `deputados.csv` - and `data/raw/manifest.json` holds 7 entries whose keys are exactly `{file, sourceUrl, sha256, bytes, downloadedAt}` and whose `sha256` and `bytes` equal the files on disk (AC 1)
Proof: `P tests/test_download.py::test_empty_cache_downloads_every_source_and_writes_manifest`

**C2** - Without `--years`, the years are 2023 through the current year: `[2023, 2024, 2025, 2026]` with the clock pinned in 2026 (AC 1)
Proof: `P tests/test_cli.py::test_default_years_run_from_2023_to_current_year`

**C3** - A second build without `--refresh` issues 0 requests for bulk files; with `--refresh` it issues 7 again (AC 2)
Proof: `P tests/test_download.py::test_cached_files_issue_no_request`
Proof: `P tests/test_download.py::test_refresh_downloads_again`

**C4** - A bulk file answering 404, a timeout, or 503 on all 4 attempts makes the build exit `2`, prints the failing URL on stderr, and leaves neither a `.part` file nor the target file in `data/raw/` (AC 3)
Proof: `P tests/test_cli.py -k test_failed_download_exits_2_with_url_and_no_partial_file`

**C5** - A 429 or 503 is retried at most 3 times after sleeping exactly `[1, 2, 4]` seconds; a 503 followed by a 200 succeeds after sleeping `[1]`; a 404 is not retried (AC 4)
Proof: `P tests/test_download.py -k test_retry_schedule`

**C6** - Every HTTP request - bulk and API - carries `User-Agent: mandato-aberto-etl/<version> (+https://github.com/augusto-dmh/mandato-aberto)` with `<version>` equal to `mandato_etl.__version__` (AC 5)
Proof: `P tests/test_download.py::test_every_request_sends_user_agent`

**C7** - Fetching 10 histories from a server that holds each request for 0.2 s never has more than 4 in flight, writes `data/raw/historico/{id}.json` for each of the 10, and a second build issues 0 history requests (AC 6)
Proof: `P tests/test_download.py::test_history_concurrency_is_capped_at_4`
Proof: `P tests/test_download.py::test_history_is_cached_per_deputy`

**C39** - After a build, `data/raw/deputados.csv` keeps its `cpf` header with every value empty, the fixture CPF `52998224725` occurs in no file under `data/raw/`, and the manifest `sha256` of `deputados.csv` equals the stored file (AD-003, door 9, added in round 1)
Proof: `P tests/test_download.py::test_raw_cache_never_keeps_cpf`

### S2 - Deputies and exercise periods · 3 files · 25 KB · ~7k

**C8** - `deputies.json` lists exactly the ids with at least one `votacoesVotos` record whose `deputado_idLegislatura` is `57`; the fixture has 3 such deputies and 1 with records only in legislature 56, which is absent (AC 7)
Proof: `P tests/test_deputies.py::test_deputy_set_is_every_legislature_57_voter`

**C9** - Every reader yields rows whose keys equal that file's allowlist exactly; no allowlist contains `cpf`; with a fixture `deputados.csv` carrying `cpf = 52998224725`, neither the key `cpf` nor the string `52998224725` occurs in any file under the output directory (AC 8)
Proof: `P tests/test_deputies.py::test_readers_keep_only_allowlisted_columns`
Proof: `P tests/test_deputies.py::test_cpf_never_reaches_output`

**C10** - A history `Exercício 2023-02-01T12:05`, `Licença 2024-03-01T00:00`, `Exercício 2025-01-10T10:00`, preceded by an `Exercício` entry dated `2022-12-01`, yields exactly `[{"start": "2023-02-01T12:05:00", "end": "2024-03-01T00:00:00"}, {"start": "2025-01-10T10:00:00", "end": "2026-09-27T09:00:00"}]`; a roll call at `2024-03-01T00:00:00` is outside the first period and one at `2023-02-01T12:05:00` is inside (AC 9)
Proof: `P tests/test_deputies.py::test_exercise_periods_are_half_open_and_close_at_build_time`

**C11** - `inExercise` is `true` for the 2 fixture deputies present in the API list `/deputados?itens=1000` and `false` for the 1 absent (AC 10)
Proof: `P tests/test_deputies.py::test_in_exercise_follows_api_list`

**C12** - A deputy whose vote records carry party `PSD`/UF `SP`/old photo at `2023-03-01T10:00:00` and party `PL`/UF `RJ`/new name and photo at `2025-05-01T10:00:00`, read in that file order reversed, gets `party = PL`, `uf = RJ` and the newer `name` and `photoUrl` (AC 11)
Proof: `P tests/test_deputies.py::test_profile_fields_come_from_latest_vote`

### S3 - Roll calls and votes · 3 files · 25 KB · ~7k

**C13** - A roll call with 5 vote records, one deputy recorded twice (`Não` at 10:00:00, `Sim` at 10:00:30), yields 4 `votes` entries, that deputy's entry is `Sim`, and `tallies` is `{"yes": 3, "no": 0, "others": 1}` (AC 12)
Proof: `P tests/test_roll_calls.py::test_duplicate_vote_keeps_latest`

**C14** - A roll call dated `2023-01-31` and a roll call with no `votacoesVotos` record are absent from `roll-calls.json` and have no `roll-calls/{id}.json` (AC 13)
Proof: `P tests/test_roll_calls.py::test_roll_calls_before_legislature_or_without_votes_are_excluded`

**C15** - `roll-calls/{id}.json` has exactly the keys `id, date, organ, description, proposition, approved, tallies, governmentOrientation, sourceUrl, votes`; `proposition` is `{id, title, summary}` or `null`; `approved` maps `"1"`/`"0"`/`""` to `true`/`false`/`null`; each `votes` entry has exactly `deputyId, vote, party`, ordered by `deputyId` ascending (AC 14)
Proof: `P tests/test_roll_calls.py::test_roll_call_record_fields`

**C16** - `deputies/{id}.json` `votes` has one entry per roll call the deputy has a record in, each with exactly `rollCallId, vote, party, partyMajority`, ordered by roll-call date descending then id ascending (AC 15)
Proof: `P tests/test_roll_calls.py::test_deputy_votes_entries_and_order`

**C17** - `governmentOrientation` is `Sim` for a roll call whose bench reads `GOVERNO`, `Não` for one reading `Governo`, and `null` for one with only party benches (AC 16)
Proof: `P tests/test_roll_calls.py::test_government_orientation_matches_governo_casefolded`

### S4 - Indicators · 2 files · 20 KB · ~5k

The fixture is 3 deputies (`101`, `102`, `103`) and 4 PLEN roll calls plus 1 committee roll call;
the expected numbers were computed by hand and are written as literals in the tests.

**C18** - `participation` is `{"count": 3, "total": 4}` for deputy 101 (one PLEN roll call with an empty vote, one committee vote not counted), `{"count": 2, "total": 2}` for 102 (on leave during 2 of the 4, one of those votes `Artigo 17`), and `{"count": 0, "total": 0}` for 103 (no exercise period) (AC 17)
Proof: `P tests/test_indicators.py::test_participation_counts`

**C19** - `governmentAlignment` is `{"count": 2, "total": 3}` for deputy 101 (orientation `Liberado` in one roll call, excluded) and `{"count": 1, "total": 1}` for 102 (`Artigo 17` excluded) (AC 18)
Proof: `P tests/test_indicators.py::test_government_alignment_counts`

**C20** - `partyAlignment` is `{"count": 2, "total": 3}` for deputy 101 and `{"count": 0, "total": 0}` for 103, whose party has no other member (AC 19)
Proof: `P tests/test_indicators.py::test_party_alignment_counts`

**C21** - The party majority of `{Sim: 2, Não: 1}` excluding an own `Sim` is `null` (tie 1-1); of `{Sim: 3, Não: 1}` excluding own `Sim` is `Sim`; of `{Sim: 1}` excluding own `Sim` is `null` (empty); of `{Sim: 2, Não: 2}` with own vote `Artigo 17` is `null` (tie) (AC 20)
Proof: `P tests/test_indicators.py -k test_party_majority_table`

**C22** - Of propositions presented on or after `2023-02-01` with the deputy as `proponente = 1`: each of `PL`, `PLP`, `PEC`, `PDL`, `PRC` is counted in `authored`, each of `REQ`, `RIC`, `INC` only in `requirementsCount`, `EMC` in neither; one presented `2023-01-20` and one with `proponente = 0` are in neither; `firstSigner` is `true` exactly for `ordemAssinatura = 1` (AC 21)
Proof: `P tests/test_indicators.py::test_authorship_counts`

**C23** - An indicator with `total = 0` is written as exactly `{"count": 0, "total": 0}` (AC 22)
Proof: `P tests/test_indicators.py::test_zero_total_indicator_shape`

**C24** - Every indicator object in the output has exactly the keys `{count, total}` with `count <= total`, and no key under the output directory contains `ratio`, `percent` or `pct` (AC 23)
Proof: `P tests/test_indicators.py::test_no_ratio_or_percentage_in_output`

**C40** - A vote counts toward `governmentAlignment.total` (orientation equal to the vote) and `partyAlignment.total` (one other member voting the same) exactly when it is `Sim`, `Não`, `Abstenção` or `Obstrução`; `Artigo 17` and an empty vote give `{"count": 0, "total": 0}` for both (AC 18, AC 19, added in round 1)
Proof: `P tests/test_indicators.py -k test_valid_vote_set_table`

### S5 - Candidacy 2026 · 2 files · 12 KB · ~3k

**C25** - With a 5-row TSE fixture (latin-1, `;`): an exact match and an accent-only difference (`JOSE` vs `JOSÉ`) each set `candidacy2026` to `{office, party, ballotNumber, situation}` with the fixture values; a row for a non-deputy changes nothing; `meta.candidacy.matched` is `2` (AC 24)
Proof: `P tests/test_tse.py::test_matches_on_normalized_name_birth_date_and_uf`

**C26** - With `--tse-csv` absent, and with `--tse-csv` naming a missing file, every deputy has `candidacy2026: null`, stderr contains `warning`, and the exit code is `0` (AC 25)
Proof: `P tests/test_tse.py -k test_missing_tse_file_warns_and_exits_0`

**C27** - Two TSE rows matching the same deputy leave that deputy at `candidacy2026: null` and `meta.candidacy.ambiguous` equals `[<that id>]` (AC 26)
Proof: `P tests/test_tse.py::test_ambiguous_match_is_null_and_listed`

**C28** - The TSE allowlist has no column whose name contains `CPF`, and the fixture value of `NR_CPF_CANDIDATO` (`11144477735`) occurs in no output file (AC 27)
Proof: `P tests/test_tse.py::test_tse_reader_never_reads_cpf_columns`

**C41** - A TSE row with a deputy's civil name and UF but another birth date, and one with the civil name and birth date but another UF, match nobody: `candidacy2026` stays `null`, `matched = 0`, `ambiguous = []` (AC 24, door 5, added in round 1)
Proof: `P tests/test_tse.py::test_birth_date_and_uf_are_part_of_the_key`

**C42** - Two deputies sharing one match key and one TSE row for that key both get `null` and both ids are listed as ambiguous (AC 26, added in round 1)
Proof: `P tests/test_tse.py::test_deputies_sharing_a_key_are_ambiguous`

### S6 - Publish and validate · 5 files · 45 KB · ~12k

**C29** - After a fixture build, every output file validates against its schema in `etl/schema/` under the `jsonschema` library, and `mandato-etl validate <out>` exits `0`; after one file is corrupted, it exits `1` and names that file on stderr (AC 28)
Proof: `P tests/test_publish.py::test_output_validates_against_schemas`
Proof: `P tests/test_publish.py::test_validate_command_exit_codes`

**C30** - `meta.json` has exactly the keys `schema_version, generatedAt, years, counts, sources, candidacy`; `schema_version` is `1`; `generatedAt` is `2026-09-27T12:00:00Z`; `counts` has `deputies = 3`, `rollCalls` and `propositions` equal to the fixture's; `sources` equals the manifest entries; `candidacy` has `file, matched, ambiguous` (AC 29)
Proof: `P tests/test_publish.py::test_meta_fields`

**C31** - A build that fails after downloading (history API answering 500) exits `2` and leaves every file of the previous output byte-identical, with no temporary directory left beside it (AC 30)
Proof: `P tests/test_publish.py::test_failed_build_keeps_previous_output`

**C32** - Two builds on the same inputs produce byte-identical trees; `deputies.json` is ordered by accent-stripped casefolded name then id, `roll-calls.json` by date descending then id ascending, and every file is written with sorted keys (AC 31)
Proof: `P tests/test_publish.py::test_builds_are_byte_identical`
Proof: `P tests/test_publish.py::test_output_ordering`

**C33** - `--years 2022` and `--years 2027` (clock in 2026) exit `1` with `usage:` on stderr, and so does an unknown flag (AC 32)
Proof: `P tests/test_cli.py -k test_year_out_of_range_exits_1`

**C34** - Deputy records carry `sourceUrl = https://www.camara.leg.br/deputados/{id}`; roll-call records carry `sourceUrl = https://dadosabertos.camara.leg.br/api/v2/votacoes/{id}`; authored propositions carry `sourceUrl = https://www.camara.leg.br/propostas-legislativas/{id}` (AC 33)
Proof: `P tests/test_publish.py::test_source_urls`

**C35** - The output directory holds exactly `meta.json`, `deputies.json`, `roll-calls.json`, one `deputies/{id}.json` per deputy and one `roll-calls/{id}.json` per roll call, and `etl/schema/` holds one schema per kind: `meta`, `deputies`, `roll-calls`, `deputy`, `roll-call` (door 1)
Proof: `P tests/test_publish.py::test_contract_layout`

**C36** - `etl/pyproject.toml` declares `dependencies = []`, `requires-python = ">=3.13"`, the script `mandato-etl = "mandato_etl.cli:main"`, and `pytest` and `jsonschema` only in the `dev` group; no module under `etl/src/` imports `jsonschema` (doors 6 and 7)
Proof: `P tests/test_packaging.py::test_runtime_dependencies_are_empty`

**C37** - The in-package validator agrees with `jsonschema` on every file of a fixture build (valid) and on 8 corrupted documents - wrong type, missing required key, extra key, enum miss, pattern miss, negative count, null where not allowed, wrong item type (all invalid) (door 8, added)
Proof: `P tests/test_schema.py -k test_builtin_validator_agrees_with_jsonschema`

**C38** - The build validates its output with the in-package validator before replacing `--out`: a schema violation injected into the computed records makes the build exit `1` with the file named on stderr and leaves the previous output unchanged (AC 28, AC 30, door 8)
Proof: `P tests/test_publish.py::test_invalid_output_is_never_published`

**C43** - A bulk file missing an allowlisted column makes the build exit `1` with the file name and the column on stderr and leaves the previous output unchanged (Observable "exit codes", added in round 1)
Proof: `P tests/test_cli.py::test_source_missing_column_exits_1`

**C44** - `--quiet` with `--tse-csv` given leaves stderr empty on a successful build; without `--quiet` stderr carries one line per stage, starting with `sources: 2023-2023` (assumption "Log format", added in round 1)
Proof: `P tests/test_cli.py::test_quiet_silences_progress`

**C45** - A `meta.json` with `schema_version: 2` is rejected by both the in-package validator and `jsonschema` (door 8, `const`, added in round 1)
Proof: `P tests/test_schema.py::test_const_violation_is_rejected_by_both`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| bulk source files per run (7) | `votacoes` C1 · `votacoesVotos` C1 · `votacoesOrientacoes` C1 · `votacoesProposicoes` C1 · `proposicoes` C1 · `proposicoesAutores` C1 · `deputados` C1 | - |
| HTTP outcomes (5) | 2xx C1 · 404 C4 · timeout C4 · 429 C5 · 503 C5 | - |
| `mandato-etl build` exit codes (3) | `0` C26 · `1` C33, C38, C43 · `2` C4 | - |
| `mandato-etl validate` exit codes (2) | `0` C29 · `1` C29 | - |
| `mandato-etl build` flags (5) | `--years` C2 · `--refresh` C3 · `--tse-csv` C25 · `--out` C31 · `--quiet` C44 | - |
| vote values in participation (6) | `Sim` C18 · `Não` C18 · `Abstenção` C18 · `Obstrução` C18 · `Artigo 17` C18 · empty C18 | - |
| vote values in alignment (6) | `Sim` C40 · `Não` C40 · `Abstenção` C40 · `Obstrução` C40 · `Artigo 17` C40 · empty C40 | - |
| government orientation values (4) | `Sim` C17 · `Não` C17 · `Liberado` C19 · absent C17 | - |
| party majority cases (4) | clear C21 · tie C21 · empty C21 · own vote not valid C21 | - |
| history transitions (4) | `Exercício` opens C10 · other status closes C10 · entry before 2023-02-01 ignored C10 · open period closes at build time C10 | - |
| proposition types (9) | `PL` C22 · `PLP` C22 · `PEC` C22 · `PDL` C22 · `PRC` C22 · `REQ` C22 · `RIC` C22 · `INC` C22 · other (`EMC`) C22 | - |
| TSE row kinds (7) | exact C25 · accent-only C25 · ambiguous C27 · non-deputy C25 · other birth date C41 · other UF C41 · key shared by two deputies C42 | - |
| output file kinds (5) | C29, table-driven over `meta` `deputies` `roll-calls` `deputy` `roll-call`; layout C35 | - |
| one-way doors (9) | contract layout C35 · indicator shape C23 · allowlist C9 · manifest C1 · match key C25, C41 · runtime deps C36 · project layout C36 · in-package validator C37, C45 · raw cache redaction C39 | - |
| entities in `Relations` (9) | Deputy C8 · ExercisePeriod C10 · Vote C13 · RollCall C15 · Proposition C22 · Orientation C17 · Authorship C22 · Candidacy2026 C25 · SourceFile/Manifest C1 | - |
| startup config: output root (2 assemblies) | CLI entry point C31 · test harness C1 | - |

- Claims naming an exit code or stderr content: C4, C26, C29, C31, C33, C38 - each proof runs `cli.main` with argv and asserts the return code and captured stderr
- Claims about HTTP behaviour: C1, C3-C7 - each proof crosses a real socket to a local `http.server`

## Test policy

The repo says tests exist and that ETL metrics get unit tests over small fixtures (grilling premise
"Testes"); it does not say which level proves the CLI and the publish step, so these rows are the bar.

| Code | Required proofs | Coverage expectation |
| --- | --- | --- |
| Decides, reached across a boundary (CLI, HTTP) | one at the boundary **and** one at its own layer | exit code + stderr at the CLI; one asserted case per row of the decision table at its own layer |
| Decides, not reached across a boundary | one at its own layer | one asserted case per row of the decision table |
| Instrumentation, pass-throughs | none of its own | covered by its consumer's proof |

Evidence:

- `compute.py` (to be written): exercise periods (4 transitions), party majority (4 cases), alignment filters (vote set x orientation set), authorship (9 types) -> decides, own layer: C10, C18-C23
- `sources/camara.py`: retry dispatch over 4 HTTP outcomes, cache hit/miss -> decides, reached across HTTP: C1, C3-C5 at the socket
- `sources/tse.py`: match outcome over 4 row kinds -> decides, own layer: C25, C27
- `cli.py`: exit-code dispatch over 3 codes -> decides, reached across the CLI: C4, C26, C33
- `readers.py`: forwards rows through an allowlist, one conditional -> own layer C9
- closest analogue in the repo: none - greenfield; the prototype has no tests

Cost: 6 test files, ~45 test functions. Without these rows the exit-code table would be proven only through the happy path.

## Swept

- validation: C33 (years, flags), C29 and C38 (schema)
- failure modes: C4, C31, C38
- idempotency: C3, C32
- authorization: n/a - no route, no user; the ETL reads public data and writes local files
- concurrency: C7
- data lifecycle: C9, C28 (CPF never read into output), C39 (CPF never kept in the raw cache), C31 (previous output kept)
- dependency failure: C4, C5, C31
- state transitions: C10 (exercise periods)
- observability: C4 (failing URL on stderr), C26 (warning on stderr)

## Out of scope

Carried by `plan.md`.

## Handoff

- Size: S1 8k + S2 7k + S3 7k + S4 5k + S5 3k + S6 12k = 42k of new code and tests, plus ~18k to read `plan.md`, this file and the prototype `etl/build.py` = 60k, under the 150k budget - one builder
- Mechanism: one builder (fits; no ask)
- **Boundary:** C1-C38 closed at `b37e34d` (CI job at `ca7ade4`); `uv run --directory etl pytest` 67 passed. A real run over 2023-2026 wrote 643 deputies and 1,597 roll calls, `mandato-etl validate` exit 0, and both alignment indicators agree with the prototype within 1 point for all 643
- **Settled mid-build:** none asked of the user. `meta.sources[].sourceUrl` accepts `^https?://` because the manifest records the URL actually fetched; every other URL in the contract stays `^https://`. Roll-call `sourceUrl` is the API record, since the Câmara portal has no page per roll call
- **Abandoned:** none
