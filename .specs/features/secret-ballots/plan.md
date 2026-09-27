# secret-ballots: roll calls whose votes the Câmara does not disclose

## Problem

The Câmara publishes two plenary roll calls of the 57th legislature as secret ballots: `2645346-18` (2026-09-02, PDL 995/2026, choice of a TCU minister) and `2576389-4` (2025-10-29, choice of Daiane Nogueira de Lira). `votacoesVotos` carries one record per deputy who voted, with a timestamp and an empty `voto`: 466 records for `2645346-18`. `votacoes` carries the official totals for both (`votosSim 404, votosNao 61, votosOutros 1` and `388, 22, 11`).

Three things follow today, found in the browser check of PR #6:

- the roll-call page shows `Sim 0`, `Não 0`, `Outros 0` and lists 466 deputies under `Registro sem voto`, while the official description right above says `Sim: 404; Não: 61`
- each of those deputies' profiles lists the vote as `Registro sem voto`
- `participation` (etl-camara AC 17) counts a roll call only when `vote` is non-empty, so a deputy who voted in a secret ballot keeps it in `total` and loses it from `count`: up to 2 roll calls understated per deputy, on the indicator a parliamentarian is most likely to contest

When this ships, a secret ballot reads as one on every page, shows the Câmara's official totals, and counts as participation for every deputy with a record in it (maintainer's choice, option 1, 2026-09-27).

## Flow

Reuses the ETL's roll-call assembly and the site's single data layer; no new module.

1. `votacoes-*.csv` -> `mandato_etl.readers` (exists) - the allowlist gains `votosSim`, `votosNao`, `votosOutros`
2. `readers` -> `mandato_etl.compute` (exists) - marks a roll call `secret` when it has at least one vote record and every record's `vote` is empty; a secret roll call takes its `tallies` from the official totals, and a record in it counts for `participation.count`
3. `compute` -> `mandato_etl.cli` / `publish` (exist) - write `secret` on every roll call and `schema_version: 2` (door 1)
4. `data/out/` -> `site/src/lib/data.ts` (exists) - reads `secret`, accepts only version 2 -> roll-call and profile pages render it

## Impact

| Front | What changes |
| --- | --- |
| domain | new term: `secret` roll call - a roll call with at least one vote record in which every record's `vote` is empty; lives in `compute` |
| domain | existing term: `participation.count` meant roll calls with a non-empty `vote`; now also roll calls where the deputy has a record and the roll call is `secret` (etl-camara AC 17 amended) |
| domain | existing term: `tallies` meant counted from the vote records; for a `secret` roll call it now means the official `votosSim`, `votosNao`, `votosOutros` |
| stored data | nothing to migrate - `data/out/` is rewritten by the next `mandato-etl build`; a v1 `data/out/` fails the site build with the existing version message until then |
| other features | `site`: `SCHEMA_VERSION` 1 -> 2, so approved check C2 changes its values (`schema_version 2` -> `3`, `reads 1` -> `reads 2`); the site fixture gains one roll call, so C1, C5, C13, C16, C29, C31, C37 change their fixture values (listed under Assumptions) |
| other features | `etl-camara`: approved tests keep the shared legislature untouched; the version tests (`test_publish.py:39`, `test_schema.py:48`) change `1` -> `2` and `2` -> `3` |

## Relations

`None - no new entity`. `RollCall` gains one field, `secret`, recorded in door 1.

## Surface

| Route | In | Out | Status |
| --- | --- | --- | --- |
| `GET /votacoes/{id}/` | roll-call id | for a secret roll call: the notice, official totals, one group of deputies with a record | `200`, `404` for an unknown id (unchanged) |

## Landing

| One-way door | Literal shape | Alternative rejected |
| --- | --- | --- |
| Contract version 2 | `"secret": <boolean>`, required, on every item of `roll-calls.json` and in every `roll-calls/{id}.json`; `meta.schema_version` is `2` and `meta.schema.json` has `"const": 2`; the site's `SCHEMA_VERSION` is `2` | the site inferring secrecy from empty votes - `roll-calls.json` carries no votes, profiles would have to read 1,597 files, and AD-002 keeps derivations in the ETL; adding the field under version 1 - breaks the etl-camara door that a contract change is a version bump |
| Secret-ballot rule | `secret = len(records) > 0 and all(vote == "" for vote in records)`, per roll call, over the vote records the contract publishes | a hand-kept list of secret roll-call ids - goes stale with the next election of an authority; matching "secreta" in `descricao` - neither of the two known descriptions contains it |

- Nothing else in this change is hard to reverse

## Criteria

### S1: The ETL publishes secret ballots with official totals (P1)

**Acceptance Criteria**

