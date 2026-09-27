# github-pages checks

Profile: light
Plan: `.specs/features/github-pages/plan.md`

19 checks in 4 slices · 2 one-way doors (static host on GitHub Pages, base path handling), approved by the maintainer's instruction in chat on 2026-09-27 · 2 open, of which 1 blocks a placeholder-free go-live (maintainers' names, plan question 1)

All proofs run from the repository root. `T` abbreviates `npm --prefix site test --` (vitest) and `E` abbreviates `uv run --directory etl pytest`. Shell proofs run under `sh -c` with GNU grep.

Every page check reads one of two `astro build`s of the fixture contract, both in `site/tests/build.test.ts`:

- the **main build**, as today: `SITE_URL=https://preview.example.org`, `SITE_BASE` unset; every approved `site` and `launch` check keeps reading it unchanged
- the **base build**, new: `SITE_URL=https://augusto-dmh.github.io`, `SITE_BASE=/mandato-aberto`, same fixture contract, photo cache and corrections fixture, `outDir` under `site/.cache/` like the other extra builds

A **site path** in a built page is the value of an `href` or `src` attribute that starts with `/` and not with `//`.

S4 runs against the deployed site after the first green `publish.yml` run on `main`. The Verifier's round 1 covers C1 to C15 at `HEAD`; a scoped round 2 covers C16 to C19 together with launch C62, C63, C64, C67, C68 and C69 as renegotiated in `.specs/features/launch/checks.md`.

Interpretations settled while deriving:

- AC 7 names `href="/` and `src="/`; the scan also rejects the expression forms that write the same root link without the literal quote (`href={\`/`, `src={\`/`, `:href="\`/`, `:src="\`/`), since the profile and roll-call templates write their links that way today. An attribute whose value starts with `withBase(` is the allowed form.
- AC 12 forbids `Cloudflare` and `Analytics` on the privacy page; the proof reads the whole built HTML of that page, not only its visible text.
- Launch C30's test (`privacy cookies analytics and host`) asserts the old Cloudflare copy, which AC 12 forbids. C30 is superseded by C13 (dated paragraph in `launch/checks.md`); its test keeps its name and now asserts C13's sentences, so C30's proof stays executable against the renegotiated claim.

Renegotiated during build (2026-09-27, checks author against the approved plan, before either proof was green):

- C1: the base build writes the same paths as the main build except the content hash of the two `_astro/` assets whose content carries the base (`Base.<hash>.css`, whose font URLs Vite prefixes, and `DeputySearch.<hash>.js`, into which Vite inlines `BASE_URL`); a byte-for-byte equal name is impossible when the content must differ. The claim compares paths with the hash removed, which is what the plan's `Flow` hop 7 states ("`dist/` unchanged in layout"). Flagged for the maintainer.
- C3: the main build legitimately contains `/mandato-aberto/` inside `https://github.com/augusto-dmh/mandato-aberto/...` (the balancing-test and source links); the claim now reads site paths only, as C2 does.
- Test assembly: vitest puts `BASE_URL=/` in its own environment and Astro lets a `BASE_URL` in the environment override the configured base while prerendering, so `astroBuild` in `build.test.ts` drops `BASE_URL` from what the child build inherits. `publish.yml` sets no `BASE_URL`.

## Checks

### S1 - The site works under a base path · 14 files · 75 KB · ~19k

**C1** - The base build exits `0` and the sorted list of file paths under its `dist/` equals the sorted list under the main build's `dist/`, once the content hash is removed from each `_astro/<name>.<hash>.<ext>` (AC 1)
Proof: `T tests/build.test.ts -t "base build writes the same files"`

**C2** - In the base build, every site path in `index.html`, `deputados/101/index.html`, `votacoes/100-1/index.html`, `404.html`, `metodologia/index.html`, `quem-somos/index.html`, `dados-e-privacidade/index.html`, `correcoes/index.html` and `reportar-erro/index.html` starts with `/mandato-aberto/`, and each of those 9 pages has at least one site path; the home contains `href="/mandato-aberto/metodologia/"` and `href="/mandato-aberto/"`, profile 101 contains `src="/mandato-aberto/fotos/101.jpg"` and `/correcoes/` contains `<a href="/mandato-aberto/votacoes/100-1/">/votacoes/100-1/</a>` (AC 1)
Proof: `T tests/build.test.ts -t "every site path starts with the base"`

