"""etl-senado S1 - download and snapshot, and the Senate allowlist (C1-C6, C43)."""

import hashlib
import json

import senado_data as sd

from mandato_etl import readers
from mandato_etl.sources import camara

LISTS_2027 = [
    "/votacao?dataInicio=2025-01-01&dataFim=2025-12-31",
    "/votacao?dataInicio=2027-01-01&dataFim=2027-01-31",
    "/votacao?dataInicio=2027-02-01&dataFim=2027-12-31",
    "/plenario/votacao/orientacaoBancada/20250101/20251231",
    "/plenario/votacao/orientacaoBancada/20270101/20270131",
    "/plenario/votacao/orientacaoBancada/20270201/20271231",
    "/senador/lista/legislatura/57?exercicio=S",
    "/senador/lista/legislatura/58?exercicio=S",
    "/senador/lista/atual",
]
AUTHORS_2027 = [f"/processo?codigoParlamentarAutor={code}&dataInicioApresentacao=2023-02-01"
                for code in ("4605", "6358", "9201", "9202")]


def test_first_build_downloads_each_source_once(sen, monkeypatch):
    accepts = []
    real_get = camara._get

    def spy(url, consume, accept=None):
        accepts.append((url, accept))
        return real_get(url, consume, accept)

    monkeypatch.setattr(camara, "_get", spy)
    sd.pin(monkeypatch, sd.CLOCK_2027)
    sd.serve(sen, sd.members_dataset())
    assert sd.build(sen, "--quiet", years=("2025", "2027")) == 0
    assert sorted(sd.requests(sen)) == sorted(LISTS_2027 + AUTHORS_2027)
    assert len(accepts) == len(LISTS_2027 + AUTHORS_2027)
    assert {accept for _, accept in accepts} == {"application/json"}
    agents = {agent for _, agent in sen.requests}
    assert agents == {camara.USER_AGENT}
    manifest = json.loads((sen.raw / "manifest.json").read_text())
    assert len(manifest) == len(LISTS_2027 + AUTHORS_2027)
    for entry in manifest:
        assert set(entry) == {"file", "sourceUrl", "sha256", "bytes", "downloadedAt"}
        stored = (sen.raw / entry["file"]).read_bytes()
        assert entry["sha256"] == hashlib.sha256(stored).hexdigest(), entry["file"]
        assert entry["bytes"] == len(stored)
        assert entry["downloadedAt"] == "2027-03-01T12:00:00Z"
    urls = {e["sourceUrl"].removeprefix(sen.base + sd.PREFIX) for e in manifest}
    assert urls == set(LISTS_2027 + AUTHORS_2027)


def test_cache_hit_issues_no_request(sen):
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet") == 0
    first = sd.requests(sen)
    before = {p.relative_to(sd.out(sen)).as_posix(): p.read_bytes() for p in sd.out(sen).rglob("*") if p.is_file()}
    sen.requests.clear()
    assert sd.build(sen, "--quiet") == 0
    assert sen.requests == []
    after = {p.relative_to(sd.out(sen)).as_posix(): p.read_bytes() for p in sd.out(sen).rglob("*") if p.is_file()}
    assert after == before
    assert sd.build(sen, "--quiet", "--refresh") == 0
    assert sorted(sd.requests(sen)) == sorted(first)
    assert len(first) == 4 + 5 + 3  # votacao, orientacao, list 57, atual; 5 authorship lists; 3 details


def test_download_failure_exits_2_and_keeps_output(sen, capsys):
    sd.serve(sen, sd.indicators())
    assert sd.build(sen, "--quiet") == 0
    before = {p.relative_to(sd.out(sen)).as_posix(): p.read_bytes() for p in sd.out(sen).rglob("*") if p.is_file()}
    path = sd.PREFIX + "/votacao?dataInicio=2025-01-01&dataFim=2025-12-31"
    sen.fail[path] = [503, 503, 503, 503]
    sen.sleeps.clear()
    capsys.readouterr()
    assert sd.build(sen, "--quiet", "--refresh") == 2
    err = capsys.readouterr().err
    assert sen.base + path in err
    assert sen.sleeps == [1, 2, 4]
    assert list(sen.raw.rglob("*.part")) == []
    after = {p.relative_to(sd.out(sen)).as_posix(): p.read_bytes() for p in sd.out(sen).rglob("*") if p.is_file()}
    assert after == before


