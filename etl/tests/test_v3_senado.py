"""contract-v3 S6 - the shape fits the Senate (C50, C51).

`fixtures/v3/senado/` is hand-written, not derived from any API response: fictitious senators 9101
and 9102, and roll call `6923` with five records covering the Senate codes the shape must hold, so
memberIds 9103-9105 have no member record.
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
    assert len(members) == 2
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
