"""contract-v3 S6 - the shape fits the Senate (C50, C51).

`fixtures/v3/senado/` is hand-written, not derived from any API response: fictitious senators 9101
to 9105, and roll call `6923` with five records, one per senator, covering the Senate codes the shape
must hold.
"""

import json
import shutil
from pathlib import Path

import pytest

from mandato_etl import cli

SENADO = Path(__file__).parent / "fixtures" / "v3" / "senado"


def test_senate_fixture_validates():
    assert cli.main(["validate", str(SENADO)]) == 0
    members = json.loads((SENADO / "members.json").read_text())
    assert [m["id"] for m in members] == [9101, 9102, 9103, 9104, 9105]
    assert all([m["legislature"] for m in member["mandates"]] == [57, 58] for member in members)
    assert all(member["house"] == "senado" for member in members)
    doc = json.loads((SENADO / "roll-calls" / "6923.json").read_text())
    assert (doc["id"], doc["ballot"], doc["house"]) == ("6923", "nominal", "senado")
    assert sorted(v["official"] for v in doc["votes"]) == sorted(
        ["Sim", "Não", "P-NRV", "Presidente (art. 51 RISF)", "Licença"])
    assert json.loads((SENADO / "meta.json").read_text())["house"] == "senado"


@pytest.mark.parametrize(
    ("relative", "mutate", "value"),
    [
        ("members.json", lambda d: d[0].__setitem__("house", "presidencia"), "presidencia"),
        ("roll-calls/6923.json", lambda d: d["votes"][0].__setitem__("position", "other"), "other"),
    ],
    ids=["house", "position"],
)
def test_invalid_senate_value_is_named(tmp_path, capsys, relative, mutate, value):
    copy = tmp_path / "senado"
    shutil.copytree(SENADO, copy)
    doc = json.loads((copy / relative).read_text())
    mutate(doc)
    (copy / relative).write_text(json.dumps(doc))
    assert cli.main(["validate", str(copy)]) == 1
    err = capsys.readouterr().err
    assert relative in err and value in err


@pytest.mark.parametrize(
    ("relative", "mutate", "named"),
    [
        ("roll-calls/6923.json", lambda d: d["votes"].append({**d["votes"][0], "memberId": 9999}), ("6923", "9999")),
        ("propositions.json", lambda d: d[0]["authors"].append({"memberId": 9998, "firstSigner": True}),
         ("160000", "9998")),
    ],
    ids=["vote", "author"],
)
def test_validate_refuses_a_member_id_missing_from_members(tmp_path, capsys, relative, mutate, named):
    copy = tmp_path / "senado"
    shutil.copytree(SENADO, copy)
    doc = json.loads((copy / relative).read_text())
    mutate(doc)
    (copy / relative).write_text(json.dumps(doc))
    assert cli.main(["validate", str(copy)]) == 1
    err = capsys.readouterr().err
    assert relative in err and all(token in err for token in named) and "members.json" in err
