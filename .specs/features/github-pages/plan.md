# github-pages: publish the site on GitHub Pages under a subpath

## Problem

The `launch` feature is merged (PR #8, 2026-09-27) and nobody can visit the site. Its deploy step targets Cloudflare Pages (AD-010), which needs an account, an API token and the `.org` domain, and on 2026-09-27 the maintainer decided not to register a domain nor open a Cloudflare account for now: the "professional deploy" comes later. Meanwhile `publish.yml` runs on every push to `main` and every day at 09:00 UTC, downloads the sources, and stops at the `SITE_URL` guard.

GitHub Pages is already enabled on the repository with build type "workflow", HTTPS enforced and the URL `https://augusto-dmh.github.io/mandato-aberto/` (API read 2026-09-27). The site cannot be served there as it is: every link it writes is root-absolute (`/deputados/123/`, `/metodologia/`, 24 occurrences across 12 files under `site/src/`, counted 2026-09-27), so under `/mandato-aberto/` each one would leave the site. The privacy page names Cloudflare as host and as analytics provider, and the dedicated e-mail is still `[a definir]`.

When this ships, merging to `main` deploys the site to `https://augusto-dmh.github.io/mandato-aberto/` with the repository's own token, every link and share image works under the subpath, the privacy page names GitHub Pages and no analytics, the e-mail reads `mandatoaberto8@gmail.com`, and the daily rebuild publishes. The domain, a host without a bandwidth cap, headers and analytics stay for the later deploy; moving is one workflow step and one base setting.

## Flow

Reuses the whole site and the whole `publish.yml` up to the deploy step; nothing here touches the ETL or the contract.

1. `site/astro.config.mjs` (exists) - `site` defaults to `https://augusto-dmh.github.io` and `base` comes from `SITE_BASE` (default `/`, door 2); the `_redirects` integration stays as it is
2. `site/src/lib/urls.ts` (door 2) - `withBase(path)` joins `import.meta.env.BASE_URL` and a site path; every root-absolute link in `Base.astro`, `Prose.astro`, the six pages, `DeputySearch.vue` and `photos.ts` (all exist) goes through it
3. `site/src/layouts/Base.astro` (exists) - canonical, `og:url` and `og:image` are built from `Astro.site`, the base and the page path
4. `site/src/pages/dados-e-privacidade.astro` and `site/src/lib/site.ts` (both existing) - host copy without Cloudflare and without analytics; `CORRECTIONS_EMAIL` filled
5. `.github/workflows/publish.yml` (exists) - the Cloudflare step is replaced by the three GitHub Pages actions (door 1); `SITE_URL` and `SITE_BASE` are written in the workflow env
6. `.specs/features/launch/checks.md` (exists) - the checks this supersedes are renegotiated in a dated paragraph, never deleted; `.specs/STATE.md` (exists) - AD-012 supersedes AD-010
7. out: `dist/` unchanged in layout (Astro writes `base` into URLs, not into output paths), deployed by `actions/deploy-pages`

## Impact

| Front | What changes |
| --- | --- |
| domain | existing term: `SITE_URL` meant the site's full origin at the root; it stays the origin, and the path under it is a new setting, `SITE_BASE`; `Base.astro` is the only consumer of `Astro.site` (checked 2026-09-27) |
| domain | new term: `withBase` - the one way a page writes a site-relative link; a bare `href="/..."` in `site/src/` is a defect after this |
| copy | `/dados-e-privacidade/` stops naming Cloudflare as host and drops the analytics sentence; `<CORRECTIONS_EMAIL>` renders as `mandatoaberto8@gmail.com` on the four pages that show it |
| other features | `launch`: checks C30, C40, C42, C43, C63, C64 change text; C65 and C66 become `n/a` for the subpath deploy (no domain to `whois`, no custom headers on GitHub Pages); C46, C48, C49, C62, C67, C68, C69 stand. Each change is a dated paragraph in `launch/checks.md` |
| other features | `site`: approved checks unchanged - the fixture build keeps `SITE_BASE` unset, so every pinned root path still holds |
| decisions | AD-010 becomes `superseded by AD-012`; AD-011 (candidacy export) unchanged; the `launch` analytics door stays with the token never set |
| stored data | nothing to migrate; the photo cache and `data/out/` are rebuilt as before |
| repository settings | none - Pages is already enabled with build type "workflow"; no secret, no variable |

## Relations

`None - no stored-data shape change`.

## Surface

Every existing route moves under the base. Only the changed signatures are listed.

| Route | In | Out | Status |
| --- | --- | --- | --- |
| `GET /mandato-aberto/<every route of site and launch>` | as before | as before, links prefixed with `/mandato-aberto/` | `200` |
| `GET /mandato-aberto/<no page>` | any | the content of `dist/404.html` | `404` |
| `GET http://augusto-dmh.github.io/mandato-aberto/*` | any | redirect to `https://augusto-dmh.github.io/mandato-aberto/*` | `301` |

## Landing

| One-way door | Literal shape | Alternative rejected |
| --- | --- | --- |
| Static host, superseding launch door 1 | GitHub Pages project site at `https://augusto-dmh.github.io/mandato-aberto/`; in `publish.yml` the job gets `permissions: {contents: read, pages: write, id-token: write}` and `environment: {name: github-pages}`, and after `actions/cache/save` runs `actions/configure-pages@v5`, `actions/upload-pages-artifact@v3` with `path: site/dist`, then `actions/deploy-pages@v4` as the last step; the job `env` carries `SITE_URL: https://augusto-dmh.github.io` and `SITE_BASE: /mandato-aberto`; no repository secret or variable | Cloudflare Pages (AD-010) - an account, a token and a domain the maintainer does not want now; the user-site repository `augusto-dmh.github.io` fed by a force-pushed `dist/` - 313 MB of new blobs into a git repository every day, and it takes over the maintainer's personal page |
| Base path handling | `base: process.env.SITE_BASE \|\| "/"` next to `trailingSlash: "always"` in `astro.config.mjs`; `withBase(path)` in `site/src/lib/urls.ts` returns `import.meta.env.BASE_URL` joined with `path` with exactly one slash between them, and returns `path` unchanged when the base is `/`; the report link keeps its `p` value as the base-less site path; tests and CI build with `SITE_BASE` unset, and one test builds with `SITE_BASE=/mandato-aberto` | relative links (`../deputados/1/`) - depth-dependent, and the Vue island cannot know its depth; rewriting `href`s in `dist/` after the build - Astro already prefixes its own asset URLs, and a post-processor cannot see the island's props |

- Nothing else in this change is hard to reverse

## Criteria

### S1: The site works under a base path (P1)

Every link, image and share tag points inside `/mandato-aberto/` when the base is set, and nothing changes when it is not.

**Acceptance Criteria**

1. WHEN `npm run build` runs with `SITE_BASE=/mandato-aberto` THEN the system SHALL write the same files under `dist/` as without it, and every `href` or `src` that names a site path on the home, profile 101, roll call 100-1, `404.html` and the five launch pages SHALL start with `/mandato-aberto/`
2. WHEN `SITE_BASE` is unset THEN the built pages SHALL contain the same root paths as today, so every approved `site` and `launch` check passes unchanged
3. WHEN the build runs with `SITE_URL=https://augusto-dmh.github.io` and `SITE_BASE=/mandato-aberto` THEN profile 101 SHALL carry canonical and `og:url` `https://augusto-dmh.github.io/mandato-aberto/deputados/101/` and `og:image` `https://augusto-dmh.github.io/mandato-aberto/cards/deputados/101.png`
4. WHILE the home's search island runs the system SHALL link each listed deputy to `withBase("/deputados/{id}/")`
5. `withBase("/")` SHALL return `/mandato-aberto/`, `withBase("/deputados/1/")` SHALL return `/mandato-aberto/deputados/1/` and `withBase("/cards/site.png")` SHALL return `/mandato-aberto/cards/site.png` when the base is `/mandato-aberto/` or `/mandato-aberto`; with base `/` each SHALL return its input
6. WHEN profile 101 renders under the base THEN its report link SHALL be `/mandato-aberto/reportar-erro/?p=/deputados/101/`
7. IF a file under `site/src/` other than `lib/urls.ts` and `lib/forbidden-terms.ts` contains `href="/` or `src="/` outside a `withBase` call THEN a test SHALL fail naming the file

**Independent test:** build the fixture with `SITE_BASE=/mandato-aberto`, open `dist/index.html` and follow every link by hand: each starts with `/mandato-aberto/`.

### S2: Deploy from the workflow (P1)

Merging to `main` publishes the site with the repository's own token.

**Acceptance Criteria**

8. WHEN `publish.yml` runs THEN after `actions/cache/save` the system SHALL run `actions/configure-pages@v5`, `actions/upload-pages-artifact@v3` with `path: site/dist` and `actions/deploy-pages@v4`, in that order and as the last three steps, with no step from `cloudflare/`
9. The job SHALL declare `permissions` with `contents: read`, `pages: write` and `id-token: write`, and `environment` `github-pages`
10. The job `env` SHALL carry `SITE_URL: https://augusto-dmh.github.io` and `SITE_BASE: /mandato-aberto`, and the `SITE_URL` guard step SHALL stay
11. IF any step exits non-zero THEN the workflow SHALL deploy nothing and the previous deployment SHALL stay live (launch AC 40, unchanged)

**Independent test:** merge and watch the run; the `deploy-pages` step prints the page URL and `https://augusto-dmh.github.io/mandato-aberto/` serves the home with the day's collection date.

### S3: Copy and identity (P1)

The privacy page tells the truth about the host, and the e-mail is real.

**Acceptance Criteria**

12. The `dados-e-privacidade` page SHALL contain `A hospedagem (GitHub Pages) processa as requisições sob a <a href="https://docs.github.com/pt/site-policy/privacy-policies/github-general-privacy-statement">política de privacidade do GitHub</a>.`, SHALL keep `O site não grava cookie nem guarda nada no seu navegador.`, and SHALL not contain `Cloudflare` nor `Analytics`
13. The constant `CORRECTIONS_EMAIL` in `site/src/lib/site.ts` SHALL be `mandatoaberto8@gmail.com`, and the built `/reportar-erro/`, `/correcoes/`, `/dados-e-privacidade/` and `/quem-somos/` SHALL contain `mailto:mandatoaberto8@gmail.com`
14. The `research/03-teste-de-balanceamento-lgpd.md` file SHALL name `mandatoaberto8@gmail.com` as the contact channel

**Independent test:** read the built privacy page; the host sentence is there, no Cloudflare, and the four pages carry the address.

### S4: Live under the subpath (P1)

Run against the deployed site after the first green run.

**Acceptance Criteria**

15. WHEN `https://augusto-dmh.github.io/mandato-aberto/` is requested THEN the host SHALL answer `200` with the home, and `https://augusto-dmh.github.io/mandato-aberto/nada/` SHALL answer `404` with `Página não encontrada`
16. WHEN `http://augusto-dmh.github.io/mandato-aberto/` is requested THEN the host SHALL answer `301` to `https://augusto-dmh.github.io/mandato-aberto/`
17. WHEN the home and a profile are requested THEN the responses SHALL carry no `Set-Cookie` header, and each `og:image` SHALL start with `https://augusto-dmh.github.io/mandato-aberto/` and answer `200`
18. The deployed `/reportar-erro/`, `/correcoes/` and `/dados-e-privacidade/` SHALL contain no `[a definir]`

**Independent test:** open the four URLs in a browser with the storage panel open; no cookie appears, every link stays under `/mandato-aberto/`.

## Out of scope

| Excluded | Why |
| --- | --- |
| A custom domain, `www`, WHOIS | the maintainer deferred the domain on 2026-09-27; when it comes, `SITE_URL` and `SITE_BASE` change and the host may change with it |
| Cloudflare Pages, security headers, analytics | the "professional deploy" later; GitHub Pages cannot set headers, and the analytics token stays unset |
| A redirect from `github.io/mandato-aberto` to the future domain | decided when the domain exists |
| Removing the `_redirects` integration and `_headers` | unused on GitHub Pages but harmless, and launch C46 pins them |

## Assumptions

| Assumption | Chosen default | Rationale | Confirmed? |
| --- | --- | --- | --- |
| Approval of this plan | given as the maintainer's instruction in chat on 2026-09-27 ("Go ahead with GitHub Pages") after the trade-offs were laid out; the plan is reviewed in the PR | five days to the target date | y (2026-09-27) |
| Setting name | `SITE_BASE`, default `/`; `SITE_URL` keeps its meaning as the origin | one new variable next to the existing one | n |
| Island base | the Vue island reads `import.meta.env.BASE_URL` at build time through `withBase`, no prop | Vite exposes it to client code | n |
| Report `p` value | stays the base-less site path (`/deputados/101/`), so the mail subject reads `Erro em /deputados/101/` | the path identifies the record, not the host | n |
| Maintainers' names | stay `[a definir]` in `site/src/lib/site.ts` and in the balancing test until the maintainer decides (open question 1); launch C68 and AC 18 here keep rejecting the placeholder on the deployed site | the legal research requires natural persons named on Quem somos; "Mandato Aberto" is the site, not a maintainer | n |
| Pages already enabled | no `enablement` flag on `configure-pages`; if a run reports Pages disabled, add `enablement: true` | the API showed `build_type: workflow` on 2026-09-27 | n |
| Verification profile | `light`; one round covers AC 1 to 14 at `HEAD`, a scoped round covers AC 15 to 18 after the first deploy | project default | n |

**Open questions:**

| # | Kind | Question | Until answered |
| --- | --- | --- | --- |
| 1 | blocks go-live | Full names and city of the maintainers on Quem somos, or a deliberate anonymous launch against the research's advice | `/quem-somos/` shows `[a definir]`; launch C68 fails on it |
| 2 | open | The TSE file for the candidacy badge | the badge stays off; the site works without it |

## Observable

| Surface | Decision | Landing |
| --- | --- | --- |
| all screens | links under the base | AC 1, AC 4, AC 6 |
| all screens | empty, loading, error, unauthorised states | n/a - unchanged from the site and launch plans |
| screen 404 | every state | AC 15 - served by the host under the subpath |
| document privacy copy | structure, tone, what next | AC 12 - one host sentence, no analytics claim |
| command `npm run build` | new setting and its default | AC 2, AC 5 - `SITE_BASE`, default `/` |
| command `npm run build` | exit codes, failing halfway | existing - unchanged |
| scheduled task `publish.yml` | steps, permissions, env | AC 8 to 10 |
| scheduled task `publish.yml` | failing halfway | AC 11 |
| API | n/a - no route takes new input |

## Sources

- Maintainer's decisions in chat, 2026-09-27: no domain and no Cloudflare for now; e-mail `mandatoaberto8@gmail.com`
- `.specs/features/launch/plan.md` doors 1, 5, 6 and AC 36 to 48 - what this supersedes and what stands
- `.specs/STATE.md` AD-007, AD-010, AD-011
- GitHub API `repos/augusto-dmh/mandato-aberto/pages`, read 2026-09-27 - Pages enabled with build type "workflow"
