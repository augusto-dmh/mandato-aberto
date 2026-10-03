# app-contract-v3 verification

**Verdict**: PASS
**Profile**: ui
**Diff range**: f0fbdbc..42b36fa28f7a09334236a18e05dbeb825ba127c8
**Round**: 2 - scoped
**Verifier**: independent sub-agent (author != verifier)

Round 2 is scoped to the fix range `27c1c71..42b36fa` (`047a66e`, `51b2ae5`, `f67751d`, `42b36fa`) and to the six gaps of round 1 (FAIL at `a714a93`). Every proof re-ran at HEAD `42b36fa`. All 82 checks are proven. Each of the six round-1 gaps is closed by a new check, or by a plan amendment plus a check:

| Round-1 gap | Closed by | How |
| --- | --- | --- |
| 1. symbolic count with no source or method note | C74 (AC 37, amended) | the counting sentence carries note 7 when `symbolicMerit` is not null, and no note when it is null; a fault was injected and caught |
| 2. result and orientation labels unproven | C76, C77 | table-driven over 3 and 6 values on both houses |
| 3. profile stats per mandate unproven | C78 | 3 labels and values over 4 mandates with distinct counts, plus the note's method link |
| 4. extra `registros-sem-voto` sentence | AC 49 copy table amended, C79 | the sentence is now approved copy and held exactly |
| 5. 15 door 3 not-null columns unproven | C81 | table-driven over all 15; a fault was injected on the 7 columns the builder's own fault left out, and caught |
| 6. `sem votações` state not drawn | AC 52 amended, C80 | the cell is now drawn by the plan and held |

The orchestrator also acted on two round-1 non-findings. The gendered score caption is now neutral copy in AC 31, held by C82. The coverage counts now carry source notes (AC 52), held by C75, with a fault injected and caught.

## Binding sources

Verified at `42b36fa` for the screens the fix touched: the member profile (deputy and senator), the roll-call result area and the methodology page. Carried from `a714a93` for everything else: the Senate roll-call groups, the head and the 404s.

Opened this round:
- the plan as amended: AC 31, AC 37, AC 49 copy table, AC 52, and door 3 for the not-null set
- `design/screens/Profile.vue`, which draws the stats and their shared note (`indicators.length + 1`, `:44`-`:58`) and the score caption (`:65`)
- `design/screens/RollCall.vue`, which draws the result and orientation
- research 06 §3 P1
- `AGENTS.md` language rules: no gendered word for the person, every derived number with method and source

I read the rendered HTML of 7 pages from the running app at HEAD (`curl http://localhost:8091/...` after `mandato:import tests/fixtures/v3`):
- `/deputados/101/`, `/deputados/101/legislatura/57/`, `/deputados/102/`
- `/senadores/9101/`
- `/votacoes/100-1/`, `/senado/votacoes/6923/`
- `/metodologia/`

Per-screen enumeration of the touched regions. Each selector-reachable decision and the check that covers it:

- **Deputy profile.** Everything covered in round 1 still holds (C34-C49, carried from `a714a93`, proofs re-run at HEAD). New this round:
  - the `.ma-symbolic` marker `#nota-7` and its `SourceNote` (member `sourceUrl`, `Câmara dos Deputados`, `#votacoes-simbolicas`) in the n > 1, n = 1 and 0 states (C74; rendered `app/resources/js/Pages/Members/Show.vue:69`, `:73`, `:75`)
  - notes renumbered `nota-1`..`nota-9` (C74)
  - the 3 `.ma-stat` labels and per-mandate values, and their one shared note to `#proposicoes` (C78; `Members/Show.vue:77`)
  - the `Votações do mandato` caption, exactly the AC 31 text (C82; `Members/Show.vue:98`)

  Rendered HTML matches: `/deputados/101/legislatura/57/` reads "... decidiu 2 votações simbólicas sobre propostas e emendas.⁷ Votação simbólica não registra ..." with stats `2`, `1`, `1` under marker 8.
