# etl-senado verification

**Verdict**: FAIL
**Profile**: standard
**Diff range**: 6a0d768..774d0dc4bf9da3df741e189a289a0f0913a31901
**Round**: 1 - full
**Verifier**: independent sub-agent (author != verifier)

All 44 checks are proven, each by a located assertion. Every proof passes at `774d0dc`. All five injected faults were killed. The feature still fails on one coverage member taken from a binding source. AC 5 says a Senate response that carries `Deprecation` or `Sunset` triggers a stderr warning. `research/07-fontes-senado.md:25` ([V]) records that a deprecated Senate service answers **`301`** with those headers. The build follows the redirect silently and prints nothing, which a probe at HEAD reproduces (see Coverage, "deprecation signal shapes"). C5 proves the warning only on a `200` that carries the headers, a shape the research never observed. The builder disclosed this in the Handoff, item (8). The disclosure records the gap. It does not close it.

Scope: this feature's own commits are `a939d85..43c5073` on the first-parent line, plus the merge `774d0dc`. The merge brings in contract-v3 commits `d219961`, `c0ccf18`, `f69ab08`, `f079ce9` and `7986dcd`. Those were treated as already verified and touch only `.specs/features/contract-v3/*` and `etl/tests/test_v3_contract.py` (`git diff --stat 774d0dc^1 774d0dc`). `aad45c8` (the split of `contract_v3.assemble` into `finish`) is on this feature's first-parent line, so it was verified here.

## Binding sources

The profile is `standard`, not `ui`, so step 1 (design enumeration) does not run. The binding non-design sources were opened and read in full: `plan.md`, `checks.md`, `research/07-fontes-senado.md`, `AGENTS.md`, and `.specs/STATE.md` AD-016 to AD-018. Their facts drive the Coverage recompute below. That is where the AC 5 / research §2 gap shows up.

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `plan.md` (approved, resolved OQ 1-4 binding) | yes - read in full | none | - |
| `research/07-fontes-senado.md` | yes - read in full | none | see Coverage row "deprecation signal shapes" |
| `.specs/STATE.md` AD-016..AD-018 | yes - lines 22-24 | none | - |
| `AGENTS.md` | yes | none | - |

## Checks

