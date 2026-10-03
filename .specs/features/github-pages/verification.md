# github-pages verification

**Verdict**: PASS
**Profile**: light
**Diff range**: 5730572..6411332fff75caca6ad35898a5a6f1000b38972b
**Round**: 1 - full
**Verifier**: independent sub-agent (author != verifier)

Scope: C1 to C15 (slices S1 to S3, AC 1 to 14) verified at `6411332`. C16 to C19 (S4, AC 15 to 18) are deferred to round 2, which runs against `https://augusto-dmh.github.io/mandato-aberto/` after the first green `publish.yml` run on `main`; no request was made to the live site in this round. Every proof was re-run at `HEAD` by the Verifier; `git status --porcelain` was empty before and after (0 lines both times).

Invocations (from the repository root):

- `npm --prefix site test -- tests/build.test.ts --reporter=verbose -t "base build writes the same files|every site path starts with the base|no base prefix without SITE_BASE|home lists in-exercise deputies as static links|profile share tags|footer links|report link on profile and roll call|share tags under the base|home island links under the base|report link keeps the site path|privacy cookies analytics and host|corrections e-mail on the four pages"` - exit 0; each of the 12 named tests printed individually as `✓` (6 under `github-pages S1 base path`, 1 under `github-pages S3 e-mail`, and the 5 pre-existing `S2 home`, `S5 share cards`, `launch S1`, `launch S2`, `launch S4` tests)
- `npm --prefix site test -- tests/search-island.test.ts --reporter=verbose -t "links each deputy under the base"` - exit 0; `✓ DeputySearch > links each deputy under the base` (1 passed, 1 skipped)
- `npm --prefix site test -- tests/urls.test.ts --reporter=verbose -t "withBase joins the base|site source writes no root link|root link scan names the file"` - exit 0; 3 passed, each printed
- every shell proof of C9 to C15 through `sh -c` exactly as written in `checks.md` - each exit 0

## Checks

