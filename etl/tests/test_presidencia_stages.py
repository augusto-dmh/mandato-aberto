"""etl-presidencia S4 - from the act to each house's roll calls (C29-C32)."""

import json

import presidencia_data as pd
import pytest


@pytest.fixture
def r(con):
    data = pd.recorded()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    return con


def stage(house, id):
    return {"house": house, "propositionId": id}


def test_mp_stages(r):
    assert pd.act(r, "mpv-1154-2023")["stages"] == [stage("camara", 2345493), stage("senado", 8349431)]
    assert pd.act(r, "mpv-1177-2023")["stages"] == [stage("camara", 2367600), stage("senado", 8468604)]
    assert pd.act(r, "mpv-1350-2026")["stages"] == [stage("senado", 9034814)]
    assert pd.load(r, "meta.json")["coverage"]["missingCamaraStage"] == 1


def test_bill_stages(r):
    assert pd.act(r, "pl-3626-2023")["stages"] == [stage("camara", 2374400), stage("senado", 8542290)]
    assert pd.act(r, "plp-93-2023")["stages"] == [stage("camara", 2357053), stage("senado", 8463489)]
    assert pd.act(r, "pec-9103-2026")["stages"] == [stage("camara", 9900103)]
    assert pd.act(r, "pl-9101-2023")["stages"] == [stage("camara", 9900101)]


def test_veto_has_no_stage(r):
    vetoes = [a for a in pd.load(r, "acts.json") if a["kind"] == "veto"]
    assert len(vetoes) == 5 and all(a["stages"] == [] for a in vetoes)
    veto = pd.act(r, "vet-49-2023")
    joint = sorted(j["id"] for j in pd.load(r, "joint-roll-calls.json") if j["actId"] == "vet-49-2023")
    assert joint == sorted(d["jointRollCallId"] for d in veto["devices"] if d["jointRollCallId"])
    assert joint == ["49.23.001", "49.23.004"]


def test_stages_join_house_roll_calls(r):
    roll_calls = {house: json.loads((pd.v4(r) / house / "roll-calls.json").read_text()) for house in ("camara", "senado")}

    def join(act_id):
        return sorted(f"{rc['house']} {rc['id']}" for s in pd.act(r, act_id)["stages"]
                      for rc in roll_calls[s["house"]] if rc["propositionId"] == s["propositionId"])

    assert join("mpv-1154-2023") == ["camara 2345493-41", "camara 2345493-64", "senado 6704"]
    assert join("pl-3626-2023") == ["camara 2374400-10"]
    assert join("plp-93-2023") == ["senado 7000"]
