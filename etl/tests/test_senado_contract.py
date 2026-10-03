"""etl-senado S5 - the Senate directory is a self-contained contract-v3 directory (C34-C40)."""

import json

import jsonschema
import pytest
import senado_data as sd

from mandato_etl import cli, schema


def snapshot(fake) -> dict[str, bytes]:
    return {p.relative_to(sd.out(fake)).as_posix(): p.read_bytes() for p in sorted(sd.out(fake).rglob("*"))
            if p.is_file()}


DATASETS = {
    "indicators": (sd.indicators, ("2025",), None),
    "recorded": (sd.recorded_dataset, ("2023", "2025"), None),
    "members": (sd.members_dataset, ("2025", "2027"), sd.CLOCK_2027),
}


@pytest.mark.parametrize("name", list(DATASETS))
def test_every_file_passes_its_schema(sen, monkeypatch, name):
    data, years, clock = DATASETS[name]
    if clock:
        sd.pin(monkeypatch, clock)
    sd.serve(sen, data())
    assert sd.build(sen, "--quiet", years=years) == 0
    assert cli.main(["validate", str(sd.out(sen))]) == 0
    for path in sorted(sd.out(sen).rglob("*.json")):
        relative = path.relative_to(sd.out(sen)).as_posix()
        spec = schema.load(schema.kind_of(relative, 3), 3)
        doc = json.loads(path.read_text())
        assert schema.first_error(doc, spec) is None, relative
        assert jsonschema.Draft202012Validator(spec).is_valid(doc), relative


def test_schema_failure_keeps_previous_output(sen, monkeypatch, capsys):
    sd.pin(monkeypatch, sd.CLOCK_2027)
    sd.serve(sen, sd.members_dataset())
    assert sd.build(sen, "--quiet", years=("2025", "2027")) == 0
    before = snapshot(sen)
    sd.serve(sen, sd.members_dataset(uf_9201_m3="São Paulo"))
    capsys.readouterr()
    assert sd.build(sen, "--quiet", "--refresh", years=("2025", "2027")) == 1
    err = capsys.readouterr().err
    assert "members.json" in err and "uf" in err
    assert snapshot(sen) == before


def test_meta_shape(sen, monkeypatch):
    sd.pin(monkeypatch, sd.CLOCK_2027)
    sd.serve(sen, sd.members_dataset())
    assert sd.build(sen, "--quiet", years=("2025", "2027")) == 0
    meta = sd.load(sen, "meta.json")
    assert set(meta) == {"schema_version", "house", "generatedAt", "legislatures", "coverage", "classification",
                         "sources"}
    assert (meta["schema_version"], meta["house"], meta["generatedAt"]) == (3, "senado", "2027-03-01T12:00:00Z")
    assert meta["legislatures"] == [
        {"id": 57, "start": "2023-02-01", "end": "2027-01-31",
         "sourceUrl": "https://legis.senado.leg.br/dadosabertos/plenario/legislatura/20230201"},
        {"id": 58, "start": "2027-02-01", "end": "2031-01-31",
         "sourceUrl": "https://legis.senado.leg.br/dadosabertos/plenario/legislatura/20270201"},
    ]
    assert [row["legislature"] for row in meta["coverage"]] == [57, 58]
    assert meta["classification"] == {"version": 1}
    manifest = json.loads((sen.raw / "manifest.json").read_text())
    assert meta["sources"] == sorted(manifest, key=lambda e: e["file"])
    assert len(meta["sources"]) == 13  # 3 votacao, 3 orientacao, 2 lists, atual, 4 authorship lists

    sen.routes.clear()
    monkeypatch.setattr(cli, "now", lambda: sd.datetime(2026, 9, 27, 12, 0, 0, tzinfo=sd.UTC))
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet", "--refresh") == 0
    assert sd.load(sen, "meta.json")["coverage"] == [{
        "legislature": 57, "through": "2025-03-16", "rollCalls": {"nominal": 6, "secret": 1, "symbolic": None},
        "unclassified": 0, "members": 5,
    }]


def test_build_is_deterministic(sen):
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet") == 0
    first = snapshot(sen)
    assert sd.build(sen, "--quiet") == 0
    assert snapshot(sen) == first
    assert [m["name"] for m in sd.load(sen, "members.json")] == [
        "Ana Alves", "Beto Braga", "Caio Costa", "Dani Dias", "Eva Esteves"]
    calls = sd.load(sen, "roll-calls.json")
    assert [r["id"] for r in calls] == [str(sd.V[n]) for n in range(7, 0, -1)]
    for rc in calls:
        ids = [v["memberId"] for v in sd.load(sen, f"roll-calls/{rc['id']}.json")["votes"]]
        assert ids == sorted(ids)


def test_build_orders_by_accent_stripped_name(sen, monkeypatch):
    sd.pin(monkeypatch, sd.CLOCK_2027)
    sd.serve(sen, sd.members_dataset())
    assert sd.build(sen, "--quiet", years=("2025", "2027")) == 0
    assert [m["name"] for m in sd.load(sen, "members.json")] == [
        "Ana Paula Lobato", "Flávio Dino", "Nove Dois Zero Dois", "Nove Dois Zero Um"]


@pytest.mark.parametrize(("name", "line"), [
    ("recorded", "senado 57: 12 roll calls (10 nominal, 2 secret, 0 symbolic), 1 unclassified"),
    ("indicators", "senado 57: 7 roll calls (6 nominal, 1 secret, 0 symbolic), 0 unclassified"),
])
def test_log_line_per_legislature(sen, capsys, name, line):
    data, years, _ = DATASETS[name]
    sd.serve(sen, data())
    assert sd.build(sen, years=years) == 0
    assert line in capsys.readouterr().err.splitlines()
    assert sd.build(sen, "--quiet", years=years) == 0
    assert line not in capsys.readouterr().err


def test_layout(sen):
    sd.serve(sen, sd.recorded_dataset())
    assert sd.build(sen, "--quiet", years=("2023", "2025")) == 0
    ids = [r["id"] for r in sd.load(sen, "roll-calls.json")]
    expected = {"meta.json", "members.json", "roll-calls.json", "propositions.json", "classification-rules.json"}
    expected |= {f"roll-calls/{rc}.json" for rc in ids}
    assert set(snapshot(sen)) == expected
    assert not (sd.out(sen) / "full-texts").exists()


def test_house_and_identity(sen):
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet") == 0
    for name in ("members.json", "roll-calls.json", "propositions.json", "classification-rules.json"):
        records = sd.load(sen, name)
        assert records and {r["house"] for r in records} == {"senado"}, name
        if name != "classification-rules.json":
            keys = [(r["house"], r["id"]) for r in records]
            assert len(keys) == len(set(keys)), name
    for r in sd.load(sen, "roll-calls.json"):
        assert r["id"].isdigit()
        assert sd.load(sen, f"roll-calls/{r['id']}.json")["house"] == "senado"
