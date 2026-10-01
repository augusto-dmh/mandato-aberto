# Panorama competitivo brasileiro — Mandato Aberto v2

**Data da pesquisa:** 30/09/2026 · **Escopo:** ferramentas brasileiras que mostram o que deputados federais, senadores e a Presidência fizeram, a partir de dados oficiais ou de curadoria. · **Para:** relançamento em 01/02/2027 (Laravel + Inertia + Vue), ver `research/04-direcao-v2.md` e `research/05-grilling-escopo-v2.md`.

**Legenda de certeza (vale para cada afirmação):**
- **[V]** verificado por mim em 30/09/2026: abri a página (WebFetch, `curl` ou navegador) e li o conteúdo citado.
- **[S]** fonte secundária: imprensa, release, Wikipedia ou resumo de busca; não abri a ferramenta, ou ela não carregou.
- **[?]** não verificado: inferência minha ou dado que não consegui confirmar.

**Limites do método.** Só capturei tela do Radar do Congresso, porque a captura do navegador falhou nos outros sites. Nos demais, a análise de UX vem do HTML bruto (stack, peso, fontes, rastreadores, tema) e do texto renderizado, não de inspeção visual. Os tempos de resposta são de uma única medição com `curl` e servem como indício, não como benchmark.

---

## 0. Linha de base: o Mandato Aberto hoje

- MVP estático (Astro) no ar em https://augusto-dmh.github.io/mandato-aberto/ com 57ª legislatura da Câmara: 643 deputados com voto, 1.597 votações nominais, 23.291 proposições. Busca por nome, UF e partido, perfis com participação em votações nominais, votos iguais à orientação do Governo, votos iguais à maioria do partido, autoria (PL, PLP, PEC, PDL, PRC) e cruzamento com candidaturas do TSE em 2026. Rebuild diário; "Atualizado em 30/09/2026 12:26". [V] https://augusto-dmh.github.io/mandato-aberto/ e https://augusto-dmh.github.io/mandato-aberto/metodologia/
- A metodologia diz que cada número "pode ser refeito à mão a partir dos arquivos oficiais". Há páginas de correção de erro, privacidade e código aberto. [V] mesma URL de metodologia
- O HTML da home tem cerca de 311 KB, porque a lista de deputados vai embutida na página. Os concorrentes leves ficam entre 33 e 40 KB. [V] medição com `curl`
- A v2 deixa dinheiro de fora (cota parlamentar, emendas, campanha). Quase todos os concorrentes têm esse dado; ver seção 5. [V] `research/05-grilling-escopo-v2.md`

---

## 1. Fichas por concorrente

### A. Rankings, notas e índices (o campo que o Mandato Aberto decidiu não ocupar)

#### Ranking dos Políticos — https://ranking.org.br (politicos.org.br redireciona para lá)
- **Status:** ativo. "Última atualização: 27/07/2026" no ranking acumulado, com 594 parlamentares, 47 páginas de lista. [V] https://ranking.org.br/en/ranking/politicos/todos/acumulado/1
- **Oferta:** nota de 0 a 10 para deputados e senadores, ranking nacional, por UF e por partido, filtros por ano (2023 a 2026), "Meu Ranking" e "Comparar" atrás de login, newsletter e "Match Eleitoral" (17 perguntas, 7 presidenciáveis, nota de 0 a 10 de alinhamento, cálculo no navegador, mas com captura de e-mail). [V] https://ranking.org.br/ · [S] https://x.com/RankPolitico/status/2102914627625422990 · https://ranking.org.br/en/match-eleitoral
- **Metodologia:** votos 75%, gastos 10%, presença 10%, privilégios 5%; bônus de produção (+0,6) e articulação (+0,4); −0,5 por condenação. Um "Conselho" escolhe as votações pontuadas com 70% de consenso. O próprio site diz "não somos neutros" e declara pilares de Estado de Direito, eficiência e ambiente de negócios. [V] https://ranking.org.br/criterios-e-metodologia · https://ranking.org.br/en/criteria-and-methodology
- **Modelo:** think tank fundado em 2011 por Alexandre Ostrowiecki e Renato Feder (ex-Multilaser), financiado por doações de pessoas físicas e jurídicas sem lista pública de doadores, com botão "Doe Agora". Promove o Prêmio Excelência Parlamentar (11ª edição em 2026: 80 deputados e 20 senadores premiados, com presidenciáveis na plateia). [S] https://pt.wikipedia.org/wiki/Ranking_dos_Pol%C3%ADticos · [V] https://ranking.org.br/ · [S] https://ranking.org.br/artigos/excelencia-parlamentar-2026-veja-a-lista-dos-premiados-pelo-ranking-dos-politicos · https://diariodopoder.com.br/brasil-e-regioes/excelencia-parlamentar-reune-presidenciaveis-e-premia-os-melhores-do-congresso
- **Tom:** editorial e normativo ("melhores", "premiados", "Vote com informação, não com promessa"). [V] https://ranking.org.br/
- **UX:** Astro com trechos legados de Next e WordPress no mesmo HTML, fonte Poppins, Google Tag Manager, cerca de 336 KB de HTML na home, login exigido para comparar. [V] `curl` da home · Qualidade visual e mobile: [?]
- **Export/API/IA:** nenhum export ou API visível; IA não mencionada. [V] https://ranking.org.br/
- **Controvérsias:** o Intercept Brasil (2018) chamou o ranking de "engodo" liberal. Segundo a Wikipedia, a FGV (2015) apontou que o site "dá ou tira pontos dependendo da tendência do político a votar". O caso mais citado é de 2018: Luiza Erundina (sem processos) em 505º e José Medeiros (cassado) em 80º. A Teoria e Debate e o DIAP escreveram sobre risco de manipulação eleitoral. O cofundador Renato Feder virou secretário de Educação (PR e SP), com acusações de conflito de interesse. [S] https://www.intercept.com.br/2018/08/05/atencao-eleitor-nao-caia-no-engodo-chamado-ranking-dos-politicos/ (403 para o meu fetch) · https://pt.wikipedia.org/wiki/Ranking_dos_Pol%C3%ADticos · https://teoriaedebate.org.br/colunas/os-rankings-parlamentares-e-os-riscos-de-manipulacao-no-processo-eleitoral/ · https://www.diap.org.br/index.php/noticias/agencia-diap/91014-rankings-parlamentares-e-riscos-de-manipulacao-na-eleicao · https://www.pragmatismopolitico.com.br/2018/08/farsa-ranking-dos-politicos-internet.html
- **Processos judiciais:** não achei ação judicial ou representação no TSE contra a organização; a Wikipedia não registra nenhuma. [?]
- **Fraqueza central:** a pontuação depende de um conselho com posição ideológica declarada, e por isso a nota não é reproduzível por quem discorda dos pesos.

