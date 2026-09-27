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

**Feature**: site
**Where**: built and verified - `verification.md` round 1 PASS (profile `light`, 41/41 checks, gate exit 0); commits `ed67062`..`8c5211e` plus the verification commit; nothing pushed
**In progress**: none
**Next step**: maintainer reviews the pages in a browser (desktop and mobile) and decides the open items below; then PR `feat(site): ...` when asked; then plan `launch` (grilling decisions 6-8, legal checklist section 5) and stop for review
**Blockers**: go-live needs the `.org` domain (`SITE_URL` stays a placeholder); open maintainer questions on the TSE CSV holding CPF and the other personal columns in the raw cache
**Open for the maintainer**: (1) resolved 2026-09-27 - the maintainer confirmed the single photo credit on the home list (new `Landing` row); (2) `dist/` is 313 MB over the real data (cards 128 MB) - `launch` sizes the Actions cache and host around it; (3) root `README.md` still says "presença" for the participation indicator
**Uncommitted**: none
**Branch**: `feat/site`, stacked on `feat/etl-camara` (PR #5); retarget to `main` once #5 merges
