"""contract-v3 S4 - positions and indicators per mandate (C28-C39); expected numbers are in v3data."""

import pytest
from v3data import (
    A1, C1, F1, INDICATOR_ORIENTATIONS, INDICATOR_ROLL_CALLS, INDICATOR_VOTES, P1, S1, build3, indicators, load3,
    mandate, member, out3, roll_call, serve, snapshot,
)

from mandato_etl import classify, contract_v3


@pytest.mark.parametrize(
    ("official", "ballot", "position"),
    [
        ("Sim", "nominal", "yes"), ("Não", "nominal", "no"), ("Abstenção", "nominal", "abstention"),
        ("Obstrução", "nominal", "obstruction"), ("Artigo 17", "nominal", "presiding"),
        ("", "secret", "secret"), ("", "nominal", "notVoting"),
    ],
    ids=["Sim", "Nao", "Abstencao", "Obstrucao", "Artigo-17", "empty-secret", "empty-nominal"],
)
def test_position_of(official, ballot, position):
    assert classify.position_of(official, ballot) == position


def test_position_of_unknown_raises():
    with pytest.raises(classify.UnknownValueError):
        classify.position_of("Presente", "nominal")


@pytest.fixture
def built_indicators(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    return fake


def votes_of(fake, rc):
    return {v["memberId"]: v for v in load3(fake, f"roll-calls/{rc}.json")["votes"]}


def test_vote_keeps_official_value(built_indicators):
    f2 = votes_of(built_indicators, "300-4")
    assert (f2[203]["official"], f2[203]["position"]) == ("Artigo 17", "presiding")
    a1 = votes_of(built_indicators, A1)
    assert (a1[201]["official"], a1[201]["position"]) == ("", "notVoting")
    assert (a1[202]["official"], a1[202]["position"]) == ("Abstenção", "abstention")
    p2 = votes_of(built_indicators, "300-2")
    assert (p2[203]["official"], p2[203]["position"]) == ("Obstrução", "obstruction")
    assert (p2[201]["official"], p2[201]["position"]) == ("Não", "no")


@pytest.mark.parametrize("where", ["vote", "orientation"])
def test_unknown_value_stops_the_build(fake, capsys, where):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    before = snapshot(out3(fake))
    if where == "vote":
        votes = [(d, rc, "Presente" if (d, rc) == ("203", P1) else v) for d, rc, v in INDICATOR_VOTES]
        serve(fake, indicators(votes=votes))
        value, rc = "Presente", P1
    else:
        serve(fake, indicators(orientations=[*INDICATOR_ORIENTATIONS, (F1, "PL", "Talvez")]))
        value, rc = "Talvez", F1
    assert build3(fake, "--quiet", "--refresh") == 1
    err = capsys.readouterr().err
    assert value in err and rc in err
    assert snapshot(out3(fake)) == before


@pytest.mark.parametrize(
    ("official", "position"),
    [("Sim", "yes"), ("Não", "no"), ("Abstenção", "abstention"), ("Obstrução", "obstruction"), ("Liberado", "free")],
)
def test_orientation_of(official, position):
    assert classify.orientation_of(official) == position


def test_orientation_of_unknown_raises():
    with pytest.raises(classify.UnknownValueError):
        classify.orientation_of("Talvez")


def test_orientations_and_government_position(built_indicators):
    assert roll_call(built_indicators, P1)["governmentOrientation"] == "yes"
    assert roll_call(built_indicators, A1)["governmentOrientation"] == "free"
    assert roll_call(built_indicators, S1)["governmentOrientation"] is None
    f1 = load3(built_indicators, f"roll-calls/{F1}.json")
    assert sorted((o["bench"], o["official"], o["position"]) for o in f1["orientations"]) == [
        ("Governo", "Sim", "yes"), ("PT", "Sim", "yes"),
    ]
    assert f1["governmentOrientation"] == "yes"


def indicator(fake, dep, key, basis):
    value = mandate(member(fake, dep), 57)[key][basis]
    return f"{value['count']}/{value['total']}"


def test_participation_all(built_indicators):
    assert [indicator(built_indicators, d, "participation", "all") for d in (201, 202, 203)] == ["4/5", "4/4", "5/5"]


def test_participation_merit(built_indicators):
    assert [indicator(built_indicators, d, "participation", "merit") for d in (201, 202, 203)] == ["2/3", "2/2", "3/3"]


def test_government_alignment(built_indicators):
    got = [(indicator(built_indicators, d, "governmentAlignment", "all"),
            indicator(built_indicators, d, "governmentAlignment", "merit")) for d in (201, 202, 203)]
    assert got == [("4/4", "2/2"), ("1/3", "0/1"), ("1/3", "1/1")]


def test_party_alignment(built_indicators):
    got = [(indicator(built_indicators, d, "partyAlignment", "all"),
            indicator(built_indicators, d, "partyAlignment", "merit")) for d in (201, 202, 203)]
    assert got == [("1/3", "0/1"), ("1/3", "0/1"), ("0/0", "0/0")]


@pytest.mark.parametrize(
    ("positions", "own", "expected"),
    [
        (["yes", "yes", "no", "notVoting"], "notVoting", "yes"),
        (["yes", "no", "presiding"], "presiding", None),
        (["no"], "no", None),
        (["yes", "no"], "no", "yes"),
        (["secret", "secret", "presiding", "no"], "no", None),
    ],
    ids=["clear", "tie", "none", "own-excluded", "non-votes-never-count"],
)
def test_party_majority(positions, own, expected):
    assert contract_v3.party_majority(positions, own) == expected


def test_symbolic_merit(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    assert [mandate(member(fake, d), 57)["symbolicMerit"] for d in (201, 202, 203)] == [1, 0, 1]
    extra = {"id": "300-9", "at": "2023-08-01T15:00:00", "organ": "PLEN", "desc": "Aprovado o Requerimento."}
    serve(fake, indicators(roll_calls=[*INDICATOR_ROLL_CALLS, extra]))
    assert build3(fake, "--quiet", "--refresh") == 0
    assert roll_call(fake, "300-9")["kind"] == "procedural"
    assert [mandate(member(fake, d), 57)["symbolicMerit"] for d in (201, 202, 203)] == [1, 0, 1]


def test_proposition_counts(built_indicators):
    def counts(dep):
        m = mandate(member(built_indicators, dep), 57)
        return m["authoredCount"], m["firstSignerCount"], m["requirementsCount"]

    assert counts(201) == (5, 2, 3)
    assert counts(202) == (1, 0, 0)
    assert counts(203) == (0, 0, 0)


def test_committee_vote_changes_no_indicator(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    with_committee = {m["id"]: m["mandates"] for m in load3(fake, "members.json")}
    serve(fake, indicators(
        roll_calls=[r for r in INDICATOR_ROLL_CALLS if r["id"] != C1],
        votes=[v for v in INDICATOR_VOTES if v[1] != C1],
        orientations=[o for o in INDICATOR_ORIENTATIONS if o[0] != C1],
    ))
    assert build3(fake, "--quiet", "--refresh") == 0
    assert C1 not in {r["id"] for r in load3(fake, "roll-calls.json")}
    assert {m["id"]: m["mandates"] for m in load3(fake, "members.json")} == with_committee


FORBIDDEN = {"ratio", "percent", "percentage", "share"}


def walk(doc, seen):
    if isinstance(doc, dict):
        seen.update(doc)
        for key in ("all", "merit"):
            if key in doc and isinstance(doc[key], dict):
                assert set(doc[key]) == {"count", "total"}, doc
        for value in doc.values():
            walk(value, seen)
    elif isinstance(doc, list):
        for value in doc:
            walk(value, seen)


def test_no_percentage_keys(built_indicators):
    import json
    keys = set()
    files = [p for p in out3(built_indicators).rglob("*.json")]
    assert len(files) > 5
    for path in files:
        walk(json.loads(path.read_text()), keys)
    assert not keys & FORBIDDEN
    assert {"all", "merit"} <= keys