**C3** - In the main build no site path in any `.html` file under `dist/` starts with `/mandato-aberto/`, the home contains `href="/deputados/101/"` and `<a href="/metodologia/">Metodologia e fontes</a>`, and profile 101 contains `src="/fotos/101.jpg"` (AC 2); the approved checks that pin root paths stay green unchanged
Proof: `T tests/build.test.ts -t "no base prefix without SITE_BASE"`
Proof: `T tests/build.test.ts -t "home lists in-exercise deputies as static links|profile share tags|footer links|report link on profile and roll call"`

**C4** - In the base build profile 101 carries canonical `https://augusto-dmh.github.io/mandato-aberto/deputados/101/`, `og:url` `https://augusto-dmh.github.io/mandato-aberto/deputados/101/` and `og:image` `https://augusto-dmh.github.io/mandato-aberto/cards/deputados/101.png`, and the home carries `og:image` `https://augusto-dmh.github.io/mandato-aberto/cards/site.png` and canonical `https://augusto-dmh.github.io/mandato-aberto/` (AC 3)
Proof: `T tests/build.test.ts -t "share tags under the base"`

**C5** - The search island, rendered with `import.meta.env.BASE_URL` stubbed to `/mandato-aberto/` over deputies 7 and 8 in exercise, links them as `href="/mandato-aberto/deputados/7/"` and `href="/mandato-aberto/deputados/8/"`; in the base build the home's deputy links are exactly `href="/mandato-aberto/deputados/101/"` and `href="/mandato-aberto/deputados/102/"`, in that order (AC 4)
Proof: `T tests/search-island.test.ts -t "links each deputy under the base"`
Proof: `T tests/build.test.ts -t "home island links under the base"`

**C6** - `withBase` is table-driven over 9 cases: with `BASE_URL` `/mandato-aberto/` and with `/mandato-aberto`, `"/"` -> `/mandato-aberto/`, `"/deputados/1/"` -> `/mandato-aberto/deputados/1/`, `"/cards/site.png"` -> `/mandato-aberto/cards/site.png`; with `BASE_URL` `/`, each of the three returns its input (AC 5)
Proof: `T tests/urls.test.ts -t "withBase joins the base"`

**C7** - In the base build profile 101 contains `<a href="/mandato-aberto/reportar-erro/?p=/deputados/101/">Reportar erro nesta página</a>` (AC 6)
Proof: `T tests/build.test.ts -t "report link keeps the site path"`

**C8** - The root-link scan over `site/src/` (minus `lib/urls.ts` and `lib/forbidden-terms.ts`) returns 0 hits; over a temporary directory holding `a.astro` with `<a href="/x/">`, `b.vue` with `<img src="/y.png">`, `c.astro` with ``<a href={`/z/${id}/`}>`` and `ok.astro` with `<a href={withBase("/x/")}>` it returns exactly the three hits naming `a.astro`, `b.vue` and `c.astro` (AC 7)
Proof: `T tests/urls.test.ts -t "site source writes no root link"`
Proof: `T tests/urls.test.ts -t "root link scan names the file"`

### S2 - Deploy from the workflow · 1 file · 3 KB · ~1k

**C9** - The last four `uses:` of `.github/workflows/publish.yml`, in file order, are `actions/cache/save@v4`, `actions/configure-pages@v5`, `actions/upload-pages-artifact@v3`, `actions/deploy-pages@v4`; the upload step carries `path: site/dist`; the file contains no `cloudflare/` (AC 8)
Proof: `sh -c 'test "$(grep -oE "uses: [^ ]+" .github/workflows/publish.yml | tail -n 4 | tr "\n" " ")" = "uses: actions/cache/save@v4 uses: actions/configure-pages@v5 uses: actions/upload-pages-artifact@v3 uses: actions/deploy-pages@v4 "'`
Proof: `sh -c 'grep -A2 "uses: actions/upload-pages-artifact@v3" .github/workflows/publish.yml | grep -q "path: site/dist" && ! grep -q "cloudflare/" .github/workflows/publish.yml'`

