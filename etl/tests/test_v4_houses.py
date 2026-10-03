"""etl-presidencia S1 - v4 beside v3: house directories at schema_version 4, flags, validation (C1-C5, C7)."""

import json

import presidencia_data as pd
import pytest
import senado_data as sd
from conftest import build, legislature
from v3data import build3, indicators, out3, serve, snapshot

from mandato_etl import cli, schema


def _v4_camara(fake, out):
    return cli.main(["build", "--contract", "4", "--house", "camara", "--years", "2023", "--out", str(out), "--quiet"])


def _only_version_differs(v3: dict, v4: dict) -> None:
    assert sorted(v3) == sorted(v4)
    for relative in v3:
        if relative == "meta.json":
            assert b'"schema_version":3' in v3[relative]
            assert v4[relative] == v3[relative].replace(b'"schema_version":3', b'"schema_version":4')
        else:
            assert v4[relative] == v3[relative], relative


def test_camara_v4_equals_v3_but_version(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    out = fake.raw.parent / "v4camara"
    assert _v4_camara(fake, out) == 0
    _only_version_differs(snapshot(out3(fake)), snapshot(out))


def test_senado_v4_equals_v3_but_version(sen):
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet") == 0
    out = sen.raw.parent / "v4senado"
    argv = ["build", "--contract", "4", "--house", "senado", "--years", "2025", "--out", str(out), "--quiet"]
    assert cli.main(argv) == 0
    _only_version_differs(snapshot(sd.out(sen)), snapshot(out))


def _stats(path):
    return {p.relative_to(path).as_posix(): (p.read_bytes(), p.stat().st_mtime_ns) for p in path.rglob("*") if p.is_file()}


def test_v4_default_out_leaves_out_and_v3_untouched(con, tmp_path, monkeypatch):
    data_dir = tmp_path / "data"
    monkeypatch.setattr(cli, "OUT_DIR", data_dir / "out")
    monkeypatch.setattr(cli, "V3_DIR", data_dir / "v3")
    monkeypatch.setattr(cli, "V4_DIR", data_dir / "v4")
    serve(con, indicators())
    assert cli.main(["build", "--years", "2023", "--quiet"]) == 0
    assert cli.main(["build", "--contract", "3", "--years", "2023", "--quiet"]) == 0
    before_out, before_v3 = _stats(data_dir / "out"), _stats(data_dir / "v3")
    assert before_out and before_v3
    assert cli.main(["build", "--contract", "4", "--house", "camara", "--years", "2023", "--quiet"]) == 0
    assert (data_dir / "v4" / "camara" / "meta.json").is_file()
    pd.house_dir(data_dir / "v4" / "senado", "senado", [])
    pd.serve(con, {"congress": {"/processo?sigla=MPV&ano=2023": [], "/materia/vetos/2023": pd.veto_list([])},
                   "camara": {}, "houses": {}, "years": ()})
    assert cli.main(["build", "--contract", "4", "--house", "presidencia", "--years", "2023", "--quiet"]) == 0
    assert (data_dir / "v4" / "presidencia" / "meta.json").is_file()
    assert _stats(data_dir / "out") == before_out
    assert _stats(data_dir / "v3") == before_v3


@pytest.mark.parametrize("argv", [
    ["--contract", "2", "--house", "presidencia"], ["--contract", "3", "--house", "presidencia"],
    ["--house", "presidencia"], ["--contract", "5"], ["--contract", "x"],
], ids=["presidencia-2", "presidencia-3", "presidencia-default", "contract-5", "contract-x"])
def test_bad_contract_or_house_exits_1(con, capsys, argv):
    out = pd.out(con)
    assert cli.main(["build", *argv, "--years", "2023", "--out", str(out)]) == 1
    assert "usage:" in capsys.readouterr().err
    assert con.requests == []
    assert not out.exists()


def test_validate_picks_the_v4_set_by_scope(sen, con, capsys):
    serve(con, indicators())
    camara4 = con.raw.parent / "v4camara"
    assert _v4_camara(con, camara4) == 0
    assert cli.main(["validate", str(camara4)]) == 0
    senado4 = con.raw.parent / "v4senado"
    sd.serve(con, sd.indicators())
    assert cli.main(["build", "--contract", "4", "--house", "senado", "--years", "2025", "--out", str(senado4),
                     "--quiet"]) == 0
    assert cli.main(["validate", str(senado4)]) == 0
    data = pd.recorded()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    assert cli.main(["validate", str(pd.out(con))]) == 0
    meta = camara4 / "meta.json"
    doc = json.loads(meta.read_text())
    meta.write_text(json.dumps({**doc, "scope": "presidencia"}))
    assert cli.main(["validate", str(camara4)]) == 1
    meta.write_text(json.dumps({**doc, "schema_version": 5}))
    capsys.readouterr()
    assert cli.main(["validate", str(camara4)]) == 1
    assert "unsupported schema_version 5" in capsys.readouterr().err
    # v2 and v3 keep their behaviour (contract-v3 AC 5)
    con.serve(legislature())
    assert build(con, "--quiet", "--refresh") == 0
    assert cli.main(["validate", str(con.out)]) == 0
    serve(con, indicators())
    assert build3(con, "--quiet", "--refresh") == 0
    assert cli.main(["validate", str(out3(con))]) == 0
    v3meta = out3(con) / "meta.json"
    v3meta.write_text(json.dumps({**json.loads(v3meta.read_text()), "schema_version": 2}))
    assert cli.main(["validate", str(out3(con))]) == 1


HOUSE_KINDS = ("meta", "members", "roll-calls", "roll-call", "propositions", "classification-rules", "full-text")
PRESIDENCY_KINDS = ("presidency-meta", "acts", "status-rules", "joint-roll-calls", "joint-roll-call",
                    "member-veto-counts")


def test_v4_schema_set():
    v4 = schema.SCHEMA_DIR / "v4"
    assert sorted(p.name for p in v4.iterdir()) == sorted(f"{k}.schema.json" for k in (*HOUSE_KINDS, *PRESIDENCY_KINDS))
    for kind in HOUSE_KINDS:
        three = json.loads((schema.SCHEMA_DIR / "v3" / f"{kind}.schema.json").read_text())
        four = json.loads((v4 / f"{kind}.schema.json").read_text())
        if kind == "meta":
            assert three["properties"]["schema_version"]["const"] == 3
            assert four["properties"]["schema_version"]["const"] == 4
            three["properties"]["schema_version"]["const"] = 4
        assert four == three, kind
