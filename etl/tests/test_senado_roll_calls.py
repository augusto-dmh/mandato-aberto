"""etl-senado S3 - roll calls, votes and classification (C15-C26).

The recorded dataset serves the real `/votacao` records named by the checks; values below were read
from those responses on 2026-10-02.
"""

import copy
import re

import pytest
import senado_data as sd

from mandato_etl import classify
from mandato_etl.sources import senado

RECORDED_IDS = ["6755", "6709", "6757", "6679", "6748", "6680", "6704", "6921", "7017", "7018", "7046", "9999"]
OFFICIAL_EXAMPLES = {
    6755: ("procedural", "senado.01"), 7046: ("procedural", "senado.02"), 6921: ("procedural", "senado.03"),
    6709: ("amendment", "senado.04"), 6757: ("amendment", "senado.04"), 6679: ("final", "senado.05"),
    7017: ("final", "senado.05"), 6748: ("final", "senado.06"), 7018: ("final", "senado.06"),
    6680: ("final", "senado.07"), 6704: ("final", "senado.07"),
}
DOOR_2 = [
    ("senado.01", "procedural", r"^\(?votacao nominal (?:do |de )?(?:requerimento|rqs)\b", "requerimento"),
    ("senado.02", "procedural", r"^(?:solicita|requer)\b", "solicita"),
    ("senado.03", "procedural", r"\bquestao de ordem\b", "questao de ordem"),
    ("senado.04", "amendment", r"\bdestacad[oa]s?\b", "destacad"),
    ("senado.05", "final", r"\bemenda (?:n[oº] ?)?[\d.]+ ?\(substitutivo\)", "substitutivo"),
    ("senado.06", "final", r"\b(?:mensagem|oficio) n[oº]", "mensagem"),
    ("senado.07", "final",
     r"\b(?:projeto|proposta de emenda|pec|plp|pl|pdl|plv|prs|substitutivo|medida provisoria|mpv)\b", "projeto"),
]


def recorded_records() -> dict[int, dict]:
    return {r["codigoSessaoVotacao"]: r for name in ("votacao-2023.json", "votacao-2025.json")
            for r in sd.recorded(name)}


@pytest.fixture
def built_recorded(sen):
    sd.serve(sen, sd.recorded_dataset())
    assert sd.build(sen, "--quiet", years=("2023", "2025")) == 0
    return sen


@pytest.fixture
def built_indicators(sen):
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet") == 0
    return sen


def doc(fake, rc):
    return sd.load(fake, f"roll-calls/{rc}.json")


def test_recorded_roll_calls_once(built_recorded):
    ids = [r["id"] for r in sd.load(built_recorded, "roll-calls.json")]
    assert sorted(ids) == sorted(RECORDED_IDS)
    assert "7045" not in ids
    assert not (sd.out(built_recorded) / "roll-calls" / "7045.json").exists()


def record(rc, session, seq, votes):
    return {"codigoSessaoVotacao": rc, "codigoSessao": session, "sequencialVotacao": seq,
            "votos": [{"codigoParlamentar": code, "siglaVotoParlamentar": value} for code, value in votes]}


def test_dedupe_twins_on_the_recorded_pair():
    records = recorded_records()
    kept = senado.dedupe_twins([records[7045], records[7046], records[6704]])
    assert [r["codigoSessaoVotacao"] for r in kept] == [7046, 6704]


def test_dedupe_twins_cases():
    votes = [(1, "Sim"), (2, "Não")]
    sequenced = record(10, 500, 4000, votes)
    twin = record(11, 500, None, votes)
    lone = record(12, 501, None, votes)
    differs = record(13, 500, None, [(1, "Sim"), (2, "Sim")])
    other_session = record(14, 502, None, votes)
    both = record(15, 500, 4001, votes)
    kept = senado.dedupe_twins([sequenced, twin, lone, differs, other_session, both])
    assert [r["codigoSessaoVotacao"] for r in kept] == [10, 12, 13, 14, 15]


def test_only_records_inside_a_legislature(built_recorded):
    calls = sd.load(built_recorded, "roll-calls.json")
    assert str(sd.BEFORE) not in [r["id"] for r in calls]
    for r in calls:
        assert (r["house"], r["organ"], r["legislature"]) == ("senado", "PLEN", 57)


