# share-cards verification

**Verdict**: FAIL
**Profile**: ui
**Diff range**: 2ba30f7..419e2ca47e50aa37678826c08d5d762bd161e620
**Round**: 2 - scoped
**Verifier**: independent sub-agent (author != verifier)

Round 2 is scoped to the fixes `3617790..419e2ca` (`34543a3`, `22fffb1`, `d6b1dc0`, `6ffcdb7`, `8ef3457`, `29410aa`, `baac197`, `419e2ca`) and to every round-1 verdict that was not PASS. Every proof re-ran at `419e2ca` in the Sail project `mandato-cards` (`APP_PORT=8094`, `FORWARD_DB_PORT=54344`, `VITE_PORT=5184`), with the design tokens, client, SSR and card bundles rebuilt at HEAD and the SSR server up (`/health` `{"status":"OK"}`). Every section below is marked `verified at 419e2ca` or `carried from 0c5238f`.

The fixes close all four round-1 FAIL items, and every new assertion surface was made to fail once:

1. C42 is no longer vacuous. Fault 1 (round 1's survivor, ` (75%)`) and fault 2 (a member's name on a roll-call card) are now killed.
2. The feed and story arrangement is decided by a recorded plan amendment and proven by C78. Fault 3 (12 px per vote) is killed.
3. The light-only card theme is proven by C79. Fault 4 (the page's `data-theme="light"` removed) is killed.
4. C14's payload clause and AC 2's height rule are proven. Fault 5 (the height rule dropped) is killed.

The verdict stays FAIL on one blocking gap, a round-1 `Uncovered` cell that no fix addressed: a1 6.3 lists "cor própria por pessoa" under "nunca no card", and no check can see a per-member colour. C44 compares only `tagName.class` (`design/tests/cards.test.ts:166`, `app/tests/Feature/CardContentTest.php:160`). C79 reads the theme tokens of one card. So a member-dependent inline colour, such as `:style="{ color: … }"` on the name, would pass every proof. Inline `:style` bindings already exist on the card (`design/components/MemberCard.vue:47`, `design/components/MandateScore.vue:49`, both widths), so the channel is live.

## Binding sources

Verified at `419e2ca` for the rows the fixes touched (plan Assumptions and S4 amendment, door 3, the a1 6.3 "nunca no card" row). The other rows are carried from `0c5238f`: the fixes changed no file those rows read (`git diff 3617790..HEAD --stat` lists only tests, `ci.yml` and `.specs`).

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `research/design-anexos/a1-referencias-de-design.md` section 6.3, P9, P14, P15 | yes - re-read lines 207 and 278-286 at HEAD | none: three formats of one template (door 4, C37, C44, C47); fixed content (C37-C41); verification code (C49-C56); one tree for everyone (C44); long names and four-digit numbers (C45); same tokens and fonts (C32). "Nunca no card" is now asserted one needle per call: `%` (`app/tests/Feature/CardContentTest.php:137`, fault 1 killed), another member's name on a member or roll-call card (`:141`, fault 2 killed), Senate absence labels (`:145`), forbidden and extra words (`:138`), URLs (`:148`) | "cor própria por pessoa" (line 284; line 207 "Cor própria por parlamentar … Template fixo e idêntico para todos"): no check reaches a colour. C44 and AC 31 compare tag and class only (`design/tests/cards.test.ts:166`, `app/tests/Feature/CardContentTest.php:160`), C79 checks the theme of a single card, and the card already carries per-member inline `:style` (`design/components/MemberCard.vue:47`). Carried unresolved from round 1's `Uncovered` cell |
| `research/01-pesquisa-juridica.md` section 3.2 (photo licences, never alter the photo) | carried from `0c5238f` - read lines 75-86 | none: bytes cached and served unaltered (C3, C15); credit by house (C18, C37); no crop, filter or enlargement (C46) | - |
| plan `Assumptions` feed and story arrangement (line 236) and the S4 amendment "decided at verification round 1 by the orchestrator" (line 167) | yes - plan lines 167 and 236 at HEAD | none in code: the amendment decides figures stacked on a shared left edge and a left-aligned score of width min(available, votes × 8 px) in the body's scale; `design/components/MemberCard.vue:47` (`maxWidth: votes.length * 8px` inside the zoomed body, `design/styles/components.css:670-678`) implements exactly that. The amendment states that it replaces the Assumption's "score at full width", so the plan resolves its own conflict, although line 236 still reads the old text (non-blocking note 2) | - : figures stacked and the score width are proven by C78 (`design/e2e/cards.spec.ts:57-58`, `:65-66`), fault 3 killed |
| plan `Assumptions` "Card theme: light only" (line 237) and door 3 `colorScheme` `light` | yes - plan lines 88 and 237 | none: `app/resources/js/cards/render.js:64` `data-theme="light"`, `:84` `colorScheme: "light"` | - : C79 (`app/tests/Feature/CardRendererTest.php:148`, `:150-151`), fault 4 killed |
| `design/README.md` MemberCard and RollCallCard sections | carried from `0c5238f` | none | - |
| `app/README.md` share-cards section | carried from `0c5238f` | none | - |

**The plan amendment and the new checks, judged.** The score-cap amendment is legitimate rather than retrofitted. Round 1 refused the cap "without a ruling", and the ruling is now on the record: dated, attributed to the plan's approver, and argued on product grounds (two votes stretched across the column draw a few absurdly wide bars) rather than "the code already does it". It also stays inside what a1 6.3 decides (a miniature score, no width rule; a1's full-width "partitura" at lines 241 and 265 is the profile hero, not the card). Two provenance defects do not change that judgment. Unlike the plan's other amendments, it omits "under the maintainer's delegation". And it says "the og format keeps its arrangement" while the cap also applies in og (note 3). The new checks are derived from the plan's text and not from the code: C78 restates the amendment's formula, C79 the Assumption and door 3, C80 AC 2, C81 door 3's pin, and C82 AC 36. None is weaker than its source, and each was made to fail here (faults 3-5) or in the builder's recorded faults (C81, C82).

