# etl-senado checks

Profile: standard
Plan: `.specs/features/etl-senado/plan.md`

44 checks in 5 slices · 4 one-way doors in the plan, 2 added while deriving checks (doors 5 and 6, below) · 0 open questions

All proofs run from the repository root. `P` below abbreviates `uv run --directory etl pytest`.
The feature base is `6a0d768` (the plan's approval; no code after contract-v3's `f73b12c`).
Senate fixtures are served by the existing `conftest.FakeCamara` (a generic local HTTP server) with
`sources.senado.API_URL` pointed at it; no test reaches the live API. Recorded responses, taken on
2026-10-02 from `https://legis.senado.leg.br/dadosabertos` at under one request per second, live in
`etl/tests/fixtures/v3/recorded/senado/`, trimmed to the records a check names and with every
personal value (`sexoParlamentar`, `NomeCompletoParlamentar`, `SexoParlamentar`, `EmailParlamentar`)
replaced by a sentinel. The clock is pinned at `2026-09-27T12:00:00Z` (`2026-09-27T09:00:00` Brasília)
unless a check names `2027-03-01T12:00:00Z` (`2027-03-01T09:00:00` Brasília).

Three datasets carry the expected numbers, computed on paper and written next to the rows in
`etl/tests/senado_data.py`:

- **indicators** (S4, `--years 2025`): senators 9001 Ana (PT, SP, open exercise from `2023-02-01`), 9002 Beto (PT, RJ, exercise `2023-02-01`..`2025-03-13`), 9003 Caio (PL, MG, open), 9004 Dani (`S/Partido`, BA, open), 9005 Eva (`S/Partido`, GO, open; `LAP` in V7), 9006 Fabio (listed, only exercise `2019-02-01`..`2021-03-19`, no vote). Roll calls V1-V7 = `6901`-`6907` on `2025-03-10`..`2025-03-16`: V1 procedural `senado.01` seq 5001; V2 final `senado.07` seq 5002; V3 amendment `senado.04` seq 5003; V4 secret final `senado.06` seq 5004, totals 40/1/1; V5 final `senado.07` seq 5005; V6 final `senado.05`, `sequencialVotacao: null`, no twin; V7 procedural `senado.02` seq 5007. Government orientation: V1 `SIM`, V2 `NÃO`, V3 `LIVRE`, V4 none, V5 `SIM`, V6 none, V7 `SIM`
- **recorded** (S3, `--years 2023 2025`): the real `/votacao` records 6755, 6709, 6757, 6679, 6748, 6680, 6704 (2023) and 6921, 7017, 7018, 7045, 7046 (2025), their real orientation entries, plus one hand record `9999` (`2025-12-18`, "Votação nominal do Parecer nº 9, de 2025.")
- **members** (S2, clock 2027, `--years 2025 2027`): the real list entries of 4605 (titular who resigned), 6358 (the alternate who took the seat) and 5666 (only exercise ended 2021); hand senators 9201 (single `Mandato`, `Exercicio` and `Suplente` objects, open exercise, mandate over the 57th and 58th, listed in both) and 9202 (listed for the 57th, only exercise in 2019-2021, one vote record in 2025). Roll calls M1 `6801` (`2025-05-01`), M2 `6802` (`2027-01-20`), M3 `6803` (`2027-02-03`)

## Checks

### S1 - Download and snapshot · 6 files · 60 KB · ~15k

**C1** - A Senate build with an empty raw cache at the 2027 clock and `--years 2025 2027` requests exactly: `/votacao?dataInicio=2025-01-01&dataFim=2025-12-31`, `/votacao?dataInicio=2027-01-01&dataFim=2027-01-31`, `/votacao?dataInicio=2027-02-01&dataFim=2027-12-31`, `/plenario/votacao/orientacaoBancada/20250101/20251231`, `/plenario/votacao/orientacaoBancada/20270101/20270131`, `/plenario/votacao/orientacaoBancada/20270201/20271231`, `/senador/lista/legislatura/57?exercicio=S`, `/senador/lista/legislatura/58?exercicio=S`, `/senador/lista/atual`, plus one `/processo?codigoParlamentarAutor=<id>&dataInicioApresentacao=<start of the member's first built legislature>` per member, each with `Accept: application/json` and the project `User-Agent`; every file has one manifest entry with `file`, `sourceUrl`, `sha256`, `bytes`, `downloadedAt` whose `sha256` and `bytes` equal the stored file (AC 1)
Proof: `P tests/test_senado_sources.py::test_first_build_downloads_each_source_once`

**C2** - A second build without `--refresh` issues 0 HTTP requests and writes byte-identical output; with `--refresh` it requests every file of C1 again (AC 2)
Proof: `P tests/test_senado_sources.py::test_cache_hit_issues_no_request`

