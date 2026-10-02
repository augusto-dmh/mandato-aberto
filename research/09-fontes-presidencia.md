# Fontes da Presidência — medidas provisórias, vetos e projetos do Executivo

**Data:** 02/10/2026 · **Entrada:** AD-002 a AD-005, AD-014, AD-016 e AD-017 (`.specs/STATE.md` e o da worktree `contract-v3`), `05-grilling-escopo-v2.md` decisões 6, 8 e 9, `06-pesquisa-design-e-concorrentes.md` movimento 3 ("Do Planalto ao plenário"), `07-fontes-senado.md`, plano aprovado do `contract-v3` e do `etl-senado` · **Método:** chamadas reais com `curl` às APIs da Câmara (`https://dadosabertos.camara.leg.br`) e do Senado/Congresso (`https://legis.senado.leg.br/dadosabertos`), com `User-Agent` identificando o projeto, cerca de 1.500 requisições no total (1.201 delas aos votos por dispositivo de veto), no máximo uma a cada 0,7 s. Contagens saem de arquivos baixados nesta sessão e processados com Python.

Legenda: **[V]** verificado por chamada nesta sessão (URL e resposta citadas) · **[D]** afirmado pela documentação oficial (OpenAPI do serviço), sem teste de comportamento · **[NV]** não verificado.

## 1. Veredito

Os três tipos de ato da decisão 8 têm fonte estruturada, e o destino de cada um no Congresso também. A ligação "do Planalto ao plenário" é possível nas duas Casas, mas por caminhos diferentes:

- **Medidas provisórias:** a lista e o destino vêm do Congresso Nacional pelo serviço `/processo` do Senado, com um código de deliberação por MP (`APROVADO_NA_INTEGRA`, `APROVADO_PLV`, `PERDA_EFICACIA`, `REVOGADO`…) e a lei gerada. A Câmara publica as mesmas 241 MPs de 2023 a 2026, mas o campo de situação dela **não serve** para dizer o destino (seção 3.3). As votações nominais sobre a MP em cada Casa já estão nos dados de votação que o contrato v3 importa.
- **Vetos:** o Congresso publica cada veto com seus dispositivos e o resultado de cada um (`Mantido`, `Rejeitado`, `Prejudicado`, `Não Apreciado`). Os votos de cada parlamentar em sessão conjunta saem **por dispositivo**, separados em Câmara e Senado, mas **só com nome, partido e UF**, sem código do parlamentar. O casamento por nome + UF funcionou em 100% da amostra (seção 4.4). Os votos de **veto total** não têm serviço estruturado: só PDF.
- **Projetos do Executivo (PL, PLP, PEC):** a autoria "Poder Executivo" está nos arquivos anuais da Câmara; o destino ("Transformado em Norma Jurídica", arquivado, retirado) bate com o do Senado em 19 de 19 casos de 2023.

Nenhuma fonte diz **quem assinou** cada ato (titular ou vice em exercício): a autoria é sempre "Presidência da República" ou "Poder Executivo". O ato só pode ser atribuído ao **mandato presidencial** pela data, e o fim do mandato atual é **5 de janeiro de 2027**, não 1º de janeiro (seção 6).

Não há CPF nem dado pessoal além de nome, partido e UF em nenhuma resposta consultada.

## 2. As fontes

