"""contract-v3 S3 - ballot and kind (C17-C27)."""

import json
import unicodedata
from pathlib import Path

import pytest
from v3data import K, build3, classification, load3, out3, roll_call, serve

from mandato_etl import classify

RECORDED = Path(__file__).parent / "fixtures" / "v3" / "recorded"
RULES = classify.load_rules("camara")


@pytest.mark.parametrize(
    ("values", "opening", "expected"),
    [
        (["Sim"], "", "nominal"),
        (["", "Não"], "", "nominal"),
        (["", ""], "Votação em turno único", "secret"),
        ([], "Votação Secreta em turno único", "secret"),
        ([], "Votação em turno único", "symbolic"),
    ],
    ids=["one-value", "mixed", "all-empty", "no-record-secreta", "no-record"],
)
def test_ballot_of(values, opening, expected):
    assert classify.ballot_of(values, opening) == expected


@pytest.fixture
def built_classification(fake):
    serve(fake, classification())
    assert build3(fake, "--quiet") == 0
    return fake


def test_roll_call_inclusion(built_classification):
    ids = {r["id"] for r in load3(built_classification, "roll-calls.json")}
    assert ids == {K[n] for n in (1, 2, 3, 4, 5, 6, 7, 9)}


def test_normalise_and_first_match_wins():
    assert classify.normalise(" Aprovado,  em\napreciação PRELIMINAR ") == "aprovado, em apreciacao preliminar"
    row = {"descricao": "Aprovado o Projeto de Lei nº 1, de 2023.",
           "ultimaAberturaVotacao_descricao": "Votação preliminar em turno único."}
    assert classify.classify(row, RULES["rules"]) == ("procedural", "camara.01")


def test_unclassified_is_counted(built_classification):
    k6 = roll_call(built_classification, K[6])
    assert (k6["kind"], k6["kindRule"]) == ("unclassified", None)
    row = next(c for c in load3(built_classification, "meta.json")["coverage"] if c["legislature"] == 57)
    assert row["unclassified"] == 1


EXAMPLES = {
    "2345378-38": ("procedural", "camara.01"), "2485066-13": ("procedural", "camara.03"),
    "2351332-7": ("procedural", "camara.04"), "2400068-23": ("procedural", "camara.04"),
    "2224999-107": ("procedural", "camara.06"), "2351179-51": ("final", "camara.07"),
    "2344938-60": ("amendment", "camara.08"), "2345368-56": ("amendment", "camara.08"),
    "2357055-29": ("final", "camara.09"), "2337246-43": ("final", "camara.09"),
    "2196833-373": ("final", "camara.09"), "2576395-4": ("final", "camara.11"),
}
RECORDED_EXAMPLES = {r["id"]: r for r in json.loads((RECORDED / "ruleset-examples.json").read_text())}


@pytest.mark.parametrize("rc", list(EXAMPLES))
def test_official_examples(rc):
    assert classify.classify(RECORDED_EXAMPLES[rc], RULES["rules"]) == EXAMPLES[rc]


def test_official_examples_are_all_recorded():
    assert set(RECORDED_EXAMPLES) == set(EXAMPLES)


@pytest.mark.parametrize(
    ("descricao", "opening", "expected"),
    [
        ("Aprovada, em apreciação preliminar, a admissibilidade", "", ("procedural", "camara.02")),
        ("Requerimento aprovado, em globo.", "", ("procedural", "camara.05")),
        ("Aprovadas.", "Votação do DTQ 5: emenda", ("amendment", "camara.10")),
    ],
    ids=["camara.02", "camara.05", "camara.10"],
)
def test_rule_without_official_example(descricao, opening, expected):
    row = {"descricao": descricao, "ultimaAberturaVotacao_descricao": opening}
    assert classify.classify(row, RULES["rules"]) == expected


def test_rules_file_is_the_applied_ruleset(built_classification):
    written = load3(built_classification, "classification-rules.json")
    assert [r["id"] for r in written] == [f"camara.{n:02d}" for n in range(1, 12)]
    assert all(set(r) == {"id", "house", "kind", "field", "pattern", "description"} for r in written)
    assert all(r["house"] == "camara" for r in written)
    assert written == [{**r, "house": "camara"} for r in RULES["rules"]]
    assert RULES["version"] == 1
    assert load3(built_classification, "meta.json")["classification"] == {"version": 1}


TERMS = {
    "camara.01": "votacao preliminar", "camara.02": "apreciacao preliminar", "camara.03": "regime de tramitacao",
    "camara.04": "requerimento", "camara.05": "requerimento", "camara.06": "redacao final",
    "camara.07": "subemenda substitutiva global", "camara.08": "destaque", "camara.09": "projeto",
    "camara.10": "dtq", "camara.11": "votacao secreta",
}


def plain(text: str) -> str:
    decomposed = unicodedata.normalize("NFKD", text)
    return " ".join("".join(c for c in decomposed if not unicodedata.combining(c)).casefold().split())


def test_rule_descriptions_name_the_official_term():
    assert {r["id"] for r in RULES["rules"]} == set(TERMS)
    for rule in RULES["rules"]:
        description = plain(rule["description"])
        assert TERMS[rule["id"]] in description, rule["id"]
        for word in ("importante", "relevante", "faltou", "ranking"):
            assert word not in description, (rule["id"], word)


def test_build_classifies_the_fixture(built_classification):
    expected = {
        K[1]: ("procedural", "camara.04", "nominal"), K[2]: ("amendment", "camara.08", "nominal"),
        K[3]: ("final", "camara.09", "symbolic"), K[4]: ("procedural", "camara.03", "symbolic"),
        K[5]: ("final", "camara.11", "secret"), K[6]: ("unclassified", None, "symbolic"),
    }
    for rc, triple in expected.items():
        r = roll_call(built_classification, rc)
        assert (r["kind"], r["kindRule"], r["ballot"]) == triple, rc


def test_symbolic_has_null_tallies_and_no_file(built_classification):
    for n in (3, 4, 6):
        assert roll_call(built_classification, K[n])["tallies"] is None
        assert not (out3(built_classification) / "roll-calls" / f"{K[n]}.json").exists()
    assert (out3(built_classification) / "roll-calls" / f"{K[1]}.json").is_file()


def test_secret_tallies(built_classification):
    assert roll_call(built_classification, K[7])["tallies"] == {"yes": 12, "no": 5, "others": 2}
    assert roll_call(built_classification, K[5])["tallies"] is None
    assert load3(built_classification, f"roll-calls/{K[5]}.json")["votes"] == []
