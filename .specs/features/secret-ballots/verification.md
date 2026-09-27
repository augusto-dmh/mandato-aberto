# secret-ballots verification

**Verdict**: PASS
**Profile**: standard
**Diff range**: daceaaf..75eb1df (fix diff 6fa6c7b..75eb1df)
**Round**: 2 - scoped
**Verifier**: independent sub-agent (author != verifier)

Round 1 (HEAD `495a22b`, report committed as `6fa6c7b`) returned FAIL on three findings:
- The participation member "in exercise, no record in a secret roll call" had no proof, and fault F3 survived.
- Two members of door 1's `secret` validation had no proof.
- The participation `Test policy` row was not met.

The fix `75eb1df` makes three changes:
- It adds `test_no_record_in_a_secret_roll_call_does_not_count` and `test_validate_requires_secret_in_both_files` to `etl/tests/test_secret_ballots.py`.
- It adds 6 lines of assertions (og:title, og:description, heading and note) to the `secret roll call` test in `site/tests/build.test.ts`.
- It extends C4, C6 and C12 in `checks.md` under a new paragraph, "Extended after verification round 1 (maintainer, 2026-09-27)".

`git diff --stat 6fa6c7b..75eb1df -- etl/src site/src` is empty. Adding `etl/schema` to that diff gives 0 lines as well, so no production code or contract changed. At `75eb1df` all 18 checks pass, the two new members and the unmet row are proven, and F3 is now killed.

## Binding sources

Carried from 495a22b: none. The plan marks no binding design source for this feature, so step 1 does not run under `standard`.

## Checks

Verified at 75eb1df. All proofs ran again in full, apart from C8:
- One pytest invocation: `uv run --directory etl pytest -v tests/test_secret_ballots.py tests/test_publish.py::test_meta_fields`. It exited 0 with 13 passed, and each named test is listed individually as PASSED, including both new tests and all five `test_secret_rule_table[votes0..4]`.
- One vitest invocation: `npm --prefix site test -- tests/data.test.ts tests/build.test.ts tests/format.test.ts tests/cards.test.ts tests/language.test.ts --reporter=verbose -t "<16-name alternation>"`. It exited 0 with 18 passed and 29 skipped, and each of the 16 named tests is listed with a check mark.
- C7, C9 and C18 ran as the literal commands in `checks.md` and exited 0.
- C8 is carried from 495a22b, because nothing under `etl/src` or `etl/schema` changed (the diff over those paths is empty) and `data/raw/` was not touched.