| Item | Fato | Certeza |
|---|---|---|
| Câmara, lista por tipo | `GET /api/v2/proposicoes?siglaTipo=MPV&ano=2023&itens=100` → 200, 52 itens, 25 KB | [V] |
| Câmara, arquivo anual | `GET /arquivos/proposicoes/csv/proposicoes-{ano}.csv` → 200; 52 MB (2023), 53 MB (2024), 90 MB (2025), 40 MB (2026); atualizado de madrugada (`last-modified: Fri, 02 Oct 2026 04:35:03 GMT`). Já está entre os seis arquivos anuais que o ETL baixa (`sources.camara.YEARLY`) | [V] |
| Câmara, autores | `GET /arquivos/proposicoesAutores/csv/proposicoesAutores-{ano}.csv` → 200, 42 MB (2023); também já baixado pelo ETL | [V] |
| Congresso, processos | `GET /dadosabertos/processo?sigla=MPV&ano=2023` → 200, lista JSON sem paginação, 52 itens, 43 KB, 0,4 s. Serve Senado **e** Congresso (`casaIdentificadora: "CN"`) | [V] |
| Congresso, detalhe | `GET /dadosabertos/processo/8349431` → 200, 78 KB: `deliberacao`, `deliberacoesMpv[]` por Casa, `normaGerada`, `processosRelacionados[]` (com `tipoRelacao: "VETO"`), `autoriaIniciativa[]` | [V] |
| Congresso, vetos do ano | `GET /dadosabertos/materia/vetos/{ano}` → `301` para `/dadosabertos/dados/ListaVetosAnoCN{ano}.json` → 200, 117 KB (2023). Arquivo estático versionado (`Metadados.Versao: 10/09/2026`) | [V] |
| Congresso, resultado por veto | `GET /dadosabertos/plenario/resultado/veto/materia/{codigoMateria}` → 200, 12 KB: dispositivos com `Situacao`, `PossuiVotos`, `TipoVotacao` (`Cédula`, `Painel`), `DataSessao` e PDFs do resultado | [V] |
| Congresso, votos por dispositivo | `GET /dadosabertos/plenario/resultado/veto/dispositivo/{codigo}` → 200, 55 KB: `Votacao.Camara.Voto[]` e `Votacao.Senado.Voto[]` com `NomeParlamentar`, `PartidoParlamentar`, `UfParlamentar`, `TipoVoto` | [V] |
| Congresso, sessão conjunta | `GET /dadosabertos/plenario/resultado/cn/20240509` → 200, 415 KB: `CodigoSessao: 26174`, `SiglaTipoSessao: "CNJ"`, itens da pauta com `idProcesso` e resultado | [V] |
| Limite | "Mais de 10 requisições por segundo estão sujeitas a receber o erro HTTP 429" (descrição da OpenAPI `/dadosabertos/v3/api-docs`, versão `4.1.3.99`) | [D] |
| Planalto | `https://www.planalto.gov.br/ccivil_03/...` **derruba a conexão** (`curl` exit 56) com o `User-Agent` do projeto e com o do ETL (`mandato-aberto-etl/...`); só responde a um agente de navegador. O ETL não usa o Planalto | [V] |
| Texto da MP no Senado | `urlDocumento` (`https://legis.senado.gov.br/sdleg-getter/documento?dm=9235724`) devolve 200 com uma página "Verificação de segurança" no lugar do documento | [V] |
| Texto da MP na Câmara | `urlInteiroTeor` da MPV 1154/2023 (`https://www.camara.leg.br/proposicoesWeb/prop_mostrarintegra?codteor=2479815`) → 200 `application/pdf`, 48 páginas, texto extraível ("MEDIDA PROVISÓRIA Nº 1.154, DE 1º DE JANEIRO DE 2023") | [V] |
| Páginas públicas | MP: `https://www.congressonacional.leg.br/materias/medidas-provisorias/-/mpv/155651` → 200. Veto: `https://www.congressonacional.leg.br/materias/vetos/-/veto/detalhe/16269` → 200 | [V] |

## 3. Medidas provisórias

### 3.1 Volume

| Ano | MPs (Câmara, `siglaTipo=MPV`) | Números | MPs (Congresso, `/processo?sigla=MPV`) |
|---|---|---|---|
| 2023 | 52 | 1154 a 1205, sem lacuna | 52 |
| 2024 | 81 | 1206 a 1286, sem lacuna | 81 |
| 2025 | 46 | 1287 a 1332, sem lacuna | 46 |
| 2026 (até 25/09) | 62 | 1333 a 1394, sem lacuna | 62 |
| **Total** | **241** | | **241** |

