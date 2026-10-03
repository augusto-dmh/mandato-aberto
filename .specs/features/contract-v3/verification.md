# contract-v3 verification

**Verdict**: PASS
**Profile**: standard
**Diff range**: bc0a4a8..f079ce999f15a522aea8d8bb8dadcce9878dedf1
**Round**: 3 - scoped
**Verifier**: independent sub-agent (author != verifier)

The fix closes the gap round 2 named. In round 2, mutants F6-F9 survived on the proposition fields `summary` and `status`. The new C59 (`etl/tests/test_v3_contract.py:258-265`) now kills each of them, and it also kills F10, a fifth fault that swaps the two sources. All 12 members of the recomputed set "proposition field sources" now have a proof. The `contract_v3.py` Test policy row is met. The full suite passes at `f079ce9` (240 passed) and C6's command exits 0. C59 is a legitimate addition, not a weakened or retrofitted check. It adds a claim and changes no existing one: the fix's `checks.md` diff is +5/-0. Each value it names follows from the plan's Flow and the v3 schema. P4-P6 below are precision notes for the maintainer and block nothing.

Scope of this round: the fix's diff (`f69ab08..f079ce9`, test-only: `etl/tests/test_v3_contract.py` +10/-0 and `.specs/features/contract-v3/checks.md` +5/-0, which adds C59, the Coverage row "proposition field sources (12)" and a Handoff line). It also covers every verdict that was not PASS in round 2: the coverage set "proposition field sources", the `contract_v3.py` Test policy row and faults F6-F9. Everything else is carried, marked "carried from f73b12c" or "carried from c0ccf18" for the round it was last verified in.

## Binding sources

Carried from f73b12c. The plan marks no binding design source (profile `standard`, not `ui`), so step 1 does not run. The fix touched no interface.

## C59 legitimacy

Verified at f079ce9. C59 claims that a proposition with a bulk `proposicoes` row takes `summary` from `ementa` and `status` from `ultimoStatus_descricaoSituacao`. It also claims that a proposition known only through a roll-call link takes `summary` from `proposicao_ementa`, with `status` null. Judged legitimate on four grounds:

- **Implied by the plan and the schema.** Flow hop 2 (`.specs/features/contract-v3/plan.md:18`) reads "the same six yearly bulk files", which include `proposicoes`. Flow hop 3 (`plan.md:19`) extends the `votacoesProposicoes` allowlist, and the base allowlist already carries `proposicao_ementa` (`etl/tests/test_v3_contract.py:237`). `etl/schema/v3/propositions.schema.json` requires `summary` and `status` as `string | null`. The builder's own handoff (`.specs/features/contract-v3/checks.md:303`, "`presentedAt` and `status` null when it predates the bulk files") already stated the null link status before this fix. The plan names no column for either field, so C59 writes down the only reading these sources support. It is the reading recorded as P3 in round 2.
- **Additive.** `git diff f69ab08..f079ce9 -- .specs/features/contract-v3/checks.md` shows 5 insertions and 0 deletions. No existing claim or proof line changed. No existing test changed either: the test file diff is +10/-0.
- **Discriminating, not shaped to the code.** For 8002 the bulk `ementa` is "Ementa 8002" (`etl/tests/v3data.py:88`), but its link `proposicao_ementa` is "Altera a Constituição." (`etl/tests/v3data.py:205`). So the assertion tells the two sources apart, and F10 proves it. Bulk status "Aguardando Parecer" (`etl/tests/v3data.py:89`) differs from the link branch's `None`.
- **Recorded as an orchestrator addition.** `checks.md:218` says "(added by the orchestrator after verification round 2)", and `checks.md:307` gives the Handoff line.

## Checks