def test_ballot_from_votacao_secreta(built_recorded, capsys):
    ballots = {r["id"]: r["ballot"] for r in sd.load(built_recorded, "roll-calls.json")}
    assert {rc for rc, b in ballots.items() if b == "secret"} == {"6748", "7018"}
    assert {rc for rc, b in ballots.items() if b == "nominal"} == set(RECORDED_IDS) - {"6748", "7018"}
    assert "symbolic" not in ballots.values()
    routes = sd.indicators()
    routes["/votacao?dataInicio=2025-01-01&dataFim=2025-12-31"][0]["votacaoSecreta"] = "X"
    sd.serve(built_recorded, routes)
    assert sd.build(built_recorded, "--quiet", "--refresh") == 1
    err = capsys.readouterr().err
    assert "6901" in err and "'X'" in err


@pytest.mark.parametrize(("rc", "expected"), list(OFFICIAL_EXAMPLES.items()), ids=str)
def test_official_examples(rc, expected):
    rules = classify.load_rules("senado")["rules"]
    assert classify.classify(recorded_records()[rc], rules) == expected


def test_build_writes_the_official_kinds(built_recorded):
    calls = {r["id"]: (r["kind"], r["kindRule"]) for r in sd.load(built_recorded, "roll-calls.json")}
    for rc, expected in OFFICIAL_EXAMPLES.items():
        assert calls[str(rc)] == expected, rc


def test_rules_file_is_the_senate_ruleset(built_recorded):
    rules = sd.load(built_recorded, "classification-rules.json")
    assert [r["id"] for r in rules] == [row[0] for row in DOOR_2]
    for rule, (rule_id, kind, pattern, term) in zip(rules, DOOR_2):
        assert set(rule) == {"id", "house", "kind", "field", "pattern", "description"}
        assert (rule["house"], rule["kind"], rule["field"], rule["pattern"]) == ("senado", kind, "descricaoVotacao",
                                                                                 pattern)
        description = classify.normalise(rule["description"])
        assert term in description, rule_id
        assert not re.search(r"importante|relevante|faltou|ranking", description), rule_id
    assert sd.load(built_recorded, "meta.json")["classification"] == {"version": 1}
    unclassified = doc(built_recorded, sd.UNCLASSIFIED)
    assert (unclassified["kind"], unclassified["kindRule"]) == ("unclassified", None)


VOTE_MAP = {
    "Sim": "yes", "Não": "no", "Abstenção": "abstention", "Votou": "secret", "Presidente (art. 51 RISF)": "presiding",
    "P-NRV": "notVoting", "AP": "notVoting", "MIS": "notVoting", "LS": "notVoting", "LP": "notVoting",
    "LAP": "notVoting", "NCom": "notVoting", "NA": "notVoting",
}
ORIENTATION_MAP = {"SIM": "yes", "NÃO": "no", "ABSTENÇÃO": "abstention", "OBSTRUÇÃO": "obstruction", "LIVRE": "free"}


@pytest.mark.parametrize(("official", "position"), list(VOTE_MAP.items()))
def test_senate_position_of(official, position):
    assert classify.senate_position_of(official) == position


@pytest.mark.parametrize("official", ["XYZ", "", "sim"])
def test_senate_position_of_unknown(official):
    with pytest.raises(classify.UnknownValueError):
        classify.senate_position_of(official)


@pytest.mark.parametrize(("official", "position"), list(ORIENTATION_MAP.items()))
def test_senate_orientation_of(official, position):
    assert classify.senate_orientation_of(official) == position


@pytest.mark.parametrize("official", ["Sim", "TALVEZ"])
def test_senate_orientation_of_unknown(official):
    with pytest.raises(classify.UnknownValueError):
        classify.senate_orientation_of(official)


def test_built_votes_keep_official_or_licenca(built_indicators):
    officials = {}
    for n in range(1, 8):
        for v in doc(built_indicators, sd.V[n])["votes"]:
            officials.setdefault(v["official"], set()).add(v["position"])
    for code in ("Sim", "Votou", "Presidente (art. 51 RISF)", "P-NRV", "AP", "MIS", "NCom", "NA"):
        assert officials[code] == {VOTE_MAP[code]}, code
    assert officials["Licença"] == {"notVoting"}
    assert not {"LS", "LP", "LAP"} & set(officials)
    ana = {n: next(v for v in doc(built_indicators, sd.V[n])["votes"] if v["memberId"] == 9001) for n in (7,)}
    assert ana[7]["official"] == "Licença"
    for path in sd.out(built_indicators).rglob("*.json"):
        text = path.read_text()
        for code in ("LS", "LP", "LAP"):
            assert f'"official":"{code}"' not in text, (path, code)


