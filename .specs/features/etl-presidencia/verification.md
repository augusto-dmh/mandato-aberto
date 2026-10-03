# etl-presidencia verification

**Verdict**: PASS
**Profile**: standard
**Diff range**: 91323e8..473e7b529f060b33076759f4a449f71f2b089c75
**Round**: 2 - scoped
**Verifier**: independent sub-agent (author != verifier)

Round 1 (`bb01ceb`) returned FAIL with four gaps. Round 2 is scoped to the fix commits `baf37ae`, `dc926f5`, `b6453d3`,
`f4cae72` and `473e7b5`, plus every round-1 verdict that was not PASS. All four gaps are closed at `473e7b5`:

1. **Alias file (gap 1).** `.gitignore:6` now holds `!etl/inputs/joint-vote-aliases.json`, and `git ls-files etl/inputs`
   lists `etl/inputs/joint-vote-aliases.json`. A clean `git worktree add /tmp/presid-v2 HEAD` with its own `uv sync`
   gives 463 passed and 0 failed, and the real tree gives the same 463.
2. **Guards (gap 2).** The five stop-the-build guards are proven by C61, and two of them were killed again here.
3. **v4 member ids (gap 3).** `validate_dir` now runs `_dangling_member` on v4 house directories (`etl/src/mandato_etl/schema.py:162-164`).
   C62 and C63 prove it, and both surfaces were killed.
4. **C52 precision (gap 4).** C52 now asserts every veto URL exactly. A fault that the pre-fix assertion lets through is
   killed by the new one.

Commands run at `HEAD` `473e7b5`:

- **Clean checkout** (`/tmp/presid-v2`, own `uv sync`), under a `sitecustomize` network guard that refuses every
  non-loopback `connect` and DNS lookup. The guard was confirmed active: a probe to `legis.senado.leg.br` was blocked.
  - `uv run --directory etl pytest -v -p no:cacheprovider`: **463 passed**, 0 failed, 0 blocked network attempts (48.8 s).
  - Every named proof in one invocation: `pytest -v <8 files> -k "<68 names or-ed>"` gave **127 passed**, 2 deselected
    (`test_term_listed_from_its_start_date` and `test_unmatched_is_zero_in_recorded_build`, which no check names; both
    are green in the full run). Each of the 68 names appears as an individual `PASSED` line at least once. All five C61
    cases (`[veto-without-device]`, `[voted-device-not-decided]`, `[unknown-tipo-votacao]`, `[alias-to-absent-member]`,
    `[act-outside-every-term]`), both C62 cases `[vote]` and `[author]`, `test_v4_fixture_house_validates`, both C63 cases
    and `test_source_urls` are each listed PASSED.
  - C57: the 14 files gave 220 passed.
  - C6: the `git diff --name-only 91323e8..HEAD -- site design .github/workflows/publish.yml etl/schema/v3 ':(glob)etl/schema/*.json'`
    test exited 0 with empty output.
  - C56: the grep exited 0 (`.github/workflows/ci.yml:38`).
  - C59: the grep exited 0 (`AGENTS.md:28`).
  - `uv run mandato-etl validate tests/fixtures/v4/presidencia` (from `etl/`) exited 0.
- **Real tree:** full suite 463 passed, named batch 127 passed, `validate` exit 0. The results match the clean checkout.
- **Untracked-but-present sweep:** `git status --porcelain --ignored` on the real tree lists only `etl/.pytest_cache/`,
  `etl/.venv/` and three `__pycache__/` directories. `etl/inputs` holds only the alias file, which is tracked and
  byte-equal to `git show HEAD:etl/inputs/joint-vote-aliases.json`. No file a test reads exists only in the working
  tree, and the equal 463/463 between the clean and real trees confirms it.

## Checks