**C3** - `/votacao` for 2025 answering 503, 503, 503, 503 makes the build exit `2` with that URL on stderr after sleeps `[1, 2, 4]`, leaves no `*.part` file under the raw cache, and a `data/v3/senado` built before keeps its bytes; answering 503 once then 200 exits `0` after one sleep of 1 s (AC 3)
Proof: `P tests/test_senado_sources.py::test_download_failure_exits_2_and_keeps_output`
Proof: `P tests/test_senado_sources.py::test_retry_then_success`

**C4** - With the server holding each response for 50 ms, the per-senator `/processo` lists and `/processo/{id}` details of the indicators dataset (5 lists, 3 details) never have more than 4 requests in flight (AC 4)
Proof: `P tests/test_senado_sources.py::test_at_most_four_concurrent_requests`

**C5** - A `/senador/lista/atual` response carrying `Deprecation: Tue, 18 Mar 2025` and `Sunset: Sun, 01 Feb 2026` makes the build print on stderr a line holding `warning`, that URL and `Sun, 01 Feb 2026`, and exit `0` (AC 5)
Proof: `P tests/test_senado_sources.py::test_deprecation_header_warns_and_continues`
Proof: `P tests/test_senado_sources.py::test_deprecation_sent_as_a_redirect_warns_and_continues`

**C6** - `/votacao` answering `{}` makes the build exit `1` naming `senado/votacao-57-2025.json`; a legislature list without `ListaParlamentarLegislatura.Parlamentares.Parlamentar` exits `1` naming `senado/legislatura-57.json`; neither writes `data/v3/senado` (AC 6)
Proof: `P tests/test_senado_sources.py -k test_bad_envelope_exits_1`

**C7** - `build --house senado` without `--contract`, and `build --contract 2 --house senado`, each exit `1` with `usage:` on stderr, 0 HTTP requests and no output directory; `build --contract 3 --house senado` with no `--out` writes `<data>/v3/senado/meta.json` and nothing under `<data>/v3/camara` or `<data>/out` (AC 7, Flow hop 1)
Proof: `P tests/test_senado_cli.py -k test_senado_needs_contract_3`
Proof: `P tests/test_senado_cli.py::test_senado_default_out`

### S2 - Senators and mandates · 4 files · 45 KB · ~11k

**C8** - In the members dataset at the 2027 clock, members are exactly 4605, 6358, 9201, 9202; mandates are 4605 `[57]`, 6358 `[57]`, 9201 `[57, 58]`, 9202 `[57]` (only a vote record in the 57th); 5666 (exercise ended 2021, no vote) has no record (AC 8)
Proof: `P tests/test_senado_members.py::test_mandate_per_exercise_or_vote`

**C9** - 57th `exercisePeriods`: 4605 `[{2023-02-01T00:00:00, 2023-02-03T00:00:00}, {2024-02-01T00:00:00, 2024-02-21T00:00:00}]`; 6358 `[{2023-02-02T00:00:00, 2024-02-01T00:00:00}, {2024-02-21T00:00:00, 2026-07-31T00:00:00}]`; 9201 `[{2023-02-01T00:00:00, 2027-02-01T00:00:00}]` and 58th `[{2027-02-01T00:00:00, 2027-03-01T09:00:00}]`; 9202 `[]`. At the 2026 clock, 9201's open 57th period ends at `2026-09-27T09:00:00` (AC 9)
Proof: `P tests/test_senado_members.py::test_exercise_periods_are_half_open_and_clipped`
Proof: `P tests/test_senado_members.py::test_open_exercise_closes_at_build_time`

**C10** - 9201's `Mandato`, `Exercicio` and `Suplente`, each a single object in the source, read as one-item lists: the build exits `0` and 9201 has the periods of C9; `readers.as_list` returns `[x]` for an object `x`, the list itself for a list, and `[]` for a missing key (AC 10)
Proof: `P tests/test_senado_members.py::test_single_objects_read_as_lists`

**C11** - 9201's member `name`, `party`, `uf` are `Nove Dois Zero Um`, `PSB`, `SP` from M3 (its latest record); its 57th mandate `party` is `PT` (M2, latest inside the 57th) and its 58th `PSB`; 4605 (no vote) takes `Flávio Dino`, `PSB` from `NomeParlamentar`, `SiglaPartidoParlamentar` and `uf` `MA` from its mandate's `UfParlamentar` (AC 11)
Proof: `P tests/test_senado_members.py::test_member_fields_from_latest_vote_or_list`

**C12** - Every member's `photoUrl` is `https://www.senado.leg.br/senadores/img/fotos-oficiais/senador<id>.jpg` and `sourceUrl` is `https://www25.senado.leg.br/web/senadores/senador/-/perfil/<id>` (AC 12)
Proof: `P tests/test_senado_members.py::test_member_urls`

