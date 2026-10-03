# etl-presidencia verification

**Verdict**: FAIL
**Profile**: standard
**Diff range**: 91323e8..bb01ceb
**Round**: 1 - full
**Verifier**: independent sub-agent (author != verifier)

The checks are sound, and in the builder's working tree every proof is green. All five injected faults were killed.
The commit at `HEAD` still does not hold the feature. `etl/inputs/joint-vote-aliases.json`, the door 6 alias file
that `presidency.load_aliases()` reads on every presidency build (`etl/src/mandato_etl/presidency.py:19`,
`etl/src/mandato_etl/cli.py` `build_presidency`), is matched by `.gitignore:4` (`etl/inputs/*`) and was never
committed: `git ls-files etl/inputs` lists nothing. A clean `git worktree add <scratch> HEAD` with its own `uv sync` gives
`40 failed, 390 passed, 23 errors`, and every failure is `FileNotFoundError: .../etl/inputs/joint-vote-aliases.json`.
CI on this branch would fail the same way. 46 of the 60 checks have a proof that is red at `HEAD`. Only the local,
ignored copy of the file makes them green.

Commands run at `HEAD` (bb01ceb), real tree, one batch each:

- `uv run --directory etl pytest -v -p no:cacheprovider` → 453 passed, 0 failed (68 s)
- every named proof in one invocation: `uv run --directory etl pytest -v tests/test_presidencia_{acts,contract,counts,joint,sources,stages}.py tests/test_v4_houses.py -k "<57 names or-ed>"` → 117 passed, 2 deselected (`test_term_listed_from_its_start_date` and `test_unmatched_is_zero_in_recorded_build`, which no check names; both ran green in the full suite). Every test named in checks.md appears individually as PASSED in the output (64 proof lines, each with ≥1 hit)
- C57: `uv run --directory etl pytest tests/test_v2_frozen.py … tests/test_senado_cli.py` (the 14 files) → 220 passed
- C6 `test -z "$(git diff --name-only 91323e8..HEAD -- site design .github/workflows/publish.yml etl/schema/v3 ':(glob)etl/schema/*.json')"` → exit 0
- C56 `grep -q 'mandato-etl validate tests/fixtures/v4/presidencia' .github/workflows/ci.yml` → exit 0 (`ci.yml:38`)
- C59 `grep -q 'A feature \`etl-presidencia\` roda em \`standard\`' AGENTS.md` → exit 0
- `uv run mandato-etl validate tests/fixtures/v4/presidencia` (from `etl/`) → exit 0, also exit 0 in the clean `HEAD` checkout
- clean checkout of `HEAD` (scratch worktree, own `uv sync`): `uv run --directory etl pytest -q -rfE` → 40 failed, 390 passed, 23 errors, all from the missing alias file
- No live API: the full suite was re-run in the scratch tree under a `sitecustomize` guard that refuses every non-loopback `connect` and DNS lookup (confirmed active by a probe to `legis.senado.leg.br` that was blocked). Result: 453 passed, 0 blocked attempts logged

"FAIL" in a Result cell below means the named proof is red on the committed `HEAD`. The cited assertion targets the
value the check defines, and it passes in the working tree with the ignored file present.

