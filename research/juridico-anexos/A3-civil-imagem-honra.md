# Jurídico 03 — Direito de imagem, honra, liberdade de expressão e responsabilidade civil

Data da pesquisa: 26/09/2026. Projeto: site sem fins lucrativos, mantido por duas pessoas físicas, com dados de mandato de deputados federais, senadores e presidente (nome, partido, foto oficial, votações nominais, presença, alinhamento com governo/partido, proposições) e, no futuro, resumos gerados por IA e cruzamento "promessa de campanha × como votou".

Legenda de certeza:
- **[P]** fonte primária aberta e lida (texto de lei no Planalto, PDF do STF, notícia oficial do STJ, autógrafo do PL na Câmara).
- **[S]** fonte secundária (Conjur, Migalhas, Dizer o Direito, Abraji, Aos Fatos, Wikipedia etc.) ou notícia oficial que não consegui abrir e cuja íntegra veio por terceiros.
- **[NV]** não verificado: afirmação de memória ou de fonte fraca, sem confirmação direta.

Observação técnica: `planalto.gov.br` e `portal.stf.jus.br` recusam conexão pelo fetch padrão (erro de certificado). Baixei os textos do Planalto e o PDF do STF com `curl -k`, então os trechos de lei citados abaixo são literais da versão compilada do Planalto.

---

## 1. Direito de imagem

### (a) O que a norma e a jurisprudência dizem

