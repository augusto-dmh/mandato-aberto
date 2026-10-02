"""contract-v3 S2 - legislature as a dimension (C7-C14)."""

import json
from datetime import UTC, datetime
from pathlib import Path

import pytest
from v3data import (
    V3_PINNED, build3, classification, legislatures, load3, mandate, member, out3, pin, serve, snapshot,
)

from mandato_etl import contract_v3

RECORDED = Path(__file__).parent / "fixtures" / "v3" / "recorded"
ZERO = {"all": {"count": 0, "total": 0}, "merit": {"count": 0, "total": 0}}


@pytest.mark.parametrize(
    ("day", "expected"),
    [("2023-02-01", 57), ("2027-01-31", 57), ("2027-02-01", 58), ("2031-01-31", 58)],
)
def test_legislature_of(day, expected):
    assert contract_v3.legislature_of(day) == expected


def test_legislature_of_outside_raises():
    with pytest.raises(contract_v3.ContractError):
        contract_v3.legislature_of("2031-02-01")


def test_legislature_dates_match_recorded_api():
    for legislature in (57, 58):
        recorded = json.loads((RECORDED / f"legislaturas-{legislature}.json").read_text())["dados"]
        start, end = contract_v3.LEGISLATURES[legislature]
        assert (start, end) == (recorded["dataInicio"], recorded["dataFim"])


def test_roll_call_outside_known_legislature_exits_1(fake, capsys):
    serve(fake, classification())
    assert build3(fake, "--quiet") == 0
    before = snapshot(out3(fake))
    serve(fake, classification([{"id": "900-1", "at": "2031-02-05T15:00:00", "organ": "PLEN", "desc": "Aprovado."}]))
    assert build3(fake, "--quiet", "--refresh") == 1
    err = capsys.readouterr().err
    assert "900-1" in err and "2031-02-05" in err
    assert snapshot(out3(fake)) == before


@pytest.mark.parametrize(
    ("moment", "ids"),
    [(datetime(2027, 2, 1, 2, 59, 59, tzinfo=UTC), [57]), (datetime(2027, 2, 1, 3, 0, 0, tzinfo=UTC), [57, 58])],
    ids=["before-58", "at-58"],
)
def test_meta_lists_started_legislatures(fake, monkeypatch, moment, ids):
    pin(monkeypatch, moment)
    serve(fake, classification(lists={57: ["201", "202", "203"], 58: []}))
    assert build3(fake, "--quiet") == 0
    listed = load3(fake, "meta.json")["legislatures"]
    expected = {
        57: {"id": 57, "start": "2023-02-01", "end": "2027-01-31",
             "sourceUrl": "https://dadosabertos.camara.leg.br/api/v2/legislaturas/57"},
        58: {"id": 58, "start": "2027-02-01", "end": "2031-01-31",
             "sourceUrl": "https://dadosabertos.camara.leg.br/api/v2/legislaturas/58"},
    }
    assert listed == [expected[i] for i in ids]


@pytest.fixture
def built_legislatures(fake, monkeypatch):
    pin(monkeypatch, V3_PINNED)
    serve(fake, legislatures())
    assert build3(fake, "--quiet") == 0
    return fake


def test_one_mandate_per_listed_or_voting_deputy(built_legislatures):
    members = load3(built_legislatures, "members.json")
    assert sorted(m["id"] for m in members) == [301, 302, 303]
    by_id = {m["id"]: [x["legislature"] for x in m["mandates"]] for m in members}
    assert by_id == {301: [57, 58], 302: [57], 303: [58]}


def test_exercise_periods_per_legislature(built_legislatures):
    rita = member(built_legislatures, 301)
    assert mandate(rita, 57)["exercisePeriods"] == [{"start": "2023-02-01T10:00:00", "end": "2027-02-01T00:00:00"}]
    assert mandate(rita, 58)["exercisePeriods"] == [{"start": "2027-02-01T10:00:00", "end": "2027-03-01T09:00:00"}]


def test_indicators_use_only_their_legislature(built_legislatures):
    rita = member(built_legislatures, 301)
    for legislature in (57, 58):
        assert mandate(rita, legislature)["participation"]["all"] == {"count": 1, "total": 1}
        assert mandate(rita, legislature)["authoredCount"] == 1


def test_mandate_without_roll_calls_is_all_zero(built_legislatures):
    saulo = mandate(member(built_legislatures, 302), 57)
    for key in ("participation", "governmentAlignment", "partyAlignment"):
        assert saulo[key] == ZERO
    assert saulo["symbolicMerit"] == 0
    assert (saulo["authoredCount"], saulo["firstSignerCount"], saulo["requirementsCount"]) == (0, 0, 0)


def test_member_fields_come_from_latest_record(built_legislatures):
    rita = member(built_legislatures, 301)
    assert (rita["name"], rita["party"], rita["uf"]) == ("Rita Alves Lima", "PSB", "SP")
    assert rita["photoUrl"] == "https://www.camara.leg.br/internet/deputado/bandep/301-58.jpg"
    assert (mandate(rita, 57)["party"], mandate(rita, 58)["party"]) == ("PT", "PSB")
    assert (mandate(rita, 57)["uf"], mandate(rita, 58)["uf"]) == ("SP", "SP")
    saulo = member(built_legislatures, 302)
    assert (saulo["name"], saulo["party"], saulo["uf"]) == ("Saulo Melo", "PDT", "CE")
    assert saulo["photoUrl"] == "https://www.camara.leg.br/internet/deputado/bandep/302.jpg"
    assert (mandate(saulo, 57)["party"], mandate(saulo, 57)["uf"]) == ("PDT", "CE")