Proofs verified at f079ce9. All proofs were re-run at `f079ce9` in one invocation, `uv run --directory etl pytest -v`: exit 0, 240 passed, 0 failed, 0 skipped, no ERROR. All 64 `Proof:` lines in `checks.md` were matched against that output, including the 16 `-k` forms by prefix. They resolve to 125 PASSED result lines, with no FAILED, SKIPPED or ERROR line among them. etl-camara's `tests/test_publish.py::test_contract_layout` also PASSED. C6's command was re-run and exited 0. Evidence citations are carried from c0ccf18, except where the fix touched `etl/tests/test_v3_contract.py`. That file was re-read: lines up to 255 did not move, the new test sits at 258-265, and lines after it shifted by 10, so C52's Senate citations are refreshed. `etl/src` was not touched, so code citations are unchanged.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | v2 build byte-identical to `bc0a4a8` (plain and secret) | `test_v2_build_matches_base_commit[plain,secret]` PASSED at f079ce9; the base-code run is carried from f73b12c | `etl/tests/test_v2_frozen.py:40` - `assert build_variant(fake, variant) == json.loads(GOLDEN.read_text())[variant]` | PASS |
| C2 | v3 default out `<data>/v3/camara`, v2 bytes+mtime untouched; `--out X` writes only X | `test_v3_writes_only_its_directory`, `test_v3_out_flag_overrides_default` PASSED | `etl/tests/test_v3_cli.py:26-28`, `:35-36` (carried from f73b12c) | PASS |
| C3 | candidacy flags under v3: exit 1, usage, 0 requests, no dir | `test_v3_rejects_candidacy_flags[3 cases]` PASSED | `etl/tests/test_v3_cli.py:43-46` (carried) | PASS |
| C4 | `--contract 4`, `x`, `--house senado`: exit 1, usage, 0 requests | `test_bad_contract_or_house_exits_1[3 cases]` PASSED | `etl/tests/test_v3_cli.py:54-56` (carried) | PASS |
| C5 | validate picks the schema set by version; v3 dir labelled 2 fails; 4 is unsupported | `test_validate_picks_schema_by_version` PASSED | `etl/tests/test_v3_cli.py:64-65`, `:70`, `:72-73` (carried) | PASS |
| C6 | diff touches no `site/`, `design/`, `publish.yml` | `test -z "$(git diff --name-only bc0a4a8..HEAD -- site design .github/workflows/publish.yml)"` exit 0 at f079ce9 | `.specs/features/contract-v3/checks.md:41` - command run, empty name list | PASS |
| C7 | `legislature_of` edges, raises outside; constants equal the recorded API | `test_legislature_of[4 cases]`, `test_legislature_of_outside_raises`, `test_legislature_dates_match_recorded_api` PASSED | `etl/tests/test_v3_legislature.py:23`, `:27-28`, `:35` (carried) | PASS |
| C8 | roll call `900-1` on `2031-02-05`: exit 1 naming both, previous output kept | `test_roll_call_outside_known_legislature_exits_1` PASSED | `etl/tests/test_v3_legislature.py:43-46` (carried) | PASS |
| C9 | `meta.legislatures` [57] at 02:59:59Z, [57,58] at 03:00:00Z | `test_meta_lists_started_legislatures[before-58,at-58]` PASSED | `etl/tests/test_v3_legislature.py:59-65` (carried) | PASS |
| C10 | members 301/302/303 with mandates [57,58]/[57]/[58] | `test_one_mandate_per_listed_or_voting_deputy` PASSED | `etl/tests/test_v3_legislature.py:78`, `:80` (carried) | PASS |
| C11 | 301's periods per legislature; the 2019 entry excluded | `test_exercise_periods_per_legislature` PASSED | `etl/tests/test_v3_legislature.py:85-86` (carried) | PASS |
| C12 | 301 participation 1/1 and authoredCount 1 in each mandate | `test_indicators_use_only_their_legislature` PASSED | `etl/tests/test_v3_legislature.py:92-93` (carried) | PASS |
| C13 | 302's 57th mandate all zero | `test_mandate_without_roll_calls_is_all_zero` PASSED | `etl/tests/test_v3_legislature.py:99-101` (carried) | PASS |
| C14 | member fields from latest record; mandate party per legislature | `test_member_fields_come_from_latest_record` PASSED | `etl/tests/test_v3_legislature.py:106-113` (carried) | PASS |
| C15 | API files cached once with manifest, 0 requests on rebuild, refresh refetches, no email | `test_v3_api_files_are_cached_and_listed`, `test_v3_refresh_fetches_again` PASSED | `etl/tests/test_v3_cli.py:80-105` (carried) | PASS |
| C16 | history 404: exit 2, URL on stderr | `test_v3_download_failure_exits_2` PASSED | `etl/tests/test_v3_cli.py:111-112` (carried) | PASS |
| C17 | `ballot_of` table | `test_ballot_of[5 cases]` PASSED | `etl/tests/test_v3_classify.py:28` (carried) | PASS |
| C18 | roll-calls.json holds K1-K7, K9; excludes K0, K8 | `test_roll_call_inclusion` PASSED | `etl/tests/test_v3_classify.py:40` (carried) | PASS |
| C19 | `normalise` and first match wins | `test_normalise_and_first_match_wins` PASSED | `etl/tests/test_v3_classify.py:44`, `:47` (carried) | PASS |
| C20 | K6 unclassified/null; coverage unclassified 1 | `test_unclassified_is_counted` PASSED | `etl/tests/test_v3_classify.py:52`, `:54` (carried) | PASS |
| C21 | 12 official examples classify as their rule row | `test_official_examples[12 cases]` PASSED | `etl/tests/test_v3_classify.py:58-70` (carried) | PASS |
| C22 | camara.02, .05, .10 unit cases | `test_rule_without_official_example[3 cases]` PASSED | `etl/tests/test_v3_classify.py:80-88` (carried) | PASS |
| C23 | rules file = the 11 applied rules in order; version 1 | `test_rules_file_is_the_applied_ruleset` PASSED | `etl/tests/test_v3_classify.py:93-98` (carried) | PASS |
| C24 | each description names its term, no banned word | `test_rule_descriptions_name_the_official_term` PASSED | `etl/tests/test_v3_classify.py:118`, `:120` (carried) | PASS |
| C25 | fixture build classifies K1-K6 (kind, rule, ballot) | `test_build_classifies_the_fixture` PASSED | `etl/tests/test_v3_classify.py:125-131` (carried) | PASS |
| C26 | symbolic: null tallies, no file; K1 has one | `test_symbolic_has_null_tallies_and_no_file` PASSED | `etl/tests/test_v3_classify.py:136-138` (carried) | PASS |
| C27 | K7 tallies 12/5/2; K5 null tallies with `votes: []` | `test_secret_tallies` PASSED | `etl/tests/test_v3_classify.py:142-144` (carried) | PASS |
| C28 | `position_of` map; built vote keeps official | `test_position_of[7 cases]`, `test_vote_keeps_official_value` PASSED | `etl/tests/test_v3_indicators.py:22`, `:43-49` (carried) | PASS |
| C29 | unknown vote / orientation: exit 1, named, output kept | `test_unknown_value_stops_the_build[vote,orientation]` PASSED | `etl/tests/test_v3_indicators.py:64-67` (carried) | PASS |
| C30 | orientation map; empty orientation dropped; government position | `test_orientation_of[5 cases]`, `test_orientation_of_unknown_raises`, `test_orientations_and_government_position` PASSED | `etl/tests/test_v3_indicators.py:75`, `:84-88` (carried) | PASS |
| C31 | participation.all 4/5, 4/4, 5/5 | `test_participation_all` PASSED | `etl/tests/test_v3_indicators.py:100` (carried) | PASS |
| C32 | participation.merit 2/3, 2/2, 3/3 | `test_participation_merit` PASSED | `etl/tests/test_v3_indicators.py:104` (carried) | PASS |
| C33 | governmentAlignment per member and basis | `test_government_alignment` PASSED | `etl/tests/test_v3_indicators.py:110` (carried) | PASS |
| C34 | partyAlignment per member and basis | `test_party_alignment` PASSED | `etl/tests/test_v3_indicators.py:116` (carried) | PASS |
| C35 | `party_majority` cases | `test_party_majority[5 cases]` PASSED | `etl/tests/test_v3_indicators.py:122-131` (carried) | PASS |
| C36 | symbolicMerit 1/0/1, unchanged by a symbolic procedural | `test_symbolic_merit` PASSED | `etl/tests/test_v3_indicators.py:137-142` (carried) | PASS |
| C37 | proposition counts 5/2/3 and 1/0/0 | `test_proposition_counts` PASSED | `etl/tests/test_v3_indicators.py:150-151` (carried) | PASS |
| C38 | committee C1 changes no indicator | `test_committee_vote_changes_no_indicator` PASSED | `etl/tests/test_v3_indicators.py:166` (carried) | PASS |
| C39 | no percentage keys; bases exactly count+total | `test_no_percentage_keys` PASSED | `etl/tests/test_v3_indicators.py:177`, `:192` (carried) | PASS |
| C40 | every file of 3 datasets passes both validators over 7 kinds | `test_every_file_passes_its_schema` PASSED | `etl/tests/test_v3_contract.py:36-45` (carried; above the fix) | PASS |
| C41 | `http://` photoUrl: exit 1 naming file and field, output kept | `test_schema_failure_keeps_previous_output` PASSED | `etl/tests/test_v3_contract.py:55-58` (carried) | PASS |
| C42 | meta exact keys, coverage row, 14 sources equal to the manifest | `test_meta_shape_and_coverage` PASSED | `etl/tests/test_v3_contract.py:63-79` (carried) | PASS |
| C43 | sourceUrl patterns | `test_source_urls` PASSED | `etl/tests/test_v3_contract.py:84,86,88` (carried) | PASS |
| C44 | house camara everywhere; unique (house, id) | `test_house_and_unique_identity` PASSED | `etl/tests/test_v3_contract.py:94-100` (carried) | PASS |
| C45 | no cpf key, no fixture CPF bytes, no cpf allowlist column | `test_no_cpf_anywhere` PASSED | `etl/tests/test_v3_contract.py:117-120` (carried) | PASS |
| C46 | deterministic bytes; member, roll-call, proposition and vote ordering | `test_build_is_deterministic_and_ordered` PASSED | `etl/tests/test_v3_contract.py:128-142` (carried; see P1) | PASS |
| C47 | log line per legislature; `--quiet` silent | `test_log_line_per_legislature` PASSED | `etl/tests/test_v3_contract.py:149`, `:151` (carried) | PASS |
| C48 | exact v3 layout; exactly 7 schema files | `test_v3_layout` PASSED | `etl/tests/test_v3_contract.py:160-164` (carried) | PASS |
| C49 | both validators reject 8 corruptions | `test_validators_agree_on_invalid[8 cases]` PASSED | `etl/tests/test_v3_contract.py:175-194` (carried) | PASS |
| C50 | Senate fixture validates; `6923` nominal with the 5 officials | `test_senate_fixture_validates` PASSED | `etl/tests/test_v3_senado.py:20-27` (carried) | PASS |
| C51 | `presidencia` / position `other` named by validate, exit 1 | `test_invalid_senate_value_is_named[house,position]` PASSED | `etl/tests/test_v3_senado.py:46`, `:48` (carried) | PASS |
| C52 | nullable symbolic counts accept null/0, reject -1/"0"; Câmara writes ints; Senate null | `test_symbolic_counts_are_nullable[4 cases]`, `test_symbolic_counts_are_nullable_in_senate_fixture` PASSED | `etl/tests/test_v3_contract.py:202`, `:204`, `:209-210`; Senate `:277,279` `[None]*n`, `:280` validate `== 0` (refreshed at f079ce9: the fix moved these lines by 10) | PASS |
| C53 | sensitive map; LS/LP/LAP fail validate, Licença passes | `test_sensitive_codes_are_generalised_map`, `..._in_validation[4 cases]` PASSED | `etl/tests/test_v3_contract.py:214-231` (carried) | PASS |
| C54 | only 8001 gets a full text, exact document | `test_full_text_for_each_target` PASSED | `etl/tests/test_v3_full_texts.py:19-27` (carried) | PASS |
| C55 | PDF and proposition record cached, listed, 0 requests on rebuild | `test_full_text_sources_are_cached` PASSED | `etl/tests/test_v3_full_texts.py:38-44` (carried) | PASS |
| C56 | opening and presentation descriptions stripped, null when empty | `test_roll_call_doc_carries_descriptions` PASSED | `etl/tests/test_v3_full_texts.py:51-54` (carried) | PASS |
| C57 | pypdf pinned in group, dependencies `[]`, only full_texts imports it | `test_pypdf_is_pinned_outside_runtime_dependencies`, `test_runtime_dependencies_are_empty` PASSED | `etl/tests/test_v3_full_texts.py:60-65`; `etl/tests/test_packaging.py:12` (carried) | PASS |
| C58 | allowlist gains exactly 5 columns, read through `readers.read` | `test_allowlist_additions` PASSED | `etl/tests/test_v3_contract.py:243-246`, `:249`, `:252`, `:255` (carried from c0ccf18; lines unmoved) | PASS |
| C59 | bulk row: `summary` from `ementa`, `status` from `ultimoStatus_descricaoSituacao` (8002); link only: `summary` from `proposicao_ementa`, `status` null (8001) | `test_proposition_summary_and_status_by_source` PASSED at f079ce9; F6-F10 each killed by it | `etl/tests/test_v3_contract.py:262` `(bulk["summary"], bulk["status"]) == ("Ementa 8002", "Aguardando Parecer")`; `:265` `(link_only["summary"], link_only["status"]) == ("Institui o teste.", None)`. 8002 has a bulk row (`etl/tests/v3data.py:221`, columns at `:88-89`); 8001 is only in `INDICATOR_LINKS` (`:204-205`), so it takes the link branch at `etl/src/mandato_etl/contract_v3.py:225-230` | PASS |

