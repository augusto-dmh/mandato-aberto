# Pesquisa de design e concorrentes — como a v2 se diferencia

**Data:** 30/09/2026 · **Entrada:** decisões de `05-grilling-escopo-v2.md`; pedido do mantenedor por design "nível Apple" e não genérico · **Método:** três frentes em paralelo, cada uma com fontes primárias abertas quando possível e marca de certeza por afirmação. Relatórios completos em [`design-anexos/`](design-anexos/):

| Anexo | Frente | Cobertura |
|---|---|---|
| [`a1-referencias-de-design.md`](design-anexos/a1-referencias-de-design.md) | Referências de design | Apple (apple.com, HIG, página de meio ambiente), Spotify (Encore, Mix, Wrapped), FT, NYT, The Pudding, Our World in Data, Bloomberg, Linear, Stripe |
| [`a2-concorrentes-brasil.md`](design-anexos/a2-concorrentes-brasil.md) | Concorrentes no Brasil | cerca de 35 ferramentas: Placar Político, Ranking dos Políticos, Radar do Congresso, Deputômetro, Basômetro, portais oficiais, projetos mortos |
| [`a3-pares-internacionais.md`](design-anexos/a3-pares-internacionais.md) | Pares internacionais | cerca de 20 projetos: TheyWorkForYou, GovTrack, HowTheyVote.eu, Abgeordnetenwatch, They Vote For You, ProPublica Represent, Plenarwatch, oParlamento.pt |

**Aviso:** pesquisa feita por IA. Os relatórios marcam o que foi conferido na fonte primária e o que não foi. Várias páginas bloquearam acesso automatizado; as notas visuais delas vêm de fonte secundária.

## 1. Veredito

**O campo brasileiro está ocupado por notas e rankings.** Placar Político (nota de 1 a 99), Ranking dos Políticos (0 a 10, conselho que diz "não somos neutros"), Tô De Olho, Fiscalize o Poder e o DIAP pontuam ou curam votações por critério de valor. O Radar do Congresso conta ausência como "não seguiu o governo". Ninguém oferece um registro recalculável, sem nota, das duas Casas e da Presidência, com uma página por votação.

**Lá fora, os projetos que duram fizeram o caminho inverso.** O GovTrack retirou os boletins anuais em 2024 depois que "senador mais liberal" virou material de campanha. O TheyWorkForYou refez os resumos de voto em 2024 por serem manipuláveis. O They Vote For You foi processado por parlamentares por causa de resumos temáticos. O HowTheyVote.eu, sem nota nem ranking, é a referência mais próxima do que a v2 quer ser.

**Design "nível Apple" é rigor, não brilho.** O que transfere da Apple é a escala tipográfica curta, a nota de rodapé em cada número, o contraste garantido e a regra de nunca alterar o objeto oficial, como os Activity rings. O que não transfere do Spotify é a psicologia do Wrapped: suspense, recompensa e rótulo de identidade aplicados a político viram veredito.

## 2. Posicionamento: "registro, não nota"

Oito movimentos, cada um ligado a uma lacuna que ninguém cobre bem e a dado oficial que já existe.

| # | Movimento | Lacuna | Prova de que funciona |
|---|---|---|---|
| 1 | **Registro, não nota.** Todo indicador em "n de m", com o denominador visível e cada votação clicável. Nenhuma composição em nota única | Todos os brasileiros pontuam ou curam | HowTheyVote.eu; retirada dos boletins do GovTrack |
| 2 | **A votação é a unidade principal.** Uma página por votação com placar, quórum exigido, orientações, "o que estava em jogo" e utilitários no topo | O portal oficial lista por sessão; nenhum concorrente explica a votação | HowTheyVote.eu, oParlamento.pt |
| 3 | **Do Planalto ao plenário.** Medidas provisórias e vetos ligados a como cada parlamentar votou neles | Ninguém liga Executivo e voto individual | lacuna própria do Brasil |
| 4 | **Desde o primeiro dia da 58ª.** Continuidade entre legislaturas a partir de 01/02/2027 | Deputômetro só cobre a 57ª; Basômetro parou em 2022 | — |
| 5 | **Dados abertos para quem escreve.** CSV e JSON com fonte e data de coleta, "citar este perfil", despejos estáticos completos | Deputômetro sem exportação; APIs de pares fechando | Our World in Data; OpenSecrets e ProPublica fecharam a API |
| 6 | **Alertas das duas Casas, sem nota.** E-mail sobre eleitos e votações | App da Câmara só cobre a Câmara; Placar dá nota | Placar, app da Câmara, TheyWorkForYou |
| 7 | **Correção pública e nota do gabinete.** Registro aberto de correções e espaço moderado de resposta do parlamentar | Ninguém publica correções | Abgeordnetenwatch (código de moderação e conselho) |
| 8 | **Card verificável.** Cada card de compartilhamento traz um código que abre os dados daquela data | Prints antigos circulam sem como conferir | ideia própria do relatório de design |

**O que precisa igualar os concorrentes:** Câmara e Senado, busca por nome, UF e partido, data de atualização visível, metodologia, tema claro e escuro, comparar parlamentares (entrega 3), alertas (entrega 2), visão por partido numa votação e página leve. A home do MVP tem cerca de 311 KB; Meu Congresso tem 33 KB e Placar, 40 KB.

## 3. Princípios de design

Resumo dos quinze princípios do anexo A1, seção 3, que entram como critérios na feature de sistema de design.

