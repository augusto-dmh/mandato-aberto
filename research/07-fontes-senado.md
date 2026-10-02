# Fontes do Senado Federal — o que os dados abertos sustentam

**Data:** 02/10/2026 · **Entrada:** AD-002 a AD-005 e AD-014 (`.specs/STATE.md`), `01-pesquisa-juridica.md` seção 3.2 e anexo A1 §3.2 e §4.4, `05-grilling-escopo-v2.md` decisão 6, ETL da Câmara em `etl/` · **Método:** chamadas reais com `curl` à API `https://legis.senado.leg.br/dadosabertos`, com `User-Agent` identificando o projeto, cerca de 50 requisições no total, nunca mais de uma por segundo. Os números de contagem abaixo saem de arquivos baixados nesta sessão e processados com Python.

Legenda: **[V]** verificado por chamada nesta sessão (URL e resposta citadas) · **[D]** afirmado pela documentação oficial (OpenAPI do serviço), sem teste de comportamento · **[NV]** não verificado.

## 1. Veredito

O Senado publica mais do que a Câmara em dois pontos que importam para nós e menos em um:

- **Mais:** cada votação nominal do Plenário traz um registro por cadeira em exercício (sempre 81) com o **motivo oficial** de quem não votou (atividade parlamentar, missão, licença, presente sem registrar voto, não compareceu, presidindo). A participação pode ser publicada com a decomposição que a Câmara não permite e que a pesquisa jurídica pediu (seção 3.4, camada 1: "sem dizer que 10 são ausências justificadas").
- **Mais:** nas votações **secretas** o Senado lista quem votou (`Votou`) e quem não votou, com motivo; a Câmara só deixa o voto vazio.
- **Menos:** a orientação do Governo existe, mas só em parte das votações abertas (cerca de 75%: 134 de 176 de 2023 a 2026) e nunca nas secretas. "Votos iguais à orientação do Governo" é possível para senadores, com denominador menor e a ressalva escrita.

Não há CPF em nenhuma resposta consultada. Há data de nascimento, naturalidade, telefone, e-mail e endereço de gabinete em `/senador/{codigo}`, que não entram no contrato (AD-003 e AGENTS.md).

## 2. A API

| Item | Fato | Certeza |
|---|---|---|
| Base | `https://legis.senado.leg.br/dadosabertos`, sem autenticação. OpenAPI em `/dadosabertos/v3/api-docs` (versão `4.1.3.99`, 173 KB) | [V] 200 |
| Formatos | `Accept: application/json` ou sufixo `.json`; também XML, CSV e, em `/votacao` e `/processo`, `application/x-ndjson` | [V] JSON; [D] demais |
| Limite de requisições | Documentado na descrição da OpenAPI: "Mais de 10 requisições por segundo estão sujeitas a receber o erro HTTP 429"; em alta demanda, 503; pede para evitar horários redondos (00:00:00, 01:15:00) | [D]; nenhum 429 visto a 1 req/s |
| Cabeçalhos de limite | Nenhum `RateLimit-*` ou `Retry-After` nas respostas 200; `cache-control: max-age=600` em `/votacao`, `max-age=900` em `/senador/lista/atual` | [V] |
| Depreciação | Serviços depreciados respondem `301` com `Deprecation`, `Sunset` e `Link: rel="successor"`. Ex.: `/plenario/votacao/nominal/2023` → `deprecation: Tue, 18 Mar 2025`, `sunset: Sun, 01 Feb 2026`, `link: <…/dadosabertos/votacao>; rel="successor"`. Também depreciados: `/senador/{codigo}/votacoes`, `/senador/{codigo}/autorias`, toda a família `/materia/*` de detalhe | [V] cabeçalhos; lista [D] |
| Erro de parâmetro | `400 application/problem+json`: `{"detail":"Limite o período a 1 ano ou informe um dos parâmetros obrigatórios.","status":400,"title":"Bad Request"}` em `/votacao?dataInicio=2023-02-01&dataFim=2026-10-02` | [V] |
| Código inexistente | `/senador/999999` devolve **200** com o envelope e sem `Parlamentar` (não 404) | [V] |
| Latência | 0,4 s a 2 s por chamada; 7,2 s para `/processo?codigoParlamentarAutor=825` sem filtro de data (3,8 MB) | [V] |
| Arquivos em lote | O portal (`www12.senado.leg.br/dados-abertos/conjuntos?portal=Legislativo&grupo=plenario`) só oferece em lote listas pequenas (`ListaLegislatura`, vetos). **Não há arquivo anual de votos** como os da Câmara; `/votacao` por ano (≈2,5 MB) cumpre esse papel | [V] |
| Licença dos dados | "exigindo-se, no máximo, citar sua proveniência", sem licença nominal (pesquisa jurídica, A1 §3.2) | [P] em 26/09, não rechecado |

