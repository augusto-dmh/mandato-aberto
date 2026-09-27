"""S5 - candidacy 2026 (C25-C28)."""

import pytest
from conftest import CPF_IN_TSE, build, legislature, load

from mandato_etl import readers


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