## Coverage

All rows are carried from f73b12c except "proposition field sources", which was recomputed at f079ce9 because the fix's authority is that set. The fix added no branch, so no other row changed.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| `ballot` enum (3) | door 5; schema enum `[nominal, secret, symbolic]` | nominal C17, C25 · secret C17, C27 · symbolic C17, C26 | - |
| `kind` enum (4) | door 6; schema enum | final, amendment, procedural C21, C25 · unclassified C20, C25 | - |
| Câmara ruleset v1 (11 rule ids) | plan table vs `rules/camara.json`, 11/11 identical | .01 C19, C21 · .02 C22 · .03 C21, C25 · .04 C21, C25 · .05 C22 · .06 C21 · .07 C21 · .08 C21, C25 · .09 C21, C25 · .10 C22 · .11 C21, C25 | - |
| rule descriptions (11) | same 11 ids | C24, table-driven (`etl/tests/test_v3_classify.py:115`) | - |
| vote `position` enum (7) | door 7; `roll-call.schema.json` `$defs/vote` | all 7 in C28 | - |
| Câmara vote values (5 + empty + unknown) | door 7, `classify.VOTE_POSITIONS` | 5 values + empty C28 · unknown C29 | - |
| orientation `position` enum (5) | door 7; schema `$defs/orientation` | all 5 in C30 | - |
| orientation values (5 + empty + unknown) | door 7 map | 5 values C30 · empty C30 · unknown C29 | - |
| `governmentOrientation` (3 cases) | door 7, AC 26 | C30 `etl/tests/test_v3_indicators.py:84-86` | - |
| `house` enum (2 + outside) | door 3 | camara C44 · senado C50 · presidencia C49, C51 | - |
| sensitive code map (4 entries) | AD-018, door 10, `classify.py:31-34` | LS, LP, LAP C53 · camara `{}` C53 | - |
| schema files (7) | `ls etl/schema/v3` | C40, C48 | - |
| party majority cases (5) | AC 31 | C35 | - |
| indicators per mandate (7) x bases (2) | door 4 | C31-C34, C36, C37 | - |
| legislature date edges (5) | door 4, AC 7 | C7, C8 | - |
| build-date edges (2) | AC 9 | C9 | - |
| mandate sources (2) | AC 10 | C10 | - |
| roll-call inclusion (4) | AC 16 | C18 | - |
| proposition types (8 + other) | AC 33 | C37 | - |
| full-text target cases (4) | door 8 | C54 | - |
| `validate` schema_version (3) | AC 5 | C5 | - |
| build flags under v3 (9) | `cli._parser` | C2, C3, C4, C15, C47 | - |
| v3 build exit codes (3) | Observable | 0 C2 · 1 C3, C4, C8, C29, C41 · 2 C16 | - |
| nullable symbolic fields (2) | door 9 | C52 | - |
| startup config: v3 output root (2) | `etl/src/mandato_etl/cli.py:17`, `:94` | C2 | - |
| proposition field sources (2 sources x 6 fields = 12), verified at f079ce9 | the two branches of `_proposition` (`etl/src/mandato_etl/contract_v3.py:217-231`: bulk row `:220-224`, link `:226-229`) against the fields `etl/schema/v3/propositions.schema.json` requires. Of those, `type`, `number`, `year`, `summary`, `presentedAt` and `status` depend on the branch; `id`, `house`, `sourceUrl` and `authors` do not (C43, C44, C37) | bulk `type`/`number`/`year` C58 `etl/tests/test_v3_contract.py:252` · bulk `summary` C59 `:262` (F7, F10 killed) · bulk `presentedAt` C46 `:138` · bulk `status` C59 `:262` (F8 killed) · link `type`/`number`/`year` C58 `:255` (F5 killed, carried from c0ccf18) · link `summary` C59 `:265` (F6 killed) · link `presentedAt` null C46 `:139` · link `status` null C59 `:265` (F9 killed) | - |

