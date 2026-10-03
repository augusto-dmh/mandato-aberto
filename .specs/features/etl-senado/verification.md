# etl-senado verification

**Verdict**: PASS
**Profile**: standard
**Diff range**: 6a0d768..fc9d599a8fc3585f71a2f3600d16795f70c7688c
**Round**: 2 - scoped
**Verifier**: independent sub-agent (author != verifier)

Round 1 (`774d0dc`) failed on one gap. AC 5 requires a deprecation warning, and research/07 §2 [V] says a deprecated Senate service answers **`301`** with `Deprecation`/`Sunset`. The build followed that redirect without printing anything. Round 2 covers three fix commits:

- `7a05743`: a redirect handler now warns on each hop, and `camara._get` gained an `opener=` parameter.
- `7b09aeb`: contract-v3 check C4 text.
- `fc9d599`: `validate` refuses a dangling v3 `memberId` (C45), and the Senate build fails closed on an unlisted voter (C46). The fixture gains 9103-9105, and test datasets gain `senado_data.with_voters`.

The round-1 gap is now closed. All 46 checks are proven at `fc9d599`. Five faults were injected on the new and touched assertion surfaces, and all five were killed. The two coverage rows the fixes touched were recomputed and have no unproven member. The shared `camara._get` change leaves the Câmara path unchanged (see "Shared change judged").

Scope: `git diff 774d0dc fc9d599` touches `sources/senado.py`, `sources/camara.py`, `schema.py`, `contract_v3.py`, `tests/conftest.py`, `tests/senado_data.py`, `tests/test_senado_sources.py`, `tests/test_senado_contract.py`, `tests/test_v3_contract.py`, `tests/test_v3_senado.py`, `tests/fixtures/v3/senado/members.json`, and the two `checks.md` files. Sections and rows outside that scope say `carried from 774d0dc`. The ones re-done here say `verified at fc9d599`.

## Binding sources

Carried from 774d0dc. None of the fixes touched a binding source. `research/07-fontes-senado.md` was re-read at §2 line 25 and §9 line 153 to recompute the deprecation row.

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `plan.md` (approved, resolved OQ 1-4 binding) | yes - read in full (round 1); AC 5 re-read at `plan.md:83` | none | - |
| `research/07-fontes-senado.md` | yes - read in full (round 1); §2 `:25` and §9 `:153` re-read | none | - |
| `.specs/STATE.md` AD-016..AD-018 | yes - lines 22-24 (round 1) | none | - |
| `AGENTS.md` | yes | none | - |

## Checks

Verified at fc9d599. All proofs were re-run in full at HEAD `fc9d599`, in two invocations:

- **Named-proof batch.** Every `Proof:` line of `checks.md` was run. Each `-k` form was expanded by `--collect-only` into its node ids, giving 87 node ids plus the eight C41 files, passed quoted to one `uv run --directory etl pytest -v ...` call. Result: exit 0, **217 passed**, 0 failed, 0 error. That is round 1's 213 plus the redirect proof, C46, and C45's `[vote]` and `[author]` cases. C45's `test_senate_fixture_validates` was already inside the C41 file `test_v3_senado.py`.
- **Whole suite.** `uv run --directory etl pytest -v` gave exit 0 and **334 passed**.

C42's command, `test -z "$(git diff --name-only 6a0d768..HEAD -- site .github/workflows/publish.yml etl/tests/fixtures/v2-golden.json)"`, exited 0. C44's command, `grep -q 'A feature \`etl-senado\` roda em \`standard\`' AGENTS.md`, exited 0. Every named test appears as a PASSED line.

