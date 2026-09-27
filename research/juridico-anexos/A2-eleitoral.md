# Frente 2 — Legislação eleitoral e propaganda na internet

**Data da pesquisa:** 26/09/2026 (8 dias antes do 1º turno de 04/10/2026; 2º turno 25/10/2026).
**Projeto analisado:** site mantido por duas pessoas físicas (sem CNPJ, sem fins lucrativos, sem vínculo partidário) que exibe dados de mandato de deputados federais, senadores e presidente (votações nominais, presença, alinhamento governo/partido, proposições), com planos futuros de resumos por IA e cruzamento "promessa x voto".

**Legenda de certeza:**
- **[P]** fonte primária lida integralmente (texto da lei/resolução no Planalto ou TSE, ementa oficial no portal "Temas Selecionados" do TSE, notícia oficial do TSE).
- **[S]** fonte secundária (imprensa, doutrina, escritório de advocacia, páginas de TRE que resumem decisões).
- **[NV]** não verificado (não consegui abrir a fonte ou não encontrei decisão).

**Nota metodológica:** o portal do TSE bloqueia fetchers automatizados (HTTP 403); consegui baixar as páginas com cabeçalhos de navegador. Todas as citações da Res. 23.610 abaixo são da versão compilada oficial, que marca cada dispositivo com "(Incluído/Redação dada pela Resolução nº X)". Os ementários "Temas Selecionados" citados estavam atualizados entre 7/8/2026 e 15/9/2026.

---

## 0. Resumo executivo (para quem só vai ler isto)

1. **Estrutura jurídica correta é a que já têm: pessoas físicas identificadas, sem CNPJ, sem dinheiro de campanha, sem impulsionamento pago.** A Lei 9.504/97 permite conteúdo político-eleitoral em site/blog "de iniciativa de qualquer pessoa natural, desde que não contrate impulsionamento" (art. 57-B, IV, "b") e dispensa comunicação do endereço à Justiça Eleitoral (art. 57-B, §1º). Se abrirem CNPJ (associação, MEI, empresa), o site passa a "sítio de pessoa jurídica" e qualquer conteúdo qualificável como propaganda, positivo ou negativo, vira multa de R$ 5 mil a R$ 30 mil (art. 57-C, §1º, I). O TSE já equiparou MEI a pessoa jurídica para esse fim (2026).
2. **Mostrar votos nominais, presença, proposições e links para fonte oficial não é propaganda eleitoral.** A lei diz expressamente que "a divulgação de atos de parlamentares e debates legislativos, desde que não se faça pedido de votos" não configura propaganda antecipada (art. 36-A, IV), e a Res. 23.610 diz que a "manifestação espontânea na internet de pessoas naturais em matéria político-eleitoral, mesmo que sob a forma de elogio ou crítica" não é propaganda (art. 28, §6º). O TSE chama isso de "indiferente eleitoral" e de "dever constitucional de prestação de contas".
3. **Os três limites que valem para qualquer pessoa, candidata ou não:** (i) ofensa à honra/imagem de candidato; (ii) "fato sabidamente inverídico" ou "gravemente descontextualizado"; (iii) pedido explícito de voto ou de não voto. Fora disso, a jurisprudência de 2024–2026 é maciça em proteger crítica "ácida, contundente, injusta ou desagradável" e em dizer que "questionar o desempenho dos candidatos no exercício dos cargos públicos que ocupam ou ocuparam é corriqueiro no debate eleitoral".
4. **Impulsionamento pago é a linha vermelha mais nítida.** Pessoa natural não candidata não pode impulsionar nenhum conteúdo eleitoral, positivo ou negativo (TSE, 18/12/2025). Impulsionar crítica a candidato é ilícito "pelo meio, não pelo conteúdo", mesmo se o fato for público e notório (TSE, 25/6/2026 e 22/9/2026). Nada de Google Ads/Meta Ads para páginas de candidatos até 25/10.
5. **Ranking de desempenho parlamentar com base em dados oficiais não é "pesquisa eleitoral" nem "enquete".** Pesquisa (art. 33) e enquete (Res. 23.600, art. 23, §1º) pressupõem levantamento de opinião de eleitores que permita inferir a ordem dos candidatos na disputa. Não encontrei nenhuma decisão do TSE tratando índice de produtividade parlamentar como pesquisa. O risco surge só se o site (a) fizer votação/enquete de usuários sobre candidatos após 15/8 ou (b) usar vocabulário de pesquisa ("intenção de voto", "aprovação", "favorito", "líder", "%").
6. **IA:** a rotulagem obrigatória (Res. 23.610, art. 9º-B) incide sobre "propaganda eleitoral", mas o art. 3º-C estende as regras dos arts. 9º-B e 9º-C a todo "conteúdo político-eleitoral" fora do período de campanha, "no que lhes couber", inclusive para "pessoas e entidades responsáveis pela criação e divulgação do conteúdo"; e o art. 9º-E, V responsabiliza plataformas por conteúdo sintético sem rótulo em geral. Conclusão prática: rotular qualquer texto gerado por IA custa nada e elimina a discussão. Deepfake (imagem/voz de candidato) é proibido em qualquer hipótese (art. 9º-C, §1º), e há janela de 72h antes/24h depois da votação vedando conteúdo sintético novo com imagem, voz ou "manifestação" de candidato (art. 9º-B, §3º-A, novo em 2026). O art. 28, §1º-C (novo em 2026) proíbe sistemas de IA de "ranquear, recomendar, sugerir ou priorizar" candidatos ou "emitir opiniões, indicar preferência eleitoral, recomendar voto", inclusive "por meio de respostas automatizadas": um chatbot ou resumo de IA que diga "o melhor deputado é X" cai literalmente nessa vedação.
7. **Lançar em 1/10 ou em novembro muda o volume de litígio, não o direito aplicável.** As mesmas regras de honra/veracidade valem sempre; o que muda no período eleitoral é que representações correm em rito de 48h perante juízes auxiliares, o direito de resposta eleitoral está ativo (art. 58: "a partir da escolha em convenção"), e há as janelas de IA (1–5/10 e 22–26/10). Dia 4/10 é crime divulgar "qualquer espécie de propaganda de partidos ou candidatos" (art. 39, §5º, III), o que não alcança conteúdo informativo, mas recomenda não mandar push/newsletter com nomes de candidatos nesse dia.

---

## 1. Lei 9.504/1997 (Lei das Eleições)

Fonte primária lida: https://www.planalto.gov.br/ccivil_03/leis/l9504.htm **[P]**

### 1.1 Art. 36 e 36-A — propaganda antecipada e o que NÃO é propaganda

**(a) O que diz a norma**

- **Art. 36, caput** (redação Lei 13.165/2015): "A propaganda eleitoral somente é permitida após o dia 15 de agosto do ano da eleição." **§3º:** multa de R$ 5.000 a R$ 25.000 ou o custo da propaganda, ao responsável pela divulgação e, se comprovado prévio conhecimento, ao beneficiário.
- **Art. 36-A, caput** (Lei 13.165/2015): "Não configuram propaganda eleitoral antecipada, **desde que não envolvam pedido explícito de voto**, a menção à pretensa candidatura, a exaltação das qualidades pessoais dos pré-candidatos e os seguintes atos, que poderão ter cobertura dos meios de comunicação social, inclusive via internet:"
  - **IV** – "a divulgação de atos de parlamentares e de debates legislativos, desde que não se faça pedido de votos;" (redação Lei 12.891/2013)
  - **V** – "a divulgação de posicionamento pessoal sobre questões políticas, inclusive nas redes sociais;" (Lei 13.165/2015)
  - **I** – participação de pré-candidatos em entrevistas, programas, encontros ou debates "na internet, inclusive com a exposição de plataformas e projetos políticos".
  - **§2º** (via Res. 23.610, art. 3º, §2º): nas hipóteses dos incisos I a VII são permitidos "o pedido de apoio político e a divulgação da pré-candidatura, das ações políticas desenvolvidas e das que se pretende desenvolver". **§3º:** o §2º "não se aplica aos profissionais de comunicação social no exercício da profissão".
- **Res. 23.610, art. 3º-A** (Res. 23.671/2021): "Considera-se propaganda antecipada passível de multa aquela divulgada extemporaneamente cuja mensagem contenha pedido explícito de voto, ou que veicule conteúdo eleitoral em local vedado ou por meio, forma ou instrumento proscrito no período de campanha." **Parágrafo único** (Res. 23.732/2024): "O pedido explícito de voto não se limita ao uso da locução 'vote em', podendo ser inferido de termos e expressões que transmitam o mesmo conteúdo." **[P]**
- **Res. 23.610, art. 27, §2º:** "As manifestações de apoio ou crítica a partido político ou a candidata ou candidato ocorridas antes da data prevista no caput deste artigo [16/8], próprias do debate democrático, são regidas pela liberdade de manifestação." **[P]**

**Conceito jurisprudencial de propaganda eleitoral (antecipada):**

- **Regra pós-2015:** propaganda antecipada exige, alternativamente, "pedido explícito de votos, o uso de meios vedados no período oficial de campanha ou violação ao princípio da igualdade de oportunidades entre os candidatos" (TSE, Ac. 7/4/2026, AgR-REspEl 0600062-40, rel. Min. Estela Aranha; Ac. 14/5/2026, AgR-AREspE 0600138-88, mesma relatora). **[P]** — https://temasselecionados.tse.jus.br/temas-selecionados/propaganda-eleitoral/caracterizacao-de-propaganda-eleitoral
- **"Palavras mágicas" → "conjunto da obra":** o TSE abandonou a exigência de literalidade em 20/9/2022 (Rp 0600229-33.2022.6.00.0000, condenação de Jair Bolsonaro por evento em Cuiabá, 4x3, divergência vencedora do Min. Lewandowski); a Min. Cármen Lúcia frisou que se mantém a exigência de pedido explícito, mudando apenas como identificá-lo. **[S]** — https://www.conjur.com.br/2022-set-21/tse-abandona-criterio-palavras-magicas-propaganda-antecipada/ . A regra foi codificada no art. 3º-A, parágrafo único, da Res. 23.610 (2024). Exemplos condenados em 2026: "SOU FURLAN... VAMOS JUNTOS!!! VEM COMIGO" (Ac. 30/4/2026, AgR-AREspE 0600039-61); "Venha somar comigo", "Vamos juntos para mais uma grande vitória!" (Ac. 20/8/2026, AgR-REspEl 0600036-82). **[P]**
- **Aferição objetiva, não por suposição:** "A aferição de propaganda eleitoral antecipada deve ser realizada a partir de dados e elementos objetivamente considerados, e não conforme meras suposições" (Ac. 24/2/2026, Rec-Rp 0600240-96, rel. Min. Nunes Marques). **[P]**
- **Convenção transmitida pela internet não é propaganda por si só; o alcance da transmissão não define natureza eleitoral:** "a natureza do ato comunicativo [deve] ser aferida a partir de seu conteúdo, sua linguagem, seus destinatários e seu contexto" (tese fixada em 1/9/2026, caso do vídeo de IA na convenção do PL, 4x3). **[P]** — https://www.tse.jus.br/comunicacao/noticias/2026/Setembro/tse-fixa-tese-sobre-deepfake-e-delimita-regra-para-as-eleicoes-2026
- **Divulgação de atos parlamentares = indiferente eleitoral:** "Não configuram propaganda eleitoral extemporânea, por consistirem em indiferentes eleitorais, os atos publicitários sem conteúdo diretamente relacionado com a disputa eleitoral. [...] A divulgação de atos parlamentares encontra abrigo no ordenamento eleitoral, decorre do dever constitucional de prestação de contas à população e, portanto, não se confunde com a propaganda eleitoral tout court" (Ac. 7/5/2020, AgR-REspe 0600083-90, rel. Min. Edson Fachin). "A vedação constante do § 3º do art. 36-A se restringe ao disposto no § 2º, não se estendendo aos demais permissivos do caput e dos incisos" (Ac. 17/9/2019, AgR-REspe 0600524-11, rel. Min. Sérgio Banhos). **[P]** — https://temasselecionados.tse.jus.br/temas-selecionados/propaganda-eleitoral/atuacao-parlamentar/divulgacao
- **Conceito antigo (pré-2015), ainda citado em precedentes de imprensa:** propaganda é "aquela que leva ao conhecimento geral, ainda que de forma dissimulada, a candidatura, mesmo que apenas postulada, a ação política que se pretende desenvolver ou razões que induzam a concluir que o beneficiário é o mais apto ao exercício de função pública" (Ac. 18/9/2018, REspe 41395, Eleições 2012). Esse conceito amplo foi superado para efeito de propaganda **antecipada** pela Lei 13.165/2015, mas continua relevante para identificar propaganda **em sítio de pessoa jurídica** durante a campanha (ver 1.2). **[P]**

