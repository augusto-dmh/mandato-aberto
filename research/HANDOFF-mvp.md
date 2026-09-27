# Handoff — Mandato Aberto, construção do MVP

Cole o bloco abaixo como primeira mensagem de uma sessão nova do Claude Code aberta em `~/projects/mandato-aberto`.

---

Vamos construir o MVP do **Mandato Aberto**, site estático que mostra por dados oficiais o que cada deputado federal fez no mandato, no ar antes do 1º turno de 04/10/2026. Toda a pesquisa e as decisões já estão em disco; não refaça pesquisa nem reabra decisões.

**Leia nesta ordem antes de qualquer comando:**
1. `AGENTS.md` (convenções, commits, fluxo por feature, perfil do `tlc-spec-lean`)
2. `research/02-grilling-escopo-mvp.md` (13 decisões de escopo com proveniência; a lista "Fora do escopo" é vinculante)
3. `research/01-pesquisa-juridica.md`, seções 2 e 5 (o que nunca fazer; checklist de lançamento). Os anexos só se uma dúvida jurídica surgir.
4. `.specs/STATE.md` (AD-001 a AD-009 são restrições ativas; a seção Handoff diz onde parou)
5. `.specs/features/etl-camara/plan.md` (aprovado; 33 critérios, 7 portas, premissas confirmadas)
6. `research/decisions-log.md` (decisões posteriores ao grilling)
7. Tag `prototype-2026-09`, arquivo `etl/build.py` (`git show prototype-2026-09:etl/build.py`): a lógica de download com cache, períodos de exercício e maioria do partido que o plano manda portar, não reescrever

**Features, em ordem, cada uma com o ciclo completo do `tlc-spec-lean` e um PR:**

1. **`etl-camara`** (perfil `standard`). O plano está aprovado: derive `.specs/features/etl-camara/checks.md`, rode `validate_checks.py`, escreva `## Handoff` com a aritmética do orçamento, construa (`etl/` com `uv`, pacote `mandato_etl`, CLI `mandato-etl`, `pytest`, `jsonschema` só em dev), rode cada prova, commits pequenos com `check_commit.py`, e despache o Verifier na mesma rodada do último commit. A fatia S5 (candidatura 2026) roda sobre fixture até o CSV do TSE existir em `etl/inputs/tse/`. Adicione o job do ETL em `.github/workflows/ci.yml`. PR `feat(etl): ...` para `main`.
2. **`site`** (perfil `light`). Escreva o plano a partir de `etl/schema/*.json` e das decisões 2, 4, 9, 10, 11 do grilling: Astro + Vue, home com busca e filtros (UF, partido, em exercício, candidato em 2026), perfil por deputado, uma página por votação nominal, card de compartilhamento por deputado gerado no build, direção visual de jornalismo de dados editorial, linguagem descritiva (a lista de termos proibidos está em `AGENTS.md`), foto oficial por cache da URL da Câmara com crédito. Pare para revisão do plano antes dos checks. PR `feat(site): ...`.
3. **`launch`** (perfil `light`). Plano a partir das decisões 6, 7, 8 e do checklist da seção 5 da pesquisa jurídica: páginas Metodologia, Quem somos, Sobre os dados e privacidade, Correções (gerada de arquivo versionado) com formulário "Reportar erro" sem backend e e-mail dedicado, rodapé legal em toda página, build diário por GitHub Actions, deploy estático (Cloudflare Pages ou GitHub Pages), analytics sem cookie, domínio `.org`. Pare para revisão do plano antes dos checks. PR `feat(launch): ...`.

**Restrições:** nada de IA gerando conteúdo, nada de comentários, enquete, ranking ou lista ordenada por indicador, nada de anúncio pago, nada de CPF em lugar nenhum (AD-003). Não faça push nem abra PR sem eu pedir; nunca force-push em `main`. Não mude versão nem `CHANGELOG`. Se uma decisão do grilling ou um AD parecer errado, não mude: escreva a objeção em uma linha no chat e continue. Um plano só vira checks depois que eu aprovar; o do `etl-camara` já está aprovado. Data-alvo de ir ao ar: 02/10/2026 (decisão 13 do grilling, ainda por confirmar).

**Pendente meu:**
- Baixar em navegador `consulta_cand_2026_BRASIL.csv` do portal de dados abertos do TSE, guardar a captura de tela do campo "Licença" em `research/`, e colocar o arquivo em `etl/inputs/tse/`.
- Confirmar a data-alvo de 02/10 e as 15 premissas do `02-grilling-escopo-mvp.md` (comentar no PR #2 ou editar a coluna "Confirmada").
- Registrar o domínio `.org` com WHOIS privado e me passar o nome final antes da feature `launch`.
- Conferir em navegador os sete itens da seção 6 de `01-pesquisa-juridica.md` antes do lançamento.

---
