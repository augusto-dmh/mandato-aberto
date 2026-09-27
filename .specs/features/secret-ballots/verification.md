# secret-ballots verification

**Verdict**: FAIL
**Profile**: standard
**Diff range**: daceaaf..HEAD
**Round**: 1 - full
**Verifier**: independent sub-agent (author != verifier)

HEAD is `495a22b`. All 18 checks pass as written, each with a located assertion. The verdict is FAIL because of the coverage recompute and fault injection. An in-exercise deputy with **no record** in a secret roll call is a member of the participation decision table (AC 3: "has a record in a secret roll call"). No proof covers that member, and the mutant that drops the `dep in by_roll_call[rc]` guard survived the whole etl suite. In addition, door 1's "`secret` required and boolean in both files" is proven for only 2 of its 4 members.

`git diff --stat 9921e6c daceaaf` printed nothing (exit 0), so C7's reference to `9921e6c` is equivalent to `daceaaf`.

## Binding sources

None: the plan marks no binding design source for this feature (profile `standard`, so step 1 does not run).

## Checks

Proof runs at `495a22b`:
- One pytest invocation: `uv run --directory etl pytest -v tests/test_secret_ballots.py tests/test_publish.py::test_meta_fields`. It reported 11 passed, and each named test was listed as PASSED, including all five `test_secret_rule_table[votes0..4]`.
- One vitest invocation: `npm --prefix site test -- tests/data.test.ts tests/build.test.ts tests/format.test.ts tests/cards.test.ts tests/language.test.ts --reporter=verbose -t "<16-name alternation>"`. It exited 0 with 18 passed and 29 skipped, and each of the 16 named tests is listed individually with a check mark.
- C7, C8, C9 and C18 ran as the literal commands in `checks.md`.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | `secret` true only for 100-6, false for the other 7, in index and documents | pytest `test_secret_flag_marks_only_the_all_empty_roll_call` PASSED | `etl/tests/test_secret_ballots.py:32` `index[R6]["secret"] is True`; `:33` doc `is True`; `:35`/`:36` `is False` over `OPEN` (7 ids, `:14`) in both places | PASS |
| C2 | `is_secret` table over 5 inputs | pytest `test_secret_rule_table[votes0..votes4]` 5 PASSED | `etl/tests/test_secret_ballots.py:41` table `[([], False), ([""], True), (["", ""], True), (["", "Sim"], False), (["Artigo 17"], False)]`; `:44` `compute.is_secret(votes) is expected` | PASS |
| C3 | 100-6 tallies 12/5/2 in both places; 100-1 keeps 2/1/0 | pytest `test_secret_tallies_come_from_official_totals` PASSED | `etl/tests/test_secret_ballots.py:49`-`:50` `== {"yes": 12, "no": 5, "others": 2}`; `:51`-`:52` `== {"yes": 2, "no": 1, "others": 0}` | PASS |
| C4 | participation 4/5, 3/3, 0/0; 101's empty open 100-3 still not counted | pytest `test_secret_record_counts_for_participation` PASSED | `etl/tests/test_secret_ballots.py:57` `== {"count": 4, "total": 5}`; `:58` `{"count": 3, "total": 3}`; `:59` `{"count": 0, "total": 0}`; `:62` `votes101[R3] == ""`; `:63` R3 `secret is False` | PASS |
| C5 | alignments unchanged; every 100-6 vote entry has `partyMajority: null` | pytest `test_secret_record_is_not_a_valid_vote` PASSED | `etl/tests/test_secret_ballots.py:74`-`:75` equality to the literal table at `:68`-`:72`; `:78` one 100-6 entry per deputy; `:79` `partyMajority is None` | PASS |
| C6 | meta version 2, schema const 2, validate 1 for missing / non-boolean `secret`, 0 unmodified | pytest `test_publish.py::test_meta_fields` and `test_validate_requires_a_boolean_secret` PASSED | `etl/tests/test_publish.py:39` `meta["schema_version"] == 2`; `etl/schema/meta.schema.json:19` `"const": 2` (read directly); `etl/tests/test_secret_ballots.py:84` `== 0`; `:91` `== 1` after `del index[0]["secret"]` (`:89`); `:100` `== 1` after `doc["secret"] = "no"` (`:98`) | PASS |
| C7 | etl test diff touches only the 5 listed files; whole suite passes | literal C7 command exit 0; `99 passed in 4.96s` | `git diff --name-only 9921e6c -- etl/tests` shows exactly `conftest.py test_publish.py test_roll_calls.py test_schema.py test_secret_ballots.py`; changed assertions at `etl/tests/test_publish.py:39`, `etl/tests/test_roll_calls.py:44`, `etl/tests/test_roll_calls.py:63`, `etl/tests/test_schema.py:48` (see Approved-test diff audit) | PASS |
| C8 | real cache: exactly 2 secret roll calls with official tallies; validate 0 | literal C8 command exit 0 (1 min 36 s; warning `no TSE file`) | inline asserts in the C8 command; `data/out/roll-calls.json` has 1,597 roll calls, and the secret ones are `2645346-18` `{yes 404, no 61, others 1}` and `2576389-4` `{yes 388, no 22, others 11}`; code under test `etl/src/mandato_etl/compute.py:173`-`:176` | PASS |
| C9 | site fixture validates, version 2, 8 roll calls, only 100-6 secret | literal C9 command exit 0 | inline asserts `m['schema_version']==2`, `len(r)==8`, `[...secret]==['100-6']` over `site/tests/fixtures/out/meta.json:1` and `site/tests/fixtures/out/roll-calls.json:1` | PASS |
| C10 | exact version message for 3 and 1; astro build fails on 3 | vitest `rejects schema_version 3`, `rejects schema_version 1`, `fails on schema_version 3` all passed | `site/tests/data.test.ts:32`-`:33` `toThrowError(new Error("...schema_version 3; this site reads 2"))`; `site/tests/data.test.ts:41`-`:42` same for 1; `site/tests/build.test.ts:119` `status).not.toBe(0)`; `:120`-`:121` output contains the version-3 line | PASS |
| C11 | `secret` on every summary and document; schema-fields test passes on v2 | vitest `reads the secret flag`, `reads only schema fields` passed | `site/tests/data.test.ts:49` `typeof r.secret` is `"boolean"` for every summary; `:51`-`:54` 100-6 true, 100-1 false in summary and document; `:110` `sorted(rollCall)).toEqual(keys(rollCallSchema))` | PASS |
| C12 | 100-6 page: notice, `Totais oficiais da Câmara` 12/5/2, one group `Deputados que votaram (3)`, 3 linked entries in order, no `Registro sem voto` | vitest `secret roll call` passed | `site/tests/build.test.ts:336` notice; `:337` `"Totais oficiais da Câmara Sim 12 Não 5 Outros 2"`; `:338` no `Registro sem voto`; `:339` one `vote-group`; `:341` `/^Deputados que votaram \(3\)/`; `:346` entries `toEqual` 101, 102, 103 with href and `Name PARTY-UF` | PASS |
| C13 | open 100-1 has no notice and no official label, still `Sim (2)` / `Não (1)`; 100-3 keeps `Registro sem voto` | vitest `open roll calls carry no secret notice` passed | `site/tests/build.test.ts:356`-`:357` `not.toContain`; `:358`-`:359` `/^Sim \(2\)/`, `/^Não \(1\)/`; `:361` 100-3 no notice; `:363` `/^Registro sem voto \(1\)/` | PASS |
| C14 | `voteLabel` 3 rows; profile 101 rows 100-6 / 100-3 | vitest `vote labels for secret ballots`, `profile secret vote` passed | `site/tests/format.test.ts:33`-`:35` `toBe("Votação secreta")`, `toBe("Registro sem voto")`, `toBe("Art. 17 (presidente da sessão)")`; `site/tests/build.test.ts:281` `vote("100-6")).toBe("Votação secreta")`; `:282` `vote("100-3")).toBe("Registro sem voto")` | PASS |
| C15 | 101 `4 de 5`, `--share: 0.8`, base ends `inclusive Art. 17 e votações secretas.`; 102 `3 de 3` | vitest `profile indicators` passed | `site/tests/build.test.ts:199` row `"4 de 5", "0.8", "3 de 3"`; `:207` `toContain(value101)`; `:209` ``toContain(`style="--share: ${share101}"`)``; `:211` 102 `toContain(value102)`; `:214` `toMatch(/...inclusive Art\. 17 e votações secretas\. Como este número é calculado$/)` | PASS |
| C16 | 8 roll-call pages, 12 dated pages, home `8 votações nominais`, cards 4 de 5 / 3 de 3 | vitest `writes one page per deputy and per roll call`, `every page states the collection date`, `home states what the site is and the legislature totals`, `deputy card content` passed | `site/tests/build.test.ts:108` `readdirSync(votacoes)).toEqual(ROLL_CALLS)` with `:19` listing the 8 ids; `:135` `toHaveLength(12)`; `:181` `"8 votações nominais"`; `site/tests/cards.test.ts:53` `"4 de 5"`; `:70` `"3 de 3"` | PASS |
| C17 | no forbidden term in site source; built 100-6 page has no `%` | vitest `site source uses no forbidden term`, `built pages use no forbidden term and no percentage` passed | `site/tests/language.test.ts:38` `expect(hits).toEqual([])` over `SRC` (includes `votacoes/[id].astro`); `site/tests/build.test.ts:412` `not.toContain("%")`, `:413` forbidden terms, over `allPages()` (`:57`, includes `100-6` via `ROLL_CALLS`) | PASS |
| C18 | CI sequence exits 0 | literal C18 command exit 0 | `site/tests` 8 files, 56 passed; `astro build` `Complete!`; etl `99 passed in 5.40s`; `sha256sum -c` of `site/package-lock.json:1` OK (unchanged); `git status --porcelain` empty afterwards | PASS |