- **Senator profile.** The null state has no symbolic marker and no `#votacoes-simbolicas` note. Notes run `nota-1`..`nota-8`, with the stats on 7 and the score on 8 (C74). The stats are `1`, `1`, `0` (C78) and the caption is the same neutral text (C82). No `deste`/`desta` + `deputad`/`senador` appears in `main` on any of the 4 pages (C82).
- **Roll-call result area (both houses).**
  - the one `.ma-result .ma-t-title-1` for `approved` true, false and null (C76; `app/app/Http/Controllers/RollCallController.php:77`-`:78`)
  - the one `.ma-result > p.ma-t-small` for the 5 orientation values and the absent one (C77; `RollCallController.php:82`, `app/app/Presenters/Labels.php:25`)

  The note marker on the result title is stripped by `textOf` (`app/tests/Pest.php:127`), so C76 compares the label alone.
- **Methodology page.**
  - `registros-sem-voto`: the table, then the AD-018 sentence, then exactly the senator-page sentence (C62, C79; `app/resources/js/Pages/Methodology/Show.vue:119`)
  - `cobertura`: per-house tables, each `Casa` cell with marker `#nota-{i+1}`, and the note after each table with portal, Brasília day and `#tipos-de-votacao` (C75; `Methodology/Show.vue:137`, `:148`; `app/app/Presenters/Labels.php:39`; `app/app/Http/Controllers/MethodologyController.php:29`-`:30`, `:58`)
  - `sem votações` for a null `through` (C80; `Methodology/Show.vue:139`)

  Rendered: "Fonte: Câmara dos Deputados, dados de 01/03/2027 · Como calculamos" for `generatedAt` `2027-03-02T02:30:00Z`, which is the Brasília day.

Arrangement: the touched regions keep their design composition. On the member page the symbolic sentence and its note sit inside the indicators section, before the stats, which have their own note, as `Profile.vue` orders indicators then stats. Each methodology house part is a table followed by its note.

| Source | Opened | Contradiction | Covered by | Uncovered |
| --- | --- | --- | --- | --- |
| plan doors 1-6, Surface, AC 1-58 (amended AC 31, 37, 49, 52) | yes - `.specs/features/app-contract-v3/plan.md`, verified at `42b36fa` | none | `registros-sem-voto` sentence now in the AC 49 table, C79; `sem votações` now in AC 52, C80 | - |
| plan resolved open question 2 / door 6 | carried from `a714a93` | none | - | - |
| `design/README.md` vote encoding + `vote.js`, `VoteMark`, `MandateScore` | carried from `a714a93` | none | - | - |
| `design/screens/Profile.vue` | yes, verified at `42b36fa` | none | stats C78; caption C82, neutral per AC 31 | - |
| `design/screens/RollCall.vue` | yes, verified at `42b36fa` | none | result C76; orientation C77 | - |
| research 06 §3 P1 / P12 + `AGENTS.md` ("cada número derivado tem metodologia pública e link para a fonte oficial") | yes, verified at `42b36fa` | none | symbolic count C74; coverage counts C75 | - |
| research 06 §4 traps (no hand-picked "votações importantes", no ranking) | carried from `a714a93` | none | C35/C46 published rule, C50 ranking words | - |
| AD-004, forbidden terms (19) | carried from `a714a93` | none | C50, `ForbiddenTermsTest`, re-run at HEAD | - |
| STATE AD-013 to AD-018 | carried from `a714a93` | none | AD-016 C26; AD-017 C1-C8; AD-018 C11, C55, C62, C79 | - |
| app-skeleton plan criteria still holding | carried from `a714a93` | none | skeleton AC 12 stats now C78; skeleton AC 18 result label now C76; AC 13 table C41; AC 30 `ForbiddenTermsTest`; AC 31 `readme names the commands` | - |

## Checks

Verified at `42b36fa`. Proof runs in Sail project `mandato-acv3` (APP_PORT 8091, FORWARD_DB_PORT 54341), after `sail npm run build` and with the SSR server restarted on the new bundle:

- `sail artisan test --log-junit`: 178 passed, 0 failed, 0 skipped (1628 assertions). The 30 new tests are C74 1, C75 1, C76 3, C77 6, C78 1, C79 1, C80 1, C81 15, C82 1.
- Each of the 86 `--filter` proofs named in checks.md ran on its own, and all 86 passed with the counts shown.
- Design: `sail npm --prefix /var/www/design test -- --reporter=verbose` gave 47 passed. Run alone, `tests/vote.test.ts -t "positionCase"` gave 4 passed and `tests/components.test.ts -t "by position"` gave 2 passed.

