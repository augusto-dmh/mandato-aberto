# Referências de design para o Mandato Aberto v2

**Data:** 30/09/2026 · **Para:** a feature de sistema de design da v2 (decisão 10 de `research/05-grilling-escopo-v2.md`) · **Entrada:** `AGENTS.md`, `research/02-grilling-escopo-mvp.md` (decisão 10), `research/05-grilling-escopo-v2.md`, CSS atual em `site/src/styles/` · **Método:** leitura de fontes primárias (HIG da Apple via JSON oficial, CSS de produção do apple.com e do ourworldindata.org, metadados do Google Fonts e repositório `google/fonts`, posts de engenharia e newsroom da Spotify, blogs da Linear, Stripe, The Pudding, mySociety e Estadão), complementada por fontes secundárias quando a primária estava inacessível.

**Legenda de certeza** (aparece entre colchetes ao fim de cada afirmação factual relevante):

- **[P]** verificado em fonte primária, lida nesta pesquisa.
- **[S]** fonte secundária (imprensa, blog de terceiros); plausível, não conferido na origem.
- **[I]** inferência ou opinião de design minha; precisa de validação no artifact da feature.
- **[NV]** não consegui verificar (página bloqueada, paywall, 403); tratar como hipótese.

---

## 0. Resumo em dez linhas

1. O que dá a sensação "Apple" não é o brilho: é uma escala tipográfica curta e rígida (13 tamanhos no apple.com, de 12 a 80 px), tracking negativo nos tamanhos grandes, duas cores de texto e uma de link, e **toda afirmação numérica amarrada a uma nota de rodapé**. Isso é transferível quase literalmente [P].
2. O que dá a sensação "Spotify" é um sistema de tokens (Encore) e uma tipografia variável com eixo de largura (Spotify Mix). O Wrapped é a parte **menos** transferível: foi desenhado em cima de surpresa, identidade e rótulos sobre a pessoa ("listening age"), que é exatamente o que o projeto não pode fazer [P/S].
3. O padrão-ouro de "número + fonte logo abaixo" já existe e é do Our World in Data: título, subtítulo, gráfico, rodapé com fonte e licença, abas Gráfico/Tabela/Fontes/Download, e prévia social gerada a partir da configuração [P].
4. A foto oficial da Câmara chega em **354 × 472 px** (3:4), e algumas em 114 × 152 px [P, no cache do MVP]. O herói "Apple" com foto gigante é impossível sem ampliar a foto, e ampliar com IA altera a foto. **O herói precisa ser tipográfico; a foto entra pequena, num passe-partout fixo** [I].
5. Cor de voto não pode ser verde/vermelho (julgamento implícito e daltonismo). Codificar Sim/Não/Abstenção/Obstrução/Ausência por **posição e forma**, com cor apenas de reforço, segue a HIG ("não dependa só de cor") e o NPR (vermelho/azul reservados a partido) [P/S].
6. O sinal de assinatura mais forte que encontrei para o produto é uma **"partitura do mandato"**: uma coluna por votação nominal, Sim acima da linha, Não abaixo, ausência como lacuna; cada coluna é um link para a votação oficial. É descritiva, legível sem cor e não ordena ninguém [I].
7. Três direções candidatas: **Diário** (serifa editorial + sans, papel quente), **Instrumento** (Inter Display/Geist, neutro tipo Apple, modo escuro de primeira classe) e **Plenário** (Archivo variável de largura 62–125, cartaz, mais próximo do Spotify Mix). Todas com fontes OFL verificadas no `google/fonts` [P].
8. Armadilhas principais: contador animado de números, índice sintético único por pessoa, cor por parlamentar no card, "seu ano em" com tom de celebração, rótulos de persona, ponteiros nervosos (a "agulha" do NYT), duotone ou recorte na foto, e parecer site oficial do gov.br.
9. O "uau, vou usar isso" para jornalista e cidadão é utilitário: buscar "onde está meu deputado" no plenário de 513 pontos, citar uma votação com um clique, baixar CSV no mesmo lugar, e um card cujo código leva ao retrato exato dos dados naquela data.
10. Ponto de atenção sobre o MVP: o acento atual é `#b3261e` (vermelho). Vermelho lê como alerta e tem associação partidária forte no Brasil; vale reconsiderar na feature de design [I].

---

## 1. Restrições que filtram as referências

| Restrição | Origem | Efeito no design |
|---|---|---|
| Linguagem descritiva; sem adjetivo sobre parlamentar; sem "faltou"; sem vocabulário de pesquisa eleitoral | `AGENTS.md` | Títulos de gráfico afirmam fato verificável, nunca interpretação. Nada de "destaque", "top", "melhor", "pior" |
| Todo número com metodologia pública e link para a fonte oficial | `AGENTS.md` | Cada número precisa de um alvo clicável e de um lugar para a fonte, inclusive no card de imagem |
| "n de m", nunca ranking | briefing da v2 | Proibido ordenar pessoas por indicador como vista padrão; distribuição sem nomes é o máximo aceitável [I] |
| Foto oficial nunca alterada | briefing | Sem duotone, recorte de fundo, filtro, upscaling por IA, máscara criativa, escurecimento em modo escuro |
| Foto da Câmara: 354 × 472 px; algumas 114 × 152 px | `site/.cache/photos/` [P] | Em tela 2x, a foto ocupa no máximo ~177 × 236 px CSS sem perder nitidez [I] |
| Resumo por IA só de proposições e votações, rotulado e revisado | decisão 11 da v2 | O bloco de IA precisa de rótulo visual próprio e link para o inteiro teor |
| Sem cookie, sem identificador individual | premissas da v2 | Nada de "seu Wrapped" baseado em comportamento do visitante |
| Tokens atuais do MVP: papel `#f7f5f0`, tinta `#1a1a1a`, acento `#b3261e`, Source Serif 4 + Inter, corpo 17 px | `site/src/styles/` [P] | Ponto de partida; a direção "Diário" abaixo é a evolução natural |

---

## 2. Referências e técnicas transferíveis

### 2.1 Apple: apple.com, página de meio ambiente e Human Interface Guidelines

**Escala tipográfica real do apple.com.** Extraí do CSS de produção da página do iPhone (`/v/iphone/home/ck/built/styles/overview.built.css`) as combinações tamanho / entrelinha / peso / tracking mais usadas [P]:

