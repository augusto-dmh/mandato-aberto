# share-cards verification

**Verdict**: FAIL
**Profile**: ui
**Diff range**: 2ba30f7..0c5238f5356cac0be8a37164c4a82236b0692bee
**Round**: 1 - full
**Verifier**: independent sub-agent (author != verifier)

Verified at `0c5238f`. All 77 checks were read against the code. Every named proof ran on its own in the Sail project `mandato-cards` (`APP_PORT=8094`, `FORWARD_DB_PORT=54344`, `VITE_PORT=5184`), with the design tokens, the client, SSR and card bundles rebuilt at HEAD and the SSR server up. All 68 Pest filters exited 0, and so did the 8 design unit and 5 design e2e names. The FAIL comes from what the green proofs miss:

1. Three of C42's "never on a card" assertions are vacuous. Pest's `toContain` takes several needles, so `not->toContain('%', $label)` passes whenever the label is absent from the text, and the label is never in the text. A card printing a percentage passed C42: the injected fault survived.
2. The plan decides the feed and story arrangement: figures stacked and the score at full width. No check covers either part. The code also caps the score at 8 px per vote, so a short mandate draws a short score (in the feed, any mandate under about 84 votes).
3. "Card theme: light only" (plan Assumption, door 3 `colorScheme` `light`) has no check.
4. Two coverage members are unproven: C14's payload clause and AC 2's height rule.