## Checks

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | v4 Câmara = v3 except `schema_version` | `test_camara_v4_equals_v3_but_version` PASSED (HEAD clean too) | `etl/tests/test_v4_houses.py:23` - `v4[relative] == v3[relative].replace(b'"schema_version":3', b'"schema_version":4')`; `:25` byte-equal otherwise | PASS |
| C2 | same for Senate | `test_senado_v4_equals_v3_but_version` PASSED (HEAD clean too) | `etl/tests/test_v4_houses.py:42` - `_only_version_differs(snapshot(sd.out(sen)), snapshot(out))` | PASS |
| C3 | default `data/v4/<house>`; out and v3 bytes and mtimes kept | `test_v4_default_out_leaves_out_and_v3_untouched` - red at HEAD (presidency build at `:64` raises FileNotFoundError) | `etl/tests/test_v4_houses.py:60`, `:65` meta files exist; `:66-67` `_stats(...) == before_out` (bytes + `st_mtime_ns`, `:46`) | FAIL |
| C4 | bad contract/house: exit 1, usage, 0 requests, no output | `-k test_bad_contract_or_house_exits_1` 5 PASSED (HEAD clean too) | `etl/tests/test_v4_houses.py:76` `== 1`; `:77` `"usage:" in err`; `:78` `con.requests == []`; `:79` `not out.exists()` | PASS |
| C5 | validate picks v4 set by scope; v2/v3 kept | `test_validate_picks_the_v4_set_by_scope` - red at HEAD (`:94` presidency build) | `etl/tests/test_v4_houses.py:86,91,95` `== 0`; `:99` scope on house `== 1`; `:103` `"unsupported schema_version 5" in err`; `:107,:110` v2/v3 `== 0`; `:113` relabelled v3 `== 1` | FAIL |
| C6 | no diff in site, design, v3 schemas, v2 schemas, publish.yml | git-diff test, exit 0 | command output empty | PASS |
| C7 | v4 schema set exact; house schemas = v3 but const 4 | `test_v4_schema_set` PASSED (HEAD clean too) | `etl/tests/test_v4_houses.py:123` file set; `:129` `const == 4`; `:131` `four == three` | PASS |
| C8 | first build: 23 Congress paths once, headers, manifest | `test_first_build_requests_each_source_once` - red at HEAD | `etl/tests/test_presidencia_sources.py:47` `sorted(requested) == sorted(OTHERS + DEVICES)`; `:48` `len == 23`; `:52-53` Accept/User-Agent; `:60-62` sha256, bytes, entry keys | FAIL |
| C9 | cache reuses only decided devices; `--refresh` refetches | `test_cache_reuses_only_decided_devices` - red at HEAD | `etl/tests/test_presidencia_sources.py:73` `== sorted(OTHERS)`; `:74` snapshot equal; `:77` `== sorted(OTHERS + DEVICES)` | FAIL |
| C10 | ≥0.45 s apart, one in flight | `test_at_most_two_requests_per_second` PASSED (HEAD clean too) | `etl/tests/test_presidencia_sources.py:94` `b - a >= 0.45`; `:95` `con.max_in_flight == 1` | PASS |
| C11 | 503×4 → exit 2, URL, sleeps [1,2,4], no `.part`, output kept; bad bodies retried | `test_download_failure_exits_2_and_keeps_output` red at HEAD; `-k test_bad_body_is_retried` 3 of 4 red at HEAD (`html-four-times` green) | `etl/tests/test_presidencia_sources.py:105` `== 2`; `:106` URL in err; `:107` `con.sleeps == [1, 2, 4]`; `:108` no `*.part`; `:109` snapshot; `:151` exit code; `:154` `con.sleeps == sleeps` | FAIL |
| C12 | no planalto request | `test_no_planalto_request` - red at HEAD | `etl/tests/test_presidencia_sources.py:166` every URL starts with fake base; `:167` `not any("planalto.gov.br" in url ...)` | FAIL |
| C13 | house dirs missing/invalid/v3 → exit 1 before any request | `-k test_house_directories_required` 3 PASSED (HEAD clean too: the gate runs before `load_aliases`) | `etl/tests/test_presidencia_sources.py:190` `== 1`; `:192` named words in err; `:193` `congress_requests == []`; `:194` `not out.exists()` | PASS |
| C14 | exactly 18 act ids, distractors excluded | `test_one_act_per_mp_veto_and_executive_bill` - red at HEAD | `etl/tests/test_presidencia_acts.py:34` `sorted(ids) == sorted(R_IDS)`; `:35` `len == 18`; `:36` no msc/pln/pl-1/9203/9204 | FAIL |
| C15 | kind/type, ints, vetoScope, non-veto devices [] | `test_kind_type_and_veto_scope` - red at HEAD | `etl/tests/test_presidencia_acts.py:45` `(kind, type) == expected[prefix]`; `:46` ints; `:48` total for vet-3-2026; `:50-51` null scope, `devices == []` | FAIL |
| C16 | pl-1-2023 excluded, counter 1 | `test_before_first_term_is_excluded` - red at HEAD | `etl/tests/test_presidencia_acts.py:56`; `:57` `excludedBeforeFirstTerm == 1` | FAIL |
| C17 | term edges; T termIds; meta.terms exact by build date | `test_term_boundaries`, `test_terms_listed_by_build_date` - both red at HEAD (edges at `:61-64` execute before the T build errors) | `etl/tests/test_presidencia_acts.py:61-64` `term_of(...)`; `:69-70` termIds; `:82` `== [TERM_2023]`; `:87` `== [TERM_2023, TERM_2027]` (literals `:73-75`) | FAIL |
| C18 | 9 MP rules, 4 rejects | `-k test_mp_status_rules` 13 PASSED (HEAD clean too) | `etl/tests/test_presidencia_acts.py:103` `mp_status(record, ...) == (status, rule)` over `:90-99`; `:113-114` `pytest.raises(PresidencyError)` over `:106-110` | PASS |
| C19 | 4 device rules, 3 rejects | `-k test_device_status_rules` 7 PASSED (HEAD clean too) | `etl/tests/test_presidencia_acts.py:122`; `:127-128` raises | PASS |
| C20 | recorded MP and device status/rule/officialStatus | `test_recorded_statuses` - red at HEAD | `etl/tests/test_presidencia_acts.py:142` `(status, statusRule, officialStatus) == ...`; `:152` same per device | FAIL |
| C21 | unknown MP/device value → exit 1 naming value + act, output kept | `-k test_unknown_status_stops_the_build` 2 - red at HEAD | `etl/tests/test_presidencia_acts.py:180` `== 1`; `:182` words in err; `:183` snapshot | FAIL |
| C22 | veto pending/decided, unit cases | `test_veto_status` - red at HEAD (uses the R build fixture) | `etl/tests/test_presidencia_acts.py:188`, `:190`; `:191-193` `veto_status(...)` | FAIL |
| C23 | bill precedence table (10 rows) | `-k test_bill_status_precedence` 10 PASSED (HEAD clean too) | `etl/tests/test_presidencia_acts.py:212` `bill_status(norma, camara, total) == (status, rule)` over `:199-210` | PASS |
| C24 | recorded bill statuses; H vetoedTotally | `test_recorded_bill_statuses`, `test_vetoed_totally` - red at HEAD | `etl/tests/test_presidencia_acts.py:218-225`; `:230` `("vetoedTotally", "bill.03")` | FAIL |
| C25 | law verbatim; approvedWithoutLaw 0/1 | `test_law_and_approved_without_law` - red at HEAD | `etl/tests/test_presidencia_acts.py:239` law literals `:235-237`; `:241` null; `:242` `== 0`; `:247-248` H `== 1` | FAIL |
| C26 | vetoedMatter and relatedActId | `test_related_act` - red at HEAD | `etl/tests/test_presidencia_acts.py:261`; `:264`; `:268` `== "pl-9105-2025"` | FAIL |
| C27 | 21 rules exact, keys, verbatim value, words, version 1 | `test_status_rules_file` - red at HEAD | `etl/tests/test_presidencia_acts.py:292` `== RULE_TABLE`; `:293` 21; `:295` keys; `:297` value in description; `:300` banned words; `:302-305`; `:306` `{"version": 1}` | FAIL |
| C28 | issuedAt/statusAt/summary, device fields | `test_act_fields` - red at HEAD | `etl/tests/test_presidencia_acts.py:312`, `:314`, `:315`, `:317`, `:319-324` | FAIL |
| C29 | MP stages, missingCamaraStage 1 | `test_mp_stages` - red at HEAD | `etl/tests/test_presidencia_stages.py:22-25` | FAIL |
| C30 | bill stages, SF-only Senate stage | `test_bill_stages` - red at HEAD | `etl/tests/test_presidencia_stages.py:29-32` | FAIL |
| C31 | vetoes have no stage; joint ids = device ids | `test_veto_has_no_stage` - red at HEAD | `etl/tests/test_presidencia_stages.py:37` `all(a["stages"] == [])`; `:40-41` | FAIL |
| C32 | stage join returns the named roll calls | `test_stages_join_house_roll_calls` - red at HEAD | `etl/tests/test_presidencia_stages.py:51-53` | FAIL |
| C33 | 6 joint roll calls, fields | `test_joint_roll_calls` - red at HEAD | `etl/tests/test_presidencia_joint.py:30`; `:32-36`; `:38-40` | FAIL |
| C34 | position map + rejects | `-k test_position_map` 9 PASSED (HEAD clean too) | `etl/tests/test_presidencia_joint.py:48`; `:53-54` raises | PASS |
| C35 | votes file shape, order, aj Albuquerque | `test_votes_file` - red at HEAD | `etl/tests/test_presidencia_joint.py:60`, `:62`, `:65`, `:66`, `:68-69` | FAIL |
| C36 | unknown TipoVoto → exit 1, output kept | `test_unknown_vote_stops_the_build` - red at HEAD | `etl/tests/test_presidencia_joint.py:86` `== 1`; `:88`; `:89` snapshot | FAIL |
| C37 | total veto: no votes, PDF URL, counter; device URLs; veto-page fallback | `test_total_veto_has_no_votes` - red at HEAD | `etl/tests/test_presidencia_joint.py:94-97`; `:101-102`; `:106-107` | FAIL |
| C38 | tallies R, H, untrimmed 43825 | `test_tallies` red at HEAD; `test_tallies_untrimmed_device` PASSED | `etl/tests/test_presidencia_joint.py:125`, `:136`; `:144-145` `(37, 414, 0, 0, 3, 0)`, `(8, 64, 0, 0, 0, 0)` | FAIL |
| C39 | resolution rule unit cases | `-k test_resolution_rule` PASSED (HEAD clean too) | `etl/tests/test_presidencia_joint.py:155-160`, `:163`, `:166` | PASS |
| C40 | homonyms split by exercise | `test_homonyms_resolve_by_exercise` - red at HEAD | `etl/tests/test_presidencia_joint.py:172` | FAIL |
| C41 | alias file exact; aliases resolve | `test_aliases` - red at HEAD: the file it asserts is not in the commit | `etl/tests/test_presidencia_joint.py:181` `== ALIASES`; `:182` keys + note; `:186`; `:188`; `:194` | FAIL |
| C42 | unmatched fails closed, one line; ambiguous; counters 0 | `-k test_unmatched_fails_closed` 2 - red at HEAD; `test_unmatched_is_zero_in_recorded_build` (unnamed, full suite) red at HEAD | `etl/tests/test_presidencia_joint.py:208`; `:215` `== 1`; `:217-219`; `:226-228`; `:232` | FAIL |
| C43 | duplicate vote / act id → exit 1 naming it | `-k test_duplicates_stop_the_build` 2 - red at HEAD | `etl/tests/test_presidencia_joint.py:251-253` | FAIL |
| C44 | H member counts exact | `test_member_counts_hand` - red at HEAD | `etl/tests/test_presidencia_counts.py:31-40` | FAIL |
| C45 | R member counts with amended base; unit base | `test_member_counts_recorded` red at HEAD; `test_base_needs_the_members_house_votes` PASSED | `etl/tests/test_presidencia_counts.py:45-59`; `:60` no 7007; `:75` `found == {("camara", 1): {...2/2}, ("senado", 2): {...1/1}}` | FAIL |
| C46 | veto counted once; split → mixed | `test_veto_counted_once`, `test_split_veto_is_mixed` - red at HEAD | `etl/tests/test_presidencia_counts.py:85` `{"count": 1, "total": 1}`; `:94` `(0, 0, 1)` | FAIL |
| C47 | R term coverage exact; sums hold in R/H/T | `test_term_coverage` 3 - red at HEAD | `etl/tests/test_presidencia_counts.py:122` `== R_TERMS` (`:97-104`); `:111`, `:113` sums | FAIL |
| C48 | no rate keys; count objects exact | `test_no_rate_keys` 3 - red at HEAD | `etl/tests/test_presidencia_counts.py:146`, `:148` | FAIL |
| C49 | every file passes in-package + Draft 2020-12; layout | `test_every_file_passes_its_schema` 3, `test_layout` - red at HEAD | `etl/tests/test_presidencia_contract.py:39`; `:40` `Draft202012Validator(spec).is_valid(doc)`; `:41` validate `== 0`; `:47` file list | FAIL |
| C50 | schema failure → exit 1 naming file/path, output kept | `test_schema_failure_keeps_previous_output` - red at HEAD | `etl/tests/test_presidencia_contract.py:64` `== 1`; `:66` `"joint-roll-calls.json: $["`, `"'49.23.001/A' does not match"`; `:67` snapshot | FAIL |
| C51 | meta keys, coverage exact, sources = manifest entries sorted | `test_meta` - red at HEAD | `etl/tests/test_presidencia_contract.py:73`, `:74`, `:75`, `:77`, `:79`, `:84` | FAIL |
| C52 | act and joint sourceUrls | `test_source_urls` - red at HEAD | `etl/tests/test_presidencia_contract.py:94-95` MP exact; `:97` veto prefix; `:99` bill exact; `:101`; `:103-105` exact examples | FAIL |
| C53 | no cpf key; vote keys exact | `test_no_cpf_and_only_allowed_vote_fields` - red at HEAD | `etl/tests/test_presidencia_contract.py:121`; `:124` | FAIL |
| C54 | deterministic; ordering | `test_build_is_deterministic` - red at HEAD | `etl/tests/test_presidencia_contract.py:131` snapshot equal; `:133`; `:135`; `:137`; `:139` | FAIL |
| C55 | one summary line per term; none with `--quiet` | `test_log_line_per_term` - red at HEAD | `etl/tests/test_presidencia_contract.py:148`; `:150` `err == ""`; `:156-159` | FAIL |
| C56 | committed fixture = fresh R build; CI validates it | `test_committed_fixture_is_the_recorded_build` red at HEAD; grep exit 0 | `etl/tests/test_presidencia_contract.py:185` `snapshot(FIXTURE) == snapshot(out)`; `:186`; `.github/workflows/ci.yml:38` | FAIL |
| C57 | v2/v3/senado suites green | 14 files, 220 passed (HEAD clean too) | suite exit 0 | PASS |
| C58 | allowlist additions only; other columns never reach output | `test_allowlist_additions` - red at HEAD | `etl/tests/test_presidencia_acts.py:328-331` allowlists; `:332` no cpf column; `:335` `"Órgão do Poder Executivo" not in text` | FAIL |
| C59 | AGENTS.md profile line | grep exit 0 | `AGENTS.md` line matched | PASS |
| C60 | house dirs read beside `--out` | `test_house_directories_are_siblings_of_out` - red at HEAD | `etl/tests/test_presidencia_sources.py:204-205`; `:207-208` `not (pd.v4(con)).exists()` | FAIL |

