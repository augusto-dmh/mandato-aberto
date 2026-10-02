# Associação — forma jurídica, caminho até o CNPJ, governança e financiamento

> **Status:** pesquisa para decisão do mantenedor; nada aqui está decidido
> **Data:** 02/10/2026 (AD-007 vale até 26/10/2026; data-alvo da v2: 01/02/2027)
> **Pergunta:** como constituir a pessoa jurídica que a decisão 7 do grilling da v2 (`05-grilling-escopo-v2.md`) exige até 01/02/2027, sem perder a neutralidade que protege o projeto?
> **Entrada:** `01-pesquisa-juridica.md` (seção 2.1 e anexos A2, A3 e A4), `03-teste-de-balanceamento-lgpd.md`, `05-grilling-escopo-v2.md`, `06-pesquisa-design-e-concorrentes.md` (seção 7), `design-anexos/a3-pares-internacionais.md`, `.specs/STATE.md` (AD-007, AD-014).
> **Método:** leitura do texto compilado de cada lei no planalto.gov.br, da Res. TSE 23.610/2019 compilada no tse.jus.br (com as alterações da Res. 23.755/2026), da Res. CD/ANPD 2/2022 no gov.br e da tabela oficial de emolumentos de 2026 dos cartórios de títulos e documentos e pessoas jurídicas da capital paulista. Todos os textos foram lidos em 02/10/2026.
> **Aviso:** pesquisa feita por IA a partir de fontes públicas. **Não é parecer jurídico nem contábil.** A seção 8 lista o que advogado e contador precisam confirmar antes de qualquer assinatura.

**Legenda de certeza**
- **[P]** verificado na fonte primária, com o link e o artigo citados.
- **[S]** fonte secundária (guia, notícia, pesquisa anterior do projeto) ou fonte primária lida só por resumo.
- **[NV]** não verificado: estimativa de mercado, prática cartorária ou inferência minha.

---

## 0. Resumo para decidir

1. **Forma recomendada: associação civil sem fins lucrativos própria** (CC, arts. 53 a 61), com estatuto redigido desde o início nos moldes da Lei 9.790/1999 (arts. 3º, 4º e 16) e da Lei 13.019/2014 (art. 33). Fundação exige patrimônio dotado e tutela do Ministério Público; abrigo numa ONG existente é mais rápido, mas entrega controle editorial e titularidade a terceiro e não livra o site da vedação eleitoral a pessoa jurídica. Detalhes na seção 1.
2. **Calendário: assembleia de constituição em 27/10/2026 ou depois**, para respeitar AD-007. Do registro ao CNPJ e à conta bancária conto de 4 a 8 semanas [NV]. Meta: CNPJ até 15/12/2026, com folga até 01/02/2027. Seção 2.
3. **Custo de cartório verificado só para a capital de São Paulo:** R$ 214,20 pelo estatuto (até 5 páginas e 3 vias) e R$ 114,01 por ata de entidade sem fins lucrativos [P]. Visto de advogado, contador, certificado digital e banco: sem fonte primária [NV]. Seção 2.4.
4. **Governança:** vedação estatutária a atividade partidária e a conteúdo de campanha, conselho consultivo de neutralidade com membros de tradições políticas diversas, registro público de interesses, regra pública de financiamento e plano de sucessão com ativos em nome da associação. Seção 3.
5. **Financiamento:** doações recorrentes pequenas, verba de fundação para montar captação e um braço de serviços com dado público sempre gratuito. **Nunca** aceitar dinheiro de partido, federação, candidato, pré-candidato, campanha, mandatário, gabinete ou fundação partidária, nem dinheiro condicionado a publicação (Res. TSE 23.610, art. 29, §8º). Seção 4.
6. **Quando a associação vira controladora**, mudam Quem somos, Dados e privacidade, o teste de balanceamento e a titularidade de domínio, repositório e contas. O ponto crítico é **2028**: de 16/08 até o 2º turno (29/10/2028), o site passa a ser "sítio de pessoa jurídica" (Lei 9.504, art. 57-C, §1º, I), e desde 2026 a vedação alcança também os perfis em redes sociais (Res. 23.610, art. 29, §1º). Seção 5.
7. **Só o mantenedor** escolhe fundadores, nome, sede e cargos, contrata advogado e contador, assina, paga e registra. Seção 7.

---

## 1. Opções de forma jurídica

### 1.1 O que a lei permite

- **Pessoas jurídicas de direito privado** são associações, sociedades, fundações, organizações religiosas, partidos e empreendimentos de economia solidária (CC, art. 44) [P] — https://www.planalto.gov.br/ccivil_03/leis/2002/l10406compilada.htm
- **Existência legal** começa com a inscrição do ato constitutivo no registro (CC, art. 45; Lei 6.015/1973, art. 119) [P]. Antes do registro não há pessoa jurídica, mesmo com ata assinada.
- **Associação:** "união de pessoas que se organizem para fins não econômicos"; não há direitos e obrigações recíprocos entre associados (CC, art. 53) [P]. O estatuto precisa conter, sob pena de nulidade, os sete itens do art. 54: denominação, fins e sede; admissão, demissão e exclusão; direitos e deveres; fontes de recursos; constituição e funcionamento dos órgãos deliberativos; alteração do estatuto e dissolução; gestão administrativa e aprovação de contas [P]. A assembleia geral destitui administradores e altera o estatuto, com quórum fixado no estatuto (art. 59); 1/5 dos associados pode convocá-la (art. 60); na dissolução, o patrimônio vai para entidade de fins não econômicos designada no estatuto (art. 61) [P].
- **Fundação:** o instituidor faz "dotação especial de bens livres" por escritura pública ou testamento (CC, art. 62) [P]. Os fins admitidos incluem "promoção da ética, da cidadania, da democracia e dos direitos humanos" e "produção e divulgação de informações e conhecimentos técnicos e científicos" (art. 62, parágrafo único, VII e VIII) [P]. O Ministério Público do Estado vela pela fundação (art. 66), e cada reforma do estatuto exige 2/3 dos gestores e aprovação do MP em até 45 dias (art. 67) [P].
- **OSCIP não é forma jurídica, é qualificação** de uma associação ou fundação já existente. Exige **no mínimo 3 anos** de constituição e funcionamento regular (Lei 9.790/1999, art. 1º, redação da Lei 13.019/2014) [P] — https://www.planalto.gov.br/ccivil_03/leis/l9790.htm. Organizações partidárias e assemelhadas não podem se qualificar (art. 2º, IV) [P]. A qualificada fica proibida de participar "em campanhas de interesse político-partidário ou eleitorais, sob quaisquer meios ou formas" (art. 16) [P].
- **OSC para parcerias com o poder público (MROSC):** entidade privada sem fins lucrativos que não distribui resultados (Lei 13.019/2014, art. 2º, I, "a") [P]. Para celebrar parceria precisa de 1, 2 ou 3 anos de CNPJ ativo, conforme a parceria seja municipal, distrital/estadual ou federal (art. 33, V, "a") [P] — https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2014/lei/l13019.htm. Fica impedida a OSC que tenha como dirigente membro de Poder ou dirigente da administração pública da mesma esfera, ou cônjuge e parente até o 2º grau (art. 39, III) [P].

