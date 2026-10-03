"""Ballot type, kind by the published rule table, and the house-independent vote positions (doors 5, 6, 7, 10).

Every map here fails closed: a value outside it raises `UnknownValueError`, so an indicator never
silently changes meaning when a house adds a code.
"""

import json
import re
import unicodedata
from pathlib import Path

RULES_DIR = Path(__file__).resolve().parent / "rules"
MERIT = {"final", "amendment"}

VOTE_POSITIONS = {
    "Sim": "yes",
    "Não": "no",
    "Abstenção": "abstention",
    "Obstrução": "obstruction",
    "Artigo 17": "presiding",
}
ORIENTATION_POSITIONS = {
    "Sim": "yes",
    "Não": "no",
    "Abstenção": "abstention",
    "Obstrução": "obstruction",
    "Liberado": "free",
}
# Senate `siglaVotoParlamentar` (door 3 of etl-senado). Every absence code, justified or not, is
# `notVoting`; the reason survives only in `official`.
SENATE_VOTE_POSITIONS = {
    "Sim": "yes",
    "Não": "no",
    "Abstenção": "abstention",
    "Votou": "secret",
    "Presidente (art. 51 RISF)": "presiding",
    **dict.fromkeys(("P-NRV", "AP", "MIS", "LS", "LP", "LAP", "NCom", "NA"), "notVoting"),
}
SENATE_ORIENTATION_POSITIONS = {
    "SIM": "yes",
    "NÃO": "no",
    "ABSTENÇÃO": "abstention",
    "OBSTRUÇÃO": "obstruction",
    "LIVRE": "free",
}
# Codes whose verbatim value would reveal health or private life (AD-018); published only as the
# generic value. The Câmara has none today.
SENSITIVE_OFFICIAL = {
    "camara": {},
    "senado": {"LS": "Licença", "LP": "Licença", "LAP": "Licença"},
}


class UnknownValueError(Exception):
    """A vote or orientation value outside the house's map."""


def normalise(text: str) -> str:
    """NFKD accent stripping, casefold, and every whitespace run collapsed to one space."""
    decomposed = unicodedata.normalize("NFKD", text)
    return " ".join("".join(c for c in decomposed if not unicodedata.combining(c)).casefold().split())


def load_rules(house: str) -> dict:
    """`{"house", "version", "rules": [{id, kind, field, pattern, description}]}` in application order."""
    return json.loads((RULES_DIR / f"{house}.json").read_text(encoding="utf-8"))


def ballot_of(values: list[str], opening: str) -> str:
    """`nominal` with any non-empty record; `secret` with only empty records, or none and `secreta` in the opening."""
    if any(values):
        return "nominal"
    if values or "secreta" in normalise(opening):
        return "secret"
    return "symbolic"


def classify(row: dict, rules: list[dict]) -> tuple[str, str | None]:
    """`(kind, rule id)` of the first rule whose pattern matches its normalised field."""
    for rule in rules:
        if re.search(rule["pattern"], normalise(row.get(rule["field"]) or "")):
            return rule["kind"], rule["id"]
    return "unclassified", None


def published_official(house: str, official: str) -> str:
    return SENSITIVE_OFFICIAL[house].get(official, official)


def position_of(official: str, ballot: str) -> str:
    if official == "":
        return "secret" if ballot == "secret" else "notVoting"
    if official not in VOTE_POSITIONS:
        raise UnknownValueError(official)
    return VOTE_POSITIONS[official]


def orientation_of(official: str) -> str:
    if official not in ORIENTATION_POSITIONS:
        raise UnknownValueError(official)
    return ORIENTATION_POSITIONS[official]


def senate_position_of(official: str) -> str:
    if official not in SENATE_VOTE_POSITIONS:
        raise UnknownValueError(official)
    return SENATE_VOTE_POSITIONS[official]


def senate_orientation_of(official: str) -> str:
    if official not in SENATE_ORIENTATION_POSITIONS:
        raise UnknownValueError(official)
    return SENATE_ORIENTATION_POSITIONS[official]