## 3. Senadores, mandatos, suplentes e afastamentos

**Lista por legislatura.** `GET /senador/lista/legislatura/57?exercicio=S` → 200, 162 KB, **125 parlamentares**: 81 titulares, 34 primeiros suplentes, 9 segundos suplentes e 1 com dois mandatos de titular [V]. O filtro quer dizer "entrou em exercício em algum momento de um mandato que abrange a 57ª", não "exerceu na 57ª": **19 dos 125 têm todos os exercícios antes de 01/02/2023** (ex.: Major Olimpio, 5666, de 2019-02-01 a 2021-03-19; Arolde de Oliveira, 751, até 2020-10-21) e nenhum voto na legislatura [V]. Exerceram na 57ª, portanto, 106; 104 deles aparecem em ao menos uma votação [V]. Sem o filtro, 245 registros (titulares e suplentes que nunca exerceram) [V]. `/legislatura/58` já devolve 81 nomes: os 27 titulares eleitos em 2022 e seus suplentes, cujo mandato atravessa a 57ª e a 58ª [V]; os eleitos em 04/10/2026 ainda não existem.

Cada registro traz `IdentificacaoParlamentar` (`CodigoParlamentar`, `NomeParlamentar`, `NomeCompletoParlamentar`, `SexoParlamentar`, `UrlFotoParlamentar`, `UrlPaginaParlamentar`, `EmailParlamentar`, `SiglaPartidoParlamentar`) e `Mandatos.Mandato[]` com `CodigoMandato`, `UfParlamentar`, `PrimeiraLegislaturaDoMandato` e `SegundaLegislaturaDoMandato` (número e datas), `DescricaoParticipacao` (`Titular`, `1º Suplente`, `2º Suplente`), `Titular` ou `Suplentes` cruzados e **`Exercicios.Exercicio[]`** [V]. Exemplo real (Flávio Dino, `CodigoParlamentar` 4605, titular pelo MA):

```json
"Exercicios": {"Exercicio": [
  {"CodigoExercicio": "3056", "DataInicio": "2024-02-01", "DataFim": "2024-02-20",
   "SiglaCausaAfastamento": "REN", "DescricaoCausaAfastamento": "Renúncia"},
  {"CodigoExercicio": "3013", "DataInicio": "2023-02-01", "DataFim": "2023-02-02",
   "SiglaCausaAfastamento": "AFO", "DescricaoCausaAfastamento": "Afastamento do exercício"}]}
```

E da suplente que assumiu a cadeira (Ana Paula Lobato, 6358): exercício de `2023-02-02` a `2024-01-31` com causa `RET` ("Retorno do titular") e de `2024-02-21` a `2026-07-30` com `LCS` ("Licença com convocação de suplente (superior a 120 dias)") [V]. `DataFim` é o último dia em exercício (o titular volta no dia seguinte) e um exercício em curso não tem `DataFim` [V, inferido dos pares acima]. **Uma única chamada por legislatura dá os períodos de exercício de todos**, sem o equivalente às 513 chamadas de `/deputados/{id}/historico` da Câmara.

**Causas de fim de exercício** na 57ª legislatura (`exercicio=S`, 126 mandatos) [V]: sem causa (exercício em curso) 82; `RET` Retorno do titular 49; `LCS` Licença com convocação de suplente 27; `AFO` Afastamento do exercício 23; `REN` Renúncia 6; `LP` Licença Particular 4; `FAL` Falecimento 2; `TER` Término do mandato 1; `CAS` Cassação de registro/diploma pela Justiça Eleitoral 1; `LS` Licença saúde 1. O texto de `LCS` muda entre serviços: "(sup 120 dias)" na lista por legislatura e "(superior a 120 dias)" em `/senador/afastados` [V]; a sigla é o que é estável. `Exercicio` veio sempre como lista nesta chamada [V].

