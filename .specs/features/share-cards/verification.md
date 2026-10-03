# share-cards verification

**Verdict**: PASS
**Profile**: ui
**Diff range**: 2ba30f7..1b05fdc66361607ac52845ab7cb3838caef85aed
**Round**: 3 - scoped
**Verifier**: independent sub-agent (author != verifier)

Round 3 covers the fixes `9ce5f80..1b05fdc` and every round-2 verdict that was not PASS. The fixes are `a3afed5` and `927abe4` (plan text, by the orchestrator) and `8beb6d1` and `1b05fdc` (C83, by the fixer). `git diff 9ce5f80..HEAD --stat` touches only `.specs/features/share-cards/{plan,checks}.md`, `design/tests/cards.test.ts`, `design/e2e/cards.spec.ts` and `design/e2e/cards.pages.mjs`. No production file changed.

Every proof re-ran at `1b05fdc` in the Sail project `mandato-cards` (`APP_PORT=8094`, `FORWARD_DB_PORT=54344`, `VITE_PORT=5184`). Before the runs, the design tokens, client, SSR and card bundles were rebuilt and the SSR server was started (`/health` `{"status":"OK"}`). Each section below is marked `verified at 1b05fdc`, `carried from 419e2ca` or `carried from 0c5238f`.

The round-2 blocking gap is closed. a1 6.3's "nunca no card: cor própria por pessoa" now has a check, C83, with two proofs:

- one in the design package over the inline `style` attributes;
- one in Chromium over the computed colours of 5 member and 2 roll-call cards per format.