[V] nas duas fontes. A primeira é a MPV 1154/2023, de `2023-01-01T17:36` (organização dos ministérios). As duas contagens batem ano a ano. Em todas as 241, `autoria` = "Presidência da República" no Congresso e o autor na Câmara é o órgão "Poder Executivo" (`codTipo: 30000`) [V].

### 3.2 Destino, segundo o Congresso

`siglaTipoDeliberacao` da lista `/processo` [V]:

| Destino | 2023 | 2024 | 2025 | 2026 | Total |
|---|---|---|---|---|---|
| `APROVADO_PLV` (aprovada com alterações, via projeto de lei de conversão) | 9 | 1 | 13 | 6 | 29 |
| `APROVADO_NA_INTEGRA` (aprovada sem alteração) | 2 | 15 | 3 | 4 | 24 |
| `PERDA_EFICACIA` (prazo de 120 dias venceu sem votação) | 39 | 58 | 28 | 21 | 146 |
| `REVOGADO` (revogada por outra MP) | 2 | 7 | 2 | 1 | 12 |
| sem deliberação, `tramitando: "Sim"` | 0 | 0 | 0 | 30 | 30 |

- As 53 aprovadas (29 + 24) são exatamente as 53 com `normaGerada` preenchida (ex.: "Lei nº 14.600 de 19/06/2023" para a MPV 1154/2023) [V].
- Nenhuma MP de 2023 a 2026 foi rejeitada em votação. O enum precisa prever a rejeição mesmo assim: de 2019 a 2022 aparecem `REJEITADO_PLENARIO_CD` (1), `INADIMITIDA_URGENCIA` (1), `IMPUGNADO_PRESIDENCIA` (1, devolvida pelo presidente do Congresso) e `SEM_EFICACIA` (1) [V, `/processo?sigla=MPV&ano={2019..2022}`, 284 MPs].
- `/processo/tipos-decisao` lista 32 códigos de deliberação, com descrição oficial em português (ex.: `PERDA_EFICACIA` = "Perda de eficácia, em decorrência do término do prazo para sua votação no Congresso") [V]. O ETL pode publicar a descrição oficial ao lado do código.
- `situacaoAtual` é nula em 3 MPs que perderam a eficácia (MPV 1169, 1179 e 1204/2023) [V]: o destino tem de vir de `siglaTipoDeliberacao`, não da situação.
- O detalhe traz a deliberação em cada Casa: `deliberacoesMpv` = "Deliberar MPV na Câmara dos Deputados, 31/05/2023, Aprovado o Projeto de Lei de Conversão" e "Deliberar MPV no Senado Federal, 01/06/2023, …" [V], e o calendário constitucional (`itensCalendario`: prazo de deliberação `2023-02-02` a `2023-06-01`) [V].

### 3.3 A situação na Câmara não é o destino

No arquivo anual da Câmara, as MPs de 2023 aparecem como "Perdeu a Eficácia" (33), vazio (8), "Aguardando Encaminhamento" (8), "Transformado em Norma Jurídica" (2) e "Aguardando Despacho do Presidente da Câmara" (1) [V]. O Congresso diz 11 aprovadas e 39 sem eficácia para o mesmo ano. Exemplos:

- MPV 1154/2023 está como "Aguardando Despacho do Presidente da Câmara dos Deputados" no arquivo anual e como "Transformado em Norma Jurídica" no `GET /api/v2/proposicoes/2345493` do mesmo dia [V]: **o arquivo e a API da Câmara divergem**.
- MPV 1177/2023 está como "Aguardando Encaminhamento", com despacho "comunica promulgação da Lei nº 14.696/2023" [V].

A situação da Câmara descreve o andamento interno do papel na Câmara, não o resultado constitucional. **O destino da MP vem do Congresso**; a Câmara entra para o identificador da proposição e para as votações.

### 3.4 Votações sobre a MP