All proofs were run at `774d0dc` in one batched invocation. It named each check's tests individually (52 node ids plus the eight C41 files): `uv run --directory etl pytest -v <node ids...>` gave exit 0 and **213 passed**, 0 failed, 0 error. The whole suite, `uv run --directory etl pytest -v`, gave exit 0 and **330 passed**. Every named test, and every `-k` form matched by prefix, appears in the output as a PASSED line. C42's and C44's shell commands both exited 0. Each test named below is new in this feature's diff (`etl/tests/test_senado_*.py`, added in `b743ccb..97a61fd`), except the C41 suite, which is contract-v3's.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | first build requests exactly the 9 lists + 4 authorship URLs, JSON + UA, manifest hash/bytes | `test_first_build_downloads_each_source_once` PASSED | `etl/tests/test_senado_sources.py:39` `sorted(sd.requests(sen)) == sorted(LISTS_2027 + AUTHORS_2027)`; `:41` accept `{"application/json"}`; `:43` UA; `:47-50` keys, sha256, bytes | PASS |
| C2 | cache hit = 0 requests and identical bytes; `--refresh` re-requests | `test_cache_hit_issues_no_request` PASSED | `test_senado_sources.py:63` `sen.requests == []`; `:65` `after == before`; `:67` `sorted(sd.requests(sen)) == sorted(first)` | PASS |
| C3 | 503x4 -> exit 2, URL on stderr, sleeps [1,2,4], no .part, output kept; 503 then 200 -> exit 0 | `test_download_failure_exits_2_and_keeps_output`, `test_retry_then_success` PASSED | `test_senado_sources.py:79` `== 2`; `:81` URL in err; `:82` `sen.sleeps == [1, 2, 4]`; `:83` no `*.part`; `:85` `after == before`; `:91-92` exit 0, `[1]` | PASS |
| C4 | at most 4 in flight | `test_at_most_four_concurrent_requests` PASSED | `test_senado_sources.py:120-121` 5 lists, 3 details; `:122` `sen.max_in_flight <= 4` | PASS |
| C5 | Deprecation/Sunset on a response -> warning with URL and Sunset date, exit 0 | `test_deprecation_header_warns_and_continues` PASSED | `test_senado_sources.py:129` `== 0`; `:131-132` one line, `"warning"` and `"Sun, 01 Feb 2026"` | PASS (the claim as written; the 301 shape is a Coverage gap) |
| C6 | bad envelopes exit 1 naming the file, no output | `-k test_bad_envelope_exits_1` (2) PASSED | `test_senado_sources.py:139-141` `== 1`, `"senado/votacao-57-2025.json"`, `not exists`; `:148-150` same for `legislatura-57.json` | PASS |
| C7 | `--house senado` without contract 3 -> exit 1 usage, 0 requests, no out; default out `data/v3/senado` only | `-k test_senado_needs_contract_3` (2), `test_senado_default_out` PASSED | `etl/tests/test_senado_cli.py:13` `== 1`; `:14` `"usage:"`; `:15` `sen.requests == []`; `:16` not exists; `:25` `v3/senado/meta.json` is_file; `:26-27` only `v3/senado` | PASS |
| C8 | members exactly 4605, 6358, 9201, 9202 with the named mandates; 5666 absent | `test_mandate_per_exercise_or_vote` PASSED | `etl/tests/test_senado_members.py:31` `sorted(members) == [4605, 6358, 9201, 9202]`; `:32-33` mandate map | PASS |
| C9 | half-open periods clipped; open period closed at next legislature / build time | `test_exercise_periods_are_half_open_and_clipped`, `test_open_exercise_closes_at_build_time` PASSED | `test_senado_members.py:39-45` the exact tuples incl. `("2027-02-01T00:00:00", "2027-03-01T09:00:00")` and `9202 == []`; `:52` `"2026-09-27T09:00:00"` | PASS |
| C10 | single `Mandato`/`Exercicio`/`Suplente` objects read as lists; `as_list` | `test_single_objects_read_as_lists` PASSED | `test_senado_members.py:62-64` fixture is objects; `:66` periods; `:67-69` `as_list` three cases | PASS |
| C11 | name/party/uf from latest vote, mandate party per legislature, list fallback | `test_member_fields_from_latest_vote_or_list` PASSED | `test_senado_members.py:76` `("Nove Dois Zero Um", "PSB", "SP")`; `:77-78` `PT`/`PSB`; `:80` `("Flávio Dino", "PSB", "MA")` (recorded 4605 has no identification UF, so `MA` comes from the mandate: discriminating) | PASS |
| C12 | photoUrl and sourceUrl patterns | `test_member_urls` PASSED | `test_senado_members.py:87-88` both f-string equalities | PASS |
| C13 | no personal key or sentinel value in output; allowlist holds none | `test_no_personal_field_reaches_output` PASSED | `test_senado_members.py:107` sentinels present in raw; `:113` absent from output; `:115-116` keys; `:126-127` allowlist keys | PASS |
| C14 | one mismatch line per legislature, `1` (members) and `2` (indicators) | `test_mismatch_warning_per_legislature` (2) PASSED | `test_senado_members.py:142` `len(lines) == 1`; `:143` `"senado 57"` and `f": {expected} "` | PASS |
| C15 | recorded ids exactly the 12, no 7045; twin rule cases | `test_recorded_roll_calls_once`, `-k test_dedupe_twins` (2) PASSED | `etl/tests/test_senado_roll_calls.py:60-62`; `:73` `== [7046, 6704]`; `:85` `== [10, 12, 13, 14, 15]` (twin dropped, lone, differing set, other session, both sequenced kept) | PASS |
| C16 | 2023-01-31 record not written; house/organ/legislature | `test_only_records_inside_a_legislature` PASSED | `test_senado_roll_calls.py:90` `str(sd.BEFORE) not in`; `:92` `("senado", "PLEN", 57)` | PASS |
| C17 | `ballot` from `votacaoSecreta`; no symbolic; `"X"` exits 1 naming roll call | `test_ballot_from_votacao_secreta` PASSED | `test_senado_roll_calls.py:97-99`; `:103` `== 1`; `:105` `"6901"` and `"'X'"` | PASS |
| C18 | 11 official examples -> door 2 kind/rule, at unit and build | `-k test_official_examples` (11), `test_build_writes_the_official_kinds` PASSED | `test_senado_roll_calls.py:111` `classify.classify(...) == expected`; `:117` built `(kind, kindRule) == expected` | PASS |
| C19 | rules file exactly door 2, pt-BR description terms, no banned words, version 1, 9999 unclassified | `test_rules_file_is_the_senate_ruleset` PASSED | `test_senado_roll_calls.py:122` ids; `:124-126` keys and `pattern`; `:128-129` terms and banned words; `:130` `{"version": 1}`; `:132` `("unclassified", None)` | PASS |
| C20 | vote map 13 codes + 3 unknowns; orientation map 5 + 2 unknowns | `-k test_senate_position_of` (16), `-k test_senate_orientation_of` (7) PASSED | `test_senado_roll_calls.py:145` `senate_position_of(official) == position`; `:150-151` raises; `:156`; `:161-162` raises | PASS |
| C21 | built `official` verbatim except LS/LP/LAP -> `Licença`, `notVoting` | `test_built_votes_keep_official_or_licenca` PASSED | `test_senado_roll_calls.py:171` eight codes; `:172` `officials["Licença"] == {"notVoting"}`; `:173` none of LS/LP/LAP; `:179` no `"official":"LS"` byte (compact separators, `publish.py:12`, so the substring test is effective) | PASS |
| C22 | unknown vote/orientation -> exit 1 with value and 6902, output kept; unjoined unknown ignored | `-k test_unknown_value_stops_the_build` (2), `test_unjoined_orientation_is_not_mapped` PASSED | `test_senado_roll_calls.py:197` `== 1`; `:199` `value in err and "6902" in err`; `:201` `after == before`; `:208` `== 0` | PASS |
| C23 | open tallies counted, secret official | `test_tallies` PASSED | `test_senado_roll_calls.py:213-217` five literal dicts; `:221` V4 `{40, 1, 1}` | PASS |
| C24 | orientation join by sequencial, Governo position, null cases, verbatim bench, null voto dropped | `test_orientation_join` PASSED | `test_senado_roll_calls.py:229-235` `("no", 15)`, `"yes"`, `("yes", 12)`, `(None, 11)`, `(None, 0)` x2; `:240` `"free"`; `:242-243` `Republica`/`OBSTRUÇÃO`; `:244` `["Governo"]` | PASS |
| C25 | 7046 fields; 6679 approved | `test_roll_call_fields` PASSED | `test_senado_roll_calls.py:249-255` each literal | PASS |
| C26 | proposition 8761212 and the sourceUrl pattern | `test_proposition_source_url` PASSED | `test_senado_roll_calls.py:261-262`; `:266` pattern for every proposition | PASS |
| C27 | participation numbers | `test_participation` PASSED | `etl/tests/test_senado_indicators.py:23-27` (recomputed on paper from `senado_data.py:198-210`: Ana 5/7 4/5, Beto 4/4 3/3, Caio 6/7 5/5, Dani 3/7 2/5, Eva 6/7 5/5 - agree) | PASS |
| C28 | governmentAlignment numbers | `test_government_alignment` PASSED | `test_senado_indicators.py:31-35` (recomputed: base V1 yes, V2 no, V5 yes, V7 yes; agree) | PASS |
| C29 | partyAlignment numbers; S/Partido partyMajority null | `test_party_alignment` PASSED | `test_senado_indicators.py:39-43`; `:46-47` both `Sim`/`S/Partido`; `:51` `partyMajority is None` | PASS |
| C30 | authored/first/requirements counts and exclusions | `test_proposition_counts` PASSED | `test_senado_indicators.py:60-63` `(6, 5, 3)`, `(1, 1, 0)`, `(1, 0, 0)`; `:67-68` 8004/8011/8012/8013/8014 absent | PASS |
| C31 | `/processo/{id}` only for multi-author, once each; PEC authors | `test_detail_only_for_multi_author` PASSED | `test_senado_indicators.py:75` exactly three paths; `:77` authors list | PASS |
| C32 | symbolicMerit null; coverage symbolic null | `test_symbolic_is_null` PASSED | `test_senado_indicators.py:82` `[None] * len(...)`; `:83` `== [None]` | PASS |
| C33 | 56th-legislature record changes no mandate | `test_indicators_use_only_the_legislature` PASSED | `test_senado_indicators.py:96` `after == before`; `:97` `"6800" not in` | PASS |
| C34 | every file passes v3 schema (in-package + Draft 2020-12), validate exits 0 | `test_every_file_passes_its_schema` (3) PASSED | `etl/tests/test_senado_contract.py:31` `validate == 0`; `:36` `first_error is None`; `:37` `Draft202012Validator(spec).is_valid` | PASS |
| C35 | schema failure exits 1 naming `members.json` and `uf`, output kept | `test_schema_failure_keeps_previous_output` PASSED | `test_senado_contract.py:47` `== 1`; `:49` `"members.json"`, `"uf"`; `:50` `snapshot(sen) == before` | PASS |
| C36 | meta shape, legislature URLs, coverage rows, classification, sources = manifest; indicators coverage row | `test_meta_shape` PASSED | `test_senado_contract.py:60`; `:61-66` legislatures; `:67` `[57, 58]`; `:68`; `:70` `sorted(manifest, ...)`; `:77-80` the coverage row literal | PASS |
| C37 | byte-identical rebuild; ordering | `test_build_is_deterministic` PASSED | `test_senado_contract.py:88` `snapshot(sen) == first`; `:89-90` names; `:92` ids date-descending; `:95` votes sorted (see P1) | PASS |
| C38 | summary line per legislature; absent with `--quiet` | `test_log_line_per_legislature` (2) PASSED | `test_senado_contract.py:114` `line in ...splitlines()`; `:116` `line not in` | PASS |
| C39 | exact layout, no `full-texts/` | `test_layout` PASSED | `test_senado_contract.py:125` `set(snapshot(sen)) == expected`; `:126` | PASS |
| C40 | `house: "senado"` everywhere, unique `(house, id)`, decimal ids | `test_house_and_identity` PASSED | `test_senado_contract.py:134`; `:137` `len(keys) == len(set(keys))`; `:139` `isdigit()` | PASS |
| C41 | whole contract-v3 suite green | the eight files of the proof line, in the same batch, all PASSED | every `tests/test_v3_*.py` and `tests/test_v2_frozen.py` node PASSED in the 213 | PASS |
| C42 | no `site/`, `publish.yml` or v2 golden change | `test -z "$(git diff --name-only 6a0d768..HEAD -- site .github/workflows/publish.yml etl/tests/fixtures/v2-golden.json)"` exit 0 | empty diff list | PASS |
| C43 | Senate allowlist projection keys (doors 1, 5, 6) | `test_allowlist_projection` PASSED | `test_senado_sources.py:166` `set(r) == DOOR_1_ROLL_CALL \| {"votos"}`; `:168-169` the five vote keys; `:171` no `senado*` kind in `readers.ALLOWLIST` | PASS |
| C44 | AGENTS.md declares standard | `grep -q 'A feature \`etl-senado\` roda em \`standard\`' AGENTS.md` exit 0 | `AGENTS.md:26` | PASS |