**C13** - No key equal to `cpf` (any case) or to `NomeCompletoParlamentar`, `SexoParlamentar`, `sexoParlamentar`, `EmailParlamentar`, `Telefones`, `DataNascimento`, `Naturalidade`, `EnderecoParlamentar` (any case) occurs in any file of the members build, and none of the fixture's sentinel values for those fields (`SENTINEL-NOME-COMPLETO`, `SENTINEL-SEXO`, `sentinel@example.invalid`, `SENTINEL-TELEFONE`, `1900-01-01`, `SENTINEL-NATURALIDADE`, `SENTINEL-ENDERECO`) occurs in any output byte; `readers.SENADO_ALLOWLIST` holds none of those keys (AC 13, door 1)
Proof: `P tests/test_senado_members.py::test_no_personal_field_reaches_output`

**C14** - Without `--quiet`, the members build prints on stderr exactly one mismatch line, for the 57th, holding `senado 57` and `1` (9202's M1 record outside every period); the indicators build prints one line for the 57th holding `2` (Beto's V5 record outside his period, Dani's missing record in V3 inside hers) (AC 23)
Proof: `P tests/test_senado_members.py::test_mismatch_warning_per_legislature`

### S3 - Roll calls, votes and classification · 5 files · 50 KB · ~13k

**C15** - In the recorded dataset, `roll-calls.json` holds exactly the ids 6755, 6709, 6757, 6679, 6748, 6680, 6704, 6921, 7017, 7018, 7046, 9999 and not 7045; `dedupe_twins` drops each `sequencialVotacao: null` record that shares `codigoSessao` and the (`codigoParlamentar`, `siglaVotoParlamentar`) set with a record whose `sequencialVotacao` is set, keeps 6704 (null, no twin), keeps both when the null record's vote set differs in one entry, and keeps both when both carry a `sequencialVotacao` (AC 14, AC 15, door 4)
Proof: `P tests/test_senado_roll_calls.py::test_recorded_roll_calls_once`
Proof: `P tests/test_senado_roll_calls.py -k test_dedupe_twins`

**C16** - A record dated `2023-01-31` served in the 2023 file is not written; every written roll call has `house: "senado"`, `organ: "PLEN"`, `legislature: 57` (AC 14)
Proof: `P tests/test_senado_roll_calls.py::test_only_records_inside_a_legislature`

**C17** - `ballot` is `secret` for 6748 and 7018 (`votacaoSecreta: "S"`) and `nominal` for the other ten; no Senate roll call has `ballot: "symbolic"`; a `votacaoSecreta` of `"X"` exits `1` naming the roll call (AC 16)
Proof: `P tests/test_senado_roll_calls.py::test_ballot_from_votacao_secreta`

**C18** - The 11 official examples, recorded verbatim, classify as: 6755 procedural `senado.01`, 7046 procedural `senado.02`, 6921 procedural `senado.03`, 6709 amendment `senado.04`, 6757 amendment `senado.04`, 6679 final `senado.05`, 7017 final `senado.05`, 6748 final `senado.06`, 7018 final `senado.06`, 6680 final `senado.07`, 6704 final `senado.07`; and the recorded build writes the same `kind` and `kindRule` for each (AC 17, door 2)
Proof: `P tests/test_senado_roll_calls.py -k test_official_examples`
Proof: `P tests/test_senado_roll_calls.py::test_build_writes_the_official_kinds`

**C19** - `classification-rules.json` is exactly the 7 rules `senado.01`..`senado.07` in that order, each `{id, house: "senado", kind, field: "descricaoVotacao", pattern, description}` with door 2's pattern; `meta.classification` is `{"version": 1}`; each `description` normalised contains its official term (`.01` "requerimento", `.02` "solicita", `.03` "questao de ordem", `.04` "destacad", `.05` "substitutivo", `.06` "mensagem", `.07` "projeto") and none of `importante`, `relevante`, `faltou`, `ranking`; 9999 ("Votação nominal do Parecer nº 9, de 2025.") is `unclassified`/`null` (door 2, contract AC 18, 20, 21)
Proof: `P tests/test_senado_roll_calls.py::test_rules_file_is_the_senate_ruleset`

**C20** - `senate_position_of` maps `Sim` -> `yes`, `Não` -> `no`, `Abstenção` -> `abstention`, `Votou` -> `secret`, `Presidente (art. 51 RISF)` -> `presiding`, and `P-NRV`, `AP`, `MIS`, `LS`, `LP`, `LAP`, `NCom`, `NA` -> `notVoting`, and raises on `XYZ`, `""` and `sim`; `senate_orientation_of` maps `SIM`, `NÃO`, `ABSTENÇÃO`, `OBSTRUÇÃO`, `LIVRE` to `yes`, `no`, `abstention`, `obstruction`, `free` and raises on `Sim` and `TALVEZ` (door 3)
Proof: `P tests/test_senado_roll_calls.py -k test_senate_position_of`
Proof: `P tests/test_senado_roll_calls.py -k test_senate_orientation_of`

**C21** - Built votes keep `official` verbatim (`Sim`, `Votou`, `Presidente (art. 51 RISF)`, `P-NRV`, `AP`, `MIS`, `NCom`, `NA`) except `LS`, `LP`, `LAP`, written `Licença` with `position: "notVoting"`; no output byte holds `"LS"`, `"LP"` or `"LAP"` as an `official` value (door 3, AD-018)
Proof: `P tests/test_senado_roll_calls.py::test_built_votes_keep_official_or_licenca`

**C22** - A vote `XYZ` in V2 or an orientation `TALVEZ` on seq 5002 makes the build exit `1` with the value and `6902` on stderr, and a `data/v3/senado` built before keeps its bytes; an unknown orientation on a sequencial absent from `/votacao` does not stop the build (AC 18)
Proof: `P tests/test_senado_roll_calls.py -k test_unknown_value_stops_the_build`
Proof: `P tests/test_senado_roll_calls.py::test_unjoined_orientation_is_not_mapped`

**C23** - Open tallies are counted from votes: 6755 `{yes: 41, no: 20, others: 0}`, 6704 `{yes: 51, no: 19, others: 1}`, 7046 `{yes: 28, no: 36, others: 0}`; secret tallies are the official totals: 6748 `{yes: 50, no: 4, others: 1}`, 7018 `{yes: 55, no: 1, others: 0}`; in the indicators dataset V4 is `{yes: 40, no: 1, others: 1}` (AC 19)
Proof: `P tests/test_senado_roll_calls.py::test_tallies`

**C24** - Orientations join by `sequencialVotacao`: 6755 has `governmentOrientation: "no"` and 15 orientations, 6679 `yes`, 7017 `yes` with 12 orientations (its 13th entry has `voto: null`), 7046 `null` with 11 orientations and no `Governo` bench, 6748 `null` with 0 orientations, 6704 (`sequencialVotacao: null`) `null` with 0; in the indicators dataset V3 is `free`, V5 `yes` with bench `Republica` written verbatim with `official` `OBSTRUÇÃO`, and V7's `PT` entry with `voto: null` is not written (AC 20)
Proof: `P tests/test_senado_roll_calls.py::test_orientation_join`

**C25** - Roll call 7046 has `date` `2025-12-17`, `sourceUrl` `https://legis.senado.leg.br/dadosabertos/votacao?codigoSessao=526732`, `description` and `openingDescription` `Solicita urgência para o Projeto de Lei nº 2.234, de 2022`, `lastPresentationDescription: null`, `approved: false`, `propositionId: 8761212`; 6679 has `approved: true` (AC 21)
Proof: `P tests/test_senado_roll_calls.py::test_roll_call_fields`

**C26** - Proposition 8761212 has `type` `RQS`, `number` 857, `year` 2024, `sourceUrl` `https://www25.senado.leg.br/web/atividade/materias/-/materia/166370`; every proposition `sourceUrl` is `https://www25.senado.leg.br/web/atividade/materias/-/materia/<codigoMateria>` (AC 22)
Proof: `P tests/test_senado_roll_calls.py::test_proposition_source_url`

### S4 - Indicators per mandate · 3 files · 35 KB · ~9k

**C27** - `participation` in the indicators dataset: Ana all `5/7` merit `4/5`; Beto all `4/4` merit `3/3`; Caio all `6/7` merit `5/5`; Dani all `3/7` merit `2/5`; Eva all `6/7` merit `5/5` (AC 24, AC 26)
Proof: `P tests/test_senado_indicators.py::test_participation`

**C28** - `governmentAlignment`: Ana all `1/2` merit `0/1`; Beto all `0/2` merit `0/1`; Caio all `2/3` merit `1/2`; Dani all `1/2` merit `0/1`; Eva all `2/3` merit `1/2` (AC 24)
Proof: `P tests/test_senado_indicators.py::test_government_alignment`

**C29** - `partyAlignment`: Ana all `1/3` merit `1/2`; Beto all `1/3` merit `1/2`; Caio `0/0` both (alone in PL); Dani and Eva `0/0` both, and every vote of Dani and Eva has `partyMajority: null` although both are `S/Partido` and voted `Sim` together in V1 and V2 (AC 24, AC 25)
Proof: `P tests/test_senado_indicators.py::test_party_alignment`

**C30** - Ana's 57th mandate has `authoredCount` 6 (PL 10/2025, PEC 2/2025, PL 20/2025, PLP 3/2025, PDL 4/2025, PRS 5/2025), `firstSignerCount` 5 (all but PEC 2/2025, whose detail lists 9003 at `ordem: 1`), `requirementsCount` 3 (`RQS 100/2025`, `REQ 6/2025 - CDH`, `INS 1/2025`); `PL 2434/2019 (Substitutivo-CD)` by "Câmara dos Deputados", `PL 30/2022` presented `2022-12-01`, `R.S 1/2025`, `VET 1/2025` and `PL 40/2025` by "Líder do Bloco ..." count nowhere; Caio has `1/1/0`, Beto `1/0/0` (AC 27, AC 28, AC 29)
Proof: `P tests/test_senado_indicators.py::test_proposition_counts`

**C31** - `/processo/{id}` is requested only for the multi-author authored processes PEC 2/2025, PL 20/2025 and PLP 3/2025 ("... e outros."), once each although PEC 2/2025 is in two senators' lists, and never for a single-author one; PEC 2/2025 lists authors `[{9001, false}, {9003, true}]` (AC 28)
Proof: `P tests/test_senado_indicators.py::test_detail_only_for_multi_author`

**C32** - Every Senate mandate has `symbolicMerit: null` and every `meta.coverage` row has `rollCalls.symbolic: null` (AC 30, contract door 9)
Proof: `P tests/test_senado_indicators.py::test_symbolic_is_null`

**C33** - A record dated `2023-01-31` (56th legislature) in a 2023 file served with `--years 2023 2025`, in which Ana, Beto and Caio vote `Sim`, changes no member's `mandates`: indicators use only roll calls of the mandate's legislature (AC 24)
Proof: `P tests/test_senado_indicators.py::test_indicators_use_only_the_legislature`

### S5 - Contract output · 4 files · 40 KB · ~10k

**C34** - Every file of the three dataset builds passes its `etl/schema/v3/` schema in the in-package validator and in `jsonschema` Draft 2020-12, and `mandato-etl validate <out>` exits `0` (AC 31)
Proof: `P tests/test_senado_contract.py::test_every_file_passes_its_schema`

**C35** - A vote record with `siglaUFParlamentar` `São Paulo` makes the build exit `1` naming `members.json` and `uf`, and a `data/v3/senado` built before keeps its bytes (AC 32)
Proof: `P tests/test_senado_contract.py::test_schema_failure_keeps_previous_output`

**C36** - `meta.json` of the members build holds exactly `schema_version: 3`, `house: "senado"`, `generatedAt: "2027-03-01T12:00:00Z"`, `legislatures` 57 and 58 with `sourceUrl` `https://legis.senado.leg.br/dadosabertos/plenario/legislatura/20230201` and `.../20270201`, `coverage` rows 57 and 58, `classification: {"version": 1}`, and `sources` equal to the manifest entries of every Senate file read, sorted by `file`; the indicators build's coverage row is `{legislature: 57, through: "2025-03-16", rollCalls: {nominal: 6, secret: 1, symbolic: null}, unclassified: 0, members: 5}` (AC 33)
Proof: `P tests/test_senado_contract.py::test_meta_shape`

**C37** - Two builds on the same raw files and clock produce byte-identical files; members are ordered by accent-stripped name then `id`, roll calls by `date` descending then `id`, votes by `memberId` (AC 34)
Proof: `P tests/test_senado_contract.py::test_build_is_deterministic`

**C38** - Without `--quiet`, the recorded build prints `senado 57: 12 roll calls (10 nominal, 2 secret, 0 symbolic), 1 unclassified` and the indicators build `senado 57: 7 roll calls (6 nominal, 1 secret, 0 symbolic), 0 unclassified`; with `--quiet` neither line is printed (AC 35)
Proof: `P tests/test_senado_contract.py::test_log_line_per_legislature`

**C39** - The Senate output holds exactly `meta.json`, `members.json`, `roll-calls.json`, `propositions.json`, `classification-rules.json` and one `roll-calls/<id>.json` per roll call, and no `full-texts/` (contract door 1, plan out of scope)
Proof: `P tests/test_senado_contract.py::test_layout`

**C40** - Every member, roll call, proposition and rule carries `house: "senado"`; no two members, roll calls or propositions share `(house, id)`; every roll-call `id` is decimal digits (door 4, contract door 3)
Proof: `P tests/test_senado_contract.py::test_house_and_identity`

**C41** - A Câmara v3 build after this feature still passes every contract-v3 proof: the whole contract-v3 suite is green (contract door 1 shared by both houses)
Proof: `P tests/test_v3_contract.py tests/test_v3_indicators.py tests/test_v3_classify.py tests/test_v3_legislature.py tests/test_v3_cli.py tests/test_v3_full_texts.py tests/test_v3_senado.py tests/test_v2_frozen.py`

**C42** - The feature diff touches no file under `site/`, and not `.github/workflows/publish.yml`; `etl/tests/fixtures/v2-golden.json` is unchanged (boundary; v2 frozen)
Proof: `test -z "$(git diff --name-only 6a0d768..HEAD -- site .github/workflows/publish.yml etl/tests/fixtures/v2-golden.json)"`

**C43** - The Senate allowlist keeps vote `nomeParlamentar` (door 5) and lives in `readers.SENADO_ALLOWLIST` beside the CSV table (door 6): `read_senado("senado-votacao", path)` returns records whose keys are exactly door 1's roll-call fields plus `votos`, and whose `votos` keys are exactly `codigoParlamentar`, `nomeParlamentar`, `siglaPartidoParlamentar`, `siglaUFParlamentar`, `siglaVotoParlamentar` (door 1, door 5, door 6)
Proof: `P tests/test_senado_sources.py::test_allowlist_projection`

**C44** - `AGENTS.md` declares `A feature \`etl-senado\` roda em \`standard\`` under `## tlc-spec-lean`
Proof: `grep -q 'A feature `etl-senado` roda em `standard`' AGENTS.md`

**C45** - `mandato-etl validate` on a v3 directory exits `1` when a roll call's vote or a proposition's author names a `memberId` absent from that directory's `members.json`, printing the file, the roll call or proposition id and the `memberId`; the Senate fixture, with its five voters in `members.json`, exits `0` (app-contract-v3 AC 10, which the app's importer enforces)
Proof: `P tests/test_v3_senado.py::test_validate_refuses_a_member_id_missing_from_members`
Proof: `P tests/test_v3_senado.py::test_senate_fixture_validates`