- **Câmara:** as votações em Plenário levam o id da MPV como prefixo e como `proposicao_id` em `votacoesProposicoes` (MPV 1154/2023: 11 votações de Plenário `2345493-31` a `2345493-64`, mais `2345493-9` da comissão mista, `siglaOrgao: "CN"`) [V, `GET /api/v2/proposicoes/2345493/votacoes`]. Nos arquivos de 2023 a 2026, 63 das 241 MPs têm ao menos uma votação de Plenário e 23 têm ao menos uma com placar nominal [V, cruzamento `votacoesProposicoes` × `votacoes`]. As outras perderam a eficácia sem ir a voto ou foram votadas só simbolicamente.
- **Senado:** `GET /dadosabertos/votacao?idProcesso=8349431` → 1 votação nominal (`codigoSessaoVotacao: 6704`, "Votação nominal do PLV nº 12/2023 e Pressupostos Constitucionais da MPV nº 1.154", 81 votos) [V]. O `idProcesso` da MP no Senado é o mesmo do processo do Congresso, e é a chave que o `etl-senado` usa como id de proposição (plano aprovado, door 4). Buscar pela sigla do PLV não funciona: `/votacao?sigla=PLV&numero=12&ano=2023` → lista vazia [V].
- **MP aprovada → veto:** o processo da MP lista o veto ao PLV em `processosRelacionados` com `tipoRelacao: "VETO"` (MPV 1154/2023 → `VET 17/2023`, processo 8477723) [V]. Na lista de vetos, o veto a um PLV aparece com `MateriaVetada.Sigla: "MPV"` (6 em 2023, 3 em 2025, 4 em 2026) [V].

## 4. Vetos

### 4.1 Volume

`/dadosabertos/materia/vetos/{ano}` [V]:

| Ano | Vetos | Totais | Parciais | Números | Dispositivos (`QuantidadeDispositivos`) | Em tramitação |
|---|---|---|---|---|---|---|
| 2023 | 49 | 5 | 44 | 1 a 49 | 813 | 4 |
| 2024 | 50 | 5 | 45 | 1 a 50 | 831 | 15 |
| 2025 | 51 | 9 | 42 | 1 a 51 | 437 | 38 |
| 2026 (até 02/10) | 55 | 10 | 45 | 1 a 55 | 448 | 54 |
| **Total** | **205** | **29** | **176** | | **2.529** | **111** |

O primeiro veto de 2023 (`VET 1/2023`) foi publicado em `2023-01-11` [V]. Cada veto traz a mensagem presidencial (`MSG 749/2023`) com um link para o Planalto que o ETL não consegue abrir (seção 2), a matéria vetada e a lei gerada (`Lei nº 14.790 de 29/12/2023`), `DataPublicacao`, `DataRecebimentoCongresso` e `DataSobrestacaoPauta` [V].

### 4.2 Resultado por dispositivo

`/plenario/resultado/veto/materia/{codigo}` para os 205 vetos (205 de 205 → 200) [V]:

| `Situacao` do dispositivo | Dispositivos | Significado |
|---|---|---|
| `Mantido` | 853 | o Congresso manteve o veto |
| `Rejeitado` | 357 | o Congresso derrubou o veto; o trecho vira lei |
| `Prejudicado` | 217 | a deliberação perdeu o objeto |
| `Não Apreciado` | 1.131 | ainda não deliberado |
| **Total** | **2.558** | |

- Todos os 1.210 dispositivos `Mantido` ou `Rejeitado` têm `PossuiVotos: "Sim"` e `TipoVotacao` `Cédula` (622) ou `Painel` (588); nenhum foi decidido sem voto nominal [V].
- Por veto: 42 com todos os dispositivos mantidos, 19 com todos derrubados, 24 mistos, 9 só com mantidos/derrubados/prejudicados, e 111 com ao menos um dispositivo não apreciado [V]. Os 111 batem com `EmTramitacao: "Sim"` da lista (4 + 15 + 38 + 54) [V].
- Veto total vem como um dispositivo único `xx.yy.000`, **sem `Codigo`** (29 casos) [V]. Os outros 2.529 dispositivos têm `Codigo` e `Identificador` (`49.23.001`), descrição (`§ 1º do art. 31`), texto vetado (`Conteudo`) e razão do veto (`RazaoVeto`) [V].
- Os votos nominais caíram em 11 datas de sessão conjunta de 2023-04-26 a 2026-05-21 [V].

