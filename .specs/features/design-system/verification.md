# design-system verification

**Verdict**: FAIL
**Profile**: ui
**Diff range**: c9b1a4a..37a8394069b66532497bdc3c9c2a3b2c93984083
**Round**: 4 - scoped
**Verifier**: independent sub-agent (author != verifier)

Scope: the diff `1b079cf..37a8394`, plus every round-3 verdict that was not PASS: C27 and the four maintainer questions. The round-3 report is committed at e4efd0b.

The diff contains three commits:
- aad89be dates the collection day in Brasília. It adds `formatCollected` and uses it in `SourceNote`, the card footer and the page footer.
- e4efd0b is the round-3 report.
- 37a8394 is the maintainer's choice. It removes `tokens/diario.json` and the Newsreader and Inter packages, adds AD-015, and marks the Assumptions rows as confirmed. It also points the tests at one direction and two font packages.

Proofs re-ran in full at 37a8394, in the real tree and again in a fresh CI-equivalent worktree:
- `npm run build`: exit 0.
- `npx vitest run tests/ --reporter=verbose`: 40 passed, 0 failed. Each named test is listed individually, including the new "SourceNote dates the collection in Brasília".
- `npx playwright test --reporter=list`: 13 passed, 0 failed.
- C27 command proof: exit 0.
- The CI-equivalent worktree ran `npm ci`, `npm ci --prefix ../site`, `npm test` and `npm run test:e2e`: 40 and 13 green. Only `archivo` and `source-serif-4` were installed under `@fontsource-variable`.

C27 now passes, and the maintainer answered all four open questions. The verdict is FAIL for one reason: a surviving mutant on a surface this diff created. The card footer and the page footer switched to the Brasília day, and no proof can tell them from the old UTC day (fault H6).

## Binding sources

Verified at 37a8394 for the rows this diff touched. The others are carried from 1b079cf.

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `research/05-grilling-escopo-v2.md` decision 10 | carried - yes | none | The validation step is closed by the maintainer's choice on the side-by-side prototype (AD-015, `research/decisions-log.md` 2026-10-02 row). |
| `06` section 5 / a1 section 5 (directions) | yes | none: AD-015 keeps Plenário = Archivo (width axis) + Source Serif 4, achromatic, one accent. That matches the direction as the research defines it, with the accent moved off gov.br blue (C34). AD-015 also records a1's discipline rule: never set the name in capitals, never use an extreme weight on a sentence about a person. | - |
| a1 section 6.1, score layout | yes - `plan.md:167` | none: the departure from the single full-width strip is now confirmed by the maintainer (`y`, and the `decisions-log.md` row). | - |
| Out of scope rows added in round 2 | yes - `plan.md:146-149`, `:168` | none: confirmed `y` by the maintainer | - |
| AC 22 scope (card outside 360 px) | yes - `plan.md:166` | none: confirmed `y` | - |
| `site/src/lib/format.ts` (collection date) | yes - `brasiliaParts` / `brasiliaLocal` | none: `formatCollected` (`design/components/format.js:14-17`) uses the MVP's fixed UTC-3 shift. | Two uses have no proof: the card footer (`design/screens/Card.vue:40`) and the page footer (`design/screens/Chrome.vue:23`). Every proof that renders them uses the fixture's `generatedAt` 12:00Z, where the UTC and Brasília days agree. See fault H6. |
| everything else | carried from 1b079cf | none | - |

Plan hygiene noted in round 3 is fixed. `plan.md:170` now records the four answers. The malformed `|| y |` cells are gone: `grep -c "|| y |"` returns 0.

## Checks

Rows C27, C1, C4, C8, C11, C20, C34 and C41 are verified at 37a8394. Their assertions or member sets changed in this diff. Every other row's evidence is carried from 1b079cf, and all its proofs re-ran green at 37a8394.

**Were any test edits weakening?** Each edit in 37a8394 was compared before and after.

