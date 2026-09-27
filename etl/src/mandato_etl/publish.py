"""Writes the contract files to a temporary directory, validates them, then swaps them in."""

import json
import shutil
import tempfile
from pathlib import Path

from mandato_etl import schema


def dumps(doc) -> str:
    return json.dumps(doc, ensure_ascii=False, sort_keys=True, separators=(",", ":")) + "\n"


def write(out: Path, files: dict[str, object]) -> None:
    """Publishes `files` (relative path -> document) as the whole content of `out`.

    Nothing touches `out` until every document has passed its schema, so a failure leaves the
    previous output as it was.
    """
    out.parent.mkdir(parents=True, exist_ok=True)
    schemas = {}
    tmp = Path(tempfile.mkdtemp(prefix=f".{out.name}-", dir=out.parent))
    try:
        for relative, doc in files.items():
            kind = schema.kind_of(relative)
            if kind not in schemas:
                schemas[kind] = schema.load(kind)
            error = schema.first_error(doc, schemas[kind])
            if error:
                raise schema.SchemaError(f"{relative}: {error}")
            path = tmp / relative
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(dumps(doc), encoding="utf-8")
        old = out.with_name(f".{out.name}-old")
        shutil.rmtree(old, ignore_errors=True)
        if out.exists():
            out.rename(old)
        tmp.rename(out)
        shutil.rmtree(old, ignore_errors=True)
    finally:
        shutil.rmtree(tmp, ignore_errors=True)