Precision notes. They are findings about the artifacts and block nothing on their own:
- P1 (C46 / AC 41), carried from f73b12c: the sort tie-breakers are not exercised. No fixture name has an accent or repeats, and dates are distinct, so "accent-stripped" and "then `id`" are not discriminated. The code does both (`etl/src/mandato_etl/contract_v3.py:304,320-321,329-330`).
- P2 (AC 29/30), carried from f73b12c: alignments are not restricted to exercise periods. The handoff records this at `.specs/features/contract-v3/checks.md:303`, and the ACs do not require it.
- P3, round 2: closed by C59.
- P4 (new): `checks.md:225`, the author's Coverage row, credits bulk `type` to C46, but C46 (`etl/tests/test_v3_contract.py:128-142`) asserts no `type`. The member is proven by C58 `:252`, so coverage is complete. Only the artifact's citation is wrong.
- P5 (new): for 8002, the bulk row and the link give the same `type`, `number` and `year` (`PEC`, 2, 2024). The bulk row derives these as `numero = prop[-2:]` and `ano = presented[:4]` (`etl/tests/v3data.py:88`, `:221`), and the link row is at `:205`. A mutant that makes the bulk branch read those three fields from the link would survive C58 `:252`. The null and garbage cases are killed. This was not injected, because the round allows at most five faults. It is deduced from the fixture. `summary` and `status` do not share this weakness (F10).
- P6 (new): the summary line `checks.md:6` still says "58 checks", but the file now holds 59 (C1-C59).

