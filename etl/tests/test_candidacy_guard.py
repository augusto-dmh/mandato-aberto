"""launch S6 - `scripts/check-candidacy.sh`, the publish workflow's candidacy guard (C41)."""

import json
import subprocess
from pathlib import Path

GUARD = Path(__file__).resolve().parents[2] / "scripts" / "check-candidacy.sh"


def guard(tmp_path: Path, matched: int, candidacy_exists: bool) -> subprocess.CompletedProcess:
    meta = tmp_path / "meta.json"
    meta.write_text(json.dumps({"candidacy": {"file": "candidacy-2026.json", "matched": matched, "ambiguous": []}}))
    candidacy = tmp_path / "candidacy-2026.json"
    if candidacy_exists:
        candidacy.write_text('{"ambiguous": [], "matched": {}, "source": {}}\n')
    return subprocess.run([str(GUARD), str(meta), str(candidacy)], capture_output=True, text=True)


def test_guard_fails_when_the_file_is_present_and_nothing_matched(tmp_path):
    result = guard(tmp_path, matched=0, candidacy_exists=True)
    assert result.returncode == 1
    assert "candidacy file present but 0 deputies matched" in result.stdout + result.stderr


def test_guard_passes_when_the_file_is_present_and_deputies_matched(tmp_path):
    assert guard(tmp_path, matched=2, candidacy_exists=True).returncode == 0


def test_guard_passes_without_the_candidacy_file(tmp_path):
    assert guard(tmp_path, matched=0, candidacy_exists=False).returncode == 0
