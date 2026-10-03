"""contract-v3 S1 and the v3 sources: emission beside v2, flags, exit codes, validation by version (C2-C5, C15, C16)."""

import json
import os

import pytest
from conftest import build, legislature, write_tse
from v3data import api_requests, build3, indicators, out3, serve, snapshot

from mandato_etl import cli


def stats(out):
    return {p.relative_to(out).as_posix(): (p.read_bytes(), p.stat().st_mtime_ns) for p in out.rglob("*") if p.is_file()}


def test_v3_writes_only_its_directory(fake, tmp_path, monkeypatch):
    data_dir = tmp_path / "data"
    monkeypatch.setattr(cli, "OUT_DIR", data_dir / "out")
    monkeypatch.setattr(cli, "V3_DIR", data_dir / "v3")
    serve(fake, indicators())
    assert cli.main(["build", "--years", "2023", "--quiet"]) == 0
    before = stats(data_dir / "out")
    assert before
    assert cli.main(["build", "--contract", "3", "--years", "2023", "--quiet"]) == 0
    assert (data_dir / "v3" / "camara" / "meta.json").is_file()
    assert stats(data_dir / "out") == before
    assert sorted(p.name for p in data_dir.iterdir()) == ["out", "v3"]


def test_v3_out_flag_overrides_default(fake, tmp_path, monkeypatch):
    monkeypatch.setattr(cli, "V3_DIR", tmp_path / "data" / "v3")
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    assert (out3(fake) / "meta.json").is_file()
    assert not (tmp_path / "data" / "v3").exists()


@pytest.mark.parametrize("flag", ["--tse-csv", "--candidacy-json", "--export-candidacy"])
def test_v3_rejects_candidacy_flags(fake, capsys, flag):
    path = write_tse(fake.raw.parent / "consulta_cand_2026_BRASIL.csv")
    argv = [flag, str(path)] if flag != "--export-candidacy" else ["--tse-csv", str(path), flag, str(path) + ".json"]
    assert build3(fake, *argv) == 1
    assert "usage:" in capsys.readouterr().err
    assert fake.requests == []
    assert not out3(fake).exists()


@pytest.mark.parametrize(
    "argv", [["--contract", "5"], ["--contract", "x"], ["--contract", "3", "--house", "presidencia"]],
    ids=["contract-5", "contract-x", "house-presidencia"],
)
def test_bad_contract_or_house_exits_1(fake, capsys, argv):
    assert cli.main(["build", *argv, "--years", "2023", "--out", str(out3(fake))]) == 1
    assert "usage:" in capsys.readouterr().err
    assert fake.requests == []


def test_validate_picks_schema_by_version(fake, capsys):
    fake.serve(legislature())
    assert build(fake, "--quiet") == 0
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    assert cli.main(["validate", str(fake.out)]) == 0
    assert cli.main(["validate", str(out3(fake))]) == 0
    meta = out3(fake) / "meta.json"
    doc = json.loads(meta.read_text())
    meta.write_text(json.dumps({**doc, "schema_version": 2}))
    capsys.readouterr()
    assert cli.main(["validate", str(out3(fake))]) == 1
    meta.write_text(json.dumps({**doc, "schema_version": 5}))
    assert cli.main(["validate", str(out3(fake))]) == 1
    assert "unsupported schema_version 5" in capsys.readouterr().err


def test_v3_api_files_are_cached_and_listed(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    first = api_requests(fake)
    assert first.count("/api/v2/deputados?idLegislatura=57&itens=1000") == 1
    for dep in ("201", "202", "203"):
        assert first.count(f"/api/v2/deputados/{dep}/historico") == 1
    manifest = {e["file"]: e for e in json.loads((fake.raw / "manifest.json").read_text())}
    import hashlib
    for name in ("deputados-legislatura-57.json", "historico-v3/201.json", "historico-v3/202.json", "historico-v3/203.json"):
        stored = (fake.raw / name).read_bytes()
        assert manifest[name]["sha256"] == hashlib.sha256(stored).hexdigest()
        assert manifest[name]["bytes"] == len(stored)
    listed = json.loads((fake.raw / "deputados-legislatura-57.json").read_text())
    assert listed and all("email" not in d for d in listed)
    fake.requests.clear()
    assert build3(fake, "--quiet") == 0
    assert api_requests(fake) == []
    assert fake.bulk_requests() == []


def test_v3_refresh_fetches_again(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    fake.requests.clear()
    assert build3(fake, "--quiet", "--refresh") == 0
    again = api_requests(fake)
    assert "/api/v2/deputados?idLegislatura=57&itens=1000" in again
    assert {f"/api/v2/deputados/{d}/historico" for d in ("201", "202", "203")} <= set(again)
    assert len(fake.bulk_requests()) == 7


def test_v3_download_failure_exits_2(fake, capsys):
    serve(fake, indicators())
    fake.fail["/api/v2/deputados/202/historico"] = [404]
    assert build3(fake, "--quiet") == 2
    assert fake.base + "/api/v2/deputados/202/historico" in capsys.readouterr().err
    assert not out3(fake).exists()


def test_v3_years_flag_reads_only_those_years(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    assert {p.rsplit("-", 1)[1] for p in fake.bulk_requests() if "deputados.csv" not in p} == {"2023.csv"}
    assert os.path.exists(out3(fake) / "members.json")