### 4.3 Votos de cada parlamentar

`/plenario/resultado/veto/dispositivo/{codigo}` [V, dispositivo 43825, "§ 1º do art. 31" do VET 49/2023, Sessão Conjunta nº 4 de 09/05/2024, cédula]:

- Câmara: 454 votos (`Não` 414, `Sim` 37, `Branco` 3). Senado: 72 votos (`Não` 64, `Sim` 8). Situação: `Rejeitado`.
- A pergunta é a manutenção do veto: **`Sim` mantém, `Não` derruba**. A derrubada exige maioria absoluta em cada Casa (257 deputados e 41 senadores, CF art. 66 § 4º); no exemplo, 414 e 64 votos `Não` [V para os números; a regra constitucional, NV nesta sessão].
- Valores vistos: `Sim`, `Não`, `Abstenção`, `Branco`, `Art. 17` (quem preside) [V, dispositivos 43825, 42871 e 46051]. Só aparecem os que votaram: **ausência não tem registro**, e o motivo da ausência também não.
- Votação por painel tem o mesmo formato (42871: Câmara 353, Senado 55; 46051: Câmara 464, Senado 69) [V]. Um mesmo voto de painel sobre um destaque pode cobrir vários dispositivos, e a API repete o mesmo vetor de votos em cada um: contar dispositivos dá peso desigual a cada decisão (seção 4.6).
- **Veto total:** `/plenario/resultado/veto/{codigoVeto}` (17969, VET 3/2026, "Dosimetria de Penas", derrubado em 30/04/2026 por painel) devolve o veto e o resultado, mas nenhum voto [V]. Os votos só estão no PDF `https://legis.senado.leg.br/siscon/api/portalcn/pdfResultadoNominalDestaque/17969` (200, 3 páginas, texto extraível: placar por Casa — Câmara Sim 144, Não 318, Abst 5; Senado Sim 24, Não 49, Art. 17 1 — e uma linha por parlamentar, com partido truncado: "Republican", "Solidaried") [V]. Nove vetos totais tiveram voto nominal de 2023 a 2026 (12, 22, 34 e 38/2023; 30 e 38/2024; 2 e 31/2025; 3/2026) [V].

### 4.4 Identificar o parlamentar sem código

O voto em sessão conjunta não traz `deputado_id` nem `CodigoParlamentar`. Casamento testado no dispositivo 43825 [V]:

- Câmara: chave (nome parlamentar sem acento e em minúsculas, UF) contra `GET /api/v2/deputados?idLegislatura=57&itens=1000` (879 linhas): 454 de 454 casados com um único id.
- Senado: mesma chave contra `GET /dadosabertos/senador/lista/legislatura/57` (245 senadores), com a UF dos `Mandatos`: 72 de 72. A lista do Senado tem **uma chave ambígua**: "Fernando Carvalho"/SE corresponde aos códigos 5980 e 6384 [V]. O desempate precisa do período em exercício na data da sessão.
- O AD-003 proíbe CPF; nome + UF é o mesmo tipo de chave que o AD-003 já prevê para cruzar fontes.

O resultado do casamento nos 1.201 dispositivos com código e voto está na seção 4.5.

### 4.5 Volume dos votos por dispositivo

Coleta dos 1.201 dispositivos em andamento nesta sessão; os números entram na próxima revisão deste documento.

### 4.6 O que conta como uma decisão

- Um veto parcial é deliberado dispositivo por dispositivo; na cédula, cada parlamentar marca cada dispositivo [V, `TipoVotacao: "Cédula"`]. No painel, um destaque pode agrupar dispositivos [V, mesmo vetor repetido].
- O VET 14/2023 sozinho tem 397 dispositivos [V]. Um indicador por dispositivo seria dominado por poucos vetos grandes; um indicador por veto não depende de como o Congresso agrupou os destaques.

### 4.7 Os vetos nos dados das Casas