@pytest.mark.parametrize("where", ["vote", "orientation"])
def test_unknown_value_stops_the_build(sen, capsys, where):
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet") == 0
    before = {p.relative_to(sd.out(sen)).as_posix(): p.read_bytes() for p in sd.out(sen).rglob("*") if p.is_file()}
    if where == "vote":
        votes = copy.deepcopy(sd.INDICATOR_VOTES)
        votes["9001"][1] = "XYZ"
        routes, value = sd.indicators(votes=votes), "XYZ"
    else:
        orientations = copy.deepcopy(sd.ORIENTATIONS)
        orientations[1]["orientacoesLideranca"][0]["voto"] = "TALVEZ"
        routes, value = sd.indicators(orientations=orientations), "TALVEZ"
    sd.serve(sen, routes)
    capsys.readouterr()
    assert sd.build(sen, "--quiet", "--refresh") == 1
    err = capsys.readouterr().err
    assert value in err and "6902" in err
    after = {p.relative_to(sd.out(sen)).as_posix(): p.read_bytes() for p in sd.out(sen).rglob("*") if p.is_file()}
    assert after == before


def test_unjoined_orientation_is_not_mapped(sen):
    orientations = copy.deepcopy(sd.ORIENTATIONS)
    orientations[-1]["orientacoesLideranca"][0]["voto"] = "TALVEZ"  # seq 4999, absent from /votacao
    sd.serve(sen, sd.indicators(orientations=orientations))
    assert sd.build(sen, "--quiet") == 0


def test_tallies(built_recorded, sen):
    tallies = {r["id"]: r["tallies"] for r in sd.load(built_recorded, "roll-calls.json")}
    assert tallies["6755"] == {"yes": 41, "no": 20, "others": 0}
    assert tallies["6704"] == {"yes": 51, "no": 19, "others": 1}
    assert tallies["7046"] == {"yes": 28, "no": 36, "others": 0}
    assert tallies["6748"] == {"yes": 50, "no": 4, "others": 1}
    assert tallies["7018"] == {"yes": 55, "no": 1, "others": 0}
    sen.routes.clear()
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet", "--refresh") == 0  # the 2025 files of the recorded build are cached
    assert doc(sen, sd.V[4])["tallies"] == {"yes": 40, "no": 1, "others": 1}


def test_orientation_join(built_recorded, sen):
    def summary(rc):
        d = doc(built_recorded, rc)
        return d["governmentOrientation"], len(d["orientations"])

    assert summary(6755) == ("no", 15)
    assert summary(6679)[0] == "yes"
    assert summary(7017) == ("yes", 12)
    assert summary(7046) == (None, 11)
    assert "governo" not in {o["bench"].casefold() for o in doc(built_recorded, 7046)["orientations"]}
    assert summary(6748) == (None, 0)
    assert summary(6704) == (None, 0)

    sen.routes.clear()
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet", "--refresh") == 0  # the 2025 files of the recorded build are cached
    assert doc(sen, sd.V[3])["governmentOrientation"] == "free"
    v5 = doc(sen, sd.V[5])
    assert v5["governmentOrientation"] == "yes"
    assert {"bench": "Republica", "official": "OBSTRUÇÃO", "position": "obstruction"} in v5["orientations"]
    assert [o["bench"] for o in doc(sen, sd.V[7])["orientations"]] == ["Governo"]


def test_roll_call_fields(built_recorded):
    d = doc(built_recorded, 7046)
    assert d["date"] == "2025-12-17"
    assert d["sourceUrl"] == "https://legis.senado.leg.br/dadosabertos/votacao?codigoSessao=526732"
    assert d["description"] == d["openingDescription"] == "Solicita urgência para o Projeto de Lei nº 2.234, de 2022"
    assert d["lastPresentationDescription"] is None
    assert d["approved"] is False
    assert d["propositionId"] == 8761212
    assert doc(built_recorded, 6679)["approved"] is True


def test_proposition_source_url(built_recorded):
    props = {p["id"]: p for p in sd.load(built_recorded, "propositions.json")}
    rqs = props[8761212]
    assert (rqs["type"], rqs["number"], rqs["year"]) == ("RQS", 857, 2024)
    assert rqs["sourceUrl"] == "https://www25.senado.leg.br/web/atividade/materias/-/materia/166370"
    materia = {r["idProcesso"]: r["codigoMateria"] for r in recorded_records().values()}
    materia[9_000_001] = 999_001
    for pid, p in props.items():
        assert p["sourceUrl"] == f"https://www25.senado.leg.br/web/atividade/materias/-/materia/{materia[pid]}"
