# app-contract-v3 verification

**Verdict**: FAIL
**Profile**: ui
**Diff range**: f0fbdbc..a714a932d8e15540e300fa6325a0c920c31d8357
**Round**: 1 - full
**Verifier**: independent sub-agent (author != verifier)

All 73 checks are proven at HEAD, and every named test exists and passed. The build is not done yet. Step 1 and the Coverage recompute found elements that a binding source draws, or a project rule requires, which no check covers:

- the profile's three proposition counts, now per mandate, have no proof
- the roll-call result and government-orientation labels have no proof; the orientation mapping is new code
- the symbolic count is a number with no source or method note
- the methodology page has a sentence that is not in the approved copy, plus a state the plan never drew
- 15 of door 3's not-null columns have no proof

Two of these are proofs that the skeleton had and that this feature deleted. The skeleton ACs 12 and 18 were superseded wholesale, but AC 23 and AC 41 brought back only part of them.

## Binding sources

Opened: the plan (Surface, Landing doors 1-6, Criteria, the AC 49 copy table, resolved open question 2), `design/README.md`, `design/components/{vote.js,VoteMark.vue,MandateScore.vue}`, `design/screens/{Profile.vue,RollCall.vue}`, `research/06-pesquisa-design-e-concorrentes.md` sections 3 (P1-P15) and 4 (traps), `AGENTS.md` language rules, `.specs/STATE.md` AD-004 and AD-013 to AD-018, `site/src/lib/forbidden-terms.ts` (19 terms), and the app-skeleton plan's criteria. Rendered HTML of 10 pages was read from the running app (`curl http://localhost:8091/...` after `mandato:import tests/fixtures/v3`).

Per-screen enumeration. Each item is a selector-reachable decision and the check that covers it:

- **Deputy profile, with legislature navigation.** Covered:
  - hero eyebrow (C34) and the only `<h1>` (C34)
  - `nav[aria-label=Legislaturas]`: presence and absence, count 2, newest first, labels, hrefs, `aria-current` (C36, C37)
  - one-mandate text inside `.ma-hero` (C37)
  - bases paragraph: once, before the first `.ma-ndem`, with its link (C46)
  - 3 `.ma-indicator` groups: headings and order, 2 `.ma-ndem` each in merit-then-all order, labels, the six values, method links (C45)
  - empty basis (C47); symbolic sentence variants (C48, C49)
  - partitura: columns, order, links, table labels (C41)
  - footer (C43), initials with no `<img>` (C44), head (C38, C39), 8 method notes (C66)

  **Not covered:** the `.ma-stat` values (authored, first signer, requirements), which `design/screens/Profile.vue` draws and the page renders per mandate. **Not covered:** a note on the symbolic count (P1, AGENTS.md).
- **Senator profile.** The same regions are covered (C35, C37, C38, C39, C41, C43, C44). Also covered: the Senate presiding label (C41), "Não registrou voto" with no official code in the HTML or the props (C42), and the Senate symbolic sentence (C49). Uncovered: the same two items as the deputy profile.
- **Senate roll call.** Covered:
  - the only `<h1>` (C51, C52) and the classification line with its rule link (C53)
  - groups in position order with headings, names, `svg.ma-vote` labels and `.ma-muted` "Registro do Senado:" notes (C55)
  - voter links (C51), symbolic and secret states (C56, C57), head (C58), 404s (C59)

  **Not covered:** the result label (`Aprovada`, `Rejeitada`, `Resultado não informado`) and the government-orientation line (`Orientação do governo: {Sim/Não/Abstenção/Obstrução/Liberado}` / `Sem orientação do governo registrada`). `design/screens/RollCall.vue` draws both. The skeleton proved the result label; that assertion was deleted with the skeleton test.
- **Methodology page.** Covered:
  - `<h1>` and the 10 `section[id]` in order, each with its copy (C62)
  - `registros-sem-voto` table, 15 rows, followed by the AD-018 sentence (C62)
  - rule tables per house, in order (C63), and empty states (C64)
  - coverage table, its columns and rows (C65), and head (C66)

  **Code renders what the plan does not draw:** a second sentence in `registros-sem-voto` (handoff copy choice 1), and the `Dados até` cell `sem votações` for a coverage entry with a null `through`.