### Deviation judged: contract-v3 test edit

`etl/tests/test_v3_cli.py:50-51` changed `["--contract", "3", "--house", "senado"]` to `["--house", "presidencia"]` (commit `4b48a97`). **This did not weaken anything.** Before the change, `senado` was rejected by argparse `choices=HOUSES` with `HOUSES = ("camara",)`. Now `presidencia` is rejected by the same `choices` (`etl/src/mandato_etl/cli.py:19`, `:57`). The same three assertions remain (exit `1`, `usage:` on stderr, 0 requests). The rejection path under test is identical. The original case could not stay, because `senado` is now valid by design, as contract-v3's plan anticipated. The behaviour that is still forbidden, `senado` without `--contract 3`, is now proven more strongly by C7, which also asserts that no output directory is written (`test_senado_cli.py:13-16`). One leftover: contract-v3's `checks.md` C4 text still reads "`--house senado` ... exit `1`" (`.specs/features/contract-v3/checks.md:34`). That is now documentary drift in another feature's artifact. It is not a defect in this one (P3).

## Coverage

The sets below were recomputed from their authority, not read from `checks.md`. Vote codes come from research/07 §4. The sensitive map comes from AD-018 / plan OQ 2. The rule table comes from plan door 2. The twin cases come from research/07 §4 "Duplicatas". The exercise cases come from research/07 §3 and plan AC 8/9. The deprecation shapes come from research/07 §2 and §9.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| Senate vote codes (13) | research/07 §4 table | `Votou`, `Sim`, `P-NRV`, `Não`, `AP`, `LS`, `MIS`, `Presidente (art. 51 RISF)`, `NCom`, `LP`, `Abstenção`, `NA`, `LAP`: each a parametrised case at `test_senado_roll_calls.py:145` (16 ids incl. 3 unknowns); at build `:171` (8 codes) and `:172-173` (LS/LP/LAP); `Não`/`Abstenção` at build through C27/C28 (Caio V2 `Não` matches `NÃO`, V3 `Abstenção` counts as participation) | - |
| AD-018 sensitive codes (3) | STATE.md AD-018, plan OQ 2 | `LS` (Ana V7), `LP` (Dani V7), `LAP` (Eva V7), all in `senado_data.py:198-210`; each would break `test_senado_roll_calls.py:173`/`:179` if published verbatim (F2) | - |
| orientation values (5 + null + unknown) | research/07 §5, plan door 3 | `SIM`, `NÃO`, `ABSTENÇÃO`, `OBSTRUÇÃO`, `LIVRE` at `:156`; `null` dropped at `:244`; unknown at `:161`, `:199` | - |
| `governmentOrientation` cases (4) | AC 20 | Governo present `:229`; no Governo `:232-233`; no feed entry `:234`; seq null `:235` | - |
| ruleset v1 rules (7) + order + no-match | plan door 2 | each rule at least once in `OFFICIAL_EXAMPLES` (`:17-22`) at `:111`/`:117`; first match `7046` (matches `.02` and `.07`) gives `.02`; no match `9999` at `:132`; pattern literals at `:125-126` | - |
| twin-rule cases of research (pairs dropped 6; both-sequenced groups kept 3; lone nulls kept 4) | research/07 §4 | dropped: recorded `7045/7046` at `:73` and `:60-62`, synthetic `11` at `:85`; both sequenced: synthetic `15` at `:85`; lone null: recorded `6704` at `:73`, synthetic `12`; differing vote set (door 4) `13`; other session `14` | - |
| exercise filter cases (7) | research/07 §3, AC 8/9 | `Exercicio` intersecting (4605, 6358) `test_senado_members.py:31-33`; vote record only (9202) `:33`; neither (5666) `:31`; listed in 58th with no exercise or vote there (6358) `:33`; `DataFim` -> next day `:39-42`; open, closed at next legislature `:43`; open, closed at build time `:52` | - |
| symbolic null (2) | plan OQ 1, AC 30 | `symbolicMerit` `test_senado_indicators.py:82`; `coverage.rollCalls.symbolic` `:83`, `test_senado_contract.py:78` | - |
| participation codes (4) | AC 26 | voted, secret, presiding, notVoting: Ana's row `test_senado_indicators.py:23` | - |
| authorship filter cases (8) | AC 27-29, research/07 §6 | all eight in `ANA_PROCESSES` (`senado_data.py:220-235`), asserted at `test_senado_indicators.py:60-68`, `:75-77` | - |
| exit codes (0/1/2) and flags (6) | plan Observable | as `checks.md` Coverage rows, each re-located in the Checks table above | - |
| **deprecation signal shapes (2)** | **research/07 §2 line 25 [V]: "Serviços depreciados respondem `301` com `Deprecation`, `Sunset`"**; §9: "falhar alto em `301` para serviço com `Sunset`"; plan AC 5 | `200` with headers -> C5 (`test_senado_sources.py:128-132`). **`301` with headers -> no proof, and the code does not handle it**: `_download` reads headers only from the final response (`etl/src/mandato_etl/sources/senado.py:24-26`), and urllib follows the `301` before that. A probe at HEAD in the scratch worktree served `301` + `Deprecation: Tue, 18 Mar 2025` + `Sunset: Sun, 01 Feb 2026` -> `Location: /new` (200 `[]`) to `senado._download`. Result: `downloaded: b'[]' warnings: []` | 301 + Deprecation/Sunset (the only shape the research observed): no warning, no proof |