Level: every claim naming an exit code or stderr goes through `cli.main` with argv (C4, C5, C11, C13, C21, C36, C42, C43, C50, C55), and the HTTP claims cross a real socket to the fake server (C8-C12). No level gap. Precision: C52 asserts the veto URL form exactly for `vet-49-2023` only (`:103-105`) and only the prefix for the other six vetoes (`:97`). The form is proven, but "every veto `…/<Codigo>`" is asserted on 1 of 7. This is a minor precision note and not a FAIL on its own.

## Coverage

Each set was taken from its authority: the plan's door 4 rule table, door 3, door 5, door 6 with its amendment, and
door 1, plus research `09` for the alias names and the observed values. Each was compared with the code
(`presidency.py:22-107`, `:39-42`, `etl/inputs/joint-vote-aliases.json` in the working tree only), then joined to a
proof that is green at `HEAD`.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| MP status rules (9) + rejects (4) | plan door 4; code `presidency.py:54-78` matches 1:1 (values, statuses, ids) | all 13 by C18 unit (`test_presidencia_acts.py:103`, `:113`), green at HEAD; build layer C20 red | - |
| Device status rules (4) + rejects (3) | plan door 4; `presidency.py:79-88` | all by C19 unit, green at HEAD | - |
| Veto status rules (2) | plan door 4 (`pending` if any device pending) | `veto.01`, `veto.02` only in C22, whose test is red at HEAD | veto.01, veto.02 (proof red at HEAD) |
| Bill status rules (6) + precedence pairs (4) | plan door 4 / AC 14; `presidency.py:155-169` | all by C23 unit (10 rows), green at HEAD | - |
| Device decision on a joint roll call (9: result kept/overridden, method cedula/painel, votesAvailable true/false, sourceUrl device/PDF/veto page) | plan door 5, AC 23, 26, 40; `presidency.py:400-435` | C33, C37, C35 - all red at HEAD | all 9 (proofs red at HEAD) |
| Extra fail-closed guards added by the builder (5) | Handoff settled item 3; `presidency.py:320` (no device), `:403` (voted device not kept/overridden), `:406` (TipoVotacao), `:206` (alias -> absent member), `:274` (act outside every known term) | none: `grep -rn "lists no device\|not kept or overridden\|is not cedula\|absent from\|outside every known term" etl/tests` finds no presidency test | all 5 (no test even in the working tree; AGENTS.md "Comportamento novo sai com teste no mesmo PR") |
| Joint positions (6) + rejects (3) | plan door 5; research 09 l.124, l.145 (`Obstrução` never seen, kept by plan) | all by C34 unit, green at HEAD | - |
| Terms (2) and their dates (start/end/holder/sourceUrl) | plan door 3, Assumptions (EC 111/2021); `presidency.py:22-28` matches | edges asserted at `test_presidencia_acts.py:61-64` but inside C17 proofs that are red at HEAD; `terms()` start-date edge by the unnamed `test_term_listed_from_its_start_date` (green) | meta.terms content and the T-build assignment 2027-01-04 / 2027-01-05 (C17 proofs red at HEAD) |
| Alias entries (4) | plan door 6 + research 09 l.147: `Márcio Bitar`/AC 285, `Janaina Carla Farias`/CE 6351, `Astr. Marcos Pontes`/SP 6009, `Prof. Dorinha Seabra`/TO 5386 - the working-tree file matches exactly | C41 - red at HEAD; the file is not in the commit (`.gitignore:4`, `git ls-files etl/inputs` empty) | all 4 (file absent from HEAD) |
| Unmatched case (1) + ambiguous (1) | AC 31 as amended (fail closed) | C42 - red at HEAD; unit `resolve` returns `(None, n)` in C39 (green) but the stop-the-build is only in C42 | unmatched and ambiguous stop-the-build (proof red at HEAD) |
| Resolution outcomes (6) | door 6 | exact, zero, several, period edge by C39 (green); alias C41, homonyms C40 (red) | alias, homonyms split by exercise |
| Member veto base and indicators (4 indicators, 5 base cases incl. amendment) | door 6 + amendment, AC 32-34 | house-published base by `test_base_needs_the_members_house_votes` (green at HEAD); everything else C44-C46 (red) | participation/keepAll/overrideAll/mixed on built data; in exercise whole time; out of office on a session date; never voted; no session (no entry) |
| `data/v4/presidencia` files (6 + votes dir) and their schemas (6) | door 1 | C7 schema set (green); committed fixture `etl/tests/fixtures/v4/presidencia` holds exactly the C49 layout and `mandato-etl validate` exits 0 on it at HEAD; C49-C51 build proofs red | - (proven via C7 + validate on the committed fixture) |
| `validate` choices (5) + v3 behaviour on v4 house dirs | AC 4, door 1 ("house shapes identical to v3") | C5 red at HEAD. Merge interaction: etl-senado's referential check runs only `if version == 3` (`etl/src/mandato_etl/schema.py:162`), so a v4 house directory, including the one the presidency gate validates (`cli.py` `_house_members`), skips the dangling-member check a byte-identical v3 directory gets | v2, v3, v4 house, v4 presidency, unknown version (proof red at HEAD); dangling-member check on v4 house dirs (absent) |
| Congress source files (5 kinds + manifest) and retryables (4) | door 7 | C8, C9, C11 red at HEAD (throttle C10 green) | all 6 file kinds, 503/429/empty/non-JSON retry (proofs red at HEAD) |
| Coverage counters (6), stage cases (4), act sourceUrl forms (3) | AC 35, 39; AC 18-22; AC 40 | C16, C25, C29-C30, C37, C42, C47, C51, C52 - red at HEAD | all (proofs red at HEAD) |
| `build --contract 4` exit codes (3) | Observable | 0 C1/C2, 1 C4/C13 green; 2 C11 red | exit 2 |