### 1.2 Comparação

| Critério | Associação própria | Fundação | Projeto abrigado em ONG existente |
|---|---|---|---|
| Patrimônio inicial | Nenhum exigido [P: CC, art. 54 não exige] | Dotação de bens livres suficientes (art. 62); se insuficientes, vão para outra fundação (art. 63) [P] | Nenhum |
| Quem controla | Assembleia dos associados (art. 59) [P] | Instituidor fixa os fins; MP fiscaliza (art. 66) [P] | A diretoria da anfitriã, por contrato ou termo de cooperação [NV] |
| Mudar o estatuto | Assembleia com quórum do estatuto (art. 59) [P] | 2/3 dos gestores + aprovação do MP (art. 67) [P] | Fora do alcance do projeto |
| Tempo até operar | Semanas (seção 2) [NV] | Meses: escritura, aprovação do estatuto pelo MP (art. 65), registro [NV] | Dias a semanas, se houver anfitriã disposta [NV] |
| Custo fixo | Contador e obrigações acessórias (seção 2.5) | O mesmo, mais prestação de contas ao MP [S] | Taxa de administração da anfitriã, se houver [NV] |
| Neutralidade | Desenhada pelo projeto no estatuto | Desenhada no ato de instituição | Herda a reputação e a agenda da anfitriã; muitas fazem advocacy, como o Abgeordnetenwatch fez e foi boicotado (a3, seção 1.4) [S] |
| Vedação eleitoral a PJ (art. 57-C, §1º, I) | Alcança o site | Alcança o site | **Também alcança**: o site passa a ser da anfitriã, que é PJ [P: o texto diz "pessoas jurídicas, com ou sem fins lucrativos"] |
| Controlador LGPD | A associação | A fundação | A anfitriã, ou controladoria conjunta [NV] |
| Satisfaz AD-014 ("uma associação sem fins lucrativos existe") | Sim | Discutível: fundação não é associação | Exige decisão do mantenedor registrada em `.specs/STATE.md` |
| Saída | Não há de onde sair | Não há | Transferir domínio, marca e contas de volta depende da anfitriã [NV] |
| Exemplos no setor | Transparência Brasil, Voto Consciente, Politize!, OKBR (anexo A4) [S] | Nenhum no benchmark do projeto | Parlametria dentro da OKBR (anexo A4) [S] |

### 1.3 Leitura

- **Fundação não cabe agora.** Pede bens que o projeto não tem e põe o MP entre o projeto e cada mudança de estatuto. Faz sentido só se aparecer um doador que queira dotar patrimônio, o que hoje não existe.
- **Abrigo em ONG existente é o plano B**, não o A. Resolve prazo e dá CNPJ emprestado, mas não resolve o problema eleitoral (a anfitriã é PJ), põe a titularidade do site em mãos de terceiro e amarra a credibilidade do projeto à agenda de outra entidade. Serve se, em 15/01/2027, o registro da associação própria não tiver saído; nesse caso o mantenedor precisa decidir e registrar se o abrigo satisfaz AD-014 para liberar a IA.
- **Associação própria** é a única opção que entrega, ao mesmo tempo, controle do estatuto, patrimônio dispensável e caminho para OSC (já), dedutibilidade para PJ doadora (já, se o estatuto cumprir a Lei 9.790; seção 4.3) e OSCIP (a partir do fim de 2029).

---

## 2. Caminho até o CNPJ

### 2.1 Passo a passo

| # | Passo | Quem faz | Fonte | Quando (proposta) |
|---|---|---|---|---|
| 1 | Escolher fundadores (pessoas que assinam a ata) e quem ocupa diretoria e conselho fiscal | Mantenedor | CC não fixa número mínimo [P]; a prática cartorária pede órgãos preenchidos, o que dá 3 a 7 pessoas [S: anexo A3, seção 6] | Até 20/10/2026 |
| 2 | Escolher denominação e conferir se não há homônima no cartório e no CNPJ; ver marca no INPI | Mantenedor | Denominação é item obrigatório (CC, art. 54, I) [P]; busca de homonímia e de marca [NV] | Até 20/10/2026 |
| 3 | Definir sede (endereço) | Mantenedor | Sede é item obrigatório (art. 54, I) [P]; escritório virtual é aceito por muitos cartórios e prefeituras [NV] | Até 20/10/2026 |
| 4 | Contratar advogado para redigir e **visar** estatuto e ata | Mantenedor | Ato constitutivo de PJ só é registrado "quando visado por advogados", sob pena de nulidade (Lei 8.906/1994, art. 1º, §2º) [P] — https://www.planalto.gov.br/ccivil_03/leis/l8906.htm | Contratar até 20/10; minuta a partir do esboço da seção 3 |
| 5 | Contratar contador antes do registro | Mantenedor | As obrigações acessórias começam com o CNPJ (seção 2.5) [S] | Até 27/10/2026 |
| 6 | Assembleia de constituição: aprova o estatuto, elege diretoria e conselho fiscal, lavra ata e lista de presença | Fundadores | CC, arts. 46 e 54 [P]; Lei 6.015, art. 120, VI exige nome, nacionalidade, estado civil e profissão de fundadores e diretores [P] | **27/10/2026 ou depois** (AD-007) |
| 7 | Protocolar no Registro Civil de Pessoas Jurídicas (RCPJ) da comarca da sede: uma via do estatuto, em papel ou eletrônica, com a ata | Representante legal | Lei 6.015, arts. 114, I, e 121 (redação da Lei 14.382/2022) [P] — https://www.planalto.gov.br/ccivil_03/leis/l6015compilada.htm | Até 06/11/2026 |
| 8 | Aguardar registro ou nota devolutiva | Cartório | Não achei prazo federal específico para o RCPJ; o prazo de 10 dias do art. 188 é do registro de imóveis [P]. Os códigos de normas estaduais fixam prazos próprios [NV] | 1 a 3 semanas [NV] |
| 9 | Inscrição no CNPJ pelo Coletor Nacional / Redesim, com o documento básico de entrada assinado e o ato registrado; natureza jurídica 399-9, "Associação Privada" | Contador | Toda entidade domiciliada no Brasil é obrigada ao CNPJ (IN RFB 2.119/2022, art. 4º) [S: texto lido em cópia de editora, anexos não abertos]; a integração com o cartório varia por estado [NV] | Até 27/11/2026 |
| 10 | Inscrição municipal, se a prefeitura exigir para entidade sem receita de serviço | Contador | Regra municipal [NV] | Junto com o CNPJ |
| 11 | Certificado digital e-CNPJ, se o contador precisar para entregar declarações | Contador | [NV] | Até 15/12/2026 |
| 12 | Conta bancária PJ em nome da associação, com estatuto, ata e cartão CNPJ | Diretoria | Exigência de cada banco [NV]; doação de PJ dedutível exige crédito em conta da entidade (Lei 9.249/1995, art. 13, §2º, III, "a") [P] | Até 15/12/2026 |
| 13 | Transferir domínio, repositório, hospedagem e e-mail para a associação | Diretoria | Seção 5 | Até 15/01/2027 |