## Approved-test diff audit

This compares `git diff daceaaf..HEAD -- etl/tests site/tests/*.ts` against the two renegotiation paragraphs.

- `etl/tests/conftest.py`: adds the variant (`R6`, `SECRET`, `SECRET_VOTES`, `legislature(secret=...)`) and `("103", R3, "Não")`. Both are listed. `_vote_row` now looks the timestamp up in `{**ROLL_CALLS, **SECRET}`, which is a setup change needed by the variant and changes no assertion.
- `etl/tests/test_publish.py:39`: `1` becomes `2` (listed). `etl/tests/test_schema.py:48`: `2` becomes `3` (listed).
- `etl/tests/test_roll_calls.py:44`: `"secret"` added to the key set (listed). `:63`: R3 tallies `0/0/0` become `0/1/0` (listed).
- `site/tests/build.test.ts`:
  - Changes within the listed ones: `ROLL_CALLS` gains `100-6` (7 to 8); `fails on schema_version 2` is renamed to `3` with the message `3 / reads 2`; `11` pages become `12`; `7` becomes `8 votações nominais`; participation `3 de 4 / 0.75 / 2 de 2` becomes `4 de 5 / 0.8 / 3 de 3`; site C20's order gains `100-6` first.
  - Additions only: the base-line `toMatch` at `:214` and the new tests `profile secret vote`, `secret roll call` and `open roll calls carry no secret notice`.
