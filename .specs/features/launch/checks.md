# launch checks

Profile: light
Plan: `.specs/features/launch/plan.md`

## Intent

69 checks in 10 slices · 7 one-way doors, doors 1 (Cloudflare Pages) and 7 (candidacy export) approved by the maintainer on 2026-09-27 · 7 open, of which 4 block go-live (domain, names and e-mail, TSE file, Cloudflare secrets)

All proofs run from the repository root. `T` abbreviates `npm --prefix site test --` (vitest) and `E` abbreviates `uv run --directory etl pytest`. Site page checks read the `dist/` of the one `astro build` that `site/tests/build.test.ts` already runs over the fixture contract (`generatedAt 2026-09-27T12:00:00Z`, so the collection date reads `27/09/2026`), with `MANDATO_CORRECTIONS_DIR` pointing at a new fixture `site/tests/fixtures/corrections/` holding two records: `2026-09-20-participacao-101.md` (`status: corrected`, `pages: [/deputados/101/]`, `resolvedAt: 2026-09-22`, `action: Base de cálculo da participação corrigida (PR #9)`) and `2026-09-25-votacao-100-1.md` (`status: reply-published`, `pages: [/votacoes/100-1/, /deputados/102/]`, with a `## Resposta` section). Three extra fixture builds prove the empty corrections state, a malformed record and the analytics token.

Placeholders: `<CORRECTIONS_EMAIL>`, the maintainers' names and city and the repository URL are read by pages and tests from one module, `site/src/lib/site.ts`; until the maintainer fills them the values read `[a definir]`, which C68 rejects on the deployed site. `<domain>` is the value of plan question 1.

S10 runs against the deployed site, with `MANDATO_DOMAIN=<domain>` in the environment, after the first green `publish.yml` run. The Verifier's round 1 covers C1 to C61 at `HEAD`; a scoped round 2 covers C62 to C69 after the first deploy. Both are recorded in `verification.md`.

Interpretations settled while deriving:

- AC 9's optional fields, when empty, render in the mail body as `<label>: (não informado)`, so the four lines are always present.
- AC 20 links the 2023 file of each bulk kind, the stable URL, and says the following years use the same name; a link per year would need a copy change every January.
- AC 31 renders `pages` as links with the path as the text.
- `corrections/` holds a `.gitkeep` so the directory exists in a fresh clone; the reader takes only `*.md` files as records.

Renegotiated during build (2026-09-27, checks author against the approved plan):

- C10: the table follows AC 10's regular expression `^/[A-Za-z0-9/_-]{1,200}$`, which C11 pins literally: `?p=/` has no character after the slash and leaves the field empty, and the case over the limit is `/` + 201 `a` (202 characters), since `/` + 200 `a` matches. The coverage member `root C10` stays, now proving the empty result.
- C41: the step runs in `working-directory: etl`, so its existence test reads `../etl/inputs/candidacy-2026.json`; the claim now names that path and the proof is unchanged.
- Site C36's test `scan catches a forbidden term in a template` writes its unscanned control file as `notes.txt` instead of `notes.md`, a consequence of AC 6 extending the scan to `.md`; confirmed.

## Checks

### S1 - Legal footer, 404 page and copy scan · 5 files · 14 KB · ~4k

**C1** - Each of the built home, profile 101, roll call 100-1, `404.html`, `/metodologia/`, `/quem-somos/`, `/dados-e-privacidade/`, `/correcoes/` and `/reportar-erro/` contains, inside `<footer`, the sentence `Este site não apoia nem se opõe a candidaturas, partidos ou federações. Todos os dados provêm de fontes oficiais indicadas em cada página. Não recebe recursos de partidos, candidatos ou campanhas.` (AC 1)
Proof: `T tests/build.test.ts -t "footer legal sentence on every page"`

**C2** - The footer of each of those 9 pages contains `Dados: ` followed by `<a href="https://dadosabertos.camara.leg.br/">Câmara dos Deputados</a> e <a href="https://dadosabertos.tse.jus.br/">TSE</a> (dados abertos). Fotos: Câmara dos Deputados.` (AC 2)
Proof: `T tests/build.test.ts -t "footer credits"`

**C3** - The footer of each of those 9 pages contains the six links `<a href="/metodologia/">Metodologia e fontes</a>`, `<a href="/quem-somos/">Quem somos</a>`, `<a href="/dados-e-privacidade/">Dados e privacidade</a>`, `<a href="/correcoes/">Correções</a>`, `<a href="/reportar-erro/">Reportar erro</a>` and `<a href="https://github.com/augusto-dmh/mandato-aberto">Código-fonte</a> ` (AC 3)
Proof: `T tests/build.test.ts -t "footer links"`

**C4** - `dist/404.html` exists, its `<title>` is `Página não encontrada - Mandato Aberto`, its `<h1>` is `Página não encontrada`, it contains `O endereço pode ter sido digitado errado ou a página pode ter deixado de existir.` and `<a href="/">Voltar à busca de deputados</a>`, and no other `dist/**/index.html` contains `Página não encontrada` (AC 4)
Proof: `T tests/build.test.ts -t "404 page"`

**C5** - `findForbidden` over a temporary directory holding `page.md` with `Faltou à sessão` and `ok.ts` with `fielmente` returns exactly one hit naming `page.md` and `faltou`; the scan over `site/src/` (every `.astro`, `.vue`, `.ts` and `.md`, minus `lib/forbidden-terms.ts`) returns 0 hits (AC 6)
Proof: `T tests/language.test.ts -t "scan covers markdown"`
Proof: `T tests/language.test.ts -t "site source uses no forbidden term"`

