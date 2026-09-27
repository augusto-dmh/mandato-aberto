# Mandato Aberto

Saiba, por dados, o que cada político fez no mandato: votações nominais, presença, alinhamento com governo e partido, proposições. Deputados federais, senadores e presidência, com fontes oficiais em cada dado.

Este repositório recomeça do zero em setembro de 2026. O protótipo anterior (ETL da Câmara em Python e site estático) está preservado na tag [`prototype-2026-09`](https://github.com/augusto-dmh/mandato-aberto/tree/prototype-2026-09).

## Estrutura

- `research/` — pesquisas que precedem o código: viabilidade jurídica, fontes de dados, escopo do MVP.
- `etl/` — ETL em Python (`uv`) que baixa os dados abertos da Câmara e publica o contrato JSON em `data/out/`, com esquemas em `etl/schema/`. Uso: `cd etl && uv run mandato-etl build` (opções em `--help`); testes: `uv run pytest`.