Verified at `473e7b5`. Every proof below is green in the clean checkout. The evidence cells were refreshed for the
two files the fixes touched: `etl/tests/test_v4_houses.py` (+20 lines from line 12) and
`etl/tests/test_presidencia_contract.py` (+7 lines from line 92). The other test files are byte-identical to `bb01ceb`
(`git diff --stat bb01ceb..HEAD`), so their citations are carried from `bb01ceb`.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | v4 Câmara = v3 except `schema_version` | `test_camara_v4_equals_v3_but_version` PASSED | `etl/tests/test_v4_houses.py:43` `v4[relative] == v3[relative].replace(b'"schema_version":3', b'"schema_version":4')`; `:45` byte-equal otherwise | PASS |
| C2 | same for the Senate | `test_senado_v4_equals_v3_but_version` PASSED | `etl/tests/test_v4_houses.py:62` `_only_version_differs(snapshot(sd.out(sen)), snapshot(out))` | PASS |
| C3 | default `data/v4/<house>`; out and v3 bytes and mtimes kept | `test_v4_default_out_leaves_out_and_v3_untouched` PASSED | `etl/tests/test_v4_houses.py:80`, `:85` meta files exist; `:86-87` `_stats(...) == before_out` / `before_v3` | PASS |
| C4 | bad contract/house: exit 1, usage, 0 requests, no output | `-k test_bad_contract_or_house_exits_1` 5 PASSED | `etl/tests/test_v4_houses.py:96` `== 1`; `:97` `"usage:" in err`; `:98` `con.requests == []`; `:99` `not out.exists()` | PASS |
| C5 | validate picks the v4 set by scope; v2/v3 kept | `test_validate_picks_the_v4_set_by_scope` PASSED | `etl/tests/test_v4_houses.py:106,111,115` `== 0`; `:119` scope on a house `== 1`; `:123` `"unsupported schema_version 5" in err`; `:127,:130` v2/v3 `== 0`; `:133` relabelled v3 `== 1` | PASS |
| C6 | no diff in site, design, v3 schemas, v2 schemas, publish.yml | git-diff test, exit 0 | empty output of the command in `.specs/features/etl-presidencia/checks.md:89` | PASS |
| C7 | v4 schema set exact; house schemas = v3 but const 4 | `test_v4_schema_set` PASSED | `etl/tests/test_v4_houses.py:175` file set; `:181` `const == 4`; `:183` `four == three` | PASS |
| C8 | first build: 23 Congress paths once, headers, manifest | `test_first_build_requests_each_source_once` PASSED | `etl/tests/test_presidencia_sources.py:47` `sorted(requested) == sorted(OTHERS + DEVICES)`; `:48` `len == 23`; `:52-53`; `:60-62` | PASS |
| C9 | cache reuses only decided devices; `--refresh` refetches | `test_cache_reuses_only_decided_devices` PASSED | `etl/tests/test_presidencia_sources.py:73` `== sorted(OTHERS)`; `:74`; `:77` | PASS |
| C10 | ≥0.45 s apart, one in flight | `test_at_most_two_requests_per_second` PASSED | `etl/tests/test_presidencia_sources.py:94` `b - a >= 0.45`; `:95` `con.max_in_flight == 1` | PASS |
| C11 | 503×4 → exit 2, URL, sleeps [1,2,4], no `.part`, output kept; bad bodies retried | `test_download_failure_exits_2_and_keeps_output` PASSED; `-k test_bad_body_is_retried` 4 PASSED | `etl/tests/test_presidencia_sources.py:105` `== 2`; `:106`; `:107` `con.sleeps == [1, 2, 4]`; `:108`; `:109`; `:151`; `:154` | PASS |
| C12 | no planalto request | `test_no_planalto_request` PASSED | `etl/tests/test_presidencia_sources.py:166`; `:167` `not any("planalto.gov.br" in url ...)` | PASS |
| C13 | house dirs missing/invalid/v3 → exit 1 before any request | `-k test_house_directories_required` 3 PASSED | `etl/tests/test_presidencia_sources.py:190` `== 1`; `:192`; `:193` `congress_requests == []`; `:194` | PASS |
| C14 | exactly 18 act ids, distractors excluded | `test_one_act_per_mp_veto_and_executive_bill` PASSED | `etl/tests/test_presidencia_acts.py:34` `sorted(ids) == sorted(R_IDS)`; `:35` `len == 18`; `:36` | PASS |
| C15 | kind/type, ints, vetoScope, non-veto devices [] | `test_kind_type_and_veto_scope` PASSED | `etl/tests/test_presidencia_acts.py:45`; `:46`; `:48`; `:50-51` | PASS |
| C16 | pl-1-2023 excluded, counter 1 | `test_before_first_term_is_excluded` PASSED | `etl/tests/test_presidencia_acts.py:56`; `:57` `excludedBeforeFirstTerm == 1` | PASS |
| C17 | term edges; T termIds; meta.terms exact by build date | `test_term_boundaries`, `test_terms_listed_by_build_date` PASSED | `etl/tests/test_presidencia_acts.py:61-64` `term_of(...)`; `:69-70`; `:82` `== [TERM_2023]`; `:87` `== [TERM_2023, TERM_2027]` | PASS |
| C18 | 9 MP rules, 4 rejects | `-k test_mp_status_rules` 13 PASSED | `etl/tests/test_presidencia_acts.py:103` `mp_status(record, ...) == (status, rule)`; `:113-114` `pytest.raises(PresidencyError)` | PASS |
| C19 | 4 device rules, 3 rejects | `-k test_device_status_rules` 7 PASSED | `etl/tests/test_presidencia_acts.py:122`; `:127-128` | PASS |
| C20 | recorded MP and device status/rule/officialStatus | `test_recorded_statuses` PASSED | `etl/tests/test_presidencia_acts.py:142`; `:152` | PASS |
| C21 | unknown MP/device value → exit 1 naming value + act, output kept | `-k test_unknown_status_stops_the_build` 2 PASSED | `etl/tests/test_presidencia_acts.py:180` `== 1`; `:182`; `:183` snapshot | PASS |
| C22 | veto pending/decided, unit cases | `test_veto_status` PASSED | `etl/tests/test_presidencia_acts.py:188`, `:190`; `:191-193` | PASS |
| C23 | bill precedence table (10 rows) | `-k test_bill_status_precedence` 10 PASSED | `etl/tests/test_presidencia_acts.py:212` `bill_status(norma, camara, total) == (status, rule)` | PASS |
| C24 | recorded bill statuses; H vetoedTotally | `test_recorded_bill_statuses`, `test_vetoed_totally` PASSED | `etl/tests/test_presidencia_acts.py:218-225`; `:230` `("vetoedTotally", "bill.03")` | PASS |
| C25 | law verbatim; approvedWithoutLaw 0/1 | `test_law_and_approved_without_law` PASSED | `etl/tests/test_presidencia_acts.py:239`; `:241`; `:242` `== 0`; `:247-248` | PASS |
| C26 | vetoedMatter and relatedActId | `test_related_act` PASSED | `etl/tests/test_presidencia_acts.py:261`; `:264`; `:268` `== "pl-9105-2025"` | PASS |
| C27 | 21 rules exact, keys, verbatim value, words, version 1 | `test_status_rules_file` PASSED | `etl/tests/test_presidencia_acts.py:292` `== RULE_TABLE`; `:293`; `:295`; `:297`; `:300`; `:306` | PASS |
| C28 | issuedAt/statusAt/summary, device fields | `test_act_fields` PASSED | `etl/tests/test_presidencia_acts.py:312`, `:314`, `:315`, `:317`, `:319-324` | PASS |
| C29 | MP stages, missingCamaraStage 1 | `test_mp_stages` PASSED | `etl/tests/test_presidencia_stages.py:22-25` | PASS |
| C30 | bill stages, SF-only Senate stage | `test_bill_stages` PASSED | `etl/tests/test_presidencia_stages.py:29-32` | PASS |
| C31 | vetoes have no stage; joint ids = device ids | `test_veto_has_no_stage` PASSED | `etl/tests/test_presidencia_stages.py:37` `all(a["stages"] == [])`; `:40-41` | PASS |
| C32 | stage join returns the named roll calls | `test_stages_join_house_roll_calls` PASSED | `etl/tests/test_presidencia_stages.py:51-53` | PASS |
| C33 | 6 joint roll calls, fields | `test_joint_roll_calls` PASSED | `etl/tests/test_presidencia_joint.py:30`; `:32-36`; `:38-40` | PASS |
| C34 | position map + rejects | `-k test_position_map` 9 PASSED | `etl/tests/test_presidencia_joint.py:48`; `:53-54` | PASS |
| C35 | votes file shape, order, aj Albuquerque | `test_votes_file` PASSED | `etl/tests/test_presidencia_joint.py:60`, `:62`, `:65`, `:66`, `:68-69` | PASS |
| C36 | unknown TipoVoto → exit 1, output kept | `test_unknown_vote_stops_the_build` PASSED | `etl/tests/test_presidencia_joint.py:86` `== 1`; `:88`; `:89` | PASS |
| C37 | total veto: no votes, PDF URL, counter; device URLs; veto-page fallback | `test_total_veto_has_no_votes` PASSED | `etl/tests/test_presidencia_joint.py:94-97`; `:101-102`; `:106-107` | PASS |
| C38 | tallies R, H, untrimmed 43825 | `test_tallies`, `test_tallies_untrimmed_device` PASSED | `etl/tests/test_presidencia_joint.py:125`, `:136`; `:144-145` | PASS |
| C39 | resolution rule unit cases | `-k test_resolution_rule` PASSED | `etl/tests/test_presidencia_joint.py:155-160`, `:163`, `:166` | PASS |
| C40 | homonyms split by exercise | `test_homonyms_resolve_by_exercise` PASSED | `etl/tests/test_presidencia_joint.py:172` | PASS |
| C41 | alias file exact; aliases resolve | `test_aliases` PASSED; the file is now in the commit (`etl/inputs/joint-vote-aliases.json`, tracked) | `etl/tests/test_presidencia_joint.py:181` `[(house, name, uf, memberId) ...] == ALIASES` (literal `:175`); `:186`; `:188`; `:194` | PASS |
| C42 | unmatched fails closed, one line; ambiguous; counters 0 | `-k test_unmatched_fails_closed` 2 PASSED (`test_unmatched_is_zero_in_recorded_build` green in full run) | `etl/tests/test_presidencia_joint.py:208`; `:215` `== 1`; `:217-219`; `:226-228`; `:232` | PASS |
| C43 | duplicate vote / act id → exit 1 naming it | `-k test_duplicates_stop_the_build` 2 PASSED | `etl/tests/test_presidencia_joint.py:251-253` | PASS |
| C44 | H member counts exact | `test_member_counts_hand` PASSED | `etl/tests/test_presidencia_counts.py:31-40` | PASS |
| C45 | R member counts with amended base; unit base | `test_member_counts_recorded`, `test_base_needs_the_members_house_votes` PASSED | `etl/tests/test_presidencia_counts.py:45-59`; `:60`; `:75` | PASS |
| C46 | veto counted once; split → mixed | `test_veto_counted_once`, `test_split_veto_is_mixed` PASSED | `etl/tests/test_presidencia_counts.py:85` `{"count": 1, "total": 1}`; `:94` `(0, 0, 1)` | PASS |
| C47 | R term coverage exact; sums hold in R/H/T | `test_term_coverage` 3 PASSED | `etl/tests/test_presidencia_counts.py:122` `== R_TERMS`; `:111`, `:113` | PASS |
| C48 | no rate keys; count objects exact | `test_no_rate_keys` 3 PASSED | `etl/tests/test_presidencia_counts.py:146`, `:148` | PASS |
| C49 | every file passes in-package + Draft 2020-12; layout | `test_every_file_passes_its_schema` 3, `test_layout` PASSED | `etl/tests/test_presidencia_contract.py:39`; `:40` `Draft202012Validator(spec).is_valid(doc)`; `:41`; `:47` | PASS |
| C50 | schema failure → exit 1 naming file/path, output kept | `test_schema_failure_keeps_previous_output` PASSED | `etl/tests/test_presidencia_contract.py:64` `== 1`; `:66`; `:67` snapshot | PASS |
| C51 | meta keys, coverage exact, sources = manifest entries sorted | `test_meta` PASSED | `etl/tests/test_presidencia_contract.py:73`, `:74`, `:75`, `:77`, `:79`, `:84` | PASS |
| C52 | act and joint sourceUrls; every veto `…/<Codigo>` | `test_source_urls` PASSED | `etl/tests/test_presidencia_contract.py:98` MP exact; `:102-103` every veto `== VETO_URL + veto_codes[a["id"]]` (codes from the served list entry `:92-94`); `:106` `seen == set(veto_codes)`; `:105` bill exact; `:108` joint `https://`; `:110-112` named examples | PASS |
| C53 | no cpf key; vote keys exact | `test_no_cpf_and_only_allowed_vote_fields` PASSED | `etl/tests/test_presidencia_contract.py:128`; `:131` | PASS |
| C54 | deterministic; ordering | `test_build_is_deterministic` PASSED | `etl/tests/test_presidencia_contract.py:138` snapshot equal; `:140`; `:142`; `:144`; `:146` | PASS |
| C55 | one summary line per term; none with `--quiet` | `test_log_line_per_term` PASSED | `etl/tests/test_presidencia_contract.py:155`; `:157` `err == ""`; `:163` | PASS |
| C56 | committed fixture = fresh R build; CI validates it | `test_committed_fixture_is_the_recorded_build` PASSED; grep exit 0 | `etl/tests/test_presidencia_contract.py:192` `snapshot(FIXTURE) == snapshot(out)`; `:193`; `.github/workflows/ci.yml:38` | PASS |
| C57 | v2/v3/senado suites green | 14 files, 220 passed | suite exit 0 (`.specs/features/etl-presidencia/checks.md:262` command) | PASS |
| C58 | allowlist additions only; other columns never reach output | `test_allowlist_additions` PASSED | `etl/tests/test_presidencia_acts.py:328-331`; `:332`; `:335` | PASS |
| C59 | AGENTS.md profile line | grep exit 0 | `AGENTS.md:28` | PASS |
| C60 | house dirs read beside `--out` | `test_house_directories_are_siblings_of_out` PASSED | `etl/tests/test_presidencia_sources.py:204-205`; `:207-208` | PASS |
| C61 | five guards each exit 1 naming the record | `test_guards_stop_the_build_naming_the_record` 5 PASSED (one per guard id) | `etl/tests/test_presidencia_guards.py:54` `pd.build4(con, data, "--quiet") == 1`; `:56` `all(w in err for w in words)` over `:33-39`: `["vet-91-2025", "no device"]`, `["90.25.003", "prejudged", "not kept or overridden"]`, `["90.25.002", "Eletrônica", "not cedula or painel"]`, `["Prof. Dorinha Seabra/TO", "5386", "absent from senado/members.json"]`, `["mpv-1290-2025", "2027-02-10", "outside every known term"]` | PASS |
| C62 | `validate` on a v4 house: dangling vote/author → exit 1 naming file, id, memberId; relabelled fixture → 0 | `test_validate_refuses_a_dangling_member_id_in_a_v4_house[vote]`, `[author]`, `test_v4_fixture_house_validates` PASSED | `etl/tests/test_v4_houses.py:148` `cli.main(["validate", ...]) == 1`; `:150` `relative in err and all(token in err for token in named) and "members.json" in err` (named `("6923", "9999")`, `("160000", "9998")` at `:17-20`); `:141` `== 0` | PASS |
| C63 | presidency build refuses a dangling `senado` dir: exit 1 naming `senado/<file>` + memberId, 0 requests, no output | `test_presidency_build_refuses_a_dangling_house_directory[vote]`, `[author]` PASSED | `etl/tests/test_v4_houses.py:161` `== 1`; `:163` `f"senado/{relative}" in err and all(token in err ...)`; `:164` `pd.congress_requests(con) == []`; `:165` `not pd.out(con).exists()` | PASS |