- `site/tests/cards.test.ts:53`, `:70`: the participation values, as listed.
- `site/tests/data.test.ts`: `rejects schema_version 2` is renamed to `3` with the new message (listed). Two tests are added.
- `site/tests/format.test.ts`: one test is added.
- No approved assertion was removed or weakened, and nothing was skipped (`git diff daceaaf..HEAD -- etl/tests site/tests/*.ts | grep -E "^\+.*(\.skip|\.only|xfail|mark\.skip|\.todo)"` exits 1, no hit; all 24 removed lines are accounted for above). The diff stays within the two paragraphs.

## Coverage

Each set was recomputed from its authority: the plan's doors and ACs, and the code for branch structure. The author's table was not reused.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| secret rule inputs (5) | door 2 literal; `compute.py:166`-`:168` | `[]` / `[""]` / `["",""]` / `["","Sim"]` / `["Artigo 17"]` -> `test_secret_ballots.py:41`,`:44` | - |
| tallies source (3) | AC 2, the renegotiation prose, `compute.py:173`-`:179` | secret with official columns -> `test_secret_ballots.py:49`-`:50`; open, counted -> `:51`-`:52`; secret without columns (hand-built rows) -> `etl/tests/test_indicators.py:118` `[empty]` runs (would raise on `int(None)`; tallies value itself not asserted) | - |
| participation decision table for the new clause (4) | AC 3 ("has a record in a secret roll call"), `compute.py:230`-`:233` | non-empty vote -> `test_secret_ballots.py:57`; empty record in open roll call -> `:57`,`:62`-`:63`; record in secret roll call -> `:57`-`:58`; **in exercise, no record in a secret roll call** -> no proof: every in-exercise deputy of the variant (101, 102) has a record in 100-6, fault F3 survived | in-exercise deputy with no record in a secret roll call (`etl/src/mandato_etl/compute.py:232`) |
| deputies in the variant (3) | plan assumption | 101 -> `test_secret_ballots.py:57`; 102 -> `:58`; 103 -> `:59` | - |
| alignment indicators unaffected (2) | AC 4 | governmentAlignment -> `test_secret_ballots.py:74`; partyAlignment -> `:75` | - |
| contract version places (4) | door 1 | ETL `cli.py:18` -> `test_publish.py:39`; `meta.schema.json:19` -> `test_secret_ballots.py:84` (validate 0 at v2) + `test_schema.py:48` (3 rejected); site `data.ts:10` -> `data.test.ts:33`,`:42`; site fixture `meta.json` -> C9 | - |
| `secret` field places (4) | door 1 + data flow | `roll-calls.json` -> `test_secret_ballots.py:32`,`:35`; `roll-calls/{id}.json` -> `:33`,`:36`; site summary -> `data.test.ts:49`,`:51`; site document -> `data.test.ts:53`,`:110` | - |
| `secret` schema validation (4) | door 1 literal: "required" and `<boolean>` on `roll-calls.json` items **and** `roll-calls/{id}.json` | index missing -> `test_secret_ballots.py:91`; document non-boolean -> `:100`; document missing (`etl/schema/roll-call.schema.json:15`) -> no proof; index non-boolean (`etl/schema/roll-calls.schema.json:101`) -> no proof (`rg '"secret"' etl/tests site/tests` shows no other validation case) | document without `secret`; index item with non-boolean `secret` |
| roll-call page states (3) | `votacoes/[id].astro:20`,`:43`,`:51` | secret -> `build.test.ts:336`-`:346`; open with votes -> `:356`-`:359`; open with an empty record -> `:361`,`:363` | - |
| profile vote labels (4) | `format.ts:52`-`:54` | `Artigo 17` -> `format.test.ts:35`; `""` secret -> `:33`, `build.test.ts:281`; `""` open -> `:34`, `build.test.ts:282`; other value -> `format.test.ts:29` | - |
| `GET /votacoes/{id}/` statuses (2) | plan Surface | 200 -> `build.test.ts:110`; unknown id has no page -> `build.test.ts:108` exact directory list | - |
| real secret ballots (2) | plan Problem, `data/raw/` | `2645346-18`, `2576389-4` -> C8 inline asserts (exact set) | - |
| startup config: contract version (2 assemblies) | read directly | ETL `etl/src/mandato_etl/cli.py:18` `SCHEMA_VERSION = 2`; site `site/src/lib/data.ts:10` `SCHEMA_VERSION = 2` | - |

