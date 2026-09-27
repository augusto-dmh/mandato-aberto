#!/usr/bin/env sh
# Rejects a commit message that breaks the repository convention (AGENTS.md, "Commits e PRs").
# Usage: scripts/check-commit-msg.sh <file with the message>   or   git log ... | scripts/check-commit-msg.sh -
set -eu
if [ "${1:-}" = "-" ]; then msg=$(cat); else msg=$(cat "$1"); fi
header=$(printf '%s\n' "$msg" | sed -n '1p')
case "$header" in
  Merge\ *|Revert\ *) exit 0 ;;
esac
if ! printf '%s' "$header" | grep -Eq '^(build|chore|ci|docs|feat|fix|perf|refactor|revert|style|test)(\([a-z0-9-]+\))?!?: [a-z].{0,70}$'; then
  echo "commit header must be '<type>(<scope>): <lowercase description>' within 72 chars: $header" >&2; exit 1
fi
if printf '%s\n' "$msg" | grep -Eiq '^(Co-Authored-By|Signed-off-by):|Generated with'; then
  echo "forbidden trailer: use 'Assisted-by: <tool>' only (see AGENTS.md)" >&2; exit 1
fi
