# secret-ballots checks

Profile: standard
Plan: `.specs/features/secret-ballots/plan.md`

## Intent

18 checks in 2 slices · 2 one-way doors (contract version 2 with `secret`, the secret-ballot rule) · 0 open

All proofs run from the repository root. `P` abbreviates `uv run --directory etl pytest`; `T` abbreviates
`npm --prefix site test --` (vitest). "The variant" is `legislature(secret=True)` in `etl/tests/conftest.py`:
the shared `legislature()` plus roll call `100-6` (PLEN, `2025-08-01T15:00:00`, `aprovacao 1`,
`votosSim 12`, `votosNao 5`, `votosOutros 2`) with an empty `voto` for deputies 101, 102 and 103. In the
variant 101 and 102 are in exercise on that date and 103 is not. `legislature()` itself does not change.

Renegotiated by the approved plan, and only these values: in `site/tests/`, the site fixture's roll calls go
from 7 to 8, 101's participation from `3 de 4` (`--share: 0.75`) to `4 de 5` (`--share: 0.8`), 102's from
`2 de 2` to `3 de 3`, the rejected contract version from `2` to `3` and the accepted one from `1` to `2`; in
`etl/tests/`, `test_publish.py:39` expects `2` and `test_schema.py:48` injects `3`. No other approved
assertion changes.

Renegotiated during the build (maintainer, 2026-09-27), because the approved rule contradicted the approved
fixture: `100-3`'s only record was 101's empty vote, so door 2 made it secret. `legislature()` gains
`("103", R3, "Não")`, which keeps `100-3` open (103 is never in exercise and `100-3` has no Governo
orientation, so no indicator moves). Consequences, and only these: `test_roll_calls.py` expects `secret` in the
roll-call key set (door 1) and `100-3` tallies `0/1/0` instead of `0/0/0`; C7 admits `test_roll_calls.py`;
in `site/tests/`, page `100-3` gains `Não (1)`, profile 103 gains a row, and site C20 lists 8 votes for 101 with
`100-6` first. A roll call built without the official columns (only possible outside `readers`, which require
them) keeps tallies counted from its records, so `test_valid_vote_set_table[empty]` runs unchanged.

## Checks

### S1 - The ETL publishes secret ballots with official totals · 8 files · 70 KB · ~18k

**C1** - Building the variant writes `"secret": true` for `100-6` in `roll-calls.json` and in `roll-calls/100-6.json`, and `"secret": false` for each of the other 7 roll calls in both places (AC 1)
Proof: `P tests/test_secret_ballots.py::test_secret_flag_marks_only_the_all_empty_roll_call`

**C2** - `compute.is_secret` returns `False` for `[]`, `True` for `[""]`, `True` for `["", ""]`, `False` for `["", "Sim"]` and `False` for `["Artigo 17"]` (AC 1, door 2)
Proof: `P tests/test_secret_ballots.py::test_secret_rule_table`

**C3** - In the variant, `100-6` has `tallies == {"yes": 12, "no": 5, "others": 2}` in `roll-calls.json` and `roll-calls/100-6.json`, while `100-1` keeps `{"yes": 2, "no": 1, "others": 0}` counted from its records, not the shared `9/9/9` (AC 2)
Proof: `P tests/test_secret_ballots.py::test_secret_tallies_come_from_official_totals`

**C4** - In the variant, `participation` is `{"count": 4, "total": 5}` for 101, `{"count": 3, "total": 3}` for 102 and `{"count": 0, "total": 0}` for 103; 101's empty vote in the open roll call `100-3` still does not count (AC 3)
Proof: `P tests/test_secret_ballots.py::test_secret_record_counts_for_participation`

**C5** - In the variant, `governmentAlignment` and `partyAlignment` equal those of the shared legislature (101: `2/3`, `2/3`; 102: `1/1`, `2/3`; 103: `0/1`, `0/0`), and every `100-6` entry in a deputy's `votes` has `partyMajority: null` (AC 4)
Proof: `P tests/test_secret_ballots.py::test_secret_record_is_not_a_valid_vote`

**C6** - A build writes `meta.schema_version == 2`, `etl/schema/meta.schema.json` has `"const": 2`, and `mandato-etl validate` exits `1` for an output whose first `roll-calls.json` item has no `secret` and for one whose `roll-calls/100-1.json` has `"secret": "no"`, and `0` for the unmodified output (AC 5, door 1)
Proof: `P tests/test_publish.py::test_meta_fields`
Proof: `P tests/test_secret_ballots.py::test_validate_requires_a_boolean_secret`

**C7** - The approved etl tests run unchanged apart from the two version lines and the renegotiation above: `git diff 9921e6c -- etl/tests` touches only `conftest.py` (the variant and `100-3`), `test_publish.py`, `test_roll_calls.py`, `test_schema.py` and the new `test_secret_ballots.py`, and the whole suite passes
Proof: `test "$(git diff --name-only 9921e6c -- etl/tests | sort | tr '\n' ' ')" = "etl/tests/conftest.py etl/tests/test_publish.py etl/tests/test_roll_calls.py etl/tests/test_schema.py etl/tests/test_secret_ballots.py " && uv run --directory etl pytest -q`

