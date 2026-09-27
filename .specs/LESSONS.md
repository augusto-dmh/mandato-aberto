# LESSONS - auto-maintained by scripts/lessons.py

> Machine-owned. Do NOT hand-edit. Changes are overwritten on the next `lessons.py` write.
> Canonical state lives in `.specs/lessons.json`. Edit lessons only via the script.
> promote_threshold=2 distinct features · window_days=45 · quarantine_threshold=2

## Confirmed (load these at Plan/Checks)

Corroborated across multiple features. Safe to apply as guidance.

_none_

## Candidates (under observation - do NOT load as guidance yet)

Seen once or not yet corroborated. Tracked, not trusted.

### L-001 - Give each component of a composite match key a fixture row that differs only in that component
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `etl` · harmful: 0
- features: etl-camara
- evidence: verification round 1 F3, etl/src/mandato_etl/sources/tse.py (etl)
- last seen: 2026-09-27T04:02:02Z

### L-002 - Test an exclusion filter with the excluded value against a condition that would otherwise include it
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `etl` · harmful: 0
- features: etl-camara
- evidence: verification rounds 1-2 F5, etl/src/mandato_etl/compute.py:225 (etl)
- last seen: 2026-09-27T04:02:02Z

### L-003 - When a test expects a value to be preserved, make it differ from the pinned clock and any default the code could substitute
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `etl` · harmful: 0
- features: etl-camara
- evidence: verification round 3, etl/src/mandato_etl/sources/camara.py:137 (etl)
- last seen: 2026-09-27T04:02:02Z

### L-004 - Apply a never-persist rule to caches and intermediate files, not only to published output
- signal: `spec_deviation` · recurrence: 1 feature(s) · scope: `etl` · harmful: 0
- features: etl-camara
- evidence: verification round 1 gap 2, data/raw/deputados.csv (etl)
- last seen: 2026-09-27T04:02:02Z

### L-005 - Assert an error message by its full line, never by a substring the injected input also contains
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `etl` · harmful: 0
- features: etl-camara
- evidence: verification round 2 gap 5, etl/tests/test_cli.py:48 (etl)
- last seen: 2026-09-27T04:02:02Z

## Quarantined (failed when applied - ignore)

A confirmed lesson that recurred alongside failure. Kept for the maintainer to review.

_none_
