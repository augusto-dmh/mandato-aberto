"""secret-ballots S1 - a roll call whose votes the Câmara does not disclose (C1-C6).

The variant `legislature(secret=True)` adds R6: PLEN, empty votes by 101, 102 and 103, official
totals 12/5/2. Expected numbers are the shared legislature's plus R6, computed in `conftest.py`.
"""

import json

import pytest
from conftest import CX, CY, CZ, R1, R2, R3, R4, R6, build, legislature, load, write_tse

from mandato_etl import cli, compute

OPEN = [R1, R2, R3, R4, CX, CZ, CY]


@pytest.fixture
def secret_built(fake):
    fake.serve(legislature(secret=True))
    tse = write_tse(fake.raw.parent / "consulta_cand_2026_BRASIL.csv")
    assert build(fake, "--tse-csv", str(tse)) == 0
    return fake


def _deputies(out):
    return {d["id"]: d for d in load(out, "deputies.json")}


def test_secret_flag_marks_only_the_all_empty_roll_call(secret_built):
    index = {rc["id"]: rc for rc in load(secret_built.out, "roll-calls.json")}
    assert sorted(index) == sorted([*OPEN, R6])
    assert index[R6]["secret"] is True
    assert load(secret_built.out, f"roll-calls/{R6}.json")["secret"] is True
    for rc in OPEN:
        assert index[rc]["secret"] is False, rc
        assert load(secret_built.out, f"roll-calls/{rc}.json")["secret"] is False, rc


@pytest.mark.parametrize(
    ("votes", "expected"),
    [([], False), ([""], True), (["", ""], True), (["", "Sim"], False), (["Artigo 17"], False)],
)
def test_secret_rule_table(votes, expected):
    assert compute.is_secret(votes) is expected


def test_secret_tallies_come_from_official_totals(secret_built):
    index = {rc["id"]: rc for rc in load(secret_built.out, "roll-calls.json")}
    assert index[R6]["tallies"] == {"yes": 12, "no": 5, "others": 2}
    assert load(secret_built.out, f"roll-calls/{R6}.json")["tallies"] == {"yes": 12, "no": 5, "others": 2}
    assert index[R1]["tallies"] == {"yes": 2, "no": 1, "others": 0}
    assert load(secret_built.out, f"roll-calls/{R1}.json")["tallies"] == {"yes": 2, "no": 1, "others": 0}


def test_secret_record_counts_for_participation(secret_built):
    deputies = _deputies(secret_built.out)
    assert deputies[101]["participation"] == {"count": 4, "total": 5}
    assert deputies[102]["participation"] == {"count": 3, "total": 3}
    assert deputies[103]["participation"] == {"count": 0, "total": 0}
    # 101's empty vote in the open R3 still does not count: 4 of 5 is R1 R2 R4 R6, R3 left out.
    votes101 = {v["rollCallId"]: v["vote"] for v in load(secret_built.out, "deputies/101.json")["votes"]}
    assert votes101[R3] == ""
    assert load(secret_built.out, f"roll-calls/{R3}.json")["secret"] is False


def test_secret_record_is_not_a_valid_vote(secret_built):
    deputies = _deputies(secret_built.out)
    expected = {
        101: ({"count": 2, "total": 3}, {"count": 2, "total": 3}),
        102: ({"count": 1, "total": 1}, {"count": 2, "total": 3}),
        103: ({"count": 0, "total": 1}, {"count": 0, "total": 0}),
    }
    for dep, (government, party) in expected.items():
        assert deputies[dep]["governmentAlignment"] == government, dep
        assert deputies[dep]["partyAlignment"] == party, dep
        doc = load(secret_built.out, f"deputies/{dep}.json")
        secret_votes = [v for v in doc["votes"] if v["rollCallId"] == R6]
        assert len(secret_votes) == 1, dep
        assert secret_votes[0]["partyMajority"] is None, dep


def test_validate_requires_a_boolean_secret(secret_built, capsys):
    out = secret_built.out
    assert cli.main(["validate", str(out)]) == 0

    index_path = out / "roll-calls.json"
    original_index = index_path.read_text()
    index = json.loads(original_index)
    del index[0]["secret"]
    index_path.write_text(json.dumps(index))
    assert cli.main(["validate", str(out)]) == 1
    assert "roll-calls.json" in capsys.readouterr().err
    index_path.write_text(original_index)
    assert cli.main(["validate", str(out)]) == 0

    doc_path = out / "roll-calls" / f"{R1}.json"
    doc = json.loads(doc_path.read_text())
    doc["secret"] = "no"
    doc_path.write_text(json.dumps(doc))
    assert cli.main(["validate", str(out)]) == 1
    assert f"roll-calls/{R1}.json" in capsys.readouterr().err


def test_no_record_in_a_secret_roll_call_does_not_count():
    # Two PLEN roll calls, both deputies in exercise throughout: 9-1 secret with a record by 1 only, 9-2 open.
    rows = {
        "votacoes": [
            {"id": rc, "data": "2023-03-01", "dataHoraRegistro": f"2023-03-01T1{i}:00:00", "siglaOrgao": "PLEN",
             "aprovacao": "1", "votosSim": "1", "votosNao": "0", "votosOutros": "0", "descricao": "d"}
            for i, rc in enumerate(("9-1", "9-2"))
        ],
        "votacoesVotos": [
            {"idVotacao": rc, "dataHoraVoto": "2023-03-01T10:00:00", "voto": vote, "deputado_id": dep,
             "deputado_nome": f"D{dep}", "deputado_siglaPartido": "P", "deputado_siglaUf": "SP",
             "deputado_idLegislatura": "57", "deputado_urlFoto": "https://x/p.jpg"}
            for rc, dep, vote in (("9-1", "1", ""), ("9-2", "1", "Sim"), ("9-2", "2", "Sim"))
        ],
    }
    histories = {dep: [{"dataHora": "2023-02-01T00:00", "situacao": "Exercício"}] for dep in ("1", "2")}
    records = compute.assemble(compute.load(lambda kind: rows.get(kind, [])), histories, set(), {}, "2026-09-27T09:00:00")
    assert [rc["secret"] for rc in records["roll_calls"] if rc["id"] == "9-1"] == [True]
    participation = {d["id"]: d["participation"] for d in records["deputies"]}
    assert participation == {1: {"count": 2, "total": 2}, 2: {"count": 1, "total": 2}}


def test_validate_requires_secret_in_both_files(secret_built, capsys):
    out = secret_built.out
    doc_path = out / "roll-calls" / f"{R1}.json"
    original_doc = doc_path.read_text()
    doc = json.loads(original_doc)
    del doc["secret"]
    doc_path.write_text(json.dumps(doc))
    assert cli.main(["validate", str(out)]) == 1
    assert f"roll-calls/{R1}.json" in capsys.readouterr().err
    doc_path.write_text(original_doc)

    index_path = out / "roll-calls.json"
    index = json.loads(index_path.read_text())
    index[0]["secret"] = "no"
    index_path.write_text(json.dumps(index))
    assert cli.main(["validate", str(out)]) == 1
    assert "roll-calls.json" in capsys.readouterr().err