The `site/tests/build.test.ts` citations were refreshed, because the fix moved lines at 339 and after.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | `secret` true only for 100-6, false for the other 7, in index and documents | pytest `test_secret_flag_marks_only_the_all_empty_roll_call` PASSED | `etl/tests/test_secret_ballots.py:32` `index[R6]["secret"] is True`; `:33` doc `is True`; `:35`/`:36` `is False` over `OPEN` (7 ids, `:14`) | PASS |
| C2 | `is_secret` table over 5 inputs | pytest `test_secret_rule_table[votes0..votes4]` 5 PASSED | `etl/tests/test_secret_ballots.py:41` table `[([], False), ([""], True), (["", ""], True), (["", "Sim"], False), (["Artigo 17"], False)]`; `:44` `compute.is_secret(votes) is expected` | PASS |
| C3 | 100-6 tallies 12/5/2 in both places; 100-1 keeps 2/1/0 | pytest `test_secret_tallies_come_from_official_totals` PASSED | `etl/tests/test_secret_ballots.py:49`-`:50` `== {"yes": 12, "no": 5, "others": 2}`; `:51`-`:52` `== {"yes": 2, "no": 1, "others": 0}` | PASS |
| C4 | variant 4/5, 3/3, 0/0 and open 100-3 not counted; plus a secret `9-1` with a record by 1 only: 1 -> 2/2, 2 -> 1/2 | pytest `test_secret_record_counts_for_participation`, `test_no_record_in_a_secret_roll_call_does_not_count` PASSED | `etl/tests/test_secret_ballots.py:57`-`:59` the three participations; `:62`-`:63` R3 empty and `secret is False`; `:121` `9-1` `secret == [True]`; `:123` `participation == {1: {"count": 2, "total": 2}, 2: {"count": 1, "total": 2}}` | PASS |
| C5 | alignments unchanged; every 100-6 vote entry has `partyMajority: null` | pytest `test_secret_record_is_not_a_valid_vote` PASSED | `etl/tests/test_secret_ballots.py:74`-`:75` equality to the literal table at `:68`-`:72`; `:79` `partyMajority is None` | PASS |
| C6 | meta version 2; schema const 2; validate 1 for all 4 missing / non-boolean cases across both files; 0 unmodified | pytest `test_publish.py::test_meta_fields`, `test_validate_requires_a_boolean_secret`, `test_validate_requires_secret_in_both_files` PASSED | `etl/tests/test_publish.py:39` `== 2`; `etl/schema/meta.schema.json:19` `"const": 2` (read); `etl/tests/test_secret_ballots.py:84` `== 0`; `:91` index missing `== 1`; `:100` doc `"no"` `== 1`; `:133`-`:134` doc missing `== 1`, err names `roll-calls/100-1.json`; `:141`-`:142` index `"no"` `== 1`, err names `roll-calls.json` | PASS |
| C7 | etl test diff touches only the 5 listed files; whole suite passes | literal C7 command exit 0; `101 passed in 7.63s` | `git diff --name-only 9921e6c -- etl/tests` shows exactly `conftest.py test_publish.py test_roll_calls.py test_schema.py test_secret_ballots.py`; changed approved assertions `etl/tests/test_publish.py:39`, `etl/tests/test_roll_calls.py:44`, `:63`, `etl/tests/test_schema.py:48` | PASS |
| C8 | real cache: exactly 2 secret roll calls with official tallies; validate 0 | carried from 495a22b: literal C8 command exit 0 | `data/out/roll-calls.json` secret set `2645346-18` `{404, 61, 1}` and `2576389-4` `{388, 22, 11}`; code under test `etl/src/mandato_etl/compute.py:173`-`:179` unchanged since | PASS |
| C9 | site fixture validates, version 2, 8 roll calls, only 100-6 secret | literal C9 command exit 0 | inline asserts over `site/tests/fixtures/out/meta.json:1` and `site/tests/fixtures/out/roll-calls.json:1` | PASS |
| C10 | exact version message for 3 and 1; astro build fails on 3 | vitest `rejects schema_version 3`, `rejects schema_version 1`, `fails on schema_version 3` passed | `site/tests/data.test.ts:32`-`:33`, `:41`-`:42` `toThrowError(new Error("...schema_version <n>; this site reads 2"))`; `site/tests/build.test.ts:119` `status).not.toBe(0)`, `:120`-`:121` the message | PASS |
| C11 | `secret` on every summary and document; schema-fields test passes on v2 | vitest `reads the secret flag`, `reads only schema fields` passed | `site/tests/data.test.ts:49` `typeof r.secret` is boolean; `:51`-`:54` true / false for 100-6 / 100-1; `:110` `sorted(rollCall)).toEqual(keys(rollCallSchema))` | PASS |
| C12 | 100-6 page: notice, official totals 12/5/2, `Quem votou` + note, one group of 3 in order, exact og:title and og:description, no `Registro sem voto` | vitest `secret roll call` passed | `site/tests/build.test.ts:336` notice; `:337` `"Totais oficiais da Câmara Sim 12 Não 5 Outros 2"`; `:338` no `Registro sem voto`; `:339` `og:title` `toBe("Votação nominal de 01/08/2025: votação secreta")`; `:340`-`:342` `og:description` exact; `:344` `/^Quem votou Em ordem alfabética\. Partido na data da votação\. Deputados que votaram \(3\)/`; `:345` one `vote-group`; `:347` group heading; `:352` entries `toEqual` 101, 102, 103 | PASS |
| C13 | open 100-1 has no notice or official label, still `Sim (2)` / `Não (1)`; 100-3 keeps `Registro sem voto` | vitest `open roll calls carry no secret notice` passed | `site/tests/build.test.ts:362`-`:363` `not.toContain`; `:364`-`:365` group headings; `:367` 100-3 no notice; `:369` `/^Registro sem voto \(1\)/` | PASS |
| C14 | `voteLabel` 3 rows; profile 101 rows 100-6 / 100-3 | vitest `vote labels for secret ballots`, `profile secret vote` passed | `site/tests/format.test.ts:33`-`:35`; `site/tests/build.test.ts:281` `toBe("Votação secreta")`; `:282` `toBe("Registro sem voto")` | PASS |
| C15 | 101 `4 de 5`, `--share: 0.8`, base ends `inclusive Art. 17 e votações secretas.`; 102 `3 de 3` | vitest `profile indicators` passed | `site/tests/build.test.ts:199` row values; `:207`, `:209` `style="--share: ${share101}"`, `:211` 102; `:214` base line `toMatch` | PASS |
| C16 | 8 roll-call pages, 12 dated pages, home `8 votações nominais`, cards 4 de 5 / 3 de 3 | vitest 4 named tests passed | `site/tests/build.test.ts:108` `toEqual(ROLL_CALLS)` with `:19`; `:135` `toHaveLength(12)`; `:181` `"8 votações nominais"`; `site/tests/cards.test.ts:53` `"4 de 5"`; `:70` `"3 de 3"` | PASS |
| C17 | no forbidden term in site source; built 100-6 page has no `%` | vitest `site source uses no forbidden term`, `built pages use no forbidden term and no percentage` passed | `site/tests/language.test.ts:38` `expect(hits).toEqual([])`; `site/tests/build.test.ts:418` `not.toContain("%")`, `:419` forbidden terms, over `allPages()` (`:57`, includes 100-6) | PASS |
| C18 | CI sequence exits 0 | literal C18 command exit 0 | site 8 files, 56 passed; `astro build` `Complete!`; etl `101 passed in 7.55s`; `sha256sum -c` of `site/package-lock.json:1` OK (unchanged); porcelain empty afterwards | PASS |