- Os arquivos de votação da Câmara **não** trazem votações de sessão conjunta: `GET /api/v2/votacoes?dataInicio=2024-05-09&dataFim=2024-05-09` → 0 votações [V]; nos arquivos anuais, as datas de sessão conjunta só têm votações do Plenário da própria Câmara (ex.: 2023-12-14, MPV 1187) [V].
- O serviço `/votacao` do Senado cobre só o Plenário do Senado (`casaSessao: "SF"`, sempre 81 votos; `07-fontes-senado.md` seção 4) [V naquela pesquisa]. A orientação de bancada do Senado inclui alguns vetos de sessão conjunta (3 em 2023, mesma pesquisa, seção 5) [V naquela pesquisa; não rechecado aqui].
- Conclusão: o voto de veto só existe na fonte do Congresso. Ele não entra no contrato v3 de nenhuma Casa (o `contract-v3` deixou sessões conjuntas fora do escopo).

## 5. Projetos do Executivo

### 5.1 Volume e autoria

Arquivos `proposicoesAutores-{ano}.csv` × `proposicoes-{ano}.csv`, autor "Poder Executivo" (`codTipoAutor: 30000`), proposições do próprio ano [V]:

| Ano | PL | PLP | PEC | Total |
|---|---|---|---|---|
| 2023 | 38 | 4 | 0 | 42 |
| 2024 | 21 | 5 | 1 | 27 |
| 2025 | 22 | 0 | 1 | 23 |
| 2026 (até 02/10) | 9 | 2 | 0 | 11 |
| **Total** | **90** | **11** | **2** | **103** |

- Nenhum desses projetos tem coautor: autoria única do Executivo [V].
- O mesmo autor assina, no mesmo período, milhares de `MSC` (mensagens: 156 em 2023, 1.428 em 2025) e `TVR` (outorgas de rádio e TV) [V]. Ficam fora: não são projetos de lei.
- O autor "Presidência da República" (outro nome do mesmo órgão) aparece em 41 `PLN` de 2023 e 1 `MCN` [V]. PLN são projetos de lei do Congresso sobre orçamento e créditos, votados em sessão conjunta: 136 de 2023 a 2026 [V, contagem por `siglaTipo` nos arquivos]. A decisão 8 fala em "projetos do Executivo"; os PLN são do Executivo, mas não estão na pergunta original (PL, PLP, PEC). Ver o plano.
- Ano do número ≠ data: o `PL 1/2023` foi apresentado em `2022-12-30` ("Política Nacional de Longo Prazo") e retirado pelo autor [V]. O mandato que responde por ele é o anterior. A atribuição tem de usar a data de apresentação.
- Desde 2019 o número do projeto é o mesmo nas duas Casas: `GET /dadosabertos/processo?sigla=PLP&numero=93&ano=2023` → 1 processo `PLP 93/2023`, `casaIdentificadora: "SF"`, `idProcessoCasaInicial: 8463488`, `siglaCasaIniciadora: "CD"`, `autoriaIniciativa[0].siglaTipo: "PRESIDENTE_REPUBLICA"` [V]. Na lista, porém, o Senado escreve `autoria: "Câmara dos Deputados"` para a fase revisora; a autoria do Executivo só aparece no detalhe [V].

### 5.2 Destino

- Câmara, situação do último status, 2023: "Transformado em Norma Jurídica" 19, "Arquivada" 9, "Aguardando Despacho do Presidente da Câmara (Chancela)" 9, "Aguardando Apreciação pelo Senado Federal" 4, "Retirado pelo(a) Autor(a)" 1 [V].
- Senado, `/processo?sigla={PL,PLP}&ano=2023&autor=Presidência da República`: 21 PL e 3 PLP chegaram ao Senado [V]. Cruzando pela identificação: os 19 transformados em norma na Câmara são os mesmos 19 com `normaGerada` no Senado; nenhum caso diverge [V]. Um `PL 4503/2023` aparece no Senado com autoria do Executivo e não aparece na Câmara com autor "Poder Executivo" [V]: o filtro por autor das duas Casas não é idêntico.
- Votações: nos arquivos de 2023 a 2026, 61 dos 103 projetos têm ao menos uma votação no Plenário da Câmara e 38 têm ao menos uma nominal [V]. No Senado, `/votacao?idProcesso=8463489` (PLP 93/2023) → 4 votações nominais [V].

