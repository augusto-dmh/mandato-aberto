"""etl-presidencia S2 - the Congress source, its raw cache and the house-directory gate (C8-C13, C60)."""

import hashlib
import json
import threading
import time
import urllib.request

import presidencia_data as pd
import pytest

from mandato_etl import cli
from mandato_etl.sources import camara, congresso

DEVICES = [f"/plenario/resultado/veto/dispositivo/{c}" for c in ("43265", "43825", "43828", "45468", "46051")]
OTHERS = [
    "/processo?sigla=MPV&ano=2023", "/processo?sigla=MPV&ano=2025", "/processo?sigla=MPV&ano=2026",
    "/materia/vetos/2023", "/materia/vetos/2025", "/materia/vetos/2026",
    *(f"/plenario/resultado/veto/materia/{m}" for m in ("158326", "161861", "166980", "169775", "172342")),
    "/processo?sigla=PL&numero=3626&ano=2023", "/processo?sigla=PLP&numero=93&ano=2023",
    "/processo?sigla=PL&numero=6233&ano=2023", "/processo?sigla=PL&numero=1084&ano=2023",
    "/processo?sigla=PL&numero=9101&ano=2023", "/processo?sigla=PL&numero=9102&ano=2025",
    "/processo?sigla=PEC&numero=9103&ano=2026",
]


def opened(monkeypatch) -> list:
    """Every request passed to `urllib.request.urlopen`, as `(url, headers)`."""
    seen, real = [], urllib.request.urlopen

    def spy(request, *args, **kwargs):
        url = request.full_url if isinstance(request, urllib.request.Request) else request
        headers = dict(request.header_items()) if isinstance(request, urllib.request.Request) else {}
        seen.append((url, headers))
        return real(request, *args, **kwargs)

    monkeypatch.setattr(urllib.request, "urlopen", spy)
    return seen


def test_first_build_requests_each_source_once(con, monkeypatch):
    seen = opened(monkeypatch)
    data = pd.recorded()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    requested = pd.congress_requests(con)
    assert sorted(requested) == sorted(OTHERS + DEVICES)
    assert len(requested) == 23
    congress = [(u, h) for u, h in seen if "/dadosabertos/" in u]
    assert len(congress) == 23
    for _, headers in congress:
        assert headers["Accept"] == "application/json"
        assert headers["User-agent"] == camara.USER_AGENT
    manifest = {e["file"]: e for e in json.loads((con.raw / "manifest.json").read_text())}
    stored = sorted(p.relative_to(con.raw).as_posix() for p in (con.raw / "congresso").iterdir())
    assert len(stored) == 23
    for relative in stored:
        entry = manifest[relative]
        body = (con.raw / relative).read_bytes()
        assert entry["sha256"] == hashlib.sha256(body).hexdigest()
        assert entry["bytes"] == len(body)
        assert set(entry) == {"file", "sourceUrl", "sha256", "bytes", "downloadedAt"}
        assert entry["sourceUrl"].startswith(con.base + "/dadosabertos/")


def test_cache_reuses_only_decided_devices(con):
    data = pd.recorded()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    first = pd.snapshot(pd.out(con))
    con.requests.clear()
    assert pd.build4(con, data, "--quiet") == 0
    assert sorted(pd.congress_requests(con)) == sorted(OTHERS)
    assert pd.snapshot(pd.out(con)) == first
    con.requests.clear()
    assert pd.build4(con, data, "--quiet", "--refresh") == 0
    assert sorted(pd.congress_requests(con)) == sorted(OTHERS + DEVICES)


def test_at_most_two_requests_per_second(con, monkeypatch):
    monkeypatch.setattr(congresso, "MIN_INTERVAL", 0.5)
    arrivals, real = [], con.server.RequestHandlerClass.do_GET

    def timed(handler):
        arrivals.append(time.monotonic())
        return real(handler)

    monkeypatch.setattr(con.server.RequestHandlerClass, "do_GET", timed)
    for i in range(3):
        con.routes[f"/dadosabertos/x{i}"] = b"[]"
    items = [(f"congresso/x{i}.json", f"{con.base}/dadosabertos/x{i}") for i in range(3)]
    congresso.fetch(con.raw, items, set(), pd.CLOCK_2027)
    assert len(arrivals) == 3
    assert all(b - a >= 0.45 for a, b in zip(arrivals, arrivals[1:]))
    assert con.max_in_flight == 1


