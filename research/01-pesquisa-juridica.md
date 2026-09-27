# Pesquisa jurídica — viabilidade legal do Mandato Aberto

> **Status:** consolidado para revisão dos mantenedores
> **Data:** 26/09/2026 (8 dias antes do 1º turno de 04/10/2026; 2º turno em 25/10/2026)
> **Pergunta:** o que podemos e o que não podemos fazer ao publicar dados de mandato de deputados federais, senadores e presidente, com fotos oficiais e, no futuro, resumos por IA e cruzamento "promessa × voto"?
> **Método:** quatro frentes de pesquisa em paralelo (dados e licenças; eleitoral; civil, imagem e honra; benchmark de projetos), cada uma com fontes primárias baixadas e lidas quando possível e cada afirmação marcada por grau de certeza. Os relatórios completos estão em `juridico-anexos/`. Este documento é a síntese e as decisões que decorrem dela.
> **Aviso:** pesquisa feita por IA a partir de fontes públicas. Não substitui parecer de advogado. Os pontos marcados como "confirmar" precisam de leitura humana da fonte antes do lançamento.

---

## 1. Veredito

**O projeto é juridicamente viável e o núcleo dele (dados oficiais de mandato, com fonte e linguagem descritiva) está em terreno firme.** Três camadas normativas independentes protegem exatamente o que queremos fazer:

1. **Lei de Acesso à Informação** obriga Câmara, Senado e TSE a publicar votações e presença em formato aberto e processável por máquina (art. 8º, §3º, III). Reutilizar, cruzar e republicar é o uso previsto pela lei, não tolerado.
2. **Lei das Eleições** diz que "a divulgação de atos de parlamentares e de debates legislativos, desde que não se faça pedido de votos" não é propaganda (art. 36-A, IV). O TSE chama isso de "indiferente eleitoral" e de "dever constitucional de prestação de contas".
3. **STF** decidiu que não existe direito ao esquecimento sobre fatos verídicos licitamente publicados (Tema 786, 2021), que biografia não exige autorização do retratado (ADI 4815, 2015) e que nome e remuneração de servidor são públicos (Tema 483, 2015). Voto nominal de parlamentar é o caso mais forte possível de informação de interesse público.

**Em vinte anos de projetos semelhantes no Brasil (Ranking dos Políticos, Excelências da Transparência Brasil, Serenata de Amor, Deputômetro), nenhuma das quatro frentes encontrou um único processo contra site por reproduzir fielmente votação, presença ou proposição oficial.** O litígio real mira adjetivo, acusação sem lastro e erro factual, não tabela.

O risco jurídico do projeto, portanto, não está no dado. Está em quatro lugares:

| Onde | Risco | Como se blinda |
|---|---|---|
| **Erro de dado** (bug de ETL, categoria mal rotulada) | Responsabilidade civil por culpa; direito de resposta | Link para fonte em cada dado, testes de consistência, canal de correção com prazo, changelog público |
| **Rótulo valorativo** ("traiu", "faltou ao trabalho", "pior deputado") | Ofensa à honra; propaganda negativa em período eleitoral | Linguagem descritiva por padrão; adjetivos só com condenação ou acusação formal |
| **"Promessa × voto"** | Distorção da premissa (o que o político disse) | Citação literal com link e data; escala neutra; espaço de resposta do parlamentar |
| **IA** | Alucinação atribuindo voto ou fala inexistente; vedações do TSE em 2026 | Grounding nos dados estruturados, validação determinística, rótulo em cada resumo, nenhuma mídia sintética de pessoa real, nenhuma recomendação de voto |

---

## 2. As decisões que a pesquisa força

### 2.1 Pessoas físicas até 26/10/2026; associação só depois, e com cautela

As frentes divergem aqui e a divergência é real, não erro de pesquisa.