Rows C1-C4 and C7-C44 keep round 1's claim and evidence, which is carried from 774d0dc. Their proofs were re-run green at fc9d599. Citations in `test_senado_sources.py` below line 132 moved by +13 and were refreshed (C6, C43). The other touched test files only appended code or changed lines that no row cites.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | first build requests the 9 lists + 4 authorship URLs, JSON + UA, manifest | `test_first_build_downloads_each_source_once` PASSED | `etl/tests/test_senado_sources.py:39` `sorted(sd.requests(sen)) == sorted(LISTS_2027 + AUTHORS_2027)`; `:41`, `:43`, `:47-50` (the spy at `:31-33` now forwards `**kwargs`, so the `opener=` path really runs) | PASS |
| C2 | cache hit = 0 requests, identical bytes; `--refresh` re-requests | `test_cache_hit_issues_no_request` PASSED | `test_senado_sources.py:63`, `:65`, `:67` | PASS |
| C3 | 503x4 -> exit 2, URL, sleeps [1,2,4], no .part; 503 then 200 -> 0 | `test_download_failure_exits_2_and_keeps_output`, `test_retry_then_success` PASSED | `test_senado_sources.py:79`, `:81`, `:82`, `:83`, `:85`, `:91-92` | PASS |
| C4 | at most 4 in flight | `test_at_most_four_concurrent_requests` PASSED | `test_senado_sources.py:122` `sen.max_in_flight <= 4` | PASS |
| C5 | Deprecation/Sunset -> stderr warning with URL and Sunset date, exit 0 | `test_deprecation_header_warns_and_continues`, `test_deprecation_sent_as_a_redirect_warns_and_continues` PASSED | 200 shape: `test_senado_sources.py:129` `== 0`; `:131-132` one line holding `warning` and `Sun, 01 Feb 2026`. 301 shape: `:139-140` the original path answers `301` + both headers -> `Location` of a moved path that carries no header; `:141` `== 0`; `:142` both paths requested; `:143-145` exactly one line holding the **original** URL, `warning` and `Sun, 01 Feb 2026`. Code: `etl/src/mandato_etl/sources/senado.py:37-39` (`redirect_request` checks the hop), `:47` (final response), `:33` (once per URL), `:57` (opener) | PASS |
| C6 | bad envelopes exit 1 naming the file, no output | `-k test_bad_envelope_exits_1` (2) PASSED | `test_senado_sources.py:152-154`; `:161-162` | PASS |
| C7-C40 | as round 1 | each named proof PASSED in the 217 | carried from 774d0dc, citations unchanged (`test_senado_cli.py`, `test_senado_members.py`, `test_senado_roll_calls.py`, `test_senado_indicators.py` untouched; `test_senado_contract.py` only appended after `:139`) | PASS |
| C41 | whole contract-v3 suite green | the eight files, all PASSED | every `tests/test_v3_*.py` and `tests/test_v2_frozen.py` node PASSED in the 217. Includes `test_v3_contract.py:277` `[None] * 10` (was `* 4`: the fixture's 5 members x 2 mandates, `test_v3_senado.py:22-23`) | PASS |
| C42 | no `site/`, `publish.yml` or v2 golden change | the `test -z ...` command, exit 0 | empty diff list at `6a0d768..fc9d599` | PASS |
| C43 | Senate allowlist projection keys | `test_allowlist_projection` PASSED | `test_senado_sources.py:179` record keys equal `DOOR_1_ROLL_CALL` plus `votos`; `:181-182`; `:184` no `senado*` kind in `readers.ALLOWLIST` | PASS |
| C44 | AGENTS.md declares standard | the `grep -q` command, exit 0 | `AGENTS.md:26` | PASS |
| C45 | `validate` exits 1 on a vote or author `memberId` absent from `members.json`, naming file, id and memberId; the Senate fixture exits 0 | `test_validate_refuses_a_member_id_missing_from_members[vote]`, `[author]`, `test_senate_fixture_validates` PASSED | `etl/tests/test_v3_senado.py:54-56` vote `9999` on `6923`, author `9998` on `160000`; `:66` `cli.main(["validate", ...]) == 1`; `:68` file, both ids and `members.json` in stderr; `:20` fixture `== 0`; `:22` members `[9101, 9102, 9103, 9104, 9105]`. Code: `etl/src/mandato_etl/schema.py:138`, `:141-155` | PASS |
| C46 | Senate build with a voter in no legislature list exits 1 naming roll call and member id, writes no `data/v3/senado` | `test_voter_absent_from_every_legislature_list_fails_the_build` PASSED | `etl/tests/test_senado_contract.py:144-147` voter `9301` on `6901`, lists `{57: []}`, `list_voters=False`; `:148` `== 1`; `:150` `"6901"`, `"9301"`, `"members"` in stderr; `:151` `not sd.out(sen).exists()`. Code: `etl/src/mandato_etl/contract_v3.py:650-655`. The build's own publish step (`publish.py:26-31`) checks schemas only, so this guard is the sole build-time barrier. F5 confirms it | PASS |

### Shared change judged: `camara._get(opener=)`

Verified at fc9d599. **This change is safe for the Câmara path.**

- `etl/src/mandato_etl/sources/camara.py:46` adds `opener=None`.
- `:52` selects `opener.open if opener else urllib.request.urlopen` once per call.
- `:55` uses it with the same `timeout`. The request, headers, retry statuses, delays and `except` arms are unchanged.
- All three Câmara call sites pass no opener (`camara.py:107`, `:152`, `:202`), so they still call `urllib.request.urlopen`. Because that name is looked up at call time, a monkeypatched `urlopen` would still take effect.
- The only caller that passes an opener is `senado.py:57`. It builds `build_opener(warner)`, which keeps urllib's default handlers and replaces only the redirect handler with the subclass.
- The etl-camara and contract-v3 suites are green inside the 334.

### Deviation judged: contract-v3 test edit and C4 text

Round 1's P3 is closed by `7b09aeb`. `.specs/features/contract-v3/checks.md:34` now names `--house presidencia`, the case `etl/tests/test_v3_cli.py:50-51` runs. The rest of the judgement is carried from 774d0dc and was not weakened.

### `with_voters` judged: it does not mask real cases

Verified at fc9d599. `senado_data.dataset` now defaults to `list_voters=True` (`etl/tests/senado_data.py:141`, `:148`). That adds a listed senator, in exercise from the legislature's start, for each voter that a dataset's trimmed lists lack (`:117-138`). Three pieces of evidence show it hides nothing:

1. **No expected value moved.** `fc9d599` edits no assertion in `test_senado_members.py`, `test_senado_indicators.py` or `test_senado_roll_calls.py`. The numbers on paper are unchanged and still pass.
2. **The suites that assert member sets do not depend on it.** A probe in the scratch worktree flipped the default to `False` and ran `-k senado`. The result was 3 failed, 9 errors and 85 passed. Every failure or error sits in `test_senado_roll_calls.py` or `test_senado_contract.py`, and each is a build stopped by C46 on a recorded or roll-call dataset whose lists were trimmed. `test_senado_members.py`, which asserts C8's exact member set including the "vote record only" case 9202, and `test_senado_indicators.py` passed with and without the helper. So the helper adds no member to them.
3. **The fail-closed path itself is proven with the helper off.** C46 runs with `list_voters=False` (`test_senado_contract.py:146`), and F5 shows that proof is discriminating.

## Coverage

The two rows touched by the fixes were recomputed and are verified at fc9d599. Every other row is carried from 774d0dc, and its proofs were re-run green.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| **deprecation signal shapes (2)** - verified at fc9d599 | research/07 §2 `:25` [V] (`301` + `Deprecation`, `Sunset`, `Link`); plan AC 5 `plan.md:83` | `200` + headers -> C5 `test_senado_sources.py:125-132` (F2 killed); `301` + headers -> `Location` -> C5 `test_senado_sources.py:135-145` (F1 killed) | - |
| **v3 `memberId` references checked by `validate` (3)** - verified at fc9d599 | app-contract-v3 AC 10 as restated by C45; `schema.py:141-155` branches | vote `memberId` absent -> C45 `[vote]` (F3 killed); author `memberId` absent -> C45 `[author]` (F4 killed); all present -> exit 0, C45 `test_senate_fixture_validates` and C34 on every dataset build | - |
| **Senate build voter-membership guard (2)** - verified at fc9d599 | `contract_v3.py:650-655` | voter in no list -> exit 1, no output, C46 (F5 killed); every voter listed -> guard silent, every other dataset build exits 0 (C1, C34, C37) | - |
| Senate vote codes (13) | research/07 §4 table | carried from 774d0dc: each at `test_senado_roll_calls.py:145`, build `:171-173` | - |
| AD-018 sensitive codes (3) | STATE.md AD-018, plan OQ 2 | carried from 774d0dc: `LS`, `LP`, `LAP` at `test_senado_roll_calls.py:173`/`:179` | - |
| orientation values (5 + null + unknown) | research/07 §5, plan door 3 | carried from 774d0dc: `:156`, `:244`, `:161`, `:199` | - |
| `governmentOrientation` cases (4) | AC 20 | carried from 774d0dc: `:229`, `:232-233`, `:234`, `:235` | - |
| ruleset v1 rules (7) + order + no-match | plan door 2 | carried from 774d0dc: `:111`/`:117`, `:132`, `:125-126` | - |
| twin-rule cases (4 kinds) | research/07 §4 | carried from 774d0dc: `:60-62`, `:73`, `:85` | - |
| exercise filter cases (7) | research/07 §3, AC 8/9 | carried from 774d0dc: `test_senado_members.py:31-33`, `:39-43`, `:52` | - |
| symbolic null (2) | plan OQ 1, AC 30 | carried from 774d0dc: `test_senado_indicators.py:82-83`, `test_senado_contract.py:78` | - |
| participation codes (4) | AC 26 | carried from 774d0dc: `test_senado_indicators.py:23` | - |
| authorship filter cases (8) | AC 27-29, research/07 §6 | carried from 774d0dc: `test_senado_indicators.py:60-68`, `:75-77` | - |
| exit codes (0/1/2) and flags (6) | plan Observable | carried from 774d0dc; exit `1` gains C46's cause (`test_senado_contract.py:148`) | - |

Swept for new enumerations the fixes created. The redirect handler adds the hop and dedupe surfaces, both covered above. The validator adds the vote, author and none surfaces, all covered. The build guard adds fires and silent, both covered. Authorship cannot dangle at build: authors are appended only for `(prop, code)` pairs collected inside the members loop (`contract_v3.py:630-631`). That is the builder's Handoff claim, and it was re-read against the code.

## Test policy rows

Rows that classify touched files were re-judged and are verified at fc9d599. The Instrumentation row is carried from 774d0dc.

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary (CLI, HTTP) | `classify.py` (Senate maps), `sources/senado.py`, `cli.py` (`--house senado` guard), `schema.py` `_dangling_member` (new, reached by `mandato-etl validate`) | own layer + boundary; exit code + stderr at CLI; one case per decision-table row | yes. The deprecation decision now has both of its rows proven over a real socket with exit code and stderr: the `200` row at `test_senado_sources.py:125-132` and the `301` row at `:135-145`. `_dangling_member` has three rows (vote dangling, author dangling, none), each asserted through `cli.main(["validate", ...])` with exit code and stderr (C45). The rest is carried from 774d0dc (C1-C7, C10, C15, C20-C22) |
| Decides, not reached across a boundary | `contract_v3.py` Senate assembly, now including the membership guard `:650-655` | one asserted case per table row | yes. The guard fires in C46 and stays silent in every other dataset build. The other rows are carried from 774d0dc (C8-C11, C23, C24, C27-C31) |
| Instrumentation, pass-throughs | `readers.py`, and `camara._get`'s new `opener` selection (`camara.py:52`) | covered by consumers | yes. Its consumers C1-C5 (Senate, with opener) and the etl-camara suite (without) are both green |

## Faults injected

Verified at fc9d599. Each fault ran in the scratch worktree `git worktree add --detach /tmp/verify-etl-senado-r2 HEAD`, after its own `uv sync --directory .../etl`. Each was applied by an exact single-occurrence string replacement, run against the narrowest covering proof, then reverted with `git checkout -- etl`. Scratch porcelain was empty after each fault. The scratch was removed with `git worktree remove --force` and pruned. The real tree's `git status --porcelain` was empty before and empty after (diffed). `git stash` was never used.

| Mutation | Location | Killed |
| --- | --- | --- |
| F1 redirect hop not checked: `self.check(req.full_url, headers)` removed from `redirect_request` | `etl/src/mandato_etl/sources/senado.py:38` | yes - `test_deprecation_sent_as_a_redirect_warns_and_continues`: `assert len(lines) == 1` -> `0 == 1` |
| F2 final response not checked: `warner.check(response.url, response.headers)` removed | `etl/src/mandato_etl/sources/senado.py:47` | yes - `test_deprecation_header_warns_and_continues`: `0 == 1` |
| F3 validator skips vote `memberId`s (`if False:`) | `etl/src/mandato_etl/schema.py:147` | yes - `test_validate_refuses_a_member_id_missing_from_members[vote]`: `assert 0 == 1` |
| F4 validator skips author `memberId`s (`if False:`) | `etl/src/mandato_etl/schema.py:152` | yes - `test_validate_refuses_a_member_id_missing_from_members[author]`: `assert 0 == 1` |
| F5 build does not fail closed on an unlisted voter (`if False:`) | `etl/src/mandato_etl/contract_v3.py:653` | yes - `test_voter_absent_from_every_legislature_list_fails_the_build`: `assert 0 == 1` (build exits 0) |

Round 1's F1-F5 (twin rule, sensitive map, orientation join, exercise filter, symbolic) were not re-injected, because no fix touched those surfaces. They are carried from 774d0dc, and their proofs were re-run green.

## Gate

Verified at fc9d599:

- `uv run --directory etl pytest -v`: 334 passed, 0 failed.
- The batched named-proof run: 217 passed, 0 failed.
- C42 and C44 commands: exit 0.

## Ranked gaps

None. The round-1 gap (deprecation sent as a `301`, C5) is closed by `sources/senado.py:37-39` and proven by `test_senado_sources.py:135-145`, and F1 confirms that proof can fail.

## Precision notes (non-blocking)

- P1 - carried from 774d0dc. C37's accent-stripped ordering is not discriminating in the named proof (`test_senado_contract.py:89-90`, `:98-103`). The shared `sort_name` is proven for the Câmara through C41.
- P2 - carried from 774d0dc. Round 1's F2 and F4 were killed by side effects, not by the check's own assertion line.
- P3 - closed by `7b09aeb` (`.specs/features/contract-v3/checks.md:34`).
- P4 - a `301` carrying the headers but **no `Location`** gets no deprecation warning. urllib refuses it before `redirect_request`, so the build exits 2 naming the URL (C3's path). A probe of `senado._download` at HEAD returned `DownloadError: .../old-noloc: HTTP 301` with `warnings: []`. The same probe with `Location` warned once. Research/07 §2 records the headers it observed but not whether `Location` was present. §9 `:153` asks to "falhar alto em `301`", and exit 2 does fail loudly. So this is not an AC 5 miss on any observed shape. It is worth a look if the live API ever answers that way.
- P5 - `with_voters` is annotated `-> dict` but returns a `(lists, added)` tuple (`etl/tests/senado_data.py:117`). Its docstring says so. Cosmetic.
- P6 - outside this feature's verdict. The new `validate` check also runs on Câmara v3 directories. The Câmara assembler admits a voter as a member only when the voter is listed, or when the record's `deputado_idLegislatura` is a built legislature (`contract_v3.py:144-155`). It has no fail-closed guard like C46. A vote record whose `deputado_idLegislatura` lies outside the built legislatures, from a deputy listed in none of them, would now build but fail `validate`. CI validates only the v2 site fixture (`.github/workflows/ci.yml:36`), so nothing breaks today. This belongs to contract-v3 / etl-camara.
