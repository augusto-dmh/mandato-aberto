"""S3 - roll calls and votes (C13-C17)."""

from conftest import CX, CY, CZ, R0, R1, R2, R3, R4, R5, load

from mandato_etl import compute


def _vote(dep, at, vote):
    return {
        "idVotacao": "9-1", "dataHoraVoto": at, "voto": vote, "deputado_id": dep, "deputado_nome": f"D{dep}",
        "deputado_siglaPartido": "P", "deputado_siglaUf": "SP", "deputado_idLegislatura": "57",
        "deputado_urlFoto": "https://x/p.jpg",
    }


def test_duplicate_vote_keeps_latest():
    sources = {
        "votacoes": [{"id": "9-1", "data": "2023-03-01", "dataHoraRegistro": "2023-03-01T10:00:00",
                      "siglaOrgao": "PLEN", "aprovacao": "1", "descricao": "d"}],
        "votacoesVotos": [
            _vote("1", "2023-03-01T10:00:30", "Sim"),
            _vote("2", "2023-03-01T10:00:05", "Sim"),
            _vote("3", "2023-03-01T10:00:00", "Não"),
            _vote("4", "2023-03-01T10:00:05", "Obstrução"),
            _vote("3", "2023-03-01T10:00:30", "Sim"),
        ],
    }
    data = compute.load(lambda kind: sources.get(kind, []))
    doc = compute.assemble(data, {}, set(), {}, "2026-09-27T09:00:00")["roll_call_docs"]["9-1"]
    assert len(doc["votes"]) == 4
    assert [v["vote"] for v in doc["votes"] if v["deputyId"] == 3] == ["Sim"]
    assert doc["tallies"] == {"yes": 3, "no": 0, "others": 1}


def test_roll_calls_before_legislature_or_without_votes_are_excluded(built):
    ids = {rc["id"] for rc in load(built.out, "roll-calls.json")}
    assert ids == {R1, R2, R3, R4, CX, CY, CZ}
    assert not (built.out / "roll-calls" / f"{R0}.json").exists()
    assert not (built.out / "roll-calls" / f"{R5}.json").exists()


def test_roll_call_record_fields(built):
    r1 = load(built.out, f"roll-calls/{R1}.json")
    assert set(r1) == {"id", "date", "organ", "description", "proposition", "approved", "secret", "tallies",
                       "governmentOrientation", "sourceUrl", "votes"}
    assert r1["date"] == "2023-03-01"
    assert r1["organ"] == "PLEN"
    assert r1["description"] == f"Votação {R1}"
    assert r1["proposition"] == {"id": 5001, "title": "PL 1/2023", "summary": "Dispõe sobre X."}
    assert r1["approved"] is True
    assert r1["tallies"] == {"yes": 2, "no": 1, "others": 0}
    assert r1["votes"] == [
        {"deputyId": 101, "vote": "Sim", "party": "PT"},
        {"deputyId": 102, "vote": "Sim", "party": "PT"},
        {"deputyId": 103, "vote": "Não", "party": "NOVO"},
    ]
    r2 = load(built.out, f"roll-calls/{R2}.json")
    assert r2["approved"] is False
    assert r2["proposition"] == {"id": 5003, "title": "PEC 3/2024", "summary": None}
    r3 = load(built.out, f"roll-calls/{R3}.json")
    assert r3["approved"] is None
    assert r3["proposition"] is None
    assert r3["tallies"] == {"yes": 0, "no": 1, "others": 0}


def test_deputy_votes_entries_and_order(built):
    votes = load(built.out, "deputies/101.json")["votes"]
    assert [v["rollCallId"] for v in votes] == [R4, CY, R3, R2, CZ, CX, R1]
    assert all(set(v) == {"rollCallId", "vote", "party", "partyMajority"} for v in votes)
    by_id = {v["rollCallId"]: v for v in votes}
    assert by_id[R1] == {"rollCallId": R1, "vote": "Sim", "party": "PT", "partyMajority": "Sim"}
    assert by_id[CZ]["partyMajority"] == "Não"
    assert by_id[R2]["partyMajority"] is None
    assert by_id[R3]["vote"] == ""


def test_deputy_votes_order_ties_by_id():
    sources = {
        "votacoes": [{"id": rc, "data": "2023-03-01", "dataHoraRegistro": "2023-03-01T10:00:00", "siglaOrgao": "PLEN",
                      "aprovacao": "1", "descricao": "d"} for rc in ("5-2", "5-1", "5-3")],
        "votacoesVotos": [{**_vote("1", "2023-03-01T10:00:00", "Sim"), "idVotacao": rc} for rc in ("5-3", "5-1", "5-2")],
    }
    data = compute.load(lambda kind: sources.get(kind, []))
    doc = compute.assemble(data, {}, set(), {}, "2026-09-27T09:00:00")["deputy_docs"]["1"]
    assert [v["rollCallId"] for v in doc["votes"]] == ["5-1", "5-2", "5-3"]


def test_government_orientation_matches_governo_casefolded(built):
    orientation = {rc["id"]: rc["governmentOrientation"] for rc in load(built.out, "roll-calls.json")}
    assert orientation[R1] == "Sim"  # bench "GOVERNO"
    assert orientation[R2] == "Não"  # bench "Governo"
    assert orientation[R3] is None  # only a party bench
    assert orientation[CX] is None  # no orientation at all
