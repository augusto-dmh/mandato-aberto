"""Downloads the Congress open-data responses for the presidency build (door 7).

Same host as `sources.senado`, one request at a time and at most two per second. A response with
an empty or non-JSON body is retried like a 429 or 503 (five of 1,201 device calls came back empty
and succeeded on retry, research 09). Every response is kept verbatim under `data/raw/congresso/`
with a manifest entry (AD-005); the Congress responses carry no personal field beyond name, party
and UF. Planalto is never called.
"""

import http.client
import json
import time
import urllib.error
import urllib.request
from datetime import UTC, datetime
from pathlib import Path

from mandato_etl.sources import camara

API_URL = "https://legis.senado.leg.br/dadosabertos"
MIN_INTERVAL = 0.5  # seconds between request starts: at most 2 per second
DIR = "congresso"

monotonic = time.monotonic
_last = [float("-inf")]


def _throttle() -> None:
    wait = _last[0] + MIN_INTERVAL - monotonic()
    if wait > 0:
        time.sleep(wait)
    _last[0] = monotonic()


def _get(url: str) -> bytes:
    """The body of `url` once it is a JSON document, retrying 429, 503, network errors and bad bodies."""
    request = urllib.request.Request(url, headers={"User-Agent": camara.USER_AGENT, "Accept": "application/json"})
    for delay in (*camara.RETRY_DELAYS, None):
        _throttle()
        try:
            with urllib.request.urlopen(request, timeout=camara.TIMEOUT) as response:
                body = response.read()
            try:
                json.loads(body)
                return body
            except (UnicodeDecodeError, json.JSONDecodeError):
                reason, retry = "empty or non-JSON body", True
        except urllib.error.HTTPError as e:
            reason, retry = f"HTTP {e.code}", e.code in camara.RETRY_STATUSES
        except (urllib.error.URLError, TimeoutError, ConnectionError, http.client.HTTPException) as e:
            reason, retry = str(getattr(e, "reason", e)) or "timeout", True
        if not retry or delay is None:
            raise camara.DownloadError(url, reason)
        camara.sleep(delay)


def fetch(raw: Path, items: list[tuple[str, str]], reuse: set[str], now: datetime) -> list[dict]:
    """Makes sure each `(relative path, url)` is in `raw`; a path in `reuse` is downloaded only when missing.

    Every other path is downloaded again. Returns the manifest entries of `items`, in order.
    """
    (raw / DIR).mkdir(parents=True, exist_ok=True)
    for stale in (raw / DIR).glob("*.part"):
        stale.unlink()
    fetched = []
    for relative, url in items:
        dest = raw / relative
        if relative in reuse and dest.exists():
            fetched.append(False)
            continue
        part = dest.with_name(dest.name + ".part")
        part.write_bytes(_get(url))
        part.replace(dest)
        fetched.append(True)
    manifest_path = raw / "manifest.json"
    manifest = {e["file"]: e for e in json.loads(manifest_path.read_text())} if manifest_path.exists() else {}
    for (relative, url), new in zip(items, fetched):
        dest = raw / relative
        if new or relative not in manifest:
            at = camara._iso(now) if new else camara._iso(datetime.fromtimestamp(dest.stat().st_mtime, UTC))
            manifest[relative] = camara._entry(dest, url, at, relative)
    if items:
        camara._write_manifest(manifest_path, manifest)
    return [manifest[relative] for relative, _ in items]


def read(raw: Path, relative: str):
    """The cached document; `SourceLayoutError` (exit 1) naming the file when it is not JSON."""
    from mandato_etl.readers import SourceLayoutError

    try:
        return json.loads((raw / relative).read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as e:
        raise SourceLayoutError(f"{relative}: not a JSON document ({e})") from None


def mp_list(year: int) -> tuple[str, str]:
    return f"{DIR}/processo-mpv-{year}.json", f"{API_URL}/processo?sigla=MPV&ano={year}"


def veto_list(year: int) -> tuple[str, str]:
    return f"{DIR}/vetos-{year}.json", f"{API_URL}/materia/vetos/{year}"


def veto_result(materia: str) -> tuple[str, str]:
    return f"{DIR}/veto-{materia}.json", f"{API_URL}/plenario/resultado/veto/materia/{materia}"


def device_votes(codigo: str) -> tuple[str, str]:
    return f"{DIR}/dispositivo-{codigo}.json", f"{API_URL}/plenario/resultado/veto/dispositivo/{codigo}"


def bill_process(sigla: str, number: int, year: int) -> tuple[str, str]:
    return (f"{DIR}/processo-{sigla.lower()}-{number}-{year}.json",
            f"{API_URL}/processo?sigla={sigla}&numero={number}&ano={year}")
