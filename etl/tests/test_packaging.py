"""Doors 6 and 7 - runtime dependencies and project layout (C36)."""

import re
import tomllib
from pathlib import Path

ETL = Path(__file__).resolve().parents[1]


def test_runtime_dependencies_are_empty():
    project = tomllib.loads((ETL / "pyproject.toml").read_text())
    assert project["project"]["dependencies"] == []
    assert project["project"]["requires-python"] == ">=3.13"
    assert project["project"]["scripts"] == {"mandato-etl": "mandato_etl.cli:main"}
    dev = [re.split(r"[<>=~! \[]", d, maxsplit=1)[0] for d in project["dependency-groups"]["dev"]]
    assert sorted(dev) == ["jsonschema", "pytest"]
    imports = [p for p in (ETL / "src").rglob("*.py") if re.search(r"^\s*(import|from)\s+jsonschema", p.read_text(), re.M)]
    assert imports == []