**C6** - The visible text (markup, `<style>` and `<script>` removed) of the 5 new pages and `404.html`, added to the existing page list, contains no forbidden term and no `%` (AC 6, site AC 27)
Proof: `T tests/build.test.ts -t "built pages use no forbidden term and no percentage"`

### S2 - Reportar erro · 6 files · 12 KB · ~4k

**C7** - Profile 101 contains `<a href="/reportar-erro/?p=/deputados/101/">Reportar erro nesta página</a>` and roll call 100-1 contains `<a href="/reportar-erro/?p=/votacoes/100-1/">Reportar erro nesta página</a>`; profile 103 and roll call 100-6 carry the same link with their own path (AC 7)
Proof: `T tests/build.test.ts -t "report link on profile and roll call"`

**C8** - The built `/reportar-erro/` has one `<form` without `action` and without `method`, with `<label`s `Página com o erro`, `O que está errado`, `Onde está o dado correto (link para a fonte oficial, se tiver)` and `Seu e-mail, se quiser resposta` bound to an `<input name="page">`, a `<textarea name="problem">`, an `<input name="source">` and an `<input name="email">`, and a `<button type="submit">Enviar por e-mail</button>`; neither the page nor any script it references contains `fetch(` or `XMLHttpRequest` (AC 8, AC 9, door 4)
Proof: `T tests/build.test.ts -t "report form fields and no submission target"`

**C9** - `reportMailto({page: "/deputados/101/", problem: "Voto errado", source: "https://x.gov.br/1", email: "a@b.c"})` returns `mailto:<CORRECTIONS_EMAIL>?subject=` + `encodeURIComponent("Erro em /deputados/101/")` + `&body=` + `encodeURIComponent` of the four lines `Página com o erro: /deputados/101/`, `O que está errado: Voto errado`, `Onde está o dado correto: https://x.gov.br/1`, `Seu e-mail: a@b.c` joined by `\n`; with `source` and `email` empty the last two lines read `Onde está o dado correto: (não informado)` and `Seu e-mail: (não informado)` (AC 9)
Proof: `T tests/report.test.ts -t "composes the mailto"`

**C10** - `pagePathFromQuery(search)` is table-driven over 7 cases: `?p=/deputados/101/` -> `/deputados/101/`; `?p=/votacoes/2645346-18/` -> `/votacoes/2645346-18/`; `?p=/` -> ``; `` (absent) -> ``; `?p=https://x/` -> ``; `?p=/a%20b/` -> ``; `?p=/` + 201 `a` (202 characters) -> `` (AC 10, AC 11)
Proof: `T tests/report.test.ts -t "prefill accepts only site paths"`

**C11** - The built `/reportar-erro/` references exactly one script asset under `dist/_astro/` and that asset's text contains the regular expression literal `^/[A-Za-z0-9/_-]{1,200}$` and the string `mailto:` (AC 9, AC 10)
Proof: `T tests/build.test.ts -t "report page script prefills from the query"`

**C12** - The built `/reportar-erro/` contains a `<noscript>` block with `<a href="mailto:<CORRECTIONS_EMAIL>"><CORRECTIONS_EMAIL></a>` and `Sem JavaScript, escreva para o endereço acima com os quatro itens do formulário.` (AC 12)
Proof: `T tests/build.test.ts -t "report page without javascript"`

**C13** - The built `/reportar-erro/` contains `Toda mensagem recebe triagem em até 48 horas. A correção, ou a resposta do parlamentar, é publicada em até 7 dias na página <a href="/correcoes/">Correções</a>.` (AC 13)
Proof: `T tests/build.test.ts -t "report page deadlines"`

### S3 - Metodologia e fontes · 3 files · 12 KB · ~4k

**C14** - The built `/metodologia/` contains `<h2 id="participacao">`, `<h2 id="alinhamento-governo">`, `<h2 id="alinhamento-partido">`, `<h2 id="proposicoes">`, `<h2 id="candidatura-2026">` and `<h2 id="fontes">` (or `<section id=...>` each opened by an `<h2>`) in that document order, and no other `<h2>` before `fontes` (AC 14)
Proof: `T tests/build.test.ts -t "methodology sections in order"`

**C15** - The `participacao` section contains the four sentences `Conta: votações nominais do plenário em que o deputado tem registro com qualquer valor: Sim, Não, Abstenção, Obstrução, Art. 17 ou registro em votação secreta.`, `Base: votações nominais do plenário realizadas enquanto o deputado estava em exercício, segundo o histórico de situações publicado pela Câmara. Só os períodos com situação Exercício entram; licença e qualquer outra situação ficam de fora.`, `Votações em comissões não entram neste número.` and `Os dados abertos não informam por que um deputado não tem registro em uma votação. Por isso o site não atribui motivo a nenhum registro que não existe.` (AC 15)
Proof: `T tests/build.test.ts -t "methodology participation"`

**C16** - The `alinhamento-governo` section contains `Conta: votos Sim, Não, Abstenção ou Obstrução iguais à orientação da bancada GOVERNO na mesma votação.`, `Base: votos Sim, Não, Abstenção ou Obstrução em votações em que a orientação GOVERNO foi um desses quatro valores.` and `Orientação Liberado, votação sem orientação registrada, registro Art. 17 e votação secreta ficam fora da conta e da base.` (AC 16)
Proof: `T tests/build.test.ts -t "methodology government alignment"`

