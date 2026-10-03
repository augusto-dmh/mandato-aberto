"""etl-senado S2 - senators and mandates (C8-C14).

The members dataset holds the real list entries of 4605 (titular who resigned in the 57th), 6358 (the
alternate who took the seat) and 5666 (only exercise ended 2021), plus hand senators 9201 and 9202.
"""

import json

import pytest
import senado_data as sd

from mandato_etl import readers

PERSONAL_KEYS = {"cpf", "nomecompletoparlamentar", "sexoparlamentar", "emailparlamentar", "telefones",
                 "datanascimento", "naturalidade", "enderecoparlamentar"}


def build_2027(sen, monkeypatch, **kwargs) -> int:
    sd.pin(monkeypatch, sd.CLOCK_2027)
    sd.serve(sen, sd.members_dataset(**kwargs))
    return sd.build(sen, "--quiet", years=("2025", "2027"))


def periods(member, legislature):
    return [(p["start"], p["end"]) for p in sd.mandate(member, legislature)["exercisePeriods"]]


def test_mandate_per_exercise_or_vote(sen, monkeypatch):
    assert build_2027(sen, monkeypatch) == 0
    members = sd.members_by_id(sen)
    assert sorted(members) == [4605, 6358, 9201, 9202]
    assert {m: [x["legislature"] for x in members[m]["mandates"]] for m in members} == {
        4605: [57], 6358: [57], 9201: [57, 58], 9202: [57]}


def test_exercise_periods_are_half_open_and_clipped(sen, monkeypatch):
    assert build_2027(sen, monkeypatch) == 0
    members = sd.members_by_id(sen)
    assert periods(members[4605], 57) == [("2023-02-01T00:00:00", "2023-02-03T00:00:00"),
                                          ("2024-02-01T00:00:00", "2024-02-21T00:00:00")]
    assert periods(members[6358], 57) == [("2023-02-02T00:00:00", "2024-02-01T00:00:00"),
                                          ("2024-02-21T00:00:00", "2026-07-31T00:00:00")]
    assert periods(members[9201], 57) == [("2023-02-01T00:00:00", "2027-02-01T00:00:00")]
    assert periods(members[9201], 58) == [("2027-02-01T00:00:00", "2027-03-01T09:00:00")]
    assert periods(members[9202], 57) == []


def test_open_exercise_closes_at_build_time(sen):
    sd.serve(sen, sd.members_dataset())
    assert sd.build(sen, "--quiet", years=("2025",)) == 0
    members = sd.members_by_id(sen)
    assert periods(members[9201], 57) == [("2023-02-01T00:00:00", "2026-09-27T09:00:00")]
    assert [x["legislature"] for x in members[9201]["mandates"]] == [57]


def test_single_objects_read_as_lists(sen, monkeypatch):
    routes = sd.members_dataset()
    entry = routes["/senador/lista/legislatura/57?exercicio=S"]["ListaParlamentarLegislatura"]["Parlamentares"][
        "Parlamentar"][3]
    assert entry["IdentificacaoParlamentar"]["CodigoParlamentar"] == "9201"
    mandato = entry["Mandatos"]["Mandato"]
    assert isinstance(mandato, dict)
    assert isinstance(mandato["Exercicios"]["Exercicio"], dict)
    assert isinstance(mandato["Suplentes"]["Suplente"], dict)
    assert build_2027(sen, monkeypatch) == 0
    assert periods(sd.members_by_id(sen)[9201], 57) == [("2023-02-01T00:00:00", "2027-02-01T00:00:00")]
    assert readers.as_list({"a": 1}) == [{"a": 1}]
    assert readers.as_list([{"a": 1}, {"b": 2}]) == [{"a": 1}, {"b": 2}]
    assert readers.as_list({}.get("Exercicio")) == []


def test_member_fields_from_latest_vote_or_list(sen, monkeypatch):
    assert build_2027(sen, monkeypatch) == 0
    members = sd.members_by_id(sen)
    nine = members[9201]
    assert (nine["name"], nine["party"], nine["uf"]) == ("Nove Dois Zero Um", "PSB", "SP")
    assert sd.mandate(nine, 57)["party"] == "PT"
    assert sd.mandate(nine, 58)["party"] == "PSB"
    dino = members[4605]
    assert (dino["name"], dino["party"], dino["uf"]) == ("Flávio Dino", "PSB", "MA")
    assert (sd.mandate(dino, 57)["party"], sd.mandate(dino, 57)["uf"]) == ("PSB", "MA")


def test_member_urls(sen, monkeypatch):
    assert build_2027(sen, monkeypatch) == 0
    for m in sd.load(sen, "members.json"):
        assert m["photoUrl"] == f"https://www.senado.leg.br/senadores/img/fotos-oficiais/senador{m['id']}.jpg"
        assert m["sourceUrl"] == f"https://www25.senado.leg.br/web/senadores/senador/-/perfil/{m['id']}"


def keys_of(doc, found):
    if isinstance(doc, dict):
        found.update(doc)
        for value in doc.values():
            keys_of(value, found)
    elif isinstance(doc, list):
        for value in doc:
            keys_of(value, found)
    return found


def test_no_personal_field_reaches_output(sen, monkeypatch):
    assert build_2027(sen, monkeypatch) == 0
    raw = b"".join(p.read_bytes() for p in sen.raw.rglob("*.json"))
    sentinels = ["SENTINEL-NOME-COMPLETO", "SENTINEL-SEXO", "sentinel@example.invalid", "SENTINEL-TELEFONE",
                 "1900-01-01", "SENTINEL-NATURALIDADE", "SENTINEL-ENDERECO"]
    assert all(s.encode() in raw for s in sentinels)  # the source does carry them
    files = [p for p in sd.out(sen).rglob("*") if p.is_file()]
    assert files
    for path in files:
        data = path.read_bytes()
        for s in sentinels:
            assert s.encode() not in data, (path, s)
        keys = {k.casefold() for k in keys_of(json.loads(data), set())}
        assert not keys & PERSONAL_KEYS, path
        assert not any("cpf" in k for k in keys), path

    def spec_keys(spec, found):
        for key, sub in spec.items():
            found.add(key.casefold())
            if sub:
                spec_keys(sub, found)
        return found

    allowed = set().union(*(spec_keys(spec, set()) for spec in readers.SENADO_ALLOWLIST.values()))
    assert not allowed & PERSONAL_KEYS
    assert not any("cpf" in k for k in allowed)


@pytest.mark.parametrize(("name", "expected"), [("members", 1), ("indicators", 2)])
def test_mismatch_warning_per_legislature(sen, monkeypatch, capsys, name, expected):
    # members: 9202's M1 record outside every period. indicators: Beto's V5 outside his period, and Dani
    # without a record in V3 inside hers.
    if name == "members":
        sd.pin(monkeypatch, sd.CLOCK_2027)
        sd.serve(sen, sd.members_dataset())
        assert sd.build(sen, years=("2025", "2027")) == 0
    else:
        sd.serve(sen, sd.indicators())
        assert sd.build(sen) == 0
    lines = [line for line in capsys.readouterr().err.splitlines() if "disagree" in line]
    assert len(lines) == 1
    assert "senado 57" in lines[0] and f": {expected} " in lines[0]
