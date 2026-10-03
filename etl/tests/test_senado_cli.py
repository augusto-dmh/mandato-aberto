"""etl-senado S1 - `--house senado` needs `--contract 3`, and its default output (C7)."""

import pytest
import senado_data as sd

from mandato_etl import cli


@pytest.mark.parametrize("argv", [["--house", "senado"], ["--contract", "2", "--house", "senado"]],
                         ids=["no-contract", "contract-2"])
def test_senado_needs_contract_3(sen, capsys, argv):
    sd.serve(sen, sd.indicators())
    assert cli.main(["build", *argv, "--years", "2025", "--out", str(sd.out(sen))]) == 1
    assert "usage:" in capsys.readouterr().err
    assert sen.requests == []
    assert not sd.out(sen).exists()


def test_senado_default_out(sen, tmp_path, monkeypatch):
    data_dir = tmp_path / "data"
    monkeypatch.setattr(cli, "OUT_DIR", data_dir / "out")
    monkeypatch.setattr(cli, "V3_DIR", data_dir / "v3")
    sd.serve(sen, sd.indicators())
    assert cli.main(["build", "--contract", "3", "--house", "senado", "--years", "2025", "--quiet"]) == 0
    assert (data_dir / "v3" / "senado" / "meta.json").is_file()
    assert sorted(p.name for p in data_dir.iterdir()) == ["v3"]
    assert sorted(p.name for p in (data_dir / "v3").iterdir()) == ["senado"]
