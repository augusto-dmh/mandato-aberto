"""contract-v3 S5 and doors 9-10 - shape, provenance, privacy (C40-C49, C52, C53, C58)."""

import copy
import hashlib
import json
from pathlib import Path

import jsonschema
import pytest
from conftest import CPF_IN_DEPUTADOS
from v3data import (
    INDICATOR_VOTES, K, V3_PINNED, build3, classification, indicators, legislatures, load3, out3, pin, serve, snapshot,
)

from mandato_etl import classify, cli, readers, schema

ETL = Path(__file__).resolve().parents[1]
KINDS = ["meta", "members", "roll-calls", "roll-call", "propositions", "classification-rules", "full-text"]


@pytest.fixture
def built_indicators(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    return fake


def test_every_file_passes_its_schema(fake, monkeypatch):
    seen = set()
    for name, data in (("indicators", indicators), ("classification", classification), ("legislatures", legislatures)):
        if name == "legislatures":
            pin(monkeypatch, V3_PINNED)
        fake.routes.clear()
        serve(fake, data())
        assert build3(fake, "--quiet", "--refresh") == 0
        assert cli.main(["validate", str(out3(fake))]) == 0
        for path in sorted(out3(fake).rglob("*.json")):
            relative = path.relative_to(out3(fake)).as_posix()
            kind = schema.kind_of(relative, 3)
            spec = schema.load(kind, 3)
            doc = json.loads(path.read_text())
            assert schema.first_error(doc, spec) is None, relative
            assert jsonschema.Draft202012Validator(spec).is_valid(doc), relative
            seen.add(kind)
    assert seen == set(KINDS)


def test_schema_failure_keeps_previous_output(fake, capsys):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    before = snapshot(out3(fake))
    votes = [(d, rc, v, {"deputado_urlFoto": "http://x/1.jpg"}) if d == "201" else (d, rc, v)
             for d, rc, v in INDICATOR_VOTES]
    serve(fake, indicators(votes=votes))
    assert build3(fake, "--quiet", "--refresh") == 1
    err = capsys.readouterr().err
    assert "members.json" in err and "photoUrl" in err
    assert snapshot(out3(fake)) == before


def test_meta_shape_and_coverage(built_indicators):
    meta = load3(built_indicators, "meta.json")
    assert set(meta) == {"schema_version", "house", "generatedAt", "legislatures", "coverage", "classification", "sources"}
    assert (meta["schema_version"], meta["house"], meta["generatedAt"]) == (3, "camara", "2026-09-27T12:00:00Z")
    assert meta["coverage"] == [{
        "legislature": 57, "through": "2024-03-01", "rollCalls": {"nominal": 6, "secret": 0, "symbolic": 1},
        "unclassified": 0, "members": 3,
    }]
    files = [s["file"] for s in meta["sources"]]
    assert files == sorted(files)
    assert set(files) == {
        *(f"{kind}-2023.csv" for kind in ("votacoes", "votacoesVotos", "votacoesOrientacoes", "votacoesProposicoes",
                                          "proposicoes", "proposicoesAutores")),
        "deputados.csv", "deputados-legislatura-57.json",
        "historico-v3/201.json", "historico-v3/202.json", "historico-v3/203.json",
        "proposicao/8001.json", "proposicao/8002.json", "inteiro-teor/8001.pdf",
    }
    manifest = {e["file"]: e for e in json.loads((built_indicators.raw / "manifest.json").read_text())}
    assert all(s == manifest[s["file"]] for s in meta["sources"])


def test_source_urls(built_indicators):
    for m in load3(built_indicators, "members.json"):
        assert m["sourceUrl"] == f"https://www.camara.leg.br/deputados/{m['id']}"
    for r in load3(built_indicators, "roll-calls.json"):
        assert r["sourceUrl"] == f"https://dadosabertos.camara.leg.br/api/v2/votacoes/{r['id']}"
    for p in load3(built_indicators, "propositions.json"):
        assert p["sourceUrl"] == f"https://www.camara.leg.br/propostas-legislativas/{p['id']}"


def test_house_and_unique_identity(built_indicators):
    for name in ("members.json", "roll-calls.json", "propositions.json", "classification-rules.json"):
        records = load3(built_indicators, name)
        assert records and all(r["house"] == "camara" for r in records), name
        if name != "classification-rules.json":
            assert len({(r["house"], r["id"]) for r in records}) == len(records), name
    for path in (out3(built_indicators) / "full-texts").glob("*.json"):
        assert json.loads(path.read_text())["house"] == "camara"
    for path in (out3(built_indicators) / "roll-calls").glob("*.json"):
        assert json.loads(path.read_text())["house"] == "camara"


def keys_of(doc, found):
    if isinstance(doc, dict):
        found.update(doc)
        for value in doc.values():
            keys_of(value, found)
    elif isinstance(doc, list):
        for value in doc:
            keys_of(value, found)
    return found


def test_no_cpf_anywhere(built_indicators):
    for path in out3(built_indicators).rglob("*"):
        if path.is_file():
            assert CPF_IN_DEPUTADOS.encode() not in path.read_bytes(), path
            if path.suffix == ".json":
                assert not any("cpf" in k.casefold() for k in keys_of(json.loads(path.read_text()), set())), path
    assert not any("cpf" in c.casefold() for columns in readers.ALLOWLIST.values() for c in columns)


def test_build_is_deterministic_and_ordered(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    first = snapshot(out3(fake))
    assert build3(fake, "--quiet") == 0
    assert snapshot(out3(fake)) == first
    assert [m["name"] for m in load3(fake, "members.json")] == ["Ana Souza", "Beto Lima", "Caio Dias"]
    calls = load3(fake, "roll-calls.json")
    assert [(r["date"], r["id"]) for r in calls] == sorted(
        sorted((r["date"], r["id"]) for r in calls), key=lambda x: x[0], reverse=True)
    assert [r["id"] for r in calls][:2] == ["300-6", "300-4"]
    props = load3(fake, "propositions.json")
    dated = [p for p in props if p["presentedAt"] is not None]
    undated = [p for p in props if p["presentedAt"] is None]
    assert props == dated + undated
    assert [p["id"] for p in dated] == [8002, 9005, 9004, 9003, 9002, 9001]
    assert [p["id"] for p in undated] == [8001, 8003, 8004]
    for path in (out3(fake) / "roll-calls").glob("*.json"):
        ids = [v["memberId"] for v in json.loads(path.read_text())["votes"]]
        assert ids == sorted(ids)


def test_log_line_per_legislature(fake, capsys):
    serve(fake, classification())
    assert build3(fake) == 0
    lines = capsys.readouterr().err.splitlines()
    assert "camara 57: 8 roll calls (3 nominal, 2 secret, 3 symbolic), 1 unclassified" in lines
    assert build3(fake, "--quiet") == 0
    assert capsys.readouterr().err == ""


def test_v3_layout(built_indicators):
    out = out3(built_indicators)
    files = {p.relative_to(out).as_posix() for p in out.rglob("*") if p.is_file()}
    calls = load3(built_indicators, "roll-calls.json")
    with_votes = {r["id"] for r in calls if r["ballot"] in ("nominal", "secret")}
    assert with_votes and len(with_votes) < len(calls)
    assert files == {
        "meta.json", "members.json", "roll-calls.json", "propositions.json", "classification-rules.json",
        *(f"roll-calls/{rc}.json" for rc in with_votes), "full-texts/8001.json",
    }
    assert sorted(p.name for p in (ETL / "schema" / "v3").iterdir()) == sorted(f"{k}.schema.json" for k in KINDS)


def corrupt(doc_kind, mutate, out):
    relative = {"members": "members.json", "roll-call": f"roll-calls/{K[1]}.json", "roll-calls": "roll-calls.json"}[doc_kind]
    doc = copy.deepcopy(json.loads((out / relative).read_text()))
    mutate(doc)
    return doc


MUTATIONS = {
    "house-presidencia": ("members", lambda d: d[0].__setitem__("house", "presidencia")),
    "position-other": ("roll-call", lambda d: d["votes"][0].__setitem__("position", "other")),
    "extra-ratio": ("members", lambda d: d[0]["mandates"][0]["participation"]["all"].__setitem__("ratio", 0.5)),
    "ballot-unknown": ("roll-calls", lambda d: d[0].__setitem__("ballot", "unknown")),
    "kind-important": ("roll-calls", lambda d: d[0].__setitem__("kind", "important")),
    "negative-count": ("members", lambda d: d[0]["mandates"][0]["participation"]["all"].__setitem__("count", -1)),
    "memberId-string": ("roll-call", lambda d: d["votes"][0].__setitem__("memberId", "201")),
    "missing-kindRule": ("roll-calls", lambda d: d[0].pop("kindRule")),
}


@pytest.mark.parametrize("mutation", MUTATIONS, ids=list(MUTATIONS))
def test_validators_agree_on_invalid(fake, mutation):
    serve(fake, classification())
    assert build3(fake, "--quiet") == 0
    kind, mutate = MUTATIONS[mutation]
    doc = corrupt(kind, mutate, out3(fake))
    spec = schema.load(kind, 3)
    assert schema.first_error(doc, spec) is not None
    assert not jsonschema.Draft202012Validator(spec).is_valid(doc)


@pytest.mark.parametrize(("value", "valid"), [(None, True), (0, True), (-1, False), ("0", False)])
def test_symbolic_counts_are_nullable(fake, value, valid):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    members = load3(fake, "members.json")
    assert [m["mandates"][0]["symbolicMerit"] for m in members] == [1, 0, 1]
    meta = load3(fake, "meta.json")
    assert meta["coverage"][0]["rollCalls"]["symbolic"] == 1
    members[0]["mandates"][0]["symbolicMerit"] = value
    meta["coverage"][0]["rollCalls"]["symbolic"] = value
    for doc, kind in ((members, "members"), (meta, "meta")):
        spec = schema.load(kind, 3)
        assert (schema.first_error(doc, spec) is None) is valid
        assert jsonschema.Draft202012Validator(spec).is_valid(doc) is valid


def test_sensitive_codes_are_generalised_map():
    assert classify.SENSITIVE_OFFICIAL["camara"] == {}
    for code in ("LS", "LP", "LAP"):
        assert classify.published_official("senado", code) == "Licença"
    assert classify.published_official("senado", "P-NRV") == "P-NRV"
    assert classify.published_official("camara", "Sim") == "Sim"


@pytest.mark.parametrize(("official", "exit_code"), [("LS", 1), ("LP", 1), ("LAP", 1), ("Licença", 0)])
def test_sensitive_codes_are_generalised_in_validation(fake, capsys, official, exit_code):
    serve(fake, classification())
    assert build3(fake, "--quiet") == 0
    path = out3(fake) / "roll-calls" / f"{K[1]}.json"
    doc = json.loads(path.read_text())
    doc["votes"][0] = {**doc["votes"][0], "official": official, "position": "notVoting"}
    path.write_text(json.dumps(doc))
    assert cli.main(["validate", str(out3(fake))]) == exit_code
    if exit_code:
        assert official in capsys.readouterr().err


BASE_ALLOWLIST = {
    "votacoes": ["id", "data", "dataHoraRegistro", "siglaOrgao", "aprovacao", "votosSim", "votosNao", "votosOutros",
                 "descricao"],
    "votacoesProposicoes": ["idVotacao", "proposicao_id", "proposicao_titulo", "proposicao_ementa"],
}


def test_allowlist_additions(built_indicators):
    added = {kind: [c for c in readers.ALLOWLIST[kind] if c not in base] for kind, base in BASE_ALLOWLIST.items()}
    assert added == {
        "votacoes": ["ultimaAberturaVotacao_descricao", "ultimaApresentacaoProposicao_descricao"],
        "votacoesProposicoes": ["proposicao_siglaTipo", "proposicao_numero", "proposicao_ano"],
    }
    assert all(c in readers.ALLOWLIST[k] for k, base in BASE_ALLOWLIST.items() for c in base)
    a1 = load3(built_indicators, "roll-calls/300-5.json")
    assert a1["openingDescription"] == "Votação do DTQ 1: Destaque"
    props = {p["id"]: p for p in load3(built_indicators, "propositions.json")}
    linked = props[8002]
    assert (linked["type"], linked["number"], linked["year"]) == ("PEC", 2, 2024)
    # 8001 is absent from the `proposicoes` bulk file, so its number and year can only come from the roll-call link
    link_only = props[8001]
    assert (link_only["type"], link_only["number"], link_only["year"]) == ("PL", 1, 2023)


def test_manifest_hashes_match_stored_files(built_indicators):
    for entry in json.loads((built_indicators.raw / "manifest.json").read_text()):
        stored = (built_indicators.raw / entry["file"]).read_bytes()
        assert entry["sha256"] == hashlib.sha256(stored).hexdigest(), entry["file"]


def test_symbolic_counts_are_nullable_in_senate_fixture():
    senado = ETL / "tests" / "fixtures" / "v3" / "senado"
    members = json.loads((senado / "members.json").read_text())
    assert [m["symbolicMerit"] for member in members for m in member["mandates"]] == [None] * 4
    meta = json.loads((senado / "meta.json").read_text())
    assert [c["rollCalls"]["symbolic"] for c in meta["coverage"]] == [None] * len(meta["coverage"])
    assert cli.main(["validate", str(senado)]) == 0