def test_retry_then_success(sen):
    sd.serve(sen, sd.indicators())
    sen.fail[sd.PREFIX + "/votacao?dataInicio=2025-01-01&dataFim=2025-12-31"] = [503]
    assert sd.build(sen, "--quiet") == 0
    assert sen.sleeps == [1]


def test_at_most_four_concurrent_requests(sen, monkeypatch):
    # A client timeout would retry while the server still holds the first request; keep it out of the count.
    monkeypatch.setattr(camara, "TIMEOUT", 10)
    sd.serve(sen, sd.indicators())
    sen.delay = 0.05
    assert sd.build(sen, "--quiet") == 0
    paths = sd.requests(sen)
    assert len(paths) == len(set(paths))
    assert sum(p.startswith("/processo?") for p in paths) == 5
    assert sum(p.startswith("/processo/") for p in paths) == 3
    assert sen.max_in_flight <= 4


def test_deprecation_header_warns_and_continues(sen, capsys):
    sd.serve(sen, sd.indicators())
    path = sd.PREFIX + "/senador/lista/atual"
    sen.headers[path] = {"Deprecation": "Tue, 18 Mar 2025", "Sunset": "Sun, 01 Feb 2026"}
    assert sd.build(sen, "--quiet") == 0
    lines = [line for line in capsys.readouterr().err.splitlines() if sen.base + path in line]
    assert len(lines) == 1
    assert "warning" in lines[0] and "Sun, 01 Feb 2026" in lines[0]


def test_bad_envelope_exits_1_votacao(sen, capsys):
    routes = sd.indicators()
    routes["/votacao?dataInicio=2025-01-01&dataFim=2025-12-31"] = {}
    sd.serve(sen, routes)
    assert sd.build(sen, "--quiet") == 1
    assert "senado/votacao-57-2025.json" in capsys.readouterr().err
    assert not sd.out(sen).exists()


def test_bad_envelope_exits_1_legislature_list(sen, capsys):
    routes = sd.indicators()
    routes["/senador/lista/legislatura/57?exercicio=S"] = {"ListaParlamentarLegislatura": {"Parlamentares": {}}}
    sd.serve(sen, routes)
    assert sd.build(sen, "--quiet") == 1
    assert "senado/legislatura-57.json" in capsys.readouterr().err
    assert not sd.out(sen).exists()


DOOR_1_ROLL_CALL = {
    "codigoSessaoVotacao", "sequencialVotacao", "codigoSessao", "dataSessao", "descricaoVotacao", "idProcesso",
    "codigoMateria", "sigla", "numero", "ano", "ementa", "resultadoVotacao", "votacaoSecreta", "totalVotosSim",
    "totalVotosNao", "totalVotosAbstencao",
}


def test_allowlist_projection(tmp_path):
    path = tmp_path / "votacao.json"
    path.write_text((sd.RECORDED / "votacao-2025.json").read_text())
    records = readers.read_senado("senado-votacao", path)
    assert records
    for r in records:
        assert set(r) == DOOR_1_ROLL_CALL | {"votos"}
        for v in r["votos"]:
            assert set(v) == {"codigoParlamentar", "nomeParlamentar", "siglaPartidoParlamentar",
                              "siglaUFParlamentar", "siglaVotoParlamentar"}
    assert "senado-votacao" in readers.SENADO_ALLOWLIST
    assert not any(kind.startswith("senado") for kind in readers.ALLOWLIST)
