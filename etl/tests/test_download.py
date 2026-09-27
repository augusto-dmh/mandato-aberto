"""S1 - download and snapshot (C1, C3, C5, C6, C7)."""

import hashlib
import json

import pytest
from conftest import build, legislature

from mandato_etl import __version__
from mandato_etl.sources import camara

FILES_2023 = [f"{n}-2023.csv" for n in camara.YEARLY] + ["deputados.csv"]


def test_empty_cache_downloads_every_source_and_writes_manifest(fake):
    fake.serve(legislature())
    assert build(fake) == 0
    assert sorted(p.rsplit("/", 1)[1] for p in fake.bulk_requests()) == sorted(FILES_2023)
    manifest = json.loads((fake.raw / "manifest.json").read_text())
    assert sorted(e["file"] for e in manifest) == sorted(FILES_2023)
    for entry in manifest:
        assert set(entry) == {"file", "sourceUrl", "sha256", "bytes", "downloadedAt"}
        content = (fake.raw / entry["file"]).read_bytes()
        assert entry["sha256"] == hashlib.sha256(content).hexdigest()
        assert entry["bytes"] == len(content)
        assert entry["sourceUrl"].endswith("/" + entry["file"])
        assert entry["downloadedAt"] == "2026-09-27T12:00:00Z"


def test_cached_files_issue_no_request(fake):
    fake.serve(legislature())
    assert build(fake) == 0
    before = len(fake.bulk_requests())
    assert build(fake) == 0
    assert len(fake.bulk_requests()) == before == 7


def test_refresh_downloads_again(fake):
    fake.serve(legislature())
    assert build(fake) == 0
    assert build(fake, "--refresh") == 0
    assert len(fake.bulk_requests()) == 14


@pytest.mark.parametrize(
    ("answers", "sleeps", "succeeds"),
    [
        ([429, 429, 429, 429], [1, 2, 4], False),
        ([503, 503, 503, 503], [1, 2, 4], False),
        ([503], [1], True),
        ([429, 503], [1, 2], True),
        ([404], [], False),
    ],
    ids=["429-exhausted", "503-exhausted", "503-then-200", "429-503-then-200", "404-not-retried"],
)
def test_retry_schedule(fake, tmp_path, answers, sleeps, succeeds):
    path = fake.bulk_path("votacoes", 2023)
    fake.routes[path] = b"id\n"
    fake.fail[path] = list(answers)
    url = camara.BULK_URL.format(name="votacoes", year=2023)
    if succeeds:
        camara._download(url, tmp_path / "votacoes-2023.csv", camara.datetime.now(camara.UTC))
    else:
        with pytest.raises(camara.DownloadError):
            camara._download(url, tmp_path / "votacoes-2023.csv", camara.datetime.now(camara.UTC))
    assert fake.sleeps == sleeps
    assert len([p for p, _ in fake.requests if p == path]) == min(len(answers) + succeeds, 4)


def test_every_request_sends_user_agent(fake):
    fake.serve(legislature())
    assert build(fake) == 0
    expected = f"mandato-aberto-etl/{__version__} (+https://github.com/augusto-dmh/mandato-aberto)"
    assert {p.split("?")[0].split("/")[1] for p, _ in fake.requests} == {"arquivos", "api"}
    assert {ua for _, ua in fake.requests} == {expected}


def _histories(fake, ids):
    for dep in ids:
        fake.routes[f"/api/v2/deputados/{dep}/historico"] = json.dumps({"dados": []}).encode()


def test_history_concurrency_is_capped_at_4(fake):
    ids = [str(i) for i in range(1, 11)]
    _histories(fake, ids)
    fake.delay = 0.2
    camara.histories(ids, fake.raw, refresh=False)
    assert fake.max_in_flight == 4
    assert sorted(p.name for p in (fake.raw / "historico").iterdir()) == sorted(f"{i}.json" for i in ids)


def test_history_is_cached_per_deputy(fake):
    ids = [str(i) for i in range(1, 11)]
    _histories(fake, ids)
    camara.histories(ids, fake.raw, refresh=False)
    first = len(fake.requests)
    camara.histories(ids, fake.raw, refresh=False)
    assert first == 10
    assert len(fake.requests) == first
