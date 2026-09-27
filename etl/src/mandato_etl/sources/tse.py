"""Matches deputies to their 2026 candidacy in the TSE registry without CPF (AD-003)."""

import unicodedata
from collections import defaultdict
from collections.abc import Iterable


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
