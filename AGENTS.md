# Mandato Aberto — guia para agentes e pessoas

Site público que mostra, por dados oficiais, o que cada deputado federal fez no mandato. Leia `research/01-pesquisa-juridica.md` (o que podemos e não podemos publicar) e `research/02-grilling-escopo-mvp.md` (o que a primeira versão entrega) antes de qualquer mudança de produto.

## Método

Cada feature segue `tlc-spec-lean`: `plan.md` revisado por humano, `checks.md` com prova em cada afirmação, build, Verifier independente. Artefatos em `.specs/features/<feature>/`. Decisões que valem para o projeto inteiro ficam em `.specs/STATE.md`.

## Fluxo por feature

- Feature com mais de ~3 arquivos ou porta de uma via: `tlc-spec-lean` completo (plan → checks → build → verify). Se o diff cabe numa frase, só `checks.md`.
- Uma feature termina com `verification.md` escrito pelo Verifier, não com afirmação do builder.
- Ordem do MVP: `etl-camara` → `site` → `launch`. O contrato entre as duas primeiras é `etl/schema/*.json`.

## tlc-spec-lean

profile: light
budget: 150k

A feature `etl-camara` roda em `standard`: os indicadores são a parte que um parlamentar pode contestar.

A feature `design-system` roda em `ui`: a entrega inteira são telas, e só esse perfil abre as fontes vinculantes e enumera texto e arranjo por tela.

A feature `app-skeleton` roda em `standard`: o importador decide o que o público vê.

A feature `contract-v3` roda em `standard`: os indicadores e a classificação das votações são o que um parlamentar pode contestar.

A feature `app-contract-v3` roda em `ui`: perfis, votações do Senado e metodologia são telas.

A feature `app-home` roda em `ui`: home, busca e visão geral da legislatura são telas, e o risco está no texto e no arranjo (nada que ordene pessoas, estados vazios).

## Convenções

- Código, commits, branches, nomes de arquivo e identificadores em inglês. Copy do site, documentos de pesquisa e corpo de PR em português. Artefatos em `.specs/` em inglês.
- Comportamento novo sai com teste no mesmo PR.

## Commits e PRs

- Nunca commite em `main`; trabalhe em `<type>/<slug>` a partir de `main` atualizado.
- Mensagem em inglês: `type(scope): description`, minúscula, imperativo, cabeçalho de até 72 caracteres, corpo explica o porquê. Tipos: build chore ci docs feat fix perf refactor revert style test. Escopo: `etl`, `site`, `research`, `specs`, `ci`.
- Trailer de IA: `Assisted-by: Claude Code`, gerado por `.claude/settings.json`. Nunca `Co-Authored-By`, "Generated with" ou link de sessão. `scripts/check-commit-msg.sh` barra os três e a CI roda o mesmo script em cada commit do PR.
- Um PR por feature ou por tema. Título com a mesma regra do commit. Corpo em português com as seções: Descrição, Contexto, Arquitetura, Mudanças Principais, Testes, Configuração, Dependências, Impactos, Como Validar, Checklist, Assistência de IA (o que o agente escreveu, o que foi revisado por humano e como).
- Não faça push nem abra PR sem pedido explícito; nunca force-push em `main`.
- Linguagem do produto é descritiva. Cada número derivado tem metodologia pública e link para a fonte oficial. Nunca "faltou", nunca adjetivo sobre parlamentar, nunca vocabulário de pesquisa eleitoral ("aprovação", "intenção de voto", "favorito", "ranking").
- CPF nunca é exibido nem persistido. Nenhum dado pessoal além de nome, partido, UF, foto oficial e atos do mandato.
- Nenhum anúncio pago, nenhuma automação de engajamento, nenhuma IA gerando conteúdo antes de decisão registrada em `.specs/STATE.md`.