Step 1, verified at `419e2ca` for the card formats and the verification page (the fix touched no interface; this re-enumerates the round-1 gaps):

- Member card, feed and story. Eyebrow above photo above name: `design/e2e/cards.spec.ts:43-44`. Figures stacked, three of them on a shared left edge: C78 `:55`, `:57-58`. Score left-aligned at min(body, votes × 8 px × zoom), with a 240-vote and a 2-vote fixture: C78 `:65-66` (`design/e2e/cards.pages.mjs:27` adds `short-{format}`). Same footer: C37, C41. Light theme under a dark request: C79. **Per-member colour: no check (blocking gap).**
- Member card, og: arrangement as in round 1 (`cards.spec.ts:40-41`). The score cap also applies here, with no plan sentence or check behind it (note 3). A real served og card of 102/57 (4 votes) draws a 32 px score.
- Roll-call card, three formats: C40, C22, C45. No member name: C42 `:141`, killed by fault 2.
- `/verificar/{code}/`: equal, changed and gone states C53, C74, C76; 404 C55; 301 C54; index C56; head tags C64, C76; image and alt C52, C60; **photo value (sha256 or initials sentence): C82** (`app/tests/Feature/VerifyPageTest.php:92`, `:100`, `:102`).

## Checks

Proof run means `sail artisan test --filter="<name>"` run alone at `419e2ca`, exit 0. The 73 distinct Pest filters each exited 0 alone. One combined run of all 73 names with `--log-junit` gave 103 testcases, 0 failed, and every name hit (68 single tests, datasets of 10, 10, 8, 4 and 3). Design proofs ran alone as `sail npm --prefix /var/www/design test -- <file> -t "<name>"` (1 passed each) and `test:e2e -- -g "<name>"` (1 passed each). Rows for checks the fixes did not touch carry their evidence from `0c5238f`, with line numbers refreshed where a touched file shifted. `app/tests/...` paths are relative to `app/`.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | selection: none, fresh, stale > 7 days, failed retried | exit 0, 1 test (verified at 419e2ca) | `tests/Feature/PhotoCommandTest.php:51`, `:57`, `:71`, `:76` (carried from 0c5238f, unshifted) | PASS |
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
| C14 | shared sha is no one's photo: pages, payloads, re-request, recovery | both proofs exit 0, 1 test each (verified at 419e2ca) | first proof `PhotoCommandTest.php:372` `currentOf(...'9104'))->toBeNull()`, `:384` re-request, `:388` recovery; second proof `:408-409` both payloads' `photoSha256` `toBeNull()`, `:410` 9106's payload equals its current sha, `:414-415` both payloads follow the recovery. Round-1 GAP closed | PASS |
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
| C44 | one tree for every member | design and app, 1 passed each | `design/tests/cards.test.ts:181`; `CardContentTest.php:166`. Proves what AC 31 says (tags and classes); per-member colour is outside it (blocking gap) | PASS |
| C45 | every format fits its box | e2e, 1 passed | `cards.spec.ts:86-87` | PASS |
| C46 | photo whole, untouched, at most native size | e2e, 1 passed | `cards.spec.ts:106-109`, `:113`; `:112` is still a tautology (note 4) | PASS |
| C47 | prototype card is `MemberCard` og | design unit and e2e, 1 passed each | `design/tests/cards.test.ts:193` | PASS |
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
| C78 | feed and story: figures stacked, score left-aligned at min(body, votes × 8 px × zoom) | e2e `-g "feed and story stack the figures and size the score by its votes"`, 1 passed (verified at 419e2ca) | `design/e2e/cards.spec.ts:55` three figures; `:57` each top ≥ previous bottom − 0.5; `:58` left edges within 0.5; `:65` score left = body left; `:66` width = min(body.width, votes × 8 × zoom), over `member-{feed,story}` (240 votes) and `short-{feed,story}` (2 votes, `design/e2e/cards.pages.mjs:27`). Fault 3 failed `:66` by 11.19 px | PASS |
| C79 | light theme under a dark scheme request | exit 0, 1 test, 7 assertions | `app/tests/Feature/CardRendererTest.php:145-146` the dark request reaches the page (unthemed turns dark); `:148` the card resolves `oklch(1 0 0)` / `oklch(0.16 0 0)` with `prefers-color-scheme: dark` true; `:150-151` `capture` pixel (10, 10) is white for both pages. Fault 4 failed `:148` (paper `oklch(0.14 0 0)`) | PASS |
| C80 | 200 × 80 and 80 × 200 refused, 100 × 100 accepted | exit 0, 3 dataset cases | `PhotoCommandTest.php:206` the exact `photo failed camara 101: smaller than 100 x 100`, `:207-208` no row and no file; `:203` 100 × 100 becomes current; dataset `:211-213`. Fault 5 failed `wide enough, too short` | PASS |
| C81 | CI installs the browser of the locked `playwright-core` | exit 0, 1 test, 6 assertions | `CardRendererTest.php:215` one install step; `:216` exactly `npx --no-install playwright-core install --with-deps --only-shell chromium`; `:218` after `npm ci`; `:219` no `node_modules/playwright` in the lock. In the Sail image, `npx --no-install playwright-core install --dry-run --only-shell chromium` resolves `chromium-headless-shell v1243` at `/opt/ms-playwright`, which is present there (`PLAYWRIGHT_VERSION=1.63.0` at `docker/8.5/Dockerfile:19`; lock `playwright-core` 1.63.0). Not run on GitHub | PASS |
| C82 | verification page prints the photo the card showed | exit 0, 1 test | `VerifyPageTest.php:92` initials sentence; `:100` `Foto oficial: arquivo {$sha}`; `:102` the older code still prints the initials sentence | PASS |

