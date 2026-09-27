"""Door 8 - the in-package validator agrees with jsonschema (C37)."""

import copy

import jsonschema
import pytest
from conftest import load

from mandato_etl import schema

MUTATIONS = {
    "wrong-type": lambda d: d.__setitem__("id", "101"),
    "missing-required": lambda d: d.pop("name"),
    "extra-key": lambda d: d.__setitem__("ratio", 0.5),
    "enum-miss": lambda d: d["authored"][0].__setitem__("type", "REQ"),
    "pattern-miss": lambda d: d.__setitem__("uf", "sp"),
    "negative-count": lambda d: d["participation"].__setitem__("count", -1),
    "null-not-allowed": lambda d: d.__setitem__("name", None),
    "wrong-item-type": lambda d: d["votes"].__setitem__(0, "x"),
}


def test_builtin_validator_agrees_with_jsonschema_on_valid_output(built):
    for path in sorted(p for p in built.out.rglob("*.json")):
        relative = path.relative_to(built.out).as_posix()
        spec = schema.load(schema.kind_of(relative))
        doc = load(built.out, relative)
        assert schema.first_error(doc, spec) is None, relative
        assert jsonschema.Draft202012Validator(spec).is_valid(doc), relative


@pytest.mark.parametrize("mutation", MUTATIONS, ids=list(MUTATIONS))
def test_builtin_validator_agrees_with_jsonschema_on_invalid(built, mutation):
    spec = schema.load("deputy")
    doc = copy.deepcopy(load(built.out, "deputies/101.json"))
    MUTATIONS[mutation](doc)
    assert schema.first_error(doc, spec) is not None
    assert not jsonschema.Draft202012Validator(spec).is_valid(doc)


def test_unsupported_keyword_is_refused():
    with pytest.raises(schema.SchemaError):
        schema.first_error({}, {"type": "object", "minProperties": 1})


def test_const_violation_is_rejected_by_both(built):
    spec = schema.load("meta")
    doc = {**load(built.out, "meta.json"), "schema_version": 2}
    assert schema.first_error(doc, spec) is not None
    assert not jsonschema.Draft202012Validator(spec).is_valid(doc)