- **Frente eleitoral (A2):** a Lei 9.504 veda propaganda eleitoral, "ainda que gratuitamente", em sítios "de pessoas jurídicas, com ou sem fins lucrativos" (art. 57-C, §1º, I). Para **pessoa natural**, a Res. TSE 23.610 diz que manifestação espontânea "mesmo que sob a forma de elogio ou crítica" **não é propaganda** (art. 28, §6º), e o único limite é honra e fato sabidamente inverídico (art. 27, §1º). Em abril de 2026 o TSE equiparou até MEI a pessoa jurídica para esse fim. Conclusão: enquanto houver candidatos no ar, o abrigo mais seguro é o de pessoa física identificada.
- **Frentes civil (A3) e benchmark (A4):** todos os projetos duráveis operam com CNPJ; associação concentra a defesa e protege o patrimônio pessoal contra assédio judicial (Bia Kicis moveu 11 ações contra comunicadores; Congresso em Foco levou 50 processos). Recomendam associação antes da camada "promessa × voto", da IA em escala ou de qualquer receita.

**Decisão recomendada:** lançar e operar como pessoas físicas identificadas até o fim do 2º turno. Depois da diplomação, avaliar associação sem fins lucrativos (ou abrigo em uma existente, como projeto de entidade de dados abertos) e, se constituída, reavaliar o enquadramento antes das eleições de 2028. Enquanto pessoa física: domínio, hospedagem e redes do projeto em nome dos mantenedores; página "Quem somos" com nomes completos e contato (o anonimato é vedado em campanha, art. 57-D).

### 2.2 Lançar antes ou depois de 04/10?

O direito material é o mesmo em qualquer data. O que muda antes da eleição é o volume e a velocidade do litígio: qualquer candidato tem legitimidade para representação em rito de 48 horas e direito de resposta em 24/72 horas, e há janelas específicas para IA (1 a 5/10 e 22 a 26/10).

**Lançar antes é defensável se, e só se:** conteúdo exclusivamente descritivo com fonte em cada dado, zero anúncio pago, zero IA gerando conteúdo novo sobre candidatos, responsáveis identificados, canal de correção funcionando. Qualquer módulo com metodologia ainda instável (por exemplo, o alinhamento com governo) fica para depois de 25/10.

### 2.3 O que nunca fazer (lista curta, sem exceção)

- **Impulsionamento pago** de qualquer página que mencione candidato, até 25/10, mesmo "neutro". Para pessoa natural não candidata o TSE veda qualquer impulsionamento de conteúdo eleitoral, e considera ilícito "pelo meio, não pelo conteúdo" (decisões de 18/12/2025, 25/06/2026 e 22/09/2026).
- **Pedir voto ou não voto**, inclusive por equivalentes: "vote consciente em quem votou a favor de X", "não reeleja quem faltou".
- **Receber dinheiro ou vantagem** de partido, candidato, campanha ou terceiro interessado (Res. 23.610, art. 29, §8º).
- **Exibir ou persistir CPF.** A API da Câmara devolve o CPF em claro, contrariando o próprio FAQ do portal e o Ato da Mesa 45/2012. Fazer o cruzamento com o TSE por nome civil + data de nascimento + UF; se precisar de CPF, só em memória ou HMAC com chave secreta.
- **Gerar imagem, áudio ou vídeo sintético de pessoa real**, mesmo rotulado, mesmo satírico (Res. 23.610, art. 9º-C, §1º; TSE 08/05/2026: ilícito objetivo).
- **Qualquer função de IA que ranqueie, recomende ou opine sobre em quem votar**, inclusive em resposta a pergunta do usuário (art. 28, §1º-C, novo em 2026). O site é tecnicamente "provedor de aplicação" mesmo sendo de pessoa natural amadora (art. 37, XVIII).
- **Enquete ou votação de usuários** sobre parlamentares candidatos até 26/10 (Lei 9.504, art. 33, §5º).
- **Republicar pesquisa eleitoral** sem número de registro no PesqEle: quem compartilha responde.
- **Vocabulário de pesquisa** em qualquer índice: "aprovação", "intenção de voto", "favorito", "líder", "chance de reeleição".
- **Recontextualizar a foto oficial**: montagem, filtro, legenda depreciativa, publicidade adjacente.
- **Exibir dado sensível extra-mandato** que o TSE publica: cor/raça, religião, endereço, telefone.