**C10** - The `publish` job declares `permissions: {contents: read, pages: write, id-token: write}` and `environment: {name: github-pages}` (AC 9)
Proof: `sh -c 'grep -qxF "    permissions: {contents: read, pages: write, id-token: write}" .github/workflows/publish.yml && grep -qxF "    environment: {name: github-pages}" .github/workflows/publish.yml'`

**C11** - The job `env` carries `SITE_URL: https://augusto-dmh.github.io` and `SITE_BASE: /mandato-aberto`; the step `if [ -z "$SITE_URL" ]; then echo "SITE_URL is not set" >&2; exit 1; fi` stays; the file contains no `vars.`, no `secrets.` and no `CF_ANALYTICS_TOKEN` (AC 10)
Proof: `sh -c 'grep -qxF "      SITE_URL: https://augusto-dmh.github.io" .github/workflows/publish.yml && grep -qxF "      SITE_BASE: /mandato-aberto" .github/workflows/publish.yml && grep -qF "if [ -z \"\$SITE_URL\" ]; then echo \"SITE_URL is not set\" >&2; exit 1; fi" .github/workflows/publish.yml && ! grep -qE "vars\.|secrets\.|CF_ANALYTICS_TOKEN" .github/workflows/publish.yml'`

**C12** - `publish.yml` contains no `continue-on-error`, no `always()` and no `failure()`, and its last step line is `      - uses: actions/deploy-pages@v4` (AC 11)
Proof: `sh -c '! grep -qE "continue-on-error|always\(\)|failure\(\)" .github/workflows/publish.yml && test "$(grep -E "^      - " .github/workflows/publish.yml | tail -n 1)" = "      - uses: actions/deploy-pages@v4"'`

### S3 - Copy and identity · 4 files · 12 KB · ~3k

**C13** - The built `/dados-e-privacidade/` of the main build contains `A hospedagem (GitHub Pages) processa as requisições sob a <a href="https://docs.github.com/pt/site-policy/privacy-policies/github-general-privacy-statement">política de privacidade do GitHub</a>.` and `O site não grava cookie nem guarda nada no seu navegador.`, and contains neither `Cloudflare` nor `Analytics` (AC 12)
Proof: `T tests/build.test.ts -t "privacy cookies analytics and host"`

**C14** - `site/src/lib/site.ts` contains `export const CORRECTIONS_EMAIL = "mandatoaberto8@gmail.com";`, and the main build's `/reportar-erro/`, `/correcoes/`, `/dados-e-privacidade/` and `/quem-somos/` each contain `mailto:mandatoaberto8@gmail.com` (AC 13)
Proof: `sh -c 'grep -qxF "export const CORRECTIONS_EMAIL = \"mandatoaberto8@gmail.com\";" site/src/lib/site.ts'`
Proof: `T tests/build.test.ts -t "corrections e-mail on the four pages"`

**C15** - `research/03-teste-de-balanceamento-lgpd.md` contains the header line `**Canal de contato:** mandatoaberto8@gmail.com (correções e direitos do art. 18)` and no `Cloudflare` (AC 14)
Proof: `sh -c 'grep -qxF "**Canal de contato:** mandatoaberto8@gmail.com (correções e direitos do art. 18)" research/03-teste-de-balanceamento-lgpd.md && ! grep -q Cloudflare research/03-teste-de-balanceamento-lgpd.md'`

### S4 - Live under the subpath (round 2, after the first deploy) · 0 files · ~1k

**C16** - `https://augusto-dmh.github.io/mandato-aberto/` answers `200` and `https://augusto-dmh.github.io/mandato-aberto/nada/` answers `404` with `Página não encontrada` in the body (AC 15)
Proof: `sh -c 'curl -s -o /dev/null -w "%{http_code}" https://augusto-dmh.github.io/mandato-aberto/ | grep -qx 200 && curl -s -o /dev/null -w "%{http_code}" https://augusto-dmh.github.io/mandato-aberto/nada/ | grep -qx 404 && curl -s https://augusto-dmh.github.io/mandato-aberto/nada/ | grep -q "Página não encontrada"'`

