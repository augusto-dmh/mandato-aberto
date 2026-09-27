# etl-camara verification

**Verdict**: PASS
**Profile**: standard
**Diff range**: fb98700..327bf5b
**Round**: 4 - scoped
**Verifier**: self-verified (degraded - author == verifier; run inline at the maintainer's request instead of a fresh sub-agent)

Round 4 covers the fix diff `0dccaf9..327bf5b`. `f729176` changes one test (`etl/tests/test_download.py`, `[replaces-listed]` case); `327bf5b` only adds the lessons and the STATE handoff. It also covers the single verdict round 3 did not mark PASS: C46, its Coverage member "listed entry keeps its original `downloadedAt`", and Test policy row 1.

This round was run by the author, inline, because the maintainer asked for it that way. That is a degraded gate: a self-check can reproduce the author's blind spot. It is kept narrow on purpose. Only the round-3 gap and the lines the fix touched are judged here, and everything else is carried from the independent round 3 at `75250c3`, marked as such.

All proofs re-ran in full at `327bf5b`: `uv run --directory etl pytest -v tests/`, 89 passed, 0 failed. Both C46 ids and `test_stale_part_file_is_deleted` appear individually as `PASSED`.

The fix diff only changes the `[replaces-listed]` setup. Before the second build it rewrites the listed entry's `downloadedAt` to `2026-09-01T08:00:00Z`, which differs from the pinned clock (`2026-09-27T12:00:00Z`), and expects that value. No assertion was removed or loosened: `:166` still compares `entry["downloadedAt"] == expected_at`, and only `expected_at` changed from a value equal to the clock to one that is not.

## Binding sources - carried from 75250c3; the door-9 row's Uncovered cell updated at 327bf5b

Step 1 is `ui`-only. As in earlier rounds, the AD rows that door 9 exists to satisfy were re-read, because the fix reworded that door (`plan.md:65`).

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `.specs/STATE.md` AD-003 ("CPF is never persisted, exposed or logged"), AD-005 ("carries the collection timestamp; raw downloads are kept with a hash"), plan door 9 as reworded at 75250c3 | yes - read at 75250c3 | none. Every clause of the reworded door matches the code. Any header containing `cpf` in any case is blanked (`etl/src/mandato_etl/sources/camara.py:82`). A cached copy that still carries a value is redacted on every run (`camara.py:138-141`). `downloadedAt` comes from the listed entry or from the mtime read before redaction (`camara.py:137`). Stale `*.part`/`*.redacted` files are removed at the start of each run (`camara.py:124-125`). Each clause has a check (C39, C46); the listed-entry `downloadedAt` clause is discriminated at 327bf5b (C46, M1 killed). | - |
| `research/02` decision 9, plan AC 19 | carried from d49d892 | none | - |
| `research/02` decisions 3 and 8, "Votações incluídas" | carried from d49d892 | none | - |
| prototype `etl/build.py` (tag `prototype-2026-09`) | carried from d49d892 | none | - |

Round-2 redaction probes, status at 75250c3:

| Round-2 probe | Status at 75250c3 |
| --- | --- |
| A - hand-placed file; `downloadedAt` took the time of the redaction rewrite | closed. The mtime is now read before `_redact` (`camara.py:137-138`). Probe (worktree): swapping those two lines back fails `test_cached_copy_with_cpf_is_redacted[hand-placed]` |
| B - verbatim copy over a listed entry kept CPF | closed. Proven by `[replaces-listed]` (`etl/tests/test_download.py:156`); R2 killed |
| C - header `CPF` kept its values | closed. Both C46 ids use header `CPF` (`test_download.py:129-131`) |
| E - stale `.part` survived a cached run | closed. Proven by `test_stale_part_file_is_deleted` (`test_download.py:170`); the stale-cleanup mutant was killed |

## Checks - proofs verified at 327bf5b; C46 citations refreshed at 327bf5b; every other row carried from 75250c3

Proof run for every row: `uv run --directory etl pytest -v tests/` from the repo root, exit 0, 89 passed in 5.48s at `327bf5b`. Every named test and each parametrized id appears individually as `PASSED`.

`rg -n "^def test_(cached_copy_with_cpf_is_redacted|stale_part_file_is_deleted|vote_value_against_sim_orientation|source_missing_column_message)" etl/tests` finds:
- `test_download.py:139`
- `test_download.py:163`
- `test_indicators.py:136`
- `test_cli.py:63`

The test diff `d49d892..75250c3` only adds lines. It adds imports, two helpers and the four new tests. It removes no assertion. `conftest.py` is untouched.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | 7 files, a 7-entry manifest with exact keys, and hashes that match the disk | `test_download.py::test_empty_cache_downloads_every_source_and_writes_manifest` PASSED | `etl/tests/test_download.py:30` - `sorted(... fake.bulk_requests()) == sorted(FILES_2023)` (literal `:16-24`); `:34` - `set(entry) == {"file", "sourceUrl", "sha256", "bytes", "downloadedAt"}`; `:36` - `entry["sha256"] == hashlib.sha256(content).hexdigest()`; `:37` - `entry["bytes"] == len(content)` | PASS |
| C2 | default years `[2023..2026]` | `test_cli.py::test_default_years_run_from_2023_to_current_year` PASSED | `etl/tests/test_cli.py:12` - `["years"] == [2023, 2024, 2025, 2026]`; `:14` - `requested == {"2023.csv", ...}` | PASS |
| C3 | 0 requests when cached; 7 again with `--refresh` | `::test_cached_files_issue_no_request`, `::test_refresh_downloads_again` PASSED | `etl/tests/test_download.py:47` - `len(fake.bulk_requests()) == before == 7`; `:54` - `== 14` | PASS |
| C4 | 404, timeout and exhausted 503 give exit 2, the URL on stderr, and no `.part` or target file | `test_cli.py -k test_failed_download_exits_2_with_url_and_no_partial_file` - 3 ids PASSED | `etl/tests/test_cli.py:22` - `build(fake) == 2`; `:23` - URL `in ...err`; `:24` - `not list(fake.raw.glob("*.part"))`; `:25` - target absent | PASS |
| C5 | sleeps `[1, 2, 4]` / `[1]` / none on 404 | `test_download.py -k test_retry_schedule` - 5 ids PASSED | `etl/tests/test_download.py:78` - `fake.sleeps == sleeps` over the literals `:60-64`; `:79` - request count | PASS |
| C6 | User-Agent on every request | `::test_every_request_sends_user_agent` PASSED | `etl/tests/test_download.py:87` - `{ua ...} == {expected}`, literal at `:85`; `:86` - both `arquivos` and `api` | PASS |
| C7 | at most 4 in flight, 10 cache files, 0 requests on the second run | `::test_history_concurrency_is_capped_at_4`, `::test_history_is_cached_per_deputy` PASSED | `etl/tests/test_download.py:100` - `fake.max_in_flight == 4`; `:101` - 10 files; `:110-111` - `first == 10`, `len(fake.requests) == first` | PASS |
| C8 | legislature-57 voters only | `test_deputies.py::test_deputy_set_is_every_legislature_57_voter` PASSED | carried from d49d892: `etl/tests/test_deputies.py:10` - `== [101, 102, 103]`; `:11` - `104.json` absent | PASS |
| C9 | allowlisted keys only; CPF never in out/ | `::test_readers_keep_only_allowlisted_columns`, `::test_cpf_never_reaches_output` PASSED | carried from d49d892: `etl/tests/test_deputies.py:15`, `:22`, `:31`, `:32` | PASS |
| C10 | half-open periods | `::test_exercise_periods_are_half_open_and_close_at_build_time` PASSED | carried from d49d892: `etl/tests/test_deputies.py:47-49,52` | PASS |
| C11 | `inExercise` follows the API | `::test_in_exercise_follows_api_list` PASSED | carried from d49d892: `etl/tests/test_deputies.py:57` | PASS |
| C12 | profile from the latest vote | `::test_profile_fields_come_from_latest_vote` PASSED | carried from d49d892: `etl/tests/test_deputies.py:80` | PASS |
| C13 | duplicate vote keeps the latest | `test_roll_calls.py::test_duplicate_vote_keeps_latest` PASSED | carried from d49d892: `etl/tests/test_roll_calls.py:30-32` | PASS |
| C14 | pre-legislature and vote-less roll calls excluded | `::test_roll_calls_before_legislature_or_without_votes_are_excluded` PASSED | carried from d49d892: `etl/tests/test_roll_calls.py:37-39` | PASS |
| C15 | roll-call record fields | `::test_roll_call_record_fields` PASSED | carried from d49d892: `etl/tests/test_roll_calls.py:44,49,50,52-56,58,61,62` | PASS |
| C16 | deputy votes entries and order | `::test_deputy_votes_entries_and_order` PASSED | carried from d49d892: `etl/tests/test_roll_calls.py:68-69` | PASS |
| C17 | `governmentOrientation` casefold | `::test_government_orientation_matches_governo_casefolded` PASSED | carried from d49d892: `etl/tests/test_roll_calls.py:90-92` | PASS |
| C18 | participation 3/4, 2/2, 0/0 | `test_indicators.py::test_participation_counts` PASSED | `etl/tests/test_indicators.py:17-21` - `== {101: {"count": 3, "total": 4}, 102: {"count": 2, "total": 2}, 103: {"count": 0, "total": 0}}` | PASS |
| C19 | government alignment 2/3 for 101 and 1/1 for 102 (`Artigo 17` excluded) | `::test_government_alignment_counts` PASSED | `etl/tests/test_indicators.py:26` - `got[101] == {"count": 2, "total": 3}`; `:27` - `got[102] == {"count": 1, "total": 1}`. The asserted values are the check's. The vote-side exclusion of `Artigo 17` under a valid orientation, which this fixture cannot isolate (102's `Artigo 17` sits at R4 under `Liberado`, `etl/tests/conftest.py:91,141`), is now discriminated by C47 (`test_indicators.py:152`), which kills F5. The parenthetical's attribution goes under Precision gaps. | PASS |
| C20 | party alignment 2/3 and 0/0 | `::test_party_alignment_counts` PASSED | `etl/tests/test_indicators.py:33` - `got[101] == {"count": 2, "total": 3}`; `:34` - `got[103] == {"count": 0, "total": 0}` | PASS |
| C21 | party-majority table | `-k test_party_majority_table` - 5 ids PASSED | `etl/tests/test_indicators.py:49` - `party_majority(Counter(counts), own) == expected` over the literals `:40-44` | PASS |
| C22 | authorship counts | `::test_authorship_counts` PASSED | `etl/tests/test_indicators.py:54` - the 5 types; `:55` - ids 6001-6005; `:59` - `== (5, 2, 3)` | PASS |
| C23 | zero-total shape | `::test_zero_total_indicator_shape` PASSED | `etl/tests/test_indicators.py:68` - `raw == '{"count": 0, "total": 0}'`; `:70` | PASS |
| C24 | no ratio, percent or pct; `{count, total}` only | `::test_no_ratio_or_percentage_in_output` PASSED | `etl/tests/test_indicators.py:88`, `:91` (18 indicators), `:92` | PASS |
| C25 | exact and accent-only matches; matched = 2 | `test_tse.py::test_matches_on_normalized_name_birth_date_and_uf` PASSED | carried from d49d892: `etl/tests/test_tse.py:12`, `:14`, `:16` | PASS |
| C26 | TSE file absent or missing: null for all, a warning, exit 0 | `-k test_missing_tse_file_warns_and_exits_0` - 2 ids PASSED | carried from d49d892: `etl/tests/test_tse.py:23-25` | PASS |
| C27 | ambiguous match is null and listed | `::test_ambiguous_match_is_null_and_listed` PASSED | carried from d49d892: `etl/tests/test_tse.py:31-32` | PASS |
| C28 | no CPF column in the TSE allowlist or the output | `::test_tse_reader_never_reads_cpf_columns` PASSED | carried from d49d892: `etl/tests/test_tse.py:36`, `:38` | PASS |
| C29 | output validates; `validate` exits 0, then 1 | `test_publish.py::test_output_validates_against_schemas`, `::test_validate_command_exit_codes` PASSED | carried from d49d892: `etl/tests/test_publish.py:21,23,27,32,33` | PASS |
| C30 | `meta.json` fields | `::test_meta_fields` PASSED | carried from d49d892: `etl/tests/test_publish.py:38-45` | PASS |
| C31 | failed build keeps the previous output | `::test_failed_build_keeps_previous_output` PASSED | carried from d49d892: `etl/tests/test_publish.py:53,55,56` | PASS |
| C32 | byte-identical builds, ordering | `::test_builds_are_byte_identical`, `::test_output_ordering` PASSED | carried from d49d892: `etl/tests/test_publish.py:82,85,89,102,103-105` | PASS |
| C33 | out-of-range years and an unknown flag: exit 1 and `usage:` | `test_cli.py -k test_year_out_of_range_exits_1` - 4 ids PASSED | `etl/tests/test_cli.py:34` - `cli.main(argv) == 1`; `:35` - `"usage:" in ...err` | PASS |
| C34 | `sourceUrl` values | `test_publish.py::test_source_urls` PASSED | carried from d49d892: `etl/tests/test_publish.py:110,112,115` | PASS |
| C35 | contract layout | `::test_contract_layout` PASSED | carried from d49d892: `etl/tests/test_publish.py:123-124` | PASS |
| C36 | packaging | `test_packaging.py::test_runtime_dependencies_are_empty` PASSED | carried from d49d892: `etl/tests/test_packaging.py:12-18` | PASS |
| C37 | built-in validator agrees with `jsonschema` | `test_schema.py -k test_builtin_validator_agrees_with_jsonschema` - 1 + 8 ids PASSED | carried from d49d892: `etl/tests/test_schema.py:28`, `:29`, `:37`, `:38` | PASS |
| C38 | invalid output is never published | `test_publish.py::test_invalid_output_is_never_published` PASSED | carried from d49d892: `etl/tests/test_publish.py:71-74` | PASS |
| C39 | raw `deputados.csv` keeps the `cpf` header with empty values; the fixture CPF is in no file under raw; the manifest `sha256` matches | `test_download.py::test_raw_cache_never_keeps_cpf` PASSED | `etl/tests/test_download.py:116` - the source carries the CPF; `:120` - `len(rows) == 4`; `:121` - `[r["cpf"] for r in rows] == ["", "", "", ""]`; `:124` - `CPF_IN_DEPUTADOS.encode() not in path.read_bytes()` for every file under `fake.raw`; `:126` - `entry["sha256"] == hashlib.sha256(...).hexdigest()` | PASS |
| C40 | with orientation equal to the vote and one colleague voting the same, a vote counts toward both alignment totals exactly when it is `Sim`, `Não`, `Abstenção` or `Obstrução`; `Artigo 17` and empty give 0/0 | `test_indicators.py -k test_valid_vote_set_table` - 6 ids PASSED | `etl/tests/test_indicators.py:120` - `government == {"count": expected, "total": expected}`; `:121` - `party == {...}` over `:115`. Under the check's own scenario (orientation = vote, `:100`; colleague = vote, `:105`) every value is asserted. Round 2's objection was that this scenario cannot make the vote-side filter (`compute.py:225`) the deciding line. C47 now covers that case, and F5 dies there. The C40 wording goes under Precision gaps. | PASS |
| C41 | another birth date or another UF matches nobody | `test_tse.py::test_birth_date_and_uf_are_part_of_the_key` PASSED | carried from d49d892: `etl/tests/test_tse.py:47`, `:49` | PASS |
| C42 | two deputies sharing a key are both ambiguous | `::test_deputies_sharing_a_key_are_ambiguous` PASSED | carried from d49d892: `etl/tests/test_tse.py:56-57` | PASS |
| C43 | a missing allowlisted column gives exit 1, names the file and the column on stderr, and keeps the previous output | `test_cli.py::test_source_missing_column_exits_1` PASSED | `etl/tests/test_cli.py:45` - `build(fake, "--refresh") == 1`; `:47` - `"votacoes-2023.csv" in err`; `:48` - `"siglaOrgao" in err`; `:49` - out/ byte-identical. `:48` is still a substring of the injected `siglaOrgaoX` (`:44`), but the column is now pinned by C48 (`:68`), which kills the round-2 message mutant. Residual under Precision gaps. | PASS |
| C44 | `--quiet` gives an empty stderr; without it, one line per stage starting with `sources: 2023-2023` | `::test_quiet_silences_progress` PASSED | `etl/tests/test_cli.py:55` - `== 0`; `:56` - `capsys.readouterr().err == ""`; `:59` - `lines[0] == "sources: 2023-2023"`; `:60` - `len(lines) >= 4` (see Precision gaps) | PASS |
| C45 | `schema_version: 2` is rejected by both validators | `test_schema.py::test_const_violation_is_rejected_by_both` PASSED | carried from d49d892: `etl/tests/test_schema.py:49-50` | PASS |
| C46 | a CPF under header `CPF` is blanked, without `--refresh`, both in a hand-placed copy and in a copy replacing a listed entry; the manifest `sha256` matches; `downloadedAt` keeps `2026-08-28T12:00:00Z` (mtime) or the listed entry's original value; a stale `.part` is deleted | `test_download.py -k test_cached_copy_with_cpf_is_redacted` - 2 ids PASSED; `::test_stale_part_file_is_deleted` PASSED, at 327bf5b | `etl/tests/test_download.py:147` - `expected_at = "2026-08-28T12:00:00Z"` (hand-placed); `:152-157` - the listed entry is rewritten to `2026-09-01T08:00:00Z` and `expected_at` set to it, distinct from the pinned clock; `:166` - `entry["downloadedAt"] == expected_at`; the CPF, request, `sha256` and `bytes` assertions and `:176` (stale files) unchanged from round 3. M1 (listed -> build clock), M2 (listed ignored -> mtime) and M3 (manifest not rewritten after redaction) are all killed | PASS |
| C47 | one PLEN roll call, government orientation `Sim`, colleague `Sim`: per vote value X, government, party and participation pairs as tabled | `test_indicators.py -k test_vote_value_against_sim_orientation` - 6 ids PASSED | `etl/tests/test_indicators.py:152` - `as_pair(deputy["governmentAlignment"]) == government`; `:153` - `partyAlignment == party`; `:154` - `participation == participation`, over the literals `:127-132` (orientation `Sim` at `:140`, colleague `Sim` at `:145`). F5 killed at the `Artigo-17` id | PASS |
| C48 | the stderr of C43 contains exactly the line `error: votacoes-2023.csv: missing columns siglaOrgao` | `test_cli.py::test_source_missing_column_message` PASSED | `etl/tests/test_cli.py:67` - `build(fake) == 1`; `:68` - `"error: votacoes-2023.csv: missing columns siglaOrgao" in capsys.readouterr().err.splitlines()` (a whole-line match). Message mutant killed | PASS |