| Check | Claim | Proof run | Evidence (`file:line` - assertion) | Result |
| --- | --- | --- | --- | --- |
| C1 | base build writes the same file paths as the main build, `_astro/` content hash removed | `T tests/build.test.ts -t "base build writes the same files"` exit 0 | `site/tests/build.test.ts:911` - `expect(unhashed(files)).toEqual(unhashed(tree(dist)))`; `:910` - `expect(files.length).toBeGreaterThan(18)` | PASS |
| C2 | every site path on the 9 pages starts with `/mandato-aberto/`, each page has one; 4 named literals | `T tests/build.test.ts -t "every site path starts with the base"` exit 0 | `site/tests/build.test.ts:928-929` - `expect(paths.length, file).toBeGreaterThan(0)`; `for (const path of paths) expect(path, file).toMatch(/^\/mandato-aberto\//)`; `:931-934` - `toContain('href="/mandato-aberto/metodologia/"')`, `toContain('href="/mandato-aberto/"')`, `toContain('src="/mandato-aberto/fotos/101.jpg"')`, `toContain('<a href="/mandato-aberto/votacoes/100-1/">/votacoes/100-1/</a>')` | PASS |
| C3 | main build: no site path starts with `/mandato-aberto/`; root literals hold; approved root-path checks green | `T tests/build.test.ts -t "no base prefix without SITE_BASE"` exit 0; the second C3 proof (`-t` alternation of the 4 approved test names) exit 0, 4 tests printed | `site/tests/build.test.ts:939` - `expect(path, file).not.toMatch(/^\/mandato-aberto\//)`; `:941-943` - `toContain('href="/deputados/101/"')`, `toContain('<a href="/metodologia/">Metodologia e fontes</a>')`, `toContain('src="/fotos/101.jpg"')`; `:407` - `expect(canonical(html)).toBe(\`${ORIGIN}/deputados/101/\`)` | PASS |
| C4 | base build share tags: profile canonical, `og:url`, `og:image`; home `og:image` and canonical | `T tests/build.test.ts -t "share tags under the base"` exit 0 | `site/tests/build.test.ts:948-953` - `expect(canonical(html)).toBe("https://augusto-dmh.github.io/mandato-aberto/deputados/101/")`, `expect(meta(html, "og:image")).toBe("https://augusto-dmh.github.io/mandato-aberto/cards/deputados/101.png")`, `expect(canonical(start)).toBe("https://augusto-dmh.github.io/mandato-aberto/")` | PASS |
| C5 | island links deputies under the base (SSR with stubbed base; base build home exactly 101, 102) | `T tests/search-island.test.ts -t "links each deputy under the base"` exit 0; `T tests/build.test.ts -t "home island links under the base"` exit 0 | `site/tests/search-island.test.ts:30-32` - `expect([...html.matchAll(/href="([^"]*)"/g)].map((m) => m[1])).toEqual(["/mandato-aberto/deputados/7/", "/mandato-aberto/deputados/8/"])`; `site/tests/build.test.ts:958` - `expect(linked).toEqual(["/mandato-aberto/deputados/101/", "/mandato-aberto/deputados/102/"])` | PASS |
| C6 | `withBase` table over 9 cases (3 bases x 3 inputs) | `T tests/urls.test.ts -t "withBase joins the base"` exit 0 | `site/tests/urls.test.ts:46` - `expect(withBase(path), \`${base} + ${path}\`).toBe(expected)` over the 9 literal rows at `:34-42` | PASS |
| C7 | profile 101 report link under the base keeps `p` base-less | `T tests/build.test.ts -t "report link keeps the site path"` exit 0 | `site/tests/build.test.ts:962-963` - `expect(basePage("deputados/101/index.html")).toContain('<a href="/mandato-aberto/reportar-erro/?p=/deputados/101/">Reportar erro nesta página</a>')` | PASS |
| C8 | root-link scan: 0 hits over `site/src/`; exactly `a.astro`, `b.vue`, `c.astro` over the fixture dir | `T tests/urls.test.ts -t "site source writes no root link"` and `-t "root link scan names the file"` exit 0, both printed | `site/tests/urls.test.ts:53` - `expect(findRootLinks(SRC, [join(SRC, "lib", "urls.ts"), join(SRC, "lib", "forbidden-terms.ts")])).toEqual([])`; `:62` - `expect(findRootLinks(dir)).toEqual([join(dir, "a.astro"), join(dir, "b.vue"), join(dir, "c.astro")])` | PASS |
| C9 | last four `uses:` are cache/save, configure-pages@v5, upload-pages-artifact@v3, deploy-pages@v4; `path: site/dist`; no `cloudflare/` | both C9 `sh -c` proofs exit 0 | `.github/workflows/publish.yml:52` - `- uses: actions/cache/save@v4`; `:56` - `- uses: actions/configure-pages@v5`; `:57-59` - `- uses: actions/upload-pages-artifact@v3` / `path: site/dist`; `:60` - `- uses: actions/deploy-pages@v4` | PASS |
| C10 | job `permissions` and `environment` | C10 `sh -c` proof exit 0 | `.github/workflows/publish.yml:18` - `permissions: {contents: read, pages: write, id-token: write}`; `:19` - `environment: {name: github-pages}` | PASS |
| C11 | job env `SITE_URL`, `SITE_BASE`; guard stays; no `vars.`, `secrets.`, `CF_ANALYTICS_TOKEN` | C11 `sh -c` proof exit 0 | `.github/workflows/publish.yml:21` - `SITE_URL: https://augusto-dmh.github.io`; `:22` - `SITE_BASE: /mandato-aberto`; `:40` - `run: if [ -z "$SITE_URL" ]; then echo "SITE_URL is not set" >&2; exit 1; fi` | PASS |
| C12 | no `continue-on-error`/`always()`/`failure()`; last step is deploy-pages | C12 `sh -c` proof exit 0 | `.github/workflows/publish.yml:60` - `      - uses: actions/deploy-pages@v4` is the last `^      - ` line (file ends at 60) | PASS |
| C13 | privacy page: GitHub Pages host sentence, no-cookie sentence, no `Cloudflare`, no `Analytics` in the whole HTML | `T tests/build.test.ts -t "privacy cookies analytics and host"` exit 0 | `site/tests/build.test.ts:682-687` - `expectHtml(html, ["O site não grava cookie nem guarda nada no seu navegador.", 'A hospedagem (GitHub Pages) processa as requisições sob a <a href="https://docs.github.com/pt/site-policy/privacy-policies/github-general-privacy-statement">política de privacidade do GitHub</a>.'])`; `expect(html).not.toContain("Cloudflare")`; `expect(html).not.toContain("Analytics")` | PASS |
| C14 | `CORRECTIONS_EMAIL` literal; `mailto:` on the 4 built pages | C14 `sh -c` proof exit 0; `T tests/build.test.ts -t "corrections e-mail on the four pages"` exit 0 | `site/src/lib/site.ts:13` - `export const CORRECTIONS_EMAIL = "mandatoaberto8@gmail.com";`; `site/tests/build.test.ts:970-971` - `for (const name of ["reportar-erro", "correcoes", "dados-e-privacidade", "quem-somos"]) expect(page(name), name).toContain("mailto:mandatoaberto8@gmail.com")` | PASS |
| C15 | balancing test names the e-mail as contact channel; no `Cloudflare` | C15 `sh -c` proof exit 0 | `research/03-teste-de-balanceamento-lgpd.md:5` - `**Canal de contato:** mandatoaberto8@gmail.com (correções e direitos do art. 18)` (exact line match, `grep -qxF`); `! grep -q Cloudflare` exit 0 | PASS |

