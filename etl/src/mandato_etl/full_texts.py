"""Text of the inteiro teor PDFs, for the AI summaries (door 8). `pypdf` is imported only here (door 11); no OCR."""

import hashlib
from pathlib import Path

import pypdf

EXTRACTOR = f"pypdf {pypdf.__version__}"


def extract(path: Path) -> str:
    reader = pypdf.PdfReader(path)
    return "\n".join(page.extract_text() or "" for page in reader.pages)


def documents(house: str, found: dict[int, tuple[str, Path]], extracted_at: str) -> dict[str, dict]:
    """`full-texts/<id>.json` for each `{propositionId: (sourceUrl, pdf path)}`."""
    return {
        f"full-texts/{prop}.json": {
            "house": house,
            "propositionId": prop,
            "sourceUrl": url,
            "documentSha256": hashlib.sha256(path.read_bytes()).hexdigest(),
            "extractor": EXTRACTOR,
            "extractedAt": extracted_at,
            "text": extract(path),
        }
        for prop, (url, path) in sorted(found.items())
    }