**C17** - The `alinhamento-partido` section contains `O partido é o registrado no voto, não o atual.`, `A maioria é calculada entre os outros deputados do mesmo partido na mesma votação, sobre os mesmos quatro valores, sem o voto do próprio deputado.` and `Empate, ou nenhum outro deputado do partido na votação, deixa a votação fora da conta e da base.` (AC 17)
Proof: `T tests/build.test.ts -t "methodology party alignment"`

**C18** - The `proposicoes` section contains `Conta PL, PLP, PEC, PDL e PRC apresentados a partir de 01/02/2023 em que o deputado consta como proponente.`, `Primeiro signatário: o deputado é o primeiro na ordem de assinatura.` and `REQ, RIC e INC são contados à parte, como requerimentos.` (AC 18)
Proof: `T tests/build.test.ts -t "methodology propositions"`

**C19** - The `candidatura-2026` section contains `<a href="https://dadosabertos.tse.jus.br/dataset/candidatos-2026">`, `O cruzamento usa nome civil, data de nascimento e UF. O CPF não é lido.`, `Um deputado que corresponde a mais de uma candidatura não recebe selo.` and `A situação exibida é a que consta no arquivo do TSE na data da última atualização manual, registrada no histórico do repositório.` (AC 19)
Proof: `T tests/build.test.ts -t "methodology candidacy"`

**C20** - The `fontes` section contains links to `https://dadosabertos.camara.leg.br/arquivos/<kind>/csv/<kind>-2023.csv` for each of the six kinds `votacoes`, `votacoesVotos`, `votacoesOrientacoes`, `votacoesProposicoes`, `proposicoes`, `proposicoesAutores`, to `https://dadosabertos.camara.leg.br/arquivos/deputados/csv/deputados.csv`, to `https://dadosabertos.camara.leg.br/api/v2/deputados` with the text `/deputados/{id}/historico` beside it, to `https://dadosabertos.tse.jus.br/dataset/candidatos-2026`, and the sentences `Os arquivos dos anos seguintes têm o mesmo nome, com o ano trocado.`, `A 57ª legislatura começou em 01/02/2023.` and `Os dados são reconstruídos todos os dias; cada página mostra a data da coleta.` (AC 20)
Proof: `T tests/build.test.ts -t "methodology sources"`

**C21** - The built `/metodologia/` contains `Em uma votação secreta, a Câmara registra quem votou, não o voto de cada deputado. Os totais exibidos são os oficiais da Câmara.` (AC 21)
Proof: `T tests/build.test.ts -t "methodology secret ballots"`

**C22** - The built `/metodologia/` contains `O site lista todo deputado com pelo menos um registro de voto na 57ª legislatura, inclusive suplentes e deputados fora de exercício.` and `Em exercício significa que o deputado consta na lista atual de deputados da Câmara.` (AC 22)
Proof: `T tests/build.test.ts -t "methodology deputy set"`

**C23** - The built `/metodologia/` contains `As fotos são as oficiais da Câmara dos Deputados, exibidas sem recorte ou filtro, com o crédito Foto: Câmara dos Deputados.` (AC 23)
Proof: `T tests/build.test.ts -t "methodology photos"`

**C24** - Every `href="/metodologia/#<anchor>"` in profile 101 (`participacao`, `alinhamento-governo`, `alinhamento-partido`, `proposicoes`) names an `id` present in the built `/metodologia/` (AC 14, site AC 13)
Proof: `T tests/build.test.ts -t "profile methodology links resolve"`

### S4 - Quem somos and Dados e privacidade · 3 files · 10 KB · ~3k

**C25** - The built `/quem-somos/` contains, for each entry of `MAINTAINERS` in `site/src/lib/site.ts`, its `name` and its `city`, contains `<a href="mailto:<CORRECTIONS_EMAIL>">`, and the sentences `O Mandato Aberto é mantido por pessoas físicas, sem vínculo com partidos, candidatos, federações ou campanhas.` and `Não recebe dinheiro nem qualquer vantagem de partidos, candidatos, campanhas ou empresas, e não paga impulsionamento de conteúdo.` (AC 24)
Proof: `T tests/build.test.ts -t "quem somos"`

**C26** - The built `/quem-somos/` contains `<a href="https://github.com/augusto-dmh/mandato-aberto">` and `O site é reconstruído todos os dias a partir das fontes listadas em <a href="/metodologia/#fontes">Metodologia e fontes</a>.` (AC 25)
Proof: `T tests/build.test.ts -t "quem somos code and rebuild"`

**C27** - The built `/dados-e-privacidade/` contains a list with the items `nome parlamentar`, `partido`, `UF`, `foto oficial`, `períodos em exercício`, `votos em votações nominais`, `proposições de autoria` and `para quem é candidato em 2026: cargo, partido, número e situação no TSE`, and the sentence `O nome civil e a data de nascimento publicados pela Câmara são lidos só para cruzar com o registro do TSE e nunca são exibidos.` (AC 26)
Proof: `T tests/build.test.ts -t "privacy fields"`

**C28** - The built `/dados-e-privacidade/` contains `Finalidade: dar acesso público aos atos do mandato de cada deputado federal.`, `Base legal: art. 7º, IX e §3º da Lei 13.709/2018 (LGPD), combinado com o art. 8º da Lei 12.527/2011 (LAI).`, `Controladores: as pessoas físicas identificadas em <a href="/quem-somos/">Quem somos</a>.` and `Para exercer os direitos do art. 18 da LGPD, escreva para <a href="mailto:<CORRECTIONS_EMAIL>"><CORRECTIONS_EMAIL></a>.` (AC 27)
Proof: `T tests/build.test.ts -t "privacy purpose basis and controllers"`

