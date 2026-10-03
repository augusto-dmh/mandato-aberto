"""contract-v3 door 8 and door 11 - full texts for AI summaries (C54-C57)."""

import hashlib
import json
import re
import tomllib
from pathlib import Path

import pypdf
from v3data import FULL_TEXT_SENTENCE, api_requests, build3, indicators, load3, out3, pdf, serve

ETL = Path(__file__).resolve().parents[1]


def test_full_text_for_each_target(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    names = sorted(p.name for p in (out3(fake) / "full-texts").iterdir())
    assert names == ["8001.json"]
    doc = load3(fake, "full-texts/8001.json")
    assert set(doc) == {"house", "propositionId", "sourceUrl", "documentSha256", "extractor", "extractedAt", "text"}
    assert (doc["house"], doc["propositionId"]) == ("camara", 8001)
    assert doc["sourceUrl"] == f"{fake.base}/inteiro-teor/8001.pdf"
    assert doc["documentSha256"] == hashlib.sha256(pdf(FULL_TEXT_SENTENCE)).hexdigest()
    assert doc["extractor"] == f"pypdf {pypdf.__version__}"
    assert doc["extractedAt"] == "2026-09-27T12:00:00Z"
    assert FULL_TEXT_SENTENCE in doc["text"]
    requested = api_requests(fake)
    assert "/api/v2/proposicoes/8001" in requested and "/api/v2/proposicoes/8002" in requested
    assert "/api/v2/proposicoes/8003" not in requested and "/api/v2/proposicoes/8004" not in requested


def test_full_text_sources_are_cached(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    manifest = {e["file"]: e for e in json.loads((fake.raw / "manifest.json").read_text())}
    for name in ("proposicao/8001.json", "proposicao/8002.json", "inteiro-teor/8001.pdf"):
        assert manifest[name]["sha256"] == hashlib.sha256((fake.raw / name).read_bytes()).hexdigest()
    assert manifest["inteiro-teor/8001.pdf"]["sourceUrl"] == f"{fake.base}/inteiro-teor/8001.pdf"
    sources = {s["file"] for s in load3(fake, "meta.json")["sources"]}
    assert {"proposicao/8001.json", "proposicao/8002.json", "inteiro-teor/8001.pdf"} <= sources
    fake.requests.clear()
    assert build3(fake, "--quiet") == 0
    assert [p for p in api_requests(fake) if "proposicoes" in p or "inteiro-teor" in p] == []


def test_roll_call_doc_carries_descriptions(fake):
    serve(fake, indicators())
    assert build3(fake, "--quiet") == 0
    f1 = load3(fake, "roll-calls/300-3.json")
    assert f1["openingDescription"] == "Votação em turno único."
    assert f1["lastPresentationDescription"] == "Apresentação do Projeto de Lei nº 1/2023"
    p2 = load3(fake, "roll-calls/300-2.json")
    assert (p2["openingDescription"], p2["lastPresentationDescription"]) == (None, None)


def test_pypdf_is_pinned_outside_runtime_dependencies():
    project = tomllib.loads((ETL / "pyproject.toml").read_text())
    locked = [p for p in tomllib.loads((ETL / "uv.lock").read_text())["package"] if p["name"] == "pypdf"]
    assert len(locked) == 1
    assert project["dependency-groups"]["full-texts"] == [f"pypdf=={locked[0]['version']}"]
    assert project["tool"]["uv"]["default-groups"] == ["dev", "full-texts"]
    assert project["project"]["dependencies"] == []
    importers = [p.name for p in (ETL / "src").rglob("*.py") if re.search(r"^\s*(import|from)\s+pypdf", p.read_text(), re.M)]
    assert importers == ["full_texts.py"]