## Test policy rows

The `contract_v3.py` row was re-judged at f079ce9. The other rows are carried from f73b12c, because the fix classifies no file they cover.

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary (CLI, HTTP) | `classify.py` | boundary C25, C29 · own layer C17-C22, C28, C30 | yes - one asserted case per row: ballot 5 cases, 11/11 rules, vote map 7 + unknown, orientation map 5 + empty + unknown |
| Decides, reached across a boundary (CLI, HTTP) | `cli.py` | CLI C2-C5, C8, C16, C29, C41, C47 | yes - every exit code and stderr content asserted via `cli.main(argv)` |
| Decides, reached across a boundary (CLI, HTTP) | `sources/camara.py` additions | HTTP to the local FakeCamara C15, C16, C55 | yes - miss, hit, refresh and 404 each asserted across the socket |
| Decides, not reached across a boundary | `contract_v3.py` | own layer through dataset builds C7-C14, C31-C38, C58, C59 | yes - each branch of `_proposition` now has an asserted case for every branch-dependent field (C58, C59, C46); F5-F10 killed |
| Decides (selection) / instrumentation (extraction) | `full_texts.py` | selection C54; extraction covered by consumer | yes - 4 target cases in C54; extracted text asserted at `etl/tests/test_v3_full_texts.py:27` |
| Instrumentation, pass-throughs | `publish.py`, `schema.py`, `readers.py` | covered by consumer | yes - C40, C41, C5, C49 exercise them; allowlist additions asserted at C58 `etl/tests/test_v3_contract.py:243` |