**C29** - The built `/dados-e-privacidade/` contains `Nenhum CPF, telefone, endereço, e-mail, cor, raça, religião ou qualquer outro campo das fontes é tratado.` and `O formulário de erro não envia nada ao site: a mensagem só sai do seu programa de e-mail, quando você a envia.` (AC 28)
Proof: `T tests/build.test.ts -t "privacy no other field and form"`

**C30** - The built `/dados-e-privacidade/` contains `O site não grava cookie nem guarda nada no seu navegador.`, `A contagem de visitas vem do Cloudflare Web Analytics, sem cookie e sem identificador individual.` and `A hospedagem (Cloudflare) processa as requisições sob a <a href="https://www.cloudflare.com/privacypolicy/">política de privacidade dela</a>.` (AC 29)
Proof: `T tests/build.test.ts -t "privacy cookies analytics and host"`

**C31** - The built `/dados-e-privacidade/` contains `<a href="https://github.com/augusto-dmh/mandato-aberto/blob/main/research/03-teste-de-balanceamento-lgpd.md">teste de balanceamento</a>` (AC 30)
Proof: `T tests/build.test.ts -t "privacy balancing test link"`

### S5 - Correções · 5 files · 12 KB · ~4k

**C32** - `readCorrections(fixtureDir)` returns two records ordered `2026-09-25-votacao-100-1`, `2026-09-20-participacao-101`, the first with `receivedAt "2026-09-25"`, `pages ["/votacoes/100-1/", "/deputados/102/"]`, `status "reply-published"`, `resolvedAt null`, `action null`, a non-empty `body` and a non-null `reply`; the second with `resolvedAt "2026-09-22"`, `action "Base de cálculo da participação corrigida (PR #9)"` and `reply null` (AC 31, AC 32, door 3)
Proof: `T tests/corrections.test.ts -t "reads records newest first"`

**C33** - The built `/correcoes/` lists the two records in that order; the first shows `25/09/2026`, `<a href="/votacoes/100-1/">/votacoes/100-1/</a>`, `<a href="/deputados/102/">/deputados/102/</a>`, `Resposta publicada` and its body; the second shows `20/09/2026`, `Corrigido`, `Resolvido em 22/09/2026` and `Base de cálculo da participação corrigida (PR #9)` (AC 31)
Proof: `T tests/build.test.ts -t "corrections page lists records"`

**C34** - The first entry of the built `/correcoes/` contains `<h3>Resposta do parlamentar</h3>` followed by the fixture reply text verbatim, and the second entry contains no `Resposta do parlamentar` (AC 32)
Proof: `T tests/build.test.ts -t "corrections page renders a reply"`

**C35** - `statusLabel` is table-driven over the 4 values: `triage` -> `Em análise`, `corrected` -> `Corrigido`, `reply-published` -> `Resposta publicada`, `no-change` -> `Sem alteração`; any other value throws (AC 31, door 3)
Proof: `T tests/corrections.test.ts -t "status labels"`

**C36** - `astro build` with `MANDATO_CORRECTIONS_DIR` pointing at an empty directory writes a `/correcoes/` containing `Nenhuma correção registrada até 27/09/2026.` and no `<article` (AC 33)
Proof: `T tests/build.test.ts -t "corrections page empty state"`

**C37** - `readCorrections` throws an error whose message contains the file name for each of 5 records in a temporary directory: `2026-09-01-a.md` without `receivedAt`; `2026-09-02-b.md` without `pages`; `2026-09-03-c.md` without `status`; `2026-09-04-d.md` with `status: fixed`; `2026-09-05-e.md` with `receivedAt: 2026-09-06`; and `astro build` over a directory holding only `2026-09-04-d.md` exits non-zero with `2026-09-04-d.md` on its output (AC 34)
Proof: `T tests/corrections.test.ts -t "rejects a malformed record"`
Proof: `T tests/build.test.ts -t "build fails on a malformed correction"`

**C38** - The built `/correcoes/` contains `Qualquer pessoa pode reportar um erro pelo <a href="/reportar-erro/">formulário</a> ou pelo e-mail <a href="mailto:<CORRECTIONS_EMAIL>"><CORRECTIONS_EMAIL></a>.`, `Toda mensagem recebe triagem em até 48 horas.` and `Um erro confirmado é corrigido, e a resposta de um parlamentar é publicada nesta página com o mesmo destaque do dado contestado, em até 7 dias.` (AC 35)
Proof: `T tests/build.test.ts -t "corrections page policy"`

**C39** - With `MANDATO_CORRECTIONS_DIR` unset `correctionsDir()` resolves to `<repo>/corrections`; `corrections/.gitkeep` exists in the repository and `readCorrections` over the repository's `corrections/` ignores it (AC 31, door 3)
Proof: `T tests/corrections.test.ts -t "default directory"`

### S6 - Deploy workflow · 4 files · 6 KB · ~3k

