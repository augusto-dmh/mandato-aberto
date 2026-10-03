# app-home checks

Profile: ui
Plan: `.specs/features/app-home/plan.md`

51 checks in 5 slices · 3 one-way doors · 0 open

All commands run from `app/` with this worktree's Sail project up: `app/.env` sets `COMPOSE_PROJECT_NAME=mandato-home`, `APP_PORT=8093`, `FORWARD_DB_PORT=54343`, `VITE_PORT=5183`; `sail` is `./vendor/bin/sail`. Pest proofs are `sail artisan test --filter="<test name>"`; page proofs read server-rendered HTML, so they need `sail npm run build` and `sail exec -d -u sail laravel.test php artisan inertia:start-ssr`, and a page test fails, never skips, when SSR is down (skeleton `requireSsr`). `{APP_URL}` is the value `phpunit.xml` sets, `https://mandato.test`.

Data the values below refer to:

- "The fixtures" are app-contract-v3's `app/tests/fixtures/v3/` (both houses), whose content `.specs/features/app-contract-v3/checks.md` lists. What this feature reads from them: Câmara `generatedAt` `2027-03-02T02:30:00Z` (Brasília day 01/03/2027), legislatures 57 (`2023-02-01`..`2027-01-31`) and 58 (`2027-02-01`..`2031-01-31`), its import listing 57 and 58; Senate `generatedAt` `2027-03-05T12:00:00Z` (05/03/2027), its import listing 57 only, with symbolic `null`. Câmara plenary roll calls of 57: `100-1` 2023-03-01 nominal final on PL 1/2023 approved, `100-2` 2023-05-10 nominal amendment no proposition rejected, `100-3` 2024-03-01 nominal procedural on PL 1/2023 result null, `100-4` 2024-08-01 symbolic, `100-5` 2025-08-01 secret final no proposition approved, `100-6` 2025-09-01 secret unclassified no proposition approved; `200-1` 2023-04-01 is `CCJC`, not plenary; of 58: `300-1` 2027-02-15 nominal final on PL 6/2027. Senate plenary of 57: `6923` 2025-04-01 nominal unclassified on PL 1/2025 approved, `7001` 2025-06-10 secret final no proposition approved. Memberships: Câmara 57 Ana Souza (101, PSB-SP), Bruno Lima (102, PL-RJ), Carla Dias (103, MDB-MG); Câmara 58 Ana Souza (101, PT-SP, period ending `2027-03-01T09:00:00`); Senate 57 Rosa Andrade (9101 PT-SP), Sérgio Prado (9102 PL-RJ), Teresa Lins (9103 MDB-BA), Ubiratan Costa (9104 PSD-AM), Vera Dantas (9105 PP-GO), Wagner Reis (9106 PSB-PE). Authorships of propositions of the counted types: PL 1/2023 (101 and 102 in 57), PEC 3/2024 (103 in 57), PL 6/2027 (101 in 58), PL 1/2025 (9101 in 57); REQ 5/2023 and INC 7/2024 are not counted types.
- "The search fixture" is a copy of the fixtures, built by a test helper, that adds through `mandato:import` (so every row passes the v3 schema and the importer): Câmara mandates in 58, each with every indicator 0 of 0 and one exercise period from `2027-02-01T00:00:00`: 104 João da Silva PT-SP (to `2027-03-01T09:00:00`), 105 Ágata Rocha PSOL-RJ (same), 106 Mariana Silva MDB-BA (to `2027-02-20T00:00:00`), 107 Abel Nunes PSD-AM (to `2027-03-01T09:00:00`), 108 Paulo Silva PL-SP (same), 1001 Paulo Silva PL-MG (same); legislature 58 added to the Senate `meta.json` (`2027-02-01`..`2031-01-31`, coverage nominal 0, secret 0, symbolic `null`) with senators 9107 Paulo Silva PT-SP (to `2027-03-05T12:00:00`), 9108 Zélia Moura PP-GO (same) and 9109 Otávio Brandão PSD-BA (to `2027-03-03T00:00:00`). In exercise in 58, per the plan's term (period end on or after the Brasília day of the house's latest `generatedAt`: Câmara 01/03/2027, Senate 05/03/2027): 101, 104, 105, 107, 108, 1001, 9107, 9108; not in exercise: 106 (ends before the Câmara day) and 9109 (ends 03/03, after the Câmara day and before the Senate's, so it is out only under its own house's day).
- "The budget dataset" is `Database\Seeders\BudgetSeeder`, the plan's Observable row made concrete (C51): legislatures 57 and 58; in 58, 600 Câmara and 81 Senate memberships, all in exercise, with names of 10 to 40 characters and 30 parties; in 57, 300 Câmara and 40 Senate memberships; per house 1,500 plenary roll calls in 58 (1,350 `nominal`, 150 `secret`) over the 48 months `2027-02`..`2031-01`, plus 500 Câmara plenary `symbolic`; each house's `ContractImport` generated `2031-01-31T15:00:00Z` listing 57 and 58 (Senate symbolic `null`). In the test database a test seeds it; for C45 it is seeded into the Sail development database with `sail artisan migrate:fresh --force && sail artisan db:seed --class=BudgetSeeder --force`.