- **`DIRECTIONS` in `e2e/screens.spec.ts:7` and `tests/prototype.test.ts:13`.** The edit only removes `diario`, which no longer exists. Every remaining page × theme is still enumerated.
- **The font url bound in `prototype.test.ts:223`, ≥ 8 changed to ≥ 4.** The bound is still tight. The rendered `fonts.css` holds exactly 4 urls (2 packages × latin and latin-ext), counted on a fresh render. The licence list stays an exact `toEqual` (`:229`).
- **The C4 near-floor value in `tokens.test.ts:85-92`.** It is now Plenário muted dark `oklch(0.59 0 0)` at 4.40:1. The verifier recomputed it independently: 4.403 against raised and 4.842 against paper. That sits 0.10 under the floor, against 0.39 before, so the test is stricter.
- **The block test in `tokens.test.ts:35`.** It became an exact `toEqual(["plenario"])`, so a leftover direction fails it (fault H4 killed).

No assertion was removed beyond members that no longer exist.

**Precision gap (documentation, non-blocking).** Some checks text still describes the state before the choice, while the tests assert the state after it:
- C1 claims "four blocks, `diario` light, `diario` dark, `plenario` light, `plenario` dark" (`.specs/features/design-system/checks.md:14`).
- C20 names `dist/prototype/diario/` (`:84`).
- Three Coverage rows list `diario` members (`:167`, `:175-177`).
- Plan AC 1 still says four blocks.
- The test name "defines every token in four blocks" now asserts two.

AC 25 and C27 define this transition, and the checks Handoff records it, so C1 and C20 are judged against the remaining member set. The text should still be amended.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | every token defined in its blocks (now Plenário light and dark, per AD-015) | vitest ✓ | `design/tests/tokens.test.ts:35` `toEqual(["plenario"])`; `dist/tokens.css` markers are `/* plenario light */` (line 23) and `/* plenario dark */` (line 82). Per-variable assertions carried. | PASS |
| C2 | pairs meet floors | vitest ✓ | carried (`tokens.test.ts:57-58`); 12 Plenário pairs | PASS |
| C3 | text × surface complete | vitest ✓ | carried | PASS |
| C4 | a below-floor pair fails the build | vitest ✓ | `tokens.test.ts:92` `toContain("plenario dark muted on raised: 4.40:1 is below 4.5:1")`; `:93` `not.toContain("muted on paper")` | PASS |
| C5 | one accent, no valence or party names | vitest ✓ | carried | PASS |
| C6 | ≤ 10 type sizes | vitest ✓ | carried | PASS |
| C7 | tabular figures | vitest ✓; playwright ✓ | carried | PASS |
| C8 | local OFL fonts, no third-party host | vitest ✓; playwright ✓ | `prototype.test.ts:223` (exactly 4 urls); `:229` `toEqual(["LICENSE-archivo.txt", "LICENSE-source-serif-4.txt"])`; `e2e/screens.spec.ts:20` carried | PASS |
| C9-C10 | n de m; empty base | vitest ✓; playwright ✓ | carried | PASS |
| C11 | NDeM and SourceNote links | vitest ✓ ×3 | carried; `SourceNote` now formats `collectedAt` with `formatCollected`, and these assertions are unchanged and green | PASS |
| C12-C19 | vote cases, score, photo, initials, tally, AI frame, forbidden terms | vitest ✓; playwright ✓ | carried | PASS |
| C20 | screens written for the direction(s) | vitest ✓ | `prototype.test.ts` "writes three screens per direction" over `DIRECTIONS = ["plenario"]` (`:13`); file list and h1 assertions carried | PASS |
| C21-C26 | schema version, no JS, motion, 360 px, card fit, theme | vitest ✓; playwright ✓ | carried; one direction × all screens and themes | PASS |
| C27 | `tokens/` holds `base.json` and the chosen file; AD-015 recorded | `test "$(ls tokens \| sort \| tr '\n' ' ')" = "base.json plenario.json " && grep -q '^\| AD-015 \|' ../.specs/STATE.md` exit 0 | `design/tokens/` = `base.json plenario.json`; `.specs/STATE.md:21` `\| AD-015 \| The v2 design system has one direction, "Plenário" ...`. Faults H4 and H5 killed. | PASS |
| C28 | README and package exports | vitest ✓ ×2 | carried; README records AD-015 | PASS |
| C29-C40 | screen empty states, groups, arrangements, mat, accents, digits, notes, ticks, card template and composition | vitest ✓; playwright ✓ | carried. C34 now loops over the Plenário accent only, which is the remaining member. | PASS |
| C41 | score note with source and Brasília date; stat markers | vitest "score and stats carry source notes" ✓; vitest "SourceNote dates the collection in Brasília" ✓ | `components.test.ts:164` `toContain("dados de 27/09/2026")` for `2026-09-28T02:00:00Z`; `:166` `toContain("dados de 28/09/2026")` for `03:00:00Z`; the prototype assertions are carried. Faults H1 and H2 killed. | PASS |
| C42-C43 | no start date without a period; accent only on interaction | vitest ✓ | carried | PASS |