## Coverage - the `downloadedAt` row verified at 327bf5b; every other row carried from 75250c3

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| vote values in alignment (6 values x 2 indicators), verified at 75250c3 | Câmara vote values, AC 18/19, `compute.py:15,194,225-231` | Under orientation `Sim` with a `Sim` colleague: `Sim` 1/1, then `Não`, `Abstenção`, `Obstrução` 0/1, then `Artigo 17` and empty 0/0, for both indicators -> C47 (`test_indicators.py:152-153`). This is the one configuration where the vote filter at `compute.py:225` decides: counting `Artigo 17` or empty would turn 0/0 into 0/1, and dropping a valid value would turn 0/1 into 0/0. F5 killed. The four valid values also count under orientation = vote -> C40 (`:120-121`). | - |
| vote values in participation (6), verified at 75250c3 | `compute.py:215` (truthiness of the vote) | `Sim`, `Não`, `Abstenção`, `Obstrução`, `Artigo 17` -> 1/1, and empty -> 0/1 -> C47 (`test_indicators.py:154` over `:127-132`). `Abstenção` is now counted on a PLEN roll call in exercise, which closes the round-2 gap. Also C18 (`:17-21`) for the fixture values. | - |
| raw-cache states that could hold a CPF (4), verified at 75250c3 | `camara.py:96-146`, door 9, AD-003 | fresh download -> C39 (`test_download.py:121,124`; R1 killed in round 2, and the download branch `camara.py:106-107` changed only its call signature) · hand-placed copy with no manifest entry -> C46 `[hand-placed]` (`:156`) · copy replacing a listed entry -> C46 `[replaces-listed]` (`:156`); R2 killed both ids · stale `.part` and `.part.redacted` -> C46 (`:170`); the stale-cleanup mutant was killed. Swept for more members: the cached-branch temp name `deputados.csv.redacted` falls under the same `*.redacted` glob (`camara.py:124`); `historico/*.json` is scanned by C39's walk of every file under raw (`:122-124`). | - |
| manifest `downloadedAt` source per cache state (3), verified at 327bf5b | `camara.py:113,137`, door 9, AD-005 | fresh download -> build clock, C1 (`test_download.py:39`) · hand-placed copy -> file mtime read before redaction, C46 `[hand-placed]` (`:147,166`) · copy replacing a listed entry -> the listed value, C46 `[replaces-listed]` (`:152-157,166`), expected `2026-09-01T08:00:00Z` differs from the pinned clock; M1 and M2 killed | - |
| `mandato-etl build` cause -> exit code (5), verified at 75250c3 | `cli.py:52-78` | success 0 -> C26 · usage 1 -> C33 · schema 1 -> C38 · `SourceLayoutError` 1 -> C43 (`test_cli.py:45`), with its message pinned by C48 (`:67-68`) · download 2 -> C4, C31 | - |
| one-way doors (9), verified at 75250c3 | plan `Landing` | contract layout C35 · indicator shape C23/C24 · allowlist C9 · manifest C1 · match key C25, C41 · runtime deps C36 · project layout C36 · in-package validator C37, C45 · raw cache redaction: the blanking clauses -> C39, C46 (R2 and the stale-cleanup mutant killed at 75250c3); the `downloadedAt` clause -> C46 (M1, M2 killed at 327bf5b) | - |
| `mandato-etl build` flags (5) | carried from d49d892 (`cli.py` untouched by the fix) | `--years` C2/C33 · `--refresh` C3 · `--tse-csv` C25/C26 · `--out` C31 · `--quiet` C44 | - |
| TSE row kinds (7) | carried from d49d892 | C25, C27, C41, C42 | - |
| in-package validator keywords (10) | carried from d49d892 | C37, C45 | - |
| bulk source files per run (7) | carried from d49d892 | all 7 -> C1 | - |
| HTTP outcomes (6) | carried from d49d892 (`camara._get` unchanged) | 2xx, 404, timeout, 429, 503, 500 | - |
| `mandato-etl validate` exit codes (2) | carried from d49d892 | 0, 1 -> C29 | - |
| government orientation values (4) | carried from d49d892 | `Sim`, `Não`, absent -> C17 · `Liberado` -> C19 | - |
| party majority cases (4) | carried from d49d892 | C21 | - |
| history transitions (4) | carried from d49d892 | C10 | - |
| proposition types (9) | carried from d49d892 | C22 | - |
| output file kinds (5) | carried from d49d892 | C29, C35 | - |
| entities in `Relations` (9) | carried from d49d892 | C1, C8, C10, C13, C15, C17, C22, C25 | - |
| startup config: output root (2 assemblies) | carried from d49d892 (`cli.py:14-16` unchanged) | CLI and test harness | - |

