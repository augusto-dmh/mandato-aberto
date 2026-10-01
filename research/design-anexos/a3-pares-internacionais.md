# Pares internacionais de monitoramento parlamentar: o que copiar e o que evitar

**Data da pesquisa:** 30/09/2026. **Para:** Mandato Aberto v2 (relançamento em 01/02/2027, Laravel + Inertia + Vue).
**Método:** WebSearch, WebFetch e `curl` direto nos sites; capturas de tela no navegador para HowTheyVote.eu, GovTrack, Abgeordnetenwatch e oParlamento.pt; Wayback Machine quando o site bloqueou acesso automatizado (TheyWorkForYou e They Vote For You devolveram 429/403/Cloudflare). Sondagem real da API da Câmara para checar o que os dados brasileiros permitem.

**Marcas de certeza (usadas em todo o documento):**
- **[V]** verificado na fonte primária, aberta nesta pesquisa.
- **[S]** fonte secundária (Wikipédia, imprensa, resumo de busca); não abri a fonte original.
- **[?]** não verificado, inferência minha ou dado que não consegui confirmar. Tudo o que tem essa marca está listado na seção 8.

---

## 1. Fichas por projeto

### 1.1 TheyWorkForYou (Reino Unido, mySociety). Ativo.

- **Status e financiamento.** No ar desde 2004. Nasceu de voluntários usando o parser do Public Whip e foi adotado pela mySociety em 2006 [S] (https://en.wikipedia.org/wiki/TheyWorkForYou). A mySociety é uma charity que vive de três fontes: doações de fundações (Esmée Fairbairn, Joseph Rowntree, Adessium e outras), doações individuais e o lucro da SocietyWorks, uma subsidiária comercial que vende software para prefeituras e doa o lucro à charity [S] (https://www.mysociety.org/about/funding/, https://www.societyworks.org/about/). Também usa financiamento coletivo por funcionalidade [S] (https://www.crowdfunder.co.uk/p/theyworkforyou).
- **Marca registrada.** Hansard pesquisável com link permanente por trecho, alertas por e-mail de fala ou palavra-chave e "voting summaries" por tema [S] (https://en.wikipedia.org/wiki/TheyWorkForYou).
- **Como mostra uma votação e o perfil.** As votações são agrupadas em "policies" (temas). Cada votação é **"scoring"** (entra no cálculo) ou **"informative"** (aparece na aba "more votes", mas não pesa). A pontuação de 0 a 100 vira frase fixa: 0–5 "consistently voted for", 5–15 "almost always voted for", 15–40 "generally voted for", 40–60 "voted a mixture of for and against", 60–85 "generally voted against", 85–95 "almost always voted against" e 95–100 "consistently voted against" [V] (https://web.archive.org/web/2025/https://www.theyworkforyou.com/voting-information/). Cada pessoa é comparada com "comparable MPs" do mesmo partido que tiveram a chance de votar nas mesmas votações. O site admite que, quando muitos se rebelam, a média do partido se desloca e a rebelião "parece menos extrema" [V] (mesma URL).
- **Ausência.** Na revisão de 2024, as ausências saíram do cálculo. O motivo declarado: a informação é útil para entender grupos, mas não cumpre o objetivo principal. A página explica o sistema de "pairing" e "slips" e diz que sem as instruções do partido não dá para saber o motivo de uma ausência [V] (https://research.mysociety.org/html/2024-voting-records/, página de voting-information acima).
- **Neutralidade e metodologia: as controvérsias.**
  - **2006:** o *Times* noticiou que deputados faziam intervenções "esquecíveis" e protocolavam perguntas escritas em massa para subir nas estatísticas do site. O caso chegou a debate na Câmara dos Comuns. O site **retirou os rankings absolutos** e acrescentou texto explicativo; Jack Straw criticou a medição "quantitativa, não qualitativa" [S] (https://en.wikipedia.org/wiki/TheyWorkForYou).
  - **Revisão de 2024:** a própria mySociety reconheceu que os resumos sugeriam convicção pessoal onde havia disciplina partidária e que o sistema era **"gameable"**. A oposição criava votações de moções sem efeito só para gerar linhas no site, e um deputado do governo ligou essa tática ao TheyWorkForYou. Os votos "weak" (peso 1/5) também faziam deputados novos parecerem mais contrários que os antigos. A resposta foi contar só os "action votes", aqueles que usam um poder do Parlamento [V] (https://research.mysociety.org/html/2024-voting-records/).
  - **Efeito da revisão:** 73% dos resumos ficaram iguais, 7 policies foram removidas, 23 criadas e cerca de 120 de 80 mil vínculos deputado–policy inverteram de sentido. Deputados do governo reclamaram que decisões tomadas sem votação (por exemplo, a meta de net zero) ficavam de fora. Por isso o site passou a incluir "agreements", em caráter experimental [V] (mesma URL).
  - **2025:** policies novas e cálculo separado para "esta legislatura" e "todo o período" [V] (https://www.mysociety.org/2025/03/13/updating-theyworkforyous-voting-summaries/).
- **Alertas e contas.** Alertas por palavra-chave existem desde cedo e foram redesenhados em 2025, com termos agrupados e gestão por tema [V] (https://www.mysociety.org/2025/10/23/theyworkforyou-update-a-richer-view-of-parliament/).
- **API, exportação e licença.** Há API desde 2006, financiada pelo Department for Constitutional Affairs [S] (Wikipédia acima). Em maio de 2025 saiu o **TheyWorkForYou Votes**: votações dos Comuns processadas "within minutes", alinhamento com o partido, oito categorias de "parliamentary dynamics", agreements extraídos do registro oficial, API e download em massa [V] (https://www.mysociety.org/2025/05/19/theyworkforyou-votes/). Licença dos dados: [?] não confirmada.
- **Direito de resposta.** O TWFY Votes tem estrutura para anotações e "whip reports", com plano declarado de "direct representative participation" [V] (post de 19/05/2025). Em outubro de 2025 o site afirmou que publicar votações "has led to more public justifications from representatives about how they vote" [V] (post de 23/10/2025).
- **IA.** Não há IA no conteúdo público dos resumos. Há um scraper assistido por LLM para coletar a lista de membros dos APPGs [V] (https://www.mysociety.org/2025/11/18/using-llm-tools-to-build-appg-scrapers/). A governança é pública: um AI Framework (2024) com o princípio "Use AI responsibly, and only when, all things considered, it is the best tool for the job", um registro interno de usos de IA e uma reunião periódica de revisão [V] (https://research.mysociety.org/html/ai-framework/, https://www.mysociety.org/2026/02/12/ai-self-governance-its-a-continuous-process/).
- **Visual.** [?] Não consegui ver: o site devolveu 429 para todo acesso automatizado. Em 2025 o perfil foi redesenhado para mobile, com navegação mais clara por seção [V] (post de 23/10/2025).

### 1.2 GovTrack.us (EUA). Ativo.

- **Status e financiamento.** É da Civic Impulse LLC, empresa de propriedade do operador, que "receives no funding in any form from outside organizations". A receita vem de anúncios (há um botão "Hide The Ads"), de assinantes e do Patreon. O GovTrack também já financiou funcionalidade por Kickstarter: o rastreador da Casa Branca, em 2025 [V] (https://www.govtrack.us/about). A carta de princípios diz: "We do not accept grants from or have any relationship with partisan organizations" [V].
- **Perfil.** Abre com um parágrafo em prosa ("X is the representative for... She has served since... next up for reelection"), guia de pronúncia do nome, botões "Track Her" e "Contact Her", seção de **Misconduct** em destaque, gráfico de ideologia × liderança, "Key Votes" e a frase **"missed 72 of 3,869 roll call votes, which is 1.9%. This is on par with the median of 2.1%"** [V] (https://www.govtrack.us/congress/members/alexandria_ocasio_cortez/412804, captura de 30/09/2026).
- **Votação.** Tabela por partido (Aye, No, Present, Not Voting), cartograma, notas explicativas ("'Aye' or 'Yea'?", o voto do Speaker), CSV para baixar, "Compare" e uma lista de votos "statistically notable", ou seja, inesperados para o escore do parlamentar [V] (https://www.govtrack.us/congress/votes/119-2025/h1). A lista de votações ordena por "Least Party Uniformity" e "Narrowest Margin" [V] (https://www.govtrack.us/congress/votes).
- **Escores e críticas.** O escore de ideologia é uma PCA sobre **coautoria** de projetos, não sobre votos. O de liderança usa PageRank sobre coautoria. O próprio site avisa que os escores "fluctuate significantly", que a etiqueta esquerda–direita é atribuída "after we see the results", que podem medir partidarismo ou popularidade e que "may be gamed by legislators" [V] (https://www.govtrack.us/about/analysis). Em julho de 2024 o GovTrack **retirou os boletins anuais de 2013, 2015, 2017 e 2019**. O de 2019 chamava Kamala Harris de "most liberal senator", foi usado na campanha de Trump e citado no *60 Minutes*. Nas palavras de Tauberer: "It's just not possible to reduce a legislator to a perfect number. I know that some legislators even try to manipulate their own ranking on our site" [V] (https://www.govtrack.us/posts/434/2024-07-26_we-retracted-our-single-year-legislator-report-cards-after-warning-about-their-unreliability).
- **Alertas e contas.** Alertas por e-mail são a função original, de 2004. As contas têm "Subscriptions & Lists", "Positions" e "Docket" [V] (https://www.govtrack.us/about, menu da conta).
- **API.** A API e os dados em massa foram **encerrados em 2017** porque "Congress created their own and other organizations created similar APIs" [V] (https://congressionaldata.org/ending-govtracks-bulk-data-and-api/).
- **IA.** Nenhum uso público encontrado. Os resumos de projeto são escritos pela equipe desde 2013 [S] (https://www.govtrack.us/about).
- **Visual.** Utilitário, com cara de 2014: tipografia de sistema, vinho com amarelo nos botões de anúncio e muita densidade. Útil, mas genérico [V] (captura).

### 1.3 ProPublica Represent e Congress API (EUA). Encerrados em 10/07/2024.

- Página oficial: "Represent and the Congress API are no longer available", lançados em 2016. **Nenhum motivo publicado** [V] (https://projects.propublica.org/represent/). Novas chaves de API deixaram de ser emitidas [S] (https://www.propublica.org/datastore/api/propublica-congress-api).
- **Linhagem:** a API nasceu no *New York Times* (2009), passou pela Sunlight Foundation e foi para a ProPublica em 2016–2017. É uma corrente de projetos de terceiros que morreram ou foram repassados [S] (https://github.com/propublica/sunlight-congress, https://congressionaldata.org/ending-govtracks-bulk-data-and-api/).
- **Lição [?] (inferência, sem motivo oficial):** um produto que não é a missão central de uma redação some quando muda a prioridade, e quem dependia da API fica sem nada. O GovTrack apontava para a API da ProPublica como substituta da sua; sete anos depois, a substituta também acabou. A alternativa que sobreviveu é a oficial (API do Congress.gov) [S] (https://www.congress.gov/help/using-data-offsite).

### 1.4 Abgeordnetenwatch.de (Alemanha, Parlamentwatch e.V.). Ativo.

- **Financiamento.** Mais de **12.500 doadores recorrentes** em dezembro de 2025, a partir de 5 € por mês e com dedução fiscal. A OLIN gGmbH aportou **85.000 € (2013–2016) para criar um cargo de captação de recursos**, como "Anschubfinanzierung" (verba inicial). A Parlamentwatch GmbH, braço técnico, foi liquidada; os relatórios anuais estão publicados [V] (https://www.abgeordnetenwatch.de/ueber-uns/mehr/finanzierung). No passado o site vendeu "perfis ampliados" a candidatos por 179 € (foto, link, agenda) [S] (https://en.wikipedia.org/wiki/Parliamentwatch). Se isso continua: [?].
- **Marca registrada: perguntas públicas a parlamentares.** Toda pergunta passa por moderação segundo um **Moderations-Codex**. Ficam de fora insultos, discurso de ódio, perguntas sobre a vida privada, matéria sob sigilo profissional, mera opinião sem pergunta e afirmações de fato sem fonte. O político recebe a pergunta mesmo quando ela não é publicada. Quem discorda da moderação (cidadão ou político) recorre a moderation@ e depois a um **Kuratorium** pluripartidário, que reavalia o caso. Moderadores e membros do Kuratorium não podem perguntar [V] (https://www.abgeordnetenwatch.de/ueber-uns/faq, https://www.abgeordnetenwatch.de/ueber-uns/mehr/moderations-codex). De 2004 a 2023 foram 294.520 perguntas publicadas, 78,7% respondidas; em 2023, 13.200 perguntas foram barradas pela moderação [S] (resumo de busca dos relatórios anuais, https://www.abgeordnetenwatch.de/ueber-uns/mehr/finanzierung/parlamentwatch-eV).
- **Perfil.** Contador "X % / N / M Fragen beantwortet" (só conta o mandato atual), lista de perguntas e respostas, votações nominais com rótulos neutros (**"Dafür gestimmt" / "Dagegen gestimmt" / "Nicht beteiligt"**), atividades paralelas remuneradas (Nebentätigkeiten) e a campanha própria "Transparenz-Versprechen" [V] (https://www.abgeordnetenwatch.de/profile/friedrich-merz).
- **Neutralidade.** A plataforma se declara überparteilich e o Kuratorium supervisiona isso [V] (FAQ). Mas a organização também faz advocacy: ações judiciais por acesso à informação, petições, investigações de lobby. O modal do newsletter diz "Bleib kritisch. Bleib informiert." [V] (captura de 30/09/2026). Em 2007, candidatos do SPD e do Die Linke em Bremen boicotaram o site porque candidatos de extrema-direita não tinham sido excluídos [S] (Wikipédia acima).
- **API e licença.** API com dados sob **CC0 1.0**, uso livre com fair use e limite de requisições [V] (https://www.abgeordnetenwatch.de/api).
- **IA.** Nenhum uso próprio encontrado. Terceiros usam os dados do site em ferramentas de IA (wahl.chat) [V] (https://wahl.chat/).
- **Visual.** Marca forte: logotipo em carimbo roxo inclinado, laranja nos botões de ação, cartões de pergunta e resposta com a foto do parlamentar. O modal de newsletter que interrompe a leitura do perfil é um ponto negativo [V] (captura).

### 1.5 HowTheyVote.eu (Parlamento Europeu). Ativo.

- **Financiamento.** A primeira versão foi paga pelo **Prototype Fund**, do Ministério Federal de Educação e Pesquisa da Alemanha. Em 2025 e 2026 recebe apoio da MIZ Babelsberg; a Tuta fornece o e-mail [V] (https://howtheyvote.eu/about). Começou em 2021 como software livre [V] (https://fosdem.org/2026/schedule/event/39YMYR-how-they-vote/).
- **Unidade central: a votação, não o parlamentar.** A página de votação tem título, data, número do relatório, resumo da fonte oficial (Legislative Observatory) com **"What do you think of this summary?"**, uma barra de âncoras ("Vote result · More information · Open data · Embed · Sources · Report an error"), uma **barra única empilhada com hachura e ícones de polegar** (a cor não é a única pista), a frase completa "For: 613. Against: 5. Abstentions: 29. In total, 647 MEPs voted. 72 MEPs didn't vote." e abas MEPs / Political Groups / Countries com filtros por nome, grupo, país e posição [V] (https://howtheyvote.eu/votes/184118, captura).
- **Compartilhamento.** Cada votação gera uma **imagem PNG própria** (`/files/votes/sharepic-184118.png`) com **alt text descritivo completo**: "A barchart visualizing the result... 613 MEPs who voted in favor..." [V] (meta og:image da página).
- **Neutralidade e metodologia.** Quatro valores, com definição: "For", "Against", "Abstention" e "Did not vote"; este último "means that the MEP did not participate in the vote, for example because the MEP was not present" [V] (https://howtheyvote.eu/about). Limites declarados: só votações nominais, sem comissões, sem emendas na interface, correções de voto não exibidas porque "do not change the result", e "we cannot rule out the possibility that individual votes are missing or contain errors" [V]. **Não há escore, ranking nem estatística de presença por eurodeputado** [V] (navegação do site: só "All Votes" e "About & FAQ").
- **Dados.** JSON e CSV por votação, "experimental API", export semanal no GitHub; licença **ODbL** com DbCL. As fotos e os resumos ficam fora da licença porque vêm do Parlamento Europeu [V] (https://howtheyvote.eu/about). Resultados aparecem em cerca de 30 minutos após a publicação oficial [V].
- **IA.** Nenhuma. Os resumos são oficiais [V].
- **Visual.** Cabeçalho azul-marinho, fundo cinza-azulado claro, **IBM Plex Sans**, tokens de cor por resultado (verde, vermelho, azul) [V] (CSS `server.entry.css`). A home é só uma busca com sugestões ("Try Ukraine, Frontex, or Environment") e um link para a última sessão [V] (https://howtheyvote.eu/). Sóbrio e muito legível; a qualidade vem da clareza tipográfica, não de efeito visual.

### 1.6 VoteWatch Europe (Parlamento Europeu). Encerrado em junho de 2022.

- Funcionou de 2008 a junho de 2022, fundado por Simon Hix, Doru Frantescu e Sara Hagemann [S] (https://en.wikipedia.org/wiki/VoteWatch_Europe). O aviso de encerramento diz que a equipe parou "as we are transitioning to our next professional projects" [V] (https://www.votewatch.eu/). O **dataset 2004–2022 foi publicado** e o HowTheyVote o recomenda para a série histórica [V] (https://howtheyvote.eu/about, https://simonhix.com/projects/).
- O modelo freemium (relatórios premium para quem faz lobby) é [?]: só achei isso em fonte fraca. Frantescu seguiu para a EU Matrix, que mistura "expertise" e IA para previsão política [S] (https://term9.eumatrix.eu/).
- **Lição:** o projeto dependia dos fundadores e acabou quando eles mudaram de carreira. O que salvou o legado foi o **dataset aberto**.

### 1.7 Parltrack (Parlamento Europeu). Ativo, ainda com o selo "BETA".

- Junta dossiês, eurodeputados, votações, agendas de comissão e **1,27 milhão de emendas, cada uma com link próprio**, coisa que o PE só publica em PDF ou Word. Alertas por e-mail e RSS por dossiê. Dados em JSON sob **ODbL**, código sob **AGPLv3**. Não usa cookies e não registra IPs [V] (https://parltrack.org/).
- **Transparência sobre a própria origem:** a página "About" admite que os desenvolvedores "have a strong background in digital issues" e cita as vitórias contra ACTA e patentes de software [V] (https://parltrack.org/about). É honesto, mas posiciona o projeto como ferramenta de ativismo.
- Financiamento: [?].
- **Visual:** datado, denso, feito para especialistas [V] (texto da home).

### 1.8 NosDéputés.fr e NosSénateurs.fr (França, Regards Citoyens). Em degradação.

- Associação **100% voluntária desde 2009**. Em 2022: "ces forces bénévoles ne se sont malheureusement que peu renouvelées et même épuisées". A equipe anunciou que a 16ª legislatura seria a última sob sua manutenção e pediu um sucessor [V] (https://www.regardscitoyens.org/nosdeputes-fr-cest-reparti-pour-un-dernier-tour/). O mesmo texto diz que o site "sera encore et toujours accusé par certains d'être la racine de tous ces maux", isto é, de inflar emendas e falas [V].
- **Observado em 30/09/2026:** `www.nosdeputes.fr` respondeu **HTTP 500** (erro Symfony) com certificado TLS **vencido em 26/09/2026**; o último post do feed é de julho de 2022 [V] (`curl` e https://www.regardscitoyens.org/feed/). Os arquivos por legislatura ficam em subdomínios (por exemplo `2017-2022.nosdeputes.fr`) [V] (post de 2022).
- Fizeram estudos com os dados, como o de sanções financeiras por ausência em comissão e o de lobby nas audiências da Assembleia [V] (https://www.regardscitoyens.org/).

### 1.9 Openpolis / Openparlamento (Itália). Ativo.

- "Openparlamento non riceve finanziamenti pubblici o da organizzazioni private... non ospita pubblicità"; vive de doações [V] (https://parlamento19.openpolis.it/). A Openpolis, por outro lado, faz projetos em parceria (#conibambini) [V] (https://www.openpolis.it/).
- **Índices próprios:** o **"Indice di forza"** pondera a relevância dos cargos de cada político; há índices de **"compattezza e affidabilità"** dos grupos, além de participação nas votações e trocas de grupo [V] (página do Openparlamento). É o mais próximo de um ranking entre os pares europeus.
- Gráficos com **código de embed** pronto, via iframe [V] (https://www.openpolis.it/).

### 1.10 OpenAustralia Foundation / They Vote For You (Austrália). Ativo.

- **Método.** As "Policies" são conjuntos de votações; para cada votação o site define como votaria quem apoia a política. "Rebel voters" e "attendance" aparecem como métricas. Os resumos das votações em linguagem simples são **editados por humanos**, e as votações ainda sem resumo dizem isso na página. O FAQ convida o leitor a conferir cada vínculo e a reportar erros. Financiado por doações ("Help keep They Vote For You trusted and independent") [V] (https://web.archive.org/web/2025/https://theyvoteforyou.org.au/help/faq).
- **Controvérsia.** Em 2021, o senador Andrew Bragg e o deputado Dave Sharma (Partido Liberal) reclamaram à ACNC e à AEC. O site dizia que Bragg votara contra direitos LGBT e contra a Voice indígena, embora ele tivesse liderado a campanha pelo casamento igualitário dentro da coalizão. Em março de 2022 os dois **processaram a fundação por "misleading and deceptive conduct"** [S] (https://en.wikipedia.org/wiki/OpenAustralia_Foundation, https://www.crikey.com.au/2021/11/16/liberal-party-voting-record-coalition-history/). O site pôs a policy em "draft" e a substituiu por duas [S] (resumo de busca). Críticos apontam que o site dizia que senadores votaram a favor ou contra o Acordo de Paris, que nunca passou pelo Senado [S] (https://patleslie.net/blogs/blog_tvfy/tvfy_post). Resultado do processo: [?].
- **Visual:** [?] Cloudflare bloqueou o acesso automatizado.

### 1.11 Vote Smart (EUA). Ativo, com déficit.

- Seis áreas por candidato: biografia, posições (Political Courage Test), votações, finanças de campanha, avaliações de grupos de interesse e discursos [S] (https://en.wikipedia.org/wiki/Vote_Smart).
- **A taxa de resposta ao Political Courage Test caiu de 72% (meados dos anos 1990) para 20% (2014)** [S] (https://www.blog.votesmart.org/post/political-courage-test-response-rates-and-voter-participation-trends-over-time-1996-2022).
- Receita de US$ 941 mil contra despesa de US$ 1,48 milhão (2025), demissões em 2014 e queda de membros pagantes [S] (Wikipédia, citando o Nonprofit Explorer da ProPublica).
- **Lição:** uma função que depende da cooperação voluntária do político perde força com o tempo.

### 1.12 Ballotpedia (EUA, Lucy Burns Institute). Ativo.

- Enciclopédia escrita por mais de 50 editores pagos (2021); receita de US$ 8,93 milhões em 2024 [S] (https://en.wikipedia.org/wiki/Ballotpedia).
- **Candidate Connection:** questionário publicado no perfil após verificação de identidade. Edições da equipe aparecem **entre [colchetes]**; incitação à violência e discurso de ódio são removidos [S] (https://ballotpedia.org/Ballotpedia:Our_approach_to_Candidate_Connection_survey_edit_and_removal_requests).
- **Política de IA generativa publicada:** a equipe não usa IA para escrever artigos, newsletters ou posts, nem para criar imagens. Pode usar para pesquisa (com verificação) e para resumir texto existente "with expert review" [S] (https://ballotpedia.org/Ballotpedia:How_we_use_generative_AI).

### 1.13 OpenSecrets (EUA). Ativo, em crise.

- Nasceu da fusão CRP + NIMP em 2021. Receita de US$ 2,5 milhões contra despesa de US$ 4,3 milhões (2023); **demitiu um terço da equipe em 2024** [S] (https://en.wikipedia.org/wiki/OpenSecrets, https://www.commondreams.org/news/opensecrets).
- **API pública encerrada em 15/04/2025**, com a indicação de procurar commercial@ para "custom data solution" [V] (https://www.opensecrets.org/open-data/api). Conteúdo sob CC BY-NC-SA 3.0 [V] (rodapé da mesma página).
- **Lição:** sem financiamento estável, a API gratuita é a primeira coisa a ser cortada e vira produto pago.

### 1.14 Congress.gov (oficial, Library of Congress)

- Votações nominais da Câmara chegaram ao Congress.gov só em agosto de 2025 [S] (https://blogs.loc.gov/law/2026/04/congress-gov-new-tip-and-top-april-2025-2/, https://www.congress.gov/help/enhancements). API pública com repositório no GitHub [S] (https://www.congress.gov/help/using-data-offsite).
- **Resumos do CRS:** escritos por analistas, com "huge backlog". Em março de 2024 o CRS desenvolvia cinco modelos de IA, com "a whole set of criteria that have to be met", e disse que os resumos por IA do Politico "did not pass the test" [S] (https://fedscoop.com/congressional-research-service-eyes-ai-bill-summaries/). Se isso já foi implantado: [?].
- **Visual:** [?] Cloudflare bloqueou o acesso.

### 1.15 América Latina e Portugal

- **Directorio Legislativo (Argentina/EUA).** Ativo. Modelo híbrido: projeto aberto (Directory of Legislators, rede latino-americana de transparência legislativa) e um **serviço pago de monitoramento regulatório** para empresas [V] (https://directoriolegislativo.org/en/what-we-do/). No conselho consultivo está **Cristiano Ferri Faria, diretor do LabHacker da Câmara dos Deputados** [V] (https://directoriolegislativo.org/en/who-we-are/), um contato direto com o Brasil.
- **Poderopedia (Chile).** Ganhou o Knight News Challenge em 2011 [S] (https://latamjournalismreview.org/es/articles/poderopedia-plataforma-digital-que-revela-redes-de-poder-lanza-nuevo-capitulo-en-venezuela/). **Em 30/09/2026, `poderopedia.org` redireciona para `/lander`, uma página de domínio estacionado** [V] (`curl`). Morto na prática.
- **Hemiciclo.pt (Portugal, 2017).** Criado por dois voluntários, tinha "ranking dos deputados mais desalinhados" com o partido [S] (https://sabado.pt/portugal/amp/hemiciclo-o-site-que-revela-como-vota-cada-deputado, https://observador.pt/2017/09/04/nasceu-o-hemiciclo-para-escrutinar-o-trabalho-dos-deputados/). **Hoje responde HTTP 410 Gone** [V].
- **oParlamento.pt / "Parlamento PT" (Portugal).** Ativo e o mais moderno do grupo. Separa **"Confirmado: voto individual confirmado diretamente pela fonte oficial"** de **"Contexto adicional: linha partidária ou leitura contextual. Não é voto individual confirmado"**, porque em Portugal quase todo voto é registrado por bancada. Mostra "Posições reconstruídas", publica a cadência de coleta por fonte (votações diárias, agenda a cada 15 minutos) e a **última votação carregada por legislatura**, e diz que "quando faltar informação, a lacuna permanece identificada em vez de ser preenchida por suposição". A legenda de tipos de votação separa lei, resolução ("em regra não altera diretamente uma lei") e voto simbólico. Tem botão "Abrir dados (CSV)", API e cartões com cor no topo (verde aprovado, vermelho rejeitado) [V] (https://oparlamento.pt/methodology, https://oparlamento.pt/votes, captura). Autoria e financiamento: [?] ("Projeto independente").

### 1.16 Novos projetos com IA (2024–2026)

- **Plenarwatch (Alemanha, Plenarwatch GbR, Munique).** O caso mais útil para a nossa IA. Título, "Worum ging es?" e argumentos de cada bancada são **escritos por IA (Claude Sonnet 4.6)**. **Os números (placar, resultado, número do documento) saem deterministicamente do XLSX oficial**, nunca da IA. As verificações automáticas são **bloqueantes**:
  - toda citação precisa estar palavra por palavra no Stenografischer Bericht, na página indicada;
  - nenhum nome de parlamentar pode aparecer fora de uma citação;
  - o título oficial é tratado como **citação**, porque títulos "sind regelmäßig wertend";
  - uma **verificação de neutralidade** confronta o título e o resumo com a posição de cada bancada que votou, e precisa **citar literalmente** o trecho que aponta; sem a citação, a objeção é descartada.

  O site também explica "Warum ein Ja manchmal Ablehnung bedeutet": o plenário vota o parecer da comissão, que muitas vezes recomenda rejeitar. Lema: "Keine Einordnung, keine Wertung". **Publica sem revisão humana quando passa nas verificações**; o que falha vai para revisão manual. Financiado "aus eigenen Mitteln", sem anúncios nem patrocínio [V] (https://plenarwatch.de/methodik/, https://plenarwatch.de/impressum/).
- **wahl.chat (Alemanha).** Chat de IA sobre programas de partidos e votações nominais, com fontes em cada resposta; diz ter "400.000+ Nutzer:innen" [V] (https://wahl.chat/).
- **ParlamentAI (Alemanha, comercial).** Busca em documentos do Bundestag com link para a passagem exata: "Wir lösen das Halluzinationsproblem nicht mit Versprechen, sondern mit Verifikation" [V] (https://parlament.ai/).
- **Civic Minded (EUA).** Resumos por IA de projetos do Congresso com o aviso explícito "**AI summaries have not yet been reviewed by humans**" [V] (https://app.becivicminded.com/). Contraexemplo útil: o rótulo existe, a revisão não.
- **Bill Explainer (EUA).** Resumos em linguagem simples, gratuitos [V] (https://bill-explainer.com/). Não achei a página que descreve o processo de revisão [?].
- **BillTrack50.** Resumos por IA gratuitos para ONGs [S] (https://www.nonprofitpro.com/article/billtrack50-boosts-nonprofits-access-to-state-legislation-with-ai-generated-bill-summaries/). Bloqueou o acesso direto [?].
- **Plural Policy** (sucessor comercial do Open States), **Quorum** e **FiscalNote.** IA legislativa vendida a empresas (B2B). A FiscalNote vendeu ativos em 2025 [S] (https://pluralpolicy.com/ai-powered-bill-tracking/, https://fiscalnote.com/newsroom/fiscalnote-reports-fourth-quarter-and-full-year-2025-financial-results). Lição: análise por IA de dados legislativos é um produto pago; o espaço gratuito, público e revisado está pouco ocupado.
- **Fracasso de referência: Politico.** O "Report Builder" (Capitol AI) e os "Live Summaries" publicaram erros, por exemplo tratando Roe v. Wade como vigente em 2025. Em novembro de 2025 um árbitro decidiu que o Politico violou o acordo coletivo ao usar IA sem supervisão humana; as ferramentas foram desligadas [S] (https://www.niemanlab.org/2025/12/politico-management-violated-key-ai-adoption-safeguards-arbitrator-finds/, https://pressgazette.co.uk/news/news-not-slop-politico-ai/).

---

## 2. Tabela comparativa

| Projeto | Status em 30/09/2026 | Financiamento | Unidade central | Escore ou índice por pessoa | Alertas e contas | Dados abertos e licença | IA | Visual |
|---|---|---|---|---|---|---|---|---|
| TheyWorkForYou | Ativo [V] | Charity: fundações, doações, lucro da SocietyWorks [S] | Perfil + resumo por tema | Frases fixas por faixa de 0–100; já retirou rankings (2006) [V/S] | Alertas por palavra e por pessoa [V] | API, download em massa (TWFY Votes) [V]; licença [?] | Só scraping interno com LLM; framework público [V] | Redesenhado em 2025 [V]; não visto [?] |
| GovTrack | Ativo [V] | Anúncios, assinantes, Patreon, Kickstarter; sem financiador externo [V] | Perfil em prosa | Ideologia e liderança por coautoria; boletins anuais retirados em 2024 [V] | Alertas desde 2004, listas, "Positions" [V] | API encerrada em 2017 [V]; CSV por votação [V] | Não encontrada | Datado, denso, com anúncios [V] |
| ProPublica Represent | Encerrado em 07/2024 [V] | Redação (doações) | Perfil | Não | Sim [?] | API encerrada [V] | Não | n/a |
| Abgeordnetenwatch | Ativo [V] | 12.500+ doadores recorrentes; verba inicial para captação [V] | Perguntas e respostas + perfil | Taxa de resposta por pessoa [V] | Newsletter; seguir perfil [?] | API CC0 [V] | Não (terceiros sim) | Marca forte; modal invasivo [V] |
| HowTheyVote.eu | Ativo [V] | Prototype Fund (BMBF), MIZ Babelsberg [V] | **Votação** | **Nenhum** [V] | RSS [V]; sem contas | ODbL, JSON/CSV por votação, dump semanal [V] | Não | Sóbrio, IBM Plex, imagem por votação [V] |
| VoteWatch Europe | Encerrado em 06/2022 [V] | Acadêmico, depois ONG; premium [?] | Estatísticas por eurodeputado | Sim [?] | [?] | Dataset histórico publicado [V] | Não | n/a |
| Parltrack | Ativo ("BETA") [V] | [?] | Dossiê + emenda | Não | E-mail e RSS por dossiê [V] | ODbL + AGPL [V] | Não | Datado [V] |
| NosDéputés | **HTTP 500, certificado vencido** [V] | Voluntários, esgotados [V] | Atividade por deputado | Estatísticas de atividade [V] | [?] | Open data [V] | Não | n/a |
| Openparlamento | Ativo [V] | Doações; sem anúncios [V] | Perfil + índices | **Indice di forza**, coesão [V] | [?] | Embeds [V]; licença [?] | Não | [?] |
| They Vote For You | Ativo [V] | Doações [V] | Política (tema) | "Voted very strongly for/against"; rebeliões, presença [V] | [?] | API [V]; licença [?] | Não (resumos humanos) [V] | [?] |
| Vote Smart | Ativo, com déficit [S] | Membros, doações [S] | Candidato | Avaliações de grupos de interesse [S] | [?] | [?] | Não | [?] |
| Ballotpedia | Ativo [S] | ONG, US$ 8,9 mi [S] | Enciclopédia | Não | Cédula de exemplo [?] | [?] | Política restritiva publicada [S] | [?] |
| OpenSecrets | Ativo, em crise [S] | Fundações; serviço comercial [V] | Dinheiro | Não | Newsletter [V] | **API encerrada em 2025**; CC BY-NC-SA [V] | Não | [?] |
| Congress.gov | Oficial [S] | Governo | Projeto | Não | Alertas [?] | API oficial [S] | Piloto no CRS [S] | [?] |
| Directorio Legislativo | Ativo [V] | Projetos + serviço B2B [V] | Diretório de legisladores | Não | B2B | [?] | [?] | [?] |
| Poderopedia | **Domínio estacionado** [V] | Knight (início) [S] | Rede de poder | Não | n/a | n/a | n/a | n/a |
| Hemiciclo.pt | **410 Gone** [V] | Voluntários [S] | Deputado | Ranking de desalinhados [S] | n/a | n/a | n/a | n/a |
| oParlamento.pt | Ativo [V] | [?] | Votação + partido | "Afinidades" com link para as votações [V] | [?] | CSV, API [V] | [?] | Moderno, cartões ilustrados [V] |
| Plenarwatch | Ativo [V] | Recursos próprios [V] | Votação como post | Não ("keine Wertung") [V] | Newsletter, Telegram [V] | [?] | **IA com verificações determinísticas bloqueantes** [V] | Feed editorial [V] |

---

## 3. Ideias transferíveis, por valor para um site brasileiro

**Dados brasileiros conferidos nesta pesquisa:** `GET /api/v2/votacoes` da Câmara lista votações com descrição ("Rejeitada a Emenda nº 97", "Mantido o texto", "Rejeitado o Requerimento"). `GET /api/v2/votacoes/{id}/orientacoes` traz a orientação de **"Governo"**, **"Maioria"** e **"Oposição"** (por exemplo `2611313-31`: Governo "Sim", Maioria "Sim", Oposição "Liberado"). Várias votações voltam com **zero orientações e sem votos nominais**: são simbólicas [V] (https://dadosabertos.camara.leg.br/api/v2/votacoes/2611313-31/orientacoes). Senado (https://legis.senado.leg.br/dadosabertos/) e TSE (https://dadosabertos.tse.jus.br/) não foram sondados nesta pesquisa [?].

**1. A votação como página principal e compartilhável.** *Quem prova que funciona: HowTheyVote.eu.* Uma página por votação nominal com:
- barra única empilhada com hachura e ícone, legível sem depender de cor;
- frase completa com o denominador ("513 deputados; 470 registraram voto; 43 não registraram");
- abas Deputados / Partidos / UF com filtros;
- **imagem gerada no servidor por votação, com alt text descritivo**;
- embed, CSV/JSON da votação, "Fontes" e "Reportar erro".

*Dados:* `/votacoes/{id}/votos` + `/orientacoes` (Câmara) e votação nominal do Senado [?]. Encaixa no SSR do Inertia (og:image por rota). É a ideia mais diferenciadora: transforma cada votação em algo compartilhável sem ranquear ninguém.

**2. Classificar o tipo de votação antes de qualquer conta.** *Quem prova: TheyWorkForYou ("action votes" × "informative"), oParlamento (legenda de tipos), Plenarwatch ("um Sim às vezes significa rejeição").* No Brasil, requerimento, destaque, emenda, "mantido o texto", redação final e mérito são coisas diferentes, e votar "Sim" num destaque supressivo pode significar manter o texto. Proposta:
- classificação **determinística** a partir de `descricao` e do tipo da proposição;
- só votos de mérito entram no "n de m" exibido no topo do perfil;
- os demais ficam visíveis em "outras votações".

*Dados:* `descricao`, `proposicaoObjeto`, `siglaOrgao` da Câmara; regras escritas na metodologia pública.

**3. Cobertura e lacunas sempre visíveis.** *Quem prova: oParlamento ("Confirmado" × "Contexto adicional"; última votação carregada), HowTheyVote ("cannot rule out... errors"), TheyWorkForYou (horário em que os votos aparecem).* No Brasil, votação simbólica não tem voto individual. O perfil deve dizer "N votações simbólicas no período não têm registro nominal" em vez de omitir. Cada página mostra "dados atualizados em" e a cadência por fonte. *Dados:* votações sem `/votos`, comparadas com o total do plenário.

**4. "n de m" com denominador comparável e contexto pela mediana, sem ranking.** *Quem prova: TheyWorkForYou ("comparable MPs" que tiveram a chance de votar), GovTrack ("on par with the median of 2.1%").* Para "votou igual à orientação do Governo em n de m" e "igual à maioria do partido em n de m", o denominador são só as votações de mérito em que houve orientação e a pessoa registrou voto. O contexto vira uma frase descritiva ("a mediana da Câmara no período é x de y"). Nunca ordenar pessoas. Vocabulário neutro, como no HowTheyVote ("did not vote") e no Abgeordnetenwatch ("Nicht beteiligt"): **"não registrou voto"**, nunca "faltou" nem "missed". *Dados:* `/orientacoes` (Governo/Maioria) + `/votos` + filiação na data da votação.

**5. Resumo por IA com verificações determinísticas e revisão humana.** *Quem prova: Plenarwatch (o modelo), Ballotpedia (política de IA publicada), mySociety (framework e registro de usos); contraexemplos: Civic Minded ("not yet reviewed") e Politico.* Proposta:
- números, placar, autoria e datas **nunca** saem da IA; o template injeta os valores da API;
- o título oficial da proposição é exibido como **citação**, não como descrição;
- verificador automático bloqueia o resumo se aparecer número que não está na fonte, nome de parlamentar ou adjetivo de uma lista proibida;
- verificação de neutralidade que precisa citar o trecho literal que aponta;
- depois disso, **revisão humana obrigatória** (decisão já tomada no grilling v2), com modelo, hash da fonte e revisor guardados;
- rótulo visível e "O que achou deste resumo?" (como no HowTheyVote).

*Dados:* inteiro teor e ementa (`/proposicoes/{id}`, `urlInteiroTeor`).

**6. Explicação do voto e direito de resposta com regras escritas.** *Quem prova: Abgeordnetenwatch (código de moderação + Kuratorium para recurso), Ballotpedia (edições entre [colchetes]), TheyWorkForYou (anotações e whip reports; o site afirma que a publicação aumentou as justificativas públicas).* Primeiro passo viável: "Nota do gabinete" anexada a uma votação ou proposição, enviada de e-mail institucional (`@camara.leg.br`, `@senado.leg.br`) [?] (validar o domínio), publicada com rótulo "texto do gabinete", moderada por código público e com recurso. É o embrião da "comunidade" futura sem abrir para perguntas abertas no primeiro dia. Atenção ao período eleitoral (pesquisa jurídica do projeto).

**7. Alertas por parlamentar, proposição e tema.** *Quem prova: GovTrack (função original, de 2004), TheyWorkForYou (alertas por palavra, redesenhados em 2025), Parltrack (e-mail e RSS por dossiê).* Um e-mail quando "meu eleito" registra voto numa votação de mérito, quando uma proposição seguida muda de situação, ou quando uma palavra aparece numa ementa. Também RSS sem conta (custo zero). *Dados:* `/votacoes` por data, `/proposicoes/{id}/tramitacoes` [?] (endpoint não sondado), filas do Laravel.

**8. Dados abertos como dump estático versionado, não como API ao vivo.** *Quem prova: HowTheyVote (dump semanal no GitHub, ODbL), Abgeordnetenwatch (CC0 com fair use), Parltrack (ODbL), VoteWatch (o legado sobreviveu pelo dataset); contraexemplos: GovTrack, ProPublica e OpenSecrets encerraram APIs.* O ETL já publica JSON versionado. Basta expor esse JSON e CSVs com licença explícita (recomendo **CC0 ou ODbL** para os dados derivados, separando fotos e textos oficiais, como o HowTheyVote faz). Evita prometer uma API que depois teria de ser cortada.

**9. Perfil que abre com uma frase descritiva gerada por template, não por IA.** *Quem prova: GovTrack ("X is the representative for... has served since... next up for reelection").* Ex.: "Fulana é deputada federal por SP pelo Partido X desde 2023. No período, registrou voto em n de m votações nominais de mérito." Fácil de ler, de compartilhar e de auditar. *Dados:* `/deputados/{id}`, histórico de filiação.

**10. Rapidez com promessa pública.** *Quem prova: TheyWorkForYou Votes ("within minutes"), HowTheyVote (cerca de 30 minutos), oParlamento (cadência por fonte).* Publicar o tempo típico entre a votação oficial e a atualização no site. Dado desatualizado corrói a confiança mais rápido que um erro de design.

**11. Home centrada em busca, com sugestões e a "última sessão".** *Quem prova: HowTheyVote ("Try Ukraine, Frontex, or Environment"; link para a última sessão).* No Brasil: busca por nome, município ou CEP → "meus eleitos" (o TSE liga candidato a UF) [?], mais "Votações desta semana".

**12. Publicar a própria parcialidade e as regras de financiamento.** *Quem prova: GovTrack (carta que recusa verba partidária), Parltrack (admite a origem em ativismo digital), Plenarwatch ("Wenn sich daran etwas ändert, steht es hier").* Uma página "Como nos financiamos" com a regra de quais fontes recusamos e um registro de mudanças.

---

## 4. Anti-padrões e fracassos a evitar

1. **Um número que resume a pessoa.** O GovTrack retirou boletins depois que "most liberal senator" virou munição de campanha; o próprio autor admite que parlamentares manipulam o escore [V]. O "Indice di forza" (Openparlamento) e o "ranking dos desalinhados" (Hemiciclo, hoje 410) seguem a mesma lógica [V/S]. Regra para nós: sem índice composto, sem ordenação de pessoas, nem no comparador.
2. **Métrica de volume que incentiva teatro.** No caso TheyWorkForYou de 2006, deputados inflaram perguntas e falas para subir nas estatísticas, e o site tirou os rankings [S]. O NosDéputés era "accusé d'être la racine de tous ces maux" [V]. Não exibir "quantidade de discursos" ou "quantidade de projetos" como desempenho; quando aparecerem, que seja como lista descritiva.
3. **Juntar votações num "posicionamento".** They Vote For You foi processado por "misleading and deceptive conduct" e chegou a atribuir votos sobre o Acordo de Paris, que nunca foi votado [S]. O TheyWorkForYou admitiu que o sistema era "gameable" por moções sem efeito [V]. O "o que estava em jogo" deve descrever **a votação**, nunca concluir "fulano é contra X".
4. **Contar ausência como falha.** O TheyWorkForYou tirou a ausência do cálculo em 2024 porque não conhecia o motivo [V]. O GovTrack ainda usa "missed" [V]. Nós: "não registrou voto", com a explicação de licenças, missões e obstrução quando a fonte oficial trouxer o motivo [?] (o campo depende da API).
5. **Depender de voluntários ou dos fundadores.** NosDéputés com HTTP 500 e certificado vencido; VoteWatch fechou quando os fundadores mudaram de projeto; Hemiciclo 410; Poderopedia com domínio estacionado [V]. Mitigação: dois mantenedores com acesso, renovação automática de certificado e domínio, runbook, dump público dos dados e arquivo por legislatura.
6. **Financiamento que some sem aviso.** Represent fechou sem explicação [V]; OpenSecrets demitiu um terço da equipe e cortou a API [S/V]; Vote Smart opera no vermelho [S].
7. **API gratuita como promessa eterna.** GovTrack (2017), ProPublica (2024) e OpenSecrets (2025) desistiram [V]. Dump estático custa quase nada; API com chave e SLA, não.
8. **IA publicada sem revisão.** Os erros do Politico terminaram em arbitragem perdida e ferramenta desligada [S]. Civic Minded publica "not yet reviewed" [V]. O CRS reprovou resumos de IA [S]. Até o Plenarwatch publica sem humano quando passa nas verificações [V], o que nossa regra (revisão humana) não permite.
9. **Funções que dependem da boa vontade do político.** Vote Smart: resposta caiu de 72% para 20% [S]. O direito de resposta deve ser um extra, nunca a base do perfil.
10. **Pagar para aparecer melhor.** O Abgeordnetenwatch já vendeu perfis ampliados por 179 € [S]. No Brasil isso pode, além do conflito com a neutralidade, esbarrar na lei eleitoral [?] (hipótese a validar com a pesquisa jurídica).
11. **Tom de militância dentro do produto neutro.** O modal "Bleib kritisch" do Abgeordnetenwatch e o histórico de boicote partidário (2007) [V/S]. Separar claramente o produto descritivo de qualquer campanha.
12. **Anúncios.** O GovTrack depende de anúncios e precisa de uma cláusula sobre neutralidade dos anunciantes [V]. Para nós já está vetado (AGENTS.md).

---

## 5. Modelos de sustentabilidade para uma associação sem fins lucrativos no Brasil

Em ordem de encaixe com a regra de neutralidade e sem anúncios:

1. **Doação recorrente pequena e numerosa.** É o modelo do Abgeordnetenwatch: mais de 12.500 doadores, a partir de 5 € por mês [V]. Openparlamento e They Vote For You fazem o mesmo [V]. No Brasil: PIX recorrente ou plataforma de recorrência. Base pulverizada protege a independência. Atenção: a dedutibilidade para pessoa física em doação a associação comum é limitada ou inexistente [?] (confirmar com contador).
2. **Verba inicial para montar a captação, não para pagar o produto.** O Abgeordnetenwatch usou os 85.000 € da OLIN para contratar uma pessoa de captação [V]. Pedir a fundações uma verba de 2–3 anos explicitamente para construir a base de doadores.
3. **Fundos de protótipo e inovação cívica.** HowTheyVote nasceu do Prototype Fund [V]. No Brasil: editais de fundações e de programas de inovação em jornalismo e tecnologia cívica [?] (levantar editais abertos em 2026–2027). Evitar verba de governo ou de partido; o GovTrack escreve isso na carta [V].
4. **Serviço comercial separado, com lucro revertido.** É o modelo da SocietyWorks da mySociety [S]; o Directorio Legislativo e o OpenSecrets vendem dados ou monitoramento sob medida [V]. Para nós: extrações sob medida, formação de jornalistas, relatórios para redações e pesquisadores. Regras: **os dados públicos continuam gratuitos**, o cliente não influencia o conteúdo e a lista de clientes é pública.
5. **Financiamento coletivo por funcionalidade.** TheyWorkForYou no Crowdfunder e GovTrack no Kickstarter [S/V]. Bom para entregas visíveis, como o comparador ou os alertas.
6. **Parcerias acadêmicas e institucionais.** VoteWatch nasceu acadêmico [S]; o LabHacker da Câmara tem gente no conselho do Directorio Legislativo [V]. Universidades podem hospedar a base de dados ou ceder bolsistas, sem controle editorial.
7. **Custo baixo como estratégia.** O Plenarwatch se paga com recursos próprios [V]; o HowTheyVote publica dumps no GitHub [V]. VPS de US$ 10–30 por mês (decisão do grilling v2) e IA só para resumos revisados mantêm o custo fixo pequeno o bastante para sobreviver a um ano ruim.
8. **Governança que sustenta a credibilidade.** Um conselho pluripartidário (como o Kuratorium do Abgeordnetenwatch) [V], relatórios financeiros anuais publicados [V] e um plano de sucessão escrito desde o início, para não repetir o Regards Citoyens [V].

---

## 6. Notas de design: o que diferencia na prática

- **HowTheyVote** prova que clareza vence efeito: uma barra, uma frase com denominador, abas e filtros, imagem por votação. Copiar a estrutura, não a estética.
- **oParlamento.pt** é a referência lusófona mais próxima: cartões com cor no topo indicando o resultado, ilustração por tema, filtros recolhíveis, CSV no topo e metodologia escrita para leigos ("Em 30 segundos").
- **Abgeordnetenwatch** mostra que uma marca forte (carimbo roxo) torna o site memorável. Mostra também que modal e tom militante atrapalham.
- **GovTrack** mostra o custo de não modernizar: densidade, anúncios e estética de 2014.
- **Oportunidade para o Mandato Aberto:** ninguém do grupo combina a sobriedade de dados do HowTheyVote com a qualidade visual de um produto de consumo (referência Apple e Spotify do grilling v2). Esse é o espaço livre.

---

## 7. Fontes principais (todas citadas acima)

- TheyWorkForYou: https://research.mysociety.org/html/2024-voting-records/ · https://web.archive.org/web/2025/https://www.theyworkforyou.com/voting-information/ · https://www.mysociety.org/2025/05/19/theyworkforyou-votes/ · https://www.mysociety.org/2025/10/23/theyworkforyou-update-a-richer-view-of-parliament/ · https://www.mysociety.org/2025/03/13/updating-theyworkforyous-voting-summaries/ · https://research.mysociety.org/html/ai-framework/ · https://en.wikipedia.org/wiki/TheyWorkForYou
- GovTrack: https://www.govtrack.us/about · https://www.govtrack.us/about/analysis · https://www.govtrack.us/posts/434/2024-07-26_we-retracted-our-single-year-legislator-report-cards-after-warning-about-their-unreliability · https://congressionaldata.org/ending-govtracks-bulk-data-and-api/
- ProPublica: https://projects.propublica.org/represent/
- Abgeordnetenwatch: https://www.abgeordnetenwatch.de/ueber-uns/mehr/finanzierung · https://www.abgeordnetenwatch.de/ueber-uns/faq · https://www.abgeordnetenwatch.de/ueber-uns/mehr/moderations-codex · https://www.abgeordnetenwatch.de/api · https://en.wikipedia.org/wiki/Parliamentwatch
- HowTheyVote: https://howtheyvote.eu/about · https://howtheyvote.eu/votes/184118 · https://fosdem.org/2026/schedule/event/39YMYR-how-they-vote/ · https://github.com/HowTheyVote/howtheyvote
- VoteWatch: https://www.votewatch.eu/ · https://simonhix.com/projects/
- Parltrack: https://parltrack.org/ · https://parltrack.org/about
- Regards Citoyens: https://www.regardscitoyens.org/nosdeputes-fr-cest-reparti-pour-un-dernier-tour/
- Openpolis: https://parlamento19.openpolis.it/
- They Vote For You: https://web.archive.org/web/2025/https://theyvoteforyou.org.au/help/faq · https://en.wikipedia.org/wiki/OpenAustralia_Foundation · https://patleslie.net/blogs/blog_tvfy/tvfy_post
- Vote Smart: https://en.wikipedia.org/wiki/Vote_Smart · https://www.blog.votesmart.org/post/political-courage-test-response-rates-and-voter-participation-trends-over-time-1996-2022
- Ballotpedia: https://ballotpedia.org/Ballotpedia:How_we_use_generative_AI · https://ballotpedia.org/Ballotpedia:Our_approach_to_Candidate_Connection_survey_edit_and_removal_requests
- OpenSecrets: https://www.opensecrets.org/open-data/api · https://en.wikipedia.org/wiki/OpenSecrets
- Congress.gov e CRS: https://fedscoop.com/congressional-research-service-eyes-ai-bill-summaries/ · https://www.congress.gov/help/enhancements
- América Latina e Portugal: https://directoriolegislativo.org/en/who-we-are/ · https://directoriolegislativo.org/en/what-we-do/ · https://oparlamento.pt/methodology · https://observador.pt/2017/09/04/nasceu-o-hemiciclo-para-escrutinar-o-trabalho-dos-deputados/
- Novos projetos com IA: https://plenarwatch.de/methodik/ · https://wahl.chat/ · https://parlament.ai/ · https://app.becivicminded.com/ · https://bill-explainer.com/ · https://www.niemanlab.org/2025/12/politico-management-violated-key-ai-adoption-safeguards-arbitrator-finds/
- Dados brasileiros: https://dadosabertos.camara.leg.br/api/v2/votacoes · https://dadosabertos.camara.leg.br/api/v2/votacoes/2611313-31/orientacoes

---

## 8. Não consegui verificar

- Visual de TheyWorkForYou, They Vote For You, Congress.gov, Vote Smart e Ballotpedia: bloqueios 429, 403 ou Cloudflare.
- Licença dos dados do TheyWorkForYou e do They Vote For You.
- Desfecho do processo de Bragg e Sharma contra a OpenAustralia Foundation.
- Motivo oficial do fechamento do ProPublica Represent (a página não informa).
- Modelo premium do VoteWatch (só em fonte fraca).
- Se o Abgeordnetenwatch ainda vende perfis ampliados.
- Se o piloto de IA do CRS já está em produção no Congress.gov.
- Financiamento do Parltrack e autoria e financiamento do oParlamento.pt.
- Processo de revisão da IA no Bill Explainer e no BillTrack50.
- Para o Brasil: orientação de bancada no Senado, o endpoint de tramitações e o campo de motivo de ausência na API da Câmara, os dados do TSE para "meus eleitos", a dedutibilidade de doações e o risco eleitoral de perfis pagos.
