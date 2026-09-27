# Mandato Aberto

Saiba, por dados oficiais, o que cada deputado federal fez no mandato: votos em votações nominais, participação nessas votações, votos iguais à orientação do governo e à maioria do partido, proposições de autoria. Cada número mostra a sua base de cálculo e leva ao registro oficial.

A versão atual cobre só a Câmara dos Deputados, 57ª legislatura (desde 01/02/2023). Senado e Presidência são incrementos futuros, depois do lançamento.

Este repositório recomeça do zero em setembro de 2026. O protótipo anterior (ETL da Câmara em Python e site estático) está preservado na tag [`prototype-2026-09`](https://github.com/augusto-dmh/mandato-aberto/tree/prototype-2026-09).

## Indicadores

Cada perfil de deputado mostra, como contagem sobre a sua base (nunca como percentual):

- **Participação em votações nominais do plenário**: votações nominais do plenário com registro do deputado, sobre as votações realizadas enquanto ele estava em exercício.
- **Votos iguais à orientação do governo**: votos Sim, Não, Abstenção ou Obstrução iguais à orientação da bancada do governo, nas votações em que ela orientou um desses valores.
- **Votos iguais à maioria do próprio partido**: os mesmos quatro valores, comparados à maioria dos outros deputados do partido na mesma votação.
- **Proposições de autoria**: PL, PLP, PEC, PDL e PRC apresentados desde 01/02/2023 como proponente, com os de primeiro signatário e os requerimentos contados à parte.

A metodologia completa, com o link para cada arquivo oficial, está na página Metodologia e fontes do site (`site/src/pages/metodologia.md`).

## Estrutura

- `research/`: estudos que precedem o código: viabilidade jurídica, escopo do MVP, teste de balanceamento da LGPD.
- `etl/`: ETL em Python (`uv`) que baixa os dados abertos da Câmara e publica o contrato JSON em `data/out/`, com esquemas em `etl/schema/`. Uso: `cd etl && uv run mandato-etl build` (opções em `--help`); testes: `uv run pytest`.
- `site/`: site estático (Astro + Vue) gerado a partir de `data/out/`: home com busca, perfil por deputado, página por votação nominal, card de compartilhamento por deputado e as páginas Metodologia e fontes, Quem somos, Dados e privacidade, Correções e Reportar erro. Uso: `cd site && npm ci && npm run build` (lê `MANDATO_DATA_DIR`, padrão `../data/out`; `SITE_URL` define a origem das tags de compartilhamento e `SITE_BASE` o caminho sob ela, padrão `/`; `MANDATO_PHOTOS=off` usa só as fotos já em `site/.cache/photos/`); testes: `npm test`.
- `corrections/`: um arquivo `AAAA-MM-DD-<assunto>.md` por erro reportado, renderizado na página Correções (veja abaixo).
- `.specs/`: planos, checks e verificações de cada feature; decisões do projeto em `.specs/STATE.md`.

## Candidatura em 2026

O arquivo de candidaturas do TSE traz o CPF de cada candidato e nunca entra no repositório. O cruzamento roda uma vez na máquina de um mantenedor, com o arquivo baixado à mão em `etl/inputs/tse/` (ignorado pelo git):

```sh
cd etl
uv run mandato-etl build --tse-csv inputs/tse/consulta_cand_2026_BRASIL.csv --export-candidacy inputs/candidacy-2026.json
```

`--export-candidacy` grava `etl/inputs/candidacy-2026.json` só com cargo, partido, número e situação de cada deputado encontrado, a lista dos ambíguos e o hash do arquivo de origem. Esse arquivo é versionado; depois dele, o CSV do TSE pode ser apagado. A publicação diária lê o arquivo com `--candidacy-json`, que substitui `--tse-csv` (os dois não podem ser usados juntos):

```sh
uv run mandato-etl build --candidacy-json ../etl/inputs/candidacy-2026.json
```

## Publicação

`.github/workflows/publish.yml` publica o site no GitHub Pages, como project site em `https://augusto-dmh.github.io/mandato-aberto/`, todo dia às 09:00 UTC, a cada push em `main` e quando acionado à mão. Em cada execução: reconstrói `data/out/` a partir de um `data/raw/` vazio, com `--candidacy-json` quando o arquivo existe; para se o arquivo de candidaturas existe e nenhum deputado foi encontrado (`scripts/check-candidacy.sh`); restaura o cache de fotos, gera o site com `SITE_URL=https://augusto-dmh.github.io` e `SITE_BASE=/mandato-aberto`, e só então publica com `actions/deploy-pages`, usando o token do próprio repositório. Qualquer passo que falhar interrompe a execução e o deploy anterior continua no ar. Uma execução espera a anterior terminar.

Nenhum secret e nenhuma variável de repositório são necessários; a CI de pull requests não publica nada. `SITE_URL` (origem) e `SITE_BASE` (caminho sob a origem, padrão `/`) ficam escritos no próprio workflow e mudam quando o site ganhar domínio próprio. Sem `SITE_URL` a execução falha antes do build. O site não carrega nenhum script de terceiros.

## Correções

Qualquer pessoa reporta um erro pelo formulário em `/reportar-erro/`, que abre o programa de e-mail do visitante, ou direto pelo e-mail publicado no site. Cada erro vira um arquivo em `corrections/`:

```markdown
---
receivedAt: 2026-09-20
pages: [/deputados/101/]
status: corrected
resolvedAt: 2026-09-22
action: Base de cálculo corrigida (PR #9)
---

Resumo do relato.

## Resposta

Resposta do parlamentar, literal, quando houver.
```

`status` é `triage`, `corrected`, `reply-published` ou `no-change`; `resolvedAt` e `action` são opcionais. Um registro malformado faz o build do site falhar com o nome do arquivo. `MANDATO_CORRECTIONS_DIR` aponta o build para outro diretório (padrão `../corrections`, relativo a `site/`).
