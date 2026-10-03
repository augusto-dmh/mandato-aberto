"""etl-presidencia S5 - joint-session votes on vetoes (C33-C43)."""

import presidencia_data as pd
import pytest

from mandato_etl import presidency

POSITION_KEYS = ("yes", "no", "abstention", "obstruction", "blank", "presiding")
ZERO = (0, 0, 0, 0, 0, 0)


@pytest.fixture
def r(con):
    data = pd.recorded()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    return con


@pytest.fixture
def h(con):
    data = pd.hand()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    return con


def test_joint_roll_calls(r):
    rows = {j["id"]: j for j in pd.load(r, "joint-roll-calls.json")}
    assert sorted(rows) == sorted(["17.23.001", "49.23.001", "49.23.004", "03.25.004", "29.25.001", "03.26.000"])
    for id, j in rows.items():
        assert j["question"] == "keepVeto"
        assert j["deviceIdentifier"] == id
        assert j["legislature"] == 57
        assert j["method"] == ("painel" if id in ("29.25.001", "03.26.000") else "cedula")
        assert j["result"] == ("kept" if id in ("17.23.001", "49.23.004") else "overridden")
    one = rows["49.23.001"]
    assert (one["actId"], one["date"], one["session"]) == (
        "vet-49-2023", "2024-05-09", "Sessão Conjunta nº 4 de 09/05/2024 às 10:00h")
    assert rows["03.26.000"]["session"] is None


@pytest.mark.parametrize("official, position", [
    ("Sim", "yes"), ("Não", "no"), ("Abstenção", "abstention"), ("Obstrução", "obstruction"), ("Branco", "blank"),
    ("Art. 17", "presiding"),
])
def test_position_map(official, position):
    assert presidency.joint_position(official, "49.23.001") == position


@pytest.mark.parametrize("official", ["Ausente", "", "sim"])
def test_position_map_rejects(official):
    with pytest.raises(presidency.PresidencyError):
        presidency.joint_position(official, "49.23.001")


def test_votes_file(r):
    doc = pd.load(r, "joint-roll-calls/49.23.001.json")
    listed = pd.joint(r, "49.23.001")
    assert {k: v for k, v in doc.items() if k != "votes"} == listed
    votes = doc["votes"]
    assert [v["house"] for v in votes] == ["camara"] * 5 + ["senado"] * 6
    for house in ("camara", "senado"):
        names = [v["name"] for v in votes if v["house"] == house]
        assert names == sorted(names)
    assert all(set(v) == {"house", "memberId", "name", "party", "uf", "official", "position"} for v in votes)
    aj = next(v for v in votes if v["name"] == "aj Albuquerque")
    assert aj == {"house": "camara", "memberId": 7004, "name": "aj Albuquerque", "party": "PP", "uf": "CE",
                  "official": "Não", "position": "no"}


def _set_vote(value):
    def edit(doc):
        doc["ResultadoVetoDispositivoCN"]["Votacao"]["Camara"]["Voto"][0]["TipoVoto"] = value
    return edit


def test_unknown_vote_stops_the_build(con, capsys):
    data = pd.recorded()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    before = pd.snapshot(pd.out(con))
    pd.serve(con, pd.patch(pd.recorded(), "/plenario/resultado/veto/dispositivo/46051", _set_vote("Ausente")),
             houses=False)
    capsys.readouterr()
    assert pd.build4(con, data, "--quiet", "--refresh") == 1
    err = capsys.readouterr().err
    assert "Ausente" in err and "29.25.001" in err
    assert pd.snapshot(pd.out(con)) == before


def test_total_veto_has_no_votes(r, con):
    total = pd.joint(r, "03.26.000")
    assert (total["votesAvailable"], total["tallies"]) == (False, None)
    assert not (pd.out(r) / "joint-roll-calls" / "03.26.000.json").exists()
    assert total["sourceUrl"] == "https://legis.senado.leg.br/siscon/api/portalcn/pdfResultadoNominalDestaque/17969"
    assert pd.load(r, "meta.json")["coverage"]["jointRollCallsWithoutVotes"] == 1
    codes = {"17.23.001": 43265, "49.23.001": 43825, "49.23.004": 43828, "03.25.004": 45468, "29.25.001": 46051}
    for id, codigo in codes.items():
        j = pd.joint(r, id)
        assert j["votesAvailable"] is True
        assert j["sourceUrl"] == f"https://legis.senado.leg.br/dadosabertos/plenario/resultado/veto/dispositivo/{codigo}"
    data = pd.hand()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet", "--refresh") == 0  # the raw cache holds R's Câmara files
    assert pd.joint(con, "91.25.000")["sourceUrl"] == \
        "https://www.congressonacional.leg.br/materias/vetos/-/veto/detalhe/19091"