**C17** - `http://augusto-dmh.github.io/mandato-aberto/` answers `301` with `Location: https://augusto-dmh.github.io/mandato-aberto/` (AC 16)
Proof: `sh -c 'curl -s -o /dev/null -w "%{http_code} %{redirect_url}\n" http://augusto-dmh.github.io/mandato-aberto/ | grep -qx "301 https://augusto-dmh.github.io/mandato-aberto/"'`

**C18** - The home and the first profile it links answer with no `Set-Cookie` header, and each one's `og:image` starts with `https://augusto-dmh.github.io/mandato-aberto/` and answers `200` (AC 17)
Proof: `sh -c 'S=https://augusto-dmh.github.io/mandato-aberto; id=$(curl -s "$S/" | grep -o "href=\"/mandato-aberto/deputados/[0-9]*/\"" | head -1 | grep -o "[0-9][0-9]*"); for u in "$S/" "$S/deputados/$id/"; do curl -sI "$u" | grep -qi "^set-cookie" && exit 1; img=$(curl -s "$u" | grep -o "property=\"og:image\" content=\"[^\"]*\"" | grep -o "https://[^\"]*"); case "$img" in "$S/"*) ;; *) exit 1;; esac; curl -s -o /dev/null -w "%{http_code}" "$img" | grep -qx 200 || exit 1; done'`

**C19** - The deployed `/reportar-erro/`, `/correcoes/` and `/dados-e-privacidade/` contain no `[a definir]` (AC 18)
Proof: `sh -c 'for p in reportar-erro correcoes dados-e-privacidade; do curl -s "https://augusto-dmh.github.io/mandato-aberto/$p/" | grep -qF "[a definir]" && exit 1; done; true'`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| `GET /mandato-aberto/<every route of site and launch>` statuses (1) | 200 C1 (every file written) · 200 C16 (host) | - |
| `GET /mandato-aberto/<no page>` statuses (1) | 404 C1 (`404.html` written) · 404 C16 (host) | - |
| `GET http://augusto-dmh.github.io/mandato-aberto/*` statuses (1) | 301 C17 | - |
| Landing doors (2) | static host C9 · static host C10 · static host C11 · static host C12 · base path handling C1 · base path handling C2 · base path handling C6 | - |
| pages checked under the base (9) | home C2 · profile 101 C2 · roll call 100-1 C2 · 404 C2 · metodologia C2 · quem-somos C2 · dados-e-privacidade C2 · correcoes C2 · reportar-erro C2 | - |
| site-path attributes (2) | `href` C2 · `src` C2 | - |
| absolute URLs from `Astro.site` + base (3) | canonical C4 · `og:url` C4 · `og:image` C4 | - |
| `withBase` inputs (3) | `/` C6 · `/deputados/1/` C6 · `/cards/site.png` C6 | - |
| `withBase` bases (3) | `/mandato-aberto/` C6 · `/mandato-aberto` C6 · `/` C6 | - |
| link writers routed through `withBase` (5 places) | `.astro` templates C8 · `DeputySearch.vue` C5 · `photos.ts` C2 · `Base.astro` share tags C4 · correction record pages C2 | - |
| root-link scan forms (3) | `href="/` C8 · `src="/` C8 · expression form C8 | - |
| workflow steps after `actions/cache/save`, in order (3) | `configure-pages@v5` C9 · `upload-pages-artifact@v3` C9 · `deploy-pages@v4` C9 · last step C12 | - |
| job permissions (3) | `contents: read` C10 · `pages: write` C10 · `id-token: write` C10 | - |
| job `environment` (1) | `github-pages` C10 | - |
| job `env` (2) and the guard (1) | `SITE_URL` C11 · `SITE_BASE` C11 · guard C11 | - |
| pages carrying the e-mail (4) | reportar-erro C14 · correcoes C14 · dados-e-privacidade C14 · quem-somos C14 | - |
| privacy copy obligations (4) | host sentence C13 · no-cookie sentence C13 · no `Cloudflare` C13 · no `Analytics` C13 | - |
| startup config: `SITE_BASE` (4 assemblies) | main fixture build unset C3 · base fixture build C1 · `publish.yml` C11 · vitest via `getViteConfig` (reads `astro.config.mjs`, unset) C6 | - |
| startup config: `SITE_URL` (3 assemblies) | main fixture build C3 · base fixture build C4 · `publish.yml` C11 | - |
| launch checks superseded (9) | launch 30 by C13 · launch 40 by C9 · launch 42 by C11 · launch 43 by C12 · launch 63 by C16 · launch 64 by C17 · launch 65 n/a, no domain (host C9) · launch 66 n/a, no custom headers (host C9) · launch 69 by C18 | - |
| go-live checks (4) | home and 404 C16 · redirect C17 · cookie and og:image C18 · no placeholder C19 | - |

