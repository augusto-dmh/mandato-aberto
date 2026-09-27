"""S5 - candidacy 2026 (C25-C28)."""

import pytest
from conftest import CPF_IN_TSE, TSE_ROWS, build, legislature, load, write_tse

from mandato_etl import readers
from mandato_etl.sources import tse


def test_matches_on_normalized_name_birth_date_and_uf(built):
    got = {d["id"]: d["candidacy2026"] for d in load(built.out, "deputies.json")}
    assert got[101] == {"office": "DEPUTADO FEDERAL", "party": "PT", "ballotNumber": "1313", "situation": "APTO"}
    # civil name "JOSÉ BRUNO LIMA" at the Câmara, "JOSE BRUNO LIMA" at the TSE
    assert got[102] == {"office": "SENADOR", "party": "PT", "ballotNumber": "131", "situation": "APTO"}
    meta = load(built.out, "meta.json")["candidacy"]
    assert meta["matched"] == 2
    assert meta["file"] == "consulta_cand_2026_BRASIL.csv"


@pytest.mark.parametrize("flag", [[], ["--tse-csv", "missing/consulta_cand_2026_BRASIL.csv"]], ids=["absent", "missing"])
def test_missing_tse_file_warns_and_exits_0(fake, capsys, flag):
    fake.serve(legislature())
    assert build(fake, *flag) == 0
    assert "warning" in capsys.readouterr().err
    assert [d["candidacy2026"] for d in load(fake.out, "deputies.json")] == [None, None, None]
    assert load(fake.out, "meta.json")["candidacy"] == {"file": None, "matched": 0, "ambiguous": []}


def test_ambiguous_match_is_null_and_listed(built):
    carla = load(built.out, "deputies/103.json")
    assert carla["candidacy2026"] is None
    assert load(built.out, "meta.json")["candidacy"]["ambiguous"] == [103]


def test_tse_reader_never_reads_cpf_columns(built):
    assert not [c for c in readers.ALLOWLIST["tse"] if "CPF" in c.upper()]
    for path in built.out.rglob("*.json"):
        assert CPF_IN_TSE not in path.read_text(), path


def test_birth_date_and_uf_are_part_of_the_key(fake):
    fake.serve(legislature())
    ana = TSE_ROWS[0]
    rows = [{**ana, "DT_NASCIMENTO": "02/05/1970"}, {**ana, "SG_UF": "RJ"}]
    path = write_tse(fake.raw.parent / "consulta_cand_2026_BRASIL.csv", rows)
    assert build(fake, "--tse-csv", str(path)) == 0
    assert load(fake.out, "deputies/101.json")["candidacy2026"] is None
    meta = load(fake.out, "meta.json")["candidacy"]
    assert (meta["matched"], meta["ambiguous"]) == (0, [])


def test_deputies_sharing_a_key_are_ambiguous():
    deputies = {"7": ("MARIA DA SILVA", "1960-01-01", "SP"), "8": ("Maria da Silva", "1960-01-01", "SP")}
    row = {**TSE_ROWS[0], "NM_CANDIDATO": "MARIA DA SILVA", "DT_NASCIMENTO": "01/01/1960", "SG_UF": "SP"}
    matched, ambiguous = tse.match(deputies, [row])
    assert matched == {}
    assert ambiguous == ["7", "8"]