**C8** - Over the real cache, `mandato-etl build` marks exactly 2 roll calls secret, `2645346-18` with tallies `404/61/1` and `2576389-4` with `388/22/11`, and `mandato-etl validate` exits 0 (AC 1, AC 2)
Proof: `uv run --directory etl mandato-etl build --quiet && uv run --directory etl mandato-etl validate && python3 -c "import json; rc={r['id']:r for r in json.load(open('data/out/roll-calls.json'))}; s=sorted(i for i,r in rc.items() if r['secret']); assert s==['2576389-4','2645346-18'], s; assert rc['2645346-18']['tallies']=={'yes':404,'no':61,'others':1}; assert rc['2576389-4']['tallies']=={'yes':388,'no':22,'others':11}"`

### S2 - The site says a vote was secret · 10 files · 75 KB · ~19k

**C9** - `site/tests/fixtures/out/` is exported from the variant: `mandato-etl validate ../site/tests/fixtures/out` exits 0, its `meta.json` has `schema_version: 2`, and `roll-calls.json` holds 8 roll calls with `100-6` the only `secret: true` (AC 5, plan assumption)
Proof: `uv run --directory etl mandato-etl validate ../site/tests/fixtures/out && python3 -c "import json; m=json.load(open('site/tests/fixtures/out/meta.json')); r=json.load(open('site/tests/fixtures/out/roll-calls.json')); assert m['schema_version']==2; assert len(r)==8; assert [x['id'] for x in r if x['secret']]==['100-6']"`

**C10** - `loadContract` throws exactly `Unsupported data contract: meta.json has schema_version 3; this site reads 2` for version 3 and `... schema_version 1; this site reads 2` for version 1, and `astro build` over the version-3 copy exits non-zero with that line (AC 9; site C2 renegotiated)
Proof: `T tests/data.test.ts -t "rejects schema_version 3"`
Proof: `T tests/data.test.ts -t "rejects schema_version 1"`
Proof: `T tests/build.test.ts -t "fails on schema_version 3"`

**C11** - `loadContract` exposes `secret` on every roll-call summary and document (`100-6` true, `100-1` false), and the existing "reads only schema fields" test still passes against the version-2 schemas (door 1)
Proof: `T tests/data.test.ts -t "reads the secret flag"`
Proof: `T tests/data.test.ts -t "reads only schema fields"`

**C12** - Page `100-6` shows `Votação secreta: a Câmara registra quem votou, não o voto de cada deputado.`, the label `Totais oficiais da Câmara` with `Sim 12`, `Não 5`, `Outros 2`, and exactly one vote group headed `Deputados que votaram (3)` listing `/deputados/101/` `Ana Souza` `PT-SP`, `/deputados/102/` `Bruno Lima` `PT-RJ`, `/deputados/103/` `Carla Dias` `NOVO-MG` in that order; it contains no `Registro sem voto` (AC 6)
Proof: `T tests/build.test.ts -t "secret roll call"`

**C13** - Page `100-1` (open) contains neither `Votação secreta` nor `Totais oficiais da Câmara` and still groups `Sim (2)` and `Não (1)`; page `100-3` still groups its empty record under `Registro sem voto` (AC 6, discrimination)
Proof: `T tests/build.test.ts -t "open roll calls carry no secret notice"`

**C14** - `voteLabel("", true)` is `Votação secreta`, `voteLabel("", false)` is `Registro sem voto`, `voteLabel("Artigo 17", false)` is `Art. 17 (presidente da sessão)`; profile 101's row `data-roll-call="100-6"` shows `Votação secreta` and its row `100-3` shows `Registro sem voto` (AC 7)
Proof: `T tests/format.test.ts -t "vote labels for secret ballots"`
Proof: `T tests/build.test.ts -t "profile secret vote"`

**C15** - Profile 101's participation block reads `4 de 5` with `style="--share: 0.8"` and its base line ends with `inclusive Art. 17 e votações secretas.`; profile 102's reads `3 de 3` (AC 3, AC 8)
Proof: `T tests/build.test.ts -t "profile indicators"`

**C16** - The fixture build writes 8 roll-call pages (`100-1` … `100-4`, `100-6`, `200-1` … `200-3`) and 12 pages carry the collection date; the home reads `8 votações nominais`; the 101 card reads `4 de 5` and the 102 card `3 de 3` for participation (site C1, C5, C13, C31, C37 renegotiated)
Proof: `T tests/build.test.ts -t "writes one page per deputy and per roll call"`
Proof: `T tests/build.test.ts -t "every page states the collection date"`
Proof: `T tests/build.test.ts -t "home states what the site is and the legislature totals"`
Proof: `T tests/cards.test.ts -t "deputy card content"`