Round-2 unproven members, status at 75250c3:

| Round-2 member | Status at 75250c3 |
| --- | --- |
| `Artigo 17` excluded from governmentAlignment and partyAlignment | proven (C47; F5 killed) |
| empty vote excluded from both alignments | proven (C47) |
| `Abstenção` in participation | proven (C47) |
| raw cache redaction of a hand-placed `deputados.csv`, without and with a manifest entry | proven (C46; R2 killed) |

Round-3 unproven member "listed entry keeps its original `downloadedAt`": proven at 327bf5b (C46 `[replaces-listed]`; M1 killed).

## Test policy rows - row 1 verified at 327bf5b; rows 2 and 3 carried from 75250c3

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary (CLI, HTTP) | `cli.py`, `sources/camara.py` | boundary: C4, C26, C33, C38, C43, C44, C48 · own layer: C3, C5, C7, C39, C46 | yes. `cli.py` carried as met. In `camara.py`, the two-row `downloadedAt` selection at `:137` now has a discriminating case per row (hand-placed `:147`, listed `:152-157`), and the rewrite decision at `:139` is discriminated by the listed case (M3 killed). |
| Decides, not reached across a boundary | `compute.py`, `sources/tse.py`, `readers.py` | own layer: C9, C10, C18-C23, C25, C27, C40-C42, C47 | yes. The alignment filter (vote set x orientation set) now has a case where the vote filter decides under a valid orientation and an existing party majority (C47; F5 killed). `tse.py` and `readers.py` are carried as met from d49d892. |
| Instrumentation, pass-throughs | `publish.dumps`, `schema.kind_of`, the `cli.build` wiring | none of their own | yes, carried from d49d892 (the fix did not touch these) |