---

## 3. Bases legais, por tema

### 3.1 Proteção de dados (LGPD)

- **Base legal do site:** legítimo interesse (art. 7º, IX) combinado com a regra dos dados de acesso público (art. 7º, §3º) e do tratamento posterior para finalidade compatível (§7º). Os dois guias da ANPD lidos (Poder Público, 2022; Legítimo Interesse, 2024) sustentam essa leitura e afirmam que dados de agentes públicos se submetem "ao escrutínio da sociedade" como "decorrência natural do exercício da atividade pública".
- **Não depender da exceção jornalística** (art. 4º, II, "a"). É defensável para não jornalistas, mas não há orientação da ANPD nem decisão que confirme. Usar como argumento subsidiário.
- **Partido é dado de mandato, não opinião política sensível.** Literalmente a sigla é "filiação a organização de caráter político" (art. 5º, II), mas é condição de elegibilidade, integra o registro de candidatura, é publicado por lei pela própria Câmara e o art. 11, §1º condiciona a proteção reforçada à possibilidade de dano. O TSE distinguiu filiado comum de mandatário em 2021 e 2022. Onde o risco existe: **inferir** opinião política. "Alinhamento com o governo: 87%" é estatística sobre atos oficiais e deve ser apresentado assim, com metodologia.
- **Obrigações que ficam:** princípios do art. 6º, transparência do art. 9º, direitos do titular do art. 18 (o parlamentar pode pedir correção). Resolução CD/ANPD 2/2022 enquadra pessoas naturais como agentes de pequeno porte: sem encarregado, só canal de comunicação.
- **Entregáveis:** teste de balanceamento de 1 a 2 páginas (Anexo II do guia de Legítimo Interesse) versionado no repositório; página "Sobre os dados e privacidade" com fontes, finalidade, base legal, controladores e e-mail; analytics sem cookie e sem identificador (elimina banner).

### 3.2 Licenças dos dados e das fotos

| Fonte | Licença dos dados | Fotos | Certeza |
|---|---|---|---|
| Câmara | FAQ: "não há qualquer restrição", inclusive comercial; sem licença nominal | Banco de Imagens sob CC BY, crédito "Fotógrafo/Câmara dos Deputados"; foto de perfil da API é da mesma autoria institucional (inferência) | Primária, exceto extensão à foto de perfil |
| Senado | "no máximo citar proveniência"; sem licença nominal | Reprodução livre com crédito e sem alteração; Política de Uso veda uso comercial e "propaganda política" na seção da TV Senado (texto ambíguo) | Primária |
| TSE | CC BY 4.0 (Portaria 93/2021) | Foto de candidato é obra do fotógrafo do candidato; TSE publica por força de lei. Usar só como fallback | **Só secundária: todo o domínio tse.jus.br devolveu 403** |