### 2.2 Por que não antes de 27/10

AD-007 diz que o projeto não terá pessoa jurídica até 26/10/2026. Enquanto houver candidatos no 2º turno, o site de pessoa natural se apoia na Res. 23.610, art. 28, §6º ("manifestação espontânea na internet de pessoas naturais [...] não será considerada propaganda eleitoral") [P] — https://www.tse.jus.br/legislacao/compilada/res/2019/resolucao-no-23-610-de-18-de-dezembro-de-2019. Tudo o que não cria a pessoa jurídica pode ser feito antes: escolher pessoas, redigir, contratar. Assinar a ata antes de 27/10 não cria a PJ (CC, art. 45) [P], mas também não ganha tempo, porque o registro teria de esperar.

### 2.3 Calendário realista

- **Caminho normal:** assembleia em 27/10 a 06/11; registro em meados de novembro; CNPJ no fim de novembro; conta no começo de dezembro. Total de 4 a 8 semanas [NV].
- **Risco de prazo:** nota devolutiva do cartório (cada exigência reinicia a espera) e abertura de conta (bancos pedem documentos adicionais de entidades sem fins lucrativos) [NV]. Fim de ano costuma desacelerar bancos e contadores [NV].
- **Data de corte sugerida: 15/01/2027.** Se até lá o CNPJ e a conta não existirem, decidir entre o plano B (abrigo) e lançar a v2 sem IA, como a decisão 7 já prevê.

### 2.4 Custos

| Item | Valor | Certeza |
|---|---|---|
| Registro do estatuto no RCPJ, capital de São Paulo, 2026 | **R$ 214,20** até 5 páginas e 3 vias; acima disso, simulação no site do cartório | [P] tabela oficial de 2026 em vigor desde 06/01/2026 — https://cdtsp.rtdbrasil.org.br/assets/files/tabelas/Tabela_2026.pdf |
| Registro de ata de entidade sem fins lucrativos, mesma tabela | **R$ 114,01** por ata até 5 páginas e 3 vias | [P] mesma fonte |
| Certidão de pessoa jurídica, mesma tabela | **R$ 23,02** por página | [P] mesma fonte |
| Estimativa de cartório em SP, estatuto de 10 a 15 páginas + ata + duas certidões | R$ 400 a R$ 700 | [NV] soma minha; o adicional por página não está na tabela em PDF |
| Cartório fora da capital paulista | Tabela estadual própria | [NV] não verifiquei porque a cidade da sede não está decidida |
| Inscrição no CNPJ | Sem taxa da Receita, segundo a prática | [NV] não achei a regra de gratuidade no texto primário |
| Advogado (redação e visto) | R$ 1.000 a R$ 3.000, conforme guias de mercado; pode ser pro bono via redes de apoio (Abraji, ARTIGO 19) | [S] anexo A3; pro bono [NV] |
| Contador | R$ 150 a R$ 400 por mês para entidade sem movimento | [NV] anexo A3, valores de mercado |
| Certificado e-CNPJ | Algumas centenas de reais por ano | [NV] |
| Conta bancária PJ | Pacote de tarifas do banco; há bancos digitais sem mensalidade | [NV] |
| **Primeiro ano, ordem de grandeza** | R$ 4.000 a R$ 8.000 | [NV] soma das linhas acima |

### 2.5 Tributos e obrigações

- **Imunidade constitucional não é o caso típico.** A Lei 9.532/1997, art. 12, regula a imunidade do art. 150, VI, "c" da Constituição para "instituição de educação ou de assistência social" que atende "a população em geral, em caráter complementar às atividades do Estado" [P] — https://www.planalto.gov.br/ccivil_03/leis/l9532.htm. Um site de transparência legislativa dificilmente se enquadra como educação nesse sentido [NV].
- **Isenção é o enquadramento provável.** O art. 15 isenta de IRPJ e CSLL "as instituições de caráter filantrópico, recreativo, cultural e científico e as associações civis que prestem os serviços para os quais houverem sido instituídas e os coloquem à disposição do grupo de pessoas a que se destinam, sem fins lucrativos" [P]. Rendimentos de aplicação financeira não são isentos (§2º) [P]. A isenção exige os requisitos do art. 12, §2º, "a" a "e" (§3º): não distribuir resultado, aplicar tudo nos fins, escrituração completa, guardar documentos por 5 anos e entregar declaração anual [P].
- **Remuneração de dirigentes** é permitida a quem atua na gestão executiva, a valor de mercado e com os requisitos dos arts. 3º e 16 da Lei 9.790 (art. 12, §2º, "a", redação da Lei 13.204/2015) [P]. Isso amarra a remuneração futura de quem trabalha no projeto à vedação de campanha da Lei 9.790, art. 16, o que é coerente com a neutralidade.
- **Obrigações acessórias mesmo sem movimento** (ECF, DCTFWeb, EFD-Reinf, conforme o caso) [S: anexo A3]. O contador confirma a lista.
- **Doações recebidas** podem sofrer ITCMD, imposto estadual com isenções próprias de cada estado [NV]. Confirmar com o contador no estado da sede.