## Faults injected - verified at 327bf5b

The faults ran in `git worktree add --detach /tmp/claude-1000/etl-verify-r4 HEAD` at `327bf5b`, where the baseline was 89 passed. Each was applied alone, the full suite ran, and the change was reverted with `git checkout -- .`. The worktree was removed with `git worktree remove --force`. The real tree's `git status --porcelain` was empty before and after and compared identical with `cmp`. `git stash` was not used. A first attempt at M3 targeted the wrong line (140 instead of 139) and changed nothing; it was discarded and re-run on line 139 in a fresh worktree.

| Mutation | Location | Killed |
| --- | --- | --- |
| M1 (round-3 survivor): a listed entry gets the build time (`listed["downloadedAt"] if listed` -> `_iso(now) if listed`) | `etl/src/mandato_etl/sources/camara.py:137` | yes - `test_cached_copy_with_cpf_is_redacted[replaces-listed]` fails; 1 failed, 88 passed |
| M2: the listed value is ignored and every cached copy is dated by its mtime | `etl/src/mandato_etl/sources/camara.py:137` | yes - `[replaces-listed]` fails; 1 failed, 88 passed |
| M3: the manifest entry is not rewritten after a listed copy is redacted (`if listed is None or redacted:` -> `if listed is None:`) | `etl/src/mandato_etl/sources/camara.py:139` | yes - `[replaces-listed]` fails; 1 failed, 88 passed |