**(b) O que significa para o projeto**

- Um site que exibe votos nominais, presença e proposições é, literalmente, "divulgação de atos de parlamentares e de debates legislativos" (art. 36-A, IV). Não é propaganda antecipada antes de 16/8 nem propaganda eleitoral durante a campanha, desde que **não peça voto nem não voto**, nem por expressão equivalente ("vote consciente em quem votou a favor de X", "não reeleja quem faltou", "vamos juntos tirar esses deputados" são expressões semanticamente equivalentes e o TSE as condena).
- O critério do TSE é objetivo e contextual: conteúdo, linguagem, destinatários e contexto. Linguagem neutra ("votou sim/não/abstenção/obstrução", "presente em N de M sessões") fica fora do conceito. Linguagem exortativa ("merece", "não merece", "fez por merecer sua confiança") entra em zona cinzenta.
- O TSE tem, desde 2022, tendência a ampliar o que conta como pedido de voto (Conjur, Gazeta do Povo 20/7/2026 **[S]**). A defesa mais sólida do projeto é a natureza puramente descritiva e a fonte oficial de cada dado.

**(c) Certeza:** texto legal e ementas [P]; história das "palavras mágicas" [S].

### 1.2 Art. 57-A a 57-J — propaganda na internet; quem pode; vedação a pessoa jurídica

**(a) O que diz a norma** **[P]**

- **Art. 57-A:** propaganda na internet permitida após 15 de agosto.
- **Art. 57-B:** formas admitidas: I – sítio do candidato; II – sítio do partido/coligação; III – mensagem eletrônica; **IV – "blogs, redes sociais, sítios de mensagens instantâneas e aplicações de internet assemelhadas cujo conteúdo seja gerado ou editado por: a) candidatos, partidos ou coligações; ou b) qualquer pessoa natural, desde que não contrate impulsionamento de conteúdos."** (Lei 13.488/2017)
  - **§1º:** "Os endereços eletrônicos das aplicações de que trata este artigo, **salvo aqueles de iniciativa de pessoa natural**, deverão ser comunicados à Justiça Eleitoral".
  - **§2º:** vedado conteúdo eleitoral "mediante cadastro de usuário de aplicação de internet com a intenção de falsear identidade".
  - **§3º:** vedado uso de "impulsionamento de conteúdos e ferramentas digitais não disponibilizadas pelo provedor da aplicação de internet, ainda que gratuitas, para alterar o teor ou a repercussão de propaganda eleitoral, tanto próprios quanto de terceiros" (bots, automação de engajamento).
  - **§5º:** multa de R$ 5.000 a R$ 30.000 (ou dobro do gasto) ao "usuário responsável pelo conteúdo e, quando comprovado seu prévio conhecimento, o beneficiário".
- **Art. 57-C, caput:** "É vedada a veiculação de qualquer tipo de propaganda eleitoral paga na internet, excetuado o impulsionamento de conteúdos, desde que identificado de forma inequívoca como tal e **contratado exclusivamente por partidos, coligações e candidatos e seus representantes**."
  - **§1º: "É vedada, ainda que gratuitamente, a veiculação de propaganda eleitoral na internet, em sítios: I – de pessoas jurídicas, com ou sem fins lucrativos; II – oficiais ou hospedados por órgãos ou entidades da administração pública"**.
  - **§2º:** multa de R$ 5.000 a R$ 30.000 (ou dobro do gasto) ao responsável pela divulgação/impulsionamento e ao beneficiário com prévio conhecimento.
  - **§3º:** impulsionamento só com provedor sediado no país "e apenas com o fim de promover ou beneficiar candidatos ou suas agremiações". A Res. 23.610, art. 29, §3º acrescenta: "vedada a realização de propaganda negativa"; art. 28, §7º-A: "vedado o uso do impulsionamento para propaganda negativa".
- **Art. 57-D:** "É livre a manifestação do pensamento, **vedado o anonimato durante a campanha eleitoral**, por meio da rede mundial de computadores – internet, assegurado o direito de resposta [...]". **§2º:** multa R$ 5.000 a R$ 30.000. **§3º:** "a Justiça Eleitoral poderá determinar, por solicitação do ofendido, a retirada de publicações que contenham agressões ou ataques a candidatos em sítios da internet, inclusive redes sociais."
- **Art. 57-E:** vedada a cessão de cadastros de clientes a candidatos; proibida venda de cadastro de e-mails.
- **Art. 57-F:** provedor que hospeda propaganda só responde se, notificado de decisão judicial, não cessar; parágrafo único: só se o material for "comprovadamente de seu prévio conhecimento".
- **Art. 57-G:** mensagens de candidato/partido devem ter descadastramento em 48h; R$ 100 por mensagem após o prazo.
- **Art. 57-H:** multa R$ 5.000 a R$ 30.000 por propaganda na internet "atribuindo indevidamente sua autoria a terceiro"; **§1º:** crime (detenção 2 a 4 anos) contratar grupo de pessoas para "emitir mensagens ou comentários na internet para ofender a honra ou denegrir a imagem de candidato".
- **Art. 57-I** (Lei 13.488/2017): a requerimento de candidato/partido, "a Justiça Eleitoral poderá determinar, no âmbito e nos limites técnicos de cada aplicação de internet, a suspensão do acesso a todo conteúdo veiculado que deixar de cumprir as disposições desta Lei", proporcional à gravidade, máximo 24 horas; §1º dobra a cada reiteração.
- **Art. 57-J:** delega ao TSE regulamentar 57-A a 57-I "de acordo com o cenário e as ferramentas tecnológicas existentes em cada momento eleitoral".

**Jurisprudência sobre pessoa jurídica x pessoa natural:**

- **MEI equiparado a pessoa jurídica (2026):** "é vedada a propaganda eleitoral em sítios eletrônicos vinculados a pessoas jurídicas ou a agentes econômicos que atuem de fato como tais (Teoria da Aparência), ainda que sob a forma de empresário individual" (Ac. 16/4/2026, AgR-AREspE 0600075-94, rel. Min. Nunes Marques). **[P]** — https://temasselecionados.tse.jus.br/temas-selecionados/propaganda-eleitoral/internet/redes-sociais
- **Sindicato (CUT) multado por propaganda em seu site (Eleições 2010):** textos que "induziam os eleitores à ideia de que a candidata representada seria a mais apta ao exercício do cargo" e propaganda negativa contra o adversário; multa R$ 15.000 à CUT e à editora (TSE, Rp 355133-33.2010, rel. Min. Nancy Andrighi, 10/4/2012). **[S]** — https://www.mpam.mp.br/caoeleitoral-jurisprudencia/caoeleitoral-propaganda/representacao-propaganda-eleitoral-irregular-internet-art-57-c-da-lei-950497-parcial-procedencia
- **Exceção para imprensa:** o TSE (AgR 0608960-34.2018.6.26.0000/SP) confirmou a vedação a sites de pessoa jurídica com exceção apenas para "órgãos de imprensa e jornalistas, em contexto exclusivamente informativo"; a empresa do caso perdeu por ser "provedor de acesso, marketing direto e agência de publicidade" sem objeto jornalístico. **[S]** — https://juizo.tre-rs.jus.br/juizo/artigos/2402 (página do TRE-RS; não abri o acórdão).
- **Pessoa natural não candidata não pode impulsionar nada de eleitoral:** "para pessoa natural, aquela que não se coloca como pré-candidato ou candidato, é vedado qualquer impulsionamento de conteúdo eleitoral veiculado por meio da internet, seja ele positivo ou negativo (art. 57-B, IV, b)" (Ac. 18/12/2025, AgR-REspEl 0600037-91, rel. Min. André Mendonça). **[P]**
- **Impulsionamento negativo: ilícito pelo meio, não pelo conteúdo:** "Ainda que a crítica política, em si, possa ser lícita quando veiculada de forma orgânica, o uso de impulsionamento pago constitui meio vedado para difusão de conteúdo negativo, sendo a ilicitude, no caso, aferida quanto ao meio utilizado, e não necessariamente quanto ao conteúdo isolado" (Eleições 2026, Ac. 25/6/2026, Ref-Rp 0600782-41, rel. Min. Estela Aranha). "A utilização de impulsionamento oneroso para amplificar artificialmente críticas a adversário político configura meio proscrito, independentemente da veracidade do conteúdo veiculado" (Ac. 23/10/2025, AgR-AREspE 0600059-66). Em 22/9/2026 o Plenário reiterou: basta "impulsionamento de conteúdo com clara feição negativa em desfavor de adversários políticos, mesmo que este eventualmente corresponda à afirmação baseada em fatos públicos e notórios" (AgR-REspEl 0600491-95.2024.6.20.0051, voto vencedor Min. Floriano de Azevedo Marques). **[P]** — https://www.tse.jus.br/comunicacao/noticias/2026/Setembro/tse-confirma-punicao-por-impulsionamento-de-propaganda-eleitoral-negativa-na-internet
- **Pessoa natural remunerada por candidato/terceiro:** Res. 23.610, art. 28, IV, "b", item 2 (Res. 23.732/2024) veda "a remuneração, a monetização ou a concessão de outra vantagem econômica como retribuição à pessoa titular do canal ou perfil, paga pelas(os) beneficiárias(os) da propaganda ou por terceiros"; art. 29, §8º (Res. 23.755/2026): é propaganda paga vedada "a contratação sob qualquer modalidade, ainda que por meio da utilização de mecanismos de competição, ranqueamento ou premiação que ofereçam, direta ou indiretamente, vantagem econômica a pessoas físicas ou jurídicas para que realizem publicações de cunho político-eleitoral em seus perfis, páginas, canais ou assemelhados [...] bem como em seus sítios eletrônicos". **[P]**

**Um site informativo de dados, mantido por pessoas físicas, que não pede voto, é "propaganda"?**