**C46** - A Senate build whose vote records name a `codigoParlamentar` listed in no legislature list exits `1` naming the roll call and the member id, and writes no `data/v3/senado`; a voter is never published without a member (fail closed, so C45 holds for every real build)
Proof: `P tests/test_senado_contract.py::test_voter_absent_from_every_legislature_list_fails_the_build`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| door 3 vote codes (13) | `Sim` C20, C21 · `Não` C20 · `Abstenção` C20 · `Votou` C20, C21 · `Presidente (art. 51 RISF)` C20, C21 · `P-NRV` C20, C21 · `AP` C20, C21 · `MIS` C20, C21 · `LS` C20, C21 · `LP` C20, C21 · `LAP` C20, C21 · `NCom` C20, C21 · `NA` C20, C21 | - |
| vote values outside door 3 (3) | `XYZ` C20, C22 · empty C20 · wrong case `sim` C20 | - |
| door 3 orientation values (7) | `SIM` C20, C24 · `NÃO` C20, C24 · `ABSTENÇÃO` C20 · `OBSTRUÇÃO` C20, C24 · `LIVRE` C20, C24 · `null` C24 · unknown C20, C22 | - |
| `governmentOrientation` cases (4) | bench `Governo` C24 · orientations without `Governo` C24 · no feed entry C24 · `sequencialVotacao: null` C24 | - |
| Senate ruleset v1 rules (7) | `senado.01` C18 · `senado.02` C18 · `senado.03` C18 · `senado.04` C18 · `senado.05` C18 · `senado.06` C18 · `senado.07` C18 | - |
| rule outcomes (2) | first match C18, C19 · no match (`unclassified`) C19, C38 | - |
| twin rule cases (4) | null twin with same session and vote set dropped C15 · null without twin kept C15 · null with a differing vote set kept C15 · both sequenced kept C15 | - |
| ballot values (3) | `secret` C17 · `nominal` C17 · other `votacaoSecreta` C17 | - |
| tally sources (2) | counted (open) C23 · official totals (secret) C23 | - |
| exercise filter cases (6) | exercise intersecting C8 · vote record only C8 · neither C8 · `DataFim` -> next day C9 · open, closed at build time C9 · open, closed at next legislature C9 | - |
| single-object lists (3) | `Mandato` C10 · `Exercicio` C10 · `Suplente` C10 | - |
| member field sources (2) | latest vote record C11 · list fallback C11 | - |
| mismatch kinds (2) | record outside every period C14 · no record inside a period C14 | - |
| participation codes (4) | voted (`Sim` and others) C27 · `secret` C27 · `presiding` C27 · `notVoting` C27 | - |
| indicators per mandate (7) | `participation` C27 · `governmentAlignment` C28 · `partyAlignment` C29 · `symbolicMerit` C32 · `authoredCount` C30 · `firstSignerCount` C30, C31 · `requirementsCount` C30 | - |
| bases (2) | `all` C27, C28, C29 · `merit` C27, C28, C29 | - |
| authorship filter cases (8) | exact `PL`/`PLP`/`PEC`/`PDL`/`PRS` C30 · suffix `(Substitutivo-CD)` C30 · autoria not `Senador` C30 · presented before the legislature C30 · single author C30, C31 · multi-author first C30, C31 · multi-author not first C30, C31 · `e outros.` C31 | - |
| requirement prefixes (3) | `RQS ` C30 · `REQ ` C30 · `INS ` C30 | - |
| `build --house senado` exit codes (3) | `0` C2, C7 · `1` C6, C7, C17, C22, C35 · `2` C3 | - |
| `build --house senado` flags (6) | `--contract` C7 · `--house` C7 · `--out` C7, C2 · `--years` C1 · `--refresh` C2 · `--quiet` C38 | - |
| stderr messages (5) | deprecation C5 · mismatch C14 · summary line C38 · failing URL C3 · failing value or file C6, C22, C35 | - |
| Senate source files (7 kinds) | `votacao` C1, C6 · `orientacao` C1 · `legislatura` C1, C6 · `atual` C1, C5 · `processo` list C1, C4 · `processo/{id}` C4, C31 · manifest C1 | - |
| v3 output file kinds (6) | `meta` C34, C36 · `members` C34 · `roll-calls` C34 · `roll-call` C34 · `propositions` C34, C26 · `classification-rules` C34, C19 | - |
| one-way doors (6) | allowlist C13, C43 · ruleset C18, C19 · position maps C20, C21 · identity C15, C40 · vote name kept C43, C11 · allowlist placement C43 | - |
| entities in `Relations` (10) | Legislature C36 · Member C8 · Mandate C8 · ExercisePeriod C9 · RollCall C15 · Vote C21 · Orientation C24 · Proposition C26 · ClassificationRule C19 · Authorship C30, C31 | - |
| startup config: Senate API base and output root (2 assemblies) | CLI default `data/v3/senado` C7 · test harness `--out` and `API_URL` C1 | - |