## Approved-test diff audit

Verified at 75eb1df.
- **Fix diff (`6fa6c7b..75eb1df`):** it only adds test lines: 41 in `etl/tests/test_secret_ballots.py` and 6 in `site/tests/build.test.ts`, with 0 removed.
- **`checks.md`:** the 6 removed lines are the claims of C4, C6 and C12 and the two Coverage rows plus one Test policy evidence line. Each is replaced by a superset under the maintainer's "Extended after verification round 1" paragraph, which says "Claims only gain cases; no assertion changes". That matches the diff.
- **Earlier changes to approved tests:** carried from 495a22b. They stay within the two renegotiation paragraphs, and nothing was skipped or weakened.

## Coverage

Verified at 75eb1df for the three rows the fix touched: participation, `secret` schema validation and roll-call page copy. The other rows are carried from 495a22b, because their authority and their code did not change (the diff over `etl/src`, `etl/schema` and `site/src` is empty) and their citations were refreshed above.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| secret rule inputs (5) - carried from 495a22b | door 2 literal; `compute.py:166`-`:168` | 5 inputs -> `test_secret_ballots.py:41`,`:44` | - |
| tallies source (3) - carried from 495a22b | AC 2, renegotiation prose, `compute.py:173`-`:179` | official when secret -> `test_secret_ballots.py:49`-`:50`; counted when open -> `:51`-`:52`; secret without columns -> `etl/tests/test_indicators.py:118` `[empty]` runs without raising | - |
| participation decision table for the new clause (4) - verified at 75eb1df | AC 3, `compute.py:230`-`:233` | non-empty vote -> `test_secret_ballots.py:57`; empty record in open roll call -> `:57`,`:62`-`:63`; record in secret roll call -> `:57`-`:58`, `:123` (deputy 1); in exercise with no record in a secret roll call -> `:123` (deputy 2 `{"count": 1, "total": 2}`), F3 killed | - |
| deputies in the variant (3) - carried from 495a22b | plan assumption | 101 `:57`, 102 `:58`, 103 `:59` | - |
| alignment indicators unaffected (2) - carried from 495a22b | AC 4 | `test_secret_ballots.py:74`, `:75` | - |
| contract version places (4) - carried from 495a22b | door 1 | `cli.py:18` -> `test_publish.py:39`; `meta.schema.json:19` -> `test_secret_ballots.py:84` + `test_schema.py:48`; `data.ts:10` -> `data.test.ts:33`,`:42`; fixture `meta.json` -> C9 | - |
| `secret` field places (4) - carried from 495a22b | door 1 + data flow | index `test_secret_ballots.py:32`; doc `:33`; site summary `data.test.ts:49`; site document `data.test.ts:53`,`:110` | - |
| `secret` schema validation (4) - verified at 75eb1df | door 1 literal: required + boolean on both files | index missing -> `test_secret_ballots.py:91`; doc non-boolean -> `:100`; doc missing (`etl/schema/roll-call.schema.json:15`) -> `:133`, F6 killed; index non-boolean (`etl/schema/roll-calls.schema.json:102`) -> `:141`, F7 killed | - |
| roll-call page states (3) - carried from 495a22b | `votacoes/[id].astro:20`,`:43`,`:51` | secret -> `build.test.ts:336`-`:352`; open with votes -> `:362`-`:365`; open with empty record -> `:367`,`:369` | - |
| secret-page copy beyond the notice (4) - verified at 75eb1df | C12 as extended (round-1 precision gap 3) | og:title -> `build.test.ts:339`, F9 killed; og:description -> `:340`-`:342`; `Quem votou` heading and note -> `:344`, F8 killed | - |
| profile vote labels (4) - carried from 495a22b | `format.ts:52`-`:54` | `format.test.ts:29`, `:33`-`:35`; `build.test.ts:281`-`:282` | - |
| `GET /votacoes/{id}/` statuses (2) - carried from 495a22b | plan Surface | 200 -> `build.test.ts:110`; unknown id has no page -> `:108` exact list | - |
| real secret ballots (2) - carried from 495a22b | plan Problem, `data/raw/` | C8 inline exact-set assert | - |
| startup config: contract version (2 assemblies) - carried from 495a22b | read directly | `etl/src/mandato_etl/cli.py:18`; `site/src/lib/data.ts:10` | - |