## 6. Quem editou cada ato: o mandato presidencial

- Autoria nas fontes: "Presidência da República" (Congresso, Senado) ou "Poder Executivo" (Câmara), sempre o órgão [V]. O texto da MPV 1154/2023 extraído do PDF termina em "Brasília, 1º de janeiro de 2023" sem o nome de quem assinou [V]. Quando o vice-presidente está em exercício, o ato sai com a assinatura dele, mas nenhuma fonte estruturada diz isso [NV quanto a casos concretos]. **O ato é atribuível ao mandato, não à pessoa que assinou.**
- Início do mandato atual: sessão solene do Congresso em `2023-01-01` (`/plenario/resultado/cn/20230101` → `CodigoSessao: 25338`, `TipoSessao: "Sessão Solene"`) [V]. A primeira MP do mandato é de `2023-01-01T17:36` [V].
- Fim do mandato atual: a Emenda Constitucional nº 111/2021 alterou o `caput` do art. 82 da Constituição ("Art. 82, caput - Alteração") e determina que "As alterações efetuadas nos arts. 28 e 82 da Constituição Federal, relativas às datas de posse de Governadores, Vice-Governadores, do Presidente e do Vice-Presidente da República, serão aplicadas somente a partir das eleições de 2026" [V, `GET /dadosabertos/legislacao/34969552` e `https://legis.senado.leg.br/norma/34969552`]. A nova data de posse é **5 de janeiro** [NV: o texto novo do art. 82 não veio em nenhuma resposta desta sessão; a página do Senado com a Constituição atualizada é montada por script]. Consequência: quem for eleito em 2026 toma posse em **2027-01-05**, e os atos de 01 a 04/01/2027 ainda são do mandato 2023–2026. O pedido original desta pesquisa dizia 1º de janeiro.
- Eleição: primeiro turno em 2026-10-04 (`07-fontes-senado.md`, `ListaLegislatura`) [V naquela pesquisa]. O titular do mandato 2027–2030 ainda não existe em 02/10/2026.
- A legislatura (57ª: 2023-02-01 a 2027-01-31) e o mandato presidencial não coincidem: em janeiro de 2023 o Executivo já edita MPs que a 56ª legislatura recebe. A atribuição do ato ao mandato não depende da legislatura; a das votações continua pela data (contract-v3, AC 7).

## 7. Dados pessoais (AD-003)

- Nenhum campo com `cpf` nas respostas de `/processo`, `/materia/vetos`, `/plenario/resultado/veto/*` e `/plenario/resultado/cn/*` desta sessão [V].
- Os votos de sessão conjunta trazem só nome parlamentar, partido, UF e voto [V]. O ETL lê a lista de deputados e senadores para casar nomes; essas listas trazem `NomeCompletoParlamentar` e `SexoParlamentar` no Senado e e-mail na Câmara [V], que ficam fora da allowlist como já fazem `etl-camara` e `etl-senado`.
- Nome do titular do mandato presidencial: agente público no exercício do cargo, mesmo critério de nome de parlamentar (`01-pesquisa-juridica.md`, pergunta inicial cobre "presidente").

## 8. Confiabilidade