C16 to C19 (S4, AC 15 to 18): deferred to round 2, not run, no verdict - they need the deployed site after the first green `publish.yml` run on `main`.

Each named test exists in the tree and was touched by this diff, except the four approved tests in C3's second proof (`home lists in-exercise deputies as static links` `build.test.ts:153`, `profile share tags` `:401`, `report link on profile and roll call` `:444`, `footer links` `:784`), which C3 deliberately names as unchanged regression guards for the main build.

## Launch replacement proofs

The dated paragraph "Superseded by github-pages (2026-09-27)" in `.specs/features/launch/checks.md` is purely additive: `git diff --numstat 5730572..HEAD` gives `10 0` for that file, and the original C30 (`:137`), C40 (`:172`), C42 (`:181`) and C43 (`:184`) texts are intact. The three replacement proofs, run through `sh -c` exactly as written in the paragraph, at `6411332`:

- launch C40 (new order proof, names `node-version: 24`) - exit 0
- launch C42 (guard, fixed `SITE_URL` and `SITE_BASE`, no `vars.` or `CF_ANALYTICS_TOKEN`) - exit 0
- launch C43 (no `continue-on-error`/`always()`/`failure()`, last step `deploy-pages@v4`) - exit 0

Launch C30's proof (same test name, now asserting C13's sentences) is C13's row above - exit 0.

## Swept existing re-read

- idempotency: holds - each run starts from a fresh checkout on `ubuntu-latest`, the ETL step is named "Build the data from an empty data/raw" (`publish.yml:34-36`), `npm run build` writes `site/dist` from scratch, and `upload-pages-artifact` ships `site/dist` whole (`publish.yml:57-59`); the "deploy-pages replaces the whole deployment" half is GitHub platform behaviour, not code
- concurrency: holds - `publish.yml:10-12` `concurrency:` / `group: publish` / `cancel-in-progress: false`
- observability: holds - the Actions log and the `deploy-pages` step (`publish.yml:60`); the e-mail on scheduled failure is platform behaviour. Note: `environment` carries no `url:` from the deploy step's `page_url`, so the environment page shows no link (cosmetic, not claimed)

## Faults injected

Not run: fault injection is a `standard`/`ui` step and this feature was approved under `light` (the `Profile:` line of `checks.md`).

## Coverage

Not recomputed: the `Coverage` recompute is a `standard`/`ui` step and this feature runs under `light`. The table in `checks.md` was read, not re-derived.

## Gate

- `npm --prefix site test` - 11 files, 121 passed, 0 failed
- `uv run --directory etl pytest -q` - 114 passed, 0 failed

## Judgments on the points the author flagged

