"""etl-presidencia S6 - descriptive counts per member and per term (C44-C48)."""

from datetime import UTC, datetime

import presidencia_data as pd
import pytest

from mandato_etl import cli, presidency

KEYS = ("participation", "keepAll", "overrideAll", "mixed")


def build(con, data, monkeypatch=None, clock=None):
    if clock is not None:
        monkeypatch.setattr(cli, "now", lambda: clock)
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    return con


def rows(con):
    found = {}
    for row in pd.load(con, "member-veto-counts.json"):
        assert row["legislature"] == 57
        found[(row["house"], row["memberId"])] = " ".join(f"{row[k]['count']}/{row[k]['total']}" for k in KEYS)
    return found


def test_member_counts_hand(con):
    build(con, pd.hand())
    assert rows(con) == {
        ("camara", 7101): "1/1 1/1 0/1 0/1",
        ("camara", 7102): "1/1 0/1 1/1 0/1",
        ("camara", 7103): "1/1 0/1 0/1 1/1",
        ("camara", 7104): "1/1 1/1 0/1 0/1",
        ("camara", 7105): "0/1 0/0 0/0 0/0",
        ("senado", 5386): "1/1 0/1 0/1 1/1",
        ("senado", 8101): "1/1 1/1 0/1 0/1",
        ("senado", 8102): "1/1 0/1 1/1 0/1",
    }


def test_member_counts_recorded(con):
    build(con, pd.recorded())
    assert rows(con) == {
        ("camara", 7001): "4/4 1/4 2/4 1/4",
        ("camara", 7002): "4/4 1/4 1/4 2/4",
        ("camara", 7003): "4/4 2/4 1/4 1/4",
        ("camara", 7004): "2/2 1/2 0/2 1/2",
        ("camara", 7005): "3/4 0/3 1/3 2/3",
        ("camara", 7006): "0/4 0/0 0/0 0/0",
        ("senado", 285): "1/3 0/1 1/1 0/1",
        ("senado", 5386): "3/3 0/3 2/3 1/3",
        ("senado", 8001): "3/3 1/3 2/3 0/3",
        ("senado", 8002): "3/3 0/3 2/3 1/3",
        ("senado", 8003): "3/3 0/3 2/3 1/3",
        ("senado", 8004): "2/3 0/2 1/2 1/2",
        ("senado", 8005): "3/3 0/3 2/3 1/3",
    }
    assert ("camara", 7007) not in rows(con)


def test_base_needs_the_members_house_votes():
    # vet-1 holds Senate votes, vet-2 only Câmara votes (Senado: null): no senator's base holds vet-2
    def jrc(id, act, day, houses):
        votes = [{"house": h, "memberId": m, "position": "yes"} for h, m in houses]
        return {"id": id, "actId": act, "date": day, "legislature": 57, "votesAvailable": True, "votes": votes}

    late = [{"start": "2025-01-01T00:00:00", "end": "2026-09-27T09:00:00"}]
    members = {"camara": [pd.member(1, "A", "P", "SP")],
               "senado": [pd.member(2, "B", "P", "SP"), pd.member(3, "C", "P", "SP", periods=late)]}
    joint = [jrc("1.24.001", "vet-1", "2024-05-09", [("camara", 1), ("senado", 2)]),
             jrc("2.25.001", "vet-2", "2025-06-17", [("camara", 1)])]
    found = {(r["house"], r["memberId"]): r["participation"] for r in presidency.member_counts(members, joint)}
    assert found == {("camara", 1): {"count": 2, "total": 2}, ("senado", 2): {"count": 1, "total": 1}}


def test_veto_counted_once(con):
    build(con, pd.hand())
    votes = [v for id in ("90.25.001", "90.25.002", "90.25.003")
             for v in pd.load(con, f"joint-roll-calls/{id}.json")["votes"] if v["memberId"] == 7101]
    assert len(votes) == 3
    assert len({pd.joint(con, id)["date"] for id in ("90.25.001", "90.25.003")}) == 2
    row = next(r for r in pd.load(con, "member-veto-counts.json") if r["memberId"] == 7101)
    assert row["participation"] == {"count": 1, "total": 1}


def test_split_veto_is_mixed(con):
    build(con, pd.hand())
    positions = {id: next(v["position"] for v in pd.load(con, f"joint-roll-calls/{id}.json")["votes"]
                          if v["memberId"] == 7103) for id in ("90.25.001", "90.25.002")}
    assert positions == {"90.25.001": "yes", "90.25.002": "no"}
    row = next(r for r in pd.load(con, "member-veto-counts.json") if r["memberId"] == 7103)
    assert (row["keepAll"]["count"], row["overrideAll"]["count"], row["mixed"]["count"]) == (0, 0, 1)


R_TERMS = [{
    "term": "2023-2026",
    "provisionalMeasure": {"total": 6, "pending": 1, "approved": 1, "approvedAmended": 1, "rejected": 0, "lapsed": 2,
                           "revoked": 1, "returned": 0},
    "veto": {"total": 5, "pending": 1, "decided": 4,
             "devices": {"kept": 2, "overridden": 4, "prejudged": 1, "pending": 1, "total": 8}},
    "bill": {"total": 7, "inProgress": 1, "law": 4, "vetoedTotally": 0, "withdrawn": 1, "archived": 1},
}]


def _sums_hold(term_rows):
    for row in term_rows:
        for kind in ("provisionalMeasure", "veto", "bill"):
            counts = {k: v for k, v in row[kind].items() if k not in ("total", "devices")}
            assert sum(counts.values()) == row[kind]["total"], (row["term"], kind)
        devices = row["veto"]["devices"]
        assert sum(v for k, v in devices.items() if k != "total") == devices["total"]


@pytest.mark.parametrize("dataset", ["recorded", "hand", "terms"])
def test_term_coverage(con, monkeypatch, dataset):
    clock = pd.CLOCK_2027 if dataset == "terms" else datetime(2026, 9, 27, 12, tzinfo=UTC)
    build(con, getattr(pd, dataset)(), monkeypatch, clock)
    term_rows = pd.load(con, "meta.json")["coverage"]["terms"]
    if dataset == "recorded":
        assert term_rows == R_TERMS
    _sums_hold(term_rows)


FORBIDDEN = {"ratio", "percent", "percentage", "share", "rate"}


def _walk(doc):
    if isinstance(doc, dict):
        yield doc
        for value in doc.values():
            yield from _walk(value)
    elif isinstance(doc, list):
        for value in doc:
            yield from _walk(value)


@pytest.mark.parametrize("dataset", ["recorded", "hand", "terms"])
def test_no_rate_keys(con, monkeypatch, dataset):
    clock = pd.CLOCK_2027 if dataset == "terms" else datetime(2026, 9, 27, 12, tzinfo=UTC)
    build(con, getattr(pd, dataset)(), monkeypatch, clock)
    import json
    for path in pd.out(con).rglob("*.json"):
        for obj in _walk(json.loads(path.read_text())):
            assert not FORBIDDEN & set(obj), path
            if "count" in obj:
                assert set(obj) == {"count", "total"}, path