def _tally(j, house):
    return tuple(j["tallies"][house][k] for k in POSITION_KEYS)


def test_tallies(r, con):
    expected = {
        "17.23.001": ((3, 1, 0, 0, 1, 0), ZERO),
        "49.23.001": ((1, 3, 0, 0, 1, 0), (1, 5, 0, 0, 0, 0)),
        "49.23.004": ((3, 1, 0, 0, 1, 0), ZERO),
        "03.25.004": ((1, 1, 1, 0, 0, 0), (0, 2, 1, 0, 2, 0)),
        "29.25.001": ((1, 3, 0, 0, 0, 0), (0, 5, 1, 0, 0, 1)),
    }
    for id, (camara, senado) in expected.items():
        j = pd.joint(r, id)
        assert set(j["tallies"]["camara"]) == set(POSITION_KEYS)
        assert (_tally(j, "camara"), _tally(j, "senado")) == (camara, senado), id
    data = pd.hand()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet", "--refresh") == 0
    hand = {
        "90.25.001": ((2, 1, 0, 0, 0, 0), (1, 1, 0, 0, 0, 0)),
        "90.25.002": ((1, 2, 0, 0, 0, 0), (2, 0, 0, 0, 0, 0)),
        "90.25.003": ((2, 1, 0, 0, 0, 0), (0, 2, 0, 0, 0, 0)),
    }
    for id, (camara, senado) in hand.items():
        j = pd.joint(con, id)
        assert (_tally(j, "camara"), _tally(j, "senado")) == (camara, senado), id


def test_tallies_untrimmed_device():
    full = pd.recorded_doc("dispositivo-43825-full.json")
    block = {"Dispositivos": {}}
    device = {"Identificador": "49.23.001", "Codigo": "43825", "TipoVotacao": "Cédula", "DataSessao": "2024-05-09"}
    j = presidency._joint("vet-49-2023", "https://example.invalid", block, device, "overridden", {"43825": full})
    assert _tally(j, "camara") == (37, 414, 0, 0, 3, 0)
    assert _tally(j, "senado") == (8, 64, 0, 0, 0, 0)


def members(*rows):
    return {"camara": [pd.member(id, name, "X", uf, periods) for id, name, uf, periods in rows], "senado": []}


def test_resolution_rule():
    whole = pd.WHOLE
    r = presidency.Resolver(members((1, "AJ  Albuquerque", "CE", whole), (2, "Aécio Neves", "MG", whole)), [])
    assert r.resolve("camara", "aj Albuquerque", "CE", "2024-05-09") == (1, 1)
    assert r.resolve("camara", "Aecio Neves", "MG", "2024-05-09") == (2, 1)
    assert r.resolve("camara", "Aécio Neves", "SP", "2024-05-09") == (None, 0)
    assert r.resolve("camara", "Ninguém", "MG", "2024-05-09") == (None, 0)
    twins = presidency.Resolver(members((1, "Ana", "SP", whole), (2, "Ana", "SP", whole)), [])
    assert twins.resolve("camara", "Ana", "SP", "2024-05-09") == (None, 2)
    ends = presidency.Resolver(members((1, "Ana", "SP", [{"start": "2023-02-01T00:00:00",
                                                          "end": "2024-05-09T00:00:00"}])), [])
    assert ends.resolve("camara", "Ana", "SP", "2024-05-09") == (None, 0)
    starts = presidency.Resolver(members((1, "Ana", "SP", [{"start": "2024-05-09T15:00:00",
                                                            "end": "2026-01-01T00:00:00"}])), [])
    assert starts.resolve("camara", "Ana", "SP", "2024-05-09") == (1, 1)