Round-1 non-blocking items, re-judged at `419e2ca`:

- CI Playwright drift: fixed by `8ef3457` (C81, plus the dry-run above).
- C52's missing photo value: fixed by C82.
- C46 tautology (`cards.spec.ts:112`), C33 proving blocking through `capture()` only, gone-state vote links, and `design/components/card.js` duplicating `Labels`: unchanged, carried from `0c5238f` (notes 4-6).

## Coverage

Rows the fixes touched are recomputed at `419e2ca`. The others are carried from `0c5238f`, where they were all `-`.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| photo body rules of AC 2 (5) | plan AC 2, `app/Console/Commands/FetchPhotos.php:216` | `FF D8 FF` C6 · ≤ 2 MiB C6 · JPEG C6 · width ≥ 100 C6, C80 (`tall enough, too narrow`) · height ≥ 100 C80 (`wide enough, too short`, fault 5 killed) | - |
| photo value on a card payload (2) | plan AC 8, `app/Cards/Payloads.php:42` | shared hash → `photoSha256` null C14 (`PhotoCommandTest.php:408-409`) · current → its sha256 C14 `:410`, C82 | - |
| photo values on the verification page (2) | plan AC 36 | sha256 C82 `VerifyPageTest.php:100` · initials C82 `:92`, `:102` | - |
| feed and story arrangement (4) | plan Assumptions line 236 as replaced by the S4 amendment line 167 | photo above the name `cards.spec.ts:43-44` · figures stacked C78 `:57-58` · score left-aligned at min(available, votes × 8 px) C78 `:65-66` · same footer C37, C41 | - |
| card theme (1) | plan Assumptions line 237, door 3 | light under a dark request C79 `CardRendererTest.php:148`, `:150-151` | - |
| never on a card (8) | AC 29, a1 6.3 line 284 | forbidden terms C42 `:138` · extra words C42 `:138` · URL C42 `:148` · `%` C42 `:137` (fault 1 killed) · another member's name on a member card C42 `:141` · a member's name on a roll-call card C42 `:141` (fault 2 killed) · Senate absence labels C42 `:145` | per-member colour ("cor própria por pessoa"): no proof; C44 reads `tagName.class` only (`design/tests/cards.test.ts:166`, `CardContentTest.php:160`) |
| Landing door 3 (members) | plan line 88 | Chromium via Playwright C22, C32, C33, C35 · version pin, image and CI C36, C81 · `colorScheme` `light` C79 | - |
| startup config: browser shell (3 assemblies) | `docker/8.5/Dockerfile:19-20,70`, `.github/workflows/ci.yml:114,144` read directly | Sail image C36, C75 · CI job `app` C36, C75, C81 · design e2e in the same image C45 | - |
| `/fotos/` statuses (3); card route statuses (16); verify statuses (5); transport rules (5); selection and outcomes (8); exit codes and options (6); formats and subjects (7); code cases, 404 causes, render outcomes, CLI exits (23); verification code cases, AC 37 states, AC 35 inputs (19); member regions and empty states (11); roll-call regions, results, tallies (12); photo rules on a card (5); share block, pages, SSR, cookie-free (31); prune (5); other Landing doors (8); config assemblies | carried from `0c5238f` | as in round 1 | - |