Swept for sets that got no row: the plan's `Landing` doors 1-6 all have rows in `checks.md`. So do the `Relations` entities and the `Observable` surfaces. `approved` (`A`/`R`) is proven by C25. No other unrowed enumeration was found.

`Swept` rows that say "existing" were re-read against the code. "Manifest written once from the main thread" (concurrency): `fetch` writes it after `pool.map` returns (`sources/senado.py:62-71`). That happens once per `fetch` call, three calls per build, each on the main thread. The constraint holds.

## Test policy rows

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary (CLI, HTTP) | `classify.py` (Senate maps), `sources/senado.py`, `cli.py` (`--house senado` guard) | own layer + boundary; exit code + stderr at CLI; one case per decision-table row at own layer | no - one decision-table row is unproven. The deprecation decision in `sources/senado.py:24-26` has two rows from its authority (research/07 §2), and only the `200` row has a proof; the `301` row has none (Coverage gap above). Everything else in the row is met. Vote map: 13 + 3 unit cases (C20) and the build (C21, C22 exit 1 + stderr). Orientation map: 5 + 2 unit (C20) and build (C24, C22). Twin rule: unit, 4 cases + other session (C15 `:85`), and build (C15 `:60-62`). Envelopes, retry and cache: C1-C6 over a real socket with exit code and stderr. Guard: C7 exit 1 + `usage:` |
| Decides, not reached across a boundary | `contract_v3.py` Senate assembly (mandate existence, periods, member fields, tallies, orientation join, authorship, first signer) | one asserted case per table row | yes. Every row listed in `checks.md` Test policy evidence has an asserted case through dataset builds (C8-C11, C23, C24, C27-C31). `finish` (the split in `aad45c8`) is also proven for the Câmara by the green C41 suite |
| Instrumentation, pass-throughs | `readers.py` `project`/`read_senado`/`as_list` | covered by consumers | yes. C13, C43 and C10 (`as_list` directly); the envelope branches of `read_senado` by C6 |