| Papel | Tamanho | Entrelinha | Peso | Tracking |
|---|---|---|---|---|
| Display máximo | 80 px | 1,05 | 600 | −0,015 em |
| Display | 64 px | 1,0625 | 600 | −0,009 em |
| Display menor | 56 px | 1,0714 | 600 | −0,005 em |
| Título de seção | 48 px | 1,083 | 600 | −0,003 em |
| Título | 40 / 32 px | 1,0–1,125 | 600 | 0 / +0,004 em |
| Subtítulo | 28 / 24 / 21 px | 1,14–1,19 | 600 | +0,007 a +0,011 em |
| Corpo | 17 px | 1,47 | 400 | −0,022 em |
| Pequeno | 14 px | 1,43 | 400 | −0,016 em |
| Legenda | 12 px | 1,33 | 400 | −0,01 em |

Técnicas que saem daqui:

- **Poucos tamanhos, muitas vezes.** Os três tamanhos mais usados (21, 28, 24 px, peso 600) aparecem 14–16 vezes cada; o display de 80 px aparece duas vezes. Hierarquia vem de salto grande entre níveis, não de muitos níveis [P].
- **Tracking que depende do tamanho.** Negativo no corpo (−0,022 em a 17 px), levemente positivo nos subtítulos médios e negativo de novo no display. A HIG explica que a fonte do sistema ajusta tracking em cada tamanho e que mockups precisam replicar isso [P]. Para fonte web, o equivalente é usar o eixo `opsz` quando existir (Inter tem `opsz` 14–32, Newsreader 6–72, Source Serif 4 8–60, verificado nos metadados do Google Fonts) [P].
- **Duas larguras de quebra.** O CSS usa `max-width: 1068px` e `max-width: 734px` centenas de vezes: três layouts, não um contínuo [P].
- **Paleta mínima.** As cores mais frequentes são o azul de link `#0071e3`, texto `#1d1d1f`, texto secundário `#6e6e73` e cinzas de fundo próximos a `#f5f5f7` [P].
- **Notas de rodapé como sistema.** A página do iPhone tem 33 elementos `footnote-number`, 5 `footnote-diamond` e 4 `footnote-supglyph` [P]. Na página de meio ambiente o padrão é: número grande, uma frase, numeral de nota ("60% recycled content overall. ²") e a nota no rodapé com o método [P]. **É o mesmo contrato do Mandato Aberto ("número grande com a fonte logo abaixo"), com acabamento melhor.**

**HIG, tipografia** [P]: tamanho padrão de corpo 17 pt, mínimo 11 pt no iOS; na escala Dynamic Type padrão, Large Title 34/41, Title 1 28/34, Title 2 22/28, Title 3 20/25, Headline 17/22 semibold, Body 17/22, Footnote 13/18, Caption 12/16. Recomenda evitar pesos Ultralight/Thin/Light, minimizar o número de famílias e preservar a hierarquia quando o usuário aumenta o texto.

**HIG, gráficos** (páginas *Charting data* e *Charts*) [P]:

- "Not every collection of data needs to be displayed in a chart": se não há o que analisar, use lista ou tabela pesquisável e ordenável.
- Título e subtítulo descritivos que resumem a mensagem antes do gráfico (o exemplo é o Tempo: "Chance of light rain in the next hour").
- Barras com eixo começando em zero; faixa fixa quando mínimo e máximo têm significado (0 a 100%).
- Sequências de marcação familiares (0, 5, 10), poucas linhas de grade, dados mais proeminentes que eixos.
- "Avoid relying solely on color": o app Saúde usa formas diferentes para as duas medidas da pressão arterial; separadores entre segmentos empilhados.
- "Don't require interaction to reveal critical information"; alvo de toque pode ser a área inteira do gráfico para quem tem controle motor reduzido; navegação por teclado ao longo do eixo X.
- Consistência entre gráficos do mesmo dado: mesma cor, mesmas marcas, mesma anotação na versão pequena e na expandida (exemplo: tendências do Saúde).

**HIG, modo escuro** [P]: respeitar a preferência do sistema; contraste mínimo 4,5:1 e 7:1 como meta para texto pequeno em cores próprias; fundo "base" mais escuro e "elevated" mais claro para camadas; cores de modo escuro não são inversão simples. A HIG sugere escurecer levemente imagens com fundo branco no modo escuro. **No Mandato Aberto isso conflita com "foto nunca alterada"**: a adaptação segura é pôr a foto num passe-partout claro fixo, sem tocar no arquivo [I].

**HIG, movimento e acessibilidade** [P]: movimento com propósito, opcional, cancelável, breve; não fazer ninguém esperar animação terminar; com "Reduzir movimento", trocar deslocamentos por fades e reduzir zoom e escala. O CSS do apple.com tem 5 blocos `prefers-reduced-motion` [P]. Alvo mínimo de toque 44 × 44 pt e cerca de 24 pt de respiro em volta de elementos sem borda [P].

**HIG, cor** [P]: não usar a mesma cor para significados diferentes; considerar leitura cultural da cor ("red communicates danger in some cultures"); usar cor da marca como acento quando o conteúdo é monocromático.

**HIG, Activity rings** [P]: regras que funcionam como modelo para a foto oficial. "Never change the colors of the rings; don't use filters or modify opacity", sempre o mesmo fundo, "design the surrounding interface to blend with the rings; never change the rings to blend with the surrounding interface", e anéis só para uma pessoa, com rótulo de quem é. Troque "anéis" por "foto oficial" e você tem a regra de foto do projeto.

**Resumos de IA da Apple** [S]: em janeiro de 2025 a Apple suspendeu resumos de notificações de apps de notícias depois de manchetes falsas atribuídas à BBC e outros (Axios, Washington Post). É o precedente para o rótulo e a revisão humana dos resumos do projeto.

### 2.2 Spotify: Encore, Spotify Mix e Wrapped

**Encore** [S, Figma blog e Medium da Spotify Design; o Medium retornou 403]: um "sistema de sistemas" em camadas concêntricas, com uma fundação compartilhada de tokens para cor e estilos de texto; subsistemas Mobile e Web; o objetivo declarado é "coesão", não consistência total. Componentes partilham anatomia, nomes e hierarquia (primário, secundário, terciário) entre plataformas. Transferível: no Laravel + Inertia + Vue, os tokens devem nascer num arquivo único (CSS custom properties geradas de um JSON) usado pelo app e pelo gerador de cards [I].

