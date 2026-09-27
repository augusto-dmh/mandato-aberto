"""S4 - indicators (C18-C24). Expected values computed by hand; see conftest.VOTES."""

import json
from collections import Counter

import pytest
from conftest import load

from mandato_etl import compute


def _indicators(out, name):
    return {d["id"]: d[name] for d in load(out, "deputies.json")}


def test_participation_counts(built):
    assert _indicators(built.out, "participation") == {
        101: {"count": 3, "total": 4},
        102: {"count": 2, "total": 2},
        103: {"count": 0, "total": 0},
    }


def test_government_alignment_counts(built):
    got = _indicators(built.out, "governmentAlignment")
    assert got[101] == {"count": 2, "total": 3}
    assert got[102] == {"count": 1, "total": 1}
    assert got[103] == {"count": 0, "total": 1}


def test_party_alignment_counts(built):
    got = _indicators(built.out, "partyAlignment")
    assert got[101] == {"count": 2, "total": 3}
    assert got[103] == {"count": 0, "total": 0}


@pytest.mark.parametrize(
    ("counts", "own", "expected"),
    [
        ({"Sim": 2, "Não": 1}, "Sim", None),
        ({"Sim": 3, "Não": 1}, "Sim", "Sim"),
        ({"Sim": 1}, "Sim", None),
        ({"Sim": 2, "Não": 2}, "Artigo 17", None),
        ({}, "Sim", None),
    ],
    ids=["tie-after-excluding-own", "clear", "empty-after-excluding-own", "own-not-valid-tie", "nobody"],
)
def test_party_majority_table(counts, own, expected):
    assert compute.party_majority(Counter(counts), own) == expected


def test_authorship_counts(built):
    ana = load(built.out, "deputies/101.json")
    assert sorted(p["type"] for p in ana["authored"]) == ["PDL", "PEC", "PL", "PLP", "PRC"]
    assert sorted(p["id"] for p in ana["authored"]) == [6001, 6002, 6003, 6004, 6005]
    assert {p["id"]: p["firstSigner"] for p in ana["authored"]} == {
        6001: True, 6002: False, 6003: True, 6004: False, 6005: False
    }
    assert (ana["authoredCount"], ana["firstSignerCount"], ana["requirementsCount"]) == (5, 2, 3)
    bruno = load(built.out, "deputies/102.json")
    assert [(p["id"], p["firstSigner"]) for p in bruno["authored"]] == [(6001, False)]
    assert bruno["requirementsCount"] == 0


def test_zero_total_indicator_shape(built):
    carla = load(built.out, "deputies/103.json")
    raw = json.dumps(carla["participation"], sort_keys=True)
    assert raw == '{"count": 0, "total": 0}'
    assert carla["partyAlignment"] == {"count": 0, "total": 0}
    assert compute.indicator(0, 0) == {"count": 0, "total": 0}


def _walk(node, found):
    if isinstance(node, dict):
        found.append(node)
        for value in node.values():
            _walk(value, found)
    elif isinstance(node, list):
        for value in node:
            _walk(value, found)


def test_no_ratio_or_percentage_in_output(built):
    objects = []
    for path in built.out.rglob("*.json"):
        _walk(json.loads(path.read_text()), objects)
    keys = {k for obj in objects for k in obj}
    assert not [k for k in keys if any(word in k.casefold() for word in ("ratio", "percent", "pct"))]
    names = ("participation", "governmentAlignment", "partyAlignment")
    indicators = [obj[name] for obj in objects for name in names if name in obj]
    assert len(indicators) == 3 * 3 * 2  # 3 indicators x 3 deputies x (summary + deputy file)
    assert all(set(i) == {"count", "total"} and i["count"] <= i["total"] for i in indicators)


def _alignment(vote):
    """One deputy voting `vote` against a government orientation and a party colleague both equal to it."""
    rows = {
        "votacoes": [{"id": "7-1", "data": "2023-03-01", "dataHoraRegistro": "2023-03-01T10:00:00",
                      "siglaOrgao": "PLEN", "aprovacao": "1", "descricao": "d"}],
        "votacoesOrientacoes": [{"idVotacao": "7-1", "siglaBancada": "Governo", "orientacao": vote}],
        "votacoesVotos": [
            {"idVotacao": "7-1", "dataHoraVoto": "2023-03-01T10:00:00", "voto": v, "deputado_id": dep,
             "deputado_nome": f"D{dep}", "deputado_siglaPartido": "P", "deputado_siglaUf": "SP",
             "deputado_idLegislatura": "57", "deputado_urlFoto": "https://x/p.jpg"}
            for dep, v in (("1", vote), ("2", vote))
        ],
    }
    records = compute.assemble(compute.load(lambda kind: rows.get(kind, [])), {}, set(), {}, "2026-09-27T09:00:00")
    deputy = next(d for d in records["deputies"] if d["id"] == 1)
    return deputy["governmentAlignment"], deputy["partyAlignment"]


@pytest.mark.parametrize(
    ("vote", "expected"),
    [("Sim", 1), ("Não", 1), ("Abstenção", 1), ("Obstrução", 1), ("Artigo 17", 0), ("", 0)],
    ids=["Sim", "Nao", "Abstencao", "Obstrucao", "Artigo-17", "empty"],
)
def test_valid_vote_set_table(vote, expected):
    government, party = _alignment(vote)
    assert government == {"count": expected, "total": expected}
    assert party == {"count": expected, "total": expected}


@pytest.mark.parametrize(
    ("vote", "government", "party", "participation"),
    [
        ("Sim", (1, 1), (1, 1), (1, 1)),
        ("Não", (0, 1), (0, 1), (1, 1)),
        ("Abstenção", (0, 1), (0, 1), (1, 1)),
        ("Obstrução", (0, 1), (0, 1), (1, 1)),
        ("Artigo 17", (0, 0), (0, 0), (1, 1)),
        ("", (0, 0), (0, 0), (0, 1)),
    ],
    ids=["Sim", "Nao", "Abstencao", "Obstrucao", "Artigo-17", "empty"],
)
def test_vote_value_against_sim_orientation(vote, government, party, participation):
    rows = {
        "votacoes": [{"id": "8-1", "data": "2023-03-01", "dataHoraRegistro": "2023-03-01T10:00:00",
                      "siglaOrgao": "PLEN", "aprovacao": "1", "descricao": "d"}],
        "votacoesOrientacoes": [{"idVotacao": "8-1", "siglaBancada": "Governo", "orientacao": "Sim"}],
        "votacoesVotos": [
            {"idVotacao": "8-1", "dataHoraVoto": "2023-03-01T10:00:00", "voto": v, "deputado_id": dep,
             "deputado_nome": f"D{dep}", "deputado_siglaPartido": "P", "deputado_siglaUf": "SP",
             "deputado_idLegislatura": "57", "deputado_urlFoto": "https://x/p.jpg"}
            for dep, v in (("1", vote), ("2", "Sim"))
        ],
    }
    history = {"1": [{"dataHora": "2023-02-01T12:05", "situacao": "Exercício"}]}
    records = compute.assemble(compute.load(lambda kind: rows.get(kind, [])), history, set(), {}, "2026-09-27T09:00:00")
    deputy = next(d for d in records["deputies"] if d["id"] == 1)
    as_pair = lambda i: (i["count"], i["total"])
    assert as_pair(deputy["governmentAlignment"]) == government
    assert as_pair(deputy["partyAlignment"]) == party
    assert as_pair(deputy["participation"]) == participation