Arrangement: each screen keeps the composition of its design screen. The member page has hero, then lede, then indicators section, then score section. The roll call has head, then result, then utilities, then members section. The checks hold the arrangement where the plan decides it: the indicator nesting (C45), the paragraph before the indicators (C46), the hero holding the legislature text (C37) and the group structure (C55). Cobertura renders one table per house, not one table. AC 51 speaks of each house's "part" of `cobertura`, so this is read as consistent, not as a finding.

| Source | Opened | Contradiction | Uncovered |
| --- | --- | --- | --- |
| plan doors 1-6, Surface, AC 1-58 | yes - `.specs/features/app-contract-v3/plan.md` | none | `registros-sem-voto` renders a sentence outside the AC 49 copy (`app/resources/js/Pages/Methodology/Show.vue:117`); the `sem votações` coverage state, which the plan does not draw (`Methodology/Show.vue:136`) |
| plan resolved open question 2 / door 6 | yes | none | - |
| `design/README.md` vote encoding + `vote.js`, `VoteMark`, `MandateScore` | yes | none | - |
| `design/screens/Profile.vue` | yes | none | `.ma-stat` authored / first-signer / requirements values per mandate (`app/resources/js/Pages/Members/Show.vue:76`, `:80`, `:84`): no proof since `DeputyPageTest` was deleted |
| `design/screens/RollCall.vue` | yes | none | result label (`app/app/Http/Controllers/RollCallController.php:76`) and government-orientation line (`RollCallController.php:82`, `app/app/Presenters/Labels.php:25`): no proof on either house |
| research 06 §3 P1 / P12 + `AGENTS.md` ("cada número derivado tem metodologia pública e link para a fonte oficial") | yes | none | the symbolic count on the member page has no note to its source or method (`app/resources/js/Pages/Members/Show.vue:68`) |
| research 06 §4 traps (no hand-picked "votações importantes", no ranking) | yes | none | - (C35/C46 published rule, C50 ranking words) |
| AD-004, forbidden terms (19) | yes - `site/src/lib/forbidden-terms.ts` | none | - (C50, `ForbiddenTermsTest`) |
| STATE AD-013 to AD-018 | yes - `.specs/STATE.md` | none | - (AD-016 C26; AD-017 C1-C8; AD-018 C11, C55, C62) |
| app-skeleton plan criteria still holding | yes - `.specs/features/app-skeleton/plan.md` | none | - (AC 13 table C41; AC 30 `ForbiddenTermsTest`; AC 31 `readme names the commands`) |

## Checks

Proof runs, at HEAD `a714a93`, in Sail project `mandato-acv3` with the SSR server up:

- `sail artisan test --log-junit`: 148 passed, 0 failed, 0 skipped. Each filter below matched by name in the JUnit output.
- `sail npm --prefix /var/www/design test -- --reporter=verbose`: 47 passed.

