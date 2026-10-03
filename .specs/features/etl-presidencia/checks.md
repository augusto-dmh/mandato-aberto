# etl-presidencia checks

Profile: standard
Plan: `.specs/features/etl-presidencia/plan.md`

60 checks in 7 slices · 7 one-way doors in the plan, none added while deriving checks · 0 open questions that block the build (open question 3 blocks go-live only)

All proofs run from the repository root. `P` below abbreviates `uv run --directory etl pytest`.
The feature base is `91323e8` (the plan's approval). Congress fixtures are served by the existing
`conftest.FakeCamara` with `sources.congresso.API_URL` pointed at it under `/dadosabertos`, and the
Câmara yearly files by the same server; no test reaches a live API. Recorded responses, taken on
2026-10-02 from `https://legis.senado.leg.br/dadosabertos` at under one request per second with the
ETL's `User-Agent`, live in `etl/tests/fixtures/v4/recorded/congresso/`, trimmed to the records a
check names (a trimmed vote list keeps every field of the votes it keeps). The joint-vote and
process responses carry no personal field beyond name, party and UF, so no sentinel replaces a
value; the untrimmed device `43825` is kept whole for C38. The clock is pinned at
`2026-09-27T12:00:00Z` (`2026-09-27T09:00:00` Brasília) unless a check names `2027-03-01T12:00:00Z`.
`congresso.MIN_INTERVAL` is set to 0 by the test fixture except in C10.

Three datasets carry the expected numbers, computed on paper and written next to the rows in
`etl/tests/presidencia_data.py`. The house directories each dataset needs are hand-written v4
documents that pass `etl/schema/v4/` (members, roll calls, empty propositions and rules):

- **recorded** (R, `--years 2023 2025 2026`): Congress responses, trimmed. MP lists 2023 (MPV 1154,
  1155, 1169, 1177, 1181) and 2026 (MPV 1350, `tramitando: "Sim"`, no `siglaTipoDeliberacao` key);
  the 2025 MP list is `[]`. Veto lists 2023 (VET 17, VET 49), 2025 (VET 3, VET 29), 2026 (VET 3).
  Veto results trimmed to devices: VET 17/2023 `17.23.001` (43265, Mantido); VET 49/2023 `49.23.001`
  (43825, Rejeitado) and `49.23.004` (43828, Mantido); VET 3/2025 `03.25.001` (45465, Não Apreciado)
  and `03.25.004` (45468, Rejeitado); VET 29/2025 `29.25.001` (46051, Rejeitado, Painel) and
  `29.25.032` (46085, Prejudicado, `PossuiVotos: "Não"`); VET 3/2026 `03.26.000` (total, no
  `Codigo`, Rejeitado, Painel, 2026-04-30). Device votes trimmed to Câmara `Adriana Ventura`/SP,
  `Afonso Hamm`/RS, `Airton Faleiro`/PA, `aj Albuquerque`/CE, `Alexandre Guimarães`/TO and Senate
  `Alessandro Vieira`/SE, `Ciro Nogueira`/PI, `Confúcio Moura`/RO, `Davi Alcolumbre`/AP, `Dr. Hiran`/RR,
  `Professora Dorinha Seabra`/TO, `Márcio Bitar`/AC, each with its real vote (43828 and 43265 have
  `Senado: null`). Bill processes recorded: PL 3626/2023, PLP 93/2023, PL 6233/2023 (all `SF`, with
  `normaGerada`). Câmara rows (hand, real ids where the API gave them): MPV 1154 `2345493`, 1155
  `2345494`, 1169 `2355224`, 1177 `2367600`, 1181 `2374255` (no row for MPV 1350); Executive bills PL
  3626/2023 `2374400` ("Transformado em Norma Jurídica"), PLP 93/2023 `2357053` (same), PL 6233/2023
  `2416729` ("Aguardando Encaminhamento", hand), PL 1084/2023 `2351177` ("Transformado em Norma
  Jurídica", Senate list `[]`), PL 1/2023 `2345485` (presented `2022-12-30`, "Retirado pelo(a)
  Autor(a)"), and hand PL 9101/2023 `9900101` ("Retirado pelo(a) Autor(a)"), PL 9102/2025 `9900102`
  ("Arquivada"), PEC 9103/2026 `9900103` ("Aguardando Parecer do Relator na Comissão Especial
  (CESP)", Senate list holds only a `casaIdentificadora: "CD"` record); distractors MSC 9201/2023 and
  PLN 9202/2023 (author `Poder Executivo`/`30000`), PL 9203/2023 (a deputy, `10000`) and PL 9204/2023
  (`Poder Executivo` with `codTipoAutor` `40000`). House members: Câmara 7001 Adriana Ventura,
  7002 Afonso Hamm, 7003 Airton Faleiro, 7004 `AJ  Albuquerque` (exercise ends `2025-01-01T00:00:00`),
  7005 Alexandre Guimarães, 7006 Bruna Sem Voto (no vote), 7007 Carlos Antigo (exercise
  `2023-02-01`..`2023-06-01` only); Senate 8001 Alessandro Vieira, 8002 Ciro Nogueira, 8003 Confúcio
  Moura, 8004 Davi Alcolumbre, 8005 Dr. Hiran, 5386 Professora Dorinha Seabra, 285 Marcio Bittar; every
  other period is `2023-02-01T00:00:00`..`2026-09-27T09:00:00` in the 57th. House roll calls: Câmara
  `2345493-41`, `2345493-64` (propositionId 2345493), `2374400-10` (2374400), `9999-1` (9999);
  Senate `6704` (8349431), `7000` (8463489)
- **hand** (H, `--years 2025`): MPV 1290/2025 (`APROVADO_NA_INTEGRA`, no `normaGerada`, Câmara
  `9800001`); VET 90/2025 partial (`Codigo` 19090, materia 190090, published `2025-01-20`) with
  `90.25.001` (49001, Rejeitado, Cédula, `2025-03-10`), `90.25.002` (49002, Mantido, Cédula,
  `2025-03-10`), `90.25.003` (49003, Mantido, Painel, `2025-04-15`); VET 91/2025 total (`Codigo`
  19091, materia 190091, published `2025-02-03`, vetoing PL 9105/2025, device `91.25.000` without
  `Codigo`, Mantido, Painel, `2025-04-15`, no `PdfsResultadoVotacao`); bill PL 9105/2025 (`9800105`,
  presented `2025-01-05`, "Aguardando Sanção", Senate `[]`). Members: Câmara 7101 Ana Lima (Sim on
  all three devices), 7102 Bruno Reis (Não on all three), 7103 Carla Dias (Sim on `.001`, Não on
  `.002`), 7104 Davi Souza (exercise from `2025-04-01T00:00:00`; Sim on `.003`), 7105 Eva Rocha (no
  vote); Senate 8101 Fernando Carvalho/SE (`2023-02-01T00:00:00`..`2025-03-20T00:00:00`; Sim on
  `.001`, `.002`), 8102 Fernando Carvalho/SE (`2025-03-20T00:00:00`..open; Não on `.003`), 5386
  Professora Dorinha Seabra/TO, whose votes are written `Prof. Dorinha Seabra` (Não, Sim, Não)
- **terms** (T, clock `2027-03-01T12:00:00Z`, `--years 2027`): MPV 1500/2027 presented
  `2027-01-04` and MPV 1501/2027 presented `2027-01-05`, both `tramitando: "Sim"`; the 2027 veto
  list has no `Veto`; no Câmara rows; empty house members

## Checks

### S1 - v4 beside v3 · 5 files · 70 KB · ~18k

**C1** - `build --contract 4 --house camara` on the contract-v3 `indicators()` dataset writes a directory whose relative paths equal those of `--contract 3` on the same inputs and clock, and every file is byte-identical except `meta.json`, which differs only in `"schema_version":4` for `"schema_version":3` (AC 1)
Proof: `P tests/test_v4_houses.py::test_camara_v4_equals_v3_but_version`

**C2** - The same holds for `--house senado` on the etl-senado `indicators` dataset (door 1: one version for every directory)
Proof: `P tests/test_v4_houses.py::test_senado_v4_equals_v3_but_version`

**C3** - With no `--out`, `--contract 4 --house camara` writes `<data>/v4/camara/meta.json` and `--contract 4 --house presidencia` writes `<data>/v4/presidencia/meta.json`; the files under `<data>/out/` and `<data>/v3/` built before keep their bytes and mtimes (AC 2, Observable `--out`)
Proof: `P tests/test_v4_houses.py::test_v4_default_out_leaves_out_and_v3_untouched`

**C4** - Each of `--contract 2 --house presidencia`, `--contract 3 --house presidencia`, `--house presidencia` (default contract 2), `--contract 5` and `--contract x` exits `1` with `usage:` on stderr, 0 HTTP requests and no output directory (AC 3)
Proof: `P tests/test_v4_houses.py -k test_bad_contract_or_house_exits_1`

**C5** - `mandato-etl validate` exits `0` on a v4 Câmara directory, a v4 Senate directory and the R presidency directory; exits `1` on a v4 house directory whose `meta.json` gains `"scope": "presidencia"`; exits `1` with `unsupported schema_version 5` on `schema_version: 5`; still exits `0` on a v2 and a v3 directory and `1` on a v3 directory relabelled `schema_version: 2` (AC 4)
Proof: `P tests/test_v4_houses.py::test_validate_picks_the_v4_set_by_scope`

**C6** - The feature diff touches nothing under `site/`, `design/`, `etl/schema/v3/`, no `etl/schema/*.json` file, and not `.github/workflows/publish.yml` (AC 5)
Proof: `test -z "$(git diff --name-only 91323e8..HEAD -- site design .github/workflows/publish.yml etl/schema/v3 ':(glob)etl/schema/*.json')"`

**C7** - `etl/schema/v4/` holds `meta`, `members`, `roll-calls`, `roll-call`, `propositions`, `classification-rules`, `full-text` `.schema.json` whose JSON equals the v3 file except `meta`'s `properties.schema_version.const` = `4`, plus `presidency-meta`, `acts`, `status-rules`, `joint-roll-calls`, `joint-roll-call`, `member-veto-counts` `.schema.json`, and nothing else (door 1)
Proof: `P tests/test_v4_houses.py::test_v4_schema_set`

### S2 - Congress source and raw cache · 3 files · 40 KB · ~10k

**C8** - The first R build with an empty raw cache requests exactly these 23 Congress paths, each once, with `Accept: application/json` and the project `User-Agent`: `/processo?sigla=MPV&ano=2023`, `…&ano=2025`, `…&ano=2026`, `/materia/vetos/2023`, `/2025`, `/2026`, `/plenario/resultado/veto/materia/{158326,161861,166980,169775,172342}`, `/plenario/resultado/veto/dispositivo/{43265,43825,43828,45468,46051}`, `/processo?sigla=PL&numero=3626&ano=2023`, `PLP` 93/2023, `PL` 6233/2023, `PL` 1084/2023, `PL` 9101/2023, `PL` 9102/2025, `PEC` 9103/2026; every response is stored under `raw/congresso/` with one manifest entry (`file`, `sourceUrl`, `sha256`, `bytes`, `downloadedAt`) whose hash and size equal the stored bytes (door 7, Observable `--years`)
Proof: `P tests/test_presidencia_sources.py::test_first_build_requests_each_source_once`

**C9** - A second R build without `--refresh` requests the 18 list, veto and process paths of C8 again and none of the 5 `dispositivo` paths, and writes byte-identical output; with `--refresh` it requests all 23 (door 7, Observable `--refresh`)
Proof: `P tests/test_presidencia_sources.py::test_cache_reuses_only_decided_devices`

**C10** - At the default `MIN_INTERVAL`, three Congress fetches arrive at the server at least 0.45 s apart and never two in flight (door 7: at most 2 requests per second)
Proof: `P tests/test_presidencia_sources.py::test_at_most_two_requests_per_second`

**C11** - `/materia/vetos/2023` answering `503` four times makes the R build exit `2` with that URL on stderr after sleeps `[1, 2, 4]`, leaves no `*.part` under `raw/congresso/`, and a `data/v4/presidencia` built before keeps its bytes; answering a `200` with an empty body, a `200` with `<html>` or a `429` once and then the JSON exits `0` after one sleep of 1 s; a `200` with `<html>` four times exits `2` (AC 45)
Proof: `P tests/test_presidencia_sources.py::test_download_failure_exits_2_and_keeps_output`
Proof: `P tests/test_presidencia_sources.py -k test_bad_body_is_retried`

**C12** - Every URL opened through `urllib.request.urlopen` during the R build starts with the fake server's base, and none contains `planalto.gov.br`, although the recorded veto lists carry `Mensagem.UrlPlanalto` (AC 44)
Proof: `P tests/test_presidencia_sources.py::test_no_planalto_request`

**C13** - The presidency build exits `1` before any Congress request and writes nothing when: `data/v4/camara` is missing (stderr names `camara`); `data/v4/senado/members.json` has `uf` `São Paulo` (stderr names `senado` and `members.json`); `data/v4/camara/meta.json` says `schema_version: 3` (stderr names `camara`) (AC 38, door 6)
Proof: `P tests/test_presidencia_sources.py -k test_house_directories_required`

### S3 - Every act with its status · 3 files · 60 KB · ~15k

**C14** - R `acts.json` ids are exactly `mpv-1154-2023`, `mpv-1155-2023`, `mpv-1169-2023`, `mpv-1177-2023`, `mpv-1181-2023`, `mpv-1350-2026`, `vet-17-2023`, `vet-49-2023`, `vet-3-2025`, `vet-29-2025`, `vet-3-2026`, `pl-3626-2023`, `plp-93-2023`, `pl-6233-2023`, `pl-1084-2023`, `pl-9101-2023`, `pl-9102-2025`, `pec-9103-2026` (18): no `pl-1-2023`, no MSC, PLN, deputy-authored or `codTipoAutor` `40000` proposition (AC 6, 7, 8)
Proof: `P tests/test_presidencia_acts.py::test_one_act_per_mp_veto_and_executive_bill`

**C15** - `kind`/`type` are `provisionalMeasure`/`MPV`, `veto`/`VET`, `bill`/`PL`, `bill`/`PLP` (plp-93-2023), `bill`/`PEC` (pec-9103-2026), with integer `number` and `year`; `vetoScope` is `total` for `vet-3-2026`, `partial` for the other four vetoes and `null` for every non-veto; non-vetoes have `devices: []` (AC 7, door 2)
Proof: `P tests/test_presidencia_acts.py::test_kind_type_and_veto_scope`

**C16** - `pl-1-2023` (presented `2022-12-30`) is not written and R `meta.coverage.excludedBeforeFirstTerm` is `1` (AC 9)
Proof: `P tests/test_presidencia_acts.py::test_before_first_term_is_excluded`

**C17** - `term_of` gives `None` for `2022-12-31`, `2023-2026` for `2023-01-01` and `2027-01-04`, `2027-2030` for `2027-01-05`; in T, `mpv-1500-2027` has `termId` `2023-2026` and `mpv-1501-2027` `2027-2030`, and `meta.terms` is exactly `[{"id": "2023-2026", "start": "2023-01-01", "end": "2027-01-04", "holder": "Luiz Inácio Lula da Silva", "sourceUrl": "https://legis.senado.leg.br/dadosabertos/plenario/resultado/cn/20230101"}, {"id": "2027-2030", "start": "2027-01-05", "end": "2031-01-04", "holder": null, "sourceUrl": null}]`; in R (2026 clock) only the first (AC 10, door 3)
Proof: `P tests/test_presidencia_acts.py::test_term_boundaries`
Proof: `P tests/test_presidencia_acts.py::test_terms_listed_by_build_date`

**C18** - `mp_status`, table-driven over the 9 MP rules: `APROVADO_NA_INTEGRA` -> `approved`/`mpv.01`, `APROVADO_PLV` -> `approvedAmended`/`mpv.02`, `PERDA_EFICACIA` -> `lapsed`/`mpv.03`, `REVOGADO` -> `revoked`/`mpv.04`, `REJEITADO_PLENARIO` -> `rejected`/`mpv.05`, `REJEITADO_PLENARIO_CD` -> `rejected`/`mpv.06`, `INADIMITIDA_URGENCIA` -> `rejected`/`mpv.07`, `IMPUGNADO_PRESIDENCIA` -> `returned`/`mpv.08`, `null` with `tramitando: "Sim"` -> `pending`/`mpv.09`; it raises on `XYZ`, `SEM_EFICACIA`, `aprovado_plv` and `null` with `tramitando: "Não"` (AC 11, 12, door 4)
Proof: `P tests/test_presidencia_acts.py -k test_mp_status_rules`

**C19** - `device_status`: `Mantido` -> `kept`/`device.01`, `Rejeitado` -> `overridden`/`device.02`, `Prejudicado` -> `prejudged`/`device.03`, `Não Apreciado` -> `pending`/`device.04`; it raises on `Sobrestado`, `""` and `mantido` (AC 11, 12, door 4)
Proof: `P tests/test_presidencia_acts.py -k test_device_status_rules`

**C20** - In R: `mpv-1154-2023` `approvedAmended`/`mpv.02`/`officialStatus` `APROVADO_PLV`; `mpv-1155-2023` and `mpv-1169-2023` `lapsed`/`mpv.03`/`PERDA_EFICACIA`; `mpv-1177-2023` `approved`/`mpv.01`; `mpv-1181-2023` `revoked`/`mpv.04`; `mpv-1350-2026` `pending`/`mpv.09`/`officialStatus: null`; devices `17.23.001` and `49.23.004` `kept`/`device.01`/`Mantido`, `49.23.001`, `03.25.004`, `29.25.001` and `03.26.000` `overridden`/`device.02`/`Rejeitado`, `29.25.032` `prejudged`/`device.03`/`Prejudicado`, `03.25.001` `pending`/`device.04`/`Não Apreciado` (AC 11)
Proof: `P tests/test_presidencia_acts.py::test_recorded_statuses`

**C21** - R with `mpv-1155-2023`'s `siglaTipoDeliberacao` set to `XYZ` exits `1` with `XYZ` and `mpv-1155-2023` on stderr; with device `49.23.004`'s `Situacao` set to `Sobrestado` exits `1` with `Sobrestado` and `vet-49-2023`; in both a `data/v4/presidencia` built before keeps its bytes (AC 12)
Proof: `P tests/test_presidencia_acts.py -k test_unknown_status_stops_the_build`

**C22** - `vet-3-2025` is `pending`/`veto.01` (one device `pending`); `vet-17-2023`, `vet-49-2023`, `vet-29-2025`, `vet-3-2026` are `decided`/`veto.02`; `veto_status` gives `decided` for `[kept, prejudged]`, `pending` for `[overridden, pending]` and for `[pending]` (AC 13)
Proof: `P tests/test_presidencia_acts.py::test_veto_status`

**C23** - `bill_status`, table-driven: `normaGerada` set with Câmara `Arquivada` -> `law`/`bill.01`; no `normaGerada`, Câmara `Transformado em Norma Jurídica` -> `law`/`bill.02`; total veto of it with Câmara `Aguardando Sanção` -> `vetoedTotally`/`bill.03`; total veto with Câmara `Arquivada` -> `vetoedTotally`; `normaGerada` with a total veto -> `law`/`bill.01`; Câmara `Transformado em Norma Jurídica` with a total veto -> `law`/`bill.02`; `Retirado pelo(a) Autor(a)` -> `withdrawn`/`bill.04`; `Arquivada` -> `archived`/`bill.05`; `Aguardando Parecer do Relator na Comissão Especial (CESP)` and `""` -> `inProgress`/`bill.06` (AC 14)
Proof: `P tests/test_presidencia_acts.py -k test_bill_status_precedence`

**C24** - In R: `pl-3626-2023`, `plp-93-2023`, `pl-6233-2023` are `law`/`bill.01` (`pl-6233-2023` with `officialStatus` `Aguardando Encaminhamento`); `pl-1084-2023` `law`/`bill.02`; `pl-9101-2023` `withdrawn`; `pl-9102-2025` `archived`; `pec-9103-2026` `inProgress` with `officialStatus` `Aguardando Parecer do Relator na Comissão Especial (CESP)`; in H `pl-9105-2025` is `vetoedTotally`/`bill.03` (AC 14)
Proof: `P tests/test_presidencia_acts.py::test_recorded_bill_statuses`
Proof: `P tests/test_presidencia_acts.py::test_vetoed_totally`

**C25** - `law` is `Lei nº 14.600 de 19/06/2023` (mpv-1154-2023), `Lei nº 14.696 de 11/10/2023` (mpv-1177-2023), `Lei nº 14.790 de 29/12/2023` (pl-3626-2023 and vet-49-2023), `Lei Complementar nº 200 de 30/08/2023` (plp-93-2023), `Lei nº 14.905 de 28/06/2024` (pl-6233-2023), and `null` for mpv-1155, 1169, 1181, 1350 and pl-1084-2023; R `approvedWithoutLaw` is `0`; H `mpv-1290-2025` (approved, no `normaGerada`) has `law: null` and H `approvedWithoutLaw` is `1` (AC 15)
Proof: `P tests/test_presidencia_acts.py::test_law_and_approved_without_law`

**C26** - `vet-17-2023` has `vetoedMatter` `{"type": "MPV", "number": 1154, "year": 2023}` and `relatedActId` `mpv-1154-2023`; `vet-49-2023` `{"PL", 3626, 2023}` and `pl-3626-2023`; `vet-3-2025` `{"PL", 576, 2021}`, `vet-29-2025` `{"PL", 2159, 2021}` and `vet-3-2026` `{"PL", 2162, 2023}` have `relatedActId: null`; H `vet-91-2025` points at `pl-9105-2025`; every non-veto has `vetoedMatter: null` and `relatedActId: null` (AC 16)
Proof: `P tests/test_presidencia_acts.py::test_related_act`

**C27** - `status-rules.json` is exactly the 21 rules `mpv.01`..`mpv.09`, `device.01`..`device.04`, `veto.01`, `veto.02`, `bill.01`..`bill.06` in that order, each exactly `{id, kind, source, field, officialValue, status, description}`, with the `officialValue`/`status` pairs of C18, C19, C22, C23; `meta.statusRules` is `{"version": 1}`; every rule with a non-null `officialValue` has it verbatim in its `description`; `mpv.09` mentions `tramitando`, `veto.01` `Não Apreciado`, `bill.01` `normaGerada`, `bill.03` `veto total`; no description, accent-stripped and casefolded, contains `derrota`, `vitoria`, `fracasso`, `importante`, `aprovacao do governo` or `ranking` (AC 17)
Proof: `P tests/test_presidencia_acts.py::test_status_rules_file`

**C28** - `mpv-1154-2023` has `issuedAt` `2023-01-01`, `statusAt` `2023-06-01`, `summary` equal to the source `ementa`; `vet-49-2023` has `issuedAt` `2023-12-30` and `statusAt` `2024-05-09`; `vet-3-2025` (pending) has `statusAt: null`; `pl-3626-2023` has `issuedAt` `2023-07-25` and `statusAt` from the Câmara `ultimoStatus_dataHora` date; device `49.23.001` has `description` `§ 1º do art. 31`, `text` starting `Para os efeitos do disposto neste artigo`, `reason` starting `“A manutenção dos §§1º e 3º`, `jointRollCallId` `49.23.001`; `03.25.001` and `29.25.032` have `jointRollCallId: null` (door 2)
Proof: `P tests/test_presidencia_acts.py::test_act_fields`

### S4 - From the act to each house's roll calls · 2 files · 20 KB · ~5k

**C29** - `mpv-1154-2023.stages` is `[{"house": "camara", "propositionId": 2345493}, {"house": "senado", "propositionId": 8349431}]`, `mpv-1177-2023` `[{camara, 2367600}, {senado, 8468604}]`, `mpv-1350-2026` `[{senado, 9034814}]`, and R `missingCamaraStage` is `1` (AC 18, 22)
Proof: `P tests/test_presidencia_stages.py::test_mp_stages`

**C30** - `pl-3626-2023.stages` is `[{camara, 2374400}, {senado, 8542290}]`, `plp-93-2023` `[{camara, 2357053}, {senado, 8463489}]`, `pec-9103-2026` `[{camara, 9900103}]` (its Senate list holds only a `CD` record) and `pl-9101-2023` `[{camara, 9900101}]` (empty list) (AC 19)
Proof: `P tests/test_presidencia_stages.py::test_bill_stages`

**C31** - Every veto has `stages: []`, and the joint roll calls of `vet-49-2023` are exactly the `jointRollCallId`s of its devices, `49.23.001` and `49.23.004` (AC 20)
Proof: `P tests/test_presidencia_stages.py::test_veto_has_no_stage`

**C32** - Joining `stages` to the R house `roll-calls.json` on (`house`, `propositionId`) gives exactly `camara 2345493-41`, `camara 2345493-64`, `senado 6704` for `mpv-1154-2023`, `camara 2374400-10` for `pl-3626-2023` and `senado 7000` for `plp-93-2023` (AC 21)
Proof: `P tests/test_presidencia_stages.py::test_stages_join_house_roll_calls`

### S5 - Joint-session votes on vetoes · 2 files · 45 KB · ~11k

**C33** - R `joint-roll-calls.json` ids are exactly `17.23.001`, `49.23.001`, `49.23.004`, `03.25.004`, `29.25.001`, `03.26.000`; each has `question: "keepVeto"`, `deviceIdentifier` equal to `id`, `legislature: 57`; `method` is `painel` for `29.25.001` and `03.26.000` and `cedula` for the rest; `result` is `kept` for `17.23.001` and `49.23.004` and `overridden` for the rest; `49.23.001` has `actId` `vet-49-2023`, `date` `2024-05-09` and `session` `Sessão Conjunta nº 4 de 09/05/2024 às 10:00h`; `03.26.000` has `session: null` (AC 23)
Proof: `P tests/test_presidencia_joint.py::test_joint_roll_calls`

**C34** - `joint_position` maps `Sim` -> `yes`, `Não` -> `no`, `Abstenção` -> `abstention`, `Obstrução` -> `obstruction`, `Branco` -> `blank`, `Art. 17` -> `presiding`, and raises on `Ausente`, `""` and `sim` (door 5)
Proof: `P tests/test_presidencia_joint.py -k test_position_map`

**C35** - `joint-roll-calls/49.23.001.json` is the C33 record plus `votes`: 5 `camara` then 6 `senado` entries, each sorted by `name`, each with exactly the keys `house`, `memberId`, `name`, `party`, `uf`, `official`, `position`; the `camara` vote of `aj Albuquerque` is `{memberId: 7004, party: "PP", uf: "CE", official: "Não", position: "no"}` (AC 24, 28)
Proof: `P tests/test_presidencia_joint.py::test_votes_file`

**C36** - R with one 46051 vote set to `Ausente` exits `1` with `Ausente` and `29.25.001` on stderr, and a `data/v4/presidencia` built before keeps its bytes (AC 25)
Proof: `P tests/test_presidencia_joint.py::test_unknown_vote_stops_the_build`

**C37** - `03.26.000` has `votesAvailable: false`, `tallies: null`, no `joint-roll-calls/03.26.000.json`, `sourceUrl` `https://legis.senado.leg.br/siscon/api/portalcn/pdfResultadoNominalDestaque/17969`, and R `jointRollCallsWithoutVotes` is `1`; the other five have `votesAvailable: true` and `sourceUrl` `https://legis.senado.leg.br/dadosabertos/plenario/resultado/veto/dispositivo/<Codigo>`; H `91.25.000` (no PDF) has `sourceUrl` `https://www.congressonacional.leg.br/materias/vetos/-/veto/detalhe/19091` (AC 26, 40)
Proof: `P tests/test_presidencia_joint.py::test_total_veto_has_no_votes`

**C38** - R tallies, as `{yes, no, abstention, obstruction, blank, presiding}`: `17.23.001` camara `{3,1,0,0,1,0}` senado all 0; `49.23.001` camara `{1,3,0,0,1,0}` senado `{1,5,0,0,0,0}`; `49.23.004` camara `{3,1,0,0,1,0}` senado all 0; `03.25.004` camara `{1,1,1,0,0,0}` senado `{0,2,1,0,2,0}`; `29.25.001` camara `{1,3,0,0,0,0}` senado `{0,5,1,0,0,1}`; H `90.25.001` camara `{2,1,…}` senado `{1,1,…}`, `90.25.002` camara `{1,2,…}` senado `{2,0,…}`, `90.25.003` camara `{2,1,…}` senado `{0,2,…}`; and the untrimmed 43825 counts camara `{37,414,0,0,3,0}`, senado `{8,64,0,0,0,0}` (AC 27)
Proof: `P tests/test_presidencia_joint.py::test_tallies`
Proof: `P tests/test_presidencia_joint.py::test_tallies_untrimmed_device`

**C39** - `resolve` with members of one house: vote `aj Albuquerque`/CE matches member `AJ  Albuquerque`/CE (case, double space); `Aecio Neves`/MG matches `Aécio Neves`/MG (accent); `Aécio Neves`/SP gives `None` (UF); a name no member has gives `None`; two members with the same name and UF both in exercise give `None`; a period ending `2024-05-09T00:00:00` does not contain session date `2024-05-09`, one starting `2024-05-09T15:00:00` does (AC 28)
Proof: `P tests/test_presidencia_joint.py -k test_resolution_rule`

**C40** - In H the `Fernando Carvalho`/SE votes on `90.25.001` and `90.25.002` (`2025-03-10`) resolve to 8101 and the one on `90.25.003` (`2025-04-15`) to 8102 (AC 29)
Proof: `P tests/test_presidencia_joint.py::test_homonyms_resolve_by_exercise`

**C41** - `etl/inputs/joint-vote-aliases.json` is exactly `[{house: "senado", name: "Márcio Bitar", uf: "AC", memberId: 285}, {senado, "Janaina Carla Farias", "CE", 6351}, {senado, "Astr. Marcos Pontes", "SP", 6009}, {senado, "Prof. Dorinha Seabra", "TO", 5386}]`, each with a non-empty `note`; `resolve` maps each of the four to its id without name matching; in R `Márcio Bitar`/AC on `29.25.001` has `memberId` 285 and in H `Prof. Dorinha Seabra`/TO has 5386 (AC 30)
Proof: `P tests/test_presidencia_joint.py::test_aliases`

**C42** - H with an extra vote `Fulano de Tal`/SP on `90.25.001` and `90.25.003` exits `1`, prints exactly one stderr line holding `camara`, `Fulano de Tal`, `SP` and `2025-03-10`, and a `data/v4/presidencia` built before keeps its bytes; H with a second Câmara member `Ana Lima`/SP in exercise exits `1` naming `Ana Lima`; R and H `meta.coverage.unmatchedVotes` are `{"camara": 0, "senado": 0}` (AC 31)
Proof: `P tests/test_presidencia_joint.py -k test_unmatched_fails_closed`

**C43** - A device listing the same (`house`, normalised name, `uf`) twice exits `1` naming the device identifier, and a list repeating an act id exits `1` naming the id (Relations: `JointVote` and `Act` unique)
Proof: `P tests/test_presidencia_joint.py -k test_duplicates_stop_the_build`

### S6 - Descriptive counts per member and per term · 2 files · 25 KB · ~6k

**C44** - H `member-veto-counts.json` is exactly, as `participation, keepAll, overrideAll, mixed` (`count/total`), all legislature 57: camara 7101 `1/1, 1/1, 0/1, 0/1`; 7102 `1/1, 0/1, 1/1, 0/1`; 7103 `1/1, 0/1, 0/1, 1/1`; 7104 `1/1, 1/1, 0/1, 0/1`; 7105 `0/1, 0/0, 0/0, 0/0`; senado 5386 `1/1, 0/1, 0/1, 1/1`; 8101 `1/1, 1/1, 0/1, 0/1`; 8102 `1/1, 0/1, 1/1, 0/1` (AC 32, door 6)
Proof: `P tests/test_presidencia_counts.py::test_member_counts_hand`

**C45** - R `member-veto-counts.json` is exactly: camara 7001 `4/4, 1/4, 2/4, 1/4`; 7002 `4/4, 1/4, 1/4, 2/4`; 7003 `4/4, 2/4, 1/4, 1/4`; 7004 `2/2, 1/2, 0/2, 1/2`; 7005 `3/4, 0/3, 1/3, 2/3`; 7006 `0/4, 0/0, 0/0, 0/0`; senado 285 `1/4, 0/1, 1/1, 0/1`; 5386 `3/4, 0/3, 2/3, 1/3`; 8001 `3/4, 1/3, 2/3, 0/3`; 8002 `3/4, 0/3, 2/3, 1/3`; 8003 `3/4, 0/3, 2/3, 1/3`; 8004 `2/4, 0/2, 1/2, 1/2`; 8005 `3/4, 0/3, 2/3, 1/3`; no entry for 7007 (AC 32)
Proof: `P tests/test_presidencia_counts.py::test_member_counts_recorded`

**C46** - 7101 voted on three devices of `vet-90-2025` over two sessions and its `participation` is `{"count": 1, "total": 1}` (AC 33); 7103 (`yes` on `.001`, `no` on `.002`) counts the veto in `mixed` only (AC 34)
Proof: `P tests/test_presidencia_counts.py::test_veto_counted_once`
Proof: `P tests/test_presidencia_counts.py::test_split_veto_is_mixed`

**C47** - R `meta.coverage.terms` is exactly `[{"term": "2023-2026", "provisionalMeasure": {"total": 6, "pending": 1, "approved": 1, "approvedAmended": 1, "rejected": 0, "lapsed": 2, "revoked": 1, "returned": 0}, "veto": {"total": 5, "pending": 1, "decided": 4, "devices": {"kept": 2, "overridden": 4, "prejudged": 1, "pending": 1, "total": 8}}, "bill": {"total": 7, "inProgress": 1, "law": 4, "vetoedTotally": 0, "withdrawn": 1, "archived": 1}}]`; in R, H and T every kind's status counts sum to its `total` (AC 35)
Proof: `P tests/test_presidencia_counts.py::test_term_coverage`

**C48** - No key named `ratio`, `percent`, `percentage`, `share` or `rate` occurs in any R, H or T presidency file, and every object holding `count` holds exactly `count` and `total` (AC 36)
Proof: `P tests/test_presidencia_counts.py::test_no_rate_keys`

### S7 - Shape, provenance, privacy and determinism · 2 files · 40 KB · ~10k

**C49** - Every file of the R, H and T builds passes its `etl/schema/v4/` schema in the in-package validator and in `jsonschema` Draft 2020-12, and `mandato-etl validate <out>` exits `0`; R holds exactly `meta.json`, `acts.json`, `status-rules.json`, `joint-roll-calls.json`, `member-veto-counts.json` and `joint-roll-calls/{17.23.001,49.23.001,49.23.004,03.25.004,29.25.001}.json` (AC 37, door 1)
Proof: `P tests/test_presidencia_contract.py::test_every_file_passes_its_schema`
Proof: `P tests/test_presidencia_contract.py::test_layout`

**C50** - R with device `49.23.001`'s `Identificador` set to `49.23.001/A` exits `1` naming `joint-roll-calls.json` and the failing path, and a `data/v4/presidencia` built before keeps its bytes (AC 38)
Proof: `P tests/test_presidencia_contract.py::test_schema_failure_keeps_previous_output`

**C51** - R `meta.json` has exactly the keys `schema_version` (4), `scope` (`presidencia`), `generatedAt` (`2026-09-27T12:00:00Z`), `terms`, `coverage`, `statusRules`, `sources`; `coverage` is exactly `{terms, unmatchedVotes: {camara: 0, senado: 0}, excludedBeforeFirstTerm: 1, missingCamaraStage: 1, approvedWithoutLaw: 0, jointRollCallsWithoutVotes: 1}`; `sources` equals the manifest entries of the 23 Congress files plus `proposicoes-{2023,2025,2026}.csv` and `proposicoesAutores-{2023,2025,2026}.csv`, sorted by `file`; there is no `house` key (AC 39)
Proof: `P tests/test_presidencia_contract.py::test_meta`

**C52** - Every R and H act `sourceUrl` is `https://www.congressonacional.leg.br/materias/medidas-provisorias/-/mpv/<codigoMateria>` for MPs (`…/mpv/155651` for mpv-1154-2023), `https://www.congressonacional.leg.br/materias/vetos/-/veto/detalhe/<Codigo>` for vetoes (`…/16269` for vet-49-2023) and `https://www.camara.leg.br/propostas-legislativas/<id>` for bills (`…/2374400` for pl-3626-2023); every joint roll call's `sourceUrl` starts with `https://` (AC 40)
Proof: `P tests/test_presidencia_contract.py::test_source_urls`

**C53** - No key containing `cpf` in any case occurs in any file under the R output or `raw/congresso/`, and every joint vote has exactly the keys of C35 (AC 41)
Proof: `P tests/test_presidencia_contract.py::test_no_cpf_and_only_allowed_vote_fields`

**C54** - Two R builds on the same raw files and clock produce byte-identical directories; `acts.json` is sorted by `issuedAt` descending then `id` and begins `mpv-1350-2026` (`2026-04-15`), `vet-3-2026` (`2026-01-09`), `pec-9103-2026`; `joint-roll-calls.json` by `date` descending then `id` (`03.26.000`, `29.25.001`, `03.25.004`, `17.23.001`, `49.23.001`, `49.23.004`); `member-veto-counts.json` by `house`, `memberId`, `legislature` (AC 42)
Proof: `P tests/test_presidencia_contract.py::test_build_is_deterministic`

**C55** - Without `--quiet` the R build prints exactly one summary line `presidencia 2023-2026: 6 MPs, 5 vetoes (8 devices), 7 bills, 6 joint roll calls, 0 unmatched votes`, the T build `presidencia 2023-2026: 1 MPs, 0 vetoes (0 devices), 0 bills, 0 joint roll calls, 0 unmatched votes` and `presidencia 2027-2030: 1 MPs, 0 vetoes (0 devices), 0 bills, 0 joint roll calls, 0 unmatched votes`; with `--quiet` none (AC 43, Observable `--quiet`)
Proof: `P tests/test_presidencia_contract.py::test_log_line_per_term`

**C56** - `etl/tests/fixtures/v4/presidencia/` equals a fresh R build byte for byte, and `.github/workflows/ci.yml` runs `uv run mandato-etl validate tests/fixtures/v4/presidencia` (Impact: CI)
Proof: `P tests/test_presidencia_contract.py::test_committed_fixture_is_the_recorded_build`
Proof: `grep -q 'mandato-etl validate tests/fixtures/v4/presidencia' .github/workflows/ci.yml`

**C57** - The v2 and v3 emissions keep every earlier proof: the v2 golden, contract-v3 and etl-senado suites are green (AC 2, door 1)
Proof: `P tests/test_v2_frozen.py tests/test_v3_contract.py tests/test_v3_indicators.py tests/test_v3_classify.py tests/test_v3_legislature.py tests/test_v3_cli.py tests/test_v3_full_texts.py tests/test_v3_senado.py tests/test_senado_sources.py tests/test_senado_members.py tests/test_senado_roll_calls.py tests/test_senado_indicators.py tests/test_senado_contract.py tests/test_senado_cli.py`

**C58** - The presidency build reads the Câmara bills through `readers.read`, whose allowlist adds only `ultimoStatus_dataHora` to `proposicoes` and `codTipoAutor`, `nomeAutor` to `proposicoesAutores` (no `cpf` column anywhere), and a `proposicoesAutores` row's other columns never reach the output (door 2, AD-003)
Proof: `P tests/test_presidencia_acts.py::test_allowlist_additions`

**C59** - `AGENTS.md` declares `A feature \`etl-presidencia\` roda em \`standard\`` under `## tlc-spec-lean`
Proof: `grep -q 'A feature `etl-presidencia` roda em `standard`' AGENTS.md`

**C60** - With the presidency `--out` default, the build reads the house directories from `<data>/v4/camara` and `<data>/v4/senado` (siblings of the output), so an explicit `--out <dir>/presidencia` reads `<dir>/camara` and `<dir>/senado` (Flow hop 2, startup config)
Proof: `P tests/test_presidencia_sources.py::test_house_directories_are_siblings_of_out`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| Act kinds (3) | `provisionalMeasure` C14, C15 · `veto` C14, C15 · `bill` C14, C15 | - |
| Act types (5) | `MPV` C15 · `VET` C15 · `PL` C15 · `PLP` C15 · `PEC` C15 | - |
| MP statuses (7) | `pending` C18, C20 · `approved` C18, C20 · `approvedAmended` C18, C20 · `rejected` C18 · `lapsed` C18, C20 · `revoked` C18, C20 · `returned` C18 | - |
| MP status rules (9) | `mpv.01` C18, C20 · `mpv.02` C18, C20 · `mpv.03` C18, C20 · `mpv.04` C18, C20 · `mpv.05` C18 · `mpv.06` C18 · `mpv.07` C18 · `mpv.08` C18 · `mpv.09` C18, C20 | - |
| MP values outside the rules (4) | `XYZ` C18, C21 · `SEM_EFICACIA` C18 · wrong case C18 · null with `tramitando: "Não"` C18 | - |
| Device statuses and rules (4) | `kept`/`device.01` C19, C20 · `overridden`/`device.02` C19, C20 · `prejudged`/`device.03` C19, C20 · `pending`/`device.04` C19, C20 | - |
| Device values outside the rules (3) | `Sobrestado` C19, C21 · empty C19 · wrong case C19 | - |
| Veto statuses and rules (2) | `pending`/`veto.01` C22 · `decided`/`veto.02` C22 | - |
| Bill statuses (5) | `inProgress` C23, C24 · `law` C23, C24 · `vetoedTotally` C23, C24 · `withdrawn` C23, C24 · `archived` C23, C24 | - |
| Bill status rules (6) | `bill.01` C23, C24 · `bill.02` C23, C24 · `bill.03` C23, C24 · `bill.04` C23, C24 · `bill.05` C23, C24 · `bill.06` C23, C24 | - |
| Bill precedence pairs (4) | law over vetoedTotally C23 · Câmara law over vetoedTotally C23 · vetoedTotally over archived C23 · Senate law over archived C23 | - |
| Bill author filter (4) | `Poder Executivo` + `30000` + PL/PLP/PEC C14 · other type (MSC, PLN) C14 · other author C14 · `codTipoAutor` not `30000` C14 | - |
| Terms (2) | `2023-2026` C17, C47, C55 · `2027-2030` C17, C55 | - |
| Term boundaries (4 edges) | `2022-12-31` C16, C17 · `2023-01-01` C17 · `2027-01-04` C17 · `2027-01-05` C17 | - |
| Term listing by build date (2) | 2026 clock C17 · 2027 clock C17 | - |
| Joint vote positions (6) | `Sim` C34, C38 · `Não` C34, C38 · `Abstenção` C34, C38 · `Obstrução` C34 · `Branco` C34, C38 · `Art. 17` C34, C38 | - |
| Joint vote values outside the map (3) | `Ausente` C34, C36 · empty C34 · wrong case C34 | - |
| Joint result (2) | `kept` C33 · `overridden` C33 | - |
| Joint method (2) | `cedula` C33 · `painel` C33 | - |
| `votesAvailable` (2) | `true` C33, C35, C38 · `false` C37 | - |
| Joint roll call `sourceUrl` forms (3) | device service C37 · first result PDF C37 · veto page C37 | - |
| A house with no published votes on a device (1) | `Senado: null` C38 (zero tallies), C45 (senators' totals) | - |
| Resolution outcomes (6) | exact normalised match C35, C39 · alias C41 · zero matches C39, C42 · several matches C39, C42 · homonyms split by exercise C40 · date on a period edge C39 | - |
| Alias entries (4) | `Márcio Bitar`/AC C41 (unit and R build) · `Janaina Carla Farias`/CE C41 (unit) · `Astr. Marcos Pontes`/SP C41 (unit) · `Prof. Dorinha Seabra`/TO C41 (unit and H build) | - |
| Unmatched case (1) | unmatched name fails closed C42 | - |
| Member count indicators (4) | `participation` C44, C45, C46 · `keepAll` C44, C45 · `overrideAll` C44, C45 · `mixed` C44, C45, C46 | - |
| Member count base cases (4) | in exercise whole time C45 · out of office on one session date C44 (7104), C45 (7004) · in exercise, never voted C44 (7105), C45 (7006) · no session in exercise (no entry) C45 (7007) | - |
| Coverage counters (6) | `terms` C47 · `unmatchedVotes` C42, C51 · `excludedBeforeFirstTerm` C16, C51 · `missingCamaraStage` C29, C51 · `approvedWithoutLaw` C25, C51 · `jointRollCallsWithoutVotes` C37, C51 | - |
| Stage cases (4) | MP both houses C29 · MP without Câmara C29 · bill both houses C30 · bill without Senate (empty list, non-`SF` record) C30 | - |
| Act `sourceUrl` forms (3) | MP C52 · veto C52 · bill C52 | - |
| `build --contract 4` exit codes (3) | `0` C1, C8 · `1` C4, C13, C21, C36, C42, C43, C50 · `2` C11 | - |
| `build` flags (6) | `--contract` C1, C4 · `--house` C1, C2, C4 · `--out` C3, C60 · `--years` C8 · `--refresh` C9 · `--quiet` C55 | - |
| stderr messages (7) | usage C4 · unknown status value C21 · unknown vote C36 · unmatched vote C42 · failing URL C11 · house directory C13 · schema C50 · summary C55 | - |
| Congress source files (6) | `processo-mpv-<year>` C8 · `vetos-<year>` C8, C11 · `veto-<codigo>` C8 · `dispositivo-<codigo>` C8, C9 · `processo-<sigla>-<numero>-<ano>` C8 · manifest C8, C51 | - |
| Retryable responses (4) | `503` C11 · `429` C11 · empty `200` C11 · non-JSON `200` C11 | - |
| v4 schema files (13) | `meta` C7, C1 · `members` C7, C1 · `roll-calls` C7, C1 · `roll-call` C7, C1 · `propositions` C7, C1 · `classification-rules` C7, C1 · `full-text` C7, C1 · `presidency-meta` C49, C51 · `acts` C49 · `status-rules` C49, C27 · `joint-roll-calls` C49, C50 · `joint-roll-call` C49, C35 · `member-veto-counts` C49, C44 | - |
| `validate` schema-set choices (5) | v2 C5 · v3 C5 · v4 house C5 · v4 presidency C5 · unknown version C5 | - |
| one-way doors (7) | 1 contract v4 C1-C7, C49 · 2 act shape C14, C15, C26, C28 · 3 terms C17 · 4 status rules C18-C27 · 5 joint roll calls C33-C38 · 6 resolution and counts C13, C39-C46 · 7 source and cache C8-C12 | - |
| entities in `Relations` (10) | Term C17 · Act C14, C43 · Stage C29, C30 · HouseRollCall C32 · VetoDevice C20, C28 · JointRollCall C33 · JointVote C35, C43 · HouseMember C39, C13 · MemberVetoCount C44 · StatusRule C27 | - |
| startup config: Congress base and output root (2 assemblies) | CLI defaults `data/v4/presidencia`, siblings for the houses C3, C60 · test harness `--out` and `API_URL` C8 | - |

- Claims naming an exit code or stderr content: C4, C5, C11, C13, C21, C36, C42, C43, C50, C55 - each proof runs `cli.main` with argv and asserts the return code and captured stderr
- Claims about HTTP behaviour: C8-C12 - each proof crosses a real socket to the local fake server
- Decision tables proven at their own layer and at the CLI: MP rules (C18 unit, C20 build), device rules (C19 unit, C20 build), veto status (C22 unit and build), bill precedence (C23 unit, C24 build), positions (C34 unit, C35, C38 build), resolution (C39 unit, C40-C42 build), counts (C44-C46 build over hand numbers)

## Test policy

Same rows as contract-v3 and etl-senado, which this feature extends; the repo still answers only
where tests live and how they run.

| Code | Required proofs | Coverage expectation |
| --- | --- | --- |
| Decides, reached across a boundary (CLI, HTTP) | one at the boundary **and** one at its own layer | exit code + stderr at the CLI; one asserted case per row of the decision table at its own layer |
| Decides, not reached across a boundary | one at its own layer | one asserted case per row of the decision table |
| Instrumentation, pass-throughs | none of its own | covered by its consumer's proof |

Evidence:

- `presidency.py` (new): MP rules (9 rows + 4 rejects), device rules (4 + 3), veto status (2), bill precedence (6 rules, 4 precedence pairs), term by date (3 outcomes), stages (4 cases), position map (6 + 3), resolution (6 outcomes), counts (4 indicators, 4 base cases) -> decides, reached across the CLI: C18, C19, C22, C23, C34, C39 at its layer; C20, C24, C35-C46 through builds
- `sources/congresso.py` (new): cache reuse for decided devices, retry on four response kinds, throttle -> decides, across HTTP: C8-C12
- `cli.py`: `--contract`/`--house` guard, default outputs, house-directory gate -> decides, at the CLI: C3, C4, C13, C60
- `schema.py` and `publish.py`: schema-set choice by version and scope (5 cases) -> decides, at the CLI: C5, C49, C50
- `contract_v3.py` house build with the version as a parameter: forwards one value -> instrumentation, proven by its consumer (C1, C2)
- closest analogue: etl-senado `tests/test_senado_indicators.py` with `senado_data.py`, numbers on paper next to the rows

Cost: 7 new test files, ~70 test functions. Without these rows, the 21-rule status table and the
resolution rule would be proven only by the paths the recorded build happens to traverse.

## Swept

- validation: C12-C13 (house directories), C18, C19, C21 (status values), C34, C36 (vote values), C43 (duplicates), C49, C50 (schemas)
- failure modes: C11, C13, C21, C36, C42, C43, C50 (each exits non-zero and keeps the previous output)
- idempotency: C9, C54 (cache reuse, byte-identical rebuild), C56 (committed fixture equals a fresh build)
- authorization: n/a - no route, no user; the ETL reads public data and writes local files
- concurrency: C10 - one Congress request at a time, at least 0.45 s apart; the manifest is written from one thread
- data lifecycle: C53 (no CPF, vote fields limited), C3 and C57 (v2 and v3 untouched), C9 (decided devices cached forever, everything else refetched)
- dependency failure: C11 (download failure exits 2), C13 (house directories missing or invalid), C21, C36 (a new official value stops the build)
- state transitions: C18-C24 (act lifecycle statuses), C17 (an act's term by issue date), C22 (a veto turns `decided` only when no device is `pending`)
- observability: C55 (summary line per term), C42 (unmatched names on stderr), C11 (failing URL)

## Out of scope

Carried by `plan.md`. One go-live item stays outside this build: the plan's term-end assumption asks
for a test pinning `2027-01-05` against a recorded official text "before the first v4 publication";
on 2026-10-02 the reachable official sources returned only metadata (`/dadosabertos/legislacao/34969552`)
or a script-built page (`normas.leg.br`), so C17 pins the constants and the official-text pin waits
for the publication feature.

## Handoff

- Size: S1 18k + S2 10k + S3 15k + S4 5k + S5 11k + S6 6k + S7 10k = 75k of new code and tests (from `wc -c`: `cli.py` 15.5 KB, `schema.py` 5.6 KB, `publish.py` 1.5 KB, `readers.py` 6.7 KB, `contract_v3.py` 32.6 KB read for reuse only, `sources/camara.py` 11 KB and `sources/senado.py` 6.9 KB read for reuse, `conftest.py` 17 KB, plus the new `presidency.py` (~25 KB), `sources/congresso.py` (~6 KB), 13 v4 schemas (~40 KB, 7 copied), `presidencia_data.py` (~25 KB) and 7 test files (~70 KB)), plus ~55k already spent reading `plan.md` (41 KB), research 09 (36 KB), the etl-senado checks and the ETL = ~130k, under the 150k budget - one builder
- Mechanism: one builder (fits; no ask)
