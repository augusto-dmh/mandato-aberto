"""CLI boundary: default years (C2), download failure (C4), usage errors (C33)."""

import pytest
from conftest import build, legislature, load

from mandato_etl import cli


def test_default_years_run_from_2023_to_current_year(fake):
    fake.serve(legislature(), years=(2023, 2024, 2025, 2026))
    assert cli.main(["build", "--out", str(fake.out)]) == 0
    assert load(fake.out, "meta.json")["years"] == [2023, 2024, 2025, 2026]
    requested = {p.rsplit("-", 1)[1] for p in fake.bulk_requests() if "deputados.csv" not in p}
    assert requested == {"2023.csv", "2024.csv", "2025.csv", "2026.csv"}


@pytest.mark.parametrize("failure", [[404], ["hang"] * 4, [503] * 4], ids=["404", "timeout", "503-exhausted"])
def test_failed_download_exits_2_with_url_and_no_partial_file(fake, capsys, failure):
    fake.serve(legislature())
    path = fake.bulk_path("votacoesVotos", 2023)
    fake.fail[path] = list(failure)
    assert build(fake) == 2
    assert fake.base + path in capsys.readouterr().err
    assert not list(fake.raw.glob("*.part"))
    assert not (fake.raw / "votacoesVotos-2023.csv").exists()


@pytest.mark.parametrize(
    "argv",
    [["build", "--years", "2022"], ["build", "--years", "2027"], ["build", "--years", "2023", "2027"], ["build", "--nope"]],
    ids=["2022", "2027", "mixed", "unknown-flag"],
)
def test_year_out_of_range_exits_1(fake, capsys, argv):
    assert cli.main(argv) == 1
    assert "usage:" in capsys.readouterr().err
    assert fake.requests == []
