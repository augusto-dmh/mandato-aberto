# Handoff — planejar o lançamento (`launch`)

Cole o bloco abaixo como primeira mensagem de uma sessão nova do Claude Code aberta em `~/projects/mandato-aberto`.

---

Planeje a feature **`launch`** do Mandato Aberto: a terceira e última do MVP (`etl-camara` → `site` → `launch`). Esta sessão só **planeja**: entrega `.specs/features/launch/plan.md` revisável e para. Nada de checks, nada de código. Não refaça pesquisa e não reabra decisões; se uma decisão ou um AD parecer errado, escreva a objeção em uma linha no chat e siga.

**Branch:** `git switch feat/launch` (criada de `main` atualizado, depois do merge do PR #7). Este arquivo já está nela.

**Leia nesta ordem antes de qualquer comando:**
1. `AGENTS.md` (convenções, commits, linguagem do produto, perfil do `tlc-spec-lean`)
2. `research/02-grilling-escopo-mvp.md`: decisões 1, 6, 7, 8, 12 e 13, e a lista "Fora do escopo", que é vinculante
3. `research/01-pesquisa-juridica.md`: seção 2.3 (o que nunca fazer), seção 5 (checklist antes do lançamento, a espinha do plano) e seção 6 (sete itens a confirmar em navegador)
4. `.specs/STATE.md`: AD-001 a AD-009 são restrições ativas (atenção a AD-003, AD-004, AD-005, AD-007, AD-008); a seção Handoff lista o que está aberto com o mantenedor
5. `research/decisions-log.md` (decisões depois do grilling)
6. `.specs/features/site/plan.md`: a tabela "Out of scope" diz o que o site deixou para o `launch`; as premissas fixam `SITE_URL` provisório e o formato dos links de metodologia
7. `.specs/features/etl-camara/plan.md` e `.specs/features/secret-ballots/plan.md`: as definições de cada indicador, que a página de Metodologia descreve
8. O código que o plano vai tocar: `site/src/layouts/Base.astro` (rodapé atual), `site/src/lib/indicators.ts` (rótulos e bases de cálculo), `site/src/lib/forbidden-terms.ts`, `site/astro.config.mjs`, `.github/workflows/ci.yml`

**Como trabalhar (skill `tlc-spec-lean`, fase Plan):** siga `references/plan.md` da skill. Escreva problema → superfícies → critérios → forma; preencha `Flow`, `Impact`, `Relations`, `Surface`, `Landing` (portas de uma via com a forma literal e a alternativa rejeitada), `Criteria` em EARS com SHALL, `Assumptions`, `Observable` com `n/a - <motivo>` explícito. Rode `validate_plan.py .specs/features/launch` (passe o diretório: um nome solto já colidiu com `./site/`) até sair com 0. Leia as lições confirmadas com `lessons.py list --status confirmed` (hoje não há nenhuma); a candidata L-010 ("fixar em check toda string de copy que um estado novo de página introduz") vale para as páginas novas. Pergunte decisões ao mantenedor com opções concretas e sua recomendação, no máximo duas por vez.

**O que o `launch` cobre** (decisões 6 a 8, checklist da seção 5, "Out of scope" do site):
- Páginas: Metodologia e fontes (âncoras já fixadas pelo site: `/metodologia/#participacao`, `#alinhamento-governo`, `#alinhamento-partido`, `#proposicoes`), Quem somos, Sobre os dados e privacidade, Correções e direito de resposta (gerada de arquivo versionado, AD-008)
- Botão "Reportar erro" em cada perfil e votação, formulário sem backend e e-mail dedicado
- Rodapé legal em toda página, com a frase da seção 5 da pesquisa e o crédito das fontes e das fotos
- Página 404
- Deploy estático (Cloudflare Pages ou GitHub Pages), build diário por GitHub Actions com o cache de fotos restaurado, domínio `.org`, `SITE_URL` real, analytics sem cookie e sem identificador individual

**Fatos que o plano precisa levar em conta:**
- A Metodologia tem que dizer que a participação conta votações secretas e Art. 17 (texto atual da base de cálculo em `site/src/lib/indicators.ts`), e que os totais de uma votação secreta são os oficiais da Câmara (`secret-ballots`)
- `dist/` sobre os dados reais tem 313 MB (cards 128 MB, perfis 111 MB); 643 deputados, 1.597 votações, cerca de 2.241 rotas; o build real leva cerca de 65 s e o ETL cerca de 40 s a 1,5 min com cache
- O contrato está na versão 2; o site recusa `data/out/` da versão 1. O `data/out/` local ainda precisa de `mandato-etl build` com o CSV do TSE
- O `README.md` da raiz ainda fala em "presença" e promete senadores e presidência, que estão fora do MVP
- Hoje é 27/09/2026. Data-alvo de ir ao ar: 02/10/2026 (decisão 13, ainda não confirmada); 1º turno em 04/10/2026. O plano tem que caber nisso ou dizer o que corta

**Entradas que só o mantenedor tem (o plano lista como premissa ou pergunta; nunca invente):**
- Nomes completos, cidade e e-mail de contato para Quem somos; o e-mail dedicado de correções
- O nome final do domínio `.org`, com WHOIS privado
- O CSV `consulta_cand_2026_BRASIL.csv` do TSE em `etl/inputs/tse/` e a decisão se guardar esse arquivo (que traz CPF de todos os candidatos) conta como persistir CPF sob o AD-003
- A confirmação da data-alvo e dos sete itens da seção 6 da pesquisa
- Contato de advogado identificado de antemão (checklist da seção 5)

**Restrições:** nenhum anúncio, nenhuma automação de engajamento, nenhuma IA gerando conteúdo, nenhum comentário ou enquete (AD-009); nenhum CPF em lugar nenhum; nenhum dado pessoal além de nome, partido, UF, foto oficial e atos do mandato; nenhum termo de `site/src/lib/forbidden-terms.ts` na copy nova (atenção: "presença" é proibido). Perfil do projeto é `light`; se o plano achar que as páginas novas pedem `ui`, diga em uma linha e deixe o mantenedor decidir.

**Ao terminar:** `validate_plan.py` com 0, commit do plano em `feat/launch` (`docs(specs): plan the launch ...`, trailer `Assisted-by: Claude Code`, validado com `check_commit.py` e `scripts/check-commit-msg.sh`), atualize a seção Handoff do `.specs/STATE.md` e pare para a revisão do mantenedor. Não escreva `checks.md` antes da aprovação. Não faça push nem abra PR sem pedido; nunca force-push em `main`.

---
