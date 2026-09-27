# etl-camara verification

**Verdict**: FAIL
**Profile**: standard
**Diff range**: fb98700..d49d892
**Round**: 2 - scoped
**Verifier**: independent sub-agent (author != verifier)

Round 2 covers the fix diff `68ee2e0..d49d892` (d49d892 is the fix; e5f24ad adds only the round-1 report) and every verdict that round 1 did not mark PASS. All proofs re-ran in full at `d49d892`: 79 passed, 0 failed. All 7 new named tests exist and appear individually as `PASSED`. The fix closes F3, `SourceLayoutError`, `--quiet`, `const`, the shared-key branch and the C1 literal list. The verdict is still FAIL because:

- 3 of 5 injected faults survived:
  - F5: an `Artigo 17` vote counted in the government-alignment total.
  - The redaction branch for hand-placed files is never exercised.
  - C43's column assertion is a substring of the renamed fixture column.
- C19, C40 and C43 are PARTIAL.
- 2 `Test policy` rows are still unmet.
- In two reachable cases the raw cache still keeps CPF verbatim (see Binding sources and Coverage).

## Binding sources - AD-003 row verified at d49d892; other rows carried from 68ee2e0

Step 1 is `ui`-only. As in round 1, AD-003 was re-read against the fix because door 9 ("Raw cache redaction") exists only to satisfy it. The fix changed only that row. The indicator, grilling and prototype rows are carried from round 1.

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `.specs/STATE.md` AD-003 ("CPF is never persisted, exposed or logged"), `research/01` §2.3 and §5, and plan door 9 | yes - read in full at d49d892 | none. C39 and door 9 agree with AD-003, and the round-1 conflict is closed for a fresh cache. The real tree's `data/raw/deputados.csv` has the `cpf` header, 0 non-empty values across 7,889 rows, and a manifest `sha256` that equals the file. | Two reachable states keep CPF verbatim under `data/raw/`, and no check covers either. (a) `camara.sync` redacts a cached file only when the manifest does not list it (`etl/src/mandato_etl/sources/camara.py:121-126`). A `data/raw/` built at `b37e34d..68ee2e0`, or a copy restored by hand over a listed entry, stays unredacted until `--refresh`, and the manifest hash then disagrees with the file (probe B below). (b) A `.part` left by a hard kill during the `deputados.csv` download holds the raw bytes, and no later cached run removes it (probe E; `camara.py:93-101`). |
| `research/02` decision 9, plan AC 19 | carried from 68ee2e0 | none | - |
| `research/02` decisions 3 and 8, "Votações incluídas" | carried from 68ee2e0 | none | - |
| prototype `etl/build.py` (tag `prototype-2026-09`) | carried from 68ee2e0 | none | - |

This is outside `data/raw/` and outside the gate, but a human has to decide it. Plan Open question 1 has a person keep `etl/inputs/tse/consulta_cand_2026_BRASIL.csv` on disk, and that file carries `NR_CPF_CANDIDATO` for every candidate. The ETL never writes it (the TSE allowlist excludes the column, C28), but AD-003 raises the same question round 1 raised for `deputados.csv`. The raw cache also keeps `siglaSexo`, `dataFalecimento`, `ufNascimento` and `municipioNascimento` for 7,889 people. None of those is needed for the match key. AGENTS.md says "Nenhum dado pessoal além de nome, partido, UF, foto oficial e atos do mandato", and it is the human's call whether that line covers a local, gitignored cache.

### Adversarial probes of the redaction (worktree at d49d892; scripts drive `camara.sync` and `camara._download` directly)