## Faults injected

Each fault ran in the scratch worktree `git worktree add --detach /tmp/verify-etl-senado HEAD`, after its own `uv sync`. Each was applied by exact string replacement, run against the narrowest covering proof, then reverted with `git checkout --`. The scratch status was clean after the five. The worktree was then removed with `git worktree remove --force` and pruned. The real tree's `git status --porcelain` was empty before and is still empty after. `git stash` was never used.

| Mutation | Location | Killed |
| --- | --- | --- |
| F1 twin rule ignores the vote set: drop any null-sequencial record whose session has a sequenced one | `etl/src/mandato_etl/sources/senado.py:148` | yes - `test_dedupe_twins_cases`: `[10, 12, 14, 15] == [10, 12, 13, 14, 15]` fails |
| F2 sensitive map bypassed: vote `official` written verbatim instead of `published_official(SENATE, ...)` | `etl/src/mandato_etl/contract_v3.py:483` | yes - `test_built_votes_keep_official_or_licenca` errors in its fixture: the build exits 1 because contract-v3's v3 schema refuses a verbatim `LS`/`LP`/`LAP`. Two layers guard it (P2) |
| F3 orientation join: `governmentOrientation` taken from the first bench instead of `Governo` | `etl/src/mandato_etl/contract_v3.py:451` | yes - `test_orientation_join`: `('free', 15) == ('no', 15)` fails for 6755 |
| F4 exercise filter treats every `Exercicio` as open-ended (ignores `DataFim < legislature start`) | `etl/src/mandato_etl/contract_v3.py:518-521` (`_intersects`) | yes - `test_mandate_per_exercise_or_vote`: build exits 2 because 5666 gains a mandate and its unserved authorship list 404s (P2) |
| F5 symbolic counted for the Senate: `finish(..., symbolic=True)` | `etl/src/mandato_etl/contract_v3.py:661` | yes - `test_symbolic_is_null`: `[0] == [None]` fails |