Carried from f73b12c: etl-camara's `test_contract_layout` was edited without being weakened. It is still an exact listing, and it PASSED at f079ce9. The v2 golden and `etl/src` were not touched by this fix.

## Faults injected

F1-F4 are carried from f73b12c, and F5 is carried from c0ccf18. F6-F10 were verified at f079ce9 in a scratch worktree, `git worktree add --detach /tmp/cv3-verify-r3 HEAD`, with its own `uv sync` and no `git stash`. Each fault was applied with `sed` to `etl/src/mandato_etl/contract_v3.py`. `git diff` confirmed a one-line change, the full suite ran, and `git checkout -- .` reverted the fault, with a clean porcelain checked before the next one. The scratch was removed with `git worktree remove --force` and `git worktree prune`, and the directory is gone. The real tree's `git status --porcelain` was empty before and after, and the two outputs are identical under `diff`.

| Mutation | Location | Killed |
| --- | --- | --- |
| F1 `ballot_of`: all-empty records no longer `secret` | `etl/src/mandato_etl/classify.py:56` | yes - carried from f73b12c |
| F2 participation ignores exercise periods | `etl/src/mandato_etl/contract_v3.py:360` | yes - carried from f73b12c |
| F3 next legislature start off by one | `etl/src/mandato_etl/contract_v3.py:47` | yes - carried from f73b12c |
| F4 roll-call schema stops refusing LS/LP/LAP | `etl/schema/v3/roll-call.schema.json:187` | yes - carried from f73b12c |
| F5 link fallback publishes `number`/`year` as `None` | `etl/src/mandato_etl/contract_v3.py:227-228` | yes - carried from c0ccf18 (`test_allowlist_additions`) |
| F6 link fallback publishes `summary` as `None` instead of `proposicao_ementa` | `etl/src/mandato_etl/contract_v3.py:228` | yes - `test_proposition_summary_and_status_by_source` failed; suite 1 failed, 239 passed |
| F7 bulk row publishes `summary` as `None` instead of `ementa` | `etl/src/mandato_etl/contract_v3.py:222` | yes - `test_proposition_summary_and_status_by_source` failed; suite 1 failed, 239 passed |
| F8 bulk row publishes `status` as `None` instead of `ultimoStatus_descricaoSituacao` | `etl/src/mandato_etl/contract_v3.py:223` | yes - `test_proposition_summary_and_status_by_source` failed; suite 1 failed, 239 passed |
| F9 link fallback publishes `status` `"Aguardando Parecer"` instead of `null` | `etl/src/mandato_etl/contract_v3.py:229` | yes - `test_proposition_summary_and_status_by_source` failed; suite 1 failed, 239 passed |
| F10 (new) bulk row takes `summary` from the link's `proposicao_ementa` when a link exists | `etl/src/mandato_etl/contract_v3.py:222` | yes - `test_proposition_summary_and_status_by_source` failed; suite 1 failed, 239 passed |

## Gate

Verified at f079ce9:
`uv run --directory etl pytest -v` - 240 passed, 0 failed (exit 0)
`test -z "$(git diff --name-only bc0a4a8..HEAD -- site design .github/workflows/publish.yml)"` - exit 0
`scripts/check-commit-msg.sh` on each of the 12 commits in `bc0a4a8..f079ce9` - exit 0 for all

Carried from f73b12c:
`PYTHONPATH=<bc0a4a8 etl/src> uv run pytest tests/test_v2_frozen.py` (scratch) - 2 passed: the base code reproduces the golden
