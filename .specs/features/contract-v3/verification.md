# contract-v3 verification

**Verdict**: FAIL
**Profile**: standard
**Diff range**: bc0a4a8..c0ccf1802b84f2882f4521dee474e5cb150378a8
**Round**: 2 - scoped
**Verifier**: independent sub-agent (author != verifier)

The fix works for what round 1 named. C58 now passes, and mutant F5 (the link fallback publishing `number`/`year` as `None`) is killed by `etl/tests/test_v3_contract.py:255`. The verdict stays FAIL because of the round-1 set "proposition field sources". Recomputed from the branches of `_proposition` (`etl/src/mandato_etl/contract_v3.py:217-231`) against the fields that `etl/schema/v3/propositions.schema.json` declares, the set has four members with no proof: `summary` from the bulk row and from the link, `status` from the bulk row, and the `null` status of a link-only proposition. That last one is named in the artifact's own prose (`.specs/features/contract-v3/checks.md:299`, "`presentedAt` and `status` null when it predates the bulk files"). Round 1 credited it to C46 `:139`, but that line asserts only `presentedAt is None`. Each of the four was injected as a fault (F6-F9) and survived the full suite, 239 passed. This is a gap in the checks, not a wrong value in the code: no AC or check names a value for `summary` or `status`, and the code reads the expected columns.

Scope of this round: the fix's diff (`f73b12c..c0ccf18`: `etl/tests/test_v3_contract.py` +5/-1, and a handoff line in `checks.md`) plus every verdict that was not PASS in round 1 (C58, the coverage set "proposition field sources", the `contract_v3.py` Test policy row, fault F5). Everything else is carried from round 1 at `f73b12c` and marked as such.

## Binding sources

Carried from f73b12c. The plan marks no binding design source (profile `standard`, not `ui`), so step 1 does not run. The fix touched no interface.

## Checks