**Spotify Mix** [P newsroom; S para detalhes técnicos]: fonte própria da Dinamo, lançada em 22/05/2024, substituiu a Circular; variável, com grande amplitude de largura e peso, "remix" de geométrica, grotesca e humanista. A newsroom não detalha eixos; os detalhes de largura vêm do It's Nice That e TechCrunch [S]. A lição transferível é **uma família variável com eixo de largura**, que permite números enormes condensados num card estreito e texto normal no corpo, sem trocar de família.

**Wrapped: mecânica de compartilhamento** [P engenharia da Spotify]:

- O foco declarado da equipe web eram os **share cards**: imagens estáticas que resumem as histórias, para Instagram, TikTok e Snapchat, que precisam "accommodate a variety of languages and dynamic data within a fixed space". O card mais complexo tinha cerca de 100 variações visuais em 30 idiomas.
- Animações de dados são nativas e **parametrizadas** ("inject variables such as localized text, images, and listening data to dictate appearance, color, motion"); animações genéricas usam Lottie.
- Em 2025 a paleta foi reduzida a preto e branco com "selective pops of color used only for key moments" [P, Spotify Selects].
- O processo começa em junho para lançar em dezembro, com exploração deliberada de "bad design" [S].

**Wrapped: por que funciona** [S, growth.design]: lacuna de curiosidade, antecipação, recompensa variável, narrativa, "delighters" de animação e prova social com expressão de identidade. Quase todos esses mecanismos são incompatíveis com a neutralidade do projeto (ver seção 4).

**Wrapped 2025, "listening age"** [S, NPR, Today]: um número sintético sobre a pessoa, com aviso "Age is just a number. So don't take this personally", que gerou irritação. É a demonstração mais clara de que um índice derivado que rotula a pessoa vira julgamento, mesmo quando a intenção é lúdica.

**Duotone de 2015** [S, Design Week, Fast Company]: a identidade da Collins recoloria fotos de artistas em duas cores, com uma ferramenta automática ("The Colorizer"). Aplicado a foto de parlamentar, seria alteração da foto oficial.

### 2.3 Financial Times: Visual Vocabulary e Chart Doctor

- **Visual Vocabulary** [P, GitHub do FT]: nove relações (desvio, correlação, ranking, distribuição, mudança no tempo, magnitude, parte-todo, espacial, fluxo), cada uma com tipos de gráfico. Para o projeto, a coluna "ranking" fica fora como vista padrão; "distribuição", "parte-todo", "mudança no tempo" e "fluxo" cobrem quase tudo (presença, votos por opção, tramitação de proposições, MPs e vetos).
- **John Burn-Murdoch** [S, iMEdD Lab]: título narrativo, anotação direta sobre as linhas em vez de legenda, uma série em cor e o resto em cinza ("minimize distraction, maximize contrast"), leitores lembram mais títulos e anotações. A adaptação para o projeto é o **título que afirma um fato contável** ("Votou Sim em 212 de 401 votações nominais"), nunca uma interpretação ("é governista").
- **Cor** [S, Datawrapper]: fundo rosado como assinatura, cores escolhidas para funcionar sobre ele, cinza de contexto ajustado ao fundo, ordem fixa de uso das cores. Papel `#FFF1E5` e teal de ação `#0D7680` são citados por fontes secundárias; o registro do Origami não abriu nesta sessão [NV].
- **Tipografia** [S]: Financier (Klim) para manchete, Metric para o resto. Não são abertas; o equivalente aberto mais próximo do par é Source Serif 4 ou Newsreader com Inter ou Public Sans [I].

### 2.4 New York Times, The Upshot e Washington Post

- **Identidade pela tipografia, não pela paleta** [S, Datawrapper]: o NYT varia cores entre matérias e mantém Cheltenham e Franklin; vermelho e azul só para partido em eleição.
- **A "agulha" de 2016** [S, Fast Company, vis4.net]: tremor animado para comunicar incerteza, chamado de "irresponsible" e "the most stressful thing I've ever looked at online"; o tremor foi desligado em 2017. É o exemplo de movimento que comunica emoção em vez de dado.
- **"You Draw It"** [S]: o leitor desenha a curva antes de ver o dado. Bom para aprendizado; no contexto do projeto vira gamificação de opinião sobre político [I].
- **"How every House member voted"** do Washington Post [NV, 403]: o formato de página por votação com todos os nomes é o equivalente direto da página de votação do projeto, mas não consegui ver o layout.
- O nytimes.com bloqueou a busca automática; nada do NYT aqui foi conferido na origem [NV].

### 2.5 The Pudding

- **"Sledgehammer stat"** [P]: abrir com o achado central e depois explorar os ângulos secundários; começar por pontos individuais concretos antes do abstrato.
- **Três formas de guiar** [P]: gráficos empilhados com texto que aponta o que olhar; scrollytelling (gráfico fixo, texto rolando, troca de estado por gatilho); navegação em passos (toque para avançar).
- **Scrollama** [P]: gráfico "sticky" e passos, com IntersectionObserver; observa a rolagem sem sequestrá-la.
- **Metodologia visível** [P]: seção de métodos para o leitor avaliar a conclusão.

### 2.6 Our World in Data

- **Fontes verificadas no CSS de produção** [P]: Lato para texto e dados (360 declarações), Playfair Display para títulos (93), `font-variant-numeric: tabular-nums` presente.
- **Grapher** [P]: abas Gráfico/Mapa/Tabela; "small multiples" em vez de sobrepor linhas; botão "alinhar escalas dos eixos"; "the source of the data is always displayed prominently" com "Learn more about this data"; download e compartilhamento agrupados no canto inferior direito; prévias sociais dinâmicas que refletem a configuração do gráfico; tabela com contraste entre linhas e colunas ordenáveis.
- Transferível quase inteiro: **todo gráfico do projeto tem aba Tabela e aba Fontes e é exportável como imagem com a fonte embutida** [I].

### 2.7 Bloomberg

- Neue Haas Grotesk e paleta saturada como assinatura [S, Datawrapper]; equipe de gráficos integrada à redação, cerca de 100 gráficos rápidos e 35 aprofundados por mês [S, Digiday]. Não encontrei fonte primária sobre o estilo de anotação [NV]. O que transfere é a **saturação como assinatura sobre fundo neutro**, que conflita com "uma cor de acento"; útil só na direção "Plenário" [I].

### 2.8 Linear e Stripe (acabamento de produto web)

