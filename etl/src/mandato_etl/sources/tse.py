"""Matches deputies to their 2026 candidacy in the TSE registry without CPF (AD-003)."""

import hashlib
import json
import unicodedata
from collections import defaultdict
from collections.abc import Iterable
from datetime import UTC, datetime
from pathlib import Path


def normalize_name(name: str) -> str:
    decomposed = unicodedata.normalize("NFKD", name)
    stripped = "".join(c for c in decomposed if not unicodedata.combining(c))
    return " ".join(stripped.casefold().split())


def normalize_date(value: str) -> str:
    """`31/01/1984` (TSE) and `1984-01-31` (Câmara) both become `1984-01-31`."""
    value = value.strip()
    if len(value) == 10 and value[2] == "/" and value[5] == "/":
        return f"{value[6:]}-{value[3:5]}-{value[:2]}"
    return value[:10]


def match_key(civil_name: str, birth_date: str, uf: str) -> tuple[str, str, str]:
    return (normalize_name(civil_name), normalize_date(birth_date), uf.strip().upper())


def match(deputies: dict[str, tuple[str, str, str]], rows: Iterable[dict]) -> tuple[dict[str, dict], list[str]]:
    """Returns the candidacy of each deputy matched by exactly one row, and the ids matched by several.

    `deputies` maps a deputy id to `(civil name, birth date, uf)`.
    """
    index = defaultdict(list)
    for dep, key in deputies.items():
        index[match_key(*key)].append(dep)
    hits = defaultdict(list)
    for r in rows:
        for dep in index.get(match_key(r["NM_CANDIDATO"], r["DT_NASCIMENTO"], r["SG_UF"]), []):
            hits[dep].append(
                {
                    "office": r["DS_CARGO"].strip(),
                    "party": r["SG_PARTIDO"].strip(),
                    "ballotNumber": r["NR_CANDIDATO"].strip(),
                    "situation": r["DS_SITUACAO_CANDIDATURA"].strip(),
                }
            )
    shared = {dep for deps in index.values() if len(deps) > 1 for dep in deps}
    matched = {dep: found[0] for dep, found in hits.items() if len(found) == 1 and dep not in shared}
    ambiguous = sorted((dep for dep in hits if dep not in matched), key=int)
    return matched, ambiguous


FIELDS = ("ballotNumber", "office", "party", "situation")


class CandidacyFileError(Exception):
    """A `--candidacy-json` file that cannot be used; the message names the file."""


def export(path: Path, csv: Path, matched: dict[str, dict], ambiguous: list[str]) -> None:
    """Writes the CPF-free candidacy file CI reads in place of the TSE CSV (plan door 7)."""
    stat = csv.stat()
    doc = {
        "source": {
            "file": csv.name,
            "sha256": hashlib.sha256(csv.read_bytes()).hexdigest(),
            "bytes": stat.st_size,
            "modifiedAt": datetime.fromtimestamp(stat.st_mtime, UTC).strftime("%Y-%m-%dT%H:%M:%SZ"),
        },
        "matched": {dep: {field: found[field] for field in FIELDS} for dep, found in matched.items()},
        "ambiguous": [int(dep) for dep in ambiguous],
    }
    path.write_text(json.dumps(doc, sort_keys=True, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")


def _has_cpf_key(value) -> bool:
    if isinstance(value, dict):
        return any("cpf" in str(key).lower() or _has_cpf_key(v) for key, v in value.items())
    if isinstance(value, list):
        return any(_has_cpf_key(v) for v in value)
    return False


def read_export(path: Path) -> tuple[dict[str, dict], list[int]]:
    """The `matched` and `ambiguous` of a file written by `export`, validated before any use."""
    try:
        doc = json.loads(path.read_text(encoding="utf-8"))
    except FileNotFoundError:
        raise CandidacyFileError(f"{path}: file does not exist") from None
    except (OSError, UnicodeDecodeError, json.JSONDecodeError) as e:
        raise CandidacyFileError(f"{path}: not a JSON file ({e})") from None
    if _has_cpf_key(doc):
        raise CandidacyFileError(f"{path}: contains a CPF key")
    if not isinstance(doc, dict) or not isinstance(doc.get("matched"), dict) or not isinstance(doc.get("ambiguous"), list):
        raise CandidacyFileError(f"{path}: expected an object with 'matched' and 'ambiguous'")
    matched = {}
    for dep, entry in doc["matched"].items():
        if not isinstance(entry, dict) or not all(isinstance(entry.get(field), str) for field in FIELDS):
            raise CandidacyFileError(f"{path}: matched[{dep}] must carry {', '.join(FIELDS)}")
        matched[str(dep)] = {field: entry[field] for field in FIELDS}
    if not all(isinstance(dep, int) for dep in doc["ambiguous"]):
        raise CandidacyFileError(f"{path}: ambiguous must list deputy ids")
    return matched, doc["ambiguous"]