**C40** - `.github/workflows/publish.yml` triggers on `schedule` with `cron: "0 9 * * *"`, on `push` to `branches: [main]` and on `workflow_dispatch`, and its steps run in this order: checkout, `astral-sh/setup-uv`, `actions/setup-node` with `node-version: 24`, `uv sync --locked`, the ETL build, the candidacy guard, the `SITE_URL` guard, `actions/cache/restore`, `npm ci`, `npm run build`, `actions/cache/save`, `cloudflare/wrangler-action@v4` with `command: pages deploy site/dist --project-name=mandato-aberto` (AC 36, door 1, door 6)
Proof: `grep -A3 '^on:' .github/workflows/publish.yml | grep -c -e 'schedule' -e 'push' -e 'workflow_dispatch' | grep -qx 3`
Proof: `grep -qF 'cron: "0 9 * * *"' .github/workflows/publish.yml && grep -qF 'branches: [main]' .github/workflows/publish.yml`
Proof: `grep -n -e 'actions/checkout' -e 'astral-sh/setup-uv' -e 'actions/setup-node' -e 'uv sync --locked' -e 'mandato-etl build' -e 'check-candidacy.sh' -e 'SITE_URL is not set' -e 'actions/cache/restore' -e 'npm ci' -e 'npm run build' -e 'actions/cache/save' -e 'cloudflare/wrangler-action@v4' -e 'pages deploy site/dist --project-name=mandato-aberto' .github/workflows/publish.yml | cut -d: -f1 | sort -c`

**C41** - The ETL step reads `if [ -f ../etl/inputs/candidacy-2026.json ]; then uv run mandato-etl build --candidacy-json ../etl/inputs/candidacy-2026.json; else uv run mandato-etl build; fi` from `working-directory: etl`, and `scripts/check-candidacy.sh <meta.json> <candidacy.json>` exits `1` printing `candidacy file present but 0 deputies matched` when the candidacy file exists and `meta.candidacy.matched` is `0`, exits `0` when it is `2`, and exits `0` when the candidacy file does not exist (AC 37, AC 38)
Proof: `grep -qF 'uv run mandato-etl build --candidacy-json ../etl/inputs/candidacy-2026.json' .github/workflows/publish.yml`
Proof: `E tests/test_candidacy_guard.py -k "guard"`

**C42** - `publish.yml` has a step before `npm run build` whose script is `if [ -z "$SITE_URL" ]; then echo "SITE_URL is not set" >&2; exit 1; fi` with `SITE_URL: ${{ vars.SITE_URL }}` in the job `env`, and `npm run build` runs with that `SITE_URL` and `CF_ANALYTICS_TOKEN: ${{ vars.CF_ANALYTICS_TOKEN }}` (AC 36, AC 39)
Proof: `grep -qF 'if [ -z "$SITE_URL" ]; then echo "SITE_URL is not set" >&2; exit 1; fi' .github/workflows/publish.yml && grep -qF 'SITE_URL: ${{ vars.SITE_URL }}' .github/workflows/publish.yml && grep -qF 'CF_ANALYTICS_TOKEN: ${{ vars.CF_ANALYTICS_TOKEN }}' .github/workflows/publish.yml`

**C43** - `publish.yml` contains no `continue-on-error`, no `if: always()` and no `if: failure()`, and the `wrangler-action` step is the last step of the job (AC 40)
Proof: `! grep -qE 'continue-on-error|always\(\)|failure\(\)' .github/workflows/publish.yml && tail -n 8 .github/workflows/publish.yml | grep -q 'cloudflare/wrangler-action@v4'`

**C44** - `publish.yml` declares `concurrency:` with `group: publish` and `cancel-in-progress: false` (AC 41)
Proof: `grep -A2 '^concurrency:' .github/workflows/publish.yml | grep -c -e 'group: publish' -e 'cancel-in-progress: false' | grep -qx 2`

**C45** - The cache steps use `path: site/.cache/photos`, `key: photos-${{ github.run_id }}` and `restore-keys: photos-` on the restore, and the same `path` and `key` on the save (AC 42, door 6)
Proof: `grep -c 'path: site/.cache/photos' .github/workflows/publish.yml | grep -qx 2 && grep -c 'key: photos-${{ github.run_id }}' .github/workflows/publish.yml | grep -qx 2 && grep -qF 'restore-keys: photos-' .github/workflows/publish.yml`

**C46** - `dist/_redirects` reads exactly `https://www.<domain>/* https://<domain>/:splat 301` and `dist/_headers` reads `/*` followed by `  X-Content-Type-Options: nosniff`, `  Referrer-Policy: strict-origin-when-cross-origin`, `  X-Frame-Options: SAMEORIGIN` (AC 57, door 1)
Proof: `T tests/build.test.ts -t "host config files"`

**C47** - `.github/workflows/ci.yml` contains no `secrets.` reference and `publish.yml` has no `pull_request` trigger (Swept authorization)
Proof: `! grep -q 'secrets\.' .github/workflows/ci.yml && ! grep -q 'pull_request' .github/workflows/publish.yml`

### S7 - Analytics without cookies · 2 files · 3 KB · ~1k

**C48** - `astro build` with `CF_ANALYTICS_TOKEN=test-token` writes pages (home, profile 101, `404.html`, `/metodologia/`) each containing exactly one `<script defer src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon='{"token": "test-token"}'></script>` and no other `<script src="http` (AC 45, door 5)
Proof: `T tests/build.test.ts -t "analytics beacon with token"`

**C49** - The main fixture build (token unset) contains no `<script src="http` and no `cloudflareinsights` in any built page (AC 46)
Proof: `T tests/build.test.ts -t "no external script without token"`

**C50** - No file under `dist/_astro/*.js` and no inline `<script>` of any built page contains `document.cookie`, `localStorage` or `sessionStorage` (AC 47)
Proof: `T tests/build.test.ts -t "no storage access in site scripts"`

### S8 - Candidacy input for the daily build · 4 files · 12 KB · ~4k