The level judgment is carried from `bb01ceb` and extended at `473e7b5`. Every claim naming an exit code or stderr goes
through `cli.main` with argv. That includes the new C61 (`pd.build4`), C62 (`cli.main(["validate", ...])`) and C63.
There is no level gap.

Precision is verified at `473e7b5`. The round-1 C52 note is closed: every veto URL is compared with its list entry's
`Codigo`, and the set of vetoes seen must equal the served set, so a missing veto cannot pass silently.

## Coverage

Rows touched by the fixes were recomputed at `473e7b5`. The other rows were recomputed at `bb01ceb`, and their only
unproven members were "proof red at HEAD". Those proofs are now green in the clean checkout, so the rows are restated
here with that status updated. The authority for each set is unchanged: the plan doors 1 and 3-7, the amendment, and
research `09`.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| MP status rules (9) + rejects (4) | plan door 4; `presidency.py:54-78` (carried from `bb01ceb`, file unchanged) | all 13 by C18 unit; build layer C20, both green at `473e7b5` | - |
| Device status rules (4) + rejects (3) | plan door 4; `presidency.py:79-88` (carried) | all by C19 unit; build C20 | - |
| Veto status rules (2) | plan door 4 | `veto.01`, `veto.02` by C22, green at `473e7b5` | - |
| Bill status rules (6) + precedence pairs (4) | plan door 4 / AC 14 (carried) | all 10 rows by C23 unit; build C24 | - |
| Device decision on a joint roll call (9) | plan door 5, AC 23, 26, 40 (carried) | C33, C35, C37 green at `473e7b5` | - |
| Builder-added stop-the-build guards (5), recomputed at `473e7b5` | Handoff settled item 3; code `presidency.py:319-320` (no device), `:402-403` (voted device not kept/overridden), `:405-406` (TipoVotacao), `:205-206` (alias to absent member), `:273-274` (act outside every term) | each by its own C61 case (`test_presidencia_guards.py:33-39`), and F1 and F2 below kill two of them independently of the fixer's runs | - |
| Joint positions (6) + rejects (3) | plan door 5 (carried) | C34 unit; build C35, C38 | - |
| Terms (2) and dates | plan door 3 (carried) | C17 edges `test_presidencia_acts.py:61-64`, meta.terms `:82`, `:87`, green at `473e7b5` | - |
| Alias entries (4), recomputed at `473e7b5` | plan door 6 + research 09 l.147; the committed `etl/inputs/joint-vote-aliases.json` lists exactly `Márcio Bitar`/AC 285, `Janaina Carla Farias`/CE 6351, `Astr. Marcos Pontes`/SP 6009, `Prof. Dorinha Seabra`/TO 5386 | all 4 by C41 (`test_presidencia_joint.py:181`), green in the clean checkout | - |
| Unmatched (1) + ambiguous (1) | AC 31 as amended (carried) | C42 green at `473e7b5` | - |
| Resolution outcomes (6) | door 6 (carried) | exact, zero, several and period edge by C39; alias C41; homonyms C40 | - |
| Member veto base and indicators (4 indicators, 5 base cases) | door 6 + amendment (carried) | C44-C46 and `test_base_needs_the_members_house_votes`, green at `473e7b5` | - |
| `data/v4/presidencia` files (6 + votes dir) and schemas (6) | door 1 (carried) | C7, C49-C51; `validate` exit 0 on the committed fixture in the clean checkout | - |
| Dangling `memberId` cases in a house directory (4), recomputed at `473e7b5` | door 1 (house shapes identical to v3) + etl-senado C45; code `schema.py:162-164` now skips only v2 and the presidency scope, then calls `_dangling_member` (`:167-181`: votes, then authors) | v3 vote/author by etl-senado C45 (inside C57, 220 passed); v4 vote/author by C62 (`validate`) and C63 (presidency gate `cli.py:347-349`) | - |
| `validate` choices (5) | AC 4, door 1 | v2, v3, v4 house, v4 presidency and unknown version by C5, green at `473e7b5` | - |
| Congress source files (5 kinds + manifest) and retryables (4) | door 7 (carried) | C8, C9, C11 green at `473e7b5`; throttle C10 | - |
| Coverage counters (6), stage cases (4), act `sourceUrl` forms (3) | AC 35, 39; AC 18-22; AC 40 (sourceUrl recomputed at `473e7b5`) | C16, C25, C29-C30, C37, C42, C47, C51 green; MP, veto (all 7 exact) and bill forms by C52 | - |
| `build --contract 4` exit codes (3) | Observable | 0 C1/C2; 1 C4, C13, C61, C63; 2 C11, all green | - |