## Test policy rows

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `presidency.py` | own layer: C18, C19, C23, C34, C39 green; boundary: C20, C24, C35-C46 red at HEAD; 5 builder-added stop-the-build branches have no proof at either layer | no - boundary proofs red at HEAD, 5 guards untested |
| Decides, reached across a boundary | `sources/congresso.py` | C8-C12 across HTTP | no - C8, C9, C11, C12 red at HEAD (C10 green) |
| Decides, reached across a boundary | `cli.py` | C3, C4, C13, C60 at the CLI | no - C3, C60 red at HEAD (C4, C13 green) |
| Decides, reached across a boundary | `schema.py`, `publish.py` | C5, C49, C50 at the CLI | no - all three red at HEAD |
| Decides, not reached across a boundary | none classified by the checks | - | yes (nothing classified) |
| Instrumentation, pass-throughs | `contract_v3.py` version parameter | covered by C1, C2 | yes - C1, C2 green at HEAD |

## Faults injected

Scratch: `git worktree add /tmp/presid-verify HEAD` with its own `uv sync`. The ignored alias file was copied into the
scratch only, because without it every covering proof is already red. Real-tree `git status --porcelain` was empty
before and empty after. The scratch was removed with `git worktree remove --force`. A second scratch
(`/tmp/presid-verify2`), used only to list the clean-checkout failures, was removed the same way, and the porcelain was
still empty.

