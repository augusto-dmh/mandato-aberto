"""Downloads the Câmara bulk files and API records, with a local cache and a hashed manifest."""

import csv
import hashlib
import json
import time
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from datetime import UTC, datetime
from pathlib import Path

from mandato_etl import __version__

BULK_URL = "https://dadosabertos.camara.leg.br/arquivos/{name}/csv/{name}-{year}.csv"
DEPUTIES_URL = "https://dadosabertos.camara.leg.br/arquivos/deputados/csv/deputados.csv"
API_URL = "https://dadosabertos.camara.leg.br/api/v2"
YEARLY = (
    "votacoes",
    "votacoesVotos",
    "votacoesOrientacoes",
    "votacoesProposicoes",
    "proposicoes",
    "proposicoesAutores",
)
USER_AGENT = f"mandato-aberto-etl/{__version__} (+https://github.com/augusto-dmh/mandato-aberto)"
RETRY_STATUSES = {429, 503}
RETRY_DELAYS = (1, 2, 4)
TIMEOUT = 300
HISTORY_WORKERS = 4
LEGISLATURE = "57"
# Files whose CPF columns - any header containing "cpf", in any case - are blanked before they are
# kept in the raw cache (AD-003): the CPF is never persisted.
REDACT = {"deputados.csv"}

sleep = time.sleep


class DownloadError(Exception):
    def __init__(self, url: str, reason: str):
        super().__init__(f"{url}: {reason}")
        self.url = url


def _get(url, consume, accept=None):
    """Runs `consume` on the response, retrying 429, 503 and network errors after 1, 2 and 4 s."""
    headers = {"User-Agent": USER_AGENT}
    if accept:
        headers["Accept"] = accept
    request = urllib.request.Request(url, headers=headers)
    for delay in (*RETRY_DELAYS, None):
        try:
            with urllib.request.urlopen(request, timeout=TIMEOUT) as response:
                return consume(response)
        except urllib.error.HTTPError as e:
            reason, retry = f"HTTP {e.code}", e.code in RETRY_STATUSES
        except (urllib.error.URLError, TimeoutError, ConnectionError) as e:
            reason, retry = str(getattr(e, "reason", e)) or "timeout", True
        if not retry or delay is None:
            raise DownloadError(url, reason)
        sleep(delay)


def _iso(moment: datetime) -> str:
    return moment.astimezone(UTC).strftime("%Y-%m-%dT%H:%M:%SZ")


def _entry(path: Path, url: str, downloaded_at: str) -> dict:
    digest, size = hashlib.sha256(), 0
    with open(path, "rb") as f:
        while chunk := f.read(1 << 20):
            digest.update(chunk)
            size += len(chunk)
    return {"file": path.name, "sourceUrl": url, "sha256": digest.hexdigest(), "bytes": size, "downloadedAt": downloaded_at}


def _redact(path: Path) -> bool:
    """Blanks every CPF column of `path` in place, keeping the header; returns whether anything changed."""
    with open(path, encoding="utf-8-sig", newline="") as src:
        rows = csv.reader(src, delimiter=";")
        header = next(rows, [])
        blank = {i for i, name in enumerate(header) if "cpf" in name.casefold()}
        if not any(row[i] for row in rows for i in blank if i < len(row)):
            return False
    tmp = path.with_name(path.name + ".redacted")
    with open(path, encoding="utf-8-sig", newline="") as src, open(tmp, "w", encoding="utf-8-sig", newline="") as dst:
        rows = csv.reader(src, delimiter=";")
        writer = csv.writer(dst, delimiter=";", quoting=csv.QUOTE_ALL, lineterminator="\n")
        writer.writerow(next(rows))
        for row in rows:
            writer.writerow(["" if i in blank else v for i, v in enumerate(row)])
    tmp.replace(path)
    return True


def _download(url: str, dest: Path, now: datetime) -> dict:
    part = dest.with_name(dest.name + ".part")

    def consume(response):
        with open(part, "wb") as f:
            while chunk := response.read(1 << 20):
                f.write(chunk)

    try:
        _get(url, consume)
        if dest.name in REDACT:
            _redact(part)
    except BaseException:
        part.unlink(missing_ok=True)
        part.with_name(part.name + ".redacted").unlink(missing_ok=True)
        raise
    part.replace(dest)
    return _entry(dest, url, _iso(now))


def _write_manifest(path: Path, manifest: dict) -> None:
    path.write_text(json.dumps(sorted(manifest.values(), key=lambda e: e["file"]), indent=2) + "\n")


def sync(raw: Path, years: list[int], refresh: bool, now: datetime, log=lambda msg: None) -> list[dict]:
    """Makes sure every bulk file for `years` is in `raw` and returns its manifest entries."""
    raw.mkdir(parents=True, exist_ok=True)
    # A download killed mid-way leaves these behind, possibly holding a CPF.
    for stale in [*raw.glob("*.part"), *raw.glob("*.redacted")]:
        stale.unlink()
    manifest_path = raw / "manifest.json"
    manifest = {}
    if manifest_path.exists():
        manifest = {e["file"]: e for e in json.loads(manifest_path.read_text())}
    wanted = [(f"{n}-{y}.csv", BULK_URL.format(name=n, year=y)) for y in years for n in YEARLY]
    wanted.append(("deputados.csv", DEPUTIES_URL))
    for name, url in wanted:
        dest = raw / name
        if dest.exists() and not refresh:
            listed = manifest.get(name)
            # A copy placed by hand is dated by its modification time, read before any redaction.
            downloaded_at = listed["downloadedAt"] if listed else _iso(datetime.fromtimestamp(dest.stat().st_mtime, UTC))
            redacted = name in REDACT and _redact(dest)
            if listed is None or redacted:
                manifest[name] = _entry(dest, url, downloaded_at)
                _write_manifest(manifest_path, manifest)
            continue
        log(f"downloading {url}")
        manifest[name] = _download(url, dest, now)
        _write_manifest(manifest_path, manifest)
    return [manifest[name] for name, _ in wanted]


def api(path: str):
    return _get(API_URL + path, json.load, accept="application/json")


def deputies_in_exercise() -> set[str]:
    return {str(d["id"]) for d in api("/deputados?itens=1000")["dados"]}


def history(dep_id: str, raw: Path, refresh: bool) -> list[dict]:
    """Status changes in the 57th legislature, cached under `historico/{id}.json`."""
    dest = raw / "historico" / f"{dep_id}.json"
    if dest.exists() and not refresh:
        return json.loads(dest.read_text())
    entries = [
        {"dataHora": h["dataHora"], "situacao": h["situacao"], "descricaoStatus": h["descricaoStatus"]}
        for h in api(f"/deputados/{dep_id}/historico")["dados"]
        if str(h["idLegislatura"]) == LEGISLATURE and h["situacao"]
    ]
    dest.parent.mkdir(parents=True, exist_ok=True)
    dest.write_text(json.dumps(entries, ensure_ascii=False))
    return entries


def histories(ids, raw: Path, refresh: bool) -> dict[str, list[dict]]:
    ids = list(ids)
    with ThreadPoolExecutor(max_workers=HISTORY_WORKERS) as pool:
        return dict(zip(ids, pool.map(lambda i: history(i, raw, refresh), ids)))
