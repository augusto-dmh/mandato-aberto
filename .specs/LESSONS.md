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

### L-006 - Give a sort test names whose accent-stripped order differs from their code-point order, or it cannot tell the two apart
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `site` · harmful: 0
- features: site
- evidence: verification round 1 G1, site/tests/format.test.ts:45 (site)
- last seen: 2026-09-27T05:01:16Z

### L-007 - Prove rendered output at the artifact a reader sees, not only at the intermediate tree that feeds the renderer
- signal: `ac_gap` · recurrence: 1 feature(s) · scope: `site` · harmful: 0
- features: site
- evidence: verification round 1 G4, site/tests/cards.test.ts:49 (site)
- last seen: 2026-09-27T05:01:16Z

### L-008 - When an indicator credits a record under a condition, prove the case where the condition holds and the deputy has no record
- signal: `surviving_mutant` · recurrence: 1 feature(s) · scope: `indicators` · harmful: 0
- features: secret-ballots
- evidence: verification round 1 F3, etl/src/mandato_etl/compute.py:232 (indicators)
- last seen: 2026-09-27T18:47:48Z

### L-009 - Enumerate a new contract field's validation once per schema file that carries it
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `contract` · harmful: 0
- features: secret-ballots
- evidence: verification round 1 C6, etl/schema/roll-call.schema.json:15 (contract)
- last seen: 2026-09-27T18:47:48Z

### L-010 - Pin every copy string a new page state introduces, including title and meta description
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `site` · harmful: 0
- features: secret-ballots
- evidence: verification round 1 C12, site/src/pages/votacoes/[id].astro:28 (site)
- last seen: 2026-09-27T18:47:48Z

### L-011 - Run a new classification rule over the shared test legislature before fixing expected values that assume its existing cases
- signal: `spec_deviation` · recurrence: 1 feature(s) · scope: `fixtures` · harmful: 0
- features: secret-ballots
- evidence: checks.md renegotiation 2026-09-27, etl/tests/conftest.py 100-3 (fixtures)
- last seen: 2026-09-27T18:47:48Z

### L-012 - Prove an order claim by comparing the first line number of each marker in the claimed order, never by sorting grep output that is already in file order
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `ci` · harmful: 0
- features: launch
- evidence: checks.md C40 (verification.md F1) (ci)
- last seen: 2026-09-27T20:26:13Z

### L-013 - Prove that several terms are present by checking each term on its own, since grep -c with many patterns counts matching lines
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `docs` · harmful: 0
- features: launch
- evidence: checks.md C61 (verification.md F2) (docs)
- last seen: 2026-09-27T20:26:13Z

### L-014 - Extend a placeholder guard to every published artifact that carries the placeholder, not only the rendered pages
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `research` · harmful: 0
- features: launch
- evidence: checks.md C58, research/03-teste-de-balanceamento-lgpd.md:4 (verification.md F4) (research)
- last seen: 2026-09-27T20:26:13Z

### L-015 - Derive a pattern's boundary cases by evaluating the exact pattern the plan fixes, not by reading it
- signal: `spec_deviation` · recurrence: 1 feature(s) · scope: `site` · harmful: 0
- features: launch
- evidence: checks.md C10 vs plan AC 10 (renegotiated 2026-09-27) (site)
- last seen: 2026-09-27T20:26:13Z

### L-016 - Resolve every path in a workflow step against that step's working-directory
- signal: `spec_deviation` · recurrence: 1 feature(s) · scope: `ci` · harmful: 0
- features: launch
- evidence: checks.md C41 vs publish.yml working-directory (renegotiated 2026-09-27) (ci)
- last seen: 2026-09-27T20:26:14Z

### L-017 - A base-path check must cover every URL Astro writes, including astro-island component-url/renderer-url and CSS url(), not only href and src.
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `site/base-path` · harmful: 0
- features: github-pages
- evidence: site/tests/build.test.ts:896 (C2) (site/base-path)
- last seen: 2026-09-27T23:23:28Z

### L-018 - Prove an island's hydrated behaviour from its client chunk, not only from server-rendered HTML, when the value comes from import.meta.env.
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `site/islands` · harmful: 0
- features: github-pages
- evidence: site/tests/search-island.test.ts:30 (C5) (site/islands)
- last seen: 2026-09-27T23:23:28Z

### L-019 - A source scan for root links misses variables and Markdown links; pair it with a scan of the built pages under the base.
- signal: `spec_precision_gap` · recurrence: 1 feature(s) · scope: `site/base-path` · harmful: 0
- features: github-pages
- evidence: site/tests/urls.test.ts:14 (C8) (site/base-path)
- last seen: 2026-09-27T23:23:28Z

### L-020 - Before changing a config default or env handling, grep the approved tests: site C33 pins the SITE_URL default, and vitest exports BASE_URL, which overrides Astro's base in child builds.
- signal: `spec_deviation` · recurrence: 1 feature(s) · scope: `site/build` · harmful: 0
- features: github-pages
- evidence: site/tests/build.test.ts:31, site/tests/data.test.ts:155 (site C33) (site/build)
- last seen: 2026-09-27T23:23:28Z

### L-021 - Never write 'the same files' as a criterion for a build whose asset names are content-hashed; say 'the same paths with the hash removed'.
- signal: `ac_gap` · recurrence: 1 feature(s) · scope: `specs/criteria` · harmful: 0
- features: github-pages
- evidence: AC 1 / C1 (specs/criteria)
- last seen: 2026-09-27T23:23:29Z

## Quarantined (failed when applied - ignore)

A confirmed lesson that recurred alongside failure. Kept for the maintainer to review.

_none_