| Probe | Result |
| --- | --- |
| A - `deputados.csv` placed by hand, no manifest entry | redacted, and the manifest `sha256` equals the stored file. `downloadedAt` is `2026-09-27T03:49:23Z` (the time of the redaction rewrite), but the file's original mtime was `2026-08-28T03:49:23Z`. `_redact` replaces the file before `_entry` reads `st_mtime` (`camara.py:125-126`), which defeats the comment at `camara.py:123` and AD-005's collection timestamp. |
| B - manifest already lists `deputados.csv`; a verbatim copy is placed over it | **CPF kept** in `deputados.csv`, and the manifest `sha256` no longer matches the stored file |
| C - header spelled `"CPF"` | **CPF kept.** The column match is exact (`camara.py:82`), so redaction fails open, whereas `readers.read` fails closed on a missing column (`readers.py:15-17`) |
| D - `_redact` raises after writing `.redacted` | no leftover file (`camara.py:102-105` removes `.part` and `.redacted`) |
| E - stale `deputados.csv.part` from a killed process, then a cached run | **the `.part` survives** with CPF |
| F - connection cut after the CPF bytes reach `.part` | no leftover file |

## Checks - proofs verified at d49d892; citations refreshed for touched files; test_roll_calls.py, test_publish.py, test_packaging.py citations carried from 68ee2e0

Proof run for every row: `uv run --directory etl pytest -v tests/` from the repo root, exit 0, 79 passed in 6.35s at `d49d892`. Every named test and each parametrized id appears individually as `PASSED`.

`rg -n "^def test_(raw_cache_never_keeps_cpf|valid_vote_set_table|birth_date_and_uf_are_part_of_the_key|deputies_sharing_a_key_are_ambiguous|source_missing_column_exits_1|quiet_silences_progress|const_violation_is_rejected_by_both)" etl/tests` finds:
- `test_download.py:111`
- `test_indicators.py:118`
- `test_tse.py:41`
- `test_tse.py:52`
- `test_cli.py:39`
- `test_cli.py:52`
- `test_schema.py:46`