| Mutation | Location | Killed |
| --- | --- | --- |
| MP rule table: `APROVADO_PLV` -> `approved` instead of `approvedAmended` | `etl/src/mandato_etl/presidency.py:57` | yes - `test_mp_status_rules` 1 failed |
| veto status: `pending` only when every device is pending | `etl/src/mandato_etl/presidency.py:152` | yes - `test_veto_status` failed |
| alias resolution skipped (`alias = None`) | `etl/src/mandato_etl/presidency.py:203` | yes - `test_aliases` errored (its R build fails closed on `Márcio Bitar`) |
| unmatched vote no longer stops the build (`if failed and False`) | `etl/src/mandato_etl/presidency.py:447` | yes - `test_unmatched_fails_closed` failed |
| member veto base ignores the member's house (`published[house]` -> `voted`) | `etl/src/mandato_etl/presidency.py:474` | yes - `test_base_needs_the_members_house_votes` failed |

## Handoff deviations

- (1) Three older tests moved from version 4 to 5 as the unknown version (`test_v3_cli.py` `contract-5` and `schema_version 5`, `test_publish.py` `v4` beside `v3`). The assertions are unchanged and door 1 makes 4 a known version. Accepted.
- (2) Landing row 8 was written after the code. `b7c32bd` (checks, 21:34) precedes `4d62e37` (code, 21:41) and already pins C20/C28/C47/C60. Accepted, and recorded transparently.
- (3) Extra fail-closed guards. These are consistent with AD-017 and AD-020, but four of the five (and the out-of-term guard) have no test. This is a finding (Coverage row above).
- (4) The PEC hand date and (5) no pattern on `jointRollCallId`: accepted, since no check was edited.
- (6) Closed by the orchestrator's amendment, and proven by `test_base_needs_the_members_house_votes` and C45.
- (7) Term date pinned by constants only, a go-live item. Accepted as out of scope.
- Deferred per-house count of vetoes with no published votes: accepted. `presidency-meta` `coverage` has `additionalProperties: false` with the six AC 39 keys (`etl/schema/v4/presidency-meta.schema.json`), so adding a key needs a version bump.
- Merge `bb01ceb`: clean, with no conflict hunks (`git show --cc` is empty). The merged etl-senado code (camara `opener`, senado redirect warning, `assemble_senado` membership guard, `_dangling_member`) leaves this feature's behaviour intact, except for the v4 house validation gap in Coverage.