| Check | Claim | Proof run | Evidence | Result |
| --- | --- | --- | --- | --- |
| C1 | members and 4 mandates field by field | `--filter="imports the members and mandates of a house"` 1/1 | `app/tests/Feature/ImportTest.php:84` - `toBe([$m['name'], $m['party'], $m['uf'], $m['photoUrl'], $m['sourceUrl']])`; `:93` both bases; `:105` - `toBe(['PSB SP', 'PT SP', 'PL RJ', 'MDB MG'])` | PASS |
| C2 | 5 exercise periods | `stores each exercise period of a mandate` 1/1 | `app/tests/Feature/ImportTest.php:121` - the 5 literal period strings | PASS |
| C3 | 8 roll calls with ballot, kind, descriptions | `imports every roll call with ballot and kind` 1/1 | `app/tests/Feature/ImportTest.php:137` field-by-field `toBe`; `:147` - `toBe('Votação secreta em turno único.')`; `:149` null pair for 100-4 | PASS |
| C4 | 13 votes with official and position | `imports every vote with official and position` 1/1 | `app/tests/Feature/ImportTest.php:173` - `$stored->toBe($expected)`; `:174`-`:176` the three named rows | PASS |
| C5 | propositions, 11 rules in order, 1 full text | `imports propositions rules and full texts` 1/1 | `app/tests/Feature/ImportTest.php:190` - `toBe(['REQ', 5, 2023])`; `:196` `camara.01..11`; `:207` full-text fields | PASS |
| C6 | exactly 4 authorships | `attaches authorship to the mandate of the presentation date` 1/1 | `app/tests/Feature/ImportTest.php:222` - the 4 tuples | PASS |
| C7 | null stays null | `keeps absent counts null and never zero` 1/1 | `app/tests/Feature/ImportTest.php:235` Senate all null; `:240` - `toBe([null, null, null])`; `:242` - `toBe([2, 1, 0])` | PASS |
| C8 | printed lines and ContractImport row | `reports and records one import per house` 1/1 | `app/tests/Feature/ImportTest.php:249` - `toBe('Imported '.CAMARA_LINE)`; `:258` meta sha; `:261` counts `[3, 4, 8, 13, 5, 1]` | PASS |
| C9 | schema_version 2 and 4 refused, counts unchanged | `refuses a schema version other than 3` 2/2 | `app/tests/Feature/ImportTest.php:275` - `toBe("schema_version {$version} is not supported; expected one of: 3")`; `:276` - `tableCounts()->toBe($before)` | PASS |
| C10 | 5 missing files, 7 schema failures with pointer | `refuses a house with a missing file` 5/5; `refuses a house file that fails its v3 schema` 7/7 | `app/tests/Feature/ImportTest.php:288`-`:289` path, house unchanged; `:301`-`:303` path, pointer, house unchanged | PASS |
| C11 | LS, LP, LAP refused | `refuses a raw senate leave code` 3/3 | `app/tests/Feature/ImportTest.php:347` - `toContain('/votes/0/official')`; `:348` 0 such votes | PASS |
| C12 | 15 unresolved references + roll-call proposition | `refuses a house whose references do not resolve` 16/16 | `app/tests/Feature/ImportTest.php:361` - `toMatch('/{dir}/{file}: .+: {id}$/')`; `:362` house unchanged | PASS |
| C13 | legislature dates differ, real and dry run | `refuses legislature dates that differ from the stored ones` 1/1 | `app/tests/Feature/ImportTest.php:425` - `toBe($line)`; `:427` 0 Senate members; `:431` dry run same line | PASS |
| C14 | idempotent import | `importing a house twice changes nothing` 1/1 | `app/tests/Feature/ImportTest.php:442` - `$second[$table]->toBe($first[$table])` | PASS |
| C15 | sweep per scope, keep member and proposition | `sweeps each scope the house lists` 1/1 | `app/tests/Feature/ImportTest.php:485`-`:496` | PASS |
| C16 | unlisted legislature left alone | `leaves a legislature the house does not list` 1/1 | `app/tests/Feature/ImportTest.php:533` - `$state()->toBe($before)` | PASS |
| C17 | rules and texts replaced as a set, Senate intact | `replaces a house's rules and full texts as a set` 1/1 | `app/tests/Feature/ImportTest.php:550` order; `:552` - `toBe(['5003'])`; `:555` Senate dump equal | PASS |
| C18 | parent: Câmara then Senado; Câmara only | `imports camara then senado from a parent directory` 1/1 | `app/tests/Feature/ImportTest.php:562`-`:563`; `:571`-`:572` | PASS |
| C19 | one house fails, other kept, both directions | `keeps the other house when one house fails` 1/1 | `app/tests/Feature/ImportTest.php:586`-`:596`; `:603`; `:615`-`:619` | PASS |
| C20 | no contract (1), missing dir (2) | `finds no contract in a directory without houses` 1/1; `rejects a missing directory` 1/1 | `app/tests/Feature/ImportTest.php:629` - `toBe("no contract found in {$dir}")`; `:635`-`:636` exit 2 and message | PASS |
| C21 | rollback on a failed write | `rolls back a house on a failed write` 1/1 | `app/tests/Feature/ImportTest.php:665`-`:666` - `tableDump()->toBe($before)` | PASS |
| C22 | lock held | `refuses while another import holds the lock` 1/1 | `app/tests/Feature/ImportTest.php:681`-`:684` | PASS |
| C23 | no candidacy field, no cpf | `persists no candidacy field and no cpf` 1/1 | `app/tests/Feature/ImportTest.php:695`, `:700` | PASS |
| C24 | schema_dir default and v3 pointer | `validates against the v3 schema directory` 1/1 | `app/tests/Feature/ImportTest.php:705`; `:720`-`:721` - `toContain('/0/uf')` | PASS |
| C25 | the ETL's Senate fixture validates in place, completed copy imports | `loads the etl's own senate fixture` 1/1 | `app/tests/Feature/ImportTest.php:727`-`:733` validated in place; `:754`-`:755` 5 officials | PASS |
| C26 | v3 reader only | `resolves only the v3 reader` 1/1 | `app/tests/Feature/ImportTest.php:759`-`:764` | PASS |
| C27 | default dir, no-arg import, missing configured dir | `defaults the contract directory to data v3` 1/1; `imports from the configured directory when none is given` 1/1 | `app/tests/Feature/ImportTest.php:768`; `:783`-`:784` 9 members; `:789`-`:790` exit 2 | PASS |
| C28 | dry run writes nothing | `dry run checks each house and writes nothing` 1/1 | `app/tests/Feature/ImportTest.php:797`-`:798`; `:804`-`:805` | PASS |
| C29 | natural keys, house check | `enforces the natural keys and the house check` 1/1; `rejects a house outside camara and senado` 3/3 | `app/tests/Feature/SchemaTest.php:81` unique; `:89` - `toBe('23514')`; `:94` `text`; `:107`-`:108` - `toContain("{$table}_house_check")` | PASS |
| C30 | PostgreSQL 18 | `runs on postgresql 18` 1/1 | `app/tests/Feature/SchemaTest.php:118`-`:119` | PASS |
| C31 | 9 check violations | `enforces the v3 enum and tally checks` 1/1 | `app/tests/Feature/SchemaTest.php:144` - `sqlState($write)->toBe('23514')`; `:148`-`:150` valid counterparts | PASS |
| C32 | keys, cascades, nullability, no FK on kind_rule | `enforces the v3 keys cascades and nullability` 1/1 | `app/tests/Feature/SchemaTest.php:156`-`:160` 23505 and 23502; `:164`, `:168` cascades; `:170` accepted | PASS |
| C33 | migration empties v2 rows | `the v3 migration empties the v2 rows` 1/1 | `app/tests/Feature/SchemaTest.php:203`; `:205` import exits 0 | PASS |
| C34 | deputy latest and per legislature | `deputy page shows the latest mandate and each legislature` 1/1 | `app/tests/Feature/MemberPageTest.php:33`-`:34`; `:38`; `:42` | PASS |
| C35 | senator page | `senator page shows the mandate` 1/1 | `app/tests/Feature/MemberPageTest.php:50`-`:51` | PASS |
| C36 | legislature nav | `member with two mandates lists the legislatures` 1/1 | `app/tests/Feature/MemberPageTest.php:58`-`:60`; `:63` | PASS |
| C37 | one mandate as text | `member with one mandate names its legislature` 1/1 | `app/tests/Feature/MemberPageTest.php:69`-`:70` | PASS |
| C38 | member head | `member head carries the share tags` 1/1 | `app/tests/Feature/MemberPageTest.php:77`-`:84`; `:87`-`:89` | PASS |
| C39 | member canonical | `member canonical is the bare path for the latest mandate` 1/1 | `app/tests/Feature/MemberPageTest.php:102`-`:103` over 5 cases | PASS |
| C40 | 9 member 404s | `member pages 404` 9/9 | `app/tests/Feature/MemberPageTest.php:110`-`:113` | PASS |
| C41 | partitura by position | `partitura draws each plenary vote by position` 1/1 | `app/tests/Feature/MemberPageTest.php:121`-`:122`; `:124`; `:127`-`:128`; `:132`-`:133` | PASS |
| C42 | Senate non-vote stays off the profile | `senator page says only that no vote was recorded` 1/1 | `app/tests/Feature/MemberPageTest.php:140`; `:145`-`:146` HTML and props | PASS |
| C43 | footer per house | `member footer carries its house's collection day` 1/1 | `app/tests/Feature/MemberPageTest.php:153`-`:155`; `:160`-`:161` | PASS |
| C44 | initials, no img | `member page draws initials and no remote image` 1/1 | `app/tests/Feature/MemberPageTest.php:168`-`:170` | PASS |
| C45 | merit then all, six values, method links | `indicators show the merit base then all votes` 1/1 | `app/tests/Feature/MemberPageTest.php:178`; `:183`-`:185`; `:187` - `toBe(['3 de 4', '7 de 10', '2 de 3', '4 de 6', '1 de 2', '5 de 8'])` | PASS |
| C46 | bases paragraph once, before indicators | `member page explains the two bases once` 1/1 | `app/tests/Feature/MemberPageTest.php:194`; `:196`-`:197`; `:199` | PASS |
| C47 | empty basis | `a basis without total shows no number` 1/1 | `app/tests/Feature/MemberPageTest.php:206`-`:209`; `:212` | PASS |
| C48 | symbolic count sentence | `member page states the symbolic count` 1/1 | `app/tests/Feature/MemberPageTest.php:218`-`:220` | PASS |
| C49 | no symbolic publication sentence | `member page says when a house publishes no symbolic votes` 1/1 | `app/tests/Feature/MemberPageTest.php:225`-`:227`; `:232`-`:233` | PASS |
| C50 | no ranking or forbidden word | `no ranking word in rendered pages` 1/1 | `app/tests/Feature/ForbiddenTermsTest.php:47` - `toHaveCount(23)`; `:54` - `inText(...)->toBe([])`; `:57`-`:59` matcher cases | PASS |
| C51 | Senate roll call lists every senator | `senate roll call page lists every senator` 1/1 | `app/tests/Feature/RollCallPageTest.php:34`; `:36`; `:39` | PASS |
| C52 | roll-call heading | `roll call heading names the proposition or the ballot` 1/1 | `app/tests/Feature/RollCallPageTest.php:55`-`:56` over 6 cases | PASS |
| C53 | ballot, kind, rule link | `roll call names its ballot kind and rule` 1/1 | `app/tests/Feature/RollCallPageTest.php:71`-`:74` over 6 cases | PASS |
| C54 | VoteGroups order and headings | `orders vote groups by position` 1/1 | `app/tests/Unit/VoteGroupsTest.php:18`-`:19`; `:22`; `:28` | PASS |
| C55 | groups, marks, Senate notes | `roll call groups every member by position` 1/1 | `app/tests/Feature/RollCallPageTest.php:80`-`:91`; `:94`-`:101`; `:104`-`:109`; `:112`-`:113`. Row links asserted on 100-2 (`:108`) and, through C51, on 6923; the 7001 and 100-6 links use the same builder | PASS |
| C56 | symbolic roll call | `symbolic roll call has no tally and no group` 1/1 | `app/tests/Feature/RollCallPageTest.php:119`-`:121` | PASS |
| C57 | secret without tally | `secret roll call without a tally says so` 1/1 | `app/tests/Feature/RollCallPageTest.php:127`-`:129`; `:132` - `toBe(['2', '1', '0'])` | PASS |
| C58 | roll-call head by ballot | `roll call head carries the share tags by ballot` 1/1 | `app/tests/Feature/RollCallPageTest.php:138`-`:147`; `:149`-`:150`; `:153`-`:156` | PASS |
| C59 | 7 roll-call 404s | `roll call pages 404` 7/7 | `app/tests/Feature/RollCallPageTest.php:162`-`:166` | PASS |
| C60 | positionCase table | `npm test -- tests/vote.test.ts -t "positionCase"` 4/4 | `design/tests/vote.test.ts:35`-`:37` 14 pairs; `:52`-`:54` 7 officials + null; `:68` equals `voteCase`; `:73`-`:76` shapes | PASS |
| C61 | VoteMark / MandateScore by position | `npm test -- tests/components.test.ts -t "by position"` 2/2 | `design/tests/components.test.ts:178`-`:179`; `:203` 6-entry legend; `:208`-`:212` old inputs | PASS |
| C62 | methodology sections and copy | `methodology page has every section in order` 1/1 | `app/tests/Feature/MethodologyPageTest.php:39`; `:41`; `:44`; `:48`-`:65` 15 rows; `:67` AD-018 sentence | PASS |
| C63 | rules per house in order | `methodology lists each house's rules in stored order` 1/1 | `app/tests/Feature/MethodologyPageTest.php:75`-`:76`; `:83`-`:87` | PASS |
| C64 | house without import | `methodology says when a house has no import` 1/1 | `app/tests/Feature/MethodologyPageTest.php:97`-`:100`; `:109`-`:111` | PASS |
| C65 | coverage table | `methodology shows coverage per house and legislature` 1/1 | `app/tests/Feature/MethodologyPageTest.php:119`-`:126` | PASS |
| C66 | methodology head, method links | `methodology head and every method link point to the app` 1/1 | `app/tests/Feature/MethodologyPageTest.php:132`-`:134`; `:139`; `:145`-`:147` | PASS |
| C67 | no Set-Cookie on 15 responses | `public pages set no cookie` 15/15; `curl -sI http://localhost:8091/senadores/9101/` piped to `grep -ci '^set-cookie'` printed `0` (status 200) | `app/tests/Feature/SharedLinksTest.php:92`-`:93` - `assertHeaderMissing('Set-Cookie')` | PASS |
| C68 | SSR body | `server renders every new page body` 1/1 | `app/tests/Feature/SharedLinksTest.php:22`-`:24`; `:27`; `:31`; `:34`-`:38` | PASS |
| C69 | head survives SSR outage | `head tags survive an ssr outage` 1/1 | `app/tests/Feature/SharedLinksTest.php:56`-`:65` | PASS |
| C70 | slashless canonical | `slashless paths answer with the slash canonical` 1/1 | `app/tests/Feature/SharedLinksTest.php:124`-`:125` | PASS |
| C71 | public group, URIs, wheres, one builder | `public routes use the cookie-free group` 1/1; `public urls have one builder` 1/1 | `app/tests/Feature/SharedLinksTest.php:151`-`:153`; `:155`; `:160`-`:165`; `:173` | PASS |
| C72 | design package pages, no Head import | `consumes the design package` 1/1; `pages leave the share tags to blade` 1/1 | `app/tests/Feature/ProjectFilesTest.php:80`; `:82`; `app/tests/Feature/SharedLinksTest.php:131` | PASS |
| C73 | Inertia JSON visits | `inertia visits answer with json` 7/7 | `app/tests/Feature/SharedLinksTest.php:74`-`:78` | PASS |