## Gate

`uv run --directory etl pytest -v` at `774d0dc`: 330 passed, 0 failed. The batched named-proof run: 213 passed, 0 failed. C42 and C44 commands: exit 0.

## Ranked gaps

1. **Deprecation via `301` not warned (AC 5 vs research/07 §2).** Check C5; code at `etl/src/mandato_etl/sources/senado.py:24-26`; proof at `etl/tests/test_senado_sources.py:125-132`. A deprecated Senate service answers `301` with `Deprecation`/`Sunset` (research [V]), and the build follows it silently. Fix direction: read the headers of each redirect hop (for example with a `urllib.request.HTTPRedirectHandler` subclass that records them) and warn from there. Add a proof that serves `301` + headers -> `Location`.

## Precision notes (non-blocking)

- P1 - C37 "ordered by accent-stripped name": the named proof uses ASCII names only (`test_senado_contract.py:89-90`). The extra `test_build_orders_by_accent_stripped_name` (`:98-103`) orders "Flávio Dino" between "Ana..." and "Nove...", and that order is the same with or without accent stripping. The shared `sort_name` is still proven discriminatingly for the Câmara by the C41 suite, through the same `finish`.
- P2 - F2 and F4 were killed by side effects, not by the check's own assertion line. F2 was killed by the schema's sensitive-code refusal (contract-v3) during the fixture build. F4 was killed by an unexpected request that got a 404. Both kills are real, but C21's `:173`/`:179` and C8's `:31` are not the lines that fired.
- P3 - `.specs/features/contract-v3/checks.md:34` C4 still says `--house senado` exits 1. The test now uses `presidencia`, so the text should follow.