Faults injected in round 3 at `75250c3` (F5, R2, the C43/C48 message mutant, the stale-cleanup mutant) were all killed then; the fix diff touches no source file, so they are carried.

## Gate - verified at 327bf5b

`uv run --directory etl pytest -v tests/` at `327bf5b` - 89 passed, 0 failed (5.48s)

## Precision gaps

These are findings about how the checks are worded. They do not fail the verdict on their own.

- **C19's parenthetical "(`Artigo 17` excluded)"** names the vote as the reason 102 is at 1/1. In the fixture, 102's `Artigo 17` is at R4, whose government orientation is `Liberado` (`etl/tests/conftest.py:91,141`), so the orientation filter excludes it before the vote filter is reached. The fixture comment at `conftest.py:90` repeats the misattribution. The behaviour the parenthetical names is proven by C47, not by C19.
- **C40's scenario (orientation = vote, colleague = vote)** cannot isolate the vote filter for `Artigo 17` or empty, because the orientation and party-count filters reject those values first. C47 is the discriminating check. C40 is now redundant for those two rows and could be reworded to cover only the four valid values.
- **C43 vs C48.** C43's own column assertion is still a substring match (`etl/tests/test_cli.py:48` against `siglaOrgaoX` at `:44`). C48 says "the stderr of C43", but its test runs a fresh build with no prior successful build and no `--refresh` (`test_cli.py:63-67`). That is the same error path (`readers.py:66`), but not the same scenario as C43.
- **C46 header spelling.** Both cached-copy ids use `CPF` (`test_download.py:131`). A cached copy with a lowercase `cpf` value is not exercised on the cached branch. The same casefolded `_redact` handles both (`camara.py:82`), and the download path covers lowercase (C39).
- **C46 stale files.** The test places `deputados.csv.part` and `deputados.csv.part.redacted` (`test_download.py:166`), but not the cached-branch temp file `deputados.csv.redacted`. The same glob covers it (`camara.py:124`).
- **Carried from 75250c3, still open:**
  - AC 19 / C21 "strict majority" (the code computes a plurality, `compute.py:62-70`).
  - C32 "id ascending" compares ids as strings (`compute.py:206`).
  - C44 asserts `len(lines) >= 4` (`test_cli.py:60`).
  - C44 against AC 25 and the Log-format assumption: `--quiet` does not silence the TSE warning (`cli.py:106`), and which rule wins is undecided.

## For the maintainer

Carried from 75250c3, updated at 327bf5b:

1. **Doors 8 and 9 approved.** The maintainer approved the in-package schema validator and the raw cache redaction on 2026-09-27 (`plan.md` `Landing`, `research/decisions-log.md`).
2. **TSE input with `NR_CPF_CANDIDATO`.** The hand-downloaded `consulta_cand_2026_BRASIL.csv` in `etl/inputs/tse/` (gitignored) carries every candidate's CPF. The ETL never reads the column (C28). Whether a hand-held input counts as "persisted" under AD-003 is still undecided.
3. **Other personal columns in the raw cache.** `data/raw/deputados.csv` keeps sex, death date and birthplace; only CPF columns are blanked (`camara.py:82`). Still undecided.
4. **Operational note.** Concurrent builds on one `data/raw/` are unsupported: the start-of-run sweep (`camara.py:124-125`) deletes every `*.part`.
