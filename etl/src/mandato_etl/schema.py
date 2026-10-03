"""Validates documents against `etl/schema/*.json` (v2) and `etl/schema/v{3,4}/*.json` without a runtime dependency.

Implements only the JSON Schema keywords the contract uses and refuses any other, so a schema
can never rely on a keyword this module would silently ignore.
"""

import json
import re
from pathlib import Path

SCHEMA_DIR = Path(__file__).resolve().parents[2] / "schema"
VERSIONS = (2, 3, 4)
V3_FILES = {
    "meta.json": "meta",
    "members.json": "members",
    "roll-calls.json": "roll-calls",
    "propositions.json": "propositions",
    "classification-rules.json": "classification-rules",
}
V3_DIRS = {"roll-calls": "roll-call", "full-texts": "full-text"}
# v4 house directories keep the v3 layout; the presidency directory is its own set, chosen by `meta.scope` (door 1).
PRESIDENCY = "presidencia"
PRESIDENCY_FILES = {
    "meta.json": "presidency-meta",
    "acts.json": "acts",
    "status-rules.json": "status-rules",
    "joint-roll-calls.json": "joint-roll-calls",
    "member-veto-counts.json": "member-veto-counts",
}
PRESIDENCY_DIRS = {"joint-roll-calls": "joint-roll-call"}

KEYWORDS = {
    "$schema", "$id", "$defs", "$ref", "title", "description",
    "type", "properties", "required", "additionalProperties", "items",
    "enum", "const", "pattern", "minimum",
}
TYPES = {
    "object": lambda v: isinstance(v, dict),
    "array": lambda v: isinstance(v, list),
    "string": lambda v: isinstance(v, str),
    "integer": lambda v: isinstance(v, int) and not isinstance(v, bool),
    "boolean": lambda v: isinstance(v, bool),
    "null": lambda v: v is None,
}


class SchemaError(Exception):
    pass


def load(kind: str, version: int = 2) -> dict:
    directory = SCHEMA_DIR if version == 2 else SCHEMA_DIR / f"v{version}"
    return json.loads((directory / f"{kind}.schema.json").read_text())


def kind_of(relative: str, version: int = 2, scope: str | None = None) -> str | None:
    """The schema that governs a file in the output directory, by its relative path."""
    parts = relative.split("/")
    if version in (3, 4):
        files, dirs = (PRESIDENCY_FILES, PRESIDENCY_DIRS) if scope == PRESIDENCY else (V3_FILES, V3_DIRS)
        if len(parts) == 1:
            return files.get(parts[0])
        if len(parts) == 2 and parts[1].endswith(".json"):
            return dirs.get(parts[0])
        return None
    if len(parts) == 1:
        return {"meta.json": "meta", "deputies.json": "deputies", "roll-calls.json": "roll-calls"}.get(parts[0])
    if len(parts) == 2 and parts[1].endswith(".json"):
        return {"deputies": "deputy", "roll-calls": "roll-call"}.get(parts[0])
    return None


def first_error(doc, schema: dict, root: dict | None = None, path: str = "$") -> str | None:
    root = root or schema
    unknown = set(schema) - KEYWORDS
    if unknown:
        raise SchemaError(f"unsupported schema keyword {sorted(unknown)[0]} at {path}")
    if "$ref" in schema:
        name = schema["$ref"].removeprefix("#/$defs/")
        return first_error(doc, root["$defs"][name], root, path)
    if "type" in schema:
        types = schema["type"] if isinstance(schema["type"], list) else [schema["type"]]
        if not any(TYPES[t](doc) for t in types):
            return f"{path}: expected {' or '.join(types)}, got {type(doc).__name__}"
    if "const" in schema and doc != schema["const"]:
        return f"{path}: expected {schema['const']!r}"
    if "enum" in schema and doc not in schema["enum"]:
        return f"{path}: {doc!r} is not one of {schema['enum']}"
    if "pattern" in schema and isinstance(doc, str) and not re.search(schema["pattern"], doc):
        return f"{path}: {doc!r} does not match {schema['pattern']}"
    if "minimum" in schema and TYPES["integer"](doc) and doc < schema["minimum"]:
        return f"{path}: {doc} is below {schema['minimum']}"
    if isinstance(doc, dict):
        for key in schema.get("required", []):
            if key not in doc:
                return f"{path}: missing required key {key!r}"
        properties = schema.get("properties", {})
        for key, value in doc.items():
            if key in properties:
                error = first_error(value, properties[key], root, f"{path}.{key}")
                if error:
                    return error
            elif schema.get("additionalProperties") is False:
                return f"{path}: unexpected key {key!r}"
    if isinstance(doc, list) and "items" in schema:
        for i, item in enumerate(doc):
            error = first_error(item, schema["items"], root, f"{path}[{i}]")
            if error:
                return error
    return None


def _meta(out: Path) -> dict:
    try:
        meta = json.loads((out / "meta.json").read_text())
    except (OSError, json.JSONDecodeError):
        return {}
    return meta if isinstance(meta, dict) else {}


def version_of(out: Path):
    """`meta.schema_version` of a directory; 2 when `meta.json` is missing or unreadable, so the v2 checks report it."""
    return _meta(out).get("schema_version", 2)


def scope_of(out: Path) -> str | None:
    """`meta.scope` of a v4 directory: `presidencia` for the presidency set, absent for a house."""
    return _meta(out).get("scope")


def validate_dir(out: Path) -> str | None:
    """The first file under `out` that is unknown or fails its schema, as `<file>: <error>`.

    The schema set is chosen by `meta.json`'s `schema_version`, and in v4 by its `scope`.
    """
    version = version_of(out)
    if version not in VERSIONS:
        return f"meta.json: unsupported schema_version {version}"
    scope = scope_of(out) if version == 4 else None
    schemas = {}
    for path in sorted(p for p in out.rglob("*") if p.is_file()):
        relative = path.relative_to(out).as_posix()
        kind = kind_of(relative, version, scope)
        if kind is None:
            return f"{relative}: not part of the contract"
        if kind not in schemas:
            schemas[kind] = load(kind, version)
        try:
            doc = json.loads(path.read_text())
        except json.JSONDecodeError as e:
            return f"{relative}: invalid JSON ({e})"
        error = first_error(doc, schemas[kind])
        if error:
            return f"{relative}: {error}"
    if version == 2:
        required = ("meta.json", "deputies.json", "roll-calls.json")
    else:
        required = PRESIDENCY_FILES if scope == PRESIDENCY else V3_FILES
    for required in required:
        if not (out / required).exists():
            return f"{required}: missing"
    return None
