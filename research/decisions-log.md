# Decisões de produto e processo (após o grilling)

Uma linha por decisão; continua `02-grilling-escopo-mvp.md`. Decisões de arquitetura que valem para o projeto inteiro vão em `.specs/STATE.md` (AD-NNN), não aqui.

| Data | Decisão | Motivo | Link |
|---|---|---|---|
| 2026-09-26 | Repositório `mandato-aberto` recomeçado do zero em `main`; protótipo preservado na tag `prototype-2026-09`; pesquisa vive dentro do repo em `research/` | recomeçar sem carregar o protótipo; um lugar só para código e pesquisa | PR #1 |
| 2026-09-26 | Método `tlc-spec-lean` por feature, perfil `light` no projeto e `standard` em `etl-camara`; artefatos em `.specs/` | indicadores são o que um parlamentar contesta; `standard` injeta falhas e recomputa cobertura | `AGENTS.md` |
| 2026-09-26 | MVP em três features em sequência: `etl-camara`, `site`, `launch`, cada uma com plano, checks, build, Verifier e PR | cada uma cabe num builder e num PR revisável; o contrato entre as duas primeiras é o esquema JSON | `.specs/STATE.md` AD-001, AD-002 |
| 2026-09-26 | Planos de `site` e `launch` são escritos quando cada feature começa, com `etl/schema/*.json` como fonte | evita planejar contra um esquema que o build do ETL ainda pode ajustar | recomendação, aceita por omissão no handoff |
| 2026-09-26 | Plano do `etl-camara` aprovado como está, com as nove premissas confirmadas; o mantenedor baixa o CSV de candidaturas do TSE à mão | portal do TSE bloqueia acesso automatizado | `.specs/features/etl-camara/plan.md` |
| 2026-09-26 | Trailer de IA `Assisted-by: Claude Code` via `.claude/settings.json`; CI rejeita `Co-Authored-By` e "Generated with". Os três primeiros commits ficam como estão | mesma convenção dos demais repositórios pessoais do mantenedor | `scripts/check-commit-msg.sh` |
| 2026-09-26 | Corpo de PR em português, seções fixas, mais uma seção "Assistência de IA" | formato que o mantenedor já usa, com a transparência de IA explícita | `AGENTS.md` |