---

## 3. Governança que protege a neutralidade: esboço de estatuto

**Isto é um esboço de conteúdo, não o estatuto.** O advogado redige o texto final. Os números entre colchetes são propostas para o mantenedor escolher.

### 3.1 Mapa dos itens obrigatórios (CC, art. 54) [P]

| Item do art. 54 | Onde está no esboço |
|---|---|
| I. Denominação, fins e sede | Cap. I |
| II. Admissão, demissão e exclusão | Cap. III |
| III. Direitos e deveres | Cap. III |
| IV. Fontes de recursos | Cap. VI |
| V. Órgãos deliberativos | Cap. IV |
| VI. Alteração e dissolução | Cap. VIII |
| VII. Gestão administrativa e aprovação de contas | Caps. IV e VI |

Para não fechar portas, o esboço também atende ao que a Lei 9.790 exige do estatuto (art. 4º: princípios, conselho fiscal, destino do patrimônio, remuneração e prestação de contas) [P] e ao que a Lei 13.019 exige para parceria (art. 33: dissolução para entidade de igual natureza e escrituração pelas Normas Brasileiras de Contabilidade) [P].

### 3.2 Esboço

**Capítulo I. Denominação, sede, duração e finalidade**
- Denominação: [a escolher pelo mantenedor], associação civil de direito privado, sem fins econômicos e **apartidária**, por prazo indeterminado, com sede em [cidade/endereço].
- Finalidade: promover a transparência e o controle social do exercício de mandatos e cargos públicos federais por meio da organização, da explicação e da publicação de dados oficiais; promover o acesso à informação, a ética, a cidadania e a democracia; produzir e divulgar dados abertos, metodologias e pesquisa. A redação espelha a Lei 9.790, art. 3º, XI e XII [P], o que mantém aberta a qualificação futura e a dedutibilidade da seção 4.3.
- Meios: manter plataformas digitais, publicar dados sob licença aberta, firmar parcerias com universidades e organizações da sociedade civil, prestar serviços compatíveis com a finalidade (Cap. VI).

**Capítulo II. Princípios, vedações e independência editorial**
- Princípios: legalidade, impessoalidade, moralidade, publicidade, economicidade e eficiência (cópia da Lei 9.790, art. 4º, I) [P], mais exatidão, fonte verificável e direito de correção.
- **Vedações**, sob quaisquer meios ou formas (a fórmula é da Lei 9.790, art. 16) [P]:
  1. apoiar ou se opor a partido, federação, coligação, candidatura ou pré-candidatura;
  2. pedir voto ou não voto, recomendar voto ou publicar conteúdo de campanha;
  3. publicar, em qualquer período, avaliação de mérito de pessoa, ranking, nota ou índice que ordene parlamentares, e vocabulário de pesquisa eleitoral (regra que já está em AGENTS.md);
  4. contratar impulsionamento pago de conteúdo que mencione candidato, partido ou federação;
  5. receber recurso ou vantagem das fontes vedadas do Cap. VI;
  6. produzir mídia sintética de pessoa real ou função de IA que ranqueie, recomende ou opine sobre voto (Res. 23.610, art. 28, §1º-C, I e II) [P].
- **Carta editorial:** documento aprovado pela assembleia e publicado no site, com as regras de linguagem, a política de correções e direito de resposta e a política de IA. O estatuto diz que a diretoria cumpre a carta e que mudá-la exige parecer prévio do conselho consultivo (Cap. V). Isso deixa as regras operacionais fora do cartório e a mudança delas difícil.

**Capítulo III. Associados**
- Categorias, que o CC, art. 55 permite [P]: **fundadores** e **efetivos**, com voto; **apoiadores** (doadores), sem voto e sem influência editorial.
- Admissão de efetivos por decisão da diretoria, com declaração de adesão à carta editorial e de interesses (Cap. VII).
- Demissão a pedido. Exclusão só por justa causa, em procedimento com defesa e recurso à assembleia (CC, art. 57) [P]; violar as vedações do Cap. II é justa causa.
- **Inelegibilidade para cargos [proposta]:** não pode integrar diretoria, conselho fiscal ou conselho consultivo, nem coordenar conteúdo, quem: (a) ocupa mandato eletivo ou cargo em comissão em gabinete parlamentar, liderança ou partido; (b) é dirigente partidário ou candidato, ou o foi nos últimos [4] anos; (c) é cônjuge, companheiro ou parente até o 2º grau de parlamentar federal em exercício, como na Lei 13.019, art. 39, III [P]. **Filiação partidária simples:** o mantenedor escolhe entre vedar para diretoria e coordenação de conteúdo (mais protetor, reduz quem pode participar) e exigir declaração pública com afastamento de decisões sobre o partido (mais aberto). Recomendo vedar para a diretoria e declarar nos demais cargos.

**Capítulo IV. Órgãos**
- **Assembleia geral:** órgão máximo; aprova contas, elege e destitui diretoria e conselhos, altera o estatuto (CC, art. 59) [P]; reúne-se ao menos uma vez por ano; convocação por 1/5 dos associados garantida (art. 60) [P].
- **Diretoria executiva**, com [3] membros e mandato de [2] anos, renovação [escalonada]: coordenação geral (representação legal), coordenação de dados e conteúdo, tesouraria. Remuneração possível só para quem atua na gestão executiva, a valor de mercado, fixada pela assembleia em ata (Lei 9.532, art. 12, §2º, "a") [P].
- **Conselho fiscal**, com [3] membros, que opina sobre relatórios financeiros e contábeis (Lei 9.790, art. 4º, III) [P].