**C51** - `build --tse-csv <fixture csv> --export-candidacy <tmp>/candidacy-2026.json` exits `0` and writes the JSON with `source` = `{"bytes": <size>, "file": "consulta_cand_2026_BRASIL.csv", "modifiedAt": <mtime as ISO 8601 UTC>, "sha256": <hash of the CSV>}`, `matched` = `{"101": {"ballotNumber": "1313", "office": "DEPUTADO FEDERAL", "party": "PT", "situation": "APTO"}, "102": {...}}` (the two matches the TSE fixture already yields), `ambiguous` = `[103]`, keys sorted, trailing newline (AC 49, door 7)
Proof: `E tests/test_candidacy_export.py -k "test_export_writes_source_matched_and_ambiguous"`

**C52** - The exported JSON's top-level keys are exactly `ambiguous`, `matched`, `source`; each `matched` entry's keys are exactly `ballotNumber`, `office`, `party`, `situation`; `source`'s keys are exactly `bytes`, `file`, `modifiedAt`, `sha256`; the file's text contains neither `cpf` (any case) nor the fixture birth dates (AC 49, door 7)
Proof: `E tests/test_candidacy_export.py -k "test_export_has_only_the_contract_fields"`

**C53** - A build with `--candidacy-json` over the file exported by C51 writes a `data/out/` whose files are byte-identical to the `--tse-csv` build except `meta.json`, which differs only in `candidacy.file` (`candidacy-2026.json` instead of `consulta_cand_2026_BRASIL.csv`); the command line carries no `--tse-csv` and no CSV under `etl/inputs/` is read (AC 50, door 7)
Proof: `E tests/test_candidacy_export.py -k "test_candidacy_json_reproduces_the_tse_build"`

**C54** - With a candidacy JSON whose `matched` holds ids `101` and `999` and whose `ambiguous` is `[103, 998]`, the build writes `candidacy2026` for 101, `null` for 102 and 103, `meta.candidacy.matched` = `1` and `meta.candidacy.ambiguous` = `[103, 998]` (AC 50)
Proof: `E tests/test_candidacy_export.py -k "test_matched_counts_ids_in_the_deputy_set"`

**C55** - `build --tse-csv x.csv --candidacy-json y.json` and `build --export-candidacy z.json` (without `--tse-csv`) each exit `1` with `usage:` on stderr and issue no HTTP request (AC 51)
Proof: `E tests/test_candidacy_export.py -k "test_conflicting_candidacy_flags_exit_1"`

**C56** - `build --candidacy-json <file>` exits `1` with the file name on stderr and leaves the previous `--out` unchanged for each of 4 files: one that does not exist; one holding `{`; one whose `matched["101"]` lacks `situation`; one holding `{"source": {}, "matched": {"101": {"office": "X", "party": "Y", "ballotNumber": "1", "situation": "APTO", "extra": {"CPF": "1"}}}, "ambiguous": []}` (AC 52)
Proof: `E tests/test_candidacy_export.py -k "test_invalid_candidacy_json_exits_1"`

**C57** - `git check-ignore -q etl/inputs/tse/consulta_cand_2026_BRASIL.csv` exits `0` and `git check-ignore -q etl/inputs/candidacy-2026.json` exits `1` (AC 53)
Proof: `git check-ignore -q etl/inputs/tse/consulta_cand_2026_BRASIL.csv && ! git check-ignore -q etl/inputs/candidacy-2026.json`

### S9 - Around the launch · 3 files · 10 KB · ~3k

**C58** - `research/03-teste-de-balanceamento-lgpd.md` exists, contains `**Data:**`, `Controladores` and the four headings `## Finalidade`, `## Necessidade`, `## Balanceamento`, `## Salvaguardas` in that order (AC 54)
Proof: `grep -c -e '^## Finalidade' -e '^## Necessidade' -e '^## Balanceamento' -e '^## Salvaguardas' research/03-teste-de-balanceamento-lgpd.md | grep -qx 4 && grep -qF '**Data:**' research/03-teste-de-balanceamento-lgpd.md && grep -q 'Controladores' research/03-teste-de-balanceamento-lgpd.md`

**C59** - `README.md` contains no term of `FORBIDDEN_TERMS`, whole word and case-insensitive (AC 55)
Proof: `T tests/language.test.ts -t "readme uses no forbidden term"`

**C60** - `README.md` contains `Participação em votações nominais do plenário`, `Votos iguais à orientação do governo`, `Votos iguais à maioria do próprio partido`, `Proposições de autoria`, `Senado` and `Presidência` each within a sentence naming them as future increments, and does not contain `senadores e presidência` (AC 55)
Proof: `grep -c -e 'Participação em votações nominais do plenário' -e 'Votos iguais à orientação do governo' -e 'Votos iguais à maioria do próprio partido' -e 'Proposições de autoria' README.md | grep -qx 4 && grep -qiE 'Senado.*Presidência.*(depois|futur|incremento)|(depois|futur|incremento).*Senado.*Presidência' README.md && ! grep -qi 'senadores e presidência' README.md`

**C61** - `README.md` contains `publish.yml`, `--export-candidacy`, `--candidacy-json`, `CLOUDFLARE_API_TOKEN`, `CLOUDFLARE_ACCOUNT_ID`, `SITE_URL`, `CF_ANALYTICS_TOKEN`, `corrections/` and `MANDATO_CORRECTIONS_DIR` (AC 56)
Proof: `grep -c -e 'publish.yml' -e '\-\-export-candidacy' -e '\-\-candidacy-json' -e 'CLOUDFLARE_API_TOKEN' -e 'CLOUDFLARE_ACCOUNT_ID' -e 'SITE_URL' -e 'CF_ANALYTICS_TOKEN' -e 'corrections/' -e 'MANDATO_CORRECTIONS_DIR' README.md | awk '$1 >= 9 {exit 0} {exit 1}'`