def test_download_failure_exits_2_and_keeps_output(con, capsys):
    data = pd.recorded()
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    before = pd.snapshot(pd.out(con))
    con.sleeps.clear()
    con.fail["/dadosabertos/materia/vetos/2023"] = [503, 503, 503, 503]
    assert pd.build4(con, data, "--quiet") == 2
    assert con.base + "/dadosabertos/materia/vetos/2023" in capsys.readouterr().err
    assert con.sleeps == [1, 2, 4]
    assert not list((con.raw / "congresso").glob("*.part"))
    assert pd.snapshot(pd.out(con)) == before


class _Body:
    """Serves `bodies` in order on one path, then the real document."""

    def __init__(self, fake, path, bodies):
        self.bodies, self.lock = list(bodies), threading.Lock()
        real = fake.routes[path]
        handler = fake.server.RequestHandlerClass
        original = handler.do_GET
        outer = self

        def do_get(h):
            if h.path == path:
                with outer.lock:
                    body = outer.bodies.pop(0) if outer.bodies else real
                if isinstance(body, int):
                    h.send_error(body)
                    return
                h.send_response(200)
                h.send_header("Content-Length", str(len(body)))
                h.end_headers()
                h.wfile.write(body)
                return
            return original(h)

        self.restore = lambda: setattr(handler, "do_GET", original)
        handler.do_GET = do_get


@pytest.mark.parametrize("bodies, code, sleeps", [
    ([b""], 0, [1]), ([b"<html>"], 0, [1]), ([429], 0, [1]),
    ([b"<html>"] * 4, 2, [1, 2, 4]),
], ids=["empty-then-json", "html-then-json", "429-then-json", "html-four-times"])
def test_bad_body_is_retried(con, capsys, bodies, code, sleeps):
    data = pd.recorded()
    pd.serve(con, data)
    path = "/dadosabertos/materia/vetos/2023"
    body = _Body(con, path, bodies)
    try:
        con.sleeps.clear()
        assert pd.build4(con, data, "--quiet") == code
    finally:
        body.restore()
    assert con.sleeps == sleeps
    if code == 2:
        assert con.base + path in capsys.readouterr().err


def test_no_planalto_request(con, monkeypatch):
    seen = opened(monkeypatch)
    data = pd.recorded()
    assert "planalto.gov.br" in json.dumps(data["congress"]["/materia/vetos/2023"])
    pd.serve(con, data)
    assert pd.build4(con, data, "--quiet") == 0
    assert seen
    assert all(url.startswith(con.base) for url, _ in seen)
    assert not any("planalto.gov.br" in url for url, _ in seen)


def _bad_uf(path):
    doc = json.loads((path / "members.json").read_text())
    doc[0]["uf"] = "São Paulo"
    (path / "members.json").write_text(json.dumps(doc))


def _v3_meta(path):
    doc = json.loads((path / "meta.json").read_text())
    (path / "meta.json").write_text(json.dumps({**doc, "schema_version": 3}))


@pytest.mark.parametrize("house, spoil, named", [
    ("camara", lambda p: __import__("shutil").rmtree(p), ["camara"]),
    ("senado", _bad_uf, ["senado", "members.json"]),
    ("camara", _v3_meta, ["camara"]),
], ids=["camara-missing", "senado-invalid", "camara-v3"])
def test_house_directories_required(con, capsys, house, spoil, named):
    data = pd.recorded()
    pd.serve(con, data)
    spoil(pd.v4(con) / house)
    assert pd.build4(con, data, "--quiet") == 1
    err = capsys.readouterr().err
    assert all(word in err for word in named)
    assert pd.congress_requests(con) == []
    assert not pd.out(con).exists()


def test_house_directories_are_siblings_of_out(con, tmp_path, monkeypatch):
    data = pd.recorded()
    pd.serve(con, data, houses=False)
    other = tmp_path / "elsewhere"
    for house, (members, roll_calls) in data["houses"].items():
        pd.house_dir(other / house, house, members, roll_calls)
    argv = ["build", "--contract", "4", "--house", "presidencia", "--years", *data["years"], "--quiet"]
    assert cli.main([*argv, "--out", str(other / "presidencia")]) == 0
    assert (other / "presidencia" / "meta.json").is_file()
    monkeypatch.setattr(cli, "V4_DIR", other)
    assert cli.main(argv) == 0
    assert not (pd.v4(con)).exists()