**Constituição Federal, art. 5º** [P, texto conhecido; URL: https://www.planalto.gov.br/ccivil_03/constituicao/constituicao.htm]
- IV: livre manifestação do pensamento; IX: livre expressão intelectual, artística, científica e de comunicação, "independentemente de censura ou licença"; XIV: acesso à informação.
- V: direito de resposta proporcional ao agravo, além de indenização por dano material, moral ou à imagem.
- X: inviolabilidade da intimidade, vida privada, honra e imagem, com indenização pela violação.
- XXVIII, "a": proteção à reprodução da imagem e voz humanas, "inclusive nas atividades desportivas".
- Art. 220: vedada qualquer restrição à manifestação do pensamento e à informação; §2º: "É vedada toda e qualquer censura de natureza política, ideológica e artística."

**Código Civil, arts. 11 a 21** [P; https://www.planalto.gov.br/ccivil_03/leis/2002/l10406compilada.htm]
- Art. 17: "O nome da pessoa não pode ser empregado por outrem em publicações ou representações que a exponham ao desprezo público, ainda quando não haja intenção difamatória."
- Art. 18: "Sem autorização, não se pode usar o nome alheio em propaganda comercial."
- Art. 20: "Salvo se autorizadas, ou se necessárias à administração da justiça ou à manutenção da ordem pública, a divulgação de escritos, a transmissão da palavra, ou a publicação, a exposição ou a utilização da imagem de uma pessoa poderão ser proibidas, a seu requerimento e sem prejuízo da indenização que couber, se lhe atingirem a honra, a boa fama ou a respeitabilidade, ou se se destinarem a fins comerciais." O Planalto anota "(Vide ADIN 4815)" nos arts. 20 e 21.
- Art. 21: vida privada inviolável; juiz adota providências para fazer cessar ato contrário.

Leitura literal do art. 20: a proibição só cabe em duas hipóteses, (i) uso que atinja honra/boa fama/respeitabilidade ou (ii) fins comerciais. Exibir foto oficial de parlamentar num contexto informativo e gratuito não se encaixa em nenhuma das duas.

**STF, ADI 4815 (biografias não autorizadas), Plenário, 10/06/2015, rel. Min. Cármen Lúcia, unânime** [S; notícia oficial https://portal.stf.jus.br/noticias/verNoticiaDetalhe.asp?idConteudo=293336&ori=1 não abriu; conteúdo confirmado via Dizer o Direito https://www.dizerodireito.com.br/2015/06/para-que-seja-publicada-uma-biografia.html e Buscador https://buscadordizerodireito.com.br/jurisprudencia/6/biografias-nao-e-necessaria-autorizacao-previa-do-biografado]
- Dispositivo: interpretação conforme à Constituição aos arts. 20 e 21 do CC, sem redução de texto, para declarar inexigível autorização da pessoa biografada (e de coadjuvantes) para obras biográficas literárias ou audiovisuais.
- Fundamentos relevantes: a CF veda censura prévia (art. 5º IX e art. 220 §2º) e a via adequada para abusos é a reparação posterior (indenização, retificação, direito de resposta e, em casos extremos, responsabilidade penal). Norma infraconstitucional não pode transformar a liberdade de expressão em direito dependente de licença do retratado.
- Implicação direta: se nem uma biografia inteira exige autorização, muito menos a exibição de nome, partido, foto oficial e atos de mandato de agente político. A ADI 4815 deslocou o eixo do art. 20 do CC de "autorização prévia" para "controle posterior de abusos".

**STJ, Súmula 403** [P, texto conhecido; https://scon.stj.jus.br/SCON/sumstj/]: "Independe de prova do prejuízo a indenização pela publicação não autorizada de imagem de pessoa com fins econômicos ou comerciais." A súmula presume o dano só no uso **comercial**. Fora disso, aplica-se a regra geral: precisa haver ilicitude e dano.

**Quando a Súmula 403 não se aplica (STJ):**
- **REsp 1.631.329/RJ, 3ª Turma, 24/10/2017, rel. p/ acórdão Min. Nancy Andrighi, Informativo 614** [S; https://buscadordizerodireito.com.br/jurisprudencia/5390/...]: imagem vinculada a fato histórico de repercussão social, divulgada com finalidade informativa e sem desvirtuamento, não atrai a Súmula 403; o direito à informação prevalece sobre a exigência de autorização.
- **AgInt no AREsp 674.270/SP, 4ª Turma, 23/08/2022, rel. Min. Raul Araújo** [S; https://informativos.trilhante.com.br/julgados/stj-agint-no-aresp-674270-sp]: "A utilização de fotografias que servir tão somente para ilustrar matéria jornalística sobre fato ocorrido e narrado pelo ponto de vista do repórter não constitui, per se, violação ao direito de preservação de imagem ou de vida íntima e privada de outrem, não havendo que se falar em causa para indenização por danos morais."
- Linha consolidada no STJ [S; resumo em https://buscadordizerodireito.com.br/jurisprudencia/13897/...]: a esfera de proteção dos direitos da personalidade de pessoas públicas, sobretudo agentes políticos, é reduzida em matéria ligada ao exercício da função.

### (b) O que significa na prática

- Usar a **foto oficial** publicada pela Câmara/Senado/Planalto, ao lado de nome, partido e dados de mandato, é uso informativo de agente público em contexto de interesse público. Não é uso comercial (Súmula 403 fora), não atinge honra por si só (art. 20 CC fora) e não depende de autorização (ADI 4815). Risco residual: **baixo**.
- O risco de imagem aparece se a foto for **recontextualizada** (montagem, legenda depreciativa, associação a fato falso, uso para vender algo). Não façam meme com a foto oficial; não coloquem a foto ao lado de rótulo pejorativo ("pior deputado", "traidor").
- Se o site um dia tiver receita (doações recorrentes, patrocínio, anúncios), o teste de "fins comerciais" fica mais cinza. Doação para manter servidor não transforma em uso comercial da imagem, mas publicidade paga ao lado das fotos, sim, aproxima. Se monetizar, separem visualmente conteúdo editorial de publicidade e mantenham licença de imagem clara (ver frente de dados/licenças do colega).
- Licença da foto em si (direito autoral do fotógrafo/Casa) é assunto da frente de dados; aqui só imagem-personalidade.

### (c) Grau de certeza
Texto do CC e da CF: [P]. ADI 4815: existência, data, relatora e dispositivo [S alto, várias fontes convergentes]. Súmula 403: [P]. Precedentes STJ citados: [S] com número, turma e data confirmados em duas fontes secundárias cada.

---

## 2. Honra e liberdade de expressão

### (a) O que a jurisprudência e a lei dizem

**STF, ADPF 130, Plenário, 30/04/2009, rel. Min. Ayres Britto** [S; íntegra do acórdão em https://conjur.com.br/2009-nov-07/leia-integra-acordao-stf-derrubou-lei-imprensa/]: Lei de Imprensa (Lei 5.250/67) não recepcionada pela CF/88. Teses centrais: liberdade de imprensa "plena", vedação a censura prévia, precedência prima facie da liberdade de expressão sobre honra/privacidade, com controle **posterior** via indenização e direito de resposta. A liberdade protege também o direito coletivo de ser informado.

**STF, ADI 4451, Plenário, 20-21/06/2018, rel. Min. Alexandre de Moraes, unânime** [S; https://conjur.com.br/2018-jun-29/direitos-fundamentais-stf-liberdade-expressao-liberacao-satiras-eleicoes/ e https://www.jota.info/coberturas-especiais/liberdade-expressao/humor-e-eleicoes-a-decisao-do-stf-na-adi-4-451-df; notícia oficial https://portal.stf.jus.br/noticias/verNoticiaDetalhe.asp?idConteudo=382174 não aberta]: inconstitucionais os incisos II e III do art. 45 da Lei 9.504/97, que proibiam emissoras de "degradar ou ridicularizar" candidatos e de difundir opinião favorável ou contrária. Fundamento: democracia exige crítica livre a candidatos e agentes públicos; restrição prévia à liberdade de expressão em período eleitoral é inconstitucional. Mesmo a sátira é protegida; a fortiori, a crítica baseada em dados.

**STF, RE 1.010.606/RJ, Tema 786 (direito ao esquecimento), Plenário, 11/02/2021, rel. Min. Dias Toffoli, tese publicada 20/05/2021** [S; https://portal.stf.jus.br/jurisprudenciaRepercussao/verAndamentoProcesso.asp?incidente=5091603&numeroProcesso=1010606&classeProcesso=RE&numeroTema=786 e https://www.tjmg.jus.br/portal-tjmg/jurisprudencia/recurso-repetitivo-e-repercussao-geral/aplicabilidade-do-direito-ao-esquecimento-...]: "É incompatível com a Constituição a ideia de um direito ao esquecimento, assim entendido como o poder de obstar, em razão da passagem do tempo, a divulgação de fatos ou dados verídicos e licitamente obtidos e publicados em meios de comunicação social analógicos ou digitais. Eventuais excessos ou abusos no exercício da liberdade de expressão e de informação devem ser analisados caso a caso, a partir dos parâmetros constitucionais (especialmente os relativos à proteção da honra, da imagem, da privacidade e da personalidade em geral) e das expressas e específicas previsões legais nos âmbitos penal e cível."
- Implicação: um parlamentar não pode exigir que o site apague votação de 2015 "porque já passou". Histórico de votos é dado verídico, lícito e público.

**STF, RE 1.075.412/PE, Tema 995, Plenário, 29/11/2023 (tese ajustada em embargos)** [S; https://portal.stf.jus.br/jurisprudenciaRepercussao/tema.asp?num=995 e https://tesesesumulas.com.br/tese/stf/995 e https://cj.estrategia.com/portal/responsabilidade-civil-imprensa-falsas-acusacoes/]: responsabilidade civil de veículo por publicar entrevista em que terceiro imputa falsamente crime a alguém só existe com **má-fé**: (i) dolo, pelo conhecimento prévio da falsidade, ou (ii) culpa grave, pela evidente negligência na apuração da veracidade. Deve o veículo assegurar direito de resposta e remover o conteúdo quando constatada a falsidade. Embora o caso trate de entrevista, a régua "dolo ou culpa grave" é o parâmetro atual do STF para erro informativo da imprensa.

**STJ, ponderação em conflitos entre liberdade de expressão e honra** [S; síntese em https://www.tjdft.jus.br/consultas/jurisprudencia/jurisprudencia-em-temas/direito-constitucional/liberdade-de-imprensa-e-responsabilidade-civil-colisao-entre-direitos-fundamentais e Buscador Dizer o Direito]:
- Critérios recorrentes: (I) compromisso ético com a informação verossímil; (II) preservação de honra, imagem, privacidade; (III) vedação de crítica com intuito de difamar/injuriar/caluniar (animus injuriandi vel diffamandi). Presentes animus narrandi e criticandi, não há dever de indenizar.
- **REsp 984.803/ES, 3ª Turma, 26/05/2009, rel. Min. Nancy Andrighi** [S]: "A honra e imagem dos cidadãos não são violados quando se divulgam informações verdadeiras e fidedignas a seu respeito e que, além disso, são do interesse público." A liberdade de informação exige compromisso com a **verossimilhança**, não certeza absoluta: cobra-se diligência na apuração, não infalibilidade.
- **REsp 1.986.335/SP, 4ª Turma, 07/04/2025, rel. Min. João Otávio de Noronha, Informativo 856** [S; https://buscadordizerodireito.com.br/jurisprudencia/13897/...]: críticas políticas sobre fatos de interesse geral a pessoa pública (deputado estadual réu em ações de improbidade) não geram dano moral, mesmo com linguagem dura, se não comprovada intenção de propagar informação inverídica.
- **Súmula 221/STJ** [P, texto conhecido]: "São civilmente responsáveis pelo ressarcimento de dano, decorrente de publicação pela imprensa, tanto o autor do escrito quanto o proprietário do veículo de divulgação." Para o projeto: os dois mantenedores respondem solidariamente pelo que o site publica.

**Código Penal, arts. 138 a 145** [P; https://www.planalto.gov.br/ccivil_03/decreto-lei/del2848compilado.htm]
- Art. 138 (calúnia): imputar **falsamente** fato definido como crime. §3º: admite-se **exceção da verdade**, salvo hipóteses listadas (entre elas, II: ofendido é o Presidente da República ou chefe de governo estrangeiro, art. 141 I).
- Art. 139 (difamação): imputar fato ofensivo à reputação. Parágrafo único: "A exceção da verdade somente se admite se o ofendido é funcionário público e a ofensa é relativa ao exercício de suas funções." Parlamentares são funcionários públicos para fins penais (art. 327 CP); logo, contra eles a verdade do fato relativo ao mandato é defesa completa.
- Art. 140 (injúria): ofensa à dignidade ou decoro; aqui não há "fato" e não cabe prova da verdade. É o tipo que pega xingamento e rótulo puramente depreciativo.
- Art. 141: aumento de 1/3 se contra funcionário público em razão das funções (II) ou por meio que facilite a divulgação (III); §2º: pena em **triplo** se cometido ou divulgado em redes sociais (incluído pela Lei 13.964/2019).
- Art. 142: "Não constituem injúria ou difamação punível: (...) II - a opinião desfavorável da crítica literária, artística ou científica, salvo quando inequívoca a intenção de injuriar ou difamar; III - o conceito desfavorável emitido por funcionário público, em apreciação ou informação que preste no cumprimento de dever do ofício." A crítica **política** não está no texto do inciso II, mas STF e STJ a tratam pela mesma lógica via ausência de dolo específico (animus criticandi/narrandi exclui o elemento subjetivo do tipo) [S; https://meusitejuridico.editorajuspodivm.com.br/2019/08/13/teses-stj-sobre-os-crimes-contra-honra-1a-parte/].
- Art. 143: retratação cabal antes da sentença isenta de pena na calúnia e difamação; parágrafo único (incluído pela Lei 13.188/2015): se praticada por meio de comunicação, a retratação se dá pelos mesmos meios, se o ofendido quiser.
- Art. 145: ação penal privada (queixa), salvo contra Presidente (requisição do Ministro da Justiça) e contra funcionário público em razão das funções (representação, com legitimidade concorrente do MP conforme Súmula 714/STF).

### (b) O que significa na prática: as três camadas de conteúdo

**1. Afirmação factual com fonte oficial** ("votou Sim no PL X em DD/MM/AAAA", "faltou a 12 de 60 sessões deliberativas").
- É o núcleo protegido: fato verídico, de interesse público, obtido de fonte oficial, sobre agente público no exercício da função. Não há calúnia (nada de crime imputado), não há difamação punível (exceção da verdade cabe contra funcionário público, art. 139 p.u.), não há dano moral (REsp 984.803, Tema 786).
- Risco real está em **erro de dado** (ver item 3) e em **omissão de contexto que torne a informação enganosa** (ex.: exibir "faltou 12 sessões" sem informar que 10 eram faltas justificadas registradas pela Casa, ou "votou contra o piso da enfermagem" quando o voto foi contra a redação de um destaque). Mostrem o que a Casa registra: tipo de voto, justificativa oficial de ausência, obstrução declarada, "votou com orientação do partido", link para a fonte.

**2. Métrica derivada** ("votou com o governo em 82%", "alinhamento partidário 64%").
- Continua sendo enunciado de fato, mas fato **construído por vocês**. A defesa não é "está no site da Câmara", é "está na metodologia publicada e é reproduzível". Publiquem: definição de "governo" (orientação do líder do governo registrada pela Casa), universo de votações (só nominais, só as com orientação registrada), tratamento de ausência/obstrução/abstenção, período, data da última atualização, e link para a base bruta.
- Sem metodologia pública, a métrica vira "opinião disfarçada de número" e perde a proteção da verossimilhança. Com metodologia pública, o parlamentar que discordar tem de atacar o método, não o site, e o Judiciário costuma respeitar critério técnico transparente.
- Evitem rótulos valorativos automáticos derivados da métrica ("fiel", "traidor", "governista de carteirinha"). O número descreve; o adjetivo julga.

**3. Juízo de valor** ("não cumpriu a promessa X").
- Aqui há duas peças: o fato (prometeu X; votou Y) e a conclusão (Y contraria X). A conclusão é opinião protegida (ADI 4451, ADPF 130, animus criticandi), **desde que** os fatos que a sustentam sejam verdadeiros e apresentados, e a inferência seja razoável.
- O caso **Jones Manoel × Kim Kataguiri (TJDFT, 16ª Vara Cível, 26/05/2026, R$ 30 mil, cabe recurso)** [S; https://www.congressoemfoco.com.br/noticia/119188/jones-manoel-e-condenado-a-pagar-r-30-mil-a-kim-kataguiri] mostra o limite: o juiz não condenou a crítica, condenou a **falsificação da premissa** ("o réu não discordou do argumento do autor; ele falsificou o argumento do autor"). O réu atribuiu ao deputado defesa do nazismo, quando a fala real do deputado era o oposto. Lição: juízo "não cumpriu" é seguro quando a promessa é citada literalmente (com fonte: plano de governo registrado no TSE, vídeo, entrevista) e o voto é citado literalmente; é perigoso quando o site resume a promessa de forma que o político não disse.
- Recomenda-se escala neutra e explicitamente metodológica em vez de veredicto binário: "voto compatível com a promessa", "voto incompatível", "relação indireta", "não avaliável". E botão "o parlamentar contesta esta leitura" com a resposta dele publicada ao lado.

### (c) Grau de certeza
ADPF 130, ADI 4451, Tema 786, Tema 995: existência, datas e teses [S alto, múltiplas fontes coerentes com o que já se sabia]. CP: [P]. Critérios do STJ: [S], com número de REsp confirmado em fonte secundária especializada. Jones × Kataguiri: [S, notícia única, decisão de 1º grau, recorrível].

---

## 3. Responsabilidade civil por informação incorreta

### (a) O que a norma e a jurisprudência dizem

**Código Civil** [P]
- Art. 186: "Aquele que, por ação ou omissão voluntária, negligência ou imprudência, violar direito e causar dano a outrem, ainda que exclusivamente moral, comete ato ilícito."
- Art. 187: abuso de direito: "excede manifestamente os limites impostos pelo seu fim econômico ou social, pela boa-fé ou pelos bons costumes."
- Art. 927: obrigação de reparar; parágrafo único: responsabilidade **objetiva** só "nos casos especificados em lei, ou quando a atividade normalmente desenvolvida pelo autor do dano implicar, por sua natureza, risco para os direitos de outrem." Publicar dados públicos de parlamentares não é atividade de risco no sentido do parágrafo único; o regime é o **subjetivo** (culpa).

**Régua de culpa na jurisprudência**
- STF, Tema 995 (acima): dolo ou **culpa grave** (negligência evidente na apuração).
- STJ, REsp 984.803/ES: verossimilhança + diligência; "verdade subjetiva" extraída da diligência do informador protege; negligência na apuração é ilícito indenizável [S].
- STJ, REsp 2.177.421/SC (2025): reprodução de notícia sem dolo informacional e sem dano concreto à honra objetiva não gera indenização [NV: só vi resumo em https://www.cognijus.com/blog/reproducao-de-noticia-nao-gera-responsabilidade-civil-sem-dolo-ou-dano-moral-resp-2177421-sc-...; não abri a ementa].
- Errata posterior: a jurisprudência do STJ diz que errata "não tem o condão de desfazer os efeitos lesivos da publicação inicial", servindo para **mitigar** o dano (reduz quantum) e não para eliminar responsabilidade já consolidada [S; síntese em resultados de busca; sem número de REsp confirmado]. Há decisões de tribunais estaduais afastando dano quando a retratação foi rápida (dias) e a repercussão pequena [NV, sem cite confiável].
- **Lei 13.188/2015, art. 2º §3º** [P]: "A retratação ou retificação espontânea, ainda que a elas sejam conferidos os mesmos destaque, publicidade, periodicidade e dimensão do agravo, não impedem o exercício do direito de resposta pelo ofendido nem prejudicam a ação de reparação por dano moral." Ou seja: corrigir é obrigatório e reduz dano, mas não é imunidade.

**Lei 13.188/2015 (direito de resposta)** [P; https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2015/lei/l13188.htm]
- Art. 1º/2º: aplica-se a "matéria divulgada, publicada ou transmitida por **veículo de comunicação social**". §1º define matéria como "qualquer reportagem, nota ou notícia divulgada por veículo de comunicação social, **independentemente do meio ou da plataforma** de distribuição, publicação ou transmissão que utilize, cujo conteúdo atente, **ainda que por equívoco de informação**, contra a honra, a intimidade, a reputação, o conceito, o nome, a marca ou a imagem de pessoa física ou jurídica".
- §2º: excluídos "os comentários realizados por usuários da internet nas páginas eletrônicas dos veículos de comunicação social".
- Art. 3º: prazo decadencial de **60 dias** da divulgação; pedido por correspondência com AR "diretamente ao veículo de comunicação social ou, **inexistindo pessoa jurídica constituída, a quem por ele responda**". §3º: em divulgação continuada, o prazo conta do início do agravo (relevante para páginas permanentes).
- Art. 4º I: na internet, a resposta terá "o destaque, a publicidade, a periodicidade e a dimensão da matéria que a ensejou".
- Art. 5º: se não publicar em **7 dias** do recebimento, nasce o interesse para ação judicial de rito especial (citação em 24h, contestação em 3 dias, decisão liminar possível, art. 6º-7º).
- Art. 12: indenização vai em ação própria.
- A lei **não define** "veículo de comunicação social". A referência a "inexistindo pessoa jurídica constituída" (art. 3º) e "independentemente do meio ou plataforma" (art. 2º §1º) indica que o legislador contemplou blogs e sites de pessoas físicas. Doutrina majoritária (Dizer o Direito, https://www.dizerodireito.com.br/2015/11/comentarios-lei-131882015-direito-de.html) [S]: blog sem PJ responde pela pessoa física que o mantém. Não encontrei decisão do STJ delimitando se um site informativo não jornalístico e sem fins lucrativos é "veículo de comunicação social" [NV]. Cautela: assumir que **pode** ser enquadrado.
- **STF, ADIs 5415, 5418 e 5436, Plenário, 11/03/2021** [S; https://www.abraji.org.br/noticias/stf-derruba-norma-que-so-permitia-decisao-colegiada-para-suspender-direito-de-resposta e https://www.stf.jus.br/arquivo/informativo/documento/informativo1009.htm]: lei declarada constitucional, exceto a expressão "em juízo colegiado prévio" do art. 10. O STF afirmou que o direito de resposta "não ofende a liberdade de expressão e de imprensa".
- **STJ, REsp 2.040.329, 3ª Turma, rel. Min. Villas Bôas Cueva, notícia de 28/07/2025** [P; https://www.stj.jus.br/sites/portalp/Paginas/Comunicacao/Noticias/2025/28072025-Terceira-Turma-mantem-direito-de-resposta-para-clinica-...aspx]: mesmo após retificação espontânea "permanece para o ofendido a possibilidade de exercer, em nome próprio, o direito de resposta"; a resposta deve observar "equivalência e imediatidade" e o Judiciário só intervém "em situações evidentemente desproporcionais".

### (b) O que significa na prática

- **Cenário: bug do ETL mostra presença errada de um deputado.** Enquadramento: culpa (art. 186), não dolo. O parlamentar teria de provar dano (à honra objetiva, ex.: repercussão na imprensa, uso por adversário). Defesas: (1) a fonte é oficial e o dado foi reproduzido com diligência razoável (verossimilhança, REsp 984.803); (2) o erro foi detectado/corrigido rapidamente e a correção foi visível; (3) havia aviso de "dado automatizado, sujeito a revisão" e link para a fonte primária, que permitia checagem imediata; (4) não houve culpa grave (Tema 995). Com essas quatro, o cenário mais provável é improcedência ou indenização baixa. Sem correção rápida ou com recusa em corrigir, vira culpa grave.
- **O que reduz exposição de verdade (não retórica):**
  1. **Registro de proveniência por dado**: cada célula com origem (endpoint, id da votação, data de coleta). Isso prova diligência e permite corrigir cirurgicamente.
  2. **Canal de correção com SLA** publicado (ex.: resposta em 48h, correção em até 7 dias, prazo alinhado ao art. 5º da Lei 13.188).
  3. **Changelog público de correções** (data, o que estava errado, o que mudou). Tribunais valorizam retificação visível; a Lei 13.188 exige "mesmo destaque".
  4. **Política de direito de resposta voluntária**: aceitar pedido por e-mail (a lei fala em carta com AR, mas nada impede receber por canal digital), publicar a manifestação do parlamentar ao lado do dado contestado, com o mesmo destaque. Custa pouco e é a defesa mais forte contra dano moral: mostra ausência de animus e boa-fé.
  5. **Testes de consistência no ETL** (ex.: presença nunca > sessões realizadas; soma de votos = quórum registrado) e comparação periódica com painéis oficiais. Documentar isso demonstra "dever geral de cuidado".
  6. **Não inferir além do dado**: exibir "ausência registrada pela Câmara" e não "faltou ao trabalho".
- **Vale adotar a Lei 13.188 voluntariamente?** Sim, como política interna, por três razões: (i) se um juiz entender que vocês são "veículo de comunicação social", já estarão cumprindo; (ii) publicar a resposta do ofendido em 7 dias esvazia grande parte da pretensão indenizatória (o Tema 995 trata a garantia de resposta como fator de exclusão de má-fé); (iii) custa quase zero. Não é preciso adotar o rito processual, só o comportamento: recebe, avalia em 7 dias, publica ou justifica.
- **Comentários de usuários** ficam fora do direito de resposta (art. 2º §2º) mas entram no Marco Civil (item 4).

### (c) Grau de certeza
CC, Lei 13.188: [P]. ADIs 5415/5418/5436: [S alto]. REsp 2.040.329: [P]. Tema 995: [S]. Régua sobre errata e retratação: [S]/[NV] conforme indicado.

---

## 4. Marco Civil da Internet (Lei 12.965/2014)

### (a) O que a lei e o STF dizem

**Texto legal** [P; https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2014/lei/l12965.htm]
- Art. 15, caput: "O provedor de aplicações de internet **constituído na forma de pessoa jurídica e que exerça essa atividade de forma organizada, profissionalmente e com fins econômicos** deverá manter os respectivos registros de acesso a aplicações de internet, sob sigilo, em ambiente controlado e de segurança, pelo prazo de 6 (seis) meses." §1º: ordem judicial pode obrigar, por tempo certo, provedores fora do caput a guardar registros de fatos específicos. §3º: entrega de registros só com autorização judicial.
- Art. 18: provedor de **conexão** não responde por conteúdo de terceiros.
- Art. 19, caput: provedor de aplicações "somente poderá ser responsabilizado civilmente por danos decorrentes de **conteúdo gerado por terceiros** se, após ordem judicial específica, não tomar as providências para (...) tornar indisponível o conteúdo apontado como infringente". §1º: ordem deve identificar o conteúdo com URL específica. §3º: causas sobre honra podem ir a juizados especiais. §4º: tutela antecipada possível.
- Art. 21: responsabilidade **subsidiária** por nudez/sexo privado divulgados sem consentimento, se não remover após **notificação** do participante.

**STF, RE 1.037.396 (Tema 987) e RE 1.057.258 (Tema 533), Plenário, 26/06/2025, 8×3, rel. Min. Dias Toffoli e Min. Luiz Fux; divergência: Mendonça, Fachin, Nunes Marques** [P; PDF oficial do STF https://www.stf.jus.br/arquivo/cms/noticiaNoticiaStf/anexo/Informac807a771oa768SociedadeArt19MCI_vRev.pdf, lido; página do tema https://portal.stf.jus.br/jurisprudenciaRepercussao/tema.asp?num=987]. Tese, itens relevantes, texto literal:
- "1. O art. 19 (...) é parcialmente inconstitucional. Há um estado de omissão parcial que decorre do fato de que a regra geral do art. 19 não confere proteção suficiente a bens jurídicos constitucionais de alta relevância."
- "2. Enquanto não sobrevier nova legislação, o art. 19 do MCI deve ser interpretado de forma que os provedores de aplicação de internet estão sujeitos à responsabilização civil, ressalvada a aplicação das disposições específicas da legislação eleitoral e os atos normativos expedidos pelo TSE."
- "3. O provedor de aplicações de internet será responsabilizado civilmente, nos termos do art. 21 do MCI, pelos danos decorrentes de conteúdos gerados por terceiros em casos de crime ou atos ilícitos, sem prejuízo do dever de remoção do conteúdo. Aplica-se a mesma regra nos casos de contas denunciadas como inautênticas."
- "3.1. Nas hipóteses de **crime contra a honra aplica-se o art. 19 do MCI**, sem prejuízo da possibilidade de remoção por notificação extrajudicial."
- "3.2. Em se tratando de sucessivas replicações do fato ofensivo já reconhecido por decisão judicial, todos os provedores de redes sociais deverão remover as publicações com idênticos conteúdos, independentemente de novas decisões judiciais, a partir de notificação judicial ou extrajudicial."
- "4. Fica estabelecida a presunção de responsabilidade dos provedores em caso de conteúdos ilícitos quando se tratar de (a) anúncios e impulsionamentos pagos; ou (b) rede artificial de distribuição (chatbot ou robôs)."
- "5. (...) responsável quando não promover a indisponibilização imediata de conteúdos que configurem as práticas de crimes graves previstas no seguinte rol taxativo" (atos antidemocráticos, terrorismo, induzimento ao suicídio, discriminação/racismo/homotransfobia, crimes contra a mulher, crimes sexuais contra vulneráveis, tráfico de pessoas). "5.1. (...) diz respeito à configuração de **falha sistêmica**." "5.4. A existência de conteúdo ilícito de forma isolada, atomizada, não é, por si só, suficiente."
- "6. Aplica-se o art. 19 do MCI ao (a) provedor de serviços de e-mail; (b) (...) reuniões fechadas por vídeo ou voz; (c) provedor de serviços de mensageria instantânea (...) exclusivamente no que diz respeito às comunicações interpessoais."
- "7. (...) marketplaces respondem civilmente de acordo com o Código de Defesa do Consumidor."
- "8. Os provedores de aplicações de internet deverão editar autorregulação que abranja" sistema de notificações, devido processo, relatórios anuais de transparência, canais de atendimento.
- Modulação: efeitos **prospectivos** (o PDF resume: "A decisão vale apenas para casos futuros"). Em todos os casos a responsabilidade é **subjetiva**.

### (b) O que significa na prática

- **Conteúdo próprio (dados, métricas, textos, resumos de IA gerados por vocês):** o art. 19 e o Tema 987 **não se aplicam**. Eles tratam de "conteúdo gerado por terceiros". O que vocês publicam é conteúdo próprio e responde pelo regime comum do CC (item 3) e do CP (item 2). Não há "porto seguro" do Marco Civil para o próprio editor. Isso já era assim antes de 2025 e não mudou.
- **Se abrirem comentários ou "sugestão de correção" de usuários:** vocês passam a ser provedor de aplicações quanto a esse conteúdo de terceiros. Regime pós-Tema 987:
  - Ofensa a parlamentar por usuário nos comentários = crime contra a honra → item 3.1: continua valendo o art. 19, ou seja, só há responsabilidade se descumprirem **ordem judicial**. Podem remover por notificação se quiserem, sem obrigação.
  - Outros ilícitos (ex.: dado pessoal de terceiro, ameaça, discriminação) → item 3: responsabilidade após **notificação extrajudicial** não atendida, nos moldes do art. 21.
  - Conteúdo do rol do item 5 (racismo, atos antidemocráticos etc.) → dever de cuidado; mas responsabilidade só por **falha sistêmica** (5.1, 5.4). Um comentário isolado não configura; ausência de qualquer moderação em site com volume, sim.
  - Item 8 (autorregulação, relatórios) foi escrito para plataformas; não há como exigir de site pessoal, mas ter um "reportar comentário" e um e-mail de contato cobre o espírito.
- **"Sugestão de correção" como formulário privado** (vai para vocês, não é publicado) não é conteúdo de terceiro divulgado; é insumo editorial. Se vocês publicarem a sugestão, o conteúdo passa a ser de vocês (responsabilidade própria) ou de terceiro identificado (art. 19/21). Melhor manter privado e publicar só a correção final, assinada pelo site.
- **Art. 15 (guarda de logs):** não se aplica a vocês (não são PJ, não têm fins econômicos). Mas §1º permite que juiz ordene guarda para fato específico; e se um dia constituírem associação **sem** fins econômicos, continuam fora do caput (o requisito é cumulativo: PJ + organizada + profissional + fins econômicos). Ainda assim, se houver comentários, guardar IP/data por 6 meses é barato e permite que o ofendido processe o autor real em vez de vocês (o art. 19 pressupõe que o provedor consiga identificar o autor; sem log, o ofendido tende a mirar o site).
- **Juizado Especial (art. 19 §3º):** ações sobre honra na internet podem ser propostas em JEC, sem advogado até 20 salários mínimos, o que reduz a barreira para um parlamentar irritado processar. Contam com isso no plano de risco.

### (c) Grau de certeza
Texto do MCI e tese do Tema 987: [P] (lidos na fonte). Modulação prospectiva: [P, resumo oficial no PDF]. Aplicação a sites pequenos sem fins econômicos: raciocínio a partir do texto; ainda não há jurisprudência pós-2025 específica para esse perfil [NV].

---

## 5. Conteúdo gerado por IA

### (a) Estado normativo em 26/09/2026

**Não há lei federal ou estadual vigente exigindo rotulagem de conteúdo de IA fora do contexto eleitoral** [S, convergência de várias fontes de 2026; https://www.barbieriadvogados.com/conteudo-gerado-por-ia-marcacao-e-rotulagem/ ; nenhuma fonte primária aponta lei em vigor]. O que existe:

- **Contexto eleitoral: Resolução TSE 23.610/2019, art. 9-B e 9-C** (redação da Res. 23.732/2024, ajustada pela Res. 23.755/2026) [S; página oficial https://www.tse.jus.br/legislacao/compilada/res/2019/resolucao-no-23-610-de-18-de-dezembro-de-2019 não abriu; conteúdo via https://www.migalhas.com.br/quentes/455672/ia-nas-campanhas-eleitorais-2026-veja-o-que-tse-autoriza-ou-proibe e notícias do TSE]. Exige aviso "explícito, destacado e acessível" de que o conteúdo foi fabricado ou manipulado e qual tecnologia foi usada, com formato por mídia (áudio no início, imagem com marca d'água e audiodescrição, vídeo compatível, impresso em cada página); proíbe deepfake; proíbe IA que "rankear, recomendar, sugerir ou priorizar candidaturas". Aplica-se à **propaganda eleitoral** (partidos, candidatos, coligações, plataformas; terceiros que disseminem). Multa R$ 5 mil a R$ 30 mil, remoção, e em casos graves cassação. Um site informativo não partidário **não faz propaganda eleitoral**, mas em 2026 (ano eleitoral) o cruzamento "promessa × voto" de um candidato à reeleição pode ser lido por adversários como propaganda negativa; a Justiça Eleitoral tem sido agressiva com conteúdo sintético em 2026 (ver caso AtlasIntel, TSE, jun/2026). Rotular qualquer texto de IA e não usar IA para gerar imagem/áudio/vídeo de político elimina esse flanco.

- **PL 2338/2023 (marco legal da IA)** [P; texto aprovado pelo Senado em 10/12/2024 e remetido à Câmara em 17/03/2025, autógrafo em https://www.camara.leg.br/proposicoesWeb/prop_mostrarintegra?codteor=2868197&filename=PL+2338%2F2023, lido; tramitação em https://www.camara.leg.br/proposicoesWeb/fichadetramitacao?idProposicao=2487262]. **Status em 26/09/2026: não aprovado pela Câmara, não sancionado, não é lei.** A ficha da Câmara mostra "aguardando parecer do relator" (Dep. Aguinaldo Ribeiro) na Comissão Especial, com apensações até 02/09/2026; as previsões de votação em maio/2026 não se concretizaram [S; https://ialocus.com.br/blog/post-pl-2338-marco-legal-ia-brasil-2026.html, atualizado 22/09/2026]. Se aprovado com alterações, volta ao Senado. O que o texto do Senado diz e importaria ao projeto:
  - Art. 1º §1º I: a lei "não se aplica ao sistema de IA utilizado por pessoa natural para fim exclusivamente particular e não econômico". Um site público **não** é uso "exclusivamente particular"; vocês seriam "aplicadores" (usam sistema de terceiro em serviço próprio).
  - Art. 4º XXI: "conteúdos sintéticos: informações, tais como imagens, vídeos, áudio e **texto**, que foram significativamente modificadas ou geradas por sistemas de IA".
  - Art. 5º I: direito da pessoa afetada "à informação quanto às suas interações com sistemas de IA".
  - Art. 19: "Quando o sistema de IA gerar conteúdo sintético, deverá (...) incluir identificador em tais conteúdos para verificação de autenticidade ou de características de sua proveniência, modificações ou transmissão, conforme regulamento." §1º: identificador não supre "outros requisitos de informação e transparência". A obrigação recai no **sistema** (desenvolvedor), mas o aplicador que republica o conteúdo tende a ser cobrado pela transparência ao usuário final.
  - Art. 20: poder público promoverá capacidades de "identificar e rotular conteúdo sintético".
  - Arts. 35-37 (responsabilidade civil): remete ao CDC nas relações de consumo e ao **Código Civil** nos demais casos, considerando "nível de autonomia do sistema (...) e o seu grau de risco" (art. 36 p.u.). Art. 37: inversão do ônus da prova se a vítima for hipossuficiente ou se for excessivamente oneroso provar. O texto original de 2023 tinha responsabilidade objetiva para alto risco e culpa presumida no resto (art. 27 do avulso original, lido); o Senado **abandonou** isso. Sistema informativo sobre política não está no rol de alto risco.
  - Conclusão: mesmo se virar lei no texto atual, para vocês o efeito prático é (i) dever de indicar que o texto foi gerado por IA e (ii) regime do CC, que já se aplica hoje.

- **Responsabilidade por alucinação de LLM que atribua voto errado a parlamentar, hoje:** não há norma específica nem decisão de tribunal superior [S; doutrina: https://www.migalhas.com.br/depeso/459409/responsabilidade-civil-pelas-alucinacoes-da-inteligencia-artificial]. Aplica-se o CC (arts. 186/927) ao **operador do site**: quem publica assina. O fornecedor do modelo (OpenAI, Anthropic, Google) responde perante vocês por contrato (com limitações de responsabilidade nos termos de uso), não perante o parlamentar. "Foi a IA" não é excludente: é como dizer "foi o estagiário". O que muda a análise de culpa é o **desenho do pipeline**: se o resumo é gerado a partir dos dados estruturados verificados (voto, data, ementa) e o texto passa por validação automática que checa cada afirmação contra a base (ex.: nome, número do PL, sentido do voto extraídos do texto e comparados), a alucinação residual é erro de boa-fé com diligência demonstrável (culpa leve, Tema 995 não configura má-fé). Se o LLM recebe acesso livre à web e escreve solto, é negligência grave.

### (b) Boas práticas que efetivamente reduzem exposição (não só disclaimer)

1. **Arquitetura "dados primeiro, texto depois"**: o LLM só recebe como contexto os registros verificados; nunca inventa voto, só reformula. Grounding com citações obrigatórias (cada frase do resumo aponta para id de votação/proposição).
2. **Validação pós-geração determinística**: extrair do texto gerado entidades (parlamentar, proposição, sentido do voto, data) e comparar com a base; rejeitar/regenerar se divergir. Guardar o log dessa validação. Isso é a prova de "dever de cuidado" que um juiz consegue entender.
3. **Rótulo padronizado e visível**: "Resumo gerado automaticamente por IA a partir dos registros oficiais de [fonte], em [data]. Pode conter erros; confira os dados originais [link]. Reporte um erro [link]." Rótulo em cada resumo, não só em página de termos. Mostrar o modelo usado e a versão ajuda na transparência (art. 5º I e 19 §1º do PL, se virar lei).
4. **Nunca gerar mídia sintética de parlamentar** (imagem, voz, vídeo). É a área de maior sensibilidade jurídica (deepfake, TSE, honra) e de zero ganho informativo.
5. **Separação visual entre fato registrado e síntese**: tabela/dados oficiais com fonte, e o texto de IA em bloco identificado. Se o resumo estiver errado e o dado ao lado estiver certo, o dano à honra é mínimo e a boa-fé é evidente.
6. **Botão de contestação no próprio resumo** com prazo de resposta e publicação da réplica (integra a política de direito de resposta do item 3).
7. **Não usar IA para juízo de valor final** ("cumpriu/não cumpriu"): a classificação deve ser regra explícita e auditável, com revisão humana das que forem "incompatível"; IA pode sugerir, humano confirma. Registro de quem revisou.
8. **Congelar e versionar** cada resumo publicado (hash + data), para provar o que estava publicado em determinada data em caso de litígio.

### (c) Grau de certeza
Texto do PL 2338 aprovado pelo Senado e status na Câmara: [P]. Inexistência de lei de rotulagem fora do eleitoral: [S alto, ausência de prova em contrário]. TSE art. 9-B: [S] (texto oficial não aberto). Responsabilidade por alucinação: análise doutrinária [S], sem precedente [NV].

---

## 6. Forma jurídica: pessoas físicas × associação

### (a) O que a norma diz

**Código Civil, arts. 53 a 61** [P]
- Art. 53: "Constituem-se as associações pela união de pessoas que se organizem para fins não econômicos. Parágrafo único. Não há, entre os associados, direitos e obrigações recíprocos."
- Art. 54: estatuto deve conter, sob pena de nulidade: denominação, fins e sede; requisitos de admissão/demissão/exclusão; direitos e deveres; fontes de recursos; modo de constituição e funcionamento dos órgãos deliberativos; condições de alteração e dissolução; forma de gestão administrativa e aprovação de contas.
- Art. 59: assembleia geral destitui administradores e altera estatuto. Art. 60: 1/5 dos associados pode convocar. Art. 61: patrimônio remanescente vai a entidade de fins não econômicos.
- Art. 45: personalidade jurídica começa com a inscrição do ato constitutivo no registro (Cartório de Registro Civil de Pessoas Jurídicas). Art. 46 V: o registro declara "se os membros respondem, ou não, subsidiariamente, pelas obrigações sociais". Art. 50: desconsideração da personalidade jurídica em caso de abuso (desvio de finalidade ou confusão patrimonial).
- O CC não fixa número mínimo de associados; a prática cartorária e a doutrina trabalham com pelo menos 2 fundadores, e os cartórios exigem estrutura de órgãos (assembleia, diretoria, conselho fiscal), o que na prática pede algo entre 3 e 7 pessoas para preencher cargos sem acumulação [S; https://higestor.com.br/blog/como-criar-uma-associacao/ e similares].

**Custo e burocracia mínima** [S; guias de 2025-2026]: estatuto + ata de assembleia de constituição + registro em cartório (emolumentos variam por estado; estimativas de mercado entre R$ 2 mil e R$ 3 mil incluindo assessoria, menos se fizerem sozinhos) + CNPJ na Receita (gratuito) + inscrição municipal se houver. Manutenção: contabilidade simplificada (imunidade/isenção de IRPJ exige escrituração e declarações anuais como ECF e DCTFWeb mesmo sem movimento), assembleia anual, prestação de contas conforme estatuto. Sem funcionários e sem receita, o custo anual é basicamente contador (muitos cobram entre R$ 150 e R$ 400/mês para entidades sem movimento) [NV, valores de mercado sem fonte primária].

### (b) O que significa na prática

**Como pessoas físicas:**
- Respondem com **patrimônio pessoal**, solidariamente (Súmula 221/STJ: autor do escrito e proprietário do veículo). Ação de dano moral por parlamentar em JEC (até 40 salários mínimos) ou vara cível é o cenário concreto. O maior custo não é a condenação (valores para pessoa pública costumam ficar entre R$ 5 mil e R$ 40 mil quando há condenação) e sim **defesa e tempo**: assédio judicial com várias ações em foros diferentes (ver item 7: Bia Kicis 11 ações; 7 políticos com 46 ações segundo Abraji).
- Titularidade de domínio, hospedagem, chaves de API e contas ficam em nome de um indivíduo; se ele sair, tudo trava.
- Não podem receber doações com recibo dedutível, nem editais, nem parcerias formais (MROSC, Lei 13.019/2014) com órgãos públicos.

**Como associação:**
- Responsabilidade patrimonial recai sobre a **associação**; dirigentes respondem pessoalmente só por culpa/dolo próprio, excesso de poderes ou desconsideração (art. 50 CC). Como o conteúdo é decidido pelos mesmos dois mantenedores, um autor pode incluir os dirigentes como corréus alegando autoria direta; a associação não é blindagem total contra ação por conteúdo que eles mesmos escrevem, mas desloca o alvo principal e concentra a defesa.
- Permite titularidade institucional de domínio e infra, doações, transparência de contas, credibilidade perante Casas Legislativas para pedidos LAI e parcerias com universidades/ONGs.
- Não altera o regime do Marco Civil art. 15 (continua sem fins econômicos) nem o da Lei 13.188 (continua sujeita a direito de resposta; aí com PJ, fica ainda mais claro que é "veículo").
- Custo: burocracia anual real e um terceiro sócio no mínimo em vários cartórios.

**Quando vale a pena:** quando (i) houver receita ou doações recorrentes, (ii) o site ganhar audiência que atraia litígio, (iii) quiserem parcerias formais, ou (iv) antes de lançar a camada "promessa × voto" e os resumos de IA, que são as partes com juízo de valor. Para o MVP só com dados oficiais e métricas transparentes, operar como PF com as práticas do item 3 é defensável. Alternativa intermediária: abrigar o projeto sob uma associação já existente (ex.: entidade de dados abertos ou de transparência) como projeto fiscal-sponsorship, o que dá PJ sem criar uma.

**Registro de domínio e hospedagem em nome de PF expõem endereço?**
- Registro.br exige CPF e endereço físico válido para domínios ".br". No WHOIS/RDAP público de titular PF aparecem **nome completo, CPF parcialmente mascarado, e-mail de contato e país**; telefone não é exibido. Sobre **endereço**: fontes divergem. Uma discussão de 04/02/2026 afirma que o Registro.br "expõe parcialmente o CPF, nome completo, e-mail e endereço" e que "não é possível esconder essas informações em nenhum domínio .br" [S; https://orbita.social.br/p/yd92aVK7nL/privacidade-em-dominios-br]; outra fonte diz que endereço e telefone de PF não são divulgados [S; https://diogopuiatti.com.br/registro-br-whois/]. A página oficial https://registro.br/tecnologia/ferramentas/whois/ exige JavaScript e não pude ler [NV]. **Não existe serviço de privacidade WHOIS para .br** (diferente de .com/.org, onde registradores oferecem proxy). Recomendação: façam uma consulta WHOIS real do domínio de teste de vocês antes de publicar; usem e-mail dedicado do projeto no cadastro; se o endereço aparecer, considerem domínio gTLD (.org) com WHOIS privacy para o site público e mantenham o .br só como redirecionamento, ou registrem o .br em nome da associação (endereço da sede, que pode ser escritório virtual).
- Hospedagem: provedores não publicam dados do cliente; o risco de exposição vem de (i) headers/WHOIS de IP dedicado (raro) e (ii) obrigação de fornecer dados mediante ordem judicial (art. 10 §1º e art. 22 MCI). Contas em nome de PF significam que a ordem judicial identifica a pessoa; isso é inevitável e não é um problema em si.
- LGPD/dados pessoais dos próprios mantenedores é tema da frente de dados.

### (c) Grau de certeza
CC: [P]. Custos e prática cartorária: [S]/[NV]. Registro.br: [S] conflitante, ponto marcado como não verificado.

---

## 7. Precedentes de casos reais

| Caso | O que aconteceu | Resultado | Argumento vencedor | Certeza |
|---|---|---|---|---|
| **Congresso em Foco** (portal de jornalismo político) | Alvo de cerca de **50 ações judiciais até 2017**, sobretudo após reportagens sobre salários de políticos acima da média (estudo Ponto de Inflexão/SembraMedia, citado na Wikipedia) | Resultados individuais não encontrados; o portal seguiu operando e foi vendido em 2024 | Não apurado | [S] https://pt.wikipedia.org/wiki/Congresso_em_Foco |
| **Transparência Brasil, projeto Excelências** (2006-2016; dados de processos, presença, gastos e votações de parlamentares) | Relatos de que "políticos se manifestaram contra" no início; **não encontrei nenhuma ação judicial** contra o projeto | Saiu do ar por falta de patrocínio, não por decisão judicial | n/a | [S] https://www.gazetadopovo.com.br/politica/republica/site-que-expunha-acoes-judiciais-de-politicos-sai-do-ar-por-falta-de-apoio-68hciltvhtg2zqxvv2g0zz6c7/ |
| **Ranking dos Políticos** (ranking.org.br; notas por votações 75%, gastos 10%, presença 10%, privilégios 5%, penalidade por condenações) | **Não encontrei processo judicial** movido por parlamentar contra o site em nenhuma busca (Jusbrasil, notícias) | n/a | Metodologia pública e baseada em dados oficiais; opera há mais de uma década | [S] https://ranking.org.br/en/articles/metodologia-ranking-dos-politicos-calculo-nota |
| **Atlas Político / AtlasIntel** | Litígios são sobre **pesquisas eleitorais** (TSE suspendeu pesquisa BR-06939/2026 em jun/2026 por suspeita de indução), não sobre avaliação de mandato | Fora de escopo | Fora de escopo | [S] https://www.tse.jus.br/comunicacao/noticias/2026/Junho/tse-suspende-divulgacao-de-pesquisa-da-atlasintel-... |
| **Aos Fatos × Revista Oeste** (TJSP) | 1º grau (41ª Vara Cível SP, maio/2021) condenou a R$ 50 mil e proibiu citar a revista, por classificar duas matérias como falsas | **3ª Câmara de Direito Privado do TJSP, 14/03/2023, rel. Des. Viviani Nicolau, reformou: improcedente** | "A checagem de notícias se tornou uma importante ferramenta do jornalismo profissional e não pode ser cerceada"; checagem é análise fundamentada, não censura; liberdade de informação exige "requisito interno da verdade", sem certeza absoluta | [S] https://www.aosfatos.org/noticias/tj-sp-acata-recurso-aos-fatos-revista-oeste/ ; https://www.jota.info/justica/aos-fatos-pode-dizer-que-certos-conteudos-da-revista-oeste-sao-falsos-decide-tjsp |
| **Aos Fatos × Jornal da Cidade Online** (TJRS) | 5ª Vara Cível de Passo Fundo (maio/2022) condenou a R$ 10 mil + remoção por afirmar que o JCO integrava rede de monetização de desinformação; Aos Fatos alegou que interpretou errado resposta do Google sobre conta AdSense | **TJRS manteve** condenação e negou majoração pedida pelo JCO; reportagem segue removida; recurso ao STJ pendente | Erro factual concreto, não retificado a contento, sobre PJ; a crítica em si não foi o problema, o dado errado foi | [S] https://www.aosfatos.org/noticias/tj-rs-nega-recurso-jornal-cidade-online-contra-aos-fatos/ ; https://www.poder360.com.br/justica/justica-condena-aos-fatos-a-pagar-r-10-000-a-site-bolsonarista/ |
| **Aos Fatos, ação penal (TJRJ, 10/02/2025)** | Queixa-crime por difamação e concorrência desleal contra a diretora Tai Nalon pela mesma reportagem | **Absolvição** | Matéria "objetivava informar o público sobre uma investigação relevante", sem dolo; prevalência da liberdade de expressão | [S] https://www.aosfatos.org/noticias/tj-rj-absolve-diretora-do-aos-fatos-por-reportagem-que-segue-sob-censura/ |
| **Agência Lupa** | Não encontrei ação de político contra a Lupa com decisão | n/a | n/a | [NV] |
| **Jones Manoel × Kim Kataguiri** (TJDFT, 16ª Vara Cível, 26/05/2026) | Comentarista atribuiu ao deputado "apologia ao nazismo" e ligações com crime organizado | **Condenado a R$ 30 mil** + remoção; recurso anunciado | Juiz: não foi opinião, foi falsificação da posição do autor ("o réu não discordou do argumento do autor; ele falsificou o argumento do autor") | [S] https://www.congressoemfoco.com.br/noticia/119188/jones-manoel-e-condenado-a-pagar-r-30-mil-a-kim-kataguiri |
| **Bia Kicis × jornalistas** (Abraji, out/2020) | Deputada moveu ao menos 11 ações (6 de remoção de conteúdo, 5 queixas-crime) contra Veja, Época, Crusoé, UOL, Aos Fatos e comunicadores | Sem vitórias relatadas para a deputada até a data da matéria | Abraji: padrão de assédio judicial; cabe litigância de má-fé | [S] https://www.abraji.org.br/noticias/deputada-bia-kicis-move-ao-menos-11-acoes-judiciais-contra-jornalistas-e-comunicadores |
| **STJ, REsp 1.986.335/SP (07/04/2025)** | Postagem crítica a deputado estadual investigado por corrupção | Improcedente | Pessoa pública, fato de interesse geral, sem prova de intenção de propagar inverdade | [S] Buscador Dizer o Direito |

**Padrões que emergem:**
1. Sites de **dados com metodologia pública** (Ranking dos Políticos, Excelências) não geraram litígio localizável em duas décadas. O litígio se concentra em **jornalismo opinativo/investigativo** e em **checagem que erra um fato**.
2. Quando o fato é verdadeiro ou verossímil e a linguagem é crítica, o autor vence (Aos Fatos × Oeste, REsp 1.986.335, absolvição no TJRJ).
3. Quando há **erro factual concreto** sobre pessoa identificada (Aos Fatos × JCO) ou **distorção do que a pessoa disse** (Jones × Kataguiri), há condenação, mesmo que modesta (R$ 10-30 mil), e o custo maior é a remoção e o processo.
4. O risco de **assédio judicial** (muitas ações, foros diversos) é o risco econômico real para PF; a proteção prática é ter tudo documentado para vencer rápido e pedir litigância de má-fé.

---

## Recomendações práticas

1. **Publiquem só o que a Casa registrou, com link para o registro.** Cada dado com id de origem, data de coleta e link. Isso resolve 80% do risco de imagem, honra e responsabilidade civil ao mesmo tempo.
2. **Metodologia pública e versionada** para toda métrica derivada (alinhamento, presença, produtividade): definições, universo, exclusões, fórmula, data. Sem isso, número vira opinião.
3. **Linguagem descritiva, não valorativa, nas camadas automáticas.** "Votou Sim", "ausência registrada", "votou com a orientação do governo em N de M votações". Adjetivos e rankings de "melhor/pior" só com metodologia explícita e nunca ao lado da foto oficial.
4. **Camada "promessa × voto" com escala neutra e citação literal** da promessa (fonte: plano de governo no TSE, transcrição com link) e do voto; classificação por regra escrita; revisão humana do que for "incompatível"; espaço para a versão do parlamentar.
5. **Política de correção e direito de resposta voluntária**, publicada: canal (e-mail + formulário), triagem em 48h, correção ou publicação da resposta em até 7 dias (espelhando art. 5º da Lei 13.188), changelog público, resposta do parlamentar exibida com o mesmo destaque do dado contestado.
6. **Foto oficial sem recontextualização**: sem montagens, sem legendas depreciativas, sem publicidade adjacente. Se monetizarem, separar editorial de anúncio.
7. **IA com grounding e validação determinística**, rótulo visível em cada resumo, versão congelada, nenhum juízo final automatizado, nenhuma mídia sintética de político.
8. **Comentários: não abrir no MVP.** Se abrirem depois: botão de denúncia, e-mail de contato, remoção em prazo curto após notificação para ilícitos em geral, guarda de IP/data por 6 meses, moderação mínima documentada. Sugestões de correção via formulário privado, não publicadas.
9. **Forma jurídica**: MVP como PF é defensável com os itens 1 a 5; constituir associação (ou abrigar-se em uma existente) antes da camada de juízo de valor, da IA em escala ou de qualquer receita. Verifiquem WHOIS do domínio antes de publicar e usem e-mail dedicado; considerem .org com privacidade para o site público.
10. **Preparo para litígio**: guardem snapshot datado (hash) de cada página publicada e de cada correção; mantenham comprovantes de coleta (respostas brutas das APIs); tenham contato de advogado com experiência em liberdade de expressão (Abraji, ARTIGO 19 e Instituto Vladimir Herzog mantêm redes de apoio a comunicadores processados).
11. **Ano eleitoral (2026)**: até a diplomação, tratem candidatos à reeleição com cuidado redobrado na camada "promessa × voto": data de atualização visível, nada de IA gerando imagem/áudio, rótulo de IA em texto, e não usar linguagem que se pareça com "vote/não vote".

---

## Não consegui verificar

- **Íntegra oficial da ADI 4815** no portal do STF (erro de certificado). Conteúdo confirmado por três fontes secundárias convergentes.
- **Íntegra oficial da ADI 4451** e da **ADPF 130** no portal do STF; usei Conjur e JOTA.
- **Ementa do REsp 2.177.421/SC** (reprodução de notícia sem dolo): só resumo em blog jurídico; tratar como indicativo.
- **Decisões do STJ sobre errata rápida afastando dano moral**: encontrei sínteses genéricas e um relato de tribunal estadual sem número de processo. A regra segura é a do art. 2º §3º da Lei 13.188 (retificação não impede resposta nem indenização).
- **Se um site informativo sem fins lucrativos mantido por PF é "veículo de comunicação social" para a Lei 13.188**: não há decisão do STJ localizada. Assumi que pode ser.
- **Resultados individuais das ~50 ações contra o Congresso em Foco** e a existência de qualquer ação contra Ranking dos Políticos, Excelências e Agência Lupa: buscas negativas, o que sugere ausência, mas ausência de notícia não é prova de ausência de processo.
- **Texto oficial do art. 9-B da Res. TSE 23.610** na página do TSE (bloqueio). Conteúdo via Migalhas e notícias do próprio TSE.
- **Quais campos exatos o Registro.br exibe no WHOIS de titular PF em 2026** (especialmente endereço): fontes de 2020 e 2026 divergem; página oficial requer JavaScript. Testar com consulta real.
- **Custos correntes de associação** (cartório, contador): estimativas de mercado sem fonte primária.
- **Jurisprudência pós-Tema 987 aplicando a tese a sites pequenos sem fins econômicos com comentários**: ainda não existe de forma localizável; a análise é dedutiva a partir do texto da tese.
- **Andamento do PL 2338 após 22/09/2026**: última verificação mostra "aguardando parecer" na Comissão Especial da Câmara; qualquer votação depois dessa data não foi capturada.