**Capítulo V. Conselho consultivo de neutralidade** (o Kuratorium do Abgeordnetenwatch adaptado; a3, seção 1.4 [S])
- Composição: [5 a 7] pessoas de **tradições políticas reconhecidamente diversas**, mais pelo menos uma da academia e uma do jornalismo ou da sociedade civil de dados, todas sob as inelegibilidades do Cap. III. Nenhum partido indica membros: indicação partidária traria partidos para dentro da governança.
- Atribuições: (a) parecer público e anual sobre a neutralidade do produto (linguagem, metodologias, cobertura); (b) instância de recurso quando um parlamentar ou cidadão discorda de uma recusa de correção ou de direito de resposta; (c) parecer prévio a mudança de metodologia, da carta editorial e das cláusulas de neutralidade e financiamento.
- Limites: não edita nem veta registros individuais. O dado oficial não passa por negociação; a metodologia sim.
- Mandato de [3] anos, sem remuneração, com renovação de 1/3 por vez.

**Capítulo VI. Recursos, patrimônio e transparência**
- Fontes admitidas: contribuições de associados; doações de pessoas físicas; doações e verbas de fundações e institutos filantrópicos; editais; parcerias com universidades; receita de serviços compatíveis com a finalidade; rendimentos de aplicação.
- **Fontes vedadas:** lista da seção 4.2, transcrita no estatuto.
- **Teto de concentração [proposta]:** a partir do 3º exercício, nenhuma fonte isolada acima de [33%] da receita anual; se ultrapassar, publicar o motivo e um plano de redução.
- **Transparência:** publicar todo ano relatório de atividades e demonstrações financeiras (Lei 9.790, art. 4º, VII, "b") [P], a lista de financiadores institucionais com valores e o total arrecadado de pessoas físicas por faixa. Nome de doador pessoa física só com consentimento (LGPD); ver seção 5.
- Escrituração conforme as Normas Brasileiras de Contabilidade (Lei 13.019, art. 33, IV) [P]; proibição de distribuir resultado (Lei 9.790, art. 1º, §1º; Lei 13.019, art. 2º, I, "a") [P].
- **Serviços pagos**, se houver: dados públicos e produto sempre gratuitos; cliente sem influência editorial; lista de clientes pública; fontes vedadas também como clientes.

**Capítulo VII. Conflito de interesses**
- Registro de interesses público para diretoria e conselhos: filiação, vínculos profissionais com o poder público, partidos, campanhas e empresas de relações governamentais, e financiamentos recebidos.
- Dever de se declarar impedido em qualquer decisão sobre pessoa, partido ou tema em que haja vínculo; a abstenção fica em ata.
- Proibição de vantagem pessoal ligada a decisões da entidade (Lei 9.790, art. 4º, II) [P].

**Capítulo VIII. Alteração, sucessão e dissolução**
- Alterar os Caps. II, V, VI e VIII exige assembleia convocada para esse fim, quórum de [2/3] dos associados com voto e parecer prévio publicado do conselho consultivo (CC, art. 59, parágrafo único, deixa o quórum ao estatuto) [P].
- **Sucessão:** domínio, repositório, hospedagem, contas e chaves em nome da associação, com no mínimo duas pessoas de acesso administrativo; runbook de operação versionado; vacância de cargo preenchida pelo conselho fiscal até a assembleia seguinte.
- **Continuidade dos dados:** se a entidade parar, publica um arquivo final dos dados e do código sob licença aberta antes da dissolução. O VoteWatch sobreviveu assim (a3, seção 1.6) [S].
- **Dissolução:** o patrimônio líquido vai para entidade sem fins lucrativos de finalidade igual ou semelhante, preferencialmente OSC ou OSCIP (CC, art. 61; Lei 13.019, art. 33, III; Lei 9.790, art. 4º, IV) [P].

---

## 4. Financiamento compatível com a regra apartidária

### 4.1 Modelos, em ordem de encaixe

| Modelo | Quem prova | Como fica aqui | Risco |
|---|---|---|---|
| Doação recorrente pequena e numerosa | Abgeordnetenwatch, com mais de 12.500 doadores a partir de 5 € por mês (a3, seção 1.4) [S] | PIX recorrente ou plataforma de recorrência, com valor mínimo baixo | Pessoa física não deduz doação a associação comum no IRPF [NV]; base lenta para crescer |
| Verba inicial para montar captação | OLIN deu 85.000 € ao Abgeordnetenwatch para contratar captação (a3) [S] | Pedir a fundações 2 a 3 anos de verba para construir a base de doadores, não para pagar o produto | Dependência temporária; aplicar o teto de concentração |
| Fundos de inovação cívica e editais | HowTheyVote.eu e o Prototype Fund (a3) [S] | Editais de jornalismo, dados e tecnologia cívica [NV: levantar os de 2026–2027] | Editais de governo: ver 4.2 |
| Braço de serviços com dado sempre gratuito | SocietyWorks da mySociety (a3) [S] | Extrações sob medida, formação de jornalistas, relatórios para redações e pesquisa | Fins econômicos podem atrair o Marco Civil, art. 15 (seção 5.4) [NV]; cliente vedado |
| Financiamento coletivo por funcionalidade | TheyWorkForYou e GovTrack (a3) [S] | Campanhas por entrega visível (comparador, alertas) | Picos sem recorrência |
| Parceria acadêmica | VoteWatch; LabHacker no conselho do Directorio Legislativo (a3) [S] | Bolsistas, hospedagem de base, revisão de metodologia, sem controle editorial | Lentidão institucional |
| Doação de PJ dedutível | Lei 9.249/1995, art. 13, §2º, III [P] | PJ no lucro real deduz até 2% do lucro operacional se a entidade for OSC nos termos da Lei 13.019 e cumprir os arts. 3º e 16 da Lei 9.790, "independentemente de certificação" (alínea "c") [P]; exige crédito em conta da entidade e declaração da beneficiária (alíneas "a" e "b") [P] | Empresa doadora com interesse legislativo; ver 4.2 |

### 4.2 O que nunca aceitar

