#!/usr/bin/env sh
# Stops the daily publication when the candidacy file is committed but matched no deputy, so a stale
# or broken export never silently removes every 2026 badge (plan AC 38).
# Usage: scripts/check-candidacy.sh <data/out/meta.json> <etl/inputs/candidacy-2026.json>
set -eu
meta=$1
candidacy=$2
[ -f "$candidacy" ] || exit 0
matched=$(python3 -c 'import json, sys; print(json.load(open(sys.argv[1]))["candidacy"]["matched"])' "$meta")
if [ "$matched" -eq 0 ]; then
  echo "candidacy file present but 0 deputies matched" >&2
  exit 1
fi