1. WHEN a roll call has at least one vote record and every record's `vote` is empty THEN the system SHALL write `"secret": true` for it in `roll-calls.json` and `roll-calls/{id}.json`, and `"secret": false` for every other roll call
2. WHEN a roll call is secret THEN the system SHALL set `tallies.yes`, `tallies.no` and `tallies.others` to the integers in `votosSim`, `votosNao` and `votosOutros` of its `votacoes` row; for any other roll call `tallies` SHALL stay counted from the vote records
3. The system SHALL set `participation.count` to the eligible PLEN roll calls in which the deputy has a non-empty `vote` or has a record in a secret roll call; `participation.total` is unchanged (amends etl-camara AC 17)
4. The system SHALL leave `governmentAlignment` and `partyAlignment` unchanged: a record in a secret roll call is not a valid vote
5. The system SHALL write `schema_version: 2`, and `mandato-etl validate` SHALL reject a `roll-calls.json` item without `secret`

**Independent test:** build the test legislature plus one secret PLEN roll call voted by deputies 101 and 102 in exercise; read `secret: true`, the official tallies, and participation `4 de 5` for 101 and `3 de 3` for 102.

### S2: The site says a vote was secret (P1)

**Acceptance Criteria**

6. WHEN `/votacoes/{id}/` renders a secret roll call THEN the system SHALL show `Votação secreta: a Câmara registra quem votou, não o voto de cada deputado.`, label the tallies `Totais oficiais da Câmara`, and list every deputy with a record under one group `Deputados que votaram (<n>)`, alphabetical, each linked with party and UF
7. WHEN a profile lists a vote in a secret roll call THEN the system SHALL render the vote as `Votação secreta` instead of `Registro sem voto`
8. The participation base-of-calculation line SHALL read `... inclusive Art. 17 e votações secretas.`
9. IF `meta.json` carries a `schema_version` other than `2` THEN the build SHALL exit non-zero with `Unsupported data contract: meta.json has schema_version <n>; this site reads 2`

**Independent test:** build the site fixture and open the secret roll call: the notice, `Sim 12`, `Não 5`, `Outros 2` from the fixture's official totals, and three deputies under `Deputados que votaram (3)`.

## Out of scope

| Excluded | Why |
| --- | --- |
| Official totals for roll calls that are not secret | the records are the source for those today; switching every roll call is a separate decision |
| A secret-ballot filter or list | grilling decision 11: no list pages beyond home, profile and roll call |
| Telling two roll calls on the same proposition apart in the profile list | found in the same browser check; a copy decision for after launch |

## Assumptions

| Assumption | Chosen default | Rationale | Confirmed? |
| --- | --- | --- | --- |
| Test legislature | etl-camara's approved tests keep `legislature()`; a variant `legislature(secret=True)` adds roll call `100-6`, PLEN, 2025-08-01, empty votes by 101, 102 and 103, official totals `votosSim 12`, `votosNao 5`, `votosOutros 2` (distinct, unlike the shared `9/9/9`); the new ETL tests and the site fixture use the variant | the approved etl checks keep their hand-computed numbers; the site fixture shows a secret ballot | y |
| Site fixture values that change | roll calls 7 -> 8 (C1, C29 lists gain `100-6`; C5, C37 count 12 pages; C13 `8 votações nominais`); 101 participation `3 de 4` -> `4 de 5`, `--share 0.8` (C16, C31); 102 participation `2 de 2` -> `3 de 3` (C16, C31); 103 is out of exercise, unchanged | the variant adds one PLEN roll call inside 101's and 102's exercise | y |
| Verification profile | `standard`, as etl-camara: the change moves an indicator | AGENTS.md | y |

**Open questions:** none - all resolved or logged above.

## Observable

| Surface | Decision | Landing |
| --- | --- | --- |
| screen roll call | secret state | AC 6 |
| screen roll call | empty, loading, error, unauthorised | n/a - unchanged from the site plan |
| screen profile | secret vote row | AC 7 |
| document page copy | tone | AC 6, AC 7, AC 8 - descriptive, no word about why the ballot was secret |
| command `mandato-etl build` / `validate` | output and exit codes | AC 5; unchanged otherwise |
| contract | versioning | door 1, AC 5, AC 9 |

## Sources

- Browser check of PR #6, 2026-09-27 - page `/votacoes/2645346-18/`
- `data/raw/votacoesVotos-2026.csv`, `data/raw/votacoes-2026.csv`, `data/raw/votacoes-2025.csv` - empty votes and official totals for the two roll calls
- `.specs/features/etl-camara/plan.md` AC 14, AC 17 and the contract door; `.specs/features/site/plan.md` door 2
- `.specs/STATE.md` AD-002, AD-004