**Base legal.** A Res. TSE 23.610, art. 29, §8º (redação da Res. 23.755/2026), inclui entre as propagandas pagas vedadas "a contratação sob qualquer modalidade, ainda que por meio da utilização de mecanismos de competição, ranqueamento ou premiação que ofereçam, direta ou indiretamente, vantagem econômica a pessoas físicas ou jurídicas para que realizem publicações de cunho político-eleitoral em seus perfis, páginas, canais ou assemelhados, em redes sociais ou aplicações de internet, bem como em seus sítios eletrônicos" [P]. A pessoa natural também não pode receber "remuneração, monetização ou [...] outra vantagem econômica" paga por beneficiários da propaganda ou terceiros (art. 28, IV, "b", 2, redação da Res. 23.732/2024) [P]. **Leitura:** qualquer dinheiro de quem se beneficia eleitoralmente do conteúdo pode ser tratado como contratação de publicação político-eleitoral, e o site de uma PJ remunerada vira propaganda paga, vedada mesmo fora do período de campanha, a depender do conteúdo [NV: interpretação a confirmar].

**Lista recomendada para o estatuto e para a página "Como nos financiamos":**
1. Partidos, federações, coligações e suas fundações e institutos.
2. Candidatos, pré-candidatos e campanhas, e quem age por eles.
3. Detentores de mandato eletivo, gabinetes parlamentares e recursos de cota ou verba de gabinete.
4. Câmara, Senado, Presidência e órgãos ligados aos agentes que o site cobre. Outras verbas públicas só por edital aberto e impessoal, com decisão registrada da assembleia. O GovTrack recusa verba partidária por carta (a3) [S].
5. Governos e entidades estrangeiras de governo. A Lei 9.504, art. 24, I e VII mostra a desconfiança da lei eleitoral com recurso externo em matéria eleitoral [P], embora regule doações a candidatos, não a associações.
6. Empresas e entidades de lobby, relações governamentais ou consultoria política, e empresas cujo negócio dependa de votação específica no Congresso [proposta].
7. Qualquer recurso condicionado a publicar, destacar, ordenar ou omitir conteúdo, inclusive "perfil ampliado" pago. O Abgeordnetenwatch vendeu perfis a 179 € (a3) [S]; aqui seria vedado.
8. Doações anônimas acima de [R$ 1.000] por ano [proposta]: sem saber a origem, não há como aplicar os itens 1 a 7.

**Na outra direção:** a associação não presta serviço, nem gratuito, a partido, candidato ou campanha. Serviço gratuito ou com desconto pode ser lido como doação estimável em dinheiro de pessoa jurídica [NV: confirmar com advogado eleitoral].

### 4.3 O que o estatuto precisa ter para a dedutibilidade de PJ

A alínea "c" da Lei 9.249, art. 13, §2º, III exige (i) ser OSC pela Lei 13.019, o que uma associação sem distribuição de resultados é (art. 2º, I, "a") [P], e (ii) cumprir os arts. 3º e 16 da Lei 9.790, ou seja, finalidade na lista do art. 3º e vedação a campanhas [P]. **O esboço da seção 3 já atende aos dois.** Por isso a finalidade deve ser redigida com os termos dos incisos XI e XII do art. 3º.

---

## 5. O que muda no site quando a associação vira controladora

### 5.1 LGPD

- **Controlador** é a "pessoa natural ou jurídica [...] a quem competem as decisões referentes ao tratamento de dados pessoais" (Lei 13.709/2018, art. 5º, VI) [P] — https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm. Quando a associação decide o tratamento, ela é a controladora, e os mantenedores agem em nome dela.
- **Agente de pequeno porte:** a Res. CD/ANPD 2/2022 inclui "pessoas jurídicas de direito privado, inclusive sem fins lucrativos" (art. 2º, I) [P: lida por resumo no gov.br] — https://www.gov.br/anpd/pt-br/documentos-e-publicacoes/regulamentacoes-da-anpd/resolucao-cd-anpd-no-2-de-27-de-janeiro-de-2022. Ficam dispensadas de indicar encarregado, mas mantêm canal de comunicação (art. 11) [P, por resumo]. **Atenção:** o benefício não vale para tratamento de alto risco (art. 3º, I), e o art. 4º lista entre os critérios específicos o "uso de tecnologias emergentes ou inovadoras" e decisões "unicamente com base em tratamento automatizado", combinados com critério geral de larga escala ou efeito significativo sobre direitos [P, por resumo]. Com contas de usuários e IA na v2, recomendo **indicar um encarregado voluntariamente** (LGPD, art. 41) e publicar identidade e contato (art. 41, §1º) [P]. Custa pouco e fecha a discussão.
- **Teste de balanceamento (`03`)**: trocar "Controladores: [a definir]" pela associação; acrescentar os titulares novos da v2 (usuários com conta: e-mail e preferências de alerta), com a base legal deles (execução de contrato ou consentimento, não legítimo interesse) [NV]; revisar antes de qualquer IA, como o próprio teste já prevê.
- **Operadores:** contrato ou termo de tratamento com hospedagem (VPS), e-mail transacional e plataforma de doação. Hospedagem fora do Brasil é transferência internacional (LGPD, art. 33) [P: artigo existe; regras da ANPD sobre cláusulas-padrão não lidas, NV].
- **Doadores** são titulares novos: CPF e dados bancários ficam com a plataforma ou o banco; publicar nome só com consentimento.

### 5.2 Páginas e rodapé

- **Quem somos:** razão social, CNPJ, sede, composição da diretoria e dos conselhos, carta editorial, registro de interesses. Manter nomes das pessoas responsáveis pelo conteúdo, que é a defesa contra o anonimato (Lei 9.504, art. 57-D) [P] e a prática que o projeto já adota.
- **Dados e privacidade:** controladora, CNPJ, encarregado, canal, operadores, titulares (parlamentares, usuários, doadores), retenção.
- **Como nos financiamos (nova):** fontes admitidas e vedadas, teto de concentração, relatório anual, histórico de mudanças. É a ideia 12 da seção 3 do anexo a3 [S].
- **Correções e direito de resposta:** sem mudança de prazo; com PJ constituída, a Lei 13.188/2015 se aplica com mais clareza (anexo A3) [S].
- **Rodapé:** "Mantido pela [denominação], associação sem fins lucrativos e apartidária, CNPJ [nº]. Não apoia nem se opõe a candidaturas, partidos ou federações. Não recebe recursos de partidos, candidatos, campanhas ou mandatários."

### 5.3 Titularidade e propriedade intelectual

