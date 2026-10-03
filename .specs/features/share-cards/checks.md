# share-cards checks

Profile: ui
Plan: `.specs/features/share-cards/plan.md`

72 checks in 7 slices plus shape checks · 9 one-way doors · 1 open, blocking go-live only (plan open question 1: the public domain; cards print the host of `APP_URL`)

All commands run from `app/` with this worktree's Sail project up: `app/.env` sets `COMPOSE_PROJECT_NAME=mandato-cards`, `APP_PORT=8094`, `FORWARD_DB_PORT=54344`, `VITE_PORT=5184`; `sail` is `./vendor/bin/sail`, and the image is built from the published `app/docker/8.5/Dockerfile` (door 3). Pest proofs are `sail artisan test --filter="<test name>"`; page proofs read server-rendered HTML, so they need `sail npm run build` and the SSR server (`sail exec -d -u sail laravel.test php artisan inertia:start-ssr`), and fail, never skip, when SSR is down (skeleton `requireSsr`). Card proofs that say "real renderer" run `node bootstrap/cards/render.mjs` with the Chromium headless shell; the others replace it with `Process::fake`. Design proofs are `sail npm --prefix /var/www/design test -- <file> -t "<name>"` and `sail npm --prefix /var/www/design run test:e2e -- -g "<name>"`. `{APP_URL}` is `https://mandato.test` (`phpunit.xml`), so `{host}` is `mandato.test`.

No test reaches the internet. Official photos are synthetic JPEGs drawn with GD inside the test (`tests/Support/Jpeg.php`) and answered by `Http::fake` for `www.camara.leg.br`, `www.senado.leg.br` and `legis.senado.leg.br`; the card renderer aborts every non-`data:` request (C33).

Fixture values below are app-contract-v3's (its `checks.md`, opening paragraphs): Câmara `generatedAt` `2027-03-02T02:30:00Z`, whose Brasília date is 01/03/2027, so every Câmara code starts `20270301-`; Senate `2027-03-05T12:00:00Z`, Brasília 05/03/2027, codes `20270305-`. Ana Souza (101) holds 57 (PSB-SP; merit 3/4, 2/3, 1/2) and 58 (PT-SP; merit 1/1, 0/0, 1/1, one vote on `300-1`); Bruno Lima (102, 57 PL-RJ) has every merit total 0; Rosa Andrade (9101, 57 PT-SP). Roll calls `100-1` (PL 1/2023, 01/03/2023, nominal final, approved, 1/1/1), `100-3` (result null), `100-4` (symbolic), `100-5` (secret, tallies null), `7001` (Senate secret, 10/06/2025, approved, 40/20/1).

## Checks

### S1 - the official photo is cached byte for byte · ~9 files · ~60 KB · ~15k

**C1** - With both fixtures imported (9 members), `mandato:photos` requests each of the 9 `photo_url`s once (3 `www.camara.leg.br/internet/deputado/bandep/{id}.jpg`, 6 `www.senado.leg.br/senadores/img/fotos-oficiais/senador{id}.jpg`); a second run right after requests none; after 101's latest `checked_at` is set 8 days back it requests only 101's, and at 6 days back none; a member whose fetch failed (no current photo) is requested again on the next run (AC 1)
Proof: `sail artisan test --filter="photos requests only members without a current photo or with a stale check"`

**C2** - `--house=senado` requests only the 6 Senate URLs; `--house=camara --member=102` requests only 102's; `--stale-after=0` requests every member of the run's scope again (AC 1, door 9)
Proof: `sail artisan test --filter="photos narrows by house and member"`

**C3** - A 200 answer for 101 with a 354 × 472 JPEG body writes `photos/{sha256}.jpg` on the `media` disk whose SHA-256 equals the body's and records one `photo_versions` row: `house` `camara`, `member_source_id` `101`, that `sha256`, `source_url` and `final_url` 101's `photo_url`, `bytes` the body length, `width` 354, `height` 472, `fetched_at` and `checked_at` set; for 9101, a 301 to `https://legis.senado.leg.br/senadores/img/fotos-oficiais/senador9101.jpg` then a 200 480 × 600 JPEG records `final_url` that address, `width` 480, `height` 600 (AC 2)
Proof: `sail artisan test --filter="photos stores the official bytes unaltered with their version"`

**C4** - When two members' bodies are identical, one file is written and both rows point at it; a file that already exists is not rewritten (its modification time is unchanged) (AC 2)
Proof: `sail artisan test --filter="photos writes each file once"`

**C5** - A stale re-check answering the same bytes leaves `photo_versions` at 1 row for the member, keeps its `fetched_at`, moves its `checked_at` forward, and prints the member under `unchanged` (AC 3)
Proof: `sail artisan test --filter="photos records no new version for unchanged bytes"`

**C6** - With 101 holding a current photo, each of 10 failing answers on its stale re-check prints `photo failed camara 101: <reason>` to stderr, writes no file and no row, and leaves the current photo as it was: no answer (`Http::failedConnection`) `no response`; 404 `status 404`; 4 chained redirects `too many redirects`; a redirect to `https://example.org/f.jpg` `redirect to example.org`; a redirect to `http://www.camara.leg.br/f.jpg` `redirect over http`; an HTML body `not a jpeg`; a PNG body `not a jpeg`; `FF D8 FF` followed by 2 KB of zeros `not a jpeg`; a 3 MiB JPEG `larger than 2 MiB`; an 80 × 80 JPEG `smaller than 100 x 100` (AC 4)
Proof: `sail artisan test --filter="photos keeps the current photo when a fetch fails"`