A count `{n}` is written with pt-BR digit grouping (`1.500`). The plan writes every count label in the plural; when `n` is 1 the label takes the singular, as AC 6 does for its own count line: `1 votação nominal no plenário`, `1 votação secreta no plenário`, `1 votação simbólica no plenário`, `1 parlamentar com mandato na legislatura`, `1 proposição apresentada por parlamentares (PL, PLP, PEC, PDL e PRC)`; 0 and every other number take the plural.

## Checks

### S1 - home · ~6 files · ~40 KB · ~10k

**C1** - `GET /` over the fixtures responds 200 with Inertia component `Home/Index`, exactly one `<h1>`, reading `O que cada parlamentar federal fez no mandato`, and the paragraph right after it reading `O Mandato Aberto mostra como cada parlamentar votou e o que apresentou na Câmara dos Deputados e no Senado Federal, com dados abertos das duas Casas e a fonte e o método de cada número.` (AC 1)
Proof: `sail artisan test --filter="home says what the site is"`

**C2** - Over the fixtures, the home holds one `<form role="search" method="get" action="/busca/">` whose controls, in order, are: text input `q` labelled `Nome`; select `casa` labelled `Casa` with options `Todas as Casas` (value empty, selected), `Câmara dos Deputados` (`camara`), `Senado Federal` (`senado`); select `uf` labelled `UF` with `Todas as UFs` (selected) then the 27 codes `AC AL AM AP BA CE DF ES GO MA MG MS MT PA PB PE PI PR RJ RN RO RR RS SC SE SP TO`; select `partido` labelled `Partido` with `Todos os partidos` (selected) then `PT` (the only party in 58); select `legislatura` labelled `Legislatura` with `58ª legislatura (2027–2031)` (`58`, selected) then `57ª legislatura (2023–2027)` (`57`); select `situacao` labelled `Situação` with `Em exercício` (`exercicio`, selected) then `Todos` (`todos`); a submit button `Buscar`. Over the search fixture the `partido` options are `Todos os partidos`, `MDB`, `PL`, `PP`, `PSD`, `PSOL`, `PT` (AC 2)
Proof: `sail artisan test --filter="home search form works without javascript"`

**C3** - Over the fixtures, the home renders one house block headed `Câmara dos Deputados` holding `1 votação nominal no plenário` and `1 parlamentar com mandato na legislatura`, each followed by a `SourceNote` whose links are `https://dadosabertos.camara.leg.br/` (text `Câmara dos Deputados`) and `{APP_URL}/metodologia/#tipos-de-votacao` (resp. `#cobertura`) and whose text holds `dados de 01/03/2027`; no `Senado Federal` block (its import does not list 58); and exactly one link `Visão geral da 58ª legislatura` to `/legislaturas/58/`, after the house blocks. Over the search fixture the Câmara block reads `1 votação nominal no plenário` and `7 parlamentares com mandato na legislatura`, and a `Senado Federal` block follows it with `0 votações nominais no plenário` and `3 parlamentares com mandato na legislatura`, its notes linking `https://legis.senado.leg.br/dadosabertos/` (text `Senado Federal`) with `dados de 05/03/2027` (AC 3)
Proof: `sail artisan test --filter="home counts the current legislature per house"`

**C4** - With no `ContractImport` row, `GET /` responds 200 with the AC 1 `<h1>` and paragraph, the text `Ainda não há dados importados.`, no `<form>`, no house block and no link to `/legislaturas/` (AC 4)
Proof: `sail artisan test --filter="home without data says so"`

**C5** - The home's head holds exactly `<title>O que cada parlamentar federal fez no mandato - Mandato Aberto</title>`, `og:title` `O que cada parlamentar federal fez no mandato`, the C1 paragraph as `description` and `og:description`, `canonical` and `og:url` `{APP_URL}/`, and no `<meta name="robots">` (AC 5)
Proof: `sail artisan test --filter="home head tags"`

### S2 - search · ~8 files · ~60 KB · ~15k

**C6** - Over the search fixture, `GET /busca/` responds 200 with component `Search/Index`, the line `8 parlamentares, em ordem alfabética`, and 8 rows named, in order, Abel Nunes, Ágata Rocha, Ana Souza, João da Silva, Paulo Silva (108), Paulo Silva (1001), Paulo Silva (9107), Zélia Moura; `?casa=senado&uf=SP` gives the line `1 parlamentar, em ordem alfabética` (AC 6)
Proof: `sail artisan test --filter="search lists the members in exercise by default"`

**C7** - Each result row is one `<a>`; over the search fixture Ana Souza's row links `/deputados/101/` and its text is exactly `Ana Souza Câmara dos Deputados PT · SP`, Paulo Silva 9107's links `/senadores/9107/` with text `Paulo Silva Senado Federal PT · SP`; under `?legislatura=57` Ana's row links `/deputados/101/legislatura/57/` with `PSB · SP` and Rosa Andrade's links `/senadores/9101/legislatura/57/`; no row holds an `<img>`, a digit or a `.ma-num` element (AC 7)
Proof: `sail artisan test --filter="search rows link the member page of that legislature"`

**C8** - Over the search fixture, `?q=joao`, `?q=JOÃO` and `?q=silva%20joão` each list exactly João da Silva; `?q=joao%20santos` lists none; `?q=silva` lists João da Silva, Paulo Silva (108), Paulo Silva (1001), Paulo Silva (9107) (AC 8)
Proof: `sail artisan test --filter="search matches every token ignoring case and accents"`