Swept existing: `conftest.FakeCamara` serving `/dadosabertos` (`etl/tests/conftest.py`), validate-then-swap in `publish.write` (`etl/src/mandato_etl/publish.py:23-30`, now refusing unknown files), and the `readers.read` allowlist (`etl/src/mandato_etl/readers.py:15`, `:60`, applied at `:82`) are all present as cited.

## Gate

- Real tree at `bb01ceb`: `uv run --directory etl pytest -v` - 453 passed, 0 failed. Named proofs batch: 117 passed. C57: 220 passed. `mandato-etl validate tests/fixtures/v4/presidencia` exit 0
- Committed `HEAD` (clean worktree, own `uv sync`): `uv run --directory etl pytest -q` - 390 passed, 40 failed, 23 errors (all `FileNotFoundError: etl/inputs/joint-vote-aliases.json`). 46 of 60 checks have a red proof
- `python3 ~/.claude/skills/tlc-spec-lean/scripts/validate_verification.py etl-presidencia` - exit 1, its only error being "verdict is FAIL"; no row contradicts the verdict

Ranked gaps:

1. `etl/inputs/joint-vote-aliases.json` is gitignored (`.gitignore:4` `etl/inputs/*`, with only `candidacy-2026.json` excepted) and absent from every commit. Every presidency build at `HEAD` fails, so 46 checks have red proofs and CI would fail. Fix: add `!etl/inputs/joint-vote-aliases.json` to `.gitignore` and commit the file. C3, C5, C8, C9, C11, C12, C14-C17, C20-C22, C24-C33, C35-C38, C40-C56, C58, C60 - `etl/src/mandato_etl/presidency.py:19`
2. Five builder-added stop-the-build branches have no test: a veto with no device, a voted device not kept or overridden, a `TipoVotacao` outside the map, an alias pointing at an absent member, and an act outside every known term. Coverage "extra guards" - `etl/src/mandato_etl/presidency.py:320,403,406,206,274`
3. Merge interaction: the referential check `_dangling_member` runs only on v3 directories, so the v4 house directories (identical to v3 by door 1) and the presidency build's house gate skip it. C5 / door 1 - `etl/src/mandato_etl/schema.py:162`
4. Precision: the veto `sourceUrl` code is asserted exactly for one veto of seven. C52 - `etl/tests/test_presidencia_contract.py:97`
