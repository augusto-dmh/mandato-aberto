"""launch S8 - the candidacy export and `--candidacy-json` (C51-C56)."""

import hashlib
import json
from datetime import UTC, datetime
from pathlib import Path

import pytest
from conftest import TSE_ROWS, legislature, load, write_tse

from mandato_etl import cli, readers

ANA = {"ballotNumber": "1313", "office": "DEPUTADO FEDERAL", "party": "PT", "situation": "APTO"}
BRUNO = {"ballotNumber": "131", "office": "SENADOR", "party": "PT", "situation": "APTO"}


def run(fake, out, *args) -> int:
    return cli.main(["build", "--years", "2023", "--out", str(out), *args])


@pytest.fixture
def exported(fake, tmp_path):
    """The fixture legislature built from the TSE CSV, exporting the candidacy file."""
    fake.serve(legislature())
    csv = write_tse(tmp_path / "consulta_cand_2026_BRASIL.csv")
    export = tmp_path / "export" / "candidacy-2026.json"
    export.parent.mkdir()
    assert run(fake, fake.out, "--tse-csv", str(csv), "--export-candidacy", str(export)) == 0
    fake.csv, fake.export = csv, export
    return fake


def test_export_writes_source_matched_and_ambiguous(exported):
    csv, text = exported.csv, exported.export.read_text()
    doc = json.loads(text)
    assert doc["source"] == {
        "bytes": csv.stat().st_size,
        "file": "consulta_cand_2026_BRASIL.csv",
        "modifiedAt": datetime.fromtimestamp(csv.stat().st_mtime, UTC).strftime("%Y-%m-%dT%H:%M:%SZ"),
        "sha256": hashlib.sha256(csv.read_bytes()).hexdigest(),
    }
    assert doc["matched"] == {"101": ANA, "102": BRUNO}
    assert doc["ambiguous"] == [103]
    assert text == json.dumps(doc, sort_keys=True, indent=2, ensure_ascii=False) + "\n"
    assert text.endswith("\n")


def test_export_has_only_the_contract_fields(exported):
    text = exported.export.read_text()
    doc = json.loads(text)
    assert sorted(doc) == ["ambiguous", "matched", "source"]
    assert all(sorted(entry) == ["ballotNumber", "office", "party", "situation"] for entry in doc["matched"].values())
    assert sorted(doc["source"]) == ["bytes", "file", "modifiedAt", "sha256"]
    assert "cpf" not in text.lower()
    for born in ["1970-05-01", "1980-02-02", "1990-03-03", *(r["DT_NASCIMENTO"] for r in TSE_ROWS)]:
        assert born not in text, born


def test_candidacy_json_reproduces_the_tse_build(exported, tmp_path, monkeypatch):
    exported.csv.unlink()  # nothing under the TSE path is there to read
    read = readers.read

    def no_tse(kind, path):
        assert kind != "tse", path
        return read(kind, path)

    monkeypatch.setattr(readers, "read", no_tse)
    second = tmp_path / "out-json"
    assert run(exported, second, "--candidacy-json", str(exported.export)) == 0

    first_files = sorted(p.relative_to(exported.out) for p in exported.out.rglob("*") if p.is_file())
    second_files = sorted(p.relative_to(second) for p in second.rglob("*") if p.is_file())
    assert first_files == second_files
    for relative in first_files:
        if relative == Path("meta.json"):
            continue
        assert (exported.out / relative).read_bytes() == (second / relative).read_bytes(), relative
    first_meta, second_meta = load(exported.out, "meta.json"), load(second, "meta.json")
    assert first_meta["candidacy"]["file"] == "consulta_cand_2026_BRASIL.csv"
    assert second_meta["candidacy"]["file"] == "candidacy-2026.json"
    second_meta["candidacy"]["file"] = first_meta["candidacy"]["file"]
    assert second_meta == first_meta


def test_matched_counts_ids_in_the_deputy_set(fake, tmp_path):
    fake.serve(legislature())
    path = tmp_path / "candidacy-2026.json"
    path.write_text(json.dumps({
        "source": {"bytes": 1, "file": "consulta_cand_2026_BRASIL.csv", "modifiedAt": "2026-09-27T12:00:00Z", "sha256": "0"},
        "matched": {"101": ANA, "999": BRUNO},
        "ambiguous": [103, 998],
    }))
    assert run(fake, fake.out, "--candidacy-json", str(path)) == 0
    got = {d["id"]: d["candidacy2026"] for d in load(fake.out, "deputies.json")}
    assert got == {101: ANA, 102: None, 103: None}
    meta = load(fake.out, "meta.json")["candidacy"]
    assert meta["matched"] == 1
    assert meta["ambiguous"] == [103, 998]
    assert meta["file"] == "candidacy-2026.json"


@pytest.mark.parametrize("args", [
    ["--tse-csv", "x.csv", "--candidacy-json", "y.json"],
    ["--export-candidacy", "z.json"],
], ids=["both inputs", "export without csv"])
def test_conflicting_candidacy_flags_exit_1(fake, capsys, args):
    fake.serve(legislature())
    assert run(fake, fake.out, *args) == 1
    assert "usage:" in capsys.readouterr().err
    assert fake.requests == []


INVALID = {
    "missing": None,
    "not json": "{",
    "entry lacking a field": json.dumps({"source": {}, "matched": {"101": {k: v for k, v in ANA.items() if k != "situation"}},
                                         "ambiguous": []}),
    "cpf key nested": json.dumps({"source": {}, "matched": {"101": {"office": "X", "party": "Y", "ballotNumber": "1",
                                                                     "situation": "APTO", "extra": {"CPF": "1"}}},
                                  "ambiguous": []}),
}


@pytest.mark.parametrize("case", list(INVALID))
def test_invalid_candidacy_json_exits_1(fake, tmp_path, capsys, case):
    fake.serve(legislature())
    assert run(fake, fake.out) == 0
    before = {p: p.read_bytes() for p in fake.out.rglob("*") if p.is_file()}
    requests = len(fake.requests)
    capsys.readouterr()

    path = tmp_path / f"bad-{case.replace(' ', '-')}.json"
    if INVALID[case] is not None:
        path.write_text(INVALID[case])
    assert run(fake, fake.out, "--candidacy-json", str(path)) == 1
    assert path.name in capsys.readouterr().err
    assert {p: p.read_bytes() for p in fake.out.rglob("*") if p.is_file()} == before
    assert len(fake.requests) == requests