## Coverage

Verified at 37a8394 for the sets this diff resized or created. The others are carried from 1b079cf and re-ran green.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| directions after AD-015 (1) | `design/tokens/` and AD-015 | Plenário -> C1, C27 | - |
| token blocks (2) | `dist/tokens.css` | Plenário light, Plenário dark -> C1 | - |
| declared contrast pairs (12) | `pairs()` over `plenario.json` | C2 | - |
| font packages (2) | `design/package.json`, fresh `npm ci` | archivo, source-serif-4 -> C8 | - |
| pages at 360 px (4), reduced motion (3), theme (6) | 1 direction × screens × themes | C24, C23, C26 | - |
| uses of the Brasília collection day (3) | `grep formatCollected` | `SourceNote.vue:16` -> C41 (`components.test.ts:164-166`) | `Card.vue:40` (card footer) and `Chrome.vue:23` (page footer): every proof renders them at 12:00Z, where the UTC and Brasília days coincide. Fault H6 survived. |
| proof environments (2) | the real tree, and a fresh worktree running the CI job's steps | 40 + 13 in both | - |
| all other sets | carried from 1b079cf | as in round 3 | - |

## Test policy rows

Verified at 37a8394 for the rows classifying touched files. The rest are carried.

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Token build | `tokens/*.json`, `scripts/build-tokens.mjs` | own layer: every pair; one failing case | yes. The failing case is tighter (4.40:1). |
| Vote option mapping | `components/vote.js` | own layer | yes (carried) |
| Components | `components/*.vue`, `components/format.js` (`formatCollected`) | render test, normal and edge case | yes for `SourceNote`, both sides of the UTC-3 day boundary |
| Layout and computed style | `screens/*.vue` (Card and Chrome footers touched) | browser test per listed member | no: the date the card and page footers print has no proof that tells Brasília from UTC (H6). C40 only matches `\d{2}/\d{2}/\d{4}`, at `e2e/screens.spec.ts:239`. |
| Prototype script | `scripts/prototype.mjs` (font list), `scripts/render.js` | own layer, child process | yes |

Swept rows: the C27 state transition happened, with exactly two token files and AD-015 present. The validation and failure paths are unchanged.

## Faults injected

Verified at 37a8394.

Scratch: `git worktree add --detach <scratchpad>/wt4 HEAD`, then `npm ci` and `npm ci --prefix ../site`. The real tree's `git status --porcelain` was empty before. After `git worktree remove --force` it was still empty, and `git worktree list` showed only the real tree.

| Mutation | Location | Killed |
| --- | --- | --- |
| H1: collection day without the UTC-3 shift (`- 0`) | `design/components/format.js:15`; "SourceNote dates the collection in Brasília" | yes: `... to contain 'dados de 27/09/2026'` |
| H2: shift of 2 h instead of 3 h | `design/components/format.js:15`; same test | yes: `... to contain 'dados de 27/09/2026'` |
| H4: `tokens/diario.json` restored next to `plenario.json` | `design/tokens/`; C27 command proof and "defines every token in four blocks" | yes: C27 exit 1; vitest 1 failed |
| H5: the AD-015 row removed from `.specs/STATE.md` | `.specs/STATE.md:21`; C27 command proof | yes: exit 1 |
| H6: card footer back to `formatDate(generatedAt)`, the UTC day | `design/screens/Card.vue:40`; full vitest and playwright suites | **no**: 40 passed, 13 passed |

## Gate

- `npm run build`: exit 0.
- `npx vitest run tests/ --reporter=verbose`: 40 passed, 0 failed.
- `npx playwright test --reporter=list`: 13 passed, 0 failed.
- C27 command proof: exit 0.
- CI-equivalent worktree: 40 + 13 passed.
- `python3 /home/augusto/.claude/skills/tlc-spec-lean/scripts/validate_verification.py design-system`: exit 1, "verdict is FAIL - route the ranked gaps back as fixes, then re-verify" (1 error, 0 warnings; no row contradicts the verdict).