## Test policy rows

Re-judged at `473e7b5`: the rows unmet in round 1, plus the row classifying `schema.py`, which the fix touched.

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `presidency.py` | own layer: C18, C19, C22, C23, C34, C39; boundary: C20, C24, C35-C46, plus the five guards at the CLI by C61 | yes - all green at `473e7b5`; each guard has its own CLI case |
| Decides, reached across a boundary | `sources/congresso.py` | C8-C12 across HTTP | yes - all five green at `473e7b5` |
| Decides, reached across a boundary | `cli.py` | C3, C4, C13, C60, C63 at the CLI | yes - all green at `473e7b5` |
| Decides, reached across a boundary | `schema.py`, `publish.py` | C5, C49, C50, C62, C63 at the CLI | yes - all green; the new v4 referential branch is reached by `validate` (C62) and by the build gate (C63) |
| Decides, not reached across a boundary | none classified by the checks | - | yes (nothing classified) |
| Instrumentation, pass-throughs | `contract_v3.py` version parameter | covered by C1, C2 | yes - carried from `bb01ceb`, still green |

## Faults injected

Round 2 was verified at `473e7b5`. The scratch was `git worktree add /tmp/presid-v2 HEAD` with its own `uv sync`, and
nothing was copied in from the real tree.

- **Baseline:** the real tree's `git status --porcelain` was empty before the run.
- **Restore:** each fault was undone with `git -C /tmp/presid-v2 checkout -- <file>`, and the scratch porcelain was
  empty after the last restore.