## Test policy rows

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `app/Cards/Code.php`, `app/Media/Photos.php`, `app/Cards/Payloads.php`, `app/Cards/Share.php`, `app/Http/Controllers/CardController.php`, `design/components/MemberCard.vue`, `RollCallCard.vue` | own layer C48-C51, C37, C39, C40, C44, C78 · boundary C52-C56, C18-C20, C22-C31, C38-C42, C57-C59, C82, C14 second proof | yes (verified at 419e2ca) - AC 29's table now asserted at the boundary on every row, one needle per call; faults 1 and 2 killed. The per-person colour gap is a binding-source member, recorded above, not an AC 29 row |
| Decides, not reached across a boundary | `app/Console/Commands/PruneCards.php` | command C66 | yes (carried from 0c5238f) |
| Entry point that decides nothing | `PhotoController`, `VerifyController::index`, routes | boundary C15-C17, C56, C27 | yes (carried from 0c5238f) |
| Instrumentation, pass-throughs | render log line, `Renderer::run` env | consumer proofs C69, C75 | yes (carried from 0c5238f) |

## Faults injected

Verified at `419e2ca`. Scratch worktree `git worktree add --detach /tmp/vf2-share-cards HEAD`, Sail project `vf2-cards` (`APP_PORT=8096`, `FORWARD_DB_PORT=54346`, `VITE_PORT=5186`), with tokens and bundles built and the 4 target proofs green before any fault. Each command that edited a file started with `cd /tmp/vf2-share-cards`. Each fault was restored with `git -C /tmp/vf2-share-cards checkout -- <file>`, the scratch porcelain checked empty, and the card bundle rebuilt where the fault had touched it. Afterwards: `sail down -v` (containers, network `vf2-cards_sail` and volume `vf2-cards_sail-pgsql` removed), `git worktree remove` and `prune`, then `docker ps -a`, `volume ls` and `network ls` showed nothing named `vf2`. No `git stash` was used. The real tree's porcelain was empty before the report was written.

| Mutation | Location | Killed |
| --- | --- | --- |
| round-1 survivor: ` (75%)` after every `n de m` (card bundle rebuilt) | `design/components/MemberCard.vue:37` | yes - C42 at `app/tests/Feature/CardContentTest.php:137` (`member 101: %`) |
| a member's name on a roll-call card: `{{ result }} · Ana Souza` (card bundle rebuilt) | `design/components/RollCallCard.vue:31` | yes - C42 at `CardContentTest.php:141` (`roll call 100-6: Ana Souza`) |
| feed and story score width: `votes.length * 8` → `* 12` | `design/components/MemberCard.vue:47` | yes - C78 at `design/e2e/cards.spec.ts:66` (`short-feed score width`, off by 11.19 px) |
| dark-scheme request: ` data-theme="light"` removed from the card page (card bundle rebuilt) | `app/resources/js/cards/render.js:64` | yes - C79 at `app/tests/Feature/CardRendererTest.php:148` (paper `oklch(0.14 0 0)`, ink dark) |
| AC 2 height rule dropped: `if ($size[0] < self::MIN_SIDE)` only | `app/Console/Commands/FetchPhotos.php:216` | yes - C80 at `app/tests/Feature/PhotoCommandTest.php:206` (1 of 3 cases failed: `wide enough, too short`) |