### S10 - Go-live, against `https://$MANDATO_DOMAIN` after the first deploy · 0 files · ~1k

**C62** - The latest `publish.yml` run concluded `success` (AC 36)
Proof: `gh run list --workflow publish.yml --limit 1 --json conclusion -q '.[0].conclusion' | grep -qx success`

**C63** - `https://$MANDATO_DOMAIN/nada/` answers `404` with `Página não encontrada` in the body (AC 5)
Proof: `curl -s -o /dev/null -w '%{http_code}' "https://$MANDATO_DOMAIN/nada/" | grep -qx 404 && curl -s "https://$MANDATO_DOMAIN/nada/" | grep -q 'Página não encontrada'`

**C64** - `http://$MANDATO_DOMAIN/deputados/` and `https://www.$MANDATO_DOMAIN/deputados/` each answer `301` with `Location: https://$MANDATO_DOMAIN/deputados/` (AC 43)
Proof: `for u in "http://$MANDATO_DOMAIN/deputados/" "https://www.$MANDATO_DOMAIN/deputados/"; do curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' "$u" | grep -qx "301 https://$MANDATO_DOMAIN/deputados/" || exit 1; done`

**C65** - `whois $MANDATO_DOMAIN` prints none of the maintainers' surnames, `<CORRECTIONS_EMAIL>`, nor a Brazilian telephone pattern (`+55` or `(1[1-9]|[2-9][0-9]) 9`) (AC 44)
Proof: `out=$(whois "$MANDATO_DOMAIN"); for s in $MAINTAINER_SURNAMES "$CORRECTIONS_EMAIL"; do echo "$out" | grep -qi -- "$s" && exit 1; done; ! echo "$out" | grep -qE '\+55|\((1[1-9]|[2-9][0-9])\) 9'`

**C66** - `https://$MANDATO_DOMAIN/` answers with `x-content-type-options: nosniff`, `referrer-policy: strict-origin-when-cross-origin` and `x-frame-options: SAMEORIGIN` (AC 58)
Proof: `curl -sI "https://$MANDATO_DOMAIN/" | tr -d '\r' | grep -ic -e '^x-content-type-options: nosniff' -e '^referrer-policy: strict-origin-when-cross-origin' -e '^x-frame-options: SAMEORIGIN' | grep -qx 3`

**C67** - `https://$MANDATO_DOMAIN/` and `https://$MANDATO_DOMAIN/deputados/` answer with no `Set-Cookie` header (AC 48)
Proof: `! curl -sI "https://$MANDATO_DOMAIN/" | grep -qi '^set-cookie' && ! curl -sI "https://$MANDATO_DOMAIN/deputados/" | grep -qi '^set-cookie'`

**C68** - The deployed `/quem-somos/`, `/dados-e-privacidade/`, `/correcoes/` and `/reportar-erro/` contain no `[a definir]` (AC 24, AC 27, assumption "Site identity values")
Proof: `for p in quem-somos dados-e-privacidade correcoes reportar-erro; do curl -s "https://$MANDATO_DOMAIN/$p/" | grep -q 'a definir' && exit 1; done; true`

**C69** - The deployed home's `og:image` and a deployed profile's `og:image` start with `https://$MANDATO_DOMAIN/` and answer `200` (AC 36, site AC 25)
Proof: `for p in / /deputados/$(curl -s "https://$MANDATO_DOMAIN/" | grep -o 'href="/deputados/[0-9]*/"' | head -1 | grep -o '[0-9]*')/; do img=$(curl -s "https://$MANDATO_DOMAIN$p" | grep -o 'property="og:image" content="[^"]*"' | grep -o 'https://[^"]*'); echo "$img" | grep -q "^https://$MANDATO_DOMAIN/" || exit 1; curl -s -o /dev/null -w '%{http_code}' "$img" | grep -qx 200 || exit 1; done`

## Coverage