- Domínio, organização no GitHub, VPS, Forge/Ploi, e-mail e contas de redes passam para a associação, com duas pessoas de acesso administrativo (Cap. VIII).
- **O repositório não tem arquivo de licença** [P: conferido na raiz do worktree em 02/10/2026]. Sem licença, o código e os textos continuam dos autores. Antes da transferência: (a) escolher a licença do código e a dos dados derivados (a3 recomenda CC0 ou ODbL para os dados) [S]; (b) cessão ou licença dos autores para a associação. É decisão do mantenedor, com o advogado.

### 5.4 Marco Civil

A guarda obrigatória de registros de acesso alcança o provedor "constituído na forma de pessoa jurídica e que exerça essa atividade de forma organizada, profissionalmente e com fins econômicos" (Lei 12.965/2014, art. 15) [S: citado no anexo A3]. Uma associação sem fins econômicos fica fora do caput (anexo A3) [S]. Com o braço de serviços pagos, o advogado deve confirmar se o enquadramento muda [NV].

### 5.5 Eleições de 2028 e 2030: o limite que nasce com o CNPJ

- **Regra:** "É vedada, ainda que gratuitamente, a veiculação de propaganda eleitoral na internet, em sítios: I – de pessoas jurídicas, com ou sem fins lucrativos" (Lei 9.504, art. 57-C, §1º, I) [P] — https://www.planalto.gov.br/ccivil_03/leis/l9504.htm. Desde a Res. 23.755/2026 a resolução diz "**sítios ou perfis em redes sociais**" (Res. 23.610, art. 29, §1º) [P]. Multa de R$ 5.000 a R$ 30.000 ao responsável (art. 57-C, §2º) [P]. O TSE equiparou até MEI a PJ pela teoria da aparência (Ac. 16/04/2026, anexo A2) [S].
- **Calendário:** eleições no primeiro domingo de outubro (Lei 9.504, art. 1º) [P], ou seja **01/10/2028** (municipais) e **06/10/2030** (gerais) [P: cálculo de calendário]. Propaganda na internet a partir de 15 de agosto (art. 57-A) [P]. O 2º turno de 2028 seria em 29/10 [NV: data do calendário do TSE ainda não publicada].
- **O que protege:** divulgar "atos de parlamentares e debates legislativos, desde que não se faça pedido de votos" não é propaganda antecipada (art. 36-A, IV) [P]. O anexo A2 avisa que, dentro do período de campanha e em site de PJ, o TSE já usou o conceito amplo de propaganda (o que leva a concluir que alguém é "o mais apto") [S]. Um site só descritivo está longe disso, mas o risco não é zero.
- **Em 2028**, deputados e senadores que disputarem prefeituras viram candidatos. **Em 2030**, quase todos os perfis do site são de candidatos. O site da associação precisa de um **modo eleitoral**, escrito antes de 15/08/2028:
  1. Parecer escrito de advogado eleitoral até [junho de 2028] sobre o site, os perfis em redes e a newsletter.
  2. Nada de número de urna, chamada para ação ou link para campanha nas páginas de pessoa; selo de candidatura só com cargo e situação, se o parecer aprovar.
  3. Cards de compartilhamento só com dado e fonte, sem frase que possa ser lida como elogio ou crítica.
  4. Perfis da associação em redes não publicam conteúdo sobre candidatos entre 16/08 e o fim do 2º turno, ou publicam só dado com link, conforme o parecer.
  5. Congelar resumos de IA novos sobre matéria ligada a candidato nas janelas de 72 horas antes a 24 horas depois de cada turno (Res. 23.610, art. 9º-B, §3º-A, conforme a pesquisa jurídica) [S], e nunca função que ranqueie, recomende ou opine (art. 28, §1º-C) [P].
  6. Zero impulsionamento pago o ano todo (já é regra do projeto).
- **Pergunta aberta para o advogado:** o TSE excetua "órgãos de imprensa e jornalistas, em contexto exclusivamente informativo" (AgR 0608960-34/2018, anexo A2) [S]. Vale saber se uma associação com finalidade estatutária de informação, equipe editorial e política de correções se aproxima dessa exceção. A matrícula de periódico no RCPJ (Lei 6.015, art. 122) [P] existe, mas o valor dela depois do fim da Lei de Imprensa (ADPF 130) é duvidoso [NV]. Não conto com isso.

---

## 6. Recomendação

**Constituir uma associação civil sem fins lucrativos própria, apartidária, com assembleia de constituição em 27/10/2026 ou logo depois, estatuto no formato da seção 3 e meta de CNPJ e conta até 15/12/2026.**

Razões:
1. **Cumpre a decisão 7 e o AD-014 sem interpretação.** É literalmente "uma associação sem fins lucrativos".
2. **É a forma mais barata e rápida que o projeto controla.** Sem patrimônio exigido e sem MP; o cartório verificado custa centenas de reais, não milhares.
3. **Abre o financiamento por etapas:** doação de pessoa física e verba de fundação desde o primeiro dia; doação dedutível de PJ desde o primeiro dia, se o estatuto seguir a Lei 9.790, arts. 3º e 16; parcerias do MROSC após 1 a 3 anos; OSCIP a partir do fim de 2029.
4. **Escreve a neutralidade no lugar mais difícil de mudar.** Estatuto com quórum qualificado e conselho consultivo, em vez de promessa num README.
5. **Concentra a defesa** (anexo A3): o alvo de uma ação passa a ser a entidade, com patrimônio separado, embora dirigentes ainda possam ser incluídos por autoria direta.

**Custo assumido:** obrigações contábeis permanentes, gente para preencher os órgãos e o limite eleitoral de PJ a partir de 2028. O último é o mais sério e só se administra com o modo eleitoral da seção 5.5.

**Plano B:** se o CNPJ não sair até 15/01/2027, abrigar o projeto numa organização de dados abertos ou transparência por termo escrito, com cláusula de independência editorial e de devolução dos ativos, **ou** lançar a v2 sem IA. O mantenedor escolhe e registra em `.specs/STATE.md`.

---

## 7. Checklist do que só o mantenedor pode fazer

**Até 20/10/2026 (preparação, sem criar PJ)**
- [ ] Decidir a forma (associação própria, como recomendado, ou outra) e registrar em `.specs/STATE.md`.
- [ ] Escolher fundadores e quem ocupa diretoria, conselho fiscal e conselho consultivo; colher o aceite de cada um.
- [ ] Escolher a denominação; conferir homonímia e marca.
- [ ] Escolher a cidade e o endereço da sede, o que define cartório, tabela de custas e ITCMD.
- [ ] Decidir as propostas entre colchetes da seção 3: filiação, quarentena, teto de concentração, quóruns, tamanho dos órgãos, limite de doação anônima.
- [ ] Contratar advogado (redação e visto obrigatório) e contador; pedir orçamento por escrito.
- [ ] Decidir a licença do código e dos dados e a cessão dos autores à associação.