**Legislaturas.** `GET /plenario/lista/legislaturas` → `301` para `/dadosabertos/dados/ListaLegislatura.json` (46 KB): 58ª de `2027-02-01` a `2031-01-31`, eleição `2026-10-04`; 57ª de `2023-02-01` a `2027-01-31` [V]. `GET /plenario/legislatura/20270201` → 200 com só a 58ª [V], um link de fonte estável por legislatura.

**Afastados agora.** `GET /senador/afastados` → `301` para `/dadosabertos/dados/AfastamentoAtual.json` → 200, 56 KB, mesma forma com `Exercicios` e causas [V]. Útil para "em exercício hoje", junto de `GET /senador/lista/atual` (200, 129 KB, 81 em exercício) [V].

**Licenças curtas.** `GET /senador/5672/licencas?dataInicio=20230201` → 200 com `Licenca[]` (`DataInicio`, `DataFim`, `SiglaTipoAfastamento`, ex. `LICENCA_ATIVIDADE_PARLAMENTAR`, "Missão política ou cultural de interesse parlamentar") [V]. Uma chamada por senador; desnecessária para os indicadores, porque o mesmo motivo já vem em cada registro de votação (seção 4).

**Mandatos de um senador.** `GET /senador/4605/mandatos` repete o bloco de mandato e acrescenta `Partidos.Partido` com `DataFiliacao` [V]. Filiação ao longo do tempo: `/senador/{codigo}/filiacoes` [D].

**Detalhe.** `GET /senador/5672` traz `DadosBasicosParlamentar.DataNascimento`, `Naturalidade`, `UfNaturalidade`, `EnderecoParlamentar` e `Telefones` [V]. Nenhum desses entra no contrato.

## 4. Votações nominais

**Serviço.** `GET /votacao?dataInicio=AAAA-MM-DD&dataFim=AAAA-MM-DD` substitui os serviços depreciados [D]. Intervalo máximo de 1 ano quando é o único filtro (erro 400 citado na seção 2) [V]. Sem paginação: devolve uma lista JSON inteira [V].

| Ano (01/01 a 31/12; 2026 até 02/10) | Registros | Abertas | Secretas | Bytes |
|---|---|---|---|---|
| 2023 | 141 | 50 | 91 | 2.684.857 |
| 2024 | 95 | 58 | 37 | 1.834.790 |
| 2025 | 128 | 56 | 72 | 2.431.403 |
| 2026 | 59 | 19 | 40 | 1.121.810 |

[V] para todas as linhas. A primeira votação da legislatura é de 08/02/2023 [V]. Todas têm `casaSessao: "SF"` e **exatamente 81 votos** [V]: o serviço cobre o Plenário do Senado. Sessões conjuntas do Congresso (vetos, PLN) não aparecem aqui (seção 5). `informeLegislativo.siglaColegiado` às vezes vale `CCJ` ou `null` (3 casos em 2023, 1 em 2024, 2 em 2025): é o colegiado do informe da matéria, não onde se votou, porque esses registros também têm 81 votos e sessão deliberativa do Plenário [V].

**Campos por votação** (2025) [V]: `codigoSessaoVotacao` (único em todos os 423 registros), `sequencialVotacao`, `codigoSessao`, `dataSessao`, `siglaTipoSessao` (`DOR`, `DEX`), `descricaoVotacao`, `idProcesso`, `codigoMateria`, `identificacao` (ex. `MSF 6/2025`), `sigla`, `numero`, `ano`, `ementa`, `resultadoVotacao` (`A` aprovada, `R` rejeitada), `votacaoSecreta` (`S`/`N`), `totalVotosSim`, `totalVotosNao`, `totalVotosAbstencao`, `informeLegislativo` e `votos[]`.

**Campos por voto** [V]: `codigoParlamentar`, `nomeParlamentar`, `sexoParlamentar`, `siglaPartidoParlamentar` (partido **na data do voto**: Alan Rick aparece `UNIÃO` em junho de 2025 e é `REPUBLICANOS` na lista atual), `siglaUFParlamentar`, `siglaVotoParlamentar`, `descricaoVotoParlamentar`.