**C7** - Every request carries the User-Agent `mandato-aberto-app (+https://github.com/augusto-dmh/mandato-aberto)`, the Guzzle options `timeout` 15 and `allow_redirects` false (each hop is followed and checked by the command), and a `photo_url` outside the three hosts is refused as `host not allowed` with no request sent (door 9)
Proof: `sail artisan test --filter="photos identifies itself and checks every hop"`

**C8** - With 9 members, requests go out in batches of at most 4 with a 250 ms `Sleep` after each batch but the last: 3 batches, 2 pauses (door 9)
Proof: `sail artisan test --filter="photos fetches four at a time with a pause"`

**C9** - A run where 102 answers 404 and every other member a distinct JPEG prints `photos camara: 3 checked, 2 new, 0 unchanged, 1 failed` and `photos senado: 6 checked, 6 new, 0 unchanged, 0 failed` to stdout and exits 0 (AC 5)
Proof: `sail artisan test --filter="photos prints one summary per house"`

**C10** - A run where every Câmara request fails exits 1 (the Senate's all succeed); a `--house=senado --member=9999` run, which requests nothing, exits 0 with `photos senado: 0 checked, 0 new, 0 unchanged, 0 failed` (AC 5)
Proof: `sail artisan test --filter="photos exits 1 when every fetch of a house failed"`

**C11** - When the media disk's root is not writable, the run prints `photo failed camara 101: storage write failed`, records no row and exits 1 (AC 5)
Proof: `sail artisan test --filter="photos exits 1 when storage fails"`

**C12** - While a second database connection holds `pg_advisory_lock` on the command's key, `mandato:photos` exits 1, prints `another photo run is running` to stderr and sends no request; the key differs from the importer's `57210057` (AC 6, door 9)
Proof: `sail artisan test --filter="photos refuses while another run holds the lock"`

**C13** - `--house=presidencia` and `--member=101` without `--house` each exit 2, print `usage: mandato:photos [--house=camara|senado] [--member=<id>] [--stale-after=<days>]` to stderr and send no request; `--stale-after=-1` and `--stale-after=x` do the same (AC 7)
Proof: `sail artisan test --filter="photos rejects bad options with the usage"`

**C14** - When 9104 and 9105 receive identical bytes, each has no current photo: both pages render the initials frame, both card payloads carry `photoSha256` null, and the next run requests both again; when 9105 later receives other bytes, 9104's photo becomes current (AC 8)
Proof: `sail artisan test --filter="a photo shared by two members is no one's photo"`

**C15** - `GET /fotos/{sha256}.jpg` for a stored photo answers 200, `Content-Type` `image/jpeg`, a body byte-identical to the stored file, `ETag` `"{sha256}"`, `Cache-Control` holding exactly the directives `public`, `max-age=31536000` and `immutable` (Symfony orders them alphabetically), and no `Set-Cookie` (AC 9, door 6)
Proof: `sail artisan test --filter="a stored photo is served with immutable headers"`

**C16** - `GET /fotos/{x}.jpg` answers 404 for 4 cases: 64 uppercase hex, 63 hex, 64 hex with a `g`, and a well-formed sha256 with no stored file; `/fotos/{sha256}` without `.jpg` answers 404 (AC 10)
Proof: `sail artisan test --filter="photo paths that are not a stored sha256 answer 404"`

**C17** - With `mandato.photo_suppressed` = `['camara:101']`, every `/fotos/` file of a `photo_versions` row of 101 answers 410 while 102's still answers 200 (AC 13)
Proof: `sail artisan test --filter="a suppressed member's photos answer 410"`

### S2 - member pages show the photo with the house's credit · ~5 files · ~40 KB · ~10k

**C18** - With a current photo stored for 101 and 9101, `/deputados/101/` renders one `.ma-hero img` with `src` `/fotos/{sha256}.jpg`, `alt` `Foto oficial de Ana Souza` and the caption `Foto: Câmara dos Deputados`; `/senadores/9101/` with `alt` `Foto oficial de Rosa Andrade` and `Foto: Agência Senado`; the Inertia props carry `photo` `{url, credit}` with the same values (AC 11)
Proof: `sail artisan test --filter="member pages show the official photo with the house credit"`

**C19** - For 103, with no photo stored, the page renders `.ma-photo__initials` with text `CD`, no `figcaption` and no `<img>` in `.ma-hero`, and props `photo` null (AC 12)
Proof: `sail artisan test --filter="a member without a current photo shows the initials"`

**C20** - With `mandato.photo_suppressed` = `['camara:101']` and a photo stored for 101, `/deputados/101/` renders the initials frame and `photo` null, and 101's current card code differs from the one computed before the suppression (the payload's `photoSha256` becomes null) (AC 13)
Proof: `sail artisan test --filter="a suppressed member renders the initials on page and card"`

**C21** - With photos stored for every member, the 6 member pages of the fixtures hold no `<img>` whose `src` is not a path starting with a single `/` (AC 14)
Proof: `sail artisan test --filter="member pages load no image from another origin"`

### S3 - cards render from the design template in three sizes · ~14 files · ~90 KB · ~23k

**C22** - Real renderer: for each of 4 subjects (deputy 101 legislature 58, senator 9101 legislature 57, roll call `100-1`, Senate roll call `7001`) × 3 formats, the card URL with the current code answers 200, `Content-Type` `image/png`, a PNG whose IHDR width and height are 1200 × 630, 1080 × 1350 or 1080 × 1920, `Cache-Control` with exactly `public`, `max-age=31536000`, `immutable`, and no `Set-Cookie` (AC 15)
Proof: `sail artisan test --filter="card routes serve a png of each format"`