## Binding sources

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| `research/design-anexos/a1-referencias-de-design.md` section 6.3, P9, P14, P15 | yes - read lines 162-197 and 278-286 at HEAD | none: three formats of one template (door 4, C37, C44, C47); fixed content with the photo unaltered, name, party-UF, at most four `n de m`, the miniature score, source and date, short address (C37-C41); the verification code (C49-C56); one tree for everyone (C44); long names and four-digit numbers (C45); same tokens and fonts (C32). Item 6.3 lists `MA·2027-09-30·204553` as an example, and plan door 5 rejects that form on the record | 6.3 "nunca no card": comparison with other members, a member's name on another card, and the Senate absence labels are proven only by C42 clauses that are vacuous (`app/tests/Feature/CardContentTest.php:136`, `:140`, `:144`); "cor própria por pessoa" has no check of its own and rests only on C44's tag and class equality (an inline `:style` per member exists at `design/components/MemberCard.vue:47`) |
| `research/01-pesquisa-juridica.md` section 3.2 (photo licences, never alter the photo) | yes - read lines 75-86 at HEAD | none: bytes cached and served unaltered (C3, C15, fault 1 killed); credit by house on page and card (C18, C37); no crop, filter or enlargement (C46). The plan uses "Foto: Câmara dos Deputados" rather than "Fotógrafo/Câmara dos Deputados" by a decision recorded in open question 2 (decisions log) | - |
| plan `Assumptions`: feed and story arrangement "photo above the name, figures stacked, score at full width, same footer" (binding through the brief: "what the plan and sources decide") | yes - `.specs/features/share-cards/plan.md` line 237 | the score is capped at `votes.length * 8` px (`design/components/MemberCard.vue:47`), so a mandate under about 84 votes in the feed (about 73 in the story) draws a partial-width score. Seen in a real served render of `/senadores/9101/legislatura/57/card/20270305-Q3Z6SQDS/1080x1350.png`: 2 votes give a 16 px score. The Handoff reports this as a reversible choice, but the plan has no amendment for it | "figures stacked" and "score at full width" have no check (`design/e2e/cards.spec.ts:30` asserts only eyebrow → photo → name, and the regions' vertical order) |
| plan `Assumptions`: "Card theme: light only" and door 3 `colorScheme` `light` | yes - plan lines 91 and 238 | none in the code (`app/resources/js/cards/render.js:64` `data-theme="light"`, `:84` `colorScheme: "light"`) | no check asserts the light theme or `colorScheme`; `rg "colorScheme\|data-theme" app/tests design/tests design/e2e` finds nothing about cards |
| `design/README.md` MemberCard and RollCallCard sections | yes - diff 2ba30f7..HEAD | none: inputs and empty states match the components and C37, C39, C40 | - |
| `app/README.md` share-cards section | yes | none: commands, disk, routes and the browsers path match door 1, door 9 and the S3 amendment | - |

Step 1 enumeration, per image and screen:

- Member card, three formats. Regions and their order (9): C37 (DOM, both houses), C38 (app HTML), browser vertical order `design/e2e/cards.spec.ts:30`. One `<h1>`: C37, C38. Three figures `n de m`: C37, C38. Singular count: C73. Empty states: C39. Footer: C37, C41. Size: C22, C45. Photo whole, at most native size, untouched, credited: C46, C37. Name as stored, `text-transform: none`: C43. Same tree for everyone: C44. og arrangement (photo column left, eyebrow in the text column): `cards.spec.ts:40-41`. Feed/story: eyebrow above photo above name `cards.spec.ts:43-44`; **figures stacked: no check; score at full width: no check, and the code departs from it; light theme: no check.**
- Roll-call card, three formats. Regions in DOM order (6), results (3) and tally states (3): C40 (design and app). Size and fit: C22, C45. No member name: C42 (vacuous, see above).
- `/verificar/{code}/` states. equal, changed (with `Ver dados atuais`) and gone: C53, C74, C76. 404: C55. 301: C54. Index form: C56. Head tags: C64, C76. Image and alt: C52, C60.
- Share block on member and roll-call pages: C61 (after the last source note, 4 links in order, `download`). og meta: C57-C59, C63; `summary` pages: C64.
- `/fotos/` responses 200, 404 and 410: C15-C17.

## Checks

Proof run means `sail artisan test --filter="<name>"` run alone at `0c5238f`, exit 0, with the test count the runner printed (`app/tests/...` paths below are relative to `app/`). Design proofs ran as `sail npm --prefix /var/www/design test -- <file> -t "<name>"` (1 passed each) and `sail npm --prefix /var/www/design run test:e2e -- -g "<name>"` (1 passed each).

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | selection: none, fresh, stale > 7 days, failed retried | exit 0, 1 test | `tests/Feature/PhotoCommandTest.php:51` `toHaveCount(9)`; `:57` failed 102 retried; `:71` 8 days → `[camaraPhotoUrl('101')]`; `:76` 6 days → `[]` | PASS |
| C2 | `--house`, `--member`, `--stale-after=0` | exit 0, 1 test | `PhotoCommandTest.php:84`, `:88`, `:92` | PASS |
| C3 | bytes, sha and version row; 301 to legis | exit 0, 1 test | `PhotoCommandTest.php:103` file hash `toBe(hash('sha256', $deputy))`; `:107` `[354, 472]`; `:116` legis `final_url` | PASS |
| C4 | one file per sha, never rewritten | exit 0, 1 test | `PhotoCommandTest.php:138` `filemtime($file))->toBe(1_000_000_000)` | PASS |
| C5 | unchanged bytes: no new version, `checked_at` moves | exit 0, 1 test | `PhotoCommandTest.php:154` `'photos camara: 1 checked, 0 new, 1 unchanged, 0 failed'` | PASS |
| C6 | 10 failing answers keep the current photo | exit 0, 10 dataset cases | `PhotoCommandTest.php:171` `toBe("photo failed camara 101: {$reason}")` plus files, rows and current sha unchanged | PASS |
| C7 | User-Agent, timeout 15, no auto-redirects, host refused | exit 0, 1 test | `PhotoCommandTest.php:229` `toBe(['mandato-aberto-app (+https://github.com/augusto-dmh/mandato-aberto)', 15, false])`; `:235`, `:236` | PASS |
| C8 | batches of 4, 2 pauses of 250 ms | exit 0, 1 test | `PhotoCommandTest.php:251` `toBe([[3, 250.0], [7, 250.0]])` | PASS |
| C9 | one summary per house, exit 0 | exit 0, 1 test | `PhotoCommandTest.php:265` both summary lines | PASS |
| C10 | every Câmara fetch failed → exit 1; empty run → 0 | exit 0, 1 test | `PhotoCommandTest.php:280`, `:284` | PASS |
| C11 | storage failure → exit 1 | exit 0, 1 test | `PhotoCommandTest.php:300` `'photo failed camara 101: storage write failed'` | PASS |
| C12 | advisory lock held → exit 1, no request | exit 0, 1 test | `PhotoCommandTest.php:320`, `:321` | PASS |
| C13 | bad options → exit 2 with the usage | exit 0, 4 dataset cases | `PhotoCommandTest.php:331` the usage line; `:332` no request | PASS |
| C14 | shared sha is no one's photo: pages, **payloads**, re-request, recovery | exit 0, 1 test | `PhotoCommandTest.php:350` `currentOf(...'9104'))->toBeNull()`; `:362` re-request; `:366` recovery. The claim "both card payloads carry `photoSha256` null" has no assertion: `rg photoSha256 app/tests` finds it only in `CardCodeTest` and `CardImageTest:87` (member 101) | GAP: payload clause not asserted |
| C15 | photo 200 with headers, byte-identical | exit 0, 1 test | `tests/Feature/PhotoRouteTest.php:22` body `toBe(Storage...get(...))`; `:24` `['immutable', 'max-age=31536000', 'public']` | PASS |
| C16 | 5 paths answer 404 | exit 0, 1 test | `PhotoRouteTest.php:39` `assertStatus(404)` over 5 paths | PASS |
| C17 | suppressed → 410, other → 200 | exit 0, 1 test | `PhotoRouteTest.php:49` `assertStatus(410)` | PASS |
| C18 | `src`, alt, credit by house, props | exit 0, 1 test | `tests/Feature/MemberPhotoTest.php:24` `src` `"/fotos/{$sha}.jpg"`, plus alt, figcaption and props in the same chain | PASS |
| C19 | no photo → initials `CD`, no caption, no img | exit 0, 1 test | `MemberPhotoTest.php:38` `toBe('CD')` | PASS |
| C20 | suppressed → initials, code changes, card initials | exit 0, 1 test | `tests/Feature/CardImageTest.php:269` `$inputs[1]['photo'])->toBeNull()`; code `not->toBe($before)` | PASS |
| C21 | no img outside the origin | exit 0, 1 test | `MemberPhotoTest.php:52` `preg_match('#^/(?!/)#', $src))->toBe(1)` | PASS |
| C22 | real renderer, 4 subjects × 3 formats | exit 0, 1 test | `CardImageTest.php:61` `pngSize(...)->toBe($size)`; `:62` cache directives; `:63` no cookie | PASS |
| C23 | snapshot stored before the render, PNG stored | exit 0, 1 test | `CardImageTest.php:82` `$rowsWhenRendering)->toBe(1)`; `:84-94` row and literal payload; `:95` stored PNG | PASS |
| C24 | 3 requests, 2 renders | exit 0, 1 test | `CardImageTest.php:110` `Process::assertRanTimes(fn () => true, 2)` | PASS |
| C25 | old stored code renders its snapshot | exit 0, 1 test | `CardImageTest.php:132` stdin votes `position` `yes` | PASS |
| C26 | unknown and another subject's code → 302 | exit 0, 1 test | `CardImageTest.php:148` `assertHeader('Location', ...{$current}/1080x1350.png")` | PASS |
| C27 | 10 URLs → 404 | exit 0, 10 dataset cases | `CardImageTest.php:158` `assertStatus(404)`; `:159` nothing ran | PASS |
| C28 | exit 1 → 503, no PNG, error log | exit 0, 1 test | `CardImageTest.php:183` `->with("card render failed {$code} 1200x630: boom")->once()` | PASS |
| C29 | timeout → 503, `timed out`, default 15 | exit 0, 1 test | `CardImageTest.php:189` `toBe(15)`; `:197` `timed out` | PASS |
| C30 | slots full → 503, stored card still 200 | exit 0, 1 test | `CardImageTest.php:208` `Process::assertNothingRan()` after the 503 | PASS |
| C31 | waits on the lock, serves the other's PNG | exit 0, 1 test; also green in 3 full runs and 8 runs with 24 busy loops on 12 cores | `CardImageTest.php:240` `getContent())->toBe($png)`; `:241` nothing ran | PASS |
| C32 | the card entry imports only the design package and two font packages | exit 0, 1 test | `tests/Feature/CardRendererTest.php:57` import list | PASS |
| C33 | no request reaches the probe server | exit 0, 1 test | `CardRendererTest.php:89` `is_file(hits.log))->toBeFalse()`. The `blocked` lines come from the test's own script printing `capture()`'s list (`:83`), not from the CLI's `main` | PASS |
| C34 | weight budget | exit 0, 1 test | `CardRendererTest.php:102` `toBeLessThanOrEqual($limit)` | PASS |
| C35 | CLI exit codes 2, 1 and 0 | exit 0, 1 test | `CardRendererTest.php:118` `toBe(2, $case)`; `:126` `toBe(1)` | PASS |
| C36 | Playwright version coupling, compose, CI order | exit 0, 1 test | `CardRendererTest.php:143` lock version; `:155` install before the tests | PASS |
| C37 | member card regions in order, 3 formats | design unit "member card text in order" and e2e "card regions in order", 1 passed each | `design/tests/cards.test.ts:81` `inDocumentOrder(regions)`; `:83-97` texts; `design/e2e/cards.spec.ts:36` vertical order | PASS |
| C38 | app card HTML for 101/57 and 9101/57 | exit 0, 1 test | `tests/Feature/CardContentTest.php:46` `['Mandato Aberto · Câmara dos Deputados · 57ª legislatura', 'Ana Souza', 'PSB · SP', ...]`; `:57` Rosa | PASS |
| C39 | total 0 and no vote | design and app, 1 passed each | `design/tests/cards.test.ts:107`; `CardContentTest.php:66`, `:74` | PASS |
| C40 | roll-call regions, results, tally states | design and app, 1 passed each | `design/tests/cards.test.ts:146-151`; `CardContentTest.php:83`, `:95` | PASS |
| C41 | footer source, date, code and address | exit 0, 1 test | `CardContentTest.php:113` `toBe("Fonte: {$house}, dados de {$date} Código {$code} · confira em mandato.test/verificar/")` | PASS |
| C42 | no `%`, forbidden terms, other names, absence labels or other URLs | exit 0, 1 test | `CardContentTest.php:137` forbidden terms `toBe([])` and `:147` URLs `toBe(['mandato.test/verificar/'])` are real. `:136` `not->toContain('%', $label)`, `:140` `not->toContain($name, $label)` and `:144` `not->toContain($a, $label)` are vacuous: `vendor/pestphp/pest/src/Mixins/Expectation.php:155` `toContain(mixed ...$needles)` treats `$label` as a second needle, which is never in the text, so the negation always holds. Fault 4 survived | FAIL |
| C43 | name as stored, `text-transform: none` | e2e, 1 passed | `design/e2e/cards.spec.ts:53-54` | PASS |
| C44 | one tree for every member | design and app, 1 passed each | `design/tests/cards.test.ts:181`; `CardContentTest.php:165` | PASS |
| C45 | every format fits its box | e2e, 1 passed | `design/e2e/cards.spec.ts:64-65` | PASS |
| C46 | photo whole, untouched, at most native size | e2e, 1 passed | `design/e2e/cards.spec.ts:84-88` box ≤ 354 × 472 and `fit` `contain`; `:91` filter, blend, transform. `:90` is a tautology (`(480*scale)/(600*scale)`); the 4:5 aspect is carried by `object-fit: contain` at `:87` | PASS |
| C47 | the prototype card is `MemberCard` og | design unit and e2e, 1 passed each | `design/tests/cards.test.ts:193` `toBe("ma-card ma-card--og")` | PASS |
| C48 | canonical JSON; floats and booleans refused | exit 0, 1 test | `tests/Unit/CardCodeTest.php:37`, `:40` | PASS |
| C49 | code = Brasília date and 40 bits; second process | exit 0, 1 test | `CardCodeTest.php:54` `'20270301-'.crockford40($digest)`; `:60` second process | PASS |
| C50 | 8 changes each change the code | exit 0, 8 dataset cases | `CardCodeTest.php:64` `not->toBe(Code::of(samplePayload()))` | PASS |
| C51 | normalisation | exit 0, 1 test | `CardCodeTest.php:86`, `:89` | PASS |
| C52 | verification page content, Câmara and Senate | exit 0, 1 test | `tests/Feature/VerifyPageTest.php:53` h1; `:56` img `src`; `:66` collected sentence; `:71` subject link; `:80` Senate | PASS |
| C53 | equal, changed and gone sentences | exit 0, 1 test | `VerifyPageTest.php:91`, `:98`, `:101`, `:106` | PASS |
| C54 | 6 variants → 301 canonical | exit 0, 1 test | `VerifyPageTest.php:167` `assertStatus(301)` plus `Location` | PASS |
| C55 | unknown and malformed → 404 with the sentence | exit 0, 1 test | `VerifyPageTest.php:178` | PASS |
| C56 | index form; `codigo` → 302 | exit 0, 1 test | `VerifyPageTest.php:196` label; `:200` Location | PASS |
| C57 | member page og tags and alt | exit 0, 1 test | `tests/Feature/CardSharingTest.php:84` `toBe(expectedCardTags(...))` exact lists | PASS |
| C58 | 57th legislature alt; senator | same test | `CardSharingTest.php:88`, `:95` | PASS |
| C59 | roll-call alts | exit 0, 1 test | `CardSharingTest.php:104`, `:113`, `:118` | PASS |
| C60 | verification img alt = AC 41 alt | same as C52 | `VerifyPageTest.php:57-58` | PASS |
| C61 | share block on 16 pages | exit 0, 1 test | `CardSharingTest.php:136` after the last note; `:139` 4 links | PASS |
| C62 | pages store no snapshot | exit 0, 1 test | `CardSharingTest.php:156` `count())->toBe(0)` | PASS |
| C63 | tags survive an SSR outage | exit 0, 1 test | `CardSharingTest.php:171` empty `#app` and identical tags | PASS |
| C64 | verify page tags with noindex; summary pages | exit 0, 1 test | `CardSharingTest.php:188`, `:192` | PASS |
| C65 | 15 responses without `Set-Cookie` | exit 0, 1 test | `CardSharingTest.php:230` count 15; `:233` `toBeFalse($name)` | PASS |
| C66 | prune decisions; rerender from snapshot | exit 0, 1 test | `tests/Feature/CardHistoryTest.php:82`, `:91`, `:95`, `:100` | PASS |
| C67 | prune exit 1 | exit 0, 1 test | `CardHistoryTest.php:123` | PASS |
| C68 | history survives a sweep; source scan | exit 0, 1 test | `CardHistoryTest.php:163`; `:180` scan | PASS |
| C69 | render log line | exit 0, 1 test | `CardHistoryTest.php:206` regex `card rendered {$code} {$format} \d+ ms {$bytes} bytes` | PASS |
| C70 | private media disk, not linked, ignored | exit 0, 1 test | `tests/Feature/MediaStorageTest.php:8-14`, `:18-19` | PASS |
| C71 | columns, checks, uniques, jsonb, index, no FK | exit 0, 1 test | `MediaStorageTest.php:44-49`, `:56-62` (`23505`, `23514`) | PASS |
| C72 | `PublicUrl` builders | exit 0, 1 test | `tests/Unit/CardUrlTest.php:11-17` | PASS |
| C73 | singular label and toggle | design unit, 1 passed each | `design/tests/cards.test.ts:118`; `design/tests/components.test.ts:218` `toBe("Ver a 1 votação como tabela")`, `:219` `toBe("Ver as 4 votações como tabela")` | PASS |
| C74 | gone subject: values as text, no img | exit 0, 1 test | `VerifyPageTest.php:126` `querySelectorAll('body img'))->toHaveCount(0)` | PASS |
| C75 | served request finds the browser | exit 0, 1 test; also reproduced live, see Gate | `CardImageTest.php:312-314`; `:319-322` config, `.env.example` and CI | PASS |
| C76 | gone subject points at nothing gone | exit 0, 1 test | `VerifyPageTest.php:145` no `og:image*`; `:147` `['summary']`; `:149` `subjectHrefs)->toBe([])` | PASS |
| C77 | strip aria-label singular | design unit, 1 passed | `design/tests/components.test.ts:226` `toEqual(["3 votações nominais em 2023", "1 votação nominal em 2024"])`, `:227` `toEqual(["1 votação nominal"])` | PASS |

Handoff deviations, judged:

- Own Sail image with Chromium: accepted. `app/docker/8.5/Dockerfile:70` pins `playwright@$PLAYWRIGHT_VERSION` to the lock (C36) and `compose.yaml` builds it. CI is different: `.github/workflows/ci.yml:143` runs `npx playwright install` in `app/`, which has only `playwright-core`, so npx fetches the newest `playwright` from the registry. Its browser revision can drift from the locked `playwright-core`. This is not a check failure but a risk to fix (`npx playwright-core install ...` or a pinned version).
- Six earlier tests now assert values set by an approved door: accepted, at equal strength. `ProjectFilesTest` (compose context, build script), `SchemaTest` (merge resolution, rollback by path), `design/tests/package.test.ts` (component list), and `MemberPageTest`, `RollCallPageTest`, `SharedLinksTest` (`twitter:card` `summary_large_image`, methodology still `summary`).
- Figure size in og (title-1 instead of display-2): accepted. The value comes from a token; the structure (three stacked `n de m` under the basis line) is unchanged.
- Feed and story text zoom of 1.4 and 1.6: accepted. The zoom applies only to the eyebrow and body (`design/styles/components.css:672`, `:677`), and C46 measures the photo box at no more than native size in every format.
- Score capped at 8 px per vote: not accepted without a ruling. It departs from the plan's "score at full width", and no check covers the arrangement (gap 2).
- C75 proven through the HTTP kernel: accepted. I reproduced it on the running server (`php -d variables_order=EGPCS artisan serve`): 6 fresh renders answered 200 `image/png` (see Gate).
- C31 timeout seen once: it did not reproduce in 3 full runs or in 8 isolated runs with 24 CPU busy loops on 12 cores. Treated as a load-only flake. The weakness is real, though: on a 5 s miss the test fails without stopping the helper (`CardImageTest.php:229-234`), which then writes into the next test's fake disk.
- Merge resolutions (`19ed49e`, `4e0dfe9`): the only combined hunk is `app/tests/Feature/SchemaTest.php`. It keeps app-contract-v3's not-null test and this feature's rollback-by-path. Correct.

## Coverage

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| `/fotos/` statuses (3) | plan Surface | 200 C15 · 404 C16 · 410 C17 | - |
| card route statuses, 4 routes × 4 (16) | plan Surface, `routes/public.php:25-28` | 200 C22 (4 subjects) · 302 C26 (4 subjects) · 404 C27 (each route) · 503 C28 (4 subjects) | - |
| `/verificar/` and `/verificar/{code}/` statuses (5) | plan Surface | C56 200, 302 · C52 200 · C54 301 · C55 404 | - |
| photo body rules of AC 2 (5) | plan AC 2 | `FF D8 FF` C6 html, png · ≤ 2 MiB C6 · JPEG to getimagesize C6 signature-only · width ≥ 100 C6 80 × 80 | height ≥ 100: only one case, 80 × 80, fails both sides at once, so dropping `$size[1] < self::MIN_SIDE` (`app/Console/Commands/FetchPhotos.php:216`) passes every proof |
| photo transport rules of AC 4 (5) | plan AC 4 | no response C6 · status C6 · > 3 redirects C6 · host C6, C7 · http C6 | - |
| photo selection (4) and outcomes (4) | plan AC 1, AC 3, AC 8 | C1, C2 · new C3, unchanged C5, failed C6, shared C14 (current photo and page) | shared hash → payload `photoSha256` null (C14's own clause): no assertion |
| `mandato:photos` exit codes (3) and options (3) | door 9 | C9, C10-C12, C13 · C2, C13 | - |
| card formats (3), subjects (4) | door 6, Surface | C22, C45, C46, C34, C37 | - |
| card code cases (5), 404 causes (10), render outcomes (5), CLI exits (3) | plan AC 18-22, door 3 | C22-C31, C27 (10 cases), C35 | - |
| verification code cases (8), AC 37 states (3), AC 35 inputs (8) | door 5, AC 37 | C51-C55, C53, C74, C76, C50 | - |
| member card regions (9), empty states (2) | AC 25, AC 26 | C37, C38, C39, C41, C43 | - |
| roll-call card regions (6), results (3), tally states (3) | AC 27 | C40 (design and app), C59 | - |
| feed and story arrangement (4) | plan Assumptions line 237 | photo above the name `design/e2e/cards.spec.ts:43-44` · same footer C37 (text per format) | figures stacked: no proof · score at full width: no proof, and the code caps it at `design/components/MemberCard.vue:47` |
| card theme (1) | plan Assumptions line 238, door 3 | - | light only: no proof (`app/resources/js/cards/render.js:64`, `:84` untested) |
| never on a card (7) | AC 29, a1 6.3 | forbidden terms C42 `:137` · 5 extra words C42 `:137` · URL C42 `:147` | `%` (`CardContentTest.php:136` vacuous, fault 4 survived) · another member's name (`:140` vacuous) · a member name on a roll-call card (`:140` vacuous) · Senate absence labels (`:144` vacuous) |
| photo rules on a card (5) | AC 33, P9 | C46 | - |
| share block links (4), pages with a card image (6) and without (4), SSR states (2), cookie-free (15) | AC 41-46 | C61, C57-C59, C64, C76, C63, C65 | - |
| prune decisions (3), exits (2) | AC 47, door 9 | C66, C67 | - |
| Landing doors (9) | plan Landing | 1 C70 · 2 C71, C68 · 3 C22, C32, C33, C35, C36 · 4 C37, C47 · 5 C48-C51 · 6 C72, C15, C22 · 7 C57, C63, C64 · 8 C23, C62 · 9 C1-C13, C66, C67 | door 3 `colorScheme` `light`: no proof (same as card theme) |
| startup config assemblies | `config/filesystems.php`, `config/mandato.php`, `docker/8.5/Dockerfile:20,70`, `ci.yml:114,143` read directly | C70, C29, C30, C36, C75 | - |

## Test policy rows

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `app/Cards/Code.php`, `app/Media/Photos.php`, `app/Cards/Payloads.php`, `app/Cards/Share.php`, `app/Http/Controllers/CardController.php`, `design/components/MemberCard.vue`, `RollCallCard.vue` | own layer C48-C51, C37, C39, C40, C44 · boundary C52-C56, C18-C20, C22-C31, C38-C42, C57-C59 | no - AC 29's decision table (what a card must not carry) is asserted at the boundary for only 3 of 7 rows; `%`, other names and absence labels are vacuous (`CardContentTest.php:136-144`) |
| Decides, not reached across a boundary | `app/Console/Commands/PruneCards.php` | command C66 | yes - 3 decision rows asserted |
| Entry point that decides nothing | `PhotoController`, `VerifyController::index`, routes | boundary C15-C17, C56, C27 | yes - accepted input, each rejection, each error path |
| Instrumentation, pass-throughs | render log line, `Renderer::run` env | consumer proofs C69, C75 | yes |

`Swept` rows all resolve to checks, not to `existing`: nothing to re-read. The authorization row is `n/a` by approved policy.

## Faults injected

Scratch worktree `/tmp/vf-share-cards` at `0c5238f`, Sail project `vf-cards` (`APP_PORT=8095`, `FORWARD_DB_PORT=54345`, `VITE_PORT=5185`), SSR up. Each fault was restored with `git -C /tmp/vf-share-cards checkout -- <file>` and the scratch porcelain checked empty. Afterwards the containers, network and volume were removed (`sail down -v`) along with the worktree. The real tree's porcelain was empty before and after.

| Mutation | Location | Killed |
| --- | --- | --- |
| photo bytes altered on store: `put($path, $body."\0")` | `app/Console/Commands/FetchPhotos.php:224` | yes - C3 at `PhotoCommandTest.php:103` (sha mismatch) |
| shared hash treated as a current photo: `count(*) ... = 1` → `>= 1` | `app/Media/Photos.php` `current()` | yes - C14 at `PhotoCommandTest.php:350` |
| code ignores the data: digest of `['sourceId' => ...]` only | `app/Cards/Code.php` `of()` | yes - C50, 8 of 8 dataset cases failed at `CardCodeTest.php:64` |
| card carries a percentage: ` (75%)` after each `n de m` (card bundle rebuilt in scratch; the mutant appears in the rendered HTML as `de 2 (50%)`) | `design/components/MemberCard.vue:37` | no - C42 passed (362 assertions) because `CardContentTest.php:136` is vacuous; the design proof C37 (`design/tests/cards.test.ts:89`) did fail on it |
| gone subject links its 404 page: `'subjectUrl' => $subjectPath` | `app/Http/Controllers/VerifyController.php` `show()` | yes - C76 at `VerifyPageTest.php:149` |

## Gate

- `sail artisan test`, full suite, 3 consecutive runs at `0c5238f`: 275 passed, 0 failed (3028 assertions) each time.
- 68 named Pest filters, each run alone: all exit 0 (64 single tests, plus datasets of 10, 4, 10 and 8).
- Design: `npm test` 55 passed (6 files); `test:e2e` 17 passed; each named design proof run alone, 1 passed.
- `sail bin pint --test`: passed. `sail bin phpstan analyse`: 0 errors.
- Builds: design tokens, client, SSR and `bootstrap/cards/render.mjs` built at HEAD. SSR health `{"status":"OK"}`. `/deputados/101/` serves the server-rendered share block and `summary_large_image`.
- Real served cards through `php -d variables_order=EGPCS artisan serve` on port 8094, each a fresh render. `/senadores/9101/legislatura/57/card/20270305-Q3Z6SQDS/{1200x630,1080x1350,1080x1920}.png`: 200 `image/png`, `file` reads 1200 × 630, 1080 × 1350 and 1080 × 1920; 38 877, 56 685 and 69 223 bytes; `Cache-Control: immutable, max-age=31536000, public`; no `Set-Cookie`. `/senado/votacoes/7001/card/20270305-WZYJ0HHC/*`: 200 `image/png` in all three formats. The log shows `card rendered ... 1014-1442 ms`. These requests added 2 snapshots and 6 PNGs to the dev database and to the gitignored `app/storage/app/media/` of `mandato-cards`.
- `python3 /home/augusto/.claude/skills/tlc-spec-lean/scripts/validate_verification.py share-cards`: exit 1, with a single error: `verdict is FAIL - route the ranked gaps back as fixes, then re-verify` (no row contradicts the verdict).

Not blocking, for the fix round:

- C33 proves the request blocking through `capture()` and not through the CLI's `main`.
- C52 does not assert the photo value the page prints.
- In the gone state, the vote links still point at roll calls. Removing a member does not remove those, but a roll call dropped from the contract would 404.
- `design/components/card.js` duplicates the app's `Labels` values, a drift risk.
