"""etl-presidencia S7 - shape, provenance, privacy and determinism of the presidency directory (C49-C56)."""

import json
import os
import shutil
import urllib.request
from datetime import UTC, datetime
from pathlib import Path

import jsonschema
import presidencia_data as pd
import pytest

from mandato_etl import cli, schema
from mandato_etl.sources import camara, congresso

CLOCKS = {"recorded": datetime(2026, 9, 27, 12, tzinfo=UTC), "hand": datetime(2026, 9, 27, 12, tzinfo=UTC),
          "terms": pd.CLOCK_2027}
FIXTURE = Path(__file__).parent / "fixtures" / "v4" / "presidencia"


def build(con, monkeypatch, name, *args):
    monkeypatch.setattr(cli, "now", lambda: CLOCKS[name])
    data = getattr(pd, name)()
    pd.serve(con, data)
    return pd.build4(con, data, "--quiet", *args)


@pytest.mark.parametrize("name", ["recorded", "hand", "terms"])
def test_every_file_passes_its_schema(con, monkeypatch, name):
    assert build(con, monkeypatch, name) == 0
    out = pd.out(con)
    for path in sorted(out.rglob("*.json")):
        relative = path.relative_to(out).as_posix()
        kind = schema.kind_of(relative, 4, "presidencia")
        assert kind is not None, relative
        spec = schema.load(kind, 4)
        doc = json.loads(path.read_text())
        assert schema.first_error(doc, spec) is None, relative
        assert jsonschema.Draft202012Validator(spec).is_valid(doc), relative
    assert cli.main(["validate", str(out)]) == 0


def test_layout(con, monkeypatch):
    assert build(con, monkeypatch, "recorded") == 0
    files = sorted(p.relative_to(pd.out(con)).as_posix() for p in pd.out(con).rglob("*") if p.is_file())
    assert files == sorted(["meta.json", "acts.json", "status-rules.json", "joint-roll-calls.json",
                            "member-veto-counts.json",
                            *(f"joint-roll-calls/{i}.json"
                              for i in ("17.23.001", "49.23.001", "49.23.004", "03.25.004", "29.25.001"))])


def _bad_identifier(doc):
    for d in doc["ResultadoVetoMateriaCN"]["Veto"]["Dispositivos"]["Dispositivo"]:
        if d["Identificador"] == "49.23.001":
            d["Identificador"] = "49.23.001/A"


def test_schema_failure_keeps_previous_output(con, monkeypatch, capsys):
    assert build(con, monkeypatch, "recorded") == 0
    before = pd.snapshot(pd.out(con))
    pd.serve(con, pd.patch(pd.recorded(), "/plenario/resultado/veto/materia/161861", _bad_identifier), houses=False)
    capsys.readouterr()
    assert pd.build4(con, pd.recorded(), "--quiet") == 1
    err = capsys.readouterr().err
    assert "joint-roll-calls.json: $[" in err and "'49.23.001/A' does not match" in err
    assert pd.snapshot(pd.out(con)) == before


def test_meta(con, monkeypatch):
    assert build(con, monkeypatch, "recorded") == 0
    meta = pd.load(con, "meta.json")
    assert set(meta) == {"schema_version", "scope", "generatedAt", "terms", "coverage", "statusRules", "sources"}
    assert (meta["schema_version"], meta["scope"], meta["generatedAt"]) == (4, "presidencia", "2026-09-27T12:00:00Z")
    assert meta["statusRules"] == {"version": 1}
    coverage = {k: v for k, v in meta["coverage"].items() if k != "terms"}
    assert coverage == {"unmatchedVotes": {"camara": 0, "senado": 0}, "excludedBeforeFirstTerm": 1,
                        "missingCamaraStage": 1, "approvedWithoutLaw": 0, "jointRollCallsWithoutVotes": 1}
    assert set(meta["coverage"]) == {"terms", *coverage}
    manifest = {e["file"]: e for e in json.loads((con.raw / "manifest.json").read_text())}
    congress = sorted(f for f in manifest if f.startswith("congresso/"))
    assert len(congress) == 23
    camara_files = [f"{kind}-{y}.csv" for kind in ("proposicoes", "proposicoesAutores") for y in (2023, 2025, 2026)]
    assert meta["sources"] == [manifest[f] for f in sorted([*congress, *camara_files])]


def test_source_urls(con, monkeypatch):
    for name in ("recorded", "hand"):
        assert build(con, monkeypatch, name, "--refresh") == 0
        mp_codes = {f"mpv-{m['identificacao'][4:].replace('/', '-')}": m["codigoMateria"]
                    for path, doc in getattr(pd, name)()["congress"].items() if "sigla=MPV" in path for m in doc}
        for a in pd.load(con, "acts.json"):
            if a["kind"] == "provisionalMeasure":
                assert a["sourceUrl"] == ("https://www.congressonacional.leg.br/materias/medidas-provisorias/-/mpv/"
                                          f"{mp_codes[a['id']]}")
            elif a["kind"] == "veto":
                assert a["sourceUrl"].startswith("https://www.congressonacional.leg.br/materias/vetos/-/veto/detalhe/")
            else:
                assert a["sourceUrl"] == f"https://www.camara.leg.br/propostas-legislativas/{a['stages'][0]['propositionId']}"
        for j in pd.load(con, "joint-roll-calls.json"):
            assert j["sourceUrl"].startswith("https://")
        if name == "recorded":
            assert pd.act(con, "mpv-1154-2023")["sourceUrl"].endswith("/mpv/155651")
            assert pd.act(con, "vet-49-2023")["sourceUrl"].endswith("/veto/detalhe/16269")
            assert pd.act(con, "pl-3626-2023")["sourceUrl"] == "https://www.camara.leg.br/propostas-legislativas/2374400"