**C23** - The first request of a card stores one `card_snapshots` row (`code` the URL's, `kind` `member`, `house` `camara`, `source_id` `101`, `legislature` 58, `template` 1, `payload` the payload the page computed), and that row already exists when the renderer starts (asserted inside the `Process::fake` callback); the PNG is then stored at `cards/{code}/1200x630.png` on the media disk, byte-identical to the body (AC 16, door 8)
Proof: `sail artisan test --filter="the snapshot is stored before the first render"`

**C24** - A second request for the same code and format, and a request for the same code in another format, start the renderer once per format: 3 requests, 2 runs, the repeated body byte-identical (AC 17)
Proof: `sail artisan test --filter="a stored card is served without rendering again"`

**C25** - After a card of 101/58 is served (code A) and 101's vote on `300-1` is changed from `yes` to `no`, the current code is B ≠ A, and a request for A answers 200 rendered from the stored payload: the renderer's stdin `props` carry the vote `yes` (AC 18)
Proof: `sail artisan test --filter="an old stored code renders from its snapshot"`

**C26** - For each of the 4 card routes, a well-formed code that is neither current nor stored (`20270301-00000000`), and a code stored for another subject, answer 302 with `Location` the same format's URL for the subject's current code (AC 19)
Proof: `sail artisan test --filter="an unknown well-formed code redirects to the current card"`

**C27** - 404 for each of 10 cases: deputy 999, senator 101 (a deputy's id), legislature 59 for 101, roll call `999-9`, Senate roll call 1, format `800x600`, format `1200x630.jpg`, code `20270301-k7q29xpd` (lowercase), code `20270301-K7Q29XPI` (`I`), code `2027031-K7Q29XPD` (7 digits) (AC 20)
Proof: `sail artisan test --filter="card urls that name nothing answer 404"`

**C28** - When the renderer exits 1 with stderr `boom`, each of the 4 card routes answers 503 with `Retry-After: 60`, stores no PNG and logs at `error` exactly `card render failed {code} 1200x630: boom` (AC 21)
Proof: `sail artisan test --filter="a failed render answers 503"`

**C29** - With the renderer replaced by `sleep 5` and the timeout set to 1 s, the card answers 503 with `Retry-After: 60`, stores no PNG and logs `card render failed {code} 1200x630: timed out`; `config('mandato.card_render_timeout')` defaults to 15 (AC 21)
Proof: `sail artisan test --filter="a render that runs too long answers 503"`

**C30** - While both render slots are held, a request for an unrendered card answers 503 with `Retry-After: 60` and starts no render; a request for an already stored card still answers 200 (AC 22)
Proof: `sail artisan test --filter="a third simultaneous render answers 503"`

**C31** - While another process holds the lock of one code and format and writes its PNG before releasing it, a request for that card waits, answers 200 with that PNG and starts no render (AC 22)
Proof: `sail artisan test --filter="one card is rendered once under simultaneous requests"`

**C32** - The card entry `resources/js/cards/render.js` imports from `mandato-design` exactly `components/MemberCard.vue`, `components/RollCallCard.vue`, `tokens.css` and `styles/components.css`, and its only other font sources are files of `@fontsource-variable/archivo` and `@fontsource-variable/source-serif-4` (AC 23, door 3)
Proof: `sail artisan test --filter="the card entry builds only from the design package"`

**C33** - Real renderer: a page holding `<img src="http://127.0.0.1:{port}/probe.jpg">` and a stylesheet link to the same server is captured with 0 requests reaching that server, and the CLI's stderr lists `blocked http://127.0.0.1:{port}/probe.jpg` (AC 23)
Proof: `sail artisan test --filter="the renderer aborts every request that is not data"`

**C34** - Real renderer, longest-name fixture (`Luiz Philippe de Orleans e Bragança`) with a synthetic 480 × 600 photographic JPEG: the PNG weighs at most 300 000 bytes at `1200x630`, 500 000 at `1080x1350` and 800 000 at `1080x1920` (AC 24)
Proof: `sail artisan test --filter="cards stay under their weight budget"`

**C35** - The render CLI exits 2 with a reason on stderr for invalid JSON, a `kind` other than `member`/`roll_call`, a `format` other than `og`/`feed`/`story`, and a `photo` that is not `null` or a `data:image/jpeg;base64,` URI; it exits 1 with a reason when the browser cannot start (`PLAYWRIGHT_BROWSERS_PATH` an empty directory); it exits 0 with a PNG on stdout otherwise (door 3)
Proof: `sail artisan test --filter="the render cli exit codes"`

**C36** - `app/package.json` depends on `"playwright-core": "^1.63"`; `app/docker/8.5/Dockerfile` runs `playwright@$PLAYWRIGHT_VERSION install --with-deps --only-shell chromium` with `PLAYWRIGHT_VERSION` equal to the `playwright-core` version in `app/package-lock.json` and to `@playwright/test` in `design/package-lock.json`; `compose.yaml` builds `./docker/8.5`; CI job `app` installs the shell and builds the card bundle before the tests (door 3)
Proof: `sail artisan test --filter="the browser shell is installed where cards render"`

### S4 - card content is fixed, the same for everyone, and limited · ~10 files · ~80 KB · ~20k

**C37** - `MemberCard` (design) renders, in DOM and vertical order: `Mandato Aberto · Câmara dos Deputados · 57ª legislatura`; the `OfficialPhoto` with `Foto: Câmara dos Deputados` (or `Foto: Agência Senado` with `house` `senado`); the name in the only `<h1>`; `PSB · SP`; `Nas votações sobre propostas e emendas`; three figures `3 de 4 Participação em votações nominais do plenário`, `2 de 3 Votos iguais à orientação do governo`, `1 de 2 Votos iguais à maioria do próprio partido`; `{n} votações nominais do plenário com registro, da mais antiga à mais recente`; the compact `.ma-score`; the footer - for each of the 3 formats (AC 25)
Proof: `sail npm --prefix /var/www/design test -- tests/cards.test.ts -t "member card text in order"`
Proof: `sail npm --prefix /var/www/design run test:e2e -- -g "card regions in order"`

**C38** - The app's card HTML (`render.mjs --html`) for 101/57 and 9101/57 holds the C37 sequence with the stored values: Ana `PSB · SP`, `3 de 4`, `2 de 3`, `1 de 2`; Rosa `Mandato Aberto · Senado Federal · 57ª legislatura`, `PT · SP` (AC 25)
Proof: `sail artisan test --filter="member card html shows the mandate in order"`

**C39** - A figure with total 0 writes `Sem base de cálculo no período` and no number (Bruno 102: all three); a member with no plenary vote in the legislature writes `Nenhuma votação nominal do plenário com registro nesta legislatura.` and no `.ma-score` (design and app) (AC 26)
Proof: `sail npm --prefix /var/www/design test -- tests/cards.test.ts -t "member card empty states"`
Proof: `sail artisan test --filter="member card html empty states"`

**C40** - `RollCallCard` renders in order `Mandato Aberto · Câmara dos Deputados`, `<h1>` `PL 1/2023`, `Votação nominal · Decisão sobre a proposta · 01/03/2023`, `Aprovada`, `1 Sim · 1 Não · 1 outros votos` with a `.ma-tally`, the footer; `100-3` writes `Resultado não informado`, `100-2` `Rejeitada`; `100-4` writes `Votação simbólica: não há registro do voto de cada parlamentar nem placar.` and no `.ma-tally`; `100-5` writes `Placar não publicado pela Casa.` and no `.ma-tally`; `7001` writes `Mandato Aberto · Senado Federal`, `40 Sim · 20 Não · 1 outros votos` (design and app) (AC 27)
Proof: `sail npm --prefix /var/www/design test -- tests/cards.test.ts -t "roll-call card text in order"`
Proof: `sail artisan test --filter="roll-call card html shows the result in order"`

**C41** - Every card's footer reads `Fonte: Câmara dos Deputados, dados de 01/03/2027` (Senate: `Fonte: Senado Federal, dados de 05/03/2027`) and `Código {code} · confira em mandato.test/verificar/`, with `{code}` the subject's current code (AC 28)
Proof: `sail artisan test --filter="every card footer names source date code and address"`

**C42** - Over the cards of the 9 fixture members' latest mandates and the 10 fixture roll calls, the text holds no `%`, none of the 19 terms of `config/forbidden-terms.php` nor `importante`, `relevante`, `posição`, `percentil`, `lugar` as whole words (case- and accent-insensitive), no name of another fixture member (on a roll-call card, no member name at all), none of the 7 Senate absence descriptions of app-contract-v3 door 5 nor `Sem voto:`, and no URL or domain other than `mandato.test/verificar/` (AC 29)
Proof: `sail artisan test --filter="cards never carry what a card must not say"`

**C43** - The `<h1>` text equals the stored name character for character for a name with accents and lowercase particles (`Luiz Philippe de Orleans e Bragança`), and its computed `text-transform` is `none` in each format (AC 30)
Proof: `sail npm --prefix /var/www/design run test:e2e -- -g "card name is set as stored"`

**C44** - For each format, two members' cards (different names, figures, votes, one with a photo and one without) have the same sequence of tag and class pairs once the `.ma-score__col` marks and the photo-or-initials node are removed (design); the app's two HTML cards of 101/57 and 103/57 do too (AC 31)
Proof: `sail npm --prefix /var/www/design test -- tests/cards.test.ts -t "every member card shares one tree"`
Proof: `sail artisan test --filter="two members share one card tree"`

**C45** - In Chromium, each format of `MemberCard` with a 48-character name and figures `1.234 de 2.345`, and of `RollCallCard` with a 48-character heading and tallies 400/100/13, measures exactly its format's size and no element extends beyond the card box or overflows its own (AC 32)
Proof: `sail npm --prefix /var/www/design run test:e2e -- -g "every card format fits its box"`

**C46** - In Chromium, each format draws a 480 × 600 photo whole inside a 3:4 mat: the image box is at most 354 × 472 and at most the photo's native size, its rendered aspect equals 4:5 within 1 %, `object-fit` is not `cover`, and `filter`, `mix-blend-mode`, `transform` are `none`, `normal`, `none` (AC 33)
Proof: `sail npm --prefix /var/www/design run test:e2e -- -g "card photo is whole and untouched"`

**C47** - `design/screens/Card.vue` renders `MemberCard` with `format` `og` (`.ma-card.ma-card--og`), and the prototype and screen tests that existed keep passing unchanged (`card composition`, `card fits the longest name`, `official photo is untouched`, `every card shares one template`) (door 4)
Proof: `sail npm --prefix /var/www/design test -- tests/cards.test.ts -t "the prototype card is the member card"`
Proof: `sail npm --prefix /var/www/design run test:e2e -- -g "card composition"`

### S5 - every code opens the data the card showed · ~8 files · ~55 KB · ~14k

**C48** - Canonical JSON sorts object keys recursively, keeps list order, writes `é` and `/` unescaped and no whitespace: `{"b":1,"a":{"d":"é/","c":[2,1]}}` -> `{"a":{"c":[2,1],"d":"é/"},"b":1}`; a float or a boolean anywhere in a payload is refused with an exception (door 5)
Proof: `sail artisan test --filter="canonical json sorts keys and refuses floats"`

**C49** - For a fixed payload with `generatedAt` `2027-03-02T02:30:00Z`, the digest is the SHA-256 hex of its canonical JSON and the code is `20270301-` followed by the first 40 bits of the digest in Crockford base32, both computed in the test by an independent bit-string conversion; a second PHP process computes the same code (AC 34, door 5)
Proof: `sail artisan test --filter="the code is the brasilia date and forty bits of the digest"`

**C50** - The code changes when each of 8 things changes, one at a time: the name, a figure count, a figure total, a vote position, the party, `photoSha256`, `generatedAt`, `template` (AC 35)
Proof: `sail artisan test --filter="any shown value changes the code"`

**C51** - Normalisation maps `20270930-k7q29xpd`, `20270930 K7Q2 9XPD`, `20270930K7Q29XPD`, `2O27O93O-K7Q29XPD` and `20270930-K7Q29XPD` with `1` written as `I` or `L` (`20270930-K7Q2IXPD` -> `20270930-K7Q21XPD`) to their canonical form `NNNNNNNN-XXXXXXXX`; a string that does not normalise to 16 characters returns null (door 5)
Proof: `sail artisan test --filter="codes normalise case separators and look-alikes"`

**C52** - After 101/58's card is served, `GET /verificar/{code}/` answers 200 with `<h1>` `Código {code}`, an `<img>` `src` `/deputados/101/legislatura/58/card/{code}/1200x630.png` with the C60 `alt`, the payload's values as text (name, `PT · SP`, `58ª legislatura`, each figure as `n de m` or `Sem base de cálculo no período`, each vote's date and label), `Dados abertos da Câmara dos Deputados, coletados em 01/03/2027.` and a link to `/deputados/101/legislatura/58/`; for `7001`, `Dados abertos do Senado Federal, coletados em 05/03/2027.` and a link to `/senado/votacoes/7001/` (AC 36)
Proof: `sail artisan test --filter="a stored code opens the card data"`

**C53** - The verification page writes `Os dados atuais são iguais aos do card.` while the subject's current code equals the stored one; after 101's vote on `300-1` changes, `Os dados mudaram desde 01/03/2027. O card mostra os dados daquela data.` with a link `Ver dados atuais` to `/deputados/101/legislatura/58/`; after member 101 is deleted from the contract tables, `Este registro não está nos dados atuais.` (AC 37)
Proof: `sail artisan test --filter="the verification page compares the card with current data"`

**C54** - For a stored code, each of 5 variants - lowercase, spaces (`%20`), no hyphen, `O` for `0`, `I` and `L` for `1` - answers 301 with `Location` `/verificar/{code}/` (AC 38)
Proof: `sail artisan test --filter="a recoverable code variant redirects to its canonical form"`

**C55** - `/verificar/20270301-00000000/` (well formed, not stored) and `/verificar/abc/` answer 404 with `<h1>` `Código não encontrado` and the sentence `O código tem oito números, um hífen e oito letras ou números, como 20270930-K7Q29XPD.` (AC 39)
Proof: `sail artisan test --filter="an unknown code answers 404 with how a code looks"`

**C56** - `GET /verificar/` answers 200 with a `<form method="get">` whose input `name="codigo"` has the `<label>` `Código de verificação`; `GET /verificar/?codigo=20270301 k7q2 9xpd` answers 302 to `/verificar/20270301-K7Q29XPD/` (AC 40)
Proof: `sail artisan test --filter="the verification index takes a code"`

### S6 - links preview the card and readers can download it · ~8 files · ~50 KB · ~13k

**C57** - `/deputados/101/` (latest, 58) holds `og:image` `https://mandato.test/deputados/101/legislatura/58/card/{code}/1200x630.png`, `og:image:width` `1200`, `og:image:height` `630`, `og:image:type` `image/png`, `twitter:card` `summary_large_image` (once) and `og:image:alt` = `twitter:image:alt` = `Card do Mandato Aberto: Ana Souza (PT-SP), Câmara dos Deputados, 58ª legislatura. Nas votações sobre propostas e emendas: participação em 1 de 1; votos iguais à orientação do governo: sem base de cálculo; votos iguais à maioria do partido em 1 de 1. Dados de 01/03/2027. Código {code}.` (AC 41)
Proof: `sail artisan test --filter="member pages preview their card"`

**C58** - `/deputados/101/legislatura/57/` alt reads `... Ana Souza (PSB-SP), Câmara dos Deputados, 57ª legislatura. Nas votações sobre propostas e emendas: participação em 3 de 4; votos iguais à orientação do governo em 2 de 3; votos iguais à maioria do partido em 1 de 2. Dados de 01/03/2027. Código {code}.`; `/senadores/9101/` names `Senado Federal`, `Dados de 05/03/2027` and a `/senadores/9101/legislatura/57/card/` image (AC 41)
Proof: `sail artisan test --filter="member pages preview their card"`

**C59** - `/votacoes/100-1/` alt reads `Card do Mandato Aberto: PL 1/2023, Câmara dos Deputados, 01/03/2023. Aprovada. 1 Sim, 1 Não, 1 outros votos. Dados de 01/03/2027. Código {code}.`; `/votacoes/100-4/` puts `Votação simbólica: não há registro do voto de cada parlamentar nem placar.` and `/votacoes/100-5/` `Placar não publicado pela Casa.` in place of the tallies; `/senado/votacoes/7001/` names `Senado Federal`, `40 Sim, 20 Não, 1 outros votos`, `Dados de 05/03/2027`, image under `/senado/votacoes/7001/card/` (AC 41)
Proof: `sail artisan test --filter="roll-call pages preview their card"`

**C60** - The verification page's `<img>` `alt` equals its code's AC 41 alt (asserted with C52)
Proof: `sail artisan test --filter="a stored code opens the card data"`

**C61** - Each of the 6 member pages and the 10 roll-call pages renders, after its last source note, one `<section>` headed `Imagens para compartilhar` with 3 links `Horizontal, 1200 × 630`, `Feed, 1080 × 1350`, `Stories, 1080 × 1920`, each with a `download` attribute and `href` that format's card URL for the current code, and the link `Código de verificação: {code}` to `/verificar/{code}/` (AC 42)
Proof: `sail artisan test --filter="pages offer the three card images and the code"`

**C62** - Requesting the 16 member and roll-call pages leaves `card_snapshots` at 0 rows (AC 43, door 8)
Proof: `sail artisan test --filter="rendering a page stores no snapshot"`

**C63** - With `inertia.ssr.url` at a closed port, `/deputados/101/`, `/senadores/9101/`, `/votacoes/100-1/` and `/senado/votacoes/7001/` still carry every tag of C57 with the same values (AC 44, door 7)
Proof: `sail artisan test --filter="card tags survive an ssr outage"`

**C64** - `/verificar/{code}/` carries the image tags of C57 for its code and `<meta name="robots" content="noindex">`; `/metodologia/`, `/verificar/` and `/verificar/20270301-00000000/` carry `twitter:card` `summary` and no `og:image` (AC 45)
Proof: `sail artisan test --filter="only card pages carry a card image"`

**C65** - No `Set-Cookie` on 15 responses: `/fotos/` 200, 404, 410; card 200, 302, 404, 503; `/verificar/` 200, 302; `/verificar/{code}/` 200, 301, 404; a member page, a roll-call page, their 404 (AC 46)
Proof: `sail artisan test --filter="new routes set no cookie"`

### S7 - storage stays bounded and history stays whole · ~4 files · ~20 KB · ~5k

**C66** - With codes A (older) and B (latest) of 101/58 and one of 102/57 (C, latest), PNG files aged by `touch`: A's 31 days, B's 31 days, C's 31 days, and a second subject-older code D of 101 aged 10 days: `mandato:cards:prune` deletes only A's file, prints `cards pruned: 1 files, {A's bytes} bytes` and exits 0; `--days=5` then deletes D's; all 4 snapshots remain; a requested-again A answers 200 rendered from its snapshot (AC 47, AC 48)
Proof: `sail artisan test --filter="prune deletes old images of codes that are not the latest"`

**C67** - When a file cannot be deleted (its directory is read-only), prune prints `card prune failed {path}` to stderr and exits 1 (door 9)
Proof: `sail artisan test --filter="prune exits 1 when a file cannot be deleted"`

**C68** - After a photo run, a card served, a prune, and a re-import of a Câmara copy without member 103 (app-contract-v3's sweep), `card_snapshots` and `photo_versions` hold every row they held before and every photo file remains; no source file under `app/app` or `app/database/migrations` of this feature calls `delete` or `update` on `card_snapshots` or `photo_versions` other than `checked_at` (AC 49)
Proof: `sail artisan test --filter="history is never deleted"`

**C69** - Each render logs at `info` `card rendered {code} {format} {ms} ms {bytes} bytes` with `{bytes}` the stored PNG's size (AC 49)
Proof: `sail artisan test --filter="each render is logged with its time and size"`

### Shape checks (doors without a criterion of their own)

**C70** - `config('filesystems.disks.media')` is `driver` `local`, `root` `storage_path('app/media')`, `visibility` `private`; `config('mandato.media_disk')` defaults to `media`; `config('filesystems.links')` does not include the media root; `app/storage/app/media/` is ignored by git (door 1)
Proof: `sail artisan test --filter="photos and cards live on the private media disk"`

**C71** - `photo_versions` and `card_snapshots` have the door 2 columns, the checks `house in ('camara','senado')` (both) and `kind in ('member','roll_call')`, the unique `(house, member_source_id, sha256)`, unique `code`, unique `digest`, `payload` of type `jsonb`, the index `(kind, house, source_id, legislature, id)`, and no foreign key; inserting a duplicate of each unique and a value outside each check fails (door 2)
Proof: `sail artisan test --filter="photo and card tables keep their constraints"`

**C72** - `PublicUrl` builds `/fotos/{sha}.jpg`, `/deputados/101/legislatura/58/card/{code}/1200x630.png`, `/senadores/9101/legislatura/57/card/{code}/1080x1350.png`, `/votacoes/100-1/card/{code}/1080x1920.png`, `/senado/votacoes/7001/card/{code}/1200x630.png`, `/verificar/` and `/verificar/{code}/` (door 6)
Proof: `sail artisan test --filter="public url builds photo card and verification paths"`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| `GET /fotos/{sha256}.jpg` statuses (3) | 200 C15 · 404 C16 · 410 C17 | - |
| `GET /deputados/{id}/legislatura/{n}/card/{code}/{format}.png` statuses (4) | 200 C22 · 302 C26 · 404 C27 · 503 C28, C29, C30 | - |
| `GET /senadores/{id}/legislatura/{n}/card/{code}/{format}.png` statuses (4) | 200 C22 · 302 C26 · 404 C27 · 503 C28 | - |
| `GET /votacoes/{id}/card/{code}/{format}.png` statuses (4) | 200 C22 · 302 C26 · 404 C27 · 503 C28 | - |
| `GET /senado/votacoes/{id}/card/{code}/{format}.png` statuses (4) | 200 C22 · 302 C26 · 404 C27 · 503 C28 | - |
| `GET /verificar/` statuses (2) | 200 C56 · 302 C56 | - |
| `GET /verificar/{code}/` statuses (3) | 200 C52 · 301 C54 · 404 C55 | - |
| changed member and roll-call pages statuses (2) | 200 C57, C59, C61 · 404 C65 (and app-contract-v3 C40, C59 unchanged) | - |
| card formats (3) | `1200x630` C22, C34, C37, C45, C46 · `1080x1350` C22, C34, C37, C45, C46 · `1080x1920` C22, C34, C37, C45, C46 | - |
| card subjects (4) | deputy C22 · senator C22 · Câmara roll call C22 · Senate roll call C22 | - |
| photo body rules of AC 2 (5) | `FF D8 FF` start C6 · at most 2 MiB C6 · JPEG to `getimagesizefromstring` C6 · width >= 100 C6 · height >= 100 C6 | - |
| photo transport rules of AC 4 (5) | no response / timeout C6, C7 · status other than 200 C6 · more than 3 redirects C6 · host outside the 3 C6, C7 · not `https` C6 | - |
| allowed photo hosts (3) | `www.camara.leg.br` C3 · `www.senado.leg.br` C3 · `legis.senado.leg.br` C3 | - |
| photo selection of AC 1 (4) | no photo C1 · stale > 7 days C1 · fresh <= 7 days C1 · `--stale-after` C2 | - |
| photo version outcomes (4) | new C3 · unchanged C5 · failed C6 · shared hash C14 | - |
| `mandato:photos` exit codes (3) | 0 C9 · 1 C10, C11, C12 · 2 C13 | - |
| `mandato:photos` options (3) | `--house` C2, C13 · `--member` C2, C13 · `--stale-after` C1, C2, C13 | - |
| photo credit by house (2) | `camara` C18, C37 · `senado` C18, C37 | - |
| photo states on a page (4) | current C18 · none C19 · suppressed C20 · shared C14 | - |
| card code request cases (5) | current C22, C23 · stored old C25 · unknown well-formed C26 · stored for another subject C26 · malformed C27 | - |
| card 404 causes (10) | C27, table-driven over all 10 | - |
| render outcomes (5) | rendered C22 · exit non-zero C28 · timeout C29 · slots full C30 · same card in flight C31 | - |
| render CLI exit codes (3) | 0 C35 · 1 C35 · 2 C35 | - |
| verification code cases (8) | canonical stored C52 · lowercase C54 · spaces C54 · no hyphen C54 · `O` for `0` C54 · `I`/`L` for `1` C54 · well-formed unknown C55 · malformed C55 | - |
| verification states of AC 37 (3) | equal C53 · changed C53 · subject gone C53 | - |
| code inputs of AC 35 (8) | C50, table-driven over all 8 | - |
| member card regions (9) | eyebrow C37 · photo C37 · name C37, C43 · party and UF C37 · basis line C37 · figures C37 · score label C37 · score C37 · footer C37, C41 | - |
| member card empty states (2) | total 0 C39 · no vote C39 | - |
| roll-call card regions (6) | eyebrow C40 · heading C40 · ballot, kind and date C40 · result C40 · tallies C40 · footer C40, C41 | - |
| roll-call results (3) | `Aprovada` C40 · `Rejeitada` C40 · `Resultado não informado` C40 | - |
| roll-call tally states (3) | present C40, C59 · symbolic C40, C59 · null C40, C59 | - |
| never on a card (7) | `%` C42 · 19 forbidden terms C42 · 5 extra words C42 · another member's name C42 · a member name on a roll-call card C42 · Senate absence labels C42 · a URL other than the AC 28 address C42 | - |
| photo rules on a card (5) | whole in 3:4 mat C46 · at most native size C46 · no filter C46 · no blend or transform C46 · no cropping fit C46 | - |
| share block links (4) | `Horizontal, 1200 × 630` C61 · `Feed, 1080 × 1350` C61 · `Stories, 1080 × 1920` C61 · `Código de verificação` C61 | - |
| pages carrying a card image (6) | deputy C57 · deputy earlier legislature C58 · senator C58 · Câmara roll call C59 · Senate roll call C59 · verification C64 | - |
| pages without a card image (3) | methodology C64 · verification index C64 · verification 404 C64 | - |
| SSR states for card tags (2) | running C57 · unreachable C63 | - |
| cookie-free responses (15) | C65, table-driven over all 15 | - |
| prune decisions (3) | old and not latest deleted C66 · latest kept C66 · younger than `--days` kept C66 | - |
| `mandato:cards:prune` exit codes (2) | 0 C66 · 1 C67 | - |
| entities of Relations (6) | `Member` C1, C18 · `PhotoVersion` C3, C71 · `PhotoFile` C3, C4, C15 · `RollCall` C22, C40 · `CardSnapshot` C23, C71 · `CardImage` C23, C24, C66 | - |
| Landing doors (9) | 1 C70, C3 · 2 C71, C68 · 3 C22, C32, C33, C35, C36 · 4 C37, C47 · 5 C48, C49, C51 · 6 C72, C15, C22 · 7 C57, C63, C64 · 8 C23, C62 · 9 C1-C13, C66, C67 | - |
| startup config: media disk (1 shared assembly) | `config/filesystems.php`, read by the routes, the commands and the tests alike, C70 | - |
| startup config: renderer command, timeout, lock store (1 shared assembly) | `config/mandato.php`, read by the card route and overridden only by tests, C29, C30 | - |
| startup config: browser shell (3 assemblies) | Sail image C36 · CI job `app` C36 · design e2e in the same image C45 | - |

- Claims naming a status code, route or response shape: C15-C17, C22-C31, C52-C65 - each proof crosses the HTTP boundary through Laravel's test client and reads headers, body or server-rendered HTML
- Claims naming an exit code or a printed line: C1-C13, C35, C66, C67 - each proof runs the command (Artisan or the CLI through `Process`) and reads exit code, stdout and stderr apart
- Claims about a browser's layout: C37 (second proof), C43, C45, C46 - Playwright in Chromium
- No other check claims more than the single case its proof exercises

## Test policy

The skeleton's and app-contract-v3's rows still answer both questions for `app/` and `design/`; this feature builds under them unchanged:

| Code | Required proofs | Coverage expectation |
| --- | --- | --- |
| Decides, reached across a boundary | one at the boundary **and** one at its own layer | the contract at the boundary; one asserted case per row of the decision table at its own layer |
| Decides, not reached across a boundary | one at its own layer | one asserted case per row of the decision table |
| Entry point that decides nothing | one at the boundary | accepted input, each rejected input, each error path |
| Instrumentation, pass-throughs | none of its own | covered by its consumer's proof |

Evidence (shapes the plan's doors imply; the build may name files differently):

- photo fetch and validation: 5 body rules + 5 transport rules + allowlist per hop -> decides, reached only through `mandato:photos`; its own layer is the command, every row asserted there (C6, C7)
- photo selection and current-photo rule: 4 selection rows, shared-hash exception -> decides, reached by the command and by pages; command C1, C2, C14, pages C18-C20
- card code: canonical JSON (sort, refusal of floats and booleans), digest, base32, Brasília date, normalisation (5 variants) -> decides, reached across HTTP; own layer C48-C51, boundary C52-C56
- card route: 5 code cases, 10 404 causes, 5 render outcomes -> decides; every row at the boundary (C22-C31), where the fixture reaches each cheaply
- card payload builder: total 0 vs > 0, votes vs none, tally present/symbolic/null, result 3 values -> decides; rows asserted through the card HTML (C38-C41) and the alt text (C57-C59)
- `MemberCard`, `RollCallCard`: format arrangement (3), empty states (2 + 3) -> decides; own layer in `design/` (C37, C39, C40, C44), browser C45, C46
- render CLI: input validation (4 rejections), browser start failure, request blocking -> decides, reached through `Process`; asserted at the CLI boundary (C33, C35)
- prune: 3 decision rows -> decides; command C66
- closest analogue: the importer command (app-contract-v3 C9-C22, every refusal asserted at the Artisan boundary) and `positionCase` (own layer, table-driven)

Cost: 4 proofs at their own layer (C48-C51) and 4 design tests (C37, C39, C40, C44) beyond the boundary proofs. Without them the code's look-alike mapping would be proven only by the one variant a page test happens to use, and the card's empty states only by the fixture member that reaches them.

## Swept

- validation: C6, C13, C16, C27, C35, C48, C51, C55
- failure modes: C6, C10, C11, C28, C29, C67
- idempotency: C4, C5, C24, C49
- authorization: n/a - public, cookie-free, read-only routes (C65); both commands run only from the shell (door 9), never over HTTP
- concurrency: C12, C30, C31
- data lifecycle: C66, C68, C71
- dependency failure: C6 (house down), C29 and C35 (browser), C63 (SSR down)
- state transitions: C14 (photo current -> shared -> current), C25 and C53 (code current -> old)
- observability: C9, C28, C69

## Handoff

Size from `wc -c` on the files each slice reads and writes, divided by four. Read once: the plan (52 KB), the tlc-spec-lean references (44 KB), app-contract-v3's checks and plan sections used (~30 KB), the app code and tests this changes (`MemberController`, `RollCallController`, the two pages, `app.blade.php`, `Pest.php`, `routes`, `PublicUrl`, `Labels`: ~45 KB) and the design package's card, photo, score, styles and e2e (~45 KB) = ~216 KB = ~54k. Written: S1 ~15k, S2 ~10k, S3 ~23k, S4 ~20k, S5 ~14k, S6 ~13k, S7 ~5k = ~100k. Total ~154k, over the 150k budget by ~4k.

- S1-S2 = 54k + 25k = 79k (photos, one surface: the photo cache and the profile); S5's code and S3-S4 enter the card surface at 136k; S6-S7 reach 154k -> proposed cut after S4, where the card exists and the verification and share surfaces start
- Mechanism: handoff, decided by the orchestrator under the maintainer's delegation in this builder's brief ("if the estimate exceeds the budget, build the first batch green and committed and report the rest"); batch 1 (this builder) is S1-S4 plus S5's code (C48-C51, which S3's routes need) and the shape checks C70-C72; batch 2 is the rest of S5 (C52-C56), S6 and S7, built on green