- **C1 renegotiation (hash stripped).** Sound. The regex `/^(_astro\/[^/]+)\.[A-Za-z0-9_-]{8}(\.[a-z0-9]+)$/` only touches files directly under `_astro/` and only removes an 8-character segment before the extension; every other path is compared verbatim, and `toEqual` on the mapped arrays keeps multiplicity, so a missing, extra or renamed file still fails. A hash of any other length is not stripped, which can only make the test fail, never hide a difference. To judge this independently the Verifier made one transient pair of fixture builds under the git-ignored `site/.cache/` (the same place the suite builds; removed afterwards, porcelain unchanged): 43 files each, and the only differing paths were `_astro/Base.<hash>.css` and `_astro/DeputySearch.<hash>.js`, exactly the two the author named. AC 1's "the same files" is met as paths; contents are not claimed.
- **C3 renegotiation (site paths only).** Sound: the main build legitimately carries `https://github.com/augusto-dmh/mandato-aberto` (`REPO_URL`), which is not a site path; the C3 scan runs over every `.html` of the main build.
- **`astroBuild` drops `BASE_URL`.** Justified and harmless for the approved checks: the main build's base is `/` either way, and `publish.yml` sets no `BASE_URL`. It is a test-assembly difference, recorded as finding 5.
- **`SITE_URL` default kept at `https://mandatoaberto.org`.** No github-pages AC needs the default: AC 3 and AC 10 set `SITE_URL` explicitly (base build env; `publish.yml:21`). Plan `Flow` hop 1 already reads that way and `Landing` door 1 pins only the workflow env. Site C33 (`site/tests/data.test.ts:150`, "site origin defaults to the placeholder domain") is green in the gate. No AC left unmet.
- **Launch C30 test rewritten under the same name.** Acceptable: the dated paragraph in `launch/checks.md` states it, and the test now asserts AC 12's sentences plus both absences. The name "privacy cookies analytics and host" now asserts that analytics is absent, which reads oddly but is documented.

## Findings

Ranked; none changes a result.