- Textos de proposições, ementas e registros de votação **não têm proteção autoral** (Lei 9.610, art. 8º, IV e V). Nenhuma licença é sequer necessária; a atribuição é boa prática.
- Ingestão: arquivos anuais em lote para carga histórica; API com pausa, User-Agent identificando o projeto e contato, backoff em 429/503. Rate limit da Câmara existe (issue #208) mas nenhum número oficial foi localizado.
- Rodapé: "Dados: Câmara dos Deputados, Senado Federal e TSE (Dados Abertos). Fotos: Câmara dos Deputados / Agência Senado / TSE." Link para a fonte primária em cada votação.

### 3.3 Eleitoral (o que muda em 2026)

Fonte primária lida: Lei 9.504/1997 no Planalto e Res. TSE 23.610/2019 compilada com as alterações da **Res. 23.755/2026** (aprovada em 02/03/2026).

- **Conceito de propaganda:** exige pedido explícito de voto (não limitado a "vote em", pode ser inferido do conjunto), meio vedado ou quebra de igualdade. Critério objetivo e contextual: "conteúdo, linguagem, destinatários e contexto" (tese de 01/09/2026).
- **Crítica × imputação factual**, a decisão mais útil para o projeto (TSE, 14/08/2026): "a crítica política exprime avaliação, associação retórica ou juízo de valor, enquanto a imputação factual atribui conduta concreta e objetivamente verificável, sujeita a juízo de veracidade e à exigência de lastro mínimo." Um site de dados é 100% imputação factual; sua proteção depende inteiramente do **lastro** (link) e da **exatidão**.
- **Propaganda negativa** tem três requisitos alternativos: pedido de não voto, desqualificação da honra ou fato sabidamente inverídico. "Fato sabidamente inverídico" exige falsidade "verificável de plano". "Questionar o desempenho dos candidatos no exercício dos cargos" é "corriqueiro no debate eleitoral".
- **Direito de resposta eleitoral** (art. 58) está ativo desde as convenções e pode ser pedido a qualquer tempo enquanto o conteúdo estiver no ar. O TSE o trata como "absolutamente excepcional", só para "fato chapadamente inverídico" ou grave ofensa pessoal. Para dado com fonte oficial, o padrão é indeferir.
- **Ranking não é pesquisa eleitoral.** Pesquisa (art. 33) e enquete (Res. 23.600, art. 23) pressupõem levantamento de opinião de eleitores. Nenhuma decisão tratou índice de desempenho parlamentar como pesquisa. O risco surge só com vocabulário de pesquisa ou com votação de usuários.
- **IA em 2026** (Res. 23.755):
  - Rotulagem obrigatória de conteúdo sintético na propaganda (art. 9º-B), estendida "no que couber" a todo "conteúdo político-eleitoral" fora da campanha, inclusive para "pessoas e entidades responsáveis pela criação e divulgação" (art. 3º-C). A incidência direta sobre nós é discutível; rotular custa zero e fecha a discussão.
  - **Janela de restrição:** entre 72 horas antes e 24 horas depois de cada turno é vedado publicar conteúdo sintético novo que use "imagem, voz ou manifestação" de candidato, mesmo rotulado (art. 9º-B, §3º-A). "Manifestação" é vago: um resumo de IA que parafraseie discurso pode ser enquadrado. Congelar geração de resumos sobre candidatos de 01 a 05/10 e de 22 a 26/10.
  - **Vedação a sistemas de IA** que ranqueiem, recomendem, sugiram ou priorizem candidatos, ou emitam opinião e preferência eleitoral, "inclusive por meio de respostas automatizadas" (art. 28, §1º-C).
  - **Inversão do ônus da prova** em representações sobre IA (art. 9º-I): o representado deve demonstrar como e em que etapas a IA foi usada e a veracidade da informação. Guardar prompt, modelo, data e fontes de cada resumo.
  - Deepfake é ilícito objetivo, mas a tese de 01/09/2026 exige que o conteúdo seja propaganda e tenha "realismo ou verossimilhança"; avatares estilizados sem semelhança com pessoa real ficam fora.
- **Código Eleitoral** (arts. 323 a 326): crimes exigem dolo ("fatos que sabe inverídicos"). Erro de dado de fonte oficial com correção rápida não preenche o tipo. Difamação admite exceção da verdade contra funcionário público no exercício da função, e parlamentar é funcionário público para fins penais.
- **Dia da eleição:** não enviar push ou newsletter com nomes de candidatos (Lei 9.504, art. 39, §5º, III é lei penal).

### 3.4 Imagem, honra e responsabilidade civil

- **Foto oficial + nome + dados de mandato** em site informativo gratuito: uso lícito sem autorização. O Código Civil, art. 20, só permite proibição se atingir a honra ou tiver fim comercial; a ADI 4815 deslocou o eixo de "autorização prévia" para "controle posterior de abusos"; a Súmula 403 do STJ só presume dano em uso comercial; o STJ afasta indenização por foto que ilustra fato de interesse público (AgInt no AREsp 674.270, 2022).
- **Três camadas de conteúdo, três níveis de risco:**
  1. Fato com fonte oficial ("votou Sim no PL X em DD/MM"): núcleo protegido. Risco só em erro ou em omissão que torne a informação enganosa (exibir "faltou 12 sessões" sem dizer que 10 são ausências justificadas registradas pela Casa).
  2. Métrica derivada ("votou com o governo em 82%"): fato construído por nós. A defesa não é "está no site da Câmara", é "está na metodologia publicada e é reproduzível". Sem metodologia pública, número vira opinião disfarçada.
  3. Juízo de valor ("não cumpriu a promessa"): opinião protegida (ADPF 130, ADI 4451) desde que a premissa seja verdadeira e citada literalmente. O caso Jones Manoel × Kim Kataguiri (TJDFT, 05/2026, R$ 30 mil, recorrível) mostra o limite: o juiz não condenou a crítica, condenou a **falsificação do que o deputado disse**.
- **Erro de ETL:** regime subjetivo (CC, arts. 186 e 927); régua do STF é dolo ou culpa grave (Tema 995, 2023). Correção rápida mitiga mas não imuniza (Lei 13.188, art. 2º, §3º). Sem correção ou com recusa em corrigir, vira culpa grave.
- **Lei 13.188/2015 (direito de resposta civil)** provavelmente alcança site de pessoa física ("inexistindo pessoa jurídica constituída, a quem por ele responda", art. 3º). Adotar voluntariamente: recebe, avalia, publica a resposta ou justifica em 7 dias, com o mesmo destaque do dado contestado. É a defesa mais forte contra dano moral porque demonstra ausência de animus.
- **Marco Civil, Tema 987 (STF, 26/06/2025):** não alcança conteúdo próprio. Só importa se abrirmos comentários: ofensa a parlamentar por usuário continua no art. 19 (só responde após ordem judicial); demais ilícitos passam a notificação extrajudicial. **Não abrir comentários no MVP.** Sugestões de correção via formulário privado, não publicadas.
- **PL 2338/2023 (marco legal da IA):** não é lei. Em 26/09/2026 aguarda parecer do relator na Comissão Especial da Câmara. Se aprovado no texto do Senado, o efeito prático para nós é dever de indicar texto gerado por IA e regime do Código Civil, que já vale hoje.
- **Registro.br** exibe nome completo, CPF parcial e e-mail do titular pessoa física; não há serviço de privacidade WHOIS para .br. Fontes divergem sobre endereço. Fazer uma consulta WHOIS real do domínio antes de publicar, usar e-mail dedicado, considerar .org com privacidade para o site público.

---

## 4. O que os projetos que sobreviveram fazem (padrões do benchmark)

1. Dado oficial, só dado oficial, e diz de onde veio, com período e exclusões.
2. Fórmula ou critério publicado **antes** da nota. Quanto mais o número é opinião, mais detalhada a metodologia.
3. Vocabulário defensivo: "suspeita" e não "irregularidade" (Serenata); "responder a processo não implica culpa" (Congresso em Foco); processo só conta após condenação (Ranking); "editorial decision, rather than an uncontestable approach" (mySociety).
4. Canal de correção visível: "Erramos?" com nota de correção (Lupa), Ouvidoria (Ranking), resposta pública a reclamação de parlamentar (mySociety), retratação pública de análise falha (GovTrack, 2024).
5. Independência e financiamento declarados: "sem vínculo com partidos", "não aceitamos dinheiro público".
6. Licença aberta no que é próprio, repasse no que é alheio.
7. IA com humano no circuito e métricas do modelo publicadas (Elas no Congresso). Ninguém da amostra rotula item a item; nós devemos, por causa da Res. 23.755.

---

## 5. Checklist antes do lançamento

**Páginas obrigatórias**
- [ ] "Quem somos": nomes completos dos mantenedores, cidade, e-mail de contato.
- [ ] "Metodologia e fontes": fórmula de cada índice, universo de votações, tratamento de abstenção, obstrução e ausência justificada, data de corte, links para APIs e portais.
- [ ] "Sobre os dados e privacidade": campos tratados, finalidade, base legal (art. 7º, IX e §3º), controladores, canal para exercício de direitos, política de analytics.
- [ ] "Correções e direito de resposta": como reportar erro, prazo de triagem (48 h), prazo de correção ou publicação da resposta (7 dias), changelog público.
- [ ] Rodapé em toda página: atribuição das fontes e das fotos; "Este site não apoia nem se opõe a candidaturas, partidos ou federações. Todos os dados provêm de fontes oficiais indicadas em cada página. Não recebe recursos de partidos, candidatos ou campanhas."

**Produto**
- [ ] Link para a fonte oficial em cada votação, proposição e registro de presença.
- [ ] Categorias oficiais da Casa para ausência (justificada, licença, missão), nunca "faltou".
- [ ] Alinhamento só onde há orientação registrada; "art. 17" e votação simbólica sem posição atribuída.
- [ ] Lista interna de termos proibidos na interface: adjetivos, "mentiu", "traiu", "corrupto", "aprovação", "intenção de voto", "favorito", "chance de reeleição", "pesquisa".
- [ ] Foto oficial sem recorte, filtro ou legenda valorativa; crédito "Foto: Câmara dos Deputados" / "Foto: Agência Senado"; servir por cache da URL oficial em vez de cópias no repositório público.
- [ ] Sem CPF em nenhum lugar (nem no banco). Sem telefone, e-mail, endereço, cor/raça, religião.
- [ ] Analytics sem cookie e sem identificador individual.
- [ ] Sem comentários, sem enquete, sem newsletter nominal no dia da eleição.

**Operação**
- [ ] Snapshot datado (hash) das respostas brutas das APIs em cada coleta.
- [ ] Testes de consistência no ETL (presença nunca maior que sessões; soma de votos igual ao quórum registrado).
- [ ] Teste de balanceamento LGPD (1 a 2 páginas) no repositório.
- [ ] Zero anúncios pagos e zero automação de engajamento até 25/10/2026.
- [ ] Política de doações publicada, se houver: recusa a partidos, candidatos, filiados, campanhas e empresas.
- [ ] Contato de advogado com experiência em eleitoral ou liberdade de expressão identificado de antemão (Abraji, ARTIGO 19 e Instituto Vladimir Herzog mantêm redes de apoio). Prazos eleitorais são de 24 a 48 horas e correm em fim de semana.

**Quando entrar IA**
- [ ] Rótulo em cada resumo: "Texto gerado por IA [modelo] em DD/MM/AAAA a partir de [fontes]. Pode conter erros; confira os dados originais. Reporte um erro."
- [ ] Pipeline "dados primeiro, texto depois": o modelo só recebe registros verificados; validação determinística pós-geração compara entidades do texto com a base; log guardado.
- [ ] Nenhuma mídia sintética de pessoa real. Nenhuma função que recomende, ranqueie ou opine sobre voto. Chatbot, se existir, nunca simula candidato e responde com dados e links.
- [ ] Congelar geração de conteúdo novo sobre candidatos de 01 a 05/10 e de 22 a 26/10/2026.
- [ ] Juízo final ("compatível / incompatível / relação indireta / não avaliável") por regra escrita, com revisão humana do que for "incompatível" e registro de quem revisou.

**Quando entrar "promessa × voto"**
- [ ] Promessa só com citação literal, fonte e data (plano de governo no TSE, transcrição com link, post arquivado). Nunca parafraseada por IA.
- [ ] Voto pelo registro nominal oficial, com link.
- [ ] Verbo descritivo: "votou em sentido oposto à declaração de [data]", não "descumpriu" ou "mentiu".
- [ ] Link para a justificativa oficial do voto ou discurso de encaminhamento, quando existir.
- [ ] Campo "resposta do parlamentar" publicado ao lado.
- [ ] Declarar cobertura: "não encontramos promessas deste parlamentar em fontes públicas" é diferente de "não prometeu nada".

---

## 6. Confirmar em navegador antes de publicar (o ambiente de pesquisa foi bloqueado)

Todo o domínio `tse.jus.br` devolveu HTTP 403 para as frentes A1, A3 e A4. A frente A2 conseguiu ler a Res. 23.610 compilada e os ementários do TSE com cabeçalhos de navegador, mas os itens abaixo continuam sem leitura humana da fonte primária:

1. **Campo "Licença" do dataset `candidatos-2026`** em dadosabertos.tse.jus.br (esperado: CC BY 4.0). Capturar tela e guardar no repositório com data.
2. **Página "Portal de Dados Abertos do TSE"** e Portaria TSE 93/2021.
3. **Res. TSE 23.755/2026**, texto oficial, especialmente art. 28, §1º-C (IA que ranqueia candidatos) e art. 9º-B, §3º-A (janela de 72 h): https://www.tse.jus.br/legislacao/compilada/res/2026/resolucao-no-23-755-de-2-de-marco-de-2026
4. **Ato da Mesa 45/2012 da Câmara**, art. 27 (lista de dados pessoais restritos, inclui CPF): só resumo foi obtido.
5. **Se a foto de perfil `bandep` da Câmara está formalmente sob a licença CC BY do Banco de Imagens**: a página sobre fotografia parlamentar devolveu 404; a extensão é inferência.
6. **WHOIS real do domínio** que vamos usar, para ver o que o Registro.br expõe de pessoa física.
7. **Andamento do PL 2338/2023** depois de 22/09/2026.

Itens que **nenhuma frente encontrou** e que podem existir sem indexação: decisão de TSE ou TRE contra site de dados parlamentares; decisão tratando ranking de desempenho como pesquisa eleitoral; orientação da ANPD sobre filiação partidária de mandatários; jurisprudência pós-Tema 987 para sites pequenos sem fins econômicos.

---

## 7. Anexos

| Arquivo | Frente | Palavras | Destaques |
|---|---|---|---|
| `juridico-anexos/A1-dados-lgpd-licencas.md` | LGPD, LAI, licenças, fotos, esquecimento | 7.200 | Guias da ANPD lidos em PDF; FAQ da Câmara; Política de Uso do Senado; Tema 786 com tese literal |
| `juridico-anexos/A2-eleitoral.md` | Lei 9.504, Res. 23.610/23.755, jurisprudência TSE 2024-2026, Código Eleitoral | 12.100 | Única frente que leu o TSE na fonte; 40+ ementas com número e data; mapa de risco em três níveis |
| `juridico-anexos/A3-civil-imagem-honra.md` | Imagem, honra, responsabilidade civil, Marco Civil, IA, forma jurídica, precedentes | 8.300 | Tese do Tema 987 literal do PDF do STF; PL 2338 lido no autógrafo; tabela de casos reais |
| `juridico-anexos/A4-benchmark-projetos.md` | 20 projetos nacionais e internacionais | 5.300 | Tabela de forma jurídica, termos, metodologia, correção, fotos, IA e litígios; citações dos disclaimers |

Cada anexo termina com "Recomendações práticas" e "Não consegui verificar". Ver também `~/projects/promessas-x-acoes/docs/pesquisa-de-viabilidade.md` (22/09/2026) para fontes de dados e arquitetura.