def _keys(doc):
    if isinstance(doc, dict):
        for k, v in doc.items():
            yield k
            yield from _keys(v)
    elif isinstance(doc, list):
        for v in doc:
            yield from _keys(v)


def test_no_cpf_and_only_allowed_vote_fields(con, monkeypatch):
    assert build(con, monkeypatch, "recorded") == 0
    for path in [*pd.out(con).rglob("*.json"), *(con.raw / "congresso").rglob("*.json")]:
        assert not any("cpf" in k.casefold() for k in _keys(json.loads(path.read_text()))), path
    votes = [v for p in (pd.out(con) / "joint-roll-calls").glob("*.json") for v in json.loads(p.read_text())["votes"]]
    assert votes
    assert all(set(v) == {"house", "memberId", "name", "party", "uf", "official", "position"} for v in votes)


def test_build_is_deterministic(con, monkeypatch):
    assert build(con, monkeypatch, "recorded") == 0
    first = pd.snapshot(pd.out(con))
    assert build(con, monkeypatch, "recorded") == 0
    assert pd.snapshot(pd.out(con)) == first
    acts = pd.load(con, "acts.json")
    assert [a["id"] for a in acts[:3]] == ["mpv-1350-2026", "vet-3-2026", "pec-9103-2026"]
    keys = [(a["issuedAt"], a["id"]) for a in acts]
    assert keys == sorted(sorted(keys, key=lambda k: k[1]), key=lambda k: k[0], reverse=True)
    joint = [j["id"] for j in pd.load(con, "joint-roll-calls.json")]
    assert joint == ["03.26.000", "29.25.001", "03.25.004", "17.23.001", "49.23.001", "49.23.004"]
    counts = [(r["house"], r["memberId"], r["legislature"]) for r in pd.load(con, "member-veto-counts.json")]
    assert counts == sorted(counts)


def test_log_line_per_term(con, monkeypatch, capsys):
    monkeypatch.setattr(cli, "now", lambda: CLOCKS["recorded"])
    data = pd.recorded()
    pd.serve(con, data)
    assert pd.build4(con, data) == 0
    lines = [line for line in capsys.readouterr().err.splitlines() if line.startswith("presidencia ")]
    assert lines == ["presidencia 2023-2026: 6 MPs, 5 vetoes (8 devices), 7 bills, 6 joint roll calls, 0 unmatched votes"]
    assert pd.build4(con, data, "--quiet") == 0
    assert capsys.readouterr().err == ""
    monkeypatch.setattr(cli, "now", lambda: pd.CLOCK_2027)
    data = pd.terms()
    pd.serve(con, data)
    assert pd.build4(con, data) == 0
    lines = [line for line in capsys.readouterr().err.splitlines() if line.startswith("presidencia ")]
    assert lines == [
        "presidencia 2023-2026: 1 MPs, 0 vetoes (0 devices), 0 bills, 0 joint roll calls, 0 unmatched votes",
        "presidencia 2027-2030: 1 MPs, 0 vetoes (0 devices), 0 bills, 0 joint roll calls, 0 unmatched votes",
    ]


OFFICIAL = {"https://legis.senado.leg.br/dadosabertos": "/dadosabertos",
            "https://dadosabertos.camara.leg.br": ""}


def test_committed_fixture_is_the_recorded_build(con, monkeypatch):
    """The R build with the official base URLs, so its manifest entries do not carry the local port."""
    real = urllib.request.urlopen

    def to_fake(request, *args, **kwargs):
        url = request.full_url
        for official, prefix in OFFICIAL.items():
            if url.startswith(official):
                url = con.base + prefix + url[len(official):]
        return real(urllib.request.Request(url, headers=dict(request.header_items())), *args, **kwargs)

    monkeypatch.setattr(urllib.request, "urlopen", to_fake)
    monkeypatch.setattr(congresso, "API_URL", "https://legis.senado.leg.br/dadosabertos")
    monkeypatch.setattr(camara, "BULK_URL", "https://dadosabertos.camara.leg.br/arquivos/{name}/csv/{name}-{year}.csv")
    monkeypatch.setattr(camara, "DEPUTIES_URL", "https://dadosabertos.camara.leg.br/arquivos/deputados/csv/deputados.csv")
    assert build(con, monkeypatch, "recorded") == 0
    if os.environ.get("PRESIDENCIA_FIXTURE_REGEN"):
        shutil.rmtree(FIXTURE, ignore_errors=True)
        shutil.copytree(pd.out(con), FIXTURE)
    assert pd.snapshot(FIXTURE) == pd.snapshot(pd.out(con))
    assert cli.main(["validate", str(FIXTURE)]) == 0
