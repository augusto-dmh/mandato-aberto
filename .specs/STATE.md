# Project state

## Decisions

| ID | Decision | Rationale | Status | Date |
| --- | --- | --- | --- | --- |
| AD-001 | The site is statically generated (Astro + Vue) from JSON published by a Python ETL; no runtime backend in the MVP | zero infrastructure to attack or keep up during the campaign; one real URL per deputy and per roll call for sharing; the ETL from the prototype already works | active | 2026-09-26 |
| AD-002 | The ETL output is a versioned JSON contract (`schema_version` in `meta.json`); the site reads it through one data layer and never reaches into raw files | keeps the door open to replace the static site with an application backed by a database later without rewriting the ETL | active | 2026-09-26 |
| AD-003 | CPF is never persisted, exposed or logged; cross-source matching uses civil name + birth date + UF | the Câmara API returns CPF in clear against its own FAQ; exhibiting it is the shortest path to a founded complaint | active | 2026-09-26 |
| AD-004 | Every derived indicator is stored as numerator and denominator, labelled descriptively, and its method is published; absences are never called "faltou" | the open data carries no absence justification; the electoral case law protects facts with a source and punishes labels | active | 2026-09-26 |
| AD-005 | Every record links to the official source URL and carries the collection timestamp; raw downloads are kept with a hash | proof of provenance is the defence in a correction or a right-of-reply request | active | 2026-09-26 |
| AD-006 | Coverage of the MVP is the Câmara dos Deputados, 57th legislature (from 2023-02-01) | the only source proven end to end; Senate is the first increment after launch | active | 2026-09-26 |
| AD-007 | The project runs under identified natural persons until 2026-10-26; no legal entity, no donations, no paid promotion until then | electoral law forbids campaign content on legal-entity sites and any paid boosting by non-candidates | active | 2026-09-26 |
| AD-008 | Correction requests arrive by a form and a dedicated e-mail; corrections and parliamentarians' replies are versioned in the repository and rendered as a public page | audit trail in git; matches the voluntary right-of-reply policy from the legal research | active | 2026-09-26 |
| AD-009 | No AI-generated content, no user comments, no polls in the MVP | each needs its own decision after the election window; the legal research lists the conditions | active | 2026-09-26 |

## Handoff

**Feature**: launch
**Where**: plan written - `.specs/features/launch/plan.md` (58 criteria in 9 slices, 7 one-way doors, 7 open questions of which 4 block go-live); `validate_plan.py` exits 0, warning only that questions stay open; no `checks.md`, no code
**In progress**: none - waiting for the maintainer's review of the plan
**Next step**: maintainer reviews the plan: door 1 (Cloudflare Pages, GitHub Pages rejected), door 7 (candidacy export by deputy id instead of any copy of the TSE CSV), the assumptions table, and open questions 1 to 4 (domain, names and e-mail, TSE file with its licence capture, Cloudflare secrets). Then `checks.md` in a new session (`tlc-spec-lean`, "write the checks"), build, Verifier at profile `light`. Still pending from `secret-ballots`: check `/votacoes/2645346-18/` and `/votacoes/2576389-4/` in a browser on desktop and phone
**Blockers**: open questions 1 to 4 of the plan block go-live, not the build; launch date 2026-10-02 unconfirmed (question 6)
**Open for the maintainer**: (2) `dist/` 313 MB - sized in the plan's door 1 (about 3,600 files, largest 730 KB, under the Cloudflare Pages limits read 2026-09-27); (3) root `README.md` "presença" - plan AC 55; (5) two roll calls on the same proposition with the same title in the profile list - after launch (plan Out of scope)
**Merged**: PR #6 (`site`) and PR #7 (`secret-ballots`) into `main` on 2026-09-27
**Uncommitted**: none
**Branch**: `feat/launch`; local `data/out/` is on contract version 2 with `candidacy2026` null (built without the TSE file)