The test diff `68ee2e0..d49d892` removes no assertion:
- The only removed lines are imports, the old `FILES_2023` derivation (now a literal, which is stronger) and `test_deputies.py`'s "the source carries CPF" premise. That premise moved from the raw file to the served route (`test_deputies.py:27-28`), with the out/ assertions unchanged.
- `conftest.py` is untouched.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | 7 files, a 7-entry manifest with exact keys, and hashes that match the disk | `test_download.py::test_empty_cache_downloads_every_source_and_writes_manifest` PASSED | `etl/tests/test_download.py:27` - `sorted(... fake.bulk_requests()) == sorted(FILES_2023)`, with `FILES_2023` now a literal (`:13-21`); `:31` - `set(entry) == {"file", "sourceUrl", "sha256", "bytes", "downloadedAt"}`; `:33` - `entry["sha256"] == hashlib.sha256(content).hexdigest()`; `:34` - `entry["bytes"] == len(content)` | PASS |
| C2 | default years `[2023..2026]` | `test_cli.py::test_default_years_run_from_2023_to_current_year` PASSED | `etl/tests/test_cli.py:12` - `["years"] == [2023, 2024, 2025, 2026]`; `:14` - `requested == {"2023.csv", ...}` | PASS |
| C3 | 0 requests when cached; 7 again with `--refresh` | `::test_cached_files_issue_no_request`, `::test_refresh_downloads_again` PASSED | `etl/tests/test_download.py:44` - `len(fake.bulk_requests()) == before == 7`; `:51` - `== 14` | PASS |
| C4 | 404, timeout and exhausted 503 give exit 2, the URL on stderr, and no `.part` or target file | `test_cli.py -k test_failed_download_exits_2_with_url_and_no_partial_file` - 3 ids PASSED | `etl/tests/test_cli.py:22` - `build(fake) == 2`; `:23` - URL `in ...err`; `:24` - `not list(fake.raw.glob("*.part"))`; `:25` - target absent | PASS |
| C5 | sleeps `[1, 2, 4]` / `[1]` / none on 404 | `test_download.py -k test_retry_schedule` - 5 ids PASSED | `etl/tests/test_download.py:75` - `fake.sleeps == sleeps` over the literals `:57-61`; `:76` - request count | PASS |
| C6 | User-Agent on every request | `::test_every_request_sends_user_agent` PASSED | `etl/tests/test_download.py:84` - `{ua ...} == {expected}` with the literal at `:82`; `:83` - both `arquivos` and `api` | PASS |
| C7 | at most 4 in flight, 10 cache files, 0 requests on the second run | `::test_history_concurrency_is_capped_at_4`, `::test_history_is_cached_per_deputy` PASSED | `etl/tests/test_download.py:97` - `fake.max_in_flight == 4`; `:98` - 10 files; `:107-108` - `first == 10`, `len(fake.requests) == first` | PASS |
| C8 | legislature-57 voters only | `test_deputies.py::test_deputy_set_is_every_legislature_57_voter` PASSED | `etl/tests/test_deputies.py:10` - `== [101, 102, 103]`; `:11` - `104.json` absent | PASS |
| C9 | allowlisted keys only; CPF never in out/ | `::test_readers_keep_only_allowlisted_columns`, `::test_cpf_never_reaches_output` PASSED | `etl/tests/test_deputies.py:15` - no allowlist has `cpf`; `:22` - `list(rows[0]) == readers.ALLOWLIST[kind]`; `:31` - `'"cpf"' not in text.casefold()`; `:32` - `CPF_IN_DEPUTADOS not in text` (the premise now reads the served source, `:27-28`) | PASS |
| C10 | half-open periods | `::test_exercise_periods_are_half_open_and_close_at_build_time` PASSED | `etl/tests/test_deputies.py:47` - `periods == expected` (`:42-45`); `:48` - `True`; `:49` - `in_periods("2024-03-01T00:00:00", periods) is False`; `:52` - the built file | PASS |
| C11 | `inExercise` follows the API | `::test_in_exercise_follows_api_list` PASSED | `etl/tests/test_deputies.py:57` - `flags == {101: True, 102: True, 103: False}` | PASS |
| C12 | profile from the latest vote | `::test_profile_fields_come_from_latest_vote` PASSED | `etl/tests/test_deputies.py:80` - `== ("PL", "RJ", "Novo Nome", "https://x/new.jpg")` | PASS |
| C13 | duplicate vote keeps the latest | `test_roll_calls.py::test_duplicate_vote_keeps_latest` PASSED | carried from 68ee2e0: `etl/tests/test_roll_calls.py:30-32` - `len == 4`, `["Sim"]`, `tallies == {"yes": 3, "no": 0, "others": 1}` | PASS |
| C14 | pre-legislature and vote-less roll calls excluded | `::test_roll_calls_before_legislature_or_without_votes_are_excluded` PASSED | carried from 68ee2e0: `etl/tests/test_roll_calls.py:37-39` | PASS |
| C15 | roll-call record fields | `::test_roll_call_record_fields` PASSED | carried from 68ee2e0: `etl/tests/test_roll_calls.py:44,49,50,52-56,58,61,62` | PASS |
| C16 | deputy votes entries and order | `::test_deputy_votes_entries_and_order` PASSED | carried from 68ee2e0: `etl/tests/test_roll_calls.py:68-69` | PASS |
| C17 | `governmentOrientation` casefold | `::test_government_orientation_matches_governo_casefolded` PASSED | carried from 68ee2e0: `etl/tests/test_roll_calls.py:90-92` | PASS |
| C18 | participation 3/4, 2/2, 0/0 | `test_indicators.py::test_participation_counts` PASSED | `etl/tests/test_indicators.py:17-21` - `== {101: {"count": 3, "total": 4}, 102: {"count": 2, "total": 2}, 103: {"count": 0, "total": 0}}` | PASS |
| C19 | government alignment 2/3 and 1/1, **with `Artigo 17` excluded** | `::test_government_alignment_counts` PASSED | `etl/tests/test_indicators.py:26` - `got[101] == {"count": 2, "total": 3}`; `:27` - `got[102] == {"count": 1, "total": 1}`. Test and fixture are unchanged since round 1. 102's `Artigo 17` sits at R4 under `Liberado` (`etl/tests/conftest.py:91,141`), so the orientation filter excludes it whatever the vote. F5 survived again at d49d892. | PARTIAL - "Artigo 17 excluded" is still not discriminated |
| C20 | party alignment 2/3 and 0/0 | `::test_party_alignment_counts` PASSED | `etl/tests/test_indicators.py:33` - `got[101] == {"count": 2, "total": 3}`; `:34` - `got[103] == {"count": 0, "total": 0}` | PASS |
| C21 | party-majority table | `-k test_party_majority_table` - 5 ids PASSED | `etl/tests/test_indicators.py:49` - `party_majority(Counter(counts), own) == expected` over the literals `:40-44` | PASS |
| C22 | authorship counts | `::test_authorship_counts` PASSED | `etl/tests/test_indicators.py:54` - the 5 types; `:55` - ids 6001-6005; `:59` - `== (5, 2, 3)` | PASS |
| C23 | zero-total shape | `::test_zero_total_indicator_shape` PASSED | `etl/tests/test_indicators.py:68` - `raw == '{"count": 0, "total": 0}'`; `:70` | PASS |
| C24 | no ratio, percent or pct; `{count, total}` only | `::test_no_ratio_or_percentage_in_output` PASSED | `etl/tests/test_indicators.py:88`, `:91` (18 indicators), `:92` | PASS |
| C25 | exact and accent-only matches; matched = 2 | `test_tse.py::test_matches_on_normalized_name_birth_date_and_uf` PASSED | `etl/tests/test_tse.py:12` - `got[101] == {"office": "DEPUTADO FEDERAL", ...}`; `:14` - `got[102] == {... "SENADOR" ...}`; `:16` - `meta["matched"] == 2` | PASS |
| C26 | TSE file absent or missing: null for all, a warning, exit 0 | `-k test_missing_tse_file_warns_and_exits_0` - 2 ids PASSED | `etl/tests/test_tse.py:23` - `== 0`; `:24` - `"warning" in ...err`; `:25` - `== [None, None, None]` | PASS |
| C27 | ambiguous match is null and listed | `::test_ambiguous_match_is_null_and_listed` PASSED | `etl/tests/test_tse.py:31` - `is None`; `:32` - `["ambiguous"] == [103]` | PASS |
| C28 | no CPF column in the TSE allowlist or the output | `::test_tse_reader_never_reads_cpf_columns` PASSED | `etl/tests/test_tse.py:36` - no `CPF` column; `:38` - `CPF_IN_TSE not in path.read_text()` | PASS |
| C29 | output validates; `validate` exits 0, then 1 | `test_publish.py::test_output_validates_against_schemas`, `::test_validate_command_exit_codes` PASSED | carried from 68ee2e0: `etl/tests/test_publish.py:21,23,27,32,33` | PASS |
| C30 | `meta.json` fields | `::test_meta_fields` PASSED | carried from 68ee2e0: `etl/tests/test_publish.py:38-45` | PASS |
| C31 | failed build keeps the previous output | `::test_failed_build_keeps_previous_output` PASSED | carried from 68ee2e0: `etl/tests/test_publish.py:53,55,56` | PASS |
| C32 | byte-identical builds, ordering | `::test_builds_are_byte_identical`, `::test_output_ordering` PASSED | carried from 68ee2e0: `etl/tests/test_publish.py:82,85,89,102,103-105` | PASS |
| C33 | out-of-range years and an unknown flag: exit 1 and `usage:` | `test_cli.py -k test_year_out_of_range_exits_1` - 4 ids PASSED | `etl/tests/test_cli.py:34` - `cli.main(argv) == 1`; `:35` - `"usage:" in ...err` | PASS |
| C34 | `sourceUrl` values | `test_publish.py::test_source_urls` PASSED | carried from 68ee2e0: `etl/tests/test_publish.py:110,112,115` | PASS |
| C35 | contract layout | `::test_contract_layout` PASSED | carried from 68ee2e0: `etl/tests/test_publish.py:123-124` | PASS |
| C36 | packaging | `test_packaging.py::test_runtime_dependencies_are_empty` PASSED | carried from 68ee2e0: `etl/tests/test_packaging.py:12-18` | PASS |
| C37 | built-in validator agrees with `jsonschema` | `test_schema.py -k test_builtin_validator_agrees_with_jsonschema` - 1 + 8 ids PASSED | `etl/tests/test_schema.py:28` - `first_error(doc, spec) is None`; `:29`; `:37` - `is not None`; `:38` | PASS |
| C38 | invalid output is never published | `test_publish.py::test_invalid_output_is_never_published` PASSED | carried from 68ee2e0: `etl/tests/test_publish.py:71-74` | PASS |
| C39 | raw `deputados.csv` keeps the `cpf` header with empty values; the fixture CPF is in no file under raw; the manifest `sha256` matches | `test_download.py::test_raw_cache_never_keeps_cpf` PASSED | `etl/tests/test_download.py:113` - the source carries the CPF; `:117` - `len(rows) == 4`; `:118` - `[r["cpf"] for r in rows] == ["", "", "", ""]`; `:121` - `CPF_IN_DEPUTADOS.encode() not in path.read_bytes()` for every file under `fake.raw`; `:123` - `entry["sha256"] == hashlib.sha256(...).hexdigest()`. This proves the download path only (fault R1 killed). | PASS |
| C40 | a vote counts toward both alignment totals exactly when it is `Sim`, `Não`, `Abstenção` or `Obstrução`; `Artigo 17` and empty give 0/0 | `test_indicators.py -k test_valid_vote_set_table` - 6 ids PASSED | `etl/tests/test_indicators.py:120` - `government == {"count": expected, "total": expected}`; `:121` - `party == {...}` over `:115`. The four valid values are discriminated. The `Artigo 17` and empty rows are not, because the helper sets the orientation equal to the vote (`:100`), so the orientation filter (`compute.py:226`) rejects an `Artigo 17` or empty orientation, and `party_counts` never counts the colleague's invalid vote (`compute.py:194`), so the majority is null. The vote-side filter at `compute.py:225` is never the deciding line. F5 survived. | PARTIAL - the "exactly when" exclusion is not discriminated for `Artigo 17` or empty |
| C41 | another birth date or another UF matches nobody | `test_tse.py::test_birth_date_and_uf_are_part_of_the_key` PASSED | `etl/tests/test_tse.py:47` - `["candidacy2026"] is None`; `:49` - `(meta["matched"], meta["ambiguous"]) == (0, [])`, over the rows at `:44`. F3 killed. | PASS |
| C42 | two deputies sharing a key are both ambiguous | `::test_deputies_sharing_a_key_are_ambiguous` PASSED | `etl/tests/test_tse.py:56` - `matched == {}`; `:57` - `ambiguous == ["7", "8"]` | PASS |
| C43 | a missing allowlisted column gives exit 1, names the file **and the column** on stderr, and keeps the previous output | `test_cli.py::test_source_missing_column_exits_1` PASSED | `etl/tests/test_cli.py:45` - `build(fake, "--refresh") == 1`; `:47` - `"votacoes-2023.csv" in err`; `:49` - out/ byte-identical. The column assertion at `:48` (`"siglaOrgao" in err`) is a substring of the renamed header `siglaOrgaoX` (`:44`), so a message that prints the header found, not the missing column, passes. That mutant survived. | PARTIAL - "the column on stderr" is not discriminated |
| C44 | `--quiet` gives an empty stderr; without it, one line per stage starting with `sources: 2023-2023` | `::test_quiet_silences_progress` PASSED | `etl/tests/test_cli.py:55` - `== 0`; `:56` - `capsys.readouterr().err == ""`; `:59` - `lines[0] == "sources: 2023-2023"`; `:60` - `len(lines) >= 4` (see Precision gaps) | PASS |
| C45 | `schema_version: 2` is rejected by both validators | `test_schema.py::test_const_violation_is_rejected_by_both` PASSED | `etl/tests/test_schema.py:49` - `schema.first_error(doc, spec) is not None`; `:50` - `not ...is_valid(doc)` | PASS |