Proofs verified at c0ccf18. All proofs were re-run at `c0ccf18` in one invocation, `uv run --directory etl pytest -v`: exit 0, 239 passed, 0 failed, 0 skipped. Each of the 68 named tests below (including etl-camara's `test_contract_layout`) appears individually as PASSED in that output, with no FAILED, SKIPPED or ERROR line for any of them. Parametrised tests show one line per case. C6's command was re-run and exited 0. Evidence citations are carried from f73b12c, except that the file the fix touched (`etl/tests/test_v3_contract.py`) was re-read: lines at or above 251 did not move, so C52's Senate citations shifted by 4 and C58 is re-cited.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | v2 build byte-identical to `bc0a4a8` (plain and secret) | `test_v2_build_matches_base_commit[plain,secret]` PASSED; the same test also passes with the base commit's `etl/src` (from `git archive bc0a4a8`) first on `PYTHONPATH`, so the base code reproduces the golden | `etl/tests/test_v2_frozen.py:40` - `assert build_variant(fake, variant) == json.loads(GOLDEN.read_text())[variant]` (dict equality, so no file is missing or extra) | PASS |
| C2 | v3 default out `<data>/v3/camara`, v2 bytes+mtime untouched; `--out X` writes only X | `test_v3_writes_only_its_directory`, `test_v3_out_flag_overrides_default` PASSED | `etl/tests/test_v3_cli.py:26` `meta.json` is_file; `:27` `stats(data_dir / "out") == before` (bytes, st_mtime_ns); `:28` `== ["out", "v3"]`; `:35-36` `X/meta.json` exists, `not (tmp_path / "data" / "v3").exists()` | PASS |
| C3 | candidacy flags under v3: exit 1, usage, 0 requests, no dir | `test_v3_rejects_candidacy_flags[3 cases]` PASSED | `etl/tests/test_v3_cli.py:43` `== 1`; `:44` `"usage:" in ...err`; `:45` `fake.requests == []`; `:46` `not out3(fake).exists()` | PASS |
| C4 | `--contract 4`, `x`, `--house senado`: exit 1, usage, 0 requests | `test_bad_contract_or_house_exits_1[3 cases]` PASSED | `etl/tests/test_v3_cli.py:54` `== 1`; `:55` `"usage:" in err`; `:56` `fake.requests == []` | PASS |
| C5 | validate picks the schema set by version; v3 dir labelled 2 fails; 4 is unsupported | `test_validate_picks_schema_by_version` PASSED | `etl/tests/test_v3_cli.py:64-65` both `== 0`; `:70` `== 1` (relabelled 2); `:72-73` `== 1` and `"unsupported schema_version 4" in err` | PASS |
| C6 | diff touches no `site/`, `design/`, `publish.yml` | `test -z "$(git diff --name-only bc0a4a8..HEAD -- site design .github/workflows/publish.yml)"` exit 0 | `.specs/features/contract-v3/checks.md:41` - command run, exit 0, empty name list | PASS |
| C7 | `legislature_of` edges, raises outside; constants equal the recorded API | `test_legislature_of[4 cases]`, `test_legislature_of_outside_raises`, `test_legislature_dates_match_recorded_api` PASSED | `etl/tests/test_v3_legislature.py:23` `legislature_of(day) == expected`; `:27-28` `pytest.raises(ContractError)` for `2031-02-01`; `:35` `(start, end) == (recorded["dataInicio"], recorded["dataFim"])` | PASS |
| C8 | roll call `900-1` on `2031-02-05`: exit 1 naming both, previous output kept | `test_roll_call_outside_known_legislature_exits_1` PASSED | `etl/tests/test_v3_legislature.py:43` `== 1`; `:45` `"900-1" in err and "2031-02-05" in err`; `:46` `snapshot(...) == before` | PASS |
| C9 | `meta.legislatures` [57] at 02:59:59Z, [57,58] at 03:00:00Z, exact entries | `test_meta_lists_started_legislatures[before-58,at-58]` PASSED | `etl/tests/test_v3_legislature.py:65` `listed == [expected[i] for i in ids]` with the literal entries at `:59-64` | PASS |
| C10 | members 301/302/303 with mandates [57,58]/[57]/[58] | `test_one_mandate_per_listed_or_voting_deputy` PASSED | `etl/tests/test_v3_legislature.py:78` `== [301, 302, 303]`; `:80` `by_id == {301: [57, 58], 302: [57], 303: [58]}` | PASS |
| C11 | 301's periods per legislature; the 2019 entry excluded | `test_exercise_periods_per_legislature` PASSED | `etl/tests/test_v3_legislature.py:85` 57th `== [{"start": "2023-02-01T10:00:00", "end": "2027-02-01T00:00:00"}]`; `:86` 58th `end "2027-03-01T09:00:00"`; exact list equality excludes the 2019 entry the fixture holds (`etl/tests/v3data.py:291`) | PASS |
| C12 | 301 participation 1/1 and authoredCount 1 in each mandate | `test_indicators_use_only_their_legislature` PASSED | `etl/tests/test_v3_legislature.py:92` `== {"count": 1, "total": 1}`; `:93` `authoredCount == 1` | PASS |
| C13 | 302's 57th mandate all zero | `test_mandate_without_roll_calls_is_all_zero` PASSED | `etl/tests/test_v3_legislature.py:99` `saulo[key] == ZERO` for 3 indicators; `:100` `symbolicMerit == 0`; `:101` `== (0, 0, 0)` | PASS |
| C14 | member fields from latest record; mandate party per legislature; 302 from list | `test_member_fields_come_from_latest_record` PASSED | `etl/tests/test_v3_legislature.py:106` `== ("Rita Alves Lima", "PSB", "SP")`; `:107` photo `301-58.jpg`; `:108` `== ("PT", "PSB")`; `:111-113` Saulo from the list entry | PASS |
| C15 | API files fetched once, cached with matching manifest, 0 requests on rebuild, refresh refetches, no email | `test_v3_api_files_are_cached_and_listed`, `test_v3_refresh_fetches_again` PASSED | `etl/tests/test_v3_cli.py:80,82` `.count(...) == 1`; `:87-88` sha256/bytes equal; `:90` `"email" not in d`; `:93-94` `api_requests(fake) == []`, `bulk_requests() == []`; `:103-105` refetched, `len(bulk_requests()) == 7` | PASS |
| C16 | history 404: exit 2, URL on stderr | `test_v3_download_failure_exits_2` PASSED | `etl/tests/test_v3_cli.py:111` `== 2`; `:112` `fake.base + "/api/v2/deputados/202/historico" in err` | PASS |
| C17 | `ballot_of` table | `test_ballot_of[5 cases]` PASSED | `etl/tests/test_v3_classify.py:28` `ballot_of(values, opening) == expected` over the rows at `:19-23` | PASS |
| C18 | roll-calls.json holds K1-K7, K9; excludes K0, K8 | `test_roll_call_inclusion` PASSED | `etl/tests/test_v3_classify.py:40` `ids == {K[n] for n in (1, 2, 3, 4, 5, 6, 7, 9)}` | PASS |
| C19 | `normalise` and first match wins | `test_normalise_and_first_match_wins` PASSED | `etl/tests/test_v3_classify.py:44` `== "aprovado, em apreciacao preliminar"`; `:47` `== ("procedural", "camara.01")` with a `descricao` that also matches camara.09 | PASS |
| C20 | K6 unclassified/null; coverage unclassified 1 | `test_unclassified_is_counted` PASSED | `etl/tests/test_v3_classify.py:52` `== ("unclassified", None)`; `:54` `row["unclassified"] == 1` | PASS |
| C21 | 12 official examples classify as their rule row | `test_official_examples[12 cases]` PASSED | `etl/tests/test_v3_classify.py:70` `classify(RECORDED_EXAMPLES[rc], ...) == EXAMPLES[rc]`, table at `:58-63` matching the checks row for row | PASS |
| C22 | camara.02, .05, .10 unit cases | `test_rule_without_official_example[3 cases]` PASSED | `etl/tests/test_v3_classify.py:88` `classify(row, ...) == expected`, rows at `:80-82` | PASS |
| C23 | rules file = the 11 applied rules in order, exact keys; version 1 | `test_rules_file_is_the_applied_ruleset` PASSED | `etl/tests/test_v3_classify.py:93` ids `camara.01..11`; `:94` exact key set; `:96` `written == [{**r, "house": "camara"} ...]`; `:98` `== {"version": 1}` | PASS |
| C24 | each description names its term, no banned word | `test_rule_descriptions_name_the_official_term` PASSED | `etl/tests/test_v3_classify.py:118` `TERMS[rule["id"]] in description`; `:120` `word not in description` | PASS |
| C25 | fixture build classifies K1-K6 (kind, rule, ballot) | `test_build_classifies_the_fixture` PASSED | `etl/tests/test_v3_classify.py:131` `(r["kind"], r["kindRule"], r["ballot"]) == triple`, triples at `:125-127` | PASS |
| C26 | symbolic: null tallies, no file; K1 has one | `test_symbolic_has_null_tallies_and_no_file` PASSED | `etl/tests/test_v3_classify.py:136` `tallies is None`; `:137` file absent; `:138` K1 file present | PASS |
| C27 | K7 tallies 12/5/2; K5 null tallies with `votes: []` | `test_secret_tallies` PASSED | `etl/tests/test_v3_classify.py:142` `== {"yes": 12, "no": 5, "others": 2}`; `:143` `is None`; `:144` `["votes"] == []` | PASS |
| C28 | `position_of` map; built vote keeps official | `test_position_of[7 cases]`, `test_vote_keeps_official_value` PASSED | `etl/tests/test_v3_indicators.py:22` `position_of(official, ballot) == position`; `:43-49` built `(official, position)` pairs incl. `("Artigo 17", "presiding")`, `("", "notVoting")` | PASS |
| C29 | unknown vote `Presente` / orientation `Talvez`: exit 1, named, output kept | `test_unknown_value_stops_the_build[vote,orientation]` PASSED | `etl/tests/test_v3_indicators.py:64` `== 1`; `:66` `value in err and rc in err`; `:67` `snapshot(...) == before` | PASS |
| C30 | orientation map; empty orientation dropped; government position | `test_orientation_of[5 cases]`, `test_orientation_of_unknown_raises`, `test_orientations_and_government_position` PASSED | `etl/tests/test_v3_indicators.py:75` map; `:84` `GOVERNO` -> `"yes"`; `:85` `"free"`; `:86` `is None`; `:88` F1 orientations exactly Governo+PT (the fixture's empty `Bloco` row, `etl/tests/v3data.py:200`, is absent) | PASS |
| C31 | participation.all 4/5, 4/4, 5/5 | `test_participation_all` PASSED | `etl/tests/test_v3_indicators.py:100` `== ["4/5", "4/4", "5/5"]` | PASS |
| C32 | participation.merit 2/3, 2/2, 3/3 | `test_participation_merit` PASSED | `etl/tests/test_v3_indicators.py:104` `== ["2/3", "2/2", "3/3"]` | PASS |
| C33 | governmentAlignment per member and basis | `test_government_alignment` PASSED | `etl/tests/test_v3_indicators.py:110` `got == [("4/4", "2/2"), ("1/3", "0/1"), ("1/3", "1/1")]` | PASS |
| C34 | partyAlignment per member and basis | `test_party_alignment` PASSED | `etl/tests/test_v3_indicators.py:116` `got == [("1/3", "0/1"), ("1/3", "0/1"), ("0/0", "0/0")]` | PASS |
| C35 | `party_majority` clear/tie/none/own excluded/non-votes | `test_party_majority[5 cases]` PASSED | `etl/tests/test_v3_indicators.py:131` `party_majority(positions, own) == expected`, rows at `:122-126` | PASS |
| C36 | symbolicMerit 1/0/1, unchanged by a symbolic procedural | `test_symbolic_merit` PASSED | `etl/tests/test_v3_indicators.py:137` `== [1, 0, 1]`; `:141` extra is `procedural`; `:142` still `== [1, 0, 1]` | PASS |
| C37 | proposition counts 5/2/3 and 1/0/0 | `test_proposition_counts` PASSED | `etl/tests/test_v3_indicators.py:150` `== (5, 2, 3)`; `:151` `== (1, 0, 0)` (the EMC, pre-2023 PL and proponente-0 rows are at `etl/tests/v3data.py:218-220`) | PASS |
| C38 | committee C1 changes no indicator | `test_committee_vote_changes_no_indicator` PASSED | `etl/tests/test_v3_indicators.py:166` mandates equal with and without C1 (the fixture comment at `etl/tests/v3data.py:191` shows C1 would change them if counted) | PASS |
| C39 | no percentage keys; bases exactly count+total | `test_no_percentage_keys` PASSED | `etl/tests/test_v3_indicators.py:177` `set(doc[key]) == {"count", "total"}`; `:192` `not keys & FORBIDDEN` | PASS |
| C40 | every file of 3 datasets passes both validators over 7 kinds; validate exits 0 | `test_every_file_passes_its_schema` PASSED | `etl/tests/test_v3_contract.py:36` validate `== 0`; `:42` `first_error(...) is None`; `:43` `Draft202012Validator(spec).is_valid(doc)`; `:45` `seen == set(KINDS)` | PASS |
| C41 | `http://` photoUrl: exit 1 naming file and field, output kept | `test_schema_failure_keeps_previous_output` PASSED | `etl/tests/test_v3_contract.py:55` `== 1`; `:57` `"members.json" in err and "photoUrl" in err`; `:58` `snapshot(...) == before` | PASS |
| C42 | meta exact keys, coverage row, 14 sources sorted and equal to the manifest | `test_meta_shape_and_coverage` PASSED | `etl/tests/test_v3_contract.py:63` exact key set; `:64` `(3, "camara", "2026-09-27T12:00:00Z")`; `:65-68` coverage row literal; `:70` sorted; `:71-77` 14-file set; `:79` `s == manifest[s["file"]]` | PASS |
| C43 | sourceUrl patterns | `test_source_urls` (test_v3_contract) PASSED | `etl/tests/test_v3_contract.py:84,86,88` - the three exact f-string equalities | PASS |
| C44 | house camara everywhere; unique (house, id) | `test_house_and_unique_identity` PASSED | `etl/tests/test_v3_contract.py:94` `all(r["house"] == "camara")`; `:96` set length equals record count; `:98,100` full texts and roll-call docs | PASS |
| C45 | no cpf key, no fixture CPF bytes, no cpf allowlist column | `test_no_cpf_anywhere` PASSED | `etl/tests/test_v3_contract.py:117` `CPF_IN_DEPUTADOS.encode() not in ...`; `:119` no key containing `cpf`; `:120` allowlist | PASS |
| C46 | deterministic bytes; member, roll-call, proposition and vote ordering | `test_build_is_deterministic_and_ordered` PASSED | `etl/tests/test_v3_contract.py:128` `snapshot(...) == first`; `:129` `== ["Ana Souza", "Beto Lima", "Caio Dias"]`; `:131-133` date desc; `:137-139` dated then null; `:142` `ids == sorted(ids)` (see precision note P1) | PASS |
| C47 | log line per legislature; `--quiet` silent | `test_log_line_per_legislature` PASSED | `etl/tests/test_v3_contract.py:149` exact line `"camara 57: 8 roll calls (3 nominal, 2 secret, 3 symbolic), 1 unclassified"`; `:151` `err == ""` | PASS |
| C48 | exact v3 layout; exactly 7 schema files | `test_v3_layout` PASSED | `etl/tests/test_v3_contract.py:160-163` exact file set; `:164` schema dir listing `== sorted(f"{k}.schema.json" for k in KINDS)` | PASS |
| C49 | both validators reject 8 corruptions | `test_validators_agree_on_invalid[8 cases]` PASSED | `etl/tests/test_v3_contract.py:193` `first_error(...) is not None`; `:194` `not ...is_valid(doc)`; mutations at `:175-182` | PASS |
| C50 | Senate fixture validates; 2 senators [57,58]; `6923` nominal with the 5 officials | `test_senate_fixture_validates` PASSED | `etl/tests/test_v3_senado.py:20` `== 0`; `:23` mandates `[57, 58]`; `:26` `== ("6923", "nominal", "senado")`; `:27` officials incl. `Licença` | PASS |
| C51 | `presidencia` / position `other` named by validate, exit 1 | `test_invalid_senate_value_is_named[house,position]` PASSED | `etl/tests/test_v3_senado.py:46` `== 1`; `:48` `relative in err and value in err` | PASS |
| C52 | nullable symbolic counts accept null/0, reject -1/"0"; Câmara writes ints; Senate null | `test_symbolic_counts_are_nullable[4 cases]`, `test_symbolic_counts_are_nullable_in_senate_fixture` PASSED | `etl/tests/test_v3_contract.py:202` `== [1, 0, 1]`; `:204` `symbolic == 1`; `:209-210` both validators `is valid`; `:267,269` Senate `[None]*n`; `:270` validate `== 0` (refreshed: the fix moved these lines by 4) | PASS |
| C53 | sensitive map; LS/LP/LAP fail validate, Licença passes | `test_sensitive_codes_are_generalised_map`, `test_sensitive_codes_are_generalised_in_validation[4 cases]` PASSED (the `-k` prefix matches both) | `etl/tests/test_v3_contract.py:214` `SENSITIVE_OFFICIAL["camara"] == {}`; `:216` `== "Licença"`; `:217` P-NRV unchanged; `:229` `== exit_code`; `:231` code named on stderr | PASS |
| C54 | only 8001 gets a full text, exact document | `test_full_text_for_each_target` PASSED | `etl/tests/test_v3_full_texts.py:19` `names == ["8001.json"]`; `:21` exact keys; `:23-27` sourceUrl, sha256 of served PDF, extractor, extractedAt, sentence in text | PASS |
| C55 | PDF and proposition record cached, listed, 0 requests on rebuild | `test_full_text_sources_are_cached` PASSED | `etl/tests/test_v3_full_texts.py:38` manifest sha256; `:41` in `meta.sources`; `:44` `== []` | PASS |
| C56 | opening and presentation descriptions stripped, null when empty | `test_roll_call_doc_carries_descriptions` PASSED | `etl/tests/test_v3_full_texts.py:51-52` stripped values (fixture has surrounding spaces, `etl/tests/v3data.py:174`); `:54` `== (None, None)` | PASS |
| C57 | pypdf pinned in group, default-groups, dependencies `[]`, only full_texts imports it | `test_pypdf_is_pinned_outside_runtime_dependencies`, `test_runtime_dependencies_are_empty` PASSED | `etl/tests/test_v3_full_texts.py:60-65` (one lock entry, `pypdf==<locked>`, default-groups, `[]`, `importers == ["full_texts.py"]`); `etl/tests/test_packaging.py:12` `dependencies == []` | PASS |
| C58 | allowlist gains exactly 5 columns, and a v3 build reads them through `readers.read` | `test_allowlist_additions` PASSED at c0ccf18; F5 re-injected and killed by it | `etl/tests/test_v3_contract.py:243-246` exact additions; `:249` `a1["openingDescription"] == "Votação do DTQ 1: Destaque"` (votacoes column); `:255` `(link_only["type"], link_only["number"], link_only["year"]) == ("PL", 1, 2023)` for 8001. 8001 is absent from `INDICATOR_PROPOSITIONS` (`etl/tests/v3data.py:208-221`) and present only in `INDICATOR_LINKS` (`:204-205`), so `proposicao_siglaTipo`, `proposicao_numero` and `proposicao_ano` are read by the link branch (`etl/src/mandato_etl/contract_v3.py:227-228`). The second `votacoes` column is C56 `etl/tests/test_v3_full_texts.py:52` | PASS |

## Coverage

Rows carried from {R1}, except "proposition field sources", which was recomputed at {HEAD}. The fix changed only a test, so no other row's authority was touched.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| `ballot` enum (3) | door 5; `roll-call.schema.json` / `roll-calls.schema.json` enum `[nominal, secret, symbolic]` | nominal C17 `:19-20`, C25 · secret C17 `:21-22`, C27 · symbolic C17 `:23`, C26 | - |
| `kind` enum (4) | door 6; schema enum `[final, amendment, procedural, unclassified]` | final C21, C25 · amendment C21, C25 · procedural C21, C25 · unclassified C20, C25 | - |
| Câmara ruleset v1 (11 rule ids) | plan table, `{verb}` expanded and compared field by field with `rules/camara.json`: 11/11 identical in id, kind, field and pattern | .01 C19, C21 · .02 C22 · .03 C21, C25 · .04 C21, C25 · .05 C22 · .06 C21 · .07 C21 · .08 C21, C25 · .09 C21, C25 · .10 C22 · .11 C21, C25 | - |
| rule descriptions (11) | same 11 ids | C24, table-driven (`etl/tests/test_v3_classify.py:115` asserts the id set equals TERMS) | - |
| vote `position` enum (7) | door 7; `roll-call.schema.json` `$defs/vote/properties/position` | all 7 in C28 `etl/tests/test_v3_indicators.py:159-161` | - |
| Câmara vote values (5 + empty + unknown) | door 7 map, `classify.VOTE_POSITIONS` | Sim/Não/Abstenção/Obstrução/Artigo 17/empty C28 · unknown C29 + `test_position_of_unknown_raises` | - |
| orientation `position` enum (5) | door 7; schema `$defs/orientation/properties/position` | all 5 in C30 `etl/tests/test_v3_indicators.py:216` | - |
| orientation values (5 + empty + unknown) | door 7 map | 5 values C30 · empty C30 (`:88`, the Bloco row is dropped) · unknown C29 | - |
| `governmentOrientation` (3 cases) | door 7, AC 26 | GOVERNO C30 `:84` · Governo/Liberado C30 `:85` · none C30 `:86` | - |
| `house` enum (2 + outside) | door 3; every v3 schema `house` enum `[camara, senado]` | camara C44 · senado C50 · presidencia C49, C51 | - |
| sensitive code map (AD-018, door 10: 4 entries) | `.specs/STATE.md` AD-018 and plan door 10; code `classify.py:31-34` matches exactly | LS, LP, LAP C53 (map and validate refusal; schema pattern `roll-call.schema.json:187`) · camara `{}` C53 | - |
| schema files (7) | `ls etl/schema/v3` = 7 files, matching door 1 + door 8 | each kind validated in C40 (`seen == set(KINDS)`); exact listing C48 | - |
| party majority cases (5) | AC 31 | clear, tie, none, own excluded, non-votes C35 | - |
| indicators per mandate (7) × bases (2) | door 4 | participation C31/C32 · government C33 · party C34 · symbolicMerit C36 · 3 proposition counts C37 | - |
| legislature date edges (5) | door 4, AC 7 | four edges + outside C7; outside at CLI C8 | - |
| build-date edges (2) | AC 9 | C9 both | - |
| mandate sources (2) | AC 10 | listed (302) and vote-only (303) C10 | - |
| roll-call inclusion (4) | AC 16 | C18 | - |
| proposition types (8 + other) | AC 33, `compute.AUTHORED_TYPES`/`REQUIREMENT_TYPES` | C37 (fixture rows 9001-9009) | - |
| full-text target cases (4) | door 8 | C54 | - |
| `validate` schema_version (3) | AC 5 | C5 | - |
| build flags under v3 (9) | `cli._parser` | `--contract` C2/C4 · `--house` C4 · `--out` C2 · `--years` C15 (`etl/tests/test_v3_cli.py:105`, 7 bulk files = one year) · `--refresh` C15 · `--quiet` C47 · 3 candidacy flags C3 | - |
| v3 build exit codes (3) | Observable | 0 C2 · 1 C3, C4, C8, C29, C41 · 2 C16 | - |
| nullable symbolic fields (2) | door 9 | C52 both | - |
| startup config: v3 output root (2 assemblies) | read directly: `etl/src/mandato_etl/cli.py:17` `V3_DIR = ROOT / "data" / "v3"`, `:94` `out = args.out if args.out is not None else V3_DIR / args.house` | CLI default C2 `etl/tests/test_v3_cli.py:26` · `--out` C2 `:35` | - |
| proposition field sources (2 sources x 6 fields = 12), verified at c0ccf18 | `contract_v3._proposition` branches `etl/src/mandato_etl/contract_v3.py:219-230` against the fields `etl/schema/v3/propositions.schema.json` declares (`type`, `number`, `year`, `summary`, `presentedAt`, `status`; `id`, `house`, `sourceUrl`, `authors` are branch-independent and covered by C43, C44, C37) | bulk row: `type`/`number`/`year` C58 `etl/tests/test_v3_contract.py:252` (8002 is in the bulk file, `etl/tests/v3data.py:221`) · `presentedAt` C46 `:135-138` · link: `type`/`number`/`year` C58 `:255` (F5 killed) · `presentedAt` null C46 `:139` | bulk-row `summary` (`contract_v3.py:222`, F7 survived) · link `summary` (`:228`, F6 survived) · bulk-row `status` (`:223`, F8 survived) · link `status` null (`:229`, F9 survived; named in `checks.md:299` and miscredited to C46 `:139` in round 1) |

Precision notes, carried from f73b12c (findings about the checks; they block nothing on their own):
- P1 (C46 / AC 41): the tie-breakers are not exercised. Member names in the fixture carry no accents and none repeat. Roll-call dates and proposition `presentedAt` values are distinct within each dataset. So "accent-stripped" and "then `id`" are not discriminated. The code does both (`etl/src/mandato_etl/contract_v3.py:304,320-321,329-330`).
- P2 (AC 29/30): alignments are not restricted to exercise periods. The handoff (`.specs/features/contract-v3/checks.md:299`) records this, and AC 29/30 do not require it. Noted for the maintainer, not a defect against the checks.
- P3 (new, round 2): no AC or check states what `summary` and `status` hold. The obvious reading is: the bulk `ementa` and `ultimoStatus_descricaoSituacao`, the link `proposicao_ementa`, and `null` status for a link-only proposition, per `checks.md:299`. Writing that down as a claim is what lets a proof exist for the four unproven members above.

## Test policy rows

The `contract_v3.py` row was re-judged at c0ccf18. The other rows are carried from f73b12c, because the fix classifies no file they cover.

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary (CLI, HTTP) | `classify.py` | boundary C25, C29 · own layer C17-C22, C28, C30 | yes - one asserted case per row: ballot 5 cases, 11/11 rules, vote map 7 + unknown, orientation map 5 + empty + unknown |
| Decides, reached across a boundary (CLI, HTTP) | `cli.py` | CLI C2-C5, C8, C16, C29, C41, C47 | yes - every exit code and stderr content asserted via `cli.main(argv)` |
| Decides, reached across a boundary (CLI, HTTP) | `sources/camara.py` additions | HTTP to the local FakeCamara C15, C16, C55 | yes - miss, hit, refresh and 404 each asserted across the socket |
| Decides, not reached across a boundary | `contract_v3.py` | own layer through dataset builds C7-C14, C31-C38, C58 | no - the `_proposition` branches now have their `number`/`year` case (F5 killed), but `summary` (both branches) and `status` (both branches) have no asserted case; F6-F9 survived |
| Decides (selection) / instrumentation (extraction) | `full_texts.py` | selection C54; extraction covered by consumer | yes - 4 target cases in C54; extracted text asserted at `etl/tests/test_v3_full_texts.py:27` |
| Instrumentation, pass-throughs | `publish.py` (`version` forwarded), `schema.py` (`kind_of`/`load` by version), `readers.py` (allowlist) | covered by consumer | yes - C40, C41, C5, C49 exercise them; allowlist additions proven statically at C58 `:243` |

Carried from f73b12c: the edit to etl-camara's `test_contract_layout` is not weakened. It is still an exact listing, and C48 pins `v3/`. v2 byte identity was proven with both the HEAD code and the base code, against `etl/tests/fixtures/v2-golden.json`. This round re-ran `test_v2_build_matches_base_commit[plain,secret]` at c0ccf18 (both PASSED). The fix touched neither the golden nor `etl/src`.

## Faults injected

F1-F4 are carried from f73b12c. F5-F9 were verified at c0ccf18, in `git worktree add --detach /tmp/cv3-verify-r2 HEAD` with its own `uv sync` and no `git stash`. Each fault was reverted with `git checkout -- .` before the next, and the scratch was removed with `git worktree remove --force` and `git worktree prune`. The real tree's `git status --porcelain` was empty before and after, and the two outputs matched under `diff`. F6-F9 probe the members of the recomputed set that no assertion names.

| Mutation | Location | Killed |
| --- | --- | --- |
| F1 `ballot_of`: all-empty records no longer `secret` (`if values or "secreta"...` -> `if "secreta"...`) | `etl/src/mandato_etl/classify.py:56` | yes - `test_ballot_of[all-empty]` failed |
| F2 participation ignores exercise periods (dropped `in_periods(...)` from eligibility) | `etl/src/mandato_etl/contract_v3.py:360` | yes - `test_participation_all` failed |
| F3 next legislature start off by one (`timedelta(days=1)` -> `0`) | `etl/src/mandato_etl/contract_v3.py:47` | yes - `test_exercise_periods_per_legislature` failed |
| F4 roll-call schema stops refusing LS/LP/LAP (pattern -> `^`) | `etl/schema/v3/roll-call.schema.json:187` | yes - `test_sensitive_codes_are_generalised_in_validation[LS,LP,LAP]` 3 failed |
| F5 (re-injected) link fallback publishes `number`/`year` as `None` | `etl/src/mandato_etl/contract_v3.py:227-228` | yes - `test_allowlist_additions` failed at `etl/tests/test_v3_contract.py:255`; full suite 1 failed, 238 passed |
| F6 link fallback publishes `summary` as `None` instead of `proposicao_ementa` | `etl/src/mandato_etl/contract_v3.py:228` | no - survived: full suite 239 passed |
| F7 bulk row publishes `summary` as `None` instead of `ementa` | `etl/src/mandato_etl/contract_v3.py:222` | no - survived: full suite 239 passed |
| F8 bulk row publishes `status` as `None` instead of `ultimoStatus_descricaoSituacao` | `etl/src/mandato_etl/contract_v3.py:223` | no - survived: full suite 239 passed |
| F9 link fallback publishes `status` `"Aguardando Parecer"` instead of `null` | `etl/src/mandato_etl/contract_v3.py:229` | no - survived: full suite 239 passed |

## Gate

Verified at c0ccf18:
`uv run --directory etl pytest -v` - 239 passed, 0 failed (exit 0)
`test -z "$(git diff --name-only bc0a4a8..HEAD -- site design .github/workflows/publish.yml)"` - exit 0
`scripts/check-commit-msg.sh` on each of the 10 commits in `bc0a4a8..c0ccf18` - exit 0 for all

Carried from f73b12c:
`PYTHONPATH=<bc0a4a8 etl/src> uv run pytest tests/test_v2_frozen.py` (scratch) - 2 passed: the base code reproduces the golden
