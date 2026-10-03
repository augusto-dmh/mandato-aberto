"""etl-senado S4 - indicators per mandate (C27-C33). Expected numbers are written next to the rows in
`senado_data.INDICATOR_VOTES` and `senado_data.ANA_PROCESSES`."""

import pytest
import senado_data as sd

ANA, BETO, CAIO, DANI, EVA = 9001, 9002, 9003, 9004, 9005


@pytest.fixture
def built(sen):
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet") == 0
    return sen


def indicator(fake, member, name):
    m = sd.mandate(sd.members_by_id(fake)[member])[name]
    return tuple((m[b]["count"], m[b]["total"]) for b in ("all", "merit"))


def test_participation(built):
    assert indicator(built, ANA, "participation") == ((5, 7), (4, 5))
    assert indicator(built, BETO, "participation") == ((4, 4), (3, 3))
    assert indicator(built, CAIO, "participation") == ((6, 7), (5, 5))
    assert indicator(built, DANI, "participation") == ((3, 7), (2, 5))
    assert indicator(built, EVA, "participation") == ((6, 7), (5, 5))


def test_government_alignment(built):
    assert indicator(built, ANA, "governmentAlignment") == ((1, 2), (0, 1))
    assert indicator(built, BETO, "governmentAlignment") == ((0, 2), (0, 1))
    assert indicator(built, CAIO, "governmentAlignment") == ((2, 3), (1, 2))
    assert indicator(built, DANI, "governmentAlignment") == ((1, 2), (0, 1))
    assert indicator(built, EVA, "governmentAlignment") == ((2, 3), (1, 2))


def test_party_alignment(built):
    assert indicator(built, ANA, "partyAlignment") == ((1, 3), (1, 2))
    assert indicator(built, BETO, "partyAlignment") == ((1, 3), (1, 2))
    assert indicator(built, CAIO, "partyAlignment") == ((0, 0), (0, 0))
    assert indicator(built, DANI, "partyAlignment") == ((0, 0), (0, 0))
    assert indicator(built, EVA, "partyAlignment") == ((0, 0), (0, 0))
    for n in (1, 2):
        votes = {v["memberId"]: v for v in sd.load(built, f"roll-calls/{sd.V[n]}.json")["votes"]}
        assert (votes[DANI]["official"], votes[EVA]["official"]) == ("Sim", "Sim")
        assert (votes[DANI]["party"], votes[EVA]["party"]) == ("S/Partido", "S/Partido")
    for n in range(1, 8):
        for v in sd.load(built, f"roll-calls/{sd.V[n]}.json")["votes"]:
            if v["memberId"] in (DANI, EVA):
                assert v["partyMajority"] is None, (n, v)


def counts(fake, member):
    m = sd.mandate(sd.members_by_id(fake)[member])
    return m["authoredCount"], m["firstSignerCount"], m["requirementsCount"]


def test_proposition_counts(built):
    assert counts(built, ANA) == (6, 5, 3)
    assert counts(built, CAIO) == (1, 1, 0)
    assert counts(built, BETO) == (1, 0, 0)
    assert counts(built, DANI) == (0, 0, 0)
    props = {p["id"]: p for p in sd.load(built, "propositions.json")}
    for pid in (8001, 8002, 8003, 8005, 8006, 8007):
        assert ANA in [a["memberId"] for a in props[pid]["authors"]], pid
    for pid in (8004, 8011, 8012, 8013, 8014):
        assert pid not in props, pid
    assert (props[8005]["type"], props[8005]["number"], props[8005]["year"]) == ("PLP", 3, 2025)
    assert props[8001]["presentedAt"] == "2025-02-10"


def test_detail_only_for_multi_author(built):
    details = sorted(p for p in sd.requests(built) if p.startswith("/processo/"))
    assert details == ["/processo/8002", "/processo/8003", "/processo/8005"]
    props = {p["id"]: p for p in sd.load(built, "propositions.json")}
    assert props[8002]["authors"] == [{"memberId": ANA, "firstSigner": False}, {"memberId": CAIO, "firstSigner": True}]


def test_symbolic_is_null(built):
    for member in sd.load(built, "members.json"):
        assert [m["symbolicMerit"] for m in member["mandates"]] == [None] * len(member["mandates"])
    assert [row["rollCalls"]["symbolic"] for row in sd.load(built, "meta.json")["coverage"]] == [None]


def test_indicators_use_only_the_legislature(sen):
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet") == 0
    before = {m["id"]: m["mandates"] for m in sd.load(sen, "members.json")}
    old = sd.roll_call(6800, "2023-01-31", "Votação nominal do Projeto de Lei nº 1, de 2023.", 3000, [
        sd.vote(code, *sd.SENATORS[code], "Sim") for code in ("9001", "9002", "9003")], ano=2023)
    sen.routes.clear()
    sd.serve(sen, sd.indicators(extra_years={("2023-02-01", "2023-12-31"): [old]}))
    assert sd.build(sen, "--quiet", years=("2023", "2025")) == 0
    after = {m["id"]: m["mandates"] for m in sd.load(sen, "members.json")}
    assert after == before
    assert "6800" not in [r["id"] for r in sd.load(sen, "roll-calls.json")]