Sets swept that have no row in checks.md: the Surface's output fields (notice, totals, one group) are covered by C12, and the Observable "tone" row is covered by C17. The secret-page copy that no AC or check fixes (title `…: votação secreta`, meta description, `h2` `Quem votou`, note `Em ordem alfabética…` at `site/src/pages/votacoes/[id].astro:28`-`:31`, `:72`-`:77`) has no authority naming it. It is recorded as a precision gap below, not as an unproven member.

## Test policy rows

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `compute.is_secret` (`compute.py:166`) | own C2 `test_secret_ballots.py:44` (5 rows) · boundary C1 | yes |
| Decides, reached across a boundary | `compute` tallies source (`compute.py:173`) | boundary C3 on both published rows | yes |
| Decides, reached across a boundary | `compute` participation clause (`compute.py:230`-`:233`) | one asserted case per row of the decision table | no - 3 of 4 rows asserted; the in-exercise "no record in a secret roll call" row has none (F3 survived) |
| Decides, reached across a boundary | `site/src/lib/format.ts` `voteLabel` | own `format.test.ts:33`-`:35` · boundary `build.test.ts:281`-`:282` | yes |
| Decides, reached across a boundary | `site/src/pages/votacoes/[id].astro` | boundary C12 and C13 on the built page | yes |
| Decides, not reached across a boundary | none in this diff | n/a | n/a |
| Instrumentation, pass-throughs | `readers.py` allowlist; `data.ts:172` `secret: r.secret`; `deputados/[id].astro:161` | consumer proofs C3, C11, C14 | yes |