## Coverage - rows touched by the fix verified at d49d892; the rest carried from 68ee2e0

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| vote values in alignment (6 values x 2 indicators), verified at d49d892 | Câmara vote values, AC 18/19, the assumption "Artigo 17", `compute.py:15,194,225-231` | `Sim`, `Não`, `Abstenção`, `Obstrução` counted in both -> C40 (`test_indicators.py:120-121`). Dropping any of them from `VALID_VOTES` turns its 1/1 into 0/0, so the round-1 `Obstrução` gap is closed. `Artigo 17` and empty excluded by the vote filter (`compute.py:225`): the C40 rows are filtered upstream (orientation = vote, and the party count holds valid votes only); in the C19 fixture 102's `Artigo 17` is under `Liberado` and 101's empty vote is at R3, which has no government bench; C20 never asserts 102's `partyAlignment`, although 102's R4 `Artigo 17` has a PT majority (101 `Obstrução`, `conftest.py:87,91`). F5 survived. | `Artigo 17` excluded from governmentAlignment and partyAlignment; empty excluded from both |
| vote values in participation (6), new row, verified at d49d892 | `compute.py:215` (truthiness of the vote) and the fixture votes `conftest.py:87-93` | `Sim` (101 R1) · `Não` (101 R2) · `Obstrução` (101 R4) · `Artigo 17` (102 R4) · empty (101 R3) -> C18 (`test_indicators.py:17-21`). **`Abstenção`**: the fixture's only `Abstenção` is 101 at CZ, a CCJC roll call that participation never reads. The checks' row cites C18 for a value C18's fixture never counts. The code has no value-specific branch, so the risk is low, but the table's proof does not touch the value. | `Abstenção` in participation |
| TSE row kinds (7), verified at d49d892 | plan door 5, AC 24/26, `tse.py:22-48` | exact, accent-only, non-deputy -> C25 · ambiguous -> C27 · other birth date and other UF -> C41 (`test_tse.py:44,47,49`). Both rows are in one build, so dropping either key component alone makes one row match. F3 (name only) was killed. · key shared by two deputies -> C42 (`test_tse.py:56-57`) | - |
| `mandato-etl build` cause -> exit code (5), verified at d49d892 | `cli.py:52-78` | success 0 -> C26 · usage 1 -> C33 · schema 1 -> C38 · `SourceLayoutError` 1 -> C43 (`test_cli.py:45`) · download 2 -> C4, C31 | - |
| `mandato-etl build` flags (5), verified at d49d892 | plan Assumptions plus `cli.py:36-43` | `--years` C2/C33 · `--refresh` C3 · `--tse-csv` C25/C26 · `--out` C31 · `--quiet` C44 (`test_cli.py:55-56`) | - |
| one-way doors (9), verified at d49d892 | plan `Landing` | contract layout C35 · indicator shape C23/C24 · allowlist C9 · manifest C1 · match key C25, C41 (F3 killed) · runtime deps C36 · project layout C36 · in-package validator C37, C45 · **raw cache redaction**: download path C39 (fault R1 killed). The hand-placed branch `camara.py:124-125` that the fix created has no test (fault R2 survived: the whole suite passes with it deleted). The cached-file-already-in-manifest path skips redaction entirely (probe B). | raw cache redaction of a hand-placed `deputados.csv` (both without and with a manifest entry) |
| in-package validator keywords (10), verified at d49d892 | door 8, `schema.py:13-17` | type, required, additionalProperties, enum, pattern, minimum, type-list null, items -> C37 · `$ref` and `properties` on every valid document -> C37 · `const` reject path -> C45 (`test_schema.py:49-50`) | - |
| bulk source files per run (7) | carried from 68ee2e0; the expected list is now a literal (`test_download.py:13-21`) | all 7 -> C1 | - |
| HTTP outcomes (6) | carried from 68ee2e0 (`camara._get` unchanged) | 2xx, 404, timeout, 429, 503, 500 | - |
| `mandato-etl validate` exit codes (2) | carried from 68ee2e0 | 0, 1 -> C29 | - |
| government orientation values (4) | carried from 68ee2e0 | `Sim`, `Não`, absent -> C17 · `Liberado` -> C19 | - |
| party majority cases (4) | carried from 68ee2e0 | C21 | - (precision gap, below) |
| history transitions (4) | carried from 68ee2e0 | C10 | - |
| proposition types (9) | carried from 68ee2e0 | C22 | - |
| output file kinds (5) | carried from 68ee2e0 | C29, C35 | - |
| entities in `Relations` (9) | carried from 68ee2e0 | C1, C8, C10, C13, C15, C17, C22, C25 | - |
| startup config: output root (2 assemblies) | carried from 68ee2e0 (`cli.py:14-16` unchanged) | CLI and test harness | - |