**C17** - No text the site writes for a secret ballot uses a forbidden term, and the built `100-6` page has no `%` in its visible text (AC 6, site AC 27-28)
Proof: `T tests/language.test.ts -t "site source uses no forbidden term"`
Proof: `T tests/build.test.ts -t "built pages use no forbidden term and no percentage"`

**C18** - The CI sequence exits 0: `npm --prefix site ci`, `npm --prefix site test`, `MANDATO_DATA_DIR=tests/fixtures/out MANDATO_PHOTOS=off npm --prefix site run build`, `uv run --directory etl pytest -q`
Proof: `npm --prefix site ci && npm --prefix site test && MANDATO_DATA_DIR=tests/fixtures/out MANDATO_PHOTOS=off npm --prefix site run build && uv run --directory etl pytest -q`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| secret rule inputs (5) | `[]` C2 · `[""]` C2 · `["",""]` C2 · `["","Sim"]` C2 · `["Artigo 17"]` C2 | - |
| tallies source (2) | official totals when secret C3 · records otherwise C3 | - |
| participation record kinds (3) | non-empty vote C4 · record in a secret roll call C4 · empty record in an open roll call C4 | - |
| deputies in the variant (3) | 101 in exercise C4 · 102 in exercise after leave C4 · 103 out of exercise C4 | - |
| alignment indicators unaffected (2) | governmentAlignment C5 · partyAlignment C5 | - |
| contract version places (3) | ETL `meta.json` C6 · `meta.schema.json` C6 · site `SCHEMA_VERSION` C10 | - |
| `secret` field places (3) | `roll-calls.json` C1 · `roll-calls/{id}.json` C1 · site data layer C11 | - |
| `secret` field validation (2) | missing C6 · non-boolean C6 | - |
| roll-call page states (2) | secret C12 · open C13 | - |
| profile vote labels (3) | secret C14 · empty in open roll call C14 · other value C14 | - |
| `GET /votacoes/{id}/` statuses (2) | 200 C16 · 404 site C1 (unchanged) | - |
| real secret ballots (2) | `2645346-18` C8 · `2576389-4` C8 | - |
| startup config: contract version (2 assemblies) | ETL build C6 · site build C10 | - |

- Claims naming a route: C12, C13, C16 - proven on the built `dist/`
- No other check claims more than the cases its proof exercises

## Test policy

Inherits the rows of `.specs/features/etl-camara/checks.md` `## Test policy` for `etl/`; the site's own tests follow the site checks (unit for pure functions, one real build for pages).

| Code | Required proofs | Coverage expectation |
| --- | --- | --- |
| Decides, reached across a boundary (CLI, built page) | one at the boundary **and** one at its own layer | the contract or page at the boundary; one asserted case per row of the decision table at its own layer |
| Decides, not reached across a boundary | one at its own layer | one asserted case per row of the decision table |
| Instrumentation, pass-throughs | none of its own | covered by its consumer's proof |

Evidence:

- `compute.is_secret` (new): 3 outcomes over empty, all-empty and mixed records -> decides, own layer C2, boundary C1
- `compute` tallies: 2 sources chosen by `secret` -> decides, boundary C3 (the choice lives inside the roll-call assembly; C3 asserts both rows)
- `compute` participation: 3 record kinds -> decides, boundary C4 over all three
- `readers` allowlist for `votacoes`: forwards 3 more columns, no conditional -> instrumentation, covered by C3
- `site/src/lib/format.ts` `voteLabel`: 3 rows -> decides, own layer C14, boundary C14
- `site/src/pages/votacoes/[id].astro`: branches on `secret` -> decides, boundary C12 and C13
- closest analogue: etl-camara C18-C22 (indicators proven on the built contract), site C22 (vote labels at both layers)

Cost: 1 new etl test file with 6 tests, ~8 site test additions or edits. Without these rows the participation change would be proven only by the real-data run in C8.

## Swept

- validation: C6 - `secret` required and boolean in the schema
- failure modes: C10 - a version-1 `data/out/` fails the site build with the version message
- idempotency: existing - etl-camara `test_builds_are_byte_identical` keeps covering the shared legislature
- authorization: n/a - public static site, no accounts
- concurrency: n/a - one build process
- data lifecycle: n/a - nothing persisted beyond `data/out/`, rewritten by the next build
- dependency failure: n/a - no new source; the three columns come from the same `votacoes` file
- state transitions: n/a - `secret` is derived per build, no stored state
- observability: n/a - no logging requirement

## Handoff

- Size: S1 18k + S2 19k = 37k of change and tests, plus ~35k to read the two plans, this file, `compute.py`, `conftest.py`, the site's `build.test.ts` and pages = 72k, under the 150k budget - one builder
- Mechanism: one builder (fits; no ask), in a new Claude session at the maintainer's request (2026-09-27); the brief is `research/HANDOFF-secret-ballots.md`
- Hazard for the builder: `etl/tests/test_publish.py:92` builds `votacoes` rows without `votosSim`, `votosNao`, `votosOutros`; those columns must be required only for a secret roll call, or that approved test breaks