**Vocabulário de voto**, contagem de 2023 a 02/10/2026 [V]:

| `siglaVotoParlamentar` | `descricaoVotoParlamentar` | Registros | O que é |
|---|---|---|---|
| `Votou` | `null` | 12.708 | Votou em votação secreta; o voto não é publicado |
| `Sim` | `null` | 9.320 | |
| `P-NRV` | Presente – Não registrou voto | 5.218 | Presença registrada na sessão, sem voto nesta votação |
| `Não` | `null` | 2.594 | |
| `AP` | Atividade parlamentar | 2.391 | Ausência por atividade parlamentar registrada pela Casa |
| `LS` | Licença saúde | 875 | Licença para tratamento de saúde |
| `MIS` | Missão da Casa no País/exterior | 620 | |
| `Presidente (art. 51 RISF)` | `null` | 192 | Presidia a sessão; o presidente só vota em caso de empate ou votação secreta |
| `NCom` | Não Compareceu | 152 | Sem justificativa registrada |
| `LP` | Licença Particular | 128 | |
| `Abstenção` | `null` | 33 | |
| `NA` | Dispositivo não citado | 31 | Significado não documentado; aparece em PECs |
| `LAP` | Licença paternidade ou ao adotante | 1 | Só em 2026 |

Não há `Obstrução` nos votos individuais (o campo `qtdObstrucoes` existe na orientação, seção 5, e vale 0 nas amostras). A lista de códigos não está numa tabela de referência estável: `/plenario/lista/tiposComparecimento` existe [D, não chamado]. Um código novo (`LAP` apareceu em 2026) pode surgir a qualquer momento.

**Totais.** Em votação secreta, `totalVotosSim/Nao/Abstencao` vêm preenchidos (ex.: `MSF 6/2025`: 40, 1, 1) e os votos individuais dizem só `Votou`. Em votação aberta, os três totais vêm **`null`** em todos os 183 casos; é preciso contar a partir de `votos[]` [V].

**Duplicatas.** Agrupando por sessão e vetor de votos, 9 grupos de 2023 a 2025 têm dois registros. Em 6 deles o mesmo ato de votação aparece duas vezes: um registro com `sequencialVotacao` preenchido e a matéria do requerimento (`RQS 1039/2023`, `informeLegislativo` sem colegiado) e outro com `sequencialVotacao: null`, mesma sessão, **vetor de votos idêntico** e a matéria principal (`PEC 8/2021`) [V]. Os 6 pares: 6779/6780, 6796/6799, 6834/6835, 6950/6953, 7031/7034, 7045/7046. Os outros 3 grupos, com vetor igual e **ambos** com `sequencialVotacao` (6773/6777 PEC 45/2019; 6781/6782 PEC 8/2021 em dois turnos; 6995/6996 duas mensagens secretas) são votações distintas que coincidem; não são duplicatas. Quatro registros de 2023 têm `sequencialVotacao: null` sem par (6704, 6717, 6761, 6768); três deles correspondem, por data e placar, às votações 4038, 4082 e 4089 da orientação (seção 5), que não aparecem em `/votacao` com o sequencial [V]. Regra que os dados sustentam: **descartar o registro sem sequencial quando há, na mesma sessão, um com sequencial e o mesmo vetor de votos**; manter os demais.

**Fonte para link.** Não há página HTML por votação isolada. Três links respondem 200 [V]: a consulta da API por sessão `https://legis.senado.leg.br/dadosabertos/votacao?codigoSessao=461394` (JSON, 57 KB, todas as votações da sessão), a página pública da sessão `https://www25.senado.leg.br/web/atividade/sessao-plenaria/-/pauta/461394` (HTML) e a página da matéria `https://www25.senado.leg.br/web/atividade/materias/-/materia/167958` (HTML). A página do senador é `https://www25.senado.leg.br/web/senadores/senador/-/perfil/5672` (200) [V]; a API devolve o mesmo endereço com `http://`.