- Claims naming an exit code or stderr content: C3, C5-C7, C14, C17, C22, C35, C38, C45, C46 - each proof runs `cli.main` with argv and asserts the return code and captured stderr
- Claims about HTTP behaviour: C1-C5 - each proof crosses a real socket to the local fake server
- Decision tables proven at their own layer and at the CLI: positions (C20 unit, C21 build), orientations (C20 unit, C24 build), rules (C18 unit and build), twins (C15 unit and build), exercise periods (C9 build, C10 unit)

## Test policy

Same rows as contract-v3 (its `## Test policy`), which this feature extends; the repo still answers only
where tests live and how they run.

| Code | Required proofs | Coverage expectation |
| --- | --- | --- |
| Decides, reached across a boundary (CLI, HTTP) | one at the boundary **and** one at its own layer | exit code + stderr at the CLI; one asserted case per row of the decision table at its own layer |
| Decides, not reached across a boundary | one at its own layer | one asserted case per row of the decision table |
| Instrumentation, pass-throughs | none of its own | covered by its consumer's proof |

Evidence:

- `classify.py` additions: vote map over 13 codes plus unknown, orientation map over 5 plus unknown -> decides, reached across the CLI: C20 at its layer, C21, C22 at the CLI
- `sources/senado.py` (new): cache hit/miss, year clamping per legislature, envelope checks, deprecation headers, twin rule (4 cases), single-object lists -> decides, across HTTP: C1-C6, C10, C15
- `contract_v3.py` Senate assembly: mandate existence (3 cases), period conversion (3 cases), member-field source (2), tally source (2), orientation join (4), authorship filters (8), first signer (3) -> decides, own layer through dataset builds: C8-C11, C23, C24, C27-C31
- `cli.py`: `--house senado` guard and dispatch -> decides, at the CLI: C7
- `readers.py` Senate projection: a nested allowlist applied by one function, no conditional deciding the result -> instrumentation, proven by its consumers (C13, C43)
- closest analogue: contract-v3 `tests/test_v3_indicators.py` with `v3data.py`, numbers on paper next to the rows

