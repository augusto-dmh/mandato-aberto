# Grilling — decisões de escopo da v2

**Data:** 30/09/2026 · **Entrada:** `04-direcao-v2.md`, `.specs/STATE.md` (AD-001 a AD-013), `01-pesquisa-juridica.md` · **Método:** árvore de decisões por rodadas, opção recomendada e consequência em cada alternativa.
Proveniência: `[user, Qn]` = escolhido pelo mantenedor na pergunta n · `[recomendação — pendente]` = default proposto, ainda não confirmado · `[pesquisa]` / `[código]` = decidido pelos fatos.

## Objetivo da v2 (enunciado pelo mantenedor)

Ser o lugar onde a pessoa vê, por dados oficiais, o que cada agente político federal fez: visualmente excelente (referências Apple e Spotify), com números claros, exportação dos dados e resumos por IA, construído num framework maduro que facilite evoluir. Política orientada a dados, contra o voto por popularidade ou parentesco. Mais adiante, uma comunidade que publica dados próprios, com aprovação.

## Decisões

| # | Decisão | Escolha | Proveniência | Consequência |
|---|---|---|---|---|
| 1 | Stack | **Monolito Laravel + Inertia + Vue com SSR; ETL em Python mantido**, publicando o JSON versionado que um comando Artisan importa | [user, chat 30/09 "Go"] | AD-013. Sem SPA separada, sem microserviço, sem bounded contexts no dia 1 |
| 2 | Data-alvo | **01/02/2027**, início da 58ª legislatura | [user, Q1] | Gancho "acompanhe desde o primeiro dia". O MVP estático cobre o período eleitoral sem mudanças |
| 3 | Repositório | **Mesmo repositório, app em `app/` ao lado de `etl/`**; `site/` sai quando o app o substituir | [user, Q2] | Contrato JSON, CI, `.specs/` e histórico num lugar só. Laravel não ocupa a raiz |
| 4 | Hospedagem | **VPS (Hetzner ou similar) com Forge ou Ploi**, cerca de US$ 10–30/mês | [user, Q4] | Postgres, filas e cron num servidor. **Deploy fora do escopo por agora** ([user, Q4]): domínio, backup, monitoramento e CI de deploy ficam para uma feature própria antes de 01/02 |
| 5 | Ordem das entregas | **1. Perfil excelente → 2. Contas, "meus eleitos" e alertas → 3. Comparador e exploração** | [user, Q5 após reexplicação] | O perfil é a base das outras duas; login e filas entram na entrega 2; comparador por último, com a regra de linguagem anti-ranking |
| 6 | Cobertura | **Câmara (57ª e 58ª), Senado e Presidência** | [user, Q6] | Legislatura vira entidade de primeira classe; ETL novo para Senado e Presidência. Substitui AD-006 para a v2 |
| 7 | Associação e IA | **Abrir associação sem fins lucrativos até 01/02/2027**; IA no lançamento sob o CNPJ | [user, Q7] | Trabalho fora do código (estatuto, cartório, contador) no caminho crítico. Se a associação não existir em 01/02, a IA não entra no lançamento (pesquisa jurídica, seção 2.1) |
| 8 | Perfil da Presidência | **Medidas provisórias, vetos e projetos do Executivo**, com o destino de cada um no Congresso | [user, Q8] | Fontes: APIs da Câmara e do Congresso Nacional. Decretos do Diário Oficial ficam fora |
| 9 | O que cede se não couber | **Presidência vai para março de 2027**; Câmara e Senado seguram 01/02 | [user, Q9] | Presidência é a única cobertura com modelo de dados novo |
| 10 | Processo de design | **Sistema de design antes das telas**: primeira feature da v2 define tokens, tipografia, componentes e três telas-chave, validadas em artifact antes do Laravel | [user, Q10] | Design é feature com plano e verificação, não acabamento |
| 11 | Escopo da IA no lançamento | **Resumo em linguagem simples de proposições e votações** ("o que estava em jogo") | [user, Q11] | IA resume texto oficial, nunca a pessoa. Sem resumo por parlamentar e sem busca em linguagem natural no lançamento |

## Premissas aplicadas sem perguntar

| Premissa | Default escolhido | Racional | Confirmada |
|---|---|---|---|
| Banco | PostgreSQL | Padrão para dado relacional com busca textual em português; roda no mesmo VPS | não |
| Ingestão | O ETL continua publicando JSON com `schema_version`; um comando Artisan valida e importa para o banco | AD-002; preserva a verificação `standard` do ETL | não |
| Testes e qualidade | Pest, Larastan, Pint; testes de feature por página e por importação | Convenção do mantenedor: comportamento novo com teste no mesmo PR | não |
| Painel de correções | Filament para triagem de correções e revisão dos resumos de IA | Admin pronto do ecossistema Laravel; AD-008 continua com histórico versionado | não |
| Revisão da IA | Resumo gerado a partir do texto oficial, rotulado como gerado por IA, guardado com modelo e hash da fonte, publicado só depois de revisão humana | Pesquisa jurídica, seção 1 (risco de alucinação) | não |
| Exportação | CSV e JSON por perfil, por votação e em lote, com a mesma fonte e data de coleta | Dado aberto por lei; baixo risco | não |
| Site estático atual | Fica no ar, sem mudanças de produto, até o app ocupar o mesmo endereço público | AD-012; o MVP cobre o período eleitoral | não |
| Regras de linguagem | Todas as do MVP valem na v2: descritivo, fonte em cada número, sem "faltou", sem adjetivo, sem vocabulário de pesquisa eleitoral | AD-004; o litígio civil mira adjetivo, não tabela | não |
| Idioma | Só pt-BR | Público brasileiro | não |
| Analytics | Sem cookie e sem identificador individual | Mantém a decisão do MVP; evita banner | não |
| Atualização dos dados | Diária, como no MVP | Arquivos em lote das casas atualizam diariamente | não |

## Fora do escopo da v2 (por escrito)

- **Deploy e operação** (domínio, backup, monitoramento, CI de deploy): adiado pelo mantenedor em Q4; volta como feature própria antes de 01/02.
- **Comunidade publicando dados**: exige moderação, associação madura e revisão jurídica própria.
- **Resumo por parlamentar e busca em linguagem natural**: Q11; erro atribuído a pessoa e vedação do TSE a resposta sobre voto.
- **Decretos do Diário Oficial**: Q8; fonte sem API estruturada.
- **Dinheiro** (cota parlamentar, emendas, campanha) e **promessa × voto**: sem decisão nova; seguem fora como no MVP.
- **Estaduais e municipais, app móvel**: sem mudança desde o MVP.

## Perguntas em aberto

- Nenhuma da v2. Pendência herdada do MVP, fora deste escopo: nomes em Quem somos (check C68 do `launch`).