#### Placar Político — https://placarpolitico.com.br (novo em 2026)
- **Status:** ativo, recalculado semanalmente. [V] https://placarpolitico.com.br/sobre
- **Oferta:** nota de 1 a 99 para os 513 deputados, ranking nacional e por UF, quiz "Qual deputado vota como você?" (respostas no navegador, sem cadastro), comparador de dois deputados ("mesma régua"), candidaturas 2026 dos deputados atuais, "Recordes do mandato" ("os extremos da legislatura"), página de municípios e newsletter semanal grátis para até 2 deputados ("presença, votos, projetos, gastos e a nota"). [V] https://placarpolitico.com.br/ · https://placarpolitico.com.br/recordes
- **Metodologia:** votações 20%, produtividade 20%, presença 20%, influência 15%, emendas 15%, economia 10%. Nas votações-chave, +1 ou −1 conforme o lado; ausência nas votações decisivas vale zero e "não é neutralidade". Presença comparada ao percentil 95. O site diz que "80% da nota é dado oficial puro" e que é "apartidário, mas não finge neutralidade". [V] https://placarpolitico.com.br/sobre
- **Modelo:** sem anúncio, sem partido e sem verba pública; PIX avulso e Apoia.se mensal, com relatório semanal completo e PDF mensal para apoiadores. [V] https://placarpolitico.com.br/ · https://placarpolitico.com.br/sobre
- **UX:** Next.js, cerca de 40 KB de HTML, fontes próprias, tema escuro por padrão (`localStorage 'theme' || 'dark'`), Instagram como canal. [V] `curl` da home
- **Por que importa:** é o concorrente mais próximo do roadmap da v2 (perfil, newsletter de "meus eleitos", comparador e candidaturas) e já tem tudo isso no ar, mas com nota e ranking.