**De 27/10/2026 em diante**
- [ ] Convocar e realizar a assembleia de constituição; assinar estatuto e ata.
- [ ] Protocolar no RCPJ e pagar os emolumentos.
- [ ] Assinar o documento de entrada do CNPJ (ou dar procuração ao contador).
- [ ] Abrir a conta bancária e assinar como representante.
- [ ] Contratar certificado digital, se o contador pedir.
- [ ] Transferir domínio, repositório, hospedagem, e-mail e redes para a associação.
- [ ] Indicar o encarregado de dados e aprovar as novas páginas de Quem somos, Dados e privacidade e Como nos financiamos.
- [ ] Registrar em `.specs/STATE.md` a data do CNPJ, o que libera a IA conforme AD-014.

---

## 8. O que advogado e contador precisam confirmar

**Advogado (societário e terceiro setor)**
1. Estatuto final: validade das vedações do Cap. II, das inelegibilidades do Cap. III (inclusive a vedação a filiados frente ao CC, art. 58) e dos quóruns do Cap. VIII.
2. Número mínimo de pessoas e acumulação de cargos aceitos pelo cartório da sede.
3. Se o esboço cumpre a Lei 9.790, arts. 3º, 4º e 16, e a Lei 13.019, art. 33, para dedutibilidade e qualificação futura.
4. Cessão ou licença do código e dos textos à associação; transferência do domínio.
5. Responsabilidade pessoal de dirigentes por conteúdo (CC, art. 50; Súmula 221/STJ) e se a associação muda a exposição de quem já é réu possível como pessoa física.

**Advogado eleitoral**
6. Se constituir a PJ em 27/10/2026, depois do 2º turno, tem algum efeito sobre conteúdo publicado durante 2026.
7. Alcance do art. 57-C, §1º, I e do art. 29, §1º (perfis em redes) sobre um site descritivo de PJ em 2028 e 2030, e o desenho do modo eleitoral da seção 5.5.
8. Se a lista de fontes vedadas da seção 4.2 cobre o art. 29, §8º e o art. 28, IV, "b", 2, e se serviço gratuito a campanha seria doação estimável vedada.
9. A exceção de imprensa (AgR 0608960-34/2018) e a matrícula de periódico (Lei 6.015, art. 122).
10. Texto oficial da Res. 23.755/2026, art. 9º-B, §3º-A (janela da IA), que esta pesquisa não releu.

**Contador**
11. Enquadramento tributário (isenção do art. 15 da Lei 9.532 e não imunidade) e obrigações acessórias a partir do CNPJ.
12. ITCMD sobre doações no estado da sede; recibos de doação; dedutibilidade para PJ doadora.
13. Prazo e documentos da Redesim e da prefeitura; necessidade de certificado digital.
14. Custo mensal real e a escrituração exigida para a dedutibilidade e para o MROSC.

**Proteção de dados**
15. Se contas de usuário e IA tornam o tratamento de alto risco pela Res. CD/ANPD 2/2022, arts. 3º e 4º; base legal das contas e dos alertas; transferência internacional para a hospedagem.

---

## 9. Fontes primárias lidas em 02/10/2026

| Norma | Artigos lidos | URL |
|---|---|---|
| Código Civil (Lei 10.406/2002), compilado | 44, 45, 46, 50, 53 a 67 | https://www.planalto.gov.br/ccivil_03/leis/2002/l10406compilada.htm |
| Lei 9.790/1999 (OSCIP) | 1º a 6º, 16 | https://www.planalto.gov.br/ccivil_03/leis/l9790.htm |
| Lei 13.019/2014 (MROSC) | 2º, 33, 34, 39 | https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2014/lei/l13019.htm |
| Lei 6.015/1973 (Registros Públicos), compilada | 114 a 122, 188 | https://www.planalto.gov.br/ccivil_03/leis/l6015compilada.htm |
| Lei 8.906/1994 (Estatuto da Advocacia) | 1º, §2º | https://www.planalto.gov.br/ccivil_03/leis/l8906.htm |
| Lei 9.532/1997 | 12 a 15 | https://www.planalto.gov.br/ccivil_03/leis/l9532.htm |
| Lei 9.249/1995 | 13, §2º, III | https://www.planalto.gov.br/ccivil_03/leis/l9249.htm |
| Lei 9.504/1997 (Eleições) | 1º, 24, 36-A, 57-A, 57-C | https://www.planalto.gov.br/ccivil_03/leis/l9504.htm |
| Lei 13.709/2018 (LGPD) | 5º, VI e VII; 41 | https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm |
| Res. TSE 23.610/2019, compilada com a Res. 23.755/2026 | 3º-C, 27 §1º, 28 IV "b" 2, 28 §1º-C, 28 §6º, 29, 37 XVIII | https://www.tse.jus.br/legislacao/compilada/res/2019/resolucao-no-23-610-de-18-de-dezembro-de-2019 (o site recusa clientes automáticos; lido com cabeçalhos de navegador) |
| Res. CD/ANPD 2/2022 | 2º I, 3º, 4º, 11 (por resumo da página) | https://www.gov.br/anpd/pt-br/documentos-e-publicacoes/regulamentacoes-da-anpd/resolucao-cd-anpd-no-2-de-27-de-janeiro-de-2022 |
| Tabela de emolumentos 2026, RTD e RCPJ da capital de São Paulo | Pessoas jurídicas sem valor declarado; certidões | https://cdtsp.rtdbrasil.org.br/assets/files/tabelas/Tabela_2026.pdf |

**Não consegui verificar:** texto primário da IN RFB 2.119/2022 (o portal de normas da Receita é uma aplicação que não abre sem navegador; li cópia de editora, sem anexos); gratuidade da inscrição no CNPJ; prazo do RCPJ nos códigos de normas estaduais; tabelas de cartório fora da capital paulista; tabela de honorários da OAB; custos de contador, certificado e banco; ITCMD por estado; a data do 2º turno de 2028 no calendário do TSE.