def test_homonyms_resolve_by_exercise(h):
    for id, member in (("90.25.001", 8101), ("90.25.002", 8101), ("90.25.003", 8102)):
        votes = pd.load(h, f"joint-roll-calls/{id}.json")["votes"]
        assert next(v for v in votes if v["name"] == "Fernando Carvalho")["memberId"] == member


ALIASES = [("senado", "Márcio Bitar", "AC", 285), ("senado", "Janaina Carla Farias", "CE", 6351),
           ("senado", "Astr. Marcos Pontes", "SP", 6009), ("senado", "Prof. Dorinha Seabra", "TO", 5386)]


def test_aliases(r, con):
    entries = presidency.load_aliases()
    assert [(a["house"], a["name"], a["uf"], a["memberId"]) for a in entries] == ALIASES
    assert all(set(a) == {"house", "name", "uf", "memberId", "note"} and a["note"].strip() for a in entries)
    listed = {"camara": [], "senado": [pd.member(id, f"Outro nome {id}", "X", uf) for _, _, uf, id in ALIASES]}
    resolver = presidency.Resolver(listed, entries)
    for house, name, uf, id in ALIASES:
        assert resolver.resolve(house, name, uf, "2024-05-09") == (id, 1)
    bitar = next(v for v in pd.load(r, "joint-roll-calls/29.25.001.json")["votes"] if v["name"] == "Márcio Bitar")
    assert (bitar["uf"], bitar["memberId"]) == ("AC", 285)
    data = pd.hand()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet", "--refresh") == 0
    dorinha = [v for id in ("90.25.001", "90.25.002", "90.25.003")
               for v in pd.load(con, f"joint-roll-calls/{id}.json")["votes"] if v["name"] == "Prof. Dorinha Seabra"]
    assert len(dorinha) == 3 and all(v["memberId"] == 5386 for v in dorinha)


def _extra_vote(name, uf):
    def edit(doc):
        doc["ResultadoVetoDispositivoCN"]["Votacao"]["Camara"]["Voto"].append(
            {"NomeParlamentar": name, "PartidoParlamentar": "PL", "UfParlamentar": uf, "TipoVoto": "Sim"})
    return edit


def test_unmatched_fails_closed(con, capsys):
    data = pd.hand()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    assert pd.load(con, "meta.json")["coverage"]["unmatchedVotes"] == {"camara": 0, "senado": 0}
    before = pd.snapshot(pd.out(con))
    spoiled = pd.hand()
    for codigo in ("49001", "49003"):
        pd.patch(spoiled, f"/plenario/resultado/veto/dispositivo/{codigo}", _extra_vote("Fulano de Tal", "SP"))
    pd.serve(con, spoiled, houses=False)
    capsys.readouterr()
    assert pd.build4(con, data, "--quiet", "--refresh") == 1
    lines = [line for line in capsys.readouterr().err.splitlines() if "Fulano de Tal" in line]
    assert len(lines) == 1
    assert all(word in lines[0] for word in ("camara", "SP", "2025-03-10"))
    assert pd.snapshot(pd.out(con)) == before


def test_unmatched_fails_closed_on_two_members(con, capsys):
    data = pd.hand()
    data["houses"]["camara"][0].append(pd.member(7199, "Ana Lima", "PT", "SP"))
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 1
    assert "Ana Lima" in capsys.readouterr().err
    assert not pd.out(con).exists()


def test_unmatched_is_zero_in_recorded_build(r):
    assert pd.load(r, "meta.json")["coverage"]["unmatchedVotes"] == {"camara": 0, "senado": 0}


def _duplicate_vote(doc):
    votes = doc["ResultadoVetoDispositivoCN"]["Votacao"]["Camara"]["Voto"]
    votes.append({**votes[0], "NomeParlamentar": votes[0]["NomeParlamentar"].upper()})


def _duplicate_mp(doc):
    doc.append(dict(doc[0]))


@pytest.mark.parametrize("path, edit, word", [
    ("/plenario/resultado/veto/dispositivo/49001", _duplicate_vote, "90.25.001"),
    ("/processo?sigla=MPV&ano=2025", _duplicate_mp, "mpv-1290-2025"),
], ids=["vote", "act"])
def test_duplicates_stop_the_build(con, capsys, path, edit, word):
    data = pd.patch(pd.hand(), path, edit)
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 1
    assert word in capsys.readouterr().err
    assert not pd.out(con).exists()
