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
| AD-010 | The site is hosted on Cloudflare Pages, deployed by direct upload from GitHub Actions; the daily publication rebuilds `data/out/` from empty and only the photo cache carries over | the 313 MB `dist/` and its share cards exceed what GitHub Pages' bandwidth cap tolerates for a site meant to spread; a cached `data/raw/` would freeze the current year's roll calls | superseded by AD-012 | 2026-09-27 |
| AD-012 | The site is published on GitHub Pages as a project site under `https://augusto-dmh.github.io/mandato-aberto/`, deployed by `actions/deploy-pages` with the repository token; every site-relative link goes through `withBase`, and `SITE_URL` (origin) plus `SITE_BASE` (path) locate the site; the daily publication still rebuilds `data/out/` from empty with only the photo cache carried over | the maintainer deferred the domain and any external host on 2026-09-27; GitHub Pages needs no account, secret or DNS, at the cost of a 100 GB/month soft bandwidth cap, no custom headers and no analytics, all acceptable until the later "professional deploy" | active | 2026-09-27 |
| AD-011 | The only candidacy input CI reads is `etl/inputs/candidacy-2026.json`, a per-deputy-id export of one local match against the TSE file, holding only the four fields the contract publishes; no copy or subset of the TSE CSV enters the repository | AD-003: the TSE file carries CPF, and a column subset still carries name and birth date of every candidate | active | 2026-09-27 |

## Handoff

**Feature**: github-pages (after `launch`, merged as PR #8)
**Where**: plan written and validated on `feat/github-pages`, approved by the maintainer's instruction in chat (2026-09-27); no checks, no code
**In progress**: a builder pane derives `checks.md`, builds, and dispatches the Verifier (round 1 over AC 1 to 14)
**Next step**: PR against `main`; the merge deploys to `https://augusto-dmh.github.io/mandato-aberto/`; then verification round 2 (AC 15 to 18 here, launch C62, C63, C67 to C69 as renegotiated). Maintainer decides the names on Quem somos (plan question 1). Still pending: the TSE file for the badge; browser check of `/votacoes/2645346-18/` and `/votacoes/2576389-4/`
**Blockers**: none for the build; question 1 (maintainers' names) blocks a placeholder-free go-live
**Open for the maintainer**: Verifier findings that change no result - C40's order proof (`grep -n | sort -c`) cannot fail and does not name `node-version: 24`; C61's proof counts lines, not terms; C41 and C43 greps are partial (all three confirmed by reading `publish.yml`); C58 passes while `research/03-teste-de-balanceamento-lgpd.md` still reads `**Controladores:** [a definir]`, and C68 does not cover that file; `correctionsDir()` resolves from `process.cwd()`, correct only from `site/` (true in vitest and `publish.yml`); plan question 5 - confirm in a browser that Cloudflare Web Analytics sets no cookie before setting `CF_ANALYTICS_TOKEN`
**Merged**: PR #6 (`site`), PR #7 (`secret-ballots`) and PR #8 (`launch`) into `main` on 2026-09-27
**Uncommitted**: none
**Branch**: `feat/github-pages` from `main` at `5730572`