Round-1 unproven members, by status:

| Round-1 member | Status at d49d892 |
| --- | --- |
| `SourceLayoutError` -> 1 | proven (C43) |
| `--quiet` | proven (C44) |
| `Obstrução` as a valid alignment vote | proven (C40) |
| TSE key birth date and UF | proven (C41, F3 killed) |
| shared-key ambiguity | proven (C42) |
| match-key door 5 | proven |
| `const` reject path | proven (C45) |
| `Artigo 17` excluded from governmentAlignment | **still open** (F5 survived) |

New unproven members: `Artigo 17` and empty in partyAlignment and in governmentAlignment, `Abstenção` in participation, and the hand-placed redaction branch.

## Test policy rows - verified at d49d892 (row 3 carried from 68ee2e0)

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary (CLI, HTTP) | `cli.py`, `sources/camara.py` | boundary: C4, C26, C33, C38, C43, C44 · own layer: C3, C5, C7, C39 | no. `cli.py` is now met: `SourceLayoutError` is asserted at `test_cli.py:45` and `--quiet` at `:56`. `camara.py` is not: the fix added a redaction decision with 3 reachable rows (downloaded file, hand-placed file without a manifest entry, cached file with a manifest entry), and only the first has an asserted case (C39). R2 survived. |
| Decides, not reached across a boundary | `compute.py`, `sources/tse.py`, `readers.py` | own layer: C9, C10, C18-C23, C25, C27, C40-C42 | no. `tse.py` is now met (C41, C42; F3 killed), and `readers.py` is met. In `compute.py` the alignment filter still has no asserted case for "`Artigo 17` or empty vote under a valid orientation" or "under an existing party majority" (F5 survived; C40's rows are decided by the orientation and party-count filters, not the vote filter). |
| Instrumentation, pass-throughs | `publish.dumps`, `schema.kind_of`, the `cli.build` wiring | none of their own | yes, carried from 68ee2e0 (the fix did not touch these) |

## Faults injected - verified at d49d892

The faults ran in `git worktree add --detach /tmp/claude-1000/etl-verify-r2 HEAD` at `d49d892`, where the baseline was 79 passed. Each fault was applied alone, the full suite ran, and the change was reverted with `git checkout -- etl`. The worktree was removed with `git worktree remove --force`. The real tree's `git status --porcelain` was empty before and after (the saved baseline and the after-state compared identical). `git stash` was not used.

| Mutation | Location | Killed |
| --- | --- | --- |
| F3: the TSE match key reduced to the name (`(normalize_name(civil_name), "", "")`) | `etl/src/mandato_etl/sources/tse.py:23` | yes - `test_birth_date_and_uf_are_part_of_the_key` fails (C41); 1 failed, 78 passed |
| F5: an `Artigo 17` vote under a valid government orientation increments `gov_total` (inserted before the `if vote in VALID_VOTES` line) | `etl/src/mandato_etl/compute.py:225` | no - survived, 79 of 79 pass |
| R1: the download path skips `_redact` (delete `if dest.name in REDACT: _redact(part, ...)`) | `etl/src/mandato_etl/sources/camara.py:100-101` | yes - `test_raw_cache_never_keeps_cpf` fails (C39) |
| R2: the hand-placed branch skips `_redact` (delete `if name in REDACT: _redact(dest, ...)`) | `etl/src/mandato_etl/sources/camara.py:124-125` | no - survived, 79 of 79 pass |
| C43: the layout error prints the header it found (`unexpected header {';'.join(header)}`) instead of the missing columns | `etl/src/mandato_etl/readers.py:66` | no - survived, 79 of 79 pass (`"siglaOrgao"` is a substring of `siglaOrgaoX`) |

## Gate - verified at d49d892

`uv run --directory etl pytest -v tests/` at `d49d892` - 79 passed, 0 failed (6.35s)

## Precision gaps

These are findings about how the checks are worded, not about unproven behaviour.

- **AC 19 / C21, "strict majority" (not worse).** The wording still does not say whether it means more than 50% or a plurality without a tie. The code computes a plurality (`compute.py:62-70`).
- **C32 / AC 31, "id ascending" (not worse).** Roll-call ids are compared as strings (`compute.py:206`).
- **C1's derived expected list: closed.** The list is now a literal (`test_download.py:13-21`).
- **C44, "one line per stage".** The test asserts it as `len(lines) >= 4` (`test_cli.py:60`), so the number of stages is not pinned.
- **C44 against AC 25, the Log-format assumption and the TSE warning.** The assumption says `--quiet` "silences everything but errors", and AC 25 requires a warning. `cli.py:106` prints the warning outside `log`, so `--quiet` does not silence it. C44 sidesteps the conflict by always passing `--tse-csv`. Which rule wins is not decided.
- **C39, "occurs in no file under `data/raw/`".** This is proven only for a fresh cache filled by the download path. The claim reads as unconditional, but the hand-placed and already-in-manifest states (probes B and E) are outside it.

Ranked gaps:

1. **F5 still survives.** Excluding `Artigo 17` and empty votes from both alignment totals is unproven, and C19 and C40 are PARTIAL. The C40 helper sets the orientation equal to the vote (`etl/tests/test_indicators.py:100`), so the vote-side filter at `etl/src/mandato_etl/compute.py:225` is never the deciding line. A discriminating case needs an `Artigo 17` vote and an empty vote under orientation `Sim`, with a colleague voting `Sim`, plus an assertion on 102's `partyAlignment` in C20.
2. **CPF stays in the raw cache when the manifest already lists `deputados.csv`.** `etl/src/mandato_etl/sources/camara.py:121-127` skips redaction for a listed entry. Any `data/raw/` built at `b37e34d..68ee2e0` (a CI cache, another machine) and any copy restored by hand keeps CPF verbatim until `--refresh`, and its manifest hash disagrees with the stored file (probe B). This conflicts with AD-003, and no check covers it.
3. **The hand-placed redaction branch has no test.** R2 survived at `etl/src/mandato_etl/sources/camara.py:124-125`, so the `camara.py` Test policy row is unmet.
4. **Hand-placed redaction overwrites `downloadedAt` with the time of the rewrite.** `_redact` runs before `st_mtime` is read (`etl/src/mandato_etl/sources/camara.py:125-126`). In probe A that gave 2026-09-27 against the file's mtime of 2026-08-28. This regression comes from the fix and works against AD-005's collection timestamp.
5. **C43's column assertion cannot fail on a wrong message.** `"siglaOrgao"` is a substring of the injected `siglaOrgaoX` (`etl/tests/test_cli.py:44,48`), and the mutant survived.
6. **Redaction fails open on the header spelling.** The column match at `etl/src/mandato_etl/sources/camara.py:82` is exact, so a `CPF` or ` cpf` header keeps the values (probe C). By contrast, `readers.read` fails closed on a missing column.
7. **A `deputados.csv.part` left by a hard kill keeps CPF, and no later cached run removes it** (probe E; `etl/src/mandato_etl/sources/camara.py:93-101`). Exceptions do clean up (probes D and F). This residual is inherent to door 9's approved design, which writes the raw bytes to `.part` before rewriting them.
8. **The participation Coverage row cites C18 for `Abstenção`,** but the fixture's only `Abstenção` is on a committee roll call (`etl/tests/conftest.py:88`).
9. **For a human decision, outside the gate:**
   - The hand-placed TSE input at `etl/inputs/tse/` carries `NR_CPF_CANDIDATO` for every candidate (plan Open question 1; AD-003).
   - The raw cache keeps sex, death date and birthplace for 7,889 people, which the match key does not need (AGENTS.md "Nenhum dado pessoal além de ...").
