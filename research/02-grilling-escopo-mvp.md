# Grilling — decisões de escopo do MVP

**Data:** 26/09/2026 · **Entrada:** `01-pesquisa-juridica.md` e a pesquisa de viabilidade de 22/09 · **Método:** árvore de decisões por rodadas; cada rodada pergunta a fronteira cujos pré-requisitos já estão fechados, com opção recomendada e consequência em cada alternativa.
Proveniência: `[user, Qn]` = escolhido ou escrito pelo mantenedor na pergunta n · `[recomendação — pendente]` = default proposto, ainda não confirmado · `[pesquisa]` = decidido pelos fatos apurados.

## Objetivo do MVP (enunciado pelo mantenedor)

Site público, bonito o bastante para viralizar, que mostra por dados o que cada deputado federal fez no mandato, no ar **antes do 1º turno de 04/10/2026**, construído como base robusta que evolui incrementalmente. Um só mantenedor ativo nos próximos dias; repositório aberto a mais pessoas.

## Decisões

| # | Decisão | Escolha | Proveniência | Consequência |
|---|---|---|---|---|
| 1 | Data de lançamento | **Antes de 04/10/2026, com o app novo** (não relançar o protótipo) | [user, Q1] | Vale o regime da pesquisa jurídica para lançamento em campanha: só conteúdo descritivo com fonte em cada dado, Quem somos com nomes, Metodologia, canal de correção, zero anúncio pago, nenhuma IA, nenhum vocabulário de pesquisa eleitoral |
| 2 | Promessa central da v1 | **Perfil por parlamentar**: busca por nome, UF ou partido e página com votos, participação, alinhamento, proposições e link oficial em cada dado | [user, Q2] | Compartilhamento pela URL de cada perfil; "meus eleitos" e comparador ficam para depois |
| 3 | Cobertura | **Só Câmara dos Deputados, 57ª legislatura** (desde fev/2023) | [user, Q3] | Reaproveita o ETL provado no protótipo; Senado é o primeiro incremento após o lançamento; Presidência exige outro modelo de dados e fonte, sem prazo |
| 4 | Stack | **Site estático gerado (Astro + Vue) sobre ETL em Python**, deploy em hospedagem estática | [user, Q4] | Uma página real por deputado e por votação, meta tags de compartilhamento, custo zero, nada para derrubar em campanha. O ETL publica JSON com esquema documentado; o site lê por uma camada de dados trocável |
| 5 | Caminho de evolução | **Pode migrar para app com banco e estado** (Laravel, framework completo que o mantenedor já domina e que resolve auth, filas, banco e admin sem reinventar) quando alertas, contas ou escala pedirem | [user, Q13] | O esquema do JSON e a separação ETL/site são o contrato a preservar; nenhuma lógica de negócio dentro de componentes de tela |
| 6 | Nome e domínio | **"Mandato Aberto" em .org com WHOIS privado** | [user, Q5] | Nomes dos mantenedores ficam na página Quem somos (exigência eleitoral), não em base pública consultável; .com.br fica para quando houver associação |
| 7 | Canal de correção | **Formulário sem backend + e-mail dedicado + página "Correções" gerada de arquivo versionado** | [user, Q6] | Botão "Reportar erro" em cada perfil e votação; triagem em 48 h, correção ou resposta do parlamentar em 7 dias; histórico auditável no git |
| 8 | Candidatura 2026 | **Sim: selo "candidato a X em 2026" no perfil e filtro na busca**, via CSV do TSE baixado à mão e cruzado por nome civil + data de nascimento + UF | [user, Q7] | É o gancho eleitoral do lançamento. Sem CPF. Homônimos resolvidos à mão. Captura da licença do dataset entra no repositório |
| 9 | Indicadores do perfil | **Os quatro do protótipo, renomeados**: participação em votações nominais (votos registrados ÷ votações do plenário em exercício, descontando licenças); votos iguais à orientação do governo (n de m, só onde há orientação); votos iguais à maioria do próprio partido (n de m); proposições de autoria e requerimentos | [user, Q8] | Nunca a palavra "faltou": os dados abertos da Câmara não trazem justificativa de ausência. Cada número com metodologia pública e base de cálculo visível |
| 10 | Direção visual | **Jornalismo de dados editorial**: tipografia forte, uma cor de destaque, números grandes com fonte logo abaixo, card de compartilhamento por deputado | [user, Q9] | Lê como informação, não como campanha; viraliza por print. Sem ilustração lúdica, sem tom satírico |
| 11 | Páginas | **Home, perfil por deputado e uma página por votação nominal** | [user, Q11] | "Veja como todos votaram" tem URL própria; sem página por partido ou UF na v1 (lista com percentuais lado a lado é quase ranking) |
| 12 | Equipe | **Um mantenedor ativo com Claude Code; repositório aberto a mais pessoas** | [user, Q10] | Um só fluxo de PRs; conteúdo (Metodologia, Quem somos) e revisão de amostras de perfis contra o portal da Câmara entram no plano como tarefas, não como pessoa |
| 13 | Data-alvo | **02/10/2026** | [recomendação — pendente] | Dois dias de folga antes do 1º turno para corrigir o que a revisão de amostras achar |