1. **Todo número carrega a sua nota**, que leva à fonte oficial e ao método. Modelo: apple.com/environment.
2. **A fonte fica dentro do enquadramento** do gráfico e vai junto no print. Modelo: Our World in Data.
3. **O título afirma um fato contável**, nunca uma interpretação.
4. **Escala tipográfica curta com saltos grandes**, algarismos tabulares em todo número.
5. **"n de m" é a unidade visual do produto**; percentual só como informação secundária.
6. **Voto codificado por posição e forma.** Sim sobe, Não desce, abstenção e obstrução têm marca própria, "não registrou voto" é lacuna. Nunca verde e vermelho.
7. **Um acento com um único papel:** interação e o elemento que a pessoa buscou. Nunca opção de voto, nunca partido.
8. **Cor gerada por regra perceptual**, com contraste garantido por construção nos dois temas.
9. **A foto oficial é intocável:** 3:4, tamanho nativo, sem filtro, sem recorte, crédito sempre. As fotos da Câmara têm 354 × 472 px, então o herói do perfil é tipográfico.
10. **A página funciona sem interação e sem animação.** SSR entrega o número final; movimento respeita `prefers-reduced-motion`.
11. **Todo gráfico tem tabela, fontes e download.**
12. **O método está a um clique** de cada indicador, incluindo o que entra no denominador.
13. **Comparar com o grupo, não ordenar o grupo.**
14. **A versão pequena e a grande do mesmo dado usam a mesma codificação.**
15. **Tela e imagem compartilhada consomem os mesmos tokens.**

**Elemento de assinatura proposto: a "partitura do mandato".** Uma coluna por votação nominal em ordem cronológica, legível em preto e branco, cada coluna ligada à votação oficial. Não ordena ninguém e é reconhecível no perfil, no card e na busca.

## 4. Armadilhas

A tabela completa está no anexo A1, seção 4. As que mais tentam:

- **"Seu ano em", ao estilo Wrapped.** Vira "o mandato até [data]", igual para todos, sem suspense.
- **Índice sintético, persona ou rótulo.** Fica só o indicador primário em "n de m".
- **Contador animado, ponteiro nervoso, prova social** ("12 mil compartilharam").
- **Cor por parlamentar ou por partido**, verde e vermelho para voto.
- **Parecer site oficial.** Nada de azul gov.br, fonte Rawline ou brasão.
- **Resumo de IA sem moldura.** A Apple suspendeu resumos de notícias em 2025 por manchetes falsas. O Politico teve ferramentas de IA desligadas por erro em 2025.
- **"Votações importantes" escolhidas a dedo.** Só por regra mecânica publicada.
- **Depender da cooperação do político.** O TemMeuVoto foi suspenso em duas semanas por falta de respostas.

## 5. Direções visuais candidatas

Todas com fontes sob SIL Open Font License, auto-hospedáveis. Detalhe no anexo A1, seção 5.

| Direção | Tipografia | Caráter | Risco |
|---|---|---|---|
| **Diário** | Newsreader + Inter | editorial, evolução do MVP, credibilidade de documento | próxima do "jornal de sempre" |
| **Instrumento** | Inter Display + Geist Mono | neutra e fria, a mais próxima do apple.com, modo escuro natural | Inter em tudo é o visual padrão de SaaS |
| **Plenário** | Archivo (eixo de largura) + Source Serif 4 | cartaz, a mais memorável, ótima em card | caixa-alta condensada lembra santinho |

**Recomendação do anexo:** prototipar **Diário com a partitura da Instrumento** e **Plenário** como contraste, nas mesmas telas e com os mesmos dados, e escolher vendo. O vermelho do MVP (`#b3261e`) deve ser revisto: lê como alarme e tem associação partidária forte no Brasil.

## 6. IA: o modelo que funciona

O Plenarwatch (Alemanha, 2025–26) é a referência: a IA escreve o texto, mas os números vêm direto do arquivo oficial, e verificações automáticas bloqueiam a publicação. Citações têm de bater palavra por palavra, nenhum nome de parlamentar fora de citação, e uma checagem de neutralidade aponta a frase problemática. A v2 soma a isso a revisão humana já decidida no grilling (decisão 11).

## 7. Sustentabilidade

Projetos morrem por abandono, não por processo: Excelências, Vote na Web, Atlas Político, Jarbas, VoteWatch, NosDéputés. Modelos que se sustentam: pequenas doações recorrentes (Abgeordnetenwatch, mais de 12.500 doadores), verba-semente para captação, braço de serviços pagos separado com dado sempre gratuito (mySociety), custo fixo baixo, conselho suprapartidário e plano de sucessão escrito. Entra no desenho da associação (decisão 7 do grilling).

## 8. Decisões que a pesquisa levanta

Nenhuma bloqueia a feature de sistema de design. Ficam para quando a feature que as usa for planejada.

| # | Decisão | Por que aparece | Quando |
|---|---|---|---|
| 1 | Bloco "Gastos" que só linka a página oficial, sem número próprio | Quase todo concorrente mostra cota e emendas; a v2 deixou dinheiro fora | plano do perfil |
| 2 | Histograma de participação sem nomes na visão geral | Mesmo sem nomes, compara pessoas | plano da visão geral |
| 3 | Classificar o tipo de votação antes de contar (requerimento, emenda, mérito) e tratar votações simbólicas | TheyWorkForYou passou a contar só "action votes"; muitas votações do plenário são simbólicas, sem voto individual | plano do ETL da v2 |
| 4 | Nota metodológica sobre por que o número de alinhamento difere do Radar | Eles contam ausência como "não seguiu" | plano do perfil |
| 5 | Nota do gabinete como direito de resposta moderado | Modelo Abgeordnetenwatch; exige associação | depois da entrega 1 |
| 6 | Checar a marca "Mandato Aberto" no INPI | Nomes parecidos no campo | antes de registrar a associação |