- **Cleanup:** the scratch was removed with `git worktree remove --force`. `git worktree list` no longer shows it, and
  the real tree's porcelain was empty before this report was written.

| Mutation | Location | Killed |
| --- | --- | --- |
| R2-F1 (C61): an unknown `TipoVotacao` defaults to `painel` (`METHODS.get(..., "painel")`) | `etl/src/mandato_etl/presidency.py:404` | yes - `[unknown-tipo-votacao]` failed, the other 4 guard cases passed |
| R2-F2 (C61): alias-to-absent-member guard off (`if False:`) | `etl/src/mandato_etl/presidency.py:205` | yes - `[alias-to-absent-member]` failed, the other 4 passed |
| R2-F3 (C62): referential check back to v3 only (`_dangling_member(out) if version == 3 else None`) | `etl/src/mandato_etl/schema.py:164` | yes - C62 `[vote]`, `[author]` and C63 `[vote]`, `[author]` failed; `test_v4_fixture_house_validates` passed |
| R2-F4 (C63): presidency house gate ignores referential errors (`if error and "not in members.json" not in error`) | `etl/src/mandato_etl/cli.py:348` | yes - C63 `[vote]`, `[author]` failed; C62 passed (its own surface, distinct from F3) |
| R2-F5 (C52): veto URL `Codigo + 1` for every veto outside 2023 | `etl/src/mandato_etl/presidency.py:312` | yes - `test_source_urls` failed at `test_presidencia_contract.py:102` (`…/17970` vs `…/17969`). The pre-fix test (`git show f4cae72^:…`) passed under the same mutant, which confirms gap 4 was real and is closed |
| R1: MP rule `APROVADO_PLV` -> `approved` (carried from `bb01ceb`; `presidency.py` unchanged since) | `etl/src/mandato_etl/presidency.py:57` | yes - `test_mp_status_rules` failed |
| R1: veto `pending` only when every device is pending (carried) | `etl/src/mandato_etl/presidency.py:152` | yes - `test_veto_status` failed |
| R1: alias resolution skipped (carried) | `etl/src/mandato_etl/presidency.py:203` | yes - `test_aliases` errored |
| R1: unmatched vote no longer stops the build (carried) | `etl/src/mandato_etl/presidency.py:447` | yes - `test_unmatched_fails_closed` failed |
| R1: member veto base ignores the member's house (carried) | `etl/src/mandato_etl/presidency.py:474` | yes - `test_base_needs_the_members_house_votes` failed |