## Gate

Verified at `419e2ca` in `mandato-cards`, started for this round:

- `sail artisan test`, full suite, twice: 282 passed, 0 failed (3070 assertions) both times.
- 73 named Pest filters, each run alone: all exit 0. In one junit-logged run of all 73: 103 testcases, 0 failures, every name hit.
- Design: `npm test` 55 passed (6 files); `test:e2e` 18 passed; the 8 named unit and 6 named e2e proofs each run alone, 1 passed each.
- `sail bin pint --test`: passed. `sail bin phpstan analyse`: 0 errors.
- Builds: `design` `build` (`dist/tokens.css`), then `npm run build` (client, SSR, `bootstrap/cards/render.mjs` 52.01 kB). SSR `/health` OK. `/deputados/101/` serves `og:image` and `summary_large_image`.
- One fresh served card per format from the running server on port 8094, `/deputados/102/legislatura/57/card/20270301-S6JM5X07/{1200x630,1080x1350,1080x1920}.png`: each 200 `image/png`, `Cache-Control: immutable, max-age=31536000, public`, no `Set-Cookie`. `file` reads 1200 × 630, 1080 × 1350 and 1080 × 1920; 42 115, 64 702 and 76 831 bytes. Logged `card rendered … 1238 / 1088 / 1293 ms`. Looking at the images: the feed card stacks the three figures on one left edge, and its 4-vote score is short and left-aligned, as the amendment decides. These requests added 1 snapshot and 3 PNGs to the dev database and to the gitignored media disk of `mandato-cards`.
- `python3 /home/augusto/.claude/skills/tlc-spec-lean/scripts/validate_verification.py share-cards`: exit 1, with a single error: `verdict is FAIL - route the ranked gaps back as fixes, then re-verify` (no row contradicts the verdict). `mandato-cards` was stopped (`sail stop`) at the end of the round.

## Ranked gaps

1. **Blocking - per-member colour is uncovered (a1 6.3 "cor própria por pessoa").** No check sees a colour or an inline style. C44 and AC 31 compare `tagName.class` only (`design/tests/cards.test.ts:166`, `app/tests/Feature/CardContentTest.php:160`), and the card already binds per-member inline styles (`design/components/MemberCard.vue:47`, `design/components/MandateScore.vue:49`). A member-dependent `color` or `background` would pass every proof. This was round 1's `Uncovered` cell on the a1 row, and no fix addressed it. A fix within C44's own proof: also compare the `style` attribute with the width declarations removed, or compare the computed `color` and `background-color` of every element across the two members' cards in each format.

Not blocking, for the orchestrator:

2. Plan door 3 (`plan.md:88`, from `419e2ca`) now reads `npx --no-install playwright-core install --with-deps --only-shell chromium --with-deps --only-shell chromium`. The flags are doubled, and the text says this command runs "in the published Sail `Dockerfile` and in CI". The Dockerfile runs `npx -y playwright@$PLAYWRIGHT_VERSION install --with-deps --only-shell chromium` (`app/docker/8.5/Dockerfile:70`). No check or behaviour is wrong (C36 and C81 assert each install apart, both pinned to 1.63.0), but the one-way door's literal text is malformed and misattributes the CI command to the Dockerfile. It needs a one-line edit.
3. Plan Assumptions line 236 still reads "score at full width" (Confirmed y). The S4 amendment (line 167) declares that it replaces this, and the Handoff says the row "stays as written". A reader of the table gets the superseded rule.
4. The S4 amendment says "the og format keeps its arrangement", but the 8 px cap at `MemberCard.vue:47` applies to og too. Before this feature, the design-system prototype drew og's score uncapped. The served og card of 102/57 (4 votes) draws a 32 px score, and any og card under about 100 votes draws a short score. No binding source decides og's score width, so this is not a contradiction, but the amendment's sentence is inaccurate and C78 covers feed and story only. The amendment also omits "under the maintainer's delegation", which every other amendment states.
5. Carried from `0c5238f`: C46's ratio assertion is a tautology (`design/e2e/cards.spec.ts:112`; the 4:5 aspect rests on `object-fit: contain` at `:109`). C33 proves request blocking through `capture()`, not through the CLI's `main`. In the gone state, the vote links still point at roll calls. `design/components/card.js` duplicates the app's `Labels`.
6. Lessons (step 7) were not distilled: this round's brief kept the Verifier read-only except for this report.