- Nenhuma chamada às APIs da Câmara e do Senado falhou nesta sessão, exceto o Planalto (exit 56) e o documento do Senado atrás de verificação de segurança [V].
- As listas de MP das duas Casas batem em 241 de 241 [V]. O destino tem uma fonte confiável (Congresso) e uma que diverge de si mesma (Câmara, arquivo × API) [V].
- A lista de vetos é um arquivo estático gerado pelo Senado com data de versão (`10/09/2026 10:20:21`); o resultado por veto é gerado na hora (`Versao: 02/10/2026 20:15:09`) [V]. O ETL deve preferir o resultado por veto ao `EmTramitacao` da lista quando divergirem; nesta sessão não divergiram (111 = 111) [V].
- O Senado está migrando serviços (`07-fontes-senado.md` seção 9). Os serviços de veto usados aqui não estão marcados como depreciados na OpenAPI de 02/10/2026 [V, `deprecated` ausente em `/plenario/resultado/veto/*` e `/materia/vetos/{ano}`]; `/materia/vetos/{ano}` responde com 301 para um arquivo, o mesmo padrão dos serviços migrados.
- Os dispositivos sem `Codigo` (vetos totais) não têm voto estruturado: lacuna da fonte, não do ETL [V].

## 9. O que fica de fora ou em aberto

- **Decretos** (decisão 8): fora.
- **Votos de veto total** só em PDF (nove vetos com voto nominal, entre eles o VET 3/2026). Extrair do PDF é possível (texto extraível, `pypdf` já é dependência do contrato v3, door 11), mas o partido vem truncado e o layout é de três colunas. Decisão do plano.
- **PLN** (orçamento e créditos, 136 em 2023–2026, votados em sessão conjunta, quase sempre simbolicamente) [contagem V; forma de votação NV].
- **Quem assinou** cada ato (titular ou vice): sem fonte estruturada.
- **Orientação do Governo nos vetos:** parcial no Senado (seção 4.7), não explorada.
- **Data exata da posse de 2027** (5 de janeiro): conhecida, não lida em resposta oficial nesta sessão.

## 10. Chamadas citadas

- Câmara: `/api/v2/proposicoes?siglaTipo=MPV&ano={2023..2026}&itens=100` · `/api/v2/proposicoes/2345493`, `/autores`, `/relacionadas`, `/votacoes` · `/api/v2/votacoes?dataInicio=2024-05-09&dataFim=2024-05-09` · `/api/v2/deputados?idLegislatura=57&itens=1000` · `/arquivos/{proposicoes,proposicoesAutores,votacoes,votacoesProposicoes}/csv/*-{2023..2026}.csv` · `https://www.camara.leg.br/proposicoesWeb/prop_mostrarintegra?codteor=2479815`
- Senado e Congresso: `/dadosabertos/v3/api-docs` · `/dadosabertos/processo?sigla=MPV&ano={2019..2026}` · `/dadosabertos/processo/{8349431,8463489}` · `/dadosabertos/processo/tipos-decisao` · `/dadosabertos/processo?sigla=PLP&numero=93&ano=2023` · `/dadosabertos/processo?sigla={PL,PLP,PEC}&ano=2023&autor=Presidência da República` · `/dadosabertos/votacao?idProcesso={8349431,8463489}` e `?sigla=PLV&numero=12&ano=2023` · `/dadosabertos/materia/vetos/{2023..2026}` · `/dadosabertos/plenario/resultado/veto/materia/{codigo}` (205 vetos) · `/dadosabertos/plenario/resultado/veto/dispositivo/{codigo}` (1.201 dispositivos) · `/dadosabertos/plenario/resultado/veto/17969` · `/dadosabertos/plenario/resultado/cn/{20230101,20240509}` · `/dadosabertos/senador/lista/legislatura/57` · `/dadosabertos/legislacao/lista?tipo=EMC&numero=111&ano=2021` · `/dadosabertos/legislacao/34969552` · `https://legis.senado.leg.br/norma/34969552` · `https://legis.senado.leg.br/siscon/api/portalcn/pdfResultadoNominalDestaque/17969` · `https://legis.senado.gov.br/sdleg-getter/documento?dm=9235724`
- Páginas: `https://www.congressonacional.leg.br/materias/medidas-provisorias/-/mpv/155651` · `https://www.congressonacional.leg.br/materias/vetos/-/veto/detalhe/16269` · `https://www.planalto.gov.br/ccivil_03/_ato2023-2026/2023/mpv/mpv1154.htm` (exit 56)