**C9** - Over the search fixture, `?q=%25`, `?q=_` and `?q=%5C` each render the C18 no-match state (no name holds `%`, `_` or `\`), where a `LIKE` pattern would have listed all 8 (AC 9)
Proof: `sail artisan test --filter="search treats wildcards as literal characters"`

**C10** - Over the search fixture, a `q` of `João` followed by 96 spaces and `x` (101 characters after trimming) lists João da Silva, because only its first 100 characters count, and the input `q` shows `João` (AC 10)
Proof: `sail artisan test --filter="search reads the first 100 characters of q"`

**C11** - Over the search fixture: `?casa=camara` lists Abel Nunes, Ágata Rocha, Ana Souza, João da Silva, Paulo Silva (108), Paulo Silva (1001); `?uf=SP` lists Ana Souza, João da Silva, Paulo Silva (108), Paulo Silva (9107); `?partido=PL` lists Paulo Silva (108), Paulo Silva (1001); `?casa=camara&uf=SP&partido=PT` lists Ana Souza, João da Silva (AC 11)
Proof: `sail artisan test --filter="search filters by house uf and party combined"`

**C12** - Over the search fixture, `?situacao=todos` lists 10 rows: Abel Nunes, Ágata Rocha, Ana Souza, João da Silva, Mariana Silva, Otávio Brandão, Paulo Silva (108), Paulo Silva (1001), Paulo Silva (9107), Zélia Moura; `?situacao=exercicio` lists the C6 8; Otávio Brandão (period to 03/03/2027, after the Câmara's day 01/03 and before the Senate's 05/03) is absent from both the default and `exercicio` lists (AC 12)
Proof: `sail artisan test --filter="search lists only members in exercise unless asked for all"`

**C13** - Over the search fixture, `?legislatura=57` and `?legislatura=57&situacao=exercicio` each list the 9 memberships of 57 in order Ana Souza, Bruno Lima, Carla Dias, Rosa Andrade, Sérgio Prado, Teresa Lins, Ubiratan Costa, Vera Dantas, Wagner Reis (every exercise period of 57 ends before both import days) and render `A 57ª legislatura terminou em 31/01/2027; a lista mostra todos que tiveram mandato nela.`; the default page renders no such line (AC 13)
Proof: `sail artisan test --filter="search of a past legislature lists everyone who held a mandate"`

**C14** - Over the search fixture, each of `?casa=foo`, `?uf=XX`, `?uf=sp`, `?partido=XYZ`, `?partido=PSB` (a party of 57 only), `?legislatura=56`, `?legislatura=abc`, `?situacao=foo` responds 200 with the C6 rows and line, and the form shows that field's default selected (`Todas as Casas`, `Todas as UFs`, `Todos os partidos`, `58ª legislatura (2027–2031)`, `Em exercício`); `?legislatura=57&partido=PSB` lists Ana Souza, Wagner Reis (AC 14)
Proof: `sail artisan test --filter="search ignores a filter value outside its set"`

**C15** - Rows order by name under `Collator('pt_BR')`, then `camara` before `senado`, then source id in numeric order: in C6, Ágata Rocha sits between Abel Nunes and Ana Souza (byte order would put it after Zélia Moura), Paulo Silva 108 precedes Paulo Silva 1001 (string order would invert them), and both precede Paulo Silva 9107; adding `?ordem=nome-desc` or `?sort=uf` changes nothing, and the page holds no control other than the C2 form and the C16 links (AC 15)
Proof: `sail artisan test --filter="search orders by name then house then id only"`

**C16** - Over the budget dataset, `/busca/` shows 50 rows, `Página 1 de 14` (681 rows), no `Página anterior` and `Próxima página` linking `/busca/?pagina=2`; `/busca/?casa=camara&pagina=2` shows 50 rows, `Página 2 de 12`, `Página anterior` to `/busca/?casa=camara` and `Próxima página` to `/busca/?casa=camara&pagina=3`; `?casa=camara&pagina=12` shows 50 rows and only `Página anterior` to `/busca/?casa=camara&pagina=11`; `?casa=senado&pagina=2` shows 31 rows; the 12 Câmara pages hold 600 distinct rows, each page's last name collating at or before the next page's first; links keep the given parameters in the order `q`, `casa`, `uf`, `partido`, `legislatura`, `situacao`, `pagina`; over the search fixture (8 rows) no `Página` text and neither link render (AC 16)
Proof: `sail artisan test --filter="search pages by 50 keeping the other parameters"`

**C17** - Over the search fixture, `?pagina=99`, `?pagina=0`, `?pagina=-1`, `?pagina=abc`, `?pagina=1.5` and `?pagina=2` each respond 404 with the `<h1>` `Página não encontrada`; `?pagina=1` responds 200; `?q=zzz&pagina=99` responds 200 with the C18 state (empty result) (AC 17)
Proof: `sail artisan test --filter="search answers 404 for a page out of range"`

**C18** - Over the search fixture, `?q=zzz` responds 200 with `Nenhum parlamentar encontrado com esses filtros.`, a link `Limpar filtros` to `/busca/`, no result list element and no count line (AC 18)
Proof: `sail artisan test --filter="search with no match offers to clear the filters"`

**C19** - With no `ContractImport` row, `GET /busca/` and `GET /busca/?q=ana` respond 200 with `Ainda não há dados importados.` and no `<form>` (AC 19)
Proof: `sail artisan test --filter="search without data says so"`

**C20** - Over the search fixture, `/busca/?q=joao&casa=camara&uf=SP&partido=PT&situacao=todos` shows the form with input `q` valued `joao`, `Câmara dos Deputados`, `SP`, `PT`, `58ª legislatura (2027–2031)` and `Todos` selected; with the `data-page` script removed, the HTML holds `joao` exactly once, as that input's `value`; neither the `<title>`, any heading, the count line nor any `<meta>` holds it; the same holds for `?q=zzqx` and the C18 text (AC 20)
Proof: `sail artisan test --filter="search echoes q only in its input"`

**C21** - `/busca/?q=ana&uf=SP` has a head with exactly `<title>Buscar parlamentares - Mandato Aberto</title>`, `og:title` `Buscar parlamentares`, `description` and `og:description` `Busque deputados federais e senadores por nome, Casa, UF, partido e legislatura, em ordem alfabética.`, `canonical` and `og:url` `{APP_URL}/busca/`, and one `<meta name="robots" content="noindex, follow">` (AC 21)
Proof: `sail artisan test --filter="search head tags keep the query out"`

**C22** - `SearchKey::of` maps `  JOÃO   da\tSilva ` to `joao da silva`, `Ágata Çé Ü` to `agata ce u`, `100%_x\\` to `100%_x\\` and `` to ``, by NFD, dropping `\p{Mn}`, `mb_strtolower`, collapsing whitespace runs to one space and trimming (door 3)
Proof: `sail artisan test --filter="search key folds case accents and spaces"`

### S3 - legislature overview · ~6 files · ~50 KB · ~13k

**C23** - Over the fixtures, `GET /legislaturas/57/` responds 200 with component `Legislatures/Show`, exactly one `<h1>` `57ª legislatura`, the line `De 01/02/2023 a 31/01/2027`, then a section headed `Câmara dos Deputados` and after it one headed `Senado Federal`. The Câmara's counts read, in order, `3 votações nominais no plenário`, `2 votações secretas no plenário`, `1 votação simbólica no plenário`, `3 parlamentares com mandato na legislatura`, `2 proposições apresentadas por parlamentares (PL, PLP, PEC, PDL e PRC)` (`200-1` of `CCJC` is not counted, so the nominal count differs from the import coverage's 4; REQ and INC are not counted). The Senate's read `1 votação nominal no plenário`, `1 votação secreta no plenário`, then C25's line, `6 parlamentares com mandato na legislatura`, `1 proposição apresentada por parlamentares (PL, PLP, PEC, PDL e PRS)`. `/legislaturas/58/` gives the Câmara `1 votação nominal no plenário`, `0 votações secretas no plenário`, `0 votações simbólicas no plenário`, `1 parlamentar com mandato na legislatura`, `1 proposição apresentada por parlamentares (PL, PLP, PEC, PDL e PRC)` (AC 22)
Proof: `sail artisan test --filter="overview counts each house activity"`

**C24** - On `/legislaturas/57/`, each of the 9 rendered counts is followed by a `SourceNote` with `dados de 01/03/2027` and `https://dadosabertos.camara.leg.br/` (text `Câmara dos Deputados`) for the Câmara's 5, `dados de 05/03/2027` and `https://legis.senado.leg.br/dadosabertos/` (text `Senado Federal`) for the Senate's 4, and the method link `{APP_URL}/metodologia/#tipos-de-votacao` for the roll-call counts, `#cobertura` for members, `#proposicoes` for propositions; note ids are unique on the page (AC 23)
Proof: `sail artisan test --filter="overview counts carry their source note"`

**C25** - On `/legislaturas/57/` the Senate section renders `Votações simbólicas no plenário: não publicadas pela Casa.` in the third place and no `votação simbólica`/`votações simbólicas` count; the Câmara section renders no such line (AC 24)
Proof: `sail artisan test --filter="overview says the senate publishes no symbolic votes"`

**C26** - On `/legislaturas/57/` the Câmara section renders a calendar headed `Votações nominais e secretas no plenário, por mês` with year rows `2023` (11 cells, `fev`..`dez`), `2024` (12), `2025` (9, `jan`..`set`, the month of `100-6`); each cell's text is `{abbr} {count}`: `mar 1` and `mai 1` in 2023, `mar 1` in 2024, `ago 1` and `set 1` in 2025, every other `{abbr} 0`; each cell's bar has inline `height: 100%` for a count of 1 and `height: 0%` for 0. The Senate's calendar has `2023` (11), `2024` (12), `2025` (6, to `jun`) with `abr 1` and `jun 1` in 2025. Over the budget dataset, each Câmara cell's bar height equals `round(count × 100 / highest month, 1)` percent (AC 25)
Proof: `sail artisan test --filter="overview calendar counts roll calls by month"`

**C27** - The stylesheet rule for the calendar bar sets its background to `var(--ma-color-muted)` (achromatic) and no rule in `app.css` that styles the calendar names `--ma-color-accent` (AC 25)
Proof: `sail artisan test --filter="overview calendar bars use a neutral colour"`

**C28** - On `/legislaturas/57/` the Câmara section renders a table with header `Mês`, `Votações`, `Dias com votação` and 32 rows in date order from `fevereiro de 2023` to `setembro de 2025`, rows `março de 2023 1 1`, `maio de 2023 1 1`, `março de 2024 1 1`, `agosto de 2025 1 1`, `setembro de 2025 1 1` and every other `{mês} de {ano} 0 0`; over the budget dataset each Câmara row's `Dias com votação` equals the count of distinct dates of that month's plenary nominal or secret roll calls (AC 26)
Proof: `sail artisan test --filter="overview table repeats the calendar with voting days"`

**C29** - On `/legislaturas/57/` the Câmara section, headed `Votações nominais e secretas mais recentes`, lists 5 items, newest first: `01/09/2025 · Votação secreta de 01/09/2025 · Votação secreta · Sem regra correspondente · Aprovada` → `/votacoes/100-6/`; `01/08/2025 · Votação secreta de 01/08/2025 · Votação secreta · Decisão sobre a proposta · Aprovada` → `/votacoes/100-5/`; `01/03/2024 · PL 1/2023 · Votação nominal · Procedimento · Resultado não informado` → `/votacoes/100-3/`; `10/05/2023 · Votação nominal de 10/05/2023 · Votação nominal · Emenda, destaque ou parte do texto · Rejeitada` → `/votacoes/100-2/`; `01/03/2023 · PL 1/2023 · Votação nominal · Decisão sobre a proposta · Aprovada` → `/votacoes/100-1/`; the Senate's lists `10/06/2025 · Votação secreta de 10/06/2025 · Votação secreta · Decisão sobre a proposta · Aprovada` → `/senado/votacoes/7001/` then `01/04/2025 · PL 1/2025 · Votação nominal · Sem regra correspondente · Aprovada` → `/senado/votacoes/6923/` (AC 27)
Proof: `sail artisan test --filter="overview lists the latest nominal and secret roll calls"`

**C30** - Over the budget dataset, each house's recent list on `/legislaturas/58/` holds exactly 10 items, the 10 first of its plenary nominal and secret roll calls of 58 sorted by date descending then source id in descending numeric order (AC 27)
Proof: `sail artisan test --filter="overview recent list stops at 10"`

**C31** - Over the fixtures, `/legislaturas/58/` renders the Senate section as only `Ainda não há dados do Senado Federal para a 58ª legislatura.` (no count, calendar or list); with only the Câmara fixture imported, `/legislaturas/57/` renders the Senate section as only `Ainda não há dados do Senado Federal para a 57ª legislatura.`; with only the Senate fixture imported, `/legislaturas/57/` renders the Câmara section as only `Ainda não há dados da Câmara dos Deputados para a 57ª legislatura.` (AC 28)
Proof: `sail artisan test --filter="overview says when a house has no data"`

**C32** - Over the fixtures with roll call `300-1` deleted, `/legislaturas/58/` renders the Câmara's 5 counts (`0 votações nominais no plenário`, `0 votações secretas no plenário`, `0 votações simbólicas no plenário`, `1 parlamentar com mandato na legislatura`, `1 proposição apresentada por parlamentares (PL, PLP, PEC, PDL e PRC)`) and `Nenhuma votação nominal ou secreta no plenário até 01/03/2027.`, and no calendar, table or recent list (AC 29)
Proof: `sail artisan test --filter="overview without nominal roll calls says until when"`

**C33** - Over the fixtures, `/legislaturas/57/` renders one `<nav aria-label="Legislaturas">` with links `58ª legislatura (2027–2031)` → `/legislaturas/58/` then `57ª legislatura (2023–2027)` → `/legislaturas/57/`, only the latter with `aria-current="page"`; with only the Senate fixture imported (legislature 57 alone stored), no such nav renders (AC 30)
Proof: `sail artisan test --filter="overview navigates between legislatures"`

**C34** - Over the search fixture (18 members), the HTML of `/legislaturas/57/` and `/legislaturas/58/`, `data-page` included, holds none of the 18 member names, no `href` starting with `/deputados/` or `/senadores/`, no `<img>`, no element whose text contains `%`, and none of the strings `participação`, `alinhamento`, `percentual`, `distribuição`, `histograma` (AC 31)
Proof: `sail artisan test --filter="overview names no person"`

**C35** - Over the fixtures, `/legislaturas/56/`, `/legislaturas/59/`, `/legislaturas/abc/`, `/legislaturas/5a/` and `/legislaturas/` each respond 404 with the `<h1>` `Página não encontrada` (AC 32, door 1)
Proof: `sail artisan test --filter="overview answers 404 for a legislature not stored"`

**C36** - `/legislaturas/57/` has a head with exactly `<title>57ª legislatura: votações e proposições na Câmara e no Senado - Mandato Aberto</title>`, `og:title` without the suffix, `description` and `og:description` `Quantas votações nominais, secretas e simbólicas e quantas proposições houve na 57ª legislatura, por Casa, com dados oficiais e a fonte de cada número.`, `canonical` and `og:url` `{APP_URL}/legislaturas/57/`, and no robots tag (AC 33)
Proof: `sail artisan test --filter="overview head tags"`

### S4 - light, cookie-free, complete without JavaScript · ~6 files · ~30 KB · ~10k

**C37** - Over the search fixture, none of these 14 responses carries a `Set-Cookie` header: `/` 200, `/busca/` 200, `/busca/?q=ana&casa=camara` 200, `/busca/?q=zzz` 200, `/busca/?uf=XX` 200, `/legislaturas/57/` 200, `/legislaturas/58/` 200, `/busca/?pagina=99` 404, `/legislaturas/56/` 404, `/legislaturas/abc/` 404, `/legislaturas/` 404, and with no import `/` 200, `/busca/` 200, `/legislaturas/57/` 404 (AC 34)
Proof: `sail artisan test --filter="home search and overview set no cookie"`

**C38** - With SSR running, over the fixtures, the HTML of `/`, `/busca/`, `/busca/?situacao=todos` and `/legislaturas/57/` holds no `<script src` and no `rel="modulepreload"`, links exactly one stylesheet (the built `app.css`), and inside `#app` holds the page's `<h1>` text, every count of C3 (on `/`), every row of the list (on `/busca/`: `Ana Souza`) and every count of C23 (on `/legislaturas/57/`) (AC 35, door 2)
Proof: `sail artisan test --filter="light pages render complete html with no script"`

**C39** - With the SSR URL pointed at a closed port, `/`, `/busca/` and `/legislaturas/57/` each respond 200 with the head tags of C5, C21 (robots included) and C36, an empty `#app`, and a `<script type="module" src="…/build/assets/app-….js">` (AC 36, door 2)
Proof: `sail artisan test --filter="light pages fall back to the client when ssr is down"`

**C40** - `/busca` and `/legislaturas/57` (no trailing slash) respond 200 with canonical `{APP_URL}/busca/` and `{APP_URL}/legislaturas/57/`, the same as the slash forms (AC 37)
Proof: `sail artisan test --filter="slashless home paths answer with the slash canonical"`

**C41** - The HTML plus Inertia props of `/`, `/busca/`, `/busca/?situacao=todos`, `/busca/?q=zzz`, `/legislaturas/57/` and `/legislaturas/58/` over the search fixture, and of `/` with no import, contain none of 26 terms as whole words, case- and accent-insensitively: `importante`, `importantes`, `relevante`, `relevantes`, `mais votado`, `menos votado`, `posição` and the 19 of `config/forbidden-terms.php`; the matcher returns `posição` for `Sua POSICAO` and `mais votado` for `o mais  votado` (AC 38)
Proof: `sail artisan test --filter="no ranking word on home search or overview"`

**C42** - With SSR running over the budget dataset, the gzip-compressed (level 6, PHP `gzencode`) HTML of `/` is at most 20,000 bytes, of `/busca/` at most 25,000 bytes and of `/legislaturas/58/` at most 30,000 bytes (AC 39)
Proof: `sail artisan test --filter="light pages stay within the html budget"`

**C43** - The stylesheets `/`, `/busca/` and `/legislaturas/58/` link (read from their `<link rel="stylesheet">` hrefs, mapped to `public/`) total at most 8,000 bytes gzip-compressed (level 6) (AC 40)
Proof: `sail artisan test --filter="light pages stay within the css budget"`

**C44** - Loaded in Chromium (Playwright 1.63, `design/node_modules/playwright`) with the cache disabled, over the budget dataset with SSR running, the sum of `encodedDataLength` of every response (CDP `Network.loadingFinished`, headers and body as received) is at most 125,000 bytes for `/`, 130,000 for `/busca/` and 135,000 for `/legislaturas/58/`, and every request's origin equals the page's; the script exits 1 on any excess or foreign origin and prints each page's total. The pages are served through the script's own gzip proxy (level 6 on `text/*`, JS, JSON and SVG, as the production server will; `php artisan serve` compresses nothing), which forwards the `Host` header so every asset URL stays on the proxy's origin (AC 41)
Proof: `node tests/budget/first-load.mjs http://localhost:8093`

**C45** - Pages that do not set `meta.hydrate` keep today's head: `/metodologia/` and `/deputados/101/` hold no `<meta name="robots">`, one `<link rel="modulepreload">` and one `<script type="module" src>` for the client entry, and their head element sequence (`meta charset`, `meta viewport`, `title`, `meta description`, `link canonical`, `og:type`, `og:site_name`, `og:locale`, `og:title`, `og:description`, `og:url`, `twitter:card`, `link preload`, `link modulepreload`, `link stylesheet`, `script`) is unchanged (door 2)
Proof: `sail artisan test --filter="hydrated pages keep their head"`

**C46** - `/`, `/busca/` and `/legislaturas/57/` answer an Inertia visit (`X-Inertia: true` with the current version) with 200, header `X-Inertia: true`, JSON whose `component` is `Home/Index`, `Search/Index`, `Legislatures/Show` and whose `props.meta.title` is non-empty (Surface)
Proof: `sail artisan test --filter="light pages answer inertia visits with json"`

**C47** - Route `home` has uri `/`, `search` has `busca`, `legislatures.show` has `legislaturas/{n}` with `wheres` `['n' => '[0-9]+']`; each sits in the `public` group and not in `web`; no route has uri `legislaturas` (door 1)
Proof: `sail artisan test --filter="home routes use the cookie-free group"`

**C48** - `BudgetSeeder` leaves legislatures 57 and 58; in 58, 600 `camara` and 81 `senado` memberships, every one in exercise, 30 distinct parties, member names of 10 to 40 characters with at least one of exactly 40; in 57, 300 and 40 memberships; per house 1,500 plenary roll calls in 58, 1,350 `nominal` and 150 `secret`, spread over exactly 48 distinct months from 2027-02 to 2031-01; 500 Câmara plenary `symbolic`; one `ContractImport` per house generated `2031-01-31T15:00:00Z` whose coverage lists 57 and 58 (plan Observable, budget dataset)
Proof: `sail artisan test --filter="budget dataset matches the plan"`

### S5 - every page leads home and to search · ~3 files · ~10 KB · ~3k

**C49** - Over the fixtures, the footer of `/`, `/busca/` and `/legislaturas/57/` holds exactly one paragraph, `Dados abertos da Câmara dos Deputados, coletados em 01/03/2027, e do Senado Federal, coletados em 05/03/2027.`; with only the Câmara fixture imported, `Dados abertos da Câmara dos Deputados, coletados em 01/03/2027.`; with only the Senate fixture imported, `Dados abertos do Senado Federal, coletados em 05/03/2027.`; with no import, the footer of `/` and `/busca/` holds no paragraph; the member page `/deputados/101/` still reads its own house line only (AC 42)
Proof: `sail artisan test --filter="home search and overview footers name both houses"`

**C50** - On each of the 16 pages of `RENDERED_PAGES` and on `/`, `/busca/` and `/legislaturas/57/`, the `<header>` holds a link `Mandato Aberto` (the wordmark) to `/` and a link `Buscar parlamentar` to `/busca/` (AC 43)
Proof: `sail artisan test --filter="every public page links home and to search"`

**C51** - Over the budget dataset, the home renders `1.350 votações nominais no plenário` and `600 parlamentares com mandato na legislatura` for the Câmara and `1.350 votações nominais no plenário` and `81 parlamentares com mandato na legislatura` for the Senate, and `/legislaturas/58/` renders `150 votações secretas no plenário` for each house and, per house, exactly 48 calendar cells and 48 table rows, `fevereiro de 2027` to `janeiro de 2031` (AC 3, AC 22, AC 25, AC 26)
Proof: `sail artisan test --filter="overview and home scale to the budget dataset"`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| `GET /` statuses (1) | 200 C1, C4 | - |
| `GET /busca/` statuses (2) | 200 C6, C19 · 404 C17 | - |
| `GET /legislaturas/{n}/` statuses (2) | 200 C23 · 404 C35 | - |
| query keys of door 1 (7) | `q` C8 · `casa` C11 · `uf` C11 · `partido` C11 · `legislatura` C13 · `situacao` C12 · `pagina` C16, C17 | - |
| `casa` values (3) | `camara` C11 · `senado` C6 · invalid C14 | - |
| `uf` values (3) | a code C11 · unknown code C14 · lowercase C14 | - |
| `partido` values (3) | a party of the chosen legislature C11 · a party of another legislature C14 · unknown C14 | - |
| `legislatura` values (4) | current C14 · past C13 · not stored C14 · not digits C14 | - |
| `situacao` values (4) | absent C6 · `exercicio` C12 · `todos` C12 · invalid C14 | - |
| `pagina` values (7) | absent C16 · in range C16 · above range C17 · 0 C17 · negative C17 · not digits C17 · with an empty result C17 | - |
| `q` cases (7) | accent-insensitive C8 · case-insensitive C8 · tokens in any order C8 · one token missing C8 · wildcard characters C9 · over 100 characters C10 · echoed only in its input C20 | - |
| in exercise (4) | Câmara in C6 · Câmara out C12 · Senate in C6 · Senate out only under its own day C12 | - |
| result ordering keys (3) | pt-BR collation C15 · house C15 · numeric source id C15 | - |
| home states (2) | with data C2, C3 · no import C4 | - |
| search states (4) | rows C6 · no match C18 · no import C19 · paged C16 | - |
| overview house states (4) | counts and calendar C23, C26 · no import listing n C31 · listed with no nominal or secret C32 · symbolic not published C25 | - |
| overview counts (5 per house) | nominal C23 · secret C23 · symbolic C23, C25 · members C23 · propositions C23 | - |
| counted proposition types (2 houses) | `camara` with PRC C23 · `senado` with PRS C23 | - |
| count label number (3) | 0 C23 · 1 C23 · many C3, C51 | - |
| source note per count (3 method anchors) | `#tipos-de-votacao` C24 · `#cobertura` C24 · `#proposicoes` C24 | - |
| source note per house (2) | `camara` C3, C24 · `senado` C3, C24 | - |
| roll-call result labels (3) | `Aprovada` C29 · `Rejeitada` C29 · `Resultado não informado` C29 | - |
| roll-call heading forms (2) | proposition C29 · ballot and date C29 | - |
| recent list size (2) | under 10 C29 · capped at 10 C30 | - |
| calendar cell values (3) | 0 C26 · max C26 · fraction of max C26 | - |
| legislature nav (2) | more than one stored C33 · one stored C33 | - |
| overview 404 causes (4) | not stored below C35 · not stored above C35 · not digits C35 · no index route C35 | - |
| head tags per page (3) | home C5 · search C21 · overview C36 | - |
| footer houses (4) | both C49 · Câmara only C49 · Senate only C49 · none C49 | - |
| masthead pages (19) | C50, table-driven over all 19 | - |
| cookie-free responses (14) | C37, table-driven over all 14 | - |
| SSR states (2) | running C38 · unreachable C39 | - |
| trailing slash (2 routes) | `/busca` C40 · `/legislaturas/{n}` C40 | - |
| vocabulary terms (26) | C41, table-driven over all 26 | - |
| performance budget numbers (9) | `/` HTML 20,000 C42 · `/busca/` HTML 25,000 C42 · overview HTML 30,000 C42 · CSS 8,000 C43 · `/` total 125,000 C44 · `/busca/` total 130,000 C44 · overview total 135,000 C44 · no script C38 · same origin C44 | - |
| budget dataset facts (7) | memberships C48 · in exercise C48 · parties C48 · name lengths C48 · roll calls C48 · months C48 · imports C48 | - |
| Landing doors (3) | 1 C47, C35, C40 · 2 C38, C39, C45, C21 · 3 C22, C8, C15 | - |
| entities read (8) | `Legislature` C13, C23 · `Member` C7 · `Membership` C6, C23 · `ExercisePeriod` C12 · `RollCall` C23, C29 · `Proposition` C23 · `Authorship` C23 · `ContractImport` C3, C24, C49 | - |
| startup config: `public` group (1 shared assembly) | `bootstrap/app.php`, read by the HTTP kernel and the test harness alike, C47 | - |

- Claims naming a status code, route, head tag or response shape: C1-C21, C23-C26, C28-C41, C45-C47, C49-C51 - each proof crosses the HTTP boundary through Laravel's test client and reads the server-rendered HTML (C46 the Inertia JSON)
- C44 crosses the real boundary: Chromium against the Sail server
- C22, C27, C43, C48 assert at their own layer (a function, a stylesheet, built files, seeded rows)
- No other check claims more than the single case its proof exercises

## Test policy

The skeleton's rows (`.specs/features/app-skeleton/checks.md`, Test policy), which app-contract-v3 kept, still answer both questions for `app/`; this feature builds under them unchanged. Where the new decision tables sit:

| Code | Required proofs | Coverage expectation |
| --- | --- | --- |
| Decides, reached across a boundary | one at the boundary **and** one at its own layer | the contract at the boundary; one asserted case per row of the decision table at its own layer |
| Decides, not reached across a boundary | one at its own layer | one asserted case per row of the decision table |
| Entry point that decides nothing | one at the boundary | accepted input, each rejected input, each error path |
| Instrumentation, pass-throughs | none of its own | covered by its consumer's proof |

Evidence (the build may name files differently):

- `SearchKey`: 4 transformations (NFD and marks, case, whitespace, trim) -> decides, reached across HTTP; own layer C22, boundary C8, C9
- search controller: 6 filter validations (5 sets plus `q` length), in-exercise per house, legislature current or past, ordering on 3 keys, page bounds with an empty-result exception, 3 page states -> decides; every row asserted at the boundary (C6-C21), where the search fixture reaches each cheaply; the request reaches nothing else, so the boundary is its own layer
- overview and home counts: 3 ballots × plenary rule per house, member and proposition counts, 4 house states, symbolic null, singular or plural label, calendar range and bar fraction, recent cap -> decides; every row at the boundary (C3, C23-C33, C51)
- Blade root view: `robots` present or not, `hydrate` × SSR answered (3 outcomes) -> decides; boundary C21, C38, C39, C45
- `PublicLayout` footer: 4 house combinations -> decides; boundary C49
- closest analogue: app-contract-v3's member and roll-call pages, proven at the HTTP boundary per branch (its C34-C59), and `VoteGroups`' pt-BR ordering (its C54)

Cost: 1 proof at its own layer (C22) beyond the boundary proofs. Without it, the whitespace and trim rows of door 3 would be proven only through a name that happens to need them.

## Swept

- validation: C14, C17, C35, C10, C9
- failure modes: C39, C18
- idempotency: n/a - every route is a `GET` that writes nothing
- authorization: n/a - public read-only pages with no account; C37 keeps every response cookie-free
- concurrency: n/a - read-only; an import running meanwhile commits per house in one transaction (app-contract-v3), so a page reads one snapshot of each house
- data lifecycle: C4, C19, C31, C13 (a legislature becomes past when a newer one is stored)
- dependency failure: C39 (SSR down), C31 (one house not imported)
- state transitions: C12 (in exercise or not, per house import day), C13 (current or past legislature)
- observability: n/a - no logging requirement; the budget measurement C44 prints each page's total

## Handoff

Size from `wc -c` on the files each slice reads and writes, divided by four. Read once: the plan (35 KB), the app-contract-v3 checks and plan sections used (~40 KB), the app code it builds on (`app/app` ~40 KB, `resources` ~35 KB, `tests/Pest.php` and support ~12 KB), the skill references (~45 KB) = ~207 KB = ~52k. Written: S1 controller, page, form component and tests ~40 KB = ~10k; S2 controller, `SearchKey`, page, fixture helper and tests ~60 KB = ~15k; S3 controller, page, CSS and tests ~50 KB = ~13k; S4 view, seeder, budget script and tests ~30 KB = ~8k; S5 layout and tests ~10 KB = ~3k; this file ~32 KB = ~8k. Test and build output ~15k. Total ~52k + 57k + 15k = ~124k, under the 150k budget - one builder

- Mechanism: one builder (the estimate fits; no ask)