## Fatos apurados durante o grilling

- O arquivo em lote `eventosPresencaDeputados` registra só quem esteve presente em cada evento; **não há justificativa de ausência** nos dados abertos da Câmara. A justificativa existe apenas na página do portal.
- A API da Câmara devolve `/deputados/{id}/eventos` vazio para o período testado; presença por sessão vem só do arquivo em lote.
- Situações de deputado na API: Afastado, Convocado, Exercício, Fim de Mandato, Licença, Suplência, Suspenso, Vacância. O cálculo de "em exercício" do protótipo usa o histórico dessas situações.
- Arquivos em lote disponíveis e relevantes: `deputados`, `votacoes`, `votacoesVotos`, `votacoesOrientacoes`, `votacoesProposicoes`, `votacoesObjetos`, `proposicoes`, `proposicoesAutores`, `proposicoesTemas`, `eventos`, `eventosPresencaDeputados`, `frentes`, `orgaosDeputados`.

## Premissas aplicadas sem perguntar

| Premissa | Default escolhido | Racional | Confirmada |
|---|---|---|---|
| Home | Busca + filtros (UF, partido, em exercício, candidato em 2026) + explicação de uma frase + números agregados da legislatura. Nenhuma lista ordenada por indicador | Lista ordenada por percentual é ranking; a home vende o perfil, não a comparação | não |
| Ordenação padrão das listas | Alfabética | Mesma razão | não |
| Card de compartilhamento | Imagem por deputado gerada no build (nome, partido, UF, foto oficial, os quatro indicadores com "n de m", fonte e data) | Decisão 10; sem servidor para gerar sob demanda | não |
| Busca | Índice no cliente sobre `deputados.json` | 513 registros; sem backend | não |
| Hospedagem | Cloudflare Pages (funções para o formulário, analytics sem cookie incluso); GitHub Pages como alternativa | Decisões 4 e 7 | não |
| Analytics | Sem cookie e sem identificador individual | Elimina banner e quase toda a LGPD de visitante | não |
| Atualização | Diária por GitHub Actions, como no protótipo; data de coleta visível em cada página | Arquivos em lote atualizam diariamente | não |
| Fotos | URL oficial da Câmara servida por cache, sem recorte ou filtro, crédito "Foto: Câmara dos Deputados" | Licença CC BY do Banco de Imagens; pesquisa jurídica | não |
| Suplentes e afastados | Aparecem, marcados; filtro "em exercício" ligado por padrão | Protótipo já faz; histórico da legislatura importa | não |
| Votações incluídas | Nominais do plenário e de comissões, como no protótipo; participação calculada só sobre o plenário | Mesma base do protótipo | não |
| Layout do repositório | `etl/` (Python, `uv`, `pytest`), `site/` (Astro), `research/`, `docs/` | Decisão 4; separação que permite a migração da decisão 5 | não |
| Testes | Unitários das métricas do ETL com fixtures pequenas; validação de esquema do JSON; testes de componente onde houver lógica; CI de lint e testes | Convenção do mantenedor: comportamento novo sai com teste no mesmo PR | não |
| Idioma | Código, commits, branches e ADRs em inglês; copy e documentos de pesquisa em pt-BR | Convenção do mantenedor | não |
| Divulgação | Só orgânica até 25/10; nenhuma doação na v1 | Pesquisa jurídica: impulsionamento vedado a pessoa natural | não |
| Deputados não reeleitos após 04/10 | Perfil permanece; selo muda para o resultado quando o TSE publicar | Tema 786: histórico não se apaga | não |

## Fora do escopo do MVP (por escrito)

Senado (primeiro incremento) · Presidência · legislaturas anteriores à 57ª · "meus eleitos" e alertas por e-mail · comparador e qualquer lista ordenada por indicador · página por partido ou UF · dinheiro (cota parlamentar, emendas, campanha) · presença oficial em sessões como indicador · promessas × votos · qualquer geração por IA (resumos, chat) · comentários de usuários · enquetes · contas e login · associação ou CNPJ · doações · anúncios · app móvel · API pública própria · estaduais e municipais.

## Perguntas em aberto

- Confirmar a data-alvo de 02/10 (decisão 13).
- Os sete itens da seção 6 de `01-pesquisa-juridica.md` continuam pendentes de confirmação em navegador antes do lançamento; o primeiro (licença do dataset de candidatos do TSE) passa a ser pré-requisito da decisão 8.