#### Tô De Olho — https://todeolho.org (TCC no IFBA, 2026)
- **Status:** ativo; no ar entre março e maio de 2026 e relançado em setembro de 2026. [S] https://portaldocase.com.br/categoria/politica/noticias/novo-site-permite-consultar-e-comparar-candidatos-das-eleicoes-de-2026 · [V] https://todeolho.org/
- **Oferta:** Senado apenas: ranking, emendas, comparador, votações e metodologia, mais o "Quem Votar" (https://todeolho.org/quemvotar/) com candidaturas, patrimônio e planos de governo do TSE. [V] https://todeolho.org/
- **Metodologia:** produtividade × 0,35 + presença em votações × 0,25 + economia da cota × 0,20 + comissões × 0,20; licença de saúde e missão oficial não contam como ausência. [V] https://todeolho.org/metodologia
- **UX:** Next.js com alternância de tema claro/escuro e código aberto. [V] https://todeolho.org/ · https://github.com/Alzarus/to-de-olho
- **Fraqueza:** projeto de uma pessoa, nascido como TCC; a sustentabilidade é incerta. [?]

#### Fiscalize o Poder — https://fiscalizeopoder.com.br (agosto de 2026)
- **Status:** ativo. [V] https://fiscalizeopoder.com.br/
- **Oferta:** "Índice Fiscalize" (assiduidade, CEAP, "coerência" nas votações nominais), rankings, comparação de partidos, emendas, "votações sensíveis", correlação voto × emenda, rede de relações políticas, mapas de calor de alinhamento e impacto municipal. [V] https://fiscalizeopoder.com.br/ · [S] https://an9.com.br/plataforma-simplifica-a-fiscalizacao-do-congresso-e-lanca-indice-para-avaliar-parlamentares/
- **IA:** chat em linguagem natural ("A IA narra dados reais — nunca inventa"). [V] https://fiscalizeopoder.com.br/
- **Modelo:** gratuito, doações via Apoia.se. [V] mesma URL
- **Risco que nos ensina:** um chat aberto sobre parlamentares em ano eleitoral bate na Res. TSE 23.610 (vedação a resposta sobre voto), que o Mandato Aberto já tirou do escopo. [?] sobre conformidade; a regra está em `research/01-pesquisa-juridica.md`

#### Atlas Político — http://atlaspolitico.com.br
- **Status:** morto: o domínio não responde (`curl` 000) em 30/09/2026. [V] Era um ranking de "eficiência" (representatividade, responsabilidade de campanha, ativismo legislativo, fidelidade partidária, debate) com registros por volta de 2015. [S] https://democraciadigital.fgv.br/iniciativas/atlas-politico

#### Elas no Congresso / QuitérIA — https://www.elasnocongresso.com.br (Instituto AzMina)
- **Status:** ativo, mas a análise publicada vai até junho de 2025; a QuitérIA continua coletando. [V] https://www.elasnocongresso.com.br/
- **Oferta:** ranking de deputados e senadores por atuação "favorável" ou "desfavorável" aos direitos das mulheres, com avaliação de 19 organizações parceiras (CFEMEA, Artigo 19 e outras); 812 proposições avaliadas entre janeiro de 2024 e julho de 2025. [V] mesma URL · [S] https://azmina.com.br/projetos/elas-no-congresso/
- **IA:** QuitérIA, lançada em 2025, usa PLN para classificar proposições por mais de 300 palavras-chave, com coleta diária nas APIs das duas Casas e acesso gratuito. [S] https://azmina.com.br/reportagens/azmina-lanca-ia-feminista/ · https://github.com/institutoazmina/ia-feminista-elas-no-congresso
- **Tom:** advocacy declarado; licença CC BY-NC-ND. [V]
- **UX:** Astro + GTM. [V] `curl`

#### Observatório do Legislativo Brasileiro (IESP-UERJ) — https://olb.org.br
- **Oferta:** ranking de −10 a +10 por tema (clima, igualdade racial, infância), calculado como "ações favoráveis menos contrárias", e o Monitor Legislativo, com atualização diária só da Câmara (PL, PLP, PEC, MPV) e temas tirados da indexação da própria Câmara, sem IA. [V] https://olb.org.br/metodologia-do-ranking-de-parlamentares/ · https://olb.org.br/monitor/ajuda/
- **Tom:** acadêmico, mas com valência temática (favorável ou contrário).

#### DIAP — Quem é Quem no Congresso — https://www.quemequemnocongresso.org.br (23/09/2026)
- **Oferta:** deputados e senadores, partidos, votações selecionadas ligadas aos ODS, bens declarados, bases eleitorais e "Critérios"; "explorar, comparar e compartilhar". [V] https://www.quemequemnocongresso.org.br/ · [S] https://www.diap.org.br/index.php/noticias/agencia-diap/93148-quem-e-quem-no-congresso-amplia-transparencia-sobre-atuacao-parlamentar
- **Tom:** curadoria sindical, que distingue dado oficial de análise editorial. [S]

### B. Jornalismo de dados

#### Radar do Congresso (Congresso em Foco) — https://radar.congressoemfoco.com.br
- **Status:** ativo; o rodapé diz "base de dados atualizada em: 30/09/2026", mas o seletor da aba Presença só vai até 2025. [V] captura de tela de https://radar.congressoemfoco.com.br/assiduidade
- **Oferta:** perfis de 513 deputados e 81 senadores com as abas Perfil, Votações, Proposições, Gastos, Discursos, Patrimônio, Eleição, Doações, Inquéritos, Campanha e Interação; navegação global com Bancadas (setoriais), Votações importantes, Governismo e Presença. [V] https://radar.congressoemfoco.com.br/parlamentar/1236423/perfil
- **Governismo:** a Câmara aparece "70% alinhada com o governo em 1.444 votações", num semicírculo "Base do Governo ↔ Oposição" com foto do presidente; ausência e abstenção derrubam o índice. [V] captura de https://radar.congressoemfoco.com.br/governismo/camara · [S] https://www.congressoemfoco.com.br/tag/indice-de-governismo
- **Presença:** tabela com Sessões, Dias presentes, "Ausências justificadas" e "Ausências não justificadas", o mesmo vocabulário da Câmara. [V] captura
- **Modelo:** lançado em 19/05/2020 com verba do Google News Initiative; o Congresso em Foco vende o "Insider" (R$ 39,90/mês: alertas no celular, agenda, análise semanal "Farol Político") e organiza o Prêmio Congresso em Foco 2026, com voto popular. [S] https://www.congressoemfoco.com.br/noticia/17737/congresso-em-foco-lanca-plataforma-inedita-sobre-acao-dos-parlamentares · https://www.congressoemfoco.com.br/noticia/21919/chegou-o-congresso-em-foco-insider-experimente · https://premio.congressoemfoco.com.br/
- **UX:** SPA em Angular. O HTML bruto de qualquer perfil tem só o título "Radar do Congresso", então links compartilhados geram prévia genérica [V] `curl`. Navegação por ícones e tipografia sans limpa; a tabela de presença transborda na horizontal a cerca de 790 px e o selo do reCAPTCHA cobre conteúdo. [V] captura
- **Export/API/IA:** nada visível. [V] parcial; [?] para áreas logadas

#### Basômetro (Estadão) — https://arte.estadao.com.br/politica/basometro/
- **Status:** congelado. Os governos disponíveis vão de "Lula 1" a "Bolsonaro (PSL, 2019–2022)" e não há Lula 3. A página tem o paywall marcado como `ativo`. [V] `curl` da página
- **Oferta:** gráfico de pontos (um ponto por deputado, mais à direita quanto mais governista, cor mais forte quanto mais presente) e visões por Governo, Partido e Deputado, desde 2003. [V] mesma URL · Código AGPL: https://github.com/estadaoDados/basometro [V]
- **Lição:** ferramenta de redação morre quando acaba o ciclo editorial.

#### Poder360 — taxa de governismo
- **Status:** reportagens pontuais com tabela interativa, não ferramenta permanente. A de 14/12/2023 cruzou mais de 100 mil votos com 283 orientações do governo e contou abstenção e obstrução como "não seguiu"; o próprio texto pede leitura "com cautela". [V] https://www.poder360.com.br/congresso/saiba-quais-sao-os-deputados-mais-e-menos-governistas/ · gráfico: https://graficos.poder360.com.br/rTWir/ [S]

#### Deputômetro (Agência Lupa) — https://www.agencialupa.org/deputometro/
- **Status:** lançado em 03/08/2026; dados de 01/01/2023 a 25/07/2026; atualização mensal prometida. [S] https://www.agencialupa.org/noticias/2026/08/03/deputometro-lupa-lanca-ferramenta-para-monitorar-atividade-legislativa/ · https://www.agencialupa.org/institucional/2026/07/31/deputometro-conheca-o-painel-de-monitoramento-da-lupa/
- **Oferta:** painéis Flourish por deputado: cota parlamentar, emendas, projetos e votos em plenário. Exclui votações em apreciação conclusiva e votações intermediárias (emendas, destaques). Cada proposição linka para a Câmara. [V] https://www.agencialupa.org/painel-deputometro/2026/07/31/deputometro-consulte-os-projetos-de-lei-e-votos-em-plenario-de-cada-deputado-federal/
- **Tom:** neutro e explicativo, sem nota. [V]
- **UX:** WordPress + Flourish, GTM, cerca de 298 KB. [V] `curl` · Sem export e sem Senado. [V]
- **Fraqueza:** só a 57ª legislatura, atualização mensal e painéis embutidos sem URL própria por votação. [?] sobre a URL

#### Núcleo — Legislatech
- Monitor por palavra-chave (Congresso + DOU) com e-mail diário ou semanal e resumos via ChatGPT; para assinantes a partir de R$ 28/mês. Lançado em 19/07/2023. [S] https://nucleo.jor.br/institucional/2023-07-19-nucleo-lanca-ferramenta-de-monitoramento-legislativo/ · Status atual: [?]

#### JOTA PRO Poder
- Inteligência política paga para empresas: alertas por WhatsApp, previsões de votação com machine learning e relatórios. Não é produto para o cidadão. [S] https://portal.jota.info/produtos/poder

### C. Quizzes e "match"

#### Meu Congresso — https://www.meucongresso.com.br
- **Oferta:** a pessoa marca sim ou não em votações reais e vê quais deputados e senadores coincidem com ela; gráficos de alinhamento, cenários salvos no navegador e exportação. Sem conta e sem cookies; projeto individual de código aberto, apoiado via Apoia.se. [V] https://www.meucongresso.com.br/
- **UX:** Astro, cerca de 33 KB, o site mais leve da amostra. [V] `curl`

#### TemMeuVoto (Coalizão pelo Voto Consciente) — https://temmeuvoto.org.br
- **Status:** suspenso em 30/09/2026, 14 dias após o lançamento (16/09). A nota oficial diz que "a adesão das candidaturas ficou abaixo do necessário". O site dependia de questionário respondido pelos candidatos. A coalizão tem 14 organizações (RenovaBR, CLP, Comunitas, Ethos, Livres, TI Brasil e outras); a edição de 2018 teve 1,3 milhão de usuários. [V] https://temmeuvoto.org.br/ · [S] https://www.poder360.com.br/poder-eleicoes-2026/organizacao-lanca-ferramenta-que-compara-eleitores-e-candidatos-ao-legislativo/
- **Lição:** produto que depende da cooperação do político quebra. Dado oficial de voto não depende de adesão.

#### Politicando (agosto de 2026)
- "Tinder eleitoral": 16 afirmações, 8 eixos e 1.051 candidatos; usa IA para cruzar as respostas com "discursos, votações e propostas". Responsáveis não identificados na matéria. [S] https://diarioesp.com.br/sao-paulo/2026/09/03/ferramenta-gratuita-usa-inteligencia-artificial-para-aproximar-eleitores-e-candidatos-nas-eleicoes-de-2026.html · https://www.metropoles.com/sao-paulo/plataforma-match-candidatos

#### Mortos ou com domínio reaproveitado
- **Quem me representa?** (UFCG, Hackfest): qmrepresenta.com.br hoje serve um portal genérico de notícias ("QM Representa | Notícias de todo do Brasil!"). [V] `curl` · origem: [S] https://exame.com/brasil/quem-me-representa-mostra-deputados-que-votam-como-voce/
- **Vote na Web** (Webcitizen): votenaweb.com.br virou agregador WordPress sem autoria, com último post em 17/03/2025. [V] https://www.votenaweb.com.br/ · origem: [S] https://democraciadigital.fgv.br/iniciativas/vote-na-web
- **Meu Congresso Nacional** (pesquisadores de computação, 2013–2020): o certificado do domínio hoje pertence a "cidadaorecifense.com". [V] erro de TLS em http://meucongressonacional.com/ · [S] http://www.dados.gov.br/aplicativo/meu-congresso-nacional

### D. Portais e apps oficiais

#### Câmara dos Deputados — https://www.camara.leg.br
- **Perfil do deputado:** propostas de autoria e relatadas, votações nominais em plenário, discursos, "Presença em Plenário" e "Presença em Comissões" com "Ausências justificadas" e "Ausências não justificadas", além de cota parlamentar, verba e pessoal de gabinete, salário, imóvel funcional, auxílio-moradia, viagens e passaporte diplomático; dados desde 2003. [V] https://www.camara.leg.br/deputados/204554 (o fetch devolveu um perfil com seletor de 2019 a 2023, provavelmente um ex-deputado)
- **App da Câmara:** acompanha deputados, proposições e temas, com notificações "100% personalizadas" mesmo com o app fechado, enquetes e o canal "Comprove". O release está em `releases/24-03-26` (padrão dd-mm-aa, ou seja, 24/03/2026). [S] https://www2.camara.leg.br/comunicacao/assessoria-de-imprensa/releases/24-03-26-camara-lanca-aplicativo-que-facilita-o-acesso-a-informacoes-legislativas-pelo-celular · https://www.camara.leg.br/assessoria-de-imprensa/1257036-camara-lanca-aplicativo-para-cidadao-acompanhar-a-atividade-legislativa/ · O fetch leu a data como 2024 numa fonte e 2026 na outra. [?]
- **Infoleg:** app antigo com pauta, quórum, resultado de votações e notificações de sessões e comissões; também há uma versão Infoleg Orçamento. [S] https://www.camara.leg.br/noticias/483158-camara-lanca-o-infoleg-aplicativo-gratuito-para-celular-e-tablet-com-informacoes-legislativas/
- **IA (Ulysses):** nova fase anunciada em 11/12/2025, com chat interno para servidores, triagem de proposições e conflitos constitucionais. "Sumarização automática de proposições" aparece como ação em desenvolvimento; não achei resumo por IA público no portal. [S] https://www.mobiletime.com.br/noticias/15/12/2025/ulysses-camara-nova-fase/ · [?] sobre exposição pública
- **Dados abertos:** API e arquivos em lote; são a matéria-prima de todos os concorrentes. [S] https://www2.camara.leg.br/transparencia/dados-abertos/dados-abertos-legislativo

#### Senado Federal — https://www25.senado.leg.br
- **Lista de senadores** exportável em HTML, PDF, JSON e CSV. [V] https://www25.senado.leg.br/web/senadores/em-exercicio
- **Perfil do senador:** dados biográficos, mandatos, comissões, frentes e missões; o fetch não trouxe votos nem presença na página principal do perfil. [V] https://www25.senado.leg.br/web/senadores/senador/-/perfil/5012 · Abas internas de votação: [?]
- **Alertas:** acompanhamento de matérias por e-mail, com resumo diário à noite e cadastro com senha. O alerta é por matéria, não por senador. [S] https://www25.senado.leg.br/web/atividade/instrucoes
- **Outros:** Senado Verifica (checagem, em parceria com o TSE em 2026) e e-Cidadania. [S] https://www12.senado.leg.br/radio/1/na-integra/2026/01/22/senado-verifica-orienta-eleitores-contra-fake-news-em-ano-eleitoral

#### TSE — DivulgaCandContas — https://divulgacandcontas.tse.jus.br
- Candidaturas de 2026: situação do registro, bens, certidões e contas de campanha, atualizados a cada 60 minutos, sem cadastro. Bloqueou meu `curl` (403). [S] https://www.tre-pr.jus.br/comunicacao/noticias/2026/Agosto/divulgacandcontas-registra-informacoes-sobre-candidatos-as-eleicoes-2026

### E. Gastos e controle social

- **Operação Serenata de Amor / Jarbas / Rosie (OKBR):** a landing está no ar [V] https://serenata.ai/, mas jarbas.serenata.ai devolveu erro 522 e depois 000 [V]. O README diz que "não recebe atualizações frequentes" e recomenda o Querido Diário [V] https://github.com/okfn-brasil/serenata-de-amor. Lição: projeto voluntário famoso perdeu fôlego sem orçamento de manutenção.
- **De Olho no Congresso** (Carlito Neto, YouTuber do canal "O Historiador"), 14/07/2025: app de gastos da cota com notas fiscais. [S] https://www.taisparanhos.com.br/2025/07/transparencia-como-ferramenta-de.html
- **Gasto Brasil** (CACB/ACSP): painel de dados públicos econômicos. [S] https://www.otempo.com.br/opiniao/2026/8/21/gasto-brasil-e-congresso-nacional-juntos-pela-melhoria-do-ambiente-economico

### F. Organizações de transparência e educação (não são concorrentes diretos de produto)

- **Transparência Brasil:** o Excelências, pioneiro em parlamento aberto e citado como inspiração da Ficha Limpa, está listado como projeto anterior. Os projetos ativos hoje são Emendas Parlamentares, DadosJusBr e Medicamentos [V] https://www.transparencia.org.br/projetos/. O domínio excelencias.org.br virou blog de "curiosidades e estilo de vida" [V] https://www.excelencias.org.br/ (assumo que era o domínio original do projeto [?]).
- **Politize!:** educação política apartidária, sem ferramenta de dados parlamentares. [S] resumo de busca sobre https://www.politize.com.br/ · [V] o site está no ar
- **Legisla Brasil:** formação e consultoria para mandatos e partidos, sem ferramenta pública de monitoramento. [V] https://legislabrasil.org/
- **Fiquem Sabendo:** agência de LAI com a ferramenta Agenda Transparente (agendas do Executivo). [S] resumo de busca; não verifiquei a URL
- **Inesc:** análise de orçamento e emendas (orçamento secreto), sem perfil por parlamentar. [S] https://inesc.org.br/orcamento-secreto-e-controlado-por-pequeno-grupo-de-partidos-parlamentares-e-pessoas-externas/
- **Instituto Update** e **Livres:** não achei ferramenta própria de monitoramento parlamentar; o Livres participa da coalizão do TemMeuVoto. [?] busca sem resultado · [S] Poder360 acima
- **Movimento Agora!:** não achei ferramenta de dados. [?]
- **Congresso em Números** (FGV Direito Rio): pesquisa e livro de 2017, não ferramenta viva. [S] https://editora.fgv.br/produto/congresso-em-numeros-2017-a-producao-legislativa-do-brasil-3410
- **Parlametria** (OKBR, Dado Capital, UFCG, 19/12/2019): perfil parlamentar e Leg.go com ML; o domínio não responde. [V] `curl` 000 · [S] https://ok.org.br/noticia/okbr-e-parceiros-lancam-parlametria-ferramenta-para-acompanhar-debates-do-congresso/

### G. Projetos independentes de código aberto (2025–2026)

O padrão é claro: em 2026 muita gente faz a mesma ideia com as mesmas APIs, quase sempre com 0 estrelas, um mantenedor só e duração de um ciclo eleitoral.
- **Parlamentômetro** (https://www.parlamentometro.com.br): Câmara e Senado com deputados, proposições por tema, votações nominais, partidos e guias ("Quem cuida do quê?"); GA4 com consentimento. [V] `curl`
- **Radar Político** (https://www.radarpoliticobr.com): 513 deputados, 1.284 votações, "Rastrear" proposição, tom neutro. [V] · Outro projeto com o mesmo nome em vanilla JS: https://github.com/ewertonlim/radarpolitico [V]
- **Pulso Público** (https://github.com/italojs/pulso-publico): "Projetos de lei sem juridiquês", resumos opcionais por IA rotulados, perfis com votos e catálogo de candidaturas; Next.js e Postgres; ainda não lançado. [V]
- **Vote Melhor** (https://github.com/10xdev-startup/vote-melhor): busca em linguagem natural, resumos e "similaridade de votos" por IA, APIs abertas; sem URL pública. [V]
- **Politicada** (https://github.com/wesleymma/politicada): deputados, plenário ao vivo, candidaturas, pesquisas registradas e apuração; 1 commit. [V]
- **Política Aberta:** "demonstrador cívico" com fonte em cada dado e cobertura inicial limitada. [S] resumo de busca; não achei a URL

---

## 2. Tabela comparativa

| Ferramenta | Status (30/09/2026) | Cobertura | Métrica central | Tom | Contas/alertas | Export/API | IA | Modelo |
|---|---|---|---|---|---|---|---|---|
| **Mandato Aberto (MVP)** | Ativo, diário [V] | Câmara 57ª | Participação, orientação do Governo, maioria do partido, autoria ("n de m") | Descritivo | Não | Não (código aberto) | Não | Independente |
| Ranking dos Políticos | Ativo, atualizado em 27/07/2026 [V] | Câmara + Senado | Nota 0–10, ranking e prêmio | Normativo, liberal declarado | Login (Meu Ranking, Comparar), newsletter | Não [V] | Não [V] | Doações sem lista pública [S] |
| Placar Político | Ativo, semanal [V] | Câmara | Nota 1–99, ranking, recordes | "Não finge neutralidade" | Newsletter (até 2 deputados) | Não [?] | Não [V] | PIX/Apoia.se [V] |
| Tô De Olho | Ativo desde 09/2026 [V] | Senado | Ranking ponderado | Métrico | Não [?] | Código aberto | Não [?] | TCC/individual |
| Fiscalize o Poder | Ativo desde 08/2026 [V] | Câmara + Senado | Índice Fiscalize | Fiscalizador | Não exige cadastro [V] | [?] | Chat em linguagem natural [V] | Apoia.se |
| Elas no Congresso | Ativo; análise até 06/2025 [V] | Câmara + Senado | Ranking favorável/desfavorável | Advocacy | Newsletter | GitHub [V] | QuitérIA (classificação) [S] | ONG (AzMina) |
| OLB/IESP | Ativo [V] | Câmara | Índice −10 a +10 por tema | Acadêmico com valência | Não | [?] | Não [V] | Universidade |
| DIAP Quem é Quem | Ativo desde 09/2026 [V] | Câmara + Senado | Votações curadas (ODS) | Curadoria sindical | Não [?] | [?] | Não [?] | Entidade sindical |
| Radar do Congresso | Ativo; presença só até 2025 [V] | Câmara + Senado | Governismo %, presença, gastos, inquéritos | Jornalístico ("base/oposição") | Insider pago com alertas [S] | Não [V] | Não [V] | Google NI + assinatura [S] |
| Basômetro | Congelado em 2022 [V] | Câmara (2003–2022) | Governismo | Jornalístico | Não | Código AGPL [V] | Não | Paywall do Estadão [V] |
| Poder360 | Reportagens pontuais [V] | Câmara | Governismo | Jornalístico | Não | Não | Não | Mídia |
| Deputômetro (Lupa) | Ativo desde 08/2026, mensal [S] | Câmara 57ª | Painéis de gastos, emendas e votos | Neutro | Não | Não [V] | Não [S] | Mídia/checagem |
| Meu Congresso | Ativo [V] | Câmara + Senado | Alinhamento com o usuário | Educativo | Sem conta | Export de cenários [V] | Não | Apoia.se |
| TemMeuVoto | **Suspenso em 30/09/2026** [V] | Candidaturas | Match por questionário | Cívico | — | — | — | Coalizão de 14 ONGs |
| Politicando | Ativo 2026 [S] | Candidaturas | Match 0–100 | Cívico | [?] | [?] | Sim, para o match [S] | [?] |
| Câmara (portal + app) | Ativo [V/S] | Câmara desde 2003 | Presença (dias), "ausências não justificadas" | Institucional | **App com seguir deputado e push** [S] | API e lotes | Ulysses interno [S] | Público |
| Senado (portal) | Ativo [V] | Senado | Perfil biográfico | Institucional | E-mail por matéria [S] | CSV/JSON [V] | Não [?] | Público |
| DivulgaCandContas | Ativo [S] | Candidaturas | Registro, bens, contas | Institucional | Não | Dados abertos TSE | Não | Público |
| Serenata/Jarbas | Landing ativa; Jarbas fora do ar [V] | Gastos (CEAP) | Gasto suspeito | Fiscalizador | Não | Código aberto | ML (Rosie) | Doações (OKBR) |
| Excelências, Quem me representa, Vote na Web, Meu Congresso Nacional, Atlas Político, Parlametria | **Mortos**; vários domínios reaproveitados [V] | — | — | — | — | — | — | — |

---

## 3. Lacunas que ninguém cobre bem, por valor para o usuário

1. **Registro de votos sem nota e com denominador explícito.** Quem interpreta o dado dá nota (Ranking dos Políticos, Placar, Tô De Olho, Fiscalize, OLB, Elas, DIAP); quem não dá nota entrega tabela crua (Deputômetro, portais oficiais). As métricas de governismo de Radar, Poder360 e Basômetro tratam ausência e obstrução como "não seguiu", o que mistura duas perguntas diferentes, e mostram porcentagem sem o "n de m". [V] capturas do Radar · [V] Poder360 · [V] Basômetro. Ninguém publica um número que uma pessoa possa refazer à mão com o arquivo oficial e link por votação.
2. **Explicação do que estava em jogo em cada votação nominal.** A Câmara ainda não publica resumo por IA [S/?]. A QuitérIA só classifica, e só gênero [S]. O Legislatech resume proposições, é pago e não trata a votação [S]. Pulso Público e Vote Melhor não estão no ar [V]. Ninguém tem uma página por votação nominal com resumo em linguagem simples revisado por humano, orientação de cada bancada, resultado e voto de cada pessoa, numa URL compartilhável.
3. **Executivo ligado ao Congresso.** Nenhuma ferramenta da amostra mostra medidas provisórias, vetos e projetos do Executivo com o destino de cada um e quem votou como na sessão do Congresso que manteve ou derrubou o veto. O governismo mede a orientação do líder do Governo, não o que aconteceu com os atos do Executivo. [?] ausência constatada nas fichas acima
4. **Continuidade entre legislaturas.** Quase tudo está preso à 57ª legislatura ou ao ciclo eleitoral: o Deputômetro cobre "2023–2026", o Basômetro parou em 2022, o TemMeuVoto foi suspenso, a presença do Radar vai até 2025 e há seis projetos mortos. [V/S] Quem existir com dado limpo desde 01/02/2027, com histórico dos reeleitos, fica sozinho nos primeiros meses da 58ª.
5. **Dado aberto reutilizável com proveniência.** Só o Senado (lista de senadores) e o Meu Congresso (cenários) exportam; nenhum concorrente de perfil oferece CSV/JSON por perfil ou votação com data de coleta e fonte, nem API pública documentada. [V] Jornalistas e pesquisadores hoje montam a própria planilha (Lupa usa Python, Power Query e Flourish). [V]
6. **"Meus eleitos" nas duas Casas, sem app e sem nota.** O app da Câmara segue deputados por push, mas só na Câmara e exige instalação [S]. O Senado alerta por matéria, não por senador [S]. O Placar manda newsletter de até 2 deputados com nota [V]. O Insider é pago [S]. Falta um aviso por e-mail do tipo "seu deputado e seus senadores votaram assim na votação X, e isto é o que estava em jogo", cobrindo Câmara e Senado.
7. **Errata pública e contestação.** Nenhum concorrente da amostra mostra registro público de correções com histórico de versão [?] (não achei nenhum). O Mandato Aberto já tem página de correções [V]. Num campo em que o parlamentar contesta, isso é confiança acumulada.
8. **Link compartilhado com prévia real.** O Radar é SPA e gera prévia genérica [V]; os painéis Flourish da Lupa não têm URL por item [?]. Card com o voto de uma pessoa numa votação, com fonte, quase ninguém faz.
9. **Tabelas densas legíveis no celular.** A tabela de presença do Radar transborda a cerca de 790 px [V]. O mobile dos demais: [?].

---

## 4. Estratégia de diferenciação: movimentos concretos

Cada movimento cita a lacuna que ataca e o dado oficial que o sustenta.

1. **"Registro, não nota." Toda métrica como "n de m" com o denominador abrível** (lacuna 1). Exemplo: "votou igual à orientação do Governo em 412 de 530 votações em que votou; em 61 não registrou voto", com cada parcela clicável até a lista de votações e cada votação linkando para a página oficial. Separar presença de alinhamento, ao contrário de Radar, Poder360 e Basômetro. Não usar a palavra "governismo" nem os rótulos "base/oposição". Metodologia versionada com data. *Dado:* votações, votos e orientações das APIs da Câmara e do Senado, que o ETL já processa.
2. **A votação nominal como página principal, não só o deputado** (lacunas 2 e 8). Cada votação tem URL própria com SSR e meta tags; resumo "o que estava em jogo" gerado a partir do texto oficial, rotulado como IA e revisado por humano (modelo e hash guardados); orientação de cada bancada; resultado; voto de cada parlamentar filtrável por UF e partido; card compartilhável "como votou a bancada do seu estado". *Dado:* inteiro teor e ementa da proposição, objeto da votação, orientações e votos; a IA resume texto, nunca pessoa (decisão 11 da v2).
3. **"Do Planalto ao plenário": perfil da Presidência ligado aos votos** (lacuna 3). Para cada MP, a tramitação até conversão, rejeição ou caducidade; para cada veto, a sessão do Congresso e o voto de cada parlamentar na manutenção ou derrubada; para cada projeto do Executivo, o destino. Nenhum concorrente da amostra faz isso. *Dado:* APIs da Câmara e do Congresso Nacional (decisão 8 da v2). Se não couber, o plano é março de 2027 (decisão 9).
4. **"Desde o primeiro dia da 58ª" como posicionamento de lançamento** (lacuna 4). Legislatura como entidade de primeira classe; em 01/02/2027, perfis dos 513 deputados e 81 senadores, com a 57ª arquivada e consultável para os reeleitos ("na legislatura anterior: n de m"). Enquanto os concorrentes de ciclo eleitoral param ou ficam desatualizados, o Mandato Aberto é o lugar que continua. *Dado:* mesmas APIs, com o parâmetro de legislatura.
5. **Dado aberto de verdade, com a imprensa como canal de distribuição** (lacuna 5). CSV e JSON por perfil, por votação e em lote, com `schema_version`, data de coleta, fonte e licença; API pública somente leitura documentada; página "para jornalistas e pesquisadores" com o dicionário de dados. É o que Lupa, Poder360 e Radar refazem à mão a cada pauta, e cada matéria que usar o dado com crédito leva tráfego para o site. *Dado:* o JSON do ETL já existe (AD-002).
6. **"Meus eleitos" por e-mail, nas duas Casas e sem nota** (lacuna 6). A pessoa escolhe UF, deputados e senadores e recebe um e-mail por votação nominal relevante ou um resumo semanal: voto de cada um, orientação do partido e do Governo e o resumo revisado da votação, com link para a fonte. Sem app, sem rastreio individual e sem "nota da semana", ao contrário do Placar. *Dado:* votos diários das duas Casas; filas do Laravel (entrega 2 da v2).
7. **Errata pública e canal formal para gabinetes** (lacuna 7). Página de correções com diff e data, formulário específico para assessorias parlamentares com prazo de resposta publicado e changelog de metodologia. Transforma a contestação, que vai acontecer, em prova de rigor. *Dado:* histórico versionado já previsto (AD-008, Filament para triagem).
8. **Comparador sem régua de valor** (lacunas 1 e 6, entrega 3 da v2). Duas ou mais pessoas lado a lado em votações específicas ("onde votaram diferente"), sem total, sem vencedor e sem ordenação por desempenho. Responde "como meus dois senadores votaram nas mesmas coisas" sem virar o "mesma régua" do Placar ou o "Comparar" do Ranking. *Dado:* votos das duas Casas.

---

## 5. O que os concorrentes fazem bem e precisamos igualar (table stakes)

| Item | Quem faz bem | Situação do Mandato Aberto |
|---|---|---|
| Busca por nome, UF e partido, com foto oficial | Todos; Radar, Ranking e Placar [V] | MVP tem [V] |
| Câmara + Senado | Ranking, Radar, Fiscalize, DIAP, Meu Congresso [V] | Planejado na v2 (decisão 6) |
| Data de atualização visível | Radar ("base atualizada em"), Ranking ("Última atualização") [V] | MVP tem [V] |
| Página de metodologia | Ranking, Placar, Tô De Olho, OLB [V] | MVP tem; manter versionada |
| Link de cada proposição para a fonte oficial | Deputômetro [V] | MVP tem; estender a cada votação |
| Visão por partido e por bancada | Radar (bancadas setoriais), Basômetro (por partido), DIAP [V] | Falta: "como votou o partido X na votação Y" |
| Comparar parlamentares | Placar, Ranking (com login), Tô De Olho [V] | Entrega 3 (movimento 8) |
| Newsletter ou alerta | Placar, Ranking, app da Câmara, Insider [V/S] | Entrega 2 (movimento 6) |
| Candidaturas vindas do TSE | Placar, Quem Votar, Radar ("Eleição") [V] | MVP tem para 2026 [V] |
| Tema claro e escuro | Placar (escuro por padrão), Tô De Olho [V] | Definir no sistema de design |
| Página leve e rápida | Meu Congresso (33 KB), Placar (40 KB) [V] | Home do MVP tem 311 KB [V]; paginar ou carregar a lista sob demanda |
| Gastos (cota parlamentar) | Radar, Deputômetro, Placar, Tô De Olho, Fiscalize, Serenata, De Olho no Congresso, Câmara [V/S] | **Fora do escopo.** Mitigação sugerida: um bloco "Gastos" que só linka para a página oficial do parlamentar, sem número próprio; precisa de decisão registrada |
| Presença no vocabulário oficial | Câmara e Radar ("ausências justificadas/não justificadas") [V] | O MVP mede participação em votações nominais, que é outra coisa; explicar a diferença num quadro "por que este número difere do da Câmara" |

---

## 6. Riscos: onde competir parece cópia e onde os erros alheios nos alertam

**Parecer cópia**
- **Comparador e newsletter parecidos com os do Placar Político.** O Placar já tem quiz, comparador, newsletter de 2 deputados e candidaturas. [V] O que nos separa precisa aparecer na interface: sem nota, sem "recordes", sem quiz de afinidade, e cobertura das duas Casas e da Presidência.
- **"Meus eleitos" parecido com o app da Câmara.** A Câmara já tem "seguir deputado" com push. [S] A diferença precisa ser explícita: Câmara + Senado, e-mail sem app e resumo da votação.
- **Métrica de "governismo" igual à do Radar e do Basômetro.** Os números vão divergir, porque eles contam ausência como "não seguiu". Isso vai gerar "o Mandato Aberto diz 87%, o Radar diz 70%". Publicar uma nota metodológica neutra sobre a diferença de denominador, sem citar concorrente de forma pejorativa.
- **Nome.** "Política Aberta", "Parlamento Aberto" e "Mandato Aberto" são parecidos; checar marca no INPI e o nome da futura associação. [?]

**Erros alheios como alerta**
- **Ranking com valência ideológica vira alvo.** As críticas da FGV, do Intercept e do DIAP ao Ranking dos Políticos, e a carreira política do cofundador, mostram que um ranking vira munição partidária e a organização vira pauta. [S] A regra anti-ranking e a governança da associação precisam ser públicas: quem financia, conselho e conflitos de interesse.
- **Curadoria de votações "relevantes" é editorial.** Ranking (conselho com 70% de consenso), DIAP (ODS), Elas (19 ONGs) e OLB (favorável ou contrário) escolhem as votações. [V] Se o Mandato Aberto destacar "votações importantes", o critério precisa ser mecânico e publicado (por exemplo, todas as votações de mérito em plenário, ou as de PEC), nunca uma escolha de valor.
- **Chat de IA e "match" em ano eleitoral.** Fiscalize (chat) e Politicando (match por IA) estão expostos à Res. TSE 23.610 e a erro atribuído a pessoa. [V/S] Manter IA só em resumo de texto oficial, revisado e rotulado, como já decidido.
- **Dependência da cooperação do político.** O TemMeuVoto foi suspenso em duas semanas por baixa adesão das candidaturas. [V] Não construir nada que dependa de o parlamentar responder.
- **Morte por abandono.** Excelências, Quem me representa, Vote na Web, Meu Congresso Nacional, Atlas Político, Parlametria, Jarbas e Basômetro morreram ou pararam; vários domínios foram reaproveitados por sites de baixa qualidade. [V] Orçamentar a manutenção, renovar o domínio por vários anos, prever arquivamento estático se o projeto parar e não depender de verba de um ciclo só, como o Google NI no Radar.
- **Ferramenta de redação congela quando o editorial perde interesse.** Basômetro parado em 2022 [V]; Poder360 só em reportagem pontual [V]. É um argumento a favor de ser infraestrutura e não pauta.
- **SPA sem SSR mata o compartilhamento.** O HTML do Radar não tem título por perfil [V]. O Inertia com SSR já está decidido; testar as meta tags por perfil e por votação no CI.
- **Selo de reCAPTCHA e tabela que transborda no celular** (Radar) [V]: incluir nos critérios de aceite do sistema de design.
- **Vocabulário.** "Ausências não justificadas" é o termo oficial da Câmara [V], e reproduzi-lo fora de contexto soa como acusação. Manter "não registrou voto" e explicar a fonte.

---

## Fontes adicionais consultadas
- https://www.congressoemfoco.com.br/noticia/16068/radar-do-congresso-mais-87-mil-horas-de-navegacao [S]
- https://azmina.com.br/reportagens/azmina-lanca-dia-15-elas-no-congresso-plataforma-de-monitoramento-de-atuacao-parlamentar/ [S]
- https://github.com/institutoazmina/elasnocongressobot [S]
- https://www.observatoriodaimprensa.com.br/codesinfo/azmina-lanca-nova-versao-da-quiteria-ferramenta-de-ia-que-monitora-projetos-de-lei-sobre-direitos-de-mulheres-e-populacao-lgbtqiapn/ [S]
- https://www.camara.leg.br/assessoria-de-imprensa/822946-infoleg-orcamento-fornece-informacoes-sobre-execucao-de-emendas-parlamentares-e-acoes-orcamentarias/ [S]
- https://www12.senado.leg.br/noticias/materias/2025/12/04/senado-cria-ferramenta-para-acompanhar-emendas-ao-orcamento [S]
- https://github.com/caimanoliveira/match-eleitoral-2026 [S]
- https://fabiovasconcellos.github.io/matcheleitoral/ [S]