Level: every claim naming a status, a route or a page shape is proven over HTTP through Laravel's test client, reading the server-rendered HTML. Every claim naming an exit code or a printed line runs the Artisan command and reads the exit code, stdout and stderr separately. There is no level gap.

## Coverage

Each set is recomputed from its authority: `app/routes/public.php` for routes, the door 3 migration for constraints, the v3 schema enums and door 5 for labels, and `design/screens/*` for elements drawn.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| routes x statuses (7 routes, 13 pairs) | `app/routes/public.php` | 200: C34, C35, C51, C52, C62 · 404: C40, C59 · slashless C70 · JSON C73 | - |
| `mandato:import` exit codes (3) | `ImportContract` | 0 C1 · 1 C9-C13, C19, C20, C22 · 2 C20, C27 | - |
| refusal causes (9) + references (16) + missing files (5) + schema kinds (7) | V3Reader, plan AC 7-11 | C9, C10 (12 sets), C11, C12 (16 sets), C13, C20, C21, C22 | - |
| AD-018 codes refused (3) | AD-018 | LS, LP, LAP: C11 | - |
| door 3 check constraints (9) | migration `app/database/migrations/2026_10_03_000000_reshape_for_contract_v3.php:130` | each one in C31 | - |
| door 3 unique, cascade, no-FK (6) | migration `:60`, `:108`, `:113` | C32 | - |
| door 3 not-null columns (17: `legislatures.starts_on`, `ends_on`; `memberships.party`, `uf`; 12 `{indicator}_{basis}_{count,total}`; `contract_imports.house`) | plan door 3; migration `:27`-`:28`, `:42`-`:48` | `memberships.party` C32 · `contract_imports.house` C32 | `legislatures.starts_on`, `legislatures.ends_on`, `memberships.uf`, the 12 indicator count/total columns |
| vote positions x houses, shown (14) + Senate officials (6 + other + null) | door 5, roll-call schema `position` enum | C54, C60 (table-driven) · page C41, C42, C55 | - |
| ballot labels (3), kind labels (4), rule link forms (2) | roll-calls schema enums, AC 41-42 | C52, C53, C63 | - |
| roll-call result label shown (3: `Aprovada`, `Rejeitada`, `Resultado não informado`) | `design/screens/RollCall.vue:37`; `RollCallController.php:76` | none (the skeleton's assertion was deleted) | all 3 |
| government orientation shown (5 enum values + absent) | door 3 `government_orientation` values; `Labels.php:25`; `design/screens/RollCall.vue:41` | none | `yes`, `no`, `abstention`, `obstruction`, `free` labels and `Sem orientação do governo registrada` |
| profile stats per mandate (3) | `design/screens/Profile.vue:44`-`:55`; `Members/Show.vue:76`-`:84` | none (skeleton `DeputyPageTest` stats assertion deleted) | authored, first signer, requirements values |
| member page decisions, indicators x bases (6), basis states (2), symbolic states (4) | plan AC 23-38 | C34-C39, C45, C47, C48, C49 | - |
| methodology sections (10), house parts (4), coverage symbolic cell (2) | AC 49-52 | C62, C63, C64, C65 | - |
| cookie-free responses (15), SSR states (2) | AC 55-57 | C67, C68, C69 | - |
| startup config (2 assemblies) | `bootstrap/app.php` public group; `config/mandato.php` | C71 reads `getMiddlewareGroups()['public']` (`SharedLinksTest.php:155`); C24/C27 read `config(...)` | - |

## Test policy rows

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `vote.js` `positionCase`, `VoteGroups`, profile copy branches, roll-call presentation (`RollCallController`, `Labels`), `V3Reader`, `Importer`, `ImportContract` | own layer + boundary; one asserted case per decision-table row | no - two decision tables in the roll-call presentation have no row asserted: the result `match` (`RollCallController.php:76`, 3 rows) and `Labels::ORIENTATIONS` (`Labels.php:25`, 5 rows + null). Every other table meets it: C60/C41/C42/C55, C54/C55, C36-C39 and C47-C49, C52, C53, C56-C58, C9-C22 |
| Decides, not reached across a boundary | `PublicUrl` house branch | own layer | yes - C71 (`SharedLinksTest.php:160`-`:165`) |
| Entry point that decides nothing | routes, `MethodologyController` | accepted, each rejected input, each error path | yes - C40, C59, C62, C70, C73 |
| Instrumentation, pass-throughs | `MemberController` props, `RollCallController` props | covered by the consumer's proof | no - the member page props `authoredCount`, `firstSignerCount`, `requirementsCount` (`MemberController.php:101`-`:103`) reach the page with no consumer assertion |

Swept rows: every row cites a check, and authorization and state transitions are `n/a`. None cites an existing constraint, so there is nothing in the code for them to be wrong about.

## Faults injected

Isolated in `git worktree add --detach /tmp/acv3-verify-fi HEAD` with its own Sail project `acv3-verify-fi` (APP_PORT 8097, FORWARD_DB_PORT 54347, VITE_PORT 5187) and the SSR server running in that project. The target proofs were green in the scratch before any fault went in (21 Pest, 4 design). The real tree's `git status --porcelain` was empty before and empty after. The scratch containers, network, volume and worktree were removed afterwards.

| Mutation | Location | Killed |
| --- | --- | --- |
| skip the `kindRule` reference check | `app/app/Contract/V3Reader.php:100` | yes - C12 data set `kindRule` failed (15/16 passed) |
| store `symbolicMerit ?? 0` | `app/app/Contract/V3Reader.php:249` | yes - C7 failed |
| send the Senate `notVoting` official to the member page | `app/app/Http/Controllers/MemberController.php:75` | yes - C42 failed |
| swap `presiding` and `secret` in the group order | `app/app/Presenters/VoteGroups.php:17` | yes - C54 failed |
| label a Senate `notVoting` with null official `Sem voto: ...` | `design/components/vote.js:60` | yes - C60, 2 of 4 tests failed |

## Handoff deviations

- **Senate fixture imported as a completed copy (C25): accepted.** `etl/tests/fixtures/v3/senado/roll-calls/6923.json` has votes by 9103, 9104 and 9105, and its `members.json` does not list them. AC 10 has to refuse that, so importing it in place would contradict AC 10. C25 still validates every file in place (`ImportTest.php:727`-`:733`). The inconsistency in the ETL fixture should be raised with contract-v3.
- **C12 count corrected from 13 to 15: accepted.** The test carries 16 data sets, the 15 of AC 10 plus the roll-call proposition, and all 16 passed.
- **Skeleton tests for superseded v2 criteria replaced: partly accepted.** The v2 assertions are rightly gone. But skeleton AC 12 and AC 18 were superseded wholesale, while AC 23 and AC 41 restate only part of them. So the profile stats assertion and the roll-call result-label assertion were deleted with no successor. See the gaps below.
- **Copy choice: profile lede dropped: accepted.** AC 35 puts the bases paragraph in the lede position before the indicators. A lede that shows only the `all` participation number would set one base above the other, which goes against S4.
- **Copy choice: extra `registros-sem-voto` sentence: a finding.** It is accurate and consistent with door 6, but it is copy outside the approved AC 49 table, and no check holds it. Either a human amends the copy table or the sentence is removed.
- **Merge a714a93: accepted.** The three adopted skeleton proofs are present and pass on v3 rows (C27, C29, C73). The v2 reader stays deleted (C26, `ImportTest.php:763`-`:764`).

Not findings, for the orchestrator:

- The partitura caption reads "deste deputado" / "deste senador" for every member, women included (`app/resources/js/Pages/Members/Show.vue:94`). The plan's eyebrow assumption rejects gendered titles for this reason.
- The rule `description` cells publish "Mérito, ..." verbatim from the ETL (plan open question 1).
- The `cobertura` counts carry no source note (P1).

## Gate

- `sail artisan test`: 148 passed, 0 failed (1506 assertions)
- `sail npm --prefix /var/www/design test`: 47 passed, 0 failed
- `sail bin pint --test`: passed
- `sail bin phpstan analyse`: 0 errors
- `sail npm run build`: wrote `public/build/manifest.json` and `bootstrap/ssr/ssr.js`
- `inertia:start-ssr`: up for every page proof
- `curl -sI http://localhost:8091/senadores/9101/ | grep -ci '^set-cookie'`: `0`

Ranked gaps:

1. The symbolic count is a derived number with no source or method note (P1, AGENTS.md) - AC 37 / C48 - `app/resources/js/Pages/Members/Show.vue:68`
2. No proof for the roll-call government-orientation labels (new `Labels::ORIENTATIONS` mapping) or the result label - no check - `app/app/Http/Controllers/RollCallController.php:76`, `:82`; `app/app/Presenters/Labels.php:25`
3. No proof for the profile stats per mandate (authored, first signer, requirements) - no check - `app/resources/js/Pages/Members/Show.vue:76`; `app/app/Http/Controllers/MemberController.php:101`
4. A `registros-sem-voto` sentence outside the approved AC 49 copy - C62 - `app/resources/js/Pages/Methodology/Show.vue:117`
5. 15 of door 3's 17 not-null columns are unproven - C32 - `app/database/migrations/2026_10_03_000000_reshape_for_contract_v3.php:27`
6. The `sem votações` coverage state is not drawn by the plan - C65 - `app/resources/js/Pages/Methodology/Show.vue:136`