Evidence for C1-C73 is carried from `a714a93`, with line numbers refreshed in the test files the fix touched: `RollCallPageTest.php` +1 from line 4, `SchemaTest.php` +26 after line 172, and `MethodologyPageTest.php` C66.

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
| C33 | migration empties v2 rows | `the v3 migration empties the v2 rows` 1/1 | `app/tests/Feature/SchemaTest.php:229`; `:231` import exits 0 | PASS |
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
| C51 | Senate roll call lists every senator | `senate roll call page lists every senator` 1/1 | `app/tests/Feature/RollCallPageTest.php:35`; `:37`; `:40` | PASS |
| C52 | roll-call heading | `roll call heading names the proposition or the ballot` 1/1 | `app/tests/Feature/RollCallPageTest.php:56`-`:57` over 6 cases | PASS |
| C53 | ballot, kind, rule link | `roll call names its ballot kind and rule` 1/1 | `app/tests/Feature/RollCallPageTest.php:72`-`:75` over 6 cases | PASS |
| C54 | VoteGroups order and headings | `orders vote groups by position` 1/1 | `app/tests/Unit/VoteGroupsTest.php:18`-`:19`; `:22`; `:28` | PASS |
| C55 | groups, marks, Senate notes | `roll call groups every member by position` 1/1 | `app/tests/Feature/RollCallPageTest.php:81`-`:92`; `:95`-`:102`; `:105`-`:110`; `:113`-`:114`. Row links asserted on 100-2 (`:109`) and, through C51, on 6923; the 7001 and 100-6 links use the same builder | PASS |
| C56 | symbolic roll call | `symbolic roll call has no tally and no group` 1/1 | `app/tests/Feature/RollCallPageTest.php:120`-`:122` | PASS |
| C57 | secret without tally | `secret roll call without a tally says so` 1/1 | `app/tests/Feature/RollCallPageTest.php:128`-`:130`; `:133` - `toBe(['2', '1', '0'])` | PASS |
| C58 | roll-call head by ballot | `roll call head carries the share tags by ballot` 1/1 | `app/tests/Feature/RollCallPageTest.php:139`-`:148`; `:150`-`:151`; `:154`-`:157` | PASS |
| C59 | 7 roll-call 404s | `roll call pages 404` 7/7 | `app/tests/Feature/RollCallPageTest.php:163`-`:167` | PASS |
| C60 | positionCase table | `npm test -- tests/vote.test.ts -t "positionCase"` 4/4 | `design/tests/vote.test.ts:35`-`:37` 14 pairs; `:52`-`:54` 7 officials + null; `:68` equals `voteCase`; `:73`-`:76` shapes | PASS |
| C61 | VoteMark / MandateScore by position | `npm test -- tests/components.test.ts -t "by position"` 2/2 | `design/tests/components.test.ts:178`-`:179`; `:203` 6-entry legend; `:208`-`:212` old inputs | PASS |
| C62 | methodology sections and copy | `methodology page has every section in order` 1/1 | `app/tests/Feature/MethodologyPageTest.php:39`; `:41`; `:44`; `:48`-`:65` 15 rows; `:67` AD-018 sentence | PASS |
| C63 | rules per house in order | `methodology lists each house's rules in stored order` 1/1 | `app/tests/Feature/MethodologyPageTest.php:75`-`:76`; `:83`-`:87` | PASS |
| C64 | house without import | `methodology says when a house has no import` 1/1 | `app/tests/Feature/MethodologyPageTest.php:97`-`:100`; `:109`-`:111` | PASS |
| C65 | coverage table | `methodology shows coverage per house and legislature` 1/1 | `app/tests/Feature/MethodologyPageTest.php:119`-`:126` | PASS |
| C66 | methodology head, method links | `methodology head and every method link point to the app` 1/1 | `app/tests/Feature/MethodologyPageTest.php:132`-`:134`; `:139`; `:146`-`:148` (9 links on a deputy page, 8 on a senator's, since C74) | PASS |
| C67 | no Set-Cookie on 15 responses | `public pages set no cookie` 15/15; `curl -sI http://localhost:8091/senadores/9101/` piped to `grep -ci '^set-cookie'` printed `0` (status 200) | `app/tests/Feature/SharedLinksTest.php:92`-`:93` - `assertHeaderMissing('Set-Cookie')` | PASS |
| C68 | SSR body | `server renders every new page body` 1/1 | `app/tests/Feature/SharedLinksTest.php:22`-`:24`; `:27`; `:31`; `:34`-`:38` | PASS |
| C69 | head survives SSR outage | `head tags survive an ssr outage` 1/1 | `app/tests/Feature/SharedLinksTest.php:56`-`:65` | PASS |
| C70 | slashless canonical | `slashless paths answer with the slash canonical` 1/1 | `app/tests/Feature/SharedLinksTest.php:124`-`:125` | PASS |
| C71 | public group, URIs, wheres, one builder | `public routes use the cookie-free group` 1/1; `public urls have one builder` 1/1 | `app/tests/Feature/SharedLinksTest.php:151`-`:153`; `:155`; `:160`-`:165`; `:173` | PASS |
| C72 | design package pages, no Head import | `consumes the design package` 1/1; `pages leave the share tags to blade` 1/1 | `app/tests/Feature/ProjectFilesTest.php:80`; `:82`; `app/tests/Feature/SharedLinksTest.php:131` | PASS |
| C73 | Inertia JSON visits | `inertia visits answer with json` 7/7 | `app/tests/Feature/SharedLinksTest.php:74`-`:78` | PASS |
| C74 | symbolic count points to source and method; null has no note | `the symbolic count points to its source and method` 1/1 | `app/tests/Feature/MemberPageTest.php:255` - `toBe(['#nota-7'])` over 57 (2), 102 (1), 101 (0); `:256`-`:259` source = member `source_url` labelled `Câmara dos Deputados`, method `#votacoes-simbolicas`; `:260` `nota-1..9`; `:264`-`:266` Senate: 0 markers, no `#votacoes-simbolicas`, `nota-1..8` | PASS |
| C75 | coverage markers per house and their notes | `coverage counts point to their source and method` 1/1 | `app/tests/Feature/MethodologyPageTest.php:173` 2 tables; `:176`-`:177` markers `#nota-1` x2, `#nota-2` x1 and no other marker; `:180`-`:182` portal URL, `#tipos-de-votacao`, note text with Brasília day | PASS |
| C76 | result label over 3 values, both houses | `roll call shows its result` 3/3 | `app/tests/Feature/RollCallPageTest.php:174` - `toBe([$label])`; data `:177`-`:179` | PASS |
| C77 | orientation line over 6 values, both houses | `roll call shows the government orientation` 6/6 | `app/tests/Feature/RollCallPageTest.php:186` - `toBe([$line])`; data `:189`-`:194` | PASS |
| C78 | stats per mandate, 4 mandates, shared note to `#proposicoes` | `the profile counts the propositions of the rendered mandate` 1/1 | `app/tests/Feature/MemberPageTest.php:279` labels; `:280` values per path; `:282`-`:283` one marker, method `https://mandato.test/metodologia/#proposicoes` | PASS |
| C79 | `registros-sem-voto` ends with the AD-018 sentence then exactly the senator-page sentence | `methodology says where senate non-votes appear` 1/1 | `app/tests/Feature/MethodologyPageTest.php:159`-`:161` - text equality, then `nextElementSibling` null | PASS |
| C80 | null `through` row reads `sem votações` | `coverage says when a legislature has no roll call` 1/1 | `app/tests/Feature/MethodologyPageTest.php:193` - `toBe(['Câmara dos Deputados', '58ª', 'sem votações', '0', '0', '0', '0'])` | PASS |
| C81 | 15 not-null columns refuse null (23502), accept a value | `refuses a null in each not-null column of door 3` 15/15 | `app/tests/Feature/SchemaTest.php:174` the 15 columns; `:195` - `toBe('23502')`; `:196` valid row `toBeNull()` | PASS |
| C82 | neutral score caption on both houses, no gendered person word | `the score caption names no gender` 1/1 | `app/tests/Feature/MemberPageTest.php:292`-`:293` caption equality; `:294` - `main` text does not match `deste`/`desta` followed by `deputad`/`senador` | PASS |

Level: every new claim naming a page shape (C74-C80, C82) is proven over HTTP through Laravel's test client, reading the server-rendered HTML. The SSR server was up, and `requireSsr` fails a test, never skips it. C81 writes to PostgreSQL 18 and reads the SQLSTATE. There is no level gap.

## Coverage

Rows the fix touched are recomputed from their authority at `42b36fa`. The other rows are carried from `a714a93`, and their proofs re-ran at HEAD.

| Set (size) | Recomputed from | Member -> proof | Unproven |
| --- | --- | --- | --- |
| routes x statuses (7 routes, 13 pairs) - carried from `a714a93` | `app/routes/public.php` | 200: C34, C35, C51, C52, C62 · 404: C40, C59 · slashless C70 · JSON C73 | - |
| `mandato:import` exit codes (3) - carried | `ImportContract` | 0 C1 · 1 C9-C13, C19, C20, C22 · 2 C20, C27 | - |
| refusal causes (9) + references (16) + missing files (5) + schema kinds (7) - carried | V3Reader, plan AC 7-11 | C9, C10 (12 sets), C11, C12 (16 sets), C13, C20, C21, C22 | - |
| AD-018 codes refused (3) - carried | AD-018 | LS, LP, LAP: C11 | - |
| door 3 check constraints (9) - carried | migration `app/database/migrations/2026_10_03_000000_reshape_for_contract_v3.php:130` | each one in C31 | - |
| door 3 unique, cascade, no-FK (6) - carried | migration `:60`, `:108`, `:113` | C32 | - |
| door 3 not-null columns (17) - verified at `42b36fa` | plan door 3 names exactly these as not null: `legislatures.starts_on`, `ends_on`; the 12 `{indicator}_{basis}_{count,total}`; `memberships.party`, `uf`; `contract_imports.house` (migration `:27`-`:28`, `:42`-`:48`) | `memberships.party`, `contract_imports.house` C32 · the other 15 C81, table-driven (`SchemaTest.php:174`) | - |
| vote positions x houses, shown (14) + Senate officials (6 + other + null) - carried | door 5, roll-call schema `position` enum | C54, C60 (table-driven) · page C41, C42, C55 | - |
| ballot labels (3), kind labels (4), rule link forms (2) - carried | roll-calls schema enums, AC 41-42 | C52, C53, C63 | - |
| roll-call result label shown (3) - verified at `42b36fa` | `design/screens/RollCall.vue:37`; `RollCallController.php:76`-`:79` match arms | `Aprovada`, `Rejeitada`, `Resultado não informado`: C76 on both houses | - |
| government orientation shown (5 enum values + absent) - verified at `42b36fa` | door 3 `government_orientation` check values; `Labels.php:25`; `RollCallController.php:82` | `yes`, `no`, `abstention`, `obstruction`, `free`, absent: C77 on both houses | - |
| profile stats per mandate (3) - verified at `42b36fa` | `design/screens/Profile.vue:44`-`:58`; `Members/Show.vue:77`; props `MemberController.php:101`-`:103` | authored, first signer, requirements: C78 over 4 mandates with distinct values | - |
| symbolic note by state (2) - verified at `42b36fa` | `Members/Show.vue:27` (`symbolicNote` null vs 7); AC 37, AC 38 | count present (n > 1, 1, 0), marker and note: C74 · null, neither: C74 | - |
| member note numbering (2 sequences) - verified at `42b36fa` | `Members/Show.vue:27`-`:28` | with symbolic count `nota-1..9`: C74 · without `nota-1..8`: C74; method links 9 / 8: C66 | - |
| score caption (1 text, 2 houses) - verified at `42b36fa` | AC 31 as amended | C82 on 2 deputies and 2 senators | - |
| member page decisions, indicators x bases (6), basis states (2), symbolic sentence states (4) - carried | plan AC 23-38 | C34-C39, C45, C47, C48, C49 | - |
| methodology sections (10), house parts (4), coverage symbolic cell (2) - carried | AC 49-52 | C62, C63, C64, C65 | - |
| `registros-sem-voto` paragraphs (2) - verified at `42b36fa` | AC 49 copy table as amended | AD-018 sentence C62 · senator-page sentence C79 (and nothing after it) | - |
| coverage `Dados até` cell (2) - verified at `42b36fa` | `Methodology/Show.vue:139`; AC 52 as amended | date C65 · `through` null C80 | - |
| coverage notes (2 houses) and `Labels::openData` branches (2) - verified at `42b36fa` | `Methodology/Show.vue:137`, `:148`; `Labels.php:39`-`:42`; AC 52 | Câmara marker and note C75 · Senado marker and note C75 | - |
| cookie-free responses (15), SSR states (2) - carried | AC 55-57 | C67, C68, C69 | - |
| startup config (2 assemblies) - carried | `bootstrap/app.php` public group; `config/mandato.php` | C71 reads `getMiddlewareGroups()['public']` (`SharedLinksTest.php:155`); C24/C27 read `config(...)` | - |

## Test policy rows

Re-judged at `42b36fa`: the two rows that were unmet in round 1, plus every row that classifies a file the fix touched. The rest are carried from `a714a93`.

| Row | Files it classifies | Required proof | Expectation met |
| --- | --- | --- | --- |
| Decides, reached across a boundary | `vote.js` `positionCase`, `VoteGroups`, profile copy branches (now including the `symbolicNote` branch, `Members/Show.vue:27`), roll-call presentation (`RollCallController`, `Labels` including `openData`), `V3Reader`, `Importer`, `ImportContract` | own layer + boundary; one asserted case per decision-table row | yes - result `match` 3 rows C76; `Labels::ORIENTATIONS` 5 rows + null C77; `symbolicNote` 2 rows C74; `Labels::openData` 2 rows C75; the rest as in round 1 (C60/C41/C42/C55, C54/C55, C36-C39 and C47-C49, C52, C53, C56-C58, C9-C22). The checks.md row prices presentation tables at the boundary, so boundary-only proof is what it asks for |
| Decides, not reached across a boundary | `PublicUrl` house branch | own layer | yes - C71 (`SharedLinksTest.php:160`-`:165`), carried from `a714a93` |
| Entry point that decides nothing | routes, `MethodologyController` | accepted, each rejected input, each error path | yes - C40, C59, C62, C70, C73; the new `MethodologyController` props (`:29`-`:30`, `:58`) reach the page through C75 |
| Instrumentation, pass-throughs | `MemberController` props, `RollCallController` props, `MethodologyController` props | covered by the consumer's proof | yes - `authoredCount`, `firstSignerCount`, `requirementsCount` (`MemberController.php:101`-`:103`) by C78; `symbolicMethodUrl` (`:115`) by C74; `sourceUrl`, `collectedAt`, `coverageMethodUrl` by C75; `approved`, `governmentOrientation` by C76, C77 |

Swept rows: every row cites a check, and authorization and state transitions are `n/a`. None cites an existing constraint, so there is nothing in the code for them to be wrong about.

## Faults injected

Verified at `42b36fa`. The faults ran in `git worktree add --detach /tmp/acv3-v2-fi HEAD`, which had its own Sail project `acv3-v2-fi` (APP_PORT 8098, FORWARD_DB_PORT 54348, VITE_PORT 5188). Its `vendor`, `node_modules` and the generated `design/dist` were copied from the real tree. Every edit ran as `cd /tmp/acv3-v2-fi && ...` and was restored with `git -C /tmp/acv3-v2-fi checkout -- <file>`, and the scratch's `git status --porcelain` was empty after each one.

Before any fault went in, the 3 target proofs were green in the scratch with SSR up: C74 1/1, C75 1/1, C81 15/15. Afterwards `sail down -v` removed the scratch containers, network and volume `acv3-v2-fi_sail-pgsql`, and `git worktree remove --force` removed the worktree. The real tree's `git status --porcelain` was empty before the report was written.

I picked three surfaces the builder's own faults had not exercised:
- the null branch of the symbolic note (the builder only showed that the marker was absent before the change)
- the per-house numbering of the coverage markers
- the 7 not-null columns the builder's migration fault left nullable-proof-free

C76-C80 and C82 were shown to fail by the builder (checks.md Handoff). I did not re-inject on them.

| Mutation | Location | Killed |
| --- | --- | --- |
| `symbolicNote` always `7`, so a null-symbolic member also gets the symbolic `SourceNote` (rebuilt, SSR restarted) | `app/resources/js/Pages/Members/Show.vue:27` | yes - C74 failed at `MemberPageTest.php:265` ("not to contain '.../#votacoes-simbolicas'") |
| every coverage marker points to `#nota-1`, so the Senate row cites the Câmara note (rebuilt, SSR restarted) | `app/resources/js/Pages/Methodology/Show.vue:137` | yes - C75 failed at `MethodologyPageTest.php:176` (expected `#nota-2`, got `#nota-1`) |
| `legislatures.ends_on` and all six `{indicator}_{basis}_count` columns made `->nullable()` | `app/database/migrations/2026_10_03_000000_reshape_for_contract_v3.php:28`, `:46` | yes - C81 failed on exactly those 7 data sets (8/15 passed: the 8 untouched columns) |

## Handoff deviations

Verified at `42b36fa` for the round-1 Handoff lines in checks.md. The rest are carried from `a714a93`.

- **Round 1, item 1 (symbolic count and coverage notes) - accepted.** It matches AC 37 and AC 52 as amended. C66's test now expects 9 method links on a deputy page and 8 on a senator's (`MethodologyPageTest.php:146`). That follows from C74, and C66's claim, "every link starts with the app's `/metodologia/#`", is unchanged.
- **Round 1, items 2, 3 and 5 (tests for code already built) - accepted.** C76, C77, C78 and C81 pass at HEAD. My own fault on C81 hit the 7 columns the builder's fault did not touch, and was caught.
- **Round 1, item 4 (methodology copy kept by amending the plan) - accepted.** It was a decision the orchestrator recorded under the maintainer's delegation. AC 49 and AC 52 mark the additions "added at verification round 1", and C79 and C80 hold the exact copy.
- **Round 1, item 6 (neutral score caption) - accepted.** AC 31 now carries the caption verbatim, and the template matches it character for character (`Members/Show.vue:98`).
- Senate fixture completed copy (C25), the C12 count of 15, superseded skeleton tests, the dropped profile lede, and merge `a714a93`: carried from `a714a93`. The round-1 partial acceptance of the superseded skeleton tests is now complete, because skeleton AC 12 (stats) and AC 18 (result label) have successors in C78 and C76.

Not findings, for the orchestrator:

- The plan amendments to AC 31, 37, 49 and 52 were made under the maintainer's delegation, not reviewed by a person. AGENTS.md asks for a human-reviewed `plan.md`, so the maintainer may want to read those four criteria before merge.
- The coverage note number is the house's position (`i + 1`), not a running count. If only the Senate is imported, its only coverage note is `nota-2`. Marker and note agree, so the link works. The plan does not set the numbering.
- `design/components/MandateScore.vue:87` renders "Ver as 1 votações como tabela" for a single vote (`/deputados/101/`). That is in the design package, outside the fix diff.
- Carried from `a714a93`: the rule `description` cells publish "Mérito, ..." verbatim from the ETL (plan open question 1, blocking go-live only).

## Gate

All at `42b36fa`, in `mandato-acv3`:

- `sail artisan test`: 178 passed, 0 failed (1628 assertions)
- the 86 named proofs, each with its own `--filter`: 86 passed
- `sail npm --prefix /var/www/design test`: 47 passed, 0 failed
- `sail bin pint --test`: passed
- `sail bin phpstan analyse`: 0 errors
- `sail npm run build`: wrote `public/build/manifest.json` and `bootstrap/ssr/ssr.js`
- `inertia:start-ssr`: restarted on the new bundle and up for every page proof (1 `ssr.js` process after the run)
- `sail artisan mandato:import tests/fixtures/v3`: exit 0, both houses imported
- `curl -sI http://localhost:8091/senadores/9101/` returned 200, and `grep -ci '^set-cookie'` printed `0`

Ranked gaps: none.