- Claims naming a route, a status or a header: C1 and C2 on the built `dist/`; C16, C17, C18 at the host - each has a proof that crosses the boundary
- No other check claims more than the cases its proof exercises

## Swept

- validation: C8 - a root link written outside `withBase` fails the suite naming the file; C11 - the `SITE_URL` guard stays
- failure modes: C12 - any failed step deploys nothing and leaves the previous deployment live (launch AC 40)
- idempotency: existing - every run rebuilds `data/out/` and `dist/` from empty and `deploy-pages` replaces the whole deployment with the uploaded artifact; C1 proves the base changes no file path
- authorization: C10 - the job holds `pages: write` and `id-token: write` only on `publish.yml`, which never runs on a pull request (launch check 47); C11 - no secret and no variable is read
- concurrency: existing - `concurrency: {group: publish, cancel-in-progress: false}` (launch check 44) keeps one deploy at a time
- data lifecycle: n/a - nothing is stored; the photo cache carries over as before (launch check 45)
- dependency failure: C12 - a GitHub Pages or Câmara outage fails the run and leaves the previous deployment live
- state transitions: n/a - no entity with a state changes
- observability: existing - the Actions log and the `deploy-pages` step's page URL; GitHub e-mails the owner when a scheduled run fails

## Handoff

- Size: S1 reads and edits `astro.config.mjs`, `Base.astro`, `Prose.astro`, `DeputySearch.vue`, `photos.ts`, the six `.astro` pages and the two dynamic pages, `build.test.ts` and `search-island.test.ts` (~75 KB = ~19k) plus ~6k of new code and tests; S2 `publish.yml` (3 KB, ~1k); S3 `site.ts`, the privacy page, `research/03` and `launch/checks.md` (~50 KB = ~12k); S4 0 files; plus ~30k to read `plan.md`, this file and the launch plan = ~68k, under the 150k budget - one builder
- Mechanism: one builder (fits; no ask)
- Verification: round 1 over C1 to C15 after the last commit; round 2, scoped to C16 to C19 and launch checks 62 to 69 as renegotiated, after the first green `publish.yml` run
- **Boundary:** C1-C15 closed at the commit that restores the `SITE_URL` default (one builder, round 1); C16-C19 wait for the first deploy
- **Settled mid-build:** the brief and `Flow` hop 1 asked for `site` to default to `https://augusto-dmh.github.io`, but approved site C33 (`tests/data.test.ts`, "site origin defaults to the placeholder domain") pins `https://mandatoaberto.org`, and no AC here needs the default because `publish.yml` and every build test set `SITE_URL`; the default stays, `Flow` hop 1 now says so, and the maintainer decides whether to renegotiate site C33. Observed at runtime: with `trailingSlash: "always"`, `import.meta.env.BASE_URL` is `/mandato-aberto/` for both `SITE_BASE=/mandato-aberto` and `SITE_BASE=/mandato-aberto/`, and `Astro.site` stays the bare origin; Vite inlines the same value into the island's client chunk
- **Abandoned:** comparing the base build's `dist/` file names byte for byte (C1) and scanning the whole main build for the string `/mandato-aberto/` (C3) - see "Renegotiated during build"