**Votações simbólicas.** `/votacao` só traz votações nominais e secretas. As decisões simbólicas do Plenário aparecem em `GET /plenario/resultado/mes/20250601` (200, 168 KB, 24 sessões, 122 itens de pauta) como itens de pauta com texto livre (`textoResultado`: "Resultado da matéria: Aprovado o projeto.") [V]. O `PL 419/2023`, aprovado em 10/06/2025, está lá e não está em `/votacao` [V]. Um item de pauta não é uma votação: um item pode conter vários requerimentos e emendas, e nada marca se a decisão foi simbólica ou nominal além da presença em `/votacao`. Contar votações simbólicas do Senado exigiria interpretar texto livre.

**Classificação por regra.** Uma tabela de 7 regras sobre `descricaoVotacao` normalizado (sem acento, minúsculo, espaços colapsados), primeira que casa vence, classificou os 423 registros de 2023 a 02/10/2026 sem sobra: `procedural` 17 (requerimentos, "Solicita urgência…", questão de ordem), `amendment` 46 (dispositivo, emenda ou expressão "destacado/destacada"), `final` 360 (emenda substitutiva votada como texto principal 21; mensagem ou ofício de indicação de autoridade 237; projeto, PEC, PLP, PLV 102) [V, script local sobre os arquivos baixados]. "Ressalvados os destaques" descreve a votação do texto principal, não do destaque; só "destacad[oa]" marca o destaque [V].

## 5. Orientação de bancada e do Governo

`GET /plenario/votacao/orientacaoBancada/{AAAAMMDD}/{AAAAMMDD}` → 200, um ano por chamada sem erro (856 KB em 2023) [V]. Cada item de `votacoes[]` tem `sequencialVotacao`, `codigoVotacaoSve`, `dataInicioVotacao`, `dataTerminoVotacao`, `siglaTipoMateria`, `numeroMateria`, `anoMateria`, `descricaoSessao`, `presidenteSessao`, `qtdVotosSim`, `qtdVotosNao`, `qtdVotosAbstencao`, `qtdObstrucoes`, `quorumInicial`, `quorumFinal`, **`orientacoesLideranca[]`** e `votosParlamentar[]` (nome, partido, voto, UF; **sem `codigoParlamentar`**) [V]. `votosLideranca` vem vazio em todas [V].

Exemplo real (`PLP 177/2023`, sequencial 4269, 25/06/2025): `{"dataHora":"2025-06-25T19:50:00","partido":"PDT","voto":"NÃO"}`, `{"partido":"NOVO","voto":"SIM"}`, `{"partido":"PL","voto":"LIVRE"}` [V].

| Ano | Votações na orientação | Com alguma orientação | Abertas em `/votacao` | Abertas com orientação `Governo` | Secretas com `Governo` |
|---|---|---|---|---|---|
| 2023 | 145 | 54 | 45 (por sequencial) | 37 | 0 |
| 2024 | 94 | 57 | 58 | 46 | 0 |
| 2025 | 126 | 52 | 54 | 40 | 0 |
| 2026 | 59 | 15 | 19 | 11 | 0 |

[V]. Valores da orientação do Governo nas abertas: `SIM` 94, `NÃO` 33, `LIVRE` 7 [V]. Somando todas as bancadas aparecem também `OBSTRUÇÃO` (5) e `null` (5) [V]. Nunca há mais de uma entrada `Governo` por votação [V]. Os nomes de bancada não são normalizados (`Republica`, `Republicanos`, `REPUBLICANOS`; `Banc Fem`, `B.Feminina`; `PODE` nos votos e `Podemos` na orientação) [V]; por isso o alinhamento ao partido deve sair da maioria dos votos do partido, como na Câmara, e não da orientação partidária.

**Junção.** `sequencialVotacao` liga as duas fontes: igual em 2024 a 2026 salvo 1 caso por ano [V]. Em 2023, 10 sequenciais só existem na orientação: 3 vetos e 2 PLN de sessões conjuntas do Congresso, 1 veto de abril e os 4 da seção 4 [V]. Os registros de `/votacao` sem sequencial ficam sem orientação conhecida.

**Conclusão:** o indicador "votou igual à orientação do Governo" existe para senadores, calculado só nas votações abertas em que o Governo orientou `SIM` ou `NÃO` (134 de 176 abertas no período, 76%), com `LIVRE`, `OBSTRUÇÃO`, `null` e ausência de orientação fora do denominador.

