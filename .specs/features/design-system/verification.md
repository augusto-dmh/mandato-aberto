# design-system verification

**Verdict**: PASS
**Profile**: ui
**Diff range**: c9b1a4a..f385c4ae0fb01c28ad08a94c1e855203e5057caf
**Round**: 5 - scoped
**Verifier**: independent sub-agent (author != verifier)

## Scope

This round covers the diff `37a8394..f385c4a` plus every verdict that was not PASS in round 4. The round-4 report is committed at 550d3c8.

The diff is f385c4a. It changes tests and specs only, and touches no product code:

- a new prototype test, "footers date the collection in Brasília", with `generatedAt` at 02:00Z;
- the C1 test renamed;
- C1, C20, the Coverage set rows and plan AC 1 amended for the single direction.

The round-4 verdicts that were not PASS:

- the Brasília-day member for the card footer and the page footer (fault H6 survived);
- the documentation precision gap.

## Proofs

All proofs re-ran in full at f385c4a:

- `npm run build`: exit 0.
- `npx vitest run tests/ --reporter=verbose`: 41 passed, 0 failed. Each named test is listed individually, including "footers date the collection in Brasília" and "defines every token in a light and a dark block per direction".
- `npx playwright test --reporter=list`: 13 passed, 0 failed.
- C27 command proof: exit 0.

## Result

Both footer faults are now killed. No check, set row, Test policy row or binding source is left open.

One wording leftover does not affect the gate. The phrases "four blocks" and "both directions" survive in C2, C24, C25, C31, C34, C39, the S3 heading and plan AC 2 / AC 22. Their tests already enumerate the one remaining direction. C1, C20 and every set row now carry an "after AD-015" note.

## Binding sources

Verified at f385c4a for the `site/src/lib/format.ts` row, which is the one the diff touched. Every other row is carried from 37a8394.

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `site/src/lib/format.ts` (MVP collection date, Brasília) | yes | none: both footers now proven (`design/tests/prototype.test.ts:208`, `:211-212`) | - |
| `research/05` decision 10; `06` sections 3-5; a1 sections 3-6; `etl/schema/*.json` | carried from 37a8394 - yes | none (AD-015 and the four maintainer answers recorded) | - |

## Checks

C1, C20 and C41 were verified at f385c4a. Every other row is carried from 37a8394, and all their proofs re-ran green at f385c4a.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | every token in a light and a dark block (Plenário, after AD-015) | vitest "defines every token in a light and a dark block per direction" ✓ | `design/tests/tokens.test.ts:32` test renamed; `:35` `toEqual(["plenario"])`; the per-variable assertions are carried | PASS |
| C2-C19 | tokens, components, vote encoding, photo, AI frame, forbidden terms | vitest ✓; playwright ✓ | carried from 37a8394 | PASS |
| C20 | three screens under `dist/prototype/plenario/` | vitest "writes three screens per direction" ✓ | carried assertions over `DIRECTIONS = ["plenario"]`; the claim text is amended | PASS |
| C21-C26 | schema version, no JS, motion, 360 px, card fit, theme | vitest ✓; playwright ✓ | carried | PASS |
| C27 | `tokens/` holds `base.json` and `plenario.json`; AD-015 recorded | command proof exit 0 | `design/tokens/` = `base.json plenario.json`; `.specs/STATE.md:21` holds the AD-015 row (carried) | PASS |
| C28-C40 | README, package, screen states, groups, arrangements, mat, accents, digits, notes, ticks, card | vitest ✓; playwright ✓ | carried | PASS |
| C41 | score note and both footers date the collection in Brasília | vitest "score and stats carry source notes" ✓, "SourceNote dates the collection in Brasília" ✓, "footers date the collection in Brasília" ✓ | `prototype.test.ts:208` card footer `toContain("dados de 27/09/2026")` for `2026-09-28T02:00:00Z`; `:211` page footer `toContain("coletados em 27/09/2026")` on profile and roll-call; `:212` `not.toContain("28/09/2026")`. H6 and H6b killed | PASS |
| C42-C43 | no start date without a period; accent only on interaction | vitest ✓ | carried | PASS |

## Coverage

The Brasília collection-day row was verified at f385c4a. Every other row is carried from 37a8394.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| uses of the Brasília collection day (3) | `grep formatCollected` | `SourceNote.vue:16` -> `components.test.ts:164-166` · `Card.vue:40` -> `prototype.test.ts:208` · `Chrome.vue:23` -> `prototype.test.ts:211-212` | - |
| directions (1), token blocks (2), contrast pairs (12), font packages (2) | carried from 37a8394 | C1, C2, C8, C27 | - |
| pages at 360 px (4), reduced motion (3), theme (6), arrangements (3), screen and component empty states, vote cases, groups, proof environments (2) | carried from 37a8394 | as in round 4 | - |

## Test policy rows

The "Layout and computed style" row was verified at f385c4a. The other rows are carried from 37a8394.

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Layout and computed style | `screens/Card.vue`, `screens/Chrome.vue` (footers) | a proof that tells the Brasília day from the UTC day | yes - `prototype.test.ts:198-215` renders at 02:00Z; H6 and H6b killed |
| Token build, Vote option mapping, Components, Prototype script | as in round 4 | as in round 4 | yes (carried from 37a8394) |

## Faults injected

Verified at f385c4a.

Scratch setup:

- `git worktree add --detach <scratchpad>/wt5 HEAD`;
- `npm ci`, then `npm ci --prefix ../site`, which is the CI job's install.

Tree checks:

- The real tree's `git status --porcelain` was empty before.
- After `git worktree remove --force` it was still empty, and `git worktree list` showed only the real tree.
- The narrowest covering test passed again on the restored scratch (1 passed).

| Mutation | Location | Killed |
| --- | --- | --- |
| H6: card footer back on the UTC day, `formatDate(generatedAt)` | `design/screens/Card.vue:40`; "footers date the collection in Brasília" | yes - `expected 'Fonte: Câmara dos Deputados, dados de…' to contain 'dados de 27/09/2026'` |
| H6b: page footer back on the UTC day, `formatDate(generatedAt)` | `design/screens/Chrome.vue:23`; same test | yes - `plenario profile: expected 'Dados abertos da Câmara dos Deputados…' to contain 'coletados em 27/09/2026'` |

## Gate

- `npm run build`: exit 0.
- `npx vitest run tests/ --reporter=verbose`: 41 passed, 0 failed.
- `npx playwright test --reporter=list`: 13 passed, 0 failed.
- C27 command proof: exit 0.
- `python3 /home/augusto/.claude/skills/tlc-spec-lean/scripts/validate_verification.py design-system`: exit 0, "0 error(s), 0 warning(s)".