- **Linear** [P]: trocou HSL por LCH para gerar temas; três variáveis por tema (base, acento, contraste) em vez de 98; a variável de contraste gera temas de alto contraste automaticamente; Inter Display nos títulos e Inter no corpo; texto e ícones neutros escurecidos no claro e clareados no escuro; alinhamento milimétrico de rótulos, ícones e botões, "felt after a few minutes" e não visível de imediato; redesenho em seis semanas e cinco marcos.
- **Stripe** [P]: escala de cor em CIELAB; regra estrutural "any two colors are guaranteed to have sufficient contrast for small text if they are at least five levels apart" e quatro níveis para ícones e texto grande; textos padrão passam 4,5:1. Transferível: a escala do projeto em OKLCH com uma regra de distância que dispensa conferência manual de contraste [I].

### 2.9 Referências cívicas e brasileiras

- **TheyWorkForYou (mySociety)** [P]: debate público da redação de cada voto "for clarity as well as to expunge any bias"; não especula sobre consciência ou orientação de bancada que não esteja no registro; ausência não entra no cálculo de apoio a uma política, mas **muitas ausências impedem os rótulos mais fortes** ("more than ⅓ of votes being absences prevents a score of 85%+"); compara cada parlamentar com colegas do mesmo partido que tiveram as mesmas votações e sinaliza diferença acima de 60/40. Ponto de alerta: eles chamam isso de "rebellions", termo que o projeto não deve usar.
- **Basômetro do Estadão** [P, GitHub]: mede governismo como votos iguais à orientação do líder do governo sobre votos dados; abstenção e obstrução contam como não governistas; só votações com orientação explícita; código aberto para reprodução. A visualização, segundo fonte secundária, põe cada deputado como círculo na cor do partido, mais alto quanto mais governista [S]. Transferível: transparência de método e código. Não transferível: a régua vertical por pessoa é um ranking visual.
- **Padrão Digital de Governo (gov.br)** [S]: fonte Rawline e azul "Blue Warm Vivid 70" como identidade do governo federal. **Armadilha**: um site que mostra dados oficiais e se parece com o gov.br pode ser lido como oficial. Evitar Rawline e o azul gov.br como cor primária [I].
- **Nexo, Folha e g1** [NV]: não achei documentação primária de estilo, só referências a equipes e uso de R no Nexo. Não recomendo basear decisões nelas sem análise visual direta.

### 2.10 Guias de ofício

- **Fontes para gráficos** [P, Datawrapper]: sans por padrão, algarismos alinhados e tabulares, regular ou medium nas anotações, evitar condensadas demais no texto; The Economist usa Econ Sans e Econ Sans Condensed, Bloomberg usa Neue Haas Grotesk.
- **Cor em guias de estilo** [P, Datawrapper]: NPR reserva vermelho e azul só para partido; Quartz restringe paleta para que "it should be very hard to screw up a chart"; McKinsey usa só azul e vizinhos; Economist usa cinzas quentes. O ponto comum é **poucas cores com papéis fixos**.

---

## 3. Princípios de design para o Mandato Aberto

Cada princípio cita a referência e diz como aparece no perfil e na página de votação.