## Handoff deviations

Carried from `bb01ceb`: deviations (1), (2), (4), (5), (6) and (7) and the deferred per-house count were all accepted
there and are unchanged. The updates below were verified at `473e7b5`:

- **(3) Extra fail-closed guards.** This is now proven by C61. The fixer reports that removing each guard in turn
  failed only that guard's own case, and F1 and F2 above reproduce that independently. Accepted.
- **Round 1 gap 1 (`baf37ae`).** The orchestrator versioned the alias file with a `.gitignore` exception. The file holds
  only public names, UFs, official member ids and a note, with no CPF (AD-003). Accepted. Side note, not a check:
  the comment at `.gitignore:3` still says "only the CPF-free candidacy export is versioned (AD-011)", and it now has
  a second exception under it.
- **Round 1 gap 3 (`b6453d3`).** The fix is in `validate_dir`, so the presidency gate inherits it. F4 shows that C63
  pins the gate on its own surface, independent of `validate`. Accepted.
- **Round 1 gap 4 (`f4cae72`).** Accepted, confirmed by F5.
- **`checks.md` (`473e7b5`).** Adds C61-C63 and the matching Coverage, Test policy and Swept rows, and edits no
  earlier check's claim. The check count line reads 63.

Swept existing: carried from `bb01ceb`. `conftest.FakeCamara`, validate-then-swap in `publish.write` and the
`readers.read` allowlist were untouched by the fixes.

## Gate

- **Clean checkout of `473e7b5`** (own `uv sync`, network guard): `uv run --directory etl pytest -v` gave 463 passed
  and 0 failed. The named-proof batch gave 127 passed. C57 gave 220 passed.
  `mandato-etl validate tests/fixtures/v4/presidencia` exited 0.
- **Real tree at `473e7b5`:** 463 passed, named batch 127 passed, `validate` exit 0. This matches the clean checkout.
- **Tracked inputs:** `git ls-files etl/inputs` lists `etl/inputs/joint-vote-aliases.json`. Nothing a test reads is
  untracked.
- **Faults:** 5 injected in round 2, 5 killed. The 5 from round 1 are carried, all killed.
- `python3 ~/.claude/skills/tlc-spec-lean/scripts/validate_verification.py etl-presidencia`: exit 0, with 0 errors and
  0 warnings.
