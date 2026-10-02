"""Door 2 - the v2 build stays byte-identical to the feature's base commit (C1).

`fixtures/v2-golden.json` holds the sha256 of every file the base commit `bc0a4a8` writes for these
two builds over the current fixture inputs. To reproduce it, run this module's `build_variant` with
`git archive bc0a4a8 etl` extracted elsewhere and its `etl/src` first on `PYTHONPATH`; never regenerate
it from the code under test.
"""

import hashlib
import json
from pathlib import Path

import pytest
from conftest import build, legislature, write_tse

GOLDEN = Path(__file__).parent / "fixtures" / "v2-golden.json"


def hashes(out: Path, base: str) -> dict[str, str]:
    """sha256 per file; the fake server's random port in `meta.sources` is replaced by a fixed origin."""
    return {
        p.relative_to(out).as_posix(): hashlib.sha256(p.read_bytes().replace(base.encode(), b"http://fake")).hexdigest()
        for p in sorted(out.rglob("*")) if p.is_file()
    }


def build_variant(fake, variant: str) -> dict[str, str]:
    if variant == "plain":
        fake.serve(legislature())
        tse = write_tse(fake.raw.parent / "consulta_cand_2026_BRASIL.csv")
        assert build(fake, "--tse-csv", str(tse)) == 0
    else:
        fake.serve(legislature(secret=True))
        assert build(fake) == 0
    return hashes(fake.out, fake.base)


@pytest.mark.parametrize("variant", ["plain", "secret"])
def test_v2_build_matches_base_commit(fake, variant):
    assert build_variant(fake, variant) == json.loads(GOLDEN.read_text())[variant]
