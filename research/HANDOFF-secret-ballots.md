# Handoff — votações secretas (`secret-ballots`)

Cole o bloco abaixo como primeira mensagem de uma sessão nova do Claude Code aberta em `~/projects/mandato-aberto`.

---

Construa a feature **`secret-ballots`** do Mandato Aberto. Plano aprovado pelo mantenedor e checks escritos; falta o build e a verificação. Não refaça pesquisa, não reabra o plano e não mude um check: se um check parecer errado, pare e pergunte.

**Branch:** `git switch fix/secret-ballots`. Ela está empilhada sobre `feat/site` (PR #6, ainda aberto). Se o #6 já tiver entrado em `main`, rebase em `main` atualizado antes de começar.

**Leia nesta ordem antes de qualquer comando:**
1. `AGENTS.md` (convenções, commits, PR em português com as seções obrigatórias)
2. `.specs/features/secret-ballots/plan.md` (o problema, as duas portas, AC 1–9)
3. `.specs/features/secret-ballots/checks.md` (18 checks, perfil `standard`, a lista fechada de valores de teste renegociados e o `## Handoff` com o risco conhecido)
4. `.specs/STATE.md` (AD-002 e AD-004; a seção Handoff)
5. Código que muda: `etl/src/mandato_etl/compute.py` (`_roll_call_summary`, `assemble`), `etl/src/mandato_etl/readers.py`, `etl/src/mandato_etl/cli.py`, `etl/schema/roll-call*.schema.json` e `meta.schema.json`, `etl/tests/conftest.py`; no site, `site/src/lib/data.ts`, `format.ts`, `indicators.ts`, `site/src/pages/votacoes/[id].astro`, `site/src/pages/deputados/[id].astro`, `site/tests/build.test.ts`

**Como trabalhar (skill `tlc-spec-lean`, fase Build em diante):**
- Escreva os testes a partir dos checks, nunca a partir do código. Nomes de teste e seletores exatamente como estão em cada `Proof:`.
- Só estes valores de testes aprovados mudam: roll calls da fixture do site 7 → 8, participação do 101 `3 de 4`/`0.75` → `4 de 5`/`0.8`, do 102 `2 de 2` → `3 de 3`, versão rejeitada `2` → `3`, versão aceita `1` → `2`, `etl/tests/test_publish.py:39` → `2`, `etl/tests/test_schema.py:48` → `3`. Nenhuma outra asserção aprovada é enfraquecida, removida ou pulada.
- `legislature()` em `etl/tests/conftest.py` não muda; crie `legislature(secret=True)` com a votação `100-6` descrita no topo do `checks.md` (totais oficiais `12/5/2`).
- Regere `site/tests/fixtures/out/` a partir da variante (um teste temporário em `etl/tests/` que chama o build sobre `legislature(secret=True)` e copia a saída; apague o teste temporário depois). A fixture continua sendo saída real do ETL, validada em CI.
- Risco conhecido: `etl/tests/test_publish.py:92` monta linhas de `votacoes` sem `votosSim`, `votosNao` e `votosOutros`. Essas colunas só podem ser exigidas para uma votação secreta.
- Copy nova do site, literal: `Votação secreta: a Câmara registra quem votou, não o voto de cada deputado.`, `Totais oficiais da Câmara`, `Deputados que votaram (<n>)`, `Votação secreta` (linha do perfil), e a base da participação terminando em `inclusive Art. 17 e votações secretas.`. Nenhum termo de `site/src/lib/forbidden-terms.ts` (atenção: "presença" é proibido).
- Não regenere `site/package-lock.json`: ele foi reescrito com o npm 11.19 da CI.
- C8 roda o ETL de verdade sobre `data/raw/` (usa o cache; a lista de deputados em exercício vem da API). Rode depois dos testes verdes.
- Commits pequenos, `type(scope): description` em inglês, trailer `Assisted-by: Claude Code`, validados com `check_commit.py` e `scripts/check-commit-msg.sh`.

**Ao terminar o último commit:** despache o Verifier independente (sub-agente novo) sobre `9921e6c..HEAD`, com **todos** os 18 checks e perfil `standard` (injeção de falhas e recomputação da cobertura). Rode `validate_verification.py .specs/features/secret-ballots` (passe o diretório da feature: o script procura primeiro uma pasta com o nome solto na raiz, e `site` já colidiu com `./site/`). Registre as lições das lacunas com `lessons.py`, atualize a seção Handoff do `.specs/STATE.md` e o item (4) de "Open for the maintainer".

**Restrições:** não faça push nem abra PR sem o mantenedor pedir. Quando pedir, o PR vai contra `feat/site` enquanto o #6 estiver aberto (ou contra `main` depois), título `fix(etl): mark secret ballots and count them as participation`, corpo em português com as seções do `AGENTS.md`. Nunca force-push em `main`. Nada de CPF, nada de percentual, nada de adjetivo sobre parlamentar.

---