**P1. Todo número carrega a sua nota.** Apple, página de meio ambiente (https://www.apple.com/environment/) e o sistema de `footnote` do apple.com. Número grande, frase curta, numeral de nota; a nota leva à fonte oficial e ao método. *Perfil:* "412 de 450 votações nominais¹", com a nota "¹ Câmara dos Deputados, API de Dados Abertos, votações nominais de 01/02/2027 a 30/09/2027; método em /metodologia#presenca". *Votação:* placar com nota para a página oficial da votação.

**P2. A fonte fica dentro do enquadramento, não num rodapé distante.** Our World in Data (https://ourworldindata.org/redesigning-our-interactive-data-visualizations). Todo gráfico tem título, subtítulo, rodapé com fonte e data de coleta, e esse conjunto vai junto quando exportado. *Perfil e votação:* a imagem exportada de qualquer gráfico se sustenta sozinha num print.

**P3. Título afirma um fato contável, não uma interpretação.** FT, Burn-Murdoch (https://lab.imedd.org/en/from-data-to-storytelling-concept-and-design-tips-from-the-financial-times-john-burn-murdoch/) e HIG *Charts* (https://developer.apple.com/design/human-interface-guidelines/charts). *Perfil:* "Votou igual à orientação do governo em 280 de 390 votações em que o governo orientou". *Votação:* "Aprovada com 312 votos Sim; o mínimo exigido era 308".

**P4. Escala tipográfica curta, saltos grandes.** apple.com (CSS de https://www.apple.com/iphone/). Oito a dez tamanhos, peso 600 nos títulos, tracking por tamanho, algarismos tabulares em todo número. *Perfil:* nome em display, "n" do indicador em display, "de m" no mesmo baseline em tamanho de corpo e cor secundária.

**P5. "n de m" é a unidade visual do produto.** Briefing do projeto, apoiado pela HIG ("prefer common chart types", eixo de 0 a 100% quando os limites têm significado). O "m" sempre visível; percentual só como informação secundária. *Perfil:* barra de unidades em que cada célula é uma votação. *Votação:* barra de 513 células com o marcador do quórum exigido.

**P6. Codificar voto por posição e forma; cor só reforça.** HIG *Charts* ("avoid relying solely on color") e NPR via Datawrapper (https://www.datawrapper.de/blog/colors-for-data-vis-style-guides). Sim acima da linha de base, Não abaixo, Abstenção e Obstrução com marca própria, ausência como lacuna. Sem verde e vermelho. *Perfil:* a "partitura" funciona em preto e branco e em impressão. *Votação:* pontos cheios, vazados e hachurados, com rótulo textual.

**P7. Uma cor de acento, com um único papel.** HIG *Color* ("avoid using the same color to mean different things", https://developer.apple.com/design/human-interface-guidelines/color) e Spotify Wrapped 2025 ("selective pops of color used only for key moments"). O acento marca interação (link, foco, seleção) e o elemento destacado pelo usuário, nunca uma opção de voto nem um partido. *Votação:* o acento acende apenas o deputado buscado.

**P8. Cor gerada por regra perceptual, com contraste garantido por construção.** Stripe (https://stripe.com/blog/accessible-color-systems) e Linear (https://linear.app/now/how-we-redesigned-the-linear-ui). Escala em OKLCH; regra "cinco degraus de distância = 4,5:1"; tema escuro gerado das mesmas três variáveis. *Perfil:* modo escuro sem cor solta.

**P9. A foto oficial é um objeto intocável, como os Activity rings.** HIG *Activity rings* (https://developer.apple.com/design/human-interface-guidelines/activity-rings). Mesmo enquadramento 3:4, mesmo passe-partout, nenhum filtro, nenhuma ampliação além do tamanho nativo, crédito sempre. A interface se adapta à foto. *Perfil:* foto em ~177 × 236 px CSS; no modo escuro, o passe-partout fica claro.

**P10. A página inteira precisa funcionar sem interação e sem animação.** HIG *Charts* ("don't require interaction to reveal critical information") e HIG *Motion*. Inertia com SSR entrega o HTML completo; hover e scrub são camada extra; `prefers-reduced-motion` troca transições por fade. *Votação:* a lista completa de nomes por opção existe como tabela, não só como pontos.

**P11. Todo gráfico tem aba Tabela, aba Fontes e Download.** Our World in Data (Grapher). *Perfil:* "Ver como tabela" e "Baixar CSV/JSON" ao lado da partitura. *Votação:* CSV da votação no topo, não escondido em rodapé.

**P12. O método é parte do layout.** The Pudding (https://pudding.cool/process/how-to-make-dope-shit-part-3/) e TheyWorkForYou (https://research.mysociety.org/html/2024-voting-records/). Cada indicador tem "como calculamos" a um clique, incluindo o que conta no denominador (por exemplo, se obstrução conta, como no Basômetro, ou não). *Perfil:* ícone de método ao lado de cada indicador.

**P13. Comparar com o grupo, não ordenar o grupo.** TheyWorkForYou (comparação com colegas de partido que tiveram as mesmas votações) e FT Visual Vocabulary (https://github.com/Financial-Times/chart-doctor/tree/main/visual-vocabulary), usando "distribuição" e não "ranking". *Perfil:* "Votou igual à maioria do seu partido em 371 de 401". *Visão geral:* histograma sem nomes; a busca coloca um marcador onde a pessoa está.

**P14. Consistência entre a versão pequena e a grande do mesmo dado.** HIG *Charting data* (https://developer.apple.com/design/human-interface-guidelines/charting-data), exemplo das tendências do Saúde. A partitura no card, na lista de busca e no perfil usa a mesma codificação; quem aprende uma, lê todas.

**P15. Tokens únicos para tela e imagem.** Spotify Encore (https://www.figma.com/blog/creating-coherence-how-spotifys-design-system-goes-beyond-platforms/) e o pipeline de share cards do Wrapped (https://engineering.atspotify.com/2022/3/jordan-loeser-web-engineer). O gerador de cards consome os mesmos tokens e as mesmas fontes do app, e o template é testado com nomes parlamentares de mais de 30 caracteres e números de quatro dígitos.

---

## 4. Movimentos Apple/Spotify que são armadilha num site cívico

| Movimento | Por que é armadilha aqui | Adaptação segura |
|---|---|---|
| **"Seu ano em" no estilo Wrapped**, com tom de celebração e revelação surpresa | O Wrapped funciona por lacuna de curiosidade, recompensa variável e identidade [S, growth.design]. Aplicado a político, a "revelação" vira veredito e a festa vira campanha | **"O mandato até [data]"**: mesmo conteúdo para todos, sem suspense, sem tela de "pronto?" e sem trilha emocional. Periodicidade fixa (mensal ou por sessão legislativa), não um evento de marketing |
| **Índice sintético por pessoa** (nota, idade, persona, aura) | "Listening age" gerou reação negativa mesmo sendo lúdico [S, NPR]. Um índice único esconde o "m" e convida a ordenar | Só indicadores primários em "n de m", cada um com método. Nenhuma composição de indicadores numa nota única |
| **Rótulos de persona** ("perfil articulador", "tipo X") | Adjetivo disfarçado; proibido em `AGENTS.md` | Frases-modelo factuais preenchidas por template, nunca geradas por IA sobre a pessoa (decisão 11) |
| **Contador animado** (números subindo até o valor) | É "hype" de produto; atrasa a leitura; a HIG pede movimento breve e cancelável [P] | Número renderizado no SSR já final. No máximo um fade de 150–200 ms, desligado com reduzir movimento |
| **Tremor, pulso, ponteiro nervoso** | A agulha do NYT foi desligada após ser chamada de "irresponsible" [S] | Estados estáveis; mudança de dado marcada por texto ("atualizado em 30/09/2026 às 06:00") e não por animação |
| **Cor própria por parlamentar** no card (aura, gradiente gerado) | Um card fica mais bonito que outro; cor vira juízo ou associação partidária | Template fixo e idêntico para todos; só o conteúdo muda |
| **Cores de partido** como codificação principal | Mais de 20 partidos, cores repetidas, e cor vira bandeira; NPR reserva vermelho/azul para partido e evita em outros temas [S] | Partido como texto (sigla). Se for preciso agrupar, usar ordem alfabética e rótulo, não cor |
| **Verde = Sim, vermelho = Não** | Carrega "certo/errado" e falha em daltonismo | Posição e forma (P6), tinta neutra; texto sempre presente |
| **Duotone, recorte de fundo, foto sangrando em tela cheia** | Altera a foto oficial; a foto da Câmara tem 354 × 472 px [P], então ampliar degrada ou exige upscaling | Foto no tamanho nativo, passe-partout fixo; o herói é tipográfico |
| **Marketing glossy** ("revolucionário", "incrível") | Tom de propaganda; aproxima o site de material de campanha | Voz de documentação: frases curtas, verbo no indicativo, datas e números |
| **Gamificação** (selos, metas, "complete o perfil", "You Draw It" sobre político) | Transforma acompanhamento em jogo de opinião; Activity rings só funcionam porque a meta é do próprio usuário | Nada de meta, selo ou quiz sobre parlamentar. Interação só para encontrar e verificar |
| **Prova social** ("12 mil pessoas compartilharam este card") | Contagem de compartilhamento vira termômetro de popularidade, perto de "intenção de voto" | Não exibir contagem de compartilhamentos nem de visualizações por parlamentar |
| **Narrativa no título que interpreta** (estilo FT) | "Fulano se afasta do governo" é interpretação | Título com fato contável e período (P3); a interpretação fica para o leitor |
| **Parecer oficial** (azul gov.br, Rawline, brasão) | Confusão com fonte oficial, risco de impersonação | Identidade própria; o link "fonte oficial" é o único lugar com marca da Câmara/Senado, em texto |
| **Scroll-jacking e páginas que só funcionam rolando** | Quebra acessibilidade e leitura por citação; HIG proíbe exigir interação para informação crítica | Scrollytelling só em peças editoriais longas (retrospectiva da legislatura), com versão estática equivalente |
| **Resumo de IA sem moldura** | Apple suspendeu resumos de notícias após manchetes falsas [S, Axios] | Bloco visualmente distinto, rótulo "Resumo gerado por IA a partir do texto oficial, revisado em DD/MM/AAAA", link ao inteiro teor e ao formulário de correção |

---

## 5. Três direções visuais candidatas

Todas as fontes abaixo estão em `google/fonts/ofl/` com `OFL.txt` (SIL Open Font License 1.1, uso web livre e auto-hospedagem permitida) [P]. Eixos conferidos nos metadados do Google Fonts [P].

### A. "Diário"

Evolução direta do MVP. Papel quente, tinta quase preta, serifa editorial nos títulos e nos números grandes, sans nos dados e na interface. Lê como jornal de referência e como documento, que é o registro certo para um site que pode ser contestado. A sensação "Apple" vem do rigor: escala curta, notas de rodapé, espaço generoso, e nenhum ornamento.

- **Tipografia:** Newsreader (Production Type; `opsz` 6–72, `wght` 200–800) para títulos, números display e resumos de IA; Inter (Rasmus Andersson; `opsz` 14–32, `wght` 100–900) para interface, tabelas e rótulos, com `tnum`. Alternativa de serifa: Source Serif 4 (já no MVP; `opsz` 8–60).
- **Cor:** papel `#f7f5f0` / tinta `#1a1a1a` (MVP) e um acento de tinta azul-petróleo ou cobalto escuro em vez do vermelho atual [I, testar]. Modo escuro "papel noturno" (fundo grafite quente, não preto puro).
- **Elemento de assinatura:** notas de rodapé numeradas em todo número, no estilo apple.com/environment, com a nota abrindo em painel lateral sem sair da página.
- **Prós:** continuidade com o MVP e o que já foi validado; serifas com `opsz` ficam muito bem em 80–120 px; tom de credibilidade.
- **Contras:** é a direção mais próxima do "NYT genérico"; o modo escuro com papel quente dá mais trabalho; menos "uau" no primeiro segundo.

### B. "Instrumento"

A direção mais próxima do apple.com e da Linear. Neutra, fria, precisa, com o número como herói em sans de display enorme e muito ar. Seções alternam claro e quase-preto como as páginas de produto da Apple. O dado parece medido por um instrumento, sem voz editorial.

- **Tipografia:** Inter Display (via `opsz` alto da Inter) para display, Inter para texto, Geist Mono (Vercel; `wght` 100–900) ou IBM Plex Mono para códigos de votação, datas e IDs. Alternativa: Geist + Geist Mono, mesma família.
- **Cor:** neutros tipo Apple (`#1d1d1f`, `#6e6e73`, `#f5f5f7`) gerados em OKLCH com a regra de três variáveis da Linear; um acento frio saturado só para interação. Modo escuro de primeira classe, com base e elevado como na HIG.
- **Elemento de assinatura:** a "partitura do mandato" em largura total no herói do perfil, desenhada uma vez da esquerda para a direita em menos de 600 ms (desligado com reduzir movimento).
- **Prós:** a leitura mais "Apple"; escala bem para app logado (entrega 2: "meus eleitos", alertas); modo escuro natural.
- **Contras:** Inter em tudo é o visual padrão de SaaS de 2020–2026 e pode parecer genérico sem um detalhe próprio forte [I]; risco de frieza que afasta o leitor comum.

### C. "Plenário"

A direção mais próxima do Spotify Mix e de cartaz. Uma família variável com eixo de largura faz o trabalho de duas: números enormes e condensados no card e no herói, texto em largura normal. Preto e branco de alto contraste com um acento só. É a que mais circula por print.

- **Tipografia:** Archivo (Omnibus-Type, fundição argentina; `wdth` 62–125, `wght` 100–900) para display e interface; Source Serif 4 para os resumos de IA e textos longos, que pedem leitura calma. Alternativa de display: Mona Sans (GitHub; `wdth` 75–125).
- **Cor:** branco e preto, cinzas quentes de contexto (como The Economist), um acento só. Modo escuro quase igual ao claro invertido, porque a paleta é acromática.
- **Elemento de assinatura:** o "plenário de pontos" (513 na Câmara, 81 no Senado) como motivo recorrente: no topo de cada votação, em miniatura no card e na visão geral.
- **Prós:** a mais distinta e memorável; cards fortes em 9:16; o eixo de largura resolve nomes longos no card sem trocar de fonte.
- **Contras:** caixa-alta condensada e preta lembra santinho e cartaz de campanha [I]; exige disciplina (nunca caixa-alta no nome do parlamentar, nunca peso Black em frase sobre pessoa); paleta acromática deixa o acento carregar muito peso.

**Recomendação [I]:** prototipar **A com o elemento de assinatura de B** (Newsreader + Inter, com a partitura e as notas de rodapé) e **C** como contraste no artifact da feature de design. B sozinho tende ao genérico; A sozinho tende ao "jornal de sempre".

---

## 6. Ideias para o momento "uau, isso é bom, vou usar"

### 6.1 Herói do perfil

- **Composição:** à esquerda, a foto oficial em 3:4 no tamanho nativo, num passe-partout com "Foto: Câmara dos Deputados" embaixo. À direita, o nome em display, linha de metadados em sans ("Partido · UF · em exercício desde 01/02/2027"), e uma **frase-resumo por template**, não por IA: "Participou de 412 de 450 votações nominais¹ e apresentou 23 proposições² desde 01/02/2027."
- **"n de m" tipográfico:** "412" em display, "de 450" no mesmo baseline em corpo e cor secundária; abaixo, uma barra de 450 células finas, cada uma uma votação, clicável.
- **A partitura do mandato** em largura total, logo abaixo: uma coluna de 2–3 px por votação nominal em ordem cronológica; Sim sobe, Não desce, Abstenção e Obstrução têm marca curta própria, "não votou" é lacuna. Eixo de meses embaixo. Hover ou foco por teclado mostra data, proposição e voto; clique abre a votação. Uma camada opcional sobrepõe a orientação do governo ou do partido onde houve, e o texto ao lado diz "igual à orientação em 280 de 390 votações em que houve orientação".
- **O detalhe que faz voltar:** um link "citar este perfil" que copia uma referência com URL, data de coleta e fonte, pronta para matéria ou trabalho escolar.
- **Sem foto disponível:** espaço reservado neutro com as iniciais, nunca uma silhueta genérica que pareça foto.

### 6.2 Página de votação ("como cada um votou")

- **Topo:** ementa oficial curta; bloco "O que estava em jogo", rotulado como resumo de IA revisado, com link para o inteiro teor; linha de resultado com o quórum: "Aprovada · 312 Sim · 141 Não · 3 Abstenção · 57 não votaram · mínimo exigido: 308 votos Sim (PEC)".
- **Barra de quórum:** 513 células numa linha, preenchidas por opção, com um marcador vertical no 308. Dá para ver em um segundo se passou e por quanto, sem adjetivo.
- **O plenário em pontos:** 513 pontos agrupados em colunas por opção de voto; dentro de cada coluna, por partido em ordem alfabética, com a sigla como rótulo. Um campo **"encontre um deputado"** acende o ponto e rola até ele; filtros por UF e partido apagam os demais sem removê-los. Botões alternam a organização (por voto, por partido, por UF); os pontos mantêm identidade e se movem entre arranjos (constância de objeto), com fade simples sob reduzir movimento.
- **Tabela de orientações:** para cada partido, a orientação registrada e "seguiram 45 de 48". A expressão "votou diferente da orientação do partido" substitui "rebelde" ou "traidor".
- **Utilitários no topo, não no rodapé:** "Baixar CSV / JSON", "Link permanente", "Citar", "Ver na Câmara" (fonte oficial).
- **Acessibilidade:** a visualização tem uma tabela equivalente com todos os nomes por opção, e cada ponto tem rótulo acessível ("Nome, Partido-UF, votou Não").

### 6.3 Card de compartilhamento

- **Três formatos do mesmo template:** 1200 × 630 (Open Graph), 1080 × 1350 (feed) e 1080 × 1920 (stories) [I; tamanhos usuais de plataforma, não conferidos na documentação de cada rede].
- **Conteúdo fixo e igual para todos:** foto sem alteração, nome, partido-UF, até quatro indicadores em "n de m", a partitura em miniatura, "Fonte: Câmara dos Deputados · dados de 30/09/2026", e o endereço curto.
- **Código de verificação:** cada card traz um código curto (por exemplo, "MA·2027-09-30·204553") que leva ao retrato exato dos dados daquela data. Um print antigo continua verificável depois que os números mudarem. É o mecanismo de confiança que o Wrapped não precisa e o projeto precisa.
- **Card de votação por UF:** "Como votaram os 70 deputados de SP na PEC X", com o mini plenário. É o formato mais compartilhável sem personalizar julgamento.
- **Nunca no card:** comparação com outros parlamentares, percentil, cor própria por pessoa, frase de IA, contagem de compartilhamentos.
- **Engenharia:** o template é desenhado com nomes longos e números de quatro dígitos desde o primeiro dia, como fez a equipe do Wrapped com 30 idiomas, e consome os mesmos tokens do app (P15).

### 6.4 Visão geral da legislatura

- **Fileira de números com nota**, no estilo apple.com/environment: votações nominais realizadas, proposições apresentadas, leis sancionadas, medidas provisórias editadas, vetos, cada um com numeral de nota e fonte.
- **Calendário de votações:** um quadrado por dia de sessão com votação nominal, intensidade pelo número de votações; clique abre as votações do dia.
- **Distribuição, não lista:** histograma de participação em votações nominais sem nomes; a busca "onde está [nome]" põe um marcador na barra. **Precisa de decisão registrada em `.specs/STATE.md`**, porque mesmo sem nomes é uma comparação entre pessoas [I].
- **Fluxos:** proposições apresentadas → aprovadas em uma Casa → aprovadas nas duas → sancionadas ou vetadas; para a Presidência, MPs editadas → convertidas em lei ou perderam eficácia, e vetos → mantidos ou derrubados. Categoria "fluxo" do FT Visual Vocabulary.
- **Retrospectiva editorial opcional:** uma peça longa por sessão legislativa em scrollytelling (Scrollama), com versão estática equivalente e sem nenhuma pessoa em destaque.

---

## 7. Tokens iniciais sugeridos para a feature de design [I]

Ponto de partida para o artifact, a ser validado com as fontes escolhidas:

- **Tipografia (px / entrelinha):** 80/1,05 · 56/1,07 · 40/1,1 · 28/1,14 · 21/1,19 · 17/1,47 (corpo) · 14/1,43 · 12/1,33. Pesos 400 e 600 (700 só no display serifado). `font-variant-numeric: tabular-nums lining-nums` em todo número. Separador de milhar "1.234" (pt-BR).
- **Larguras:** três layouts com quebras em 1068 e 734 px, como no apple.com; coluna de leitura de 640–700 px para resumos.
- **Espaçamento:** base de 4 px, ritmo de 8 px; seções separadas por 80–120 px no desktop.
- **Cor:** escala de neutros em OKLCH com 10–12 degraus e regra de distância de cinco degraus para texto; um acento; um "papel" para o modo claro e um "grafite" para o escuro; nenhuma cor semântica de "bom" ou "ruim".
- **Movimento:** 150–250 ms, ease-out, só fade e transições de posição dos pontos; tudo sob `prefers-reduced-motion`.
- **Acessibilidade:** WCAG 2.2 AA como piso (4,5:1 texto, 3:1 para elementos gráficos e foco), meta de 7:1 em texto pequeno (HIG), alvo de toque de 44 px.
- **Performance:** fontes variáveis auto-hospedadas em WOFF2 com subconjunto `latin` + `latin-ext`, `font-display: swap`; SSR do Inertia entrega números finais no HTML.

---

## 8. Fontes

**Apple**

- Apple. *Human Interface Guidelines: Typography.* https://developer.apple.com/design/human-interface-guidelines/typography (lido via https://developer.apple.com/tutorials/data/design/human-interface-guidelines/typography.json)
- Apple. *HIG: Charting data.* https://developer.apple.com/design/human-interface-guidelines/charting-data
- Apple. *HIG: Charts.* https://developer.apple.com/design/human-interface-guidelines/charts
- Apple. *HIG: Dark Mode.* https://developer.apple.com/design/human-interface-guidelines/dark-mode
- Apple. *HIG: Color.* https://developer.apple.com/design/human-interface-guidelines/color
- Apple. *HIG: Accessibility.* https://developer.apple.com/design/human-interface-guidelines/accessibility
- Apple. *HIG: Motion.* https://developer.apple.com/design/human-interface-guidelines/motion
- Apple. *HIG: Activity rings.* https://developer.apple.com/design/human-interface-guidelines/activity-rings
- Apple. *iPhone* (página e CSS de produção). https://www.apple.com/iphone/ e https://www.apple.com/v/iphone/home/ck/built/styles/overview.built.css
- Apple. *Environment.* https://www.apple.com/environment/
- Axios. *Apple pauses AI-generated news alerts after fake headline notifications* (17/01/2025). https://www.axios.com/2025/01/17/apple-ai-news-alerts-fake-headlines

**Spotify**

- Spotify Newsroom. *Introducing Spotify Mix, our new and exclusive font* (22/05/2024). https://newsroom.spotify.com/2024-05-22/introducing-spotify-mix-our-new-and-exclusive-font/
- It's Nice That. *Spotify launches new bespoke typeface with Dinamo.* https://www.itsnicethat.com/articles/spotify-dinamo-new-typeface-spotify-mix-project-230524
- Figma Blog. *Creating coherence: how Spotify's design system goes beyond platforms.* https://www.figma.com/blog/creating-coherence-how-spotifys-design-system-goes-beyond-platforms/
- Spotify Design (Medium). *Reimagining Design Systems at Spotify* (403 nesta sessão). https://medium.com/spotify-design/reimagining-design-systems-at-spotify-2fe20fbb3552
- Spotify Engineering. *Jordan Loeser: Web Engineer* (share cards do Wrapped). https://engineering.atspotify.com/2022/3/jordan-loeser-web-engineer
- Spotify Engineering. *Exploring the Animation Landscape of 2023 Wrapped.* https://engineering.atspotify.com/2024/01/exploring-the-animation-landscape-of-2023-wrapped
- Spotify Selects. *Designing 2025 Wrapped: Turning a Year of Listening into Art.* https://spotifyselects.substack.com/p/designing-2025-wrapped-turning-a
- Spotify Newsroom. *Rasmus Wangelin explains the creative behind 2021 Wrapped.* https://newsroom.spotify.com/2021-12-01/global-head-of-brand-design-rasmus-wangelin-explains-the-creative-behind-spotify-2021-wrapped/
- It's Nice That. *"No grid, no rules": Wrapped 2023.* https://www.itsnicethat.com/features/spotify-wrapped-campaign-identity-2023-graphic-design-301123
- growth.design. *Spotify Wrapped: 6 psychology principles.* https://growth.design/case-studies/spotify-wrapped-psychology
- NPR. *How old is your music taste? Spotify will tell you* (04/12/2025). https://www.npr.org/2025/12/04/nx-s1-5632595/spotify-wrapped-listening-age
- Design Week. *Spotify undergoes colourful brand refresh* (2015). https://www.designweek.co.uk/issues/9-15-march-2015/spotify-undergoes-colourful-brand-refresh/

**Jornalismo de dados**

- Financial Times. *Visual Vocabulary.* https://github.com/Financial-Times/chart-doctor/tree/main/visual-vocabulary
- iMEdD Lab. *From data to storytelling: tips from the FT's John Burn-Murdoch.* https://lab.imedd.org/en/from-data-to-storytelling-concept-and-design-tips-from-the-financial-times-john-burn-murdoch/
- Datawrapper. *A detailed guide to colors in data vis style guides.* https://www.datawrapper.de/blog/colors-for-data-vis-style-guides
- Datawrapper. *Which fonts to use for your charts and tables.* https://www.datawrapper.de/blog/fonts-for-data-visualization
- Our World in Data. *Redesigning our interactive data visualizations.* https://ourworldindata.org/redesigning-our-interactive-data-visualizations
- Our World in Data. CSS de produção. https://ourworldindata.org/assets/owid.css
- The Pudding. *Making Internet Things, part 3: Storytelling.* https://pudding.cool/process/how-to-make-dope-shit-part-3/
- The Pudding. *How to implement scrollytelling with six different libraries.* https://pudding.cool/process/how-to-implement-scrollytelling/
- Fast Company. *The most hated data visualization in politics is back.* https://www.fastcompany.com/90459366/the-most-hated-data-visualization-in-politics-is-back-to-spike-your-blood-pressure
- vis4.net. *Why we used jittery gauges in our live election forecast.* http://www.vis4.net/blog/jittery-gauges-election-forecast/
- Washington Post. *How every House member voted on the bill to reopen the government* (403 nesta sessão). https://www.washingtonpost.com/politics/interactive/2025/11/12/how-every-house-member-voted-bill-reopen-government/
- Digiday. *How Bloomberg's 20-person graphics team visualizes the news.* https://digiday.com/media/bloomberg-graphics-team/

**Produto web**

- Linear. *How we redesigned the Linear UI (part II).* https://linear.app/now/how-we-redesigned-the-linear-ui
- Stripe. *Designing accessible color systems.* https://stripe.com/blog/accessible-color-systems

**Cívico e Brasil**

- mySociety. *TheyWorkForYou strives to be unbiased, reliable and truthful. Here's how.* https://www.mysociety.org/2017/09/19/theyworkforyou-strives-to-be-unbiased-reliable-and-truthful-heres-how/
- mySociety. *TheyWorkForYou's voting summaries: 2024 revision.* https://research.mysociety.org/html/2024-voting-records/
- mySociety. *Updating TheyWorkForYou's voting summaries* (2025). https://www.mysociety.org/2025/03/13/updating-theyworkforyous-voting-summaries/
- Estadão. *Basômetro* (código e método). https://github.com/estadao/basometro
- Governo Federal. *Padrão Digital de Governo: cores.* https://www.gov.br/ds/fundamentos-visuais/cores

**Fontes tipográficas (licença e eixos)**

- Google Fonts, metadados. https://fonts.google.com/metadata/fonts
- Repositório `google/fonts`, diretórios `ofl/inter`, `ofl/newsreader`, `ofl/sourceserif4`, `ofl/archivo`, `ofl/ibmplexsans`, `ofl/ibmplexmono`, `ofl/instrumentserif`, `ofl/geist`, `ofl/geistmono`, `ofl/fraunces`, `ofl/publicsans`, `ofl/atkinsonhyperlegiblenext`, todos com `OFL.txt`. https://github.com/google/fonts/tree/main/ofl

**Repositório (contexto)**

- `AGENTS.md`, `research/02-grilling-escopo-mvp.md`, `research/04-direcao-v2.md`, `research/05-grilling-escopo-v2.md`, `site/src/styles/` e `site/.cache/photos/` (dimensões das fotos oficiais).