Three faults were injected on its surfaces: a per-member colour on the name, on a figure and on the score marks. All three were killed. The three round-2 plan-text notes are fixed. C83 still has precision gaps, recorded in Ranked gaps 1. Neither proof reads `stroke` outside the marks, or `background-image`, `box-shadow` and the other colour properties beyond the four C83 names. The unit proof also skips an element that has no `style` on one card. So a per-member gradient (a1 line 207's "aura, gradiente gerado") bound only on some members, or a per-member `stroke` attribute on the score's axis line, would pass both proofs. These are narrower than the gap they replace: every colour the card draws today is compared. I judge them non-blocking, but the orchestrator may close them before merge (two one-line changes).

## Binding sources

Verified at `1b05fdc` for the a1 6.3 row and for the plan rows the fixes touched: door 3, the Assumptions "Feed and story arrangement" row, and the S4 amendment. The other rows are carried from `0c5238f`, because no file they read changed.

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `research/design-anexos/a1-referencias-de-design.md` section 6.3, P9, P14, P15 | yes - re-read lines 207 and 278-286 at HEAD | none: three formats of one template (door 4, C37, C44, C47); fixed content (C37-C41); verification code (C49-C56); one tree for everyone (C44); long names and four-digit numbers (C45); same tokens and fonts (C32); "nunca no card" asserted one needle per call in C42 (`app/tests/Feature/CardContentTest.php:137`, `:138`, `:141`, `:145`, `:148`, carried from 419e2ca). The "cor própria por pessoa" rule (line 284; line 207 "Template fixo e idêntico para todos") is now covered by C83: inline styles minus widths per `tagName.class` (`design/tests/cards.test.ts:222`), and computed `color`, `background-color`, `border-color`, `fill` per `tagName.class` plus mark `fill` and `stroke` (`design/e2e/cards.spec.ts:150`, `:157`). Each was killed by a fault here | - |
| `research/01-pesquisa-juridica.md` section 3.2 (photo licences, never alter the photo) | carried from `0c5238f` - read lines 75-86 | none: bytes cached and served unaltered (C3, C15); credit by house (C18, C37); no crop, filter or enlargement (C46) | - |
| plan door 3 (`plan.md:88`) | yes, at HEAD | none: the commands are now given per place. The Dockerfile runs `npx -y playwright@$PLAYWRIGHT_VERSION install --with-deps --only-shell chromium`, matching `app/docker/8.5/Dockerfile:70` with `ARG PLAYWRIGHT_VERSION=1.63.0` at `:19`. CI runs `npx --no-install playwright-core install --with-deps --only-shell chromium`, matching `.github/workflows/ci.yml:144`. The doubled flags are gone (round-2 note 2 fixed) | - |
| plan Assumptions "Feed and story arrangement" (`plan.md:236`) and the S4 amendment (`plan.md:167`) | yes, at HEAD | none: the row now reads "score left-aligned at min(available width, votes × 8 px)", which matches the amendment, `design/components/MemberCard.vue:47` and C78 (round-2 note 3 fixed). The amendment now reads "under the maintainer's delegation" and ends "The og format keeps its arrangement, and the same 8 px per vote cap applies to its score", which matches `MemberCard.vue:47` (the cap is not format-dependent) and the og card served here (round-2 note 4 fixed) | - |
| plan `Assumptions` "Card theme: light only" (line 237) and door 3 `colorScheme` `light` | carried from `419e2ca` | none: `app/resources/js/cards/render.js:64`, `:84`; C79 | - |
| `design/README.md` MemberCard and RollCallCard sections | carried from `0c5238f` | none | - |
| `app/README.md` share-cards section | carried from `0c5238f` | none | - |

**C83, judged against its source.** C83 comes from a1 6.3's text and plan door 4, not from the code. Its two proofs reach different channels:

- The unit proof reads every inline `style`, which is where the card already binds per-render values (`MemberCard.vue:47`, `MandateScore.vue:49`). It removes only the `width`, `min-width` and `max-width` declarations, which legitimately follow the vote count, and asserts that those declarations exist (`cards.test.ts:224`), so the strip cannot silently empty the comparison.
- The Chromium proof reads computed colour. It also catches presentation attributes and stylesheet rules, which the unit proof cannot see: fault 3 survived the unit proof and was killed here.
- The mark exemption is narrow. A mark's `fill` legitimately depends on the vote's position, so marks are excluded from the `fill` comparison. They are instead held to `none` or the strip's own colour, and the strip's colour is itself compared across pages.
- The fixtures differ in name, party, house, legislature, figures, vote count, positions and photo (`design/e2e/cards.pages.mjs:35-52`). `cards.spec.ts:160` asserts that more than 8 elements are shared by every page in a group, so the comparison compares something.

**Step 1**, verified at `1b05fdc`. The fixes touched no interface, so only the round-2 gap is re-enumerated. Per-member colour on the member card (og, feed, story) and the roll-call card (og, feed, story): C83, both proofs, every format (`cards.spec.ts:145`, `cards.test.ts:219`). Every other element and arrangement of the member card, roll-call card and `/verificar/{code}/` is carried from `419e2ca`: C37-C47, C52-C56, C74, C76, C78, C79, C82.

## Checks

Proof run means the proof was run alone at `1b05fdc` and exited 0:

- Pest: `sail artisan test --filter="<name>"`. All 73 distinct filters exited 0 alone. One combined run of all 73 with `--log-junit` gave 103 testcases and 0 failures, and every name was hit.
- Design unit: `sail npm --prefix /var/www/design test -- <file> -t "<name>"`. All 9 names gave 1 passed each.
- Design e2e: `test:e2e -- -g "<name>"`. All 7 names gave 1 passed each.

Evidence for rows the fixes did not touch is carried from `419e2ca`, or from `0c5238f` where that report carried it. `app/tests/...` paths are relative to `app/`.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | selection: none, fresh, stale > 7 days, failed retried | exit 0, 1 test (re-run at 1b05fdc; evidence from 419e2ca) | `tests/Feature/PhotoCommandTest.php:51`, `:57`, `:71`, `:76` (carried from 0c5238f, unshifted) | PASS |
| C2 | `--house`, `--member`, `--stale-after=0` | exit 0, 1 test | `PhotoCommandTest.php:84`, `:88`, `:92` | PASS |
| C3 | bytes, sha and version row; 301 to legis | exit 0, 1 test | `PhotoCommandTest.php:103`, `:107`, `:116` | PASS |
| C4 | one file per sha, never rewritten | exit 0, 1 test | `PhotoCommandTest.php:138` | PASS |
| C5 | unchanged bytes: no new version | exit 0, 1 test | `PhotoCommandTest.php:154` | PASS |
| C6 | 10 failing answers keep the current photo | exit 0, 10 dataset cases | `PhotoCommandTest.php:171` | PASS |
| C7 | User-Agent, timeout, no auto-redirects, host refused | exit 0, 1 test | `PhotoCommandTest.php:251`, `:257`, `:258` (shifted +22) | PASS |
| C8 | batches of 4, 2 pauses | exit 0, 1 test | `PhotoCommandTest.php:273` | PASS |
| C9 | one summary per house | exit 0, 1 test | `PhotoCommandTest.php:287` | PASS |
| C10 | every Câmara fetch failed → exit 1; empty → 0 | exit 0, 1 test | `PhotoCommandTest.php:302`, `:306` | PASS |
| C11 | storage failure → exit 1 | exit 0, 1 test | `PhotoCommandTest.php:322` | PASS |
| C12 | lock held → exit 1, no request | exit 0, 1 test | `PhotoCommandTest.php:342`, `:343` | PASS |
| C13 | bad options → exit 2 with the usage | exit 0, 4 dataset cases | `PhotoCommandTest.php:353`, `:354` | PASS |
| C14 | shared sha is no one's photo: pages, payloads, re-request, recovery | both proofs exit 0, 1 test each (re-run at 1b05fdc; evidence from 419e2ca) | first proof `PhotoCommandTest.php:372` `currentOf(...'9104'))->toBeNull()`, `:384` re-request, `:388` recovery; second proof `:408-409` both payloads' `photoSha256` `toBeNull()`, `:410` 9106's payload equals its current sha, `:414-415` both payloads follow the recovery. Round-1 GAP closed | PASS |
| C15 | photo 200, headers, byte-identical | exit 0, 1 test | `tests/Feature/PhotoRouteTest.php:22`, `:24` | PASS |
| C16 | 5 paths → 404 | exit 0, 1 test | `PhotoRouteTest.php:39` | PASS |
| C17 | suppressed → 410 | exit 0, 1 test | `PhotoRouteTest.php:49` | PASS |
| C18 | `src`, alt, credit by house | exit 0, 1 test | `tests/Feature/MemberPhotoTest.php:24` | PASS |
| C19 | no photo → initials | exit 0, 1 test | `MemberPhotoTest.php:38` | PASS |
| C20 | suppressed → initials, code changes | exit 0, 1 test | `tests/Feature/CardImageTest.php:269` | PASS |
| C21 | no img outside the origin | exit 0, 1 test | `MemberPhotoTest.php:52` | PASS |
| C22 | real renderer, 4 subjects × 3 formats | exit 0, 1 test | `CardImageTest.php:61`, `:62`, `:63` | PASS |
| C23 | snapshot stored before the render | exit 0, 1 test | `CardImageTest.php:82`, `:84-94`, `:95` | PASS |
| C24 | 3 requests, 2 renders | exit 0, 1 test | `CardImageTest.php:110` | PASS |
| C25 | old code renders its snapshot | exit 0, 1 test | `CardImageTest.php:132` | PASS |
| C26 | unknown and other subject's code → 302 | exit 0, 1 test | `CardImageTest.php:148` | PASS |
| C27 | 10 URLs → 404 | exit 0, 10 dataset cases | `CardImageTest.php:158`, `:159` | PASS |
| C28 | exit 1 → 503 and log | exit 0, 1 test | `CardImageTest.php:183` | PASS |
| C29 | timeout → 503, default 15 | exit 0, 1 test | `CardImageTest.php:189`, `:197` | PASS |
| C30 | slots full → 503 | exit 0, 1 test | `CardImageTest.php:208` | PASS |
| C31 | waits on the lock, serves the other's PNG | exit 0, 1 test; also green in 2 full runs | `CardImageTest.php:240`, `:241` | PASS |
| C32 | card entry imports only design and fonts | exit 0, 1 test | `tests/Feature/CardRendererTest.php:57` | PASS |
| C33 | no request reaches the probe server | exit 0, 1 test | `CardRendererTest.php:89` (note 5) | PASS |
| C34 | weight budget | exit 0, 1 test | `CardRendererTest.php:102` | PASS |
| C35 | CLI exit codes 2, 1, 0 | exit 0, 1 test | `CardRendererTest.php:166`, `:174` (shifted +48) | PASS |
| C36 | Playwright version coupling, compose, CI order | exit 0, 1 test (verified at 419e2ca; step matcher changed by `8ef3457`) | `CardRendererTest.php:190` Dockerfile `playwright@$PLAYWRIGHT_VERSION install …`, `:191` lock version; `:198` matcher `install --with-deps --only-shell chromium` still finds exactly the CI step (the only other match in job `app` would need those flags, and C81 asserts a single install step). Equal strength | PASS |
| C37 | member card regions in order, 3 formats | design unit and e2e, 1 passed each | `design/tests/cards.test.ts:81`, `:83-97`; `design/e2e/cards.spec.ts:30`, `:36` | PASS |
| C38 | app card HTML for 101/57 and 9101/57 | exit 0, 1 test | `CardContentTest.php:46`, `:57` | PASS |
| C39 | total 0 and no vote | design and app, 1 passed each | `design/tests/cards.test.ts:107`; `CardContentTest.php:66`, `:74` | PASS |
| C40 | roll-call regions, results, tallies | design and app, 1 passed each | `design/tests/cards.test.ts:146-151`; `CardContentTest.php:83`, `:95` | PASS |
| C41 | footer source, date, code, address | exit 0, 1 test | `CardContentTest.php:113` | PASS |
| C42 | no `%`, forbidden terms, other names, absence labels or other URLs | exit 0, 1 test (verified at 419e2ca, 355 assertions) | `CardContentTest.php:137` `expect(str_contains($text, '%'))->toBeFalse(...)`; `:138` forbidden terms `toBe([])`; `:141` other names; `:145` absence labels; `:148` URLs `toBe(['mandato.test/verificar/'])`. One needle per call; fault 1 fails `:137` (`member 101: %`), fault 2 fails `:141` (`roll call 100-6: Ana Souza`). Round-1 FAIL closed | PASS |
| C43 | name as stored, `text-transform: none` | e2e, 1 passed | `design/e2e/cards.spec.ts:75-76` (shifted +22) | PASS |
| C44 | one tree for every member | design and app, 1 passed each | `design/tests/cards.test.ts:181`; `CardContentTest.php:166`. Proves what AC 31 says (tags and classes); per-person colour is now C83's (unchanged at 1b05fdc) | PASS |
| C45 | every format fits its box | e2e, 1 passed | `cards.spec.ts:86-87` | PASS |
| C46 | photo whole, untouched, at most native size | e2e, 1 passed | `cards.spec.ts:106-109`, `:113`; `:112` is still a tautology (note 4) | PASS |
| C47 | prototype card is `MemberCard` og | design unit and e2e, 1 passed each | `design/tests/cards.test.ts:237` (shifted +44 by `8beb6d1`) | PASS |
| C48 | canonical JSON | exit 0, 1 test | `tests/Unit/CardCodeTest.php:37`, `:40` | PASS |
| C49 | code = Brasília date and 40 bits | exit 0, 1 test | `CardCodeTest.php:54`, `:60` | PASS |
| C50 | 8 changes each change the code | exit 0, 8 dataset cases | `CardCodeTest.php:64` | PASS |
| C51 | normalisation | exit 0, 1 test | `CardCodeTest.php:86`, `:89` | PASS |
| C52 | verification page content | exit 0, 1 test | `tests/Feature/VerifyPageTest.php:54`, `:57`, `:67`, `:72`, `:81` (shifted +1); the photo value is now C82 | PASS |
| C53 | equal, changed, gone sentences | exit 0, 1 test | `VerifyPageTest.php:110`, `:117`, `:120`, `:125` | PASS |
| C54 | 6 variants → 301 | exit 0, 1 test | `VerifyPageTest.php:186` | PASS |
| C55 | unknown and malformed → 404 | exit 0, 1 test | `VerifyPageTest.php:198` | PASS |
| C56 | index form; `codigo` → 302 | exit 0, 1 test | `VerifyPageTest.php:215`, `:219` | PASS |
| C57 | member page og tags and alt | exit 0, 1 test | `tests/Feature/CardSharingTest.php:84` | PASS |
| C58 | 57th legislature alt; senator | same test | `CardSharingTest.php:88`, `:95` | PASS |
| C59 | roll-call alts | exit 0, 1 test | `CardSharingTest.php:104`, `:113`, `:118` | PASS |
| C60 | verification img alt | same as C52 | `VerifyPageTest.php:58-59` | PASS |
| C61 | share block on 16 pages | exit 0, 1 test | `CardSharingTest.php:136`, `:139` | PASS |
| C62 | pages store no snapshot | exit 0, 1 test | `CardSharingTest.php:156` | PASS |
| C63 | tags survive an SSR outage | exit 0, 1 test | `CardSharingTest.php:171` | PASS |
| C64 | verify page tags, noindex; summary pages | exit 0, 1 test | `CardSharingTest.php:188`, `:192` | PASS |
| C65 | 15 responses without `Set-Cookie` | exit 0, 1 test | `CardSharingTest.php:230`, `:233` | PASS |
| C66 | prune decisions; rerender | exit 0, 1 test | `tests/Feature/CardHistoryTest.php:82`, `:91`, `:95`, `:100` | PASS |
| C67 | prune exit 1 | exit 0, 1 test | `CardHistoryTest.php:123` | PASS |
| C68 | history survives a sweep | exit 0, 1 test | `CardHistoryTest.php:163`, `:180` | PASS |
| C69 | render log line | exit 0, 1 test | `CardHistoryTest.php:206` | PASS |
| C70 | private media disk | exit 0, 1 test | `tests/Feature/MediaStorageTest.php:8-14`, `:18-19` | PASS |
| C71 | columns, checks, uniques, index | exit 0, 1 test | `MediaStorageTest.php:44-49`, `:56-62` | PASS |
| C72 | `PublicUrl` builders | exit 0, 1 test | `tests/Unit/CardUrlTest.php:11-17` | PASS |
| C73 | singular label and toggle | design unit, 1 passed each | `design/tests/cards.test.ts:118`; `design/tests/components.test.ts:218`, `:219` | PASS |
| C74 | gone subject: values as text, no img | exit 0, 1 test | `VerifyPageTest.php:145` | PASS |
| C75 | served request finds the browser | exit 0, 1 test; also served live, see Gate | `CardImageTest.php:312-314`, `:319-322` | PASS |
| C76 | gone subject points at nothing gone | exit 0, 1 test | `VerifyPageTest.php:164`, `:166`, `:168` | PASS |
| C77 | strip aria-label singular | design unit, 1 passed | `design/tests/components.test.ts:226`, `:227` | PASS |
| C78 | feed and story: figures stacked, score left-aligned at min(body, votes × 8 px × zoom) | e2e `-g "feed and story stack the figures and size the score by its votes"`, 1 passed (re-run at 1b05fdc; evidence from 419e2ca) | `design/e2e/cards.spec.ts:55` three figures; `:57` each top ≥ previous bottom − 0.5; `:58` left edges within 0.5; `:65` score left = body left; `:66` width = min(body.width, votes × 8 × zoom), over `member-{feed,story}` (240 votes) and `short-{feed,story}` (2 votes, `design/e2e/cards.pages.mjs:27`). Fault 3 failed `:66` by 11.19 px | PASS |
| C79 | light theme under a dark scheme request | exit 0, 1 test, 7 assertions | `app/tests/Feature/CardRendererTest.php:145-146` the dark request reaches the page (unthemed turns dark); `:148` the card resolves `oklch(1 0 0)` / `oklch(0.16 0 0)` with `prefers-color-scheme: dark` true; `:150-151` `capture` pixel (10, 10) is white for both pages. Fault 4 failed `:148` (paper `oklch(0.14 0 0)`) | PASS |
| C80 | 200 × 80 and 80 × 200 refused, 100 × 100 accepted | exit 0, 3 dataset cases | `PhotoCommandTest.php:206` the exact `photo failed camara 101: smaller than 100 x 100`, `:207-208` no row and no file; `:203` 100 × 100 becomes current; dataset `:211-213`. Fault 5 failed `wide enough, too short` | PASS |
| C81 | CI installs the browser of the locked `playwright-core` | exit 0, 1 test, 6 assertions | `CardRendererTest.php:215` one install step; `:216` exactly `npx --no-install playwright-core install --with-deps --only-shell chromium`; `:218` after `npm ci`; `:219` no `node_modules/playwright` in the lock. In the Sail image, `npx --no-install playwright-core install --dry-run --only-shell chromium` resolves `chromium-headless-shell v1243` at `/opt/ms-playwright`, which is present there (`PLAYWRIGHT_VERSION=1.63.0` at `docker/8.5/Dockerfile:19`; lock `playwright-core` 1.63.0). Not run on GitHub | PASS |
| C82 | verification page prints the photo the card showed | exit 0, 1 test | `VerifyPageTest.php:92` initials sentence; `:100` `Foto oficial: arquivo {$sha}`; `:102` the older code still prints the initials sentence | PASS |
| C83 | no colour on a card belongs to a person: inline styles minus widths identical per tag and class (design); computed `color`, `background-color`, `border-color` and, outside the marks, `fill` identical per tag and class across 5 member and 2 roll-call cards per format, and every mark's `fill` and `stroke` `none` or the strip's colour (Chromium) | design unit `-t "no card carries a style of its own per subject"` 1 passed; e2e `-g "no card draws a colour of its own per subject"` 1 passed (verified at 1b05fdc) | `design/tests/cards.test.ts:190-196` strips `width`, `min-width`, `max-width` and keeps every other declaration; `:222` one style string per `tagName.class` across two members (Câmara with photo vs Senate without, 3 vs 30 votes) and two roll calls (Câmara approved vs Senate rejected); `:224` the stripped width declarations exist. `design/e2e/cards.spec.ts:129-131` the per-element tuple; `:134-137` mark `fill` and `stroke` outside {`none`, strip colour}; `:149` strip only on member cards; `:150` marks list empty; `:157` one tuple per key across `member`, `member48`, `short`, `nophoto`, `other` (and `rollcall`, `rollcall2`); `:160` more than 8 keys shared by every page; pages `design/e2e/cards.pages.mjs:35-52`. Faults 1-3 killed (below). Precision gaps: Ranked gaps 1 | PASS |

Round-2 non-blocking items, re-judged at `1b05fdc`:

- Note 2 (door 3's doubled flags and misattributed command): fixed by `a3afed5`, see Binding sources.
- Note 3 (the Assumptions row still read "score at full width"): fixed by `a3afed5`.
- Note 4 (og cap sentence and delegation wording): fixed by `927abe4` and `a3afed5`.
- Note 5 (C46 tautology at `design/e2e/cards.spec.ts:112`, C33 through `capture()` only, gone-state vote links, `design/components/card.js` duplicating `Labels`): unchanged, carried from `0c5238f`.

## Coverage

The rows the fixes touched are recomputed at `1b05fdc`. The other rows are carried from `419e2ca`, where every one of them read `-`.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| never on a card (8) | AC 29, a1 6.3 line 284 (verified at 1b05fdc) | forbidden terms C42 `:138` · extra words C42 `:138` · URL C42 `:148` · `%` C42 `:137` · another member's name on a member card C42 `:141` · a member's name on a roll-call card C42 `:141` · Senate absence labels C42 `:145` (C42 rows carried from 419e2ca) · a colour of its own per person C83 (`design/tests/cards.test.ts:222`, `design/e2e/cards.spec.ts:150`, `:157`; faults 1-3 killed) | - |
| C83 colour channels the card binds today (4) | `design/components/MemberCard.vue:31-47`, `MandateScore.vue:49`, `:53`, `:78`, `vote.js:77-99`, `RollCallCard.vue`, `TallyBar.vue` (verified at 1b05fdc) | inline `style` C83 unit `:222` (fault 1 and fault 2 killed) · computed text, background and border colour of every element C83 e2e `:157` (faults 1 and 2 killed) · score mark `fill`/`stroke` C83 e2e `:150` (fault 3 killed, survived the unit proof as expected) · strip `color` (the marks' `currentColor`) C83 e2e `:157` | - |
| feed and story arrangement (4) | plan Assumptions line 236 (now consistent with the S4 amendment line 167) | photo above the name `cards.spec.ts:43-44` · figures stacked C78 `:57-58` · score left-aligned at min(available, votes × 8 px) C78 `:65-66` · same footer C37, C41 (carried from 419e2ca) | - |
| Landing door 3 (members) | `plan.md:88` (verified at 1b05fdc) | Chromium via Playwright C22, C32, C33, C35 · Dockerfile pin and CI command C36, C81 · `colorScheme` `light` C79 | - |
| photo body rules of AC 2 (5); photo value on a payload (2); photo values on the verification page (2); card theme (1); startup config, browser shell (3) | carried from `419e2ca` | as in round 2 | - |
| `/fotos/` statuses (3); card route statuses (16); verify statuses (5); transport rules (5); selection and outcomes (8); exit codes and options (6); formats and subjects (7); code cases, 404 causes, render outcomes, CLI exits (23); verification code cases, AC 37 states, AC 35 inputs (19); member regions and empty states (11); roll-call regions, results, tallies (12); photo rules on a card (5); share block, pages, SSR, cookie-free (31); prune (5); other Landing doors (8); config assemblies | carried from `0c5238f` | as in round 1 | - |

## Test policy rows

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `app/Cards/Code.php`, `app/Media/Photos.php`, `app/Cards/Payloads.php`, `app/Cards/Share.php`, `app/Http/Controllers/CardController.php`, `design/components/MemberCard.vue`, `RollCallCard.vue` | own layer C48-C51, C37, C39, C40, C44, C78, C83 · boundary C52-C56, C18-C20, C22-C31, C38-C42, C57-C59, C82, C14 second proof | yes (verified at 1b05fdc for the design row: C83 adds an own-layer and a browser proof for the template's colour; the other files carried from 419e2ca) |
| Decides, not reached across a boundary | `app/Console/Commands/PruneCards.php` | command C66 | yes (carried from 0c5238f) |
| Entry point that decides nothing | `PhotoController`, `VerifyController::index`, routes | boundary C15-C17, C56, C27 | yes (carried from 0c5238f) |
| Instrumentation, pass-throughs | render log line, `Renderer::run` env | consumer proofs C69, C75 | yes (carried from 0c5238f) |

## Faults injected

Rows 1-3 are verified at `1b05fdc`. Setup:

- Scratch worktree `git worktree add --detach /tmp/vf3-share-cards HEAD`, with `vendor` and `node_modules` copied locally from the real tree.
- Sail project `vf3-cards` (`APP_PORT=8097`, `FORWARD_DB_PORT=54347`, `VITE_PORT=5187`).
- Both C83 proofs were green there before any fault. The real tree's porcelain was empty at baseline.

Every command that edited a file started with `cd /tmp/vf3-share-cards`. Each fault was restored with `git -C /tmp/vf3-share-cards checkout -- <file>`, and the scratch porcelain was then checked empty. No `git stash` was used.

Cleanup: `sail down -v` removed the containers, network `vf3-cards_sail` and volume `vf3-cards_sail-pgsql`. Then `git worktree remove --force` and `git worktree prune` ran. Afterwards `docker ps -a`, `volume ls` and `network ls` showed nothing named `vf3`, `/tmp/vf3-share-cards` no longer exists, and the real tree's porcelain was empty before this report was written. Rows 4-8 are carried from `419e2ca`, and the code they mutated is unchanged since.

| Mutation | Location | Killed |
| --- | --- | --- |
| per-party colour on the name: `:style="{ maxWidth: '100%', color: member.party === 'PL' ? 'oklch(0.17 0 0)' : 'oklch(0.16 0 0)' }"` on the `<h1>` (also tests that only the width declaration is stripped) | `design/components/MemberCard.vue:31` | yes - unit at `design/tests/cards.test.ts:222` (`og member H1.ma-card__name color:oklch(0.16 0 0),color:oklch(0.17 0 0): expected 2 to be 1`); e2e at `design/e2e/cards.spec.ts:157` (`og member H1.ma-card__name`, `other` page `oklch(0.16 0 0)` against `oklch(0.17 0 0)`) |
| per-name background on each figure: `:style="{ backgroundColor: \`hsl(${member.name.charCodeAt(0)} 30% 97%)\` }"` on `.ma-card__figure` | `design/components/MemberCard.vue:35` | yes - unit at `cards.test.ts:222` (`og member P.ma-card__figure background-color:hsl(65 30% 97%),background-color:hsl(76 30% 97%)`); e2e at `cards.spec.ts:157` (`og member P.ma-card__figure`) |
| per-mandate colour on the score marks through a presentation attribute: `v-bind="{ ...s.attrs, fill: s.attrs.fill === 'none' \|\| votes.length >= 100 ? s.attrs.fill : 'oklch(0.3 0 0)' }"` | `design/components/MandateScore.vue:78` | yes - e2e at `cards.spec.ts:150` (`short-og marks use the strip colour or none`). The unit proof passed, as expected: it reads `style` only, and this channel is the e2e proof's |
| round 2: ` (75%)` after every `n de m` | `design/components/MemberCard.vue:37` | yes - C42 `app/tests/Feature/CardContentTest.php:137` (carried from 419e2ca) |
| round 2: a member's name on a roll-call card | `design/components/RollCallCard.vue:31` | yes - C42 `CardContentTest.php:141` (carried from 419e2ca) |
| round 2: feed and story score `* 8` → `* 12` | `design/components/MemberCard.vue:47` | yes - C78 `design/e2e/cards.spec.ts:66` (carried from 419e2ca) |
| round 2: `data-theme="light"` removed from the card page | `app/resources/js/cards/render.js:64` | yes - C79 `app/tests/Feature/CardRendererTest.php:148` (carried from 419e2ca) |
| round 2: AC 2 height rule dropped | `app/Console/Commands/FetchPhotos.php:216` | yes - C80 `app/tests/Feature/PhotoCommandTest.php:206` (carried from 419e2ca) |

## Gate

Verified at `1b05fdc` in `mandato-cards`, which was started for this round:

- Builds: `design` `build` wrote `dist/tokens.css`, then `npm run build` built the client, the SSR bundle and `bootstrap/cards/render.mjs` (52.01 kB). SSR `/health` returned `{"status":"OK"}`.
- `sail artisan test`, the full suite, ran twice: 282 passed, 0 failed (3070 assertions) both times.
- The 73 named Pest filters each exited 0 alone. One junit-logged run of all 73 gave 103 testcases, 0 failures, and every name was hit.
- Design: `npm test` gave 56 passed (6 files), `test:e2e` gave 19 passed. The 9 named unit proofs and 7 named e2e proofs, run alone, gave 1 passed each, including both C83 proofs.
- `sail bin pint --test` passed. `sail bin phpstan analyse` reported 0 errors.
- `validate_checks share-cards`: 0 errors. `validate_plan share-cards`: 0 errors, 1 warning (open question 1).
- `/deputados/103/` serves `og:image` `…/deputados/103/legislatura/57/card/20270301-Y3ZC2A86/1200x630.png` and `twitter:card` `summary_large_image`.
- One fresh card per format was served by the running server on port 8094 at `/deputados/103/legislatura/57/card/20270301-Y3ZC2A86/{1200x630,1080x1350,1080x1920}.png`. Each answered 200 `image/png` with `Cache-Control: immutable, max-age=31536000, public` and no `Set-Cookie`. `file` reads 1200 × 630, 1080 × 1350 and 1080 × 1920, at 37 205, 57 817 and 67 264 bytes. The log shows `card rendered … 1638 / 1578 / 1153 ms`.
- Looking at the feed image: ink and grey on white only, figures stacked on one left edge, and a 3-vote score that is short and left-aligned. These requests added 1 snapshot and 3 PNGs to the dev database and to the gitignored media disk of `mandato-cards`.
- `mandato-cards` was stopped (`sail stop`) after the served cards, before the fault project started.
- `python3 /home/augusto/.claude/skills/tlc-spec-lean/scripts/validate_verification.py share-cards`: exit 0, 0 errors, 0 warnings.

## Ranked gaps

None blocking. For the orchestrator:

1. **C83 precision gaps (a1 6.3 "cor própria por pessoa"; line 207 names "aura, gradiente gerado").** Two escape paths remain. Both are deterministic from reading the code; neither was injected, because the brief capped faults at three.
   - (a) The unit proof skips an element whose `style` is null (`design/tests/cards.test.ts:192`). An inline style bound on only some members (`:style="cond ? {…} : null"`) is therefore never compared with its absence. The claim says the attributes "are identical for every element", so the proof is weaker than the claim here.
   - (b) The Chromium tuple (`design/e2e/cards.spec.ts:131`) reads `color`, `background-color`, `border-color` and `fill`. It does not read `stroke` outside the marks, which the axis `<line>` carries as a presentation attribute (`design/components/MandateScore.vue:53`). Nor does it read `background-image`, `box-shadow`, `outline-color`, `text-shadow` or `text-decoration-color`.

   Together, a gradient or shadow bound on only some members, or a per-member `stroke` on the axis line, passes both proofs. Fixes within the existing proofs: treat a null `style` as `""` instead of skipping it, and add `stroke`, `background-image` and `box-shadow` to the tuple.
2. The `checks.md` header (line 6) still reads "82 checks". There are 83.
3. Wording only: the plan Assumptions row (`plan.md:236`) now carries the amended rule but labels itself "(superseded by the S4 amendment …)". The amendment (`plan.md:167`) still says it "replaces the Assumption's 'score at full width'", a phrase the row no longer contains. A reader is not misled about the rule.
4. Carried from `0c5238f`: C46's ratio assertion is a tautology (`design/e2e/cards.spec.ts:112`). C33 proves request blocking through `capture()`, not through the CLI's `main`. In the gone state, the vote links still point at roll calls. `design/components/card.js` duplicates the app's `Labels`.
5. Lessons (step 7) were not distilled. This round's brief kept the Verifier read-only except for this report, and gap 1 is a grounded precision gap the orchestrator may record.