1. **Level gap - the hydrated island's links (C5).** Both C5 proofs read server-rendered HTML (SSR with `vi.stubEnv`, and the base build's static `index.html`). Once the island hydrates and a filter changes, Vue re-renders the list from the client chunk, whose base is whatever Vite inlined. No test reads the client chunk. The Verifier's transient build shows it is correct today (`_astro/DeputySearch.<hash>.js` contains `` `/mandato-aberto/`.replace(/\/+$/,``) ``), but a regression there would pass the suite. Evidence: `site/tests/search-island.test.ts:30`, `site/tests/build.test.ts:958`, neither of which reads `_astro/*.js`.
2. **Precision gap - C2's "site path" covers `href`/`src` only (C2, AC 1).** The island's `component-url`/`renderer-url` attributes and the fonts' CSS `url(...)` are outside the definition at `site/tests/build.test.ts:896` (`/(?<![\w-])(?:href|src)="(\/(?!\/)[^"]*)"/g`). If Astro stopped prefixing them, the island would not hydrate and the fonts would 404 under the subpath, with C2 green. Observed correct in the Verifier's build (`component-url="/mandato-aberto/_astro/DeputySearch....js"`, `url(/mandato-aberto/_astro/source-serif-4-....woff2)`), not asserted.
3. **Precision gap - the root-link scan's forms (C8, AC 7).** `ROOT_LINK` at `site/tests/urls.test.ts:14` catches attribute literals and template-literal expressions, not a root path held in a variable (`href={path}` - the exact form `correcoes.astro` used before this diff) nor Markdown link syntax `[t](/x/)` in `.md` pages (scanned by extension, but that form is not matched). Neither exists in `site/src/` today (`grep -n '](/'` over `site/src/pages/metodologia.md` returns nothing), and C2's built-output check would catch the variable form on the 9 sampled pages. AC 7 only names `href="/` and `src="/`, so this is a gap in the checks, not a failed check.
4. **Uncovered diff - README still describes the Cloudflare deploy.** `README.md:45` says `publish.yml` publishes to Cloudflare Pages, `:51-54` list `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_ACCOUNT_ID`, a `SITE_URL` variable and `CF_ANALYTICS_TOKEN`, none of which `publish.yml` reads any more; `:24` does not mention `SITE_BASE`. No check covers the README.
5. **Test-assembly difference - `astroBuild` strips `BASE_URL`** (`site/tests/build.test.ts:31`). Correct for vitest, but it means the suite never exercises what happens if a build environment exports `BASE_URL`; the author states Astro lets it override `base`. `publish.yml` sets none and GitHub runners do not set it by default, so nothing breaks today. Worth a comment in `publish.yml` or the README if the deploy moves.
6. **Stale docs in the diff.**
   - `.specs/STATE.md` Handoff at HEAD still reads "plan written and validated ... no checks, no code" and "a builder pane derives `checks.md`, builds", but checks, code and this verification exist.
   - `astro.config.mjs:6` still documents `_redirects` as "for Cloudflare Pages". The base build writes `https://www.augusto-dmh.github.io/* https://augusto-dmh.github.io/:splat 301` into `dist/_redirects`, which GitHub Pages serves as a plain public file (harmless, as plan `Out of scope` says).
7. **Plan/launch paragraph inconsistency on launch C69.** Plan `Impact` lists C69 among the launch checks that "stand". The dated paragraph in `.specs/features/launch/checks.md` (the `C63, C64, C69` bullet) rewrites C69's proof, and `checks.md` Coverage says "launch 69 by C18". The rewrite is needed (the old proof assumed root paths), so the plan's `Impact` row is the stale one. This matters for round 2's scope.
8. **Sampling note (C2, C7).** The base-path claims read profile 101 and roll call 100-1 out of 3 profiles and 8 roll calls in the fixture. The pages share one template each and C3 scans every main-build page, so this is acceptable. The base build itself is never scanned whole; only the 9 listed pages are.

## Round 2 - scoped, live site

**Verdict**: PASS
**Profile**: light
**Site**: `https://augusto-dmh.github.io/mandato-aberto/`
**Run**: `publish.yml` run `36359746320` (event `push`, head `1bede0c43a35c17c6690bdfedb92a9db6a7fc46e`, conclusion `success`, completed `2026-09-28T00:19:45Z`) - the first green `publish.yml` run on `main`, confirmed with `gh run list --workflow publish.yml --limit 15 --json databaseId,conclusion,status,headSha,createdAt,event` (the prior run, `36349392312`, concluded `failure`)
**Verifier**: independent sub-agent (author != verifier)

Every proof below is C16-C19 exactly as written in `checks.md`, run once through `sh -c` against the live host.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C16 | home answers `200`; `/nada/` answers `404` with `Página não encontrada` in the body | `sh -c` proof, `checks.md:92` - exit 0 | `curl -s -o /dev/null -w "%{http_code}" .../` -> `200`; `.../nada/` -> `404`; body of `/nada/` contains `Página não encontrada` | PASS |
| C17 | `http://` answers `301` to the `https://` URL | `sh -c` proof, `checks.md:95` - exit 0 | raw `curl -s -o /dev/null -w "%{http_code} %{redirect_url}\n" http://augusto-dmh.github.io/mandato-aberto/` -> `301 https://augusto-dmh.github.io/mandato-aberto/` | PASS |
| C18 | home and the first linked profile: no `Set-Cookie`, `og:image` under the base, `og:image` answers `200` | `sh -c` proof, `checks.md:98` - exit 0 | first linked id `204379`; home `og:image` `https://augusto-dmh.github.io/mandato-aberto/cards/site.png` -> `200`; profile `og:image` `https://augusto-dmh.github.io/mandato-aberto/cards/deputados/204379.png` -> `200`; no `set-cookie` header on either response | PASS |
| C19 | deployed `/reportar-erro/`, `/correcoes/`, `/dados-e-privacidade/` contain no `[a definir]` | `sh -c` proof, `checks.md:101` - exit 0 | `grep -qF "[a definir]"` found no match on any of the three pages | PASS |

## Findings (Round 2)

1. **Precision note - C19's page set.** C19 covers only `/reportar-erro/`, `/correcoes/` and `/dados-e-privacidade/`; it does not cover `/quem-somos/`. That page is checked separately by launch C68, which **fails** in this round: `/quem-somos/` still renders `<li>[a definir], [a definir]</li>` for the maintainers list. This is not a gap in C19 - the two checks were scoped to different page sets on purpose - but a reader of this file alone should know the go-live is not placeholder-free; see `.specs/features/launch/verification.md` Round 2 for the failing check and evidence. Both `checks.md`'s header line ("2 open, of which 1 blocks a placeholder-free go-live (maintainers' names, plan question 1)") and `plan.md:132` (open question 1) already record the maintainers' names as an undecided input, so this is expected, not a regression.
2. No other finding. All four checks in this feature's round-2 scope (C16-C19) pass with the evidence above.