Não, por três camadas normativas independentes:
1. Lei 9.504, art. 36-A, IV (divulgação de atos parlamentares não é propaganda antecipada) e V (posicionamento pessoal em sites pessoais).
2. Res. 23.610, art. 28, §6º: "A manifestação espontânea na internet de pessoas naturais em matéria político-eleitoral, mesmo que sob a forma de elogio ou crítica a candidata, candidato, partido político, federação ou coligação, **não será considerada propaganda eleitoral** na forma do inciso IV do caput deste artigo, desde que observados os limites estabelecidos no § 1º do art. 27 desta Resolução." **[P]**
3. Res. 23.610, art. 27, §1º (redação Res. 23.755/2026): "A livre manifestação do pensamento de pessoa eleitora identificada ou identificável na internet **somente é passível de limitação quando ofender a honra ou a imagem** de candidatas, candidatos, partidos, federações ou coligações, **ou quando divulgar fatos sabidamente inverídicos**, observado o disposto no art. 9º-C desta Resolução." **[P]**

Ou seja: mesmo que o conteúdo fosse considerado "propaganda" (elogio/crítica), para pessoa natural identificada isso é lícito (art. 57-B, IV, "b"); a única coisa que muda para pessoa natural é que ela não pode impulsionar nem ser remunerada. O enquadramento como propaganda só "morde" se (i) houver pessoa jurídica por trás, (ii) houver impulsionamento, (iii) houver anonimato, (iv) houver ofensa à honra ou fato sabidamente inverídico.

**(b) O que significa para o projeto**

- **Não constituir pessoa jurídica** enquanto o site tiver conteúdo sobre candidatos em período eleitoral. Associação sem fins lucrativos e MEI estão expressamente cobertos pela vedação (art. 57-C, §1º, I: "com ou sem fins lucrativos"; MEI: TSE 16/4/2026). Se precisarem de CNPJ no futuro (para receber doações, por exemplo), a saída jurisprudencial é ser reconhecido como "órgão de imprensa/jornalista em contexto exclusivamente informativo", o que é incerto para um site de dados sem jornalistas. Alternativa: manter o domínio e a hospedagem em nome das pessoas físicas e criar a PJ apenas para atividades não relacionadas ao conteúdo, mas isso é exatamente o que a "teoria da aparência" do TSE ataca.
- **Identificação clara dos responsáveis** (nome completo, e opcionalmente CPF parcial, cidade, contato) em página "Quem somos". O art. 57-D veda o anonimato durante a campanha; a Res. 23.610, art. 38, §§2º-3º diz que "a ausência de identificação imediata do usuário" não basta para remoção, e que só é anônimo quem não puder ser identificado após quebra de registros. Identificar-se voluntariamente elimina o tema.
- **Zero impulsionamento pago** (Google Ads, Meta Ads, X Ads, Taboola, anúncios em newsletters de terceiros) de qualquer página que mencione candidato, até 25/10/2026 (2º turno). Isso inclui anúncio "neutro" do tipo "veja como seu deputado votou" se a landing page tiver candidatos: a jurisprudência olha o conjunto. Impulsionar apenas página institucional genérica (sem nome de candidato) é menos arriscado, mas a Res. 23.610, art. 28, §7º-B, II veda até priorização paga em buscadores com nome de adversário como palavra-chave (para candidatos); para pessoa natural, a regra do TSE é "qualquer impulsionamento de conteúdo eleitoral". Recomendação: nenhum anúncio pago até o fim do 2º turno.
- **Nenhuma automação de engajamento** (bots, disparo em massa, cross-posting automatizado para simular repercussão): art. 57-B, §3º e Res. 23.610, art. 34, II.
- **Nenhum dinheiro de partido, candidato, campanha ou "terceiro" interessado.** Doação de partido ou candidato transforma o site em propaganda paga (art. 29, §8º) e o próprio candidato responde. Se aceitarem doações do público, ter regra pública recusando doações de pessoas filiadas/candidatas/partidos e de empresas.
- **Se candidatos usarem o site como "vitrine" (prints, links, selos),** o risco é do candidato, não do site: matéria do ND+ (2026) relata que parlamentares candidatos à reeleição transformaram resultados do Prêmio Congresso em Foco em peças de campanha, sem menção a sanção ao site. **[S]** — https://ndmais.com.br/politica/premio-congresso-em-foco-candidatos-reeleicao/ . Ainda assim, uma nota de rodapé "Este site não apoia nem se opõe a candidaturas; dados oficiais, sem juízo eleitoral" ajuda a demonstrar o contexto.

**(c) Certeza:** texto legal e regulamentar [P]; ementas 2025–2026 [P]; caso CUT 2012 e AgR 0608960-34/2018 [S].

### 1.3 Art. 58 — direito de resposta

**(a) O que diz a norma** **[P]**

- **Art. 58, caput:** "A partir da escolha de candidatos em convenção, é assegurado o direito de resposta a candidato, partido ou coligação atingidos, ainda que de forma indireta, por conceito, imagem ou afirmação caluniosa, difamatória, injuriosa ou sabidamente inverídica, difundidos por qualquer veículo de comunicação social."
- **§1º, IV** (Lei 13.165/2015): prazo para pedir: "a qualquer tempo, quando se tratar de conteúdo que esteja sendo divulgado na internet, ou em 72 (setenta e duas) horas, após a sua retirada."
- **§2º:** ofensor notificado para defesa em 24h; decisão em até 72h.
- **Res. 23.608/2019, art. 31** (redação Res. 23.672/2021): estende expressamente a "provedores de aplicativos de internet e redes sociais". **Parágrafo único:** se o pedido versar sobre conteúdo "sabidamente inverídico, inclusive veiculado originariamente por pessoa terceira, caberá à representada ou ao representado demonstrar que procedeu à verificação prévia de elementos que permitam concluir, com razoável segurança, pela fidedignidade da informação." **[P]** — https://www.tse.jus.br/legislacao/compilada/res/2019/resolucao-no-23-608-de-18-de-dezembro-de-2019
- **Res. 23.610, art. 30, §3º:** em provedor "que não exerça controle editorial prévio", a resposta recai sobre o usuário responsável. Um site com controle editorial (como o do projeto) publicaria ele mesmo a resposta.
- **Res. 23.610, art. 9º, caput:** quem usa em propaganda conteúdo "inclusive veiculado por terceiras(os)" deve ter verificado "com razoável segurança, pela fidedignidade da informação". Isso é dever do **candidato** que reproduzir dados do site; se o dado do site estiver errado, quem responde por direito de resposta é primariamente quem o reproduziu em propaganda, mas o site pode ser demandado como veículo.

**Jurisprudência (portal Temas Selecionados, atualizado 25/6/2026) [P]:**

- "a concessão do direito de resposta é absolutamente excepcional e somente se legitima com comprometimento do próprio direito de acesso à informação pelo eleitor cidadão, nas hipóteses de fato chapadamente inverídico, ou em casos de graves ofensas pessoais, tais como injúria, calúnia ou difamação" (Ac. 29/11/2024, AgR-REspEl 0600104-40, rel. Min. Floriano de Azevedo Marques).
- No mesmo acórdão: matéria que disse que a candidata foi intimada pela Justiça a prestar esclarecimentos sobre fechamento de instituição, **omitindo que fora intimada como testemunha**, não é inverídica nem descontextualizada; "Tratando-se de mensagem divulgada por veículo de imprensa, sem indícios mínimos de manipulação de dados ou mesmo da inobservância do dever de cuidado na apuração dos fatos, a intervenção da Justiça Eleitoral no debate público deve ser mínima".
- "se a propaganda tem foco em matéria jornalística, apenas noticiando conhecido episódio, não incide o disposto no art. 58" (Ac. 3/10/2022, Ref-TutCautAnt 0601234-90, rel. Min. Sérgio Banhos).
- "A descrição objetiva de alegações constantes de processo judicial [...] não se consubstancia em afirmação sabidamente inverídica, caluniosa, difamatória ou injuriosa" (Ac. 25/10/2018, Rp 0601640-53, rel. Min. Carlos Horbach).
- "Fatos negativos noticiados na mídia não autorizam direito de resposta em caso no qual não se comprove informação sabidamente inverídica" (Ac. 28/10/2022, DR 0601590-85, rel. Min. Cármen Lúcia).
- Métrica das Eleições 2022: "somente é legítima a utilização, contra outros concorrentes, de adjetivos cuja significação técnica insinue eventual prática de crime, se e quando houver condenação judicial específica, ou, ao menos, acusação formal nesse sentido" (Ac. 24/10/2022, REC-DR 0601508-54, rel. Min. Maria Claudia Bucchianeri).
- URL: https://temasselecionados.tse.jus.br/temas-selecionados/direito-de-resposta-na-propaganda-eleitoral/caracterizacao-da-ofensa-1/materia-jornalistica

**(b) O que significa para o projeto**

- Um candidato pode pedir direito de resposta contra o site a qualquer tempo enquanto o conteúdo estiver no ar, entre a convenção (20/7 a 5/8/2026) e a eleição. O rito é de 24h para defesa e 72h para decisão. Para dado factual com fonte oficial linkada, o padrão do TSE é indeferir. O risco concentra-se em (i) erro de dado (voto registrado errado, presença errada), (ii) rótulo valorativo que insinue crime ou desonestidade ("mentiu", "traiu o eleitor", "corrupto"), (iii) omissão que altere o sentido (por exemplo, "faltou" sem indicar licença médica/missão oficial registrada pela Casa).
- Ter um **canal de correção público e rápido** (formulário "Reportar erro", prazo interno de 24h para checar e corrigir, registro público do que foi corrigido) é a melhor defesa: demonstra "dever de cuidado na apuração" e retira o objeto da representação antes da decisão.
- Guardar **evidência da fonte no momento da coleta** (hash, timestamp, URL da API da Câmara/Senado, cópia do JSON) para provar a fidedignidade.

**(c) Certeza:** [P].

### 1.4 Art. 33 a 35-A — pesquisas eleitorais e "rankings"

**(a) O que diz a norma** **[P]**

- **Art. 33, caput:** "As entidades e empresas que realizarem **pesquisas de opinião pública relativas às eleições ou aos candidatos**, para conhecimento público, são obrigadas, para cada pesquisa, a registrar, junto à Justiça Eleitoral, até cinco dias antes da divulgação" contratante, valor/origem dos recursos, metodologia, plano amostral, margem de erro, questionário, quem pagou (incisos I a VII). **§3º:** divulgação sem registro: multa de 50 mil a 100 mil UFIR (≈ R$ 53 mil a R$ 106 mil, valor de 2024 segundo notícia do TSE). **§4º:** pesquisa fraudulenta é crime (6 meses a 1 ano). **§5º** (Lei 12.891/2013): "É vedada, no período de campanha eleitoral, a realização de enquetes relacionadas ao processo eleitoral."
- **Art. 34, §§1º-3º:** fiscalização por partidos; crime dificultar; obrigação de veicular dados corretos. **Art. 35:** responsabilidade penal de representantes legais da empresa de pesquisa **e do órgão veiculador**. **Art. 35-A:** vedação de divulgação nos 15 dias anteriores foi suspensa pela ADI 3.741 (anotação do Planalto).
- **Res. TSE 23.600/2019** (pesquisas; alterada pela Res. 23.747/2026): **art. 2º:** registro no PesqEle a partir de 1º de janeiro do ano da eleição; **art. 23** (redação 2026): "É vedada, após o dia 15 de agosto do ano da eleição, a realização de enquetes relacionadas ao respectivo processo eleitoral." **§1º:** "Entende-se por enquete ou sondagem o **levantamento de opiniões** sem plano amostral, que dependa da participação espontânea da parte interessada ou importe viés cognitivo de autosseleção e que não utilize método científico para sua realização, **quando apresentados resultados que possibilitem à eleitora ou ao eleitor inferir a ordem das candidatas e dos candidatos na disputa**." **§1º-A:** "A enquete que seja apresentada à população como pesquisa eleitoral será reconhecida como pesquisa de opinião pública sem registro". **§2º:** após 15/8 cabe poder de polícia com ordem de remoção "sob pena de crime de desobediência". **[P]** — https://www.tse.jus.br/legislacao/compilada/res/2019/resolucao-no-23-600-de-12-de-dezembro-de-2019