Cost: 6 new test files, ~50 test functions. Without these rows, the 13-code vote map and the twin rule would be proven only by the one path a fixture build traverses.

## Swept

- validation: C6 (envelopes), C17 (`votacaoSecreta`), C22 (codes), C34, C35 (schemas)
- failure modes: C3, C6, C22, C35 (each exits non-zero and keeps the previous output)
- idempotency: C2, C37 (cache reuse, byte-identical rebuild)
- authorization: n/a - no route, no user; the ETL reads public data and writes local files
- concurrency: C4 - at most 4 requests in flight; the manifest is written once from the main thread
- data lifecycle: C13 (personal fields never published), C21 (sensitive codes generalised, AD-018), C42 (v2 untouched)
- dependency failure: C3 (download failure), C5 (deprecated service), C22 (a new code stops the build)
- state transitions: C9 (exercise periods per legislature, open period closed at build time or next legislature)
- observability: C14 (mismatch line), C38 (summary line), C5 (deprecation warning)

## Out of scope

Carried by `plan.md`.

## Handoff

- Size: S1 15k + S2 11k + S3 13k + S4 9k + S5 10k = 58k of new code and tests (from `wc -c`: `cli.py` 11.5 KB, `contract_v3.py` 17.9 KB, `classify.py` 2.8 KB, `readers.py` 2.5 KB, `sources/camara.py` 11 KB read for reuse, `conftest.py` 16 KB for one fake-server addition, plus the new `sources/senado.py`, `rules/senado.json`, `tests/senado_data.py` and six test files estimated at ~95 KB), plus ~45k already spent reading `plan.md` (25 KB), research 07 (24 KB), contract-v3's plan and checks (70 KB) and the ETL = ~105k, under the 150k budget - one builder
- Mechanism: one builder (fits; no ask)
- **Boundary:** C1-C44 closed at `bb2f5e3`; every named proof passes and `uv run --directory etl pytest` gives 329 passed. Off-suite evidence on the real `/votacao` 2023-2026, orientation 2023-2026, 57th list and current list recorded on 2026-10-02 (authorship lists stubbed empty, clock pinned at `2026-10-02T23:00:00Z`): exit 0, `validate` exit 0, 106 members (research 07: the 106 who sat in the 57th), 417 roll calls (423 records minus the 6 twins) with 177 nominal, 240 secret, 0 unclassified, 134 open roll calls with a `Governo` orientation, 71 vote records disagreeing with the exercise periods, and no `LS`, `LP` or `LAP` published
- **Settled mid-build:** nobody answered questions (orchestrator delegation); decided by the builder and reviewable in the diff: (1) contract-v3's `test_bad_contract_or_house_exits_1[house-senado]` (its C4) expected `--house senado` to fail, which contract-v3's plan said would last only until this feature; the case now uses `--house presidencia` with the same assertions. (2) Doors 5 and 6 were added before the code (vote `nomeParlamentar` for AC 11; the Senate allowlist in `readers.SENADO_ALLOWLIST`, because etl-camara's test renders every `readers.ALLOWLIST` kind as CSV). (3) `contract_v3.assemble` was split so both houses share `finish` (indicators, ordering, coverage, files); the Câmara output is unchanged and its suite green. (4) The live API cut a chunked response mid-body while fixtures were recorded; `camara._get` now retries `http.client.HTTPException`, and the Senate download also retries a body short of its `Content-Length` (urllib returns it silently); the Câmara bulk download has no such length check yet. (5) Roll calls whose `/votacao` record has no `dataApresentacao` in door 1 get `presentedAt: null` unless the process is in an authorship list; their `authors` is `[]` because first signers are only fetched for authored types. (6) A listed senator with no `SiglaPartidoParlamentar` and no vote gets party `S/Partido`, the Senate's own label. (7) Mismatch warnings and the deprecation warning print even with `--quiet`, as the v2 TSE warning does. (8) Deprecation is read on every hop: a recording `HTTPRedirectHandler` warns for the URL of each `301` that carries the headers, and the final response is checked too, once per URL (research 07 §2); the redirected body still has to pass its envelope check (AC 6). (9) Requirements follow AC 29 literally (prefix only, no `autoria` filter), so a `RQS` signed by a bloc leader that lists the senator counts for them
- **Abandoned:** recording every senator's authorship list for the off-suite run (about 100 calls, over the 1 request per second budget for live calls); a Senate-only assembler (the plan forbids it; `finish` is shared instead)
- **Verification round 1 fix:** deprecation sent as a `301` is now warned about (C5, `sources/senado.py` `_DeprecationWarner`, `camara._get(opener=)`); proof `test_deprecation_sent_as_a_redirect_warns_and_continues` failed before the fix (0 warning lines) and passes after
- **Verification round 1 fix (member ids):** `validate` now checks every v3 vote and authorship `memberId` against `members.json` (C45); fixture roll call `6923` had voters 9103-9105 without members, now added. A real build could emit such a voter when the Senate's legislature list omits one who voted (`senate_mandates` only builds mandates for listed senators), so `assemble_senado` fails closed with a `ContractError` (C46); authorship cannot dangle because authors come only from members' own processes. Test datasets with empty lists now get listed voters from `senado_data.with_voters`; if the live build trips C46, the source list is incomplete and the fix is a decision about what to publish for that voter
