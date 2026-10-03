"""Downloads the Senate open-data responses for contract v3, with the raw cache and manifest of `sources.camara`.

Every response is kept verbatim under `data/raw/senado/` (it carries no CPF) and read only through
`readers.read_senado`, which drops every personal field (door 1).
"""

import http.client
import json
from concurrent.futures import ThreadPoolExecutor
from datetime import UTC, datetime
from pathlib import Path

from mandato_etl import readers
from mandato_etl.sources import camara

API_URL = "https://legis.senado.leg.br/dadosabertos"
WORKERS = camara.HISTORY_WORKERS


def _download(url: str, dest: Path, now: datetime, warn) -> None:
    part = dest.with_name(dest.name + ".part")

    def consume(response):
        deprecation, sunset = response.headers.get("Deprecation"), response.headers.get("Sunset")
        if deprecation or sunset:
            warn(f"warning: {url} is deprecated (Deprecation: {deprecation or '-'}; Sunset: {sunset or '-'})")
        size = 0
        with open(part, "wb") as f:
            while chunk := response.read(1 << 20):
                size += f.write(chunk)
        expected = response.headers.get("Content-Length")
        if expected is not None and size != int(expected):
            raise http.client.IncompleteRead(b"", int(expected) - size)

    try:
        camara._get(url, consume, accept="application/json")
    except BaseException:
        part.unlink(missing_ok=True)
        raise
    part.replace(dest)


def fetch(raw: Path, items: list[tuple[str, str]], refresh: bool, now: datetime, warn) -> list[dict]:
    """Makes sure each `(relative path, url)` is cached under `raw`, at most `WORKERS` requests at a time.

    Returns the manifest entries of `items`, in order; the manifest is written once, from this thread.
    """
    raw.mkdir(parents=True, exist_ok=True)
    for stale in (raw / "senado").rglob("*.part") if (raw / "senado").exists() else ():
        stale.unlink()

    def one(item):
        relative, url = item
        dest = raw / relative
        if dest.exists() and not refresh:
            return False
        dest.parent.mkdir(parents=True, exist_ok=True)
        _download(url, dest, now, warn)
        return True

    with ThreadPoolExecutor(max_workers=WORKERS) as pool:
        fetched = list(pool.map(one, items))
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


def read(raw: Path, kind: str, relative: str):
    return readers.read_senado(kind, raw / relative, relative)


def year_ranges(legislatures: dict[int, tuple[str, str]], years: list[int]) -> list[tuple[int, int, str, str]]:
    """`(legislature, year, first day, last day)` for every year of `years` inside each legislature, clamped to it."""
    ranges = []
    for n, (start, end) in sorted(legislatures.items()):
        for year in years:
            first, last = max(f"{year}-01-01", start), min(f"{year}-12-31", end)
            if first <= last:
                ranges.append((n, year, first, last))
    return ranges


def lists(raw: Path, legislatures: dict[int, tuple[str, str]], years: list[int], refresh: bool, now: datetime,
          warn) -> tuple[dict, list[dict]]:
    """Roll calls, orientations and the member lists of each built legislature (AC 1).

    Returns `({"votacao": [relative], "orientacao": [relative], "legislatura": {n: relative}, "atual": relative},
    manifest entries)`.
    """
    items, files = [], {"votacao": [], "orientacao": [], "legislatura": {}, "atual": "senado/atual.json"}
    for n, year, first, last in year_ranges(legislatures, years):
        votacao, orientacao = f"senado/votacao-{n}-{year}.json", f"senado/orientacao-{n}-{year}.json"
        items.append((votacao, f"{API_URL}/votacao?dataInicio={first}&dataFim={last}"))
        items.append((orientacao, f"{API_URL}/plenario/votacao/orientacaoBancada/"
                                  f"{first.replace('-', '')}/{last.replace('-', '')}"))
        files["votacao"].append(votacao)
        files["orientacao"].append(orientacao)
    for n in sorted(legislatures):
        files["legislatura"][n] = f"senado/legislatura-{n}.json"
        items.append((files["legislatura"][n], f"{API_URL}/senador/lista/legislatura/{n}?exercicio=S"))
    items.append((files["atual"], f"{API_URL}/senador/lista/atual"))
    return files, fetch(raw, items, refresh, now, warn)


def authorship(raw: Path, starts: dict[str, str], refresh: bool, now: datetime, warn) -> tuple[dict, list[dict]]:
    """`/processo?codigoParlamentarAutor=<id>&dataInicioApresentacao=<start>` per member; `({id: [process]}, entries)`."""
    items = [
        (f"senado/processos/{member}-{start.replace('-', '')}.json",
         f"{API_URL}/processo?codigoParlamentarAutor={member}&dataInicioApresentacao={start}")
        for member, start in sorted(starts.items(), key=lambda item: int(item[0]))
    ]
    entries = fetch(raw, items, refresh, now, warn)
    found = {member: read(raw, "senado-processos", relative) for member, (relative, _) in zip(
        sorted(starts, key=int), items)}
    return found, entries


def details(raw: Path, ids: list[int], refresh: bool, now: datetime, warn) -> tuple[dict, list[dict]]:
    """`/processo/<id>` for each multi-author process; `({id: {ordem: codigoParlamentar}}, entries)`."""
    items = [(f"senado/processo/{prop}.json", f"{API_URL}/processo/{prop}") for prop in sorted(ids)]
    entries = fetch(raw, items, refresh, now, warn)
    found = {}
    for prop, (relative, _) in zip(sorted(ids), items):
        doc = read(raw, "senado-processo", relative)
        found[prop] = {a.get("ordem"): str(a.get("codigoParlamentar")) for a in doc["autoriaIniciativa"]}
    return found, entries


def vote_set(record: dict) -> frozenset:
    return frozenset((str(v["codigoParlamentar"]), v["siglaVotoParlamentar"]) for v in record["votos"])


def dedupe_twins(records: list[dict]) -> list[dict]:
    """Drops a record without `sequencialVotacao` when its session holds a sequenced one with the same votes (door 4)."""
    sequenced = {}
    for r in records:
        if r["sequencialVotacao"] is not None:
            sequenced.setdefault(r["codigoSessao"], set()).add(vote_set(r))
    return [
        r for r in records
        if r["sequencialVotacao"] is not None or vote_set(r) not in sequenced.get(r["codigoSessao"], set())
    ]