## 6. Proposições de autoria

`GET /processo?codigoParlamentarAutor={codigo}&dataInicioApresentacao=2023-02-01` → 200, sem paginação; com o autor informado, o limite de 1 ano não se aplica (Paulo Paim, 825: 622 processos de 01/02/2023 a 21/09/2026, 626 KB, 1,2 s) [V]. Sem o filtro de data vem a carreira inteira (4.338 processos desde 2001, 3,8 MB) [V].

Campos [V]: `id`, `codigoMateria`, `identificacao` (`PL 2036/2023`), `tipoDocumento`, `ementa`, `dataApresentacao`, `situacaoAtual`, `dataSituacaoAtual`, `tramitando`, `autoria` (texto: "Senador Alan Rick (UNIÃO/AC)"), `urlDocumento`, `casaIdentificadora`. Não há campo `sigla` separado (vem `null`); o tipo sai do prefixo de `identificacao` [V]. Tipos encontrados para Paim: `RQS` 287, `REQ` 202, `PL` 63, `PEC` 38, `R.S` 18, `PRS` 10, `VET` 3, `INS` 1 [V].

**Primeiro signatário.** A lista não marca a ordem. O detalhe `GET /processo/{id}` traz `autoriaIniciativa[]` com `ordem` e `codigoParlamentar` [V]. Em 6 de 6 processos sorteados (PEC 35/2023, PEC 41/2024, PLP 165/2026, PL 6383/2025, PEC 33/2025, PL 2036/2023) o primeiro nome do texto `autoria` era o autor de `ordem: 1` [V]; a amostra é pequena e o texto não traz código.

Página pública do processo: `https://www25.senado.leg.br/web/atividade/materias/-/materia/{codigoMateria}` (200 para 167958) [V].

**Autoria que não é do senador.** A consulta por autor também devolve emendas e substitutivos da Câmara a projetos do senador, como processos próprios com sufixo e autoria "Câmara dos Deputados": `PL 2434/2019 (Substitutivo-CD)`, `PL 1770/2024 (Emenda-CD)` (4 de 111 projetos de Paim; 1 de 112 de Alan Rick) [V]. Só contam como autoria do senador os processos cuja `identificacao` é exatamente `<sigla> <número>/<ano>` e cujo texto `autoria` começa por "Senador" ou "Senadora". Dos projetos dos tipos `PL`, `PLP`, `PEC`, `PDL` e `PRS`, 43 de 111 (Paim) e 78 de 112 (Alan Rick) têm mais de um autor, e só esses precisam do detalhe para saber o primeiro signatário [V].

## 7. Fotos

`UrlFotoParlamentar` vem como `http://www.senado.leg.br/senadores/img/fotos-oficiais/senador{codigo}.jpg`, que responde `301` para `https://…` e de novo `301` para `https://legis.senado.leg.br/senadores/fotos-oficiais/{codigo}` → `200 image/jpeg`, 241 KB, `cache-control: max-age=600, public` [V]. Também para quem já saiu (4605 → 200) e para suplentes que exerceram (6366 → 200) [V]. **Suplente que nunca exerceu não tem foto** (5918 → 404) [V]; para nós isso não importa, porque só publicamos quem exerceu.

Termos: "Todo o material produzido pelos veículos do Senado Federal pode ser baixado ou reproduzido livremente, desde que citada a respectiva fonte e não haja descaracterização do conteúdo" (`https://www12.senado.leg.br/assessoria-de-imprensa/como-obter-imagens-audios-e-textos`, 200, texto relido nesta sessão) [V]. Crédito "Foto: Agência Senado" e nenhuma alteração da imagem (A1 §4.4). A restrição a "propaganda política" da mesma Política de Uso fica na seção da TV Senado (A1 §4.4) [P, não rechecado].

## 8. Dados pessoais (AD-003)

Procurei `cpf` (qualquer caixa) nas respostas de `/senador/lista/atual`, `/senador/lista/legislatura/57`, `/senador/5672` e `/votacao` de 2025: **nenhuma ocorrência** [V]. O que existe e não entra no contrato: `DataNascimento`, `Naturalidade`, `UfNaturalidade`, `EnderecoParlamentar`, `Telefones`, `EmailParlamentar`, `NomeCompletoParlamentar`, `SexoParlamentar` [V]. Não precisamos deles para nada que esta feature publica.