| Set (size) | Member -> proof | Unproven |
| --- | --- | --- |
| `GET /metodologia/` statuses (1) | 200 C14 | - |
| `GET /quem-somos/` statuses (1) | 200 C25 | - |
| `GET /dados-e-privacidade/` statuses (1) | 200 C27 | - |
| `GET /correcoes/` statuses (1) | 200 C33 | - |
| `GET /reportar-erro/` statuses (1) | 200 C8 | - |
| `GET /<no page>` statuses (1) | 404 C4 (file) · 404 C63 (host) | - |
| `GET http://<domain>/*` and `https://www.<domain>/*` statuses (1) | 301 C64 (both hosts) | - |
| Landing doors (7) | static host C40 · static host C46 · static host C62 · page paths C14 · page paths C25 · page paths C27 · page paths C33 · page paths C8 · page paths C4 · correction record C32 · correction record C35 · correction record C37 · error-report channel C8 · error-report channel C9 · error-report channel C12 · analytics C48 · analytics C49 · daily publication C40 · daily publication C44 · daily publication C45 · candidacy input C51 · candidacy input C53 · candidacy input C57 | - |
| pages carrying the footer (9 kinds) | home C1 · profile C1 · roll call C1 · 404 C1 · metodologia C1 · quem-somos C1 · dados-e-privacidade C1 · correcoes C1 · reportar-erro C1 | - |
| footer links (6) | metodologia C3 · quem-somos C3 · dados-e-privacidade C3 · correcoes C3 · reportar-erro C3 · código-fonte C3 | - |
| pages with the report link (2 kinds) | profile C7 · roll call C7 | - |
| report form fields (4) | page C8 · problem C8 · source C8 · email C8 | - |
| mail body lines (4) | page C9 · problem C9 · source C9 · email C9 | - |
| `p` query cases (7) | profile path C10 · roll-call path C10 · root C10 · absent C10 · absolute URL C10 · disallowed character C10 · over 200 C10 | - |
| methodology sections (6) | participacao C14 · participacao C15 · alinhamento-governo C14 · alinhamento-governo C16 · alinhamento-partido C14 · alinhamento-partido C17 · proposicoes C14 · proposicoes C18 · candidatura-2026 C14 · candidatura-2026 C19 · fontes C14 · fontes C20 | - |
| profile methodology anchors (4) | participacao C24 · alinhamento-governo C24 · alinhamento-partido C24 · proposicoes C24 | - |
| source links in `#fontes` (9) | votacoes C20 · votacoesVotos C20 · votacoesOrientacoes C20 · votacoesProposicoes C20 · proposicoes C20 · proposicoesAutores C20 · deputados.csv C20 · API C20 · TSE C20 | - |
| privacy statements (5 groups) | fields C27 · purpose, basis, controllers, rights C28 · other fields and form C29 · cookies, analytics, host C30 · balancing test C31 | - |
| correction statuses (4) | triage C35 · corrected C35 · corrected C33 · reply-published C35 · reply-published C33 · no-change C35 | - |
| correction record validation (5) | missing receivedAt C37 · missing pages C37 · missing status C37 · unknown status C37 · name mismatch C37 | - |
| corrections page states (3) | list C33 · reply C34 · empty C36 | - |
| workflow triggers (3) | schedule C40 · push main C40 · dispatch C40 | - |
| workflow guards (2) | candidacy C41 · SITE_URL C42 | - |
| candidacy guard outcomes (3) | file present and 0 matched C41 · file present and matched C41 · file absent C41 | - |
| cache steps (2) | restore C45 · save C45 | - |
| response headers (3) | nosniff C46 · referrer-policy C46 · frame-options C46 · nosniff C66 · referrer-policy C66 · frame-options C66 | - |
| external scripts (2 states) | token set C48 · token unset C49 | - |
| browser storage (4) | document.cookie C50 · localStorage C50 · sessionStorage C50 · Set-Cookie header C67 | - |
| candidacy flag combinations (4) | `--tse-csv` alone C51 · `--candidacy-json` alone C53 · both C55 · `--export-candidacy` without `--tse-csv` C55 | - |
| export keys (3 top-level) | source C52 · matched C52 · ambiguous C52 · entry fields C52 | - |
| invalid candidacy JSON (4) | missing file C56 · not JSON C56 · entry lacking a field C56 · `cpf` key nested C56 | - |
| ignore rules (2) | `etl/inputs/tse/` ignored C57 · `etl/inputs/candidacy-2026.json` tracked C57 | - |
| README obligations (3) | labels and increments C60 · forbidden terms C59 · operations C61 | - |
| startup config: corrections directory (2 assemblies) | build test C32 · default in CI and `publish.yml` C39 | - |
| startup config: `SITE_URL` (2 assemblies) | fixture build (existing site C33) · `publish.yml` C42 | - |
| go-live checks (8) | run green C62 · 404 C63 · redirects C64 · whois C65 · headers C66 · no cookie C67 · no placeholder C68 · og:image C69 | - |

- Claims naming a route, a status or a header: C4, C46 on the built `dist/`; C63, C64, C66, C67 at the host - each has a proof that crosses the boundary
- No other check claims more than the cases its proof exercises

## Swept

- validation: C10 - the `p` query; C37 - correction records; C56 - the candidacy JSON; C41 - the candidacy guard
- failure modes: C43 - any failed step deploys nothing; C37 - a malformed record fails the build; C56 - an invalid JSON leaves `--out` intact
- idempotency: existing - every run rebuilds `data/out/` and `dist/` from empty (etl-camara AC 31 makes them byte-identical for identical inputs) and `wrangler pages deploy` re-uploads the same files; C53 proves the two candidacy paths give identical output
- authorization: C47 - secrets are read only by `publish.yml`, which never runs on a pull request; the site is public and has no accounts
- concurrency: C44 - one publish run at a time, never cancelled
- data lifecycle: C45 - the photo cache carries over between runs; `data/raw/` is discarded with the runner (door 6); a correction record is never deleted, only edited in git (door 3)
- dependency failure: C43 - a Câmara or Cloudflare outage fails the run and leaves the previous deployment live; C41 - a broken candidacy file stops the run before the site build; a failed photo download renders no photo (existing site C24)
- state transitions: C35 - the four correction statuses; a transition is an edit to the file reviewed in a PR, nothing enforces an order
- observability: existing - the ETL log and the Actions log; GitHub e-mails the repository owner when a scheduled workflow fails; nothing to assert

## Handoff

- Size: S1 4k + S2 4k + S3 4k + S4 3k + S5 4k + S6 3k + S7 1k + S8 4k + S9 3k + S10 1k = 31k of new code, copy and tests, plus ~40k to read `plan.md`, this file, `Base.astro`, the three page templates, `build.test.ts`, `conftest.py`, `test_tse.py` and `cli.py` = 71k, under the 150k budget - one builder
- Mechanism: one builder (fits; no ask)
- Verification: round 1 over C1 to C61 after the last commit; round 2, scoped to C62 to C69, after the first green `publish.yml` run at the domain