## Test policy rows

Verified at 75eb1df for the row that round 1 found unmet. The rest are carried from 495a22b, because none of the files they classify changed.

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `compute.is_secret` (`compute.py:166`) - carried | own `test_secret_ballots.py:44` (5 rows) · boundary C1 | yes |
| Decides, reached across a boundary | `compute` tallies source (`compute.py:175`) - carried | boundary C3, both published rows | yes |
| Decides, reached across a boundary | `compute` participation clause (`compute.py:230`-`:233`) - verified at 75eb1df | boundary C4 on the built contract (`test_secret_ballots.py:57`-`:59`) · own layer `compute.assemble` with one case per row, including no record in a secret roll call (`:123`) | yes |
| Decides, reached across a boundary | `site/src/lib/format.ts` `voteLabel` - carried | own `format.test.ts:33`-`:35` · boundary `build.test.ts:281`-`:282` | yes |
| Decides, reached across a boundary | `site/src/pages/votacoes/[id].astro` - carried | boundary C12 and C13 on the built page | yes |
| Decides, not reached across a boundary | none in this diff | n/a | n/a |
| Instrumentation, pass-throughs | `readers.py` allowlist; `data.ts:172`; `deputados/[id].astro:161` - carried | consumer proofs C3, C11, C14 | yes |

## Faults injected

Verified at 75eb1df. The faults ran in a fresh scratch worktree (`git worktree add <scratchpad>/wt2 HEAD`) with `site/node_modules` symlinked to the real one. The real tree's `git status --porcelain` was empty before and after the worktree was removed. This round re-injected F3 and added one fault on each new assertion surface. F1, F2, F4 and F5 are carried from 495a22b: their code and tests are unchanged, and all four were killed.

| Mutation | Location | Killed |
| --- | --- | --- |
| F1 `len(votes) > 0` -> `>= 0` (carried from 495a22b) | `etl/src/mandato_etl/compute.py:168` | yes - `test_secret_rule_table[votes0-False]` |
| F2 official-totals branch disabled (carried from 495a22b) | `etl/src/mandato_etl/compute.py:175` | yes - `test_secret_tallies_come_from_official_totals` |
| F3 participation guard dropped: `... or roll_call_docs[rc]["secret"]` (re-injected) | `etl/src/mandato_etl/compute.py:232` | yes - `test_no_record_in_a_secret_roll_call_does_not_count` FAILED at `:123` (in round 1 this mutant lived) |
| F4 `voteLabel` ignores `secret` (carried from 495a22b) | `site/src/lib/format.ts:53` | yes - `vote labels for secret ballots`, `profile secret vote` |
| F5 roll-call page groups per value when secret (carried from 495a22b) | `site/src/pages/votacoes/[id].astro:20` | yes - `secret roll call` |
| F6 `"secret"` removed from `required` | `etl/schema/roll-call.schema.json:15` | yes - `test_validate_requires_secret_in_both_files` `assert 0 == 1` at `:133` |
| F7 index `secret` type `boolean` -> `["boolean", "string"]` | `etl/schema/roll-calls.schema.json:102` | yes - `test_validate_requires_secret_in_both_files` `assert 0 == 1` at `:141` |
| F8 secret `h2` `Quem votou` -> `Como cada deputado votou` | `site/src/pages/votacoes/[id].astro:72` | yes - `secret roll call` at `build.test.ts:344` |
| F9 secret title `…: votação secreta` -> `…: como cada deputado votou` | `site/src/pages/votacoes/[id].astro:28` | yes - `secret roll call` at `build.test.ts:339` (Received `Votação nominal de 01/08/2025: como cada deputado votou`) |

## Observations

- `etl/tests/test_download.py::test_history_concurrency_is_capped_at_4` did not flake in this round's full etl runs (C7 and C18), or in round 1's three runs.
- Round-1 precision gaps 1, 2 and 3 are closed by the extended C4, C6 and C12.
- Two remaining gaps do not fail the feature:
  - Gap 4: the tallies member "secret without official columns" is still proven only because a test runs without raising; no test asserts the tallies value.
  - Gap 5: C11 asserts 2 of 8 documents. The risk is small, because `site/src/lib/data.ts:179` builds every document from `rollCallSummary`.
- `checks.md` still names the tallies source set with 2 members. The recompute counts 3, and all 3 are proven.

## Gate

At 75eb1df, `npm --prefix site ci && npm --prefix site test && MANDATO_DATA_DIR=tests/fixtures/out MANDATO_PHOTOS=off npm --prefix site run build && uv run --directory etl pytest -q` exited 0. The site tests had 56 passed and 0 failed, the build completed, and the etl suite had 101 passed and 0 failed.
