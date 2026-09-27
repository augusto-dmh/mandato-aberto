# Handoff — construir o lançamento (`launch`)

Cole o bloco abaixo como primeira mensagem de uma sessão nova do Claude Code aberta em `~/projects/mandato-aberto`.

---

Construa a feature **`launch`** do Mandato Aberto, a terceira e última do MVP. Plano aprovado pelo mantenedor (portas 1 e 7 confirmadas em 27/09/2026, AD-010 e AD-011) e checks escritos; falta o build e a verificação. Não refaça pesquisa, não reabra o plano e não mude um check: se um check parecer errado ou impossível, pare e pergunte. Hoje é 27/09/2026; a data-alvo de ir ao ar é 02/10/2026.

**Branch:** `git switch feat/launch` (base `a523a43` em `main`, HEAD atual `baa34b1`). Nada está empilhado sobre PR aberto.

**Leia nesta ordem antes de qualquer comando:**
1. `AGENTS.md` (convenções, commits, PR em português, linguagem do produto, perfil `light`)
2. `.specs/features/launch/plan.md` (o problema, o `Flow` em três caminhos, as 7 portas, AC 1–58, as premissas e as 7 perguntas abertas)
3. `.specs/features/launch/checks.md` (69 checks; o cabeçalho fixa a fixture de correções, o módulo de identidade e as quatro interpretações; S10 não roda agora)
4. `.specs/STATE.md` (AD-001, AD-003, AD-005, AD-007 a AD-011; a seção Handoff)
5. Código que muda ou serve de modelo: `site/src/layouts/Base.astro`, `site/src/pages/deputados/[id].astro`, `site/src/pages/votacoes/[id].astro`, `site/src/lib/forbidden-terms.ts`, `site/src/lib/data.ts` (padrão de leitura por variável de ambiente), `site/tests/build.test.ts` (o único `astro build` da fixture; os checks novos entram nele), `site/tests/language.test.ts`, `etl/src/mandato_etl/cli.py`, `etl/src/mandato_etl/sources/tse.py`, `etl/tests/conftest.py` (`build`, `write_tse`, `TSE_ROWS`, servidor local), `etl/tests/test_tse.py`, `.github/workflows/ci.yml`, `.gitignore`, `README.md`
6. Para a copy: `research/01-pesquisa-juridica.md` seções 3.1 e 5 (privacidade, teste de balanceamento, rodapé) e `.specs/features/etl-camara/plan.md` AC 17–27 com `.specs/features/secret-ballots/plan.md` AC 1–4 (o que a Metodologia descreve)

**Como trabalhar (skill `tlc-spec-lean`, fase Build em diante):**
- Escreva os testes a partir dos checks, nunca a partir do código. Nomes de teste e seletores exatamente como estão em cada `Proof:` (`T` = `npm --prefix site test --`, `E` = `uv run --directory etl pytest`). Ordem sugerida: S1, S2, S3, S4, S5, S8, S6, S9, S7. C62 a C69 (S10) ficam para depois do primeiro deploy; não os rode nem os marque.
- Toda string de copy está fixada nos checks (C1–C4, C8–C13, C15–C23, C25–C31, C33–C38). Copie literalmente. Nenhum termo de `site/src/lib/forbidden-terms.ts` em `site/src/` nem no `README.md` (atenção: "presença", "ausência", "pesquisa" e "aprovação" são proibidos; "Aprovada" como resultado de votação já existe e não é o termo).
- Identidade do site num módulo `site/src/lib/site.ts`: `MAINTAINERS` (lista de `{name, city}`), `CORRECTIONS_EMAIL`, `REPO_URL`. Valores `[a definir]` até o mantenedor os fornecer; os testes leem o módulo, não valores fixos. Nas frases dos checks, `<CORRECTIONS_EMAIL>` é o valor desse módulo.
- `_redirects` e `_headers` seguem `SITE_URL`: gere-os no build a partir de `Astro.site` (o `astro build` da fixture usa `https://preview.example.org`, então C46 lê `https://www.preview.example.org/* https://preview.example.org/:splat 301`). Arquivos em `src/pages/` com `_` inicial são ignorados pelo Astro; use `public/` só para o que não depende do domínio.
- Correções: diretório de `MANDATO_CORRECTIONS_DIR` (padrão `../corrections`, relativo a `site/`, como `MANDATO_DATA_DIR`); `corrections/.gitkeep` no repo; fixture em `site/tests/fixtures/corrections/` com os dois registros do cabeçalho dos checks. Sem dependência nova para o frontmatter (as chaves são fixas e o `pages` é uma lista de caminhos): um parser mínimo. Uma dependência nova é uma linha em `Landing` antes do código, nunca depois.
- Builds extras do Astro nos testes (correções vazias, registro malformado, token de analytics) seguem o padrão do `build.test.ts`: `astroBuild(dataDir, outDir)` com `outDir` sob `site/.cache/` (o `/tmp` é tmpfs e o Astro renomeia assets entre filesystems). Não regenere `site/package-lock.json`.
- ETL: `--export-candidacy` só com `--tse-csv`; `--candidacy-json` exclusivo com `--tse-csv`; o export tem exatamente as chaves de C51/C52 (`modifiedAt` é o mtime do CSV em ISO 8601 UTC). A leitura do JSON valida antes de qualquer download e deixa `--out` intacto (C56). Nenhum teste aprovado do `etl-camara` ou do `secret-ballots` muda; `legislature()` e `TSE_ROWS` não mudam.
- `scripts/check-candidacy.sh <meta.json> <candidacy.json>` é o guard de C41; teste-o por `etl/tests/test_candidacy_guard.py` com `subprocess`.
- `publish.yml` literal como as portas 1 e 6 e os checks C40–C45 descrevem. `ci.yml` não ganha secrets (C47).
- Copy do `research/03-teste-de-balanceamento-lgpd.md` (C58) e do `README.md` (C59–C61): rascunho seu, a partir da seção 3.1 da pesquisa jurídica e do que o repositório já documenta; o mantenedor revisa no PR. Controladores como `[a definir]` até os nomes chegarem.
- Não rode o ETL real sobre `data/raw/` nem o build real sobre `data/out/`: nenhum check pede isso.
- Commits pequenos, `type(scope): description` em inglês, trailer `Assisted-by: Claude Code`, validados com `check_commit.py` e `scripts/check-commit-msg.sh`. Escopos: `site`, `etl`, `ci`, `docs`, `research`.

**Ao terminar o último commit:** despache o Verifier independente (sub-agente novo, nunca você) sobre `a523a43..HEAD`, com os checks **C1 a C61** e perfil `light`, e diga a ele que C62–C69 são a rodada 2, depois do primeiro deploy, e ficam fora do escopo desta rodada. Rode `validate_verification.py .specs/features/launch` (passe o diretório da feature). Registre as lições das lacunas com `lessons.py`, atualize a seção Handoff do `.specs/STATE.md` (próximo passo: PR quando o mantenedor pedir; depois, os insumos das perguntas 1–4 e a rodada 2) e pare.

**Restrições:** não faça push nem abra PR sem o mantenedor pedir; nunca force-push em `main`. Quando ele pedir, o PR vai contra `main`, título `feat(site): add the legal pages, the correction channel and the daily publication`, corpo em português com as seções do `AGENTS.md`, inclusive "Assistência de IA" dizendo que a copy foi rascunhada pelo agente a partir dos checks e revisada frase a frase pelo mantenedor. Nada de CPF, nada de percentual, nada de adjetivo sobre parlamentar, nenhum comentário, enquete ou IA gerando conteúdo.

---