**Jurisprudência (portal Temas Selecionados "Enquete", atualizado 30/6/2026) [P]:**

- "a falta de formalidades mínimas do art. 33 da Lei n. 9.504/1997, sem elementos que induzam o eleitor ao erro, caracteriza mera enquete, que dispensa registro e não gera multa" (Ac. 2/12/2025, AgR-REspEl 0600535-23, rel. Min. Estela Aranha).
- "A divulgação de nomes, fotografias e percentuais atribuídos a candidatos, acompanhada da utilização expressa do termo 'pesquisa', possui aptidão para induzir o eleitor a crer que se trata de levantamento realizado segundo critérios técnicos e científicos" → multa (Ac. 18/6/2026, REspEl 0600497-82, rel. Min. Villas Bôas Cueva). "basta que a pesquisa eleitoral sem registro prévio tenha sido dirigida para conhecimento público, não importando o número de pessoas atingidas".
- "as enquetes apresentadas ao público sem o necessário esclarecimento em relação à sua natureza, com dados próprios de pesquisas eleitorais, geram o efeito de pesquisa e assim devem ser tratadas" (Ac. 11/12/2025, AgR-AREspE 0600068-14).
- Quem **compartilha** pesquisa não registrada, mesmo publicada por terceiro (imprensa), também paga a multa (Ac. 18/6/2026, AgR-AREspE 0600002-52; TSE 3/12/2024).
- A multa por enquete em período vedado **não tem base legal** (só remoção): "a competência normativa do TSE não alcança a instituição de sanção de natureza pecuniária" (Ac. 16/12/2021, AgR-AREspE 0601038-25, rel. Min. Alexandre de Moraes, citando precedente).
- TRE-RN (19/8/2026, Rp 0600174-85): publicação com "menção expressa aos termos 'pesquisa', percentuais, amostragem, margem de erro, nível de confiança e número de registro perante o TSE" induz a erro; TRE-RN (17/10/2024, REl 0600073-92): menção genérica a "três pesquisas" em discurso de tribuna, sem linguagem científica, é mera sondagem, sem multa. **[S]** — https://www.tre-rn.jus.br/jurisprudencia/temas-selecionados/pesquisa-eleitoral/12-1-enquete-x-pesquisa-eleitoral-x-sondagem

**Há decisão do TSE sobre "ranking"/"índice" de desempenho parlamentar como pesquisa?** Não encontrei nenhuma, nem no portal Temas Selecionados (pesquisa eleitoral: generalidades, enquete, divulgação, penalidade), nem em busca aberta, nem envolvendo Ranking dos Políticos, Congresso em Foco, Politicos.org.br, Meu Deputado, Vigie Aqui, Atlas Político, Aos Fatos, Lupa ou Deputômetro. **[NV]** Todos os casos localizados envolvem percentuais de **intenção de voto**.

**(b) O que significa para o projeto**

- Um índice calculado a partir de votações, presença e proposições **não é levantamento de opinião de eleitores**, logo não é "pesquisa de opinião pública" (art. 33) nem "enquete" (Res. 23.600, art. 23, §1º). A doutrina jurisprudencial exige "dados próprios de pesquisas eleitorais" (intenção de voto, percentuais por candidato, margem de erro) e aptidão para induzir o eleitor a crer que se trata de levantamento científico de intenção de voto.
- **Vocabulário a evitar em qualquer ranking:** "pesquisa", "intenção de voto", "aprovação", "rejeição", "favorito", "líder", "chance de reeleição", "quem vai ganhar", percentuais que possam ser lidos como preferência do eleitorado. Preferir: "índice de presença (metodologia)", "alinhamento com orientação do governo em N votações", "nota de produtividade legislativa (critérios públicos)".
- **Não abrir votação/enquete de usuários** ("qual deputado você aprova?", "quem merece reeleição?") até 26/10. Isso é enquete relacionada ao processo eleitoral em período vedado (remoção via poder de polícia; se apresentado como pesquisa, multa de até ≈ R$ 106 mil). Após a eleição, enquete deixa de estar vedada, mas continua sem valor informativo.
- Não republicar pesquisas de intenção de voto sem checar o registro no PesqEle (quem compartilha responde).
- Deixar a **metodologia explícita** em página própria (fontes, fórmula, data de corte, o que conta como ausência justificada, como se calcula alinhamento). Isso serve ao mesmo tempo para afastar a tese de "induzir o eleitor a erro" e para a defesa de honra (ver seção 5).

**(c) Certeza:** texto legal/regulamentar e ementas [P]; TRE-RN [S]; ausência de precedente sobre ranking de desempenho [NV].

---

## 2. Resolução TSE 23.610/2019 (propaganda) na versão para 2026

Fonte primária: versão compilada https://www.tse.jus.br/legislacao/compilada/res/2019/resolucao-no-23-610-de-18-de-dezembro-de-2019 **[P]**. Alteradora de 2026: **Resolução TSE nº 23.755, de 2/3/2026** (Instrução 0600751-65.2019.6.00.0000, rel. Min. Nunes Marques, aprovada por unanimidade em sessão administrativa de 2/3/2026, publicada no DJE-TSE de 3/3/2026) — https://www.tse.jus.br/legislacao/compilada/res/2026/resolucao-no-23-755-de-2-de-marco-de-2026 ; PDF da resolução com voto: https://www.tse.jus.br/eleicoes/eleicoes-2026-content/normas-e-documentacoes/arquivos-2026/resolucao-e-voto-propaganda/@@display-file/file/Resolucao-e-voto-propaganda.pdf **[P]**. Notícia oficial da aprovação: https://www.tse.jus.br/comunicacao/noticias/2026/Marco/tse-aprova-calendario-eleitoral-e-regulamenta-uso-de-ia-nas-eleicoes-2026 **[P]**. O calendário eleitoral é a Res. 23.760/2026. O TSE aprovou 14 resoluções para 2026 entre 26/2 e 2/3/2026; a de pesquisas é a Res. 23.747/2026 (altera a 23.600).

### 2.1 Dispositivos sobre conteúdo informativo/jornalístico e liberdade na internet **[P]**

- **Art. 6º, §2º:** "O poder de polícia se restringe às providências necessárias para inibir práticas ilegais, **vedada a censura prévia sobre o teor dos programas e das matérias jornalísticas** a serem exibidos na televisão, na rádio, **na internet** e na imprensa escrita."
- **Art. 7º, §1º:** "Caso a irregularidade constatada na internet se refira ao **teor** da propaganda, não será admitido o exercício do poder de polícia, nos termos do art. 19 da Lei nº 12.965/2014" (só forma/meio; teor exige representação e decisão judicial).
- **Art. 10, §1º:** a restrição a meios publicitários que criem estados emocionais "não pode ser interpretada de forma a inviabilizar a publicidade das candidaturas ou **embaraçar a crítica de natureza política**, devendo-se proteger, no maior grau possível, a liberdade de pensamento e expressão."
- **Art. 27, §§1º e 2º** (transcritos em 1.2): limites da manifestação de pessoa eleitora identificada = honra/imagem e fatos sabidamente inverídicos; antes de 16/8, apoio e crítica são livres.
- **Art. 28, §6º** (transcrito em 1.2): manifestação espontânea de pessoas naturais, inclusive elogio ou crítica, não é propaganda. **§6º-A:** é lícita propaganda em perfis de pessoas naturais de grande audiência ou em mobilizações de rede, observados o §6º e a proibição de remuneração.
- **Art. 29-A, §3º:** "A cobertura jornalística da live eleitoral deve respeitar os limites legais aplicáveis à programação normal de rádio e televisão".
- **Art. 38:** "A atuação da Justiça Eleitoral em relação a conteúdos divulgados na internet deve ser realizada com a menor interferência possível no debate democrático". **§1º:** remoção "limitada às hipóteses em que, mediante decisão fundamentada, sejam constatadas violações às regras eleitorais ou ofensas a direitos de pessoas que participam do processo eleitoral". **§4º:** ordem deve indicar URL específica, prazo não inferior a 24h (salvo exceção). **§7º** (2024): ordens de remoção mantêm efeitos após o período eleitoral. **§8º-A** (2024): a eleição não gera perda de objeto de procedimentos sobre anonimato ou fato inverídico contra honra de candidato.
- **Art. 38-A** (Res. 23.755/2026, novo): remoção de **perfil** só para "usuário comprovadamente falso, apócrifo ou vinculado a pessoa que sequer exista fora do universo digital (perfil automatizado ou robô)" com prática reiterada de crime eleitoral ou desinformação sobre o processo eleitoral, em processo com ampla defesa.
- **Art. 37, XVIII:** "provedor de aplicação de internet: a empresa, organização **ou pessoa natural que, de forma profissional ou amadora**, forneça um conjunto de funcionalidades que podem ser acessadas por meio de um terminal conectado à internet, não importando se os objetivos são econômicos". (O site do projeto é, tecnicamente, um provedor de aplicação: isso importa para o art. 28, §1º-C, abaixo.)
- **Art. 27-A, §1º:** define "conteúdo político-eleitoral" de modo amplíssimo: "aquele que versar sobre eleições, partidos políticos, federações e coligações, cargos eletivos, **pessoas detentoras de cargos eletivos**, pessoas candidatas, propostas de governo, **projetos de lei**, exercício do direito ao voto [...]". O conteúdo do projeto é, portanto, "conteúdo político-eleitoral" para fins da resolução.

Não há na Res. 23.610 uma categoria própria de "site de notícias" para a internet; a proteção vem da vedação de censura prévia (art. 6º, §2º), da liberdade da pessoa natural (arts. 27 e 28) e do art. 38. A categoria "imprensa" propriamente dita aparece no cap. V (arts. 42 e seguintes: jornal impresso e sua reprodução na internet) e para rádio/TV (art. 43 e seguintes).

### 2.2 Desinformação e IA (arts. 9º a 9º-J) — o que é de 2024 e o que é de 2026 **[P]**