**Atenção a saúde.** O código `LS` ("Licença saúde") num registro de votação diz que um senador estava em licença para tratamento de saúde naquele dia. Dado referente à saúde é dado sensível (LGPD, art. 5º, II) e o legítimo interesse não serve de base para dado sensível (guia da ANPD citado em A1). O Senado publica o código, mas republicar "licença saúde" em escala, por pessoa e por dia, é tratamento novo. A saída conservadora é agrupar `LS`, `LP` e `LAP` como "em licença" no que publicamos.

## 9. Confiabilidade

- Nenhuma chamada falhou nesta sessão (cerca de 50, todas 200 ou 301 esperado) [V].
- O Senado está migrando serviços (`/materia/*` → `/processo`, votações → `/votacao`) e já desligou serviços com `Sunset` em 01/02/2026 [V]. O ETL deve usar só os serviços não depreciados da OpenAPI de 02/10/2026 e falhar alto em `301` para serviço com `Sunset`.
- Os nomes de campo dos serviços novos são camelCase (`codigoParlamentar`); os antigos ainda vivos (`/senador/*`) são PascalCase com listas que viram objeto quando há um item só (`"Exercicio": {...}` em vez de `[...]`, observado em `Partidos.Partido`) [V].
- Inconsistência observada: Romário (5322) tem 3 registros `LS` em 10/12/2025 fora de qualquer `Exercicio` publicado [V]. A lista de exercícios e os registros de voto não concordam sempre; o denominador da participação deve vir de uma só delas.

## 10. O que fica de fora ou em aberto

- **Sessões conjuntas do Congresso** (vetos, PLN): os senadores votam, mas os votos estão em `/plenario/resultado/veto/*` e `/plenario/resultado/cn/{data}` [D, não chamados]. Fora desta feature.
- **Votações em comissão** (`/votacaoComissao/*`) [D]: fora, como na Câmara.
- **Votações simbólicas**: só como texto livre de itens de pauta (seção 4); fora até haver regra que não dependa de interpretação.
- **`NA` (Dispositivo não citado)**: significado não documentado; 31 registros. Tratar como "outro registro", sem interpretar.
- **Página HTML por votação**: não achei um link estável; o link de fonte de cada votação aponta para a consulta da API.

## 11. Chamadas citadas

Todas em 02/10/2026, com `Accept: application/json`:

- `/dadosabertos/v3/api-docs` · `/dadosabertos/senador/lista/atual` · `/dadosabertos/senador/lista/legislatura/{56,57,58}` e `/57?exercicio=S` · `/dadosabertos/senador/afastados` · `/dadosabertos/senador/{5672,999999}` · `/dadosabertos/senador/{5918,4605}/mandatos` · `/dadosabertos/senador/5672/licencas?dataInicio=20230201`
- `/dadosabertos/votacao?dataInicio={2023,2024,2025}-01-01&dataFim=…-12-31`, `2026-01-01` a `2026-10-02`, `2025-06-01` a `2025-06-30` e o intervalo inválido de 2023 a 2026
- `/dadosabertos/plenario/votacao/orientacaoBancada/{2023..2026}0101/{…}` e `20250601/20250630` · `/dadosabertos/plenario/votacao/nominal/2023` (depreciado)
- `/dadosabertos/plenario/resultado/20250610` e `/plenario/resultado/mes/20250601` · `/dadosabertos/plenario/lista/legislaturas` e `/plenario/legislatura/20270201`
- `/dadosabertos/processo?codigoParlamentarAutor={5672,825}` com e sem `dataInicioApresentacao` · `/dadosabertos/processo/{8360649, 8513795, 8743101, 9061253, 8974219, 8900982, 8419253}`
- `https://legis.senado.leg.br/senadores/fotos-oficiais/{5672,4605,6366,5918}` · `https://www12.senado.leg.br/assessoria-de-imprensa/como-obter-imagens-audios-e-textos` · `https://www12.senado.leg.br/dados-abertos/conjuntos?portal=Legislativo&grupo=plenario`