## Faults injected

Scratch worktree: `git worktree add <scratchpad>/wt HEAD`, with `site/node_modules` symlinked to the real one. The real tree's `git status --porcelain` was empty before and after the worktree was removed.

| Mutation | Location | Killed |
| --- | --- | --- |
| F1 `len(votes) > 0` -> `>= 0` | `etl/src/mandato_etl/compute.py:168` | yes - `test_secret_rule_table[votes0-False]` failed |
| F2 official-totals branch disabled (`if False and secret ...`) | `etl/src/mandato_etl/compute.py:175` | yes - `test_secret_tallies_come_from_official_totals` failed |
| F3 participation guard dropped: `... or roll_call_docs[rc]["secret"]` (no `dep in by_roll_call[rc]`) | `etl/src/mandato_etl/compute.py:232` | no - survived: whole etl suite `99 passed`; the site fixture is precomputed, so no site test can see it. On the real `data/out/`, about 47 (`2645346-18`) and 92 (`2576389-4`) in-exercise deputies have no record and would be counted |
| F4 `voteLabel` ignores `secret` (always `Registro sem voto`) | `site/src/lib/format.ts:53` | yes - `vote labels for secret ballots` and `profile secret vote` failed |
| F5 roll-call page groups per value even when secret (`const groups = false ? ...`) | `site/src/pages/votacoes/[id].astro:20` | yes - `secret roll call` failed |

## Observations

- `etl/tests/test_download.py::test_history_concurrency_is_capped_at_4` did not flake: it passed in all three full etl runs (C7, the F3 mutant run, C18).
- C8 took 1 min 36 s, not about 40 s, and printed `warning: no TSE file; candidacy2026 is null for every deputy`. The warning is expected, because C8 does not pass `--tse-csv`.

## Precision gaps in the checks

1. C4 and the Coverage row "participation record kinds (3)" leave out the member that AC 3's wording creates: an in-exercise deputy with **no** record in a secret roll call. The variant cannot tell the cases apart, because both in-exercise deputies voted in 100-6. C8 asserts nothing about participation on real data. The indicator most likely to be contested therefore has its new negative case proven nowhere.
2. C6 and the "`secret` field validation (2)" row treat door 1 as one schema. The door names two files, which gives 4 members, and 2 are exercised.
3. The secret-ballot copy beyond AC 6 is fixed by no AC or check: page title, meta description, `Quem votou` and the ordering note. Only the forbidden-term scan reaches it.
4. The tallies member "secret without official columns" is proven only by the fact that `test_valid_vote_set_table[empty]` does not raise. No test asserts the counted value.
5. C11 says "every … document" but asserts 2 of 8 documents. This is structurally mitigated: `site/src/lib/data.ts:179` builds every document from `rollCallSummary`.

## Gate

`npm --prefix site ci && npm --prefix site test && MANDATO_DATA_DIR=tests/fixtures/out MANDATO_PHOTOS=off npm --prefix site run build && uv run --directory etl pytest -q`: exit 0. It reported site 56 passed and 0 failed, the build completed, and etl 99 passed and 0 failed.