- **Art. 9º-A:** revogado pela Res. 23.714/2022 (a norma sobre desinformação contra o processo eleitoral passou para a Res. 23.714/2022 e para os arts. 9º-D a 9º-G). **Atenção: o pedido citava "art. 9-A" como norma de desinformação; hoje ele está revogado.**
- **Art. 9º, caput:** quem usa conteúdo em propaganda, "inclusive veiculado por terceiras(os)", deve ter verificado "a fidedignidade da informação"; **§§1º-2º** (2024): checagens de agências parceiras do TSE (Lupa, Aos Fatos etc.) ficam no site da Justiça Eleitoral e servem de parâmetro do dever de diligência.
- **Art. 9º-B** (Res. 23.732/2024; caput e §1º, IV com nova redação pela Res. 23.755/2026): "A utilização **na propaganda eleitoral, em qualquer modalidade**, de conteúdo sintético multimídia gerado por meio de inteligência artificial **ou tecnologia equivalente** para criar, substituir, omitir, mesclar ou alterar a velocidade ou sobrepor imagens ou sons, impõe ao responsável pela propaganda o dever de informar, de modo explícito, destacado e acessível, que o conteúdo foi fabricado ou manipulado e qual tecnologia foi utilizada."
  - **§1º:** formato do aviso: no início de peças de áudio; rótulo (marca d'água) e audiodescrição em imagens estáticas; ambos em vídeo; em cada página de impresso.
  - **§2º:** exceções: melhoria de qualidade de imagem/som; identidade visual/vinhetas/logos; montagem fotográfica de uso costumeiro em campanha.
  - **§3º:** "O uso de **chatbots, avatares e conteúdos sintéticos** como artifício para intermediar a comunicação de campanha com pessoas naturais submete-se ao disposto no caput deste artigo, **vedada qualquer simulação de interlocução com a pessoa candidata ou outra pessoa real**."
  - **§3º-A (NOVO, Res. 23.755/2026):** "Ficam vedadas a publicação e a republicação, ainda que gratuitas, bem como o impulsionamento pago de **novos conteúdos sintéticos** produzidos ou alterados por inteligência artificial ou por tecnologias equivalentes que utilizem **imagem, voz ou manifestação de candidata ou candidato ou de pessoa pública**, mesmo que rotulados e em conformidade com as demais exigências deste artigo, no período compreendido entre as **72 (setenta e duas) horas que antecedem e as 24 (vinte e quatro) horas que sucedem o término do pleito**."
  - **§4º** (redação 2026): descumprimento do caput, §3º e §3º-A "impõe a imediata remoção do conteúdo ou indisponibilidade do serviço de comunicação, por iniciativa do provedor de aplicação ou por determinação judicial, sem prejuízo de apuração nos termos do § 2º do art. 9º-C".
  - **§5º (NOVO, 2026):** plataformas de impulsionamento devem oferecer campo para declarar uso de IA.
- **Art. 9º-C** (2024): "É vedada a utilização, **na propaganda eleitoral**, qualquer que seja sua forma ou modalidade, de conteúdo fabricado ou manipulado para difundir fatos notoriamente inverídicos ou descontextualizados com potencial para causar danos ao equilíbrio do pleito ou à integridade do processo eleitoral." **§1º:** "É proibido o uso, para prejudicar ou para favorecer candidatura, de conteúdo sintético em formato de áudio, vídeo ou combinação de ambos, que tenha sido gerado ou manipulado digitalmente, **ainda que mediante autorização**, para criar, substituir ou alterar imagem ou voz de pessoa viva, falecida ou fictícia (deep fake)." **§2º:** descumprimento "configura abuso do poder político e uso indevido dos meios de comunicação social, acarretando a cassação do registro ou do mandato, e impõe apuração das responsabilidades nos termos do § 1º do art. 323 do Código Eleitoral".
- **Art. 3º-C** (2024): "A veiculação de **conteúdo político-eleitoral em período que não seja o de campanha eleitoral** se sujeita às regras de transparência previstas no art. 27-A desta Resolução e de uso de tecnologias digitais previstas nos arts. 9º-B, caput e parágrafos, e 9º-C desta Resolução, que deverão ser cumpridas, no que lhes couber, pelos provedores de aplicação e **pelas pessoas e entidades responsáveis pela criação e divulgação do conteúdo**."
- **Art. 9º-D** (2024): deveres das plataformas (termos de uso, canais de denúncia, avaliação de impacto em ano eleitoral). **§6º (NOVO 2026):** juiz que suspender perfil indicará prazo.
- **Art. 9º-E** (2024; incisos V, VI, VII alterados/incluídos em 2026): plataformas "solidariamente responsáveis, civil e administrativamente, quando não promoverem a indisponibilização imediata de conteúdos e contas, durante o período eleitoral", em casos de: atos antidemocráticos; desinformação sobre o processo eleitoral; ameaça a membros da JE; discurso de ódio; **"V – de divulgação ou compartilhamento de conteúdo sintético gerado ou modificado por inteligência artificial ou por tecnologia equivalente, em desacordo com as regras de rotulagem ou incidente nas vedações previstas nesta Resolução"** (redação 2026); **VI (NOVO 2026)** – republicação de conteúdo já removido por ordem judicial; **VII (NOVO 2026)** – violência política contra a mulher.
- **Art. 9º-F/9º-G:** vinculação de juízes às decisões do TSE sobre desinformação contra o processo eleitoral e repositório público de decisões (https://temasselecionados.tse.jus.br/temas-selecionados/repositorio-decisoes-sobre-enfrentamento-desinformacao-eleitoral).
- **Art. 9º-H** (2024): remoção não impede a multa do art. 57-D.
- **Art. 9º-I (NOVO 2026):** inversão do ônus da prova em representações sobre IA quando for "excessivamente oneroso ao autor demonstrar a irregularidade"; o representado deve "demonstrar como e em quais etapas da produção de conteúdo o recurso de inteligência artificial foi empregado, bem como a veracidade da informação veiculada".
- **Art. 9º-J (NOVO 2026):** convênios com universidades para perícia de IA.
- **Art. 10, §1º-A** (2024): a vedação de "meios publicitários" para criar estados emocionais "incide sobre o uso de ferramentas tecnológicas para adulterar ou fabricar áudios, imagens, vídeos [...] destinadas a difundir fato falso ou gravemente descontextualizado sobre candidatas, candidatos ou sobre o processo eleitoral."
- **Art. 28, §1º-C (NOVO 2026):** "É vedado aos provedores de aplicação que ofertem sistemas de inteligência artificial ou por tecnologia equivalente, **ainda que solicitado pela(o) usuária(o)**: I – **ranquear, recomendar, sugerir ou priorizar** candidatas(os), campanhas, partidos políticos, federações ou coligações; II – **emitir opiniões, indicar preferência eleitoral, recomendar voto ou realizar qualquer forma de favorecimento ou desfavorecimento político-eleitoral**, de maneira direta ou indireta, **inclusive por meio de respostas automatizadas**; III – criar/alterar imagem com sexo, nudez ou pornografia envolvendo candidato; IV – formular publicidade eleitoral que represente violência política contra a mulher."
- **Art. 28, §§4º-A a 4º-C (NOVOS 2026):** plataformas devem remover sem ordem judicial conteúdo que descredibilize o sistema eletrônico de votação, incite crimes contra o Estado Democrático, fomente ruptura institucional ou violência política contra a mulher, com direito de recurso do usuário.
- **Art. 29, §1º** (redação 2026): vedação de propaganda gratuita estendida a "sítios **ou perfis em redes sociais**" de pessoas jurídicas. **§8º** (redação 2026): inclui "mecanismos de competição, ranqueamento ou premiação" como forma de propaganda paga vedada. **§11** (2024): entre 48h antes e 24h depois da eleição é vedada "circulação paga ou impulsionada de propaganda eleitoral na internet".
- **Art. 2º, §5º (NOVO 2026):** propaganda intrapartidária na internet observa os arts. 57-C a 57-J. **Art. 3º, VIII (NOVO 2026):** manifestação espontânea em ambientes universitários/comunitários não é propaganda antecipada. **Art. 28, §1º-B (NOVO 2026):** endereços eletrônicos preexistentes não informados no RRC/DRAP só podem ser usados 48h após registro.

**Outras mudanças de 2026 relatadas por fontes secundárias (não vi o texto):** planos de conformidade de plataformas com mais de 5 milhões de usuários mensais até 16/8/2026 (Judit.io; Estado de Minas 1/9/2026). **[S]** — https://judit.io/legislacao-atualizacoes-regulatorias/mercado-juridico-regras-ia-eleicoes-2026/ ; https://www.em.com.br/mundo-corporativo/2026/09/7502136-tse-esclarece-regras-sobre-deepfake-nas-eleicoes-de-2026.html . Panorama do ciclo normativo: Conjur 30/3/2026 **[S]** — https://conjur.com.br/2026-mar-30/tse-conclui-ciclo-normativo-de-2026-e-atualiza-disciplina-sobre-propaganda-ia-contas-e-acessibilidade/

**Jurisprudência 2026 sobre IA [P]:**

- **Tese do TSE de 1/9/2026 (5x2):** "Para os fins do art. 9º-C, § 1º, [...] a caracterização de deepfake pressupõe conteúdo sintético produzido ou manipulado mediante inteligência artificial ou tecnologia equivalente, dotado de grau de realismo ou verossimilhança e destinado a criar, reproduzir ou alterar imagem, voz ou manifestação de pessoa viva, falecida ou fictícia. A incidência da vedação ao emprego de deepfake prevista no art. 9º-C, § 1º, **pressupõe a caracterização do conteúdo como propaganda eleitoral**." (caso do vídeo de IA de Jair Bolsonaro na convenção do PL; pedido de multa contra Flávio Bolsonaro rejeitado por 4x3). — https://www.tse.jus.br/comunicacao/noticias/2026/Setembro/tse-fixa-tese-sobre-deepfake-e-delimita-regra-para-as-eleicoes-2026
- **Deepfake é ilícito de natureza objetiva:** vídeo com Obama, Taylor Swift, Tom Cruise e Cristiano Ronaldo "falando" apoio ao candidato → multa "independentemente da comprovação de potencialidade para induzir o eleitor em erro" (Ac. 8/5/2026, AgR-REspEl 0600201-63, rel. Min. Villas Bôas Cueva).
- Divergência sobre realismo x rotulagem entre TREs em 2024 (TRE-MS exigiu realismo; TRE-MG considerou irrelevante o aviso de IA) e decisão do Min. André Mendonça em 2026 (vídeo fotorrealista de Flávio Bolsonaro com Vorcaro removido apesar de caráter satírico): coluna Metrópoles/Observatório IDP. **[S]** — https://www.metropoles.com/colunas/observatorio-das-eleicoes/eleicoes-2026-o-que-os-tribunais-estao-decidindo-sobre-deepfakes

**As regras de rotulagem valem para qualquer conteúdo ou só para propaganda?**

Leitura do texto **[P]** com interpretação minha:
- O **caput do art. 9º-B** fala em "propaganda eleitoral, em qualquer modalidade" e em "responsável pela propaganda". A notícia oficial do TSE de abril/2026 descreve a regra como dirigida a "partidos, candidatos e provedores de internet" e diz que o aviso "vale para textos, áudios, vídeos e imagens". — https://www.tse.jus.br/comunicacao/noticias/2026/Abril/por-dentro-das-eleicoes-conheca-as-regras-sobre-uso-de-ia-na-campanha-eleitoral-de-2026
- Porém **três dispositivos alargam o alcance para além de candidatos:** (i) **art. 3º-C** submete "conteúdo político-eleitoral em período que não seja o de campanha" às regras dos arts. 9º-B e 9º-C, "no que lhes couber", para "pessoas e entidades responsáveis pela criação e divulgação do conteúdo"; (ii) **art. 9º-E, V** responsabiliza plataformas por conteúdo sintético sem rótulo em geral, o que na prática leva as plataformas a remover conteúdo de terceiros sem rótulo; (iii) **art. 27, §1º** aponta o art. 9º-C como limite da manifestação de qualquer eleitor. Além disso, o art. 37, XXXV define conteúdo sintético incluindo "texto".
- A tese de 1/9/2026 fixou que a **proibição de deepfake** (9º-C, §1º) exige que o conteúdo seja propaganda; **não** decidiu o mesmo para a rotulagem do 9º-B nem para o art. 3º-C.
- **Conclusão prática:** a incidência direta da rotulagem sobre um site informativo de pessoas físicas é discutível, mas o custo de rotular ("Resumo gerado por IA [modelo X] a partir das votações oficiais; revisado em DD/MM") é zero e fecha a discussão, inclusive perante plataformas que reproduzam o conteúdo.

**(b) O que significa para o projeto (IA)**

1. **Rotular todo texto gerado por IA** de forma explícita, destacada e acessível, com a tecnologia usada (art. 9º-B, §1º por analogia).
2. **Nunca gerar imagem, áudio ou vídeo de parlamentar/candidato**, mesmo com aviso, mesmo satírico: art. 9º-C, §1º + natureza objetiva (TSE 8/5/2026). Avatares/ilustrações genéricas sem semelhança com pessoa real ficam fora do conceito de deepfake da tese de 1/9/2026.
3. **Janela de silêncio de IA:** entre 00h de 1/10 e 24h após o fim da votação de 4/10 (e entre 22/10 e 26/10 para o 2º turno), **não publicar conteúdo sintético novo que use "imagem, voz ou manifestação" de candidato** (art. 9º-B, §3º-A). "Manifestação" é termo vago: um resumo de IA que parafraseie discursos do candidato pode ser enquadrado. Recomendação conservadora: congelar a geração de novos resumos de IA sobre candidatos nesses períodos, mantendo apenas dados brutos e resumos publicados antes da janela.
4. **Nenhum chatbot que simule o candidato** (art. 9º-B, §3º) e **nenhuma função de IA que recomende, ranqueie ou opine sobre em quem votar** (art. 28, §1º-C), inclusive em respostas automatizadas a perguntas de usuários ("qual deputado devo escolher?" → a resposta deve ser recusa ou dados brutos). Como o art. 37, XVIII inclui pessoa natural amadora no conceito de provedor de aplicação, o site pode ser enquadrado literalmente.
5. **Rastreabilidade:** guardar prompt, modelo, data e fontes de cada resumo. O art. 9º-I permite inversão do ônus da prova: o representado deve "demonstrar como e em quais etapas [...] a inteligência artificial foi empregada, bem como a veracidade da informação".
6. **Resumo de IA é imputação factual:** vale toda a seção 5 (fato x opinião). Alucinação que atribua voto ou fala inexistente a candidato é "fato inverídico" sob o art. 27, §1º e, se houver dolo, art. 323 do Código Eleitoral (o dolo é improvável em erro de IA, mas a responsabilidade civil e o direito de resposta independem de dolo).

**(c) Certeza:** texto da Res. 23.610/23.755 [P]; notícias oficiais do TSE [P]; alcance da rotulagem a terceiros é interpretação minha sobre texto primário; plano de conformidade de 5 milhões de usuários [S].

---

## 3. Jurisprudência sobre sites/apps de informação política

### 3.1 Casos nominais procurados

Procurei decisões do TSE/TREs envolvendo **Ranking dos Políticos, Politicos.org.br, Congresso em Foco (Prêmio), Meu Deputado, Vigie Aqui, Voto Consciente/TemMeuVoto, Atlas Político, Aos Fatos, Lupa e Deputômetro** em: portal Temas Selecionados (propaganda eleitoral, internet, redes sociais, pesquisa eleitoral, direito de resposta, desinformação), busca aberta e Jusbrasil. **Não localizei nenhuma representação, direito de resposta ou ordem de remoção contra esses sites por exibir dados de votação. [NV]**

O que existe de fato verificável:
- **Ranking dos Políticos** (ranking.org.br): think tank fundado em 2011, avalia deputados e senadores com dados oficiais; realiza o Prêmio Excelência Parlamentar desde 2016; em 2026 divulgou metodologia e premiados durante o ano eleitoral. **[S]** — https://ranking.org.br/en ; https://ranking.org.br/en/articles/metodologia-ranking-dos-politicos-calculo-nota
- **Prêmio Congresso em Foco 2026** entregue em setembro/2026 (voto popular, júri de jornalistas e júri técnico); candidatos à reeleição usaram os resultados como material de campanha (ND+). **[S]** — https://ndmais.com.br/politica/premio-congresso-em-foco-candidatos-reeleicao/ ; https://premio.congressoemfoco.com.br/
- **TemMeuVoto** (Coalizão pelo Voto Consciente: RenovaBR, CLP, Instituto Ethos, Transparência Internacional Brasil e outras 10 organizações; operado por empresa de desenvolvimento) lançado em **16/9/2026**, em plena campanha, cruzando respostas de candidatos com preferências do eleitor, "não recomenda candidatos". **[S]** — https://www.poder360.com.br/poder-eleicoes-2026/organizacao-lanca-ferramenta-que-compara-eleitores-e-candidatos-ao-legislativo/ . É um exemplo de ferramenta mantida por pessoas jurídicas operando durante a campanha sem notícia de sanção, mas isso não é precedente jurídico.
- **Deputômetro** (Agência Lupa, 31/7/2026): painel dos 513 deputados com votos, emendas e gastos. **[S]** — https://www.agencialupa.org/painel-deputometro/2026/07/31/deputometro-consulte-os-projetos-de-lei-e-votos-em-plenario-de-cada-deputado-federal/
- **ComoVotou.org**: histórico de votações, gastos e proposições. **[S]** — https://comovotou.org/
- **Agências de checagem:** o TSE tem termos de cooperação com Lupa, Aos Fatos, Comprova etc.; a Res. 23.610, art. 9º, §§1º-2º dá a essas checagens papel de parâmetro do dever de diligência dos candidatos. **[P]**

### 3.2 Critérios do TSE aplicáveis por analogia (todos [P], portal Temas Selecionados)

**Distinção crítica x imputação factual (a decisão mais útil para o projeto):**
> "A distinção entre crítica política protegida e imputação factual ilícita decorre da natureza da mensagem: a crítica política exprime avaliação, associação retórica ou juízo de valor, enquanto a imputação factual atribui conduta concreta e objetivamente verificável, sujeita a juízo de veracidade e à exigência de lastro mínimo." (Ac. 14/8/2026, Ref-Rp 0601107-16, rel. Min. André Mendonça — caso Flávio Bolsonaro; removidas afirmações sem lastro de que teria abandonado funções e recebido ingresso suspeito.)
— https://temasselecionados.tse.jus.br/temas-selecionados/propaganda-eleitoral/critica-politica/generalidades

**Crítica ao desempenho no cargo é normal:**
> "o ato de questionar o desempenho dos candidatos no exercício dos cargos públicos que ocupam ou ocuparam é corriqueiro no debate eleitoral, caracterizando crítica normal a que se submetem as personagens da vida pública" (Ac. 3/4/2025, AgR-AREspE 0600107-27, rel. Min. André Mendonça).

**Três requisitos alternativos da propaganda negativa:**
> "(a) pedido explícito de não voto; (b) desqualificação da honra ou imagem do pré-candidato; ou (c) divulgação de fato sabidamente inverídico" (Ac. 19/3/2026, AgR-AREspE 0600026-54; Ac. 23/4/2025, AgR-AREspE 0600070-92, entre muitos).

**Sem intervenção imediata se não há pedido de não voto nem falsidade manifesta (Eleições 2026):**
> "A divulgação de propaganda eleitoral antecipada que não contenha pedido explícito de não voto nem revela manifesta falsidade ou grave descontextualização não justifica a intervenção imediata e restritiva da Justiça Eleitoral." (Ac. 14/8/2026, Ref-Rp 0601215-45, rel. Min. Nunes Marques.)

**Fato sabidamente inverídico exige verificação imediata e objetiva:**
> "a configuração de propaganda ilícita negativa por veiculação de fato sabidamente inverídico pressupõe verificação imediata, inequívoca e objetiva da falsidade, sem necessidade de dilação probatória" (Ac. 18/12/2025, AgR-AREspE 0600057-43, rel. Min. Estela Aranha). "Os fatos sabidamente inverídicos a ensejar a ação repressiva da Justiça Eleitoral são aqueles verificáveis de plano" (Ac. 11/5/2023, AgR-REspEl 0600387-44).

**"Responde na justiça" ancorado em fato real não é inverídico:**
> "a expressão 'responde na justiça' não pode ser enquadrada nessa conceituação, uma vez que a notícia divulgada está ancorada em uma premissa fática real e não altera de maneira profunda o sentido da informação. [...] O eleitorado comum não possui o conhecimento sobre termos técnicos jurídicos" (Ac. 27/8/2026, REspEl 0600588-92, rel. Min. Antonio Carlos Ferreira).

**Descontextualização de proposta é desinformação:**
> "A supressão de elemento essencial de proposta de governo, com alteração relevante de seu sentido original, caracteriza propaganda irregular por veiculação de desinformação" → multa art. 57-D (Ac. 6/8/2026, AgR-AREspE 0600467-94, rel. Min. Estela Aranha). Relevante para "prometeu X": não cortar a promessa do contexto.

**Fatos pretéritos da vida pública podem ser criticados:**
> "não configura ofensa contra a honra ou a imagem de candidatos a realização de críticas com base em acontecimentos pretéritos da vida de figuras públicas" (Ac. 2/10/2025, AgR-AREspE 0600418-70, rel. Min. Isabel Gallotti). Vídeo com fatos notórios e material jornalístico da época (condenações de Lula depois anuladas) mantido no ar (Ac. 26/10/2023, Ref-Rp 0601192-41).

**Matéria jornalística/blog com crítica contundente é liberdade de expressão:**
> "Matéria jornalística publicada em blogue contra a atuação administrativa de candidato à reeleição ao governo estadual. Alegada ofensa à honra. Inocorrência" (Ac. 31/5/2024, AgR-AREspE 0601532-06, rel. Min. Cármen Lúcia). "A mera abordagem [...] de supostos fatos veiculados na imprensa envolvendo a gestão pretérita de candidato, enquanto agente político, não ultrapassa os limites da liberdade de imprensa e do direito à informação" (Ac. 3/5/2024, AgR-REspEl 0601495-44). "Conflita com o Estado Democrático de Direito o estabelecimento de severas e automáticas restrições à liberdade de expressão com supedâneo no mero início do período eleitoral" (mesmo acórdão).
— https://temasselecionados.tse.jus.br/temas-selecionados/propaganda-eleitoral/imprensa-escrita/materia-jornalistica ; https://temasselecionados.tse.jus.br/temas-selecionados/propaganda-eleitoral/liberdade-de-expressao

**Mas o pêndulo também pune:** "quadrilha do ladrão" em paródia = ofensa pessoal, multa (Ac. 9/6/2026, REspEl 0600486-53); "propaga intolerância religiosa, persegue, incita o ódio e mente" = extrapolação (Ac. 23/4/2025, AgR-REspEl 0600279-08); imputar homicídio, coação e corrupção "sem que se apresentem dados fáticos concretos" = multa (Ac. 6/8/2026, AgR-AREspE 0600536-34); site com matéria que imputa crimes nominalmente a pré-candidato = discurso de ódio (Ac. 25/4/2024, REspEl 0600408-42, deputado estadual/ES, "matéria veiculada em website").

**Alcance limitado não exclui, mas atenua:** mensagens em grupo pequeno de WhatsApp sem viralização não caracterizam propaganda irregular (Ac. 26/3/2026, AgR-REspEl 0600332-80); grupo de 474 membros com vídeo distorcido caracteriza (Ac. 19/3/2026, AgR-REspEl 0600523-82). Um site público de alcance nacional está na ponta de maior exposição.

**Multa do art. 57-D não exige anonimato:** "a aplicação da multa do art. 57-D da Lei n. 9.504/97 não se restringe aos casos de anonimato, alcançando as hipóteses de abuso da liberdade de expressão na propaganda eleitoral com disseminação de conteúdo difamante ou sabidamente inverídico veiculada por meio da internet" (Ac. 17/8/2026, AgR-AREspE 0600486-58, rel. Min. Dias Toffoli). Ou seja, pessoa identificada também paga R$ 5 mil a R$ 30 mil.

### 3.3 Síntese para o projeto

- Não há precedente diretamente contra sites de dados parlamentares, positivo ou negativo. **[NV]** O corpo jurisprudencial disponível protege fortemente (i) dados verdadeiros com fonte, (ii) crítica ao desempenho no cargo, (iii) reprodução de fatos notórios/noticiados, e pune (i) imputação factual sem lastro mínimo, (ii) adjetivos que insinuem crime sem condenação ou acusação formal, (iii) descontextualização que altere o sentido, (iv) qualquer impulsionamento de conteúdo negativo.
- O padrão de decisão do TSE em 2024–2026 é: "crítica = juízo de valor protegido; imputação = fato que precisa de lastro". Um site de dados é 100% imputação factual; sua proteção depende inteiramente do **lastro** (link para a fonte oficial em cada dado) e da **exatidão**.

---

## 4. Período eleitoral (16/08 a 04/10, e até 25/10)

**(a) O que diz a norma [P]**

- Não há norma que proíba terceiros não candidatos e não imprensa de publicar análises sobre candidatos no período eleitoral. O regime é o do art. 57-D da Lei 9.504 ("é livre a manifestação do pensamento, vedado o anonimato") e dos arts. 27, §1º e 28, §6º da Res. 23.610 (limites: honra/imagem, fatos sabidamente inverídicos).
- **A vedação de "propaganda negativa" não se aplica só a candidatos.** Os requisitos (pedido de não voto, ofensa à honra, fato inverídico) e a multa do art. 57-D, §2º alcançam "o responsável pela divulgação", e a jurisprudência aplica a pessoas naturais não candidatas (Ac. 18/12/2025, AgR-REspEl 0600037-91: pessoa natural impulsionando conteúdo negativo; Ac. 2/10/2025, AgR-AREspE 0600338-59: "matéria de cunho jornalístico. Pessoa natural. Liberdade de expressão"). A diferença é que, para não candidatos, a crítica orgânica é protegida com mais força (art. 28, §6º), enquanto para candidatos há o dever de verificação do art. 9º e a possibilidade de abuso de poder/cassação (art. 9º-C, §2º; LC 64/90, art. 22).
- **Diferenças concretas do período eleitoral para um site de terceiros:**
  1. **Direito de resposta eleitoral ativo** (art. 58: "a partir da escolha de candidatos em convenção"; convenções de 20/7 a 5/8/2026), com rito de 24h/72h e legitimidade de qualquer candidato/partido. Fora do período, só ação na Justiça Comum (Lei 13.188/2015, direito de resposta civil, e responsabilidade civil por dano moral — tema da frente civil).
  2. **Representações do art. 96 da Lei 9.504** (rito de 48h, juízes auxiliares nos TREs e no TSE) para propaganda irregular, incluindo pedidos de remoção liminar sob o art. 38 da Res. 23.610. Após a eleição, ordens de remoção mantêm efeitos (art. 38, §7º) e procedimentos sobre anonimato/fato inverídico contra honra não perdem objeto (§8º-A).
  3. **Enquetes** relacionadas ao processo eleitoral vedadas após 15/8 (Lei 9.504, art. 33, §5º; Res. 23.600, art. 23).
  4. **Janelas de IA:** 72h antes até 24h depois de cada turno (Res. 23.610, art. 9º-B, §3º-A): 1/10–5/10 e 22/10–26/10.
  5. **48h antes até 24h depois:** vedada circulação paga/impulsionada de propaganda na internet (art. 29, §11), irrelevante se o site não impulsiona.
  6. **Dia da eleição (4/10 e 25/10):** Lei 9.504, art. 39, §5º, III: "Constituem crimes, no dia da eleição, puníveis com detenção de seis meses a um ano [...] III – a divulgação de qualquer espécie de propaganda de partidos políticos ou de seus candidatos." A Res. 23.610, art. 5º, parágrafo único, exclui da vedação de 48h a propaganda gratuita dos próprios candidatos na internet, mas o art. 39, §5º é lei penal para o dia da votação. Conteúdo informativo não é "propaganda de partidos ou candidatos", mas o risco de interpretação recomenda não enviar notificações/newsletters nominais no dia.
  7. **Crime do art. 323 do Código Eleitoral** (seção 5) aplica-se "na propaganda eleitoral **ou durante período de campanha eleitoral**" (Lei 14.192/2021), logo a qualquer pessoa entre 16/8 e a eleição.
- **Pré-campanha (antes de 16/8):** art. 27, §2º da Res. 23.610: apoio e crítica são "regidas pela liberdade de manifestação". Ainda assim, propaganda antecipada negativa é punível (Ac. 14/8/2026, Ref-Rp 0601107-16) quando há imputação sem lastro.

**(b) Publicar em 1/10 versus novembro: o risco é diferente?**

- **O direito material é o mesmo.** Dados verdadeiros com fonte oficial e linguagem descritiva são lícitos em qualquer data. Ofensa à honra e fato inverídico são ilícitos em qualquer data (na Justiça Eleitoral durante o período; na Justiça Comum depois).
- **O que muda em 1/10:** (i) o site nasce na semana de maior litigiosidade eleitoral, quando qualquer candidato ou partido tem legitimidade e um rito de 48h para pedir remoção/direito de resposta; (ii) a janela de IA de 1/10 a 5/10 impede resumos de IA novos sobre candidatos exatamente no lançamento; (iii) o interesse público é máximo, o que é a razão de existir do projeto; (iv) juízes auxiliares decidem liminares em horas, e a Res. 23.610, art. 38, §4º exige URL específica: uma liminar tende a atingir páginas individuais, não o site inteiro, salvo a hipótese extrema do art. 57-I (suspensão de até 24h do acesso, a requerimento de candidato/partido, para "conteúdo que deixar de cumprir as disposições desta Lei").
- **O que muda em novembro:** nenhum candidato em disputa; o direito de resposta eleitoral deixa de existir; sobra a responsabilidade civil comum e a LGPD (outras frentes). Em contrapartida, o site perde o momento em que o dado importa para o eleitor.
- **Recomendação:** lançar em 1/10 é juridicamente defensável **se** o conteúdo for exclusivamente descritivo e com fonte, sem impulsionamento, sem IA gerando conteúdo novo sobre candidatos até 5/10, com identificação dos responsáveis e canal de correção. Se houver dúvida sobre a exatidão de algum módulo (por exemplo, "alinhamento com governo" com metodologia ainda instável), lançar esse módulo depois de 25/10.

**(c) Certeza:** [P] para normas e ementas; a análise comparativa de datas é opinião fundamentada no texto.

---

## 5. Fato x opinião e honra — Código Eleitoral, arts. 323 a 326

Fonte primária: https://www.planalto.gov.br/ccivil_03/leis/l4737compilado.htm **[P]**

**(a) O que diz a norma**

- **Art. 323** (redação Lei 14.192/2021): "Divulgar, na propaganda eleitoral **ou durante período de campanha eleitoral**, fatos que **sabe inverídicos** em relação a partidos ou a candidatos e capazes de exercer influência perante o eleitorado: Pena – detenção de dois meses a um ano, ou pagamento de 120 a 150 dias-multa." **§1º:** mesmas penas a quem "produz, oferece ou vende vídeo com conteúdo inverídico". **§2º:** aumento de 1/3 até metade se cometido "por meio da imprensa, rádio ou televisão, ou por meio da internet ou de rede social, ou é transmitido em tempo real" (I) ou envolve menosprezo à condição de mulher ou à cor/raça/etnia (II).
- **Art. 324:** "Caluniar alguém, na propaganda eleitoral, ou visando fins de propaganda, imputando-lhe falsamente fato definido como crime: Pena – detenção de seis meses a dois anos". **§1º:** quem propala sabendo falsa. **§2º:** exceção da verdade admitida, salvo se o fato é imputado ao Presidente da República (II) ou houve absolvição irrecorrível (III).
- **Art. 325:** "Difamar alguém, na propaganda eleitoral, ou visando a fins de propaganda, imputando-lhe fato ofensivo à sua reputação: Pena – detenção de três meses a um ano". **Parágrafo único:** "A exceção da verdade somente se admite se ofendido é funcionário público e a ofensa é relativa ao exercício de suas funções." (Parlamentares e Presidente são funcionários públicos em sentido penal; a atuação parlamentar é exercício de funções: a prova da verdade é admitida.)
- **Art. 326:** "Injuriar alguém, na propaganda eleitoral, ou visando a fins de propaganda, ofendendo-lhe a dignidade ou o decoro: Pena – detenção até seis meses". Não admite exceção da verdade (é ofensa à honra subjetiva, não imputação de fato).
- **Art. 326-A** (Lei 13.834/2019): denunciação caluniosa com finalidade eleitoral (reclusão 2 a 8 anos); §3º: quem divulga sabendo da inocência.
- **Art. 326-B** (Lei 14.192/2021): violência política contra a mulher (assediar, constranger, humilhar candidata ou detentora de mandato com menosprezo à condição de mulher/cor/raça), reclusão 1 a 4 anos.
- **Res. 23.610, art. 22, IX/X** (via Código Eleitoral, art. 243, IX): não será tolerada propaganda "que caluniar, difamar ou injuriar qualquer pessoa"; **art. 23:** ação cível de dano moral independente da penal.

**(b) Aplicação a métricas factuais com metodologia declarada**

- **Elemento subjetivo:** os arts. 323 a 326 exigem dolo. O art. 323 exige que o agente "sabe inverídicos" (dolo direto quanto à falsidade). Erro de dado colhido de fonte oficial, com metodologia pública e correção rápida quando apontado, não preenche o tipo. A responsabilidade eleitoral não penal (art. 57-D, §2º; direito de resposta) usa o conceito de "fato sabidamente inverídico", que a jurisprudência lê como falsidade "verificável de plano", "imediata, inequívoca e objetiva".
- **Métricas verdadeiras não são difamação:** difamação é imputar "fato ofensivo à reputação"; se o fato é verdadeiro e diz respeito ao exercício da função pública, cabe exceção da verdade (art. 325, parágrafo único). Publicar "votou contra o PL X" ou "esteve ausente em N sessões" é fato relativo à função; a prova é o registro oficial.
- **Onde métricas viram problema:**
  1. **Categoria mal rotulada.** A Câmara registra ausências justificadas (licença, missão oficial, atestado) separadamente. Chamar de "faltou" o que a Casa registra como "ausência justificada" é descontextualização (TSE: "supressão de elemento essencial [...] com alteração relevante de seu sentido"). Solução: usar as categorias oficiais e explicar a metodologia.
  2. **Voto x orientação.** "Votou contra o governo" só é fato se houver orientação registrada da liderança do governo naquela votação. Sem orientação, dizer "contra o governo" é inferência. Rotular como "alinhamento com a orientação do líder do governo (quando existente)".
  3. **Obstrução, abstenção, "art. 17"** (presidente da Casa não vota) e votações simbólicas: não existe voto nominal; não atribuir posição.
  4. **Adjetivos.** "Fisiológico", "traidor", "vendido", "inútil", "corrupto", "mentiroso" são juízos de valor que, ligados a uma pessoa nominada, o TSE tem tratado como ofensa à honra (casos "quadrilha do ladrão", "persegue, incita o ódio e mente"). A métrica de 2022 do TSE: adjetivo que insinue crime só com condenação ou acusação formal.
  5. **Processos judiciais.** "Responde a processo" ancorado em registro real é lícito (TSE 27/8/2026); "é réu"/"condenado" só se for tecnicamente verdadeiro; indicar o número do processo e a fase.
- **"Prometeu X, votou Y":** é a hipótese de maior cuidado porque combina duas imputações factuais:
  1. **A promessa** tem que ter lastro documental (plano de governo registrado no TSE, entrevista com link e data, post do candidato arquivado). Promessa parafraseada por IA ou reconstruída de memória não tem "lastro mínimo". Citar textualmente e linkar.
  2. **O voto** tem que ser o registro nominal oficial, com link.
  3. **A conclusão** ("não cumpriu", "contradição") é juízo de valor sobre fatos verdadeiros e, em princípio, crítica protegida ("questionar o desempenho [...] é corriqueiro"). Mas a forma importa: "votou de modo diferente do que declarou em [data]" é descrição; "mentiu para o eleitor" ou "traiu quem votou nele" imputa desonestidade (injúria/difamação em potencial e "desqualificação da honra" para fins de propaganda negativa).
  4. **Contexto** que altere o sentido não pode ser omitido: se o candidato explicou publicamente o voto (por exemplo, votou contra por acordo para aprovar outra medida), o site deve ao menos linkar a justificativa oficial da votação ou o discurso de encaminhamento. A omissão não é ilícita em si (TSE 29/11/2024: omitir que era testemunha não tornou a notícia falsa), mas reduz o risco de "descontextualização grave".
  5. Dar ao parlamentar um **espaço de resposta no próprio site** (campo "resposta do parlamentar") antes de qualquer decisão judicial desarma o direito de resposta.
- **Presidente da República:** o art. 324, §2º, II exclui a exceção da verdade em calúnia contra o Presidente; irrelevante para dados de votação, mas relevante se o site vier a imputar crime ao Presidente.

**(c) Certeza:** texto legal [P]; ementas [P]; a aplicação a métricas é análise minha sobre fontes primárias.

---

## 6. Mapa de risco: o que pode sem restrição, o que exige cuidado, o que não fazer

### 6.1 Sem restrição eleitoral relevante (fonte: Lei 9.504, arts. 36-A, IV e 57-B, IV, "b"; Res. 23.610, arts. 27, §§1º-2º, 28, §6º, 38)
- Exibir votações nominais, presença/ausência conforme categorias oficiais, proposições apresentadas, relatorias, discursos, com link para a fonte oficial (Câmara, Senado, Planalto, TSE).
- Exibir dados de campanha públicos do TSE (bens, partido, número) sem comentário valorativo.
- Explicar metodologia, glossário, como funciona uma votação.
- Permitir filtros, comparações lado a lado e download de dados brutos.
- Identificar-se ("Quem somos": nomes, contato).
- Publicar antes de 16/8, entre 16/8 e 4/10 ou depois: o regime de dados verdadeiros e descritivos não muda.

### 6.2 Exige cuidado (fonte: Res. 23.610, arts. 9º-B, 9º-C, 27, §1º, 28, §1º-C; Lei 9.504, arts. 33, 57-D, 58; CE arts. 323-326; jurisprudência 2024–2026)
- **Rankings e índices:** lícitos se baseados em dados de mandato e com metodologia pública; evitar vocabulário de pesquisa ("aprovação", "intenção de voto", "favorito", "líder", "%" de preferência). Nunca "chance de reeleição". Não encontrei precedente contrário, mas também nenhum favorável específico.
- **Adjetivos e rótulos:** substituir por descrição. Nunca adjetivos que insinuem crime ou desonestidade sem condenação/acusação formal.
- **"Cumpriu / não cumpriu":** só com citação textual e link da promessa, link do voto, e espaço para justificativa do parlamentar. Preferir "votou em sentido oposto à declaração de [data]" a "descumpriu"/"mentiu".
- **Alinhamento governo/partido:** só onde há orientação registrada; explicar o tratamento de abstenção, obstrução e ausência.
- **Resumos por IA:** rotular sempre (tecnologia + data + fonte); revisão humana antes de publicar; nunca imagem/áudio/vídeo de pessoa real; nenhuma recomendação/preferência/ranking gerado por IA; congelar geração de conteúdo novo sobre candidatos em 1–5/10 e 22–26/10; guardar prompts e fontes (art. 9º-I).
- **Chatbot:** se existir, não pode simular candidato (art. 9º-B, §3º), não pode ranquear/recomendar/opinar sobre voto (art. 28, §1º-C), deve responder com dados e links.
- **Enquetes/votações de usuários sobre parlamentares candidatos:** não até 26/10 (Lei 9.504, art. 33, §5º).
- **Republicar pesquisas de terceiros:** só com número de registro no PesqEle (quem compartilha responde).
- **Correções:** canal público, prazo interno de 24h, registro de erratas.
- **Dia da eleição:** sem push/newsletter nominal.
- **Doações do público:** regra pública recusando partidos, candidatos, filiados e empresas.

### 6.3 Não fazer (fonte: Lei 9.504, arts. 57-B, IV, "b", 57-C, 57-D; Res. 23.610, arts. 28, IV, "b", 2; 29, §8º; 9º-C, §1º; jurisprudência)
- **Impulsionamento pago** de qualquer página que mencione candidato, positivo ou negativo, "neutro" ou não, até 25/10 (TSE 18/12/2025; 25/6/2026; 22/9/2026).
- **Constituir pessoa jurídica** (inclusive associação sem fins lucrativos ou MEI) como titular do site enquanto houver conteúdo sobre candidatos em período eleitoral (art. 57-C, §1º, I; TSE 16/4/2026).
- **Receber dinheiro, serviço ou vantagem** de partido, candidato, campanha ou "terceiro" interessado (art. 28, IV, "b", 2; art. 29, §8º).
- **Anonimato** ou identidade falsa (art. 57-D; art. 57-B, §2º).
- **Pedir voto ou não voto**, inclusive por equivalentes ("vote consciente em quem...", "não reeleja...", "vamos tirar...").
- **Deepfake** ou qualquer conteúdo sintético com imagem/voz de parlamentar, mesmo rotulado, mesmo satírico (art. 9º-C, §1º; TSE 8/5/2026).
- **Bots, automação de engajamento, disparo em massa** (art. 57-B, §3º; Res. 23.610, art. 34, II).
- **Imputar crime ou desonestidade** sem condenação/acusação formal; **descontextualizar** proposta, voto ou declaração.

---

## Recomendações práticas (ordem de prioridade)

1. **Manter titularidade em pessoas físicas** (domínio, hospedagem, redes sociais do projeto). Não abrir CNPJ antes de 26/10/2026; se abrir depois, reavaliar antes das eleições de 2028.
2. **Página "Quem somos" com nomes completos e contato**, e página "Metodologia e fontes" com fórmula de cada índice, categorias oficiais usadas, data de corte e links para as APIs/portais oficiais.
3. **Zero anúncios pagos e zero automação de engajamento até 25/10/2026.**
4. **Política de doações** publicada: não aceitar de partidos, candidatos, filiados, campanhas e empresas; manter registro.
5. **Linguagem descritiva por padrão** ("votou sim/não/abstenção/obstrução", "ausência justificada/não justificada conforme registro da Casa", "alinhado à orientação do líder do governo em N de M votações com orientação"). Lista interna de termos proibidos (adjetivos, "mentiu", "traiu", "corrupto", "aprovação", "intenção de voto", "favorito", "chance de reeleição").
6. **Módulo "promessa x voto" só com citação textual + link datado da promessa + link do voto + campo de resposta do parlamentar**, e sem verbo de juízo ("descumpriu", "mentiu"); usar "votou em sentido oposto à declaração".
7. **IA:** rótulo padrão em todo resumo ("Texto gerado por IA [modelo], revisado em DD/MM/AAAA, a partir de [fontes]"); nenhum conteúdo visual/sonoro sintético de pessoa real; nenhuma função que recomende ou ranqueie candidatos; congelar geração de conteúdo novo sobre candidatos de 1/10 00h a 5/10 e de 22/10 a 26/10; logs de prompts e fontes.
8. **Canal "Reportar erro"** com SLA interno de 24h, log público de correções e snapshot da fonte oficial no momento da coleta (hash + timestamp).
9. **Nenhuma enquete/votação de usuários** sobre parlamentares candidatos até 26/10; nenhuma republicação de pesquisa sem número de registro.
10. **No dia 4/10 e 25/10**, não enviar push/newsletter com nomes de candidatos.
11. **Se receber intimação eleitoral (representação ou direito de resposta):** prazos são de 24h a 48h e correm em fins de semana (Res. 23.608); ter um advogado eleitoralista identificado de antemão e o material de lastro (fontes, metodologia, logs) pronto para juntar.
12. **Disclaimer** no rodapé: "Este site não apoia nem se opõe a candidaturas, partidos ou federações. Todos os dados provêm de fontes oficiais indicadas em cada página. Não recebe recursos de partidos, candidatos ou campanhas."

## Não consegui verificar

- **Nenhuma decisão do TSE ou de TRE envolvendo especificamente** Ranking dos Políticos, Politicos.org.br, Congresso em Foco/Prêmio, Meu Deputado, Vigie Aqui, Voto Consciente/TemMeuVoto, Atlas Político, Aos Fatos, Lupa ou Deputômetro por propaganda irregular, direito de resposta ou remoção. Pode existir em 1º grau ou em TREs sem indexação nos ementários consultados.
- **Nenhuma decisão tratando "ranking/índice de desempenho parlamentar" como pesquisa eleitoral não registrada.** A conclusão de que não é pesquisa/enquete decorre do texto do art. 33 e da Res. 23.600, art. 23, §1º, e da jurisprudência sobre enquetes, não de precedente específico.
- **Alcance da rotulagem de IA (art. 9º-B) a conteúdo de terceiros não candidatos:** o texto restringe a "propaganda eleitoral"; o art. 3º-C estende a "conteúdo político-eleitoral" fora da campanha "no que couber"; não encontrei decisão do TSE aplicando o 9º-B a site informativo de pessoa física. A tese de 1/9/2026 tratou só do 9º-C, §1º.
- **Acórdão AgR 0608960-34.2018.6.26.0000 (exceção para imprensa na vedação a sites de PJ):** citado a partir de página do TRE-RS; não abri o inteiro teor.
- **Rp 355133-33.2010 (CUT):** citada a partir de página do MP-AM; não abri o inteiro teor.
- **Requisito de plano de conformidade para plataformas com mais de 5 milhões de usuários (Res. 23.755/2026):** só em fontes secundárias; não localizei o dispositivo no texto compilado que li (pode estar em artigo que não transcrevi).
- **Res. 23.608/2019 (representações):** li o art. 31; não li os artigos sobre prazos e legitimados na versão 2026 (alterada pela Res. 23.752/2026 ou similar; número não verificado).
- **Aplicação da AIJE/LC 64/90, art. 22 ("uso indevido dos meios de comunicação social")** a sites de terceiros que beneficiem candidato: não pesquisei jurisprudência específica; mencionado apenas como risco teórico para o candidato beneficiado.
- **Valor atualizado da UFIR para a multa do art. 33, §3º em 2026:** usei o valor de R$ 106.410 citado pelo TSE em dezembro de 2024.
- **Gazeta do Povo (20/7/2026)** sobre ampliação do conceito de propaganda antecipada: matéria sem números de processo; usada só como indicação de tendência.
