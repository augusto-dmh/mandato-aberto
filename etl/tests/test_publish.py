"""S6 - publish and validate (C29-C32, C34, C35, C38)."""

import json

import jsonschema
from conftest import CX, CY, CZ, R1, R2, R3, R4, build, legislature, load

from mandato_etl import cli, compute, schema

KINDS = {"meta", "deputies", "roll-calls", "deputy", "roll-call"}


def _tree(out):
    return {p.relative_to(out).as_posix(): p.read_bytes() for p in sorted(out.rglob("*")) if p.is_file()}


def test_output_validates_against_schemas(built):
    validated = set()
    for relative in _tree(built.out):
        kind = schema.kind_of(relative)
        jsonschema.validate(load(built.out, relative), schema.load(kind))
        validated.add(kind)
    assert validated == KINDS


def test_validate_command_exit_codes(built, capsys):
    assert cli.main(["validate", str(built.out)]) == 0
    path = built.out / "deputies" / "101.json"
    doc = json.loads(path.read_text())
    doc["participation"]["count"] = -1
    path.write_text(json.dumps(doc))
    assert cli.main(["validate", str(built.out)]) == 1
    assert "deputies/101.json" in capsys.readouterr().err


def test_meta_fields(built):
    meta = load(built.out, "meta.json")
    assert set(meta) == {"schema_version", "generatedAt", "years", "counts", "sources", "candidacy"}
    assert meta["schema_version"] == 2
    assert meta["generatedAt"] == "2026-09-27T12:00:00Z"
    assert meta["years"] == [2023]
    assert meta["counts"] == {"deputies": 3, "rollCalls": 7, "propositions": 5}
    manifest = json.loads((built.raw / "manifest.json").read_text())
    assert sorted(meta["sources"], key=lambda e: e["file"]) == sorted(manifest, key=lambda e: e["file"])
    assert set(meta["candidacy"]) == {"file", "matched", "ambiguous"}


def test_failed_build_keeps_previous_output(fake, capsys):
    fake.serve(legislature())
    assert build(fake) == 0
    before = _tree(fake.out)
    fake.fail["/api/v2/deputados/102/historico"] = [500]
    assert build(fake, "--refresh") == 2
    assert "/api/v2/deputados/102/historico" in capsys.readouterr().err
    assert _tree(fake.out) == before
    assert sorted(p.name for p in fake.out.parent.iterdir()) == ["out", "raw"]


def test_invalid_output_is_never_published(fake, capsys, monkeypatch):
    fake.serve(legislature())
    assert build(fake) == 0
    before = _tree(fake.out)
    assemble = compute.assemble

    def corrupt(*args):
        records = assemble(*args)
        records["deputies"][0]["participation"]["ratio"] = 0.75
        return records

    monkeypatch.setattr(compute, "assemble", corrupt)
    assert build(fake) == 1
    assert "deputies.json" in capsys.readouterr().err
    assert _tree(fake.out) == before
    assert sorted(p.name for p in fake.out.parent.iterdir()) == ["out", "raw"]


def test_builds_are_byte_identical(fake):
    fake.serve(legislature())
    assert build(fake) == 0
    first = _tree(fake.out)
    assert build(fake) == 0
    assert _tree(fake.out) == first
    for relative, content in first.items():
        text = content.decode()
        assert text == json.dumps(json.loads(text), ensure_ascii=False, sort_keys=True, separators=(",", ":")) + "\n"


def test_output_ordering(built):
    assert [rc["id"] for rc in load(built.out, "roll-calls.json")] == [R4, CY, R3, R2, CZ, CX, R1]
    rows = {
        "votacoes": [{"id": rc, "data": "2023-03-01", "dataHoraRegistro": "2023-03-01T10:00:00", "siglaOrgao": "PLEN",
                      "aprovacao": "1", "descricao": "d"} for rc in ("5-2", "5-1", "5-3")],
        "votacoesVotos": [
            {"idVotacao": rc, "dataHoraVoto": "2023-03-01T10:00:00", "voto": "Sim", "deputado_id": dep,
             "deputado_nome": name, "deputado_siglaPartido": "P", "deputado_siglaUf": "SP",
             "deputado_idLegislatura": "57", "deputado_urlFoto": "https://x/p.jpg"}
            for rc in ("5-2", "5-1", "5-3")
            for dep, name in (("4", "Bruno"), ("3", "Álvaro"), ("1", "Alberto"), ("2", "alberto"))
        ],
    }
    records = compute.assemble(compute.load(lambda kind: rows.get(kind, [])), {}, set(), {}, "2026-09-27T09:00:00")
    assert [rc["id"] for rc in records["roll_calls"]] == ["5-1", "5-2", "5-3"]
    assert [(d["name"], d["id"]) for d in records["deputies"]] == [
        ("Alberto", 1), ("alberto", 2), ("Álvaro", 3), ("Bruno", 4)
    ]


def test_source_urls(built):
    for d in load(built.out, "deputies.json"):
        assert d["sourceUrl"] == f"https://www.camara.leg.br/deputados/{d['id']}"
    for rc in load(built.out, "roll-calls.json"):
        assert rc["sourceUrl"] == f"https://dadosabertos.camara.leg.br/api/v2/votacoes/{rc['id']}"
        assert load(built.out, f"roll-calls/{rc['id']}.json")["sourceUrl"] == rc["sourceUrl"]
    for p in load(built.out, "deputies/101.json")["authored"]:
        assert p["sourceUrl"] == f"https://www.camara.leg.br/propostas-legislativas/{p['id']}"


def test_contract_layout(built):
    ids = [d["id"] for d in load(built.out, "deputies.json")]
    rcs = [rc["id"] for rc in load(built.out, "roll-calls.json")]
    expected = {"meta.json", "deputies.json", "roll-calls.json"}
    expected |= {f"deputies/{i}.json" for i in ids} | {f"roll-calls/{i}.json" for i in rcs}
    assert set(_tree(built.out)) == expected
    assert sorted(p.name for p in schema.SCHEMA_DIR.iterdir()) == sorted(f"{k}.schema.json" for k in KINDS)
